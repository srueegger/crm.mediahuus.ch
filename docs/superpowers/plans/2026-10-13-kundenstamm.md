# Kundenstamm Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Kundentabelle mit automatischer Zuordnung neuer und bestehender Dokumente, Kunden-Picker in allen Formularen, Kundenliste/-detail/-bearbeitung und Zusammenführen.

**Architecture:** Neue Tabelle `customers` und nullable `documents.customer_id`; Dokumente behalten ihre Kundenfelder als Momentaufnahme. Ein `CustomerResolver` ordnet beim Speichern zu, eine zentrale `CustomerFieldMap` beschreibt pro Dokumenttyp, welche Formularfelder Kundendaten sind (genutzt von Controllern, Vorbelegung, Picker und Import).

**Tech Stack:** PHP 8.2, Slim 4, PHP-DI, Doctrine DBAL 3, Twig 3, Tailwind, Vanilla JS, PHPUnit 10.

**Spec:** `docs/superpowers/specs/2026-10-13-kundenstamm-design.md`

## Global Constraints

- Branch `feat/customers`, basiert auf `feat/csrf` (muss vorher fertig sein: `csrf_field()` existiert). Commits nach Conventional Commits, deutsch, **ohne** Hinweis auf KI/Claude und ohne `Co-Authored-By`-Trailer.
- `declare(strict_types=1)`, PSR-12, SQL nur parametrisiert über DBAL.
- Jedes neue POST-Formular enthält `{{ csrf_field() }}`; jeder fetch-POST den Header `X-CSRF-Token`.
- Gestaltung mit `mediahuus-input`, `mediahuus-label`, `mediahuus-button-primary`/`-secondary`, `mediahuus-form-section`; Icons und Buttons im im Projekt üblichen Emerald-Grün (`emerald-600/700`, Hintergründe `emerald-50/100`).
- Bestehende Dokumentfelder, PDFs und Detailansichten bleiben inhaltlich unverändert.
- Spaltengrössen: `company_name` 70, `name` 255, `phone` 20, `phone_normalized` 20, `email` 255, `street` 255, `zip` 16, `city` 255.
- Normalisierung: Telefon gemäss Spec-Abschnitt «Normalisierung»; Name `trim` + Mehrfach-Leerzeichen → eins + `mb_strtolower`; E-Mail und Strasse `trim` + `mb_strtolower` (+ Leerzeichen zusammenfassen bei Strasse); PLZ `trim`.
- Picker: ab 2 Zeichen, Debounce 250 ms, max. 10 Treffer. Liste: 25 pro Seite.
- Tests: `ddev exec vendor/bin/phpunit`; Migration: `ddev composer migrate`.

## Review Focus

- **Quittung/Rechnung ohne Telefon und E-Mail, gleicher Kunde zweimal** → zweites Dokument landet beim selben Kunden über Strasse+PLZ. Test: `CustomerResolverTest::testMatchesByAddressWhenNoPhoneOrEmail`.
- **Picker gewählt, danach Name für andere Person überschrieben** → bestehender Kunde bleibt unverändert, neuer Kunde entsteht. Test: `CustomerResolverTest::testIgnoresSelectionWhenNameChanged`.
- **Familie mit gemeinsamer Nummer, unterschiedliche Namen** → zwei Kunden. Test: `CustomerResolverTest::testSamePhoneDifferentNameCreatesNewCustomer`.
- **Validierungsfehler im Formular nach Picker-Auswahl** → Verknüpfungsanzeige und `customer_id` bleiben erhalten. Prüfung: manueller Schritt in Task 6.
- **`?customer_id=abc` oder unbekannte ID in der URL** → leeres Formular, kein Fehler. Test: `CustomerFormPrefillTest::testInvalidIdYieldsEmptyPrefill`.

---

### Task 1: PhoneNormalizer und CustomerData

**Files:**
- Create: `app/Services/PhoneNormalizer.php`, `app/Models/CustomerData.php`
- Test: `tests/Services/PhoneNormalizerTest.php`, `tests/Models/CustomerDataTest.php`

