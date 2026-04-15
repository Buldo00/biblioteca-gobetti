<?php
/**
 * Funzioni principali - Biblioteca Gobetti
 */

require_once __DIR__ . '/../config/database.php';

// ============================================
// GESTIONE LIBRI
// ============================================

/**
 * Ottieni tutti i libri con filtri opzionali
 */
function getLibri($filtri = [], $pagina = 1, $perPagina = 20) {
    $db = getDB();
    $where = [];
    $params = [];
    
    if (!empty($filtri['titolo'])) {
        $where[] = "l.titolo LIKE ?";
        $params[] = '%' . $filtri['titolo'] . '%';
    }
    if (!empty($filtri['autore'])) {
        $where[] = "l.autore LIKE ?";
        $params[] = '%' . $filtri['autore'] . '%';
    }
    if (!empty($filtri['genere'])) {
        $where[] = "l.genere = ?";
        $params[] = $filtri['genere'];
    }
    if (!empty($filtri['lingua'])) {
        $where[] = "l.lingua = ?";
        $params[] = $filtri['lingua'];
    }
    if (!empty($filtri['anno'])) {
        $where[] = "l.anno_pubblicazione = ?";
        $params[] = $filtri['anno'];
    }
    if (!empty($filtri['tipologia'])) {
        $where[] = "l.tipologia = ?";
        $params[] = $filtri['tipologia'];
    }
    if (!empty($filtri['codice_dewey'])) {
        $where[] = "l.codice_dewey LIKE ?";
        $params[] = '%' . $filtri['codice_dewey'] . '%';
    }
    if (!empty($filtri['isbn'])) {
        $where[] = "l.isbn LIKE ?";
        $params[] = '%' . $filtri['isbn'] . '%';
    }
    if (isset($filtri['disponibile']) && $filtri['disponibile'] !== '') {
        if ($filtri['disponibile'] == '1') {
            $where[] = "(SELECT COUNT(*) FROM biblioteca_copie c WHERE c.id_libro = l.id_libro AND c.stato = 'disponibile') > 0";
        } else {
            $where[] = "(SELECT COUNT(*) FROM biblioteca_copie c WHERE c.id_libro = l.id_libro AND c.stato = 'disponibile') = 0";
        }
    }
    if (!empty($filtri['ricerca'])) {
        $where[] = "(l.titolo LIKE ? OR l.autore LIKE ? OR l.isbn LIKE ?)";
        $params[] = '%' . $filtri['ricerca'] . '%';
        $params[] = '%' . $filtri['ricerca'] . '%';
        $params[] = '%' . $filtri['ricerca'] . '%';
    }
    
    $whereStr = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    $offset = ($pagina - 1) * $perPagina;
    
    // Count totale
    $countSql = "SELECT COUNT(*) as totale FROM biblioteca_libri l $whereStr";
    $stmt = $db->prepare($countSql);
    $stmt->execute($params);
    $totale = $stmt->fetch()['totale'];
    
    // Query con copie disponibili
    $sql = "SELECT l.*, 
            (SELECT COUNT(*) FROM biblioteca_copie c WHERE c.id_libro = l.id_libro) as totale_copie,
            (SELECT COUNT(*) FROM biblioteca_copie c WHERE c.id_libro = l.id_libro AND c.stato = 'disponibile') as copie_disponibili
            FROM biblioteca_libri l 
            $whereStr
            ORDER BY l.titolo ASC
            LIMIT " . (int)$perPagina . " OFFSET " . (int)$offset;
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    
    return [
        'libri' => $stmt->fetchAll(),
        'totale' => $totale,
        'pagine' => ceil($totale / $perPagina),
        'pagina_corrente' => $pagina
    ];
}

/**
 * Ottieni un singolo libro per ID
 */
