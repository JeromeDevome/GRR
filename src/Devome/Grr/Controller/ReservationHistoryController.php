<?php

namespace Devome\Grr\Controller;

use Devome\Grr\Service\GrrApplicationContext;
use SecuAccess;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Twig\Environment;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ReservationHistoryController
{
    public function __construct(
        private readonly Environment $twig,
        private readonly GrrApplicationContext $applicationContext,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function index(Request $request): Response
    {
        if ($request->query->get('p') !== 'resahistorique') {
            throw new NotFoundHttpException();
        }

        $reservationId = $request->query->getInt('idresa');
        if ($reservationId < 1) {
            throw new BadRequestHttpException('The "idresa" parameter must be a positive integer.');
        }

        $bootstrap = $this->applicationContext->boot();
        $context = $bootstrap['context'];
        global $d, $vocab;
        $d = $context['d'] ?? [];
        $vocab = $context['vocab'] ?? [];

        $sql = 'SELECT '.TABLE_PREFIX.'_entry.name, '.TABLE_PREFIX.'_entry.id, '
            .TABLE_PREFIX.'_entry.description, '.TABLE_PREFIX.'_entry.beneficiaire, '
            .TABLE_PREFIX.'_room.room_name, '.TABLE_PREFIX.'_area.area_name, '
            .TABLE_PREFIX.'_entry.type, '.TABLE_PREFIX.'_entry.room_id, '
            .TABLE_PREFIX.'_entry.repeat_id, '
            .grr_sql_syntax_timestamp_to_unix(TABLE_PREFIX.'_entry.timestamp').', '
            .'('.TABLE_PREFIX.'_entry.end_time - '.TABLE_PREFIX.'_entry.start_time), '
            .TABLE_PREFIX.'_entry.start_time, '.TABLE_PREFIX.'_entry.end_time, '
            .TABLE_PREFIX.'_entry.statut_entry, '.TABLE_PREFIX.'_room.delais_option_reservation, '
            .TABLE_PREFIX.'_entry.option_reservation, '.TABLE_PREFIX.'_entry.moderate, '
            .TABLE_PREFIX.'_entry.beneficiaire_ext, '.TABLE_PREFIX.'_entry.create_by, '
            .TABLE_PREFIX.'_entry.jours, '.TABLE_PREFIX.'_room.active_ressource_empruntee, '
            .TABLE_PREFIX.'_entry.clef, '.TABLE_PREFIX.'_entry.courrier, '
            .TABLE_PREFIX.'_room.active_cle, '.TABLE_PREFIX.'_entry.nbparticipantmax '
            .'FROM '.TABLE_PREFIX.'_entry, '.TABLE_PREFIX.'_room, '.TABLE_PREFIX.'_area '
            .'WHERE '.TABLE_PREFIX.'_entry.room_id = '.TABLE_PREFIX.'_room.id '
            .'AND '.TABLE_PREFIX.'_room.area_id = '.TABLE_PREFIX.'_area.id '
            .'AND '.TABLE_PREFIX.'_entry.id='.$reservationId;
        $result = grr_sql_query($sql);
        if (!$result) {
            throw new \RuntimeException('Unable to load the reservation history.');
        }

        $reservation = grr_sql_row_keyed($result, 0);
        grr_sql_free($result);
        if (!$reservation) {
            throw new NotFoundHttpException('The requested reservation does not exist.');
        }

        if (SecuAccess::UserLevel(getUserName(), $reservation['room_id']) <= 2) {
            throw new AccessDeniedHttpException();
        }

        $logs = [];
        $reservationLogs = grr_sql_query(
            'SELECT idlogresa, date, identifiant, action, infoscomp FROM '.TABLE_PREFIX.'_log_resa '
            .'WHERE idresa='.$reservationId.' ORDER BY date ASC'
        );
        if (!$reservationLogs) {
            throw new \RuntimeException('Unable to load reservation audit events.');
        }
        for ($index = 0; ($row = grr_sql_row($reservationLogs, $index)); ++$index) {
            $logs[] = [
                'date' => $row[1],
                'identifiant' => $row[2],
                'action' => $row[3],
                'infos' => $row[4],
            ];
        }
        grr_sql_free($reservationLogs);

        $mailLogs = grr_sql_query(
            'SELECT date, sujet, type, erreur FROM '.TABLE_PREFIX.'_log_mail '
            .'WHERE idresa='.$reservationId.' ORDER BY date ASC'
        );
        if (!$mailLogs) {
            throw new \RuntimeException('Unable to load reservation mail history.');
        }
        for ($index = 0; ($row = grr_sql_row($mailLogs, $index)); ++$index) {
            $type = match ((int) $row[2]) {
                1 => $this->translator->trans('mail_desc_dest_adm'),
                2 => $this->translator->trans('mail_desc_dest_beneficiaire'),
                3 => $this->translator->trans('mail_desc_dest_gestionnaire'),
                default => '',
            };
            $logs[] = [
                'date' => date('Y-m-d H:i:s', $row[0]),
                'identifiant' => $type,
                'action' => 8,
                'infos' => $row[1],
                'erreur' => $row[3],
            ];
        }
        grr_sql_free($mailLogs);

        usort($logs, static function (array $first, array $second): int {
            $firstDate = is_numeric($first['date']) ? (int) $first['date'] : strtotime($first['date']);
            $secondDate = is_numeric($second['date']) ? (int) $second['date'] : strtotime($second['date']);

            return $firstDate <=> $secondDate;
        });

        return new Response(
            $this->twig->render('resahistorique.twig', [
                'd' => $d,
                'settings' => $context['AllSettings'] ?? [],
                'resa' => $reservation,
                'logsresa' => $logs,
            ]),
            $bootstrap['status'],
            $bootstrap['headers'],
        );
    }
}
