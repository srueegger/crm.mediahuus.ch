# Kundenstamm – Design

Datum: 2026-10-13
Status: Entwurf zur Durchsicht

## Ziel

Kundendaten werden heute pro Dokument erfasst und gespeichert. Ein **Kundenstamm** soll zwei Dinge
gleichwertig ermöglichen:

1. **Schneller erfassen:** Stammkunden im Formular suchen, Daten werden übernommen statt neu getippt.
2. **Kundenhistorie:** Alle Dokumente eines Kunden (Kostenvoranschläge, Ankäufe, Quittungen,
   Gutachten, Rechnungen) auf einer Seite.

Erfolgskriterien:

- In jedem der fünf Formulare lässt sich ein bestehender Kunde per Name, Telefon oder E-Mail finden
  und mit einem Klick übernehmen.
- Jedes neue Dokument mit Kundennamen ist nach dem Speichern einem Kunden zugeordnet, ohne dass
  Mitarbeitende etwas zusätzlich tun müssen.
- Bestehende Dokumente in der Produktion sind nach einem einmaligen Import Kunden zugeordnet.
- PDFs und Detailansichten bestehender Dokumente sehen nach der Einführung exakt gleich aus.

## Entscheide

| Thema | Entscheid |
|---|---|
| Verknüpfung | Tabelle `customers` + `documents.customer_id` (nullable FK) |
| Dokumentdaten | Kundenfelder in `documents` (und `purchases.seller_*`, `insurance_assessments.customer_*`) bleiben als **Momentaufnahme**; PDFs, Export und Detailansichten lesen weiter daraus |
| Formular | bleibt wie bisher; Kunde wählen ist optional und füllt nur die Felder vor |
| Zuordnung beim Speichern | automatisch über `CustomerResolver` (Regeln siehe unten) |
| Barkauf-Quittung | Quittung ohne Kundennamen erhält keinen Kunden |
| Abgleich | gleiche normalisierte Telefonnummer **oder** gleiche E-Mail **und** gleicher Name |
| Altdaten | einmaliger Import per CLI-Skript mit denselben Regeln |
| Kundendaten ändern | ändert nur den Kunden, nie bestehende Dokumente |
| Berechtigung | alle angemeldeten Mitarbeitenden (es gibt keine Rollen) |

## Nicht im Umfang

- Kunden löschen, Datenschutz-Löschfristen, Datenexport pro Kunde
- Marketing-Felder (Newsletter-Einwilligung o. ä.)
- Nachträgliches Umhängen eines einzelnen Dokuments zu einem anderen Kunden (nur via Zusammenführen)
- CSRF-Schutz: separat in `feat/csrf` (Spec `2026-10-13-csrf-design.md`), wird vorher gemergt; alle neuen POST-Formulare des Kundenstamms enthalten `{{ csrf_field() }}`

## Datenmodell

Migration `database/migrations/014_create_customers_table.sql`:

```sql
CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(70) NULL,
    name VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NULL,
    phone_normalized VARCHAR(20) NULL,
    email VARCHAR(255) NULL,
    street VARCHAR(255) NULL,
    zip VARCHAR(16) NULL,
    city VARCHAR(255) NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name),
    INDEX idx_phone_normalized (phone_normalized),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE documents
    ADD COLUMN customer_id INT NULL AFTER user_id,
    ADD INDEX idx_customer_id (customer_id),
    ADD CONSTRAINT fk_documents_customer FOREIGN KEY (customer_id)
        REFERENCES customers(id) ON DELETE SET NULL;
```

- Keine UNIQUE-Bedingung auf Telefon/E-Mail (Familien teilen Nummern; Duplikate löst das
  Zusammenführen).
- `name` ist die Person bzw. der Anzeigename, `company_name` optional (Rechnungen).
- Kunden-Nr. in der Oberfläche und im Export = `customers.id`.

### Code

- `App\Models\Customer` (inkl. `getDisplayName()`: «Firma, Name» bzw. nur Name)
- `App\Repositories\CustomerRepository`: `create`, `update`, `findById`, `search(string $q, int $limit)`,
  `paginate(?string $q, int $page, int $perPage)` inkl. Anzahl Dokumente und letztes Dokumentdatum,
  `findMatches(?string $phoneNormalized, ?string $email)`, `delete`
- `Document` und `DocumentRepository` erhalten `customerId` (Konstruktor, `create`, `update`,
  Hydration) sowie `findByCustomerId(int $customerId)` und `reassignCustomer(int $from, int $to)`

## Normalisierung

`App\Services\PhoneNormalizer::normalize(?string): ?string`

