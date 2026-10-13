# Reparaturen Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Reparaturen als neuer Dokumenttyp mit Status, Liste der offenen Reparaturen, öffentlicher Statusseite per Token und E-Mails (Erfasst, Abholbereit) über einen zentralen Mailgun-fähigen `MailService`.

**Architecture:** Reparatur = Eintrag in `documents` (`doc_type = 'repair'`, `RP-JJJJ-NNNNNN`) + Detailtabelle `repairs` + Verlauf `repair_events`. Statusregeln liegen im Modell (`Repair::withStatus`), Abläufe (Anlegen, Bearbeiten, Statuswechsel, Mailauslösung) im `RepairService`. Alle E-Mails laufen über `MailService` (Symfony Mailer, Transport aus `MAILER_DSN`). Die Statusseite bekommt nur das reduzierte Array aus `RepairStatusPage`.

**Tech Stack:** PHP 8.2, Slim 4, PHP-DI, Doctrine DBAL 3, Twig 3, TCPDF, Symfony Mailer + Mailgun-Transport, Tailwind, PHPUnit 10.

**Spec:** `docs/superpowers/specs/2026-10-13-reparaturen-design.md`

## Global Constraints

- Branch `feat/repairs`. Commits nach Conventional Commits, deutsch, **ohne** Hinweis auf KI/Claude, **ohne** `Co-Authored-By`-Trailer.
- `declare(strict_types=1)`, PSR-12, SQL nur parametrisiert über DBAL.
- Jedes neue POST-Formular enthält direkt nach `<form method="POST" …>` die Zeile `{{ csrf_field() }}`.
- Gestaltung mit `mediahuus-input`, `mediahuus-label`, `mediahuus-button-primary`/`-secondary`, `mediahuus-form-section`; Icons/Buttons im Projekt-Grün (`emerald-600/700`, Hintergründe `emerald-50/100`).
- **Jede E-Mail des Systems läuft über `App\Services\MailService::send()`.** Kein `mail()`, kein eigener SMTP-Code, keine zweite Mailer-Instanz.
- `MailService` wirft nie in den aufrufenden Code; leere `MAILER_DSN` = kein Versand, nur Log.
- Entsperrcode (`unlock_code`) erscheint nie in PDF, E-Mail, Statusseite, Excel-Export oder Log.
- Statuswerte exakt: `received`, `in_progress`, `waiting_parts`, `ready`, `picked_up`, `cancelled`; Anzeigenamen: Angenommen, In Arbeit, Wartet auf Ersatzteil, Abholbereit, Abgeholt, Storniert. Offen = alle ausser `picked_up`, `cancelled`.
- Token: `bin2hex(random_bytes(16))`, Route `/r/{token:[a-f0-9]{32}}`. Statusseite gültig bis 30 Tage nach `closed_at`.
- Neue `.env`-Variablen: `MAILER_DSN`, `MAIL_FROM=info@mediahuus.ch`, `MAIL_FROM_NAME="Mediahuus"`, `APP_URL=https://crm.mediahuus.ch`.
- Liste: 25 pro Seite. Tests: `ddev exec vendor/bin/phpunit`; Migration: `ddev composer migrate`.

## Review Focus

- **Status zurück von «Abgeholt» auf «In Arbeit»** (Fehlklick) → `closed_at` wird geleert, Statusseite wieder unbefristet gültig; Entsperrcode bleibt aber gelöscht. Test: `RepairTest::testReopenClearsClosedAt`.
- **Zweimal «Abholbereit» gesetzt** (z. B. ready → in_progress → ready) → jedes Mal eine E-Mail; gleicher Status ohne Wechsel → keine. Test: `RepairServiceTest::testSameStatusDoesNothing`, `testReadySendsMailEachTimeItIsEntered`.
- **Kunde ohne E-Mail** → kein Versand, kein Fehlereintrag, kein gelber Hinweis. Test: `RepairMailerTest::testNoCustomerEmailSkipsSilently`.
- **Mailgun-Fehler beim Anlegen** → Reparatur gespeichert, Ereignis `email_failed`, Detailansicht mit Hinweis. Test: `RepairServiceTest::testMailFailureDoesNotRollBack`; Controller leitet auf `/repairs/{id}?mail=failed`.
- **Preis leer / «fertig bis» leer** → Statusseite, PDF und E-Mail zeigen die Zeile nicht statt «CHF 0.00»/leerem Datum. Test: `RepairStatusPageTest::testOmitsEmptyPriceAndDueDate`.

