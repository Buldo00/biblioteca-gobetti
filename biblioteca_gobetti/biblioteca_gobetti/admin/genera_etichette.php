<?php
/**
 * Genera Etichette - Biblioteca Gobetti (Bibliotecari+)
 * Etichette semplificate con solo dati localizzazione fisica
 */

require_once '../includes/functions.php';
requireMinLevel(LIVELLO_BIBLIOTECARIO);

$db = getDB();

$libro_id = $_GET['libro_id'] ?? null;
$libro = null;

if ($libro_id) {
    $stmt = $db->prepare("SELECT * FROM libri WHERE id = ?");
    $stmt->execute([$libro_id]);
    $libro = $stmt->fetch();
}

$libri = $db->query("SELECT id, titolo, autore, numero_copie FROM libri ORDER BY titolo ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Genera Etichette - Biblioteca Gobetti</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.1/build/qrcode.min.js"></script>
    <style>
        .etichetta {
            width: 210mm;
            height: 60mm;
            border: 2px solid #333;
            padding: 8mm;
            margin: 10mm auto;
            background: white;
            page-break-after: always;
            display: flex;
            gap: 5mm;
            position: relative;
            overflow: hidden;
        }
        
        .etichetta-info {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .etichetta-header {
            text-align: center;
            font-weight: bold;
            font-size: 16pt;
            border-bottom: 3px solid #333;
            padding-bottom: 3mm;
            margin-bottom: 4mm;
        }
        
        .etichetta-campo {
            margin: 2mm 0;
            font-size: 11pt;
            line-height: 1.4;
        }
        
        .etichetta-campo strong {
            display: inline-block;
            width: 30mm;
            font-weight: 600;
        }
        
        .etichetta-qr {
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 50mm;
        }
        
        .etichetta-qr canvas {
            border: 1px solid #ccc;
        }
        
        @media print {
            body { margin: 0; padding: 0; }
            .no-print { display: none !important; }
            .etichetta { margin: 0; page-break-after: always; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <?php include '../includes/header.php'; ?>
        
        <div class="container">
            <h2>🏷️ Genera Etichette</h2>
            
            <div class="card" style="max-width: 800px; margin: 0 auto;">
                <div class="card-header">
                    <h3 class="card-title">Seleziona Libro</h3>
                </div>
                
                <form method="GET">
                    <div class="form-group">
                        <label class="form-label">Libro *</label>
                        <select name="libro_id" class="form-control" required onchange="this.form.submit()">
                            <option value="">-- Seleziona libro --</option>
                            <?php foreach ($libri as $l): ?>
                                <option value="<?php echo $l['id']; ?>" <?php echo $libro_id == $l['id'] ? 'selected' : ''; ?>>
                                    <?php echo e($l['titolo']); ?><?php echo $l['autore'] ? ' - ' . e($l['autore']) : ''; ?>
                                    (<?php echo $l['numero_copie']; ?> copie)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
                
                <?php if ($libro): ?>
                    <div style="margin-top: 20px; text-align: center;">
                        <button onclick="window.print()" class="btn btn-primary">🖨️ Stampa Etichette</button>
                        <a href="genera_etichette.php" class="btn btn-secondary">🔄 Altro Libro</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php if ($libro): ?>
        <?php for ($copia = 1; $copia <= $libro['numero_copie']; $copia++): ?>
            <div class="etichetta">
                <div class="etichetta-info">
                    <div class="etichetta-header">BIBLIOTECA GOBETTI</div>
                    
                    <div class="etichetta-campo">
                        <strong>Copia:</strong> <?php echo $copia; ?> / <?php echo $libro['numero_copie']; ?>
                    </div>
                    
                    <?php if ($libro['numero_armadio']): ?>
                        <div class="etichetta-campo">
                            <strong>Armadio:</strong> <?php echo e($libro['numero_armadio']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($libro['numero_ripiano']): ?>
                        <div class="etichetta-campo">
                            <strong>Ripiano:</strong> <?php echo e($libro['numero_ripiano']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($libro['numero_aula']): ?>
                        <div class="etichetta-campo">
                            <strong>Aula:</strong> <?php echo e($libro['numero_aula']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($libro['codice_dewey']): ?>
                        <div class="etichetta-campo">
                            <strong>Dewey:</strong> <?php echo e($libro['codice_dewey']); ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="etichetta-qr">
                    <canvas id="qr-<?php echo $copia; ?>"></canvas>
                </div>
            </div>
        <?php endfor; ?>
        
        <script>
            <?php for ($copia = 1; $copia <= $libro['numero_copie']; $copia++): ?>
                QRCode.toCanvas(
                    document.getElementById('qr-<?php echo $copia; ?>'),
                    '<?php echo "http://" . $_SERVER['HTTP_HOST'] . "/biblioteca_gobetti/user/dettaglio_libro.php?id=" . $libro['id'] . "&copia=" . $copia; ?>',
                    { width: 170, margin: 1, errorCorrectionLevel: 'M' }
                );
            <?php endfor; ?>
        </script>
    <?php endif; ?>
    
    <script src="../assets/js/main.js"></script>
</body>
</html>
