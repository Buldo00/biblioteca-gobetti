<?php
/**
 * Catalogo Libri - Biblioteca Gobetti
 */

require_once '../includes/functions.php';
requireLogin();

$user = getCurrentUser();
$db = getDB();

// Parametri ricerca e filtri
$search = $_GET['search'] ?? '';
$tipo = $_GET['tipo'] ?? '';
$genere = $_GET['genere'] ?? '';
$disponibilita = $_GET['disponibilita'] ?? '';

// Query base
$sql = "SELECT * FROM libri WHERE 1=1";
$params = [];

if ($search) {
    $sql .= " AND (titolo LIKE ? OR autore LIKE ? OR genere LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if ($tipo) {
    $sql .= " AND tipo = ?";
    $params[] = $tipo;
}

if ($genere) {
    $sql .= " AND genere = ?";
    $params[] = $genere;
}

if ($disponibilita === 'si') {
    $sql .= " AND copie_disponibili > 0";
} elseif ($disponibilita === 'no') {
    $sql .= " AND copie_disponibili = 0";
}

$sql .= " ORDER BY titolo ASC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$libri = $stmt->fetchAll();

// Ottieni tutti i generi per il filtro
$generi = $db->query("SELECT DISTINCT genere FROM libri WHERE genere IS NOT NULL ORDER BY genere")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catalogo - Biblioteca Gobetti</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body data-livello="<?php echo $user['livello']; ?>">
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">📚 Catalogo Biblioteca</h2>
            </div>
            
            <!-- Barra di Ricerca -->
            <div class="search-box">
                <input 
                    type="text" 
                    id="search-input" 
                    placeholder="Cerca per titolo, autore, genere..."
                    value="<?php echo e($search); ?>"
                >
                <span class="search-icon">🔍</span>
            </div>
            
            <!-- Filtri -->
            <form method="GET" action="" class="filters">
                <div class="filter-group">
                    <label class="form-label">Tipo</label>
                    <select name="tipo" id="filtro_tipo" class="form-control">
                        <option value="">Tutti</option>
                        <option value="libro" <?php echo $tipo === 'libro' ? 'selected' : ''; ?>>Libri</option>
                        <option value="rivista" <?php echo $tipo === 'rivista' ? 'selected' : ''; ?>>Riviste</option>
                        <option value="dizionario" <?php echo $tipo === 'dizionario' ? 'selected' : ''; ?>>Dizionari</option>
                        <option value="manuale" <?php echo $tipo === 'manuale' ? 'selected' : ''; ?>>Manuali</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label class="form-label">Genere</label>
                    <select name="genere" id="filtro_genere" class="form-control">
                        <option value="">Tutti</option>
                        <?php foreach ($generi as $g): ?>
                            <option value="<?php echo e($g); ?>" <?php echo $genere === $g ? 'selected' : ''; ?>>
                                <?php echo e($g); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label class="form-label">Disponibilità</label>
                    <select name="disponibilita" id="filtro_disponibilita" class="form-control">
                        <option value="">Tutti</option>
                        <option value="si" <?php echo $disponibilita === 'si' ? 'selected' : ''; ?>>Disponibili</option>
                        <option value="no" <?php echo $disponibilita === 'no' ? 'selected' : ''; ?>>Non disponibili</option>
                    </select>
                </div>
                
                <div class="filter-group" style="display: flex; align-items: flex-end;">
                    <button type="submit" class="btn btn-primary">Applica Filtri</button>
                    <a href="catalogo.php" class="btn btn-secondary" style="margin-left: 10px;">Reset</a>
                </div>
            </form>
        </div>
        
        <!-- Griglia Libri -->
        <div class="book-grid">
            <?php if (empty($libri)): ?>
                <div class="card" style="grid-column: 1 / -1; text-align: center;">
                    <p>Nessun libro trovato con i criteri di ricerca selezionati.</p>
                </div>
            <?php endif; ?>
            
            <?php foreach ($libri as $libro): ?>
                <div class="book-card" 
                     data-tipo="<?php echo e($libro['tipo']); ?>"
                     data-genere="<?php echo e($libro['genere']); ?>"
                     data-disponibile="<?php echo $libro['copie_disponibili'] > 0 ? '1' : '0'; ?>"
                     data-search="<?php echo e($libro['titolo'] . ' ' . $libro['autore'] . ' ' . $libro['genere']); ?>"
                     onclick="openModal('modal-libro-<?php echo $libro['id']; ?>')">
                    
                    <?php if ($libro['immagine_copertina']): ?>
                        <img src="<?php echo e($libro['immagine_copertina']); ?>" alt="Copertina" class="book-cover">
                    <?php else: ?>
                        <div class="book-cover" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; color: white; font-size: 3rem;">
                            📖
                        </div>
                    <?php endif; ?>
                    
                    <div class="book-info">
                        <h3 class="book-title"><?php echo e($libro['titolo']); ?></h3>
                        <?php if ($libro['autore']): ?>
                            <p class="book-author"><?php echo e($libro['autore']); ?></p>
                        <?php endif; ?>
                        
                        <div style="margin: 10px 0;">
                            <span class="badge badge-info"><?php echo e(ucfirst($libro['tipo'])); ?></span>
                            <?php if ($libro['genere']): ?>
                                <span class="badge badge-primary"><?php echo e($libro['genere']); ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="book-meta">
                            <div class="availability">
                                <span class="availability-dot <?php echo $libro['copie_disponibili'] > 0 ? '' : 'unavailable'; ?>"></span>
                                <span><?php echo $libro['copie_disponibili']; ?>/<?php echo $libro['numero_copie']; ?> disponibili</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Modal Dettagli Libro -->
                <div class="modal" id="modal-libro-<?php echo $libro['id']; ?>">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3 class="modal-title"><?php echo e($libro['titolo']); ?></h3>
                            <button class="modal-close" onclick="closeModal('modal-libro-<?php echo $libro['id']; ?>')">&times;</button>
                        </div>
                        
                        <div class="form-row">
                            <div>
                                <?php if ($libro['immagine_copertina']): ?>
                                    <img src="<?php echo e($libro['immagine_copertina']); ?>" alt="Copertina" style="width: 100%; border-radius: 8px;">
                                <?php endif; ?>
                            </div>
                            
                            <div>
                                <?php if ($libro['autore']): ?>
                                    <p><strong>Autore:</strong> <?php echo e($libro['autore']); ?></p>
                                <?php endif; ?>
                                <?php if ($libro['anno_uscita']): ?>
                                    <p><strong>Anno:</strong> <?php echo e($libro['anno_uscita']); ?></p>
                                <?php endif; ?>
                                <?php if ($libro['casa_editrice']): ?>
                                    <p><strong>Editore:</strong> <?php echo e($libro['casa_editrice']); ?></p>
                                <?php endif; ?>
                                <p><strong>Lingua:</strong> <?php echo e($libro['lingua']); ?></p>
                                <p><strong>Tipo:</strong> <?php echo e(ucfirst($libro['tipo'])); ?></p>
                                <?php if ($libro['genere']): ?>
                                    <p><strong>Genere:</strong> <?php echo e($libro['genere']); ?></p>
                                <?php endif; ?>
                                <?php if ($libro['codice_dewey']): ?>
                                    <p><strong>Codice Dewey:</strong> <?php echo e($libro['codice_dewey']); ?></p>
                                <?php endif; ?>
                                <?php if ($libro['collocazione']): ?>
                                    <p><strong>Collocazione:</strong> <?php echo e($libro['collocazione']); ?></p>
                                <?php endif; ?>
                                <p><strong>Disponibilità:</strong> <?php echo $libro['copie_disponibili']; ?>/<?php echo $libro['numero_copie']; ?></p>
                            </div>
                        </div>
                        
                        <?php if ($libro['trama']): ?>
                            <div style="margin-top: 20px;">
                                <strong>Trama:</strong>
                                <p><?php echo nl2br(e($libro['trama'])); ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <div style="margin-top: 20px; text-align: center;">
                            <?php if ($libro['copie_disponibili'] > 0 && !$user['in_blacklist']): ?>
                                <button 
                                    onclick="prenotaLibro(<?php echo $libro['id']; ?>)"
                                    class="btn btn-success">
                                    📦 Prenota Ora
                                </button>
                            <?php elseif ($libro['copie_disponibili'] == 0): ?>
                                <button 
                                    onclick="richiediNotifica(<?php echo $libro['id']; ?>)"
                                    class="btn btn-warning"
                                    data-libro-id="<?php echo $libro['id']; ?>">
                                    🔔 Avvisami quando disponibile
                                </button>
                            <?php elseif ($user['in_blacklist']): ?>
                                <div class="alert alert-danger">
                                    Sei in blacklist. Non puoi prenotare.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <script src="../assets/js/main.js"></script>
    <script>
        // Ricerca in tempo reale
        document.getElementById('search-input').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const cards = document.querySelectorAll('.book-card');
            
            cards.forEach(card => {
                const searchData = card.getAttribute('data-search').toLowerCase();
                if (searchData.includes(searchTerm)) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>
