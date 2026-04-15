<?php
/**
 * Gestione Utenti - Biblioteca Gobetti (Admin+)
 */

require_once '../includes/functions.php';
requireMinLevel(LIVELLO_BIBLIOTECARIO); // Bibliotecari e Admin

$db = getDB();
$message = '';
$error = '';

// Gestione azioni
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $azione = $_POST['azione'] ?? '';
    
    try {
        if ($azione === 'aggiungi_utente') {
            $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $livello = (int)$_POST['livello'];
            $classe_id = null;
            
            // Gestisci classe solo se è studente
            if ($livello === LIVELLO_STUDENTE && !empty($_POST['classe_numero'])) {
                $classe_numero = $_POST['classe_numero'];
                $sezione = strtoupper($_POST['sezione'] ?? 'A');
                $anno_scolastico = '2024-2025'; // TODO: configurabile
                
                // Cerca o crea la classe
                $stmt = $db->prepare("SELECT id FROM classi WHERE nome = ? AND sezione = ? AND anno_scolastico = ?");
                $stmt->execute([$classe_numero, $sezione, $anno_scolastico]);
                $classe_esistente = $stmt->fetch();
                
                if ($classe_esistente) {
                    $classe_id = $classe_esistente['id'];
                } else {
                    // Crea nuova classe
                    $stmt = $db->prepare("INSERT INTO classi (nome, sezione, anno_scolastico) VALUES (?, ?, ?)");
                    $stmt->execute([$classe_numero, $sezione, $anno_scolastico]);
                    $classe_id = $db->lastInsertId();
                }
            }
            
            $stmt = $db->prepare("
                INSERT INTO utenti (username, password, nome, cognome, email, livello, classe_id)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $_POST['username'],
                $password_hash,
                $_POST['nome'],
                $_POST['cognome'],
                $_POST['email'],
                $livello,
                $classe_id
            ]);
            
            logActivity($_SESSION['user_id'], 'utente_aggiunto', 'utenti', $db->lastInsertId(), 'Nuovo utente creato');
            $message = 'Utente aggiunto con successo!';
            
        } elseif ($azione === 'modifica_utente') {
            $sql = "UPDATE utenti SET nome = ?, cognome = ?, email = ?, livello = ?, classe_id = ?";
            $params = [$_POST['nome'], $_POST['cognome'], $_POST['email'], $_POST['livello'], $_POST['classe_id'] ?: null];
            
            // Se c'è una nuova password
            if (!empty($_POST['password'])) {
                $sql .= ", password = ?";
                $params[] = password_hash($_POST['password'], PASSWORD_DEFAULT);
            }
            
            $sql .= " WHERE id = ?";
            $params[] = $_POST['utente_id'];
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            
            logActivity($_SESSION['user_id'], 'utente_modificato', 'utenti', $_POST['utente_id'], 'Utente modificato');
            $message = 'Utente modificato con successo!';
            
        } elseif ($azione === 'toggle_blacklist') {
            $utente_id = $_POST['utente_id'];
            $in_blacklist = $_POST['in_blacklist'];
            
            if ($in_blacklist == '1') {
                // Rimuovi da blacklist
                rimuoviBlacklist($utente_id, $_SESSION['user_id']);
                $message = 'Utente rimosso dalla blacklist';
            } else {
                // Aggiungi a blacklist
                aggiungiBlacklist($utente_id, 'manuale', 'Aggiunto manualmente da amministratore');
                $message = 'Utente aggiunto alla blacklist';
            }
            
        } elseif ($azione === 'disattiva_utente') {
            $stmt = $db->prepare("UPDATE utenti SET attivo = 0 WHERE id = ?");
            $stmt->execute([$_POST['utente_id']]);
            
            logActivity($_SESSION['user_id'], 'utente_disattivato', 'utenti', $_POST['utente_id'], 'Utente disattivato');
            $message = 'Utente disattivato';
            
        } elseif ($azione === 'attiva_utente') {
            $stmt = $db->prepare("UPDATE utenti SET attivo = 1 WHERE id = ?");
            $stmt->execute([$_POST['utente_id']]);
            
            logActivity($_SESSION['user_id'], 'utente_attivato', 'utenti', $_POST['utente_id'], 'Utente attivato');
            $message = 'Utente attivato';
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Ottieni tutti gli utenti
$utenti = $db->query("
    SELECT u.*, c.nome as classe_nome,
           (SELECT COUNT(*) FROM prestiti WHERE utente_id = u.id AND stato IN ('attivo', 'in_ritardo')) as prestiti_attivi
    FROM utenti u
    LEFT JOIN classi c ON u.classe_id = c.id
    ORDER BY u.livello DESC, u.cognome, u.nome
")->fetchAll();

// Ottieni classi per il form
$classi = $db->query("SELECT * FROM classi WHERE attiva = 1 ORDER BY nome")->fetchAll();

$livelli_nomi = [
    100 => 'Studente',
    300 => 'Docente',
    320 => 'Bibliotecario',
    400 => 'Tecnico',
    500 => 'Collaboratore',
    600 => 'Amministrativo',
    900 => 'Dirigente'
];
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Utenti - Biblioteca Gobetti</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body data-livello="<?php echo $_SESSION['livello']; ?>">
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <h2>👥 Gestione Utenti</h2>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo e($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo e($error); ?></div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header flex-between">
                <h3 class="card-title">Utenti Registrati (<?php echo count($utenti); ?>)</h3>
                <button onclick="openModal('modal-aggiungi-utente')" class="btn btn-primary">
                    ➕ Aggiungi Utente
                </button>
            </div>
            
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>Livello</th>
                            <th>Classe</th>
                            <th>Prestiti Attivi</th>
                            <th>Stato</th>
                            <th>Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($utenti as $utente): ?>
                            <tr>
                                <td>
                                    <strong><?php echo e($utente['nome'] . ' ' . $utente['cognome']); ?></strong><br>
                                    <small>@<?php echo e($utente['username']); ?></small>
                                </td>
                                <td><?php echo e($utente['email']); ?></td>
                                <td>
                                    <span class="badge badge-info">
                                        <?php echo $livelli_nomi[$utente['livello']] ?? "Livello {$utente['livello']}"; ?>
                                    </span>
                                </td>
                                <td><?php echo e($utente['classe_nome'] ?: '-'); ?></td>
                                <td>
                                    <?php if ($utente['prestiti_attivi'] > 0): ?>
                                        <span class="badge badge-warning"><?php echo $utente['prestiti_attivi']; ?></span>
                                    <?php else: ?>
                                        <span class="badge badge-success">0</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($utente['in_blacklist']): ?>
                                        <span class="badge badge-danger">⚠️ Blacklist</span>
                                    <?php elseif (!$utente['attivo']): ?>
                                        <span class="badge badge-secondary">Disattivo</span>
                                    <?php else: ?>
                                        <span class="badge badge-success">✓ Attivo</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button onclick="openModal('modal-modifica-<?php echo $utente['id']; ?>')" class="btn btn-warning btn-sm">
                                        ✏️ Modifica
                                    </button>
                                    
                                    <?php if (hasMinLevel(LIVELLO_BIBLIOTECARIO)): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="azione" value="toggle_blacklist">
                                            <input type="hidden" name="utente_id" value="<?php echo $utente['id']; ?>">
                                            <input type="hidden" name="in_blacklist" value="<?php echo $utente['in_blacklist']; ?>">
                                            <button type="submit" class="btn <?php echo $utente['in_blacklist'] ? 'btn-success' : 'btn-danger'; ?> btn-sm">
                                                <?php echo $utente['in_blacklist'] ? '✓ Sblocca' : '⚠️ Blacklist'; ?>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="azione" value="<?php echo $utente['attivo'] ? 'disattiva_utente' : 'attiva_utente'; ?>">
                                        <input type="hidden" name="utente_id" value="<?php echo $utente['id']; ?>">
                                        <button type="submit" class="btn btn-secondary btn-sm">
                                            <?php echo $utente['attivo'] ? '🚫 Disattiva' : '✓ Attiva'; ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            
                            <!-- Modal Modifica Utente -->
                            <div class="modal" id="modal-modifica-<?php echo $utente['id']; ?>">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h3 class="modal-title">Modifica Utente</h3>
                                        <button class="modal-close" onclick="closeModal('modal-modifica-<?php echo $utente['id']; ?>')">&times;</button>
                                    </div>
                                    
                                    <form method="POST">
                                        <input type="hidden" name="azione" value="modifica_utente">
                                        <input type="hidden" name="utente_id" value="<?php echo $utente['id']; ?>">
                                        
                                        <div class="form-row">
                                            <div class="form-group">
                                                <label class="form-label">Nome</label>
                                                <input type="text" name="nome" class="form-control" required value="<?php echo e($utente['nome']); ?>">
                                            </div>
                                            <div class="form-group">
                                                <label class="form-label">Cognome</label>
                                                <input type="text" name="cognome" class="form-control" required value="<?php echo e($utente['cognome']); ?>">
                                            </div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label class="form-label">Email</label>
                                            <input type="email" name="email" class="form-control" required value="<?php echo e($utente['email']); ?>">
                                        </div>
                                        
                                        <div class="form-row">
                                            <div class="form-group">
                                                <label class="form-label">Livello</label>
                                                <select name="livello" class="form-control" required>
                                                    <?php foreach ($livelli_nomi as $liv => $nome): ?>
                                                        <option value="<?php echo $liv; ?>" <?php echo $utente['livello'] == $liv ? 'selected' : ''; ?>>
                                                            <?php echo $nome . " ($liv)"; ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label class="form-label">Classe</label>
                                                <select name="classe_id" class="form-control">
                                                    <option value="">Nessuna</option>
                                                    <?php foreach ($classi as $classe): ?>
                                                        <option value="<?php echo $classe['id']; ?>" <?php echo $utente['classe_id'] == $classe['id'] ? 'selected' : ''; ?>>
                                                            <?php echo e($classe['nome']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label class="form-label">Nuova Password (lascia vuoto per non modificare)</label>
                                            <input type="password" name="password" class="form-control">
                                        </div>
                                        
                                        <button type="submit" class="btn btn-primary btn-block">Salva Modifiche</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div style="margin-top: 30px; text-align: center;">
            <a href="../user/dashboard.php" class="btn btn-secondary">← Torna alla Dashboard</a>
        </div>
    </div>
    
    <!-- Modal Aggiungi Utente -->
    <div class="modal" id="modal-aggiungi-utente">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Aggiungi Nuovo Utente</h3>
                <button class="modal-close" onclick="closeModal('modal-aggiungi-utente')">&times;</button>
            </div>
            
            <form method="POST">
                <input type="hidden" name="azione" value="aggiungi_utente">
                
                <div class="form-group">
                    <label class="form-label">Username *</label>
                    <input type="text" name="username" class="form-control" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Nome *</label>
                        <input type="text" name="nome" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Cognome *</label>
                        <input type="text" name="cognome" class="form-control" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Email *</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Password *</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Livello *</label>
                        <select name="livello" id="livello-aggiungi" class="form-control" required>
                            <?php foreach ($livelli_nomi as $liv => $nome): ?>
                                <option value="<?php echo $liv; ?>" <?php echo $liv == 100 ? 'selected' : ''; ?>>
                                    <?php echo $nome . " ($liv)"; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <!-- Campi visibili solo per studenti -->
                <div class="form-row campi-studente">
                    <div class="form-group">
                        <label class="form-label">Classe (1-5)</label>
                        <select name="classe_numero" class="form-control">
                            <option value="">Seleziona classe</option>
                            <option value="1">1</option>
                            <option value="2">2</option>
                            <option value="3">3</option>
                            <option value="4">4</option>
                            <option value="5">5</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Sezione (A-Z)</label>
                        <input type="text" name="sezione" class="form-control" maxlength="1" pattern="[A-Za-z]" placeholder="A">
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Aggiungi Utente</button>
            </form>
        </div>
    </div>
    
    <script src="../assets/js/main.js"></script>
    <script>
        // Mostra/nascondi campi classe e sezione basato sul livello selezionato
        function toggleCampiStudente(livelloSelect, isModale) {
            const prefix = isModale ? 'modal-' : '';
            const livello = parseInt(livelloSelect.value);
            const campiStudente = document.querySelectorAll(`.${prefix}campi-studente`);
            
            campiStudente.forEach(campo => {
                if (livello === 100) { // Studente
                    campo.style.display = '';
                } else {
                    campo.style.display = 'none';
                    // Resetta i valori quando nascosti
                    const inputs = campo.querySelectorAll('input, select');
                    inputs.forEach(input => input.value = '');
                }
            });
        }
        
        // Inizializza al caricamento pagina
        document.addEventListener('DOMContentLoaded', function() {
            // Per form aggiungi
            const livelloAggiungi = document.getElementById('livello-aggiungi');
            if (livelloAggiungi) {
                toggleCampiStudente(livelloAggiungi, false);
                livelloAggiungi.addEventListener('change', function() {
                    toggleCampiStudente(this, false);
                });
            }
            
            // Per form modifica
            document.querySelectorAll('.livello-modifica').forEach(select => {
                toggleCampiStudente(select, true);
                select.addEventListener('change', function() {
                    toggleCampiStudente(this, true);
                });
            });
        });
    </script>
</body>
</html>
