<?php

namespace Devome\Grr\Http;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Fin anticipée d'une page par le code legacy : remplace « header('Location: …'); exit; »
 * et « echo …; exit; ». L'exception remonte jusqu'au noyau, où PageInterruptionListener
 * renvoie la réponse correspondante — au lieu d'un arrêt brutal du script (page blanche).
 *
 * Elle est levée par grr_redirection() et grr_fin_page() (include/functions.inc.php).
 *
 * L'exception ne transporte que des données simples : la Response est construite par
 * toResponse(), une fois sorti du code legacy. (Créer un objet Response au milieu du
 * démarrage legacy fait planter PHP quand Xdebug est en mode « develop ».)
 */
final class PageInterruption extends \RuntimeException
{
    private function __construct(
        private readonly ?string $redirectUrl,
        private readonly string $content,
    ) {
        parent::__construct('Page GRR terminée avant la fin du contrôleur.');
    }

    public static function redirect(string $url): self
    {
        return new self($url, '');
    }

    /**
     * La page s'arrête en l'état : ce que le code legacy a déjà affiché devient le contenu de la réponse.
     * Les tampons de sortie ouverts depuis le démarrage de la page (niveau $baseLevel) sont récupérés ici,
     * avant que les blocs « finally » des contrôleurs ne les vident.
     */
    public static function withBufferedOutput(int $baseLevel): self
    {
        $content = '';
        while (ob_get_level() > $baseLevel) {
            $captured = ob_get_clean();
            if ($captured !== false) {
                $content = $captured.$content;
            }
        }

        return new self(null, $content);
    }

    public function toResponse(): Response
    {
        return $this->redirectUrl !== null
            ? new RedirectResponse($this->redirectUrl)
            : new Response($this->content);
    }
}
