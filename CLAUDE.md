# Mediahuus CRM - Claude Code Dokumentation

## Ursprünglicher Projektauftrag

**Rolle:** Senior-Full-Stack-Pair-Programmer für komplette Projektabwicklung (Setup, Architektur, Code, Tests, Git-Flow, Dokumentation)

### Projektziel
Internes CRM für einen Handy-An-/Verkauf- & Reparatur-Shop mit 2 Filialen:
- **Filiale 1:** Mediahuus Clara  
- **Filiale 2:** Mediahuus Reinach

**Workflow:** Mitarbeitende → Login → Filiale wählen → Vorgänge erfassen → PDF mit Logo & Filial-Adresse → Dashboard mit allen Dokumenten

### Entwicklungsansatz
- Iterative Entwicklung in kleinen, merge-fertigen Branches
- Klare Commits nach Conventional Commits Standard
- Saubere, schlanke Basis ohne Over-Engineering

## Projektübersicht

Das Mediahuus CRM ist ein internes Customer Relationship Management System für die Mediahuus Handy-An-/Verkauf & Reparatur-Shops. Es handelt sich um eine PHP-basierte Webanwendung zur Verwaltung von Kostenvoranschlägen, Ankäufen und Versicherungsgutachten.

## Technische Architektur

### Tech Stack (ADR-0001)
- **Framework**: Slim 4 (Performance & Einfachheit)
- **Template Engine**: Twig 3 (Security & DX)
- **Database**: Doctrine DBAL + MariaDB (Query Builder ohne ORM-Overhead)
- **PDF Generation**: TCPDF (ursprünglich geplant: mPDF)
- **Frontend**: Tailwind CSS + Vanilla JavaScript (Utility-first)
- **Authentication**: PHP Sessions + bcrypt (CSRF-Schutz)
- **Dependency Injection**: PHP-DI Container (Auto-wiring)
- **Logging**: Monolog
- **Environment**: DDEV (PHP 8.2 + MariaDB)

### Projektstruktur

```
app/
├── Controllers/        # HTTP Request Handler
│   ├── AuthController.php
│   ├── BaseController.php
│   ├── DashboardController.php
│   ├── EstimateController.php
│   └── UserController.php
├── Models/            # Datenmodelle
│   ├── Branch.php
│   ├── Document.php
│   ├── Estimate.php
│   └── User.php
├── Repositories/      # Datenzugriff
├── Services/          # Business Logic
│   ├── AuthService.php
│   └── PdfService.php
└── Middleware/        # Request Middleware

config/
├── container.php      # DI Container Konfiguration
└── routes.php         # Routing Definitionen

database/
├── migrations/        # SQL Migrations
│   ├── 001_create_users_table.sql
│   ├── 002_create_branches_table.sql
│   ├── 003_create_documents_table.sql
│   ├── 004_create_estimates_table.sql
│   ├── 005_add_device_fields_to_estimates.sql
│   └── 006_update_branch_emails.sql
└── backups/          # SQL Dumps

public/
├── index.php         # Application Entry Point
└── assets/           # CSS, JS, Images

templates/            # Twig Templates
├── auth/
├── base.html.twig
└── dashboard.html.twig
```

## Kernfunktionen (MVP-Status)

### ✅ Vollständig implementiert
- **Login/Logout**: Authentifizierung für Mitarbeitende (gehashte Passwörter)
- **Dashboard**: Kacheln für "Kostenvoranschlag", "Ankauf", "Versicherungsgutachten"
- **Benutzerverwaltung**: CRUD für Mitarbeitende (Admin-Bereich)
- **Kostenvoranschläge**: 
  - Formular mit allen Pflichtfeldern (Kunde, Telefon, E-Mail, Schaden, Kosten, Filiale)
  - PDF-Generation mit Logo & Filial-Adressen
  - Eindeutige Dokumentnummer (Format: KO-YYYY-NNNNNN)
  - Schweizer Standards (CHF, DD.MM.YYYY)