---

### Task 1: Zentraler MailService

**Files:**
- Modify: `composer.json`, `composer.lock` (`ddev composer require symfony/mailer symfony/mailgun-mailer`)
- Create: `app/Services/MailService.php`, `app/Services/MailResult.php`
- Modify: `config/container.php`, `.env.example`
- Test: `tests/Services/MailServiceTest.php`

**Interfaces:**
- Produces:
  - `final class MailResult` mit Konstanten `SENT = 'sent'`, `NOT_CONFIGURED = 'not_configured'`, `FAILED = 'failed'`; readonly `string $status`, `?string $error`; Named-Constructors `sent()`, `notConfigured()`, `failed(string $error)`; `isSent(): bool`.
  - `MailService::__construct(?Symfony\Component\Mailer\Transport\TransportInterface $transport, Symfony\Component\Mime\Address $from, Psr\Log\LoggerInterface $logger)`
  - `MailService::send(Symfony\Component\Mime\Email $email): MailResult` — setzt `from`, fängt `\Throwable`.
- Container: `MailService::class` → Transport `Transport::fromDsn($_ENV['MAILER_DSN'])` nur wenn nicht leer, sonst `null`; `new Address($_ENV['MAIL_FROM'] ?? 'info@mediahuus.ch', $_ENV['MAIL_FROM_NAME'] ?? 'Mediahuus')`.
- `.env.example`: Block `# Mail (alle E-Mails laufen über MailService / Mailgun)` mit den vier Variablen aus den Global Constraints; Kommentar `# lokal: MAILER_DSN=smtp://localhost:1025 (DDEV Mailpit)`.

- [ ] **Step 1: Failing tests** (Transport-Stub = anonyme Klasse `implements TransportInterface`, merkt sich die Nachricht bzw. wirft)

```php
testWithoutTransportReturnsNotConfiguredAndLogs()   // transport null → status 'not_configured', logger->info einmal
testSendsWithConfiguredFromAddress()                // Stub erhält Email, from[0] = info@mediahuus.ch / 'Mediahuus'; isSent() true
testTransportExceptionReturnsFailed()               // Stub wirft TransportException('boom') → status 'failed', error 'boom', logger->error einmal, keine Exception
```

- [ ] **Step 2:** `ddev exec vendor/bin/phpunit tests/Services/MailServiceTest.php` → FAIL (Klasse fehlt)
- [ ] **Step 3:** Pakete installieren, `MailResult` und `MailService` implementieren, Container und `.env.example` ergänzen.
- [ ] **Step 4:** Test → PASS; `ddev exec vendor/bin/phpunit` → alles grün.
- [ ] **Step 5: Commit** `feat: zentraler MailService mit Symfony Mailer und Mailgun-Transport`

---

### Task 2: Datenmodell, Modelle, Repositories, Token

**Files:**
- Create: `database/migrations/015_create_repairs_tables.sql` (SQL exakt aus Spec «Datenmodell»)
- Modify: `app/Models/Document.php` (`TYPE_REPAIR = 'repair'`, in `validateDocType`, Label `'Reparatur'`)
- Modify: `app/Repositories/DocumentRepository.php` (`generateDocNumber`: `Document::TYPE_REPAIR => 'RP'`)
- Create: `app/Models/Repair.php`, `app/Models/RepairEvent.php`, `app/Repositories/RepairRepository.php`, `app/Repositories/RepairEventRepository.php`, `app/Services/RepairStatusTokenGenerator.php`
- Modify: `config/container.php` (beide Repositories)
- Test: `tests/Models/RepairTest.php`, `tests/Services/RepairStatusTokenGeneratorTest.php`

