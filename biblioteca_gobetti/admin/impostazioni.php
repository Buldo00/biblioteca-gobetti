<?php
/**
 * Impostazioni - Biblioteca Gobetti
 * Configurazione del sistema (solo Admin)
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireMinLevel(LIVELLO_ADMIN);

$currentUser = getCurrentUser();
$baseUrl = getBaseUrl();
$message = '';
$error = '';

// Definizione impostazioni con etichette e descrizioni
$impostazioniConfig = [
    'max_prestiti_studente' => [
        'label' => 'Max prestiti studente',
        'descrizione' => 'Numero massimo di prestiti contemporanei per gli studenti',
        'tipo' => 'number',
        'default' => '3',
    ],
    'max_prestiti_docente' => [
        'label' => 'Max prestiti docente',
        'descrizione' => 'Numero massimo di prestiti contemporanei per i docenti',
        'tipo' => 'number',
        'default' => '3',
    ],
    'giorni_ritiro' => [
        'label' => 'Giorni per ritiro',
        'descrizione' => 'Giorni a disposizione per ritirare un libro prenotato',
        'tipo' => 'number',
        'default' => '3',
    ],
    'giorni_prestito' => [
        'label' => 'Giorni prestito',
        'descrizione' => 'Durata massima in giorni di un prestito',
        'tipo' => 'number',
        'default' => '30',
    ],
    'limite_blacklist' => [
        'label' => 'Limite blacklist',
        'descrizione' => 'Numero di ritardi prima dell\'inserimento automatico in blacklist',
        'tipo' => 'number',
        'default' => '3',
    ],
    'orario_apertura' => [
        'label' => 'Orario apertura',
        'descrizione' => 'Orario di apertura della biblioteca',
        'tipo' => 'time',
        'default' => '08:00',
    ],
    'orario_chiusura' => [
        'label' => 'Orario chiusura',
        'descrizione' => 'Orario di chiusura della biblioteca',
        'tipo' => 'time',
        'default' => '16:00',
    ],
    'email_biblioteca' => [
        'label' => 'Email biblioteca',
        'descrizione' => 'Indirizzo email della biblioteca per comunicazioni',
        'tipo' => 'email',
        'default' => '',
    ],
    'nome_biblioteca' => [
        'label' => 'Nome biblioteca',
        'descrizione' => 'Nome visualizzato della biblioteca',
        'tipo' => 'text',
        'default' => 'Biblioteca Gobetti',
    ],
];

// Gestione salvataggio
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $azione = $_POST['azione'] ?? '';

    try {
        if ($azione === 'salva_impostazioni') {
            $salvate = 0;
            foreach ($impostazioniConfig as $chiave => $config) {
                if (isset($_POST['setting_' . $chiave])) {
                    $valore = trim($_POST['setting_' . $chiave]);
                    setSetting($chiave, $valore, $currentUser['id']);
                    $salvate++;
                }
            }
            logOperazione($currentUser['id'], 'impostazioni_salvate', 'biblioteca_settings', null, "$salvate impostazioni aggiornate");
            $message = "Impostazioni salvate con successo! ($salvate aggiornate)";
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Carica impostazioni correnti
$impostazioniDB = getAllSettings();
$valoriCorrente = [];
foreach ($impostazioniDB as $imp) {
    $valoriCorrente[$imp['chiave']] = $imp['valore'];
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-cog"></i> Impostazioni Sistema</h2>
        </div>
        <div class="card-body">

            <?php if ($message): ?>
                <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="azione" value="salva_impostazioni">

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: var(--space-6);">
                    <?php foreach ($impostazioniConfig as $chiave => $config): ?>
                        <?php
                            $valoreCorrente = $valoriCorrente[$chiave] ?? $config['default'];
                        ?>
                        <div class="form-group" style="padding: var(--space-4); background: var(--gray-50); border-radius: var(--border-radius); border: 1px solid var(--gray-200);">
                            <label style="font-weight: 600; font-size: var(--font-size-base);">
                                <?= htmlspecialchars($config['label']) ?>
                            </label>
                            <p style="font-size: var(--font-size-sm); color: var(--gray-600); margin-bottom: var(--space-2);">
                                <?= htmlspecialchars($config['descrizione']) ?>
                            </p>
                            <?php if ($config['tipo'] === 'number'): ?>
                                <input type="number" name="setting_<?= htmlspecialchars($chiave) ?>"
                                       class="form-control" value="<?= htmlspecialchars($valoreCorrente) ?>" min="0">
                            <?php elseif ($config['tipo'] === 'time'): ?>
                                <input type="time" name="setting_<?= htmlspecialchars($chiave) ?>"
                                       class="form-control" value="<?= htmlspecialchars($valoreCorrente) ?>">
                            <?php elseif ($config['tipo'] === 'email'): ?>
                                <input type="email" name="setting_<?= htmlspecialchars($chiave) ?>"
                                       class="form-control" value="<?= htmlspecialchars($valoreCorrente) ?>" placeholder="email@esempio.it">
                            <?php else: ?>
                                <input type="text" name="setting_<?= htmlspecialchars($chiave) ?>"
                                       class="form-control" value="<?= htmlspecialchars($valoreCorrente) ?>">
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div style="margin-top: var(--space-6); display: flex; gap: var(--space-4);">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-save"></i> Salva impostazioni
                    </button>
                    <button type="reset" class="btn btn-secondary">
                        <i class="fas fa-undo"></i> Ripristina
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
