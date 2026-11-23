<?php

namespace Devome\Grr\Controller;

use Devome\Grr\Service\GrrApplicationContext;
use Pages;
use SecuChaine;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Twig\Environment;

final class ContentPageController
{
    public function __construct(
        private readonly Environment $twig,
        private readonly GrrApplicationContext $applicationContext,
    ) {
    }

    public function index(Request $request): Response
    {
        if ($request->query->get('p') !== 'page') {
            throw new NotFoundHttpException();
        }

        global $d;

        $bootstrap = $this->applicationContext->boot();
        $context = $bootstrap['context'];
        $d = $context['d'] ?? [];

        $pageName = SecuChaine::Alphanumeric($request->query->getString('pageaffiche', ''));
        $validPopup = $request->query->getString('validePopup', '');

        if ($pageName === 'popup') {
            if (($context['userConnecte'] ?? 'no_session') !== 'with_session') {
                return new Response('Accès interdit', Response::HTTP_FORBIDDEN);
            }

            $d['modePage'] = 2;
            if ($validPopup === '1') {
                $result = grr_sql_query(
                    "UPDATE ".TABLE_PREFIX."_utilisateurs SET popup = 0 WHERE login='".SecuChaine::ProtectDataSql(getUserName())."'"
                );
                if (!$result) {
                    throw new \RuntimeException('Unable to record the popup acknowledgement.');
                }

                $referer = $request->headers->get('referer');
                if ($referer === null || parse_url($referer, PHP_URL_HOST) !== $request->getHost()) {
                    $referer = page_accueil();
                }

                return new RedirectResponse($referer);
            }
        } else {
            $d['modePage'] = 1;
        }

        require_once dirname(__DIR__, 4).'/include/pages.class.php';

        if (!Pages::load()) {
            throw new \RuntimeException('Unable to load GRR content pages.');
        }

        $page = Pages::get($pageName);
        if ($page === null) {
            throw new NotFoundHttpException(sprintf('The content page "%s" does not exist.', $pageName));
        }

        if ($page[2]) {
            $d['CtnPage'] = "Impossible d'y accèder.";
        } else {
            $d['nomPage'] = $pageName;
            $d['TitrePage'] = $page[0];
            $d['CtnPage'] = $page[1];
        }

        return new Response(
            $this->twig->render('page.twig', [
                'd' => $d,
                'settings' => $context['AllSettings'] ?? [],
            ]),
            $bootstrap['status'],
            $bootstrap['headers'],
        );
    }
}
