<?php
/**
 * Autenticazione e Gestione Sessione - Biblioteca Gobetti
 * Integra con il sistema utenti esistente della scuola
 */

session_start();

// Costanti livelli utente
define('LIVELLO_STUDENTE', 100);
define('LIVELLO_DOCENTE', 300);
define('LIVELLO_BIBLIOTECARIO', 320);
define('LIVELLO_TECNICO', 400);
define('LIVELLO_COLLABORATORE', 500);
define('LIVELLO_AMMINISTRATIVO', 600);
define('LIVELLO_DIRIGENTE', 900);
define('LIVELLO_ADMIN', 999);

require_once __DIR__ . '/../config/database.php';

/**
 * Verifica se l'utente è autenticato
 */
function isLogged() {
    return isset($_SESSION['biblioteca_user_id']);
}

/**
 * Effettua il login dell'utente
 * Utilizza le tabelle utenti e profili esistenti
 */
function login($email, $password) {
    $db = getDB();
    
    // Cerca l'utente nella tabella utenti esistente
    $stmt = $db->prepare("
        SELECT u.IDUtente, u.emailUtente, u.passwordUtente, u.statoUtente,
               COALESCE(p.nomeProfilo, s.nomeStu) as nome,
               COALESCE(p.cognomeProfilo, s.cognomeStu) as cognome
        FROM utenti u
        LEFT JOIN profili p ON p.idUtente = u.IDUtente
        LEFT JOIN studenti s ON s.IDUtente = u.IDUtente
        WHERE u.emailUtente = ? AND u.statoUtente = 1
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user || !$user['passwordUtente']) {
        return false;
    }
    
    if (!password_verify($password, $user['passwordUtente'])) {
        return false;
    }
    
    // Ottieni il livello più alto dell'utente
    $livello = getUserMaxLevel($user['IDUtente']);
    
    // Controlla se è uno studente e ottieni classe
    $studentInfo = getStudentInfo($user['IDUtente']);
    
    // Salva in sessione
    $_SESSION['biblioteca_user_id'] = $user['IDUtente'];
    $_SESSION['biblioteca_user_email'] = $user['emailUtente'];
    $_SESSION['biblioteca_user_nome'] = $user['nome'] ?: 'Utente';
    $_SESSION['biblioteca_user_cognome'] = $user['cognome'] ?: '';
    $_SESSION['biblioteca_user_livello'] = $livello;
    $_SESSION['biblioteca_user_ruolo'] = getLevelName($livello);
    
    if ($studentInfo) {
        $_SESSION['biblioteca_user_classe_id'] = $studentInfo['IDClasse'];
        $_SESSION['biblioteca_user_classe'] = $studentInfo['anno'] . $studentInfo['sezione'];
    }
    
    // Aggiorna numero accessi
    $db->prepare("UPDATE utenti SET numeroAccessi = numeroAccessi + 1 WHERE IDUtente = ?")->execute([$user['IDUtente']]);
    
    return true;
}

/**
 * Ottieni il livello massimo di un utente
 */
function getUserMaxLevel($userId) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT MAX(tl.livelloAccount) as max_livello
        FROM utenti_tipolivelli utl
        JOIN tipolivelli tl ON tl.IDTipoAccount = utl.idLivello
        WHERE utl.idUtente = ?
    ");
    $stmt->execute([$userId]);
    $result = $stmt->fetch();
    return $result['max_livello'] ?? LIVELLO_STUDENTE;
}

/**
 * Ottieni info studente (classe)
 */
function getStudentInfo($userId) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT s.IDStudente, s.IDClasse, c.anno, c.sezione
        FROM studenti s
        LEFT JOIN classi c ON c.IDClasse = s.IDClasse
        WHERE s.IDUtente = ?
    ");
    $stmt->execute([$userId]);
    return $stmt->fetch();
}

/**
 * Nome del livello utente
 */
