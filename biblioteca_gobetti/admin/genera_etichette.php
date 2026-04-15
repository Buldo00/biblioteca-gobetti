<?php
/**
 * Genera Etichette - Biblioteca Gobetti (Bibliotecari+)
 */

require_once '../includes/functions.php';
requireMinLevel(LIVELLO_BIBLIOTECARIO);

$db = getDB();

// Ottieni libro se specificato
$libro_id = $_GET['libro_id'] ?? null;
$libro = null;

if ($libro_id) {
    $stmt = $db->prepare("SELECT * FROM libri WHERE id = ?");
    $stmt->execute([$libro_id]);
    $libro = $stmt->fetch();
}

// Ottieni tutti i libri per il dropdown
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
            padding: 10mm;
            margin: 10mm auto;
            background: white;
            page-break-after: always;
            display: flex;
            gap: 5mm;
            position: relative;
        }
        
        .etichetta-info {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        
        .etichetta-header {
            text-align: center;
            font-weight: bold;
            font-size: 16pt;
            border-bottom: 2px solid #333;
            padding-bottom: 2mm;
            margin-bottom: 3mm;
        }
        
        .etichetta-campo {
            margin: 2mm 0;
            font-size: 11pt;
        }
        
        .etichetta-campo strong {
            display: inline-block;
            width: 40mm;
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
            body {
                margin: 0;
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
            .etichetta {
                margin: 0;
                page-break-after: always;
            }
        }
        
        .form-etichette {
            max-width: 800px;
            margin: 0 auto 30px;
        }
    </style>
</head>
<body>
    <div class="no-print">
        <?php include '../includes/header.php'; ?>
        
        <div class="container">
            <h2>🏷️ Genera Etichette Libri</h2>
            
            <div class="card form-etichette">
                <div class="card-header">
                    <h3 class="card-title">Seleziona Libro</h3>
                </div>
                
                <form method="GET" action="">
                    <div class="form-group">
                        <label class="form-label">Libro *</label>
                        <select name="libro_id" class="form-control" required onchange="this.form.submit()">
                            <option value="">-- Seleziona un libro --</option>
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
                        <button onclick="window.print()" class="btn btn-primary">
                            🖨️ Stampa Etichette
                        </button>
                        <a href="genera_etichette.php" class="btn btn-secondary">
                            🔄 Genera per Altro Libro
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php if ($libro): ?>
        <!-- Genera un'etichetta per ogni copia -->
        <?php for ($copia = 1; $copia <= $libro['numero_copie']; $copia++): ?>
            <div class="etichetta" id="etichetta-<?php echo $copia; ?>">
                <div class="etichetta-info">
                    <div class="etichetta-header">
                        BIBLIOTECA GOBETTI
                    </div>
                    
                    <div class="etichetta-campo">
                        <strong>Titolo:</strong> <?php echo e($libro['titolo']); ?>
                    </div>
                    
                    <?php if ($libro['autore']): ?>
                        <div class="etichetta-campo">
                            <strong>Autore:</strong> <?php echo e($libro['autore']); ?>
                        </div>
                    <?php endif; ?>
                    
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
            // Genera QR codes per ogni etichetta
            <?php for ($copia = 1; $copia <= $libro['numero_copie']; $copia++): ?>
                QRCode.toCanvas(
                    document.getElementById('qr-<?php echo $copia; ?>'),
                    '<?php echo "http://" . $_SERVER['HTTP_HOST'] . "/biblioteca_gobetti/user/dettaglio_libro.php?id=" . $libro['id'] . "&copia=" . $copia; ?>',
                    {
                        width: 180,
                        margin: 1,
                        errorCorrectionLevel: 'M'
                    }
                );
            <?php endfor; ?>
        </script>
    <?php endif; ?>
    
    <script src="../assets/js/main.js"></script>
</body>
</html>
