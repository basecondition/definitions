<?php
/**
 * @var rex_addon $this
 *
 * ┓            ┓• •       ┓  ┏•  • •
 * ┣┓┏┓┏┏┓┏┏┓┏┓┏┫┓╋┓┏┓┏┓  ┏┫┏┓╋┓┏┓┓╋┓┏┓┏┓┏
 * ┗┛┗┻┛┗ ┗┗┛┛┗┗┻┗┗┗┗┛┛┗  ┗┻┗ ┛┗┛┗┗┗┗┗┛┛┗┛
 * @package definitions
 * @author Joachim Doerr
 * @copyright (C) hello@basecondition.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

// add all config stuff by late PACKAGES_INCLUDED
use BSC\Definition\DefinitionConfig;
use BSC\Domain\DomainContextProvider;
use BSC\Language\LanguageContextProvider;
use BSC\Context\DefinitionContext;

// Domain-Provider initialisieren
DomainContextProvider::init();

// YRewrite-Integration, falls vorhanden
if (rex_addon::exists('yrewrite') && rex_addon::get('yrewrite')->isAvailable()) {
    // YRewrite-basierte Domain-Erkennung aktivieren
    rex_extension::register('BSC_DOMAIN_DETECTION', function(rex_extension_point $ep) {
        $domain = rex_yrewrite::getCurrentDomain();
        if ($domain) {
            return $domain->getName();
        }
        return $ep->getSubject();
    }, rex_extension::EARLY);
}

// Sprach-Provider initialisieren
LanguageContextProvider::init();

// Hauptladeprozess der Definitionen (wird spät ausgeführt)
rex_extension::register('PACKAGES_INCLUDED', static function () {
    // Basis-Konfiguration laden
    $config = DefinitionConfig::getInstance();
    $baseSchemes = $config->getSearchSchemes();

    // Kontext-bewusste Pfade erstellen
    $contextSchemes = [];
    foreach ($baseSchemes as $scheme) {
        $basePath = dirname($scheme);
        $filename = basename($scheme);

        // Je nach Typ unterschiedliche Pfade generieren
        if (strpos($basePath, 'module') !== false) {
            $modulePaths = DefinitionContext::getModuleSearchPaths($basePath);
            foreach ($modulePaths as $path) {
                $contextSchemes[] = $path;
            }
        } else {
            $contextPaths = DefinitionContext::getSearchPaths($basePath);
            foreach ($contextPaths as $path) {
                $contextSchemes[] = $path;
            }
        }
    }

    // Definitionen mit kontext-bewussten Pfaden laden
    BSC\config::loadConfig($contextSchemes);
}, rex_extension::LATE);

//rex_extension::register('PACKAGES_INCLUDED', static function () {
//    // add search schemes for all default config definitions
//    $config = DefinitionConfig::getInstance();
//    $schemes =$config->getSearchSchemes();
//    // add and load some config definition stuff
//    BSC\config::loadConfig($schemes);
//    // erst ganz am schluss nach dem yrewrite und ycom initialisiert wurde kann alles geladen und verarbeitet werden
//}, rex_extension::LATE);

// wenn man eine config definition hinzufügen möchte geht das wie bei folgendem beispiel
//rex_extension::register('BSC_CONFIG_LOAD', static function(rex_extension_point $ep) {
//    /** @var array $subject */
//    $subject = $ep->getSubject();
//    $newSubject = [];
//    /**
//     * @var int $key
//     * @var string $item
//     */
//    foreach ($subject as $key => $item) {
//        $newSubject[$key] = $item;
//    }
//    $ep->setSubject($newSubject);
//});
