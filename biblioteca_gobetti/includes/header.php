<?php
/**
 * Header navigazione - Biblioteca Gobetti
 * Responsive con hamburger menu per mobile
 */

if (!defined('HEADER_LOADED')) {
    define('HEADER_LOADED', true);
}

$currentUser = getCurrentUser();
$livello = $currentUser['livello'] ?? 0;
$headerColor = getLevelColor($livello);
$baseUrl = getBaseUrl();
$inBlacklist = isInBlacklist();
$currentPage = basename($_SERVER['SCRIPT_NAME']);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biblioteca Gobetti</title>
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root { --header-color: <?= $headerColor ?>; }
    </style>
</head>
<body>
    <header class="main-header" style="background-color: <?= $headerColor ?>;">
        <div class="header-container">
            <div class="header-brand">
                <button class="hamburger" id="hamburgerBtn" aria-label="Menu">
                    <i class="fas fa-bars"></i>
                </button>
                <a href="<?= $baseUrl ?>/user/dashboard.php" class="brand-link">
                    <i class="fas fa-book-open"></i>
                    <span class="brand-text">Biblioteca Gobetti</span>
                </a>
            </div>
            
            <nav class="main-nav" id="mainNav">
                <ul class="nav-list">
                    <li><a href="<?= $baseUrl ?>/user/dashboard.php" class="nav-link <?= $currentPage == 'dashboard.php' ? 'active' : '' ?>">
                        <i class="fas fa-home"></i> <span>Dashboard</span>
                    </a></li>
                    <li><a href="<?= $baseUrl ?>/user/catalogo.php" class="nav-link <?= $currentPage == 'catalogo.php' ? 'active' : '' ?>">
                        <i class="fas fa-search"></i> <span>Catalogo</span>
                    </a></li>
                    <li><a href="<?= $baseUrl ?>/user/prestiti.php" class="nav-link <?= $currentPage == 'prestiti.php' ? 'active' : '' ?>">
                        <i class="fas fa-book-reader"></i> <span>I Miei Prestiti</span>
                    </a></li>
                    
                    <?php if ($livello >= LIVELLO_BIBLIOTECARIO): ?>
                    <li class="nav-divider"></li>
                    <li><a href="<?= $baseUrl ?>/admin/gestione_libri.php" class="nav-link <?= $currentPage == 'gestione_libri.php' ? 'active' : '' ?>">
                        <i class="fas fa-books"></i> <span>Gestione Libri</span>
                    </a></li>
                    <li><a href="<?= $baseUrl ?>/admin/gestione_prestiti.php" class="nav-link <?= $currentPage == 'gestione_prestiti.php' ? 'active' : '' ?>">
                        <i class="fas fa-exchange-alt"></i> <span>Gestione Prestiti</span>
                    </a></li>
                    <li><a href="<?= $baseUrl ?>/admin/gestione_blacklist.php" class="nav-link <?= $currentPage == 'gestione_blacklist.php' ? 'active' : '' ?>">
                        <i class="fas fa-ban"></i> <span>Blacklist</span>
                    </a></li>
                    <li><a href="<?= $baseUrl ?>/admin/gestione_utenti.php" class="nav-link <?= $currentPage == 'gestione_utenti.php' ? 'active' : '' ?>">
                        <i class="fas fa-users-cog"></i> <span>Gestione Utenti</span>
                    </a></li>
                    <?php endif; ?>
                    
                    <?php if ($livello >= LIVELLO_ADMIN): ?>
                    <li class="nav-divider"></li>
                    <li><a href="<?= $baseUrl ?>/admin/impostazioni.php" class="nav-link <?= $currentPage == 'impostazioni.php' ? 'active' : '' ?>">
                        <i class="fas fa-cog"></i> <span>Impostazioni</span>
                    </a></li>
                    <?php endif; ?>
                    
                    <?php if ($livello >= LIVELLO_BIBLIOTECARIO): ?>
                    <li><a href="<?= $baseUrl ?>/admin/statistiche.php" class="nav-link <?= $currentPage == 'statistiche.php' ? 'active' : '' ?>">
                        <i class="fas fa-chart-bar"></i> <span>Statistiche</span>
                    </a></li>
                    <?php endif; ?>
                </ul>
            </nav>
            
            <div class="header-user">
                <?php if ($inBlacklist): ?>
                <span class="badge badge-danger" title="Sei in blacklist">
                    <i class="fas fa-exclamation-triangle"></i>
                </span>
                <?php endif; ?>
                <span class="user-info">
                    <span class="user-name"><?= htmlspecialchars($currentUser['nome'] . ' ' . $currentUser['cognome']) ?></span>
                    <span class="user-role"><?= htmlspecialchars($currentUser['ruolo']) ?></span>
                </span>
                <a href="<?= $baseUrl ?>/user/logout.php" class="btn-logout" title="Esci">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
    </header>
    
    <?php if ($inBlacklist): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-triangle"></i> 
        Sei attualmente in blacklist. Non puoi effettuare prenotazioni fino alla restituzione dei libri in ritardo.
    </div>
    <?php endif; ?>
    
    <main class="main-content">
