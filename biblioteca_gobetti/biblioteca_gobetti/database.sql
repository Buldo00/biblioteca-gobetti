-- Database Biblioteca Gobetti
-- Sistema completo di gestione biblioteca scolastica

CREATE DATABASE IF NOT EXISTS biblioteca_gobetti CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE biblioteca_gobetti;

-- Tabella Utenti
CREATE TABLE utenti (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    nome VARCHAR(100) NOT NULL,
    cognome VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    livello INT NOT NULL DEFAULT 100,
    classe_id INT NULL,
    in_blacklist BOOLEAN DEFAULT FALSE,
    motivo_blacklist TEXT NULL,
    data_inizio_blacklist DATETIME NULL,
    data_creazione TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultimo_accesso TIMESTAMP NULL,
    attivo BOOLEAN DEFAULT TRUE,
    INDEX idx_livello (livello),
    INDEX idx_blacklist (in_blacklist),
    INDEX idx_classe (classe_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabella Classi
CREATE TABLE classi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) NOT NULL,
    sezione VARCHAR(10) DEFAULT NULL,
    anno_scolastico VARCHAR(20) NOT NULL,
    numero_studenti INT DEFAULT 0,
    attiva BOOLEAN DEFAULT TRUE,
    UNIQUE KEY unique_classe (nome, sezione, anno_scolastico)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabella Libri
CREATE TABLE libri (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo ENUM('libro', 'rivista', 'dizionario', 'manuale') DEFAULT 'libro',
    titolo VARCHAR(255) NOT NULL,
    autore VARCHAR(255) NULL,
    anno_uscita INT NULL,
    casa_editrice VARCHAR(150) NULL,
    lingua VARCHAR(50) DEFAULT 'Italiano',
    genere VARCHAR(100) NULL,
    codice_dewey VARCHAR(50) NULL,
    collocazione VARCHAR(100) NULL,
    numero_armadio VARCHAR(20) NULL,
    numero_ripiano VARCHAR(20) NULL,
    numero_aula VARCHAR(20) NULL,
    isbn VARCHAR(20) NULL,
    immagine_copertina VARCHAR(255) NULL,
    trama TEXT NULL,
    numero_copie INT DEFAULT 1,
    copie_disponibili INT DEFAULT 1,
    stato ENUM('disponibile', 'non_disponibile', 'manutenzione') DEFAULT 'disponibile',
    prenotabile BOOLEAN DEFAULT TRUE,
    data_inserimento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultima_modifica TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_tipo (tipo),
    INDEX idx_stato (stato),
    INDEX idx_disponibili (copie_disponibili),
    FULLTEXT idx_ricerca (titolo, autore, genere)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabella Dispositivi Elettronici
CREATE TABLE dispositivi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo ENUM('tablet', 'laptop', 'lettore_ebook', 'calcolatrice', 'altro') NOT NULL,
    marca VARCHAR(100) NOT NULL,
    modello VARCHAR(150) NOT NULL,
    numero_seriale VARCHAR(100) UNIQUE NOT NULL,
    codice_inventario VARCHAR(50) UNIQUE NOT NULL,
    stato ENUM('disponibile', 'in_prestito', 'manutenzione', 'dismesso') DEFAULT 'disponibile',
    note TEXT NULL,
    data_acquisto DATE NULL,
    data_inserimento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultima_modifica TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_stato (stato),
    INDEX idx_tipo (tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabella Prenotazioni
CREATE TABLE prenotazioni (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utente_id INT NOT NULL,
    libro_id INT NULL,
    dispositivo_id INT NULL,
    tipo_prenotazione ENUM('personale', 'classe') DEFAULT 'personale',
    classe_id INT NULL,
    studenti_selezionati TEXT NULL, -- JSON array degli ID studenti per prenotazioni di classe
    data_prenotazione TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_scadenza_ritiro DATETIME NOT NULL, -- 3 giorni dalla prenotazione
    stato ENUM('attiva', 'ritirata', 'scaduta', 'annullata') DEFAULT 'attiva',
    note TEXT NULL,
    FOREIGN KEY (utente_id) REFERENCES utenti(id) ON DELETE CASCADE,
    FOREIGN KEY (libro_id) REFERENCES libri(id) ON DELETE CASCADE,
    FOREIGN KEY (dispositivo_id) REFERENCES dispositivi(id) ON DELETE CASCADE,
    FOREIGN KEY (classe_id) REFERENCES classi(id) ON DELETE SET NULL,
    INDEX idx_utente (utente_id),
    INDEX idx_libro (libro_id),
    INDEX idx_dispositivo (dispositivo_id),
    INDEX idx_stato (stato),
    INDEX idx_scadenza (data_scadenza_ritiro)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabella Prestiti
CREATE TABLE prestiti (
    id INT AUTO_INCREMENT PRIMARY KEY,
    prenotazione_id INT NULL,
    utente_id INT NOT NULL,
    libro_id INT NULL,
    dispositivo_id INT NULL,
    tipo_prestito ENUM('personale', 'classe') DEFAULT 'personale',
    data_ritiro DATETIME NOT NULL,
    data_scadenza DATETIME NOT NULL,
    data_restituzione DATETIME NULL,
    giorni_ritardo INT DEFAULT 0,
    stato ENUM('attivo', 'restituito', 'in_ritardo', 'smarrito') DEFAULT 'attivo',
    check_ritiro_bibliotecario BOOLEAN DEFAULT FALSE,
    check_ritiro_utente BOOLEAN DEFAULT FALSE,
    bibliotecario_ritiro_id INT NULL,
    check_restituzione_bibliotecario BOOLEAN DEFAULT FALSE,
    check_restituzione_utente BOOLEAN DEFAULT FALSE,
    bibliotecario_restituzione_id INT NULL,
    note TEXT NULL,
    FOREIGN KEY (prenotazione_id) REFERENCES prenotazioni(id) ON DELETE SET NULL,
    FOREIGN KEY (utente_id) REFERENCES utenti(id) ON DELETE CASCADE,
    FOREIGN KEY (libro_id) REFERENCES libri(id) ON DELETE CASCADE,
    FOREIGN KEY (dispositivo_id) REFERENCES dispositivi(id) ON DELETE CASCADE,
    FOREIGN KEY (bibliotecario_ritiro_id) REFERENCES utenti(id) ON DELETE SET NULL,
    FOREIGN KEY (bibliotecario_restituzione_id) REFERENCES utenti(id) ON DELETE SET NULL,
    INDEX idx_utente (utente_id),
    INDEX idx_libro (libro_id),
    INDEX idx_dispositivo (dispositivo_id),
    INDEX idx_stato (stato),
    INDEX idx_scadenza (data_scadenza)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabella Blacklist Log
CREATE TABLE blacklist_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utente_id INT NOT NULL,
    motivo ENUM('ritardo', 'mancato_ritiro', 'danno', 'manuale') NOT NULL,
    dettagli TEXT NULL,
    prestito_id INT NULL,
    prenotazione_id INT NULL,
    data_inizio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_fine TIMESTAMP NULL,
    attiva BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (utente_id) REFERENCES utenti(id) ON DELETE CASCADE,
    FOREIGN KEY (prestito_id) REFERENCES prestiti(id) ON DELETE SET NULL,
    FOREIGN KEY (prenotazione_id) REFERENCES prenotazioni(id) ON DELETE SET NULL,
    INDEX idx_utente (utente_id),
    INDEX idx_attiva (attiva)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabella Notifiche "Avvisami"
CREATE TABLE notifiche_disponibilita (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utente_id INT NOT NULL,
    libro_id INT NULL,
    dispositivo_id INT NULL,
    data_richiesta TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notificato BOOLEAN DEFAULT FALSE,
    data_notifica TIMESTAMP NULL,
    FOREIGN KEY (utente_id) REFERENCES utenti(id) ON DELETE CASCADE,
    FOREIGN KEY (libro_id) REFERENCES libri(id) ON DELETE CASCADE,
    FOREIGN KEY (dispositivo_id) REFERENCES dispositivi(id) ON DELETE CASCADE,
    INDEX idx_utente (utente_id),
    INDEX idx_libro (libro_id),
    INDEX idx_notificato (notificato)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabella Impostazioni
CREATE TABLE impostazioni (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chiave VARCHAR(100) UNIQUE NOT NULL,
    valore TEXT NOT NULL,
    descrizione TEXT NULL,
    tipo ENUM('int', 'string', 'boolean', 'json', 'time') DEFAULT 'string',
    ultima_modifica TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    modificato_da INT NULL,
    FOREIGN KEY (modificato_da) REFERENCES utenti(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabella Log Attività
CREATE TABLE log_attivita (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utente_id INT NULL,
    azione VARCHAR(100) NOT NULL,
    tabella VARCHAR(50) NULL,
    record_id INT NULL,
    dettagli TEXT NULL,
    ip_address VARCHAR(45) NULL,
    data_ora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utente_id) REFERENCES utenti(id) ON DELETE SET NULL,
    INDEX idx_utente (utente_id),
    INDEX idx_data (data_ora),
    INDEX idx_azione (azione)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserimento Impostazioni Default
INSERT INTO impostazioni (chiave, valore, descrizione, tipo) VALUES
('max_prestiti_studente', '3', 'Numero massimo di prestiti contemporanei per studente', 'int'),
('giorni_ritiro_prenotazione', '3', 'Giorni massimi per ritirare un libro prenotato', 'int'),
('giorni_durata_prestito', '14', 'Durata standard di un prestito in giorni', 'int'),
('mancati_ritiri_blacklist', '3', 'Numero di mancati ritiri prima della blacklist', 'int'),
('giorni_ritardo_blacklist', '7', 'Giorni di ritardo prima della blacklist automatica', 'int'),
('orari_ritiro', '{"lunedi": "08:00-13:00", "martedi": "08:00-13:00", "mercoledi": "08:00-13:00", "giovedi": "08:00-13:00", "venerdi": "08:00-13:00"}', 'Orari disponibili per ritiro/riconsegna', 'json'),
('email_notifiche', 'biblioteca@gobetti.it', 'Email mittente per le notifiche', 'string'),
('nome_scuola', 'Biblioteca Gobetti', 'Nome della scuola/biblioteca', 'string'),
('prestiti_classe_min', '10', 'Numero minimo libri per prestito di classe', 'int'),
('prestiti_classe_max', '30', 'Numero massimo libri per prestito di classe', 'int');

-- Inserimento Utenti di Esempio
-- Password: tutti hanno password 'password123' (in produzione usare password hashate con password_hash())
INSERT INTO utenti (username, password, nome, cognome, email, livello, classe_id) VALUES
-- Admin e Dirigenti
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'Sistema', 'admin@gobetti.it', 900, NULL),
('dirigente1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Paolo', 'Ferri', 'dirigente@gobetti.it', 900, NULL),

-- Bibliotecari
('bibliotecario1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Maria', 'Rossi', 'bibliotecario1@gobetti.it', 320, NULL),
('bibliotecario2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Laura', 'Bianchi', 'bibliotecario2@gobetti.it', 320, NULL),
('bibliotecario3', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Giulia', 'Verdi', 'bibliotecario3@gobetti.it', 320, NULL),

-- Docenti
('docente1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Giovanni', 'Bianchi', 'docente1@gobetti.it', 300, NULL),
('docente2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Marco', 'Neri', 'docente2@gobetti.it', 300, NULL),
('docente3', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Anna', 'Russo', 'docente3@gobetti.it', 300, NULL),
('docente4', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Francesca', 'Gallo', 'docente4@gobetti.it', 300, NULL),
('docente5', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Roberto', 'Costa', 'docente5@gobetti.it', 300, NULL),

-- Studenti Classe 1A
('studente1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Luca', 'Verdi', 'studente1@gobetti.it', 100, 1),
('studente2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sofia', 'Marino', 'studente2@gobetti.it', 100, 1),
('studente3', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Alessandro', 'Ricci', 'studente3@gobetti.it', 100, 1),
('studente4', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Giulia', 'Ferrari', 'studente4@gobetti.it', 100, 1),
('studente5', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Matteo', 'Romano', 'studente5@gobetti.it', 100, 1),

-- Studenti Classe 2A
('studente6', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Chiara', 'Colombo', 'studente6@gobetti.it', 100, 2),
('studente7', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Lorenzo', 'Esposito', 'studente7@gobetti.it', 100, 2),
('studente8', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Martina', 'Rizzo', 'studente8@gobetti.it', 100, 2),
('studente9', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Davide', 'Moretti', 'studente9@gobetti.it', 100, 2),
('studente10', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Francesca', 'Bruno', 'studente10@gobetti.it', 100, 2),

-- Studenti Classe 3A
('studente11', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Simone', 'Conti', 'studente11@gobetti.it', 100, 3),
('studente12', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Elisa', 'De Luca', 'studente12@gobetti.it', 100, 3),
('studente13', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Andrea', 'Mancini', 'studente13@gobetti.it', 100, 3),
('studente14', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Beatrice', 'Santoro', 'studente14@gobetti.it', 100, 3),
('studente15', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Federico', 'Lombardi', 'studente15@gobetti.it', 100, 3),

-- Studenti Classe 3B
('studente16', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Valentina', 'Morelli', 'studente16@gobetti.it', 100, 6),
('studente17', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Tommaso', 'Barbieri', 'studente17@gobetti.it', 100, 6),
('studente18', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Alessia', 'Fontana', 'studente18@gobetti.it', 100, 6),
('studente19', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Nicol\u00f2', 'Caruso', 'studente19@gobetti.it', 100, 6),
('studente20', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sara', 'Greco', 'studente20@gobetti.it', 100, 6);

-- Inserimento Classi di Esempio
INSERT INTO classi (nome, sezione, anno_scolastico, numero_studenti) VALUES
('1', 'A', '2024-2025', 25),
('2', 'A', '2024-2025', 23),
('3', 'A', '2024-2025', 27),
('4', 'A', '2024-2025', 22),
('5', 'A', '2024-2025', 20),
('3', 'B', '2024-2025', 24),
('4', 'B', '2024-2025', 26);

-- Inserimento Libri di Esempio
INSERT INTO libri (tipo, titolo, autore, anno_uscita, casa_editrice, lingua, genere, codice_dewey, collocazione, numero_copie, copie_disponibili, trama) VALUES
('libro', 'I Promessi Sposi', 'Alessandro Manzoni', 1827, 'Mondadori', 'Italiano', 'Romanzo storico', '853', 'A-MAN-01', 5, 5, 'Il celebre romanzo storico ambientato nel XVII secolo in Lombardia.'),
('libro', 'La Divina Commedia', 'Dante Alighieri', 1321, 'Rizzoli', 'Italiano', 'Poesia', '851', 'A-DAN-01', 8, 8, 'Il viaggio di Dante attraverso Inferno, Purgatorio e Paradiso.'),
('libro', '1984', 'George Orwell', 1949, 'Penguin', 'Inglese', 'Distopico', '823', 'B-ORW-01', 3, 3, 'Un romanzo distopico sul totalitarismo e la sorveglianza.'),
('dizionario', 'Dizionario Italiano Garzanti', 'AA.VV.', 2020, 'Garzanti', 'Italiano', 'Dizionario', '453', 'REF-DIZ-01', 2, 2, NULL),
('manuale', 'Matematica per il Liceo', 'Bergamini-Trifone', 2021, 'Zanichelli', 'Italiano', 'Matematica', '510', 'MAN-MAT-01', 4, 4, NULL),
('rivista', 'Focus - Gennaio 2024', 'AA.VV.', 2024, 'Mondadori', 'Italiano', 'Scienza', '500', 'RIV-FOC-01', 1, 1, NULL);

-- Inserimento Dispositivi di Esempio
INSERT INTO dispositivi (tipo, marca, modello, numero_seriale, codice_inventario, stato) VALUES
('tablet', 'Samsung', 'Galaxy Tab A8', 'SN123456789', 'INV-TAB-001', 'disponibile'),
('tablet', 'Samsung', 'Galaxy Tab A8', 'SN123456790', 'INV-TAB-002', 'disponibile'),
('laptop', 'HP', 'ProBook 450', 'SN987654321', 'INV-LAP-001', 'disponibile'),
('lettore_ebook', 'Kobo', 'Libra 2', 'SN456789123', 'INV-EBK-001', 'disponibile'),
('calcolatrice', 'Casio', 'FX-991EX', 'SN741852963', 'INV-CAL-001', 'disponibile');

-- Creazione View per statistiche rapide
CREATE VIEW vista_prestiti_attivi AS
SELECT 
    p.*,
    u.nome,
    u.cognome,
    u.email,
    u.livello,
    l.titolo as libro_titolo,
    d.modello as dispositivo_modello,
    DATEDIFF(CURRENT_DATE, p.data_scadenza) as giorni_ritardo_calcolati
FROM prestiti p
LEFT JOIN utenti u ON p.utente_id = u.id
LEFT JOIN libri l ON p.libro_id = l.id
LEFT JOIN dispositivi d ON p.dispositivo_id = d.id
WHERE p.stato IN ('attivo', 'in_ritardo');

CREATE VIEW vista_prenotazioni_attive AS
SELECT 
    pr.*,
    u.nome,
    u.cognome,
    u.email,
    l.titolo as libro_titolo,
    l.copie_disponibili,
    d.modello as dispositivo_modello
FROM prenotazioni pr
LEFT JOIN utenti u ON pr.utente_id = u.id
LEFT JOIN libri l ON pr.libro_id = l.id
LEFT JOIN dispositivi d ON pr.dispositivo_id = d.id
WHERE pr.stato = 'attiva';

CREATE VIEW vista_utenti_blacklist AS
SELECT 
    u.*,
    bl.motivo,
    bl.dettagli,
    bl.data_inizio,
    COUNT(p.id) as prestiti_attivi
FROM utenti u
LEFT JOIN blacklist_log bl ON u.id = bl.utente_id AND bl.attiva = TRUE
LEFT JOIN prestiti p ON u.id = p.utente_id AND p.stato = 'attivo'
WHERE u.in_blacklist = TRUE
GROUP BY u.id;

-- Trigger per aggiornare copie disponibili
DELIMITER //

CREATE TRIGGER after_prenotazione_insert
AFTER INSERT ON prenotazioni
FOR EACH ROW
BEGIN
    IF NEW.libro_id IS NOT NULL AND NEW.stato = 'attiva' THEN
        UPDATE libri 
        SET copie_disponibili = GREATEST(0, copie_disponibili - 1) 
        WHERE id = NEW.libro_id;
    END IF;
    IF NEW.dispositivo_id IS NOT NULL AND NEW.stato = 'attiva' THEN
        UPDATE dispositivi SET stato = 'in_prestito' WHERE id = NEW.dispositivo_id;
    END IF;
END;//

CREATE TRIGGER after_prenotazione_update
AFTER UPDATE ON prenotazioni
FOR EACH ROW
BEGIN
    IF OLD.stato = 'attiva' AND NEW.stato IN ('annullata', 'scaduta') THEN
        IF NEW.libro_id IS NOT NULL THEN
            UPDATE libri SET copie_disponibili = copie_disponibili + 1 WHERE id = NEW.libro_id;
        END IF;
        IF NEW.dispositivo_id IS NOT NULL THEN
            UPDATE dispositivi SET stato = 'disponibile' WHERE id = NEW.dispositivo_id;
        END IF;
    END IF;
END;//

CREATE TRIGGER after_prestito_insert
AFTER INSERT ON prestiti
FOR EACH ROW
BEGIN
    IF NEW.libro_id IS NOT NULL THEN
        UPDATE libri 
        SET copie_disponibili = GREATEST(0, copie_disponibili - 1) 
        WHERE id = NEW.libro_id;
    END IF;
    IF NEW.dispositivo_id IS NOT NULL THEN
        UPDATE dispositivi SET stato = 'in_prestito' WHERE id = NEW.dispositivo_id;
    END IF;
END;//

CREATE TRIGGER after_prestito_update
AFTER UPDATE ON prestiti
FOR EACH ROW
BEGIN
    IF OLD.stato != 'restituito' AND NEW.stato = 'restituito' THEN
        IF NEW.libro_id IS NOT NULL THEN
            UPDATE libri SET copie_disponibili = copie_disponibili + 1 WHERE id = NEW.libro_id;
            -- Notifica utenti in attesa
            INSERT INTO log_attivita (azione, tabella, record_id, dettagli)
            VALUES ('libro_disponibile', 'libri', NEW.libro_id, 'Libro tornato disponibile');
        END IF;
        IF NEW.dispositivo_id IS NOT NULL THEN
            UPDATE dispositivi SET stato = 'disponibile' WHERE id = NEW.dispositivo_id;
        END IF;
    END IF;
END;//

DELIMITER ;
