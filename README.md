Ich werde die README.md für das REDAXO Addon "Definitions" anpassen, um die neuen domain- und sprachabhängigen Funktionen zu dokumentieren.

```
┓            ┓• •       ┓  ┏•  • •     
┣┓┏┓┏┏┓┏┏┓┏┓┏┫┓╋┓┏┓┏┓  ┏┫┏┓╋┓┏┓┓╋┓┏┓┏┓┏
┗┛┗┻┛┗ ┗┗┛┛┗┗┻┗┗┗┗┛┛┗  ┗┻┗ ┛┗┛┗┗┗┗┗┛┛┗┛
```
# Definitions für REDAXO

Ein REDAXO-Addon zur zentralen Verwaltung von Konfigurationen und Definitionen über YAML-Dateien.

## Features

- Zentrale Verwaltung von Konfigurationen über YAML-Dateien
- Automatische Template-, Domain- und Sprachkontext-Erkennung
- Flexibles Caching-System für optimale Performance
- Erweiterbare Struktur durch Extension Points
- Unterstützung für Template-, Navigations- und Modulkonfigurationen
- Freie Konfigurationen für eigene Anwendungsfälle
- Automatisches Laden von Konfigurationen beim Systemstart
- Vererbung von Basis-Konfigurationen durch `extend`-Funktionalität
- Flexible Registrierung von Definition-Verzeichnissen
- Überschreiben von Definitionen durch strukturelle Übereinstimmung und Ladereihenfolge
- Priorisierte Ladereihenfolge (EARLY, NORMAL, LATE)
- Domain-spezifische Konfigurationen
- Sprachspezifische Konfigurationen

## Installation

1. Im REDAXO-Installer das Addon "definitions" auswählen und installieren
2. Mindestvoraussetzungen:
   - REDAXO >= 5.15.0
   - PHP >= 8.1

## Grundlegende Konzepte

Das Addon bietet drei Hauptanwendungsfälle:

1. **System-Definitionen**: Vordefinierte Strukturen für Templates, Navigation und Module
2. **Context-Definitionen**: Domain- und sprachspezifische Konfigurationen
3. **Freie Konfigurationen**: Beliebige eigene YAML-basierte Konfigurationen

## Empfohlene Verzeichnisstruktur

### Default usage via redaxo/data 
```
redaxo-root/
├── redaxo/
│   ├── data/
│   │   └── definitions/
│   │       ├── navigation/
│   │       │   ├── main.yml                  # Standard (domain- und sprachübergreifend)
│   │       │   ├── de/                       # Sprachspezifisch (domainübergreifend)
│   │       │   │   └── main.yml
│   │       │   └── example.com/              # Domainspezifisch
│   │       │       ├── main.yml              # Nur Domain
│   │       │       └── de/                   # Domain + Sprache
│   │       │           └── main.yml
│   │       ├── template/
│   │       │   ├── home.yml                  # Standard-Template
│   │       │   ├── article.yml               # Standard-Template
│   │       │   ├── de/                       # Sprachspezifische Templates
│   │       │   │   └── home.yml
│   │       │   └── example.com/              # Domainspezifische Templates
│   │       │       ├── home.yml
│   │       │       └── de/
│   │       │           └── home.yml
│   │       └── module/
│   │           ├── text_image/               # Standard-Module
│   │           │   └── default.yml
│   │           ├── gallery/
│   │           │   └── default.yml
│   │           ├── de/                       # Sprachspezifische Module
│   │           │   └── text_image/
│   │           │       └── default.yml
│   │           └── example.com/              # Domainspezifische Module
│   │               └── text_image/
│   │                   ├── default.yml
│   │                   └── de/
│   │                       └── default.yml
```

### Unter vewendung des Project Addons
```
redaxo-root/
├── redaxo/
│   └── addons/
│       └── project/                          # Für projekt-spezifische Anpassungen (optional)
│           └── definitions/
│               ├── navigation/
│               ├── template/
│               └── module/
```

### Unter verwendung des Theme Addons
```
redaxo-root/
├── themes/
│   └── private/
│       └── definitions/
│           ├── navigation/
│           │   ├── main.yml                  # Standard (domain- und sprachübergreifend)
│           │   ├── de/                       # Sprachspezifisch (domainübergreifend)
│           │   │   └── main.yml
│           │   └── example.com/              # Domainspezifisch
│           │       ├── main.yml              # Nur Domain
│           │       └── de/                   # Domain + Sprache
│           │           └── main.yml
│           ├── template/
│           └── module/
```

## System-Definitionen verwenden

### Automatische Kontext-Erkennung

Das Definitions Addon erkennt automatisch den Template-Key des aktuell verwendeten Artikels, die aktuelle Domain und die aktuelle Sprache. Dies ermöglicht eine dynamische Zusammenstellung der Definitionen ohne manuelle Verkettung.

