<?php

namespace Devome\Grr\Controller;

use Devome\Grr\Service\GrrApplicationContext;
use SecuAccess;
use Settings;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class ReservationDragDropController
{
    public function __construct(
        private readonly GrrApplicationContext $applicationContext,
        private readonly ReservationEditSubmitController $editSubmitController,
    ) {
    }

    public function move(Request $request): Response
    {
        $bootstrap = $this->applicationContext->boot();
        $context = $bootstrap['context'];
        global $gSessionName, $gSameSite, $room, $area, $id_site, $modulesActifs, $vocab, $d;
        global $resolution, $morningstarts, $eveningends, $eveningends_minutes;
        global $weekstarts, $twentyfourhour_format, $enable_periods, $periods_name;
        global $display_day, $nb_display_day, $description_breve, $description_complete, $locale, $user_name;

        extract($context, EXTR_SKIP);
        $user_name = $context['user_name'] ?? getUserName();
        $page = 'dragdrop';
        $grr_script_name = $page.'.php';

        $_GET = $request->query->all();
        $_POST = $request->request->all();
        $_GET['url'] = $request->query->get('url', '');
        $_REQUEST = array_merge($_GET, $_POST, $_COOKIE);
        require_once dirname(__DIR__, 4).'/include/planning.php';

        $id = $this->integerParameter($request, 'id', 0);
        $newHour = $this->integerParameter($request, 'hour', -1);
        $newMinute = $this->integerParameter($request, 'minute', -1);
        $day = $this->integerParameter($request, 'day', 0);
        $month = $this->integerParameter($request, 'month', 0);
        $year = $this->integerParameter($request, 'year', 0);
        $resource = $this->integerParameter($request, 'ressource', 0);

        if (!$id || !$day || !$month || !$year) {
            return new Response('Paramètres manquants', Response::HTTP_BAD_REQUEST, $bootstrap['headers']);
        }

        if (
            !grr_resumeSession()
            && (
                Settings::get('authentification_obli') == 1
                || isset($_SESSION['login'])
            )
        ) {
            $url = $GLOBALS['url'] ?? '';

            return new RedirectResponse(
                './deconnexion?auto=1&url='.rawurlencode((string) $url),
                Response::HTTP_FOUND,
                $bootstrap['headers'],
            );
        }

        $result = grr_sql_query(
            'SELECT start_time, end_time, room_id FROM '.TABLE_PREFIX.'_entry WHERE id='.$id
        );
        if (!$result || !($entry = grr_sql_row_keyed($result, 0))) {
            if ($result) {
                grr_sql_free($result);
            }

            return new Response('Réservation non trouvée', Response::HTTP_NOT_FOUND, $bootstrap['headers']);
        }
        grr_sql_free($result);

        $start = (int) $entry['start_time'];
        $end = (int) $entry['end_time'];
        $roomFromEntry = (int) $entry['room_id'];
        $currentUser = getUserName();
        if (!SecuAccess::IsAllowedToModifyResa($currentUser, $id)) {
            return new Response('Permission refusée', Response::HTTP_FORBIDDEN, $bootstrap['headers']);
        }

        if (!checkdate($month, $day, $year) || est_hors_reservation(mktime(0, 0, 0, $month, $day, $year))) {
            return new Response('Hors période de réservation', Response::HTTP_BAD_REQUEST, $bootstrap['headers']);
        }

        $room = $resource !== 0 ? $resource : $roomFromEntry;
        $hour = $newHour !== -1 && $newMinute !== -1 ? $newHour : (int) date('G', $start);
        $minute = $newHour !== -1 && $newMinute !== -1 ? $newMinute : (int) date('i', $start);
        $newStart = mktime($hour, $minute, (int) date('s', $start), $month, $day, $year);
        $newEnd = $newStart + ($end - $start);

        $parameters = array_merge($request->query->all(), $request->request->all(), [
            'id' => $id,
            'room' => $room,
            'rooms' => [$room],
            'start_day' => $day,
            'start_month' => $month,
            'start_year' => $year,
            'end_day' => (int) date('j', $newEnd),
            'end_month' => (int) date('n', $newEnd),
            'end_year' => (int) date('Y', $newEnd),
            'start_hour' => (int) date('G', $newStart),
            'start_minute' => (int) date('i', $newStart),
            'period' => (int) date('i', $newStart),
            'end_hour' => (int) date('G', $newEnd),
            'end_minute' => (int) date('i', $newEnd),
            'end_' => date('H:i', $newEnd),
            'type_affichage_reser' => 1,
            'envoyer_notif' => 1,
            'dragdrop' => '1',
        ]);
        if (($context['enable_periods'] ?? null) === 'y') {
            $parameters['end_period'] = (int) date('i', $newEnd) - 1;
        }

        $forwardedRequest = $request->duplicate($parameters, $parameters);
        $localContext = get_defined_vars();
        foreach (array_keys($context) as $name) {
            if (array_key_exists($name, $localContext)) {
                $bootstrap['context'][$name] = $localContext[$name];
            }
        }

        return $this->editSubmitController->submitWithBootstrap($forwardedRequest, $bootstrap);
    }

    private function integerParameter(Request $request, string $name, int $default): int
    {
        $value = $request->get($name, $default);
        if (!is_scalar($value) || preg_match('/^-?[0-9]+$/D', (string) $value) !== 1) {
            return $default;
        }

        return (int) $value;
    }
}
