<?php
/**
 * Dashboard Utente - Biblioteca Gobetti
 */

require_once '../includes/functions.php';
requireLogin();

$user = getCurrentUser();
$db = getDB();

// Statistiche personali
$stmt = $db->prepare("
    SELECT COUNT(*) as count 
    FROM prestiti 
    WHERE utente_id = ? AND stato IN ('attivo', 'in_ritardo')
");
$stmt->execute([$_SESSION['user_id']]);
$prestiti_attivi = $stmt->fetch()['count'];

$stmt = $db->prepare("
    SELECT COUNT(*) as count 
    FROM prenotazioni 
    WHERE utente_id = ? AND stato = 'attiva'
");
$stmt->execute([$_SESSION['user_id']]);
$prenotazioni_attive = $stmt->fetch()['count'];

// Prestiti in scadenza (prossimi 3 giorni)
$stmt = $db->prepare("
    SELECT p.*, l.titolo as libro_titolo, d.modello as dispositivo_modello
    FROM prestiti p
    LEFT JOIN libri l ON p.libro_id = l.id
    LEFT JOIN dispositivi d ON p.dispositivo_id = d.id
    WHERE p.utente_id = ? 
    AND p.stato = 'attivo'
    AND p.data_scadenza BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 3 DAY)
    ORDER BY p.data_scadenza ASC
");
$stmt->execute([$_SESSION['user_id']]);
$prestiti_in_scadenza = $stmt->fetchAll();

// Prenotazioni da ritirare
$stmt = $db->prepare("
    SELECT pr.*, l.titolo as libro_titolo, l.autore, d.modello as dispositivo_modello
    FROM prenotazioni pr
    LEFT JOIN libri l ON pr.libro_id = l.id
    LEFT JOIN dispositivi d ON pr.dispositivo_id = d.id
    WHERE pr.utente_id = ? AND pr.stato = 'attiva'
    ORDER BY pr.data_scadenza_ritiro ASC
");
$stmt->execute([$_SESSION['user_id']]);
$prenotazioni = $stmt->fetchAll();

// Statistiche aggiuntive per bibliotecari/admin
$stats_biblioteca = null;
if (hasMinLevel(LIVELLO_BIBLIOTECARIO)) {
    $stats_biblioteca = [
        'prestiti_attivi_totali' => $db->query("SELECT COUNT(*) as c FROM prestiti WHERE stato IN ('attivo', 'in_ritardo')")->fetch()['c'],
        'prenotazioni_attive_totali' => $db->query("SELECT COUNT(*) as c FROM prenotazioni WHERE stato = 'attiva'")->fetch()['c'],
        'utenti_blacklist' => $db->query("SELECT COUNT(*) as c FROM utenti WHERE in_blacklist = 1")->fetch()['c'],
        'prestiti_in_ritardo' => $db->query("SELECT COUNT(*) as c FROM prestiti WHERE stato = 'in_ritardo'")->fetch()['c']
    ];
}

$page_title = 'Dashboard';
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Biblioteca Gobetti</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body data-livello="<?php echo $user['livello']; ?>">
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <h2>Benvenuto, <?php echo e($user['nome'] . ' ' . $user['cognome']); ?>!</h2>
        
        <?php if ($user['in_blacklist']): ?>
            <div class="alert alert-danger">
                <strong>⚠️ Sei in Blacklist</strong><br>
                Motivo: <?php echo e($user['motivo_blacklist']); ?><br>
                Non puoi effettuare nuove prenotazioni fino alla restituzione dei materiali.
            </div>
        <?php endif; ?>
        
        <!-- Statistiche Personali -->
        <div class="dashboard-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $prestiti_attivi; ?></div>
                <div class="stat-label">Prestiti Attivi</div>
            </div>
            
            <div class="stat-card warning">
                <div class="stat-number"><?php echo $prenotazioni_attive; ?></div>
                <div class="stat-label">Prenotazioni da Ritirare</div>
            </div>
            
            <div class="stat-card success">
                <div class="stat-number">
                    <?php echo getSetting('max_prestiti_studente', 3) - $prestiti_attivi; ?>
                </div>
                <div class="stat-label">Prestiti Disponibili</div>
            </div>
        </div>
        
        <?php if ($stats_biblioteca && hasMinLevel(LIVELLO_BIBLIOTECARIO)): ?>
            <!-- Statistiche Biblioteca (solo per bibliotecari+) -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">📊 Statistiche Biblioteca</h3>
                </div>
                <div class="dashboard-grid">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats_biblioteca['prestiti_attivi_totali']; ?></div>
                        <div class="stat-label">Prestiti Attivi Totali</div>
                    </div>
                    <div class="stat-card warning">
                        <div class="stat-number"><?php echo $stats_biblioteca['prenotazioni_attive_totali']; ?></div>
                        <div class="stat-label">Prenotazioni Totali</div>
                    </div>
                    <div class="stat-card danger">
                        <div class="stat-number"><?php echo $stats_biblioteca['prestiti_in_ritardo']; ?></div>
                        <div class="stat-label">Prestiti in Ritardo</div>
                    </div>
                    <div class="stat-card danger">
                        <div class="stat-number"><?php echo $stats_biblioteca['utenti_blacklist']; ?></div>
                        <div class="stat-label">Utenti in Blacklist</div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Avvisi Prestiti in Scadenza -->
        <?php if (!empty($prestiti_in_scadenza)): ?>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">⚠️ Prestiti in Scadenza</h3>
                </div>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Materiale</th>
                                <th>Data Ritiro</th>
                                <th>Data Scadenza</th>
                                <th>Tempo Rimanente</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($prestiti_in_scadenza as $prestito): ?>
                                <tr>
                                    <td>
                                        <?php 
                                        echo e($prestito['libro_titolo'] ?? $prestito['dispositivo_modello']);
                                        ?>
                                    </td>
                                    <td><?php echo formatData($prestito['data_ritiro'], true); ?></td>
                                    <td><?php echo formatData($prestito['data_scadenza'], true); ?></td>
                                    <td>
                                        <span class="badge badge-warning" data-countdown="<?php echo $prestito['data_scadenza']; ?>">
                                            Calcolo...
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Prenotazioni da Ritirare -->
        <?php if (!empty($prenotazioni)): ?>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">📦 Prenotazioni da Ritirare</h3>
                </div>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Materiale</th>
                                <th>Data Prenotazione</th>
                                <th>Ritirare Entro</th>
                                <th>Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($prenotazioni as $pren): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo e($pren['libro_titolo'] ?? $pren['dispositivo_modello']); ?></strong>
                                        <?php if ($pren['autore']): ?>
                                            <br><small><?php echo e($pren['autore']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo formatData($pren['data_prenotazione'], true); ?></td>
                                    <td>
                                        <span class="badge badge-warning" data-countdown="<?php echo $pren['data_scadenza_ritiro']; ?>">
                                            <?php echo formatData($pren['data_scadenza_ritiro'], true); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button 
                                            onclick="annullaPrenotazione(<?php echo $pren['id']; ?>)"
                                            class="btn btn-danger btn-sm">
                                            Annulla
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Link Rapidi -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">⚡ Link Rapidi</h3>
            </div>
            <div class="dashboard-grid">
                <a href="catalogo.php" class="btn btn-primary btn-block">
                    📚 Sfoglia Catalogo
                </a>
                <a href="prestiti.php" class="btn btn-success btn-block">
                    📖 I Miei Prestiti
                </a>
                <a href="prenotazioni.php" class="btn btn-warning btn-block">
                    📦 Le Mie Prenotazioni
                </a>
                
                <?php if (hasMinLevel(LIVELLO_DOCENTE)): ?>
                    <a href="prenotazioni_classe.php" class="btn btn-secondary btn-block">
                        👥 Prenotazioni di Classe
                    </a>
                <?php endif; ?>
                
                <?php if (hasMinLevel(LIVELLO_BIBLIOTECARIO)): ?>
                    <a href="../admin/gestione_prestiti.php" class="btn btn-primary btn-block">
                        🔧 Gestione Prestiti
                    </a>
                    <a href="../admin/gestione_libri.php" class="btn btn-primary btn-block">
                        📚 Gestione Libri
                    </a>
                    <a href="../admin/gestione_utenti.php" class="btn btn-primary btn-block">
                        👤 Gestione Utenti
                    </a>
                <?php endif; ?>
                
                <?php if (hasMinLevel(LIVELLO_AMMINISTRATIVO)): ?>
                    <a href="../admin/impostazioni.php" class="btn btn-secondary btn-block">
                        ⚙️ Impostazioni
                    </a>
                    <a href="../admin/statistiche.php" class="btn btn-secondary btn-block">
                        📊 Statistiche
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/main.js"></script>
</body>
</html>
