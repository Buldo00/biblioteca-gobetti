<?php
/**
 * Header Responsive con Menu Hamburger - Biblioteca Gobetti
 */

if (!isLogged()) {
    header("Location: /biblioteca_gobetti/index.php");
    exit;
}

$user = getCurrentUser();
$livelli_nomi = [
    100 => 'Studente',
    300 => 'Docente',
    320 => 'Bibliotecario',
    400 => 'Tecnico',
    500 => 'Collaboratore',
    600 => 'Amministrativo',
    900 => 'Dirigente'
];
$ruolo = $livelli_nomi[$user['livello']] ?? 'Utente';
?>

<header class="header">
    <div class="header-content">
        <div class="header-logo">
            <h1>📚 Biblioteca Gobetti</h1>
        </div>
        
        <!-- Hamburger Button (visibile solo su mobile) -->
        <button class="hamburger-menu" id="hamburger-btn" aria-label="Menu">
            <span></span>
            <span></span>
            <span></span>
        </button>
        
        <!-- Navigation Menu -->
        <nav class="header-nav" id="main-nav">
            <div class="nav-links">
                <a href="/biblioteca_gobetti/user/dashboard.php">Dashboard</a>
                <a href="/biblioteca_gobetti/user/catalogo.php">Catalogo</a>
                <a href="/biblioteca_gobetti/user/prestiti.php">Prestiti</a>
                <a href="/biblioteca_gobetti/user/prenotazioni.php">Prenotazioni</a>
                
                <?php if (hasMinLevel(LIVELLO_DOCENTE)): ?>
                    <a href="/biblioteca_gobetti/user/prestiti_avanzati.php">Gestione Prestiti</a>
                <?php endif; ?>
                
                <?php if (hasMinLevel(LIVELLO_BIBLIOTECARIO)): ?>
                    <a href="/biblioteca_gobetti/admin/gestione_prestiti.php">Gestione</a>
                    <a href="/biblioteca_gobetti/admin/gestione_utenti.php">Utenti</a>
                    <a href="/biblioteca_gobetti/admin/gestione_blacklist.php">Blacklist</a>
                <?php endif; ?>
                
                <?php if (hasMinLevel(LIVELLO_AMMINISTRATIVO)): ?>
                    <a href="/biblioteca_gobetti/admin/impostazioni.php">Impostazioni</a>
                <?php endif; ?>
            </div>
            
            <div class="user-info">
                <?php if ($user['in_blacklist']): ?>
                    <span class="blacklist-warning">⚠️ BLACKLIST</span>
                <?php endif; ?>
                
                <span class="user-badge">
                    <?php echo e($user['nome'] . ' ' . $user['cognome']); ?>
                    <br>
                    <small><?php echo $ruolo; ?></small>
                </span>
                
                <form method="POST" action="/biblioteca_gobetti/user/logout.php" style="display: inline;">
                    <button type="submit" class="btn btn-secondary btn-sm">Logout</button>
                </form>
            </div>
        </nav>
    </div>
</header>

<!-- Overlay per chiudere menu su mobile -->
<div class="menu-overlay" id="menu-overlay"></div>

<script>
// Menu Hamburger Toggle
document.addEventListener('DOMContentLoaded', function() {
    const hamburger = document.getElementById('hamburger-btn');
    const nav = document.getElementById('main-nav');
    const overlay = document.getElementById('menu-overlay');
    const body = document.body;
    
    function toggleMenu() {
        hamburger.classList.toggle('active');
        nav.classList.toggle('active');
        overlay.classList.toggle('active');
        body.classList.toggle('menu-open');
    }
    
    function closeMenu() {
        hamburger.classList.remove('active');
        nav.classList.remove('active');
        overlay.classList.remove('active');
        body.classList.remove('menu-open');
    }
    
    // Click hamburger
    hamburger.addEventListener('click', toggleMenu);
    
    // Click overlay
    overlay.addEventListener('click', closeMenu);
    
    // Click link nel menu (chiude menu su mobile)
    const navLinks = nav.querySelectorAll('a');
    navLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 768) {
                closeMenu();
            }
        });
    });
    
    // ESC key per chiudere
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && nav.classList.contains('active')) {
            closeMenu();
        }
    });
});
</script>
