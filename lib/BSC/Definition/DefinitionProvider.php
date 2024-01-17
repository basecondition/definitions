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


use Doctrine\Common\Cache\PhpFileCache;
use Symfony\Component\Yaml\Parser;
use rex_addon;

class DefinitionProvider
{
    const CACHE_TTL = 345600; // 48h

    /**
     * @param array<DefinitionMergeInterface>|null $mergeHandler
     */
    public static function load(array|string $searchSchemes, array $mergeHandler = null, array $toMerge = null, int $cacheTTL = self::CACHE_TTL): array
    {
        if (is_string($searchSchemes)) $searchSchemes = [$searchSchemes];

        $cacheDriver = new PhpFileCache(rex_addon::get('definitions')->getCachePath('.definition'));
        $hashKeys = array();
        $groups = [];

        // find all files by schema
        foreach ($searchSchemes as $searchSchema) {
            $files = glob($searchSchema);
            foreach ($files as $file) {
                $key = basename(dirname($file));
                $name = pathinfo($file, PATHINFO_FILENAME);

                if (!isset($groups[$key][$name])) $groups[$key][$name] = [];

                $extendDefinitions = self::getExtendDefinitions($file);
                if (count($extendDefinitions) > 0) {
                    $groups[$key][$name] = array_merge($extendDefinitions, $groups[$key][$name]);
                }
                $groups[$key][$name][] = $file;
                $hashKeys[] = $file;
            }
            if (!empty($key) && !empty($name) && isset($toMerge[$key][$name])) {
                $groups[$key][$name][] = $toMerge[$key][$name];
            }
        }

        // use last modification date for cache key
        $lastModifications = array_map(function ($f) {
            return filemtime($f);
        }, $hashKeys);
        // set cache keys
        $cacheKey = md5(sprintf("%s:%s", __CLASS__, implode('.', $searchSchemes))) . '.' . md5(implode('.', $lastModifications));
        // load from cache
        if ($definition = $cacheDriver->fetch($cacheKey)) {
            // get cached content as output
            return $definition;
        }

        // parse yml
        $parser = new Parser();
        $parsedContents = [];
        foreach ($groups as $key => $group) {
            foreach ($group as $name => $files) {
                $parsedContents[$key][$name] = array_map(
                    fn($file) => $parser->parse(file_get_contents($file)),
                    is_array($files) ? $files : []
                );
            }
        }

        // löst komma getrennte array keys auf
        $parsedContents = self::transformKeysRecursive($parsedContents);

        // merge definitions by parsed contents
        $definition = self::mergeParsedContents($parsedContents, $mergeHandler);

        // save cache
        $cacheDriver->save($cacheKey, $definition, $cacheTTL);
        return $definition;
    }

    private static function transformKeysRecursive(array $data): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            if (str_contains($key, '.')) {
                $parts = explode('.', $key);
                $current = &$result;

                foreach ($parts as $part) {
                    $current = &$current[$part];
                }

                $current = $value;
            } else {
                $result[$key] = is_array($value) ? self::transformKeysRecursive($value) : $value;
            }
        }

        return $result;
    }

    /** @param array<DefinitionMergeInterface>|null $mergeHandler */
    public static function mergeParsedContents(array $parsedContents, array|null $mergeHandler = null): array
    {
        $definitionMergeHandler = new DefinitionMergeHandler();
        $mergedContents = [];
        $mergedGroups = [];

        foreach ($parsedContents as $key => $content) {
            if (isset($mergeHandler[$key])) {
                $handler = new $mergeHandler[$key]();
                $mergedGroups[$key] = $handler->mergeGroup($content);
                unset($parsedContents[$key]);
            }
        }

        if (count($parsedContents) > 0) $mergedContents = $definitionMergeHandler->mergeDefinition($parsedContents);
        if (count($mergedGroups) > 0) $mergedContents = array_merge($mergedContents, $mergedGroups);

        return $mergedContents;
    }

    private static function getExtendDefinitions(string $file): array
    {
        if ($file === '' || !file_exists($file)) return [];
        $firstLine = fgets(fopen($file, 'r'));
        if ($firstLine === false) return [];
        $basePath = realpath(dirname($file));

        $result = [];
        if (preg_match('/extend:\s*(.*)/', $firstLine, $matches)) {
            $extendValue = trim($matches[1]);
            $extendValueArray = explode(',', $extendValue);
            $extendValueArray = array_map('trim', $extendValueArray);
            foreach ($extendValueArray as $value) {
                $file = $basePath . '/' . $value;
                $recResult = self::getExtendDefinitions($file);
                if (count($recResult) > 0) {
                    $result = array_merge($result, $recResult);
                }
                $result[] = $file;
            }
        }
        return $result;
    }
}