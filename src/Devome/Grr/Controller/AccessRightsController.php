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

final class AccessRightsController
{
    public function __construct(
        private readonly Environment $twig,
        private readonly GrrApplicationContext $applicationContext,
    ) {
    }

    public function domain(Request $request): Response
    {
        if ($request->query->get('p') !== 'vuedroitsdomaine') {
            throw new NotFoundHttpException();
        }

        $areaId = $request->query->getInt('area_id');
        if ($areaId < 1) {
            throw new BadRequestHttpException('The "area_id" parameter must be a positive integer.');
        }

        $bootstrap = $this->applicationContext->boot();
        $context = $bootstrap['context'];
        $this->setTranslations($context);
        if (SecuAccess::UserLevel(getUserName(), $areaId, 'area') < 4) {
            throw new AccessDeniedHttpException();
        }

        $result = grr_sql_query('SELECT * FROM '.TABLE_PREFIX.'_area WHERE id='.$areaId);
        if (!$result) {
            throw new \RuntimeException('Unable to load the requested domain.');
        }

        $domain = grr_sql_row_keyed($result, 0);
        grr_sql_free($result);
        if (!$domain) {
            throw new NotFoundHttpException('The requested domain does not exist.');
        }

        $domainAdmins = $this->loadUsers(
            'SELECT u.login, u.nom, u.prenom, u.etat FROM '.TABLE_PREFIX.'_utilisateurs u '
            .'LEFT JOIN '.TABLE_PREFIX.'_j_useradmin_area j ON u.login=j.login '
            .'WHERE j.id_area='.$areaId.' ORDER BY u.nom, u.prenom'
        );
        $domainUsers = [];
        if ($domain['access'] === 'r') {
            $domainUsers = $this->loadUsers(
                'SELECT u.login, u.nom, u.prenom, u.etat FROM '.TABLE_PREFIX.'_utilisateurs u '
                .'LEFT JOIN '.TABLE_PREFIX.'_j_user_area j ON u.login=j.login '
                .'WHERE j.id_area='.$areaId.' ORDER BY u.nom, u.prenom'
            );
        }

        return $this->render($bootstrap, 'vuedroitsdomaine.twig', [
            'domaine' => $domain,
            'admindomaine' => $domainAdmins,
            'accesdomaine' => $domainUsers,
        ]);
    }

    public function resource(Request $request): Response
    {
        if ($request->query->get('p') !== 'vuedroitsressource') {
            throw new NotFoundHttpException();
        }

        $roomId = $request->query->getInt('id_room');
        if ($roomId < 1) {
            throw new BadRequestHttpException('The "id_room" parameter must be a positive integer.');
        }

        $bootstrap = $this->applicationContext->boot();
        $context = $bootstrap['context'];
        $this->setTranslations($context);
        if (SecuAccess::UserLevel(getUserName(), $roomId) < 4 || !SecuAccess::UserResource(getUserName(), $roomId)) {
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
        $d = $context['d'] ?? [];
        $d['area_name'] = grr_sql_query1('SELECT area_name FROM '.TABLE_PREFIX.'_area WHERE id='.(int) $areaId);
        $d['area_access'] = grr_sql_query1('SELECT access FROM '.TABLE_PREFIX.'_area WHERE id='.(int) $areaId);

        $domainAdmins = $this->loadUsers(
            'SELECT u.login, u.nom, u.prenom, u.etat FROM '.TABLE_PREFIX.'_utilisateurs u '
            .'LEFT JOIN '.TABLE_PREFIX.'_j_useradmin_area j ON u.login=j.login '
            .'WHERE j.id_area='.(int) $areaId.' ORDER BY u.nom, u.prenom'
        );
        $resourceAdmins = $this->loadUsers(
            'SELECT u.login, u.nom, u.prenom, u.etat FROM '.TABLE_PREFIX.'_utilisateurs u '
            .'LEFT JOIN '.TABLE_PREFIX.'_j_user_room j ON u.login=j.login '
            .'WHERE j.id_room='.$roomId.' ORDER BY u.nom, u.prenom'
        );
        $mailUsers = $this->loadUsers(
            'SELECT u.login, u.nom, u.prenom, u.etat FROM '.TABLE_PREFIX.'_utilisateurs u '
            .'LEFT JOIN '.TABLE_PREFIX.'_j_mailuser_room j ON u.login=j.login '
            .'WHERE j.id_room='.$roomId.' ORDER BY u.nom, u.prenom'
        );

        $domainUsers = [];
        if ($d['area_access'] === 'r') {
            $domainUsers = $this->loadUsers(
                'SELECT u.login, u.nom, u.prenom, u.etat FROM '.TABLE_PREFIX.'_utilisateurs u '
                .'LEFT JOIN '.TABLE_PREFIX.'_j_user_area j ON u.login=j.login '
                .'WHERE j.id_area='.(int) $areaId.' ORDER BY u.nom, u.prenom'
            );
        }

        $resourceUsers = [];
        if ((int) $resource['who_can_book'] === 0) {
            $resourceUsers = $this->loadUsers(
                'SELECT u.login, u.nom, u.prenom, u.etat FROM '.TABLE_PREFIX.'_utilisateurs u '
                .'LEFT JOIN '.TABLE_PREFIX.'_j_userbook_room j ON u.login=j.login '
                .'WHERE j.id_room='.$roomId.' ORDER BY u.nom, u.prenom'
            );
        }

        return $this->render($bootstrap, 'vuedroitsressource.twig', [
            'd' => $d,
            'ressource' => $resource,
            'admindomaine' => $domainAdmins,
            'adminressource' => $resourceAdmins,
            'mailressource' => $mailUsers,
            'accesdomaine' => $domainUsers,
            'accesressource' => $resourceUsers,
        ]);
    }

    private function loadUsers(string $sql): array
    {
        $result = grr_sql_query($sql);
        if (!$result) {
            throw new \RuntimeException('Unable to load the access list.');
        }

        $users = [];
        for ($index = 0; ($row = grr_sql_row($result, $index)); ++$index) {
            $users[] = [
                'login' => $row[0],
                'nom' => $row[1],
                'prenom' => $row[2],
                'etat' => $row[3],
            ];
        }
        grr_sql_free($result);

        return $users;
    }

    private function setTranslations(array $context): void
    {
        global $d, $vocab;

        $d = $context['d'] ?? [];
        $vocab = $context['vocab'] ?? [];
    }

    private function render(array $bootstrap, string $template, array $variables): Response
    {
        $context = $bootstrap['context'];
        $variables += [
            'd' => $context['d'] ?? [],
            'settings' => $context['AllSettings'] ?? [],
        ];

        return new Response(
            $this->twig->render($template, $variables),
            $bootstrap['status'],
            $bootstrap['headers'],
        );
    }
}
