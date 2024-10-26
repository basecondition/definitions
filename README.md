```
 ┓            ┓• •
 ┣┓┏┓┏┏┓┏┏┓┏┓┏┫┓╋┓┏┓┏┓
 ┗┛┗┻┛┗ ┗┗┛┛┗┗┻┗┗┗┗┛┛┗━━
```
## Definitions für REDAXO 5

Ein REDAXO-Addon zur zentralen Verwaltung von Konfigurationen und Definitionen über YAML-Dateien.

## Features

- Zentrale Verwaltung von Konfigurationen über YAML-Dateien
- Flexibles Caching-System für optimale Performance
- Erweiterbare Struktur durch Extension Points
- Unterstützung für Template-, Navigations- und Modulkonfigurationen
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

## Grundlegende Verwendung

### Definition-Verzeichnisse registrieren

Die Registrierung erfolgt nach einer klaren Prioritätenreihenfolge, die bestimmt, wie Definitionen überschrieben werden:

1. **Core/AddOn Definitionen (EARLY):**
```php
rex_extension::register('BSC_CONFIG_LOAD', function(rex_extension_point $ep) {
    $schemes = $ep->getSubject();
    // AddOn spezifische Definitionen
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
    if (rex_addon::exists('theme') && $theme = rex_addon::get('theme')) {
        // Definitionen im Theme-Ordner via theme_path::base()
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

### Empfohlene Verzeichnisstruktur

Nach der Installation wird folgende Verzeichnisstruktur empfohlen:

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

### Überschreiben von Definitionen

Die Überschreibung von Definitionen erfolgt primär durch:
1. Strukturelle Übereinstimmung der YAML-Struktur
2. Die Ladereihenfolge (EARLY, NORMAL, LATE)

Beispiel für Überschreibung:

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
      - slider              # überschreibt die sections komplett
      - content
      - footer

# /redaxo/addons/project/definitions/template/default.yml
template:
  default:
    name: "Projekt Template"  # überschreibt den Namen erneut
```

### Vererbung mittels extend

Die `extend`-Funktionalität dient der Vererbung von Basis-Konfigurationen innerhalb einer Ebene:

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

### Debug-Modus

Zur Überprüfung der geladenen Definitionen kann der Debug-Modus genutzt werden:

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

### Verwendung im Code

```php
use BSC\config;

// Komplette Konfiguration abrufen
$allConfig = config::getAll();

// Spezifischen Wert abrufen
$templateName = config::get('template.default.name');

// Wert mit Standardwert abrufen
$sections = config::get('template.default.sections', []);
```

## Erweiterte Funktionen

### Extension Points

Das Addon bietet verschiedene Extension Points zur Erweiterung:

- `BSC_CONFIG_LOAD`: Wird beim Laden der Konfigurationen aufgerufen
- `BSC_CONFIG_LOADED`: Wird nach dem Laden aller Konfigurationen aufgerufen
- `BSC_DEFINITIONS_LOAD`: Wird beim Laden der Definitionen aufgerufen
- `DEFINITION_CACHE_KEY`: Ermöglicht die Modifikation des Cache-Keys
- `DEFINITION_BEFORE_CACHE_LOAD`: Wird vor dem Laden des Caches aufgerufen
- `DEFINITION_AFTER_CACHE_LOAD`: Wird nach dem Laden des Caches aufgerufen
- `DEFINITION_BEFORE_CACHE_SAVE`: Wird vor dem Speichern des Caches aufgerufen
- `DEFINITION_AFTER_CACHE_SAVE`: Wird nach dem Speichern des Caches aufgerufen

### Best Practices

- Nutzen Sie die empfohlene Verzeichnisstruktur für bessere Übersichtlichkeit
- Verwenden Sie `theme_path::base()` für den Zugriff auf Theme-Verzeichnisse
- Beachten Sie die Ladereihenfolge beim Überschreiben von Definitionen
- Nutzen Sie `extend` für die Vererbung von Basis-Konfigurationen innerhalb einer Ebene
- Verwenden Sie strukturelle Überschreibungen für ebenenübergreifende Anpassungen
- Nutzen Sie das Theme-Addon für theme-spezifische Konfigurationen
- Verwenden Sie das Project-Addon für finale projektspezifische Überschreibungen
- Gruppieren Sie Definitionen nach logischen Einheiten
- Nutzen Sie sprechende Dateinamen
- Dokumentieren Sie Ihre Konfigurationsstrukturen
- Aktivieren Sie den Debug-Modus während der Entwicklung

### Caching

Das Addon implementiert ein automatisches Caching-System mit einer Standard-TTL von 48 Stunden. Der Cache kann über die Extension Points beeinflusst oder manuell geleert werden.

### Eigene Definition Handler

Sie können eigene Handler für spezielle Definitionen erstellen:

```php
class CustomMergeHandler implements DefinitionMergeInterface 
{
    public function mergeGroup(array $group): array 
    {
        // Eigene Merge-Logik
        return $group;
    }

    public function mergeDefinition(array $definitions): array 
    {
        // Eigene Definition-Logik
        return $definitions;
    }
}
```

## Support & Lizenz

- **Autor:** Joachim Doerr
- **Support:** https://github.com/basecondition/definitions
- **Lizenz:** MIT