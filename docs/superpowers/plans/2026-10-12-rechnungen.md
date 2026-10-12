# Rechnungen mit Swiss QR-Einzahlungsschein – Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Neues Dokumentmodul „Rechnung“ mit Positionen inkl. MWST, Live-Vorschau und einem PDF, das Rechnung und Swiss QR-Zahlteil enthält.

**Architecture:** Gleiches Muster wie Quittungen (`documents` + Detailtabellen, Controller → Repository, Twig, TCPDF). Rechen-, Nummern-, Validierungs- und QR-Logik liegen in kleinen, reinen Klassen mit PHPUnit-Tests; `InvoiceService` speichert in einer Transaktion, `InvoicePdfService` erzeugt das PDF mit `sprain/swiss-qr-bill` (TCPDF-Ausgabe).

**Tech Stack:** PHP 8.2/8.3, Slim 4, Twig 3, Doctrine DBAL 3, TCPDF 6, `sprain/swiss-qr-bill` ^5.3, PHPUnit, Tailwind + Vanilla JS.

**Spec:** `docs/superpowers/specs/2026-10-12-rechnungen-design.md`

## Global Constraints

- Absender immer: `Mediahuus GmbH`, `Hauptstrasse 38`, `4153`, `Reinach`, Land `CH`.
- IBAN `CH1306300507650167830` (normale IBAN → Referenztyp SCOR).
- MWST-Nummer im PDF: `CHE-178.268.076 MWST`.
- MWST-Sätze: `8.1`, `2.6`, `0`; vorausgewählt `8.1`. Preise werden inkl. MWST eingegeben.
- Zahlungsfrist Standard `10` Tage, erlaubt `0`–`365`.
- Nummernformat `RE-JJJJ-MM-TT-NN`, `NN` pro Rechnungstag ab `01`, ab 100 dreistellig.
- Filiale jeder Rechnung: Mediahuus Reinach (`branches.id = 2`, über Name ermittelt, nicht hart codiert).
- Grenzen: Firma/Name/Strasse max. 70, PLZ max. 16, Ort max. 35 Zeichen; Total 0.01–999'999'999.99.
- Beträge mit Rappen-Integer bzw. `bcmath` rechnen, nie mit Float-Arithmetik.
- Alle Dateien `declare(strict_types=1)`, PSR-12, UI-Texte Deutsch (Schweiz, „ss“ statt „ß“).
- Buttons/Icons im Projekt-Grün (Tailwind `emerald`, `#10b981`).
- Commit-Messages: Conventional Commits auf Deutsch, **ohne** Hinweis auf KI/Claude, ohne Co-Authored-By-Trailer.
- Composer-Plattform `php: 8.2` (DDEV 8.2, Prod 8.3).

## Review Focus

1. **Rundung bei gemischten Sätzen** – mehrere Positionen mit 8.1 % und 2.6 % und krummen Mengen: Summe Netto + Summe MWST muss exakt Summe Brutto ergeben (Test in Task 2).
2. **Strasse ohne Hausnummer bzw. mit Zusatz** (`Postfach 12`, `Musterweg 3a`) – muss als strukturierte Adresse im QR-Teil gültig sein (Test in Task 4).
3. **Umlaute und Sonderzeichen** in Firma/Ort (`Müller & Söhne AG`, `Zürich`) – QR gültig, PDF korrekt dargestellt (Test in Task 4).
4. **Viele Positionen** (z. B. 40) – Tabelle bricht sauber um, QR-Teil steht unüberlappt auf eigener Seite (Test in Task 6).
5. **Formular mit Fehlern** – nach Fehlermeldung bleiben alle Positionen inkl. MWST-Satz erhalten, Vorschau rechnet sofort wieder (manuelle Prüfung Task 7).

---

## File Structure

