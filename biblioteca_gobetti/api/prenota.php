<?php
/**
 * API Prenotazione Libro - Biblioteca Gobetti
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
$idLibro = (int)($_POST['id_libro'] ?? 0);

if (!$idLibro) {
    echo json_encode(['success' => false, 'message' => 'Libro non specificato']);
    exit;
}

// Check blacklist
if (isInBlacklist($userId)) {
    echo json_encode(['success' => false, 'message' => 'Sei in blacklist. Non puoi prenotare.']);
    exit;
}

// Check reservation limit
if (!puoPrenotare($userId)) {
    echo json_encode(['success' => false, 'message' => 'Hai raggiunto il limite massimo di prestiti.']);
    exit;
}

// Check availability
$libro = getLibro($idLibro);
if (!$libro || $libro['copie_disponibili'] <= 0) {
    echo json_encode(['success' => false, 'message' => 'Nessuna copia disponibile.']);
    exit;
}

$prenotazioneId = creaPrenotazione($userId, $idLibro);
if ($prenotazioneId) {
    logOperazione($userId, 'prenotazione', 'biblioteca_prenotazioni', $prenotazioneId, "Prenotazione libro: {$libro['titolo']}");
    echo json_encode(['success' => true, 'message' => 'Prenotazione effettuata con successo!', 'id' => $prenotazioneId]);
} else {
    echo json_encode(['success' => false, 'message' => 'Errore durante la prenotazione.']);
}
