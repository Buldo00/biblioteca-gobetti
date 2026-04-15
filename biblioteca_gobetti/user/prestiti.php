<?php
/**
 * I Miei Prestiti - Biblioteca Gobetti
 * Visualizzazione prestiti e prenotazioni dell'utente
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$user = getCurrentUser();
$prestitiUtente = getPrestitiUtente($user['id']);
$prenotazioniUtente = getPrenotazioniUtente($user['id']);

// Messaggio di feedback
$messaggio = '';
$tipoMessaggio = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $azione = $_POST['azione'] ?? '';
    
    if ($azione === 'conferma_ritiro') {
        $idPrestito = (int)($_POST['id_prestito'] ?? 0);
        if ($idPrestito) {
            confermaRitiro($idPrestito, 'utente', $user['id']);
            $messaggio = 'Conferma di ritiro registrata. In attesa della conferma del bibliotecario.';
            $tipoMessaggio = 'success';
            // Refresh data
            $prestitiUtente = getPrestitiUtente($user['id']);
        }
    } elseif ($azione === 'annulla_prenotazione') {
        $idPrenotazione = (int)($_POST['id_prenotazione'] ?? 0);
        if ($idPrenotazione) {
            $risultato = annullaPrenotazione($idPrenotazione, $user['id']);
            if ($risultato) {
                $messaggio = 'Prenotazione annullata con successo.';
                $tipoMessaggio = 'success';
            } else {
                $messaggio = 'Impossibile annullare la prenotazione.';
                $tipoMessaggio = 'danger';
            }
            // Refresh data
            $prenotazioniUtente = getPrenotazioniUtente($user['id']);
            $prestitiUtente = getPrestitiUtente($user['id']);
        }
    }
}

// Separa prestiti per stato
$prestitiAttivi = array_filter($prestitiUtente, fn($p) => in_array($p['stato'], ['attivo', 'in_attesa', 'in_ritardo']));
$prestitiPassati = array_filter($prestitiUtente, fn($p) => $p['stato'] === 'restituito');
$prenotazioniAttive = array_filter($prenotazioniUtente, fn($p) => $p['stato'] === 'attiva');

include __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1><i class="fas fa-book-reader"></i> I Miei Prestiti</h1>
        <p class="subtitle">
            <?= count($prestitiAttivi) ?> prestiti attivi &middot; 
            <?= count($prenotazioniAttive) ?> prenotazioni in corso
        </p>
    </div>

    <?php if ($messaggio): ?>
    <div class="alert alert-<?= $tipoMessaggio ?>">
        <i class="fas fa-<?= $tipoMessaggio === 'success' ? 'check-circle' : ($tipoMessaggio === 'warning' ? 'exclamation-triangle' : 'times-circle') ?>"></i>
        <?= htmlspecialchars($messaggio) ?>
    </div>
    <?php endif; ?>

    <!-- Prenotazioni attive -->
    <?php if (!empty($prenotazioniAttive)): ?>
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-clock"></i> Prenotazioni da Ritirare</h2>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Libro</th>
                            <th>Autore</th>
                            <th>Data Prenotazione</th>
                            <th>Ritirare Entro</th>
                            <th>Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($prenotazioniAttive as $pren): ?>
                        <tr>
                            <td>
                                <a href="dettaglio_libro.php?id=<?= (int)$pren['id_libro'] ?>">
                                    <strong><?= htmlspecialchars($pren['titolo']) ?></strong>
                                </a>
                            </td>
                            <td><?= htmlspecialchars($pren['autore']) ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($pren['data_prenotazione'])) ?></td>
                            <td>
                                <?php
                                $scadenza = strtotime($pren['data_scadenza']);
                                $oggi = time();
                                $giorniRimasti = max(0, (int)ceil(($scadenza - $oggi) / 86400));
                                $urgente = $giorniRimasti <= 1;
                                ?>
                                <span class="badge <?= $urgente ? 'badge-danger' : 'badge-warning' ?>">
                                    <?= date('d/m/Y', $scadenza) ?>
                                    (<?= $giorniRimasti ?> giorn<?= $giorniRimasti === 1 ? 'o' : 'i' ?>)
                                </span>
                            </td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="azione" value="annulla_prenotazione">
                                    <input type="hidden" name="id_prenotazione" value="<?= (int)$pren['id_prenotazione'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm" 
                                            onclick="return confirm('Vuoi annullare questa prenotazione?')">
                                        <i class="fas fa-times"></i> Annulla
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Prestiti attivi -->
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-book"></i> Prestiti Attivi</h2>
        </div>
        <div class="card-body">
            <?php if (empty($prestitiAttivi)): ?>
            <div class="text-center" style="padding: var(--space-8);">
                <i class="fas fa-inbox" style="font-size: 2rem; color: var(--gray-400); margin-bottom: var(--space-4);"></i>
                <p style="color: var(--gray-500);">Nessun prestito attivo al momento.</p>
                <a href="catalogo.php" class="btn btn-primary btn-sm"><i class="fas fa-search"></i> Cerca un libro</a>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Libro</th>
                            <th>Autore</th>
                            <th>Copia</th>
                            <th>Stato</th>
                            <th>Scadenza</th>
                            <th>Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($prestitiAttivi as $p): ?>
                        <tr>
                            <td>
                                <a href="dettaglio_libro.php?id=<?= (int)$p['id_libro'] ?>">
                                    <strong><?= htmlspecialchars($p['titolo']) ?></strong>
                                </a>
                            </td>
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
                                <span class="badge <?= $badgeClass ?>">
                                    <?= ucfirst(str_replace('_', ' ', $p['stato'])) ?>
                                </span>
                                <?php if ($p['stato'] === 'in_attesa'): ?>
                                <br><small class="text-muted">
                                    Bibl: <?= $p['check_bibliotecario'] ? '✅' : '⏳' ?>
                                    &middot; Tu: <?= $p['check_utente'] ? '✅' : '⏳' ?>
                                </small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($p['data_scadenza']): ?>
                                    <?php
                                    $scadenza = strtotime($p['data_scadenza']);
                                    $oggi = time();
                                    $giorniRimasti = (int)ceil(($scadenza - $oggi) / 86400);
                                    ?>
                                    <?= date('d/m/Y', $scadenza) ?>
                                    <?php if ($p['stato'] === 'attivo'): ?>
                                    <br><small class="<?= $giorniRimasti <= 3 ? 'text-danger' : 'text-muted' ?>">
                                        <?php if ($giorniRimasti < 0): ?>
                                            <?= abs($giorniRimasti) ?> giorn<?= abs($giorniRimasti) === 1 ? 'o' : 'i' ?> di ritardo
                                        <?php else: ?>
                                            <?= $giorniRimasti ?> giorn<?= $giorniRimasti === 1 ? 'o' : 'i' ?> rimanent<?= $giorniRimasti === 1 ? 'e' : 'i' ?>
                                        <?php endif; ?>
                                    </small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($p['stato'] === 'in_attesa' && !$p['check_utente']): ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="azione" value="conferma_ritiro">
                                    <input type="hidden" name="id_prestito" value="<?= (int)$p['id_prestito'] ?>">
                                    <button type="submit" class="btn btn-success btn-sm" 
                                            onclick="return confirm('Confermi di aver ritirato questo libro?')">
                                        <i class="fas fa-check"></i> Conferma Ritiro
                                    </button>
                                </form>
                                <?php elseif ($p['stato'] === 'in_attesa' && $p['check_utente']): ?>
                                <span class="text-muted"><i class="fas fa-hourglass-half"></i> In attesa bibliotecario</span>
                                <?php else: ?>
                                <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Storico prestiti -->
    <?php if (!empty($prestitiPassati)): ?>
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-history"></i> Storico Prestiti</h2>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Libro</th>
                            <th>Autore</th>
                            <th>Prestito</th>
                            <th>Restituzione</th>
                            <th>Stato</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($prestitiPassati as $p): ?>
                        <tr>
                            <td>
                                <a href="dettaglio_libro.php?id=<?= (int)$p['id_libro'] ?>">
                                    <?= htmlspecialchars($p['titolo']) ?>
                                </a>
                            </td>
                            <td><?= htmlspecialchars($p['autore']) ?></td>
                            <td><?= $p['data_prestito'] ? date('d/m/Y', strtotime($p['data_prestito'])) : '-' ?></td>
                            <td><?= $p['data_restituzione'] ? date('d/m/Y', strtotime($p['data_restituzione'])) : '-' ?></td>
                            <td><span class="badge badge-light"><?= ucfirst(str_replace('_', ' ', $p['stato'])) ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