**Interfaces:**
- Produces:
  - `PhoneNormalizer::normalize(?string $phone): ?string` (static)
  - `final class CustomerData` (readonly-Properties `string $name`, `?string $company`, `?string $phone`, `?string $email`, `?string $street`, `?string $zip`, `?string $city`); Konstruktor wandelt getrimmte Leerstrings in `null` (Name in `''`).
  - `CustomerData::normalizeName(string): string`, `normalizeEmail(?string): ?string`, `normalizeStreet(?string): ?string` (static)
  - `isEmpty(): bool` (Name leer)

- [ ] **Step 1: Failing tests**

```php
// PhoneNormalizerTest – Daten-Provider:
['+41 79 123 45 67', '0791234567'], ['079 123 45 67', '0791234567'],
['0041791234567', '0791234567'], ['+49 170 1234567', '00491701234567'],
['(061) 711-22-33', '0617112233'], ['', null], [null, null], ['  ', null]

// CustomerDataTest
testTrimsAndNullsEmptyFields()   // new CustomerData('  Max ', null, ' ', '') → name 'Max', phone null, email null
testNormalizeName()              // '  Max   MUSTER ' → 'max muster'
testNormalizeStreet()            // 'Hauptstrasse  38 ' → 'hauptstrasse 38'
testIsEmpty()                    // name '' → true
```

- [ ] **Step 2:** Tests → FAIL. **Step 3:** implementieren. **Step 4:** Tests → PASS.
- [ ] **Step 5: Commit** `feat: Telefon-Normalisierung und Kundendaten-Wertobjekt`

---

### Task 2: Migration, Customer-Modell, CustomerRepository, Document.customerId

**Files:**
- Create: `database/migrations/014_create_customers_table.sql` (SQL exakt aus Spec «Datenmodell»)
- Create: `app/Models/Customer.php`, `app/Repositories/CustomerRepository.php`
- Modify: `app/Models/Document.php` (Konstruktor-Parameter `?int $customerId = null` **am Ende**, Getter, `toArray()['customer_id']`)
- Modify: `app/Repositories/DocumentRepository.php` (`create`, Hydration, neue Methoden)
- Modify: `config/container.php` (`CustomerRepository`)
- Test: `tests/Models/CustomerTest.php`

**Interfaces:**
- Consumes: `CustomerData`, `PhoneNormalizer`.
- Produces:
  - `Customer`: Konstruktor (`?int $id, string $name, ?string $companyName, ?string $phone, ?string $email, ?string $street, ?string $zip, ?string $city, ?string $notes, ?\DateTime $createdAt = null, ?\DateTime $updatedAt = null`), Getter, `getPhoneNormalized(): ?string` (berechnet), `getDisplayName(): string` («Firma, Name»; nur Name, wenn keine Firma oder Firma == Name), `toArray(): array` mit Schlüsseln `id, name, company_name, phone, email, street, zip, city, notes, display_name, created_at`,
    `withData(CustomerData $d): Customer` (nicht-null Felder überschreiben),
    `hasSameData(Customer $other): bool`,
    `mergedWith(Customer $source): Customer` (leere Felder aus Quelle ergänzen; Notizen `ziel . "\n\n" . quelle`, falls beide gesetzt).
  - `CustomerRepository`: `create(Customer): int`, `update(Customer): void` (schreibt `phone_normalized` mit), `findById(int): ?Customer`, `delete(int): void`,
    `search(string $q, int $limit = 10): Customer[]`,
    `paginate(?string $q, int $page, int $perPage = 25): array{items: list<array{customer: Customer, document_count: int, last_document_at: ?string}>, total: int}`,
    `findCandidates(?string $phoneNormalized, ?string $email, ?string $zip): Customer[]` (SQL `OR` über `phone_normalized = ?`, `LOWER(email) = ?`, `zip = ?`; null-Kriterien weglassen; keines gesetzt → `[]`).
  - `DocumentRepository`: `findByCustomerId(int): Document[]` (neueste zuerst), `setCustomerId(int $documentId, int $customerId): void`, `reassignCustomer(int $fromCustomerId, int $toCustomerId): int`, `findWithoutCustomer(): Document[]` (älteste zuerst).
  - Suche (`search`/`paginate`): `name`, `company_name`, `email` per `LIKE %q%`; enthält `q` ≥ 3 Ziffern zusätzlich `phone_normalized LIKE %<PhoneNormalizer::normalize(q)>%`. Sortierung nach `name`.