**Interfaces:**
- Produces:
  - `Repair` (immutable, Konstruktor mit Named-Args): `?int $id, int $documentId, string $status, string $statusToken, string $deviceName, ?string $imeiSerial, ?string $damageType, string $issueText, ?string $workText, ?string $priceChf` (Dezimal als String), `?\DateTimeImmutable $dueDate, ?string $accessories, ?string $unlockCode, ?\DateTimeImmutable $closedAt, ?\DateTimeImmutable $createdAt = null, ?\DateTimeImmutable $updatedAt = null`; Getter für alle.
  - Konstanten `Repair::STATUS_RECEIVED … STATUS_CANCELLED`, `Repair::STATUS_LABELS` (Wert → Anzeigename, Reihenfolge wie Spec), `Repair::CLOSED_STATUSES = ['picked_up', 'cancelled']`.
  - `Repair::isOpen(): bool`, `Repair::statusLabel(): string`, `static Repair::isValidStatus(string): bool`
  - `Repair::withStatus(string $status, \DateTimeImmutable $now): self` — geschlossen → `unlockCode = null`, `closedAt = $now`; offen → `closedAt = null`. Ungültiger Status → `\InvalidArgumentException`.
  - `Repair::toArray(): array` (snake_case-Keys, `due_date` als `Y-m-d`, inkl. `status_label`, `is_open`, `is_overdue` = offen und `dueDate` < heute)
  - `RepairEvent`: `?int $id, int $repairId, string $eventType` (`TYPE_STATUS = 'status'`, `TYPE_EMAIL_SENT = 'email_sent'`, `TYPE_EMAIL_FAILED = 'email_failed'`, `TYPE_NOTE = 'note'`), `?string $status, ?string $message, ?int $userId, ?\DateTimeImmutable $createdAt = null`; `toArray()`.
  - `RepairRepository`: `create(Repair): int`, `update(Repair): void`, `findByDocumentId(int): ?Repair`, `findByToken(string): ?Repair`, `findList(array $filters, int $page, int $perPage): array{items: list<array{document: Document, repair: Repair}>, total: int}` (Filter `branch_id`, `status`, `scope` = `open`|`all`, Default `open`; Sortierung `due_date IS NULL, due_date ASC, documents.created_at DESC`), `countOpen(): int`, `countByStatus(string): int`, `findAll(): list<Repair>` (für Export).
  - `RepairEventRepository`: `add(RepairEvent): void`, `findByRepairId(int): list<RepairEvent>` (älteste zuerst).
  - `RepairStatusTokenGenerator::generate(): string`

- [ ] **Step 1: Failing tests**

```php
// RepairTest
testNewRepairIsOpen()                          // status received → isOpen true, statusLabel 'Angenommen'
testPickedUpClearsUnlockCodeAndSetsClosedAt()  // withStatus('picked_up', $now) → unlockCode null, closedAt == $now, isOpen false
testCancelledClearsUnlockCode()
testReopenClearsClosedAt()                     // picked_up → in_progress: closedAt null, unlockCode bleibt null
testInvalidStatusThrows()                      // withStatus('done', …) → InvalidArgumentException
testOverdueOnlyWhenOpenAndPastDue()            // dueDate gestern + received → toArray()['is_overdue'] true; + picked_up → false
// RepairStatusTokenGeneratorTest
testTokenIs32Hex()                             // assertMatchesRegularExpression('/^[a-f0-9]{32}$/')
testTokensAreUnique()                          // 1000 Aufrufe, count(array_unique) === 1000
```

- [ ] **Step 2:** Tests → FAIL. **Step 3:** Migration, Modelle, Repositories, Generator, Document-/Nummern-Erweiterung, Container.
- [ ] **Step 4:** `ddev composer migrate` läuft ohne Fehler; `ddev exec vendor/bin/phpunit` → grün.
- [ ] **Step 5: Commit** `feat: Datenmodell und Repositories für Reparaturen`

---

### Task 3: PDF Auftragsbestätigung

Als Methode in `PdfService` (nicht als eigene Klasse), damit `addHeader`/`addFooter` wiederverwendet werden (gleiches Layout wie Kostenvoranschlag).

**Files:**
- Modify: `app/Services/PdfService.php`
- Create: `app/Services/AppUrl.php`
- Modify: `config/container.php` (`AppUrl` aus `$_ENV['APP_URL']`)
- Test: `tests/Services/AppUrlTest.php`, `tests/Services/RepairPdfTest.php`

