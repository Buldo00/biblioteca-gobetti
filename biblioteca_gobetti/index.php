<?php
/**
 * Pagina di Login - Biblioteca Gobetti
 */
session_start();

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

// Se già loggato, redirect alla dashboard
if (isLogged()) {
    header('Location: user/dashboard.php');
    exit;
}

$errore = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $errore = 'Inserisci email e password.';
    } else {
        if (login($email, $password)) {
            header('Location: user/dashboard.php');
            exit;
        } else {
            $errore = 'Email o password non validi.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Biblioteca Gobetti</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <i class="fas fa-book-open login-icon"></i>
                <h1>Biblioteca Gobetti</h1>
                <p>Sistema di Gestione Biblioteca Scolastica</p>
            </div>
            
            <?php if ($errore): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($errore) ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" class="login-form" autocomplete="off">
                <div class="form-group">
                    <label for="email"><i class="fas fa-envelope"></i> Email</label>
                    <input type="email" id="email" name="email" class="form-control" 
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                           placeholder="nome.cognome@gobettire.istruzioneer.it" required autofocus>
                </div>
                
                <div class="form-group">
                    <label for="password"><i class="fas fa-lock"></i> Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="password" name="password" class="form-control" 
                               placeholder="Inserisci la password" required
                               oncopy="return false" onpaste="return false" oncut="return false">
                        <button type="button" class="password-toggle" id="togglePassword" 
                                onmousedown="showPassword()" onmouseup="hidePassword()" 
                                onmouseleave="hidePassword()" ontouchstart="showPassword()" 
                                ontouchend="hidePassword()">
                            <i class="fas fa-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-sign-in-alt"></i> Accedi
                </button>
            </form>
            
            <div class="login-footer">
                <small>Account di test (password: <code>password123</code>):</small>
                <div class="test-accounts">
                    <span class="badge badge-info">s.christian.aicardi@gobettire.istruzioneer.it (Studente)</span>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    function showPassword() {
        document.getElementById('password').type = 'text';
        document.getElementById('eyeIcon').className = 'fas fa-eye-slash';
    }
    function hidePassword() {
        document.getElementById('password').type = 'password';
        document.getElementById('eyeIcon').className = 'fas fa-eye';
    }
    // Prevent copy/paste on password
    document.getElementById('password').addEventListener('paste', e => e.preventDefault());
    document.getElementById('password').addEventListener('copy', e => e.preventDefault());
    document.getElementById('password').addEventListener('cut', e => e.preventDefault());
    </script>
</body>
</html>
