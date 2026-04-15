<?php
/**
 * API Annullamento Prenotazione - Biblioteca Gobetti
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isLogged()) {
    echo json_encode(['success' => false, 'message' => 'Non autenticato']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Metodo non valido']);
    exit;
}

$userId = $_SESSION['biblioteca_user_id'];
$idPrenotazione = (int)($_POST['id_prenotazione'] ?? 0);

if (!$idPrenotazione) {
    echo json_encode(['success' => false, 'message' => 'Prenotazione non specificata']);
    exit;
}

$result = annullaPrenotazione($idPrenotazione, $userId);
if ($result) {
    logOperazione($userId, 'annullamento_prenotazione', 'biblioteca_prenotazioni', $idPrenotazione, 'Prenotazione annullata');
    echo json_encode(['success' => true, 'message' => 'Prenotazione annullata con successo.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Errore durante l\'annullamento della prenotazione.']);
}
