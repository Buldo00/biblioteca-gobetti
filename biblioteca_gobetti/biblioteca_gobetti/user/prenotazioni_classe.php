<?php
/**
 * Prenotazioni di Classe - Biblioteca Gobetti (Docenti+)
 */

require_once '../includes/functions.php';
requireMinLevel(LIVELLO_DOCENTE);

$db = getDB();
$user = getCurrentUser();

$message = '';
$error = '';

// Gestione prenotazione
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['azione'])) {
    $azione = $_POST['azione'];
    
    try {
        if ($azione === 'prenota_classe') {
            $libro_id = $_POST['libro_id'];
            $classe_id = $_POST['classe_id'];
            $studenti_ids = $_POST['studenti'] ?? [];
            
            if (empty($studenti_ids)) {
                throw new Exception('Seleziona almeno uno studente');
            }
            
            // Verifica disponibilità copie
            $stmt = $db->prepare("SELECT copie_disponibili, titolo FROM libri WHERE id = ?");
            $stmt->execute([$libro_id]);
            $libro = $stmt->fetch();
            
            if (!$libro) {
                throw new Exception('Libro non trovato');
            }
            
            if ($libro['copie_disponibili'] < count($studenti_ids)) {
                throw new Exception("Copie insufficienti. Disponibili: {$libro['copie_disponibili']}, Richieste: " . count($studenti_ids));
            }
            
            $db->beginTransaction();
            
            // Calcola scadenza ritiro
            $giorni_ritiro = getSetting('giorni_ritiro_prenotazione', 3);
            $data_scadenza = date('Y-m-d H:i:s', strtotime("+$giorni_ritiro days"));
            
            // Crea una prenotazione per ogni studente
            $stmt = $db->prepare("
                INSERT INTO prenotazioni 
                (utente_id, libro_id, tipo_prenotazione, classe_id, data_scadenza_ritiro)
                VALUES (?, ?, 'classe', ?, ?)
            ");
            
            foreach ($studenti_ids as $studente_id) {
                $stmt->execute([$studente_id, $libro_id, $classe_id, $data_scadenza]);
            }
            
            // Log attività
            logActivity($_SESSION['user_id'], 'prenotazione_classe_creata', 'prenotazioni', null, 
                "Prenotazione di classe: {$libro['titolo']} per " . count($studenti_ids) . " studenti");
            
            $db->commit();
            
            $message = "Prenotazione di classe creata con successo! " . count($studenti_ids) . " studenti hanno prenotato il libro.";
            
        }
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        $error = $e->getMessage();
    }
}

// Ottieni classi del docente o tutte se è admin
if ($user['livello'] >= LIVELLO_BIBLIOTECARIO) {
    $classi = $db->query("SELECT * FROM classi WHERE attiva = 1 ORDER BY nome, sezione")->fetchAll();
} else {
    // Per i docenti, filtra per le loro classi (da implementare se necessario)
    $classi = $db->query("SELECT * FROM classi WHERE attiva = 1 ORDER BY nome, sezione")->fetchAll();
}

// Ottieni libri disponibili
$libri = $db->query("
    SELECT id, titolo, autore, copie_disponibili 
    FROM libri 
    WHERE copie_disponibili > 0 AND prenotabile = 1
    ORDER BY titolo
")->fetchAll();

// Se è stata selezionata una classe, ottieni gli studenti
$studenti = [];
$classe_selezionata = null;
if (isset($_GET['classe_id'])) {
    $classe_id = $_GET['classe_id'];
    $stmt = $db->prepare("SELECT * FROM classi WHERE id = ?");
    $stmt->execute([$classe_id]);
    $classe_selezionata = $stmt->fetch();
    
    if ($classe_selezionata) {
        $stmt = $db->prepare("
            SELECT u.*, 
                   COUNT(p.id) as prestiti_attivi
            FROM utenti u
            LEFT JOIN prestiti p ON u.id = p.utente_id AND p.stato IN ('attivo', 'in_ritardo')
            WHERE u.classe_id = ? AND u.attivo = 1 AND u.livello = ?
            GROUP BY u.id
            ORDER BY u.cognome, u.nome
        ");
        $stmt->execute([$classe_id, LIVELLO_STUDENTE]);
        $studenti = $stmt->fetchAll();
    }
}

// Ottieni prenotazioni di classe attive
$stmt = $db->prepare("
    SELECT pr.*, 
           l.titolo as libro_titolo,
           u.nome, u.cognome,
           c.nome as classe_nome, c.sezione as classe_sezione,
           COUNT(*) OVER (PARTITION BY pr.libro_id, pr.classe_id, DATE(pr.data_prenotazione)) as totale_studenti
    FROM prenotazioni pr
    JOIN utenti u ON pr.utente_id = u.id
    JOIN libri l ON pr.libro_id = l.id
    LEFT JOIN classi c ON pr.classe_id = c.id
    WHERE pr.tipo_prenotazione = 'classe' AND pr.stato = 'attiva'
    ORDER BY pr.data_prenotazione DESC
");
$stmt->execute();
$prenotazioni_classe = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prenotazioni di Classe - Biblioteca Gobetti</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body data-livello="<?php echo $user['livello']; ?>">
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <h2>👥 Prenotazioni di Classe</h2>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo e($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo e($error); ?></div>
        <?php endif; ?>
        
        <!-- Form Nuova Prenotazione -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">📦 Nuova Prenotazione di Classe</h3>
            </div>
            
            <form method="POST" id="formPrenotazioneClasse">
                <input type="hidden" name="azione" value="prenota_classe">
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Libro *</label>
                        <select name="libro_id" id="libro_id" class="form-control" required>
                            <option value="">-- Seleziona libro --</option>
                            <?php foreach ($libri as $libro): ?>
                                <option value="<?php echo $libro['id']; ?>" data-copie="<?php echo $libro['copie_disponibili']; ?>">
                                    <?php echo e($libro['titolo']); ?>
                                    <?php echo $libro['autore'] ? ' - ' . e($libro['autore']) : ''; ?>
                                    (<?php echo $libro['copie_disponibili']; ?> copie disponibili)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Classe *</label>
                        <select name="classe_id" class="form-control" required onchange="this.form.action='prenotazioni_classe.php'; this.form.method='GET'; this.form.submit();">
                            <option value="">-- Seleziona classe --</option>
                            <?php foreach ($classi as $classe): ?>
                                <option value="<?php echo $classe['id']; ?>" <?php echo $classe_selezionata && $classe_selezionata['id'] == $classe['id'] ? 'selected' : ''; ?>>
                                    <?php echo e($classe['nome'] . ($classe['sezione'] ? $classe['sezione'] : '')); ?> 
                                    (<?php echo $classe['numero_studenti']; ?> studenti)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <?php if (!empty($studenti)): ?>
                    <div class="form-group">
                        <label class="form-label">
                            Seleziona Studenti * 
                            <small id="copie-info" style="color: #7f8c8d;"></small>
                        </label>
                        <div style="background: var(--light-bg); padding: 15px; border-radius: 8px; max-height: 400px; overflow-y: auto;">
                            <label style="display: block; margin-bottom: 10px;">
                                <input type="checkbox" id="seleziona-tutti" onchange="toggleTuttiStudentiClasse(this)">
                                <strong> Seleziona Tutti</strong>
                            </label>
                            <hr>
                            
                            <?php foreach ($studenti as $studente): ?>
                                <label style="display: block; padding: 8px; margin: 5px 0; background: white; border-radius: 6px; cursor: pointer;">
                                    <input 
                                        type="checkbox" 
                                        name="studenti[]" 
                                        value="<?php echo $studente['id']; ?>"
                                        class="studente-checkbox-classe"
                                        onchange="aggiornaContatore()"
                                        <?php echo $studente['in_blacklist'] ? 'disabled' : ''; ?>
                                    >
                                    <strong><?php echo e($studente['cognome'] . ' ' . $studente['nome']); ?></strong>
                                    <span class="badge badge-info" style="margin-left: 10px;">
                                        <?php echo $studente['prestiti_attivi']; ?> prestiti
                                    </span>
                                    <?php if ($studente['in_blacklist']): ?>
                                        <span class="badge badge-danger">Blacklist</span>
                                    <?php endif; ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        
                        <div style="margin-top: 15px; text-align: center;">
                            <strong>Studenti selezionati: <span id="contatore-studenti">0</span></strong>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block" id="btn-prenota">
                        📦 Prenota per la Classe
                    </button>
                <?php else: ?>
                    <div class="alert alert-info">
                        Seleziona una classe per visualizzare gli studenti.
                    </div>
                <?php endif; ?>
            </form>
        </div>
        
        <!-- Prenotazioni Attive -->
        <?php if (!empty($prenotazioni_classe)): ?>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">📋 Prenotazioni di Classe Attive</h3>
                </div>
                
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Libro</th>
                                <th>Classe</th>
                                <th>Studente</th>
                                <th>Data Prenotazione</th>
                                <th>Scadenza Ritiro</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($prenotazioni_classe as $pren): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo e($pren['libro_titolo']); ?></strong>
                                        <?php if ($pren['totale_studenti'] > 1): ?>
                                            <br><small class="badge badge-info">
                                                Prenotazione di gruppo (<?php echo $pren['totale_studenti']; ?> studenti)
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo e($pren['classe_nome'] . ($pren['classe_sezione'] ? $pren['classe_sezione'] : '')); ?></td>
                                    <td><?php echo e($pren['nome'] . ' ' . $pren['cognome']); ?></td>
                                    <td><?php echo formatData($pren['data_prenotazione'], true); ?></td>
                                    <td>
                                        <span class="badge badge-warning">
                                            <?php echo formatData($pren['data_scadenza_ritiro'], true); ?>
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
    <script>
        function toggleTuttiStudentiClasse(checkbox) {
            const checkboxes = document.querySelectorAll('.studente-checkbox-classe:not(:disabled)');
            checkboxes.forEach(cb => {
                cb.checked = checkbox.checked;
            });
            aggiornaContatore();
        }
        
        function aggiornaContatore() {
            const selezionati = document.querySelectorAll('.studente-checkbox-classe:checked').length;
            document.getElementById('contatore-studenti').textContent = selezionati;
            
            // Verifica copie disponibili
            const libroSelect = document.getElementById('libro_id');
            const btnPrenota = document.getElementById('btn-prenota');
            const copieInfo = document.getElementById('copie-info');
            
            if (libroSelect.value) {
                const copieDisponibili = parseInt(libroSelect.options[libroSelect.selectedIndex].dataset.copie);
                
                if (selezionati > copieDisponibili) {
                    btnPrenota.disabled = true;
                    copieInfo.textContent = `⚠️ Copie insufficienti! Disponibili: ${copieDisponibili}`;
                    copieInfo.style.color = '#e74c3c';
                } else {
                    btnPrenota.disabled = false;
                    copieInfo.textContent = `✓ Copie sufficienti (${copieDisponibili} disponibili)`;
                    copieInfo.style.color = '#27ae60';
                }
            }
        }
        
        // Aggiorna quando si cambia libro
        document.getElementById('libro_id').addEventListener('change', aggiornaContatore);
        
        // Inizializza contatore
        aggiornaContatore();
    </script>
</body>
</html>
