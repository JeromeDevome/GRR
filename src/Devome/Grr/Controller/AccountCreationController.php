<?php

namespace Devome\Grr\Controller;

use Devome\Grr\Service\GrrApplicationContext;
use Email;
use Gregwar\Captcha\PhraseBuilder;
use Pages;
use SecuChaine;
use Settings;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Twig\Environment;
use Symfony\Contracts\Translation\TranslatorInterface;

final class AccountCreationController
{
    public function __construct(
        private readonly Environment $twig,
        private readonly GrrApplicationContext $applicationContext,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function index(Request $request): Response
    {
        if ($request->query->get('p') !== 'creationcompte') {
            throw new NotFoundHttpException();
        }

        $bootstrap = $this->applicationContext->boot();
        $context = $bootstrap['context'];
        global $d, $vocab;
        $d = $context['d'] ?? [];
        $vocab = $context['vocab'] ?? [];

        require_once dirname(__DIR__, 4).'/include/pages.class.php';
        if (!Pages::load()) {
            throw new \RuntimeException('Unable to load account-creation settings.');
        }

        $passLength = (int) ($context['pass_leng'] ?? 0);
        $d['caractMini'] = $passLength.' caractères minimum';
        $d['msgErreur'] = '';
        $d['msgOk'] = '';

        if (Settings::get('fct_crea_cpt') == 1 && $request->request->has('nom')) {
            $values = [];
            foreach (['nom', 'prenom', 'mdp1', 'mdp2', 'email', 'telephone', 'commentaire', 'captcha'] as $key) {
                $values[$key] = $this->postString($request, $key);
            }
            $values['mdp1'] = SecuChaine::Unslashes($values['mdp1']);
            $values['mdp2'] = SecuChaine::Unslashes($values['mdp2']);

            $missingFields = '';
            foreach ([
                'nom' => 'Votre nom',
                'prenom' => 'Votre prénom',
                'email' => 'Votre adresse email',
            ] as $key => $label) {
                if ($values[$key] === '') {
                    $missingFields .= $label.'<br/>';
                }
            }
            if ($values['mdp1'] === '' || $values['mdp2'] === '') {
                $missingFields .= 'Le mot de passe<br/>';
            }

            $hasError = false;
            if (Settings::get('fct_crea_cpt_captcha') == 1) {
                if (
                    isset($_SESSION['phrase'])
                    && PhraseBuilder::comparePhrases((string) $_SESSION['phrase'], $values['captcha'])
                ) {
                    unset($_SESSION['phrase']);
                } else {
                    $d['msgErreur'] .= $vocab['captcha_incorrect'].'<br/>';
                    $hasError = true;
                    unset($_SESSION['phrase']);
                }
            }

            if ($missingFields !== '') {
                $hasError = true;
                $d['msgErreur'] .= "Erreur. Les champs suivants doivent être obligatoirement remplis :<br/><br/>".$missingFields;
            }
            if ($values['mdp1'] !== $values['mdp2']) {
                $hasError = true;
                $d['msgErreur'] .= "Les mots de passe sont différents.<br/><br/>";
            }
            if (!check_password_difficult($values['mdp1'])) {
                $hasError = true;
                $d['msgErreur'] .= $this->translator->trans('mdp_taille').$passLength.".<br/><br/>";
            }

            if (!$hasError) {
                $passwordHash = password_hash($values['mdp1'], PASSWORD_DEFAULT);
                $sql = 'INSERT INTO '.TABLE_PREFIX.'_utilisateurs_demandes SET '
                    ."nom='".SecuChaine::ProtectDataSql($values['nom'])."', "
                    ."prenom='".SecuChaine::ProtectDataSql($values['prenom'])."', "
                    ."email='".SecuChaine::ProtectDataSql($values['email'])."', "
                    ."telephone='".SecuChaine::ProtectDataSql($values['telephone'])."', "
                    ."mdp='".SecuChaine::ProtectDataSql($passwordHash)."', "
                    ."commentaire='".SecuChaine::ProtectDataSql($values['commentaire'])."', "
                    ."datedemande='".date('Ymd')."'";
                if (grr_sql_command($sql) < 0) {
                    throw new \RuntimeException('Unable to create the account request: '.grr_sql_error());
                }

                require_once dirname(__DIR__, 4).'/include/mail.class.php';
                $mailTemplate = Pages::get('mails_demandecompte_'.($context['locale'] ?? 'fr-fr'));
                if (!is_array($mailTemplate) || !isset($mailTemplate[0], $mailTemplate[1])) {
                    throw new \RuntimeException('The account-request email template is missing.');
                }

                $codes = [
                    '%nomdusite%' => Settings::get('title_home_page'),
                    '%nometablissement%' => Settings::get('company'),
                    '%urlgrr%' => traite_grr_url('', 'y'),
                    '%webmasteremail%' => Settings::get('webmaster_email'),
                    '%formnom%' => $values['nom'],
                    '%formprenom%' => $values['prenom'],
                    '%formemail%' => $values['email'],
                    '%formtelephone%' => $values['telephone'],
                    '%formcommentaire%' => $values['commentaire'],
                ];
                $subject = str_replace(array_keys($codes), $codes, $mailTemplate[0]);
                $message = str_replace(array_keys($codes), $codes, $mailTemplate[1]);
                $templateName = 'mails_demandecompte_'.($context['locale'] ?? 'fr-fr');
                Email::Envois($values['email'], $subject, $message, $values['email'], '', '', '', $templateName);

                $managers = $this->loadEmailRecipients(
                    "SELECT email FROM ".TABLE_PREFIX."_utilisateurs WHERE statut='gestionnaire_utilisateur'"
                );
                if ($managers !== []) {
                    Email::Envois(implode(';', $managers), $subject, $message, (string) Settings::get('webmaster_email'), '', '', '', $templateName);
                } else {
                    $administrators = $this->loadEmailRecipients(
                        "SELECT email FROM ".TABLE_PREFIX."_utilisateurs WHERE statut='administrateur'"
                    );
                    if ($administrators !== []) {
                        Email::Envois(implode(';', $administrators), $subject, $message, '', '', (string) Settings::get('webmaster_email'), '', $templateName);
                    }
                }

                $d['msgOk'] = 'Votre demande a bien été prise en compte. Vous recevrez un mail après que votre demande soit traitée.';
            }
        }

        if (Settings::get('fct_crea_cpt') != 1) {
            return new Response('<h3>Erreur : Fonction non active</h3>', Response::HTTP_FORBIDDEN);
        }

        return new Response(
            $this->twig->render('creationcompte.twig', [
                'd' => $d,
                'settings' => $context['AllSettings'] ?? [],
            ]),
            $bootstrap['status'],
            $bootstrap['headers'],
        );
    }

    private function postString(Request $request, string $key): string
    {
        try {
            return $request->request->getString($key, '');
        } catch (\InvalidArgumentException $exception) {
            throw new BadRequestHttpException(sprintf('The "%s" field must be a string.', $key), $exception);
        }
    }

    private function loadEmailRecipients(string $sql): array
    {
        $result = grr_sql_query($sql);
        if (!$result) {
            throw new \RuntimeException('Unable to load account-request recipients: '.grr_sql_error());
        }

        $recipients = [];
        for ($index = 0; ($row = grr_sql_row($result, $index)); ++$index) {
            if ($row[0] !== '') {
                $recipients[] = $row[0];
            }
        }
        grr_sql_free($result);

        return $recipients;
    }
}