function getLibro($id) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT l.*, 
            (SELECT COUNT(*) FROM biblioteca_copie c WHERE c.id_libro = l.id_libro) as totale_copie,
            (SELECT COUNT(*) FROM biblioteca_copie c WHERE c.id_libro = l.id_libro AND c.stato = 'disponibile') as copie_disponibili
        FROM biblioteca_libri l WHERE l.id_libro = ?
    ");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

/**
 * Aggiungi un nuovo libro
 */
function addLibro($data) {
    $db = getDB();
    $stmt = $db->prepare("
        INSERT INTO biblioteca_libri (titolo, autore, anno_pubblicazione, casa_editrice, lingua, genere, codice_dewey, isbn, copertina, trama, tipologia, inserito_da)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $data['titolo'], $data['autore'], $data['anno_pubblicazione'] ?: null,
        $data['casa_editrice'] ?: null, $data['lingua'] ?: 'Italiano',
        $data['genere'] ?: null, $data['codice_dewey'] ?: null,
        $data['isbn'] ?: null, $data['copertina'] ?: null,
        $data['trama'] ?: null, $data['tipologia'] ?: 'libro',
        $_SESSION['biblioteca_user_id'] ?? null
    ]);
    return $db->lastInsertId();
}

/**
 * Modifica un libro
 */
function updateLibro($id, $data) {
    $db = getDB();
    $stmt = $db->prepare("
        UPDATE biblioteca_libri SET titolo=?, autore=?, anno_pubblicazione=?, casa_editrice=?, lingua=?, genere=?, codice_dewey=?, isbn=?, copertina=?, trama=?, tipologia=?
        WHERE id_libro=?
    ");
    return $stmt->execute([
        $data['titolo'], $data['autore'], $data['anno_pubblicazione'] ?: null,
        $data['casa_editrice'] ?: null, $data['lingua'] ?: 'Italiano',
        $data['genere'] ?: null, $data['codice_dewey'] ?: null,
        $data['isbn'] ?: null, $data['copertina'] ?: null,
        $data['trama'] ?: null, $data['tipologia'] ?: 'libro', $id
    ]);
}

/**
 * Elimina un libro (e le sue copie)
 */
function deleteLibro($id) {
    $db = getDB();
    // Check if there are active loans
    $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM biblioteca_prestiti WHERE id_libro = ? AND stato IN ('attivo','in_attesa','in_ritardo')");
    $stmt->execute([$id]);
    if ($stmt->fetch()['cnt'] > 0) {
        return false; // Cannot delete books with active loans
    }
    $db->prepare("DELETE FROM biblioteca_libri WHERE id_libro = ?")->execute([$id]);
    return true;
}

/**
 * Ottieni generi distinti
 */
function getGeneri() {
    $db = getDB();
    return $db->query("SELECT DISTINCT genere FROM biblioteca_libri WHERE genere IS NOT NULL AND genere != '' ORDER BY genere")->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Ottieni lingue distinte
 */
function getLingue() {
    $db = getDB();
    return $db->query("SELECT DISTINCT lingua FROM biblioteca_libri WHERE lingua IS NOT NULL AND lingua != '' ORDER BY lingua")->fetchAll(PDO::FETCH_COLUMN);
}

// ============================================
// GESTIONE COPIE
// ============================================

/**
 * Ottieni copie di un libro
 */
function getCopieLibro($idLibro) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM biblioteca_copie WHERE id_libro = ? ORDER BY numero_copia");
    $stmt->execute([$idLibro]);
    return $stmt->fetchAll();
}

/**
 * Ottieni una singola copia
 */
function getCopia($idCopia) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT c.*, l.titolo, l.autore, l.codice_dewey, l.isbn
        FROM biblioteca_copie c
        JOIN biblioteca_libri l ON l.id_libro = c.id_libro
        WHERE c.id_copia = ?
    ");
    $stmt->execute([$idCopia]);
    return $stmt->fetch();
}

/**
 * Ottieni copia per QR code
 */
