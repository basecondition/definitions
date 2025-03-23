<?php
use BSC\Definition\DefinitionConfig;

$addon = rex_addon::get('definitions');
$config = DefinitionConfig::getInstance();

// Konfigurationsformular
$form = rex_config_form::factory('definitions');

// Package-Defaults laden
$packageDefaults = $config->getPackageDefaults();

// Base Path
$field = $form->addTextField('base_path');
$field->setLabel($addon->i18n('base_path'));
$value = $addon->getConfig('base_path');
$default = $packageDefaults['base_path'];
$field->setValue($value);
$field->setAttribute('placeholder', $default);
$field->setNotice(
    $addon->i18n('base_path_note') .
    '<br>' .
    '<span class="text-muted">' .
    $addon->i18n('definition_package_default') . ': <code>' . $default . '</code>' .
    ($value && $value !== $default ? '<br>' . $addon->i18n('current_value') . ': <code>' . $value . '</code>' : '') .
    '</span>'
);

// Definition Keys
$fieldset = $form->addFieldset($addon->i18n('definition_keys'));

$savedKeys = $addon->getConfig('definition_keys', []);
foreach ($packageDefaults['definition_keys'] as $key => $defaultPath) {
    $field = $form->addTextField('definition_keys[' . $key . ']');
    $field->setLabel($addon->i18n('definition_key_' . $key));
    $value = $savedKeys[$key] ?? null;
    $field->setValue($value);
    $field->setAttribute('placeholder', $defaultPath);
    $field->setNotice(
        $addon->i18n('definition_keys_note') .
        '<br>' .
        '<span class="text-muted">' .
        $addon->i18n('definition_package_default') . ': <code>' . $defaultPath . '</code>' .
        ($value && $value !== $defaultPath ? '<br>' . $addon->i18n('current_value') . ': <code>' . $value . '</code>' : '') .
        '</span>'
    );
}

// Formular ausgeben
$fragment = new rex_fragment();
$fragment->setVar('class', 'edit', false);
$fragment->setVar('title', $addon->i18n('settings'), false);
$fragment->setVar('body', $form->get(), false);
echo $fragment->parse('core/page/section.php');

// Nach dem Speichern Cache löschen
if (rex_post('btn_save', 'string') !== '') {
    if (rex_dir::delete($addon->getCachePath())) {
        echo rex_view::success($addon->i18n('cache_deleted'));
    } else {
        echo rex_view::warning($addon->i18n('cache_not_deleted'));
    }
}