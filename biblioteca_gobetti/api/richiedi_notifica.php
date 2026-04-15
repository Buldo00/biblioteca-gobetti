<?php
/**
 * API Richiesta Notifica Disponibilità - Biblioteca Gobetti
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

$libro = getLibro($idLibro);
if (!$libro) {
    echo json_encode(['success' => false, 'message' => 'Libro non trovato']);
    exit;
}

$result = richiediNotifica($userId, $idLibro);
if ($result) {
    logOperazione($userId, 'richiesta_notifica', 'biblioteca_libri', $idLibro, "Notifica richiesta per: {$libro['titolo']}");
    echo json_encode(['success' => true, 'message' => 'Riceverai una notifica quando il libro sarà disponibile.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Hai già richiesto una notifica per questo libro.']);
}
