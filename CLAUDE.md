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

Das Mediahuus CRM ist ein internes Customer Relationship Management System für die Mediahuus Handy-An-/Verkauf & Reparatur-Shops. Es handelt sich um eine PHP-basierte Webanwendung zur Verwaltung von Kostenvoranschlägen, Ankäufen, Quittungen, Versicherungsgutachten und Rechnungen (mit Swiss QR-Zahlteil).

## Technische Architektur

### Tech Stack (ADR-0001)
- **Framework**: Slim 4 (Performance & Einfachheit)
- **Template Engine**: Twig 3 (Security & DX)
- **Database**: Doctrine DBAL + MariaDB (Query Builder ohne ORM-Overhead)
- **PDF Generation**: TCPDF (ursprünglich geplant: mPDF)
- **QR-Rechnung**: sprain/swiss-qr-bill (Swiss QR-Zahlteil, SCOR-Referenz)
- **Tests**: PHPUnit 10 (`phpunit.xml.dist`, Tests in `tests/`)
- **Frontend**: Tailwind CSS + Vanilla JavaScript (Utility-first)
- **Authentication**: PHP Sessions + bcrypt (CSRF-Schutz)
- **Dependency Injection**: PHP-DI Container (Auto-wiring)
- **Logging**: Monolog
- **E-Mail**: Symfony Mailer + Mailgun-Transport (`symfony/mailer`, `symfony/mailgun-mailer`; `symfony/http-client` für Mailgun über HTTPS), zentral über `MailService`
- **Environment**: DDEV (PHP 8.2 + MariaDB)

### Projektstruktur

