<?php

namespace BSC;

use BSC\Definition\AbstractDefinitionProvider;
use rex_article;
use rex_template;

/**
 * @description die BSC\base klasse extendet vom AbstractDefinitionProvider und stellt somit ein basis array als system grundbaum für alle möglichen übergreifend
 *  einzusetzenden elementen dar. sie kann genutzt werden um jede art von objekte abzulegen und sie somit global verfügbar zu machen.
 * TODO: description ausbauen -> verwendung beschreiben, EP's beschreiben, rückbezug auf definitions und config als info.
 */
class base extends AbstractDefinitionProvider
{
    public static function getTemplateKey(int|null $id = null): ?string
    {
        if (is_null($id) && !is_null($article = rex_article::getCurrent())) {
            $id = $article->getTemplateId();
        }
        $template = new rex_template($id);
        return $template->getKey();
    }

    public static function config(string|int|null $key = null, mixed $default = null): mixed
    {
        return parent::get($key, $default);
    }
}
