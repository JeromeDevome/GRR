<?php

namespace Devome\Grr\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Twig\Environment;
use Symfony\Contracts\Translation\TranslatorInterface;
use Devome\Grr\Service\SemainePageBuilder;

final class SemaineController
{
    public function __construct(
        private readonly Environment $twig,
        private readonly TranslatorInterface $translator,
        private readonly SemainePageBuilder $pageBuilder,
    ) {
    }

    public function index(Request $request): Response
    {
        if ($request->query->get('p') !== 'semaine') {
            throw new NotFoundHttpException();
        }

        global $gSessionName, $gSameSite, $room, $area, $id_site, $modulesActifs, $vocab, $d;
        global $resolution, $morningstarts, $eveningends, $eveningends_minutes;
        global $weekstarts, $twentyfourhour_format, $enable_periods, $periods_name;
        global $display_day, $nb_display_day, $description_breve, $description_complete, $locale;

        $projectDir = dirname(__DIR__, 4);
        $workingDirectory = getcwd();
        if ($workingDirectory === false) {
            throw new \RuntimeException('Unable to determine the current working directory.');
        }

        if (!chdir($projectDir)) {
            throw new \RuntimeException(sprintf('Unable to change directory to "%s".', $projectDir));
        }

        $bufferLevel = ob_get_level();
        ob_start();
        $GLOBALS['grr_symfony_translator'] = $this->translator;
        $responseHeaders = [];
        $statusCode = Response::HTTP_OK;

        try {
            require $projectDir.'/include/grr_application_bootstrap.inc.php';
            $bootstrapContext = get_defined_vars();
            $legacyOutput = ob_get_clean();

            if ($legacyOutput === false) {
                throw new \RuntimeException('Unable to capture the GRR week page context.');
            }

            $context = $this->pageBuilder->build($bootstrapContext);

            foreach (headers_list() as $headerLine) {
                $separator = strpos($headerLine, ':');
                if ($separator === false) {
                    continue;
                }

                $name = trim(substr($headerLine, 0, $separator));
                $value = trim(substr($headerLine, $separator + 1));
                $responseHeaders[$name][] = $value;
            }

            $phpStatusCode = http_response_code();
            if (is_int($phpStatusCode) && $phpStatusCode >= 100) {
                $statusCode = $phpStatusCode;
            }

            header_remove();
            $content = $legacyOutput.$this->twig->render('semaine.twig', $context);
        } finally {
            unset($GLOBALS['grr_symfony_translator']);

            while (ob_get_level() > $bufferLevel) {
                ob_end_clean();
            }

            if (!chdir($workingDirectory)) {
                throw new \RuntimeException(sprintf('Unable to restore the working directory to "%s".', $workingDirectory));
            }
        }

        return new Response($content, $statusCode, $responseHeaders);
    }
}
