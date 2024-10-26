<?php

/** @var rex_addon $this */

// Nutze rex_be_controller um zur Settings-Seite weiterzuleiten
echo rex_view::title($this->i18n('title'));

// Include current page
include rex_be_controller::getCurrentPageObject()->getSubPath();