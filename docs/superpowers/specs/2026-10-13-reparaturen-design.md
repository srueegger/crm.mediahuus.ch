# Reparaturen – Design

Datum: 2026-10-13
Status: Entwurf zur Durchsicht

## Ziel

Reparaturen werden heute nicht erfasst; es gibt nur den Kostenvoranschlag. Neu soll man eine
**Reparatur** anlegen (Kunde, Gerät, was gemacht wird), ihren **Status** laufend pflegen und die
**offenen Reparaturen** jederzeit im Blick haben. Der Kunde erhält per E-Mail einen **Link zu einer
Statusseite** ohne Login und eine E-Mail, sobald das Gerät **abholbereit** ist.

Erfolgskriterien:

- Eine Reparatur lässt sich in einem Formular erfassen; Kunde wird wie in den anderen Formularen über
  den Kunden-Picker gewählt und über den `CustomerResolver` dem Kundenstamm zugeordnet.
- Die Liste `/repairs` zeigt standardmässig alle offenen Reparaturen, überfällige sind sofort erkennbar.
- Der Status lässt sich in der Detailansicht mit einem Klick ändern; der Verlauf ist nachvollziehbar.
- Hat der Kunde eine E-Mail-Adresse, erhält er beim Anlegen eine E-Mail mit Status-Link und
  Auftragsbestätigung (PDF) und beim Wechsel auf «Abholbereit» eine zweite E-Mail.
- Die Statusseite zeigt keine Personendaten; wer den Link findet, erfährt nichts über den Kunden.
- Fehlt die Mail-Konfiguration oder schlägt der Versand fehl, funktioniert die Reparaturverwaltung
  trotzdem vollständig.

## Entscheide

| Thema | Entscheid |
|---|---|
| Einordnung | Reparatur ist ein **Dokument**: `documents.doc_type = 'repair'`, Detailtabelle `repairs` |
| Nummer | `RP-JJJJ-NNNNNN` über `DocumentRepository::generateDocNumber()` (Präfix `RP`) |
| Bearbeitbarkeit | Anders als die übrigen Dokumente ist eine Reparatur **bearbeitbar** und hat einen Status |
| Status | Angenommen → In Arbeit → Wartet auf Ersatzteil → Abholbereit → Abgeholt, zusätzlich Storniert; Wechsel frei in jede Richtung |
| Offen | alle Status ausser «Abgeholt» und «Storniert» |
| Statusseite | öffentlich unter `/r/{token}`, Token 32 Zeichen zufällig, zeigt Preis und «fertig bis» |
| E-Mail-Auslöser | automatisch beim Anlegen und beim Wechsel auf «Abholbereit», manuell erneut sendbar |
| E-Mail-Versand | **ein** zentraler `MailService` (Symfony Mailer, Mailgun-Transport) für **alle** E-Mails des Systems |
| Entsperrcode | nur intern sichtbar, wird bei «Abgeholt»/«Storniert» gelöscht |
| Berechtigung | alle angemeldeten Mitarbeitenden (es gibt keine Rollen) |

## Nicht im Umfang

- Reparatur aus einem Kostenvoranschlag starten (kommt später)
- Reparatur in Quittung/Rechnung umwandeln
- Ersatzteil-Bestellung, Lager, Arbeitszeiterfassung
- SMS-Benachrichtigung, E-Mail bei jedem Statuswechsel
- Kunden-Login, Kundenportal mit mehreren Reparaturen
- Löschen von Reparaturen (Storniert statt Löschen)
- Mailgun-Konto und DNS (SPF/DKIM) einrichten: erfolgt später ausserhalb des Codes

## Datenmodell

Migration `database/migrations/015_create_repairs_tables.sql`:

```sql
ALTER TABLE documents
    MODIFY COLUMN doc_type ENUM('estimate', 'purchase', 'insurance', 'receipt', 'invoice', 'repair') NOT NULL;

CREATE TABLE repairs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    document_id INT NOT NULL UNIQUE,
    status ENUM('received', 'in_progress', 'waiting_parts', 'ready', 'picked_up', 'cancelled')
        NOT NULL DEFAULT 'received',
    status_token CHAR(32) NOT NULL UNIQUE,
    device_name VARCHAR(255) NOT NULL,
    imei_serial VARCHAR(64) NULL,
    damage_type VARCHAR(50) NULL,
    issue_text TEXT NOT NULL,
    work_text TEXT NULL,
    price_chf DECIMAL(10,2) NULL,
    due_date DATE NULL,
    accessories VARCHAR(255) NULL,
    unlock_code VARCHAR(100) NULL,
    closed_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE,
    INDEX idx_status (status),
    INDEX idx_due_date (due_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE repair_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    repair_id INT NOT NULL,
    event_type ENUM('status', 'email_sent', 'email_failed', 'note') NOT NULL,
    status VARCHAR(20) NULL,
    message VARCHAR(500) NULL,
    user_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (repair_id) REFERENCES repairs(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_repair_created (repair_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

- Kundendaten (Name, Telefon, E-Mail) liegen wie bei allen Dokumenten als Momentaufnahme in
  `documents.customer_*`, Verknüpfung über `documents.customer_id`.
- `damage_type` verwendet die Schlüssel aus `DamageTypeService::DAMAGE_TYPES`.
- `closed_at` wird beim Wechsel auf «Abgeholt» oder «Storniert» gesetzt und beim Zurücksetzen auf
  einen offenen Status wieder geleert. Grundlage für die 30-Tage-Gültigkeit der Statusseite.
- `repair_events` ist Statusverlauf und Protokoll in einem. Ereignisse mit `event_type = 'status'`
  sind auf der Statusseite sichtbar (nur Status und Datum, nie `message`), alle anderen nur intern.

### Statuswerte und Anzeigenamen

| Wert | Anzeige |
|---|---|
| `received` | Angenommen |
| `in_progress` | In Arbeit |
| `waiting_parts` | Wartet auf Ersatzteil |
| `ready` | Abholbereit |
| `picked_up` | Abgeholt |
| `cancelled` | Storniert |

## Komponenten

| Einheit | Aufgabe |
|---|---|
| `Models/Repair.php` | Datenobjekt inkl. Statuskonstanten, `isOpen()`, Anzeigename je Status |
| `Models/RepairEvent.php` | Datenobjekt eines Verlaufseintrags |
| `Repositories/RepairRepository.php` | CRUD `repairs`, `findOpen(filters)`, `findByToken()`, Zählungen fürs Dashboard |
| `Repositories/RepairEventRepository.php` | Einträge schreiben und je Reparatur lesen |
| `Services/RepairService.php` | Anlegen (Dokument + Nummer + Token + Kunde + erstes Ereignis), Bearbeiten, Statuswechsel mit Regeln, Auslösen der E-Mails |
| `Services/RepairStatusTokenGenerator.php` | `bin2hex(random_bytes(16))` |
| `Services/RepairStatusPage.php` | Baut aus Reparatur, Ereignissen und Filiale das reduzierte Array für die Statusseite und prüft die 30-Tage-Regel |
| `Services/RepairPdfService.php` | Auftragsbestätigung (Layout wie Kostenvoranschlag, TCPDF) |
| `Services/RepairMailer.php` | Baut die zwei Reparatur-E-Mails und übergibt sie dem `MailService` |
| `Services/MailService.php` | **Einziger** Versandweg für alle E-Mails des Systems |
| `Controllers/RepairController.php` | Interne Seiten (Liste, Erfassen, Detail, Bearbeiten, Status, PDF, E-Mail erneut senden) |
| `Controllers/RepairStatusController.php` | Öffentliche Statusseite |

### Regeln beim Statuswechsel (`RepairService::changeStatus`)

1. Gleicher Status wie bisher → keine Änderung, kein Ereignis.
2. Ereignis `status` mit neuem Status, optionaler Notiz und Benutzer schreiben.
3. Neuer Status `picked_up` oder `cancelled` → `unlock_code = NULL`, `closed_at = NOW()`.
4. Neuer Status offen → `closed_at = NULL`.
5. Neuer Status `ready` und Kunden-E-Mail vorhanden → E-Mail «abholbereit» senden.
6. Schritte 2–4 laufen in einer Transaktion; der Versand (5) erst danach, damit ein Mailfehler die
   Statusänderung nicht zurückrollt.

## Seiten und Routen

### Intern (hinter `AuthMiddleware`)

```
GET  /repairs                    → Liste (Standard: offene), Filter Filiale, Status, «alle»
GET  /repairs/create             → Formular
POST /repairs                    → Speichern + Redirect auf Detail
GET  /repairs/{id}               → Detailansicht
GET  /repairs/{id}/edit          → Bearbeiten
POST /repairs/{id}               → Aktualisieren
POST /repairs/{id}/status        → Status ändern (Felder status, note)
POST /repairs/{id}/send-email    → E-Mail erneut senden (Feld type = created|ready)
GET  /repairs/{id}/pdf           → Auftragsbestätigung als PDF
```

**Formular (Erfassen und Bearbeiten):** Filiale, Kunden-Picker (`customers/_picker.html.twig`) mit
Name, Telefon, E-Mail; Gerät (Pflicht), IMEI/Seriennummer, Schadenstyp, Fehlerbeschreibung (Pflicht),
geplante Arbeiten, voraussichtlicher Preis CHF, fertig bis, Zubehör, Entsperrcode. Kundenname ist
Pflicht. Beim Bearbeiten läuft der `CustomerResolver` erneut; die Momentaufnahme in `documents` wird
mit den neuen Angaben überschrieben (eine Reparatur ist ein laufender Vorgang, kein abgeschlossener
Beleg).

**Liste:** Spalten Nummer, Kunde, Gerät, Status (farbiges Badge), fertig bis, Filiale, angelegt am.
Sortierung nach «fertig bis» aufsteigend, ohne Datum am Ende. Offene Reparaturen mit «fertig bis»
in der Vergangenheit sind rot markiert. 25 pro Seite.

**Detailansicht:** alle Angaben inkl. Entsperrcode; Statuswechsel per Auswahl + optionaler Notiz;
Verlauf (Datum, Benutzer, Ereignis, Notiz); Buttons Bearbeiten, PDF, «Status-Link kopieren»,
«Erfassungs-E-Mail erneut senden», «Abholbereit-E-Mail erneut senden» (nur wenn E-Mail vorhanden).

**Navigation:** In `NavigationExtension` unter «Erfassen» der Punkt «Reparatur» (`/repairs/create`,
Präfix `/repairs`), unter «Verwalten» der Punkt «Offene Reparaturen» (`/repairs`). Neues Icon
`repair` in `partials/icons.html.twig`, Grün wie die übrigen Icons und Buttons.

**Dashboard:** Hinweis-Kachel mit Anzahl offener und Anzahl abholbereiter Reparaturen, verlinkt auf
`/repairs` bzw. `/repairs?status=ready`.

**Kunde:** Reparaturen erscheinen in der Dokumentliste der Kunden-Detailansicht.

**Excel-Export:** neues Blatt «Reparaturen» (Nummer, Datum, Filiale, Kunden-Nr., Kunde, Telefon,
E-Mail, Gerät, IMEI/Seriennr., Schadenstyp, Status, fertig bis, Preis). Ohne Entsperrcode.

### Öffentlich (ohne Login)

```
GET /r/{token:[a-f0-9]{32}}      → Statusseite
```

- Eigenes schlankes Layout `templates/public/base.html.twig` (Logo, keine Sidebar, mobil zuerst).
- Inhalt: Reparaturnummer, Gerät, aktueller Status als Schrittanzeige (bei «Storniert» stattdessen
  Hinweis), Statusverlauf (nur Status + Datum), fertig bis, voraussichtlicher Preis (Hinweis
  «voraussichtlich, inkl. MWST»), Filiale mit Adresse, Telefon, E-Mail.
- Nie angezeigt: Kundenname, Telefon, E-Mail, IMEI/Seriennummer, Entsperrcode, Notizen, Benutzer.
- Unbekannter Token → 404 mit neutralem Text. Abgeschlossene Reparatur mit `closed_at` älter als
  30 Tage → 410 «Dieser Link ist nicht mehr gültig» mit Filialkontakt.
- Antwort-Header `X-Robots-Tag: noindex, nofollow` und `Referrer-Policy: no-referrer`; Meta
  `robots noindex` im Layout.

## E-Mail

### Zentraler `MailService`

- Abhängigkeiten: `symfony/mailer`, `symfony/mailgun-mailer`.
- **Regel für das ganze Projekt:** Jede E-Mail, die das System verschickt (jetzt und künftig), läuft
  über `MailService`. Kein `mail()`, kein eigener SMTP-Code, keine zweite Mailer-Instanz. Die Regel
  wird in `CLAUDE.md` festgehalten.
- Konfiguration über `.env`:

  ```
  MAILER_DSN=           # z. B. mailgun+https://KEY:DOMAIN@default?region=eu ; leer = kein Versand
  MAIL_FROM=info@mediahuus.ch
  MAIL_FROM_NAME="Mediahuus"
  APP_URL=https://crm.mediahuus.ch   # Basis für Links in E-Mails und QR-Code
  ```

- Schnittstelle: `send(Email $email): MailResult` (Erfolg, Fehlermeldung, «nicht konfiguriert»).
  Absender setzt der Service, Aufrufer setzen Empfänger, Betreff, HTML, Text, Anhänge.
- `MAILER_DSN` leer → nichts senden, Log-Eintrag (info), Ergebnis «nicht konfiguriert».
- Transportfehler → Exception wird gefangen, Log-Eintrag (error), Ergebnis «fehlgeschlagen».
  `MailService` wirft nie in den aufrufenden Code.
- Lokal: DDEV Mailpit über `MAILER_DSN=smtp://localhost:1025`.

