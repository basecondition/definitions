<?php

namespace BSC\Definition;

use rex;
use rex_addon;
use rex_path;

class DefinitionConfig
{
    private static ?self $instance = null;
    private array $config;
    private rex_addon $addon;

    // Absolute Fallback-Werte, falls weder package.yml noch gespeicherte Konfig existiert
    private const FALLBACKS = [
        'base_path' => 'definitions',
        'definition_keys' => [
            'navigation' => 'navigation/*.yml',
            'template' => 'template/*.yml',
            'module' => 'module/*/*.yml'
        ]
    ];

    private function __construct()
    {
        $this->addon = rex_addon::get('definitions');
        $this->initConfig();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function initConfig(): void
    {
        // Default-Werte aus package.yml laden
        $packageDefaults = $this->addon->getProperty('config', []);
        $basePath = $packageDefaults['base_path'];

        // Konfiguration zusammenbauen
        $this->config = [
            'base_path' => (!empty($basePath)) ? $basePath: $packageDefaults,
            'definition_keys' => $this->getDefinitionKeysConfig($packageDefaults),
            'cache' => $this->getCacheConfig($packageDefaults)
        ];
    }

    private function getConfigValue(string $key, array $packageDefaults)
    {
        // 1. Gespeicherte Konfiguration
        // 2. Package Defaults
        // 3. Fallback
        return $this->addon->getConfig(
            $key,
            $packageDefaults[$key] ?? self::FALLBACKS[$key]
        );
    }

    private function getDefinitionKeysConfig(array $packageDefaults): array
    {
        // 1. Gespeicherte Konfiguration laden
        $savedKeys = $this->addon->getConfig('definition_keys', []);

        // 2. Package Defaults als Basis verwenden
        $defaultKeys = $packageDefaults['definition_keys'] ?? self::FALLBACKS['definition_keys'];

        // Recursive merge anwenden statt manueller Zusammenführung
        return $this->arrayMergeRecursive($defaultKeys, $savedKeys);
    }

    private function getCacheConfig(array $packageDefaults): array
    {
        // 1. Gespeicherte Cache-Konfiguration laden
        $savedCache = $this->addon->getConfig('cache', []);

        // 2. Package Defaults als Basis verwenden
        $defaultCache = $packageDefaults['cache'] ?? self::FALLBACKS;

        // recursive merge
        return $this->arrayMergeRecursive($defaultCache, $savedCache);
    }

    private function arrayMergeRecursive(array $array1, array $array2): array
    {
        $merged = $array1;

        foreach ($array2 as $key => $value) {
            if (is_array($value) && isset($array1[$key]) && is_array($array1[$key])) {
                $merged[$key] = $this->arrayMergeRecursive($array1[$key], $value);
            } else {
                $merged[$key] = $value;
            }
        }

        return $merged;
    }

    public function getBasePath(): string
    {
        return rex_path::base($this->config['base_path']);
    }

    public function getDefinitionKeys(): array
    {
        return $this->config['definition_keys'];
    }

    public function getSearchSchemes(bool $withBasePath = false): array
    {
        $schemes = [];
        foreach ($this->getDefinitionKeys() as $path) {
            if ($withBasePath) {
                $schemes[] = $this->getBasePath() . '/' . trim($path, '/');
            } else {
                $schemes[] = $this->config['base_path'] . '/' . trim($path, '/');
            }
        }
        return $schemes;
    }

    public function isDebugEnabled(): bool
    {
        return rex::isDebugMode();
    }

    public function get(string $key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }

    public function getPackageDefaults(): array
    {
        return $this->addon->getProperty('config', self::FALLBACKS);
    }
}