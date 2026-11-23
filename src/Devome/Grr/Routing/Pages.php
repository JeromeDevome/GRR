<?php

namespace Devome\Grr\Routing;

/**
 * Pages de l'application servies par une route propre « /<page> » (ex. /jour, /report).
 * Le nom de la page est aussi l'ancien paramètre « p » de app.php?p=<page>, conservé en redirection.
 *
 * Toute page ajoutée ici doit aussi figurer dans la règle de réécriture du .htaccess racine
 * (vérifié par tests/symfony_smoke.php).
 */
final class Pages
{
    /** Liste des pages, sous forme d'expression régulière utilisable dans #[Route(requirements: …)]. */
    public const ROUTE_REQUIREMENT = 'account|annee|annee_all|contact|contactresa|creationcompte|deconnexion'
        .'|dragdrop|echangeresa|editentree|editentreetrt|gestionfichier|jour|login|moderationsliste'
        .'|mois|mois2_all|mois_all|page|participation|pdfgenerator|reinitmdp|report|resahistorique|ressourcefiche'
        .'|semaine|semaine_all|supreservation|vuedroitsdomaine|vuedroitsressource|vuereservation';

    /** @return list<string> */
    public static function all(): array
    {
        return explode('|', self::ROUTE_REQUIREMENT);
    }

    public static function exists(string $page): bool
    {
        return in_array($page, self::all(), true);
    }

    /**
     * URL propre d'une page, relative à la racine de l'application (ex. « /GitHub/GRR »).
     *
     * @param array<string, mixed> $parameters paramètres de requête, « p » exclu
     */
    public static function url(string $basePath, string $page, array $parameters = []): string
    {
        unset($parameters['p']);
        $query = http_build_query($parameters);

        return rtrim($basePath, '/').'/'.$page.($query !== '' ? '?'.$query : '');
    }
}