- **Dokumentenliste**: Tabellarisch mit Filtern (Filiale, Typ, Datum)
- **Filialen-System**: Clara & Reinach mit separaten Adressen

### 🔗 Placeholder implementiert
- **Ankauf**: Link führt zurück zum Dashboard
- **Versicherungsgutachten**: Link führt zurück zum Dashboard

### ⏳ Noch zu implementieren (nächste Sprints)
- **Ankauf-Modul**: Geräte-Einschätzungs-Formulare
- **Versicherungsgutachten**: Spezielle Assessment-Workflows
- **Erweiterte Dokumentenverwaltung**: Archive, Export
- **Tests**: Smoke-Tests für zentrale Use-Cases

## Datenmodell (Implementiert)

### Tabellen-Schema
```sql
users (id, name, email [unique], password_hash, is_active, created_at, updated_at)
branches (id, name, street, zip, city, phone, email, created_at, updated_at)
documents (id, doc_type ENUM["estimate","purchase","insurance"], doc_number UNIQUE, branch_id FK, user_id FK, customer_name, customer_phone, customer_email, created_at, updated_at)
estimates (id, document_id FK UNIQUE, issue_text TEXT, price_chf DECIMAL(10,2))
```

### Design-Prinzip
- **Generische Dokumente**: `documents` als Basis-Tabelle
- **Typ-spezifische Details**: `estimates` für Kostenvoranschläge
- **Erweiterbar**: Zukünftig `purchase`, `insurance` analog
- **Eindeutige Dokumentnummern**: Format KO-2025-000001

### Environment Variablen
```
# Database Connection
DB_HOST=db
DB_DATABASE=crm_mediahuus
DB_USERNAME=root
DB_PASSWORD=root

# Application
APP_NAME="Mediahuus CRM
APP_ENV=production
DEBUG=false

# Twig & Session
TWIG_CACHE=false
SESSION_NAME=mediahuus_crm_session
```

## Entwicklung

### Setup & Installation
```bash
# DDEV Container starten
ddev start

# Dependencies installieren
ddev composer install

# Migrations ausführen
ddev composer migrate

# Test-Daten laden
ddev composer seed
```

### Verfügbare Commands
```bash
# DDEV Container-Management
ddev start          # Container starten
ddev stop           # Container stoppen
ddev launch         # Browser öffnen

# Dependencies & Entwicklung
ddev composer install    # Dependencies installieren
ddev composer update     # Dependencies aktualisieren

# Database-Operations
ddev composer migrate    # Database Migrations
ddev composer seed       # Test-Daten laden

# Development Tools (geplant)
ddev composer test       # PHPUnit Tests
ddev composer phpcs      # Code Style Check
ddev composer phpstan    # Static Analysis

# System-Commands
ddev logs -f            # Logs anzeigen
ddev ssh                # Shell in Container
```

### Demo Zugang
```
E-Mail: admin@mediahuus.ch
Passwort: admin123
```

## Code Standards

- **PHP Standards**: PSR-12 für Code Style
- **Strict Types**: `declare(strict_types=1)` in allen Dateien
- **Dependency Injection**: Konstruktor-basierte DI über PHP-DI Container
- **Error Handling**: Try-catch mit Logging über Monolog
- **SQL**: Prepared Statements über Doctrine DBAL

## Sicherheit

- **Session Management**: Sichere Session-Konfiguration
- **Password Hashing**: PHP `password_hash()` für User-Passwörter
- **SQL Injection**: Parameterisierte Queries über DBAL
- **Input Validation**: Formulardaten-Validierung vor DB-Operationen

## Routen-Architektur (Implementiert)

### Authentifizierung
```
GET  /login          → Login-Formular
POST /login          → Auth-Verarbeitung
POST /logout         → Session beenden
```

