<?php

namespace Devome\Grr\Controller;

use Devome\Grr\Routing\Pages;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class LegacyReservationController
{
    #[Route('/reservation', name: 'symfony_legacy_reservation_root')]
    #[Route('/{projectPath}/reservation', name: 'symfony_legacy_reservation_subdirectory', requirements: ['projectPath' => '.+'])]
    public function root(Request $request, string $projectPath = ''): Response
    {
        return new RedirectResponse($this->basePath($request, $projectPath).'/index.php');
    }

    #[Route('/reservation/controleurs/{page}.php', name: 'symfony_legacy_reservation_controller', requirements: ['page' => '[A-Za-z0-9_]+'])]
    #[Route('/{projectPath}/reservation/controleurs/{page}.php', name: 'symfony_legacy_reservation_controller_subdirectory', requirements: ['projectPath' => '.+', 'page' => '[A-Za-z0-9_]+'])]
    public function controller(Request $request, string $page, string $projectPath = ''): Response
    {
        if ($page === 'index') {
            return new RedirectResponse($this->basePath($request, $projectPath).'/index.php');
        }
        if (!Pages::exists($page)) {
            return new RedirectResponse($this->basePath($request, $projectPath).'/index.php');
        }

        $status = $request->isMethod('POST')
            ? Response::HTTP_TEMPORARY_REDIRECT
            : Response::HTTP_FOUND;

        return new RedirectResponse(
            Pages::url($this->basePath($request, $projectPath), $page, $request->query->all()),
            $status,
        );
    }

    private function basePath(Request $request, string $projectPath): string
    {
        $basePath = $request->getBasePath();
        if ($basePath === '' && $projectPath !== '') {
            $basePath = '/'.trim($projectPath, '/');
        }

        return rtrim($basePath, '/');
    }
}