- Alle Zeichen ausser Ziffern entfernen; ein führendes `+` wird vorher als `00` gelesen.
- `0041…` → `0…`; Ergebnis leer → `null`.
- Beispiele: `+41 79 123 45 67` → `0791234567`, `079 123 45 67` → `0791234567`,
  `0041791234567` → `0791234567`, `+49 170 1234567` → `00491701234567`.

Namen werden für den Vergleich normalisiert: `trim`, mehrfache Leerzeichen zu einem, `mb_strtolower`.
E-Mails: `trim` + `mb_strtolower`.

## Formular und Ablauf

### Kunden-Picker (alle fünf Erfassungsformulare)

- Twig-Partial `templates/customers/_picker.html.twig` oberhalb der Kundenfelder, Feld
  «Bestehenden Kunden suchen».
- Vanilla-JS `public/assets/js/customer-picker.js`: ab 2 Zeichen (Debounce 250 ms) Aufruf von
  `GET /customers/search?q=…` (JSON, max. 10 Treffer), Trefferliste als Dropdown, Tastatur
  (↑/↓/Enter/Esc) und Maus.
- Auswahl füllt die Formularfelder über eine Zuordnung, die das Formular per `data-*`-Attributen
  am Picker definiert (Feldname im Formular je Kundenattribut), und setzt das versteckte Feld
  `customer_id`. Anzeige «Verknüpft mit: <Name> ✕»; ✕ leert `customer_id` (die Felder bleiben).
- Vorbelegung ohne JS: `…/create?customer_id=42` füllt Felder und `customer_id` serverseitig.

Feldzuordnung je Dokumenttyp:

| Typ | Name | Telefon | E-Mail | Adresse | Firma |
|---|---|---|---|---|---|
| Kostenvoranschlag | `customer_name` | `customer_phone` | `customer_email` | – | – |
| Ankauf | `customer_name` | `customer_phone` | `customer_email` | `seller_street/zip/city` | – |
| Quittung | `customer_name` | – | – | `customer_street/zip/city` | – |
| Gutachten | `customer_name` | `customer_phone` | `customer_email` | `customer_street/zip/city` | – |
| Rechnung | `contact_name` | – | – | `street/zip/city` | `company_name` |

Rechnung: Ist nur eine Firma angegeben, ist der Kundenname die Firma.

### Zuordnung beim Speichern: `App\Services\CustomerResolver`

`resolve(CustomerData $data, ?int $selectedCustomerId): ?int` – `CustomerData` ist ein kleines
Wertobjekt (name, company, phone, email, street, zip, city), das jeder Controller aus seinen Feldern
baut.

1. **Kein Name** (z. B. Barkauf-Quittung) → `null`, kein Kunde.
2. **`customer_id` gesetzt, Kunde existiert, normalisierter Name gleich** → verknüpfen. Im Formular
   ausgefüllte Felder überschreiben die Kundendaten (neueste Angaben gewinnen); leere Felder
   überschreiben nichts. `phone_normalized` wird mitgeführt.
3. **`customer_id` gesetzt, aber Name abweichend** oder Kunde existiert nicht → Auswahl ignorieren,
   weiter mit 4.
4. **Abgleich:** Kandidaten mit gleichem `phone_normalized` oder gleicher E-Mail, davon jene mit
   gleichem normalisiertem Namen. Genau ein Treffer → verknüpfen und wie in 2 aktualisieren.
   Kein oder mehrere Treffer → neuen Kunden anlegen.

### Transaktionen

In `EstimateController`, `PurchaseController`, `ReceiptController`, `InsuranceController` werden
Kundenzuordnung, Dokument und Detaildatensatz in **einer** `Connection::transactional`-Klammer
gespeichert (heute nur bei Rechnungen). `InvoiceService` ruft den Resolver innerhalb seiner
bestehenden Transaktion auf. Ankauf: Datei-Uploads der Ausweisfotos bleiben vor der Transaktion,
wie bisher.

## Kundenverwaltung

Routen (in der bestehenden, per `AuthMiddleware` geschützten Gruppe):

```
GET  /customers                  → Liste mit Suche (25 pro Seite, sortiert nach Name)
GET  /customers/search?q=        → JSON für den Picker (max. 10)
GET  /customers/create           → Formular neuer Kunde
POST /customers                  → Speichern
GET  /customers/{id}             → Detail: Stammdaten, Notizen, alle Dokumente
GET  /customers/{id}/edit        → Bearbeiten
POST /customers/{id}             → Aktualisieren
GET  /customers/{id}/merge?target={id} → Bestätigungsseite Zusammenführen
POST /customers/{id}/merge       → Zusammenführen (Feld target_id)
```

