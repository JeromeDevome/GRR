<?php

namespace Devome\Grr\Controller;

use Devome\Grr\Service\AccountReservations;
use Devome\Grr\Service\GrrApplicationContext;
use SecuAccess;
use SecuChaine;
use Settings;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Twig\Environment;
use Symfony\Contracts\Translation\TranslatorInterface;

final class AccountController
{
    public function __construct(
        private readonly Environment $twig,
        private readonly GrrApplicationContext $applicationContext,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function index(Request $request): Response
    {
        if ($request->query->get('action') === 'lists' || $request->query->get('legacy_lists') === '1') {
            return $this->accountLists($request);
        }

        $page = $this->queryString($request, 'pc', 'moncompte');
        if (!in_array($page, ['moncompte', 'changemdp', 'mesreservations', 'mesconnexions'], true)) {
            throw new NotFoundHttpException();
        }

        $bootstrap = $this->boot($request, $page);
        $redirect = $this->bootstrapRedirect($bootstrap);
        if ($redirect !== null) {
            return $redirect;
        }

        if (getUserName() === '') {
            return new RedirectResponse('login');
        }

        return match ($page) {
            'moncompte' => $this->profile($request, $bootstrap),
            'changemdp' => $this->changePassword($request, $bootstrap),
            'mesreservations' => $this->reservations($request, $bootstrap),
            'mesconnexions' => $this->connections($bootstrap),
        };
    }

    private function profile(Request $request, array $bootstrap): Response
    {
        global $d, $vocab;

        $context = $bootstrap['context'];
        $d = $context['d'] ?? [];
        $vocab = $context['vocab'] ?? [];
        $login = getUserName();
        $message = '';

        Definition_ressource_domaine_site();

        if ($request->request->get('valid') === 'yes') {
            $profile = $this->loadProfile($login);
            $message = $this->updateProfile($request, $profile);
            $this->updatePreferences($request, $profile);
        } elseif ($request->request->get('valid') === 'reset') {
            $this->updatePreferences($request, $this->loadProfile($login));
        }

        $profile = $this->loadProfile($login);
        $d['dDay'] = $this->queryString($request, 'day', date('d'));
        $d['dMonth'] = $this->queryString($request, 'month', date('m'));
        $d['dYear'] = $this->queryString($request, 'year', date('Y'));
        $d['use_site'] = Settings::get('module_multisite') == 1 ? 1 : 0;
        $d['accesStats'] = SecuAccess::UserSearch($login);
        $d['levelUser'] = SecuAccess::UserLevel($login, -1);
        $d['gNomUser'] = $login;
        $d['identifiant'] = $login;
        $d['modificationNom'] = SecuAccess::IsAllowedToModifyProfil();
        $d['modificationMail'] = SecuAccess::IsAllowedToModifyEmail();
        $d['modificationMDP'] = SecuAccess::IsAllowedToModifyMdp();
        $d['user_nom'] = $profile['nom'];
        $d['user_prenom'] = $profile['prenom'];
        $d['user_email'] = $profile['email'];
        $d['text_user_statut'] = $this->userStatusLabel($profile['statut']);
        $d['default_list_type'] = $profile['default_list_type'];
        $d['default_site'] = $profile['default_site'];
        $d['default_area'] = $profile['default_area'];
        $d['default_css'] = $profile['default_style'];
        $d['default_language'] = $profile['default_language'];
        $d['default_room'] = $profile['default_room'];

        //if (Settings::get('allow_my_connections') == 0 && Settings::get('allow_my_reservations') == 0) {
        //    $d['masquerMenu'] = 1;
        //}
        if ($d['modificationNom'] && (trim($profile['nom']) === '' || trim($profile['prenom']) === '')) {
            $d['AvertissementNomPrenom'] = 1;
        }


        $sites = $this->loadSites();
        $domainOptions = '';
        $resourceOptions = '';
        if ($d['levelUser'] >= Settings::get('allow_users_modify_domaine')) {
            $domainOptions = $this->buildDomainOptions(
                (int) $profile['default_site'],
                (int) $profile['default_area'],
                $d['use_site'],
                $login
            );
            $resourceOptions = $this->buildRoomOptions(
                (int) $profile['default_area'],
                (int) $profile['default_room']
            );
        }
        $themes = $this->loadThemes($context);
        $languages = $this->loadLanguages();
        $popup = $this->capturePopup($message);

        return $this->render('moncompte.twig', $bootstrap, [
            'd' => $d,
            'sites' => $sites,
            'domainOptions' => $domainOptions,
            'resourceOptions' => $resourceOptions,
            'themes' => $themes,
            'langues' => $languages,
            'popup' => $popup,
        ]);
    }

    private function updateProfile(Request $request, array $profile): string
    {
        $login = getUserName();
        $message = '';

        if (SecuAccess::IsAllowedToModifyMdp()) {
            $oldPassword = $this->postString($request, 'reg_password_a');
            $newPassword = $this->postString($request, 'reg_password1');
            $confirmPassword = $this->postString($request, 'reg_password2');
            if ($oldPassword !== '' && $newPassword !== '') {
                $storedPassword = (string) ($_SESSION['password'] ?? '');
                if ($storedPassword === '' || !password_verify($oldPassword, $storedPassword)) {
                    $message = $this->translator->trans('wrong_old_pwd');
                } elseif ($newPassword !== $confirmPassword) {
                    $message = $this->translator->trans('wrong_pwd2');
                } elseif (!check_password_difficult($newPassword)) {
                    $message = $this->translator->trans('mdp_taille').($GLOBALS['pass_leng'] ?? '');
                } else {
                    VerifyModeDemo();
                    $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
                    $this->execute(
                        'UPDATE '.TABLE_PREFIX.'_utilisateurs SET password=\''.SecuChaine::ProtectDataSql($passwordHash).'\' WHERE login=\''.SecuChaine::ProtectDataSql($login).'\'',
                        'Unable to update the account password.',
                    );
                    $_SESSION['password'] = $passwordHash;
                    $message = $this->translator->trans('update_pwd_succeed');
                }
            }
        }

        $email = $this->postString($request, 'reg_email', $profile['email']);
        $lastName = $this->postString($request, 'reg_nom', $profile['nom']);
        $firstName = $this->postString($request, 'reg_prenom', $profile['prenom']);
        $missingName = trim($lastName) === '' || trim($firstName) === '';
        $updates = [];

        if (SecuAccess::IsAllowedToModifyProfil()) {
            if (trim($lastName) !== '') {
                $updates[] = "nom='".SecuChaine::ProtectDataSql($lastName)."'";
                $_SESSION['nom'] = htmlspecialchars($lastName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            }
            if (trim($firstName) !== '') {
                $updates[] = "prenom='".SecuChaine::ProtectDataSql($firstName)."'";
                $_SESSION['prenom'] = htmlspecialchars($firstName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            }
        }

        if (SecuAccess::IsAllowedToModifyEmail()) {
            $unique = true;
            if (Settings::get('mail_user_unique') == 1) {
                $count = grr_sql_query1(
                    'SELECT COUNT(*) FROM '.TABLE_PREFIX.'_utilisateurs WHERE email=\''.SecuChaine::ProtectDataSql($email).'\' AND login<>\''.SecuChaine::ProtectDataSql($login).'\''
                );
                if ($count < 0) {
                    throw new \RuntimeException('Unable to verify that the account email address is unique: '.grr_sql_error());
                }
                $unique = $count <= 0;
            }
            if (!$unique) {
                $message = $this->translator->trans('mail_user_unique_error');
            } elseif (Settings::get('mail_user_obligatoire') != 1 || $email !== '') {
                $updates[] = "email='".SecuChaine::ProtectDataSql($email)."'";
            } else {
                $message = $this->translator->trans('mail_user_obligatoire');
            }
        }

        if ($updates !== []) {
            $this->execute(
                'UPDATE '.TABLE_PREFIX.'_utilisateurs SET '.implode(', ', $updates).' WHERE login=\''.SecuChaine::ProtectDataSql($login).'\'',
                'Unable to update the account profile.',
            );
            $message .= "\n".$this->translator->trans('message_records');
        }
        if (SecuAccess::IsAllowedToModifyProfil() && $missingName) {
            $message .= "\n".str_replace("\\'", "'", $this->translator->trans('required'));
        }

        return $message;
    }

    private function updatePreferences(Request $request, array $profile): void
    {
        $login = getUserName();
        $level = SecuAccess::UserLevel($login, -1);
        $updates = [];
        $site = $this->postString($request, 'p_id_site');
        $area = $this->postString($request, 'p_id_area');
        $room = $this->postString($request, 'p_id_room');
        $style = $this->postString($request, 'default_css');
        $listType = $this->postString($request, 'area_item_format');
        $language = $this->postString($request, 'default_language');

        if ($level >= Settings::get('allow_users_modify_domaine')) {
            foreach (['default_site' => $site, 'default_area' => $area, 'default_room' => $room] as $column => $value) {
                if ($value !== '') {
                    $updates[] = $column.'=\''.SecuChaine::ProtectDataSql($value).'\'';
                }
            }
        }
        if ($level >= Settings::get('allow_users_modify_theme') && $style !== '') {
            $updates[] = "default_style='".SecuChaine::ProtectDataSql($style)."'";
        }
        if ($level >= Settings::get('allow_users_modify_affichage') && $listType !== '') {
            $updates[] = "default_list_type='".SecuChaine::ProtectDataSql($listType)."'";
        }
        if ($level >= Settings::get('allow_users_modify_langue') && $language !== '') {
            $updates[] = "default_language='".SecuChaine::ProtectDataSql($language)."'";
        }

        if ($updates !== []) {
            $this->execute(
                'UPDATE '.TABLE_PREFIX.'_utilisateurs SET '.implode(', ', $updates).' WHERE login=\''.SecuChaine::ProtectDataSql($login).'\'',
                'Unable to update account preferences.',
            );
        }

        if ($level >= Settings::get('allow_users_modify_domaine')) {
            $_SESSION['default_site'] = $site !== '' && $site !== '0' ? $site : Settings::get('default_site');
            $_SESSION['default_area'] = $area !== '' && $area !== '0' ? $area : Settings::get('default_area');
            $_SESSION['default_room'] = $room !== '' && $room !== '0' ? $room : Settings::get('default_room');
        } else {
            $_SESSION['default_site'] = $profile['default_site'];
            $_SESSION['default_area'] = $profile['default_area'];
            $_SESSION['default_room'] = $profile['default_room'];
        }
        if ($level >= Settings::get('allow_users_modify_theme')) {
            $_SESSION['default_style'] = $style !== '' ? $style : Settings::get('default_css');
        }
        if ($level >= Settings::get('allow_users_modify_affichage')) {
            $_SESSION['default_list_type'] = $listType !== '' ? $listType : Settings::get('area_list_format');
        }
        if ($level >= Settings::get('allow_users_modify_langue')) {
            $_SESSION['default_language'] = $language !== '' ? $language : Settings::get('default_language');
        }
    }

    private function changePassword(Request $request, array $bootstrap): Response
    {
        global $d, $vocab;

        $context = $bootstrap['context'];
        $d = $context['d'] ?? [];
        $vocab = $context['vocab'] ?? [];
        $message = '';
        $forceChange = (int) ($_SESSION['changepwd'] ?? 0) === 1;

        if ($request->request->get('valid') === 'yes' && (SecuAccess::IsAllowedToModifyMdp() || $forceChange)) {
            $oldPassword = $this->postString($request, 'reg_password_a');
            $newPassword = $this->postString($request, 'reg_password1');
            $confirmPassword = $this->postString($request, 'reg_password2');
            if ($oldPassword !== '' && $newPassword !== '') {
                $storedPassword = (string) ($_SESSION['password'] ?? '');
                if ($storedPassword === '' || !password_verify($oldPassword, $storedPassword)) {
                    $message = $this->translator->trans('wrong_old_pwd');
                } elseif ($newPassword !== $confirmPassword) {
                    $message = $this->translator->trans('wrong_pwd2');
                } elseif ($oldPassword === $newPassword) {
                    $message = $this->translator->trans('wrong_pwd3');
                } elseif (!check_password_difficult($newPassword)) {
                    $message = $this->translator->trans('mdp_taille').($context['pass_leng'] ?? '');
                } else {
                    VerifyModeDemo();
                    $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
                    $this->execute(
                        'UPDATE '.TABLE_PREFIX.'_utilisateurs SET password=\''.SecuChaine::ProtectDataSql($passwordHash).'\', changepwd=\'0\' WHERE login=\''.SecuChaine::ProtectDataSql(getUserName()).'\'',
                        'Unable to update the account password.',
                    );
                    $_SESSION['password'] = $passwordHash;
                    $_SESSION['changepwd'] = 0;
                    $message = $this->translator->trans('update_pwd_succeed');
                    if ($forceChange) {
                        $_SESSION['msg_a_afficher'] = $message;
                        $_SESSION['displ_msg'] = 'yes';

                        return new RedirectResponse('account?pc=moncompte', Response::HTTP_FOUND, $bootstrap['headers']);
                    }
                }
            }
        }

        if (SecuAccess::IsAllowedToModifyMdp() || $forceChange) {
            $d['droitChanger'] = 1;
        }
        if ($forceChange) {
            $d['obligatoirement'] = 1;
        }

        $popup = $this->capturePopup($message);

        return $this->render('changemdp.twig', $bootstrap, [
            'd' => $d,
            'popup' => $popup,
        ]);
    }

    private function reservations(Request $request, array $bootstrap): Response
    {
        global $d, $vocab, $gListeReservations, $dformat;

        if (Settings::get('allow_my_reservations') == 0) {
            return new Response('Accès refusé', Response::HTTP_FORBIDDEN, $bootstrap['headers']);
        }

        $context = $bootstrap['context'];
        $d = $context['d'] ?? [];
        $vocab = $context['vocab'] ?? [];
        $dformat = $context['dformat'] ?? null;
        if (!is_string($dformat)) {
            throw new \RuntimeException('The account reservation date format was not initialized.');
        }
        $choice = $this->integerParameter($request, 'choix', 1);
        $choice = in_array($choice, [0, 1], true) ? $choice : 1;

        $d['nbResultat'] = AccountReservations::mes_resas(getUserName(), $choice, $dformat);
        $d['choix'] = $choice;
        foreach ([
            'resas_toutes', 'resas_a_venir', 'goto', 'start_date', 'time', 'duration',
            'match_area', 'room', 'sum_by_descrip', 'fulldescription', 'type',
            'lastupdate', 'statut', 'entry_found', 'entries_found', 'nothing_found',
            'admin', 'manage_my_account', 'report',
        ] as $key) {
        }

        return $this->render('mesreservations.twig', $bootstrap, [
            'd' => $d,
            'reservations' => $gListeReservations ?? [],
        ]);
    }

    private function connections(array $bootstrap): Response
    {
        global $d, $vocab;

        if (Settings::get('allow_my_connections') == 0) {
            return new Response('Accès refusé', Response::HTTP_FORBIDDEN, $bootstrap['headers']);
        }

        $context = $bootstrap['context'];
        $d = $context['d'] ?? [];
        $vocab = $context['vocab'] ?? [];
        $result = grr_sql_query(
            'SELECT START, SESSION_ID, REMOTE_ADDR, USER_AGENT, REFERER, AUTOCLOSE, END FROM '.TABLE_PREFIX.'_log WHERE LOGIN=\''.SecuChaine::ProtectDataSql(getUserName()).'\' ORDER BY START DESC'
        );
        if (!$result) {
            throw new \RuntimeException('Unable to load account connection history: '.grr_sql_error());
        }

        $connections = [];
        $now = time();
        for ($index = 0; ($row = grr_sql_row($result, $index)); ++$index) {
            $end = (string) $row[6];
            $endTime = $end !== '' ? strtotime($end) : false;
            $color = $endTime !== false && $endTime > $now ? 'green' : ((bool) $row[5] ? 'red' : 'black');
            $connections[] = [
                'debut' => $row[0],
                'fin' => $row[6],
                'ip' => $row[2],
                'navigateur' => $row[3],
                'provenance' => $row[4],
                'couleur' => $color,
            ];
        }
        grr_sql_free($result);

        foreach (['see_connexions_explain', 'begining_of_session', 'end_of_session', 'ip_adress', 'navigator', 'referer', 'demo_info_cache', 'admin', 'manage_my_account', 'report'] as $key) {
        }

        return $this->render('mesconnexions.twig', $bootstrap, [
            'd' => $d,
            'connexions' => $connections,
        ]);
    }

    private function accountLists(Request $request): Response
    {
        $bootstrap = $this->boot($request, 'account');
        $redirect = $this->bootstrapRedirect($bootstrap);
        if ($redirect !== null) {
            return new Response('', Response::HTTP_UNAUTHORIZED, [
                'X-GRR-Redirect' => $redirect->getTargetUrl(),
                'Cache-Control' => 'no-store',
            ]);
        }

        $currentLogin = getUserName();
        if ($currentLogin === '') {
            return new Response('', Response::HTTP_UNAUTHORIZED, [
                'X-GRR-Redirect' => 'login',
                'Cache-Control' => 'no-store',
            ]);
        }
        if (SecuAccess::UserLevel($currentLogin, -1) < 1) {
            return new Response('Accès refusé', Response::HTTP_FORBIDDEN, $bootstrap['headers']);
        }

        $targetLogin = $this->queryString($request, 'session_login', $currentLogin);
        if (
            $targetLogin !== $currentLogin
            && SecuAccess::UserLevel($currentLogin, -1) < 6
            && (int) SecuAccess::UserLevel($currentLogin, -1, 'user') !== 1
        ) {
            return new Response('Accès refusé', Response::HTTP_FORBIDDEN, $bootstrap['headers']);
        }

        $type = $this->queryString($request, 'type');
        if ($type === 'domaine') {
            $html = $this->domainOptions($request, $targetLogin);
        } elseif ($type === 'ressource') {
            $html = $this->roomOptions($request);
        } else {
            throw new BadRequestHttpException('Type de liste invalide.');
        }

        return new Response($html, Response::HTTP_OK, array_merge($bootstrap['headers'], [
            'Content-Type' => 'text/html; charset='.($GLOBALS['charset_html'] ?? 'UTF-8'),
            'Cache-Control' => 'no-store',
        ]));
    }

    private function domainOptions(Request $request, string $targetLogin): string
    {
        // Site / domaine par défaut absents (ex. création d'un utilisateur) : aucun (-1)
        $site = $this->integerParameter($request, 'id_site', -1);
        $defaultArea = $this->integerParameter($request, 'default_area', -1);
        $useSite = $this->integerParameter($request, 'use_site', null);
        if (!in_array($useSite, [0, 1], true)) {
            throw new BadRequestHttpException('Paramètres de domaine manquants.');
        }

        return $this->buildDomainOptions($site, $defaultArea, $useSite, $targetLogin);
    }

    private function buildDomainOptions(int $site, int $defaultArea, int $useSite, string $targetLogin): string
    {
        if ($useSite === 1 && $site !== -1) {
            $sql = 'SELECT a.id, a.area_name, a.access FROM '.TABLE_PREFIX.'_area a, '.TABLE_PREFIX.'_j_site_area j WHERE a.id=j.id_area AND j.id_site='.$site.' ORDER BY a.order_display, a.area_name';
        } elseif ($useSite === 0) {
            $sql = 'SELECT id, area_name, access FROM '.TABLE_PREFIX.'_area ORDER BY order_display, area_name';
        } else {
            $sql = '';
        }

        $html = '<div class="form-group row col-sm-12"><label class="col-sm-6" for="p_id_area">'.htmlspecialchars($this->translator->trans('default_area'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</label><div class="col-sm-6"><select class="form-control" id="p_id_area" name="p_id_area" onchange="modifier_liste_ressources(1)"><option value="-1">'.htmlspecialchars($this->translator->trans('choose_an_area'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</option>';
        if ($sql !== '') {
            $result = grr_sql_query($sql);
            if (!$result) {
                throw new \RuntimeException('Unable to load account areas: '.grr_sql_error());
            }
            for ($index = 0; ($row = grr_sql_row($result, $index)); ++$index) {
                if (SecuAccess::UserArea($targetLogin, $row[0]) == 0) {
                    continue;
                }
                $label = htmlspecialchars((string) $row[1], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                if ($row[2] === 'r') {
                    $label .= ' ('.htmlspecialchars($this->translator->trans('restricted'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').')';
                }
                $selected = $defaultArea === (int) $row[0] ? ' selected="selected"' : '';
                $html .= '<option value="'.(int) $row[0].'"'.$selected.'>'.$label.'</option>';
            }
            grr_sql_free($result);
        }

        return $html.'</select></div></div>';
    }

    private function roomOptions(Request $request): string
    {
        // Ressource par défaut absente (ex. création d'un utilisateur) : « toutes les ressources » (-1)
        $defaultRoom = $this->integerParameter($request, 'default_room', -1);
        // « mode » et non « action » : ce dernier est déjà utilisé dans l'URL (action=lists)
        $action = $this->integerParameter($request, 'mode', 1);

        $label = htmlspecialchars($this->translator->trans('default_room'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        if ($action === 2) {
            return '<div class="form-group row col-sm-12"><label class="col-sm-6" for="p_id_room">'.$label.'</label><div class="col-sm-6"><select class="form-control" name="p_id_room" id="p_id_room"><option value="-1">'.htmlspecialchars($this->translator->trans('default_room_all'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</option></select></div></div>';
        }

        // Aucun domaine choisi : seules les options « toutes les ressources » sont proposées
        $area = $this->integerParameter($request, 'id_area', -1);

        return $this->buildRoomOptions($area, $defaultRoom);
    }

    private function buildRoomOptions(int $area, int $defaultRoom): string
    {
        $label = htmlspecialchars($this->translator->trans('default_room'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $sql = 'SELECT id, room_name FROM '.TABLE_PREFIX.'_room WHERE area_id='.$area;
        foreach (SecuAccess::UserResource(getUserName(), 'all') as $roomId) {
            $sql .= ' AND id <> '.(int) $roomId;
        }
        $result = grr_sql_query($sql.' ORDER BY order_display, room_name');
        if (!$result) {
            throw new \RuntimeException('Unable to load account rooms: '.grr_sql_error());
        }

        $html = '<div class="form-group row col-sm-12"><label class="col-sm-6" for="p_id_room">'.$label.'</label><div class="col-sm-6"><select class="form-control" name="p_id_room" id="p_id_room">';
        foreach ([-1 => 'default_room_all', -2 => 'default_room_week_all', -3 => 'default_room_month_all', -4 => 'default_room_month_all_bis'] as $value => $vocabKey) {
            $selected = $defaultRoom === $value ? ' selected="selected"' : '';
            $html .= '<option value="'.$value.'"'.$selected.'>'.htmlspecialchars($this->translator->trans($vocabKey), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</option>';
        }
        for ($index = 0; ($row = grr_sql_row($result, $index)); ++$index) {
            $selected = $defaultRoom === (int) $row[0] ? ' selected="selected"' : '';
            $html .= '<option value="'.(int) $row[0].'"'.$selected.'>'.htmlspecialchars((string) $row[1], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').' '.htmlspecialchars($this->translator->trans('display_week'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</option>';
        }
        grr_sql_free($result);

        return $html.'</select></div></div>';
    }

    private function loadProfile(string $login): array
    {
        $result = grr_sql_query(
            'SELECT nom, prenom, statut, email, default_site, default_area, default_room, default_style, default_list_type, default_language, source FROM '.TABLE_PREFIX.'_utilisateurs WHERE login=\''.SecuChaine::ProtectDataSql($login).'\''
        );
        if (!$result) {
            throw new \RuntimeException('Unable to load account profile: '.grr_sql_error());
        }
        $row = grr_sql_row($result, 0);
        grr_sql_free($result);
        if (!$row) {
            throw new \RuntimeException('The current GRR account could not be found.');
        }

        return [
            'nom' => (string) $row[0],
            'prenom' => (string) $row[1],
            'statut' => (string) $row[2],
            'email' => (string) $row[3],
            'default_site' => $row[4] !== '' && $row[4] !== '0' ? $row[4] : Settings::get('default_site'),
            'default_area' => $row[5] !== '' && $row[5] !== '0' ? $row[5] : Settings::get('default_area'),
            'default_room' => $row[6] !== '' && $row[6] !== '0' ? $row[6] : Settings::get('default_room'),
            'default_style' => (string) $row[7],
            'default_list_type' => $row[8] !== '' ? $row[8] : Settings::get('area_list_format'),
            'default_language' => $row[9] !== '' ? $row[9] : Settings::get('default_language'),
        ];
    }

    private function loadSites(): array
    {
        $result = grr_sql_query('SELECT id, sitecode, sitename FROM '.TABLE_PREFIX.'_site ORDER BY id ASC');
        if (!$result) {
            throw new \RuntimeException('Unable to load account sites: '.grr_sql_error());
        }

        $sites = [];
        for ($index = 0; ($row = grr_sql_row($result, $index)); ++$index) {
            $sites[] = ['idsite' => $row[0], 'nomsite' => $row[2]];
        }
        grr_sql_free($result);

        return $sites;
    }

    private function loadThemes(array $context): array
    {
        $themes = [];
        foreach (($context['liste_themes'] ?? []) as $index => $theme) {
            $themes[] = [
                'idtheme' => $theme,
                'nomtheme' => encode_message_utf8($context['liste_name_themes'][$index] ?? $theme),
            ];
        }

        return $themes;
    }

    private function loadLanguages(): array
    {
        $languages = [];
        foreach (explode(';', (string) Settings::get('langues_dispo')) as $language) {
            if ($language !== '') {
                $languages[] = ['idlangue' => $language, 'nomlangue' => encode_message_utf8($language)];
            }
        }

        return $languages;
    }

    private function userStatusLabel(string $status): string
    {
        return match ($status) {
            'utilisateur' => $this->translator->trans('statut_user'),
            'visiteur' => $this->translator->trans('statut_visitor'),
            'gestionnaire_utilisateur' => $this->translator->trans('statut_user_administrator'),
            'administrateur' => $this->translator->trans('statut_administrator'),
            default => $status,
        };
    }

    private function capturePopup(string $message): string
    {
        ob_start();
        affiche_pop_up($message, 'user');

        return (string) ob_get_clean();
    }

    private function render(string $template, array $bootstrap, array $data): Response
    {
        $context = $bootstrap['context'];
        $data += [
            'd' => $context['d'] ?? [],
            'settings' => $context['AllSettings'] ?? [],
        ];
        $body = $this->twig->render('@account/'.$template, $data).($data['popup'] ?? '');

        return new Response($body, $bootstrap['status'], $bootstrap['headers']);
    }

    private function boot(Request $request, string $page): array
    {
        $originalGet = $_GET;
        $originalPhpSelf = $_SERVER['PHP_SELF'] ?? null;
        $_GET = array_merge($_GET, $request->query->all());
        $_GET['p'] = $page;
        $_SERVER['PHP_SELF'] = rtrim($request->getBasePath(), '/').'/app.php';
        $GLOBALS['page'] = $page;
        $GLOBALS['grr_script_name'] = 'app.php';
        $GLOBALS['desactive_VerifNomPrenomUser'] = 'y';
        $GLOBALS['niveauDossier'] = 1;

        try {
            $bootstrap = $this->applicationContext->boot();
        } finally {
            $_GET = $originalGet;
            if ($originalPhpSelf === null) {
                unset($_SERVER['PHP_SELF']);
            } else {
                $_SERVER['PHP_SELF'] = $originalPhpSelf;
            }
        }

        $context = $bootstrap['context'];
        foreach (['d', 'vocab', 'locale', 'trad'] as $name) {
            if (array_key_exists($name, $context)) {
                $GLOBALS[$name] = $context[$name];
            }
        }
        $GLOBALS['trad'] = $GLOBALS['vocab'] ?? [];

        return $bootstrap;
    }

    private function bootstrapRedirect(array $bootstrap): ?RedirectResponse
    {
        foreach ($bootstrap['headers'] as $name => $values) {
            if (strcasecmp((string) $name, 'Location') === 0 && isset($values[0])) {
                return new RedirectResponse((string) $values[0], $bootstrap['status'], $this->withoutLocation($bootstrap['headers']));
            }
        }

        return null;
    }

    private function withoutLocation(array $headers): array
    {
        foreach (array_keys($headers) as $name) {
            if (strcasecmp((string) $name, 'Location') === 0) {
                unset($headers[$name]);
            }
        }

        return $headers;
    }

    private function execute(string $sql, string $message): void
    {
        if (grr_sql_command($sql) < 0) {
            throw new \RuntimeException($message.' '.grr_sql_error());
        }
    }

    private function postString(Request $request, string $key, string $default = ''): string
    {
        $value = $request->request->get($key, $default);
        if (!is_string($value) && !is_numeric($value)) {
            throw new BadRequestHttpException(sprintf('The "%s" field must be a scalar string.', $key));
        }

        return (string) $value;
    }

    private function queryString(Request $request, string $key, string $default = ''): string
    {
        $value = $request->query->get($key, $default);
        if (!is_string($value) && !is_numeric($value)) {
            throw new BadRequestHttpException(sprintf('The "%s" query parameter must be a scalar string.', $key));
        }

        return (string) $value;
    }

    private function integerParameter(Request $request, string $key, ?int $default): ?int
    {
        $value = $request->request->get($key, $request->query->get($key));
        if ($value === null || $value === '') {
            return $default;
        }
        if (!is_scalar($value) || preg_match('/^-?[0-9]+$/D', (string) $value) !== 1) {
            throw new BadRequestHttpException(sprintf('The "%s" query parameter must be an integer.', $key));
        }

        return (int) $value;
    }
}
