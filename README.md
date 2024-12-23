# Magellano.ai - Revenue Management Platform

A Laravel-based platform for revenue management and statement processing.

## Requirements
- PHP 8.2+
- MySQL 8.0+
- Node.js & NPM


## TODO
script afterInstall
- generare .env
- eliminare .git folder


# Developer Documentation
Project: revenue.magellano.ai
Version: 1.0.0

## Project Overview

### Tech Stack
- PHP 8.0+
- Laravel Framework
- MySQL/MariaDB
- Redis (code e cache)
- Node.js e NPM (frontend assets)
- Alpine.js (frontend interactivity)
- Tailwind CSS (styling)

### Key Features
- Sistema autenticazione multi-ruolo
- Gestione publisher e sub-publisher
- Upload e processing file CSV
- Generazione export AX
- Integrazione FTP
- Sistema notifiche email

## Setup Development Environment

### Prerequisites
```bash

# Requisiti di sistema
PHP >= 8.0
MySQL >= 8.0
Redis >= 6.0
Node >= 14

# PHP Extensions necessarie
php8.0-mysql
php8.0-redis
php8.0-xml
php8.0-zip
php8.0-mbstring
php8.0-gd
```

### Key Files
- `app/Http/Controllers/UploadController.php`: Gestione upload file
- `app/Services/CsvProcessorService.php`: Elaborazione CSV
- `app/Services/AxExportService.php`: Export AX
- `app/Services/FtpUploadService.php`: Upload FTP

## Core Components

### Authentication System
Localizzato in `app/Http/Controllers/Auth/`

### File Upload System
Localizzato in `app/Services/UploadService.php`

### CSV Processing System
Localizzato in `app/Jobs/ProcessCsvUpload.php`

### Export System
Localizzato in `app/Services/AxExportService.php`


//////////////////////////

# VERSIONE = 1.0.0

# Documentazione Progetto: Sistema di Gestione Publisher

## 1. Overview del Sistema
Il sistema è una piattaforma web sviluppata in PHP/Laravel che gestisce le relazioni tra publisher e sub-publisher, con funzionalità di gestione utenti, upload file, elaborazione consuntivi e integrazione con sistemi esterni (AX e FTP).

### 1.1 Caratteristiche Principali
- Gestione multi-ruolo (admin, publisher)
- Sistema di autenticazione e autorizzazione avanzato
- Gestione publisher e sub-publisher
- Upload e processamento file CSV
- Generazione export per sistema AX
- Integrazione FTP
- Sistema di notifiche email
- Gestione consuntivi e statements

## 2. Architettura del Sistema

### 2.1 Componenti Core
- **Authentication System**: Gestione completa del ciclo di autenticazione (login, registrazione, reset password)
- **Role-Based Access Control**: Sistema di autorizzazione basato su ruoli e policy
- **File Processing System**: Sistema di elaborazione file con code asincrona
- **Notification System**: Sistema di notifiche email per vari eventi
- **Export System**: Generazione export per sistema AX e FTP
- **API Layer**: API interne per la gestione dei dati

### 2.2 Modelli Principali
- `User`: Gestione utenti con relazioni a ruoli e publisher
- `Publisher`: Gestione editori con dati anagrafici e fiscali
- `SubPublisher`: Gestione sotto-editori collegati ai publisher
- `Statement`: Gestione consuntivi e dati finanziari
- `FileUpload`: Gestione upload e processing file
- `Role`: Gestione ruoli utente

## 3. Funzionalità Dettagliate

### 3.1 Sistema di Autenticazione
- **Login**: 
  - Rate limiting per sicurezza
  - Protezione contro attacchi brute force
  - Validazione email e account
  - Gestione sessioni sicure

- **Registrazione**:
  - Validazione partita IVA
  - Verifica esistenza publisher
  - Validazione dati anagrafici e fiscali
  - Invio email di verifica

- **Reset Password**:
  - Token sicuri per reset
  - Validazione password complessa
  - Notifiche email

### 3.2 Gestione Publisher
- Creazione e modifica publisher
- Gestione dati fiscali e bancari
- Sistema di sub-publisher
- Export dati in formato Excel
- Integrazione con sistema AX