#### Template-Abhängige Definitionen

```php
// Im Template oder Modul
$templateConfig = BSC\config::get('template');
// Lädt automatisch die Template-Definition basierend auf dem aktiven Template

$moduleConfig = BSC\config::get('module');
// Lädt automatisch die Modul-Definition passend zum Template-Kontext
```

#### Domain-Abhängige Definitionen

Definitionen können domainspezifisch abgelegt werden:

```
definitions/
└── template/
    ├── home.yml                  # Standard für alle Domains
    └── example.com/              # Spezifisch für example.com
        └── home.yml              # Überschreibt Standards für diese Domain
```

#### Sprach-Abhängige Definitionen

Definitionen können sprachspezifisch abgelegt werden:

```
definitions/
└── template/
    ├── home.yml                  # Standard für alle Sprachen
    └── de/                       # Spezifisch für deutsche Inhalte
        └── home.yml              # Überschreibt Standards für diese Sprache
```

#### Komplexe Kontexte

Die verschiedenen Kontexte können beliebig kombiniert werden:

```
definitions/
└── template/
    ├── home.yml                       # Standard
    ├── de/                            # Sprachspezifisch
    │   └── home.yml
    ├── example.com/                   # Domainspezifisch
    │   ├── home.yml
    │   └── de/                        # Domain + Sprache
    │       └── home.yml
    └── de/home/                       # Sprache + Template
        └── default.yml
```

### Prioritätsreihenfolge der Definitionen

Die Definitionen werden in folgender Prioritätsreihenfolge geladen und überschrieben:

1. Allgemeine Basisdefinitionen (z.B. `/template/home.yml`)
2. Template-spezifische Definitionen (z.B. `/template/default/home.yml`)
3. Sprachspezifische Definitionen (z.B. `/template/de/home.yml`)
4. Sprach- und Template-spezifische Definitionen (z.B. `/template/de/default/home.yml`)
5. Domainspezifische Definitionen (z.B. `/template/example.com/home.yml`)
6. Domain- und Template-spezifische Definitionen (z.B. `/template/example.com/default/home.yml`)
7. Domain- und Sprachspezifische Definitionen (z.B. `/template/example.com/de/home.yml`)
8. Domain-, Sprach- und Template-spezifische Definitionen (z.B. `/template/example.com/de/default/home.yml`)

Spätere Definitionen überschreiben frühere bei gleichem Schlüssel. Dies ermöglicht eine flexible Anpassung von Konfigurationen für verschiedene Kontexte.

### Definition-Verzeichnisse registrieren

Die Registrierung erfolgt nach einer klaren Prioritätenreihenfolge:

1. **Core/AddOn Definitionen (EARLY):**
```php
rex_extension::register('BSC_CONFIG_LOAD', function(rex_extension_point $ep) {
    $schemes = $ep->getSubject();
    if ($addon = rex_addon::get('mein_addon')) {
        $schemes[] = $addon->getPath('definitions/*.yml');
    }
    return $schemes;
}, rex_extension::EARLY);
```

2. **Theme Definitionen (NORMAL):**
```php
rex_extension::register('BSC_CONFIG_LOAD', function(rex_extension_point $ep) {
    $schemes = $ep->getSubject();
    if (rex_addon::exists('theme')) {
        $schemes[] = theme_path::base('private/definitions/navigation/*.yml');
        $schemes[] = theme_path::base('private/definitions/template/*.yml');
        $schemes[] = theme_path::base('private/definitions/module/*/*.yml');
    }
    return $schemes;
});
```

3. **Project-Addon Definitionen (LATE):**
```php
rex_extension::register('BSC_CONFIG_LOAD', function(rex_extension_point $ep) {
    $schemes = $ep->getSubject();
    $projectPath = rex_addon::get('project')->getPath();
    $schemes[] = $projectPath . 'definitions/navigation/*.yml';
    $schemes[] = $projectPath . 'definitions/template/*.yml';
    $schemes[] = $projectPath . 'definitions/module/*/*.yml';
    return $schemes;
}, rex_extension::LATE);
```

### Vererbung mittels extend

Die `extend`-Funktionalität ermöglicht die Vererbung von Basis-Konfigurationen:

```yaml
# /themes/private/definitions/template/base.yml
template:
   base:
      sections:
         - header
         - content
         - footer
      defaults:
         show_breadcrumb: true
         cache_ttl: 3600

# /themes/private/definitions/template/home.yml
extend: base.yml  # erbt die Basis-Konfiguration

template:
   home:
      sections:
         - header
         - slider    # eigene Sektion
         - content
         - footer
      defaults:
         show_breadcrumb: false  # überschreibt einzelnen Wert
```

