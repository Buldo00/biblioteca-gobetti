<?php
/**
 * API Conferma Ritiro Prestito - Biblioteca Gobetti
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
$idPrestito = (int)($_POST['id_prestito'] ?? 0);
$tipo = $_POST['tipo'] ?? '';

if (!$idPrestito) {
    echo json_encode(['success' => false, 'message' => 'Prestito non specificato']);
    exit;
}

if (!in_array($tipo, ['bibliotecario', 'utente'], true)) {
    echo json_encode(['success' => false, 'message' => 'Tipo conferma non valido']);
    exit;
}

// Only librarians can confirm as 'bibliotecario'
if ($tipo === 'bibliotecario' && !hasMinLevel(LIVELLO_BIBLIOTECARIO)) {
    echo json_encode(['success' => false, 'message' => 'Non autorizzato per questa operazione']);
    exit;
}

$result = confermaRitiro($idPrestito, $tipo, $userId);
if ($result) {
    logOperazione($userId, 'conferma_ritiro', 'biblioteca_prestiti', $idPrestito, "Conferma ritiro ($tipo)");
    echo json_encode(['success' => true, 'message' => 'Ritiro confermato con successo.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Errore durante la conferma del ritiro.']);
}
