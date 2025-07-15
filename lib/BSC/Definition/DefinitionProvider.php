<?php

namespace BSC\Definition;

use BSC\base;
use BSC\Domain\DomainContextProvider;
use BSC\Language\LanguageContextProvider;
use Exception;
use rex_addon;
use rex_file;
use rex_logger;
use rex_extension;
use rex_extension_point;
use RuntimeException;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Exception\ParseException;

class DefinitionProvider
{
    private const CACHE_FILE_EXTENSION = '.cache';

    /**
     * @param array<DefinitionMergeInterface>|null $mergeHandler
     * @throws RuntimeException
     */
    public static function load(
        array|string $searchSchemes,
        ?array $mergeHandler = null,
        ?array $toMerge = null
    ): array {
        if (is_string($searchSchemes)) {
            $searchSchemes = [$searchSchemes];
        }

        try {
            $files = self::collectFiles($searchSchemes, $toMerge);
            $cacheKey = self::generateCacheKey($files['hashKeys'], $searchSchemes);

            // EP: Modify cache key generation
            $cacheKey = rex_extension::registerPoint(new rex_extension_point(
                'DEFINITION_CACHE_KEY',
                $cacheKey,
                ['files' => $files, 'searchSchemes' => $searchSchemes]
            ));

            if ($cachedData = self::loadFromCache($cacheKey)) {
                return $cachedData;
            }

            $definition = self::processDefinitions($files, $mergeHandler);

            self::saveToCache($cacheKey, $definition);

            return $definition;

        } catch (Exception $e) {
            rex_logger::logException($e);
            throw new RuntimeException('Failed to load definitions: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * @throws RuntimeException
     */
    private static function collectFiles(array $searchSchemes, ?array $toMerge): array
    {
        $hashKeys = [];
        $groups = [];

        foreach ($searchSchemes as $searchSchema) {
            $files = glob($searchSchema);
            if ($files === false) {
                throw new RuntimeException("Failed to glob files for schema: $searchSchema");
            }

            foreach ($files as $file) {
                $key = basename(dirname($file));
                $name = pathinfo($file, PATHINFO_FILENAME);

                if (!isset($groups[$key][$name])) {
                    $groups[$key][$name] = [];
                }

                $extendDefinitions = self::getExtendDefinitions($file);
                if (!empty($extendDefinitions)) {
                    $groups[$key][$name] = array_merge($extendDefinitions, $groups[$key][$name]);
                }

                $groups[$key][$name][] = $file;
                $hashKeys[] = $file;
            }

            if (!empty($key) && !empty($name) && isset($toMerge[$key][$name])) {
                $groups[$key][$name][] = $toMerge[$key][$name];
            }
        }

        return [
            'hashKeys' => $hashKeys,
            'groups' => $groups
        ];
    }

    private static function generateCacheKey(array $hashKeys, array $searchSchemes): string
    {
        $lastModifications = array_map(static function ($f) {
            return filemtime($f);
        }, $hashKeys);

        // Domain und Sprache in den Cache-Key einbeziehen
        $domain = DomainContextProvider::getDomainKey();
        $language = LanguageContextProvider::getCurrentLanguage();
        $template = base::getTemplateKey();

        $contextKey = "d_{$domain}_l_{$language}_t_{$template}";

        return md5(sprintf("%s:%s:%s", __CLASS__, implode('.', $searchSchemes), $contextKey))
//        return md5(sprintf("%s:%s", __CLASS__, implode('.', $searchSchemes)))
            . '.'
            . md5(implode('.', $lastModifications));
    }

    private static function loadFromCache(string $cacheKey): ?array
    {
        $cacheFile = self::getCacheFilePath($cacheKey);

        // EP: Before cache load
        $cacheFile = rex_extension::registerPoint(new rex_extension_point(
            'DEFINITION_BEFORE_CACHE_LOAD',
            $cacheFile,
            ['cacheKey' => $cacheKey]
        ));

        if (file_exists($cacheFile)) {
            $definition = rex_file::getCache($cacheFile);
            if ($definition !== false) {
                // EP: After cache load
                return rex_extension::registerPoint(new rex_extension_point(
                    'DEFINITION_AFTER_CACHE_LOAD',
                    $definition,
                    ['cacheKey' => $cacheKey, 'cacheFile' => $cacheFile]
                ));
            }
        }

        return null;
    }

    private static function saveToCache(string $cacheKey, array $definition): void
    {
        $cacheFile = self::getCacheFilePath($cacheKey);

        // EP: Before cache save
        $saveData = rex_extension::registerPoint(new rex_extension_point(
            'DEFINITION_BEFORE_CACHE_SAVE',
            $definition,
            ['cacheKey' => $cacheKey, 'cacheFile' => $cacheFile]
        ));

        rex_file::putCache($cacheFile, $saveData);

        // EP: After cache save
        rex_extension::registerPoint(new rex_extension_point(
            'DEFINITION_AFTER_CACHE_SAVE',
            true,
            ['cacheKey' => $cacheKey, 'cacheFile' => $cacheFile, 'definition' => $definition]
        ));
    }

    /**
     * @throws ParseException
     */
    private static function processDefinitions(array $files, ?array $mergeHandler): array
    {
        $parser = new Parser();
        $parsedContents = [];

        foreach ($files['groups'] as $key => $group) {
            foreach ($group as $name => $groupFiles) {
                $parsedContents[$key][$name] = array_map(
                    static function($file) use ($parser) {
                        $content = file_get_contents($file);
                        if ($content === false) {
                            throw new RuntimeException("Failed to read file: $file");
                        }
                        return $parser->parse($content);
                    },
                    is_array($groupFiles) ? $groupFiles : []
                );
            }
        }

        $parsedContents = self::transformKeysRecursive($parsedContents);

        return self::mergeParsedContents($parsedContents, $mergeHandler);
    }

    private static function getCacheFilePath(string $cacheKey): string
    {
        return rex_addon::get('definitions')->getCachePath($cacheKey . self::CACHE_FILE_EXTENSION);
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