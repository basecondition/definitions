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
- Vererbung von Definitionen durch `extend`-Funktionalität
- Flexible Registrierung von Definition-Verzeichnissen

## Installation

1. Im REDAXO-Installer das Addon "definitions" auswählen und installieren
2. Mindestvoraussetzungen:
    - REDAXO >= 5.15.0
    - PHP >= 8.1

## Grundlegende Verwendung

### Definition-Verzeichnisse registrieren

Es gibt mehrere Möglichkeiten, Definition-Verzeichnisse zu registrieren:

1. **Via boot.php im Project-Addon:**
```php
rex_extension::register('BSC_CONFIG_LOAD', function(rex_extension_point $ep) {
    $schemes = $ep->getSubject();
    
    // Projekt-spezifische Definitionen
    $schemes[] = "theme/private/definitions/navigation/*.yml";
    $schemes[] = "theme/private/definitions/template/*.yml";
    
    // Modul-spezifische Definitionen
    $schemes[] = "theme/private/definitions/module/*/config.yml";
    
    return $schemes;
});
```

2. **Via Extension Point für bestimmte Addons:**
```php
rex_extension::register('BSC_DEFINITIONS_LOAD', function(rex_extension_point $ep) {
    $schemes = $ep->getSubject();
    
    // Addon-spezifische Definitionen
    $schemes[] = rex_addon::get('mein_addon')->getPath('definitions/*.yml');
    
    return $schemes;
});
```

### Standard-Verzeichnisstruktur

Nach der Installation werden YAML-Dateien standardmäßig in folgendem Verzeichnis erwartet:

```
redaxo/data/definitions/
├── navigation/
│   └── main.yml
├── template/
│   └── default.yml
└── module/
    └── article.yml
```

Sie können diese Struktur durch die Registration zusätzlicher Verzeichnisse erweitern.

### Basis-Konfiguration

Beispiel für eine einfache YAML-Konfigurationsdatei:

```yaml
template:
  default:
    name: Standard-Template
    sections:
      - header
      - content
      - footer
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

### YAML-Vererbung

Sie können YAML-Dateien voneinander erben lassen:

```yaml
extend: base.yml

template:
  custom:
    name: Erweitertes Template
```

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

Beispiel für die Verwendung eines Extension Points:

```php
rex_extension::register('BSC_CONFIG_LOAD', function(rex_extension_point $ep) {
    $schemes = $ep->getSubject();
    // Eigene Suchpfade hinzufügen
    $schemes[] = "definitions/custom/*.yml";
    return $schemes;
});
```

### Best Practices für Definition-Verzeichnisse

- Gruppieren Sie Definitionen nach logischen Einheiten (navigation, template, module etc.)
- Nutzen Sie sprechende Dateinamen
- Beachten Sie die Ladereihenfolge bei überschreibenden Definitionen
- Verwenden Sie relative Pfade ausgehend vom REDAXO-Root
- Dokumentieren Sie die Verzeichnisstruktur im Projekt

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

## Best Practices

- Strukturieren Sie Ihre YAML-Dateien logisch nach Funktionsbereichen
- Nutzen Sie die Vererbungsfunktion für wiederkehrende Basis-Konfigurationen
- Implementieren Sie eigene Merge-Handler für spezielle Anwendungsfälle
- Nutzen Sie Extension Points für flexible Anpassungen
- Dokumentieren Sie Ihre Konfigurationsstrukturen

## Support & Lizenz

- **Autor:** Joachim Doerr
- **Support:** https://github.com/basecondition/definitions
- **Lizenz:** MIT
