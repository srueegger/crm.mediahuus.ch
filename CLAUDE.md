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

Das Mediahuus CRM ist ein internes Customer Relationship Management System für die Mediahuus Handy-An-/Verkauf & Reparatur-Shops. Es handelt sich um eine PHP-basierte Webanwendung zur Verwaltung von Kostenvoranschlägen, Ankäufen, Quittungen und Versicherungsgutachten.

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
│   ├── PurchaseController.php
│   ├── ReceiptController.php
│   └── UserController.php
├── Models/            # Datenmodelle
│   ├── Branch.php
│   ├── Document.php
│   ├── Estimate.php
│   ├── Purchase.php
│   ├── Receipt.php
│   ├── ReceiptItem.php
│   └── User.php
├── Repositories/      # Datenzugriff
│   ├── BranchRepository.php
│   ├── DocumentRepository.php
│   ├── EstimateRepository.php
│   ├── PurchaseRepository.php
│   ├── ReceiptItemRepository.php
│   ├── ReceiptRepository.php
│   └── UserRepository.php
├── Services/          # Business Logic
│   ├── AuthService.php
│   ├── DamageTypeService.php
│   ├── FileUploadService.php
│   └── PdfService.php
└── Middleware/        # Request Middleware
    └── AuthMiddleware.php

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
│   ├── 006_update_branch_emails.sql
│   ├── 007_add_damage_type_to_estimates.sql
│   ├── 007_create_purchases_table.sql
│   ├── 008_create_receipts_table.sql
│   └── 009_add_receipt_type_to_documents.sql
└── backups/          # SQL Dumps

public/
├── index.php         # Application Entry Point
├── assets/           # CSS, JS, Images
└── uploads/          # User Uploads
    └── id_documents/ # Ausweis-Fotos (Ankauf)

templates/            # Twig Templates
├── auth/
│   └── login.html.twig
├── base.html.twig
├── dashboard.html.twig
├── estimates/
│   ├── create.html.twig
│   ├── index.html.twig
│   └── show.html.twig
├── purchases/
│   ├── create.html.twig
│   ├── index.html.twig
│   └── show.html.twig
├── receipts/
│   ├── create.html.twig
│   ├── index.html.twig
│   └── show.html.twig
└── users/
    ├── create.html.twig
    ├── edit.html.twig
    └── index.html.twig
