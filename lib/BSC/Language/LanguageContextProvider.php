<?php
namespace BSC\Language;


use rex;
use rex_clang;
use rex_extension;
use rex_extension_point;

class LanguageContextProvider
{
    private static ?string $currentLanguage = null;

    /**
     * Initialisiert den LanguageContextProvider.
     */
    public static function init(): void
    {
        // Extension Point für Spracherkennung registrieren
        rex_extension::register('BSC_LANGUAGE_DETECTION', function(rex_extension_point $ep) {
            return self::detectLanguage();
        });
    }

    /**
     * Gibt die aktuelle Sprache zurück.
     *
     * @return string Die aktuelle Sprache als Code (z.B. 'de_de')
     */
    public static function getCurrentLanguage(): string
    {
        if (self::$currentLanguage === null) {
            self::$currentLanguage = rex_extension::registerPoint(
                new rex_extension_point('BSC_LANGUAGE_DETECTION', self::getDefaultLanguage())
            );
        }

        return self::$currentLanguage;
    }

    /**
     * Setzt die aktuelle Sprache manuell.
     *
     * @param string $language Die zu setzende Sprache
     */
    public static function setCurrentLanguage(string $language): void
    {
        self::$currentLanguage = $language;
    }

    /**
     * Erkennt die aktuelle Sprache anhand von REDAXO-Mechanismen.
     *
     * @return string Der erkannte Sprachcode
     */
    private static function detectLanguage(): string
    {
        // Im Frontend
        if (!rex::isBackend()) {
            $clang = rex_clang::getCurrent();
            if ($clang) {
                return $clang->getCode();
            }
        }
        // Im Backend
        else {
            $clangId = rex_request('clang', 'int', rex_clang::getStartId());
            $clang = rex_clang::get($clangId);
            if ($clang) {
                return $clang->getCode();
            }
        }

        // Fallback auf System-Sprache
        return self::getDefaultLanguage();
    }

    /**
     * Gibt die Standard-Sprache zurück.
     *
     * @return string Der Standard-Sprachcode
     */
    private static function getDefaultLanguage(): string
    {
        // Verwende die erste verfügbare Sprache
        $startClang = rex_clang::get(rex_clang::getStartId());
        if ($startClang) {
            return $startClang->getCode();
        }

        // Absoluter Fallback
        return rex::getProperty('lang', 'de_de');
    }

    /**
     * Gibt alle verfügbaren Sprachen zurück.
     *
     * @return array Assoziatives Array mit Code => Name
     */
    public static function getAvailableLanguages(): array
    {
        $languages = [];

        foreach (rex_clang::getAll() as $clang) {
            $languages[$clang->getCode()] = $clang->getName();
        }

        return $languages;
    }
}