```
app/
├── Controllers/        # HTTP Request Handler
│   ├── AuthController.php
│   ├── BaseController.php
│   ├── CustomerController.php
│   ├── DashboardController.php
│   ├── EstimateController.php
│   ├── InvoiceController.php
│   ├── PurchaseController.php
│   ├── ReceiptController.php
│   ├── RepairController.php         # Reparaturen (intern)
│   ├── RepairStatusController.php   # Öffentliche Statusseite /r/{token}
│   └── UserController.php
├── Models/            # Datenmodelle
│   ├── Branch.php
│   ├── Customer.php
│   ├── CustomerData.php
│   ├── Document.php
│   ├── Estimate.php
│   ├── Invoice.php
│   ├── InvoiceItem.php
│   ├── Purchase.php
│   ├── Receipt.php
│   ├── ReceiptItem.php
│   ├── Repair.php                   # Statuskonstanten, isOpen(), Anzeigenamen
│   ├── RepairEvent.php
│   └── User.php
├── Repositories/      # Datenzugriff
│   ├── BranchRepository.php
│   ├── CustomerRepository.php
│   ├── DocumentRepository.php
│   ├── EstimateRepository.php
│   ├── InvoiceRepository.php
│   ├── PurchaseRepository.php
│   ├── ReceiptItemRepository.php
│   ├── ReceiptRepository.php
│   ├── RepairEventRepository.php
│   ├── RepairRepository.php
│   └── UserRepository.php
├── Services/          # Business Logic
│   ├── AppUrl.php                  # APP_URL für Links in E-Mails und QR-Code
│   ├── AuthService.php
│   ├── CustomerFieldMap.php        # Zuordnung Kundenattribute ↔ Formularfelder je Dokumenttyp
│   ├── CustomerFormPrefill.php     # Vorbelegung per ?customer_id=
│   ├── CustomerMergeService.php    # Kunden zusammenführen
│   ├── CustomerResolver.php        # Automatische Kundenzuordnung beim Speichern
│   ├── DamageTypeService.php
│   ├── FileUploadService.php
│   ├── InvoiceCalculator.php
│   ├── InvoiceInputValidator.php
│   ├── InvoiceNumberGenerator.php
│   ├── InvoicePdfService.php
│   ├── InvoiceService.php
│   ├── MailResult.php              # Ergebnis eines Versands (gesendet / nicht konfiguriert / fehlgeschlagen)
│   ├── MailService.php             # EINZIGER Versandweg für E-Mails (siehe «E-Mail-Versand»)
│   ├── PdfService.php
│   ├── PhoneNormalizer.php         # Telefonnummern für den Abgleich normalisieren
│   ├── QrBillFactory.php
│   ├── RepairInputValidator.php
│   ├── RepairMailer.php            # Reparatur-E-Mails (erfasst, abholbereit) über MailService
│   ├── RepairSaveResult.php
│   ├── RepairService.php           # Anlegen, Bearbeiten, Statuswechsel mit Regeln
│   ├── RepairStatusPage.php        # Reduziertes Array für die öffentliche Statusseite
│   ├── RepairStatusTokenGenerator.php
│   └── TwigCacheConfig.php         # TWIG_CACHE auswerten (false / true / Pfad)
└── Middleware/        # Request Middleware
    └── AuthMiddleware.php

config/
├── container.php      # DI Container Konfiguration
├── invoice.php        # Rechnungs-Konfiguration (Absender, IBAN, MWST-Sätze, Zahlungsfrist)
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
│   ├── 009_add_receipt_type_to_documents.sql
│   ├── 010-012 (Versicherungsgutachten, Kundenadresse, Quittungs-Garantie)
│   ├── 013_create_invoices_tables.sql
│   ├── 014_create_customers_table.sql (Kundenstamm: customers, documents.customer_id)
│   └── 015_create_repairs_tables.sql (Reparaturen: repairs, repair_events, doc_type 'repair')
└── backups/          # SQL Dumps

bin/                  # CLI-Skripte
├── migrate.php       # Migrations ausführen
├── seed.php          # Test-Daten laden
└── import-customers.php  # Altdaten-Import Kundenstamm (--dry-run)

public/
├── index.php         # Application Entry Point
├── assets/           # CSS, JS, Images (u.a. js/invoice-form.js: Live-Vorschau Rechnung; js/customer-picker.js: Kundensuche in den Formularen)
└── uploads/          # User Uploads
    └── id_documents/ # Ausweis-Fotos (Ankauf)

templates/            # Twig Templates
├── auth/
│   └── login.html.twig
├── base.html.twig
├── customers/
│   ├── _picker.html.twig     # Kunden-Picker (in allen fünf Erfassungsformularen eingebunden)
│   ├── form.html.twig
│   ├── index.html.twig
│   ├── merge.html.twig
│   └── show.html.twig
├── dashboard.html.twig
├── emails/                   # E-Mail-Templates (HTML + Text)
│   ├── base.html.twig
│   ├── repair_created.{html,txt}.twig
│   └── repair_ready.{html,txt}.twig
├── estimates/
│   ├── create.html.twig
│   ├── index.html.twig
│   └── show.html.twig
├── invoices/
│   ├── create.html.twig
│   └── show.html.twig
├── purchases/
│   ├── create.html.twig
│   ├── index.html.twig
│   └── show.html.twig
├── public/                   # Öffentliche Seiten ohne Login (eigenes Layout)
│   ├── _branch.html.twig
│   ├── base.html.twig
│   ├── not_found.html.twig
│   ├── repair_status.html.twig
│   └── repair_status_gone.html.twig
├── receipts/
│   ├── create.html.twig
│   ├── index.html.twig
│   └── show.html.twig
├── repairs/
│   ├── _status_badge.html.twig
│   ├── create.html.twig      # Erfassen und Bearbeiten
│   ├── index.html.twig
│   └── show.html.twig
└── users/
    ├── create.html.twig
    ├── edit.html.twig
    └── index.html.twig

tests/                 # PHPUnit
├── AuthServiceTest.php
├── Middleware/        # CSRF- und Session-Middleware
├── Models/            # Customer*Test, RepairTest
└── Services/          # Invoice*Test, QrBillFactoryTest, Customer*Test, PhoneNormalizerTest, CsrfTokenManagerTest, TwigCacheConfigTest, Repair*Test, MailServiceTest, AppUrlTest

phpunit.xml.dist       # PHPUnit-Konfiguration
```

## Kernfunktionen