**Interfaces:**
- Consumes: `Repair`, `Document`, `Branch`.
- Produces:
  - `AppUrl::__construct(string $baseUrl)`; `AppUrl::absolute(string $path): string` (entfernt `/` am Ende der Basis, stellt `/` vor den Pfad); `AppUrl::repairStatus(string $token): string` → `{base}/r/{token}`.
  - `PdfService::generateRepairPdf(Document $document, Repair $repair, Branch $branch, string $statusUrl): string` (PDF-Bytes)
  - `PdfService::getRepairPdfFilename(Document $document): string` → `Reparatur_{doc_number}.pdf` (Muster wie `getEstimatePdfFilename`)
  - `PdfService::repairPdfLines(Document $document, Repair $repair): list<array{label: string, value: string}>` (public) — die Datenzeilen des PDFs; `generateRepairPdf` rendert genau diese Zeilen.
- Inhalt gemäss Spec «PDF Auftragsbestätigung»; Titel «Reparaturauftrag»; QR per `$pdf->write2DBarcode($statusUrl, 'QRCODE,M', x, y, 28, 28)` mit Text «Status jederzeit online abrufen»; leere Felder (Preis, fertig bis, Zubehör, IMEI, geplante Arbeiten) werden weggelassen; Unterschriftenlinien «Abgabe» und «Abholung». Kein Entsperrcode.

- [ ] **Step 1: Failing tests**

```php
// AppUrlTest
testJoinsBaseAndPath()        // new AppUrl('https://crm.mediahuus.ch/') ->absolute('/r/abc') === 'https://crm.mediahuus.ch/r/abc'
testRepairStatusUrl()         // repairStatus(str_repeat('a', 32)) endet auf '/r/' . str_repeat('a', 32)
// RepairPdfTest
testGeneratesPdf()                // generateRepairPdf(...) beginnt mit '%PDF'
testLinesNeverContainUnlockCode() // Repair mit unlockCode 'GEHEIM-4711' → kein value in repairPdfLines() enthält 'GEHEIM-4711'
testLinesOmitEmptyOptionalFields() // priceChf, dueDate, accessories, imeiSerial, workText null → keine Zeile mit diesen Labels
```

- [ ] **Step 2:** Tests → FAIL. **Step 3:** implementieren. **Step 4:** Tests → PASS.
- [ ] **Step 5: Commit** `feat: PDF-Auftragsbestätigung für Reparaturen mit QR-Code zur Statusseite`

---

### Task 4: Reparatur-E-Mails (RepairMailer)

**Files:**
- Create: `app/Services/RepairMailer.php`
- Create: `templates/emails/base.html.twig`, `templates/emails/repair_created.html.twig`, `templates/emails/repair_created.txt.twig`, `templates/emails/repair_ready.html.twig`, `templates/emails/repair_ready.txt.twig`
- Modify: `config/container.php`
- Test: `tests/Services/RepairMailerTest.php`

**Interfaces:**
- Consumes: `MailService::send`, `MailResult`, `PdfService::generateRepairPdf`/`getRepairPdfFilename`, `AppUrl::repairStatus`, `RepairEventRepository::add`, `Twig\Environment`.
- Produces:
  - `RepairMailer::__construct(MailService $mail, Environment $twig, PdfService $pdf, AppUrl $url, RepairEventRepository $events, LoggerInterface $logger)`
  - `RepairMailer::sendCreated(Document $document, Repair $repair, Branch $branch, ?int $userId): ?MailResult`
  - `RepairMailer::sendReady(Document $document, Repair $repair, Branch $branch, ?int $userId): ?MailResult`
  - Beide: `null` ohne Kunden-E-Mail (kein Ereignis). Sonst Ereignis `email_sent` mit Message `Erfassungs-E-Mail an {email}` bzw. `Abholbereit-E-Mail an {email}`, oder `email_failed` mit `… fehlgeschlagen: {Grund}`; Grund bei `not_configured` = `Mailversand nicht konfiguriert`.
- Betreff exakt: `Ihre Reparatur {RP-…} bei Mediahuus` / `Ihr Gerät ist abholbereit ({RP-…})`. `replyTo` = Filial-E-Mail (wenn vorhanden). `sendCreated` hängt das PDF an (`application/pdf`, Dateiname aus `getRepairPdfFilename`). Templates erhalten: `doc_number`, `device_name`, `status_url`, `price_chf` (nur `ready`), `branch` (name, street, zip, city, phone, email). Kein Entsperrcode, keine IMEI.

