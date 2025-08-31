# ADR-0001: Tech Stack Choice für Mediahuus CRM

## Status
Accepted

## Kontext
Wir entwickeln ein internes CRM für einen Handy-An-/Verkauf- & Reparatur-Shop mit 2 Filialen. Das System muss schnell entwickelt werden, wartbar sein und die Grundfunktionen (Login, Dokumentenerstellung, PDF-Generation) zuverlässig bereitstellen.

## Entscheidung

### Framework: Slim 4
**Begründung:**
- **Performance**: Minimaler Overhead, sehr schnelle Request-Response-Zeiten
- **Lernkurve**: Einfach zu verstehen und zu erweitern
- **Flexibilität**: Keine Conventions, die das kleine Team einschränken
- **Wartbarkeit**: Weniger "Magic", explizite Dependencies

### Template Engine: Twig 3
**Begründung:**
- **Security**: Automatisches Escaping, sichere Template-Syntax
- **Performance**: Kompiliert Templates zu PHP-Code
- **DX**: Gute IDE-Unterstützung, klare Syntax
- **Ökosystem**: Große Community, viele Extensions

### Database: Doctrine DBAL + Custom Repository Pattern
**Begründung:**
- **Flexibilität**: Query Builder für komplexe Queries
- **Performance**: Kein ORM-Overhead für einfache Operationen
- **Migration**: Robuste Schema-Migrations
- **Typsicherheit**: Prepared Statements, Parameter-Binding

### PDF Generation: mPDF
**Begründung:**
- **CSS Support**: Bessere HTML/CSS-Unterstützung als dompdf
- **Swiss Formatting**: Gute UTF-8 und Währungsformatierung
- **Performance**: Schneller bei A4-Dokumenten
- **Features**: Header/Footer-Support out-of-the-box

### Authentication: Native PHP Sessions + bcrypt
**Begründung:**
- **Einfachheit**: Keine komplexe JWT-Logik notwendig
- **Security**: bcrypt für Password-Hashing, CSRF-Protection
- **Performance**: Server-side sessions, keine Token-Validation
- **Wartbarkeit**: Standard PHP-Mechanismen

### Frontend: Tailwind CSS + Vanilla JS
**Begründung:**
- **Geschwindigkeit**: Utility-first, kein CSS-Framework-Overhead
- **Wartbarkeit**: Konsistente Design-Tokens
- **Bundle-Size**: Nur verwendete Utilities
- **DX**: Schnelles Prototyping

### Dependency Injection: PHP-DI
**Begründung:**
- **Performance**: Container-Caching
- **DX**: Auto-wiring, Attribute-Support
- **Testing**: Einfaches Mocking
- **Slim Integration**: Native Slim 4 Integration

## Konsequenzen

### Positiv
- Schnelle Entwicklung und Deployment
- Hohe Performance bei geringem Ressourcenverbrauch
- Einfache Erweiterbarkeit für zukünftige Features
- Gute Testbarkeit durch explizite Dependencies

### Negativ
- Mehr Boilerplate-Code als bei Full-Stack-Frameworks
- Weniger "Batteries included" - eigene Implementierungen nötig
- Kleineres Ökosystem als Laravel/Symfony

### Risiken
- Team muss Slim-Patterns lernen (mitigiert durch gute Dokumentation)
- Manuelle Konfiguration statt Convention (mitigiert durch klare Struktur)

## Nächste Schritte
1. DDEV-Konfiguration für PHP 8.2 + MariaDB
2. Composer-Setup mit gewählten Dependencies
3. Basis-Routing und Middleware-Setup
4. Template-Struktur und Asset-Pipeline