### ✅ Vollständig implementiert
- **Login/Logout**: Authentifizierung für Mitarbeitende (gehashte Passwörter)
- **Navigation**: Sidebar auf allen angemeldeten Seiten (`templates/partials/sidebar.html.twig`), auf Handy/Tablet per ☰ ausklappbar; aktive Seite grün markiert. Menüpunkte zentral in `app/Twig/NavigationExtension.php` (`nav_groups()`, `nav_active()`), Icons in `templates/partials/icons.html.twig`
- **Dashboard**: Kacheln aus denselben Menüpunkten, gruppiert in «Erfassen» (Kostenvoranschlag, Quittung, Rechnung, Ankauf, Gutachten, Reparatur) und «Verwalten» (Offene Reparaturen, Kunden, Benutzer); Hinweis-Kachel mit Anzahl offener und abholbereiter Reparaturen
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
  - Eindeutige Dokumentnummer (Format: AK-YYYY-NNNNNN)
- **Quittungen (Verkaufsbelege)**:
  - Formular mit Positions-/Artikelliste (Beschreibung, Menge, Einzelpreis)
  - Dynamische Zeilen hinzufügen/entfernen
  - Automatische Summenberechnung
  - PDF-Generierung mit Artikeltabelle & Gesamtbetrag
  - Eindeutige Dokumentnummer (Format: QU-YYYY-NNNNNN)
- **Rechnungen** (Swiss QR-Rechnung):
  - Empfänger: Firma und/oder Name plus Adresse (Pflicht)
  - Positionen mit Beschreibung, Menge, Einzelpreis (inkl. MWST) und MWST-Satz pro Position (8.1 % vorausgewählt, 2.6 %, 0 %)
  - Live-Vorschau im Formular (`invoice-form.js`); massgebend sind die serverseitig berechneten Summen (`InvoiceCalculator`)
  - Zahlungsfrist standardmässig 10 Tage, pro Rechnung änderbar
  - PDF mit Rechnungskopf und Swiss QR-Zahlteil, SCOR-Referenz (ISO 11649) aus der Rechnungsnummer
  - Absender immer Mediahuus GmbH, Reinach (zentral in `config/invoice.php`), E-Mail im Kopf immer info@mediahuus.ch; Filiale intern immer Reinach
  - Eindeutige Rechnungsnummer (Format: RE-JJJJ-MM-TT-NN, laufende Nummer pro Rechnungstag)
  - Im Dashboard und im Excel-Export enthalten
  - Nicht im Umfang: Status offen/bezahlt, Mahnwesen, Bearbeiten/Stornieren, E-Mail-Versand
- **Versicherungsgutachten**: Formular, Detailansicht und PDF (`InsuranceController`, VG-YYYY-NNNNNN)
- **Excel-Export**: `GET /export/excel` (`ExportController`)
- **Dokumentenliste**: Tabellarisch mit Filtern (Filiale, Typ, Datum) pro Modul
- **Kundenstamm** (`CustomerController`, Tabelle `customers`):
  - Kundenliste mit Suche (Name, Firma, E-Mail, Telefon; 25 pro Seite), Detailansicht mit Stammdaten, Notizen und allen Dokumenten des Kunden, Bearbeiten
  - Kunden-Picker in allen fünf Erfassungsformularen (`customer-picker.js`, `GET /customers/search`, max. 10 Treffer): Auswahl füllt die Kundenfelder vor, Anzeige «Verknüpft mit» mit Entfernen-Button; Vorbelegung ohne JS über `?customer_id=`
  - Automatische Zuordnung beim Speichern über `CustomerResolver`: Kein Name → kein Kunde (z. B. Barkauf-Quittung). Ausgewählter Kunde mit gleichem Namen → verknüpft, ausgefüllte Felder aktualisieren den Kunden (leere Felder nie). Sonst Abgleich über gleiche normalisierte Telefonnummer **oder** E-Mail **oder** Adresse (Strasse + PLZ) **und** gleicher Name; genau ein Treffer → verknüpfen, sonst neuer Kunde
  - Dokumente speichern die Kundendaten als **Momentaufnahme** weiter; PDFs, Detailansichten und Export lesen daraus. Änderungen am Kunden wirken nur auf den Kunden, nie auf bestehende Dokumente
  - Zusammenführen zweier Kunden (`CustomerMergeService`): Dokumente werden umgehängt, leere Felder ergänzt, Notizen aneinandergehängt, Quelle gelöscht
  - Excel-Export: Spalte «Kunden-Nr.» (= `customers.id`) in den Blättern mit Kundendaten; Detailansichten verlinken den Kundennamen
  - Altdaten: einmaliger Import per `bin/import-customers.php` mit denselben Regeln (siehe Deployment)
  - Nicht im Umfang: Kunden löschen, Datenschutz-Löschfristen, Umhängen eines einzelnen Dokuments, Marketing-Felder