```

## Kernfunktionen

### ✅ Vollständig implementiert
- **Login/Logout**: Authentifizierung für Mitarbeitende (gehashte Passwörter)
- **Dashboard**: Kacheln für "Kostenvoranschlag", "Ankauf", "Quittung", "Versicherungsgutachten"
- **Benutzerverwaltung**: CRUD für Mitarbeitende (Admin-Bereich)
- **Kostenvoranschläge**:
  - Formular mit allen Pflichtfeldern (Kunde, Telefon, E-Mail, Schaden, Kosten, Filiale)
  - Schadenstyp-Klassifikation (Akku, Display, Wasser, etc.) via DamageTypeService
  - PDF-Generation mit Logo & Filial-Adressen
  - Eindeutige Dokumentnummer (Format: KO-YYYY-NNNNNN)
  - Schweizer Standards (CHF, DD.MM.YYYY)
- **Ankauf (Geräte-Einkauf)**:
  - Formular mit Verkäuferdaten (Name, Adresse, Telefon, E-Mail)
  - Gerätedaten (Typ, Marke, Modell, IMEI, Seriennummer, Zustand, Zubehör)
  - IMEI-Barcode-Scanner via Kamera (QuaggaJS)
  - Ausweis-Fotografie via Kamera (Vorder- & Rückseite)
  - Ankaufspreis in CHF
  - PDF-Generierung mit Bestätigungstext & Unterschriftenlinien
  - Eindeutige Dokumentnummer (Format: AN-YYYY-NNNNNN)
- **Quittungen (Verkaufsbelege)**:
  - Formular mit Positions-/Artikelliste (Beschreibung, Menge, Einzelpreis)
  - Dynamische Zeilen hinzufügen/entfernen
  - Automatische Summenberechnung
  - PDF-Generierung mit Artikeltabelle & Gesamtbetrag
  - Eindeutige Dokumentnummer (Format: QU-YYYY-NNNNNN)
- **Dokumentenliste**: Tabellarisch mit Filtern (Filiale, Typ, Datum) pro Modul
- **Filialen-System**: Clara & Reinach mit separaten Adressen

### 🔗 Placeholder implementiert
- **Versicherungsgutachten**: Link führt zurück zum Dashboard

### ⏳ Noch zu implementieren
- **Versicherungsgutachten**: Spezielle Assessment-Workflows
- **Erweiterte Dokumentenverwaltung**: Archive, Export
- **Tests**: Smoke-Tests für zentrale Use-Cases

## Datenmodell (Implementiert)

### Tabellen-Schema
```sql
users (id, name, email [unique], password_hash, is_active, created_at, updated_at)
branches (id, name, street, zip, city, phone, email, created_at, updated_at)
documents (id, doc_type ENUM["estimate","purchase","insurance","receipt"], doc_number UNIQUE, branch_id FK, user_id FK, customer_name, customer_phone, customer_email, created_at, updated_at)
estimates (id, document_id FK UNIQUE, device_name, serial_number, damage_type ENUM, issue_text TEXT, price_chf DECIMAL(10,2))
purchases (id, document_id FK UNIQUE, seller_street, seller_zip, seller_city, device_type, device_brand, device_model, imei, serial_number, device_condition, accessories, purchase_price_chf DECIMAL(10,2), id_document_front, id_document_back)
receipts (id, document_id FK UNIQUE, notes TEXT, total_amount DECIMAL(10,2))
receipt_items (id, receipt_id FK, item_description, quantity INT, unit_price DECIMAL(10,2), line_total DECIMAL(10,2))
```

### Design-Prinzip
- **Generische Dokumente**: `documents` als Basis-Tabelle
- **Typ-spezifische Details**: `estimates`, `purchases`, `receipts` je als Detail-Tabelle
- **Eindeutige Dokumentnummern**: KO-YYYY-NNNNNN, AN-YYYY-NNNNNN, QU-YYYY-NNNNNN

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

### Ankauf (Geräte-Einkauf)
```
GET  /purchases              → Ankaufsliste (mit Filtern)
GET  /purchases/create       → Ankauf-Formular
POST /purchases              → Speichern + Redirect
GET  /purchases/{id}         → Detailansicht
GET  /purchases/{id}/pdf     → PDF-Download (Ankaufsbeleg)
```

### Quittungen (Verkaufsbelege)
```
GET  /receipts               → Quittungsliste (mit Filtern)
GET  /receipts/create        → Quittungs-Formular
POST /receipts               → Speichern + Redirect
GET  /receipts/{id}          → Detailansicht
GET  /receipts/{id}/pdf      → PDF-Download (Quittung)
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
- [✅] Database-Migrations
- [✅] Kostenvoranschlag-Formular mit Validierung
- [✅] Schadenstyp-Klassifikation (DamageTypeService)
- [✅] PDF-Generierung (Logo, Filial-Adressen, CH-Standards)
- [✅] Dokumenten-Liste mit Filtern (Filiale, Typ, Datum)
- [✅] Dashboard mit Navigation und letzten Dokumenten
- [✅] User-Management (CRUD)

### ✅ Sprint 2: Ankauf-Modul (ABGESCHLOSSEN)
- [✅] Ankauf-Formular mit Verkäufer- & Gerätedaten
- [✅] IMEI-Barcode-Scanner (QuaggaJS via Kamera)
- [✅] Ausweis-Fotografie (Kamera-Capture, Vorder-/Rückseite)
- [✅] FileUploadService für ID-Dokument-Uploads
- [✅] PDF-Generierung (Ankaufsbeleg mit Bestätigung & Unterschriften)
- [✅] Dokumenten-Liste mit Filtern

### ✅ Sprint 3: Quittungen (ABGESCHLOSSEN)
- [✅] Quittungs-Formular mit dynamischer Artikelliste
- [✅] Positions-Management (hinzufügen/entfernen, Menge, Einzelpreis)
- [✅] PDF-Generierung (Artikeltabelle mit Summen)
- [✅] Dokumenten-Liste mit Filtern

### 🎯 Sprint 4: Versicherungsgutachten (AUSSTEHEND)
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

## Nächste Schritte
1. **Versicherungsgutachten**: Sprint 4 - Gutachten-Formulare & PDF-Layouts
2. **Tests implementieren**: Smoke-Tests für Login, Dokument-Erstellung, PDF-Generation
3. **Code-Quality**: PHPStan-Setup, PHPCS-Konfiguration
4. **Erweiterte Dokumentenverwaltung**: Archive, Export
5. **Deployment**: Staging/Produktions-Setup definieren

---

**Status:** Sprints 1-3 abgeschlossen (Kostenvoranschläge, Ankauf, Quittungen)
**Nächste Iteration:** Versicherungsgutachten (Sprint 4)
- immer das gleiche gürn für icons und buttons verwenden, das wir sonst auch überall bei dem projekt nutzen