# CSRF-Schutz und Session-Härtung – Design

Datum: 2026-10-13
Status: Entwurf zur Durchsicht

## Ziel

Alle zustandsändernden Requests (POST/PUT/PATCH/DELETE) im CRM sind gegen Cross-Site Request
Forgery geschützt. Das Session-Cookie ist gehärtet. Wird vor dem Kundenstamm umgesetzt
(eigener Branch `feat/csrf`), damit neue Formulare den Schutz direkt nutzen.

Erfolgskriterien:

- Ein POST ohne gültiges Token wird mit 403 abgewiesen, auch auf `/login`.
- Alle bestehenden Formulare, Logout und «Cache leeren» funktionieren unverändert.
- Mehrere offene Tabs stören sich nicht gegenseitig.

## Bestand

- 10 Templates mit POST-Formularen: `base.html.twig` (Logout), `auth/login`,
  `estimates/create`, `receipts/create`, `invoices/create`, `purchases/create`,
  `insurance/create`, `users/create`, `users/edit`, `users/index` (Status umschalten).
- 1 JavaScript-POST: `fetch('/clear-cache', { method: 'POST' })` in `dashboard.html.twig`.
- `session_start()` verstreut im `AuthService` mit PHP-Standard-Cookie; `SESSION_NAME` aus `.env`
  wird nicht verwendet; kein `HttpOnly`/`SameSite`/`Secure`.
- `public/index.php`: `addErrorMiddleware(true, true, true)` zeigt Fehlerdetails auch in Produktion.

## Nicht im Umfang

- Login-Rate-Limiting, Passwort-Richtlinien, Content-Security-Policy.

## Design

### `App\Middleware\SessionMiddleware` (global)

- Startet die Session, falls noch keine aktiv ist, mit:
  - Name aus `$_ENV['SESSION_NAME']` (Fallback `mediahuus_crm_session`)
  - `httponly = true`, `samesite = Lax`, `path = /`
  - `secure = true`, wenn der Request über HTTPS kommt (`Uri::getScheme() === 'https'` oder
    `X-Forwarded-Proto: https`)
  - `session.use_strict_mode = 1`
- Die bestehenden `session_start()`-Aufrufe im `AuthService` bleiben (greifen nur, wenn keine
  Session aktiv ist, z. B. in CLI/Tests).

Hinweis: Durch den neuen Cookie-Namen sind beim Deployment alle Mitarbeitenden einmal abgemeldet.

### `App\Services\CsrfTokenManager`

- `getToken(): string` – liefert `$_SESSION['_csrf_token']`, erzeugt es bei Bedarf
  (`bin2hex(random_bytes(32))`).
- `isValid(?string $token): bool` – `hash_equals` gegen das Session-Token; leer/nicht-String → false.
- `regenerate(): void` – neues Token.
- Session-Zugriff über ein kleines, injizierbares Array-Backend (Standard: `$_SESSION`), damit
  testbar ohne echte Session.

### `App\Middleware\CsrfMiddleware` (global)

- Methoden `GET`, `HEAD`, `OPTIONS` → durchlassen.
- Sonst Token aus Body-Feld `_csrf` (geparster Body, auch multipart) oder Header `X-CSRF-Token`.
- Gültig → weiter. Ungültig →
  - **Upload zu gross:** Body leer und `Content-Length` > `post_max_size` → 413 mit Seite
    «Die hochgeladenen Dateien sind zu gross.»
  - sonst 403, Template `errors/csrf.html.twig`: «Die Sitzung ist abgelaufen oder das Formular ist
    veraltet. Bitte Seite neu laden und erneut versuchen.» mit Link zurück
    (Referer, falls gleiche Herkunft, sonst `/`).
    - Bei `Accept: application/json` oder gesetztem Header `X-CSRF-Token` stattdessen JSON
    `{"success":false,"error":"csrf"}` mit demselben Statuscode.
  - Warnung ins Log (Pfad, Methode, User-ID falls vorhanden).

### Reihenfolge in `public/index.php`

Slim führt die zuletzt hinzugefügte Middleware zuerst aus (LIFO). Aufrufe im Code:

```php
$app->add(CsrfMiddleware::class);
$app->addBodyParsingMiddleware();
$app->add(SessionMiddleware::class);
$app->addErrorMiddleware($debug, true, true);
```

Ausführung damit: Error → Session → BodyParsing → Csrf → Routing → Auth (Routen-Gruppe).
Csrf sieht so den geparsten Body und eine gestartete Session.

### Token-Wechsel

- `AuthService::startSession()` (Login) und `endSession()` (Logout) rufen
  `CsrfTokenManager::regenerate()` auf.
- Sonst bleibt das Token für die ganze Session gleich (mehrere Tabs funktionieren).

### Twig

- Funktionen `csrf_token()` und `csrf_field()` (gibt
  `<input type="hidden" name="_csrf" value="…">` aus, `is_safe: html`), registriert in
  `config/container.php`.
- `csrf_field()` in alle 10 POST-Formulare.
- `base.html.twig`: `<meta name="csrf-token" content="{{ csrf_token() }}">`;
  `clearCache()` sendet `headers: {'X-CSRF-Token': …}`.

### Fehleranzeige

`public/index.php`: `addErrorMiddleware($debug, true, true)` mit
`$debug = filter_var($_ENV['DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN)`.

## Tests

- `tests/Services/CsrfTokenManagerTest.php`: Token stabil, `isValid` true/false/leer,
  `regenerate` ändert Token.
- `tests/Middleware/CsrfMiddlewareTest.php`: GET ohne Token → durch; POST gültig (Body) → durch;
  POST gültig (Header) → durch; POST ohne/mit falschem Token → 403; JSON-Variante; zu grosser
  Upload → 413.
- Manuell (DDEV): Login, Logout, alle 5 Erfassungsformulare (Ankauf mit Ausweisfotos),
  Benutzer anlegen/bearbeiten/Status, «Cache leeren»; Formular mit manipuliertem Token → 403;
  Cookie-Flags im Browser prüfen.

## Deployment

Keine Migration. Nach dem Hochladen sind alle einmal abgemeldet (neuer Cookie-Name).

## Risiken

- **Offenes Formular über Session-Ablauf hinaus** → 403 statt stillem Fehler; Seite erklärt, was
  zu tun ist. Eingaben gehen verloren (wie heute beim Login-Redirect).
- **Middleware-Reihenfolge falsch** → Body nicht geparst oder Session nicht gestartet; abgefangen
  durch manuellen Test aller Formulare.
