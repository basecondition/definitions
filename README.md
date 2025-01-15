```
 ┓            ┓
 ┣┓┏┓┏┏┓┏┏┓┏┓┏┫┓╋┓┏┓┏┓
 ┗┛┗┻┛┗ ┗┗┛┛┗┗┻┗┗┗┗┛┛┗━━
```
## Definitions für REDAXO 5

Ein REDAXO-Addon zur zentralen Verwaltung von Konfigurationen und Definitionen über YAML-Dateien.

## Features

- Zentrale Verwaltung von Konfigurationen über YAML-Dateien
- Automatische Template- und Modulkontext-Erkennung
- Flexibles Caching-System für optimale Performance
- Erweiterbare Struktur durch Extension Points
- Unterstützung für Template-, Navigations- und Modulkonfigurationen
- Freie Konfigurationen für eigene Anwendungsfälle
- Automatisches Laden von Konfigurationen beim Systemstart
- Vererbung von Basis-Konfigurationen durch `extend`-Funktionalität
- Flexible Registrierung von Definition-Verzeichnissen
- Überschreiben von Definitionen durch strukturelle Übereinstimmung und Ladereihenfolge
- Priorisierte Ladereihenfolge (EARLY, NORMAL, LATE)

## Installation

1. Im REDAXO-Installer das Addon "definitions" auswählen und installieren
2. Mindestvoraussetzungen:
   - REDAXO >= 5.15.0
   - PHP >= 8.1

## Grundlegende Konzepte

Das Addon bietet zwei Hauptanwendungsfälle:

1. **System-Definitionen**: Vordefinierte Strukturen für Templates, Navigation und Module
2. **Freie Konfigurationen**: Beliebige eigene YAML-basierte Konfigurationen

### Empfohlene Verzeichnisstruktur

```
redaxo-root/
├── themes/
│   └── private/
│       └── definitions/
│           ├── navigation/
│           ├── template/
│           └── module/
├── redaxo/
│   ├── data/
│   │   └── definitions/
│   │       ├── navigation/
│   │       ├── template/
│   │       └── module/
│   └── addons/
│       └── project/
│           └── definitions/
│               ├── navigation/
│               ├── template/
│               └── module/
```

## System-Definitionen verwenden

### Automatische Kontext-Erkennung

Das Definitions Addon erkennt automatisch den Template-Key des aktuell verwendeten Artikels. Dies ermöglicht eine dynamische Zusammenstellung sowohl der Template- als auch der Modul-Definitionen ohne manuelle Verkettung.

#### Template-Abhängige Definitionen

```php
// Im Template oder Modul
$templateConfig = BSC\config::get('template');
// Lädt automatisch die Template-Definition basierend auf dem aktiven Template

$moduleConfig = BSC\config::get('module');
// Lädt automatisch die Modul-Definition passend zum Template-Kontext
```

#### Verzeichnisstruktur für Module
```
definitions/
└── module/
    ├── blog/                  # Template-Key "blog"
    │   ├── text_image.yml     # Modul-Definition für blog
    │   └── gallery.yml
    ├── shop/                  # Template-Key "shop"
    │   ├── text_image.yml     # Gleiche Module, andere Definition
    │   └── gallery.yml
    └── default/              # Template-Key "default"
        ├── text_image.yml    # Default Definition
        └── gallery.yml
```

#### Beispiel: Modulkonfiguration je nach Template-Kontext

```yaml
# /definitions/module/blog/text_image.yml
module:
    image:
        sizes:
            - 800x450  # Blog-optimierte Bildgrößen
            - 400x225
        class: 'blog-image'
    layout:
        type: 'blog-layout'
        
# /definitions/module/shop/text_image.yml
module:
    image:
        sizes:
            - 600x600  # Quadratische Product-Shots
            - 300x300
        class: 'product-image'
    layout:
        type: 'shop-layout'

# /definitions/module/default/text_image.yml
module:
    image:
        sizes:
            - 1200x400  # Breite Content-Bilder
            - 600x200
        class: 'content-image'
    layout:
        type: 'default-layout'
```

Das Addon erkennt automatisch den Template-Key des aktuellen Artikels und lädt die entsprechenden Modul-Definitionen aus dem passenden Unterverzeichnis. Dadurch kann ein und dasselbe Modul je nach Template-Kontext unterschiedliche Konfigurationen erhalten, ohne dass dies im Modul selbst definiert werden muss.

#### Vorteile der Kontext-Erkennung
- Module passen sich automatisch dem Template-Kontext an
- Ein Modul kann in verschiedenen Templates unterschiedlich konfiguriert werden
- Keine manuelle Verkettung der Template-Keys notwendig
- Wiederverwendbarkeit von Modulen über verschiedene Templates hinweg
- Zentrale Steuerung des Modul-Verhaltens über Templates
- Saubere Trennung von Modul-Logik und Template-spezifischer Konfiguration

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
    }
});
```

## Extension Points

Das Addon bietet folgende Extension Points:

- `BSC_CONFIG_LOAD`: Beim Laden der Konfigurationen
- `BSC_CONFIG_LOADED`: Nach dem Laden aller Konfigurationen
- `BSC_DEFINITIONS_LOAD`: Beim Laden der Definitionen
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

2. **Ladereihenfolge:**
   - Beachten Sie die EARLY/NORMAL/LATE Prioritäten
   - Nutzen Sie das Project-Addon für finale Überschreibungen
   - Laden Sie projekt-spezifische Konfigurationen früh im Boot-Prozess

3. **Konfigurationsmanagement:**
   - Nutzen Sie `extend` für die Vererbung innerhalb einer Ebene
   - Verwenden Sie strukturelle Überschreibungen für ebenenübergreifende Anpassungen
   - Halten Sie sensible Daten in separaten Konfigurationsdateien

4. **Performance:**
   - Aktivieren Sie das Caching in Produktivumgebungen
   - Vermeiden Sie das mehrfache Laden gleicher Konfigurationen
   - Cachen Sie häufig verwendete Konfigurationswerte

5. **Entwicklung:**
   - Aktivieren Sie den Debug-Modus während der Entwicklung
   - Dokumentieren Sie Ihre Konfigurationsstrukturen
   - Nutzen Sie Versionskontrolle für Konfigurationsdateien

## Support & Lizenz

- **Autor:** Joachim Doerr
- **Support:** https://github.com/basecondition/definitions
- **Lizenz:** MIT
