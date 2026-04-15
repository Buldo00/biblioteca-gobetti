<?php
/**
 * I Miei Prestiti - Biblioteca Gobetti
 */

require_once '../includes/functions.php';
requireLogin();

$db = getDB();
$user_id = $_SESSION['user_id'];

// Ottieni prestiti attivi
$stmt = $db->prepare("
    SELECT p.*, 
           l.titolo as libro_titolo, l.autore,
           d.modello as dispositivo_modello,
           DATEDIFF(p.data_scadenza, NOW()) as giorni_rimanenti
    FROM prestiti p
    LEFT JOIN libri l ON p.libro_id = l.id
    LEFT JOIN dispositivi d ON p.dispositivo_id = d.id
    WHERE p.utente_id = ? AND p.stato IN ('attivo', 'in_ritardo')
    ORDER BY p.data_scadenza ASC
");
$stmt->execute([$user_id]);
$prestiti_attivi = $stmt->fetchAll();

// Ottieni storico prestiti
$stmt = $db->prepare("
    SELECT p.*, 
           l.titolo as libro_titolo, l.autore,
           d.modello as dispositivo_modello
    FROM prestiti p
    LEFT JOIN libri l ON p.libro_id = l.id
    LEFT JOIN dispositivi d ON p.dispositivo_id = d.id
    WHERE p.utente_id = ? AND p.stato = 'restituito'
    ORDER BY p.data_restituzione DESC
    LIMIT 20
");
$stmt->execute([$user_id]);
$prestiti_storici = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>I Miei Prestiti - Biblioteca Gobetti</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body data-livello="<?php echo $user['livello']; ?>">
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <h2>📖 I Miei Prestiti</h2>
        
        <!-- Prestiti Attivi -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Prestiti Attivi (<?php echo count($prestiti_attivi); ?>)</h3>
            </div>
            
            <?php if (empty($prestiti_attivi)): ?>
                <p style="text-align: center; padding: 40px; color: #7f8c8d;">
                    Non hai prestiti attivi al momento.<br>
                    <a href="catalogo.php" class="btn btn-primary" style="margin-top: 15px;">
                        Sfoglia il Catalogo
                    </a>
                </p>
            <?php else: ?>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Materiale</th>
                                <th>Data Ritiro</th>
                                <th>Data Scadenza</th>
                                <th>Stato</th>
                                <th>Check Ritiro</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($prestiti_attivi as $prestito): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo e($prestito['libro_titolo'] ?? $prestito['dispositivo_modello']); ?></strong>
                                        <?php if ($prestito['autore']): ?>
                                            <br><small><?php echo e($prestito['autore']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo formatData($prestito['data_ritiro'], true); ?></td>
                                    <td><?php echo formatData($prestito['data_scadenza']); ?></td>
                                    <td>
                                        <?php if ($prestito['stato'] === 'in_ritardo'): ?>
                                            <span class="badge badge-danger">
                                                ⚠️ In Ritardo (<?php echo abs($prestito['giorni_rimanenti']); ?>g)
                                            </span>
                                        <?php elseif ($prestito['giorni_rimanenti'] <= 3): ?>
                                            <span class="badge badge-warning">
                                                ⏰ Scadenza vicina (<?php echo $prestito['giorni_rimanenti']; ?>g)
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-success">
                                                ✓ Attivo
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($prestito['check_ritiro_utente'] && $prestito['check_ritiro_bibliotecario']): ?>
                                            <span class="badge badge-success">✓ Completato</span>
                                        <?php elseif ($prestito['check_ritiro_bibliotecario']): ?>
                                            <span class="badge badge-warning">In attesa tua conferma</span>
                                        <?php else: ?>
                                            <span class="badge badge-info">In attesa bibliotecario</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Storico Prestiti -->
        <?php if (!empty($prestiti_storici)): ?>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">📜 Storico Prestiti</h3>
                </div>
                
                <div class="table-container">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Materiale</th>
                                <th>Data Ritiro</th>
                                <th>Data Restituzione</th>
                                <th>Durata</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($prestiti_storici as $prestito): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo e($prestito['libro_titolo'] ?? $prestito['dispositivo_modello']); ?></strong>
                                        <?php if ($prestito['autore']): ?>
                                            <br><small><?php echo e($prestito['autore']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo formatData($prestito['data_ritiro']); ?></td>
                                    <td><?php echo formatData($prestito['data_restituzione']); ?></td>
                                    <td>
                                        <?php 
                                        $giorni = round((strtotime($prestito['data_restituzione']) - strtotime($prestito['data_ritiro'])) / 86400);
                                        echo $giorni . ' giorni';
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
        
        <div style="margin-top: 30px; text-align: center;">
            <a href="dashboard.php" class="btn btn-secondary">← Torna alla Dashboard</a>
        </div>
    </div>
    
    <script src="../assets/js/main.js"></script>
</body>
</html>