- [ ] **Step 1: Failing tests** (Mocks für `MailService`, `PdfService`, `RepairEventRepository`; echte Twig-`Environment` mit `FilesystemLoader(templates)`)

```php
testNoCustomerEmailSkipsSilently()     // customerEmail null → mail->send never, events->add never, Ergebnis null
testCreatedMailHasLinkSubjectAndPdf()  // gesendete Email: to kunde@example.ch, subject 'Ihre Reparatur RP-2026-000001 bei Mediahuus',
                                       // html+text enthalten 'https://crm.test/r/<token>', 1 Anhang 'Reparatur_RP-2026-000001.pdf'
testReadyMailSubject()                 // subject 'Ihr Gerät ist abholbereit (RP-2026-000001)', kein Anhang
testUnlockCodeNeverInMail()            // unlockCode 'GEHEIM-4711' nicht in html/text
testSuccessWritesSentEvent()           // events->add mit eventType 'email_sent', message 'Erfassungs-E-Mail an kunde@example.ch'
testNotConfiguredWritesFailedEvent()   // MailResult::notConfigured() → 'email_failed', message enthält 'Mailversand nicht konfiguriert'
```

- [ ] **Step 2:** Tests → FAIL. **Step 3:** implementieren. **Step 4:** Tests → PASS.
- [ ] **Step 5: Commit** `feat: E-Mails bei Erfassung und Abholbereitschaft einer Reparatur`

---

### Task 5: Eingabevalidierung und RepairService

**Files:**
- Create: `app/Services/RepairInputValidator.php`, `app/Services/RepairService.php`, `app/Services/RepairSaveResult.php`
- Modify: `app/Services/CustomerFieldMap.php` (`Document::TYPE_REPAIR` → `name/phone/email` wie Kostenvoranschlag)
- Modify: `config/container.php`
- Test: `tests/Services/RepairInputValidatorTest.php`, `tests/Services/RepairServiceTest.php`, `tests/Services/CustomerFieldMapTest.php` (Fall `repair` ergänzen)

**Interfaces:**
- Consumes: Task 2 (Modelle, Repositories, Token), Task 4 (`RepairMailer`), `CustomerResolver::resolve`, `CustomerFieldMap::dataFromForm`, `DocumentRepository::generateDocNumber/create/update/findById`, `BranchRepository::findById`, `Connection::transactional`.
- Produces:
  - `RepairInputValidator::validate(array $form): array<string, string>` (Feld → Fehlermeldung). Pflicht: `branch_id`, `customer_name`, `device_name`, `issue_text`. `status` gibt es im Formular nicht. `price_chf` leer oder Zahl ≥ 0 (Komma erlaubt → Punkt). `due_date` leer oder `Y-m-d`. `customer_email` leer oder gültig. `damage_type` leer oder Schlüssel aus `DamageTypeService::DAMAGE_TYPES`. Längen: `device_name` 255, `imei_serial` 64, `accessories` 255, `unlock_code` 100.
  - `RepairSaveResult`: readonly `int $documentId`, `?MailResult $mail`; `mailFailed(): bool` (= `$mail !== null && !$mail->isSent()`).
  - `RepairService::create(array $form, ?int $selectedCustomerId, int $userId): RepairSaveResult` — Transaktion: Nummer, Kunde (`CustomerResolver`), `Document`, `Repair` (Status `received`, neuer Token), Ereignis `status`/`received`. Danach `sendCreated`.
  - `RepairService::update(int $documentId, array $form, ?int $selectedCustomerId, int $userId): void` — Transaktion: Kunde neu auflösen, `Document` mit gleicher Nummer/ID und neuen Kundenfeldern + `branch_id` aktualisieren, `Repair` mit neuen Details (Status, Token, `closedAt` unverändert; Entsperrcode bei geschlossener Reparatur bleibt `null`), Ereignis `note` mit Message `Angaben bearbeitet`.
  - `RepairService::changeStatus(int $documentId, string $status, ?string $note, int $userId): ?MailResult` — Regeln 1–6 aus Spec «Regeln beim Statuswechsel»; gibt das Ergebnis des Abholbereit-Versands zurück, sonst `null`. Ungültiger Status → `\InvalidArgumentException`.
  - `RepairService::resend(int $documentId, string $type, int $userId): ?MailResult` — `type` `created`|`ready`.