| Datei | Verantwortung |
|---|---|
| `config/invoice.php` | Absenderdaten (Name, Strasse, Hausnr., PLZ, Ort, Land, IBAN, MWST-Nr., erlaubte Sätze, Standardfrist) |
| `app/Services/InvoiceCalculator.php` | reine Berechnung von Positionstotalen, MWST pro Satz, Totalen |
| `app/Services/InvoiceNumberGenerator.php` | reine Bildung der nächsten Nummer und der SCOR-Basis |
| `app/Services/InvoiceInputValidator.php` | Formulardaten prüfen und normalisieren |
| `app/Services/QrBillFactory.php` | `QrBill`-Objekt aus Rechnungsdaten bauen und validieren |
| `app/Services/InvoiceService.php` | Rechnung erstellen: Nummer, QR-Prüfung, Transaktion, Retry |
| `app/Services/InvoicePdfService.php` | PDF inkl. QR-Zahlteil |
| `app/Models/Invoice.php`, `app/Models/InvoiceItem.php` | Datenobjekte mit `toArray()` |
| `app/Repositories/InvoiceRepository.php` | Persistenz `invoices` + `invoice_items` |
| `app/Controllers/InvoiceController.php` | `create`, `store`, `show`, `generatePdf` |
| `templates/invoices/create.html.twig`, `show.html.twig` | UI |
| `public/assets/js/invoice-form.js` | Positionen hinzufügen/entfernen, Live-Vorschau |
| `database/migrations/013_create_invoices_tables.sql` | ENUM + Tabellen |
| `tests/Services/*Test.php` | Unit-Tests |

---

### Task 1: Abhängigkeiten und Test-Setup

**Files:**
- Modify: `composer.json`, `.gitignore`
- Create: `composer.lock` (versioniert), `phpunit.xml.dist`

**Interfaces:**
- Produces: `vendor/bin/phpunit` lauffähig; Klassen `Sprain\SwissQrBill\*` autoloadbar.

- [ ] **Step 1:** Prod-`composer.lock` als Basis holen, damit bestehende Versionen gleich bleiben: `scp scifitre@rueegger.dev:public_html/crm.mediahuus.ch/composer.lock .`
- [ ] **Step 2:** `composer.json`: `"config": {"platform": {"php": "8.2"}}` ergänzen; `composer require sprain/swiss-qr-bill:^5.3`; `composer require --dev phpunit/phpunit:^10.5`.
- [ ] **Step 3:** `composer.lock` aus `.gitignore` entfernen.
- [ ] **Step 4:** `phpunit.xml.dist` mit Testsuite `tests/`, Bootstrap `vendor/autoload.php`.
- [ ] **Step 5:** Run `vendor/bin/phpunit` – Expected: bestehende `AuthServiceTest` läuft (PASS oder vorbestehende Fehler notieren, nicht beheben).
- [ ] **Step 6:** `composer show tecnickcom/tcpdf slim/slim doctrine/dbal` – Expected: dieselben Versionen wie in der Prod-Lock-Datei.
- [ ] **Step 7: Commit** `chore: Swiss-QR-Bill-Bibliothek und PHPUnit einrichten`

---

### Task 2: InvoiceCalculator

**Files:**
- Create: `app/Services/InvoiceCalculator.php`, `tests/Services/InvoiceCalculatorTest.php`

**Interfaces:**
- Produces:
  - `InvoiceCalculator::lineTotal(string $quantity, string $unitPriceGross): string` – Dezimalstring mit 2 Stellen.
  - `InvoiceCalculator::calculate(array $items): array` mit `$items = [['quantity' => '1.5', 'unit_price_gross' => '100.00', 'vat_rate' => '8.1'], ...]`, Rückgabe:
    ```php
    [
      'lines' => [['line_total_gross' => '150.00'], ...],          // gleiche Reihenfolge wie $items
      'vat_groups' => [ '8.1' => ['gross' => '150.00', 'vat' => '11.24', 'net' => '138.76'], ... ], // absteigend nach Satz
      'total_net' => '138.76', 'total_vat' => '11.24', 'total_gross' => '150.00',
    ]
    ```
  - Alle Beträge als Strings `"0.00"`-Format.

