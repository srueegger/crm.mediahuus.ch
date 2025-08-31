# Mediahuus CRM

Internes CRM-System für die Mediahuus Handy-An-/Verkauf & Reparatur-Shops.

## Features (MVP)

- ✅ Login/Logout für Mitarbeitende
- ✅ Dashboard mit Navigation
- 🚧 Kostenvoranschlag erstellen (nächste Iteration)
- 🚧 Dokumenten-Management
- 🚧 PDF-Generierung
- ⏳ Ankauf (geplant)
- ⏳ Versicherungsgutachten (geplant)

## Tech Stack

- **Framework**: Slim 4 (Performance & Einfachheit)
- **Template Engine**: Twig 3 (Security & DX)
- **Database**: Doctrine DBAL + MariaDB
- **PDF Generation**: TCPDF
- **Frontend**: Tailwind CSS + Vanilla JS
- **DI Container**: PHP-DI

Siehe [ADR-0001](docs/adr/0001-tech-choice.md) für Details zur Stack-Entscheidung.

## Lokale Entwicklung mit DDEV

### Voraussetzungen

- DDEV installiert
- Composer verfügbar
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

# Database-Migrations ausführen (wenn vorhanden)
# ddev composer migrate

# Browser öffnen
ddev launch
```

### Verfügbare Befehle

```bash
# Container starten
ddev start

# Container stoppen
ddev stop

# Composer-Befehle
ddev composer install
ddev composer update

# Database-Befehle (zukünftig)
ddev composer migrate
ddev composer seed

# Code-Quality (zukünftig)
ddev composer phpcs
ddev composer phpstan
ddev composer test

# Browser öffnen
ddev launch

# Logs anzeigen
ddev logs -f

# Shell in Container
ddev ssh
```

## Demo-Zugang

```
E-Mail: admin@mediahuus.ch
Passwort: admin123
```

## Projektstruktur

```
app/
├── Controllers/     # Request-Handler
├── Models/         # Datenmodelle (zukünftig)
├── Services/       # Business Logic (zukünftig)
└── Middleware/     # Request-Middleware

config/
├── container.php   # DI-Container-Konfiguration
└── routes.php      # Route-Definitionen

database/
├── migrations/     # Database-Migrations (zukünftig)
└── seeds/         # Test-Daten (zukünftig)

public/
├── index.php      # Einsprungspunkt
└── assets/        # Statische Assets (Logo, CSS, JS)

templates/
├── auth/          # Login-Templates
├── base.html.twig # Layout-Template
└── dashboard.html.twig

docs/
└── adr/          # Architecture Decision Records
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

## Roadmap

### Sprint 1: Kostenvoranschlag (aktuell)
- [ ] Database-Migrations (users, branches, documents, estimates)
- [ ] Kostenvoranschlag-Formular
- [ ] PDF-Generierung mit Logo & Filial-Adressen
- [ ] Dokumenten-Liste mit Filtern
- [ ] Basic Tests

### Sprint 2: Ankauf
- [ ] Ankauf-Formular
- [ ] PDF-Templates erweitern
- [ ] Erweiterte Filiale-Features

### Sprint 3: Versicherungsgutachten
- [ ] Gutachten-Formular
- [ ] Spezielle PDF-Layouts
- [ ] Archivierung

## Filialen

- **Mediahuus Clara**: Placeholder Adresse
- **Mediahuus Reinach**: Placeholder Adresse

(Adressen werden in Database-Seeds definiert)

## Support

Für Fragen und Issues:
- Interne Dokumentation: `/docs/`
- Code-Review: Pull Requests
- Deployment: DDEV → Produktion (TBD)