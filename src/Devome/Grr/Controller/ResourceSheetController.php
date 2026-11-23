<?php

namespace Devome\Grr\Controller;

use Devome\Grr\Service\GrrApplicationContext;
use SecuAccess;
use Settings;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Twig\Environment;

final class ResourceSheetController
{
    public function __construct(
        private readonly Environment $twig,
        private readonly GrrApplicationContext $applicationContext,
    ) {
    }

    public function index(Request $request): Response
    {
        if ($request->query->get('p') !== 'ressourcefiche') {
            throw new NotFoundHttpException();
        }

        if (!$request->query->has('id_room')) {
            throw new BadRequestHttpException('The "id_room" parameter is required.');
        }

        $roomId = $request->query->getInt('id_room');
        if ($roomId < 1) {
            throw new BadRequestHttpException('The "id_room" parameter must be a positive integer.');
        }

        global $d, $vocab;

        $bootstrap = $this->applicationContext->boot();
        $context = $bootstrap['context'];
        $d = $context['d'] ?? [];
        $vocab = $context['vocab'] ?? [];

        if (
            (Settings::get('authentification_obli') == 1 && SecuAccess::UserLevel(getUserName(), -1) < 1)
            || !SecuAccess::UserResource(getUserName(), $roomId)
        ) {
            throw new AccessDeniedHttpException();
        }

        $result = grr_sql_query('SELECT * FROM '.TABLE_PREFIX.'_room WHERE id='.$roomId);
        if (!$result) {
            throw new \RuntimeException('Unable to load the requested resource.');
        }

        $resource = grr_sql_row_keyed($result, 0);
        grr_sql_free($result);
        if (!$resource) {
            throw new NotFoundHttpException('The requested resource does not exist.');
        }

        $areaId = mrbsGetRoomArea($roomId);
        $d['area_name'] = grr_sql_query1('SELECT area_name FROM '.TABLE_PREFIX.'_area WHERE id='.(int) $areaId);
        $d['area_access'] = grr_sql_query1('SELECT access FROM '.TABLE_PREFIX.'_area WHERE id='.(int) $areaId);

        if (
            SecuAccess::UserLevel(getUserName(), -1) >= Settings::get('visu_fiche_description')
            && $resource['description'] !== ''
        ) {
            $d['visuDescription'] = 1;
        }

        if (
            SecuAccess::UserLevel(getUserName(), -1) >= Settings::get('acces_fiche_reservation')
            && $resource['comment_room'] !== ''
        ) {
            $d['visuDescriptionComplete'] = 1;
        }

        $d['max_booking_per_area'] = grr_sql_query1(
            'SELECT max_booking FROM '.TABLE_PREFIX.'_area WHERE id='.(int) $areaId
        );

        $imageDirectory = $context['gcDossierImg'] ?? null;
        if (is_string($imageDirectory) && $imageDirectory !== '') {
            $imageKey = hash('ripemd128', $resource['id'].Settings::get('tokenprivee'));
            $imagePath = './personnalisation/'.$imageDirectory.'/ressources/'.$resource['id'].'-'.$imageKey.'/'.$resource['picture_room'];
            if (is_file(dirname(__DIR__, 4).'/'.$imagePath)) {
                $d['lienImage'] = $imagePath;
            }
        }

        return new Response(
            $this->twig->render('ressourcefiche.twig', [
                'd' => $d,
                'settings' => $context['AllSettings'] ?? [],
                'ressource' => $resource,
            ]),
            $bootstrap['status'],
            $bootstrap['headers'],
        );
    }
}