- [ ] **Step 1: Failing tests** in `InvoiceCalculatorTest`:
  - `testSingleLineStandardRate`: 1 × 108.10 @ 8.1 → vat `8.10`, net `100.00`, gross `108.10`.
  - `testQuantityWithDecimals`: 1.5 × 100.00 @ 8.1 → line `150.00`, vat `11.24` (150×8.1/108.1 = 11.2396), net `138.76`.
  - `testMixedRates`: [2 × 49.90 @ 8.1, 1 × 20.00 @ 2.6, 1 × 10.00 @ 0] → groups `8.1`: gross `99.80` vat `7.48` net `92.32`; `2.6`: gross `20.00` vat `0.51` net `19.49`; `0`: gross `10.00` vat `0.00` net `10.00`; totals gross `129.80`, vat `7.99`, net `121.81`.
  - `testTotalsAreConsistent`: für die Mixed-Daten gilt `bcadd(total_net, total_vat, 2) === total_gross`.
  - `testSmallAmountsRounding`: 1 × 0.05 @ 8.1 → vat `0.00` (0.0037), net `0.05`; 3 × 0.35 @ 8.1 → line `1.05`, vat `0.08` (0.0787).
  - `testLineTotalRounding`: `lineTotal('0.333', '10.00')` → `3.33`; `lineTotal('0.335', '10.00')` → `3.35` (Half-Up).
- [ ] **Step 2:** Run `vendor/bin/phpunit tests/Services/InvoiceCalculatorTest.php` – Expected: FAIL (Klasse fehlt).
- [ ] **Step 3:** Implementieren mit `bcmath` (Skala 6 für Zwischenwerte), kaufmännische Rundung auf 2 Stellen über eine private `round2(string): string` (Half-Up, auch für negative Werte korrekt).
- [ ] **Step 4:** Tests laufen – Expected: PASS.
- [ ] **Step 5: Commit** `feat: Berechnung von Rechnungsbeträgen inkl. MWST`

---

### Task 3: Nummernvergabe und DocumentRepository

**Files:**
- Create: `app/Services/InvoiceNumberGenerator.php`, `tests/Services/InvoiceNumberGeneratorTest.php`
- Modify: `app/Repositories/DocumentRepository.php:90-109` (`generateDocNumber`)

**Interfaces:**
- Produces:
  - `InvoiceNumberGenerator::next(\DateTimeInterface $invoiceDate, ?string $lastNumberOfDay): string` → `RE-2026-10-12-01`.
  - `InvoiceNumberGenerator::referenceBase(string $invoiceNumber): string` → `RE2026101201`.
  - `DocumentRepository::findLastInvoiceNumberForDate(\DateTimeInterface $date): ?string` – SQL `WHERE doc_type = 'invoice' AND doc_number LIKE 'RE-2026-10-12-%' ORDER BY LENGTH(doc_number) DESC, doc_number DESC LIMIT 1`.

- [ ] **Step 1: Failing tests**:
  - `testFirstNumberOfDay`: `next(2026-10-12, null)` → `RE-2026-10-12-01`.
  - `testContinuesSequence`: `next(2026-10-12, 'RE-2026-10-12-09')` → `RE-2026-10-12-10`.
  - `testThreeDigitsAfter99`: `next(2026-10-12, 'RE-2026-10-12-99')` → `RE-2026-10-12-100`; `next(…, 'RE-2026-10-12-100')` → `RE-2026-10-12-101`.
  - `testReferenceBase`: `referenceBase('RE-2026-10-12-01')` → `RE2026101201`.