function getCopiaByQR($qrCode) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT c.*, l.titolo, l.autore, l.codice_dewey, l.isbn
        FROM biblioteca_copie c
        JOIN biblioteca_libri l ON l.id_libro = c.id_libro
        WHERE c.qr_code_univoco = ?
    ");
    $stmt->execute([$qrCode]);
    return $stmt->fetch();
}

/**
 * Aggiungi copie a un libro
 */
function addCopie($idLibro, $numCopie, $armadio, $ripiano, $aula) {
    $db = getDB();
    // Get current max copy number
    $stmt = $db->prepare("SELECT COALESCE(MAX(numero_copia), 0) as max_copia FROM biblioteca_copie WHERE id_libro = ?");
    $stmt->execute([$idLibro]);
    $maxCopia = $stmt->fetch()['max_copia'];
    
    $copieInserite = [];
    for ($i = 1; $i <= $numCopie; $i++) {
        $numeroCopia = $maxCopia + $i;
        $qrCode = 'BG-LIB' . str_pad($idLibro, 3, '0', STR_PAD_LEFT) . '-C' . str_pad($numeroCopia, 3, '0', STR_PAD_LEFT);
        
        $stmt = $db->prepare("
            INSERT INTO biblioteca_copie (id_libro, numero_copia, qr_code_univoco, numero_armadio, numero_ripiano, numero_aula, stato)
            VALUES (?, ?, ?, ?, ?, ?, 'disponibile')
        ");
        $stmt->execute([$idLibro, $numeroCopia, $qrCode, $armadio, $ripiano, $aula]);
        $copieInserite[] = $db->lastInsertId();
    }
    return $copieInserite;
}

/**
 * Aggiorna stato copia
 */
function updateStatoCopia($idCopia, $stato, $noteDanno = null) {
    $db = getDB();
    $stmt = $db->prepare("UPDATE biblioteca_copie SET stato = ?, note_danno = ? WHERE id_copia = ?");
    return $stmt->execute([$stato, $noteDanno, $idCopia]);
}

/**
 * Aggiorna posizione copia
 */
function updatePosizioneCopia($idCopia, $armadio, $ripiano, $aula) {
    $db = getDB();
    $stmt = $db->prepare("UPDATE biblioteca_copie SET numero_armadio = ?, numero_ripiano = ?, numero_aula = ? WHERE id_copia = ?");
    return $stmt->execute([$armadio, $ripiano, $aula, $idCopia]);
}

// ============================================
// GESTIONE PRENOTAZIONI
// ============================================

/**
 * Conta prestiti attivi di un utente
 */
function contaPrestitiAttivi($userId) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT COUNT(*) as cnt FROM biblioteca_prestiti 
        WHERE id_utente = ? AND stato IN ('attivo','in_attesa','in_ritardo')
    ");
    $stmt->execute([$userId]);
    return $stmt->fetch()['cnt'];
}

/**
 * Verifica se l'utente può prenotare (controlla limite prestiti)
 */
function puoPrenotare($userId) {
    $livello = getUserMaxLevel($userId);
    
    // Admin e Bibliotecari: prestiti illimitati
    if ($livello >= LIVELLO_BIBLIOTECARIO) {
        return true;
    }
    
    // Verifica blacklist
    if (isInBlacklist($userId)) {
        return false;
    }
    
    // Studenti e docenti: max 3 (configurabile)
    $maxPrestiti = $livello >= LIVELLO_DOCENTE 
        ? (int)getSetting('max_prestiti_docente', 3) 
        : (int)getSetting('max_prestiti_studente', 3);
    
    return contaPrestitiAttivi($userId) < $maxPrestiti;
}

/**
 * Ottieni prestiti rimanenti
 */
