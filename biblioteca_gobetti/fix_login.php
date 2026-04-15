<?php
/**
 * FIX LOGIN - Resetta le password degli utenti di test
 * 
 * ISTRUZIONI:
 * 1. Carica questo file nella root del progetto
 * 2. Apri nel browser: http://localhost/biblioteca_gobetti/fix_login.php
 * 3. Le password verranno resettate a 'password123'
 * 4. Elimina questo file dopo l'uso per sicurezza
 */

require_once 'config/database.php';

try {
    $db = getDB();
    
    // Genera hash corretto per 'password123'
    $password = 'password123';
    $hash = password_hash($password, PASSWORD_DEFAULT);
    
    echo "<h1>🔧 Fix Login - Biblioteca Gobetti</h1>";
    echo "<p>Aggiornamento password in corso...</p>";
    echo "<hr>";
    
    // Aggiorna tutti gli utenti di test
    $utenti = [
        'admin' => 'Admin Sistema',
        'bibliotecario1' => 'Bibliotecario',
        'docente1' => 'Docente',
        'studente1' => 'Studente'
    ];
    
    foreach ($utenti as $username => $nome) {
        $stmt = $db->prepare("UPDATE utenti SET password = ? WHERE username = ?");
        $stmt->execute([$hash, $username]);
        
        if ($stmt->rowCount() > 0) {
            echo "✅ <strong>$nome</strong> ($username) - Password aggiornata<br>";
        } else {
            echo "⚠️ <strong>$nome</strong> ($username) - Utente non trovato<br>";
        }
    }
    
    echo "<hr>";
    echo "<h2>✅ Operazione Completata!</h2>";
    echo "<p>Tutti gli utenti ora hanno password: <strong>password123</strong></p>";
    echo "<p><a href='index.php' style='background: #3498db; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 20px;'>Vai al Login</a></p>";
    echo "<hr>";
    echo "<p style='color: red;'><strong>⚠️ IMPORTANTE:</strong> Elimina questo file (fix_login.php) dopo l'uso per sicurezza!</p>";
    
    // Verifica che il login funzioni
    echo "<h3>🧪 Test Login</h3>";
    $test = $db->prepare("SELECT username, password FROM utenti WHERE username = 'admin'");
    $test->execute();
    $user = $test->fetch();
    
    if ($user && password_verify('password123', $user['password'])) {
        echo "✅ Test verifica password: <strong style='color: green;'>OK</strong><br>";
        echo "Il login dovrebbe funzionare correttamente!";
    } else {
        echo "❌ Test verifica password: <strong style='color: red;'>FALLITO</strong><br>";
        echo "C'è ancora un problema. Contatta il supporto.";
    }
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ Errore</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<p>Assicurati che:</p>";
    echo "<ul>";
    echo "<li>Il database sia stato creato</li>";
    echo "<li>Il file config/database.php abbia le credenziali corrette</li>";
    echo "<li>MySQL sia in esecuzione</li>";
    echo "</ul>";
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Login - Biblioteca Gobetti</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f7fa;
        }
        h1 {
            color: #2c3e50;
        }
        hr {
            border: none;
            border-top: 2px solid #ecf0f1;
            margin: 20px 0;
        }
    </style>
</head>
<body>
</body>
</html>
