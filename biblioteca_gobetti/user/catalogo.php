<?php
/**
 * Catalogo Libri - Biblioteca Gobetti
 * Ricerca e visualizzazione del catalogo biblioteca
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$user = getCurrentUser();

// Parametri di ricerca
$filtri = [];
$ricerca = trim($_GET['q'] ?? '');
$genere = trim($_GET['genere'] ?? '');
$lingua = trim($_GET['lingua'] ?? '');
$anno = trim($_GET['anno'] ?? '');
$disponibile = $_GET['disponibile'] ?? '';
$dewey = trim($_GET['dewey'] ?? '');
$pagina = max(1, (int)($_GET['pagina'] ?? 1));

if ($ricerca !== '') $filtri['ricerca'] = $ricerca;
if ($genere !== '') $filtri['genere'] = $genere;
if ($lingua !== '') $filtri['lingua'] = $lingua;
if ($anno !== '') $filtri['anno'] = $anno;
if ($disponibile !== '') $filtri['disponibile'] = $disponibile;
if ($dewey !== '') $filtri['codice_dewey'] = $dewey;

$risultati = getLibri($filtri, $pagina, 24);
$libri = $risultati['libri'];
$totalePagine = $risultati['pagine'];
$totaleLibri = $risultati['totale'];

// Dati per i filtri
$generi = getGeneri();
$lingue = getLingue();

include __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1><i class="fas fa-search"></i> Catalogo Biblioteca</h1>
        <p class="subtitle"><?= $totaleLibri ?> libri trovati</p>
    </div>

    <!-- Barra di ricerca -->
    <div class="card">
        <div class="card-body">
            <form method="GET" class="search-form" id="searchForm">
                <div class="search-bar">
                    <div class="search-input-wrapper">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" name="q" class="form-control search-input" 
                               value="<?= htmlspecialchars($ricerca) ?>"
                               placeholder="Cerca per titolo, autore o ISBN...">
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Cerca
                    </button>
                    <button type="button" class="btn btn-outline" id="toggleFilters">
                        <i class="fas fa-filter"></i> Filtri
                    </button>
                </div>

                <!-- Filtri avanzati -->
                <div class="filters-panel" id="filtersPanel" style="<?= ($genere || $lingua || $anno || $disponibile !== '' || $dewey) ? '' : 'display:none;' ?>">
                    <div class="filters-grid">
                        <div class="form-group">
                            <label for="genere"><i class="fas fa-tag"></i> Genere</label>
                            <select name="genere" id="genere" class="form-control">
                                <option value="">Tutti i generi</option>
                                <?php foreach ($generi as $g): ?>
                                <option value="<?= htmlspecialchars($g) ?>" <?= $genere === $g ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($g) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="lingua"><i class="fas fa-globe"></i> Lingua</label>
                            <select name="lingua" id="lingua" class="form-control">
                                <option value="">Tutte le lingue</option>
                                <?php foreach ($lingue as $l): ?>
                                <option value="<?= htmlspecialchars($l) ?>" <?= $lingua === $l ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($l) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="anno"><i class="fas fa-calendar"></i> Anno</label>
                            <input type="number" name="anno" id="anno" class="form-control" 
                                   value="<?= htmlspecialchars($anno) ?>" placeholder="es. 2020" min="1000" max="<?= date('Y') ?>">
                        </div>
                        <div class="form-group">
                            <label for="disponibile"><i class="fas fa-check-circle"></i> Disponibilità</label>
                            <select name="disponibile" id="disponibile" class="form-control">
                                <option value="">Tutti</option>
                                <option value="1" <?= $disponibile === '1' ? 'selected' : '' ?>>Disponibili</option>
                                <option value="0" <?= $disponibile === '0' ? 'selected' : '' ?>>Non disponibili</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="dewey"><i class="fas fa-barcode"></i> Codice Dewey</label>
                            <input type="text" name="dewey" id="dewey" class="form-control" 
                                   value="<?= htmlspecialchars($dewey) ?>" placeholder="es. 800">
                        </div>
                    </div>
                    <div class="filters-actions">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-search"></i> Applica Filtri
                        </button>
                        <a href="catalogo.php" class="btn btn-outline btn-sm">
                            <i class="fas fa-times"></i> Resetta
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Griglia libri -->
    <?php if (empty($libri)): ?>
    <div class="card">
        <div class="card-body text-center" style="padding: var(--space-12);">
            <i class="fas fa-book-open" style="font-size: 3rem; color: var(--gray-400); margin-bottom: var(--space-4);"></i>
            <h3 style="color: var(--gray-600);">Nessun libro trovato</h3>
            <p style="color: var(--gray-500);">Prova a modificare i criteri di ricerca.</p>
            <a href="catalogo.php" class="btn btn-primary"><i class="fas fa-undo"></i> Vedi tutti i libri</a>
        </div>
    </div>
    <?php else: ?>
    <div class="book-grid">
        <?php foreach ($libri as $libro): ?>
        <?php 
            $disponibili = max(0, (int)$libro['copie_disponibili']);
            $totCopie = (int)$libro['totale_copie'];
        ?>
        <a href="dettaglio_libro.php?id=<?= (int)$libro['id_libro'] ?>" class="book-card">
            <div class="book-cover">
                <?php if (!empty($libro['copertina'])): ?>
                <img src="<?= htmlspecialchars($libro['copertina']) ?>" alt="<?= htmlspecialchars($libro['titolo']) ?>">
                <?php else: ?>
                <div class="book-cover-placeholder">
                    <i class="fas fa-book"></i>
                    <span><?= htmlspecialchars(mb_substr($libro['titolo'], 0, 30)) ?></span>
                </div>
                <?php endif; ?>
                <div class="book-availability <?= $disponibili > 0 ? 'available' : 'unavailable' ?>">
                    <?= $disponibili ?>/<?= $totCopie ?> <?= $disponibili === 1 ? 'copia' : 'copie' ?>
                </div>
            </div>
            <div class="book-info">
                <h3 class="book-title"><?= htmlspecialchars($libro['titolo']) ?></h3>
                <p class="book-author"><?= htmlspecialchars($libro['autore'] ?: 'Autore sconosciuto') ?></p>
                <?php if ($libro['genere']): ?>
                <span class="badge badge-info badge-sm"><?= htmlspecialchars($libro['genere']) ?></span>
                <?php endif; ?>
                <?php if ($libro['anno_pubblicazione']): ?>
                <span class="badge badge-light badge-sm"><?= (int)$libro['anno_pubblicazione'] ?></span>
                <?php endif; ?>
            </div>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Paginazione -->
    <?php if ($totalePagine > 1): ?>
    <nav class="pagination">
        <?php if ($pagina > 1): ?>
        <a href="?<?= http_build_query(array_merge($_GET, ['pagina' => $pagina - 1])) ?>" class="pagination-btn">
            <i class="fas fa-chevron-left"></i> Precedente
        </a>
        <?php endif; ?>

        <div class="pagination-numbers">
            <?php
            $start = max(1, $pagina - 2);
            $end = min($totalePagine, $pagina + 2);
            if ($start > 1): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['pagina' => 1])) ?>" class="pagination-num">1</a>
                <?php if ($start > 2): ?><span class="pagination-dots">…</span><?php endif; ?>
            <?php endif;
            for ($i = $start; $i <= $end; $i++): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['pagina' => $i])) ?>" 
                   class="pagination-num <?= $i === $pagina ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor;
            if ($end < $totalePagine): ?>
                <?php if ($end < $totalePagine - 1): ?><span class="pagination-dots">…</span><?php endif; ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['pagina' => $totalePagine])) ?>" class="pagination-num"><?= $totalePagine ?></a>
            <?php endif; ?>
        </div>

        <?php if ($pagina < $totalePagine): ?>
        <a href="?<?= http_build_query(array_merge($_GET, ['pagina' => $pagina + 1])) ?>" class="pagination-btn">
            Successiva <i class="fas fa-chevron-right"></i>
        </a>
        <?php endif; ?>
    </nav>
    <?php endif; ?>
    <?php endif; ?>
</div>

<script>
document.getElementById('toggleFilters')?.addEventListener('click', function() {
    const panel = document.getElementById('filtersPanel');
    panel.style.display = panel.style.display === 'none' ? '' : 'none';
    this.classList.toggle('active');
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
