<?php
/**
 * API Scansione QR Code - Biblioteca Gobetti
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isLogged()) {
    echo json_encode(['success' => false, 'message' => 'Non autenticato']);
    exit;
}

$qrCode = $_GET['qr_code'] ?? '';
if (empty($qrCode)) {
    echo json_encode(['success' => false, 'message' => 'QR code non specificato']);
    exit;
}

$copia = getCopiaByQR($qrCode);
if (!$copia) {
    echo json_encode(['success' => false, 'message' => 'Copia non trovata']);
    exit;
}

$livello = $_SESSION['biblioteca_user_livello'] ?? 0;
$baseUrl = getBaseUrl();

if ($livello >= LIVELLO_BIBLIOTECARIO) {
    // Bibliotecario: assignment or return
    if ($copia['stato'] === 'disponibile') {
        echo json_encode([
            'success' => true,
            'action' => 'assign',
            'message' => 'Copia disponibile - Assegnazione',
            'redirect' => $baseUrl . '/admin/gestione_prestiti.php?id_copia=' . $copia['id_copia'],
            'copia' => $copia
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'action' => 'return',
            'message' => 'Copia in prestito - Ritiro',
            'redirect' => $baseUrl . '/admin/gestione_prestiti.php?return_copia=' . $copia['id_copia'],
            'copia' => $copia
        ]);
    }
} else {
    // Student/Docente: show availability
    $libro = getLibro($copia['id_libro']);
    echo json_encode([
        'success' => true,
        'action' => 'view',
        'message' => $copia['stato'] === 'disponibile' ? 'Libro disponibile!' : 'Libro non disponibile',
        'disponibile' => $copia['stato'] === 'disponibile',
        'redirect' => $baseUrl . '/user/dettaglio_libro.php?id=' . $copia['id_libro'],
        'libro' => [
            'titolo' => $libro['titolo'],
            'autore' => $libro['autore'],
            'copie_disponibili' => max(0, $libro['copie_disponibili']),
            'totale_copie' => $libro['totale_copie']
        ]
    ]);
}