function getLevelName($livello) {
    if ($livello >= 999) return 'Admin';
    if ($livello >= 900) return 'Dirigente';
    if ($livello >= 600) return 'Amministrativo';
    if ($livello >= 500) return 'Collaboratore';
    if ($livello >= 400) return 'Tecnico';
    if ($livello >= 320) return 'Bibliotecario';
    if ($livello >= 300) return 'Docente';
    return 'Studente';
}

/**
 * Ottieni colore header basato sul livello
 */
function getLevelColor($livello) {
    if ($livello >= 999) return '#c0392b';      // Admin - Rosso
    if ($livello >= 900) return '#8e44ad';      // Dirigente - Viola
    if ($livello >= 600) return '#2980b9';      // Amministrativo - Blu
    if ($livello >= 500) return '#16a085';      // Collaboratore - Teal
    if ($livello >= 400) return '#d35400';      // Tecnico - Arancione
    if ($livello >= 320) return '#27ae60';      // Bibliotecario - Verde
    if ($livello >= 300) return '#2c3e50';      // Docente - Blu scuro
    return '#3498db';                           // Studente - Azzurro
}

/**
 * Verifica se l'utente ha almeno il livello minimo
 */
function hasMinLevel($minLevel) {
    return isLogged() && ($_SESSION['biblioteca_user_livello'] ?? 0) >= $minLevel;
}

/**
 * Richiede login, reindirizza se non autenticato
 */
function requireLogin() {
    if (!isLogged()) {
        header('Location: ' . getBaseUrl() . '/index.php');
        exit;
    }
}

/**
 * Richiede un livello minimo
 */
function requireMinLevel($minLevel) {
    requireLogin();
    if (!hasMinLevel($minLevel)) {
        header('Location: ' . getBaseUrl() . '/user/dashboard.php?errore=permessi');
        exit;
    }
}

/**
 * Ottieni dati utente corrente dalla sessione
 */
function getCurrentUser() {
    if (!isLogged()) return null;
    return [
        'id' => $_SESSION['biblioteca_user_id'],
        'email' => $_SESSION['biblioteca_user_email'],
        'nome' => $_SESSION['biblioteca_user_nome'],
        'cognome' => $_SESSION['biblioteca_user_cognome'],
        'livello' => $_SESSION['biblioteca_user_livello'],
        'ruolo' => $_SESSION['biblioteca_user_ruolo'],
        'classe_id' => $_SESSION['biblioteca_user_classe_id'] ?? null,
        'classe' => $_SESSION['biblioteca_user_classe'] ?? null
    ];
}

/**
 * Logout
 */
function logout() {
    session_destroy();
    header('Location: ' . getBaseUrl() . '/index.php');
    exit;
}

/**
 * URL base del modulo biblioteca
 */
function getBaseUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptDir = dirname(dirname($_SERVER['SCRIPT_NAME']));
    // Ensure we get the biblioteca_gobetti directory
    if (basename($scriptDir) !== 'biblioteca_gobetti') {
        $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
        if (basename($scriptDir) !== 'biblioteca_gobetti') {
            $scriptDir = '/biblioteca_gobetti';
        }
    }
    return $protocol . '://' . $host . $scriptDir;
}

/**
 * Verifica se l'utente è in blacklist
 */
function isInBlacklist($userId = null) {
    $userId = $userId ?? ($_SESSION['biblioteca_user_id'] ?? 0);
    $db = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM biblioteca_blacklist WHERE id_utente = ? AND attiva = 1");
    $stmt->execute([$userId]);
    return $stmt->fetch()['cnt'] > 0;
}

/**
 * Verifica se l'utente può essere messo in blacklist
 * Admin e Bibliotecari NON possono essere messi in blacklist
 */
function canBeBlacklisted($userId) {
    $livello = getUserMaxLevel($userId);
    return $livello < LIVELLO_BIBLIOTECARIO;
}
