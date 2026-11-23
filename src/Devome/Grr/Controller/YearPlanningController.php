<?php

namespace Devome\Grr\Controller;

use Devome\Grr\Service\GrrApplicationContext;
use Devome\Grr\Service\Annee;
use Settings;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Twig\Environment;

final class YearPlanningController
{
    public function __construct(
        private readonly Environment $twig,
        private readonly GrrApplicationContext $applicationContext,
        private readonly \Devome\Grr\Service\Annee $annee,
    ) {
    }

    public function index(Request $request): Response
    {
        $page = $request->query->get('p');
        if (!in_array($page, ['annee', 'annee_all'], true)) {
            throw new NotFoundHttpException();
        }

        $bootstrap = $this->applicationContext->boot();
        $context = $bootstrap['context'];
        global $gSessionName, $gSameSite, $room, $area, $id_site, $modulesActifs, $vocab, $d;
        global $resolution, $morningstarts, $eveningends, $eveningends_minutes;
        global $weekstarts, $twentyfourhour_format, $enable_periods, $periods_name;
        global $display_day, $nb_display_day, $description_breve, $description_complete, $locale;
        global $user_name;

        extract($context, EXTR_SKIP);
        $page = (string) $page;
        $grr_script_name = $page.'.php';

        include dirname(__DIR__, 4).'/include/resume_session.php';
        include dirname(__DIR__, 4).'/include/planning.php';

        $areaParameter = $request->query->getInt('area');
        if ($request->query->has('area')) {
            $area = $areaParameter;
            $site = mrbsGetAreaSite($area);
        } elseif ($request->query->has('site')) {
            $site = $request->query->getInt('site');
            $area = get_default_area($site);
        } else {
            $site = get_default_site();
            $area = get_default_area($site);
        }

        $fromMonth = $request->query->getInt('from_month');
        $fromYear = $request->query->getInt('from_year');
        $toMonth = $request->query->getInt('to_month');
        $toYear = $request->query->getInt('to_year');
        $day = 1;
        $now = time();

        if ($fromMonth < 1 || $fromMonth > 12 || $fromYear < 1 || !checkdate($fromMonth, 1, $fromYear)) {
            $date = $this->clampBookingDate($now);
            $day = (int) date('d', $date);
            $fromMonth = (int) date('m', $date);
            $fromYear = (int) date('Y', $date);
        } else {
            $date = $this->clampBookingDate(mktime(0, 0, 0, $fromMonth, $day, $fromYear));
            $day = (int) date('d', $date);
            $fromMonth = (int) date('m', $date);
            $fromYear = (int) date('Y', $date);
        }

        if ($toMonth < 1 || $toMonth > 12 || $toYear < 1 || !checkdate($toMonth, 1, $toYear)) {
            $toMonth = $fromMonth;
            $toYear = $fromYear;
        } else {
            $date = $this->clampBookingDate(mktime(0, 0, 0, $toMonth, 1, $toYear));
            $toMonth = (int) date('m', $date);
            $toYear = (int) date('Y', $date);
        }

        $monthStart = mktime(0, 0, 0, $fromMonth, 1, $fromYear);
        $monthEnd = mktime(23, 59, 59, $toMonth, 1, $toYear);
        $monthEnd = mktime(23, 59, 59, $toMonth, (int) date('t', $monthEnd), $toYear);

        get_planning_area_values($area);
        if ($enable_periods == 'y') {
            $resolution = 60;
            $morningstarts = 12;
            $eveningends = 12;
            $eveningends_minutes = count($periods_name) - 1;
        }

        $months = [];
        if ($page === 'annee') {
            $d['nomDomaine'] = grr_sql_query1('SELECT area_name FROM '.TABLE_PREFIX.'_area WHERE id='.(int) $area);
            if (($d['pview'] ?? 0) != 1) {
                $d['listeSiteHtml'] = make_site_select_html('annee', $site, $fromYear, $fromMonth, $day, $user_name);
                $d['listeDomaineHtml'] = make_area_select_all_html('annee', $site, $area, $fromYear, $fromMonth, $day, $user_name);
                $d['dateDebutHtml'] = genDateSelectorForm('from_', '', $fromMonth, $fromYear, '');
                $d['dateFintHtml'] = genDateSelectorForm('to_', '', $toMonth, $toYear, '');
            }

            $reservations = $this->annee->Reservations($monthEnd, $monthStart, $area);
            for ($monthIndex = $monthStart; $monthIndex < $monthEnd;) {
                $monthNumber = (int) date('m', $monthIndex);
                $yearNumber = (int) date('Y', $monthIndex);
                [$days, $resources] = $this->annee->MoisAffichage($monthIndex, $area, $display_day, $reservations);
                $months[] = [
                    'numMois' => $monthNumber,
                    'annee' => $yearNumber,
                    'nomMois' => ucfirst(utf8_strftime('%B', $monthIndex)),
                    'joursMois' => $days,
                    'ressourcesMois' => $resources,
                ];
                $monthIndex = mktime(0, 0, 0, $monthNumber + 1, 1, $yearNumber);
            }
            $template = 'annee.twig';
        } else {
            if (($d['pview'] ?? 0) != 1) {
                $d['dateDebutHtml'] = genDateSelectorForm('from_', '', $fromMonth, $fromYear, '');
                $d['dateFintHtml'] = genDateSelectorForm('to_', '', $toMonth, $toYear, '');
            }

            if ($site == -1) {
                $domainSql = 'SELECT a.id AS idArea, a.area_name AS nomArea FROM '.TABLE_PREFIX.'_area a ORDER BY a.order_display';
            } else {
                if ($site == 0) {
                    $site = get_default_site();
                } elseif ($site > 0) {
                    $siteName = grr_sql_query1('SELECT sitename FROM '.TABLE_PREFIX.'_site WHERE id='.(int) $site);
                    $d['nomSite'] = $siteName.' - ';
                }
                $domainSql = 'SELECT a.id AS idArea, a.area_name AS nomArea FROM '.TABLE_PREFIX.'_area a '
                    .'JOIN '.TABLE_PREFIX.'_j_site_area j ON j.id_area=a.id WHERE j.id_site='.(int) $site
                    .' ORDER BY a.order_display';
            }

            for ($monthIndex = $monthStart; $monthIndex < $monthEnd;) {
                $result = grr_sql_query($domainSql);
                if (!$result) {
                    throw new \RuntimeException('Unable to load domains for the year planning.');
                }

                $monthNumber = (int) date('m', $monthIndex);
                $yearNumber = (int) date('Y', $monthIndex);
                $domains = [];
                for ($index = 0; ($domain = grr_sql_row_keyed($result, $index)); ++$index) {
                    $reservations = $this->annee->Reservations($monthEnd, $monthStart, $domain['idArea']);
                    [$days, $resources] = $this->annee->MoisAffichage($monthIndex, $domain['idArea'], $display_day, $reservations);
                    $domains[] = [
                        'nomDomaine' => $domain['nomArea'],
                        'joursMois' => $days,
                        'ressourcesMois' => $resources,
                    ];
                }
                grr_sql_free($result);
                $months[] = [
                    'numMois' => $monthNumber,
                    'annee' => $yearNumber,
                    'nomMois' => ucfirst(utf8_strftime('%B', $monthIndex)),
                    'lesDomaines' => $domains,
                ];
                $monthIndex = mktime(0, 0, 0, $monthNumber + 1, 1, $yearNumber);
            }
            $template = 'annee_all.twig';
        }

        return new Response(
            $this->twig->render($template, [
                'd' => $d,
                'settings' => $AllSettings,
                'lesMois' => $months,
            ]),
            $bootstrap['status'],
            $bootstrap['headers'],
        );
    }

    private function clampBookingDate(int $timestamp): int
    {
        $start = (int) Settings::get('begin_bookings');
        $end = (int) Settings::get('end_bookings');

        if ($timestamp < $start) {
            return $start;
        }

        if ($timestamp > $end) {
            return $end;
        }

        return $timestamp;
    }
}
