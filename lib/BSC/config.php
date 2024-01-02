<?php
/**
 * @package components
 * @author Joachim Doerr
 * @copyright (C) hello@basecondition.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace BSC;

use BSC\Definition\AbstractDefinitionProvider;
use rex_extension;
use rex_extension_point;

/**
 * @description die BSC\config klasse ist der zentrale rote configurationsfaden für template, navigation und module-einstellungen, settings und konfigurationen.
 *  zudem ist es natürlich möglich jede weitere config mit hilfe der bsc component klasse zu verwalten. die BSC\config wird automatisiert über die boot.php initialisiert.
 * TODO: description ausbauen -> verwendung beschreiben, EP's beschreiben, rückbezug auf definitions und base als info.
 */
class config extends AbstractDefinitionProvider
{
    // TODO config definition keys in addon config festlegen
    const CONFIG_DEFINITION_KEYS = ['navigation', 'template', 'module/*'];

    public static array $defaultDefinitionKeys = self::CONFIG_DEFINITION_KEYS;

    public static function getConfigDefinitionKeys(): array
    {
        return self::$defaultDefinitionKeys;
    }

    public static function loadConfig(array $searchSchemes): void
    {
        // register extension point load
        $searchSchemes = rex_extension::registerPoint(new rex_extension_point('BSC_CONFIG_LOAD', $searchSchemes));

        // split search schemes
        $moduleSearchSchemes = [];
        foreach ($searchSchemes as $key => $schema) {
            if (str_contains($schema, '/module/')) {
                $moduleSearchSchemes[] = $schema;
                unset($searchSchemes[$key]);
            }
        }

        // load definitions by search schemes
        self::loadDefinitions($searchSchemes);
        self::loadDefinitions($moduleSearchSchemes, 'module.');

        // reset definitions by template key
        foreach (self::getConfigDefinitionKeys() as $definitionKey) {
            $definitionKey = str_replace('/*', '', $definitionKey);
            $moduleConfig = self::get($definitionKey);
            if (!is_null($moduleConfig) && isset($moduleConfig[base::getTemplateKey()])) {
                self::setDefinition($moduleConfig[base::getTemplateKey()], $definitionKey);
            }
        }

        // register extension point loaded
        rex_extension::registerPoint(new rex_extension_point('BSC_CONFIG_LOADED', self::getConfig()));
    }

    public static function setConfig(array|string $config, string $alternativeKey = null): void
    {
        self::setDefinition($config, $alternativeKey);
    }

    public static function getConfig(): array
    {
        return self::getDefinitions();
    }
}
