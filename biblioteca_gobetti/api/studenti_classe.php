<?php
/**
 * API Studenti per Classe - Biblioteca Gobetti
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isLogged() || !hasMinLevel(LIVELLO_DOCENTE)) {
    echo json_encode(['success' => false, 'message' => 'Non autorizzato']);
    exit;
}

$idClasse = (int)($_GET['id_classe'] ?? 0);
if (!$idClasse) {
    echo json_encode(['success' => false, 'message' => 'Classe non specificata']);
    exit;
}

$studenti = getStudentiClasse($idClasse);
$result = [];

foreach ($studenti as $s) {
    $prestitiAttivi = contaPrestitiAttivi($s['IDUtente']);
    $maxPrestiti = (int)getSetting('max_prestiti_studente', 3);
    $inBlacklist = isInBlacklist($s['IDUtente']);

    $result[] = [
        'id_utente' => $s['IDUtente'],
        'nome' => $s['nome'],
        'cognome' => $s['cognome'],
        'classe' => $s['anno'] . $s['sezione'],
        'prestiti_attivi' => $prestitiAttivi,
        'max_prestiti' => $maxPrestiti,
        'puo_prenotare' => ($prestitiAttivi < $maxPrestiti) && !$inBlacklist,
        'in_blacklist' => $inBlacklist
    ];
}

echo json_encode(['success' => true, 'studenti' => $result]);
