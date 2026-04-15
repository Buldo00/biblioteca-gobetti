<?php
/**
 * Dashboard Utente - Biblioteca Gobetti
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$user = getCurrentUser();
$prestitiAttivi = contaPrestitiAttivi($user['id']);
$prestitiUtente = getPrestitiUtente($user['id']);
$prenotazioniUtente = getPrenotazioniUtente($user['id']);
$rimanenti = prestitiRimanenti($user['id']);

include __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
        <p class="subtitle">Benvenuto, <strong><?= htmlspecialchars($user['nome'] . ' ' . $user['cognome']) ?></strong>
        <?php if ($user['classe']): ?> - Classe <?= htmlspecialchars($user['classe']) ?><?php endif; ?></p>
    </div>
    
    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-book"></i></div>
            <div class="stat-info">
                <h3><?= $prestitiAttivi ?></h3>
                <p>Prestiti Attivi</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-clock"></i></div>
            <div class="stat-info">
                <h3><?= count(array_filter($prenotazioniUtente, fn($p) => $p['stato'] === 'attiva')) ?></h3>
                <p>Prenotazioni</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-bookmark"></i></div>
            <div class="stat-info">
                <h3><?= $rimanenti >= 0 ? $rimanenti : '∞' ?></h3>
                <p>Prestiti Disponibili</p>
            </div>
        </div>
    </div>
    
    <!-- Prestiti Attivi -->
    <?php 
    $prestitiAttiviList = array_filter($prestitiUtente, fn($p) => in_array($p['stato'], ['attivo','in_attesa','in_ritardo']));
    if (!empty($prestitiAttiviList)): ?>
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-book-reader"></i> I Tuoi Prestiti Attivi</h2>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Libro</th>
                            <th>Autore</th>
                            <th>Copia</th>
                            <th>Stato</th>
                            <th>Scadenza</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($prestitiAttiviList as $p): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($p['titolo']) ?></strong></td>
                            <td><?= htmlspecialchars($p['autore']) ?></td>
                            <td>#<?= (int)$p['numero_copia'] ?></td>
                            <td>
                                <?php 
                                $badgeClass = match($p['stato']) {
                                    'attivo' => 'badge-success',
                                    'in_attesa' => 'badge-warning',
                                    'in_ritardo' => 'badge-danger',
                                    default => 'badge-info'
                                };
                                ?>
                                <span class="badge <?= $badgeClass ?>"><?= ucfirst(str_replace('_', ' ', $p['stato'])) ?></span>
                            </td>
                            <td><?= $p['data_scadenza'] ? date('d/m/Y', strtotime($p['data_scadenza'])) : '-' ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Quick Links -->
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-link"></i> Azioni Rapide</h2>
        </div>
        <div class="card-body">
            <div class="quick-links">
                <a href="catalogo.php" class="quick-link-btn">
                    <i class="fas fa-search"></i> Cerca nel Catalogo
                </a>
                <a href="prestiti.php" class="quick-link-btn">
                    <i class="fas fa-book-reader"></i> I Miei Prestiti
                </a>
                <?php if ($user['livello'] >= LIVELLO_BIBLIOTECARIO): ?>
                <a href="<?= $baseUrl ?>/admin/gestione_libri.php" class="quick-link-btn">
                    <i class="fas fa-plus-circle"></i> Aggiungi Elemento
                </a>
                <a href="<?= $baseUrl ?>/admin/gestione_prestiti.php" class="quick-link-btn">
                    <i class="fas fa-exchange-alt"></i> Gestione Prestiti
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
