<?php
/**
 * Genera Etichette - Biblioteca Gobetti
 * Generazione etichette con QR code per copie dei libri
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireMinLevel(LIVELLO_BIBLIOTECARIO);

$currentUser = getCurrentUser();
$baseUrl = getBaseUrl();

$idCopia = (int)($_GET['id_copia'] ?? 0);
if ($idCopia <= 0) {
    header('Location: gestione_libri.php');
    exit;
}

$copia = getCopia($idCopia);
if (!$copia) {
    header('Location: gestione_libri.php');
    exit;
}

$qrCode = $copia['qr_code_univoco'] ?? '';
$qrImageUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($qrCode);

// Modalità stampa
$stampa = isset($_GET['stampa']);

if ($stampa):
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Etichetta - <?= htmlspecialchars($qrCode) ?></title>
    <style>
        @media print {
            body { margin: 0; }
            .no-print { display: none !important; }
        }
        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px;
        }
        .etichetta {
            border: 2px solid #333;
            border-radius: 8px;
            padding: 20px;
            width: 300px;
            text-align: center;
            background: #fff;
        }
        .etichetta h2 {
            font-size: 16px;
            margin: 0 0 12px 0;
            padding-bottom: 8px;
            border-bottom: 2px solid #333;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .etichetta .info {
            text-align: left;
            font-size: 13px;
            margin-bottom: 12px;
        }
        .etichetta .info div {
            margin: 4px 0;
        }
        .etichetta .info strong {
            display: inline-block;
            min-width: 70px;
        }
        .etichetta .qr-img {
            margin: 10px auto;
        }
        .etichetta .qr-img img {
            width: 150px;
            height: 150px;
        }
        .etichetta .qr-code-text {
            font-size: 11px;
            color: #666;
            margin-top: 6px;
            word-break: break-all;
        }
        .btn-print {
            margin-top: 20px;
            padding: 10px 30px;
            background: #3498db;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
        }
        .btn-print:hover { background: #2980b9; }
    </style>
</head>
<body>
    <div class="etichetta">
        <h2>Biblioteca Gobetti</h2>
        <div class="info">
            <div><strong>Copia:</strong> <?= (int)$copia['numero_copia'] ?></div>
            <div><strong>Armadio:</strong> <?= htmlspecialchars($copia['numero_armadio'] ?? '-') ?></div>
            <div><strong>Ripiano:</strong> <?= htmlspecialchars($copia['numero_ripiano'] ?? '-') ?></div>
            <div><strong>Aula:</strong> <?= htmlspecialchars($copia['numero_aula'] ?? '-') ?></div>
            <div><strong>Dewey:</strong> <?= htmlspecialchars($copia['codice_dewey'] ?? '-') ?></div>
        </div>
        <div class="qr-img">
            <img src="<?= htmlspecialchars($qrImageUrl) ?>" alt="QR Code">
        </div>
        <div class="qr-code-text"><?= htmlspecialchars($qrCode) ?></div>
    </div>

    <div class="no-print">
        <button class="btn-print" onclick="window.print()">
            🖨️ Stampa / Scarica PDF
        </button>
        <p style="margin-top: 10px; font-size: 13px; color: #666;">
            Usa "Stampa" > "Salva come PDF" nel browser per generare il PDF.
        </p>
        <p style="margin-top: 10px;">
            <a href="genera_etichette.php?id_copia=<?= (int)$idCopia ?>">← Torna all'anteprima</a>
        </p>
    </div>
</body>
</html>
<?php
exit;
endif;

// Pagina anteprima normale
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-qrcode"></i> Genera Etichetta</h2>
            <div class="card-actions">
                <a href="gestione_copie.php?id_libro=<?= (int)$copia['id_libro'] ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Torna alle copie
                </a>
            </div>
        </div>
        <div class="card-body">

            <!-- Info copia -->
            <div style="margin-bottom: var(--space-6); padding: var(--space-4); background: var(--gray-50); border-radius: var(--border-radius);">
                <h3 style="margin-bottom: var(--space-3);">Informazioni copia</h3>
                <div class="form-row" style="gap: var(--space-6); flex-wrap: wrap;">
                    <div><strong>Libro:</strong> <?= htmlspecialchars($copia['titolo'] ?? '') ?></div>
                    <div><strong>Autore:</strong> <?= htmlspecialchars($copia['autore'] ?? '-') ?></div>
                    <div><strong>Copia n°:</strong> <?= (int)$copia['numero_copia'] ?></div>
                    <div><strong>QR Code:</strong> <code><?= htmlspecialchars($qrCode) ?></code></div>
                </div>
            </div>

            <!-- Anteprima etichetta -->
            <div style="display: flex; flex-direction: column; align-items: center; margin-bottom: var(--space-6);">
                <h3 style="margin-bottom: var(--space-4);">Anteprima etichetta</h3>
                <div style="border: 2px solid var(--gray-800); border-radius: var(--border-radius); padding: var(--space-6); width: 300px; text-align: center; background: var(--white);">
                    <h4 style="font-size: 16px; margin-bottom: var(--space-3); padding-bottom: var(--space-2); border-bottom: 2px solid var(--gray-800); text-transform: uppercase; letter-spacing: 1px;">
                        Biblioteca Gobetti
                    </h4>
                    <div style="text-align: left; font-size: 13px; margin-bottom: var(--space-3);">
                        <div style="margin: 4px 0;"><strong>Copia:</strong> <?= (int)$copia['numero_copia'] ?></div>
                        <div style="margin: 4px 0;"><strong>Armadio:</strong> <?= htmlspecialchars($copia['numero_armadio'] ?? '-') ?></div>
                        <div style="margin: 4px 0;"><strong>Ripiano:</strong> <?= htmlspecialchars($copia['numero_ripiano'] ?? '-') ?></div>
                        <div style="margin: 4px 0;"><strong>Aula:</strong> <?= htmlspecialchars($copia['numero_aula'] ?? '-') ?></div>
                        <div style="margin: 4px 0;"><strong>Dewey:</strong> <?= htmlspecialchars($copia['codice_dewey'] ?? '-') ?></div>
                    </div>
                    <div style="margin: var(--space-3) auto;">
                        <img src="<?= htmlspecialchars($qrImageUrl) ?>" alt="QR Code" style="width: 150px; height: 150px;">
                    </div>
                    <div style="font-size: 11px; color: var(--gray-600); word-break: break-all;">
                        <?= htmlspecialchars($qrCode) ?>
                    </div>
                </div>
            </div>

            <!-- Bottoni azione -->
            <div style="display: flex; gap: var(--space-4); justify-content: center; flex-wrap: wrap;">
                <a href="genera_etichette.php?id_copia=<?= (int)$idCopia ?>&stampa=1" class="btn btn-primary btn-lg" target="_blank">
                    <i class="fas fa-file-pdf"></i> Scarica PDF
                </a>
                <a href="genera_etichette.php?id_copia=<?= (int)$idCopia ?>&stampa=1" class="btn btn-success btn-lg" target="_blank">
                    <i class="fas fa-sync-alt"></i> Rigenera etichetta
                </a>
            </div>

            <p style="text-align: center; margin-top: var(--space-4); color: var(--gray-600); font-size: var(--font-size-sm);">
                Per scaricare il PDF, usa la funzione "Stampa" → "Salva come PDF" del browser nella pagina di stampa.<br>
                <em>Nota: per la generazione PDF nativa lato server è necessaria la libreria TCPDF.</em>
            </p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
