<?php

namespace Devome\Grr;

/**
 * Fournit APP_SECRET à Symfony.
 *
 * Une variable d'environnement définie par le serveur (SetEnv APP_SECRET, SetEnvIf en local…)
 * reste prioritaire ; sinon la valeur $appSecret de personnalisation/connect.inc.php est utilisée.
 */
final class AppSecret
{
    public static function load(string $projectDir): void
    {
        if (self::isDefined($_SERVER['APP_SECRET'] ?? null) || self::isDefined($_ENV['APP_SECRET'] ?? null) || self::isDefined(getenv('APP_SECRET'))) {
            return;
        }

        $connectFile = $projectDir.'/personnalisation/connect.inc.php';
        if (!is_file($connectFile)) {
            return;
        }

        // Inclusion dans une portée isolée : seules les variables du fichier sont visibles.
        $secret = (static function (string $file) {
            include $file;

            return $appSecret ?? null;
        })($connectFile);

        if (self::isDefined($secret)) {
            $_SERVER['APP_SECRET'] = $secret;
        }
    }

    private static function isDefined(mixed $value): bool
    {
        return is_string($value) && $value !== '';
    }
}