- [ ] **Step 1: Failing tests** `CustomerTest`:

```php
testDisplayNameWithCompany()      // company 'Muster AG', name 'Max Muster' → 'Muster AG, Max Muster'
testDisplayNameCompanyEqualsName()// company 'Muster AG', name 'Muster AG' → 'Muster AG'
testWithDataOverwritesOnlyNonNull()  // phone '079 1', email 'a@b.ch'; withData(phone '078 2', email null) → phone '078 2', email 'a@b.ch'
testPhoneNormalized()             // phone '+41 79 123 45 67' → '0791234567'
testMergedWithFillsEmptyAndJoinsNotes() // target street null, notes 'A'; source street 'X', notes 'B' → street 'X', notes "A\n\nB"
testMergedWithKeepsTargetValues() // beide street gesetzt → target bleibt
```

- [ ] **Step 2:** FAIL. **Step 3:** Modell, Repository, Document-Änderungen implementieren. **Step 4:** Tests → PASS.
- [ ] **Step 5:** `ddev composer migrate` → «014_create_customers_table completed»; `ddev mysql -e "SHOW CREATE TABLE documents\G"` zeigt `fk_documents_customer`.
- [ ] **Step 6:** `ddev exec vendor/bin/phpunit` → alle grün (bestehende Document-Konstruktoraufrufe bleiben gültig).
- [ ] **Step 7: Commit** `feat: Kundentabelle und Verknüpfung zu Dokumenten`

---

### Task 3: CustomerResolver

**Files:**
- Create: `app/Services/CustomerResolver.php`
- Test: `tests/Services/CustomerResolverTest.php`

**Interfaces:**
- Consumes: `CustomerRepository` (gemockt), `CustomerData`, `Customer`.
- Produces: `CustomerResolver::resolve(CustomerData $data, ?int $selectedCustomerId): ?int` – Regeln 1–4 aus Spec. Bei Verknüpfung `update()` nur, wenn `!hasSameData()`. Adresstreffer zählt nur, wenn `normalizeStreet` **und** PLZ gleich sind; Telefon-/E-Mail-Treffer per normalisiertem Wert.

- [ ] **Step 1: Failing tests** (Repo-Mock, `Customer` als echte Objekte)

```php
testEmptyNameReturnsNull()                     // resolve(new CustomerData(''), null) → null, keine Repo-Aufrufe
testSelectedCustomerWithSameNameIsLinked()     // findById(5) → 'Max Muster'; Data 'max  muster' → 5
testSelectedCustomerUpdatedWithNewValues()     // Data phone neu → update() einmal mit neuem phone
testSelectedCustomerNotUpdatedWhenUnchanged()  // update() nie
testIgnoresSelectionWhenNameChanged()          // findById(5) 'Max Muster', Data 'Anna Beispiel', keine Kandidaten
                                               // → create() einmal, update() nie, Rückgabe = neue ID
testUnknownSelectedIdFallsBackToMatching()     // findById → null → findCandidates wird aufgerufen
testMatchesByPhoneAndName()                    // Kandidat gleiche Nummer+Name → dessen ID, create() nie
testMatchesByEmailAndName()
testMatchesByAddressWhenNoPhoneOrEmail()       // Data name+street 'Hauptstrasse 38'+zip '4153'; Kandidat 'hauptstrasse  38'/'4153' → ID
testSameZipDifferentStreetNoMatch()            // → create()
testSamePhoneDifferentNameCreatesNewCustomer() // → create()
testMultipleMatchesCreateNewCustomer()         // zwei Kandidaten mit gleichem Namen → create()
```

- [ ] **Step 2:** FAIL. **Step 3:** implementieren. **Step 4:** PASS.
- [ ] **Step 5: Commit** `feat: automatische Kundenzuordnung beim Speichern`

---

### Task 4: CustomerFieldMap und Vorbelegung

