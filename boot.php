<?php
/**
 * ┓            ┓• •
 * ┣┓┏┓┏┏┓┏┏┓┏┓┏┫┓╋┓┏┓┏┓
 * ┗┛┗┻┛┗ ┗┗┛┛┗┗┻┗┗┗┗┛┛┗━━
 * @package definitions
 * @author Joachim Doerr
 * @copyright (C) hello@basecondition.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

// add all config stuff by late PACKAGES_INCLUDED
use BSC\Definition\DefinitionConfig;

rex_extension::register('PACKAGES_INCLUDED', static function () {
    // add search schemes for all default config definitions
    $config = DefinitionConfig::getInstance();
    $schemes =$config->getSearchSchemes();
    // add and load some config definition stuff
    BSC\config::loadConfig($schemes);
    // erst ganz am schluss nach dem yrewrite und ycom initialisiert wurde kann alles geladen und verarbeitet werden
}, rex_extension::LATE);

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
