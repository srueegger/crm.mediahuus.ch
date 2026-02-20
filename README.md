# Mediahuus CRM

Internes CRM-System für die Mediahuus Handy-An-/Verkauf & Reparatur-Shops.

## Features

- ✅ Login/Logout für Mitarbeitende
- ✅ Dashboard mit Navigation
- ✅ Kostenvoranschläge erstellen & als PDF exportieren
- ✅ Ankauf (Geräte-Einkauf) mit Ausweis-Fotografie & IMEI-Scanner
- ✅ Quittungen (Verkaufsbelege) mit Artikelliste
- ✅ PDF-Generierung für alle Dokumenttypen
- ✅ Benutzerverwaltung (CRUD)
- ✅ Filialen-System (Clara & Reinach)
- ⏳ Versicherungsgutachten (geplant)

## Tech Stack

- **Framework**: Slim 4 (Performance & Einfachheit)
- **Template Engine**: Twig 3 (Security & DX)
- **Database**: Doctrine DBAL + MariaDB
- **PDF Generation**: TCPDF
- **Frontend**: Tailwind CSS + Vanilla JS
- **DI Container**: PHP-DI
- **Barcode Scanner**: QuaggaJS (IMEI-Scan via Kamera)

## Lokale Entwicklung mit DDEV

### Voraussetzungen

- DDEV installiert
- Git

### Setup

```bash
# Repository klonen
git clone <repo-url> mediahuus-crm
cd mediahuus-crm

# DDEV Container starten
ddev start

# Dependencies installieren
ddev composer install

# Environment-Datei erstellen
cp .env.example .env

# Database-Migrations ausführen
ddev composer migrate

# Test-Daten laden
ddev composer seed

# Browser öffnen
ddev launch
```

### Verfügbare Befehle

```bash
# Container-Management
ddev start              # Container starten
ddev stop               # Container stoppen
ddev launch             # Browser öffnen

# Dependencies
ddev composer install   # Dependencies installieren
ddev composer update    # Dependencies aktualisieren

# Database
ddev composer migrate   # Migrations ausführen
ddev composer seed      # Test-Daten laden

# System
ddev logs -f            # Logs anzeigen
ddev ssh                # Shell in Container
```

## Demo-Zugang

```
E-Mail: admin@mediahuus.ch
Passwort: admin123
```

## Projektstruktur

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
├── Models/             # Datenmodelle
├── Repositories/       # Datenzugriff
├── Services/           # Business Logic
│   ├── AuthService.php
│   ├── DamageTypeService.php
│   ├── FileUploadService.php
│   └── PdfService.php
└── Middleware/
    └── AuthMiddleware.php

config/
├── container.php       # DI-Container-Konfiguration
└── routes.php          # Route-Definitionen

database/
├── migrations/         # SQL Migrations (001-009)
└── backups/            # SQL Dumps

public/
├── index.php           # Einsprungspunkt
├── assets/             # CSS, JS, Images
└── uploads/            # User Uploads (ID-Dokumente)

templates/
├── auth/               # Login
├── estimates/          # Kostenvoranschläge
├── purchases/          # Ankauf
├── receipts/           # Quittungen
├── users/              # Benutzerverwaltung
├── base.html.twig      # Layout-Template
└── dashboard.html.twig
```

## Entwicklung

### Git-Workflow

1. Feature-Branch erstellen: `git checkout -b feat/feature-name`
2. Änderungen committen (Conventional Commits)
3. Push und Pull Request erstellen
4. Review und Merge in `main`

### Conventional Commits

```bash
feat: add estimate form validation
fix: resolve PDF generation encoding issue
chore: update dependencies
docs: improve setup instructions
```

## Filialen

- **Mediahuus Clara**: Hauptfiliale
- **Mediahuus Reinach**: Zweigstelle

(Adressen und Kontaktdaten in database/migrations definiert)
