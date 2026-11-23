<?php

namespace Devome\Grr\Controller;

use Devome\Grr\Service\GrrApplicationContext;
use SecuAccess;
use SecuChaine;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ParticipationController
{
    public function __construct(
        private readonly GrrApplicationContext $applicationContext,
    ) {
    }

    public function index(Request $request): Response
    {
        if ($request->query->get('p') !== 'participation') {
            throw new NotFoundHttpException();
        }

        $entryId = $request->query->getInt('id');
        if ($entryId < 1) {
            throw new BadRequestHttpException('The "id" parameter must be a positive integer.');
        }

        $bootstrap = $this->applicationContext->boot();
        global $enable_periods, $can_delete_or_create;

        $entry = mrbsGetEntryInfo($entryId);
        if (!$entry) {
            throw new NotFoundHttpException('The reservation does not exist.');
        }

        $username = getUserName();
        $userLevel = SecuAccess::UserLevel($username, $entryId);
        $areaId = (int) mrbsGetRoomArea($entry['room_id']);

        if ($userLevel < 1) {
            throw new AccessDeniedHttpException();
        }

        $participationLevel = grr_sql_query1(
            'SELECT '.TABLE_PREFIX.'_room.inscription_participant FROM '.TABLE_PREFIX.'_entry '
            .'JOIN '.TABLE_PREFIX.'_room ON '.TABLE_PREFIX.'_entry.room_id='.TABLE_PREFIX.'_room.id '
            .'WHERE '.TABLE_PREFIX.'_entry.id='.$entryId
        );
        if ($participationLevel < 1 || $userLevel < $participationLevel) {
            throw new AccessDeniedHttpException();
        }
        if (SecuAccess::UserArea($username, $areaId) == 0) {
            throw new AccessDeniedHttpException();
        }

        $roomId = (int) grr_sql_query1(
            'SELECT room_id FROM '.TABLE_PREFIX.'_entry WHERE id='.$entryId
        );
        get_planning_area_values($areaId);
        $dateAllowed = verif_participation_date($username, $entryId, $roomId, -1, time(), $enable_periods);
        if (!$dateAllowed || ($dateAllowed && $can_delete_or_create !== 'y')) {
            throw new AccessDeniedHttpException();
        }

        $login = SecuChaine::ProtectDataSql($username);
        $participantResult = grr_sql_query(
            'SELECT id_participation FROM '.TABLE_PREFIX.'_participants '
            .'WHERE idresa='.$entryId." AND beneficiaire='".$login."'"
        );
        if (!$participantResult) {
            throw new \RuntimeException('Unable to load reservation participants.');
        }

        if (grr_sql_count($participantResult) >= 1) {
            PaticipationAnnulation($entryId, $username);
        } else {
            grr_sql_free($participantResult);
            $participants = grr_sql_query(
                'SELECT beneficiaire FROM '.TABLE_PREFIX.'_participants WHERE idresa='.$entryId
            );
            if (!$participants) {
                throw new \RuntimeException('Unable to load reservation participants.');
            }

            $participantCount = grr_sql_count($participants);
            grr_sql_free($participants);
            $maxParticipants = grr_sql_query1(
                'SELECT nbparticipantmax FROM '.TABLE_PREFIX.'_entry WHERE id='.$entryId
            );
            if ($participantCount >= $maxParticipants) {
                throw new AccessDeniedHttpException('The reservation has reached its participant limit.');
            }

            PaticipationAjout($entryId, $username, $username, '');
        }
        grr_sql_free($participantResult);

        $day = (int) date('d', $entry['start_time']);
        $month = (int) date('m', $entry['start_time']);
        $year = (int) date('Y', $entry['start_time']);
        $roomBack = $request->query->getInt('room_back', (int) $entry['room_id']);
        $_SESSION['displ_msg'] = 'yes';
        $parameters = [
            'p' => verif_page(),
            'day' => $day,
            'month' => $month,
            'year' => $year,
            'area' => $areaId,
        ];
        if ($roomBack !== 0) {
            $parameters['room'] = $roomBack;
        }

        return new RedirectResponse(grr_page_url($parameters), Response::HTTP_FOUND, $bootstrap['headers']);
    }
}
