<?php
/**
 * Statistiche - Biblioteca Gobetti (Admin+)
 */

require_once '../includes/functions.php';
requireMinLevel(LIVELLO_BIBLIOTECARIO);

$db = getDB();

// Statistiche generali
$stats = [];

// Utenti
$stats['totale_utenti'] = $db->query("SELECT COUNT(*) as c FROM utenti WHERE attivo = 1")->fetch()['c'];
$stats['utenti_blacklist'] = $db->query("SELECT COUNT(*) as c FROM utenti WHERE in_blacklist = 1")->fetch()['c'];
$stats['studenti'] = $db->query("SELECT COUNT(*) as c FROM utenti WHERE livello = 100 AND attivo = 1")->fetch()['c'];
$stats['docenti'] = $db->query("SELECT COUNT(*) as c FROM utenti WHERE livello = 300 AND attivo = 1")->fetch()['c'];

// Libri
$stats['totale_libri'] = $db->query("SELECT COUNT(*) as c FROM libri")->fetch()['c'];
$stats['libri_disponibili'] = $db->query("SELECT COUNT(*) as c FROM libri WHERE copie_disponibili > 0")->fetch()['c'];
$stats['totale_copie'] = $db->query("SELECT SUM(numero_copie) as s FROM libri")->fetch()['s'] ?: 0;
$stats['copie_disponibili'] = $db->query("SELECT SUM(copie_disponibili) as s FROM libri")->fetch()['s'] ?: 0;

// Prestiti
$stats['prestiti_attivi'] = $db->query("SELECT COUNT(*) as c FROM prestiti WHERE stato IN ('attivo', 'in_ritardo')")->fetch()['c'];
$stats['prestiti_in_ritardo'] = $db->query("SELECT COUNT(*) as c FROM prestiti WHERE stato = 'in_ritardo'")->fetch()['c'];
$stats['prestiti_totali'] = $db->query("SELECT COUNT(*) as c FROM prestiti")->fetch()['c'];
$stats['prestiti_restituiti'] = $db->query("SELECT COUNT(*) as c FROM prestiti WHERE stato = 'restituito'")->fetch()['c'];

// Prenotazioni
$stats['prenotazioni_attive'] = $db->query("SELECT COUNT(*) as c FROM prenotazioni WHERE stato = 'attiva'")->fetch()['c'];
$stats['prenotazioni_scadute'] = $db->query("SELECT COUNT(*) as c FROM prenotazioni WHERE stato = 'scaduta'")->fetch()['c'];

