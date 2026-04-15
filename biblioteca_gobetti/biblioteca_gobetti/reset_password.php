<?php
/**
 * Script Reset Password - Da eseguire UNA VOLTA dopo l'installazione
 * IMPORTANTE: Elimina questo file dopo l'uso!
 */

require_once 'config/database.php';

echo "<h1>Reset Password Utenti</h1>";

$db = getDB();

// Password da impostare: password123
$password = 'password123';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "<p>Hash generato per 'password123': <code>$hash</code></p>";

// Aggiorna tutti gli utenti di esempio
$utenti = [
    'admin',
    'bibliotecario1',
    'docente1',
    'studente1'
];

try {
    $stmt = $db->prepare("UPDATE utenti SET password = ? WHERE username = ?");
    
    foreach ($utenti as $username) {
        $stmt->execute([$hash, $username]);
        echo "<p>✅ Password aggiornata per: <strong>$username</strong></p>";
    }
    
    echo "<hr>";
    echo "<h2 style='color: green;'>✅ Tutte le password sono state aggiornate!</h2>";
    echo "<p>Ora puoi fare login con:</p>";
    echo "<ul>";
    echo "<li><strong>Username:</strong> admin - <strong>Password:</strong> password123</li>";
    echo "<li><strong>Username:</strong> bibliotecario1 - <strong>Password:</strong> password123</li>";
    echo "<li><strong>Username:</strong> docente1 - <strong>Password:</strong> password123</li>";
    echo "<li><strong>Username:</strong> studente1 - <strong>Password:</strong> password123</li>";
    echo "</ul>";
    echo "<hr>";
    echo "<p style='color: red; font-weight: bold;'>⚠️ IMPORTANTE: Elimina questo file (reset_password.php) dopo aver fatto il primo login!</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Errore: " . $e->getMessage() . "</p>";
}
?>