- [ ] **Step 1: Failing tests** (Connection-Mock: `transactional` ruft Callback auf; Repositories, `CustomerResolver`, `RepairMailer` gemockt)

```php
// RepairInputValidatorTest
testRequiredFields()             // leeres Formular → Keys branch_id, customer_name, device_name, issue_text
testPriceAcceptsCommaAndEmpty()  // '120,50' ok, '' ok, '-5' Fehler, 'abc' Fehler
testInvalidDueDate()             // '31.12.2026' → Fehler
testUnknownDamageType()          // 'xyz' → Fehler
// RepairServiceTest
testCreateWritesReceivedEventAndSendsCreatedMail() // repairs->create mit status 'received' und 32-Hex-Token; events->add eventType 'status' status 'received'; mailer->sendCreated einmal
testStatusChangeWritesEvent()                      // received → in_progress: events->add status 'in_progress' mit note; repairs->update
testSameStatusDoesNothing()                        // in_progress → in_progress: update never, add never, mailer never
testPickedUpClearsUnlockCode()                     // repairs->update mit unlockCode null und closedAt gesetzt
testReadySendsMailEachTimeItIsEntered()            // in_progress → ready: sendReady einmal; ready → in_progress: never
testMailFailureDoesNotRollBack()                   // sendReady liefert MailResult::failed('x') → repairs->update wurde aufgerufen, Rückgabe failed
testInvalidStatusThrows()
```

- [ ] **Step 2:** Tests → FAIL. **Step 3:** implementieren. **Step 4:** `ddev exec vendor/bin/phpunit` → grün.
- [ ] **Step 5: Commit** `feat: RepairService mit Statusregeln und Eingabevalidierung`

---

### Task 6: Interne Seiten (Liste, Erfassen, Detail, Bearbeiten, Status, PDF)

**Files:**
- Create: `app/Controllers/RepairController.php`
- Create: `templates/repairs/index.html.twig`, `create.html.twig` (auch fürs Bearbeiten mit `mode = 'edit'`), `show.html.twig`, `_status_badge.html.twig`
- Modify: `config/routes.php` (Routen aus Spec «Intern», in der geschützten Gruppe), `config/container.php` (Controller), `url_for`-Liste (`repairs.index`, `repairs.create`, `repairs.store`, `repairs.show`, `repairs.edit`, `repairs.update`, `repairs.status`, `repairs.send_email`, `repairs.pdf`)

**Interfaces:**
- Consumes: `RepairService`, `RepairInputValidator`, `RepairRepository::findList/findByDocumentId`, `RepairEventRepository::findByRepairId`, `DocumentRepository`, `BranchRepository`, `UserRepository` (Namen im Verlauf), `PdfService::generateRepairPdf`, `AppUrl::repairStatus`, `CustomerFormPrefill::forRequest/linked`, `CustomerFieldMap::for(Document::TYPE_REPAIR)`, `DamageTypeService::getDamageTypes()`, `BaseController::selectedCustomerId`.
- Verhalten:
  - `index`: Query `scope` (`open` Default | `all`), `status`, `branch_id`, `page`; 25 pro Seite; Zeilen mit `is_overdue` rot (`text-red-600`, Badge rot).
  - `store`/`update`: Validierungsfehler → Formular erneut mit `errors`, `formData`, `linkedCustomer`. Erfolg → Redirect `/repairs/{id}`, bei `RepairSaveResult::mailFailed()` mit `?mail=failed`.
  - `changeStatus`: POST `status`, `note`; Redirect `/repairs/{id}` (bzw. `?mail=failed`). Ungültiger Status → Redirect `?error=status`.
  - `sendEmail`: POST `type`; Redirect mit `?mail=sent` oder `?mail=failed`; ohne Kunden-E-Mail `?mail=none`.
  - `show`: Hinweise je `mail`-Parameter (grün gesendet, gelb fehlgeschlagen «Die E-Mail konnte nicht gesendet werden. Die Reparatur ist gespeichert.»); Status-Link mit Kopier-Button (`navigator.clipboard.writeText`); Verlauf mit Datum `d.m.Y H:i`, Benutzername, Ereignis, Notiz; Entsperrcode sichtbar; Formular für Statuswechsel (Select mit allen sechs Status + Textfeld Notiz).
  - `pdf`: wie `EstimateController::generatePdf`.
  - Formular nutzt `{% include 'customers/_picker.html.twig' %}` wie `estimates/create.html.twig`.

