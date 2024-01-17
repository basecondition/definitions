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

use BSC\Repository\YComGroupRepository;
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
        if (!is_array($searchSchemes)) return;

        // register extension point
        self::$searchSchemes = rex_extension::registerPoint(new rex_extension_point('BSC_DEFINITIONS_LOAD', $searchSchemes));
        foreach (self::$searchSchemes as $key => $schema) {
            // TODO config parameter base path für definitions
            self::$searchSchemes[$key] = rex_path::data($schema);
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
        $baseDefinition = self::$definitions;
        if (!is_null($alternativeKey)) $baseDefinition = self::get($alternativeKey);
        // 3. overwrite
        // TODO
        //  EP vor execution -> damit kann man sich hier noch einkinken -> zum bearbeiten des definition sets vordem es als überschreib set genutzt wird
        $result = array_merge_recursive($baseDefinition, $definition);
        self::setDefinition($result, $alternativeKey);
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

    public static function get(string|int|null $key = null, mixed $default = null): mixed
    {
        if (is_null($key)) return self::$definitions;
        if (str_contains($key, '.')) {
            $definitions = self::$definitions;
            $keys = explode('.', $key);

            foreach ($keys as $index => $key) {
                // crazy mandant magic
                if ($key == 'mandant' && isset($definitions[$key]) && $definitions[$key] instanceof \rex_yform_manager_dataset && isset($keys[($index+1)])) {
                    $mandant = $definitions[$key];
                    if ($keys[($index + 1)] == 'ycom_group') {
                        $definition = self::getMandantGroup($mandant->getValue('key'));
                        if (isset($keys[($index + 2)]) && $definition instanceof \rex_yform_manager_dataset) {
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

    private static function getMandantGroup($mandantKey): ?\rex_yform_manager_dataset
    {
        if (!self::get('ycom.mandant_group') instanceof \rex_yform_manager_dataset) {
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