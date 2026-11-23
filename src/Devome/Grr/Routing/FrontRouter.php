<?php

namespace Devome\Grr\Routing;

class FrontRouter
{
    /** URL relative de la liste des réservations à modérer, en conservant domaine / ressource / site. */
    public static function toModeratePage(): string
    {
        $parameters = [];
        foreach (['area', 'room', 'id_site'] as $key) {
            if (isset($_GET[$key])) {
                $parameters[$key] = (int) $_GET[$key];
            }
        }

        return 'moderationsliste'.($parameters !== [] ? '?'.http_build_query($parameters) : '');
    }
}
