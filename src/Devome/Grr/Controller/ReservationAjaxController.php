<?php

namespace Devome\Grr\Controller;

use Devome\Grr\Service\GrrApplicationContext;
use Devome\Grr\Service\LegacyControllerResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ReservationAjaxController
{
    private const SCRIPTS = [
        'captcha.inc.php' => 'captcha.inc.php',
        'edit_entry_beneficiaire.php' => 'edit_entry_beneficiaire.php',
        'edit_entry_champs_add.php' => 'edit_entry_champs_add.php',
        'edit_entry_description_params.php' => 'edit_entry_description_params.php',
        'edit_entry_types.php' => 'edit_entry_types.php',
        'frmcontactlist.php' => 'frmcontactlist.php',
    ];

    public function __construct(
        private readonly GrrApplicationContext $applicationContext,
        private readonly TranslatorInterface $translator,
        private readonly LegacyControllerResponse $response,
    ) {
    }

    #[Route('/reservation/ajax/{action}', name: 'symfony_reservation_ajax', requirements: ['action' => '[A-Za-z0-9_.-]+'])]
    #[Route('/{projectPath}/reservation/ajax/{action}', name: 'symfony_reservation_ajax_subdirectory', requirements: ['projectPath' => '.+', 'action' => '[A-Za-z0-9_.-]+'])]
    public function index(Request $request, string $action, string $projectPath = ''): Response
    {
        if (!isset(self::SCRIPTS[$action])) {
            throw new NotFoundHttpException();
        }

        $projectDir = dirname(__DIR__, 4);
        $workingDirectory = getcwd();
        if ($workingDirectory === false || !chdir($projectDir)) {
            throw new \RuntimeException('Unable to prepare the reservation AJAX endpoint.');
        }

        try {
            if ($action === 'captcha.inc.php') {
                return $this->response->capture(
                    ['context' => [], 'headers' => [], 'status' => Response::HTTP_OK],
                    static function () use ($projectDir, $action): void {
                        include $projectDir.'/src/Devome/Grr/LegacyAjax/'.self::SCRIPTS[$action];
                    },
                );
            }

            if ($action === 'edit_entry_champs_add.php'
                && (!$request->query->has('id') || !$request->query->has('areas') || !$request->query->has('room'))
            ) {
                throw new BadRequestHttpException('Les paramètres id, areas et room sont requis.');
            }
            if ($action === 'edit_entry_types.php'
                && (!$request->query->has('areas') || !$request->query->has('room'))
            ) {
                throw new BadRequestHttpException('Les paramètres areas et room sont requis.');
            }

            $bootstrap = $this->applicationContext->boot();
            $context = $bootstrap['context'];

            if (in_array($action, [
                'edit_entry_beneficiaire.php',
                'edit_entry_champs_add.php',
                'edit_entry_description_params.php',
                'edit_entry_types.php',
            ], true) && !grr_resumeSession()) {
                $basePath = rtrim($request->getBasePath(), '/');

                return new RedirectResponse(
                    $basePath.'/deconnexion?auto=1&url='.rawurlencode($request->getRequestUri()),
                    Response::HTTP_FOUND,
                    $bootstrap['headers'],
                );
            }

            // Mis à disposition des scripts de LegacyAjax/ (inclus ci-dessous)
            $translator = $this->translator;
            $response = $this->response->capture(
                $bootstrap,
                static function () use ($context, $projectDir, $action, $translator): void {
                    extract($context, EXTR_SKIP);
                    include $projectDir.'/src/Devome/Grr/LegacyAjax/'.self::SCRIPTS[$action];
                },
            );

            $location = $response->headers->get('Location');
            // Redirection relative émise par le code legacy (app.php ou page propre comme « ./deconnexion ») :
            // résolue depuis la racine de l'application et non depuis /reservation/ajax/
            $target = is_string($location) ? basename((string) parse_url($location, PHP_URL_PATH)) : '';
            if ($target === 'app.php' || \Devome\Grr\Routing\Pages::exists($target)) {
                $query = parse_url($location, PHP_URL_QUERY);
                $basePath = rtrim($request->getBasePath(), '/');
                $response->headers->set(
                    'Location',
                    $basePath.'/'.$target.(is_string($query) && $query !== '' ? '?'.$query : ''),
                );
            }

            return $response;
        } finally {
            if (!chdir($workingDirectory)) {
                throw new \RuntimeException(sprintf('Unable to restore the working directory to "%s".', $workingDirectory));
            }
        }
    }
}
