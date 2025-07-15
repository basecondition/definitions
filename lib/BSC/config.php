<?php

namespace BSC;

use BSC\Definition\AbstractDefinitionProvider;
use BSC\Definition\DefinitionConfig;
use BSC\Domain\DomainContextProvider;
use BSC\Language\LanguageContextProvider;
use rex;
use rex_article;
use rex_extension;
use rex_extension_point;
use rex_logger;
use rex_template;

/**
 * Die BSC\config Klasse ist der zentrale Konfigurationsmechanismus für Template-, Navigations- und Modul-Einstellungen.
 *
 * Diese Klasse verwaltet die gesamte Konfiguration des Addons und stellt Funktionen bereit, um:
 * - YAML-Definitionen aus verschiedenen Quellen zu laden und zu verwalten
 * - Konfigurationen nach Template-Kontext zu ordnen
 * - Den Zugriff auf Konfigurationsdaten über einen standardisierten Pfad zu ermöglichen
 *
 * Extension Points:
 * - BSC_CONFIG_LOAD: Wird während des Ladevorgangs der Konfigurationen ausgelöst (kann zum Hinzufügen eigener Pfade genutzt werden)
 * - BSC_CONFIG_LOADED: Wird nach dem Laden aller Konfigurationen ausgelöst (kann für Debugging oder Nachbearbeitung genutzt werden)
 *
 * Verwendungsbeispiele:
 * 1. Konfiguration abrufen:
 *    $templateConfig = BSC\config::get('template');
 *    $navigationConfig = BSC\config::get('navigation.main');
 *
 * 2. Konfigurationswert mit Fallback:
 *    $cacheTime = BSC\config::get('cache.lifetime', 3600);
 *
 * 3. Eigene Konfigurationen laden:
 *    BSC\config::loadConfig(['resources/*.yml', 'config/listener.yml']);
 *
 * 4. Konfigurationswert setzen:
 *    BSC\config::set('cache.enabled', true);
 *
 * Die Klasse wird automatisch über die boot.php des Addons initialisiert und lädt die Standard-Definitionen
 * entsprechend der in der DefinitionConfig definierten Pfade.
 */
class config extends AbstractDefinitionProvider
{
    /**
     * Gibt alle definierten Konfigurationsschlüssel zurück.
     *
     * @return array Liste aller Konfigurationsschlüssel
     */
    public static function getConfigDefinitionKeys(): array
    {
        return array_keys(DefinitionConfig::getInstance()->getDefinitionKeys());
    }

    /**
     * Lädt Konfigurationen aus den angegebenen Suchschemata.
     *
     * @param array $searchSchemes Liste von Suchpfaden für YAML-Dateien
     * @return void
     */
    public static function loadConfig(array $searchSchemes): void
    {
        // Extension Point zum Modifizieren der Suchschemata vor dem Laden
        $searchSchemes = rex_extension::registerPoint(new rex_extension_point('BSC_CONFIG_LOAD', $searchSchemes));

        // Aufteilung der Suchschemata in reguläre und modul-spezifische Pfade
        $moduleSearchSchemes = [];
        foreach ($searchSchemes as $key => $schema) {
            if (str_contains($schema, '/module/')) {
                $moduleSearchSchemes[] = $schema;
                unset($searchSchemes[$key]);
            }
        }

        // Lade Definitionen
        self::loadDefinitions($searchSchemes);
        self::loadDefinitions($moduleSearchSchemes, 'module.');

        // Template-Key-basierte Umstrukturierung der Definitionen
        $domain = DomainContextProvider::getDomainKey();
        $language = LanguageContextProvider::getCurrentLanguage();
        $templateKey = base::getTemplateKey();

        // Template-Key-basierte Umstrukturierung der Definitionen
        foreach (self::getConfigDefinitionKeys() as $definitionKey) {
            $moduleConfig = self::get($definitionKey);
            if (!is_null($moduleConfig) && isset($moduleConfig[$templateKey])) {
                self::setDefinition($moduleConfig[$templateKey], $definitionKey);
            }
        }

        // Extension Point nach dem Laden aller Konfigurationen
        rex_extension::registerPoint(new rex_extension_point('BSC_CONFIG_LOADED', self::get()));
    }

    /**
     * Setzt eine Konfiguration oder überschreibt eine vorhandene.
     *
     * @param array|string $config Die Konfigurationsdaten
     * @param string|null $alternativeKey Optionaler Schlüssel für die Konfiguration
     * @return void
     */
    public static function setConfig(array|string $config, string $alternativeKey = null): void
    {
        self::setDefinition($config, $alternativeKey);
    }

    /**
     * Gibt einen booleschen Wert als String zurück (true/false).
     *
     * @param string|int $key Der Konfigurationsschlüssel
     * @return string "true" oder "false"
     */
    public static function getAsBoolString(string|int $key): string
    {
        $value = self::get($key);
        return ($value) ? 'true' : 'false';
    }

    /**
     * Gibt alle definierten Konfigurationen zurück.
     *
     * @return array Alle Konfigurationen
     */
    public static function getAll(): array {
        return self::$definitions;
    }
}
