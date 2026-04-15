<?php
/**
 * Test Connessione Database e Login
 * Usa questo file per diagnosticare problemi di login
 */

// Abilita visualizzazione errori
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>🔍 Diagnostica Sistema</h1>";

// Test 1: Connessione Database
echo "<h2>1️⃣ Test Connessione Database</h2>";
try {
    require_once 'config/database.php';
    $db = getDB();
    echo "<p style='color: green;'>✅ Connessione al database riuscita!</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Errore connessione: " . $e->getMessage() . "</p>";
    echo "<p><strong>Verifica:</strong></p>";
    echo "<ul>";
    echo "<li>Il database 'biblioteca_gobetti' esiste?</li>";
    echo "<li>Le credenziali in config/database.php sono corrette?</li>";
    echo "<li>MySQL è in esecuzione?</li>";
    echo "</ul>";
    exit;
}

// Test 2: Tabella Utenti
echo "<h2>2️⃣ Test Tabella Utenti</h2>";
try {
    $stmt = $db->query("SELECT COUNT(*) as count FROM utenti");
    $count = $stmt->fetch()['count'];
    echo "<p style='color: green;'>✅ Tabella utenti trovata! Utenti totali: <strong>$count</strong></p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Errore: " . $e->getMessage() . "</p>";
    echo "<p>La tabella 'utenti' non esiste. Hai importato il file database.sql?</p>";
    exit;
}

// Test 3: Utenti di test esistono?
echo "<h2>3️⃣ Test Utenti di Esempio</h2>";
$test_users = ['admin', 'bibliotecario1', 'docente1', 'studente1'];
foreach ($test_users as $username) {
    $stmt = $db->prepare("SELECT username, attivo FROM utenti WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user) {
        $status = $user['attivo'] ? "✅ Attivo" : "❌ Disattivato";
        echo "<p>$status - Username: <strong>$username</strong></p>";
    } else {
        echo "<p style='color: orange;'>⚠️ Username <strong>$username</strong> non trovato</p>";
    }
}

// Test 4: Test Password Hash
echo "<h2>4️⃣ Test Password Hash</h2>";
$test_password = 'password123';
$stmt = $db->prepare("SELECT username, password FROM utenti WHERE username = 'admin'");
$stmt->execute();
$admin = $stmt->fetch();

if ($admin) {
    echo "<p>Username admin trovato</p>";
    echo "<p>Hash nel database: <code style='font-size: 0.8em;'>" . substr($admin['password'], 0, 30) . "...</code></p>";
    
    if (password_verify($test_password, $admin['password'])) {
        echo "<p style='color: green; font-weight: bold;'>✅ La password 'password123' funziona correttamente!</p>";
    } else {
        echo "<p style='color: red; font-weight: bold;'>❌ La password nel database NON corrisponde a 'password123'</p>";
        echo "<p><strong>SOLUZIONE:</strong> Esegui il file <code>reset_password.php</code> per reimpostare le password.</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Utente admin non trovato!</p>";
}

// Test 5: Test funzione login
echo "<h2>5️⃣ Test Funzione Login</h2>";
try {
    require_once 'includes/functions.php';
    
    // Prova login con admin
    session_start();
    $login_result = login('admin', 'password123');
    
    if ($login_result) {
        echo "<p style='color: green; font-weight: bold;'>✅ Login funzionante! La funzione login() funziona correttamente.</p>";
        session_destroy(); // Pulisci sessione test
    } else {
        echo "<p style='color: red; font-weight: bold;'>❌ Login fallito! La password non corrisponde.</p>";
        echo "<p><strong>SOLUZIONE:</strong> Esegui <code>reset_password.php</code></p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Errore: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h2>📋 Riepilogo</h2>";
echo "<p>Se vedi errori ❌ sopra, segui le soluzioni indicate.</p>";
echo "<p><strong>Per reimpostare le password:</strong></p>";
echo "<ol>";
echo "<li>Vai a: <a href='reset_password.php'>reset_password.php</a></li>";
echo "<li>Le password verranno reimpostate a 'password123'</li>";
echo "<li>Torna alla <a href='index.php'>pagina di login</a></li>";
echo "</ol>";
?>