- **Reparaturen** (`RepairController`, `RepairService`, Tabellen `repairs`, `repair_events`):
  - Reparatur ist ein Dokument (`doc_type = 'repair'`, Nummer `RP-YYYY-NNNNNN`) und anders als die übrigen Dokumente **bearbeitbar**; kein Löschen (stattdessen «Storniert»)
  - Erfassungsformular mit Kunden-Picker (Zuordnung über `CustomerResolver`), Gerät, IMEI/Seriennummer, Schadenstyp, Fehlerbeschreibung, geplanten Arbeiten, Preis, «fertig bis», Zubehör, Entsperrcode. Beim Bearbeiten wird die Momentaufnahme in `documents` überschrieben
  - Status: Angenommen → In Arbeit → Wartet auf Ersatzteil → Abholbereit → Abgeholt, zusätzlich Storniert; Wechsel frei in jede Richtung (`Repair::STATUS_*`). «Offen» = alle ausser Abgeholt/Storniert
  - Regeln beim Statuswechsel (`RepairService::changeStatus`): gleicher Status → nichts; Ereignis schreiben; Abgeholt/Storniert → Entsperrcode löschen und `closed_at` setzen; offener Status → `closed_at` leeren; Wechsel auf Abholbereit → E-Mail (nach der Transaktion, ein Mailfehler rollt den Status nicht zurück)
  - Liste `/repairs` (Standard: offene; Filter Filiale, Status, «alle»; nach «fertig bis» sortiert, überfällige rot; 25 pro Seite), Detailansicht mit Statuswechsel + Notiz, Verlauf (`repair_events`), PDF-Auftragsbestätigung (mit QR-Code zur Statusseite), «Status-Link kopieren», E-Mails erneut senden
  - **Öffentliche Statusseite** `/r/{token}` (ohne Login, Token 32 Hex-Zeichen): zeigt nur Nummer, Gerät, Status, Verlauf (Status + Datum), «fertig bis», voraussichtlichen Preis und Filialkontakt; nie Kundendaten, IMEI, Entsperrcode oder Notizen (Template erhält nur das Array aus `RepairStatusPage`). Unbekannter Token → 404; abgeschlossene Reparatur mit `closed_at` älter als 30 Tage → 410. Header `X-Robots-Tag: noindex, nofollow`, `Referrer-Policy: no-referrer`
  - E-Mails (`RepairMailer`, nur mit Kunden-E-Mail): beim Anlegen «Erfasst» (Status-Link, Auftragsbestätigung als PDF-Anhang), beim Wechsel auf Abholbereit «Abholbereit». Ergebnis je Versuch als Ereignis `email_sent`/`email_failed`; schlägt der Versand fehl, ist die Reparatur trotzdem gespeichert (gelber Hinweis)
  - Excel-Export: Blatt «Reparaturen» (ohne Entsperrcode); Reparaturen erscheinen auch in der Dokumentliste der Kunden-Detailansicht
  - Nicht im Umfang: Reparatur aus Kostenvoranschlag starten, Umwandlung in Quittung/Rechnung, Ersatzteil-/Lagerverwaltung, SMS, Kundenportal
- **E-Mail-Versand**: zentral über `MailService` (siehe Abschnitt «E-Mail-Versand»)
- **Filialen-System**: Clara & Reinach mit separaten Adressen

