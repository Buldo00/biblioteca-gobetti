<?php
/**
 * Pagina di Login - Biblioteca Gobetti
 */

require_once 'includes/functions.php';

// Se già loggato, redirect alla dashboard
if (isLogged()) {
    header("Location: user/dashboard.php");
    exit;
}

$error = '';

// Gestione login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Inserisci username e password';
    } else {
        if (login($username, $password)) {
            header("Location: user/dashboard.php");
            exit;
        } else {
            $error = 'Credenziali non valide. Verifica username e password.';
            // Per debug (commentare in produzione):
            // $error .= " <a href='test_sistema.php' target='_blank' style='color: white;'>Testa il sistema</a>";
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
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1>📚 Biblioteca Gobetti</h1>
                <p>Sistema di Gestione Prestiti</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <?php echo e($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username" class="form-label">Username</label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        class="form-control" 
                        required 
                        autofocus
                        value="<?php echo e($_POST['username'] ?? ''); ?>"
                    >
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <div style="position: relative;">
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="form-control" 
                            required
                            onpaste="return false"
                            oncopy="return false"
                            oncut="return false"
                            style="padding-right: 45px;"
                        >
                        <button 
                            type="button" 
                            id="togglePassword"
                            style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; font-size: 1.2rem; padding: 5px;"
                            onmousedown="document.getElementById('password').type = 'text'"
                            onmouseup="document.getElementById('password').type = 'password'"
                            onmouseleave="document.getElementById('password').type = 'password'"
                            ontouchstart="document.getElementById('password').type = 'text'"
                            ontouchend="document.getElementById('password').type = 'password'">
                            👁️
                        </button>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">
                    Accedi
                </button>
            </form>
            
            <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ecf0f1;">
                <h3 style="font-size: 1.1rem; margin-bottom: 15px; color: #2c3e50;">Account di Test:</h3>
                <div style="font-size: 0.9rem; color: #7f8c8d; line-height: 1.8;">
                    <strong>Admin:</strong> admin / password<br>
                    <strong>Bibliotecario:</strong> bibliotecario1 / password<br>
                    <strong>Docente:</strong> docente1 / password<br>
                    <strong>Studente:</strong> studente1 / password
                </div>
            </div>
        </div>
    </div>
    
    <script src="assets/js/main.js"></script>
</body>
</html>
