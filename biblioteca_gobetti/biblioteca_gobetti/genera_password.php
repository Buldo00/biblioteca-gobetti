<?php
/**
 * Script per generare hash password corretti
 */

// Genera hash per 'password123'
$password = 'password123';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "Hash generato per 'password123':\n";
echo $hash . "\n\n";

// Verifica che funzioni
if (password_verify($password, $hash)) {
    echo "✓ Verifica password OK\n";
} else {
    echo "✗ Verifica password FALLITA\n";
}

// Genera hash per tutti gli utenti
echo "\n--- SQL per aggiornare database ---\n\n";
echo "UPDATE utenti SET password = '$hash' WHERE username = 'admin';\n";
echo "UPDATE utenti SET password = '$hash' WHERE username = 'bibliotecario1';\n";
echo "UPDATE utenti SET password = '$hash' WHERE username = 'docente1';\n";
echo "UPDATE utenti SET password = '$hash' WHERE username = 'studente1';\n";
?>