function prestitiRimanenti($userId) {
    $livello = getUserMaxLevel($userId);
    if ($livello >= LIVELLO_BIBLIOTECARIO) return -1; // illimitati
    
    $maxPrestiti = $livello >= LIVELLO_DOCENTE 
        ? (int)getSetting('max_prestiti_docente', 3) 
        : (int)getSetting('max_prestiti_studente', 3);
    
    return max(0, $maxPrestiti - contaPrestitiAttivi($userId));
}

/**
 * Crea una prenotazione
 */
function creaPrenotazione($userId, $idLibro, $tipo = 'personale', $idClasse = null) {
    $db = getDB();
    
    // Trova una copia disponibile
    $stmt = $db->prepare("SELECT id_copia FROM biblioteca_copie WHERE id_libro = ? AND stato = 'disponibile' LIMIT 1");
    $stmt->execute([$idLibro]);
    $copia = $stmt->fetch();
    
    if (!$copia) return false;
    
    $giorniRitiro = (int)getSetting('giorni_ritiro', 3);
    $scadenza = date('Y-m-d H:i:s', strtotime("+$giorniRitiro days"));
    
    // Crea prenotazione
    $stmt = $db->prepare("
        INSERT INTO biblioteca_prenotazioni (id_utente, id_libro, id_copia, tipo_prenotazione, id_classe, data_scadenza, stato)
        VALUES (?, ?, ?, ?, ?, ?, 'attiva')
    ");
    $stmt->execute([$userId, $idLibro, $copia['id_copia'], $tipo, $idClasse, $scadenza]);
    $prenotazioneId = $db->lastInsertId();
    
    // Aggiorna stato copia
    updateStatoCopia($copia['id_copia'], 'prenotato');
    
    // Crea anche il prestito in stato "in_attesa"
    $giorniPrestito = (int)getSetting('giorni_prestito', 30);
    $scadenzaPrestito = date('Y-m-d H:i:s', strtotime("+$giorniPrestito days"));
    
    $stmt = $db->prepare("
        INSERT INTO biblioteca_prestiti (id_prenotazione, id_utente, id_copia, id_libro, tipo_prestito, data_scadenza, stato)
        VALUES (?, ?, ?, ?, ?, ?, 'in_attesa')
    ");
    $stmt->execute([$prenotazioneId, $userId, $copia['id_copia'], $idLibro, $tipo, $scadenzaPrestito]);
    
    return $prenotazioneId;
}

/**
 * Annulla prenotazione
 */
function annullaPrenotazione($idPrenotazione, $userId) {
    $db = getDB();
    
    $stmt = $db->prepare("SELECT * FROM biblioteca_prenotazioni WHERE id_prenotazione = ?");
    $stmt->execute([$idPrenotazione]);
    $prenotazione = $stmt->fetch();
    
    if (!$prenotazione) return false;
    
    // Solo il proprietario o admin/bibliotecari possono annullare
    $livello = $_SESSION['biblioteca_user_livello'] ?? 0;
    if ($prenotazione['id_utente'] != $userId && $livello < LIVELLO_BIBLIOTECARIO) {
        return false;
    }
    
    // Aggiorna prenotazione
    $db->prepare("UPDATE biblioteca_prenotazioni SET stato = 'annullata' WHERE id_prenotazione = ?")->execute([$idPrenotazione]);
    
    // Libera la copia
    if ($prenotazione['id_copia']) {
        updateStatoCopia($prenotazione['id_copia'], 'disponibile');
    }
    
    // Rimuovi prestito in attesa associato
    $db->prepare("UPDATE biblioteca_prestiti SET stato = 'restituito', data_restituzione = NOW() WHERE id_prenotazione = ? AND stato = 'in_attesa'")->execute([$idPrenotazione]);
    
    return true;
}

/**
 * Ottieni prenotazioni utente
 */
function getPrenotazioniUtente($userId) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT p.*, l.titolo, l.autore, c.numero_copia
        FROM biblioteca_prenotazioni p
        JOIN biblioteca_libri l ON l.id_libro = p.id_libro
        LEFT JOIN biblioteca_copie c ON c.id_copia = p.id_copia
        WHERE p.id_utente = ?
        ORDER BY p.data_prenotazione DESC
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

// ============================================
// GESTIONE PRESTITI
// ============================================

/**
 * Ottieni prestiti di un utente
 */
function getPrestitiUtente($userId) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT pr.*, l.titolo, l.autore, c.numero_copia, c.qr_code_univoco
        FROM biblioteca_prestiti pr
        JOIN biblioteca_libri l ON l.id_libro = pr.id_libro
        JOIN biblioteca_copie c ON c.id_copia = pr.id_copia
        WHERE pr.id_utente = ?
        ORDER BY pr.data_prestito DESC
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

/**
 * Ottieni tutti i prestiti (per admin/bibliotecari)
 */
function getTuttiPrestiti($filtroStato = null) {
    $db = getDB();
    $where = '';
    $params = [];
    if ($filtroStato) {
        $where = 'WHERE pr.stato = ?';
        $params[] = $filtroStato;
    }
    
    $stmt = $db->prepare("
        SELECT pr.*, l.titolo, l.autore, c.numero_copia, c.qr_code_univoco,
               COALESCE(p.nomeProfilo, s.nomeStu) as nome_utente,
               COALESCE(p.cognomeProfilo, s.cognomeStu) as cognome_utente
        FROM biblioteca_prestiti pr
        JOIN biblioteca_libri l ON l.id_libro = pr.id_libro
        JOIN biblioteca_copie c ON c.id_copia = pr.id_copia
        LEFT JOIN profili p ON p.idUtente = pr.id_utente
        LEFT JOIN studenti s ON s.IDUtente = pr.id_utente
        $where
        ORDER BY pr.data_prestito DESC
    ");
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Conferma ritiro prestito (doppia conferma)
 */
function confermaRitiro($idPrestito, $tipo, $userId) {
    $db = getDB();
    
    if ($tipo === 'bibliotecario') {
        $db->prepare("UPDATE biblioteca_prestiti SET check_bibliotecario = 1, bibliotecario_id = ? WHERE id_prestito = ?")->execute([$userId, $idPrestito]);
    } else {
        $db->prepare("UPDATE biblioteca_prestiti SET check_utente = 1 WHERE id_prestito = ?")->execute([$idPrestito]);
    }
    
    // Verifica se entrambe le conferme sono state date
    $stmt = $db->prepare("SELECT * FROM biblioteca_prestiti WHERE id_prestito = ?");
    $stmt->execute([$idPrestito]);
    $prestito = $stmt->fetch();
    
    if ($prestito['check_bibliotecario'] && $prestito['check_utente']) {
        // Attiva il prestito
        $db->prepare("UPDATE biblioteca_prestiti SET stato = 'attivo', data_prestito = NOW() WHERE id_prestito = ?")->execute([$idPrestito]);
        // Aggiorna stato copia
        updateStatoCopia($prestito['id_copia'], 'in_prestito');
        // Aggiorna prenotazione se esiste
        if ($prestito['id_prenotazione']) {
            $db->prepare("UPDATE biblioteca_prenotazioni SET stato = 'confermata' WHERE id_prenotazione = ?")->execute([$prestito['id_prenotazione']]);
        }
    }
    
    return true;
}

/**
 * Restituisci un libro
 */
function restituisciLibro($idPrestito) {
    $db = getDB();
    
    $stmt = $db->prepare("SELECT * FROM biblioteca_prestiti WHERE id_prestito = ?");
    $stmt->execute([$idPrestito]);
    $prestito = $stmt->fetch();
    
    if (!$prestito) return false;
    
    // Segna come restituito
    $db->prepare("UPDATE biblioteca_prestiti SET stato = 'restituito', data_restituzione = NOW() WHERE id_prestito = ?")->execute([$idPrestito]);
    
    // Libera la copia
    updateStatoCopia($prestito['id_copia'], 'disponibile');
    
    // Rimuovi dalla blacklist se era in blacklist per questo libro
    $db->prepare("UPDATE biblioteca_blacklist SET attiva = 0, data_rimozione = NOW() WHERE id_utente = ? AND attiva = 1")->execute([$prestito['id_utente']]);
    
    // Notifica chi aspettava questo libro
    notificaDisponibilita($prestito['id_libro']);
    
    return true;
}

/**
 * Crea prestito diretto (senza prenotazione, per bibliotecario)
 */
function creaPrestito($userId, $idCopia, $tipo = 'personale') {
    $db = getDB();
    
    $copia = getCopia($idCopia);
    if (!$copia || $copia['stato'] !== 'disponibile') return false;
    
    $giorniPrestito = (int)getSetting('giorni_prestito', 30);
    $scadenza = date('Y-m-d H:i:s', strtotime("+$giorniPrestito days"));
    
    $stmt = $db->prepare("
        INSERT INTO biblioteca_prestiti (id_utente, id_copia, id_libro, tipo_prestito, data_scadenza, stato, check_bibliotecario, check_utente)
        VALUES (?, ?, ?, ?, ?, 'in_attesa', 0, 0)
    ");
    $stmt->execute([$userId, $idCopia, $copia['id_libro'], $tipo, $scadenza]);
    
    // Aggiorna stato copia
    updateStatoCopia($idCopia, 'prenotato');
    
    return $db->lastInsertId();
}

// ============================================
// GESTIONE BLACKLIST
// ============================================

/**
 * Aggiungi utente alla blacklist
 */
function addToBlacklist($userId, $motivo, $inseritoDa) {
    // Non si possono blacklistare admin e bibliotecari
    if (!canBeBlacklisted($userId)) return false;
    
    $db = getDB();
    $stmt = $db->prepare("
        INSERT INTO biblioteca_blacklist (id_utente, motivo, inserito_da)
        VALUES (?, ?, ?)
    ");
    return $stmt->execute([$userId, $motivo, $inseritoDa]);
}

/**
 * Rimuovi dalla blacklist
 */
function removeFromBlacklist($idBlacklist) {
    $db = getDB();
    return $db->prepare("UPDATE biblioteca_blacklist SET attiva = 0, data_rimozione = NOW() WHERE id_blacklist = ?")->execute([$idBlacklist]);
}

/**
 * Ottieni utenti in blacklist
 */
function getBlacklist() {
    $db = getDB();
    return $db->query("
        SELECT bl.*, 
               COALESCE(p.nomeProfilo, s.nomeStu) as nome,
               COALESCE(p.cognomeProfilo, s.cognomeStu) as cognome
        FROM biblioteca_blacklist bl
        LEFT JOIN profili p ON p.idUtente = bl.id_utente
        LEFT JOIN studenti s ON s.IDUtente = bl.id_utente
        WHERE bl.attiva = 1
        ORDER BY bl.data_inserimento DESC
    ")->fetchAll();
}

// ============================================
// IMPOSTAZIONI
// ============================================

/**
 * Ottieni un'impostazione
 */
function getSetting($chiave, $default = null) {
    $db = getDB();
    $stmt = $db->prepare("SELECT valore FROM biblioteca_settings WHERE chiave = ?");
    $stmt->execute([$chiave]);
    $result = $stmt->fetch();
    return $result ? $result['valore'] : $default;
}

/**
 * Salva un'impostazione
 */
function setSetting($chiave, $valore, $userId = null) {
    $db = getDB();
    $stmt = $db->prepare("
        UPDATE biblioteca_settings SET valore = ?, modificato_da = ?, data_modifica = NOW()
        WHERE chiave = ?
    ");
    return $stmt->execute([$valore, $userId, $chiave]);
}

/**
 * Ottieni tutte le impostazioni
 */
function getAllSettings() {
    $db = getDB();
    return $db->query("SELECT * FROM biblioteca_settings ORDER BY chiave")->fetchAll();
}

// ============================================
// NOTIFICHE DISPONIBILITA
// ============================================

/**
 * Richiedi notifica disponibilità
 */
function richiediNotifica($userId, $idLibro) {
    $db = getDB();
    // Check if already requested
    $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM biblioteca_notifiche_attesa WHERE id_utente = ? AND id_libro = ? AND notificato = 0");
    $stmt->execute([$userId, $idLibro]);
    if ($stmt->fetch()['cnt'] > 0) return false;
    
    $stmt = $db->prepare("INSERT INTO biblioteca_notifiche_attesa (id_utente, id_libro) VALUES (?, ?)");
    return $stmt->execute([$userId, $idLibro]);
}

/**
 * Notifica disponibilità libro
 */
function notificaDisponibilita($idLibro) {
    $db = getDB();
    $db->prepare("UPDATE biblioteca_notifiche_attesa SET notificato = 1, data_notifica = NOW() WHERE id_libro = ? AND notificato = 0")->execute([$idLibro]);
}

// ============================================
// STATISTICHE
// ============================================

/**
 * Statistiche generali della biblioteca
 */
function getStatistiche() {
    $db = getDB();
    
    $stats = [];
    
    // Totale libri
    $stats['totale_libri'] = $db->query("SELECT COUNT(*) as cnt FROM biblioteca_libri")->fetch()['cnt'];
    
    // Totale copie
    $stats['totale_copie'] = $db->query("SELECT COUNT(*) as cnt FROM biblioteca_copie")->fetch()['cnt'];
    
    // Copie disponibili
    $stats['copie_disponibili'] = $db->query("SELECT COUNT(*) as cnt FROM biblioteca_copie WHERE stato = 'disponibile'")->fetch()['cnt'];
    
    // Prestiti attivi
    $stats['prestiti_attivi'] = $db->query("SELECT COUNT(*) as cnt FROM biblioteca_prestiti WHERE stato IN ('attivo','in_attesa')")->fetch()['cnt'];
    
    // Prestiti totali
    $stats['prestiti_totali'] = $db->query("SELECT COUNT(*) as cnt FROM biblioteca_prestiti")->fetch()['cnt'];
    
    // Utenti in blacklist
    $stats['utenti_blacklist'] = $db->query("SELECT COUNT(*) as cnt FROM biblioteca_blacklist WHERE attiva = 1")->fetch()['cnt'];
    
    // Prenotazioni attive
    $stats['prenotazioni_attive'] = $db->query("SELECT COUNT(*) as cnt FROM biblioteca_prenotazioni WHERE stato = 'attiva'")->fetch()['cnt'];
    
    // Libri più prenotati
    $stats['libri_piu_prenotati'] = $db->query("
        SELECT l.titolo, l.autore, COUNT(pr.id_prestito) as num_prestiti
        FROM biblioteca_prestiti pr
        JOIN biblioteca_libri l ON l.id_libro = pr.id_libro
        GROUP BY pr.id_libro, l.titolo, l.autore
        ORDER BY num_prestiti DESC
        LIMIT 10
    ")->fetchAll();
    
    // Studenti con più prestiti
    $stats['studenti_attivi'] = $db->query("
        SELECT COALESCE(p.nomeProfilo, s.nomeStu) as nome,
               COALESCE(p.cognomeProfilo, s.cognomeStu) as cognome,
               COUNT(pr.id_prestito) as num_prestiti
        FROM biblioteca_prestiti pr
        LEFT JOIN profili p ON p.idUtente = pr.id_utente
        LEFT JOIN studenti s ON s.IDUtente = pr.id_utente
        GROUP BY pr.id_utente, nome, cognome
        ORDER BY num_prestiti DESC
        LIMIT 10
    ")->fetchAll();
    
    // Tasso di restituzione
    $totRestituti = $db->query("SELECT COUNT(*) as cnt FROM biblioteca_prestiti WHERE stato = 'restituito'")->fetch()['cnt'];
    $totPrestiti = $db->query("SELECT COUNT(*) as cnt FROM biblioteca_prestiti WHERE stato != 'in_attesa'")->fetch()['cnt'];
    $stats['tasso_restituzione'] = $totPrestiti > 0 ? round(($totRestituti / $totPrestiti) * 100, 1) : 0;
    
    // Prestiti per mese (ultimi 6 mesi)
    $stats['prestiti_per_mese'] = $db->query("
        SELECT DATE_FORMAT(data_prestito, '%Y-%m') as mese, COUNT(*) as num
        FROM biblioteca_prestiti
        WHERE data_prestito >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY mese
        ORDER BY mese
    ")->fetchAll();
    
    return $stats;
}

// ============================================
// GESTIONE CLASSI (integrazione con tabella esistente)
// ============================================

/**
 * Ottieni tutte le classi
 */
function getClassi() {
    $db = getDB();
    return $db->query("SELECT * FROM classi ORDER BY anno, sezione")->fetchAll();
}

/**
 * Ottieni studenti di una classe
 */
function getStudentiClasse($idClasse) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT s.IDStudente, s.IDUtente, s.nomeStu as nome, s.cognomeStu as cognome, s.IDClasse,
               c.anno, c.sezione
        FROM studenti s
        JOIN classi c ON c.IDClasse = s.IDClasse
        WHERE s.IDClasse = ? AND s.statoIscrizione IS NULL
        ORDER BY s.cognomeStu, s.nomeStu
    ");
    $stmt->execute([$idClasse]);
    return $stmt->fetchAll();
}

/**
 * Ottieni tutti gli utenti (per admin)
 */
function getTuttiUtenti($filtro = null) {
    $db = getDB();
    $where = '';
    $params = [];
    if ($filtro) {
        $where = "WHERE (COALESCE(p.nomeProfilo, s.nomeStu) LIKE ? OR COALESCE(p.cognomeProfilo, s.cognomeStu) LIKE ? OR u.emailUtente LIKE ?)";
        $params = ['%'.$filtro.'%', '%'.$filtro.'%', '%'.$filtro.'%'];
    }
    
    $stmt = $db->prepare("
        SELECT u.IDUtente, u.emailUtente, u.statoUtente,
               COALESCE(p.nomeProfilo, s.nomeStu) as nome,
               COALESCE(p.cognomeProfilo, s.cognomeStu) as cognome,
               s.IDClasse, cl.anno as classe_anno, cl.sezione as classe_sezione,
               MAX(tl.livelloAccount) as livello
        FROM utenti u
        LEFT JOIN profili p ON p.idUtente = u.IDUtente
        LEFT JOIN studenti s ON s.IDUtente = u.IDUtente
        LEFT JOIN classi cl ON cl.IDClasse = s.IDClasse
        LEFT JOIN utenti_tipolivelli utl ON utl.idUtente = u.IDUtente
        LEFT JOIN tipolivelli tl ON tl.IDTipoAccount = utl.idLivello
        $where
        GROUP BY u.IDUtente
        HAVING nome IS NOT NULL
        ORDER BY cognome, nome
        LIMIT 200
    ");
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// ============================================
// LOG OPERAZIONI
// ============================================

/**
 * Registra un'operazione nel log
 */
function logOperazione($userId, $azione, $tabella = null, $recordId = null, $dettagli = null) {
    $db = getDB();
    $stmt = $db->prepare("
        INSERT INTO biblioteca_log_operazioni (id_utente, azione, tabella, record_id, dettagli, ip_address)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$userId, $azione, $tabella, $recordId, $dettagli, $_SERVER['REMOTE_ADDR'] ?? '']);
}
