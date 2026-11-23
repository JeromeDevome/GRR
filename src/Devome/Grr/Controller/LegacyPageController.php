<?php

namespace Devome\Grr\Controller;

use Devome\Grr\Routing\Pages;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Point d'entrée des pages de l'application.
 *
 *  - /<page>             : route propre (ex. /jour, /report) ;
 *  - app.php?p=<page>    : ancienne URL. En GET (hors AJAX jQuery) redirection vers /<page> ;
 *                          en POST / AJAX la page est servie directement (formulaires, drag & drop).
 */
final class LegacyPageController
{
    public function __construct(
        private readonly SemaineController $semaineController,
        private readonly ContactController $contactController,
        private readonly ContentPageController $contentPageController,
        private readonly ResourceSheetController $resourceSheetController,
        private readonly AccessRightsController $accessRightsController,
        private readonly ReservationHistoryController $reservationHistoryController,
        private readonly ModerationListController $moderationListController,
        private readonly LogoutController $logoutController,
        private readonly LoginController $loginController,
        private readonly AccountCreationController $accountCreationController,
        private readonly PasswordResetController $passwordResetController,
        private readonly AccountController $accountController,
        private readonly ContactReservationController $contactReservationController,
        private readonly PdfGeneratorController $pdfGeneratorController,
        private readonly YearPlanningController $yearPlanningController,
        private readonly BookingDeletionController $bookingDeletionController,
        private readonly ParticipationController $participationController,
        private readonly PlanningCalendarController $planningCalendarController,
        private readonly ReportController $reportController,
        private readonly ReservationViewController $reservationViewController,
        private readonly ReservationFileController $reservationFileController,
        private readonly ReservationDragDropController $reservationDragDropController,
        private readonly ReservationEditController $reservationEditController,
        private readonly ReservationEditSubmitController $reservationEditSubmitController,
        private readonly ReservationExchangeController $reservationExchangeController,
    ) {
    }

    #[Route('/{page}', name: 'grr_page', requirements: ['page' => Pages::ROUTE_REQUIREMENT])]
    #[Route('/{projectPath}/{page}', name: 'grr_page_subdirectory', requirements: ['projectPath' => '.+', 'page' => Pages::ROUTE_REQUIREMENT])]
    public function page(Request $request, string $page, string $projectPath = ''): Response
    {
        // Ancien lien relatif « ?p=autre » résolu sur une route propre : on rejoint la bonne page
        $requested = $request->query->get('p');
        if (is_string($requested) && $requested !== $page && Pages::exists($requested) && $this->isRedirectable($request)) {
            return new RedirectResponse(Pages::url($this->basePath($request, $projectPath), $requested, $request->query->all()));
        }

        // Le code legacy lit la page courante dans $_GET['p'] / QUERY_STRING
        $request->query->set('p', $page);
        $_GET['p'] = $page;
        $_SERVER['QUERY_STRING'] = http_build_query($request->query->all());

        return $this->dispatch($page, $request);
    }

    #[Route('/app.php', name: 'symfony_legacy_page')]
    #[Route('/{projectPath}/app.php', name: 'symfony_legacy_page_subdirectory', requirements: ['projectPath' => '.+'])]
    public function index(Request $request, string $projectPath = ''): Response
    {
        $page = $request->query->get('p');
        if ($page === '') {
            $page = $this->previousNonEmptyPageParameter($request) ?? $page;
        }

        if ($page === null) {
            return $this->loginController->index($request);
        }
        if (!is_string($page) || !Pages::exists($page)) {
            return new RedirectResponse($this->basePath($request, $projectPath).'/index.php');
        }

        if ($this->isRedirectable($request)) {
            return new RedirectResponse(Pages::url($this->basePath($request, $projectPath), $page, $request->query->all()));
        }

        $request->query->set('p', $page);
        $_GET['p'] = $page;

        return $this->dispatch($page, $request);
    }

