<?php
/**
 * @package definitions
 * @author Joachim Doerr
 * @copyright (C) hello@basecondition.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace BSC\Definition;

use rex_extension;
use rex_extension_point;
use rex_path;
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
        if (is_array($searchSchemes)) self::$searchSchemes = $searchSchemes;

        // register extension point
        self::$searchSchemes = rex_extension::registerPoint(new rex_extension_point('BSC_DEFINITIONS_LOAD', self::$searchSchemes));

        foreach (self::$searchSchemes as $key => $schema) {
            self::$searchSchemes[$key] = rex_path::src($schema);
        }

        $definitions = DefinitionProvider::load(self::$searchSchemes);
        foreach ($definitions as $key => $definition) {
            self::setDefinition($definition, $keyPrefix . $key);
        }
    }

    // TODO auch hier sollte noch ein EP rein!
    public static function setDefinition(array|string $definition, string $alternativeKey): void
    {
        self::set($alternativeKey, self::getParsedDefinition($definition));
    }

    // TODO ich will das nachträgliche überschreiben der fertigen definition erlauben -> hier muss auch in jedem fall noch ein EP rein!
    public static function overwriteDefinition(array|string $definition, string $alternativeKey = null): void
    {
        // 1. parse definition
        $definition = self::getParsedDefinition($definition);
        // 2. get existed definition to overwrite
        $baseDefinition = self::getDefinitions();
        if (!is_null($alternativeKey)) $baseDefinition = self::get($alternativeKey);
        // 3. overwrite
        // TODO
        //  EP vor execution -> damit kann man sich hier noch einkinken -> zum bearbeiten des definition sets vordem es als überschreib set genutzt wird
        $result = array_merge_recursive($baseDefinition, $definition);
        self::setDefinition($result, $alternativeKey);
    }

    public static function getDefinitions(): array
    {
        return self::$definitions;
    }

    // TODO hier sollte auch noch ein EP rein!
    public static function set(string|int $key, mixed $value): void
    {
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
    }

    // TODO und hier sollte auch noch ein EP rein!
    public static function addStringTo(string $key, string $string): void
    {
        self::set($key, self::get($key) . $string);
    }

    public static function getAsBoolString(string|int $key): string
    {
        $value = self::get($key);
        return ($value) ? 'true' : 'false';
    }

    public static function get(string|int $key, mixed $default = null): mixed
    {
        if (str_contains($key, '.')) {
            $definitions = self::$definitions;
            $keys = explode('.', $key);
            foreach ($keys as $key) {
                if (is_array($definitions) && isset($definitions[$key])) {
                    $definitions = $definitions[$key];
                } else {
                    return $default;
                }
            }
            return $definitions;
        }
        return (isset(self::$definitions[$key])) ? self::$definitions[$key] : $default;
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