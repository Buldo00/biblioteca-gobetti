<?php
/**
 * Ottimizzazioni Performance - Biblioteca Gobetti
 * Includere all'inizio di ogni pagina
 */

// Abilita compressione output
if (!ob_start('ob_gzhandler')) {
    ob_start();
}

// Headers per caching
header('Cache-Control: public, max-age=3600'); // 1 ora di cache
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT');

// Headers sicurezza e performance
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');

// Disabilita cache per pagine autenticate (opzionale, commentare se si vuole cache)
if (isset($_SESSION['user_id'])) {
    header('Cache-Control: private, no-cache, must-revalidate');
    header('Pragma: no-cache');
}
?>