- [ ] **Step 1:** Controller, Templates, Routen, Container implementieren.
- [ ] **Step 2:** `ddev exec vendor/bin/phpunit` → grün; `ddev exec php -l app/Controllers/RepairController.php`.
- [ ] **Step 3: Manuell prüfen** (`ddev launch`, Login admin@mediahuus.ch): Reparatur mit Kunden-Picker anlegen → Detail; Status auf «Abholbereit» → Verlauf zeigt Status + `email_failed` («nicht konfiguriert») und gelben Hinweis; mit `MAILER_DSN=smtp://localhost:1025` in `.env` erneut → Mail in Mailpit (`ddev launch -m`) mit PDF-Anhang und funktionierendem Link; Validierungsfehler nach Picker-Auswahl behält Verknüpfung; Liste zeigt offene, «alle» zeigt abgeholte; überfällige rot.
- [ ] **Step 4: Commit** `feat: Reparaturen erfassen, bearbeiten und Status pflegen`

---

### Task 7: Navigation, Dashboard, Kunde, Excel-Export

**Files:**
- Modify: `app/Twig/NavigationExtension.php`, `templates/partials/icons.html.twig` (Icon `repair`, Schraubenschlüssel, gleiche Strichstärke wie übrige)
- Modify: `app/Controllers/DashboardController.php`, `templates/dashboard.html.twig` (Kachel offen/abholbereit; Typ-Filter-Option und Zeilen für `repair`)
- Modify: `app/Controllers/CustomerController.php` (`DOC_ROUTES['repair'] = 'repairs'`), `templates/customers/show.html.twig` (Button «Neue Reparatur»)
- Modify: `app/Controllers/ExportController.php` (`buildRepairsSheet`, Spalten aus Spec «Excel-Export», ohne Entsperrcode), `config/container.php` (neue Abhängigkeiten)
- Test: `tests/Twig/NavigationExtensionTest.php`

**Interfaces:**
- Consumes: `RepairRepository::countOpen/countByStatus/findAll/findByDocumentId`.
- Navigation: «Erfassen» erhält nach `estimates` den Eintrag `['key' => 'repairs', 'label' => 'Reparatur', 'url' => '/repairs/create', 'prefix' => '/repairs/create', 'icon' => 'repair']`; «Verwalten» erhält zuerst `['key' => 'repairs_open', 'label' => 'Offene Reparaturen', 'url' => '/repairs', 'prefix' => '/repairs', 'icon' => 'repair']`.

- [ ] **Step 1: Failing test** — `testGroupsContainAllSectionsInOrder` anpassen: Erfassen-Keys `['estimates', 'repairs', 'receipts', 'invoices', 'purchases', 'insurance']`, Verwalten-Keys `['repairs_open', 'customers', 'users']`; neuer Test `testRepairDetailActivatesOpenRepairs()` (`/repairs/12` → `isActive('/repairs')` true, `isActive('/repairs/create')` false).
- [ ] **Step 2:** Test → FAIL. **Step 3:** implementieren. **Step 4:** Tests → PASS; Dashboard, Kundendetail und `/export/excel` manuell öffnen (Blatt «Reparaturen» vorhanden, keine Spalte Entsperrcode).
- [ ] **Step 5: Commit** `feat: Reparaturen in Navigation, Dashboard, Kundendetail und Excel-Export`

---

### Task 8: Öffentliche Statusseite

**Files:**
- Create: `app/Services/RepairStatusPage.php`, `app/Controllers/RepairStatusController.php`
- Create: `templates/public/base.html.twig`, `templates/public/repair_status.html.twig`, `templates/public/repair_status_gone.html.twig`, `templates/public/not_found.html.twig`
- Modify: `config/routes.php` (bei den öffentlichen Routen: `$app->get('/r/{token:[a-f0-9]{32}}', [RepairStatusController::class, 'show'])->setName('repairs.public');`), `config/container.php`
- Test: `tests/Services/RepairStatusPageTest.php`

