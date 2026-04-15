# 🚀 Script di Installazione Rapida - Biblioteca Gobetti

## Per Linux/Mac

```bash
#!/bin/bash

echo "📚 Installazione Biblioteca Gobetti"
echo "===================================="

# 1. Verifica MySQL
echo "Verifico MySQL..."
if ! command -v mysql &> /dev/null; then
    echo "❌ MySQL non trovato. Installa MySQL prima di continuare."
    exit 1
fi

# 2. Crea database
echo "Creazione database..."
mysql -u root -p << EOF
CREATE DATABASE IF NOT EXISTS biblioteca_gobetti CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EOF

# 3. Importa schema
echo "Importazione schema database..."
mysql -u root -p biblioteca_gobetti < database.sql

echo "✅ Database creato con successo!"

# 4. Configura permessi
echo "Configurazione permessi..."
chmod 755 -R .
chmod 644 config/database.php

echo "✅ Installazione completata!"
echo ""
echo "Prossimi passi:"
echo "1. Modifica config/database.php con le tue credenziali"
echo "2. Apri http://localhost/biblioteca_gobetti nel browser"
echo "3. Login con: admin / password123"
echo ""
echo "⚠️  IMPORTANTE: Cambia le password di default!"
```

## Per Windows (XAMPP/WAMP)

1. **Avvia XAMPP/WAMP**
   - Avvia Apache e MySQL

2. **Crea Database**
   - Apri phpMyAdmin (http://localhost/phpmyadmin)
   - Clicca "Nuovo"
   - Nome database: `biblioteca_gobetti`
   - Collation: `utf8mb4_unicode_ci`
   - Clicca "Crea"

3. **Importa Schema**
   - Seleziona il database `biblioteca_gobetti`
   - Clicca "Importa"
   - Scegli il file `database.sql`
   - Clicca "Esegui"

4. **Copia File**
   - Copia la cartella `biblioteca_gobetti` in:
     - XAMPP: `C:\xampp\htdocs\`
     - WAMP: `C:\wamp64\www\`

5. **Configura Database**
   - Apri `config/database.php`
   - Modifica le credenziali se necessario

6. **Accedi**
   - Apri: http://localhost/biblioteca_gobetti
   - Login: admin / password123

## Verifica Installazione

### Checklist
- [ ] Database creato correttamente
- [ ] Tutte le tabelle presenti (11 tabelle)
- [ ] Dati di esempio caricati
- [ ] Pagina di login accessibile
- [ ] Login con admin funzionante
- [ ] Dashboard visibile

### Test Rapidi

1. **Test Login**
   ```
   URL: http://localhost/biblioteca_gobetti
   User: admin
   Pass: password123
   ```

2. **Test Database**
   ```sql
   USE biblioteca_gobetti;
   SELECT COUNT(*) FROM utenti; -- Deve restituire 4
   SELECT COUNT(*) FROM libri;  -- Deve restituire 6
   ```

3. **Test Funzionalità**
   - Login come studente (studente1/password123)
   - Vai al catalogo
   - Prova a prenotare un libro
   - Verifica che la prenotazione appaia

## Risoluzione Problemi Comuni

### Errore: "Database connection failed"
**Soluzione:**
1. Verifica che MySQL sia avviato
2. Controlla credenziali in `config/database.php`
3. Verifica che il database esista

### Errore: "404 Not Found"
**Soluzione:**
1. Verifica che i file siano nella cartella corretta
2. Controlla il percorso nel browser
3. Riavvia Apache

### Errore: "Call to undefined function password_verify"
**Soluzione:**
1. Verifica versione PHP >= 7.4
2. Riavvia il server web

### Le sessioni non funzionano
**Soluzione:**
1. Verifica permessi cartella tmp
2. Controlla php.ini per session.save_path
3. Riavvia Apache/PHP-FPM

## Configurazione Avanzata

### Email (Opzionale)
Per abilitare le notifiche email:
1. Installa PHPMailer: `composer require phpmailer/phpmailer`
2. Modifica la funzione `sendEmail()` in `includes/functions.php`
3. Configura SMTP nelle impostazioni

### Cron Jobs (Consigliato)
Aggiungi al crontab:
```bash
# Controlla scadenze ogni ora
0 * * * * php /path/to/biblioteca_gobetti/includes/functions.php

# Backup giornaliero
0 2 * * * mysqldump -u root -p biblioteca_gobetti > /backup/biblioteca_$(date +\%Y\%m\%d).sql
```

### HTTPS (Produzione)
1. Ottieni certificato SSL (Let's Encrypt)
2. Configura Apache/Nginx per HTTPS
3. Forza redirect HTTP → HTTPS

## Supporto

**Documentazione completa:** README.md

**Account di test:**
- admin / password123 (Dirigente)
- bibliotecario1 / password123 (Bibliotecario)
- docente1 / password123 (Docente)
- studente1 / password123 (Studente)

**Nota:** Cambia TUTTE le password in produzione!