**Files:**
- Create: `app/Services/CustomerFieldMap.php`, `app/Services/CustomerFormPrefill.php`
- Test: `tests/Services/CustomerFieldMapTest.php`, `tests/Services/CustomerFormPrefillTest.php`

**Interfaces:**
- Consumes: `Document::TYPE_*`, `CustomerData`, `Customer`, `CustomerRepository`.
- Produces:
  - `CustomerFieldMap::for(string $docType): array<string, string>` – Schlüssel `name|company|phone|email|street|zip|city`, Wert = Formularfeld; Werte exakt nach Spec-Tabelle «Feldzuordnung» (fehlende Attribute weglassen; Rechnung: `name => contact_name`, `company => company_name`, `street => street`, …).
  - `CustomerFieldMap::dataFromForm(string $docType, array $form): CustomerData` – Rechnung: Name = `contact_name` ?: `company_name`. Quittung: Name aus `customer_name` (leer bleibt leer, «Barkauf» wird erst im Controller gesetzt).
  - `CustomerFormPrefill::forRequest(string $docType, mixed $customerIdParam): array{formData: array<string,string>, linkedCustomer: ?array}` – `formData` enthält die gemappten Felder plus `customer_id`; Rechnung: wenn `name == companyName`, `contact_name` leer lassen.
  - `CustomerFormPrefill::linked(mixed $customerId): ?array` – `Customer::toArray()` oder `null` (für Re-Render nach Validierungsfehler).

- [ ] **Step 1: Failing tests**

```php
// CustomerFieldMapTest
testPurchaseMapsSellerAddress()   // for('purchase')['street'] === 'seller_street'
testReceiptHasNoPhone()           // assertArrayNotHasKey('phone', for('receipt'))
testInvoiceNameFallsBackToCompany() // dataFromForm('invoice', ['company_name'=>'Muster AG','contact_name'=>'']) → name 'Muster AG', company 'Muster AG'
testEstimateData()                // dataFromForm('estimate', [...]) → name/phone/email, street null

// CustomerFormPrefillTest (Repo-Mock)
testInvalidIdYieldsEmptyPrefill() // 'abc', '0', null, unbekannte 99 → ['formData'=>[], 'linkedCustomer'=>null]
testPrefillsInsuranceFields()     // Kunde mit Adresse → formData['customer_street'], formData['customer_id'] === '7'
testInvoiceCompanyOnlyLeavesContactEmpty()
```

- [ ] **Step 2:** FAIL. **Step 3:** implementieren (`CustomerFormPrefill` im Container registrieren bzw. Autowiring). **Step 4:** PASS.
- [ ] **Step 5: Commit** `feat: Feldzuordnung und Vorbelegung für Kundendaten`

---

### Task 5: Zuordnung in allen Erfassungen (Transaktionen)

**Files (Modify):**
- `app/Controllers/EstimateController.php`, `ReceiptController.php`, `PurchaseController.php`, `InsuranceController.php` (`store` und `create`)
- `app/Controllers/InvoiceController.php` (`create`, `store`), `app/Services/InvoiceService.php`
- `config/container.php` (Factories der 5 Controller und `InvoiceService` um `Connection`, `CustomerResolver`, `CustomerFormPrefill` ergänzen)

**Interfaces:**
- Consumes: `CustomerResolver::resolve`, `CustomerFieldMap::dataFromForm`, `CustomerFormPrefill::forRequest/linked`.
- Produces: `InvoiceService::create(array $data, int $userId, ?int $selectedCustomerId = null): int`. Alle Formular-Templates erhalten die Variablen `formData` (ggf. vorbelegt) und `linkedCustomer`.

