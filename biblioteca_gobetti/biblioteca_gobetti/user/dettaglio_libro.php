<?php
/**
 * Dettaglio Libro da QR Code - Biblioteca Gobetti
 */

require_once '../includes/functions.php';
requireLogin();

$db = getDB();
$user = getCurrentUser();

$libro_id = $_GET['id'] ?? null;
$copia = $_GET['copia'] ?? 1;

if (!$libro_id) {
    die("Libro non specificato");
}

// Ottieni libro
$stmt = $db->prepare("SELECT * FROM libri WHERE id = ?");
$stmt->execute([$libro_id]);
$libro = $stmt->fetch();

if (!$libro) {
    die("Libro non trovato");
}

// Verifica se il libro è in prestito per questa copia
$stmt = $db->prepare("
    SELECT p.*, u.nome, u.cognome 
    FROM prestiti p
    JOIN utenti u ON p.utente_id = u.id
    WHERE p.libro_id = ? AND p.stato IN ('attivo', 'in_ritardo')
    LIMIT 1
");
$stmt->execute([$libro_id]);
$prestito_attivo = $stmt->fetch();

// Determina azione basata su ruolo e stato
$is_bibliotecario = $user['livello'] >= LIVELLO_BIBLIOTECARIO;
$azione = null;

if ($is_bibliotecario) {
    if ($prestito_attivo) {
        $azione = 'restituzione';
    } else {
        $azione = 'assegnazione';
    }
} else {
    // Studenti e docenti vedono solo disponibilità
    $azione = 'visualizza';
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($libro['titolo']); ?> - Biblioteca Gobetti</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body data-livello="<?php echo $user['livello']; ?>">
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">
                    <?php echo e($libro['titolo']); ?>
                    <span class="badge badge-info" style="font-size: 0.7em; margin-left: 10px;">
                        Copia <?php echo $copia; ?>/<?php echo $libro['numero_copie']; ?>
                    </span>
                </h2>
            </div>
            
            <div class="form-row">
                <div style="flex: 1;">
                    <?php if ($libro['immagine_copertina']): ?>
                        <img src="<?php echo e($libro['immagine_copertina']); ?>" alt="Copertina" style="width: 100%; max-width: 300px; border-radius: 12px;">
                    <?php endif; ?>
                </div>
                
                <div style="flex: 2;">
                    <?php if ($libro['autore']): ?>
                        <p><strong>Autore:</strong> <?php echo e($libro['autore']); ?></p>
                    <?php endif; ?>
                    <?php if ($libro['anno_uscita']): ?>
                        <p><strong>Anno:</strong> <?php echo e($libro['anno_uscita']); ?></p>
                    <?php endif; ?>
                    <?php if ($libro['casa_editrice']): ?>
                        <p><strong>Editore:</strong> <?php echo e($libro['casa_editrice']); ?></p>
                    <?php endif; ?>
                    <p><strong>Tipo:</strong> <?php echo e(ucfirst($libro['tipo'])); ?></p>
                    <?php if ($libro['genere']): ?>
                        <p><strong>Genere:</strong> <?php echo e($libro['genere']); ?></p>
                    <?php endif; ?>
                    <?php if ($libro['codice_dewey']): ?>
                        <p><strong>Codice Dewey:</strong> <?php echo e($libro['codice_dewey']); ?></p>
                    <?php endif; ?>
                    <?php if ($libro['numero_armadio']): ?>
                        <p><strong>Armadio:</strong> <?php echo e($libro['numero_armadio']); ?></p>
                    <?php endif; ?>
                    <?php if ($libro['numero_ripiano']): ?>
                        <p><strong>Ripiano:</strong> <?php echo e($libro['numero_ripiano']); ?></p>
                    <?php endif; ?>
                    <p><strong>Disponibilità:</strong> 
                        <span class="badge <?php echo $libro['copie_disponibili'] > 0 ? 'badge-success' : 'badge-danger'; ?>">
                            <?php echo $libro['copie_disponibili']; ?>/<?php echo $libro['numero_copie']; ?> disponibili
                        </span>
                    </p>
                </div>
            </div>
            
            <?php if ($libro['trama']): ?>
                <div style="margin-top: 20px; padding-top: 20px; border-top: 2px solid var(--light-bg);">
                    <strong>Descrizione:</strong>
                    <p><?php echo nl2br(e($libro['trama'])); ?></p>
                </div>
            <?php endif; ?>
            
            <!-- Azioni basate su ruolo -->
            <div style="margin-top: 30px; text-align: center;">
                <?php if ($azione === 'assegnazione' && $is_bibliotecario): ?>
                    <div class="alert alert-info">
                        <strong>📦 Libro Disponibile</strong><br>
                        Questo libro è libero e può essere assegnato a uno studente.
                    </div>
                    <a href="../admin/gestione_prestiti.php" class="btn btn-primary">
                        Vai a Gestione Prestiti
                    </a>
                    
                <?php elseif ($azione === 'restituzione' && $is_bibliotecario): ?>
                    <div class="alert alert-warning">
                        <strong>📚 Libro in Prestito</strong><br>
                        Attualmente in prestito a: <strong><?php echo e($prestito_attivo['nome'] . ' ' . $prestito_attivo['cognome']); ?></strong><br>
                        Scadenza: <?php echo formatData($prestito_attivo['data_scadenza']); ?>
                    </div>
                    <a href="../admin/gestione_prestiti.php" class="btn btn-success">
                        Gestisci Restituzione
                    </a>
                    
                <?php elseif ($azione === 'visualizza'): ?>
                    <?php if ($libro['copie_disponibili'] > 0 && !$user['in_blacklist']): ?>
                        <div class="alert alert-success">
                            <strong>✅ Libro Disponibile</strong><br>
                            Puoi prenotare questo libro!
                        </div>
                        <button onclick="prenotaLibro(<?php echo $libro['id']; ?>)" class="btn btn-primary">
                            📦 Prenota Ora
                        </button>
                    <?php elseif ($libro['copie_disponibili'] == 0): ?>
                        <div class="alert alert-danger">
                            <strong>❌ Libro Non Disponibile</strong><br>
                            Tutte le copie sono attualmente in prestito.
                        </div>
                        <button onclick="richiediNotifica(<?php echo $libro['id']; ?>)" class="btn btn-warning">
                            🔔 Avvisami quando Disponibile
                        </button>
                    <?php else: ?>
                        <div class="alert alert-danger">
                            <strong>⚠️ Non puoi prenotare</strong><br>
                            Sei in blacklist. Restituisci i materiali in prestito.
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            
            <div style="margin-top: 20px; text-align: center;">
                <a href="catalogo.php" class="btn btn-secondary">← Torna al Catalogo</a>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/main.js"></script>
</body>
</html>