- [ ] **Step 2:** Run – Expected: FAIL.
- [ ] **Step 3:** `InvoiceNumberGenerator` implementieren (`sprintf('%02d')` auf die Zahl nach dem letzten `-`).
- [ ] **Step 4:** `DocumentRepository::generateDocNumber()`: `COUNT(*)` ersetzen durch höchste Nummer gleichen Typs und Jahres (`doc_number LIKE 'KO-2026-%' ORDER BY doc_number DESC LIMIT 1`, Zahl nach letztem `-` + 1); Format `%s-%s-%06d` unverändert. Typ `invoice` hier **nicht** behandeln (wirft `InvalidArgumentException`, Rechnungen nutzen den Generator). `findLastInvoiceNumberForDate()` ergänzen.
- [ ] **Step 5:** Tests – Expected: PASS. `php -l app/Repositories/DocumentRepository.php` – Expected: keine Fehler.
- [ ] **Step 6: Commit** `feat: Rechnungsnummern pro Tag und robustere Dokumentnummern`

---

### Task 4: Konfiguration und QrBillFactory

**Files:**
- Create: `config/invoice.php`, `app/Services/QrBillFactory.php`, `tests/Services/QrBillFactoryTest.php`

**Interfaces:**
- `config/invoice.php` gibt zurück:
  ```php
  ['creditor' => ['name' => 'Mediahuus GmbH', 'street' => 'Hauptstrasse', 'building_number' => '38',
    'postal_code' => '4153', 'city' => 'Reinach', 'country' => 'CH', 'iban' => 'CH1306300507650167830',
    'vat_number' => 'CHE-178.268.076 MWST', 'branch_name' => 'Mediahuus Reinach'],
   'vat_rates' => ['8.1', '2.6', '0'], 'default_vat_rate' => '8.1', 'default_payment_term_days' => 10]
  ```
- Produces:
  - `QrBillFactory::__construct(array $creditorConfig)`
  - `QrBillFactory::create(string $invoiceNumber, string $amountGross, string $debtorName, string $debtorStreet, string $debtorZip, string $debtorCity): \Sprain\SwissQrBill\QrBill` – wirft `\RuntimeException` mit deutscher Meldung, wenn `!$qrBill->isValid()` (Meldung enthält die Violations).
  - `QrBillFactory::scorReference(string $invoiceNumber): string` – über `RfCreditorReferenceGenerator::generate(InvoiceNumberGenerator::referenceBase(...))`.
- Adressen als `StructuredAddress::createWithStreet($name, $street, null, $zip, $city, 'CH')` (Strasse inkl. Hausnummer in einem Feld, Hausnummer `null`). Zusätzliche Information `Rechnung <Nummer>`. Währung `CHF`.

- [ ] **Step 1: Failing tests**:
  - `testCreatesValidQrBill`: Nummer `RE-2026-10-12-01`, Betrag `129.80`, Debtor `Max Muster`, `Musterweg 3a`, `4051`, `Basel` → `isValid()` true; `getPaymentReference()->getReference()` beginnt mit `RF` und endet mit `RE2026101201`.
  - `testUmlautsAndAmpersand`: Debtor `Müller & Söhne AG`, `Zürichstrasse 1`, `8001`, `Zürich` → valid.
  - `testPostboxWithoutHouseNumber`: Strasse `Postfach 12` → valid.
  - `testRejectsTooLongCity`: Ort mit 36 Zeichen → `RuntimeException`.
  - `testRejectsZeroAmount`: Betrag `0.00` → `RuntimeException`.
- [ ] **Step 2:** Run – Expected: FAIL.
- [ ] **Step 3:** Implementieren. Vor dem Bauen die Namespaces/Signaturen in `vendor/sprain/swiss-qr-bill/src` prüfen (`QrBill::create`, `CreditorInformation::create`, `PaymentAmountInformation::create`, `PaymentReference::create(PaymentReference::TYPE_SCOR, …)`, `AdditionalInformation::create`).
- [ ] **Step 4:** Tests – Expected: PASS.
- [ ] **Step 5: Commit** `feat: Swiss QR-Rechnung aus Rechnungsdaten erzeugen`

---

### Task 5: Datenbank, Models, Repository, Validator, InvoiceService

