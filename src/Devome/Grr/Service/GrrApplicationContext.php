<?php

namespace Devome\Grr\Service;

use Devome\Grr\Http\PageInterruption;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

final class GrrApplicationContext
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly Environment $twig,
        #[Autowire('%kernel.debug%')]
        private readonly bool $debug = false,
    ) {
    }

    /**
     * @return array{context: array<string, mixed>, headers: array<string, list<string>>, status: int}
     */
    public function boot(): array
    {
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
        // Repères lus par le code legacy (include/) pendant toute la requête :
        //  - grr_symfony : la page est servie par Symfony (grr_redirection() / grr_fin_page() lèvent une PageInterruption) ;
        //  - grr_ob_niveau_base : niveau de tampon de sortie à partir duquel la sortie appartient à la page ;
        //  - grr_debug : en debug, une requête SQL en échec lève une exception ;
        //  - grr_symfony_translator : traducteur utilisé par get_vocab().
        $GLOBALS['grr_symfony'] = true;
        $GLOBALS['grr_ob_niveau_base'] = $bufferLevel;
        $GLOBALS['grr_debug'] = $this->debug;
        $GLOBALS['grr_bootstrap_headers'] = [];
        $GLOBALS['grr_symfony_translator'] = $this->translator;
        $GLOBALS['grr_symfony_twig'] = $this->twig;
        $headers = [];
        $status = 200;

        try {
            $interruption = require $projectDir.'/include/grr_application_bootstrap.inc.php';
            if ($interruption instanceof PageInterruption) {
                // Redirection ou fin de page décidée pendant le démarrage legacy (changement de langue, mise à jour…)
                throw $interruption;
            }
            unset($interruption);
            $context = get_defined_vars();
            $output = ob_get_clean();
            if ($output === false) {
                throw new \RuntimeException('Unable to capture the GRR application bootstrap output.');
            }

            foreach (headers_list() as $headerLine) {
                $separator = strpos($headerLine, ':');
                if ($separator === false) {
                    continue;
                }

                $name = trim(substr($headerLine, 0, $separator));
                $headers[$name][] = trim(substr($headerLine, $separator + 1));
            }

            $phpStatus = http_response_code();
            if (is_int($phpStatus) && $phpStatus >= 100) {
                $status = $phpStatus;
            }

            header_remove();
            // Repris par PageInterruptionListener si la page se termine par une interruption
            $GLOBALS['grr_bootstrap_headers'] = $headers;

            unset(
                $context['this'],
                $context['projectDir'],
                $context['workingDirectory'],
                $context['bufferLevel'],
                $context['headers'],
                $context['status'],
                $context['output'],
                $context['phpStatus'],
            );

            return [
                'context' => $context,
                'headers' => $headers,
                'status' => $status,
            ];
        } finally {
            unset($GLOBALS['grr_symfony_twig']);

            while (ob_get_level() > $bufferLevel) {
                ob_end_clean();
            }

            if (!chdir($workingDirectory)) {
                throw new \RuntimeException(sprintf('Unable to restore the working directory to "%s".', $workingDirectory));
            }
        }
    }
}