    private function dispatch(string $page, Request $request): Response
    {
        return match ($page) {
            'semaine' => $this->semaineController->index($request),
            'contact' => $this->contactController->index($request),
            'page' => $this->contentPageController->index($request),
            'ressourcefiche' => $this->resourceSheetController->index($request),
            'vuedroitsdomaine' => $this->accessRightsController->domain($request),
            'vuedroitsressource' => $this->accessRightsController->resource($request),
            'resahistorique' => $this->reservationHistoryController->index($request),
            'moderationsliste' => $this->moderationListController->index($request),
            'deconnexion' => $this->logoutController->index($request),
            'login' => $this->loginController->index($request),
            'account' => $this->accountController->index($request),
            'creationcompte' => $this->accountCreationController->index($request),
            'reinitmdp' => $this->passwordResetController->index($request),
            'contactresa' => $this->contactReservationController->index($request),
            'pdfgenerator' => $this->pdfGeneratorController->index($request),
            'annee', 'annee_all' => $this->yearPlanningController->index($request),
            'supreservation' => $this->bookingDeletionController->index($request),
            'participation' => $this->participationController->index($request),
            'jour' => $this->planningCalendarController->jour($request),
            'mois' => $this->planningCalendarController->mois($request),
            'semaine_all' => $this->planningCalendarController->semaineAll($request),
            'mois_all' => $this->planningCalendarController->moisAll($request),
            'mois2_all' => $this->planningCalendarController->mois2All($request),
            'report' => $this->reportController->index($request),
            'vuereservation' => $this->reservationViewController->index($request),
            'gestionfichier' => $this->reservationFileController->manage($request),
            'dragdrop' => $this->reservationDragDropController->move($request),
            'editentree' => $this->reservationEditController->index($request),
            'editentreetrt' => $this->reservationEditSubmitController->submit($request),
            'echangeresa' => $this->reservationExchangeController->exchange($request),
        };
    }

    #[Route('/compte', name: 'symfony_legacy_account_root', defaults: ['legacyPath' => ''])]
    #[Route('/compte/{legacyPath}', name: 'symfony_legacy_account', requirements: ['legacyPath' => '.+'])]
    #[Route('/{projectPath}/compte', name: 'symfony_legacy_account_subdirectory_root', requirements: ['projectPath' => '.+'], defaults: ['legacyPath' => ''])]
    #[Route('/{projectPath}/compte/{legacyPath}', name: 'symfony_legacy_account_subdirectory', requirements: ['projectPath' => '.+', 'legacyPath' => '.+'])]
    public function legacyAccount(Request $request, string $legacyPath = '', string $projectPath = ''): Response
    {
        $parameters = $request->query->all();
        if ($legacyPath === 'my_account_modif_listes.php') {
            $parameters['legacy_lists'] = '1';
        } elseif (preg_match('~^controleurs/(moncompte|changemdp|mesreservations|mesconnexions)\.php$~', $legacyPath, $matches) === 1) {
            $parameters['pc'] = $matches[1];
        }
        $status = $request->isMethod('POST') ? Response::HTTP_TEMPORARY_REDIRECT : Response::HTTP_FOUND;

        return new RedirectResponse(Pages::url($this->basePath($request, $projectPath), 'account', $parameters), $status);
    }

    /**
     * Seules les navigations GET sont redirigées vers l'URL propre : un POST perdrait ses données
     * et les appels AJAX jQuery attendent la réponse directement.
     */
    private function isRedirectable(Request $request): bool
    {
        return $request->isMethod('GET') && !$request->isXmlHttpRequest();
    }

    private function basePath(Request $request, string $projectPath): string
    {
        $basePath = $request->getBasePath();
        if ($basePath === '' && $projectPath !== '') {
            $basePath = '/'.trim($projectPath, '/');
        }

        return rtrim($basePath, '/');
    }

    private function previousNonEmptyPageParameter(Request $request): ?string
    {
        $queryString = $request->server->get('QUERY_STRING', '');
        if (!is_string($queryString)) {
            return null;
        }

        $page = null;
        foreach (explode('&', $queryString) as $parameter) {
            [$name, $value] = array_pad(explode('=', $parameter, 2), 2, '');
            $value = urldecode($value);
            if (urldecode($name) === 'p' && $value !== '') {
                $page = $value;
            }
        }

        return $page;
    }
}
