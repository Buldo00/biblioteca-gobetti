<?php
/**
 * Gestione Prestiti - Biblioteca Gobetti (Bibliotecari+)
 */

require_once '../includes/functions.php';
requireMinLevel(LIVELLO_BIBLIOTECARIO);

$db = getDB();
$user = getCurrentUser();

// Gestione azioni POST
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $azione = $_POST['azione'] ?? '';
    
    try {
        if ($azione === 'crea_prestito_da_prenotazione') {
            // Crea prestito da prenotazione esistente
            $prenotazione_id = $_POST['prenotazione_id'];
            
            $stmt = $db->prepare("SELECT * FROM prenotazioni WHERE id = ? AND stato = 'attiva'");
            $stmt->execute([$prenotazione_id]);
            $prenotazione = $stmt->fetch();
            
            if (!$prenotazione) {
                throw new Exception('Prenotazione non valida');
            }
            
            $giorni_prestito = getSetting('giorni_durata_prestito', 14);
            $data_scadenza = date('Y-m-d H:i:s', strtotime("+$giorni_prestito days"));
            
            $stmt = $db->prepare("
                INSERT INTO prestiti 
                (prenotazione_id, utente_id, libro_id, dispositivo_id, tipo_prestito, 
                 data_ritiro, data_scadenza, bibliotecario_ritiro_id, check_ritiro_bibliotecario)
                VALUES (?, ?, ?, ?, ?, NOW(), ?, ?, 1)
            ");
            
            $stmt->execute([
                $prenotazione_id,
                $prenotazione['utente_id'],
                $prenotazione['libro_id'],
                $prenotazione['dispositivo_id'],
                $prenotazione['tipo_prenotazione'],
                $data_scadenza,
                $_SESSION['user_id']
            ]);
            
            // Aggiorna prenotazione
            $stmt = $db->prepare("UPDATE prenotazioni SET stato = 'ritirata' WHERE id = ?");
            $stmt->execute([$prenotazione_id]);
            
            logActivity($_SESSION['user_id'], 'prestito_creato', 'prestiti', $db->lastInsertId(), 'Prestito creato da prenotazione');
            
            $message = 'Prestito creato con successo! L\'utente deve ancora confermare il ritiro.';
            
        } elseif ($azione === 'conferma_restituzione') {
            // Conferma restituzione
            $prestito_id = $_POST['prestito_id'];
            
            $stmt = $db->prepare("
                UPDATE prestiti 
                SET check_restituzione_bibliotecario = 1,
                    bibliotecario_restituzione_id = ?,
                    data_restituzione = NOW(),
                    stato = 'restituito'
                WHERE id = ?
            ");
            $stmt->execute([$_SESSION['user_id'], $prestito_id]);
            
            // Recupera info prestito per rimuovere blacklist
            $stmt = $db->prepare("SELECT utente_id, libro_id FROM prestiti WHERE id = ?");
            $stmt->execute([$prestito_id]);
            $prestito = $stmt->fetch();
            
            rimuoviBlacklist($prestito['utente_id'], $_SESSION['user_id']);
            
            if ($prestito['libro_id']) {
                notificaDisponibilita($prestito['libro_id']);
            }
            
            logActivity($_SESSION['user_id'], 'restituzione_confermata', 'prestiti', $prestito_id, 'Restituzione confermata');
            
            $message = 'Restituzione confermata con successo!';
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Ottieni prenotazioni attive da trasformare in prestiti
$prenotazioni_attive = $db->query("
    SELECT pr.*, 
           u.nome, u.cognome, u.email,
           l.titolo as libro_titolo,
           d.modello as dispositivo_modello
    FROM prenotazioni pr
    JOIN utenti u ON pr.utente_id = u.id
    LEFT JOIN libri l ON pr.libro_id = l.id
    LEFT JOIN dispositivi d ON pr.dispositivo_id = d.id
    WHERE pr.stato = 'attiva'
    ORDER BY pr.data_prenotazione ASC
")->fetchAll();

// Prestiti attivi che necessitano conferma utente per ritiro
$prestiti_in_attesa_ritiro = $db->query("
    SELECT p.*, 
           u.nome, u.cognome, u.email,
           l.titolo as libro_titolo,
           d.modello as dispositivo_modello
    FROM prestiti p
    JOIN utenti u ON p.utente_id = u.id
    LEFT JOIN libri l ON p.libro_id = l.id
    LEFT JOIN dispositivi d ON p.dispositivo_id = d.id
    WHERE p.stato = 'attivo' 
    AND p.check_ritiro_bibliotecario = 1 
    AND p.check_ritiro_utente = 0
    ORDER BY p.data_ritiro DESC
")->fetchAll();

// Prestiti da restituire
$prestiti_attivi = $db->query("
    SELECT p.*, 
           u.nome, u.cognome, u.email,
           l.titolo as libro_titolo,
           d.modello as dispositivo_modello,
           DATEDIFF(p.data_scadenza, NOW()) as giorni_rimanenti
    FROM prestiti p
    JOIN utenti u ON p.utente_id = u.id
    LEFT JOIN libri l ON p.libro_id = l.id
    LEFT JOIN dispositivi d ON p.dispositivo_id = d.id
    WHERE p.stato IN ('attivo', 'in_ritardo')
    AND p.check_ritiro_utente = 1
    ORDER BY p.data_scadenza ASC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Prestiti - Biblioteca Gobetti</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body data-livello="<?php echo $_SESSION['livello']; ?>">
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <h2>🔧 Gestione Prestiti</h2>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo e($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo e($error); ?></div>
        <?php endif; ?>
        
        <!-- Prenotazioni da Trasformare in Prestiti -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">📦 Prenotazioni da Ritirare (<?php echo count($prenotazioni_attive); ?>)</h3>
            </div>
            
            <?php if (empty($prenotazioni_attive)): ?>
                <p style="text-align: center; padding: 20px; color: #7f8c8d;">
                    Nessuna prenotazione attiva
                </p>
            <?php else: ?>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Utente</th>
                                <th>Materiale</th>
                                <th>Data Prenotazione</th>
                                <th>Scade il</th>
                                <th>Tipo</th>
                                <th>Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($prenotazioni_attive as $pr): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo e($pr['nome'] . ' ' . $pr['cognome']); ?></strong><br>
                                        <small><?php echo e($pr['email']); ?></small>
                                    </td>
                                    <td><?php echo e($pr['libro_titolo'] ?? $pr['dispositivo_modello']); ?></td>
                                    <td><?php echo formatData($pr['data_prenotazione'], true); ?></td>
                                    <td>
                                        <span class="badge badge-warning">
                                            <?php echo formatData($pr['data_scadenza_ritiro'], true); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-info">
                                            <?php echo e(ucfirst($pr['tipo_prenotazione'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="azione" value="crea_prestito_da_prenotazione">
                                            <input type="hidden" name="prenotazione_id" value="<?php echo $pr['id']; ?>">
                                            <button type="submit" class="btn btn-success btn-sm">
                                                ✓ Consegna Materiale
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Prestiti in Attesa di Conferma Utente -->
        <?php if (!empty($prestiti_in_attesa_ritiro)): ?>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">⏳ In Attesa di Conferma Utente (<?php echo count($prestiti_in_attesa_ritiro); ?>)</h3>
                </div>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Utente</th>
                                <th>Materiale</th>
                                <th>Data Ritiro</th>
                                <th>Scadenza</th>
                                <th>Check Bibliotecario</th>
                                <th>Check Utente</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($prestiti_in_attesa_ritiro as $p): ?>
                                <tr>
                                    <td><?php echo e($p['nome'] . ' ' . $p['cognome']); ?></td>
                                    <td><?php echo e($p['libro_titolo'] ?? $p['dispositivo_modello']); ?></td>
                                    <td><?php echo formatData($p['data_ritiro'], true); ?></td>
                                    <td><?php echo formatData($p['data_scadenza']); ?></td>
                                    <td><span class="badge badge-success">✓ Confermato</span></td>
                                    <td><span class="badge badge-warning">In attesa</span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Prestiti Attivi -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">📚 Prestiti Attivi (<?php echo count($prestiti_attivi); ?>)</h3>
            </div>
            
            <?php if (empty($prestiti_attivi)): ?>
                <p style="text-align: center; padding: 20px; color: #7f8c8d;">
                    Nessun prestito attivo
                </p>
            <?php else: ?>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Utente</th>
                                <th>Materiale</th>
                                <th>Data Ritiro</th>
                                <th>Data Scadenza</th>
                                <th>Stato</th>
                                <th>Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($prestiti_attivi as $p): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo e($p['nome'] . ' ' . $p['cognome']); ?></strong><br>
                                        <small><?php echo e($p['email']); ?></small>
                                    </td>
                                    <td><?php echo e($p['libro_titolo'] ?? $p['dispositivo_modello']); ?></td>
                                    <td><?php echo formatData($p['data_ritiro']); ?></td>
                                    <td><?php echo formatData($p['data_scadenza']); ?></td>
                                    <td>
                                        <?php if ($p['stato'] === 'in_ritardo'): ?>
                                            <span class="badge badge-danger">
                                                In Ritardo (<?php echo abs($p['giorni_rimanenti']); ?>g)
                                            </span>
                                        <?php elseif ($p['giorni_rimanenti'] <= 3): ?>
                                            <span class="badge badge-warning">
                                                In Scadenza (<?php echo $p['giorni_rimanenti']; ?>g)
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-success">Attivo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="azione" value="conferma_restituzione">
                                            <input type="hidden" name="prestito_id" value="<?php echo $p['id']; ?>">
                                            <button 
                                                type="submit" 
                                                class="btn btn-primary btn-sm"
                                                onclick="return confirm('Confermi la restituzione del materiale?')">
                                                ✓ Conferma Restituzione
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        
        <div style="margin-top: 30px; text-align: center;">
            <a href="../user/dashboard.php" class="btn btn-secondary">← Torna alla Dashboard</a>
        </div>
    </div>
    
    <script src="../assets/js/main.js"></script>
</body>
</html>
