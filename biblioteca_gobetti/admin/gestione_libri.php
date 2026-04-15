<?php
/**
 * Gestione Libri - Biblioteca Gobetti
 * CRUD completo per i libri della biblioteca
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireMinLevel(LIVELLO_BIBLIOTECARIO);

$currentUser = getCurrentUser();
$baseUrl = getBaseUrl();
$message = '';
$error = '';

// Gestione azioni POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $azione = $_POST['azione'] ?? '';

    try {
        if ($azione === 'aggiungi') {
            $data = [
                'titolo'              => trim($_POST['titolo'] ?? ''),
                'autore'              => trim($_POST['autore'] ?? ''),
                'anno_pubblicazione'  => trim($_POST['anno_pubblicazione'] ?? ''),
                'casa_editrice'       => trim($_POST['casa_editrice'] ?? ''),
                'lingua'              => trim($_POST['lingua'] ?? 'Italiano'),
                'genere'              => trim($_POST['genere'] ?? ''),
                'codice_dewey'        => trim($_POST['codice_dewey'] ?? ''),
                'isbn'                => trim($_POST['isbn'] ?? ''),
                'copertina'           => trim($_POST['copertina'] ?? ''),
                'trama'               => trim($_POST['trama'] ?? ''),
                'tipologia'           => trim($_POST['tipologia'] ?? 'libro'),
            ];

            if (empty($data['titolo'])) {
                throw new Exception('Il titolo è obbligatorio.');
            }

            $id = addLibro($data);
            logOperazione($currentUser['id'], 'libro_aggiunto', 'biblioteca_libri', $id, 'Aggiunto: ' . $data['titolo']);
            $message = 'Elemento aggiunto con successo!';

        } elseif ($azione === 'modifica') {
            $libroId = (int)($_POST['libro_id'] ?? 0);
            if ($libroId <= 0) {
                throw new Exception('ID libro non valido.');
            }

            $data = [
                'titolo'              => trim($_POST['titolo'] ?? ''),
                'autore'              => trim($_POST['autore'] ?? ''),
                'anno_pubblicazione'  => trim($_POST['anno_pubblicazione'] ?? ''),
                'casa_editrice'       => trim($_POST['casa_editrice'] ?? ''),
                'lingua'              => trim($_POST['lingua'] ?? 'Italiano'),
                'genere'              => trim($_POST['genere'] ?? ''),
                'codice_dewey'        => trim($_POST['codice_dewey'] ?? ''),
                'isbn'                => trim($_POST['isbn'] ?? ''),
                'copertina'           => trim($_POST['copertina'] ?? ''),
                'trama'               => trim($_POST['trama'] ?? ''),
                'tipologia'           => trim($_POST['tipologia'] ?? 'libro'),
            ];

            if (empty($data['titolo'])) {
                throw new Exception('Il titolo è obbligatorio.');
            }

            updateLibro($libroId, $data);
            logOperazione($currentUser['id'], 'libro_modificato', 'biblioteca_libri', $libroId, 'Modificato: ' . $data['titolo']);
            $message = 'Elemento modificato con successo!';

        } elseif ($azione === 'elimina') {
            $libroId = (int)($_POST['libro_id'] ?? 0);
            if ($libroId <= 0) {
                throw new Exception('ID libro non valido.');
            }

            $result = deleteLibro($libroId);
            if (!$result) {
                throw new Exception('Impossibile eliminare: ci sono prestiti attivi per questo libro.');
            }
            logOperazione($currentUser['id'], 'libro_eliminato', 'biblioteca_libri', $libroId, 'Libro eliminato');
            $message = 'Libro eliminato con successo!';
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Filtri e paginazione
$filtri = [];
if (!empty($_GET['ricerca'])) {
    $filtri['ricerca'] = trim($_GET['ricerca']);
}
if (!empty($_GET['genere'])) {
    $filtri['genere'] = trim($_GET['genere']);
}
if (!empty($_GET['lingua'])) {
    $filtri['lingua'] = trim($_GET['lingua']);
}
if (!empty($_GET['tipologia'])) {
    $filtri['tipologia'] = trim($_GET['tipologia']);
}

$pagina = max(1, (int)($_GET['pagina'] ?? 1));
$perPagina = 20;
$risultato = getLibri($filtri, $pagina, $perPagina);
$libri = $risultato['libri'];
$totale = $risultato['totale'];
$pagine = $risultato['pagine'];

$generi = getGeneri();
$lingue = getLingue();

// Per modifica: carica libro
$libroEdit = null;
if (!empty($_GET['modifica'])) {
    $libroEdit = getLibro((int)$_GET['modifica']);
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-book"></i> Gestione Libri</h2>
            <div class="card-actions">
                <button class="btn btn-primary" onclick="apriModal('modalLibro')">
                    <i class="fas fa-plus"></i> Aggiungi elemento
                </button>
            </div>
        </div>
        <div class="card-body">

            <?php if ($message): ?>
                <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <!-- Filtri -->
            <form method="GET" class="form-row" style="margin-bottom: var(--space-6); gap: var(--space-4); flex-wrap: wrap;">
                <div class="form-group" style="flex:2; min-width:200px;">
                    <input type="text" name="ricerca" class="form-control" placeholder="Cerca per titolo, autore o ISBN..."
                           value="<?= htmlspecialchars($_GET['ricerca'] ?? '') ?>">
                </div>
                <div class="form-group" style="flex:1; min-width:150px;">
                    <select name="genere" class="form-control">
                        <option value="">Tutti i generi</option>
                        <?php foreach ($generi as $g): ?>
                            <option value="<?= htmlspecialchars($g) ?>" <?= ($_GET['genere'] ?? '') === $g ? 'selected' : '' ?>>
                                <?= htmlspecialchars($g) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="flex:1; min-width:150px;">
                    <select name="lingua" class="form-control">
                        <option value="">Tutte le lingue</option>
                        <?php foreach ($lingue as $l): ?>
                            <option value="<?= htmlspecialchars($l) ?>" <?= ($_GET['lingua'] ?? '') === $l ? 'selected' : '' ?>>
                                <?= htmlspecialchars($l) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="flex:1; min-width:150px;">
                    <select name="tipologia" class="form-control">
                        <option value="">Tutte le tipologie</option>
                        <option value="libro" <?= ($_GET['tipologia'] ?? '') === 'libro' ? 'selected' : '' ?>>Libro</option>
                        <option value="rivista" <?= ($_GET['tipologia'] ?? '') === 'rivista' ? 'selected' : '' ?>>Rivista</option>
                        <option value="dvd" <?= ($_GET['tipologia'] ?? '') === 'dvd' ? 'selected' : '' ?>>DVD</option>
                        <option value="altro" <?= ($_GET['tipologia'] ?? '') === 'altro' ? 'selected' : '' ?>>Altro</option>
                    </select>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filtra</button>
                    <a href="gestione_libri.php" class="btn btn-secondary"><i class="fas fa-times"></i></a>
                </div>
            </form>

            <p style="margin-bottom: var(--space-4); color: var(--gray-600);">
                Trovati <strong><?= (int)$totale ?></strong> risultati
            </p>

            <!-- Tabella -->
            <div class="table-container">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Titolo</th>
                            <th>Autore</th>
                            <th>Tipologia</th>
                            <th>ISBN</th>
                            <th>Genere</th>
                            <th class="text-center">Copie</th>
                            <th class="text-center">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($libri)): ?>
                        <tr><td colspan="7" style="text-align:center;">Nessun libro trovato.</td></tr>
                    <?php else: ?>
                        <?php foreach ($libri as $libro): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($libro['titolo']) ?></strong></td>
                            <td><?= htmlspecialchars($libro['autore'] ?? '-') ?></td>
                            <td><span class="badge badge-info"><?= htmlspecialchars($libro['tipologia'] ?? 'libro') ?></span></td>
                            <td><?= htmlspecialchars($libro['isbn'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($libro['genere'] ?? '-') ?></td>
                            <td class="text-center">
                                <span class="badge <?= max(0, (int)$libro['copie_disponibili']) > 0 ? 'badge-success' : 'badge-danger' ?>">
                                    <?= max(0, (int)$libro['copie_disponibili']) ?>/<?= (int)$libro['totale_copie'] ?>
                                </span>
                            </td>
                            <td class="text-center actions">
                                <a href="gestione_copie.php?id_libro=<?= (int)$libro['id_libro'] ?>" class="btn btn-sm btn-info" title="Gestione Copie">
                                    <i class="fas fa-copy"></i>
                                </a>
                                <button class="btn btn-sm btn-warning" title="Modifica"
                                    onclick="modificaLibro(<?= htmlspecialchars(json_encode($libro, JSON_HEX_APOS | JSON_HEX_QUOT)) ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Sei sicuro di voler eliminare questo libro?');">
                                    <input type="hidden" name="azione" value="elimina">
                                    <input type="hidden" name="libro_id" value="<?= (int)$libro['id_libro'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger" title="Elimina">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Paginazione -->
            <?php if ($pagine > 1): ?>
            <div class="pagination">
                <?php if ($pagina > 1): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['pagina' => $pagina - 1])) ?>">&laquo; Prec</a>
                <?php else: ?>
                    <span class="disabled">&laquo; Prec</span>
                <?php endif; ?>

                <?php for ($i = max(1, $pagina - 2); $i <= min($pagine, $pagina + 2); $i++): ?>
                    <?php if ($i == $pagina): ?>
                        <span class="active"><?= $i ?></span>
                    <?php else: ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['pagina' => $i])) ?>"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($pagina < $pagine): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['pagina' => $pagina + 1])) ?>">Succ &raquo;</a>
                <?php else: ?>
                    <span class="disabled">Succ &raquo;</span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Aggiungi/Modifica Libro -->
<div class="modal-overlay" id="modalLibro">
    <div class="modal modal-lg">
        <div class="modal-header">
            <h3 id="modalLibroTitolo"><i class="fas fa-plus"></i> Aggiungi elemento</h3>
            <button class="modal-close" onclick="chiudiModal('modalLibro')">&times;</button>
        </div>
        <form method="POST" id="formLibro">
            <input type="hidden" name="azione" id="formAzione" value="aggiungi">
            <input type="hidden" name="libro_id" id="formLibroId" value="">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group" style="flex:2;">
                        <label>Titolo <span class="required">*</span></label>
                        <input type="text" name="titolo" id="campo_titolo" class="form-control" required>
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label>Tipologia</label>
                        <select name="tipologia" id="campo_tipologia" class="form-control">
                            <option value="libro">Libro</option>
                            <option value="rivista">Rivista</option>
                            <option value="dvd">DVD</option>
                            <option value="altro">Altro</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group" style="flex:1;">
                        <label>Autore</label>
                        <input type="text" name="autore" id="campo_autore" class="form-control">
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label>Casa Editrice</label>
                        <input type="text" name="casa_editrice" id="campo_casa_editrice" class="form-control">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group" style="flex:1;">
                        <label>Anno Pubblicazione</label>
                        <input type="number" name="anno_pubblicazione" id="campo_anno_pubblicazione" class="form-control" min="1000" max="<?= date('Y') ?>">
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label>Lingua</label>
                        <input type="text" name="lingua" id="campo_lingua" class="form-control" value="Italiano">
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label>Genere</label>
                        <input type="text" name="genere" id="campo_genere" class="form-control" list="lista_generi">
                        <datalist id="lista_generi">
                            <?php foreach ($generi as $g): ?>
                                <option value="<?= htmlspecialchars($g) ?>">
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group" style="flex:1;">
                        <label>Codice Dewey</label>
                        <input type="text" name="codice_dewey" id="campo_codice_dewey" class="form-control">
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label>ISBN</label>
                        <input type="text" name="isbn" id="campo_isbn" class="form-control">
                    </div>
                </div>
                <div class="form-group">
                    <label>URL Copertina</label>
                    <input type="url" name="copertina" id="campo_copertina" class="form-control" placeholder="https://...">
                </div>
                <div class="form-group">
                    <label>Trama</label>
                    <textarea name="trama" id="campo_trama" class="form-control" rows="4"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="chiudiModal('modalLibro')">Annulla</button>
                <button type="submit" class="btn btn-primary" id="btnSalvaLibro">
                    <i class="fas fa-save"></i> Salva
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function apriModal(id) {
    document.getElementById(id).classList.add('active');
}
function chiudiModal(id) {
    document.getElementById(id).classList.remove('active');
    if (id === 'modalLibro') resetFormLibro();
}

function resetFormLibro() {
    document.getElementById('formAzione').value = 'aggiungi';
    document.getElementById('formLibroId').value = '';
    document.getElementById('modalLibroTitolo').innerHTML = '<i class="fas fa-plus"></i> Aggiungi elemento';
    document.getElementById('formLibro').reset();
    document.getElementById('campo_lingua').value = 'Italiano';
}

function modificaLibro(libro) {
    document.getElementById('formAzione').value = 'modifica';
    document.getElementById('formLibroId').value = libro.id_libro;
    document.getElementById('modalLibroTitolo').innerHTML = '<i class="fas fa-edit"></i> Modifica elemento';

    document.getElementById('campo_titolo').value = libro.titolo || '';
    document.getElementById('campo_autore').value = libro.autore || '';
    document.getElementById('campo_anno_pubblicazione').value = libro.anno_pubblicazione || '';
    document.getElementById('campo_casa_editrice').value = libro.casa_editrice || '';
    document.getElementById('campo_lingua').value = libro.lingua || 'Italiano';
    document.getElementById('campo_genere').value = libro.genere || '';
    document.getElementById('campo_codice_dewey').value = libro.codice_dewey || '';
    document.getElementById('campo_isbn').value = libro.isbn || '';
    document.getElementById('campo_copertina').value = libro.copertina || '';
    document.getElementById('campo_trama').value = libro.trama || '';
    document.getElementById('campo_tipologia').value = libro.tipologia || 'libro';

    apriModal('modalLibro');
}

// Chiudi modal al click sull'overlay
document.getElementById('modalLibro').addEventListener('click', function(e) {
    if (e.target === this) chiudiModal('modalLibro');
});

// Apri modal se in modalità modifica via GET
<?php if ($libroEdit): ?>
modificaLibro(<?= json_encode($libroEdit, JSON_HEX_APOS | JSON_HEX_QUOT) ?>);
<?php endif; ?>
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