### Überschreiben von Definitionen

Das Überschreiben erfolgt durch strukturelle Übereinstimmung und Ladereihenfolge:

```yaml
# /redaxo/data/definitions/template/default.yml
template:
   default:
      name: "Standard Template"
      sections:
         - header
         - content
         - footer

# /themes/private/definitions/template/default.yml
template:
   default:
      name: "Theme Template"    # überschreibt den Namen
      sections:
         - header
         - slider              # überschreibt die sections
         - content
         - footer

# /redaxo/addons/project/definitions/template/default.yml
template:
   default:
      name: "Projekt Template"  # überschreibt den Namen erneut
```

## Domain-Konfiguration

### Einrichtung von Domain-Mappings

Domain-Mappings können über die Konfigurationsseite des Addons oder programmatisch eingerichtet werden:

```php
// Domain-Mapping registrieren
\BSC\Domain\DomainContextProvider::registerDomainMapping('example.com', 'example_com');
```

### YRewrite Integration

Bei Verwendung des YRewrite-Addons werden Domains automatisch erkannt:

```php
// Wird automatisch in der boot.php bei vorhandenem YRewrite Addon ausgeführt
if (rex_addon::exists('yrewrite') && rex_addon::get('yrewrite')->isAvailable()) {
    rex_extension::register('BSC_DOMAIN_DETECTION', function(rex_extension_point $ep) {
        $domain = \rex_yrewrite::getCurrentDomain();
        if ($domain) {
            return $domain->getName();
        }
        return $ep->getSubject();
    }, rex_extension::EARLY);
}
```

### Zugriff auf Domain-Informationen

```php
// Aktuelle Domain ermitteln
$currentDomain = \BSC\Domain\DomainContextProvider::getCurrentDomain();

// Domain-Key für Verzeichnisstruktur ermitteln
$domainKey = \BSC\Domain\DomainContextProvider::getDomainKey();
```

## Sprach-Konfiguration

### REDAXO-Sprachintegration

Das Addon integriert sich nahtlos mit REDAXO's Sprachsystem:

```php
// Aktuelle Sprache ermitteln
$currentLanguage = \BSC\Language\LanguageContextProvider::getCurrentLanguage();

// Alle verfügbaren Sprachen abrufen
$languages = \BSC\Language\LanguageContextProvider::getAvailableLanguages();
```

## Die Config-Klasse verwenden

Die Config-Klasse ist das zentrale Element für den Zugriff auf Definitionen und Konfigurationen.

### System-Definitionen abrufen

```php
use BSC\config;

// Template-Definition abrufen
$templateConfig = config::get('template');

// Navigations-Definition abrufen  
$navigationConfig = config::get('navigation.main');

// Modul-Definition abrufen
$moduleConfig = config::get('module');
```

### Kontext-bewusster Zugriff

Die Config-Klasse berücksichtigt automatisch den aktuellen Domain-, Sprach- und Template-Kontext:

```php
// Lädt automatisch die passende Konfiguration für aktuelle Domain, Sprache und Template
$config = config::get('template');
```

### Freie Konfigurationen

Die Config-Klasse eignet sich auch für eigene Konfigurationen:

```php
use BSC\config;

// Konfigurationsdateien laden
config::loadConfig([
    'resources/*.yml',           // Verzeichnis
    'config/listener.yml',       // Einzelne Datei
    'services/*.yml'            // Weiteres Verzeichnis
]);

// Zugriff auf Konfigurationen
$apiKey = config::get('resources.api.key');
$serviceConfig = config::get('services.mail');
```

#### Beispiel: Event Listener Konfiguration

```yaml
# services/listener.yml
services:
   listener:
      PAGE_HEADER:
         - SecurityHeaderService::addSecurityHeaders
      PACKAGES_INCLUDED:
         - CacheService::clearCache
```

```php
// Listener registrieren
$listeners = config::get('services.listener');
foreach($listeners as $event => $callbacks) {
    foreach($callbacks as $callback) {
        rex_extension::register($event, $callback);
    }
}
```

#### Beispiel: API Konfiguration

```yaml
# resources/api.yml
api:
   endpoints:
      users: "https://api.example.com/users"
      posts: "https://api.example.com/posts"
   settings:
      timeout: 30
      retries: 3
```

### Dynamische Konfigurationen

Die Config-Klasse unterstützt auch das dynamische Setzen von Werten:

```php
// Einzelwert setzen
config::set('cache.enabled', true);

// Komplette Sektion setzen
config::set('mail', [
    'host' => 'smtp.example.com',
    'port' => 587
]);

// Wert an String anhängen
config::addStringTo('assets.css', 'custom.css');
```

## Debug-Modus