### Reparatur-E-Mails (`RepairMailer`)

Nur wenn `documents.customer_email` gesetzt ist. Templates in `templates/emails/` als HTML und
Text, Absender aus `MAIL_FROM`, Antwortadresse = E-Mail der Filiale.

1. **Erfasst** (beim Anlegen): Betreff «Ihre Reparatur RP-… bei Mediahuus»; Gerät, Link zur
   Statusseite, Filialkontakt; Anhang Auftragsbestätigung (PDF).
2. **Abholbereit** (beim Wechsel auf `ready`): Betreff «Ihr Gerät ist abholbereit (RP-…)»; Gerät,
   voraussichtlicher Preis, Filialadresse, Link zur Statusseite.

Ergebnis jedes Versuchs als Ereignis: `email_sent` («Erfassungs-E-Mail an x@y.ch») oder
`email_failed` (inkl. Grund, z. B. «Mailversand nicht konfiguriert»). Bei Fehler zeigt die
Detailansicht nach dem Speichern einen gelben Hinweis; die Reparatur ist trotzdem gespeichert.

## PDF Auftragsbestätigung

Layout wie Kostenvoranschlag (Logo, Filialadresse). Inhalt: Reparaturnummer, Datum, Kunde (Name,
Telefon, E-Mail), Gerät, IMEI/Seriennummer, Schadenstyp, Fehlerbeschreibung, geplante Arbeiten,
Zubehör, voraussichtlicher Preis, fertig bis, QR-Code (TCPDF `write2DBarcode`) mit Link zur
Statusseite samt Hinweis «Status jederzeit online abrufen», Unterschriftenlinien «Abgabe» und
«Abholung». Ohne Entsperrcode.

