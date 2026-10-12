# Rechnungen mit Swiss QR-Einzahlungsschein – Design

Datum: 2026-10-12
Status: Entwurf zur Durchsicht

## Ziel

Neben Kostenvoranschlag, Ankauf, Quittung und Versicherungsgutachten sollen Mitarbeitende im CRM
**Rechnungen** erstellen können. Eine Rechnung wird als **ein einziges PDF** heruntergeladen, das
die Rechnung und einen gültigen **Swiss QR-Einzahlungsschein** (QR-Rechnung nach SIX-Standard)
enthält.

Erfolgskriterien:

- Rechnung mit beliebig vielen Positionen in wenigen Minuten erfasst.
- Während der Eingabe sieht man live die Beträge exkl. MWST, die MWST pro Satz und das Total inkl. MWST.
- Der QR-Code wird von Schweizer Banking-Apps ohne Fehler gelesen; Betrag, Konto und Referenz stimmen.
- Rechnungen erscheinen wie alle anderen Dokumente im Dashboard und im Excel-Export.

## Feste Vorgaben

| Thema | Vorgabe |
|---|---|
| Absender (immer) | Mediahuus GmbH, Hauptstrasse 38, 4153 Reinach |
| Konto | IBAN CH13 0630 0507 6501 6783 0 (Valiant Bank, normale IBAN, keine QR-IBAN) |
| MWST-Nummer | CHE-178.268.076 MWST |
| MWST-Sätze | pro Position wählbar: 8.1 % (vorausgewählt), 2.6 %, 0 % |
| Preiseingabe | inklusive MWST |
| Zahlungsfrist | Standard 10 Tage, pro Rechnung änderbar |
| Filiale (intern) | immer Mediahuus Reinach (kein Feld im Formular) |
| Rechnungsnummer | `RE-JJJJ-MM-TT-NN`, laufende Nummer pro Rechnungstag ab `01` |
| QR-Referenz | SCOR (ISO 11649 Creditor Reference) aus der Rechnungsnummer |

Die Absenderdaten (Name, Adresse, IBAN, MWST-Nummer) werden zentral in einer Konfiguration
(`config/invoice.php`) gepflegt, nicht verteilt im Code. Telefon und E-Mail im PDF-Kopf kommen aus
der Filiale Reinach (`branches`).

## Nicht im Umfang

- Status offen/bezahlt, überfällige Rechnungen, Mahnwesen
- Rechnungen bearbeiten oder stornieren
- Versand per E-Mail
- Kundenstamm / Adressbuch
- Telefon und E-Mail des Empfängers

## Datenmodell

Migration `database/migrations/013_create_invoices_tables.sql`:

- `documents.doc_type` ENUM wird um `invoice` erweitert.
- Für Rechnungen gilt in `documents`:
  - `branch_id` = Filiale Reinach
  - `customer_name` = Anzeigename (Firma, sonst Name) – damit Liste, Filter und Export ohne Sonderfall funktionieren
  - `customer_street`, `customer_zip`, `customer_city` = Empfängeradresse (Pflicht)
  - `customer_phone`, `customer_email` = NULL

```sql
CREATE TABLE invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    document_id INT NOT NULL UNIQUE,
    company_name VARCHAR(70) NULL,
    contact_name VARCHAR(70) NULL,
    invoice_date DATE NOT NULL,
    payment_term_days INT NOT NULL DEFAULT 10,
    due_date DATE NOT NULL,
    total_net_chf DECIMAL(12,2) NOT NULL,
    total_vat_chf DECIMAL(12,2) NOT NULL,
    total_gross_chf DECIMAL(12,2) NOT NULL,
    qr_reference VARCHAR(25) NOT NULL,
    FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE
);

CREATE TABLE invoice_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT NOT NULL,
    position INT NOT NULL,
    description TEXT NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    unit_price_gross_chf DECIMAL(12,2) NOT NULL,
    vat_rate DECIMAL(4,2) NOT NULL,
    line_total_gross_chf DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
);
```

`qr_reference` wird beim Erstellen gespeichert, damit sie unveränderlich bleibt.

## Berechnung

Eingabe der Preise inklusive MWST. Eine eigene, reine Klasse `InvoiceCalculator` (ohne DB/HTTP)
rechnet:

1. Positionstotal = Menge × Einzelpreis, kaufmännisch gerundet auf 0.01.
2. Positionen werden pro MWST-Satz summiert (Brutto pro Satz).
3. MWST pro Satz = Brutto × Satz / (100 + Satz), gerundet auf 0.01.
4. Netto pro Satz = Brutto − MWST.
5. Totale: Summe Netto, Summe MWST, Summe Brutto. Rechnungsbetrag (auch im QR-Teil) = Summe Brutto.