**Files:**
- Create: `database/migrations/013_create_invoices_tables.sql` (Schema exakt wie Spec, plus `ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci`, Index `idx_invoice_id`, ALTER des ENUM auf `('estimate','purchase','insurance','receipt','invoice')`)
- Create: `app/Models/Invoice.php`, `app/Models/InvoiceItem.php`, `app/Repositories/InvoiceRepository.php`, `app/Services/InvoiceInputValidator.php`, `app/Services/InvoiceService.php`
- Create: `tests/Services/InvoiceInputValidatorTest.php`
- Modify: `app/Models/Document.php` (`TYPE_INVOICE = 'invoice'`, in `getValidTypes()`, Label `Rechnung`)

**Interfaces:**
- `InvoiceInputValidator::__construct(array $invoiceConfig)`; `validate(array $post): array` → `['errors' => array<string,string>, 'data' => array]`. Fehler-Keys: `company_name`, `contact_name` (Meldung „Firma oder Name ist erforderlich“ auf `contact_name`), `street`, `zip`, `city`, `invoice_date`, `payment_term_days`, `items`, `items.N.description|quantity|unit_price|vat_rate`. `data` enthält getrimmte Strings, `invoice_date` als `\DateTimeImmutable`, `payment_term_days` als int, `items` als Liste `['description','quantity','unit_price_gross','vat_rate']` (Komma als Dezimaltrennzeichen akzeptieren, leere Zeilen ignorieren).
- `Invoice` Felder wie Tabelle `invoices` + `array $items` (`InvoiceItem[]`); `toArray()` liefert snake_case-Keys inkl. `items` und berechneter `vat_groups`.
- `InvoiceRepository::create(Invoice $invoice): int` (schreibt auch Items), `findByDocumentId(int): ?Invoice`, `findAll(): array`.
- `InvoiceService::create(array $data, int $userId): int` (Rückgabe `document_id`):
  1. Filiale per `BranchRepository` über `branch_name` aus Config.
  2. `InvoiceCalculator::calculate`.
  3. Bis zu 3 Versuche: Nummer über `findLastInvoiceNumberForDate` + `InvoiceNumberGenerator::next`; `QrBillFactory::create` (wirft vor jedem DB-Zugriff); in `Connection::transactional()` Document + Invoice + Items speichern; bei `Doctrine\DBAL\Exception\UniqueConstraintViolationException` neuer Versuch.
  4. `customer_name` = Firma, sonst Name; `due_date` = Rechnungsdatum + Frist.

- [ ] **Step 1: Failing tests** `InvoiceInputValidatorTest`:
  - `testValidInputWithCompanyOnly` → keine Fehler, 1 Position.
  - `testRequiresCompanyOrName` → Fehler `contact_name`.
  - `testRequiresAddress` → Fehler `street`, `zip`, `city`.
  - `testRejectsUnknownVatRate` (`7.7`) → Fehler `items.0.vat_rate`.
  - `testAcceptsCommaDecimal` (`"49,90"`) → `unit_price_gross` `49.90`.
  - `testIgnoresEmptyRowsAndRequiresOne` → nur leere Zeilen ergibt Fehler `items`.
  - `testPaymentTermBounds` (`-1`, `366`) → Fehler `payment_term_days`; leer → `10`.
  - `testFieldLengthLimits` (Ort 36 Zeichen) → Fehler `city`.
- [ ] **Step 2:** Run – Expected: FAIL.
- [ ] **Step 3:** Validator, Models, Repository, Service, Migration, `Document`-Erweiterung implementieren; Service und Repository im DI-Container registrieren (`config/container.php`, Config via `require __DIR__ . '/invoice.php'`).
- [ ] **Step 4:** Tests – Expected: PASS; `php -l` auf alle neuen Dateien – Expected: keine Fehler.
- [ ] **Step 5:** Mit laufendem DDEV (`ddev start` – benötigt sudo, ggf. durch den Projektinhaber im eigenen Terminal): `ddev composer migrate` – Expected: `013_create_invoices_tables completed`. `ddev mysql -e "SHOW COLUMNS FROM documents LIKE 'doc_type'"` – Expected: ENUM enthält `invoice`.
- [ ] **Step 6: Commit** `feat: Datenmodell und Speicherung von Rechnungen`

