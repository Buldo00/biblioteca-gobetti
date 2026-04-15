<?php
/**
 * Gestione Prestiti - Biblioteca Gobetti
 * Gestione completa prestiti personali e per classe
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$currentUser = getCurrentUser();
$livelloUtente = $currentUser['livello'];

// Docenti possono accedere per prenotazioni classe, bibliotecari per tutto
if ($livelloUtente < LIVELLO_DOCENTE) {
    requireMinLevel(LIVELLO_BIBLIOTECARIO);
}

$baseUrl = getBaseUrl();
$isBibliotecario = $livelloUtente >= LIVELLO_BIBLIOTECARIO;
$message = '';
$error = '';

// Gestione azioni POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $azione = $_POST['azione'] ?? '';

    try {
        if ($azione === 'create_personal' && $isBibliotecario) {
            $idUtente = (int)($_POST['id_utente'] ?? 0);
            $idLibro = (int)($_POST['id_libro'] ?? 0);

            if ($idUtente <= 0 || $idLibro <= 0) {
                throw new Exception('Seleziona un utente e un libro.');
            }

            // Verifica se l'utente può prendere in prestito
            if (!puoPrenotare($idUtente)) {
                throw new Exception('L\'utente ha raggiunto il limite di prestiti o è in blacklist.');
            }

            // Trova una copia disponibile
            $copieLibro = getCopieLibro($idLibro);
            $copiaDisponibile = null;
            foreach ($copieLibro as $c) {
                if ($c['stato'] === 'disponibile') {
                    $copiaDisponibile = $c;
                    break;
                }
            }

            if (!$copiaDisponibile) {
                throw new Exception('Nessuna copia disponibile per questo libro.');
            }

            $prestitoId = creaPrestito($idUtente, $copiaDisponibile['id_copia'], 'personale');
            if (!$prestitoId) {
                throw new Exception('Errore nella creazione del prestito.');
            }

            logOperazione($currentUser['id'], 'prestito_creato', 'biblioteca_prestiti', $prestitoId, "Prestito personale per utente $idUtente");
            $message = 'Prestito personale creato con successo! In attesa di conferma ritiro.';

        } elseif ($azione === 'create_class' && $livelloUtente >= LIVELLO_DOCENTE) {
            $idLibro = (int)($_POST['id_libro_classe'] ?? 0);
            $idClasse = (int)($_POST['id_classe'] ?? 0);
            $studentiSelezionati = $_POST['studenti'] ?? [];

            if ($idLibro <= 0 || $idClasse <= 0) {
                throw new Exception('Seleziona un libro e una classe.');
            }

            if (empty($studentiSelezionati)) {
                throw new Exception('Seleziona almeno uno studente.');
            }

            // Verifica copie disponibili
            $libro = getLibro($idLibro);
            $copieDisponibili = max(0, (int)$libro['copie_disponibili']);

            if (count($studentiSelezionati) > $copieDisponibili) {
                throw new Exception('Non ci sono abbastanza copie disponibili. Disponibili: ' . $copieDisponibili . ', richiesti: ' . count($studentiSelezionati));
            }

            // Ottieni copie disponibili
            $copieLibro = getCopieLibro($idLibro);
            $copieLibere = [];
            foreach ($copieLibro as $c) {
                if ($c['stato'] === 'disponibile') {
                    $copieLibere[] = $c;
                }
            }

            $creati = 0;
            $errori = [];
            foreach ($studentiSelezionati as $index => $idUtenteStudente) {
                $idUtenteStudente = (int)$idUtenteStudente;

                if (!puoPrenotare($idUtenteStudente)) {
                    $errori[] = "Studente ID $idUtenteStudente: limite prestiti raggiunto o in blacklist.";
                    continue;
                }

                if (!isset($copieLibere[$index])) {
                    $errori[] = "Studente ID $idUtenteStudente: nessuna copia disponibile.";
                    continue;
                }

                $prestitoId = creaPrestito($idUtenteStudente, $copieLibere[$index]['id_copia'], 'classe');
                if ($prestitoId) {
                    $creati++;
                } else {
                    $errori[] = "Studente ID $idUtenteStudente: errore creazione prestito.";
                }
            }

            logOperazione($currentUser['id'], 'prestito_classe_creato', 'biblioteca_prestiti', $idLibro, "Classe $idClasse, $creati prestiti");

            if ($creati > 0) {
                $message = "$creati prestiti classe creati con successo!";
            }
            if (!empty($errori)) {
                $error = implode(' ', $errori);
            }

        } elseif ($azione === 'confirm_pickup' && $isBibliotecario) {
            $idPrestito = (int)($_POST['id_prestito'] ?? 0);
            if ($idPrestito <= 0) {
                throw new Exception('ID prestito non valido.');
            }

            confermaRitiro($idPrestito, 'bibliotecario', $currentUser['id']);
            logOperazione($currentUser['id'], 'conferma_ritiro', 'biblioteca_prestiti', $idPrestito, 'Conferma bibliotecario');
            $message = 'Ritiro confermato! Il prestito verrà attivato quando anche l\'utente confermerà.';

        } elseif ($azione === 'return_book' && $isBibliotecario) {
            $idPrestito = (int)($_POST['id_prestito'] ?? 0);
            if ($idPrestito <= 0) {
                throw new Exception('ID prestito non valido.');
            }

            $result = restituisciLibro($idPrestito);
            if (!$result) {
                throw new Exception('Errore nella restituzione.');
            }

            logOperazione($currentUser['id'], 'restituzione', 'biblioteca_prestiti', $idPrestito, 'Libro restituito');
            $message = 'Libro restituito con successo!';
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Dati per i form
$filtroStato = $_GET['stato'] ?? null;
if ($filtroStato === '') $filtroStato = null;
$prestiti = $isBibliotecario ? getTuttiPrestiti($filtroStato) : [];

// Carica libri per il dropdown (solo quelli con copie disponibili)
$tuttiLibri = getLibri([], 1, 1000);
$libriDisponibili = [];
foreach ($tuttiLibri['libri'] as $l) {
    if (max(0, (int)$l['copie_disponibili']) > 0) {
        $libriDisponibili[] = $l;
    }
}

// Carica utenti per il dropdown
$utenti = getTuttiUtenti();

// Carica classi
$classi = getClassi();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">

    <?php if ($message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Sezione 1: Nuovo Prestito -->
    <div class="card" style="margin-bottom: var(--space-6);">
        <div class="card-header">
            <h2><i class="fas fa-plus-circle"></i> Nuovo Prestito</h2>
        </div>
        <div class="card-body">

            <!-- Tipo di prestito -->
            <div class="form-group" style="margin-bottom: var(--space-6);">
                <label><strong>Tipo di prestito:</strong></label>
                <div style="display: flex; gap: var(--space-6); margin-top: var(--space-2);">
                    <?php if ($isBibliotecario): ?>
                    <label style="display:flex; align-items:center; gap:var(--space-2); cursor:pointer;">
                        <input type="radio" name="tipo_prestito" value="personale" id="radio_personale" checked
                               onchange="switchTipoPrestito('personale')">
                        <i class="fas fa-user"></i> Prestito Personale
                    </label>
                    <?php endif; ?>
                    <?php if ($livelloUtente >= LIVELLO_DOCENTE): ?>
                    <label style="display:flex; align-items:center; gap:var(--space-2); cursor:pointer;">
                        <input type="radio" name="tipo_prestito" value="classe" id="radio_classe"
                               <?= !$isBibliotecario ? 'checked' : '' ?>
                               onchange="switchTipoPrestito('classe')">
                        <i class="fas fa-users"></i> Prestito per Classe
                    </label>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Form Prestito Personale -->
            <?php if ($isBibliotecario): ?>
            <div id="form_personale" style="display: block;">
                <form method="POST" id="formPrestitoPersonale">
                    <input type="hidden" name="azione" value="create_personal">
                    <div class="form-row" style="gap: var(--space-4); flex-wrap: wrap; align-items: flex-end;">
                        <div class="form-group" style="flex:2; min-width:200px;">
                            <label>Libro <span class="required">*</span></label>
                            <select name="id_libro" class="form-control" required>
                                <option value="">-- Seleziona libro --</option>
                                <?php foreach ($libriDisponibili as $l): ?>
                                    <option value="<?= (int)$l['id_libro'] ?>">
                                        <?= htmlspecialchars($l['titolo']) ?> - <?= htmlspecialchars($l['autore'] ?? 'N/D') ?>
                                        (<?= max(0, (int)$l['copie_disponibili']) ?> disponibili)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group" style="flex:2; min-width:200px;">
                            <label>Utente <span class="required">*</span></label>
                            <select name="id_utente" class="form-control" required>
                                <option value="">-- Seleziona utente --</option>
                                <?php foreach ($utenti as $u): ?>
                                    <option value="<?= (int)$u['IDUtente'] ?>">
                                        <?= htmlspecialchars(($u['cognome'] ?? '') . ' ' . ($u['nome'] ?? '')) ?>
                                        (<?= htmlspecialchars($u['emailUtente']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-handshake"></i> Crea Prestito
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            <?php endif; ?>

            <!-- Form Prestito per Classe -->
            <?php if ($livelloUtente >= LIVELLO_DOCENTE): ?>
            <div id="form_classe" style="display: <?= !$isBibliotecario ? 'block' : 'none' ?>;">
                <form method="POST" id="formPrestitoClasse">
                    <input type="hidden" name="azione" value="create_class">
                    <div class="form-row" style="gap: var(--space-4); flex-wrap: wrap; margin-bottom: var(--space-4);">
                        <div class="form-group" style="flex:1; min-width:200px;">
                            <label>Libro <span class="required">*</span></label>
                            <select name="id_libro_classe" id="select_libro_classe" class="form-control" required
                                    onchange="aggiornaMaxStudenti()">
                                <option value="" data-copie="0">-- Seleziona libro --</option>
                                <?php foreach ($libriDisponibili as $l): ?>
                                    <option value="<?= (int)$l['id_libro'] ?>" data-copie="<?= max(0, (int)$l['copie_disponibili']) ?>">
                                        <?= htmlspecialchars($l['titolo']) ?> - <?= htmlspecialchars($l['autore'] ?? 'N/D') ?>
                                        (<?= max(0, (int)$l['copie_disponibili']) ?> disponibili)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group" style="flex:1; min-width:200px;">
                            <label>Classe <span class="required">*</span></label>
                            <select name="id_classe" id="select_classe" class="form-control" required
                                    onchange="caricaStudentiClasse(this.value)">
                                <option value="">-- Seleziona classe --</option>
                                <?php foreach ($classi as $cl): ?>
                                    <option value="<?= (int)$cl['IDClasse'] ?>">
                                        <?= htmlspecialchars($cl['anno'] . $cl['sezione']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Lista studenti della classe -->
                    <div id="container_studenti" style="display:none; margin-bottom: var(--space-4);">
                        <div class="card">
                            <div class="card-header">
                                <h3><i class="fas fa-user-graduate"></i> Studenti della classe</h3>
                                <div class="card-actions">
                                    <span id="contatore_selezionati" class="badge badge-primary">0 selezionati</span>
                                    <span id="copie_info" class="badge badge-info">0 copie disponibili</span>
                                </div>
                            </div>
                            <div class="card-body">
                                <div style="margin-bottom: var(--space-3);">
                                    <label style="cursor:pointer;">
                                        <input type="checkbox" id="seleziona_tutti" onchange="toggleTuttiStudenti(this.checked)">
                                        <strong>Seleziona/Deseleziona tutti</strong>
                                    </label>
                                </div>
                                <div id="lista_studenti" class="form-row" style="gap: var(--space-3); flex-wrap: wrap;">
                                    <p style="color: var(--gray-600);">Seleziona una classe per vedere gli studenti.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-success" id="btn_crea_classe" disabled>
                        <i class="fas fa-users"></i> Crea Prestiti per Classe
                    </button>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Sezione 2: Gestione Prestiti Esistenti -->
    <?php if ($isBibliotecario): ?>
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-exchange-alt"></i> Gestione Prestiti Esistenti</h2>
        </div>
        <div class="card-body">

            <!-- Filtro stato -->
            <form method="GET" style="margin-bottom: var(--space-4);">
                <div class="form-row" style="gap: var(--space-4); align-items: flex-end; flex-wrap: wrap;">
                    <div class="form-group" style="min-width: 200px;">
                        <label>Filtra per stato</label>
                        <select name="stato" class="form-control" onchange="this.form.submit()">
                            <option value="">Tutti gli stati</option>
                            <option value="in_attesa" <?= $filtroStato === 'in_attesa' ? 'selected' : '' ?>>In attesa</option>
                            <option value="attivo" <?= $filtroStato === 'attivo' ? 'selected' : '' ?>>Attivo</option>
                            <option value="in_ritardo" <?= $filtroStato === 'in_ritardo' ? 'selected' : '' ?>>In ritardo</option>
                            <option value="restituito" <?= $filtroStato === 'restituito' ? 'selected' : '' ?>>Restituito</option>
                        </select>
                    </div>
                </div>
            </form>

            <div class="table-container">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Utente</th>
                            <th>Libro</th>
                            <th>Copia</th>
                            <th>Tipo</th>
                            <th>Stato</th>
                            <th>Data Prestito</th>
                            <th>Scadenza</th>
                            <th class="text-center">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($prestiti)): ?>
                        <tr><td colspan="9" style="text-align:center;">Nessun prestito trovato.</td></tr>
                    <?php else: ?>
                        <?php foreach ($prestiti as $p): ?>
                        <tr>
                            <td><?= (int)$p['id_prestito'] ?></td>
                            <td>
                                <strong><?= htmlspecialchars(($p['cognome_utente'] ?? '') . ' ' . ($p['nome_utente'] ?? '')) ?></strong>
                            </td>
                            <td><?= htmlspecialchars($p['titolo'] ?? '') ?></td>
                            <td><code><?= htmlspecialchars($p['qr_code_univoco'] ?? '') ?></code></td>
                            <td><span class="badge badge-secondary"><?= htmlspecialchars(ucfirst($p['tipo_prestito'] ?? 'personale')) ?></span></td>
                            <td>
                                <?php
                                $statoBadge = 'badge-secondary';
                                switch ($p['stato']) {
                                    case 'in_attesa': $statoBadge = 'badge-warning'; break;
                                    case 'attivo': $statoBadge = 'badge-success'; break;
                                    case 'in_ritardo': $statoBadge = 'badge-danger'; break;
                                    case 'restituito': $statoBadge = 'badge-info'; break;
                                }
                                ?>
                                <span class="badge <?= $statoBadge ?>"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $p['stato']))) ?></span>
                            </td>
                            <td><?= $p['data_prestito'] ? date('d/m/Y', strtotime($p['data_prestito'])) : '-' ?></td>
                            <td><?= $p['data_scadenza'] ? date('d/m/Y', strtotime($p['data_scadenza'])) : '-' ?></td>
                            <td class="text-center actions">
                                <?php if ($p['stato'] === 'in_attesa'): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="azione" value="confirm_pickup">
                                        <input type="hidden" name="id_prestito" value="<?= (int)$p['id_prestito'] ?>">
                                        <button type="submit" class="btn btn-sm btn-success" title="Conferma Ritiro">
                                            <i class="fas fa-check"></i> Conferma
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <?php if (in_array($p['stato'], ['attivo', 'in_ritardo'])): ?>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Confermi la restituzione?');">
                                        <input type="hidden" name="azione" value="return_book">
                                        <input type="hidden" name="id_prestito" value="<?= (int)$p['id_prestito'] ?>">
                                        <button type="submit" class="btn btn-sm btn-primary" title="Restituisci">
                                            <i class="fas fa-undo"></i> Restituisci
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <?php if ($p['stato'] === 'restituito'): ?>
                                    <span style="color: var(--gray-500);"><i class="fas fa-check-circle"></i></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Dati studenti per classe (JSON embedded) -->
<script>
var studentiPerClasse = {};
var maxCopieDisponibili = 0;

<?php
// Pre-caricare gli studenti per ogni classe in JS
foreach ($classi as $cl) {
    $studenti = getStudentiClasse($cl['IDClasse']);
    $studentiJson = [];
    foreach ($studenti as $s) {
        $studentiJson[] = [
            'id_utente'  => (int)$s['IDUtente'],
            'nome'       => $s['nome'] ?? '',
            'cognome'    => $s['cognome'] ?? '',
        ];
    }
    echo "studentiPerClasse[" . (int)$cl['IDClasse'] . "] = " . json_encode($studentiJson, JSON_HEX_APOS | JSON_HEX_QUOT) . ";\n";
}
?>

function switchTipoPrestito(tipo) {
    var formPersonale = document.getElementById('form_personale');
    var formClasse = document.getElementById('form_classe');

    if (formPersonale) formPersonale.style.display = tipo === 'personale' ? 'block' : 'none';
    if (formClasse) formClasse.style.display = tipo === 'classe' ? 'block' : 'none';
}

function aggiornaMaxStudenti() {
    var select = document.getElementById('select_libro_classe');
    if (!select) return;
    var opt = select.options[select.selectedIndex];
    maxCopieDisponibili = parseInt(opt.getAttribute('data-copie')) || 0;

    var copieInfo = document.getElementById('copie_info');
    if (copieInfo) copieInfo.textContent = maxCopieDisponibili + ' copie disponibili';

    aggiornaCheckboxStudenti();
}

function caricaStudentiClasse(idClasse) {
    var container = document.getElementById('container_studenti');
    var listaDiv = document.getElementById('lista_studenti');

    if (!idClasse || !studentiPerClasse[idClasse]) {
        container.style.display = 'none';
        listaDiv.innerHTML = '<p style="color: var(--gray-600);">Nessuno studente in questa classe.</p>';
        return;
    }

    var studenti = studentiPerClasse[idClasse];
    if (studenti.length === 0) {
        container.style.display = 'block';
        listaDiv.innerHTML = '<p style="color: var(--gray-600);">Nessuno studente in questa classe.</p>';
        return;
    }

    var html = '';
    studenti.forEach(function(s) {
        html += '<label style="display:flex; align-items:center; gap:var(--space-2); min-width:200px; padding:var(--space-2); border:1px solid var(--gray-200); border-radius:var(--border-radius-sm); cursor:pointer;" class="student-checkbox-label">';
        html += '<input type="checkbox" name="studenti[]" value="' + s.id_utente + '" class="student-checkbox" onchange="aggiornaCheckboxStudenti()">';
        html += '<span>' + escapeHtml(s.cognome) + ' ' + escapeHtml(s.nome) + '</span>';
        html += '</label>';
    });

    listaDiv.innerHTML = html;
    container.style.display = 'block';

    document.getElementById('seleziona_tutti').checked = false;
    aggiornaMaxStudenti();
}

function aggiornaCheckboxStudenti() {
    var checkboxes = document.querySelectorAll('.student-checkbox');
    var selezionati = document.querySelectorAll('.student-checkbox:checked').length;
    var contatore = document.getElementById('contatore_selezionati');
    var btnCrea = document.getElementById('btn_crea_classe');

    if (contatore) contatore.textContent = selezionati + ' selezionati';
    if (btnCrea) btnCrea.disabled = selezionati === 0;

    // Limita le checkbox selezionabili al numero di copie disponibili
    checkboxes.forEach(function(cb) {
        if (!cb.checked && selezionati >= maxCopieDisponibili) {
            cb.disabled = true;
            cb.parentElement.style.opacity = '0.5';
        } else {
            cb.disabled = false;
            cb.parentElement.style.opacity = '1';
        }
    });
}

function toggleTuttiStudenti(seleziona) {
    var checkboxes = document.querySelectorAll('.student-checkbox');
    var count = 0;

    checkboxes.forEach(function(cb) {
        if (seleziona && count < maxCopieDisponibili) {
            cb.checked = true;
            cb.disabled = false;
            cb.parentElement.style.opacity = '1';
            count++;
        } else if (seleziona) {
            cb.checked = false;
            cb.disabled = true;
            cb.parentElement.style.opacity = '0.5';
        } else {
            cb.checked = false;
            cb.disabled = false;
            cb.parentElement.style.opacity = '1';
        }
    });

    aggiornaCheckboxStudenti();
}

function escapeHtml(text) {
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(text));
    return div.innerHTML;
}

// Init: se docente non-bibliotecario, mostra solo form classe
<?php if (!$isBibliotecario && $livelloUtente >= LIVELLO_DOCENTE): ?>
switchTipoPrestito('classe');
<?php endif; ?>
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
