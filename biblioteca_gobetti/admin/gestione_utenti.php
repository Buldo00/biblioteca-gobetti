<?php
/**
 * Gestione Utenti - Biblioteca Gobetti
 * Visualizzazione e gestione utenti del sistema
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireMinLevel(LIVELLO_BIBLIOTECARIO);

$currentUser = getCurrentUser();
$baseUrl = getBaseUrl();

// Filtro ricerca
$filtro = trim($_GET['ricerca'] ?? '');
$utenti = getTuttiUtenti($filtro ?: null);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-users-cog"></i> Gestione Utenti</h2>
        </div>
        <div class="card-body">

            <!-- Filtro ricerca -->
            <form method="GET" style="margin-bottom: var(--space-6);">
                <div class="form-row" style="gap: var(--space-4); align-items: flex-end; flex-wrap: wrap;">
                    <div class="form-group" style="flex:2; min-width:250px;">
                        <input type="text" name="ricerca" class="form-control"
                               placeholder="Cerca per nome, cognome o email..."
                               value="<?= htmlspecialchars($filtro) ?>">
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Cerca</button>
                        <a href="gestione_utenti.php" class="btn btn-secondary"><i class="fas fa-times"></i></a>
                    </div>
                </div>
            </form>

            <p style="margin-bottom: var(--space-4); color: var(--gray-600);">
                Trovati <strong><?= count($utenti) ?></strong> utenti
            </p>

            <div class="table-container">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Cognome</th>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>Classe</th>
                            <th>Ruolo</th>
                            <th>Livello</th>
                            <th class="text-center">Blacklist</th>
                            <th class="text-center">Prestiti</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($utenti)): ?>
                        <tr><td colspan="9" style="text-align:center;">Nessun utente trovato.</td></tr>
                    <?php else: ?>
                        <?php foreach ($utenti as $u): ?>
                        <?php
                            $livello = (int)($u['livello'] ?? LIVELLO_STUDENTE);
                            $nomeRuolo = getLevelName($livello);
                            $inBl = isInBlacklist($u['IDUtente']);
                            $prestitiAttivi = contaPrestitiAttivi($u['IDUtente']);
                            $classe = '';
                            if (!empty($u['classe_anno']) && !empty($u['classe_sezione'])) {
                                $classe = $u['classe_anno'] . $u['classe_sezione'];
                            }
                        ?>
                        <tr>
                            <td><?= (int)$u['IDUtente'] ?></td>
                            <td><strong><?= htmlspecialchars($u['cognome'] ?? '-') ?></strong></td>
                            <td><?= htmlspecialchars($u['nome'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($u['emailUtente'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($classe ?: '-') ?></td>
                            <td><span class="badge badge-secondary"><?= htmlspecialchars($nomeRuolo) ?></span></td>
                            <td class="text-center"><?= $livello ?></td>
                            <td class="text-center">
                                <?php if ($inBl): ?>
                                    <span class="badge badge-danger" title="In blacklist"><i class="fas fa-ban"></i> Sì</span>
                                <?php else: ?>
                                    <span style="color: var(--gray-400);">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <span class="badge <?= $prestitiAttivi > 0 ? 'badge-info' : 'badge-secondary' ?>">
                                    <?= $prestitiAttivi ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