- [ ] **Step 1:** In jedem `store`: Generierung Dokumentnummer, `resolve(...)`, `Document` (mit `customerId:`), Detaildatensatz in `$this->connection->transactional(fn() => …)` zusammenfassen; `selectedCustomerId` aus `(int) ($data['customer_id'] ?? 0) ?: null`. Ankauf: Uploads bleiben vor der Transaktion, Aufräumen im bestehenden `catch`. Quittung: Resolver bekommt den Namen **vor** dem «Barkauf»-Fallback.
- [ ] **Step 2:** In jedem `create`: `CustomerFormPrefill::forRequest($type, $request->getQueryParams()['customer_id'] ?? null)` → `formData`, `linkedCustomer` ans Template. Bei Re-Render nach Validierungsfehler: `linkedCustomer = linked($data['customer_id'] ?? null)`.
- [ ] **Step 3:** `InvoiceService::create`: Resolver innerhalb der bestehenden Transaktion, `CustomerFieldMap::dataFromForm('invoice', $data)`.
- [ ] **Step 4:** `ddev exec vendor/bin/phpunit` → grün.
- [ ] **Step 5: Manuell:** je Typ ein Dokument speichern → `ddev mysql -e "SELECT id,doc_type,customer_id FROM documents ORDER BY id DESC LIMIT 5"` zeigt gesetzte `customer_id` (ausser Barkauf); zweite Quittung mit gleichem Namen+Adresse → gleiche `customer_id`.
- [ ] **Step 6: Commit** `feat: Dokumente beim Speichern Kunden zuordnen`

---

### Task 6: Kunden-Picker in den Formularen

**Files:**
- Create: `templates/customers/_picker.html.twig`, `public/assets/js/customer-picker.js`
- Create: `app/Controllers/CustomerController.php` (vorerst nur `search`)
- Modify: `config/routes.php` (`GET /customers/search` → `customers.search`), `config/container.php` (`url_for`: `customers.search`)
- Modify: die 5 `templates/*/create.html.twig` (Picker oberhalb der Kundenfelder; JS einbinden)

**Interfaces:**
- Consumes: `CustomerRepository::search`, `CustomerFieldMap::for`.
- Produces:
  - `GET /customers/search?q=` → JSON-Array von `Customer::toArray()` (max. 10); `q` < 2 Zeichen → `[]`.
  - Partial-Aufruf: `{% include 'customers/_picker.html.twig' with { fieldMap: customerFieldMap, linkedCustomer: linkedCustomer, customerId: formData.customer_id|default('') } %}`; Controller geben `customerFieldMap` = `CustomerFieldMap::for($type)` mit.
  - Partial rendert: Suchfeld, Dropdown-Container, `<input type="hidden" name="customer_id">`, Verknüpfungsanzeige «Verknüpft mit: {display_name} ✕», Wurzel-Element mit `data-customer-picker` und `data-field-map='{{ fieldMap|json_encode|e('html_attr') }}'`.
  - JS: initialisiert alle `[data-customer-picker]`; Auswahl setzt Felder laut Map (Schlüssel `name|company|phone|email|street|zip|city` → Attribute `name|company_name|phone|email|street|zip|city` aus JSON), setzt `customer_id`, zeigt Anzeige; ✕ leert nur `customer_id` + Anzeige; Tastatur ↑/↓/Enter/Esc; Enter im Suchfeld sendet das Formular nicht ab. Trefferzeile: `display_name` fett, darunter Telefon · Ort.

- [ ] **Step 1:** Endpoint + Route; `curl -s -b <cookie> 'https://crm-mediahuus.ddev.site/customers/search?q=mu'` bzw. Browser-Aufruf liefert JSON.
- [ ] **Step 2:** Partial und JS schreiben, in die 5 Formulare einbauen.
- [ ] **Step 3: Manuell im Browser:** in allen 5 Formularen Kunden suchen (Name, Teil der Nummer «079 12»), auswählen → Felder gefüllt; ✕ → Verknüpfung weg, Felder bleiben; Pflichtfeld leer lassen und absenden → nach Re-Render Verknüpfungsanzeige noch da; `…/create?customer_id=<id>` → vorbelegt und verknüpft.
- [ ] **Step 4: Commit** `feat: Kundensuche in den Erfassungsformularen`

---

### Task 7: Kundenliste, Detail, Anlegen/Bearbeiten, Dashboard-Kachel

