<?php
/**
 * Impostazioni Sistema - Biblioteca Gobetti (Admin+)
 */

require_once '../includes/functions.php';
requireMinLevel(LIVELLO_BIBLIOTECARIO);

$db = getDB();
$message = '';
$error = '';

// Gestione salvataggio
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        foreach ($_POST as $chiave => $valore) {
            if ($chiave !== 'salva') {
                setSetting($chiave, $valore, $_SESSION['user_id']);
            }
        }
        
        logActivity($_SESSION['user_id'], 'impostazioni_modificate', 'impostazioni', null, 'Impostazioni sistema aggiornate');
        $message = 'Impostazioni salvate con successo!';
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Ottieni tutte le impostazioni
$impostazioni = $db->query("SELECT * FROM impostazioni ORDER BY id")->fetchAll();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Impostazioni - Biblioteca Gobetti</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body data-livello="<?php echo $_SESSION['livello']; ?>">
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <h2>⚙️ Impostazioni Sistema</h2>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo e($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo e($error); ?></div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">📝 Configurazione Biblioteca</h3>
            </div>
            
            <form method="POST">
                <?php foreach ($impostazioni as $impostazione): ?>
                    <div class="form-group">
                        <label class="form-label">
                            <strong><?php echo e(ucfirst(str_replace('_', ' ', $impostazione['chiave']))); ?></strong>
                            <?php if ($impostazione['descrizione']): ?>
                                <br><small style="color: #7f8c8d;"><?php echo e($impostazione['descrizione']); ?></small>
                            <?php endif; ?>
                        </label>
                        
                        <?php if ($impostazione['tipo'] === 'boolean'): ?>
                            <select name="<?php echo e($impostazione['chiave']); ?>" class="form-control">
                                <option value="1" <?php echo $impostazione['valore'] == '1' ? 'selected' : ''; ?>>Sì</option>
                                <option value="0" <?php echo $impostazione['valore'] == '0' ? 'selected' : ''; ?>>No</option>
                            </select>
                            
                        <?php elseif ($impostazione['tipo'] === 'int'): ?>
                            <input 
                                type="number" 
                                name="<?php echo e($impostazione['chiave']); ?>" 
                                class="form-control" 
                                value="<?php echo e($impostazione['valore']); ?>"
                                min="0"
                            >
                            
                        <?php elseif ($impostazione['tipo'] === 'json'): ?>
                            <textarea 
                                name="<?php echo e($impostazione['chiave']); ?>" 
                                class="form-control" 
                                rows="4"
                            ><?php echo e($impostazione['valore']); ?></textarea>
                            <small style="color: #7f8c8d;">Formato JSON</small>
                            
                        <?php else: ?>
                            <input 
                                type="text" 
                                name="<?php echo e($impostazione['chiave']); ?>" 
                                class="form-control" 
                                value="<?php echo e($impostazione['valore']); ?>"
                            >
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                
                <button type="submit" name="salva" class="btn btn-primary btn-block">
                    💾 Salva Impostazioni
                </button>
            </form>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">ℹ️ Informazioni Sistema</h3>
            </div>
            
            <div class="form-group">
                <strong>Versione PHP:</strong> <?php echo PHP_VERSION; ?>
            </div>
            <div class="form-group">
                <strong>Database:</strong> MySQL
            </div>
            <div class="form-group">
                <strong>Ultima modifica impostazioni:</strong> 
                <?php 
                $ultima = $db->query("SELECT MAX(ultima_modifica) as data FROM impostazioni")->fetch();
                echo formatData($ultima['data'], true);
                ?>
            </div>
        </div>
        
        <div style="margin-top: 30px; text-align: center;">
            <a href="../user/dashboard.php" class="btn btn-secondary">← Torna alla Dashboard</a>
        </div>
    </div>
    
    <script src="../assets/js/main.js"></script>
</body>
</html>