### ⏳ Noch zu implementieren
- **Erweiterte Dokumentenverwaltung**: Archiv
- **Tests**: Smoke-Tests für Controller/Use-Cases (Services der Rechnungen sind mit PHPUnit abgedeckt)

## Datenmodell (Implementiert)

### Tabellen-Schema
```sql
users (id, name, email [unique], password_hash, is_active, created_at, updated_at)
branches (id, name, street, zip, city, phone, email, created_at, updated_at)
documents (id, doc_type ENUM["estimate","purchase","insurance","receipt","invoice","repair"], doc_number UNIQUE, branch_id FK, user_id FK, customer_id FK NULL, customer_name, customer_phone, customer_email, created_at, updated_at)
estimates (id, document_id FK UNIQUE, device_name, serial_number, damage_type ENUM, issue_text TEXT, price_chf DECIMAL(10,2))
purchases (id, document_id FK UNIQUE, seller_street, seller_zip, seller_city, device_type, device_brand, device_model, imei, serial_number, device_condition, accessories, purchase_price_chf DECIMAL(10,2), id_document_front, id_document_back)
receipts (id, document_id FK UNIQUE, notes TEXT, total_amount DECIMAL(10,2))
receipt_items (id, receipt_id FK, item_description, quantity INT, unit_price DECIMAL(10,2), line_total DECIMAL(10,2))
invoices (id, document_id FK UNIQUE, company_name, contact_name, invoice_date, payment_term_days, due_date, total_net_chf, total_vat_chf, total_gross_chf, qr_reference)
invoice_items (id, invoice_id FK, position, description, quantity DECIMAL(10,2), unit_price_gross_chf, vat_rate DECIMAL(4,2), line_total_gross_chf)
customers (id, company_name, name, phone, phone_normalized, email, street, zip, city, notes, created_at, updated_at)
repairs (id, document_id FK UNIQUE, status ENUM["received","in_progress","waiting_parts","ready","picked_up","cancelled"], status_token CHAR(32) UNIQUE, device_name, imei_serial, damage_type, issue_text, work_text, price_chf, due_date, accessories, unlock_code, closed_at, created_at, updated_at)
repair_events (id, repair_id FK, event_type ENUM["status","email_sent","email_failed","note"], status, message, user_id FK NULL, created_at)
```

Rechnungen: `documents.customer_name` enthält den Anzeigenamen (Firma, sonst Name), `customer_street/zip/city` die Empfängeradresse (`customer_zip` seit Migration 013 VARCHAR(16)), `branch_id` ist immer Reinach.

Reparaturen (Migration 015): `repairs` ist die Detailtabelle zu `documents` (Nummer `RP-YYYY-NNNNNN`). `status_token` (128 Bit, `bin2hex(random_bytes(16))`) ist der Schlüssel der öffentlichen Statusseite. `closed_at` wird bei Abgeholt/Storniert gesetzt (Grundlage der 30-Tage-Gültigkeit der Statusseite) und beim Wiedereröffnen geleert. `unlock_code` wird bei Abgeholt/Storniert gelöscht und erscheint nie in PDF, E-Mail, Statusseite, Excel-Export oder Log. `repair_events` ist Statusverlauf und Protokoll in einem; auf der Statusseite sind nur Ereignisse `status` (Status + Datum, nie `message`) sichtbar.

Kundenstamm (Migration 014): `documents.customer_id` ist ein nullable FK auf `customers.id` (`ON DELETE SET NULL`). Die Kundenfelder in `documents` (sowie `purchases.seller_*` und `insurance_assessments.customer_*`) bleiben als Momentaufnahme bestehen. `customers.phone_normalized` ist die normalisierte Telefonnummer (`PhoneNormalizer`) für den Abgleich. Keine UNIQUE-Bedingung auf Telefon/E-Mail; Duplikate löst das Zusammenführen.

