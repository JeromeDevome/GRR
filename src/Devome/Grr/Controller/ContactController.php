<?php

namespace Devome\Grr\Controller;

use Devome\Grr\Service\GrrApplicationContext;
use Email;
use SecuChaine;
use Settings;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Twig\Environment;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ContactController
{
    public function __construct(
        private readonly Environment $twig,
        private readonly GrrApplicationContext $applicationContext,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function index(Request $request): Response
    {
        if ($request->query->get('p') !== 'contact') {
            throw new NotFoundHttpException();
        }

        global $d, $vocab;

        $bootstrap = $this->applicationContext->boot();
        $context = $bootstrap['context'];
        $d = $context['d'] ?? [];
        $vocab = $context['vocab'] ?? [];

        $cible = $this->getParameter($request, 'cible');
        $d['UserNomPrenomEmail'] = null;
        $d['MailReponse'] = null;
        $d['resultat'] = null;
        $d['cible'] = $cible;
        $typeCible = $this->getParameter($request, 'type_cible');
        if ($typeCible !== 'identifiant:non') {
            $typeCible = '';
        }
        $d['type_cible'] = $typeCible;

        $action = $request->request->getString('action', '');
        $corpsMessage = $request->request->getString('message', '');
        $emailReponse = $request->request->getString('email_reponse', '');
        $objetMessage = $request->request->getString('objet_message', '');

        if ($request->request->has('objet_message') && trim($objetMessage) === '') {
            $action = '';
        }

        $d['action'] = $action;

        foreach ([
            'Envoi_d_un_courriel',
            'Message_poste_par',
            'deux_points',
            'webmaster_name',
            'company',
            'Email_pour_la_reponse',
            'Objet_du_message',
            'submit',
        ] as $translation) {
        }

        if ($action === 'envoi') {
            $recipient = '';
            if ($typeCible === 'identifiant:non') {
                if ($cible === 'contact_administrateur') {
                    $recipient = (string) Settings::get('webmaster_email');
                } elseif ($cible === 'contact_support') {
                    $recipient = (string) Settings::get('technical_support_email');
                }
            } else {
                $recipient = grr_sql_query1(
                    "SELECT email FROM ".TABLE_PREFIX."_utilisateurs WHERE login = '".SecuChaine::ProtectDataSql($cible)."'"
                );
                if ($recipient == -1) {
                    $recipient = '';
                }
            }

            if ($recipient === '') {
                $d['resultat'] = "L'envoi de messages est impossible car l'adresse email du destinataire n'a pas été renseignée par l'administrateur.";
            } else {
                $message = '';
                $username = getUserName();
                if ($username !== '') {
                    $message .= 'Nom et prénom du demandeur : '.affiche_nom_prenom_email($username, '', 'nomail')."\n";
                    $userEmail = grr_sql_query1(
                        "select email from ".TABLE_PREFIX."_utilisateurs where login='".SecuChaine::ProtectDataSql($username)."'"
                    );
                    if ($userEmail !== '' && $userEmail != -1) {
                        $message .= 'Email du demandeur : '.$userEmail."\n";
                    }
                    $message .= $vocab['statut'].preg_replace('/ /', ' ', $vocab['deux_points']).$_SESSION['statut']."\n";
                }

                $message .= $vocab['company'].preg_replace('/ /', ' ', $vocab['deux_points']).removeMailUnicode(Settings::get('company'))."\n";
                $message .= $vocab['email'].preg_replace('/ /', ' ', $vocab['deux_points']).$emailReponse."\n";
                $message .= "\n".$corpsMessage."\n";
                $subject = $vocab['subject_mail1'].' - '.$objetMessage;

                require_once dirname(__DIR__, 4).'/include/mail.class.php';

                Email::Envois((string) Settings::get('webmaster_email'), $subject, $message, $emailReponse, '', '');
                $d['resultat'] = 'Votre message a été envoyé !';
            }
        } else {
            $username = getUserName();
            if ($username !== '') {
                $d['UserNomPrenomEmail'] = affiche_nom_prenom_email($username, '', 'nomail');
                $userEmail = grr_sql_query1(
                    "SELECT email FROM ".TABLE_PREFIX."_utilisateurs WHERE login='".SecuChaine::ProtectDataSql($username)."'"
                );
                if ($userEmail !== '' && $userEmail != -1) {
                    $d['MailReponse'] = $userEmail;
                }
            }
        }

        return new Response(
            $this->twig->render('contact.twig', [
                'd' => $d,
                'settings' => $context['AllSettings'] ?? [],
            ]),
            $bootstrap['status'],
            $bootstrap['headers'],
        );
    }

    private function getParameter(Request $request, string $key): string
    {
        $source = $request->request->has($key) ? $request->request : $request->query;
        try {
            return $source->getString($key, '');
        } catch (\InvalidArgumentException $exception) {
            throw new BadRequestHttpException(sprintf('The "%s" parameter must be a string.', $key), $exception);
        }
    }
}