- `App\Controllers\CustomerController`, Templates `templates/customers/{index,show,form,merge}.html.twig`.
- Suche: `name`, `company_name`, `email` per `LIKE %q%`; enthält `q` mindestens 3 Ziffern, zusätzlich
  `phone_normalized LIKE %<normalisierte Ziffern>%`.
- Liste: Name/Firma, Telefon, Ort, Anzahl Dokumente, Datum letztes Dokument.
- Detail: Dokumente neueste zuerst (Typ, Nummer, Datum, Filiale, Links Detail/PDF) und Buttons
  «Neuer Kostenvoranschlag / Quittung / Rechnung / Ankauf / Gutachten» → `…/create?customer_id=`.
- Validierung Kundenformular: Name Pflicht, Telefon-Format wie in den Dokumentformularen,
  E-Mail via `filter_var`.
- Gestaltung mit den vorhandenen Klassen (`mediahuus-input`, `mediahuus-label`,
  `mediahuus-button-primary/-secondary`) und dem im Projekt verwendeten Emerald-Grün für Icons
  und Buttons.

### Zusammenführen: `App\Services\CustomerMergeService`

`merge(int $sourceId, int $targetId): void`, in einer Transaktion:

1. Quelle ≠ Ziel, beide existieren (sonst `InvalidArgumentException`).
2. Alle Dokumente der Quelle → Ziel (`reassignCustomer`).
3. Leere Felder des Ziels aus der Quelle ergänzen; Notizen beider aneinanderhängen
   (getrennt durch Leerzeile).
4. Quelle löschen.

Oberfläche: Auf der Kundenseite «Mit anderem Kunden zusammenführen» → Ziel per Picker wählen →
Bestätigungsseite mit beiden Kunden nebeneinander und Anzahl Dokumente → Bestätigen → Weiterleitung
auf den Zielkunden.

## Einbindung

- Dashboard: Kachel «Kunden» neben «Benutzer».
- Detailansichten der fünf Dokumenttypen: Kundenname verlinkt auf `/customers/{id}`, sofern verknüpft.
- Excel-Export: zusätzliche Spalte «Kunden-Nr.» in den Blättern, die Kundendaten enthalten.

## Altdaten-Import

`bin/import-customers.php [--dry-run]`

- Nimmt alle Dokumente mit `customer_id IS NULL`, ältestes zuerst (so gewinnen die neuesten Angaben).
- Baut `CustomerData` je Typ aus der richtigen Quelle (Ankauf: `purchases.seller_*`, Gutachten:
  `insurance_assessments.customer_*`, Rechnung: `invoices.company_name/contact_name`, sonst
  `documents.customer_*`) und ruft `CustomerResolver::resolve(…, null)` auf.
- Barkauf-Quittungen ohne Kundendaten bleiben ohne Kunde (Name `Barkauf` gilt als leer).
- Wiederholt ausführbar (verarbeitet nur Dokumente ohne Kunde); `--dry-run` läuft in einer
  Transaktion mit Rollback und gibt aus, wie viele Kunden entstehen und wie viele Dokumente
  verknüpft würden.

## Tests

PHPUnit mit Mocks, im Stil der bestehenden Tests:

- `tests/Services/PhoneNormalizerTest.php`: Formate CH/international, Leerwerte.
- `tests/Services/CustomerResolverTest.php`: Regeln 1–4, abweichender Name bei gesetzter
  `customer_id`, mehrere Treffer → neuer Kunde, leere Felder überschreiben nichts.
- `tests/Services/CustomerMergeServiceTest.php`: Umhängen, Felder ergänzen, Notizen, Quelle = Ziel.

Manuell im Browser (DDEV): alle fünf Formulare mit und ohne Picker, Vorbelegung per `customer_id`,
Kundenliste/Suche, Bearbeiten, Zusammenführen, Import mit `--dry-run` und echt auf Testdaten.

## Deployment (Prod)

1. Code und `vendor/` wie gewohnt hochladen.
2. `php bin/migrate.php` (Migration 014).
3. `php bin/import-customers.php --dry-run` prüfen, danach ohne `--dry-run` ausführen.
4. Vorher DB-Backup.

## Risiken

- **Falsche Zusammenfassung** beim Abgleich: durch Bedingung «Name gleich» klein gehalten; zu viele
  Duplikate sind die bewusst gewählte, leicht behebbare Fehlerrichtung.
- **Kundenstamm ändert sich, Dokumente nicht:** gewollt (Momentaufnahme); auf der Kundenseite ist
  das am jeweiligen Dokument ersichtlich.
- **Picker überschreibt bereits getippte Felder:** Auswahl ist eine bewusste Aktion; Felder bleiben
  danach editierbar.