Zur Überprüfung der geladenen Definitionen:

```php
rex_extension::register('BSC_CONFIG_LOADED', function(rex_extension_point $ep) {
    if (rex::isDebugMode()) {
        dump('Geladene Definition Pfade:', BSC\config::getAll());
        
        if ($templateConfig = BSC\config::get('template')) {
            dump('Template Konfigurationen:', $templateConfig);
        }
        
        // Domain- und Sprachkontext ausgeben
        dump('Kontext-Informationen:', [
            'domain' => \BSC\Domain\DomainContextProvider::getDomainKey(),
            'language' => \BSC\Language\LanguageContextProvider::getCurrentLanguage(),
            'template' => BSC\base::getTemplateKey()
        ]);
    }
});
```

## Extension Points

Das Addon bietet folgende Extension Points:

### Core Extension Points
- `BSC_CONFIG_LOAD`: Beim Laden der Konfigurationen, kann zur Modifikation der Suchpfade genutzt werden
- `BSC_CONFIG_LOADED`: Nach dem Laden aller Konfigurationen, ideal für Debugging oder Nachbearbeitung
- `BSC_DEFINITIONS_LOAD`: Beim Laden der Definitionen, ermöglicht Modifikation der Definition-Suchpfade
- `BSC_DOMAIN_DETECTION`: Ermöglicht Einflussnahme auf die Domain-Erkennung
- `BSC_LANGUAGE_DETECTION`: Ermöglicht Einflussnahme auf die Sprach-Erkennung

### Definition-Handling Extension Points
- `BSC_DEFINITION_SET`: Vor dem Setzen einer Definition
- `BSC_DEFINITION_SET_AFTER`: Nach dem Setzen einer Definition
- `BSC_DEFINITION_OVERWRITE`: Vor dem Überschreiben einer Definition
- `BSC_DEFINITION_OVERWRITE_MERGED`: Nach dem Zusammenführen, aber vor dem Setzen einer überschriebenen Definition
- `BSC_DEFINITION_VALUE_SET`: Vor dem Setzen eines Werts
- `BSC_DEFINITION_VALUE_SET_AFTER`: Nach dem Setzen eines Werts
- `BSC_DEFINITION_STRING_ADD`: Vor dem Hinzufügen eines Strings
- `BSC_DEFINITION_STRING_ADD_AFTER`: Nach dem Hinzufügen eines Strings

### Cache-Handling Extension Points
- `DEFINITION_CACHE_KEY`: Zur Modifikation des Cache-Keys
- `DEFINITION_BEFORE_CACHE_LOAD`: Vor dem Laden des Caches
- `DEFINITION_AFTER_CACHE_LOAD`: Nach dem Laden des Caches
- `DEFINITION_BEFORE_CACHE_SAVE`: Vor dem Speichern des Caches
- `DEFINITION_AFTER_CACHE_SAVE`: Nach dem Speichern des Caches

## Best Practices

1. **Strukturierung:**
   - Nutzen Sie die empfohlene Verzeichnisstruktur
   - Gruppieren Sie Konfigurationen nach logischen Einheiten
   - Verwenden Sie sprechende Dateinamen
   - Legen Sie domainspezifische Konfigurationen nur bei Bedarf an

2. **Kontextualisierung:**
   - Nutzen Sie Domain-Verzeichnisse nur für domain-spezifische Anpassungen
   - Legen Sie sprachspezifische Konfigurationen in separaten Sprach-Verzeichnissen ab
   - Kombinieren Sie Domain-, Sprach- und Template-Kontexte bei Bedarf

3. **Ladereihenfolge:**
   - Beachten Sie die EARLY/NORMAL/LATE Prioritäten
   - Nutzen Sie das Project-Addon für finale Überschreibungen
   - Laden Sie projekt-spezifische Konfigurationen früh im Boot-Prozess

4. **Konfigurationsmanagement:**
   - Nutzen Sie `extend` für die Vererbung innerhalb einer Ebene
   - Verwenden Sie strukturelle Überschreibungen für ebenenübergreifende Anpassungen
   - Halten Sie sensible Daten in separaten Konfigurationsdateien

5. **Performance:**
   - Aktivieren Sie das Caching in Produktivumgebungen
   - Vermeiden Sie das mehrfache Laden gleicher Konfigurationen
   - Cachen Sie häufig verwendete Konfigurationswerte

6. **Entwicklung:**
   - Aktivieren Sie den Debug-Modus während der Entwicklung
   - Dokumentieren Sie Ihre Konfigurationsstrukturen
   - Nutzen Sie Versionskontrolle für Konfigurationsdateien

## Support & Lizenz

- **Autor:** Joachim Doerr
- **Support:** https://github.com/basecondition/definitions