Gerechnet wird mit Rappen als Integer bzw. `bcmath`, nicht mit Floats. Gespeichert wird
ausschliesslich das Ergebnis des Servers. Das JavaScript im Formular verwendet dieselbe Formel nur
für die Live-Vorschau.

## Rechnungsnummer und Referenz

- Format `RE-JJJJ-MM-TT-NN`, z. B. `RE-2026-10-12-01`; Datum = Rechnungsdatum. `NN` beginnt jeden
  Tag bei `01` und wird ab der 100. Rechnung eines Tages automatisch dreistellig.
- Vergabe: höchste bestehende Nummer mit demselben Datumspräfix + 1. Kollidiert der UNIQUE-Index
  (zwei gleichzeitige Speichervorgänge), wird die Nummer neu ermittelt und bis zu 3 Mal erneut versucht.
- Gezielte Verbesserung: `DocumentRepository::generateDocNumber()` wird für die bestehenden Typen
  ebenfalls von `COUNT(*)+1` auf „höchste Nummer + 1“ umgestellt (heute führen gelöschte Dokumente
  zu doppelten Nummern). Das Format der bestehenden Typen bleibt unverändert.
- SCOR-Referenz: Rechnungsnummer ohne Bindestriche (`RE2026101201`) mit RF-Prüfziffer nach
  ISO 11649, erzeugt über die Bibliothek, z. B. `RF.. RE20 2610 1201`.

## Formular und Ablauf

Routen (in der geschützten Gruppe in `config/routes.php`):

```
GET  /invoices/create     → Formular
POST /invoices            → Speichern, Weiterleitung auf Detailansicht
GET  /invoices/{id}       → Detailansicht mit PDF-Button
GET  /invoices/{id}/pdf   → PDF-Download (Rechnung + QR-Zahlteil)
```

`templates/invoices/create.html.twig`, aufgebaut wie das Quittungsformular:

1. **Empfänger**: Firma, Name (mindestens eines), Strasse, PLZ, Ort (Pflicht).
2. **Rechnungsdaten**: Rechnungsdatum (Standard heute), Zahlungsfrist in Tagen (Standard 10);
   Fälligkeitsdatum wird live angezeigt.
3. **Positionen**: Beschreibung, Menge, Einzelpreis inkl. MWST, MWST-Satz (Select, 8.1 % vorausgewählt);
   Zeilen hinzufügen/entfernen.
4. **Live-Vorschau** bei jeder Eingabe: Total pro Zeile; Zusammenfassung mit Total exkl. MWST,
   MWST pro Satz (mit Basisbetrag) und Total inkl. MWST. Auf dem Handy bleibt die Zusammenfassung
   unten sichtbar.

Nach dem Speichern: Weiterleitung auf die Detailansicht mit PDF-Button (wie bei der Quittung).
Bei Fehlern wird das Formular mit Meldungen neu angezeigt; alle Eingaben inkl. Positionen bleiben erhalten.

Buttons und Icons verwenden das im Projekt übliche Grün (Emerald, `#10b981`).

### Validierung (serverseitig, massgebend)

- Firma oder Name ausgefüllt; je max. 70 Zeichen.
- Strasse max. 70, PLZ max. 16, Ort max. 35 Zeichen (Grenzen der QR-Rechnung).
- Rechnungsdatum gültig; Zahlungsfrist ganze Zahl 0–365.
- Mindestens eine Position; Beschreibung nicht leer; Menge > 0; Einzelpreis ≥ 0;
  MWST-Satz aus der erlaubten Liste.
- Total inkl. MWST zwischen 0.01 und 999'999'999.99 (Grenze der QR-Rechnung).
- Der QR-Teil wird vor dem Speichern mit der Bibliothek validiert. Ist er ungültig, wird nichts
  gespeichert und eine verständliche Meldung angezeigt.

Dokument, Rechnung und Positionen werden in **einer DB-Transaktion** gespeichert.

## PDF

