<?php

namespace Devome\Grr\Controller;

use Devome\Grr\Service\GrrApplicationContext;
use Devome\Grr\Service\PasswordReset;
use Hook;
use SecuChaine;
use Settings;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Twig\Environment;
use Symfony\Contracts\Translation\TranslatorInterface;

final class LoginController
{
    public function __construct(
        private readonly Environment $twig,
        private readonly GrrApplicationContext $applicationContext,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function index(Request $request): Response
    {
        if ($request->query->has('p') && $request->query->get('p') !== 'login') {
            throw new NotFoundHttpException();
        }

        $bootstrap = $this->applicationContext->boot();
        $context = $bootstrap['context'];
        global $d, $vocab;
        $d = $context['d'] ?? [];
        $vocab = $context['vocab'] ?? [];

        if ($request->query->has('url')) {
            $d['url'] = rawurlencode($this->stringParameter($request, 'url', false));
        }

        if (Settings::get('redirection_https') == 1 && !$request->isSecure()) {
            $httpsUrl = 'https://'.$request->getHttpHost().$request->getRequestUri();

            return $this->redirect($httpsUrl, $bootstrap, Response::HTTP_MOVED_PERMANENTLY);
        }

        foreach ([
            'tokenprivee' => static fn (): string => generationToken(),
            'tokenpublic' => static fn (): string => generationToken(),
            'tokenapi' => static fn (): string => generationToken(),
            'tokenuser' => static fn (): string => generationToken(),
            'idinstallation' => static fn (): string => uniqid('', true),
        ] as $setting => $generator) {
            if (Settings::get($setting) === '') {
                Settings::set($setting, $generator());
            }
        }

        $this->sendAnonymousStatistics($context);

        if ($request->request->has('login') && $request->request->has('password')) {
            $login = $this->stringParameter($request, 'login', true);
            $password = $this->stringParameter($request, 'password', true);
            $_SESSION = [];
            $result = grr_opensession($login, SecuChaine::Unslashes($password));
            session_write_close();

            $message = $this->loginErrorMessage((string) $result);
            if ($message !== null) {
                $d['messageLogin'] = $message;
            } elseif ((string) $result === '12') {
                return $this->redirect('./account?pc=changemdp', $bootstrap);
            } elseif ((string) $result !== '14') {
                nettoieLogConnexion($context['nbMaxJoursLogConnexion'] ?? 365);
                nettoieLogEmail($context['nbMaxJoursLogEmail'] ?? 365);

                if ($request->request->has('url')) {
                    $url = rawurldecode($this->stringParameter($request, 'url', true));
                    $target = is_safe_redirect($url) ? $url : './'.htmlspecialchars_decode(page_accueil());
                } else {
                    $target = './'.htmlspecialchars_decode(page_accueil());
                }

                return $this->redirect($target, $bootstrap);
            }
        }

        MajMysqlModeDemo();

        $hiddenLoginUrl = Settings::get('Url_cacher_page_login');
        if ($hiddenLoginUrl !== null && $hiddenLoginUrl !== '' && empty($context['sso_super_admin']) && !$request->query->has('local')) {
            return $this->redirect('./index.php', $bootstrap);
        }

        $imageDirectory = $context['gcDossierImg'] ?? '';
        $logo = './personnalisation/'.$imageDirectory.'/logos/'.Settings::get('logo');
        if (Settings::get('logo') !== '' && is_file(dirname(__DIR__, 4).'/'.$logo)) {
            $d['logo'] = $logo;
        }

        $loginImage = './personnalisation/'.$imageDirectory.'/logos/'.Settings::get('image_connexion');
        if (Settings::get('image_connexion') !== '' && is_file(dirname(__DIR__, 4).'/'.$loginImage)) {
            $d['image_connexion'] = $loginImage;
        }

        $hookResult = Hook::Appel('hookLienConnexion1');
        $d['hookLienConnexion1'] = $hookResult['hookLienConnexion1'] ?? '';

        if (Settings::get('webmaster_email') !== '') {
            $contactLink = affiche_lien_contact('contact_administrateur', 'identifiant:non', 'seulement_si_email');
            if ($contactLink !== '') {
                $d['contactAdmin'] = '['.$contactLink.'] ';
            }
        }

        $d['liensPerso2'] = liensPerso(2, $d['levelUser'] ?? 0);
        $d['lienGRR'] = $context['grr_devel_url'] ?? '';
        $d['reinitMdp'] = PasswordReset::isEnabled();
        $templateNumber = Settings::get('login_template') > 0 ? Settings::get('login_template') : 1;
        $template = 'login'.$templateNumber.'.twig';

        return new Response(
            $this->twig->render($template, [
                'd' => $d,
                'settings' => $context['AllSettings'] ?? [],
            ]),
            $bootstrap['status'],
            $this->captureHeaders($bootstrap['headers']),
        );
    }

    private function sendAnonymousStatistics(array $context): void
    {
        if (($context['gEnvoisStatGRR'] ?? 0) != 1 || Settings::get('cronstat') >= time()) {
            return;
        }

        Settings::set('cronstat', time() + 60 * 60 * 24);
        $query = [
            'id' => Settings::get('idinstallation'),
            'versiongrr' => $context['version_grr'] ?? '',
            'versionrepo' => $context['versionReposite'] ?? '',
            'versionbdd' => Settings::get('version'),
            'versionbddprevious' => Settings::get('previousversion'),
            'firstversion' => Settings::get('firstversion'),
            'langue' => Settings::get('default_language'),
        ];

        if (($context['gEnvoisServeur'] ?? 0) == 1) {
            $query += [
                'php' => phpversion(),
                'moteursql' => $context['dbsys'] ?? '',
                'versionsql' => grr_sql_version(),
                'os' => preg_replace('/\s+/', '', php_uname('s')),
            ];
        }

        $url = 'https://grr.devome.com/API/statgrr.php?'.http_build_query($query);
        $streamContext = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "Connection: close\r\n",
                'timeout' => 2,
            ],
        ]);
        @file_get_contents($url, false, $streamContext);
    }

    private function loginErrorMessage(string $result): ?string
    {
        $message = $this->translator->trans('echec_connexion_GRR');

        return match ($result) {
            '2' => $message.' '.$this->translator->trans('wrong_pwd'),
            '3' => $message.'<br />'.$this->translator->trans('importation_impossible'),
            '4' => $message.' '.$this->translator->trans('causes_possibles').'<br />- '.$this->translator->trans('wrong_pwd').'<br />- '.$this->translator->trans('echec_authentification_ldap'),
            '5' => $message.'<br />'.$this->translator->trans('connexion_a_grr_non_autorisee'),
            '6' => $message.'<br />'.$this->translator->trans('connexion_a_grr_non_autorisee').'<br />'.$this->translator->trans('format_identifiant_incorrect'),
            '7' => $message.'<br />'.$this->translator->trans('connexion_a_grr_non_autorisee').'<br />'.$this->translator->trans('echec_authentification_ldap').'<br />'.$this->translator->trans('ldap_chemin_invalide'),
            '8' => $message.'<br />'.$this->translator->trans('connexion_a_grr_non_autorisee').'<br />'.$this->translator->trans('echec_authentification_ldap').'<br />'.$this->translator->trans('ldap_recherche_identifiant_aucun_resultats'),
            '9' => $message.'<br />'.$this->translator->trans('connexion_a_grr_non_autorisee').'<br />'.$this->translator->trans('echec_authentification_ldap').'<br />'.$this->translator->trans('ldap_doublon_identifiant'),
            '10' => $message.'<br />'.$this->translator->trans('connexion_a_grr_non_autorisee').'<br />'.$this->translator->trans('echec_authentification_imap'),
            '11' => $message.'<br />'.$this->translator->trans('echec_authentification_ip'),
            '13' => $message.'<br />'.$this->translator->trans('echec_authentification_horaire').' '.Settings::get('horaireconnexionde').' - '.Settings::get('horaireconnexiona'),
            '14' => null,
            default => null,
        };
    }

    private function stringParameter(Request $request, string $key, bool $post): string
    {
        $bag = $post ? $request->request : $request->query;
        try {
            return $bag->getString($key);
        } catch (\InvalidArgumentException $exception) {
            throw new BadRequestHttpException(sprintf('The "%s" parameter must be a string.', $key), $exception);
        }
    }

    private function redirect(string $target, array $bootstrap, int $status = Response::HTTP_FOUND): RedirectResponse
    {
        return new RedirectResponse($target, $status, $this->captureHeaders($bootstrap['headers']));
    }

    private function captureHeaders(array $headers): array
    {
        foreach (headers_list() as $headerLine) {
            $separator = strpos($headerLine, ':');
            if ($separator === false) {
                continue;
            }

            $name = trim(substr($headerLine, 0, $separator));
            $headers[$name][] = trim(substr($headerLine, $separator + 1));
        }
        header_remove();

        return $headers;
    }
}
