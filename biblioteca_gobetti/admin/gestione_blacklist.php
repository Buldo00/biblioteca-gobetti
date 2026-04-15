<?php
/**
 * Gestione Blacklist - Biblioteca Gobetti
 * Gestione utenti in blacklist
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
            $idUtente = (int)($_POST['id_utente'] ?? 0);
            $motivo = trim($_POST['motivo'] ?? '');

            if ($idUtente <= 0) {
                throw new Exception('Seleziona un utente.');
            }
            if (empty($motivo)) {
                throw new Exception('Inserisci un motivo.');
            }

            // Non può blacklistare se stesso
            if ($idUtente == $currentUser['id']) {
                throw new Exception('Non puoi inserire te stesso nella blacklist.');
            }

            // Verifica che l'utente possa essere blacklistato (no admin/bibliotecari)
            if (!canBeBlacklisted($idUtente)) {
                throw new Exception('Questo utente non può essere inserito in blacklist (admin o bibliotecario).');
            }

            // Verifica se è già in blacklist
            if (isInBlacklist($idUtente)) {
                throw new Exception('L\'utente è già in blacklist.');
            }

            $result = addToBlacklist($idUtente, $motivo, $currentUser['id']);
            if (!$result) {
                throw new Exception('Errore nell\'inserimento in blacklist.');
            }

            logOperazione($currentUser['id'], 'blacklist_aggiunto', 'biblioteca_blacklist', $idUtente, "Motivo: $motivo");
            $message = 'Utente aggiunto alla blacklist.';

        } elseif ($azione === 'rimuovi') {
            $idBlacklist = (int)($_POST['id_blacklist'] ?? 0);
            if ($idBlacklist <= 0) {
                throw new Exception('ID blacklist non valido.');
            }

            removeFromBlacklist($idBlacklist);
            logOperazione($currentUser['id'], 'blacklist_rimosso', 'biblioteca_blacklist', $idBlacklist, 'Rimosso dalla blacklist');
            $message = 'Utente rimosso dalla blacklist.';
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Dati
$blacklist = getBlacklist();
$utenti = getTuttiUtenti();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">

    <?php if ($message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Aggiungi alla blacklist -->
    <div class="card" style="margin-bottom: var(--space-6);">
        <div class="card-header">
            <h2><i class="fas fa-user-plus"></i> Aggiungi alla blacklist</h2>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="azione" value="aggiungi">
                <div class="form-row" style="gap: var(--space-4); flex-wrap: wrap; align-items: flex-end;">
                    <div class="form-group" style="flex:2; min-width:250px;">
                        <label>Utente <span class="required">*</span></label>
                        <select name="id_utente" class="form-control" required>
                            <option value="">-- Seleziona utente --</option>
                            <?php foreach ($utenti as $u): ?>
                                <?php
                                    $livelloU = (int)($u['livello'] ?? LIVELLO_STUDENTE);
                                    // Non mostrare utenti non blacklistabili
                                    if ($livelloU >= LIVELLO_BIBLIOTECARIO) continue;
                                    // Non mostrare se stesso
                                    if ((int)$u['IDUtente'] === (int)$currentUser['id']) continue;
                                    // Non mostrare se già in blacklist
                                    if (isInBlacklist($u['IDUtente'])) continue;
                                ?>
                                <option value="<?= (int)$u['IDUtente'] ?>">
                                    <?= htmlspecialchars(($u['cognome'] ?? '') . ' ' . ($u['nome'] ?? '')) ?>
                                    (<?= htmlspecialchars($u['emailUtente']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="flex:2; min-width:250px;">
                        <label>Motivo <span class="required">*</span></label>
                        <input type="text" name="motivo" class="form-control" placeholder="Motivo dell'inserimento in blacklist" required>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-danger" onclick="return confirm('Sei sicuro di voler aggiungere questo utente alla blacklist?');">
                            <i class="fas fa-ban"></i> Aggiungi
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Lista blacklist -->
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-ban"></i> Utenti in Blacklist</h2>
            <div class="card-actions">
                <span class="badge badge-danger"><?= count($blacklist) ?> utenti</span>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($blacklist)): ?>
                <p style="text-align:center; color: var(--gray-600);">
                    <i class="fas fa-check-circle" style="color: var(--success);"></i>
                    Nessun utente in blacklist.
                </p>
            <?php else: ?>
                <div class="table-container">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Utente</th>
                                <th>Motivo</th>
                                <th>Data inserimento</th>
                                <th class="text-center">Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($blacklist as $bl): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars(($bl['cognome'] ?? '') . ' ' . ($bl['nome'] ?? '')) ?></strong>
                                </td>
                                <td><?= htmlspecialchars($bl['motivo'] ?? '-') ?></td>
                                <td><?= $bl['data_inserimento'] ? date('d/m/Y H:i', strtotime($bl['data_inserimento'])) : '-' ?></td>
                                <td class="text-center">
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Rimuovere questo utente dalla blacklist?');">
                                        <input type="hidden" name="azione" value="rimuovi">
                                        <input type="hidden" name="id_blacklist" value="<?= (int)$bl['id_blacklist'] ?>">
                                        <button type="submit" class="btn btn-sm btn-success" title="Rimuovi dalla blacklist">
                                            <i class="fas fa-user-check"></i> Rimuovi
                                        </button>
                                    </form>
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