### Design-Prinzip
- **Generische Dokumente**: `documents` als Basis-Tabelle
- **Typ-spezifische Details**: `estimates`, `purchases`, `receipts`, `invoices`, `repairs` je als Detail-Tabelle
- **Eindeutige Dokumentnummern**: KO-YYYY-NNNNNN, AK-YYYY-NNNNNN, QU-YYYY-NNNNNN, VG-YYYY-NNNNNN, RP-YYYY-NNNNNN (Reparatur), Rechnungen RE-JJJJ-MM-TT-NN (z. B. RE-2026-10-12-01)
- **Nummernvergabe**: "höchste bestehende Nummer + 1" (nicht mehr COUNT+1, damit gelöschte Dokumente keine Duplikate erzeugen); bei Kollision des UNIQUE-Index bis zu 3 Wiederholungen

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
TWIG_CACHE=false   # false/leer = kein Cache, true = var/cache/twig, sonst Pfad (relativ ab Projektverzeichnis)
SESSION_NAME=mediahuus_crm_session

# Mail (alle E-Mails laufen über MailService)
MAILER_DSN=        # leer = kein Versand (nur Log); Mailgun: mailgun+https://KEY:DOMAIN@default?region=eu; lokal: smtp://localhost:1025 (DDEV Mailpit)
MAIL_FROM=info@mediahuus.ch
MAIL_FROM_NAME="Mediahuus"
APP_URL=https://crm.mediahuus.ch   # Basis für Links in E-Mails und QR-Code
```

## E-Mail-Versand

**Regel: Alle E-Mails des Systems (jetzt und künftig) laufen über `App\Services\MailService::send()`** (Symfony Mailer, Mailgun via `MAILER_DSN`).

- Kein `mail()`, kein eigener SMTP-Code, keine zweite Mailer-Instanz.
- `MailService` setzt den Absender (`MAIL_FROM`, `MAIL_FROM_NAME`); Aufrufer setzen Empfänger, Betreff, HTML, Text und Anhänge. `send()` liefert ein `MailResult` (gesendet / nicht konfiguriert / fehlgeschlagen).
- `MailService` wirft nie in den aufrufenden Code: Transportfehler werden gefangen, geloggt (error) und als Ergebnis «fehlgeschlagen» zurückgegeben. Die Fachlogik (z. B. Reparatur speichern) muss auch ohne funktionierenden Versand vollständig laufen.
- Leere `MAILER_DSN` = kein Versand, nur Log-Eintrag (info), Ergebnis «nicht konfiguriert».
- Lokal: `MAILER_DSN=smtp://localhost:1025` (DDEV Mailpit).
- Neue E-Mails: Templates in `templates/emails/` (HTML + Text, Basis `base.html.twig`), eigene Mailer-Klasse nach dem Muster `RepairMailer`, Links über `AppUrl`.

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
ddev exec php bin/import-customers.php --dry-run   # Kunden-Altdatenimport (Testlauf)

# Tests
ddev exec vendor/bin/phpunit   # PHPUnit Tests

# Development Tools (geplant)
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
- **CSRF-Schutz**: `CsrfMiddleware` prüft alle POST-Requests auf ein gültiges Token (Formularfeld `_csrf` oder Header `X-CSRF-Token`, Vergleich mit `hash_equals`); das Token liegt pro Session in `_csrf_token`. Ungültig/fehlend → 403-Seite (bzw. JSON `{"success":false,"error":"csrf"}` bei Header-/`Accept: application/json`-Requests). Überschreitet `Content-Length` `post_max_size`, antwortet die Middleware vor der Token-Prüfung mit 413. Ein abgelaufenes Login-Formular (`POST /login` mit ungültigem Token) zeigt das Login-Formular erneut (403) mit Hinweis «Ihre Sitzung ist abgelaufen. Bitte erneut anmelden.» und der eingegebenen E-Mail. Das Token wird in `AuthController` nach `startSession()`/`endSession()` per `CsrfTokenManager::regenerate()` erneuert.
- **Session-Cookie**: Name aus `SESSION_NAME`, `HttpOnly`, `SameSite=Lax`, `Secure` bei HTTPS, `session.use_strict_mode=1`.
- **Neue POST-Formulare** brauchen direkt nach `<form method="POST" …>` die Zeile `{{ csrf_field() }}`; **fetch-POSTs** den Header `X-CSRF-Token` (Wert aus `<meta name="csrf-token">` in `base.html.twig`).

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

