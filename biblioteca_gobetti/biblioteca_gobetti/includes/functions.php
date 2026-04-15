<?php
/**
 * Funzioni comuni - Biblioteca Gobetti
 */

require_once __DIR__ . '/../config/database.php';

// Avvia sessione se non già avviata
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Livelli di accesso
define('LIVELLO_STUDENTE', 100);
define('LIVELLO_DOCENTE', 300);
define('LIVELLO_BIBLIOTECARIO', 320); // Bibliotecari hanno privilegi completi come admin
define('LIVELLO_TECNICO', 400);
define('LIVELLO_COLLABORATORE', 500);
define('LIVELLO_AMMINISTRATIVO', 600);
define('LIVELLO_DIRIGENTE', 900);

/**
 * Verifica se l'utente è loggato
 */
function isLogged() {
    return isset($_SESSION['user_id']) && isset($_SESSION['livello']);
}

/**
 * Verifica se l'utente ha un livello minimo richiesto
 */
function hasMinLevel($livello_minimo) {
    if (!isLogged()) return false;
    return $_SESSION['livello'] >= $livello_minimo;
}

/**
 * Ottiene informazioni utente corrente
 */
function getCurrentUser() {
    if (!isLogged()) return null;
    
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM utenti WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

/**
 * Login utente
 */
function login($username, $password) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM utenti WHERE username = ? AND attivo = 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['livello'] = $user['livello'];
        $_SESSION['nome'] = $user['nome'];
        $_SESSION['cognome'] = $user['cognome'];
        $_SESSION['in_blacklist'] = $user['in_blacklist'];
        
        // Aggiorna ultimo accesso
        $stmt = $db->prepare("UPDATE utenti SET ultimo_accesso = NOW() WHERE id = ?");
        $stmt->execute([$user['id']]);
        
        logActivity($user['id'], 'login', 'utenti', $user['id'], 'Login effettuato');
        
        return true;
    }
    return false;
}

/**
 * Logout utente
 */
function logout() {
    if (isLogged()) {
        logActivity($_SESSION['user_id'], 'logout', 'utenti', $_SESSION['user_id'], 'Logout effettuato');
    }
    session_destroy();
    header("Location: /biblioteca_gobetti/index.php");
    exit;
}

/**
 * Redirect se non loggato
 */
function requireLogin() {
    if (!isLogged()) {
        header("Location: /biblioteca_gobetti/index.php");
        exit;
    }
}

/**
 * Redirect se non ha livello minimo
 */
function requireMinLevel($livello_minimo) {
    requireLogin();
    if (!hasMinLevel($livello_minimo)) {
        header("Location: /biblioteca_gobetti/user/dashboard.php");
        exit;
    }
}

/**
 * Log attività
 */