Eigene Klasse `InvoicePdfService` (nicht in `PdfService`, der bereits ~1'500 Zeilen hat), TCPDF A4,
Stil (Logo, Schrift, Fusszeile) wie die bestehenden Dokumente:

- **Kopf**: Logo links; rechts Mediahuus GmbH, Hauptstrasse 38, 4153 Reinach, Telefon, E-Mail,
  CHE-178.268.076 MWST.
- **Empfänger-Adressblock** rechts an der Position für Fenstercouverts mit Fenster rechts (CH-Norm C5/6): x = 118 mm, y = 55 mm, Breite 80 mm. Die Absenderangaben im Kopf enden oberhalb von y = 45 mm.
- **Titel** „Rechnung RE-2026-10-12-01“, darunter Rechnungsdatum, Zahlbar bis, Zahlungsfrist.
- **Positionstabelle**: Pos., Beschreibung, Menge, Einzelpreis, MWST, Total (inkl. MWST, CHF).
- **Summenblock**: Total exkl. MWST; MWST je Satz mit Basisbetrag; Total CHF inkl. MWST (fett).
- **Zahlungshinweis** mit Fälligkeitsdatum und IBAN in Textform.
- **QR-Zahlteil** (Empfangsschein + Zahlteil, 105 mm, Scherenlinie) unten auf der letzten Seite,
  erzeugt mit `sprain/swiss-qr-bill` (TCPDF-Ausgabe). Reicht der Platz nicht, folgt er auf einer
  eigenen neuen Seite; nichts überlappt den Zahlteil.
  - Konto: IBAN, Mediahuus GmbH, Hauptstrasse 38, 4153 Reinach (strukturierte Adresse)
  - Betrag, Währung CHF
  - Referenz: SCOR
  - Zahlbar durch: Empfänger (Firma bzw. Name, Adresse, Land CH)
  - Zusätzliche Information: „Rechnung RE-2026-10-12-01“

Dateiname: `Rechnung_RE-2026-10-12-01.pdf`.

## Einbindung

- **Dashboard**: neue Kachel „Rechnung“; Typfilter „Rechnung“; Badge in der Liste; Detail-Modal mit
  Empfänger, Positionen, Summen und PDF-Button.
- **Excel-Export**: Rechnungen mit Nummer, Datum, Empfänger, Netto, MWST, Total, Fälligkeit.
- **Model/Repository**: `Invoice`, `InvoiceItem`, `InvoiceRepository` nach bestehendem Muster;
  `Document::TYPE_INVOICE` mit Label „Rechnung“.
- `url_for` in `config/container.php` erhält die neuen Routennamen.

## Abhängigkeiten

- `sprain/swiss-qr-bill` ^5.3 (bringt `endroid/qr-code`, `symfony/validator`, `symfony/intl`,
  `kmukku/php-iso11649` mit). Voraussetzungen auf Prod erfüllt: PHP 8.3, `bcmath`, `dom`, `gd`.
- `composer.lock` wird künftig versioniert (heute in `.gitignore`), damit lokal und Prod dieselben
  Versionen verwenden. Die Composer-Plattform wird auf PHP 8.3 festgelegt.
- PHPUnit als Dev-Abhängigkeit für die Tests.

## Tests

PHPUnit (testgetrieben) für die reine Logik:

- `InvoiceCalculator`: einzelne Position, Mengen mit Dezimalstellen, gemischte Sätze, Satz 0 %,
  Rundungsfälle.
- Nummernvergabe: erste Nummer des Tages, Fortsetzung, neuer Tag beginnt bei 01, dreistellig ab 100.
- SCOR-Referenz: gültige RF-Prüfziffer.
- QR-Rechnung: Bibliothek akzeptiert die erzeugten Daten; ungültige Adresse wird abgelehnt.

Manuell:

- Formular und Live-Vorschau im Browser (Desktop und Handybreite).
- Erzeugtes PDF ansehen (Layout, Seitenumbruch bei vielen Positionen, QR-Teil auf eigener Seite).
- QR-Code mit einer Banking-App scannen (durch Mediahuus, ohne Zahlung auszulösen).

## Deployment (Prod, manuell)

Jeder Schritt wird vorher mit dem Projektinhaber abgestimmt.

1. Backup: DB (`documents` und neue Tabellen betroffen) und geänderte Dateien nach `~/crm_backups/`.
2. Migration 013 ausführen.
3. `vendor/` lokal mit `composer install --no-dev --optimize-autoloader` für PHP 8.3 bauen und hochladen.
4. Code hochladen, Twig-Cache (`public/false`) leeren.
5. Prüfen: Login, Formular, eine Testrechnung erzeugen und PDF prüfen; Testrechnung danach mit
   Rücksprache entfernen.

## Risiken

- **Abhängigkeiten auf Prod**: Ohne Composer auf dem Server muss `vendor/` vollständig hochgeladen
  werden; ein fehlerhafter Upload legt die ganze App lahm. Gegenmassnahme: Backup des bisherigen
  `vendor/` und Austausch per Umbenennen statt Überschreiben.
- **QR-Lesbarkeit**: Wird durch die Bibliothek abgesichert; finale Bestätigung erst durch einen
  Scan mit einer Banking-App.