### Rechnungen
```
GET  /invoices/create        → Rechnungsformular (Live-Vorschau)
POST /invoices               → Speichern + Redirect
GET  /invoices/{id}          → Detailansicht
GET  /invoices/{id}/pdf      → PDF-Download (Rechnung mit QR-Zahlteil)
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

### Reparaturen
```
GET  /repairs                  → Liste (Standard: offene; Filter Filiale, Status, alle)
GET  /repairs/create           → Formular
POST /repairs                  → Speichern + Redirect auf Detail
GET  /repairs/{id}             → Detailansicht (Verlauf, Statuswechsel)
GET  /repairs/{id}/edit        → Bearbeiten
POST /repairs/{id}             → Aktualisieren
POST /repairs/{id}/status      → Status ändern (Felder status, note)
POST /repairs/{id}/send-email  → E-Mail erneut senden (Feld type = created|ready)
GET  /repairs/{id}/pdf         → PDF-Download (Auftragsbestätigung)
GET  /r/{token}                → ÖFFENTLICH (ohne Login): Statusseite für Kunden
```

### Kundenstamm
```
GET  /customers                    → Kundenliste mit Suche (25 pro Seite)
GET  /customers/search?q=          → JSON für den Kunden-Picker (max. 10 Treffer)
GET  /customers/create             → Neuer Kunde
POST /customers                    → Kunde speichern
GET  /customers/{id}               → Detailansicht (Stammdaten, Notizen, Dokumente)
GET  /customers/{id}/edit          → Kunde bearbeiten
POST /customers/{id}               → Kunde aktualisieren
GET  /customers/{id}/merge?target= → Bestätigungsseite Zusammenführen
POST /customers/{id}/merge         → Zusammenführen (Feld target_id)
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