---

### Task 6: InvoicePdfService

**Files:**
- Create: `app/Services/InvoicePdfService.php`, `tests/Services/InvoicePdfServiceTest.php`

**Interfaces:**
- Consumes: `Invoice`, `Document`, `Branch` (Reinach, für Telefon/E-Mail), `QrBillFactory`, Config.
- Produces: `InvoicePdfService::generate(Document $document, Invoice $invoice, Branch $branch): string` (PDF-Binärstring); `getFilename(Document $document): string` → `Rechnung_RE-2026-10-12-01.pdf`.

- [ ] **Step 1: Failing tests** (Invoice/Document im Test direkt konstruiert, keine DB):
  - `testGeneratesPdf`: Ergebnis beginnt mit `%PDF-`.
  - `testSinglePageWhenFewItems`: 3 Positionen → `preg_match_all('/\/Type\s*\/Page[^s]/', $pdf)` = 1.
  - `testManyItemsSpanPages`: 40 Positionen → Seitenzahl ≥ 2.
  - `testNeedsSeparateQrPage`: `needsSeparateQrPage(192.0)` → false, `needsSeparateQrPage(192.1)` → true (Zahlteil beginnt bei y = 297 − 105 = 192 mm).
  - `testFilename`.
- [ ] **Step 2:** Run – Expected: FAIL.
- [ ] **Step 3:** Layout gemäss Spec-Abschnitt „PDF“ umsetzen (Logo `public/assets/logo.png`; Empfängerblock x=118 mm, y=55 mm, Breite 80 mm für Fenstercouvert rechts, Kopf-Absenderangaben enden oberhalb y=45 mm; öffentliche Methode `needsSeparateQrPage(float $currentY): bool`). Zahlteil: `new TcPdfOutput($qrBill, 'de', $pdf, 0, 192)`, `setPrintable(false)`, `getPaymentPart()`; Seitenumbruch der Tabelle mit `SetAutoPageBreak(true, 20)`, vor dem Zahlteil `SetAutoPageBreak(false)`.
- [ ] **Step 4:** Tests – Expected: PASS.
- [ ] **Step 5:** Beispiel-PDF in den Scratchpad schreiben (kleines CLI-Skript) und visuell prüfen: Adressblock, Summen, MWST-Nr., QR-Teil mit Schweizerkreuz und Scherenlinie.
- [ ] **Step 6: Commit** `feat: Rechnungs-PDF mit QR-Zahlteil`

---

### Task 7: Controller, Routen, Formular mit Live-Vorschau, Detailansicht

**Files:**
- Create: `app/Controllers/InvoiceController.php`, `templates/invoices/create.html.twig`, `templates/invoices/show.html.twig`, `public/assets/js/invoice-form.js`
- Modify: `config/routes.php` (vier Routen, Namen `invoices.create|store|show|pdf`), `config/container.php` (`url_for`-Einträge + Controller)

**Interfaces:**
- Consumes: `InvoiceInputValidator::validate`, `InvoiceService::create`, `InvoiceRepository::findByDocumentId`, `InvoicePdfService::generate/getFilename`.
- `store`: Validierungsfehler oder `RuntimeException` aus dem Service → Formular mit `errors` und `formData` neu rendern; Erfolg → `302` auf `/invoices/{documentId}`.
- `generatePdf`: `Content-Type: application/pdf`, `Content-Disposition: attachment; filename="…"`.
- JS-Rechnung identisch zur Formel von Task 2 (Rappen-Integer, Half-Up), nur Anzeige; Formularfelder `items[N][description|quantity|unit_price|vat_rate]`; Vorschau-Elemente `#preview-net`, `#preview-vat-groups`, `#preview-gross`, `#due-date`.

