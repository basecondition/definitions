<?php
namespace BSC\Context;

use BSC\Domain\DomainContextProvider;
use BSC\Language\LanguageContextProvider;
use BSC\base;

class DefinitionContext
{
    public static function getSearchPaths(string $basePath): array
    {
        $domain = DomainContextProvider::getDomainKey();
        $language = LanguageContextProvider::getCurrentLanguage();
        $template = base::getTemplateKey();

        // Priorisierte Pfade, von spezifisch nach allgemein
        $paths = [];

        // Nur wenn eine spezifische Domain vorhanden ist und nicht 'default'
        if ($domain && $domain !== 'default') {
            // Domain + Sprache + Template
            if ($language && $template) {
                $paths[] = "$basePath/$domain/$language/$template/*.yml";
            }

            // Domain + Sprache
            if ($language) {
                $paths[] = "$basePath/$domain/$language/*.yml";
            }

            // Domain + Template
            if ($template) {
                $paths[] = "$basePath/$domain/$template/*.yml";
            }

            // Nur Domain
            $paths[] = "$basePath/$domain/*.yml";
        }

        // Globale Sprachdefinitionen
        if ($language) {
            // Sprache + Template
            if ($template) {
                $paths[] = "$basePath/$language/$template/*.yml";
            }

            // Nur Sprache
            $paths[] = "$basePath/$language/*.yml";
        }

        // Nur Template
        if ($template) {
            $paths[] = "$basePath/$template/*.yml";
        }

        // Standard (weder Domain noch Sprache noch Template)
        $paths[] = "$basePath/*.yml";

        return $paths;
    }

    public static function getModuleSearchPaths(string $basePath): array
    {
        $domain = DomainContextProvider::getDomainKey();
        $language = LanguageContextProvider::getCurrentLanguage();
        $template = base::getTemplateKey();

        $paths = [];

        // Domainspezifische Modul-Pfade
        if ($domain && $domain !== 'default') {
            // Mit Sprache
            if ($language) {
                $paths[] = "$basePath/$domain/$language/*/*.yml";
            }

            // Nur Domain
            $paths[] = "$basePath/$domain/*/*.yml";
        }

        // Sprachspezifische Modul-Pfade
        if ($language) {
            $paths[] = "$basePath/$language/*/*.yml";
        }

        // Template-spezifische Modul-Pfade
        if ($template) {
            $paths[] = "$basePath/$template/*/*.yml";
        }

        // Standard-Modul-Pfade
        $paths[] = "$basePath/*/*.yml";

        return $paths;
    }
}