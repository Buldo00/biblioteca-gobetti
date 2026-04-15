<?php
/**
 * API Prenotazione - Biblioteca Gobetti
 */

require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isLogged()) {
    echo json_encode(['success' => false, 'message' => 'Non autenticato']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Metodo non consentito']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$libro_id = $data['libro_id'] ?? null;
$dispositivo_id = $data['dispositivo_id'] ?? null;
$tipo_prenotazione = $data['tipo_prenotazione'] ?? 'personale';
$classe_id = $data['classe_id'] ?? null;
$studenti_ids = $data['studenti_ids'] ?? [];

$user_id = $_SESSION['user_id'];
$user = getUserById($user_id);

// Verifica se può prenotare
$check = puoPrenotare($user_id);
if (!$check['ok']) {
    echo json_encode(['success' => false, 'message' => $check['messaggio']]);
    exit;
}

// Verifica che sia libro o dispositivo
if (!$libro_id && !$dispositivo_id) {
    echo json_encode(['success' => false, 'message' => 'Specifica libro o dispositivo']);
    exit;
}

$db = getDB();

try {
    $db->beginTransaction();
    
    // Verifica disponibilità
    if ($libro_id) {
        $stmt = $db->prepare("SELECT copie_disponibili FROM libri WHERE id = ? AND prenotabile = 1");
        $stmt->execute([$libro_id]);
        $libro = $stmt->fetch();
        
        if (!$libro || $libro['copie_disponibili'] <= 0) {
            throw new Exception('Libro non disponibile');
        }
    }
    
    if ($dispositivo_id) {
        $stmt = $db->prepare("SELECT stato FROM dispositivi WHERE id = ?");
        $stmt->execute([$dispositivo_id]);
        $dispositivo = $stmt->fetch();
        
        if (!$dispositivo || $dispositivo['stato'] !== 'disponibile') {
            throw new Exception('Dispositivo non disponibile');
        }
    }
    
    // Calcola data scadenza ritiro (3 giorni default)
    $giorni_ritiro = getSetting('giorni_ritiro_prenotazione', 3);
    $data_scadenza = date('Y-m-d H:i:s', strtotime("+$giorni_ritiro days"));
    
    // Se è prenotazione di classe, verifica che sia docente
    if ($tipo_prenotazione === 'classe' && $user['livello'] < LIVELLO_DOCENTE) {
        throw new Exception('Solo i docenti possono prenotare per la classe');
    }
    
    // Inserisci prenotazione
    $stmt = $db->prepare("
        INSERT INTO prenotazioni 
        (utente_id, libro_id, dispositivo_id, tipo_prenotazione, classe_id, studenti_selezionati, data_scadenza_ritiro)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    $studenti_json = !empty($studenti_ids) ? json_encode($studenti_ids) : null;
    
    $stmt->execute([
        $user_id,
        $libro_id,
        $dispositivo_id,
        $tipo_prenotazione,
        $classe_id,
        $studenti_json,
        $data_scadenza
    ]);
    
    $prenotazione_id = $db->lastInsertId();
    
    // Log attività
    logActivity($user_id, 'prenotazione_creata', 'prenotazioni', $prenotazione_id, 
        "Prenotato " . ($libro_id ? "libro ID $libro_id" : "dispositivo ID $dispositivo_id"));
    
    $db->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Prenotazione effettuata con successo',
        'prenotazione_id' => $prenotazione_id
    ]);
    
} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