- [ ] **Step 1:** Controller + Routen + Container implementieren; `php -l` – Expected: keine Fehler.
- [ ] **Step 2:** Templates nach Vorbild `templates/receipts/create.html.twig` (Layout, Klassen `mediahuus-input`/`mediahuus-label`); MWST-Select mit Optionen aus Config, `8.1` vorausgewählt; bei `formData` alle Zeilen wiederherstellen.
- [ ] **Step 3:** `invoice-form.js`: Zeilen hinzufügen/entfernen, Neuberechnung bei `input`/`change`, Fälligkeitsdatum aus Datum + Frist.
- [ ] **Step 4:** Browser-Prüfung (DDEV): Rechnung mit 2 × 49.90 @ 8.1, 1 × 20.00 @ 2.6, 1 × 10.00 @ 0 → Vorschau zeigt exakt die Werte aus `testMixedRates`; Speichern → Detailansicht; PDF-Download öffnet sich.
- [ ] **Step 5:** Fehlerfall: Ort leer lassen, absenden → Meldung beim Ort, alle 4 Positionen inkl. Sätze noch da, Vorschau korrekt (Review Focus 5). Handybreite (375 px): Zusammenfassung sichtbar, kein horizontales Scrollen.
- [ ] **Step 6: Commit** `feat: Rechnungsformular mit Live-Vorschau und Detailansicht`

---

### Task 8: Dashboard und Excel-Export

**Files:**
- Modify: `app/Controllers/DashboardController.php` (Konstruktor + `case 'invoice'`), `templates/dashboard.html.twig` (Kachel, Filteroption, Badge, Modal-Abschnitt, PDF-Link), `app/Controllers/ExportController.php` (Blatt „Rechnungen“, `primaryAmount` für `invoice` = `total_gross`), `config/container.php` (neue Konstruktor-Parameter)

**Interfaces:**
- Consumes: `InvoiceRepository::findByDocumentId`, `findAll`; `Invoice::toArray()`.

- [ ] **Step 1:** Dashboard erweitern; Kachel „Rechnung“ mit Link `url_for('invoices.create')`, Badge-Farbe im Emerald-Stil wie bestehende Badges.
- [ ] **Step 2:** Export: neues Blatt mit Spalten Nummer, Datum, Fällig, Empfänger, Adresse, Netto, MWST, Total, Erstellt von.
- [ ] **Step 3:** Browser-Prüfung: Kachel sichtbar, Filter „Rechnung“ zeigt nur Rechnungen, Modal zeigt Positionen/Summen, Export enthält Blatt „Rechnungen“ mit der Testrechnung.
- [ ] **Step 4:** `vendor/bin/phpunit` – Expected: alle Tests PASS.
- [ ] **Step 5: Commit** `feat: Rechnungen im Dashboard und Excel-Export`

---

### Task 9: Doku und Deployment (jeder Prod-Schritt nur nach Freigabe)

**Files:**
- Modify: `CLAUDE.md`, `README.md` (Modul Rechnungen, Route, Nummernformat, neue Abhängigkeit, Tests)

- [ ] **Step 1:** Doku aktualisieren; **Commit** `docs: Rechnungsmodul dokumentieren`.
- [ ] **Step 2:** Pull Request erstellen.
- [ ] **Step 3 (Freigabe):** Backup Prod: `mysqldump` (`documents`) + `tar` von `app config templates public/assets vendor composer.*` nach `~/crm_backups/pre-invoices-<timestamp>`.
- [ ] **Step 4 (Freigabe):** Lokal `composer install --no-dev --optimize-autoloader` in einem sauberen Build-Ordner; `vendor/` als `vendor.new` hochladen, dann `mv vendor vendor.old && mv vendor.new vendor`.
- [ ] **Step 5 (Freigabe):** Code hochladen, Migration 013 auf Prod ausführen, `public/false` löschen.
- [ ] **Step 6:** Prüfen: `/login` 200, Formular lädt, eine Testrechnung erzeugen und PDF prüfen; Testrechnung nach Rücksprache löschen; `vendor.old` nach Bestätigung entfernen.
- [ ] **Step 7:** Projektinhaber scannt den QR-Code mit einer Banking-App (ohne Zahlung).