// Libri più prestati
$libri_popolari = $db->query("
    SELECT l.titolo, l.autore, COUNT(p.id) as volte_prestato
    FROM prestiti p
    JOIN libri l ON p.libro_id = l.id
    GROUP BY l.id
    ORDER BY volte_prestato DESC
    LIMIT 10
")->fetchAll();

// Utenti più attivi
$utenti_attivi = $db->query("
    SELECT u.nome, u.cognome, COUNT(p.id) as prestiti_totali
    FROM prestiti p
    JOIN utenti u ON p.utente_id = u.id
    GROUP BY u.id
    ORDER BY prestiti_totali DESC
    LIMIT 10
")->fetchAll();

// Statistiche per mese (ultimi 6 mesi)
$stats_mensili = $db->query("
    SELECT 
        DATE_FORMAT(data_ritiro, '%Y-%m') as mese,
        COUNT(*) as prestiti
    FROM prestiti
    WHERE data_ritiro >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(data_ritiro, '%Y-%m')
    ORDER BY mese DESC
")->fetchAll();

// Generi più popolari
$generi_popolari = $db->query("
    SELECT l.genere, COUNT(p.id) as prestiti
    FROM prestiti p
    JOIN libri l ON p.libro_id = l.id
    WHERE l.genere IS NOT NULL
    GROUP BY l.genere
    ORDER BY prestiti DESC
    LIMIT 5
")->fetchAll();

// Distribuzione per livello utente
$livelli_dist = $db->query("
    SELECT 
        CASE 
            WHEN livello = 100 THEN 'Studenti'
            WHEN livello = 300 THEN 'Docenti'
            WHEN livello = 320 THEN 'Bibliotecari'
            WHEN livello >= 600 THEN 'Amministrativi'
            ELSE 'Altri'
        END as categoria,
        COUNT(*) as numero
    FROM utenti
    WHERE attivo = 1
    GROUP BY categoria
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiche - Biblioteca Gobetti</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body data-livello="<?php echo $_SESSION['livello']; ?>">
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <h2>📊 Statistiche Biblioteca</h2>
        
        <!-- Statistiche Generali -->
        <div class="dashboard-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['totale_utenti']; ?></div>
                <div class="stat-label">Utenti Attivi</div>
            </div>
            
            <div class="stat-card success">
                <div class="stat-number"><?php echo $stats['totale_libri']; ?></div>
                <div class="stat-label">Titoli in Catalogo</div>
            </div>
            
            <div class="stat-card warning">
                <div class="stat-number"><?php echo $stats['prestiti_attivi']; ?></div>
                <div class="stat-label">Prestiti Attivi</div>
            </div>
            
            <div class="stat-card danger">
                <div class="stat-number"><?php echo $stats['prestiti_in_ritardo']; ?></div>
                <div class="stat-label">Prestiti in Ritardo</div>
            </div>
        </div>
        
        <!-- Dettagli -->
        <div class="form-row" style="align-items: stretch;">
            <div class="card" style="flex: 1;">
                <div class="card-header">
                    <h3 class="card-title">👥 Utenti</h3>
                </div>
                <p><strong>Studenti:</strong> <?php echo $stats['studenti']; ?></p>
                <p><strong>Docenti:</strong> <?php echo $stats['docenti']; ?></p>
                <p><strong>In Blacklist:</strong> <span class="badge badge-danger"><?php echo $stats['utenti_blacklist']; ?></span></p>
            </div>
            
            <div class="card" style="flex: 1;">
                <div class="card-header">
                    <h3 class="card-title">📚 Libri</h3>
                </div>
                <p><strong>Totale Copie:</strong> <?php echo $stats['totale_copie']; ?></p>
                <p><strong>Copie Disponibili:</strong> <span class="badge badge-success"><?php echo $stats['copie_disponibili']; ?></span></p>
                <p><strong>Tasso Utilizzo:</strong> <?php echo $stats['totale_copie'] > 0 ? round((($stats['totale_copie'] - $stats['copie_disponibili']) / $stats['totale_copie']) * 100, 1) : 0; ?>%</p>
            </div>
            
            <div class="card" style="flex: 1;">
                <div class="card-header">
                    <h3 class="card-title">📖 Prestiti</h3>
                </div>
                <p><strong>Totali:</strong> <?php echo $stats['prestiti_totali']; ?></p>
                <p><strong>Restituiti:</strong> <?php echo $stats['prestiti_restituiti']; ?></p>
                <p><strong>Tasso Restituzione:</strong> <?php echo $stats['prestiti_totali'] > 0 ? round(($stats['prestiti_restituiti'] / $stats['prestiti_totali']) * 100, 1) : 0; ?>%</p>
            </div>
        </div>
        
        <!-- Libri Più Prestati -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">🏆 Top 10 Libri Più Prestati</h3>
            </div>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Titolo</th>
                            <th>Autore</th>
                            <th>Prestiti</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $pos = 1; foreach ($libri_popolari as $libro): ?>
                            <tr>
                                <td><strong><?php echo $pos++; ?></strong></td>
                                <td><?php echo e($libro['titolo']); ?></td>
                                <td><?php echo e($libro['autore'] ?: '-'); ?></td>
                                <td><span class="badge badge-info"><?php echo $libro['volte_prestato']; ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Utenti Più Attivi -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">👤 Top 10 Utenti Più Attivi</h3>
            </div>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nome</th>
                            <th>Prestiti Totali</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $pos = 1; foreach ($utenti_attivi as $utente): ?>
                            <tr>
                                <td><strong><?php echo $pos++; ?></strong></td>
                                <td><?php echo e($utente['nome'] . ' ' . $utente['cognome']); ?></td>
                                <td><span class="badge badge-success"><?php echo $utente['prestiti_totali']; ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Generi Più Popolari -->
        <?php if (!empty($generi_popolari)): ?>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">📚 Generi Più Popolari</h3>
                </div>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Genere</th>
                                <th>Prestiti</th>
                                <th>Percentuale</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $totale_generi = array_sum(array_column($generi_popolari, 'prestiti'));
                            foreach ($generi_popolari as $genere): 
                                $perc = $totale_generi > 0 ? round(($genere['prestiti'] / $totale_generi) * 100, 1) : 0;
                            ?>
                                <tr>
                                    <td><?php echo e($genere['genere']); ?></td>
                                    <td><span class="badge badge-info"><?php echo $genere['prestiti']; ?></span></td>
                                    <td>
                                        <div style="background: var(--light-bg); border-radius: 10px; overflow: hidden; height: 20px;">
                                            <div style="background: var(--secondary-color); height: 100%; width: <?php echo $perc; ?>%;"></div>
                                        </div>
                                        <?php echo $perc; ?>%
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Andamento Mensile -->
        <?php if (!empty($stats_mensili)): ?>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">📈 Andamento Prestiti (Ultimi 6 Mesi)</h3>
                </div>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Mese</th>
                                <th>Prestiti</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stats_mensili as $mese): ?>
                                <tr>
                                    <td><?php echo date('F Y', strtotime($mese['mese'] . '-01')); ?></td>
                                    <td><span class="badge badge-primary"><?php echo $mese['prestiti']; ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
        
        <div style="margin-top: 30px; text-align: center;">
            <a href="../user/dashboard.php" class="btn btn-secondary">← Torna alla Dashboard</a>
        </div>
    </div>
    
    <script src="../assets/js/main.js"></script>
</body>
</html>
