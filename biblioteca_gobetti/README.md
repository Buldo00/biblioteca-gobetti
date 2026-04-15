# 📚 Biblioteca Gobetti - Sistema di Gestione Prestiti

Sistema completo di gestione biblioteca scolastica con prenotazioni, prestiti, blacklist e gestione multi-livello.

## 🚀 Installazione

### Requisiti
- PHP 7.4 o superiore
- MySQL 5.7 o superiore
- Server web (Apache/Nginx)
- Estensione PDO MySQL per PHP

### Passo 1: Creazione Database

1. Accedi a MySQL/phpMyAdmin
2. Esegui il file `database.sql` per creare il database completo:
   ```bash
   mysql -u root -p < database.sql
   ```
   Oppure importa tramite phpMyAdmin

### Passo 2: Configurazione Database

Modifica il file `config/database.php` con le tue credenziali:

```php
define('DB_HOST', 'localhost');      // Il tuo host
define('DB_NAME', 'biblioteca_gobetti');
define('DB_USER', 'root');           // Il tuo username
define('DB_PASS', '');               // La tua password
```

### Passo 3: Installazione File

1. Copia tutti i file nella directory del tuo server web (es. `/var/www/html/biblioteca_gobetti/` o `htdocs/biblioteca_gobetti/`)
2. Assicurati che i permessi siano corretti:
   ```bash
   chmod 755 -R biblioteca_gobetti/
   ```

### Passo 4: Accesso al Sistema

1. Apri il browser e vai a: `http://localhost/biblioteca_gobetti/`
2. Usa uno degli account di test per accedere

## 👥 Account di Test

Il sistema include account preconfigurati per ogni livello:

| Ruolo | Username | Password | Livello |
|-------|----------|----------|---------|
| Admin/Dirigente | admin | password123 | 900 |
| Bibliotecario | bibliotecario1 | password123 | 320 |
| Docente | docente1 | password123 | 300 |
| Studente | studente1 | password123 | 100 |

**IMPORTANTE:** Cambia le password in produzione!

## 🎯 Funzionalità Principali

### Per Studenti (Livello 100)
- ✅ Ricerca e sfoglia catalogo libri
- ✅ Prenota fino a 3 libri contemporaneamente
- ✅ Visualizza i propri prestiti e prenotazioni
- ✅ Richiesta notifica quando un libro diventa disponibile
- ✅ Annullamento prenotazioni
- ⚠️ Gestione automatica blacklist per ritardi

### Per Docenti (Livello 300)
- ✅ Tutte le funzionalità studenti
- ✅ Prenotazione di classe (10-30 libri)
- ✅ Selezione studenti per prenotazioni di classe
- ✅ Gestione prestiti per la classe

### Per Bibliotecari (Livello 320)
- ✅ Tutte le funzionalità docenti
- ✅ Gestione completa prestiti (ritiri e restituzioni)
- ✅ Gestione catalogo libri (CRUD)
- ✅ Gestione dispositivi elettronici
- ✅ Visualizzazione prenotazioni attive
- ✅ Conferma doppio check ritiro/restituzione
- ✅ Aggiunta manuale utenti in blacklist

### Per Amministratori (Livello 600+)
- ✅ Tutte le funzionalità bibliotecari
- ✅ Gestione impostazioni sistema
- ✅ Modifica parametri (giorni prestito, limiti, etc.)
- ✅ Gestione utenti
- ✅ Statistiche avanzate
- ✅ Eliminazione libri

## ⚙️ Configurazione Sistema

### Impostazioni Modificabili

Accedi come admin a `/admin/impostazioni.php` per modificare:

- **max_prestiti_studente**: Numero massimo prestiti contemporanei (default: 3)
- **giorni_ritiro_prenotazione**: Giorni per ritirare una prenotazione (default: 3)
- **giorni_durata_prestito**: Durata standard prestito in giorni (default: 14)
- **mancati_ritiri_blacklist**: Mancati ritiri prima della blacklist (default: 3)
- **giorni_ritardo_blacklist**: Giorni di ritardo per blacklist automatica (default: 7)
- **orari_ritiro**: Orari disponibili per ritiro/riconsegna (JSON)
- **prestiti_classe_min/max**: Limiti per prestiti di classe (default: 10-30)

## 🔄 Sistema Blacklist

### Trigger Automatici
1. **Ritardo**: Dopo 7 giorni (configurabile) di ritardo nella restituzione
2. **Mancato Ritiro**: Dopo 3 (configurabile) prenotazioni non ritirate

### Uscita dalla Blacklist
- Automatica: quando tutti i prestiti vengono restituiti
- Manuale: il bibliotecario può rimuovere dalla blacklist

## 📋 Workflow Prestito

### 1. Prenotazione
```
Utente → Catalogo → Prenota Libro
↓
Sistema verifica disponibilità e limiti
↓
Prenotazione creata (3 giorni per ritiro)
```

### 2. Ritiro (Doppio Check)
```
Bibliotecario conferma consegna (check 1)
↓
Utente conferma ricezione (check 2)
↓
Prestito attivo (14 giorni)
```

### 3. Restituzione (Doppio Check)
```
Utente consegna materiale (check 1)
↓
Bibliotecario conferma ricezione (check 2)
↓
Prestito chiuso + notifica utenti in attesa
```

## 🔧 Struttura File