### Dashboard & Hauptnavigation
```
GET  /               → Dashboard (Kacheln, letzte Dokumente)
POST /clear-cache    → Cache-Management
```

### Kostenvoranschläge
```
GET  /estimates              → Dokumentenliste (mit Filtern)
GET  /estimates/create       → Formular
POST /estimates              → Speichern + Redirect
GET  /estimates/{id}         → Detailansicht
GET  /estimates/{id}/pdf     → PDF-Download
```

### Benutzerverwaltung
```
GET  /users                  → User-Liste
GET  /users/create           → Neuer User
POST /users                  → User speichern
GET  /users/{id}/edit        → User bearbeiten
POST /users/{id}             → User aktualisieren
POST /users/{id}/toggle-status → Aktiv/Inaktiv
```

### Placeholder-Routes
```
GET  /purchase/new           → Redirect zu Dashboard
GET  /insurance/new          → Redirect zu Dashboard
```

## Git-Workflow & Entwicklung

### Branching-Strategie
- **main**: Immer grün, deploy-fähig
- **Feature-Branches**: `feat/estimate-form`, `feat/pdf-generation`, `feat/auth`
- **Kleine Commits**: Aussagekräftige Messages nach Conventional Commits

### Code-Standards
- **PHP**: PSR-12 Code Style, `declare(strict_types=1)`
- **Security**: CSRF-Schutz, Input-Validierung, Prepared Statements
- **Commits**: `feat:`, `fix:`, `chore:`, `docs:`
- **Quality Tools**: PHPStan/Larastan geplant

## Sprint-Status

### ✅ Sprint 1: Kostenvoranschlag (ABGESCHLOSSEN)
- [✅] Database-Migrations (alle 6 Migrations)
- [✅] Kostenvoranschlag-Formular mit Validierung
- [✅] PDF-Generierung (Logo, Filial-Adressen, CH-Standards)
- [✅] Dokumenten-Liste mit Filtern (Filiale, Typ, Datum)
- [✅] Dashboard mit Navigation und letzten Dokumenten
- [✅] User-Management (CRUD)
- [⏳] Tests (noch ausstehend)

### 🎯 Sprint 2: Ankauf-Modul (NÄCHSTER)
- [ ] Ankauf-Formular für Geräte-Bewertung
- [ ] PDF-Templates für Ankaufs-Belege
- [ ] Integration mit Document-System
- [ ] Erweiterte Filiale-Features

### 🎯 Sprint 3: Versicherungsgutachten
- [ ] Gutachten-Formulare
- [ ] Spezielle PDF-Layouts für Versicherungen
- [ ] Archivierungs-System

## Deployment

- **Lokal**: DDEV Container-Environment
- **Staging/Produktion**: TBD (noch zu definieren)
- **Database**: MariaDB mit SQL-Dumps für Backup

## Filialen

- **Mediahuus Clara**: Hauptfiliale
- **Mediahuus Reinach**: Zweigstelle

(Adressen und Kontaktdaten in database/migrations definiert)

## Demo-Zugang
```
E-Mail: admin@mediahuus.ch
Passwort: admin123
```

## Nächste Schritte
1. **Tests implementieren**: Smoke-Tests für Login, Estimate-Erstellung, PDF-Generation
2. **Code-Quality**: PHPStan-Setup, PHPCS-Konfiguration
3. **Sprint 2 starten**: Ankauf-Modul planen und implementieren
4. **Performance**: Caching-Layer für PDF-Generation
5. **UX**: Frontend-Verbesserungen, Responsive Design

---

**Status:** MVP vollständig implementiert, Sprint 1 abgeschlossen
**Nächste Iteration:** Ankauf-Modul (Sprint 2)

Diese Dokumentation wird kontinuierlich mit der Entwicklung aktualisiert.
- immer das gleiche gürn für icons und buttons verwenden, das wir sonst auch überall bei dem projekt nutzen