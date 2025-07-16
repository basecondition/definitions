<?php
namespace BSC\Domain;

use rex;
use rex_addon;
use rex_extension;
use rex_extension_point;
use rex_yrewrite;

class YRewriteIntegration
{
    public static function init(): void
    {
        // Nur wenn YRewrite aktiv ist
        if (!rex_addon::exists('yrewrite') || !rex_addon::get('yrewrite')->isAvailable()) {
            return;
        }

        // Lade Domain-Mappings aus YRewrite
        self::loadYRewriteDomains();

        // Extension Point für Domain-Erkennung
        rex_extension::register('BSC_DOMAIN_DETECTION', function(rex_extension_point $ep) {
            $domain = rex_yrewrite::getCurrentDomain();
            if ($domain) {
                return $domain->getName();
            }
            return $ep->getSubject();
        }, rex_extension::EARLY);
    }

    public static function loadYRewriteDomains(): void
    {
        if (!rex_addon::exists('yrewrite') || !rex_addon::get('yrewrite')->isAvailable()) {
            return;
        }

        $domains = rex_yrewrite::getDomains();
        $domainMappings = rex::getProperty('definitions_domains', []);

        foreach ($domains as $domain) {
            $domainName = $domain->getName();
            $domainKey = str_replace(['.', '-'], '_', $domainName);
            $domainMappings[$domainName] = $domainKey;
        }

        rex::setProperty('definitions_domains', $domainMappings);
    }
}