function logActivity($utente_id, $azione, $tabella = null, $record_id = null, $dettagli = null) {
    $db = getDB();
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    
    $stmt = $db->prepare("
        INSERT INTO log_attivita (utente_id, azione, tabella, record_id, dettagli, ip_address)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([$utente_id, $azione, $tabella, $record_id, $dettagli, $ip]);
}

/**
 * Ottiene impostazione
 */
function getSetting($chiave, $default = null) {
    $db = getDB();
    $stmt = $db->prepare("SELECT valore, tipo FROM impostazioni WHERE chiave = ?");
    $stmt->execute([$chiave]);
    $result = $stmt->fetch();
    
    if (!$result) return $default;
    
    $valore = $result['valore'];
    
    // Converti in base al tipo
    switch ($result['tipo']) {
        case 'int':
            return (int)$valore;
        case 'boolean':
            return filter_var($valore, FILTER_VALIDATE_BOOLEAN);
        case 'json':
            return json_decode($valore, true);
        default:
            return $valore;
    }
}

/**
 * Imposta valore impostazione
 */
function setSetting($chiave, $valore, $utente_id = null) {
    $db = getDB();
    
    // Converti in stringa se necessario
    if (is_array($valore) || is_object($valore)) {
        $valore = json_encode($valore);
    } elseif (is_bool($valore)) {
        $valore = $valore ? '1' : '0';
    }
    
    $stmt = $db->prepare("
        UPDATE impostazioni 
        SET valore = ?, modificato_da = ?
        WHERE chiave = ?
    ");
    
    return $stmt->execute([$valore, $utente_id, $chiave]);
}

/**
 * Conta prestiti attivi per utente
 */
function contaPrestitiAttivi($utente_id) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM prestiti 
        WHERE utente_id = ? AND stato IN ('attivo', 'in_ritardo')
    ");
    $stmt->execute([$utente_id]);
    return $stmt->fetch()['count'];
}

/**
 * Verifica se utente può prenotare
 */
function puoPrenotare($utente_id) {
    $user = getUserById($utente_id);
    
    // Verifica blacklist
    if ($user['in_blacklist']) {
        return ['ok' => false, 'messaggio' => 'Sei in blacklist. Restituisci i materiali in prestito.'];
    }
    
    // Bibliotecari e Admin hanno prestiti ILLIMITATI
    if ($user['livello'] >= LIVELLO_BIBLIOTECARIO) {
        return ['ok' => true];
    }
    
    // Studenti e Docenti hanno MAX 3 prestiti
    $max_prestiti = getSetting('max_prestiti_studente', 3);
    $prestiti_attivi = contaPrestitiAttivi($utente_id);
    
    if ($prestiti_attivi >= $max_prestiti) {
        return ['ok' => false, 'messaggio' => "Hai raggiunto il limite di $max_prestiti prestiti contemporanei."];
    }
    
    return ['ok' => true];
}

/**
 * Ottiene utente per ID
 */
function getUserById($id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM utenti WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

/**
 * Verifica scadenza prenotazioni e aggiorna stato
 */
function aggiornaScadenzePrenotazioni() {
    $db = getDB();
    
    // Trova prenotazioni scadute
    $stmt = $db->query("
        SELECT id, utente_id 
        FROM prenotazioni 
        WHERE stato = 'attiva' 
        AND data_scadenza_ritiro < NOW()
    ");
    
    $scadute = $stmt->fetchAll();
    
    foreach ($scadute as $prenotazione) {
        // Aggiorna stato
        $update = $db->prepare("UPDATE prenotazioni SET stato = 'scaduta' WHERE id = ?");
        $update->execute([$prenotazione['id']]);
        
        // Conta mancati ritiri
        $count_stmt = $db->prepare("
            SELECT COUNT(*) as count 
            FROM prenotazioni 
            WHERE utente_id = ? AND stato = 'scaduta'
        ");
        $count_stmt->execute([$prenotazione['utente_id']]);
        $mancati_ritiri = $count_stmt->fetch()['count'];
        
        $max_mancati = getSetting('mancati_ritiri_blacklist', 3);
        
        // Se supera il limite, blacklist
        if ($mancati_ritiri >= $max_mancati) {
            aggiungiBlacklist($prenotazione['utente_id'], 'mancato_ritiro', 
                "Superato limite di $max_mancati mancati ritiri", null, $prenotazione['id']);
        }
    }
}

/**
 * Verifica ritardi prestiti e aggiorna stato
 */
function aggiornaRitardiPrestiti() {
    $db = getDB();
    
    // Trova prestiti in ritardo
    $stmt = $db->query("
        SELECT id, utente_id, data_scadenza,
               DATEDIFF(NOW(), data_scadenza) as giorni_ritardo
        FROM prestiti 
        WHERE stato = 'attivo' 
        AND data_scadenza < NOW()
    ");
    
    $in_ritardo = $stmt->fetchAll();
    
    foreach ($in_ritardo as $prestito) {
        // Aggiorna stato e giorni ritardo
        $update = $db->prepare("
            UPDATE prestiti 
            SET stato = 'in_ritardo', giorni_ritardo = ?
            WHERE id = ?
        ");
        $update->execute([$prestito['giorni_ritardo'], $prestito['id']]);
        
        $max_giorni = getSetting('giorni_ritardo_blacklist', 7);
        
        // Se supera il limite, blacklist
        if ($prestito['giorni_ritardo'] >= $max_giorni) {
            aggiungiBlacklist($prestito['utente_id'], 'ritardo', 
                "Ritardo di {$prestito['giorni_ritardo']} giorni", $prestito['id'], null);
        }
    }
}

/**
 * Aggiunge utente alla blacklist
 */
function aggiungiBlacklist($utente_id, $motivo, $dettagli = null, $prestito_id = null, $prenotazione_id = null) {
    $db = getDB();
    
    // Verifica se già in blacklist
    $check = $db->prepare("SELECT in_blacklist FROM utenti WHERE id = ?");
    $check->execute([$utente_id]);
    $user = $check->fetch();
    
    if ($user['in_blacklist']) return false; // Già in blacklist
    
    // Aggiorna utente
    $stmt = $db->prepare("
        UPDATE utenti 
        SET in_blacklist = 1, 
            motivo_blacklist = ?,
            data_inizio_blacklist = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$dettagli, $utente_id]);
    
    // Log blacklist
    $log = $db->prepare("
        INSERT INTO blacklist_log (utente_id, motivo, dettagli, prestito_id, prenotazione_id)
        VALUES (?, ?, ?, ?, ?)
    ");
    $log->execute([$utente_id, $motivo, $dettagli, $prestito_id, $prenotazione_id]);
    
    logActivity($utente_id, 'blacklist_aggiunto', 'utenti', $utente_id, $dettagli);
    
    // Invia email notifica (da implementare)
    
    return true;
}

/**
 * Rimuove utente dalla blacklist
 */
function rimuoviBlacklist($utente_id, $eseguito_da = null) {
    $db = getDB();
    
    // Verifica se tutti i prestiti sono stati restituiti
    $check = $db->prepare("
        SELECT COUNT(*) as count 
        FROM prestiti 
        WHERE utente_id = ? AND stato IN ('attivo', 'in_ritardo')
    ");
    $check->execute([$utente_id]);
    
    if ($check->fetch()['count'] > 0) {
        return ['ok' => false, 'messaggio' => 'L\'utente ha ancora prestiti attivi'];
    }
    
    // Rimuovi da blacklist
    $stmt = $db->prepare("
        UPDATE utenti 
        SET in_blacklist = 0,
            motivo_blacklist = NULL,
            data_inizio_blacklist = NULL
        WHERE id = ?
    ");
    $stmt->execute([$utente_id]);
    
    // Chiudi log blacklist attivi
    $log = $db->prepare("
        UPDATE blacklist_log 
        SET attiva = 0, data_fine = NOW()
        WHERE utente_id = ? AND attiva = 1
    ");
    $log->execute([$utente_id]);
    
    logActivity($eseguito_da ?? $utente_id, 'blacklist_rimosso', 'utenti', $utente_id, 'Utente rimosso dalla blacklist');
    
    return ['ok' => true, 'messaggio' => 'Utente rimosso dalla blacklist'];
}

/**
 * Formatta data italiana
 */
function formatData($data, $includi_ora = false) {
    if (!$data) return '-';
    $timestamp = strtotime($data);
    if ($includi_ora) {
        return date('d/m/Y H:i', $timestamp);
    }
    return date('d/m/Y', $timestamp);
}

/**
 * Escape HTML
 */
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Genera token CSRF
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifica token CSRF
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Invia email (da configurare con SMTP reale)
 */
function sendEmail($to, $subject, $message) {
    // TODO: Implementare invio email reale con PHPMailer o simile
    // Per ora solo log
    error_log("Email to: $to - Subject: $subject");
    return true;
}

/**
 * Notifica disponibilità libro
 */
function notificaDisponibilita($libro_id) {
    $db = getDB();
    
    // Trova utenti in attesa
    $stmt = $db->prepare("
        SELECT n.*, u.email, u.nome, u.cognome, l.titolo
        FROM notifiche_disponibilita n
        JOIN utenti u ON n.utente_id = u.id
        JOIN libri l ON n.libro_id = l.id
        WHERE n.libro_id = ? AND n.notificato = 0
    ");
    $stmt->execute([$libro_id]);
    $notifiche = $stmt->fetchAll();
    
    foreach ($notifiche as $notifica) {
        // Invia email
        $subject = "Libro disponibile: {$notifica['titolo']}";
        $message = "Ciao {$notifica['nome']},\n\nIl libro '{$notifica['titolo']}' che avevi richiesto è ora disponibile!\n\nPuoi prenotarlo al seguente link:\n[LINK]\n\nBiblioteca Gobetti";
        
        sendEmail($notifica['email'], $subject, $message);
        
        // Aggiorna notifica
        $update = $db->prepare("
            UPDATE notifiche_disponibilita 
            SET notificato = 1, data_notifica = NOW()
            WHERE id = ?
        ");
        $update->execute([$notifica['id']]);
    }
}

// Esegui controlli automatici (da chiamare tramite cron o all'accesso)
if (isLogged()) {
    aggiornaScadenzePrenotazioni();
    aggiornaRitardiPrestiti();
}
?>
