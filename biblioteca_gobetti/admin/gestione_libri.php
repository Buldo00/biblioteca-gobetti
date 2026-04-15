<?php
/**
 * Gestione Libri - Biblioteca Gobetti (Bibliotecari+)
 */

require_once '../includes/functions.php';
requireMinLevel(LIVELLO_BIBLIOTECARIO);

$db = getDB();
$message = '';
$error = '';

// Gestione azioni
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $azione = $_POST['azione'] ?? '';
    
    try {
        if ($azione === 'aggiungi') {
            $stmt = $db->prepare("
                INSERT INTO libri 
                (tipo, titolo, autore, anno_uscita, casa_editrice, lingua, genere, 
                 codice_dewey, collocazione, numero_armadio, numero_ripiano, isbn, numero_copie, copie_disponibili, trama, immagine_copertina)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $copie = (int)$_POST['numero_copie'];
            
            $stmt->execute([
                $_POST['tipo'],
                $_POST['titolo'],
                $_POST['autore'] ?: null,
                $_POST['anno_uscita'] ?: null,
                $_POST['casa_editrice'] ?: null,
                $_POST['lingua'] ?: 'Italiano',
                $_POST['genere'] ?: null,
                $_POST['codice_dewey'] ?: null,
                $_POST['collocazione'] ?: null,
                $_POST['numero_armadio'] ?: null,
                $_POST['numero_ripiano'] ?: null,
                $_POST['isbn'] ?: null,
                $copie,
                $copie, // inizialmente tutte disponibili
                $_POST['trama'] ?: null,
                $_POST['immagine_copertina'] ?: null
            ]);
            
            logActivity($_SESSION['user_id'], 'libro_aggiunto', 'libri', $db->lastInsertId(), 'Nuovo elemento aggiunto');
            $message = 'Elemento aggiunto con successo!';
            
        } elseif ($azione === 'modifica') {
            $stmt = $db->prepare("
                UPDATE libri SET
                tipo = ?, titolo = ?, autore = ?, anno_uscita = ?, casa_editrice = ?,
                lingua = ?, genere = ?, codice_dewey = ?, collocazione = ?, numero_armadio = ?,
                numero_ripiano = ?, isbn = ?, numero_copie = ?, trama = ?, immagine_copertina = ?
                WHERE id = ?
            ");
            
            $stmt->execute([
                $_POST['tipo'],
                $_POST['titolo'],
                $_POST['autore'] ?: null,
                $_POST['anno_uscita'] ?: null,
                $_POST['casa_editrice'] ?: null,
                $_POST['lingua'],
                $_POST['genere'] ?: null,
                $_POST['codice_dewey'] ?: null,
                $_POST['collocazione'] ?: null,
                $_POST['numero_armadio'] ?: null,
                $_POST['numero_ripiano'] ?: null,
                $_POST['isbn'] ?: null,
                $_POST['numero_copie'],
                $_POST['trama'] ?: null,
                $_POST['immagine_copertina'] ?: null,
                $_POST['libro_id']
            ]);
            
            logActivity($_SESSION['user_id'], 'libro_modificato', 'libri', $_POST['libro_id'], 'Elemento modificato');
            $message = 'Elemento modificato con successo!';
            
        } elseif ($azione === 'elimina' && hasMinLevel(LIVELLO_AMMINISTRATIVO)) {
            $libro_id = $_POST['libro_id'];
            
            // Verifica che non ci siano prestiti attivi
            $stmt = $db->prepare("SELECT COUNT(*) as c FROM prestiti WHERE libro_id = ? AND stato IN ('attivo', 'in_ritardo')");
            $stmt->execute([$libro_id]);
            if ($stmt->fetch()['c'] > 0) {
                throw new Exception('Impossibile eliminare: ci sono prestiti attivi');
            }
            
            $stmt = $db->prepare("DELETE FROM libri WHERE id = ?");
            $stmt->execute([$libro_id]);
            
            logActivity($_SESSION['user_id'], 'libro_eliminato', 'libri', $libro_id, 'Libro eliminato');
            $message = 'Libro eliminato con successo!';
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Ottieni tutti i libri
$libri = $db->query("SELECT * FROM libri ORDER BY titolo ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Libri - Biblioteca Gobetti</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body data-livello="<?php echo $_SESSION['livello']; ?>">
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <h2>📚 Gestione Libri</h2>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo e($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo e($error); ?></div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header flex-between">
                <h3 class="card-title">Elementi nel Catalogo (<?php echo count($libri); ?>)</h3>
                <div style="display: flex; gap: 10px;">
                    <a href="genera_etichette.php" class="btn btn-warning">
                        🏷️ Genera Etichette
                    </a>
                    <button onclick="openModal('modal-aggiungi-libro')" class="btn btn-primary">
                        ➕ Aggiungi Elemento
                    </button>
                </div>
            </div>
            
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Titolo</th>
                            <th>Autore</th>
                            <th>Tipo</th>
                            <th>Genere</th>
                            <th>Copie</th>
                            <th>Disponibili</th>
                            <th>Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($libri as $libro): ?>
                            <tr>
                                <td><strong><?php echo e($libro['titolo']); ?></strong></td>
                                <td><?php echo e($libro['autore'] ?: '-'); ?></td>
                                <td><span class="badge badge-info"><?php echo e(ucfirst($libro['tipo'])); ?></span></td>
                                <td><?php echo e($libro['genere'] ?: '-'); ?></td>
                                <td><?php echo $libro['numero_copie']; ?></td>
                                <td>
                                    <span class="badge <?php echo $libro['copie_disponibili'] > 0 ? 'badge-success' : 'badge-danger'; ?>">
                                        <?php echo $libro['copie_disponibili']; ?>
                                    </span>
                                </td>
                                <td>
                                    <button onclick="openModal('modal-modifica-<?php echo $libro['id']; ?>')" class="btn btn-warning btn-sm">
                                        ✏️ Modifica
                                    </button>
                                    <?php if (hasMinLevel(LIVELLO_AMMINISTRATIVO)): ?>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Sei sicuro di voler eliminare questo libro?')">
                                            <input type="hidden" name="azione" value="elimina">
                                            <input type="hidden" name="libro_id" value="<?php echo $libro['id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">🗑️ Elimina</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            
                            <!-- Modal Modifica Libro -->
                            <div class="modal" id="modal-modifica-<?php echo $libro['id']; ?>">
                                <div class="modal-content" style="max-width: 800px;">
                                    <div class="modal-header">
                                        <h3 class="modal-title">Modifica Libro</h3>
                                        <button class="modal-close" onclick="closeModal('modal-modifica-<?php echo $libro['id']; ?>')">&times;</button>
                                    </div>
                                    
                                    <form method="POST">
                                        <input type="hidden" name="azione" value="modifica">
                                        <input type="hidden" name="libro_id" value="<?php echo $libro['id']; ?>">
                                        
                                        <?php include 'form_libro.php'; ?>
                                        
                                        <button type="submit" class="btn btn-primary btn-block">Salva Modifiche</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div style="margin-top: 30px; text-align: center;">
            <a href="../user/dashboard.php" class="btn btn-secondary">← Torna alla Dashboard</a>
        </div>
    </div>
    
    <!-- Modal Aggiungi Elemento -->
    <div class="modal" id="modal-aggiungi-libro">
        <div class="modal-content" style="max-width: 800px;">
            <div class="modal-header">
                <h3 class="modal-title">Aggiungi Nuovo Elemento</h3>
                <button class="modal-close" onclick="closeModal('modal-aggiungi-libro')">&times;</button>
            </div>
            
            <form method="POST">
                <input type="hidden" name="azione" value="aggiungi">
                
                <?php $libro = null; include 'form_libro.php'; ?>
                
                <button type="submit" class="btn btn-primary btn-block">Aggiungi Elemento</button>
            </form>
        </div>
    </div>
    
    <script src="../assets/js/main.js"></script>
</body>
</html>