### Versicherungsgutachten & Export
```
GET  /insurance/create       → Formular
POST /insurance              → Speichern
GET  /insurance/{id}         → Detailansicht
GET  /insurance/{id}/pdf     → PDF-Download
GET  /export/excel           → Excel-Export
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

### 🎯 Sprint 4: Versicherungsgutachten (Formulare und PDF umgesetzt)
- [✅] Gutachten-Formulare
- [✅] PDF-Layout für Versicherungen
- [ ] Archivierungs-System

### ✅ Sprint 5: Rechnungen (Swiss QR-Rechnung)
- [✅] Rechnungsformular mit Live-Vorschau, MWST pro Position
- [✅] PDF mit QR-Zahlteil (sprain/swiss-qr-bill, SCOR-Referenz)
- [✅] Dashboard-Kachel, Excel-Export
- [✅] PHPUnit-Tests für Rechnungs-Services

### ✅ Kundenstamm (Feature-Branch `feat/customers`)
- [✅] Tabelle `customers` und `documents.customer_id` (Migration 014)
- [✅] Automatische Kundenzuordnung beim Speichern (`CustomerResolver`, `PhoneNormalizer`)
- [✅] Kunden-Picker und Vorbelegung in allen fünf Erfassungsformularen
- [✅] Kundenliste, Suche, Detailansicht, Bearbeiten, Zusammenführen
- [✅] Dokumentdetails und Excel-Export mit Kunden-Nr.
- [✅] Altdaten-Import (`bin/import-customers.php`), PHPUnit-Tests für Resolver, Merge, Normalisierung und Feldzuordnung

### ✅ Reparaturen (Feature-Branch `feat/repairs`)
- [✅] Migration 015, `RepairService` mit Statusregeln, Eingabevalidierung
- [✅] Erfassen, Bearbeiten, Liste mit Filtern, Detailansicht mit Statusverlauf
- [✅] PDF-Auftragsbestätigung mit QR-Code, Navigation, Dashboard, Kundendetail, Excel-Export
- [✅] Öffentliche Statusseite `/r/{token}`
- [✅] Zentraler `MailService` (Mailgun) und Reparatur-E-Mails, PHPUnit-Tests
- [ ] Mailgun-Domain einrichten (EU-Region, SPF/DKIM) und `MAILER_DSN` setzen

## Deployment

- **Lokal**: DDEV Container-Environment
- **Staging/Produktion**: TBD (noch zu definieren); auf Produktion ist kein Composer verfügbar: `vendor/` wird lokal gebaut (`composer install --no-dev --optimize-autoloader`) und hochgeladen
- **Rollout CSRF/Session-Härtung**: Beim ersten Deployment werden alle Benutzer einmalig abgemeldet (neue Session-Konfiguration) und müssen sich neu anmelden.
- **Rollout Kundenstamm** (in dieser Reihenfolge):
  1. DB-Backup erstellen (SQL-Dump)
  2. Code und `vendor/` hochladen
  3. `php bin/migrate.php` (Migration 014: Tabelle `customers`, Spalte `documents.customer_id`)
  4. `php bin/import-customers.php --dry-run` prüfen (Testlauf in Transaktion mit Rollback, zeigt Anzahl neuer Kunden und verknüpfter Dokumente)
  5. `php bin/import-customers.php` ausführen. Wiederholbar: verarbeitet nur Dokumente ohne `customer_id`, ältestes zuerst, damit die neuesten Angaben gewinnen. Barkauf-Quittungen ohne Kundennamen bleiben ohne Kunde.
- **Rollout Reparaturen** (in dieser Reihenfolge):
  1. DB-Backup erstellen (SQL-Dump)
  2. `vendor/` lokal mit `composer install --no-dev --optimize-autoloader` bauen und mit dem Code hochladen (neue Pakete `symfony/mailer`, `symfony/mailgun-mailer`; `symfony/http-client` wird für Mailgun über HTTPS benötigt)
  3. `php bin/migrate.php` (Migration 015: `doc_type` 'repair', Tabellen `repairs`, `repair_events`)
  4. `.env` auf Produktion ergänzen: `APP_URL`, `MAIL_FROM`, `MAIL_FROM_NAME`; `MAILER_DSN` bleibt leer, bis Mailgun eingerichtet ist (Reparaturen funktionieren bis dahin ohne E-Mails, der Status-Link lässt sich in der Detailansicht kopieren)
  5. Später: Mailgun-Domain einrichten (EU-Region, SPF/DKIM), `MAILER_DSN` setzen, Test-E-Mail über «Erfassungs-E-Mail erneut senden» prüfen
- **Composer**: `composer.lock` ist versioniert; `config.platform.php = 8.2` stellt sicher, dass nur mit PHP 8.2 kompatible Versionen aufgelöst werden
- **Database**: MariaDB mit SQL-Dumps für Backup
- **Twig-Cache**: `TWIG_CACHE` wird von `TwigCacheConfig` ausgewertet (`false` → kein Cache, `true` → `var/cache/twig`, sonst Pfad). `auto_reload` ist immer aktiv, nach einem Deploy werden geänderte Templates also ohne manuelles Leeren neu kompiliert. «Cache leeren» (`POST /clear-cache`) löscht `var/cache/twig`, den konfigurierten Pfad und das frühere Verzeichnis `public/false`; Letzteres kann auf Produktion nach dem Deploy einmalig gelöscht werden.

## Filialen

- **Mediahuus Clara**: Hauptfiliale
- **Mediahuus Reinach**: Zweigstelle

(Adressen und Kontaktdaten in database/migrations definiert)

## Nächste Schritte
1. **Archivierung**: Archiv für Dokumente
2. **Tests erweitern**: Smoke-Tests für Login, Dokument-Erstellung, PDF-Generation
3. **Code-Quality**: PHPStan-Setup, PHPCS-Konfiguration
4. **Erweiterte Dokumentenverwaltung**: Archive
5. **Deployment**: Staging/Produktions-Setup definieren

---

**Status:** Kostenvoranschläge, Ankauf, Quittungen, Versicherungsgutachten, Rechnungen, Kundenstamm und Reparaturen (inkl. zentralem E-Mail-Versand) umgesetzt
**Nächste Iteration:** Archivierung / erweiterte Dokumentenverwaltung
- immer das gleiche gürn für icons und buttons verwenden, das wir sonst auch überall bei dem projekt nutzen