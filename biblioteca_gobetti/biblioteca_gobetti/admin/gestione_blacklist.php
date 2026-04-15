<?php
/**
 * Gestione Blacklist - Biblioteca Gobetti (Bibliotecari+)
 */

require_once '../includes/functions.php';
requireMinLevel(LIVELLO_BIBLIOTECARIO);

$db = getDB();
$user = getCurrentUser();

$message = '';
$error = '';

// Gestione azioni
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $azione = $_POST['azione'] ?? '';
    
    try {
        if ($azione === 'aggiungi_blacklist') {
            $utente_id = $_POST['utente_id'];
            $motivo = $_POST['motivo'];
            $dettagli = $_POST['dettagli'] ?? '';
            
            // Verifica che non si possano mettere in blacklist utenti privilegiati
            $stmt = $db->prepare("SELECT livello FROM utenti WHERE id = ?");
            $stmt->execute([$utente_id]);
            $target = $stmt->fetch();
            
            // Non permettere blacklist di se stessi, bibliotecari o admin
            if ($utente_id == $_SESSION['user_id']) {
                throw new Exception('Non puoi mettere te stesso in blacklist');
            }
            
            if ($target['livello'] >= LIVELLO_BIBLIOTECARIO) {
                throw new Exception('Non puoi mettere in blacklist un bibliotecario o un amministratore');
            }
            
            aggiungiBlacklist($utente_id, $motivo, $dettagli, $_SESSION['user_id']);
            
            $message = 'Utente aggiunto alla blacklist con successo!';
            
        } elseif ($azione === 'rimuovi_blacklist') {
            $utente_id = $_POST['utente_id'];
            
            rimuoviBlacklist($utente_id, $_SESSION['user_id']);
            
            $message = 'Utente rimosso dalla blacklist!';
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Ottieni utenti in blacklist
$utenti_blacklist = $db->query("
    SELECT u.*, 
           bl.motivo, bl.dettagli, bl.data_inizio,
           COUNT(p.id) as prestiti_attivi
    FROM utenti u
    JOIN blacklist_log bl ON u.id = bl.utente_id AND bl.attiva = TRUE
    LEFT JOIN prestiti p ON u.id = p.utente_id AND p.stato IN ('attivo', 'in_ritardo')
    WHERE u.in_blacklist = TRUE
    GROUP BY u.id
    ORDER BY bl.data_inizio DESC
")->fetchAll();

// Ottieni utenti non in blacklist per il form
$utenti_disponibili = $db->query("
    SELECT id, nome, cognome, email, livello
    FROM utenti 
    WHERE in_blacklist = FALSE AND attivo = TRUE AND livello < " . LIVELLO_BIBLIOTECARIO . "
    ORDER BY cognome, nome
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Blacklist - Biblioteca Gobetti</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body data-livello="<?php echo $user['livello']; ?>">
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <h2>⚠️ Gestione Blacklist</h2>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo e($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo e($error); ?></div>
        <?php endif; ?>
        
        <!-- Form Aggiungi Blacklist -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Aggiungi Utente a Blacklist</h3>
            </div>
            
            <form method="POST">
                <input type="hidden" name="azione" value="aggiungi_blacklist">
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Utente *</label>
                        <select name="utente_id" class="form-control" required>
                            <option value="">-- Seleziona utente --</option>
                            <?php foreach ($utenti_disponibili as $u): ?>
                                <option value="<?php echo $u['id']; ?>">
                                    <?php echo e($u['cognome'] . ' ' . $u['nome']); ?> 
                                    (<?php echo e($u['email']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Motivo *</label>
                        <select name="motivo" class="form-control" required>
                            <option value="ritardo">Ritardo Restituzione</option>
                            <option value="mancato_ritiro">Mancato Ritiro</option>
                            <option value="danno">Danno Materiale</option>
                            <option value="manuale">Altro (Manuale)</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Dettagli</label>
                    <textarea name="dettagli" class="form-control" rows="3" placeholder="Descrizione dettagliata del motivo..."></textarea>
                </div>
                
                <button type="submit" class="btn btn-danger">⚠️ Aggiungi a Blacklist</button>
            </form>
        </div>
        
        <!-- Lista Utenti in Blacklist -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Utenti in Blacklist (<?php echo count($utenti_blacklist); ?>)</h3>
            </div>
            
            <?php if (empty($utenti_blacklist)): ?>
                <p style="text-align: center; padding: 20px; color: #7f8c8d;">
                    Nessun utente in blacklist
                </p>
            <?php else: ?>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Utente</th>
                                <th>Contatto</th>
                                <th>Motivo</th>
                                <th>Dettagli</th>
                                <th>Data Inizio</th>
                                <th>Prestiti Attivi</th>
                                <th>Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($utenti_blacklist as $u): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo e($u['cognome'] . ' ' . $u['nome']); ?></strong>
                                    </td>
                                    <td><?php echo e($u['email']); ?></td>
                                    <td>
                                        <span class="badge badge-danger">
                                            <?php echo e(ucfirst($u['motivo'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo e($u['dettagli'] ?: '-'); ?></td>
                                    <td><?php echo formatData($u['data_inizio'], true); ?></td>
                                    <td>
                                        <span class="badge <?php echo $u['prestiti_attivi'] > 0 ? 'badge-warning' : 'badge-success'; ?>">
                                            <?php echo $u['prestiti_attivi']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Rimuovere dalla blacklist?')">
                                            <input type="hidden" name="azione" value="rimuovi_blacklist">
                                            <input type="hidden" name="utente_id" value="<?php echo $u['id']; ?>">
                                            <button type="submit" class="btn btn-success btn-sm">
                                                ✓ Rimuovi
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
        
        <div style="margin-top: 20px; text-align: center;">
            <a href="dashboard.php" class="btn btn-secondary">← Torna alla Dashboard</a>
        </div>
    </div>
    
    <script src="../assets/js/main.js"></script>
</body>
</html>
