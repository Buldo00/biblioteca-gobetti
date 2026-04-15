<?php
/**
 * Header Comune - Biblioteca Gobetti
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
        <h1>📚 Biblioteca Gobetti</h1>
        
        <nav class="header-nav">
            <a href="/biblioteca_gobetti/user/dashboard.php">Dashboard</a>
            <a href="/biblioteca_gobetti/user/catalogo.php">Catalogo</a>
            <a href="/biblioteca_gobetti/user/prestiti.php">Prestiti</a>
            <a href="/biblioteca_gobetti/user/prenotazioni.php">Prenotazioni</a>
            
            <?php if (hasMinLevel(LIVELLO_DOCENTE)): ?>
                <a href="/biblioteca_gobetti/user/prenotazioni_classe.php">Classe</a>
            <?php endif; ?>
            
            <?php if (hasMinLevel(LIVELLO_BIBLIOTECARIO)): ?>
                <a href="/biblioteca_gobetti/admin/gestione_prestiti.php">Gestione</a>
            <?php endif; ?>
            
            <?php if (hasMinLevel(LIVELLO_AMMINISTRATIVO)): ?>
                <a href="/biblioteca_gobetti/admin/impostazioni.php">Impostazioni</a>
            <?php endif; ?>
            
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
