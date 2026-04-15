<?php
/**
 * API Richiedi Notifica - Biblioteca Gobetti
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

if (!$libro_id && !$dispositivo_id) {
    echo json_encode(['success' => false, 'message' => 'Specifica libro o dispositivo']);
    exit;
}

$user_id = $_SESSION['user_id'];
$db = getDB();

try {
    // Verifica se già richiesto
    $stmt = $db->prepare("
        SELECT id FROM notifiche_disponibilita 
        WHERE utente_id = ? AND libro_id = ? AND dispositivo_id = ? AND notificato = 0
    ");
    $stmt->execute([$user_id, $libro_id, $dispositivo_id]);
    
    if ($stmt->fetch()) {
        throw new Exception('Notifica già richiesta');
    }
    
    // Inserisci richiesta notifica
    $stmt = $db->prepare("
        INSERT INTO notifiche_disponibilita (utente_id, libro_id, dispositivo_id)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$user_id, $libro_id, $dispositivo_id]);
    
    logActivity($user_id, 'notifica_richiesta', 'notifiche_disponibilita', $db->lastInsertId(), 
        "Richiesta notifica per " . ($libro_id ? "libro $libro_id" : "dispositivo $dispositivo_id"));
    
    echo json_encode(['success' => true, 'message' => 'Ti avviseremo quando sarà disponibile']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
