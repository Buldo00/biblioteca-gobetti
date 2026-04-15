<?php
/**
 * Dettaglio Libro - Biblioteca Gobetti
 * Visualizzazione completa informazioni libro
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$user = getCurrentUser();
$idLibro = (int)($_GET['id'] ?? 0);

if (!$idLibro) {
    header('Location: catalogo.php');
    exit;
}

$libro = getLibro($idLibro);
if (!$libro) {
    header('Location: catalogo.php?errore=libro_non_trovato');
    exit;
}

$copie = getCopieLibro($idLibro);
$disponibili = max(0, (int)$libro['copie_disponibili']);
$totCopie = (int)$libro['totale_copie'];
$puoPrenotareUtente = puoPrenotare($user['id']);
$inBlacklistUtente = isInBlacklist($user['id']);

// Messaggio di feedback
$messaggio = '';
$tipoMessaggio = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $azione = $_POST['azione'] ?? '';
    
    if ($azione === 'prenota' && $disponibili > 0 && $puoPrenotareUtente && !$inBlacklistUtente) {
        $risultato = creaPrenotazione($user['id'], $idLibro);
        if ($risultato) {
            $messaggio = 'Prenotazione effettuata con successo! Ritira il libro in biblioteca.';
            $tipoMessaggio = 'success';
            // Refresh data
            $libro = getLibro($idLibro);
            $copie = getCopieLibro($idLibro);
            $disponibili = max(0, (int)$libro['copie_disponibili']);
            $totCopie = (int)$libro['totale_copie'];
        } else {
            $messaggio = 'Impossibile effettuare la prenotazione. Nessuna copia disponibile.';
            $tipoMessaggio = 'danger';
        }
    } elseif ($azione === 'notifica') {
        $risultato = richiediNotifica($user['id'], $idLibro);
        if ($risultato) {
            $messaggio = 'Ti avviseremo quando il libro sarà di nuovo disponibile.';
            $tipoMessaggio = 'success';
        } else {
            $messaggio = 'Hai già richiesto una notifica per questo libro.';
            $tipoMessaggio = 'warning';
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <a href="catalogo.php" class="btn btn-outline btn-sm">
            <i class="fas fa-arrow-left"></i> Torna al catalogo
        </a>
    </div>

    <?php if ($messaggio): ?>
    <div class="alert alert-<?= $tipoMessaggio ?>">
        <i class="fas fa-<?= $tipoMessaggio === 'success' ? 'check-circle' : ($tipoMessaggio === 'warning' ? 'exclamation-triangle' : 'times-circle') ?>"></i>
        <?= htmlspecialchars($messaggio) ?>
    </div>
    <?php endif; ?>

    <div class="book-detail">
        <!-- Copertina e info principali -->
        <div class="book-detail-top">
            <div class="book-detail-cover">
                <?php if (!empty($libro['copertina'])): ?>
                <img src="<?= htmlspecialchars($libro['copertina']) ?>" alt="<?= htmlspecialchars($libro['titolo']) ?>">
                <?php else: ?>
                <div class="book-cover-placeholder large">
                    <i class="fas fa-book"></i>
                    <span><?= htmlspecialchars(mb_substr($libro['titolo'], 0, 50)) ?></span>
                </div>
                <?php endif; ?>
            </div>

            <div class="book-detail-info">
                <h1 class="book-detail-title"><?= htmlspecialchars($libro['titolo']) ?></h1>
                <p class="book-detail-author">
                    <i class="fas fa-user"></i> <?= htmlspecialchars($libro['autore'] ?: 'Autore sconosciuto') ?>
                </p>

                <!-- Disponibilità -->
                <div class="availability-box <?= $disponibili > 0 ? 'available' : 'unavailable' ?>">
                    <div class="availability-status">
                        <i class="fas fa-<?= $disponibili > 0 ? 'check-circle' : 'times-circle' ?>"></i>
                        <strong><?= $disponibili > 0 ? 'Disponibile' : 'Non disponibile' ?></strong>
                    </div>
                    <div class="availability-count">
                        <?= $disponibili ?> di <?= $totCopie ?> <?= $totCopie === 1 ? 'copia' : 'copie' ?> disponibil<?= $disponibili === 1 ? 'e' : 'i' ?>
                    </div>
                </div>

                <!-- Azioni -->
                <div class="book-actions">
                    <?php if ($inBlacklistUtente): ?>
                    <div class="alert alert-danger" style="margin: 0;">
                        <i class="fas fa-ban"></i> Sei in blacklist. Non puoi prenotare.
                    </div>
                    <?php elseif ($disponibili > 0 && $puoPrenotareUtente): ?>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="azione" value="prenota">
                        <button type="submit" class="btn btn-primary btn-lg" onclick="return confirm('Confermi la prenotazione di questo libro?')">
                            <i class="fas fa-bookmark"></i> Prenota questo libro
                        </button>
                    </form>
                    <?php elseif ($disponibili > 0 && !$puoPrenotareUtente): ?>
                    <div class="alert alert-warning" style="margin: 0;">
                        <i class="fas fa-exclamation-triangle"></i> Hai raggiunto il limite di prestiti.
                    </div>
                    <?php else: ?>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="azione" value="notifica">
                        <button type="submit" class="btn btn-warning btn-lg">
                            <i class="fas fa-bell"></i> Avvisami quando disponibile
                        </button>
                    </form>
                    <?php endif; ?>
                </div>

                <!-- Metadata tabella -->
                <div class="book-metadata">
                    <table class="metadata-table">
                        <?php if ($libro['casa_editrice']): ?>
                        <tr>
                            <th><i class="fas fa-building"></i> Casa Editrice</th>
                            <td><?= htmlspecialchars($libro['casa_editrice']) ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if ($libro['anno_pubblicazione']): ?>
                        <tr>
                            <th><i class="fas fa-calendar"></i> Anno</th>
                            <td><?= (int)$libro['anno_pubblicazione'] ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if ($libro['lingua']): ?>
                        <tr>
                            <th><i class="fas fa-globe"></i> Lingua</th>
                            <td><?= htmlspecialchars($libro['lingua']) ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if ($libro['genere']): ?>
                        <tr>
                            <th><i class="fas fa-tag"></i> Genere</th>
                            <td><span class="badge badge-info"><?= htmlspecialchars($libro['genere']) ?></span></td>
                        </tr>
                        <?php endif; ?>
                        <?php if ($libro['tipologia']): ?>
                        <tr>
                            <th><i class="fas fa-layer-group"></i> Tipologia</th>
                            <td><?= htmlspecialchars(ucfirst($libro['tipologia'])) ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if ($libro['isbn']): ?>
                        <tr>
                            <th><i class="fas fa-barcode"></i> ISBN</th>
                            <td><code><?= htmlspecialchars($libro['isbn']) ?></code></td>
                        </tr>
                        <?php endif; ?>
                        <?php if ($libro['codice_dewey']): ?>
                        <tr>
                            <th><i class="fas fa-hashtag"></i> Codice Dewey</th>
                            <td><code><?= htmlspecialchars($libro['codice_dewey']) ?></code></td>
                        </tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </div>

        <!-- Trama -->
        <?php if (!empty($libro['trama'])): ?>
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-align-left"></i> Trama</h2>
            </div>
            <div class="card-body">
                <p class="book-description"><?= nl2br(htmlspecialchars($libro['trama'])) ?></p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Elenco copie (solo per bibliotecari/admin) -->
        <?php if ($user['livello'] >= LIVELLO_BIBLIOTECARIO && !empty($copie)): ?>
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-copy"></i> Elenco Copie</h2>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>QR Code</th>
                                <th>Stato</th>
                                <th>Posizione</th>
                                <th>Note</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($copie as $copia): ?>
                            <tr>
                                <td><?= (int)$copia['numero_copia'] ?></td>
                                <td><code><?= htmlspecialchars($copia['qr_code_univoco'] ?? '-') ?></code></td>
                                <td>
                                    <?php
                                    $statoBadge = match($copia['stato']) {
                                        'disponibile' => 'badge-success',
                                        'in_prestito' => 'badge-warning',
                                        'prenotato' => 'badge-info',
                                        'danneggiato' => 'badge-danger',
                                        'smarrito' => 'badge-danger',
                                        default => 'badge-light'
                                    };
                                    ?>
                                    <span class="badge <?= $statoBadge ?>"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $copia['stato']))) ?></span>
                                </td>
                                <td>
                                    <?php
                                    $posizione = [];
                                    if (!empty($copia['numero_aula'])) $posizione[] = 'Aula ' . htmlspecialchars($copia['numero_aula']);
                                    if (!empty($copia['numero_armadio'])) $posizione[] = 'Arm. ' . htmlspecialchars($copia['numero_armadio']);
                                    if (!empty($copia['numero_ripiano'])) $posizione[] = 'Rip. ' . htmlspecialchars($copia['numero_ripiano']);
                                    echo !empty($posizione) ? implode(', ', $posizione) : '<span class="text-muted">-</span>';
                                    ?>
                                </td>
                                <td><?= htmlspecialchars($copia['note_danno'] ?? '-') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
