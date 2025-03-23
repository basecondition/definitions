<?php

$file = rex_file::get(rex_path::addon('definitions', 'README.md'));
$parser = new ParsedownExtra();
$content = $parser->text($file);

$fragment = new rex_fragment();
$fragment->setVar('title', $this->i18n('documentation'), false);
$fragment->setVar('body', $content, false);
echo $fragment->parse('core/page/section.php');