<?php
/**
 * Statistiche - Biblioteca Gobetti
 * Dashboard con statistiche della biblioteca
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireMinLevel(LIVELLO_BIBLIOTECARIO);

$currentUser = getCurrentUser();
$baseUrl = getBaseUrl();
$stats = getStatistiche();

// Calcolo max per le barre del grafico
$maxPrestitiMese = 0;
foreach ($stats['prestiti_per_mese'] as $pm) {
    if ((int)$pm['num'] > $maxPrestitiMese) {
        $maxPrestitiMese = (int)$pm['num'];
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="card" style="margin-bottom: var(--space-6);">
        <div class="card-header">
            <h2><i class="fas fa-chart-bar"></i> Statistiche Biblioteca</h2>
        </div>
        <div class="card-body">

            <!-- Overview cards -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: var(--space-4); margin-bottom: var(--space-8);">
                <div class="stat-card" style="text-align: center; padding: var(--space-6);">
                    <div style="font-size: var(--font-size-3xl); font-weight: 700; color: var(--primary);">
                        <?= (int)$stats['totale_libri'] ?>
                    </div>
                    <div style="color: var(--gray-600); font-size: var(--font-size-sm);">Libri totali</div>
                </div>
                <div class="stat-card" style="text-align: center; padding: var(--space-6);">
                    <div style="font-size: var(--font-size-3xl); font-weight: 700; color: var(--info);">
                        <?= (int)$stats['totale_copie'] ?>
                    </div>
                    <div style="color: var(--gray-600); font-size: var(--font-size-sm);">Copie totali</div>
                </div>
                <div class="stat-card" style="text-align: center; padding: var(--space-6);">
                    <div style="font-size: var(--font-size-3xl); font-weight: 700; color: var(--success);">
                        <?= max(0, (int)$stats['copie_disponibili']) ?>
                    </div>
                    <div style="color: var(--gray-600); font-size: var(--font-size-sm);">Copie disponibili</div>
                </div>
                <div class="stat-card" style="text-align: center; padding: var(--space-6);">
                    <div style="font-size: var(--font-size-3xl); font-weight: 700; color: var(--warning);">
                        <?= (int)$stats['prestiti_attivi'] ?>
                    </div>
                    <div style="color: var(--gray-600); font-size: var(--font-size-sm);">Prestiti attivi</div>
                </div>
                <div class="stat-card" style="text-align: center; padding: var(--space-6);">
                    <div style="font-size: var(--font-size-3xl); font-weight: 700; color: var(--danger);">
                        <?= (int)$stats['utenti_blacklist'] ?>
                    </div>
                    <div style="color: var(--gray-600); font-size: var(--font-size-sm);">Utenti in blacklist</div>
                </div>
                <div class="stat-card" style="text-align: center; padding: var(--space-6);">
                    <div style="font-size: var(--font-size-3xl); font-weight: 700; color: var(--success-dark);">
                        <?= $stats['tasso_restituzione'] ?>%
                    </div>
                    <div style="color: var(--gray-600); font-size: var(--font-size-sm);">Tasso restituzione</div>
                </div>
            </div>

            <!-- Grafico prestiti per mese -->
            <div class="card" style="margin-bottom: var(--space-6);">
                <div class="card-header">
                    <h3><i class="fas fa-chart-line"></i> Prestiti per mese (ultimi 6 mesi)</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($stats['prestiti_per_mese'])): ?>
                        <p style="text-align: center; color: var(--gray-600);">Nessun dato disponibile.</p>
                    <?php else: ?>
                        <div style="display: flex; align-items: flex-end; gap: var(--space-4); height: 250px; padding: var(--space-4) 0;">
                            <?php foreach ($stats['prestiti_per_mese'] as $pm): ?>
                                <?php
                                    $altezza = $maxPrestitiMese > 0 ? round(((int)$pm['num'] / $maxPrestitiMese) * 200) : 0;
                                    $mesiNomi = ['01'=>'Gen','02'=>'Feb','03'=>'Mar','04'=>'Apr','05'=>'Mag','06'=>'Giu','07'=>'Lug','08'=>'Ago','09'=>'Set','10'=>'Ott','11'=>'Nov','12'=>'Dic'];
                                    $parti = explode('-', $pm['mese']);
                                    $nomeMese = ($mesiNomi[$parti[1] ?? ''] ?? $parti[1] ?? '') . ' ' . ($parti[0] ?? '');
                                ?>
                                <div style="flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: flex-end;">
                                    <div style="font-weight: 700; margin-bottom: var(--space-2); font-size: var(--font-size-sm);">
                                        <?= (int)$pm['num'] ?>
                                    </div>
                                    <div style="width: 100%; max-width: 60px; height: <?= max(4, $altezza) ?>px; background: var(--primary); border-radius: var(--border-radius-sm) var(--border-radius-sm) 0 0; transition: height var(--transition);">
                                    </div>
                                    <div style="font-size: var(--font-size-xs); color: var(--gray-600); margin-top: var(--space-2); text-align: center;">
                                        <?= htmlspecialchars($nomeMese) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Due colonne: Libri più prestati e Studenti più attivi -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: var(--space-6);">

                <!-- Libri più prestati -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-trophy"></i> Libri più prestati</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($stats['libri_piu_prenotati'])): ?>
                            <p style="text-align: center; color: var(--gray-600);">Nessun dato.</p>
                        <?php else: ?>
                            <div class="table-container">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Libro</th>
                                            <th class="text-center">Prestiti</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($stats['libri_piu_prenotati'] as $i => $lp): ?>
                                        <tr>
                                            <td><?= $i + 1 ?></td>
                                            <td>
                                                <strong><?= htmlspecialchars($lp['titolo']) ?></strong><br>
                                                <small style="color: var(--gray-600);"><?= htmlspecialchars($lp['autore'] ?? '') ?></small>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge badge-primary"><?= (int)$lp['num_prestiti'] ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Studenti più attivi -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-user-graduate"></i> Utenti più attivi</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($stats['studenti_attivi'])): ?>
                            <p style="text-align: center; color: var(--gray-600);">Nessun dato.</p>
                        <?php else: ?>
                            <div class="table-container">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Utente</th>
                                            <th class="text-center">Prestiti</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($stats['studenti_attivi'] as $i => $sa): ?>
                                        <tr>
                                            <td><?= $i + 1 ?></td>
                                            <td><strong><?= htmlspecialchars(($sa['cognome'] ?? '') . ' ' . ($sa['nome'] ?? '')) ?></strong></td>
                                            <td class="text-center">
                                                <span class="badge badge-primary"><?= (int)$sa['num_prestiti'] ?></span>
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

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
