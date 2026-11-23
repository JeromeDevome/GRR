<?php

namespace Devome\Grr\Controller;

use Devome\Grr\Service\GrrApplicationContext;
use Settings;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Twig\Environment;

final class ModerationListController
{
    public function __construct(
        private readonly Environment $twig,
        private readonly GrrApplicationContext $applicationContext,
    ) {
    }

    public function index(Request $request): Response
    {
        if ($request->query->get('p') !== 'moderationsliste') {
            throw new NotFoundHttpException();
        }

        $bootstrap = $this->applicationContext->boot();
        $context = $bootstrap['context'];
        global $d, $vocab;
        $d = $context['d'] ?? [];
        $vocab = $context['vocab'] ?? [];

        $action = $request->request->getString('action_moderation', '');
        if ($action !== '' && !in_array($action, ['accepter', 'refuser'], true)) {
            throw new BadRequestHttpException('Invalid moderation action.');
        }

        if ($action !== '') {
            $formData = $request->request->all();
            $selectedIds = $formData['moderation_ids'] ?? [];
            if (!is_array($selectedIds) && !is_scalar($selectedIds)) {
                throw new BadRequestHttpException('The "moderation_ids" field must be a list of IDs.');
            }
            if (!is_array($selectedIds)) {
                $selectedIds = [$selectedIds];
            }

            $validatedIds = [];
            foreach ($selectedIds as $reservationId) {
                if (!is_scalar($reservationId)) {
                    continue;
                }
                $validatedId = filter_var(
                    $reservationId,
                    FILTER_VALIDATE_INT,
                    ['options' => ['min_range' => 1]],
                );
                if ($validatedId !== false) {
                    $validatedIds[] = (int) $validatedId;
                }
            }

            foreach ($validatedIds as $reservationId) {
                moderate_entry_do($reservationId, $action === 'accepter' ? 1 : 0, '', 'yes');
            }

            if ($validatedIds !== []) {
                $d['message'] = count($validatedIds).' modération(s) '.($action === 'accepter' ? 'acceptée(s)' : 'refusée(s)');
            }
        }

        $reservations = [];
        $pendingReservations = resaToModerate($d['gNomUser'] ?? '');
        if (!empty($pendingReservations)) {
            $d['acces'] = 1;
            foreach ($pendingReservations as $reservation) {
                $link = 'vuereservation?id='.$reservation['id'].'&mode=page';
                $row = [
                    'id' => $reservation['id'],
                    'ressource' => $reservation['room'],
                    'debut' => time_date_string($reservation['start_time'], $context['dformat'] ?? ''),
                    'createur' => $reservation['create_by'],
                    'beneficiaire' => $reservation['beneficiaire'],
                    'lien' => $link,
                ];
                if (Settings::get('module_multisite') == 1) {
                    $row['site'] = $reservation['site'];
                } else {
                    $row['domaine'] = $reservation['area'];
                }
                $reservations[] = $row;
            }
            $d['nbResaAModerer'] = count($reservations);
        }

        return new Response(
            $this->twig->render('moderationsliste.twig', [
                'd' => $d,
                'settings' => $context['AllSettings'] ?? [],
                'resas' => $reservations,
            ]),
            $bootstrap['status'],
            $bootstrap['headers'],
        );
    }
}
