<?php
/**
 * API Annulla Prenotazione - Biblioteca Gobetti
 */

require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isLogged()) {
    echo json_encode(['success' => false, 'message' => 'Non autenticato']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Metodo non consentito']);
    exit;
}

$prenotazione_id = $_GET['id'] ?? null;

if (!$prenotazione_id) {
    echo json_encode(['success' => false, 'message' => 'ID prenotazione mancante']);
    exit;
}

$user_id = $_SESSION['user_id'];
$db = getDB();

try {
    // Verifica che la prenotazione appartenga all'utente
    $stmt = $db->prepare("
        SELECT * FROM prenotazioni 
        WHERE id = ? AND utente_id = ? AND stato = 'attiva'
    ");
    $stmt->execute([$prenotazione_id, $user_id]);
    $prenotazione = $stmt->fetch();
    
    if (!$prenotazione) {
        throw new Exception('Prenotazione non trovata o non annullabile');
    }
    
    // Annulla prenotazione
    $stmt = $db->prepare("UPDATE prenotazioni SET stato = 'annullata' WHERE id = ?");
    $stmt->execute([$prenotazione_id]);
    
    // Log
    logActivity($user_id, 'prenotazione_annullata', 'prenotazioni', $prenotazione_id, 'Prenotazione annullata dall\'utente');
    
    echo json_encode(['success' => true, 'message' => 'Prenotazione annullata']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
