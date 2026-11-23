<?php

namespace Devome\Grr\Controller;

use Devome\Grr\Service\GrrApplicationContext;
use Devome\Grr\Service\PasswordReset;
use Email;
use Gregwar\Captcha\PhraseBuilder;
use Pages;
use Settings;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

/**
 * Page « mot de passe oublié » :
 *  - sans jeton : formulaire de demande (identifiant, ou adresse mail si les adresses sont uniques) ;
 *  - avec jeton : saisie du nouveau mot de passe.
 */
final class PasswordResetController
{
    public function __construct(
        private readonly Environment $twig,
        private readonly GrrApplicationContext $applicationContext,
        private readonly TranslatorInterface $translator,
        private readonly PasswordReset $passwordReset,
    ) {
    }

    public function index(Request $request): Response
    {
        if ($request->query->get('p') !== 'reinitmdp') {
            throw new NotFoundHttpException();
        }

        $bootstrap = $this->applicationContext->boot();
        $context = $bootstrap['context'];
        global $d, $vocab;
        $d = $context['d'] ?? [];
        $vocab = $context['vocab'] ?? [];

        if (!PasswordReset::isEnabled()) {
            return new Response('<h3>'.htmlspecialchars($this->translator->trans('reinit_mdp_inactif')).'</h3>', Response::HTTP_FORBIDDEN);
        }

        $d['msgErreur'] = '';
        $d['msgOk'] = '';
        $d['emailLookup'] = PasswordReset::allowsEmailLookup();
        $d['captcha'] = Settings::get('fct_reinit_mdp_captcha') == 1;
        $d['token'] = $this->stringParameter($request, 'token', $request->isMethod('POST'));
        $d['etape'] = 'demande';

        if ($d['token'] !== '') {
            $this->handleNewPassword($request, $context);
        } elseif ($request->isMethod('POST')) {
            $this->handleRequest($request, $context);
        }

        // Le jeton (secret) ne doit pas fuiter vers d'autres sites via l'en-tête Referer
        $headers = $bootstrap['headers'];
        $headers['Referrer-Policy'] = 'no-referrer';

        return new Response(
            $this->twig->render('reinitmdp.twig', [
                'd' => $d,
                'settings' => $context['AllSettings'] ?? [],
            ]),
            $bootstrap['status'],
            $headers,
        );
    }

    private function handleRequest(Request $request, array $context): void
    {
        global $d;

        if ($d['captcha']) {
            $phrase = $_SESSION['phrase'] ?? null;
            unset($_SESSION['phrase']);
            if (!is_string($phrase) || !PhraseBuilder::comparePhrases($phrase, $this->stringParameter($request, 'captcha', true))) {
                $d['msgErreur'] = $this->translator->trans('captcha_incorrect');

                return;
            }
        }

        $identifier = $this->stringParameter($request, 'identifiant', true);
        if (trim($identifier) === '') {
            $d['msgErreur'] = $this->translator->trans($d['emailLookup'] ? 'reinit_mdp_identifiant_email' : 'reinit_mdp_identifiant');

            return;
        }

        $user = $this->passwordReset->findUser($identifier);
        if ($user !== null) {
            $this->sendMail($user, $context);
        }

        // Réponse identique que le compte existe ou non, pour ne pas révéler les identifiants
        $d['msgOk'] = $this->translator->trans('reinit_mdp_demande_ok', ['%duree%' => PasswordReset::validityMinutes()]);
        $d['etape'] = 'envoye';
    }

    private function handleNewPassword(Request $request, array $context): void
    {
        global $d;

        $user = $this->passwordReset->userFromToken($d['token']);
        if ($user === null) {
            $d['msgErreur'] = $this->translator->trans('reinit_mdp_lien_invalide');
            $d['token'] = '';

            return;
        }

        $d['etape'] = 'nouveau';
        $d['login'] = $user['login'];
        $d['passLeng'] = (int) ($context['pass_leng'] ?? 0);
        if (!$request->isMethod('POST')) {
            return;
        }

        $password = $this->stringParameter($request, 'mdp1', true);
        $confirmation = $this->stringParameter($request, 'mdp2', true);
        if ($password !== $confirmation) {
            $d['msgErreur'] = $this->translator->trans('wrong_pwd2');
        } elseif (!check_password_difficult($password)) {
            $d['msgErreur'] = $this->translator->trans('mdp_taille').$d['passLeng'];
        } else {
            VerifyModeDemo();
            $this->passwordReset->updatePassword($user['login'], $password);
            $d['msgOk'] = $this->translator->trans('reinit_mdp_ok');
            $d['etape'] = 'termine';
            $d['token'] = '';
        }
    }

    private function sendMail(array $user, array $context): void
    {
        $projectDir = dirname(__DIR__, 4);
        require_once $projectDir.'/include/pages.class.php';
        require_once $projectDir.'/include/mail.class.php';
        if (!Pages::load()) {
            throw new \RuntimeException('Unable to load the email templates.');
        }

        $templateName = 'mails_reinitmdp_'.($context['locale'] ?? 'fr-fr');
        $template = Pages::get($templateName);
        if (!is_array($template) || !isset($template[0], $template[1])) {
            throw new \RuntimeException('The password-reset email template is missing.');
        }

        $link = traite_grr_url('reinitmdp', 'y').'?token='.rawurlencode($this->passwordReset->createToken($user));
        $codes = [
            '%nomdusite%' => Settings::get('title_home_page'),
            '%nometablissement%' => Settings::get('company'),
            '%urlgrr%' => traite_grr_url('', 'y'),
            '%webmasteremail%' => Settings::get('webmaster_email'),
            '%identifiant%' => $user['login'],
            '%nom%' => $user['nom'],
            '%prenom%' => $user['prenom'],
            '%lienreinit%' => '<a href="'.htmlspecialchars($link).'">'.htmlspecialchars($link).'</a>',
            '%dureevalidite%' => (string) PasswordReset::validityMinutes(),
        ];
        $subject = str_replace(array_keys($codes), $codes, $template[0]);
        $message = str_replace(array_keys($codes), $codes, $template[1]);

        Email::Envois($user['email'], $subject, $message, (string) Settings::get('grr_mail_from'), '', '', '', $templateName);
    }

    private function stringParameter(Request $request, string $key, bool $fromPost): string
    {
        try {
            return ($fromPost ? $request->request : $request->query)->getString($key, '');
        } catch (\InvalidArgumentException $exception) {
            throw new BadRequestHttpException(sprintf('The "%s" field must be a string.', $key), $exception);
        }
    }
}