**Files:**
- Modify: `app/Controllers/CustomerController.php` (`index`, `show`, `create`, `store`, `edit`, `update`)
- Create: `templates/customers/index.html.twig`, `show.html.twig`, `form.html.twig`
- Modify: `config/routes.php`, `config/container.php` (`url_for`: `customers.index|show|create|store|edit|update`), `templates/dashboard.html.twig` (Kachel «Kunden» nach «Benutzer», gleiches Markup wie die anderen Kacheln)

**Interfaces:**
- Consumes: `CustomerRepository::paginate/findById/create/update`, `DocumentRepository::findByCustomerId`, `BranchRepository` (Filialnamen).
- Produces: Routen laut Spec «Kundenverwaltung» (ohne merge). `show` übergibt `documents` als Liste `{document, branch_name, show_url, pdf_url}` (URLs über das bestehende `url_for`-Schema je Typ `estimates|receipts|invoices|purchases|insurance`).
- Validierung (`store`/`update`): Name Pflicht («Name ist erforderlich»), Telefon `/^[\d\s\+\-\(\)]+$/` («Ungültiges Telefonnummer-Format»), E-Mail `FILTER_VALIDATE_EMAIL` («Ungültige E-Mail-Adresse»), Längen gemäss Global Constraints.

- [ ] **Step 1:** Routen und Controller-Methoden; Formular mit `{{ csrf_field() }}`.
- [ ] **Step 2:** Templates: Liste (Suchfeld GET `q`, Tabelle Name/Firma, Telefon, Ort, Anzahl Dokumente, letztes Dokument `d.m.Y`, Blättern), Detail (Stammdaten, Notizen, Buttons «Bearbeiten», «Mit anderem Kunden zusammenführen» (Ziel folgt in Task 8, Button bis dahin weglassen), 5 Buttons «Neuer …» → `…/create?customer_id=`, Dokumenttabelle neueste zuerst).
- [ ] **Step 3: Manuell:** Kachel öffnet Liste; Suche nach Name/Nummer; Kunde anlegen (Fehlerfälle prüfen), bearbeiten → alte Dokumente auf der Detailseite und im PDF unverändert; «Neue Quittung» → vorbelegtes Formular.
- [ ] **Step 4: Commit** `feat: Kundenliste, Detailansicht und Bearbeitung`

---

### Task 8: Zusammenführen

**Files:**
- Create: `app/Services/CustomerMergeService.php`, `templates/customers/merge.html.twig`
- Modify: `app/Controllers/CustomerController.php` (`mergeForm`, `merge`), `config/routes.php`, `config/container.php`, `templates/customers/show.html.twig` (Button + Ziel-Picker)
- Test: `tests/Services/CustomerMergeServiceTest.php`

**Interfaces:**
- Consumes: `Connection::transactional`, `CustomerRepository::findById/update/delete`, `DocumentRepository::reassignCustomer`, `Customer::mergedWith`.
- Produces: `CustomerMergeService::merge(int $sourceId, int $targetId): void` (wirft `\InvalidArgumentException` bei gleicher ID oder fehlendem Kunden). Routen `GET /customers/{id}/merge?target={id}` (Bestätigung) und `POST /customers/{id}/merge` (Feld `target_id`) → Redirect auf `/customers/{target}`.

- [ ] **Step 1: Failing tests** (Connection-Mock: `transactional` ruft Callback auf)

```php
testMergeReassignsUpdatesAndDeletes() // reassignCustomer(3, 7) einmal; update() mit mergedWith-Ergebnis; delete(3)
testSameIdThrows()                    // merge(3, 3) → InvalidArgumentException, keine Repo-Schreibaufrufe
testMissingCustomerThrows()           // findById(7) → null → InvalidArgumentException
```

- [ ] **Step 2:** FAIL. **Step 3:** Service implementieren. **Step 4:** PASS.
- [ ] **Step 5:** UI: auf `show` Picker-Variante ohne Feld-Map (nur Auswahl) → Weiterleitung `GET …/merge?target=` ; Bestätigungsseite mit beiden Kunden nebeneinander und Dokumentanzahl, POST-Formular mit `{{ csrf_field() }}`; Hinweis «Der Kunde links wird gelöscht, alle Dokumente gehen an den Kunden rechts.»
- [ ] **Step 6: Manuell:** zwei Kunden zusammenführen → Dokumente beim Ziel, Quelle 404 (bestehendes Not-Found-Verhalten der Detailseiten übernehmen), Notizen kombiniert.
- [ ] **Step 7: Commit** `feat: Kunden zusammenführen`

