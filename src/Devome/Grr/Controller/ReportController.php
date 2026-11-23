<?php

namespace Devome\Grr\Controller;

use Devome\Grr\Service\GrrApplicationContext;
use Devome\Grr\Service\Report\AdditionalField;
use Devome\Grr\Service\Report\ReportCriteria;
use Devome\Grr\Service\Report\ReportGenerator;
use Devome\Grr\Service\Report\ReportViewer;
use SecuAccess;
use Settings;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Twig\Environment;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Page « Recherche & Stats » (report).
 */
final class ReportController
{
    private const DEFAULT_DATE_FORMAT = '%A %d %B %Y';

    private const VOCABULARY = [
        'search_report_stats', 'deux_points', 'report_start', 'report_end',
        'valide_toutes_les_conditions_suivantes', 'valide_au_moins_une_des_conditions_suivantes',
        'choose', 'site', 'match_area', 'room', 'type', 'namebooker', 'match_descr', 'match_login',
        'contient', 'ne_contient_pas', 'include', 'report_only', 'summary_only', 'report_and_summary',
        'summarize_by', 'summarize_by_precisions', 'sum_by_creator', 'sum_by_descrip',
        'moderation', 'en_attente_moderation', 'moderation_acceptee', 'moderation_refusee', 'submit',
        'nothing_found', 'entry_found', 'entries_found', 'date', 'datefin', 'time', 'duration',
        'lastupdate', 'summary_header_per', 'summary_header', 'total',
    ];

    public function __construct(
        private readonly Environment $twig,
        private readonly GrrApplicationContext $applicationContext,
        private readonly TranslatorInterface $translator,
        private readonly ReportGenerator $generator,
    ) {
    }

    public function index(Request $request): Response
    {
        if ($request->query->get('p') !== 'report') {
            throw new NotFoundHttpException();
        }
        // Lus par le code legacy (layout, session, describe_span…)
        global $d, $vocab, $dformat;
        $_GET = $request->query->all();

        $bootstrap = $this->applicationContext->boot();
        $context = $bootstrap['context'];
        $d = $context['d'] ?? [];
        $vocab = $context['vocab'] ?? [];
        $dformat = is_string($context['dformat'] ?? null) ? $context['dformat'] : self::DEFAULT_DATE_FORMAT;

        if (!grr_resumeSession()
            && (Settings::get('authentification_obli') == 1 || isset($_SESSION['login']))) {
            return new RedirectResponse(
                './deconnexion?auto=1&url='.rawurlencode((string) ($context['url'] ?? '')),
                Response::HTTP_FOUND,
                $bootstrap['headers'],
            );
        }
        if (!SecuAccess::UserSearch(getUserName())) {
            return new RedirectResponse(
                cheminDetermination(1).'erreur.php?code=403&grr_script_name=report.php&infosdebug=',
                Response::HTTP_FOUND,
                $bootstrap['headers'],
            );
        }

        $viewer = ReportViewer::current();
        $additionalFields = AdditionalField::visibleTo($viewer);
        $criteria = ReportCriteria::fromRequest(
            $request,
            (int) $context['day'],
            (int) $context['month'],
            (int) $context['year'],
            (int) Settings::get('default_report_days'),
        );

        foreach (self::VOCABULARY as $key) {
        }

        return new Response(
            $this->twig->render('report.twig', [
                'd' => $d,
                'settings' => $context['AllSettings'] ?? [],
                'criteria' => $criteria,
                'conditions' => $criteria->formRows(),
                'additionalFields' => $additionalFields,
                'datePickerStart' => genDateSelectorForm('From_', $criteria->fromDay, $criteria->fromMonth, $criteria->fromYear, ''),
                'datePickerEnd' => genDateSelectorForm('To_', $criteria->toDay, $criteria->toMonth, $criteria->toYear, ''),
                'sumByLabel' => $this->sumByLabel($criteria->sumBy, $additionalFields),
                'report' => $criteria->submitted
                    ? $this->generator->generate($criteria, $viewer, $additionalFields, $dformat)
                    : null,
            ]),
            $bootstrap['status'],
            $bootstrap['headers'],
        );
    }

    /** @param list<AdditionalField> $additionalFields */
    private function sumByLabel(string $sumBy, array $additionalFields): string
    {
        foreach ($additionalFields as $field) {
            if ($field->key() === $sumBy) {
                return $field->label;
            }
        }

        return match ($sumBy) {
            ReportCriteria::SUM_BY_DESCRIPTION => $this->translator->trans('sum_by_descrip'),
            ReportCriteria::SUM_BY_TYPE => $this->translator->trans('type'),
            default => $this->translator->trans('sum_by_creator'),
        };
    }
}