### 3.3 Sistema Upload File
- Upload file CSV
- Validazione formati e contenuti
- Processing asincrono con code
- Gestione stati elaborazione
- Generazione report errori
- Export verso sistema AX
- Upload FTP automatizzato

### 3.4 Gestione Consuntivi
- Creazione consuntivi da file
- Validazione dati
- Calcolo importi e statistiche
- Sistema di pubblicazione
- Export dati

### 3.5 Sistema di Notifiche
- Notifiche email per:
  - Verifica account
  - Reset password
  - Upload completati
  - Pubblicazione consuntivi
  - Errori elaborazione

## 4. Sicurezza

### 4.1 Misure di Sicurezza Implementate
- CSRF protection
- Rate limiting
- Session security
- Password hashing
- Input validation
- SQL injection protection
- XSS protection
- Security headers

### 4.2 Middleware di Sicurezza
- `CheckRole`: Validazione ruoli
- `SessionSecurity`: Gestione sessioni sicure
- `SecurityHeaders`: Headers HTTP sicuri
- `ForceHttps`: Forzatura HTTPS
- `EnsureTermsAccepted`: Validazione termini

## 5. Integrazioni

### 5.1 Sistema AX
- Generazione file TSV
- Validazione dati
- Upload automatico
- Gestione errori

### 5.2 FTP
- Upload automatico file
- Gestione connessioni sicure
- Retry automatici
- Logging operazioni

## 6. Jobs e Code

### 6.1 Jobs Principali
- `ProcessCsvUpload`: Elaborazione CSV
- `GenerateAxExport`: Generazione export AX

### 6.2 Eventi e Listener
- `FileUploadProcessed`
- `DispatchAxExport`

## 7. Manutenzione

### 7.1 Sistema di Logging
- Log dettagliati per operazioni critiche
- Rotazione log automatica
- Categorizzazione per canali

### 7.2 Pulizia Sistema
- Cleanup file temporanei
- Pulizia log vecchi
- Gestione file esportati

## 8. API e Endpoints

### 8.1 API Pubbliche
- `/api/check-vat`: Validazione partita IVA
- `/api/publishers/search`: Ricerca publisher
- `/api/publishers/{id}`: Dettagli publisher

### 8.2 API Protette
- Upload management
- Publisher management
- User management
- Statement management

## 9. Requisiti Tecnici

### 9.1 Server Requirements
- PHP 8.0+
- MySQL/MariaDB
- Redis (per code)
- Composer
- Node.js e NPM

### 9.2 Dipendenze Principali
- Laravel Framework
- Alpine.js
- Tailwind CSS
- Excel library
- FTP library

## 10. Best Practices Implementate

### 10.1 Coding Standards
- PSR-12 compliance
- SOLID principles
- DRY principle
- Repository pattern
- Service pattern

### 10.2 Performance
- Caching
- Code asincrone
- Ottimizzazione query
- Lazy loading
- Indici database

### 10.3 Testing
- Unit tests
- Feature tests
- Integration tests
- Error handling
- Logging

## 11. Deployment e Ambiente

### 11.1 Ambienti
- Development
- Staging
- Production

### 11.2 Configurazione
- Variabili ambiente
- File di configurazione
- Secrets management

## 12. Workflow Tipici

### 12.1 Upload Consuntivo
1. Upload file CSV
2. Validazione formato
3. Processing asincrono
4. Generazione report
5. Notifica completamento
6. Export AX/FTP

### 12.2 Registrazione Publisher
1. Inserimento P.IVA
2. Verifica esistenza
3. Compilazione dati
4. Validazione email
5. Approvazione admin
6. Attivazione account

## 13. Considerazioni Future

### 13.1 Possibili Miglioramenti
- Implementazione cache distribuita
- Miglioramento performance upload
- Ottimizzazione query pesanti
- Enhancement sicurezza
- API pubbliche

### 13.2 Scalabilità
- Horizontal scaling
- Load balancing
- Queue workers
- Cache distribution
- Database sharding