## Sicherheit

- Alle neuen POST-Formulare mit `{{ csrf_field() }}`.
- Token aus `random_bytes(16)` (128 Bit), Route akzeptiert nur 32 Hex-Zeichen.
- Statusseite liest ausschliesslich die oben genannten Felder; das Template erhält nur das
  reduzierte Array aus `RepairStatusPage`, nie den ganzen Datensatz.
- Entsperrcode erscheint weder in PDF, E-Mail, Statusseite, Excel-Export noch im Log.

## Tests (PHPUnit)

- `RepairServiceTest`: Anlegen erzeugt Nummer, Token, Ereignis «Angenommen» und sendet die
  Erfassungs-E-Mail nur mit Kunden-E-Mail; Statuswechsel schreibt Ereignis, löscht Entsperrcode
  und setzt `closed_at` bei Abgeholt/Storniert, leert `closed_at` beim Wiedereröffnen, sendet
  E-Mail nur beim Wechsel auf Abholbereit; gleicher Status → nichts passiert; Mailfehler rollt
  Status nicht zurück.
- `RepairStatusTokenGeneratorTest`: 32 Hex-Zeichen, keine Wiederholung über viele Aufrufe.
- `RepairStatusPageTest`: Array enthält keine Personendaten und keinen Entsperrcode, Verlauf nur
  Status-Ereignisse ohne Notiz; 30-Tage-Regel nach `closed_at`.
- `MailServiceTest`: leere DSN → «nicht konfiguriert», kein Versand; Transportfehler →
  «fehlgeschlagen», keine Exception; Absender wird gesetzt (mit Symfony `NullTransport` bzw.
  Test-Transport).
- `NavigationExtensionTest` um die neuen Einträge ergänzen.

## Rollout

1. DB-Backup erstellen.
2. `vendor/` lokal mit `composer install --no-dev --optimize-autoloader` bauen und mit dem Code
   hochladen (neue Pakete `symfony/mailer`, `symfony/mailgun-mailer`).
3. `php bin/migrate.php` (Migration 015).
4. `.env` auf Produktion ergänzen: `APP_URL`, `MAIL_FROM`, `MAIL_FROM_NAME`; `MAILER_DSN` bleibt
   leer, bis Mailgun eingerichtet ist (Reparaturen funktionieren bis dahin ohne E-Mails, der
   Status-Link lässt sich in der Detailansicht kopieren).
5. Später: Mailgun-Domain einrichten (EU-Region, SPF/DKIM), `MAILER_DSN` setzen, Test-E-Mail über
   «Erfassungs-E-Mail erneut senden» prüfen.

## Dokumentation

`CLAUDE.md` ergänzen: Modul Reparaturen (Funktionen, Routen, Tabellen, Nummernformat), Regel
«alle E-Mails über `MailService`», neue Umgebungsvariablen, Rollout-Schritte.
