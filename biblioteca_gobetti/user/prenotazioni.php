<?php
/**
 * Le Mie Prenotazioni - Biblioteca Gobetti
 */

require_once '../includes/functions.php';
requireLogin();

$db = getDB();
$user_id = $_SESSION['user_id'];

// Ottieni prenotazioni attive
$stmt = $db->prepare("
    SELECT pr.*, 
           l.titolo as libro_titolo, l.autore, l.copie_disponibili,
           d.modello as dispositivo_modello,
           TIMESTAMPDIFF(HOUR, NOW(), pr.data_scadenza_ritiro) as ore_rimanenti
    FROM prenotazioni pr
    LEFT JOIN libri l ON pr.libro_id = l.id
    LEFT JOIN dispositivi d ON pr.dispositivo_id = d.id
    WHERE pr.utente_id = ? AND pr.stato = 'attiva'
    ORDER BY pr.data_scadenza_ritiro ASC
");
$stmt->execute([$user_id]);
$prenotazioni_attive = $stmt->fetchAll();

// Ottieni storico prenotazioni
$stmt = $db->prepare("
    SELECT pr.*, 
           l.titolo as libro_titolo, l.autore,
           d.modello as dispositivo_modello
    FROM prenotazioni pr
    LEFT JOIN libri l ON pr.libro_id = l.id
    LEFT JOIN dispositivi d ON pr.dispositivo_id = d.id
    WHERE pr.utente_id = ? AND pr.stato IN ('ritirata', 'scaduta', 'annullata')
    ORDER BY pr.data_prenotazione DESC
    LIMIT 20
");
$stmt->execute([$user_id]);
$prenotazioni_storiche = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Le Mie Prenotazioni - Biblioteca Gobetti</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body data-livello="<?php echo $user['livello']; ?>">
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <h2>📦 Le Mie Prenotazioni</h2>
        
        <!-- Prenotazioni Attive -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Prenotazioni Attive (<?php echo count($prenotazioni_attive); ?>)</h3>
            </div>
            
            <?php if (empty($prenotazioni_attive)): ?>
                <p style="text-align: center; padding: 40px; color: #7f8c8d;">
                    Non hai prenotazioni attive al momento.<br>
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
                                <th>Data Prenotazione</th>
                                <th>Ritirare Entro</th>
                                <th>Tempo Rimanente</th>
                                <th>Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($prenotazioni_attive as $pren): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo e($pren['libro_titolo'] ?? $pren['dispositivo_modello']); ?></strong>
                                        <?php if ($pren['autore']): ?>
                                            <br><small><?php echo e($pren['autore']); ?></small>
                                        <?php endif; ?>
                                        <?php if ($pren['tipo_prenotazione'] === 'classe'): ?>
                                            <br><span class="badge badge-info">Prenotazione di Classe</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo formatData($pren['data_prenotazione'], true); ?></td>
                                    <td><?php echo formatData($pren['data_scadenza_ritiro'], true); ?></td>
                                    <td>
                                        <?php 
                                        $ore = $pren['ore_rimanenti'];
                                        if ($ore < 0) {
                                            echo '<span class="badge badge-danger">Scaduto</span>';
                                        } elseif ($ore < 24) {
                                            echo '<span class="badge badge-danger">' . round($ore) . ' ore</span>';
                                        } else {
                                            $giorni = floor($ore / 24);
                                            echo '<span class="badge badge-warning">' . $giorni . ' giorni</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <button 
                                            onclick="annullaPrenotazione(<?php echo $pren['id']; ?>)"
                                            class="btn btn-danger btn-sm">
                                            ✕ Annulla
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="alert alert-info" style="margin-top: 20px;">
                    <strong>📍 Ricordati:</strong> Devi ritirare i materiali prenotati entro 3 giorni dalla prenotazione.
                    Dopo questo periodo, la prenotazione scadrà automaticamente.
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Storico Prenotazioni -->
        <?php if (!empty($prenotazioni_storiche)): ?>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">📜 Storico Prenotazioni</h3>
                </div>
                
                <div class="table-container">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Materiale</th>
                                <th>Data Prenotazione</th>
                                <th>Stato</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($prenotazioni_storiche as $pren): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo e($pren['libro_titolo'] ?? $pren['dispositivo_modello']); ?></strong>
                                        <?php if ($pren['autore']): ?>
                                            <br><small><?php echo e($pren['autore']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo formatData($pren['data_prenotazione'], true); ?></td>
                                    <td>
                                        <?php
                                        $badges = [
                                            'ritirata' => 'badge-success',
                                            'scaduta' => 'badge-danger',
                                            'annullata' => 'badge-secondary'
                                        ];
                                        $testi = [
                                            'ritirata' => '✓ Ritirata',
                                            'scaduta' => '⏰ Scaduta',
                                            'annullata' => '✕ Annullata'
                                        ];
                                        ?>
                                        <span class="badge <?php echo $badges[$pren['stato']]; ?>">
                                            <?php echo $testi[$pren['stato']]; ?>
                                        </span>
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
