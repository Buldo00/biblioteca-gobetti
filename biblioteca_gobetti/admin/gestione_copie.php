<?php
/**
 * Gestione Copie - Biblioteca Gobetti
 * Gestione copie fisiche di un libro
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireMinLevel(LIVELLO_BIBLIOTECARIO);

$currentUser = getCurrentUser();
$baseUrl = getBaseUrl();
$message = '';
$error = '';

$idLibro = (int)($_GET['id_libro'] ?? 0);
if ($idLibro <= 0) {
    header('Location: gestione_libri.php');
    exit;
}

$libro = getLibro($idLibro);
if (!$libro) {
    header('Location: gestione_libri.php');
    exit;
}

// Gestione azioni POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $azione = $_POST['azione'] ?? '';

    try {
        if ($azione === 'aggiungi_copie') {
            $numCopie = max(1, (int)($_POST['num_copie'] ?? 1));
            $armadio = trim($_POST['armadio'] ?? '');
            $ripiano = trim($_POST['ripiano'] ?? '');
            $aula = trim($_POST['aula'] ?? '');

            $copieIds = addCopie($idLibro, $numCopie, $armadio, $ripiano, $aula);
            logOperazione($currentUser['id'], 'copie_aggiunte', 'biblioteca_copie', $idLibro, "Aggiunte $numCopie copie");
            $message = "$numCopie copie aggiunte con successo!";

        } elseif ($azione === 'modifica_posizione') {
            $idCopia = (int)($_POST['id_copia'] ?? 0);
            $armadio = trim($_POST['armadio'] ?? '');
            $ripiano = trim($_POST['ripiano'] ?? '');
            $aula = trim($_POST['aula'] ?? '');

            if ($idCopia > 0) {
                updatePosizioneCopia($idCopia, $armadio, $ripiano, $aula);
                logOperazione($currentUser['id'], 'posizione_aggiornata', 'biblioteca_copie', $idCopia, "Posizione aggiornata");
                $message = 'Posizione aggiornata con successo!';
            }

        } elseif ($azione === 'modifica_stato') {
            $idCopia = (int)($_POST['id_copia'] ?? 0);
            $stato = trim($_POST['stato'] ?? '');
            $note = trim($_POST['note_danno'] ?? '');

            $statiValidi = ['disponibile', 'danneggiato', 'smarrito'];
            if ($idCopia > 0 && in_array($stato, $statiValidi)) {
                updateStatoCopia($idCopia, $stato, $note);
                logOperazione($currentUser['id'], 'stato_copia_aggiornato', 'biblioteca_copie', $idCopia, "Stato: $stato");
                $message = 'Stato aggiornato con successo!';
            } else {
                throw new Exception('Stato non valido.');
            }
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Ricarica copie dopo eventuali modifiche
$copie = getCopieLibro($idLibro);
$libro = getLibro($idLibro);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">

    <!-- Info libro -->
    <div class="card" style="margin-bottom: var(--space-6);">
        <div class="card-header">
            <h2><i class="fas fa-book"></i> <?= htmlspecialchars($libro['titolo']) ?></h2>
            <div class="card-actions">
                <a href="gestione_libri.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Torna ai libri</a>
            </div>
        </div>
        <div class="card-body">
            <div class="form-row" style="gap: var(--space-6);">
                <div><strong>Autore:</strong> <?= htmlspecialchars($libro['autore'] ?? '-') ?></div>
                <div><strong>ISBN:</strong> <?= htmlspecialchars($libro['isbn'] ?? '-') ?></div>
                <div><strong>Codice Dewey:</strong> <?= htmlspecialchars($libro['codice_dewey'] ?? '-') ?></div>
                <div><strong>Copie totali:</strong> <?= (int)$libro['totale_copie'] ?></div>
                <div><strong>Copie disponibili:</strong>
                    <span class="badge <?= max(0, (int)$libro['copie_disponibili']) > 0 ? 'badge-success' : 'badge-danger' ?>">
                        <?= max(0, (int)$libro['copie_disponibili']) ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Aggiungi copie -->
    <div class="card" style="margin-bottom: var(--space-6);">
        <div class="card-header">
            <h3><i class="fas fa-plus-circle"></i> Aggiungi copie</h3>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="azione" value="aggiungi_copie">
                <div class="form-row" style="gap: var(--space-4); flex-wrap: wrap; align-items: flex-end;">
                    <div class="form-group" style="flex:1; min-width:120px;">
                        <label>Numero copie</label>
                        <input type="number" name="num_copie" class="form-control" value="1" min="1" max="50" required>
                    </div>
                    <div class="form-group" style="flex:1; min-width:120px;">
                        <label>Armadio</label>
                        <input type="text" name="armadio" class="form-control" placeholder="es. A1">
                    </div>
                    <div class="form-group" style="flex:1; min-width:120px;">
                        <label>Ripiano</label>
                        <input type="text" name="ripiano" class="form-control" placeholder="es. 3">
                    </div>
                    <div class="form-group" style="flex:1; min-width:120px;">
                        <label>Aula</label>
                        <input type="text" name="aula" class="form-control" placeholder="es. Biblioteca">
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-success"><i class="fas fa-plus"></i> Aggiungi</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Lista copie -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-copy"></i> Copie (<?= count($copie) ?>)</h3>
        </div>
        <div class="card-body">
            <?php if (empty($copie)): ?>
                <p style="text-align:center; color: var(--gray-600);">Nessuna copia presente. Aggiungi le prime copie usando il modulo sopra.</p>
            <?php else: ?>
                <div class="table-container">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>QR Code</th>
                                <th>Armadio</th>
                                <th>Ripiano</th>
                                <th>Aula</th>
                                <th>Stato</th>
                                <th class="text-center">Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($copie as $copia): ?>
                            <tr>
                                <td><strong><?= (int)$copia['numero_copia'] ?></strong></td>
                                <td><code><?= htmlspecialchars($copia['qr_code_univoco'] ?? '') ?></code></td>
                                <td><?= htmlspecialchars($copia['numero_armadio'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($copia['numero_ripiano'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($copia['numero_aula'] ?? '-') ?></td>
                                <td>
                                    <?php
                                    $badgeClass = 'badge-success';
                                    if ($copia['stato'] === 'danneggiato') $badgeClass = 'badge-warning';
                                    elseif ($copia['stato'] === 'smarrito') $badgeClass = 'badge-danger';
                                    elseif ($copia['stato'] === 'in_prestito' || $copia['stato'] === 'prenotato') $badgeClass = 'badge-info';
                                    ?>
                                    <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars(ucfirst($copia['stato'])) ?></span>
                                </td>
                                <td class="text-center actions">
                                    <a href="genera_etichette.php?id_copia=<?= (int)$copia['id_copia'] ?>" class="btn btn-sm btn-info" title="Scarica etichetta PDF">
                                        <i class="fas fa-qrcode"></i>
                                    </a>
                                    <button class="btn btn-sm btn-warning" title="Modifica posizione"
                                        onclick="modificaPosizione(<?= (int)$copia['id_copia'] ?>, '<?= htmlspecialchars($copia['numero_armadio'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars($copia['numero_ripiano'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars($copia['numero_aula'] ?? '', ENT_QUOTES) ?>')">
                                        <i class="fas fa-map-marker-alt"></i>
                                    </button>
                                    <?php if (in_array($copia['stato'], ['disponibile', 'danneggiato', 'smarrito'])): ?>
                                    <button class="btn btn-sm btn-secondary" title="Cambia stato"
                                        onclick="cambiaStato(<?= (int)$copia['id_copia'] ?>, '<?= htmlspecialchars($copia['stato'], ENT_QUOTES) ?>')">
                                        <i class="fas fa-exchange-alt"></i>
                                    </button>
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
</div>

<!-- Modal Modifica Posizione -->
<div class="modal-overlay" id="modalPosizione">
    <div class="modal">
        <div class="modal-header">
            <h3><i class="fas fa-map-marker-alt"></i> Modifica posizione</h3>
            <button class="modal-close" onclick="chiudiModal('modalPosizione')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="azione" value="modifica_posizione">
            <input type="hidden" name="id_copia" id="pos_id_copia" value="">
            <div class="modal-body">
                <div class="form-group">
                    <label>Armadio</label>
                    <input type="text" name="armadio" id="pos_armadio" class="form-control">
                </div>
                <div class="form-group">
                    <label>Ripiano</label>
                    <input type="text" name="ripiano" id="pos_ripiano" class="form-control">
                </div>
                <div class="form-group">
                    <label>Aula</label>
                    <input type="text" name="aula" id="pos_aula" class="form-control">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="chiudiModal('modalPosizione')">Annulla</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Salva</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Cambia Stato -->
<div class="modal-overlay" id="modalStato">
    <div class="modal">
        <div class="modal-header">
            <h3><i class="fas fa-exchange-alt"></i> Cambia stato copia</h3>
            <button class="modal-close" onclick="chiudiModal('modalStato')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="azione" value="modifica_stato">
            <input type="hidden" name="id_copia" id="stato_id_copia" value="">
            <div class="modal-body">
                <div class="form-group">
                    <label>Nuovo stato</label>
                    <select name="stato" id="stato_select" class="form-control" required>
                        <option value="disponibile">Disponibile</option>
                        <option value="danneggiato">Danneggiato</option>
                        <option value="smarrito">Smarrito</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Note (opzionale)</label>
                    <textarea name="note_danno" class="form-control" rows="3" placeholder="Descrizione del danno o altre note..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="chiudiModal('modalStato')">Annulla</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Salva</button>
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
}

function modificaPosizione(idCopia, armadio, ripiano, aula) {
    document.getElementById('pos_id_copia').value = idCopia;
    document.getElementById('pos_armadio').value = armadio;
    document.getElementById('pos_ripiano').value = ripiano;
    document.getElementById('pos_aula').value = aula;
    apriModal('modalPosizione');
}

function cambiaStato(idCopia, statoCorrente) {
    document.getElementById('stato_id_copia').value = idCopia;
    document.getElementById('stato_select').value = statoCorrente;
    apriModal('modalStato');
}

// Chiudi modal al click sull'overlay
document.querySelectorAll('.modal-overlay').forEach(function(el) {
    el.addEventListener('click', function(e) {
        if (e.target === this) chiudiModal(this.id);
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
