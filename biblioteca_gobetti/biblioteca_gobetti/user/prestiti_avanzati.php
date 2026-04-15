<?php
/**
 * Prestiti Avanzati - Biblioteca Gobetti (Docenti+)
 * Unisce prestiti personali e di classe in un'unica interfaccia
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
        if ($azione === 'prenota') {
            $libro_id = $_POST['libro_id'];
            $tipo_prestito = $_POST['tipo_prestito']; // 'personale' o 'classe'
            $classe_id = $_POST['classe_id'] ?? null;
            $studenti_ids = $_POST['studenti'] ?? [];
            
            // Se è personale
            if ($tipo_prestito === 'personale') {
                $utente_target = $_POST['utente_id'] ?? $user['id'];
                
                // Verifica disponibilità
                $stmt = $db->prepare("SELECT copie_disponibili, titolo FROM libri WHERE id = ?");
                $stmt->execute([$libro_id]);
                $libro = $stmt->fetch();
                
                if (!$libro || $libro['copie_disponibili'] <= 0) {
                    throw new Exception('Libro non disponibile');
                }
                
                $db->beginTransaction();
                
                $giorni_ritiro = getSetting('giorni_ritiro_prenotazione', 3);
                $data_scadenza = date('Y-m-d H:i:s', strtotime("+$giorni_ritiro days"));
                
                $stmt = $db->prepare("
                    INSERT INTO prenotazioni 
                    (utente_id, libro_id, tipo_prenotazione, data_scadenza_ritiro)
                    VALUES (?, ?, 'personale', ?)
                ");
                
                $stmt->execute([$utente_target, $libro_id, $data_scadenza]);
                
                logActivity($_SESSION['user_id'], 'prenotazione_creata', 'prenotazioni', $db->lastInsertId(), 
                    "Prenotazione personale: {$libro['titolo']}");
                
                $db->commit();
                
                $message = "Prenotazione personale creata con successo!";
                
            } else { // Prenotazione di classe
                if (empty($studenti_ids)) {
                    throw new Exception('Seleziona almeno uno studente');
                }
                
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
                
                $giorni_ritiro = getSetting('giorni_ritiro_prenotazione', 3);
                $data_scadenza = date('Y-m-d H:i:s', strtotime("+$giorni_ritiro days"));
                
                $stmt = $db->prepare("
                    INSERT INTO prenotazioni 
                    (utente_id, libro_id, tipo_prenotazione, classe_id, data_scadenza_ritiro)
                    VALUES (?, ?, 'classe', ?, ?)
                ");
                
                foreach ($studenti_ids as $studente_id) {
                    $stmt->execute([$studente_id, $libro_id, $classe_id, $data_scadenza]);
                }
                
                logActivity($_SESSION['user_id'], 'prenotazione_classe_creata', 'prenotazioni', null, 
                    "Prenotazione di classe: {$libro['titolo']} per " . count($studenti_ids) . " studenti");
                
                $db->commit();
                
                $message = "Prenotazione di classe creata! " . count($studenti_ids) . " studenti hanno prenotato il libro.";
            }
        }
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        $error = $e->getMessage();
    }
}

// Ottieni libri disponibili
$libri = $db->query("
    SELECT id, titolo, autore, copie_disponibili 
    FROM libri 
    WHERE copie_disponibili > 0 AND prenotabile = 1
    ORDER BY titolo
")->fetchAll();

// Ottieni classi
$classi = $db->query("SELECT * FROM classi WHERE attiva = 1 ORDER BY nome, sezione")->fetchAll();

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
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Prestiti - Biblioteca Gobetti</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body data-livello="<?php echo $user['livello']; ?>">
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <h2>📚 Gestione Prestiti</h2>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo e($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo e($error); ?></div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Nuova Prenotazione</h3>
            </div>
            
            <form method="POST" id="formPrenotazione">
                <input type="hidden" name="azione" value="prenota">
                
                <!-- Tipo Prestito -->
                <div class="form-group">
                    <label class="form-label">Tipo Prestito *</label>
                    <select name="tipo_prestito" id="tipo_prestito" class="form-control" required onchange="toggleTipoPrestito()">
                        <option value="personale">Personale</option>
                        <option value="classe">Per la Classe</option>
                    </select>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Libro *</label>
                        <select name="libro_id" id="libro_id" class="form-control" required>
                            <option value="">-- Seleziona libro --</option>
                            <?php foreach ($libri as $libro): ?>
                                <option value="<?php echo $libro['id']; ?>" data-copie="<?php echo $libro['copie_disponibili']; ?>">
                                    <?php echo e($libro['titolo']); ?>
                                    <?php echo $libro['autore'] ? ' - ' . e($libro['autore']) : ''; ?>
                                    (<?php echo $libro['copie_disponibili']; ?> copie)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Campo Classe (visibile solo per prestito classe) -->
                    <div class="form-group" id="campo-classe" style="display: none;">
                        <label class="form-label">Classe *</label>
                        <select name="classe_id" id="select_classe" class="form-control" onchange="caricaStudenti()">
                            <option value="">-- Seleziona classe --</option>
                            <?php foreach ($classi as $classe): ?>
                                <option value="<?php echo $classe['id']; ?>" <?php echo $classe_selezionata && $classe_selezionata['id'] == $classe['id'] ? 'selected' : ''; ?>>
                                    <?php echo e($classe['nome'] . ($classe['sezione'] ? $classe['sezione'] : '')); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <!-- Selezione Studenti (visibile solo per prestito classe) -->
                <div id="sezione-studenti" style="display: none;">
                    <?php if (!empty($studenti)): ?>
                        <div class="form-group">
                            <label class="form-label">
                                Seleziona Studenti * 
                                <small id="copie-info" style="color: #7f8c8d;"></small>
                            </label>
                            <div style="background: var(--light-bg); padding: 15px; border-radius: 8px; max-height: 300px; overflow-y: auto;">
                                <label style="display: block; margin-bottom: 10px;">
                                    <input type="checkbox" id="seleziona-tutti" onchange="toggleTuttiStudenti(this)">
                                    <strong> Seleziona Tutti</strong>
                                </label>
                                <hr>
                                
                                <?php foreach ($studenti as $studente): ?>
                                    <label style="display: block; padding: 6px; margin: 3px 0; background: white; border-radius: 4px; cursor: pointer;">
                                        <input 
                                            type="checkbox" 
                                            name="studenti[]" 
                                            value="<?php echo $studente['id']; ?>"
                                            class="studente-checkbox"
                                            onchange="aggiornaContatore()"
                                            <?php echo $studente['in_blacklist'] ? 'disabled' : ''; ?>
                                        >
                                        <?php echo e($studente['cognome'] . ' ' . $studente['nome']); ?>
                                        <span class="badge badge-info" style="margin-left: 8px; font-size: 0.8rem;">
                                            <?php echo $studente['prestiti_attivi']; ?> prestiti
                                        </span>
                                        <?php if ($studente['in_blacklist']): ?>
                                            <span class="badge badge-danger">Blacklist</span>
                                        <?php endif; ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            
                            <div style="margin-top: 10px; text-align: center;">
                                <strong>Selezionati: <span id="contatore-studenti">0</span></strong>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block" id="btn-prenota">
                    📦 Crea Prenotazione
                </button>
            </form>
        </div>
        
        <div style="margin-top: 20px; text-align: center;">
            <a href="dashboard.php" class="btn btn-secondary">← Torna alla Dashboard</a>
        </div>
    </div>
    
    <script src="../assets/js/main.js"></script>
    <script>
        function caricaStudenti() {
            const classeId = document.getElementById('select_classe').value;
            if (!classeId) return;
            
            // Redirect con classe_id per caricare studenti lato server
            window.location.href = 'prestiti_avanzati.php?classe_id=' + classeId;
        }
        
        function toggleTipoPrestito() {
            const tipo = document.getElementById('tipo_prestito').value;
            const campoClasse = document.getElementById('campo-classe');
            const sezioneStudenti = document.getElementById('sezione-studenti');
            
            if (tipo === 'classe') {
                campoClasse.style.display = '';
                sezioneStudenti.style.display = '';
                // Carica studenti se classe già selezionata
                const classeSelect = document.getElementById('select_classe');
                if (classeSelect && classeSelect.value) {
                    sezioneStudenti.style.display = '';
                }
            } else {
                campoClasse.style.display = 'none';
                sezioneStudenti.style.display = 'none';
            }
        }
        
        function toggleTuttiStudenti(checkbox) {
            const checkboxes = document.querySelectorAll('.studente-checkbox:not(:disabled)');
            checkboxes.forEach(cb => {
                cb.checked = checkbox.checked;
            });
            aggiornaContatore();
        }
        
        function aggiornaContatore() {
            const selezionati = document.querySelectorAll('.studente-checkbox:checked').length;
            const contatore = document.getElementById('contatore-studenti');
            if (contatore) {
                contatore.textContent = selezionati;
            }
            
            const libroSelect = document.getElementById('libro_id');
            const btnPrenota = document.getElementById('btn-prenota');
            const copieInfo = document.getElementById('copie-info');
            
            if (libroSelect.value && document.getElementById('tipo_prestito').value === 'classe') {
                const copieDisponibili = parseInt(libroSelect.options[libroSelect.selectedIndex].dataset.copie);
                
                if (selezionati > copieDisponibili) {
                    btnPrenota.disabled = true;
                    copieInfo.textContent = `⚠️ Insufficienti! Disponibili: ${copieDisponibili}`;
                    copieInfo.style.color = '#f56565';
                } else {
                    btnPrenota.disabled = false;
                    copieInfo.textContent = `✓ OK (${copieDisponibili} disponibili)`;
                    copieInfo.style.color = '#48bb78';
                }
            }
        }
        
        document.getElementById('libro_id').addEventListener('change', aggiornaContatore);
        
        // Inizializza e mantieni stato dopo redirect
        window.addEventListener('DOMContentLoaded', function() {
            // Se siamo tornati da selezione classe, mantieni tipo_prestito = classe
            <?php if (isset($_GET['classe_id'])): ?>
                document.getElementById('tipo_prestito').value = 'classe';
            <?php endif; ?>
            
            toggleTipoPrestito();
            aggiornaContatore();
        });
    </script>
</body>
</html>