```
biblioteca_gobetti/
├── config/
│   └── database.php          # Configurazione DB
├── includes/
│   ├── functions.php         # Funzioni comuni
│   └── header.php            # Header comune
├── assets/
│   ├── css/
│   │   └── style.css         # Stile principale
│   └── js/
│       └── main.js           # JavaScript
├── user/
│   ├── dashboard.php         # Dashboard utente
│   ├── catalogo.php          # Catalogo libri
│   ├── prestiti.php          # Prestiti personali
│   ├── prenotazioni.php      # Prenotazioni personali
│   └── logout.php            # Logout
├── admin/
│   ├── gestione_prestiti.php    # Gestione prestiti (bibliotecari)
│   ├── gestione_libri.php       # CRUD libri (bibliotecari)
│   ├── gestione_utenti.php      # Gestione utenti (admin)
│   ├── gestione_dispositivi.php # Gestione dispositivi
│   ├── impostazioni.php         # Impostazioni sistema (admin)
│   └── form_libro.php           # Form riutilizzabile libri
├── api/
│   ├── prenota.php              # API prenotazione
│   ├── annulla_prenotazione.php # API annullamento
│   ├── richiedi_notifica.php    # API notifica disponibilità
│   └── conferma_prestito.php    # API doppio check
├── database.sql              # Schema database completo
└── index.php                 # Pagina login
```

## 📊 Database

### Tabelle Principali
- **utenti**: Gestione utenti e livelli
- **libri**: Catalogo completo
- **dispositivi**: Dispositivi elettronici
- **prestiti**: Prestiti attivi e storici
- **prenotazioni**: Prenotazioni attive
- **blacklist_log**: Storico blacklist
- **notifiche_disponibilita**: Richieste notifica
- **impostazioni**: Configurazione sistema
- **log_attivita**: Log completo azioni

### Trigger Automatici
- Aggiornamento copie disponibili
- Gestione stati prestiti/prenotazioni
- Notifiche disponibilità

## 🔐 Livelli di Accesso

| Livello | Ruolo | Codice |
|---------|-------|--------|
| 100 | Studente | LIVELLO_STUDENTE |
| 300 | Docente | LIVELLO_DOCENTE |
| 320 | Bibliotecario | LIVELLO_BIBLIOTECARIO |
| 400 | Tecnico | LIVELLO_TECNICO |
| 500 | Collaboratore | LIVELLO_COLLABORATORE |
| 600 | Amministrativo | LIVELLO_AMMINISTRATIVO |
| 900 | Dirigente | LIVELLO_DIRIGENTE |

## 🛠️ Personalizzazione

### Aggiungere Nuovi Libri
1. Login come bibliotecario
2. Vai a "Gestione Libri"
3. Clicca "Aggiungi Libro"
4. Compila il form

### Modificare Impostazioni
1. Login come admin
2. Vai a "Impostazioni"
3. Modifica i valori desiderati
4. Salva

### Gestire Blacklist
1. Login come bibliotecario
2. Vai a "Gestione Utenti"
3. Visualizza utenti in blacklist
4. Aggiungi/rimuovi manualmente se necessario

## 📱 Responsive Design

Il sistema è completamente responsive e funziona su:
- 💻 Desktop
- 📱 Tablet
- 📱 Smartphone

## 🎨 Personalizzazione Stile

Modifica `assets/css/style.css` per cambiare:
- Colori (variabili CSS in `:root`)
- Layout e spaziature
- Font e tipografia

Colori principali:
```css
--primary-color: #2c3e50;
--secondary-color: #3498db;
--success-color: #27ae60;
--danger-color: #e74c3c;
--warning-color: #f39c12;
```

## 🔄 Manutenzione

### Task Automatici (da configurare con Cron)
```bash
# Controlla scadenze ogni ora
0 * * * * php /path/to/biblioteca_gobetti/cron/check_scadenze.php

# Invia notifiche giornaliere
0 8 * * * php /path/to/biblioteca_gobetti/cron/notifiche_giornaliere.php
```

### Backup Database
```bash
mysqldump -u root -p biblioteca_gobetti > backup_$(date +%Y%m%d).sql
```

## 🐛 Risoluzione Problemi

### Errore di Connessione Database
- Verifica credenziali in `config/database.php`
- Controlla che MySQL sia in esecuzione
- Verifica che il database esista

### Pagine Bianche
- Attiva visualizzazione errori PHP:
  ```php
  ini_set('display_errors', 1);
  error_reporting(E_ALL);
  ```
- Controlla i log di errore PHP

### Sessioni Non Funzionanti
- Verifica permessi cartella sessioni
- Controlla `session_start()` in `functions.php`

## 📞 Supporto

Per problemi o domande:
1. Verifica il file `database.sql` sia stato eseguito correttamente
2. Controlla i permessi dei file
3. Verifica la configurazione PHP (PDO MySQL abilitato)

## 📝 Note di Sicurezza

**Per Produzione:**
1. ✅ Cambia TUTTE le password di default
2. ✅ Usa password hashate (già implementato con `password_hash()`)
3. ✅ Configura HTTPS
4. ✅ Limita accesso a `config/database.php`
5. ✅ Abilita protezione CSRF (già implementata)
6. ✅ Configura invio email SMTP reale
7. ✅ Backup regolari del database

## 🎓 Sviluppato per

Sistema di gestione biblioteca per la **Biblioteca Gobetti**

## 📄 Licenza

Sistema sviluppato per uso interno scolastico.

---

**Versione**: 1.0  
**Data**: 2024  
**Tecnologie**: PHP, MySQL, HTML5, CSS3, JavaScript