---

### Task 9: Verlinkung in Dokumenten und Excel-Export

**Files (Modify):** `templates/estimates/show.html.twig`, `receipts/show.html.twig`, `invoices/show.html.twig`, `purchases/show.html.twig`, `insurance/show.html.twig`, `app/Controllers/ExportController.php`

- [ ] **Step 1:** In jeder Detailansicht den Kundennamen, falls `document.customer_id`, als Link (`text-emerald-700 hover:underline`) auf `/customers/{id}` darstellen.
- [ ] **Step 2:** Export: in jedem Blatt mit Spalte «Kunde» direkt davor Spalte «Kunden-Nr.» (`customer_id` oder leer); Spaltenbuchstaben der Folgespalten nachziehen.
- [ ] **Step 3: Manuell:** Detailansichten verlinken; `GET /export/excel` öffnen, Spalte vorhanden, übrige Spalten korrekt.
- [ ] **Step 4: Commit** `feat: Kunden in Dokumentansichten und Export verlinken`

---

### Task 10: Import der Altdaten

**Files:**
- Create: `bin/import-customers.php`
- Modify: `app/Services/CustomerFieldMap.php` (`dataFromDocument`)
- Test: `tests/Services/CustomerFieldMapTest.php` (erweitern)

**Interfaces:**
- Consumes: `DocumentRepository::findWithoutCustomer/setCustomerId`, `PurchaseRepository::findByDocumentId`, `InsuranceAssessmentRepository::findByDocumentId`, `InvoiceRepository::findByDocumentId`, `CustomerResolver::resolve`.
- Produces: `CustomerFieldMap::dataFromDocument(Document $doc, ?Purchase $p, ?InsuranceAssessment $a, ?Invoice $i): CustomerData` – Quellen laut Spec «Altdaten-Import»; Name `Barkauf` (Quittung) → leerer Name.

- [ ] **Step 1: Failing tests**

```php
testDataFromPurchaseUsesSellerAddress()
testDataFromInsuranceUsesAssessmentAddress()
testDataFromInvoiceUsesContactThenCompany()
testBarkaufReceiptIsEmpty()   // customer_name 'Barkauf' → isEmpty() true
```

- [ ] **Step 2:** FAIL. **Step 3:** implementieren. **Step 4:** PASS.
- [ ] **Step 5:** Skript nach Muster von `bin/migrate.php` (Container laden). Ablauf in `$connection->beginTransaction()`; je Dokument `resolve(…, null)` und bei Ergebnis `setCustomerId`; Zähler für neue Kunden (`SELECT COUNT(*) FROM customers` vorher/nachher), verknüpfte und übersprungene Dokumente. `--dry-run` → `rollBack()`, sonst `commit()`. Ausgabe z. B. «Dokumente verarbeitet: 120, verknüpft: 112, ohne Kunde: 8, neue Kunden: 74 (Testlauf, nichts gespeichert)».
- [ ] **Step 6: Manuell:** lokal einige Dokumente ohne Kunde anlegen (`UPDATE documents SET customer_id = NULL`; `DELETE FROM customers`), `ddev exec php bin/import-customers.php --dry-run` → Zahlen, DB unverändert; ohne `--dry-run` → verknüpft; zweiter Lauf → «verknüpft: 0».
- [ ] **Step 7: Commit** `feat: Import-Skript für bestehende Kundendaten`

---

### Task 11: Dokumentation

**Files (Modify):** `CLAUDE.md` (Kernfunktionen «Kundenstamm», Projektstruktur, Datenmodell `customers` + `documents.customer_id`, Routen `/customers…`, Deployment-Schritte: Backup → `php bin/migrate.php` → `php bin/import-customers.php --dry-run` → ohne `--dry-run`).

- [ ] **Step 1:** ergänzen. **Step 2: Commit** `docs: Kundenstamm dokumentieren`
