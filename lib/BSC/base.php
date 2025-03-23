<?php

namespace BSC;

use BSC\Definition\AbstractDefinitionProvider;
use rex_article;
use rex_template;

/**
 * Die BSC\base Klasse erweitert den AbstractDefinitionProvider und stellt einen Basis-Array als Systemgrundbaum
 * für übergreifend einsetzbare Elemente dar. Sie kann genutzt werden, um Objekte global verfügbar zu machen.
 *
 * Verwendung:
 * - Abrufen des Template-Keys: BSC\base::getTemplateKey()
 * - Zugriff auf Konfigurationen: BSC\base::config('pfad.zum.wert')
 *
 * Extension Points:
 * - BSC_DEFINITIONS_LOAD: Wird beim Laden der Definitionen ausgelöst
 * - BSC_DEFINITION_SET: Wird vor dem Setzen einer Definition ausgelöst
 * - BSC_DEFINITION_SET_AFTER: Wird nach dem Setzen einer Definition ausgelöst
 * - BSC_DEFINITION_OVERWRITE: Wird vor dem Überschreiben einer Definition ausgelöst
 * - BSC_DEFINITION_OVERWRITE_MERGED: Wird nach dem Zusammenführen, aber vor dem Setzen einer überschriebenen Definition ausgelöst
 * - BSC_DEFINITION_VALUE_SET: Wird vor dem Setzen eines Werts ausgelöst
 * - BSC_DEFINITION_VALUE_SET_AFTER: Wird nach dem Setzen eines Werts ausgelöst
 * - BSC_DEFINITION_STRING_ADD: Wird vor dem Hinzufügen eines Strings ausgelöst
 * - BSC_DEFINITION_STRING_ADD_AFTER: Wird nach dem Hinzufügen eines Strings ausgelöst
 *
 * Zusammenhang mit anderen Klassen:
 * - BSC\config: Zentrale Verwaltung aller Konfigurationen und Definitionen
 * - BSC\Definition\DefinitionProvider: Lädt und cached die YAML-Definitionen
 */
class base extends AbstractDefinitionProvider
{
    /**
     * Ermittelt den Template-Key basierend auf der Template-ID.
     *
     * Diese Methode dient als Hilfsfunktion zur Ermittlung des aktuell verwendeten Template-Keys.
     * Wenn keine ID übergeben wird, wird automatisch der aktuelle Artikel verwendet.
     *
     * @param int|null $id Die Template-ID (optional, verwendet den aktuellen Artikel, wenn null)
     * @return string|null Der Template-Key oder null, wenn kein Template gefunden wurde
     *
     * @example
     * // Aktuellen Template-Key ermitteln
     * $templateKey = BSC\base::getTemplateKey();
     *
     * // Template-Key für eine spezifische Template-ID ermitteln
     * $templateKey = BSC\base::getTemplateKey(3);
     */
    public static function getTemplateKey(int|null $id = null): ?string
    {
        // Wenn keine ID übergeben wurde und ein aktueller Artikel existiert,
        // verwende die Template-ID des aktuellen Artikels
        if (is_null($id) && !is_null($article = rex_article::getCurrent())) {
            $id = $article->getTemplateId();
        }

        // Erstelle ein Template-Objekt mit der ermittelten ID
        $template = new rex_template($id);

        // Gib den Template-Key zurück
        return $template->getKey();
    }

    /**
     * Zugriffsmethode für Konfigurationswerte - Alias für die get-Methode des Eltern-Providers.
     *
     * Diese Methode bietet eine semantisch klarere Alternative zur get-Methode und ermöglicht
     * den direkten Zugriff auf Konfigurationswerte mit optionalem Fallback-Wert.
     *
     * @param string|int|null $key Der Konfigurationsschlüssel im Dot-Notation-Format (z.B. 'template.layout')
     * @param mixed $default Optionaler Fallback-Wert, der zurückgegeben wird, wenn der Schlüssel nicht existiert
     * @return mixed Der Konfigurationswert oder der Fallback-Wert
     *
     * @example
     * // Einfacher Zugriff auf einen Konfigurationswert
     * $cacheEnabled = BSC\base::config('cache.enabled');
     *
     * // Zugriff mit Fallback-Wert
     * $timeout = BSC\base::config('api.timeout', 30);
     */
    public static function config(string|int|null $key = null, mixed $default = null): mixed
    {
        // Delegation an die get-Methode des AbstractDefinitionProvider
        return parent::get($key, $default);
    }
}