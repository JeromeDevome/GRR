<?php

namespace Devome\Grr\Controller;

use Devome\Grr\Service\GrrApplicationContext;
use Settings;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Twig\Environment;
use Symfony\Contracts\Translation\TranslatorInterface;

final class LogoutController
{
    public function __construct(
        private readonly Environment $twig,
        private readonly GrrApplicationContext $applicationContext,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function index(Request $request): Response
    {
        if ($request->query->get('p') !== 'deconnexion') {
            throw new NotFoundHttpException();
        }

        $bootstrap = $this->applicationContext->boot();
        $context = $bootstrap['context'];
        global $d, $vocab, $gSessionName;
        $d = $context['d'] ?? [];
        $vocab = $context['vocab'] ?? [];

        $auto = $request->query->getInt('auto');
        if (in_array(Settings::get('sso_statut'), ['lasso_visiteur', 'lasso_utilisateur'], true)) {
            require_once SPKITLASSO.'/lassospkit_public_api.inc.php';
            session_name($gSessionName);
            session_start();
            if (!empty($_SESSION['lasso_nameid'])) {
                lassospkit_set_userid(getUserName());
                lassospkit_set_nameid($_SESSION['lasso_nameid']);
                lassospkit_soap_logout();
                lassospkit_clean();
            }
        }

        grr_closeSession($auto);

        $headers = $this->captureHeaders($bootstrap['headers']);
        if ($request->query->has('url')) {
            $url = rawurlencode($request->query->getString('url'));

            return new RedirectResponse('login?url='.$url, Response::HTTP_FOUND, $headers);
        }

        $disconnectUrl = Settings::get('url_disconnect');
        if ($disconnectUrl !== '') {
            return new RedirectResponse((string) $disconnectUrl, Response::HTTP_FOUND, $headers);
        }

        if ($request->query->getString('redirect_page_accueil', '') === 'yes') {
            return new RedirectResponse('./'.htmlspecialchars_decode(page_accueil()), Response::HTTP_FOUND, $headers);
        }

        $d['msgLogout'] = $this->translator->trans($auto ? 'msg_logout2' : 'msg_logout1');

        return new Response(
            $this->twig->render('deconnexion.twig', [
                'd' => $d,
                'settings' => $context['AllSettings'] ?? [],
            ]),
            $bootstrap['status'],
            $headers,
        );
    }

    private function captureHeaders(array $headers): array
    {
        foreach (headers_list() as $headerLine) {
            $separator = strpos($headerLine, ':');
            if ($separator === false) {
                continue;
            }

            $name = trim(substr($headerLine, 0, $separator));
            $headers[$name][] = trim(substr($headerLine, $separator + 1));
        }

        header_remove();

        return $headers;
    }
}
