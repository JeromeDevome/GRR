<?php

namespace Devome\Grr\Service;

use SecuChaine;
use Settings;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Réinitialisation du mot de passe par l'utilisateur (« mot de passe oublié »).
 *
 * Le lien envoyé par mail contient un jeton signé (HMAC avec APP_SECRET) portant l'identifiant,
 * la date d'expiration et une empreinte du mot de passe actuel : aucun stockage n'est nécessaire
 * et le lien devient invalide dès que le mot de passe est modifié (usage unique).
 */
final class PasswordReset
{
    /** Durée de validité du lien, en minutes (paramètre fct_reinit_mdp_duree) : valeur par défaut et bornes. */
    public const DEFAULT_VALIDITY_MINUTES = 60;
    public const MIN_VALIDITY_MINUTES = 5;
    public const MAX_VALIDITY_MINUTES = 1440;

    public function __construct(
        #[Autowire('%kernel.secret%')]
        private readonly string $secret,
    ) {
    }

    /**
     * La fonction est disponible si elle est activée dans l'administration, que l'envoi de mails
     * est configuré et que l'URL de GRR (utilisée pour construire le lien) est renseignée.
     */
    public static function isEnabled(): bool
    {
        return Settings::get('fct_reinit_mdp') == 1
            && !in_array((string) Settings::get('grr_mail_method'), ['', 'bloque'], true)
            && trim((string) Settings::get('grr_url')) !== '';
    }

    /** Durée de validité du lien configurée dans l'administration, ramenée dans les bornes autorisées. */
    public static function validityMinutes(): int
    {
        $minutes = (int) Settings::get('fct_reinit_mdp_duree');
        if ($minutes <= 0) {
            return self::DEFAULT_VALIDITY_MINUTES;
        }

        return max(self::MIN_VALIDITY_MINUTES, min(self::MAX_VALIDITY_MINUTES, $minutes));
    }

    /** La recherche par adresse mail n'est possible que si les adresses sont uniques. */
    public static function allowsEmailLookup(): bool
    {
        return Settings::get('mail_user_unique') == 1;
    }

    /**
     * Compte local actif correspondant à l'identifiant, ou à l'adresse mail si autorisé.
     *
     * @return array{login: string, nom: string, prenom: string, email: string, password: string}|null
     */
    public function findUser(string $identifier): ?array
    {
        $identifier = trim($identifier);
        if ($identifier === '') {
            return null;
        }

        $columns = 'login, nom, prenom, email, password';
        $conditions = "source='local' AND etat='actif' AND email<>''";
        $value = SecuChaine::ProtectDataSql($identifier);

        $user = $this->fetchSingle("SELECT $columns FROM ".TABLE_PREFIX."_utilisateurs WHERE login='$value' AND $conditions");
        if ($user === null && self::allowsEmailLookup() && str_contains($identifier, '@')) {
            $user = $this->fetchSingle("SELECT $columns FROM ".TABLE_PREFIX."_utilisateurs WHERE email='$value' AND $conditions");
        }

        return $user;
    }

    public function createToken(array $user, ?int $now = null): string
    {
        $expires = ($now ?? time()) + self::validityMinutes() * 60;
        $encodedLogin = rtrim(strtr(base64_encode($user['login']), '+/', '-_'), '=');

        return $encodedLogin.'.'.$expires.'.'.$this->signature($user['login'], $expires, $user['password']);
    }

    /**
     * Utilisateur ciblé par un jeton valide, non expiré et toujours d'actualité, sinon null.
     *
     * @return array{login: string, nom: string, prenom: string, email: string, password: string}|null
     */
    public function userFromToken(string $token, ?int $now = null): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3 || !ctype_digit($parts[1])) {
            return null;
        }

        [$encodedLogin, $expires, $signature] = $parts;
        if ((int) $expires < ($now ?? time())) {
            return null;
        }

        $login = base64_decode(strtr($encodedLogin, '-_', '+/'), true);
        if (!is_string($login) || $login === '') {
            return null;
        }

        $user = $this->fetchSingle(
            'SELECT login, nom, prenom, email, password FROM '.TABLE_PREFIX."_utilisateurs WHERE login='"
            .SecuChaine::ProtectDataSql($login)."' AND source='local' AND etat='actif'"
        );
        if ($user === null || !hash_equals($this->signature($user['login'], (int) $expires, $user['password']), $signature)) {
            return null;
        }

        return $user;
    }

    public function updatePassword(string $login, string $newPassword): void
    {
        $sql = 'UPDATE '.TABLE_PREFIX."_utilisateurs SET password='"
            .SecuChaine::ProtectDataSql(password_hash($newPassword, PASSWORD_DEFAULT))
            ."', changepwd='0', nb_tentative='0', date_blocage='0' WHERE login='".SecuChaine::ProtectDataSql($login)."'";
        if (grr_sql_command($sql) < 0) {
            throw new \RuntimeException('Unable to reset the password: '.grr_sql_error());
        }
    }

    private function signature(string $login, int $expires, string $passwordHash): string
    {
        $key = hash('sha256', 'grr-password-reset|'.$this->secret, true);

        return hash_hmac('sha256', $login."\n".$expires."\n".$passwordHash, $key);
    }

    /** @return array{login: string, nom: string, prenom: string, email: string, password: string}|null */
    private function fetchSingle(string $sql): ?array
    {
        $result = grr_sql_query($sql);
        if (!$result) {
            throw new \RuntimeException('Unable to load the user for password reset: '.grr_sql_error());
        }

        $user = null;
        if (grr_sql_count($result) === 1) {
            $row = grr_sql_row($result, 0);
            $user = [
                'login' => (string) $row[0],
                'nom' => (string) $row[1],
                'prenom' => (string) $row[2],
                'email' => (string) $row[3],
                'password' => (string) $row[4],
            ];
        }
        grr_sql_free($result);

        return $user;
    }
}