**Interfaces:**
- Consumes: `RepairRepository::findByToken`, `RepairEventRepository::findByRepairId`, `DocumentRepository::findById`, `BranchRepository::findById`.
- Produces:
  - `RepairStatusPage::isExpired(Repair $repair, \DateTimeImmutable $now): bool` — `closedAt !== null && closedAt < now - 30 Tage`.
  - `RepairStatusPage::build(Document $document, Repair $repair, array $events, Branch $branch): array` mit **genau** den Keys `doc_number`, `device_name`, `status`, `status_label`, `cancelled` (bool), `steps` (list von `{label, done, current}` für received, in_progress, waiting_parts, ready, picked_up), `history` (list von `{status_label, date}` nur aus Ereignissen `status`, Datum `d.m.Y H:i`), `due_date` (`d.m.Y` oder null), `price_chf` (formatiert `CHF 120.50` oder null), `branch` (`name, street, zip, city, phone, email`).
  - Controller: unbekannter Token → 404 `not_found`; `isExpired` → 410 `repair_status_gone` (mit Filialkontakt); sonst 200. Alle Antworten mit Headern `X-Robots-Tag: noindex, nofollow`, `Referrer-Policy: no-referrer`, `Cache-Control: no-store`. Layout: `<meta name="robots" content="noindex, nofollow">`, Logo, mobil zuerst, Schrittanzeige in Emerald.

- [ ] **Step 1: Failing tests**

```php
testContainsOnlyAllowedKeys()           // array_keys(build(...)) === die Liste oben (assertSame mit sortierten Keys)
testNoPersonalDataAnywhere()            // Document mit Name 'Erika Muster', Telefon, E-Mail, Repair mit IMEI '356938035643809', unlockCode 'GEHEIM-4711':
                                        // json_encode(build(...)) enthält keinen dieser Werte
testHistoryOnlyStatusEventsWithoutNotes() // Ereignisse status(note 'intern'), email_sent → history hat 1 Eintrag, 'intern' nicht im JSON
testOmitsEmptyPriceAndDueDate()         // priceChf null, dueDate null → beide null
testStepsMarkCurrent()                  // status waiting_parts → steps[2].current true, steps[0..1].done true
testCancelledFlag()                     // status cancelled → cancelled true
testExpiredAfter30Days()                // closedAt now-31d → true; now-29d → false; null → false
```

- [ ] **Step 2:** Tests → FAIL. **Step 3:** implementieren. **Step 4:** Tests → PASS.
- [ ] **Step 5: Manuell prüfen:** Status-Link aus Detailansicht in privatem Fenster (ohne Login) öffnen → Seite erscheint, Handy-Ansicht ok; falscher Token (32 Hex) → 404; `closed_at` per SQL auf vor 31 Tagen setzen → 410; Response-Header mit `curl -I` prüfen.
- [ ] **Step 6: Commit** `feat: öffentliche Statusseite für Reparaturen`

---

### Task 9: Dokumentation

**Files:**
- Modify: `CLAUDE.md`

- [ ] **Step 1:** Ergänzen: Projektstruktur (neue Dateien), Kernfunktion «Reparaturen» (Status, Liste, Statusseite, E-Mails), Datenmodell (`repairs`, `repair_events`, `doc_type` `repair`, Nummer `RP-YYYY-NNNNNN`), Routen (intern + `/r/{token}`), Environment-Variablen (`MAILER_DSN`, `MAIL_FROM`, `MAIL_FROM_NAME`, `APP_URL`), Abschnitt **E-Mail-Versand** mit der Regel «Alle E-Mails des Systems laufen über `MailService` (Mailgun via `MAILER_DSN`); kein `mail()`, kein eigener SMTP-Code», Rollout-Schritte aus Spec «Rollout», Sprint-Status.
- [ ] **Step 2:** `ddev exec vendor/bin/phpunit` → grün.
- [ ] **Step 3: Commit** `docs: Reparaturen und zentralen E-Mail-Versand dokumentieren`
