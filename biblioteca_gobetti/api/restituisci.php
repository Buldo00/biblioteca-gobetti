<?php
/**
 * API Restituzione Libro - Biblioteca Gobetti
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isLogged() || !hasMinLevel(LIVELLO_BIBLIOTECARIO)) {
    echo json_encode(['success' => false, 'message' => 'Non autorizzato']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Metodo non valido']);
    exit;
}

$userId = $_SESSION['biblioteca_user_id'];
$idPrestito = (int)($_POST['id_prestito'] ?? 0);

if (!$idPrestito) {
    echo json_encode(['success' => false, 'message' => 'Prestito non specificato']);
    exit;
}

$result = restituisciLibro($idPrestito);
if ($result) {
    logOperazione($userId, 'restituzione', 'biblioteca_prestiti', $idPrestito, 'Libro restituito');
    echo json_encode(['success' => true, 'message' => 'Libro restituito con successo.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Errore durante la restituzione del libro.']);
}
