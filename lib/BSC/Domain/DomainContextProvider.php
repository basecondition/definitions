<?php
namespace BSC\Domain;

use rex;
use rex_extension;
use rex_extension_point;

class DomainContextProvider
{
    private static ?string $currentDomain = null;
    private static array $domainMappings = [];
    private static array $localDomainMappings = [
        'localhost' => 'default',
        '127.0.0.1' => 'default'
    ];

    /**
     * Initialisiert den DomainContextProvider.
     * Lädt Domain-Mappings aus der Konfiguration und registriert Extension Points.
     */
    public static function init(): void
    {
        // Lade Domain-Mappings aus REDAXO-Konfiguration
        self::$domainMappings = rex::getProperty('definitions_domains', []);

        // Extension Point für Domain-Erkennung registrieren
        rex_extension::register('BSC_DOMAIN_DETECTION', function(rex_extension_point $ep) {
            return self::detectDomain();
        });
    }

    /**
     * Gibt die aktuelle Domain zurück.
     * Erkennt die Domain beim ersten Aufruf und cached das Ergebnis.
     *
     * @return string Die aktuelle Domain oder 'default' als Fallback
     */
    public static function getCurrentDomain(): string
    {
        if (self::$currentDomain === null) {
            self::$currentDomain = rex_extension::registerPoint(
                new rex_extension_point('BSC_DOMAIN_DETECTION', 'default')
            );
        }

        return self::$currentDomain;
    }

    /**
     * Setzt die aktuelle Domain manuell.
     * Nützlich für Tests oder spezielle Anwendungsfälle.
     *
     * @param string $domain Die zu setzende Domain
     */
    public static function setCurrentDomain(string $domain): void
    {
        self::$currentDomain = $domain;
    }

    /**
     * Ermittelt den Domain-Key für eine gegebene Domain.
     * Der Domain-Key wird für die Verzeichnisstruktur verwendet.
     *
     * @param string|null $domain Die zu prüfende Domain (optional, verwendet aktuelle Domain wenn null)
     * @return string Der Domain-Key oder 'default' als Fallback
     */
    public static function getDomainKey(string $domain = null): string
    {
        $domainToCheck = $domain ?? self::getCurrentDomain();

        // Prüfen auf lokale Entwicklungsumgebung
        if (isset(self::$localDomainMappings[$domainToCheck])) {
            return self::$localDomainMappings[$domainToCheck];
        }

        // Exakte Domain-Übereinstimmung
        if (isset(self::$domainMappings[$domainToCheck])) {
            return self::$domainMappings[$domainToCheck];
        }

        // Pattern-basierte Zuordnung (z.B. für Subdomains)
        foreach (self::$domainMappings as $pattern => $key) {
            if (strpos($pattern, '*') !== false) {
                $regexPattern = '/^' . str_replace(['.', '*'], ['\\.', '.*'], $pattern) . '$/';
                if (preg_match($regexPattern, $domainToCheck)) {
                    return $key;
                }
            }
        }

        // Fallback auf Standard-Domain
        return 'default';
    }

    /**
     * Erkennt die aktuelle Domain anhand von Server-Variablen.
     *
     * @return string Die erkannte Domain oder 'default' als Fallback
     */
    private static function detectDomain(): string
    {
        // 1. Versuche Domain aus Server-Variablen zu ermitteln
        $domain = $_SERVER['HTTP_HOST'] ?? null;

        // 2. Fallback auf REDAXO Server-Konfiguration
        if (!$domain) {
            $domain = rex::getServer();
        }

        // 3. Entferne Port-Nummer, falls vorhanden
        if ($domain && strpos($domain, ':') !== false) {
            $domain = explode(':', $domain)[0];
        }

        // 4. Wenn immer noch keine Domain, verwende "default"
        if (!$domain) {
            return 'default';
        }

        return $domain;
    }

    /**
     * Registriert ein neues Domain-Mapping.
     *
     * @param string $domain Die Domain (kann ein Pattern mit * sein)
     * @param string $key Der zugehörige Domain-Key für die Verzeichnisstruktur
     */
    public static function registerDomainMapping(string $domain, string $key): void
    {
        self::$domainMappings[$domain] = $key;
        rex::setProperty('definitions_domains', self::$domainMappings);
    }

    /**
     * Entfernt ein Domain-Mapping.
     *
     * @param string $domain Die zu entfernende Domain
     * @return bool True wenn erfolgreich, False wenn Domain nicht gefunden
     */
    public static function removeDomainMapping(string $domain): bool
    {
        if (isset(self::$domainMappings[$domain])) {
            unset(self::$domainMappings[$domain]);
            rex::setProperty('definitions_domains', self::$domainMappings);
            return true;
        }
        return false;
    }

    /**
     * Gibt alle registrierten Domain-Mappings zurück.
     *
     * @return array Assoziatives Array mit Domain => Key
     */
    public static function getDomainMappings(): array
    {
        return self::$domainMappings;
    }

    /**
     * Registriert lokale Entwicklungsdomains.
     * Diese werden automatisch auf den angegebenen Domain-Key gemappt.
     *
     * @param string $domain Die lokale Domain (z.B. 'myproject.local')
     * @param string $key Der Domain-Key
     */
    public static function registerLocalDomain(string $domain, string $key): void
    {
        self::$localDomainMappings[$domain] = $key;
    }

    /**
     * Prüft, ob die aktuelle oder angegebene Domain eine lokale Entwicklungsdomain ist.
     *
     * @param string|null $domain Die zu prüfende Domain (optional)
     * @return bool True wenn es sich um eine lokale Domain handelt
     */
    public static function isLocalDomain(string $domain = null): bool
    {
        $domainToCheck = $domain ?? self::getCurrentDomain();
        return isset(self::$localDomainMappings[$domainToCheck]);
    }

    /**
     * Gibt alle verfügbaren Domains zurück (aus Domain-Mappings und lokalen Domains).
     *
     * @param bool $includeLocal Ob lokale Domains einbezogen werden sollen
     * @return array Assoziatives Array mit Domain => Key
     */
    public static function getAllDomains(bool $includeLocal = true): array
    {
        if ($includeLocal) {
            return array_merge(self::$domainMappings, self::$localDomainMappings);
        }
        return self::$domainMappings;
    }
}