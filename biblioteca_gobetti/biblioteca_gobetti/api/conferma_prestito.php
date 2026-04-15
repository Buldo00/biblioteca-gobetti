<?php
/**
 * API Conferma Prestito (Doppio Check) - Biblioteca Gobetti
 */

require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isLogged()) {
    echo json_encode(['success' => false, 'message' => 'Non autenticato']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Metodo non consentito']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$prestito_id = $data['prestito_id'] ?? null;
$tipo = $data['tipo'] ?? null; // 'ritiro' o 'restituzione'

if (!$prestito_id || !$tipo) {
    echo json_encode(['success' => false, 'message' => 'Parametri mancanti']);
    exit;
}

if (!in_array($tipo, ['ritiro', 'restituzione'])) {
    echo json_encode(['success' => false, 'message' => 'Tipo non valido']);
    exit;
}

$user_id = $_SESSION['user_id'];
$user = getUserById($user_id);
$db = getDB();

try {
    // Ottieni prestito
    $stmt = $db->prepare("SELECT * FROM prestiti WHERE id = ?");
    $stmt->execute([$prestito_id]);
    $prestito = $stmt->fetch();
    
    if (!$prestito) {
        throw new Exception('Prestito non trovato');
    }
    
    // Determina quale check fare
    $is_bibliotecario = $user['livello'] >= LIVELLO_BIBLIOTECARIO;
    $is_proprietario = $prestito['utente_id'] == $user_id;
    
    if ($tipo === 'ritiro') {
        if ($is_bibliotecario) {
            // Check bibliotecario ritiro
            $stmt = $db->prepare("
                UPDATE prestiti 
                SET check_ritiro_bibliotecario = 1, bibliotecario_ritiro_id = ?
                WHERE id = ?
            ");
            $stmt->execute([$user_id, $prestito_id]);
            
            logActivity($user_id, 'check_ritiro_bibliotecario', 'prestiti', $prestito_id, 'Check bibliotecario effettuato');
            
        } elseif ($is_proprietario) {
            // Check utente ritiro
            $stmt = $db->prepare("UPDATE prestiti SET check_ritiro_utente = 1 WHERE id = ?");
            $stmt->execute([$prestito_id]);
            
            logActivity($user_id, 'check_ritiro_utente', 'prestiti', $prestito_id, 'Check utente effettuato');
        } else {
            throw new Exception('Non autorizzato');
        }
        
    } elseif ($tipo === 'restituzione') {
        if ($is_bibliotecario) {
            // Check bibliotecario restituzione
            $stmt = $db->prepare("
                UPDATE prestiti 
                SET check_restituzione_bibliotecario = 1, bibliotecario_restituzione_id = ?
                WHERE id = ?
            ");
            $stmt->execute([$user_id, $prestito_id]);
            
            logActivity($user_id, 'check_restituzione_bibliotecario', 'prestiti', $prestito_id, 'Check restituzione bibliotecario');
            
        } elseif ($is_proprietario) {
            // Check utente restituzione
            $stmt = $db->prepare("UPDATE prestiti SET check_restituzione_utente = 1 WHERE id = ?");
            $stmt->execute([$prestito_id]);
            
            logActivity($user_id, 'check_restituzione_utente', 'prestiti', $prestito_id, 'Check restituzione utente');
        } else {
            throw new Exception('Non autorizzato');
        }
    }
    
    // Verifica se entrambi i check sono stati fatti
    $stmt = $db->prepare("SELECT * FROM prestiti WHERE id = ?");
    $stmt->execute([$prestito_id]);
    $prestito_updated = $stmt->fetch();
    
    $message = 'Check registrato';
    
    if ($tipo === 'ritiro' && 
        $prestito_updated['check_ritiro_bibliotecario'] && 
        $prestito_updated['check_ritiro_utente']) {
        $message = 'Ritiro completato! Entrambi i check effettuati.';
        
        // Aggiorna prenotazione se esiste
        if ($prestito_updated['prenotazione_id']) {
            $stmt = $db->prepare("UPDATE prenotazioni SET stato = 'ritirata' WHERE id = ?");
            $stmt->execute([$prestito_updated['prenotazione_id']]);
        }
    }
    
    if ($tipo === 'restituzione' && 
        $prestito_updated['check_restituzione_bibliotecario'] && 
        $prestito_updated['check_restituzione_utente']) {
        
        // Completa restituzione
        $stmt = $db->prepare("
            UPDATE prestiti 
            SET stato = 'restituito', data_restituzione = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$prestito_id]);
        
        // Rimuovi da blacklist se era in blacklist per questo prestito
        $result = rimuoviBlacklist($prestito_updated['utente_id'], $user_id);
        
        $message = 'Restituzione completata! ' . ($result['ok'] ? 'Utente rimosso dalla blacklist.' : '');
        
        // Notifica disponibilità
        if ($prestito_updated['libro_id']) {
            notificaDisponibilita($prestito_updated['libro_id']);
        }
    }
    
    echo json_encode(['success' => true, 'message' => $message]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
