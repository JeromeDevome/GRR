<?php

namespace Devome\Grr\Routing;

class FrontRouter
{
    public static function toModeratePage(): string
    {
        $url = '?p=moderationsliste';
        if (isset($_GET['area'])) {
            $url .= '&area='.(int)$_GET['area'];
        }
        if (isset($_GET['room'])) {
            $url .= '&room='.(int)$_GET['room'];
        }
        if (isset($_GET['id_site'])) {
            $url .= '&id_site='.(int)$_GET['id_site'];
        }

        return $url;
    }
}