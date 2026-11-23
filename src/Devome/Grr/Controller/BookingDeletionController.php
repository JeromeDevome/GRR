<?php

namespace Devome\Grr\Controller;

use Devome\Grr\Service\GrrApplicationContext;
use SecuAccess;
use SecuChaine;
use Settings;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class BookingDeletionController
{
    public function __construct(
        private readonly GrrApplicationContext $applicationContext,
    ) {
    }

    public function index(Request $request): Response
    {
        if ($request->query->get('p') !== 'supreservation') {
            throw new NotFoundHttpException();
        }

        if (!$request->query->has('id')) {
            return new RedirectResponse('login');
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

        $day = (int) date('d', $entry['start_time']);
        $month = (int) date('m', $entry['start_time']);
        $year = (int) date('Y', $entry['start_time']);
        $areaId = (int) mrbsGetRoomArea($entry['room_id']);
        $username = getUserName();
        if (
            SecuAccess::UserLevel($username, -1) < 1
            || !SecuAccess::IsAllowedToModifyResa($username, $entryId)
            || SecuAccess::UserArea($username, $areaId) == 0
        ) {
            throw new AccessDeniedHttpException();
        }

        if (Settings::get('automatic_mail') == 1) {
            $_SESSION['session_message_error'] = send_mail($entryId, 3, $bootstrap['context']['dformat'] ?? '');
        }
        $roomId = (int) grr_sql_query1(
            'SELECT room_id FROM '.TABLE_PREFIX.'_entry WHERE id='.$entryId
        );
        get_planning_area_values($areaId);
        $whoCanBook = grr_sql_query1(
            'SELECT who_can_book FROM '.TABLE_PREFIX.'_room WHERE id='.$roomId
        );
        $userCanBook = $whoCanBook || SecuAccess::UserBookingResourceRestrict($username, $entry['room_id']);
        $bookingDateAllowed = verif_booking_date($username, $entryId, $roomId, -1, time(), $enable_periods);

        if (
            !$bookingDateAllowed
            || ($bookingDateAllowed && $can_delete_or_create !== 'y' && $userCanBook)
        ) {
            throw new AccessDeniedHttpException();
        }

        $series = $request->query->getInt('series');
        $deleted = mrbsDelEntry($username, $entryId, $series, 1);
        if (!$deleted) {
            throw new \RuntimeException('Unable to delete the reservation.');
        }

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
