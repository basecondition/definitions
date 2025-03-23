<?php

namespace BSC\Definition;

use BSC\Repository\YComGroupRepository;
use rex_extension;
use rex_extension_point;
use rex_path;
use rex_yform_manager_dataset;
use Symfony\Component\Yaml\Parser;

/**
 * @description mit dieser klasse können /{definitions-file-name}.yml dateien geladen und deren inhalt als abrufbare parameter ketten bereitgestellt werden
 *  zudem ermöglicht die klasse den aufbau eines multiplen array baumes, somit kann sie unabhängig von zu ladenden definitionen als array pool basis dienen
 */
abstract class AbstractDefinitionProvider
{
    protected static array $searchSchemes = [];
    protected static array $definitions = [];

    public static function loadDefinitions(array $searchSchemes = null, string $keyPrefix = ''): void
    {
        if (!is_array($searchSchemes)) return;

        // register extension point
        self::$searchSchemes = rex_extension::registerPoint(new rex_extension_point('BSC_DEFINITIONS_LOAD', $searchSchemes));

        foreach (self::$searchSchemes as $key => $schema) {
            self::$searchSchemes[$key] = rex_path::data($schema);

//            if (\rex_addon::exists('project') && \rex_addon::get('project')->isAvailable()) {
//                self::$searchSchemes[$key] = \rex_addon::get('project')->getPath($schema);
//            }
//            if (\rex_addon::exists('theme') && \rex_addon::get('theme')->isAvailable()) {
//                self::$searchSchemes[$key] = \theme_path::base('*/' . $schema);
//            }
        }

        $definitions = DefinitionProvider::load(self::$searchSchemes);
        foreach ($definitions as $key => $definition) {
            self::setDefinition($definition, $keyPrefix . $key);
        }
    }

    public static function setDefinition(array|string $definition, string $alternativeKey): void
    {
        // Register extension point before setting definition
        $definition = rex_extension::registerPoint(new rex_extension_point(
            'BSC_DEFINITION_SET',
            $definition,
            ['key' => $alternativeKey]
        ));

        self::set($alternativeKey, self::getParsedDefinition($definition));

        // Register extension point after setting definition
        rex_extension::registerPoint(new rex_extension_point(
            'BSC_DEFINITION_SET_AFTER',
            null,
            ['key' => $alternativeKey, 'definition' => $definition]
        ));
    }

    public static function overwriteDefinition(array|string $definition, string $alternativeKey = null): void
    {
        // 1. parse definition
        $definition = self::getParsedDefinition($definition);
        // 2. get existed definition to overwrite
        $baseDefinition = self::$definitions;
        if (!is_null($alternativeKey)) $baseDefinition = self::get($alternativeKey);

        // Register extension point before overwriting
        $definition = rex_extension::registerPoint(new rex_extension_point(
            'BSC_DEFINITION_OVERWRITE',
            $definition,
            [
                'key' => $alternativeKey,
                'base_definition' => $baseDefinition
            ]
        ));

        // 3. overwrite
        $result = array_merge_recursive($baseDefinition, $definition);

        // Register extension point after merging but before setting
        $result = rex_extension::registerPoint(new rex_extension_point(
            'BSC_DEFINITION_OVERWRITE_MERGED',
            $result,
            [
                'key' => $alternativeKey,
                'original_definition' => $definition,
                'base_definition' => $baseDefinition
            ]
        ));

        self::setDefinition($result, $alternativeKey);
    }

    public static function set(string|int $key, mixed $value): void
    {
        // Register extension point before setting value
        $value = rex_extension::registerPoint(new rex_extension_point(
            'BSC_DEFINITION_VALUE_SET',
            $value,
            ['key' => $key]
        ));

        if (str_contains($key, '.')) {
            $definitions = &self::$definitions; // Verwende "&" für die Referenz, um das Original-Array zu aktualisieren
            $keys = explode('.', $key);

            foreach ($keys as $innerKey) {
                if (!isset($definitions[$innerKey]) || !is_array($definitions[$innerKey])) {
                    $definitions[$innerKey] = [];
                }

                $definitions = &$definitions[$innerKey];
            }

            $definitions = $value;
        } else {
            self::$definitions[$key] = $value;
        }

        // Register extension point after setting value
        rex_extension::registerPoint(new rex_extension_point(
            'BSC_DEFINITION_VALUE_SET_AFTER',
            null,
            ['key' => $key, 'value' => $value]
        ));
    }

    public static function addStringTo(string $key, string $string): void
    {
        // Register extension point before adding string
        $string = rex_extension::registerPoint(new rex_extension_point(
            'BSC_DEFINITION_STRING_ADD',
            $string,
            ['key' => $key, 'current_value' => self::get($key)]
        ));

        self::set($key, self::get($key) . $string);

        // Register extension point after adding string
        rex_extension::registerPoint(new rex_extension_point(
            'BSC_DEFINITION_STRING_ADD_AFTER',
            null,
            ['key' => $key, 'added_string' => $string, 'new_value' => self::get($key)]
        ));
    }

    public static function get(string|int|null $key = null, mixed $default = null): mixed
    {
        if (is_null($key)) return self::$definitions;
        if (str_contains($key, '.')) {
            $definitions = self::$definitions;
            $keys = explode('.', $key);

            foreach ($keys as $index => $key) {
                // crazy mandant magic
                if ($key == 'mandant' && isset($definitions[$key]) && $definitions[$key] instanceof rex_yform_manager_dataset && isset($keys[($index+1)])) {
                    $mandant = $definitions[$key];
                    if ($keys[($index + 1)] == 'ycom_group') {
                        $definition = self::getMandantGroup($mandant->getValue('key'));
                        if (isset($keys[($index + 2)]) && $definition instanceof rex_yform_manager_dataset) {
                            $definition = $definition->getValue($keys[($index + 2)]);
                        }
                        return $definition;
                    }
                    return $mandant->getValue($keys[($index + 1)]);
                } else if ($key == '') {

                } else if (is_array($definitions) && isset($definitions[$key])) {
                    $definitions = $definitions[$key];
                } else {
                    return $default;
                }
            }
            return $definitions;
        }
        return (isset(self::$definitions[$key])) ? self::$definitions[$key] : $default;
    }

    private static function getMandantGroup($mandantKey): ?rex_yform_manager_dataset
    {
        if (!self::get('ycom.mandant_group') instanceof rex_yform_manager_dataset) {
            self::set('ycom.mandant_group', YComGroupRepository::findGroupByMandantKey($mandantKey));
        }
        return self::get('ycom.mandant_group');
    }

    private static function getParsedDefinition(array|string $definition): array
    {
        if (is_string($definition)) {
            $parser = new Parser();
            $definition = $parser->parse($definition);
        }
        return $definition;
    }
}