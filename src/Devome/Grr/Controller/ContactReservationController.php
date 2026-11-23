<?php

namespace Devome\Grr\Controller;

use Devome\Grr\Service\GrrApplicationContext;
use Email;
use Gregwar\Captcha\PhraseBuilder;
use Pages;
use SecuAccess;
use SecuChaine;
use Settings;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Twig\Environment;

final class ContactReservationController
{
    public function __construct(
        private readonly Environment $twig,
        private readonly GrrApplicationContext $applicationContext,
    ) {
    }

    public function index(Request $request): Response
    {
        if ($request->query->get('p') !== 'contactresa') {
            throw new NotFoundHttpException();
        }

        $bootstrap = $this->applicationContext->boot();
        $context = $bootstrap['context'];
        global $d, $vocab;
        $d = $context['d'] ?? [];
        $vocab = $context['vocab'] ?? [];

        require_once dirname(__DIR__, 4).'/include/pages.class.php';
        if (!Pages::load()) {
            throw new \RuntimeException('Unable to load the contact-reservation page.');
        }

        $page = Pages::get('contactresa');
        if (!is_array($page)) {
            throw new NotFoundHttpException('The contact-reservation page is not configured.');
        }
        $d['TitrePage'] = $page[0];
        $d['CtnPage'] = $page[1];
        $d['msgErreur'] = '';
        $d['msgOk'] = '';

        if ($request->request->has('nom')) {
            $values = [];
            foreach ([
                'nom', 'prenom', 'email', 'telephone', 'sujet', 'area', 'room',
                'start_day', 'start_month', 'start_year', 'duree', 'heure', 'minutes',
                'dureemin', 'captcha',
            ] as $key) {
                $values[$key] = $this->postString($request, $key);
            }

            $missingFields = '';
            foreach ([
                'nom' => 'Votre nom',
                'prenom' => 'Votre prénom',
                'email' => 'Votre adresse email',
                'sujet' => 'Le sujet de votre demande',
                'area' => 'Le domaine n’est pas rempli',
                'room' => 'Aucune salle de choisie',
                'start_day' => 'Aucun jour choisi',
                'start_month' => 'Aucun mois choisi',
                'start_year' => 'Aucune année choisie',
                'duree' => 'Aucune durée choisie',
            ] as $key => $label) {
                if ($values[$key] === '') {
                    $missingFields .= $label.'<br/>';
                }
            }

            $captchaError = false;
            if (Settings::get('mail_contact_resa_captcha') == 1) {
                if (
                    !isset($_SESSION['phrase'])
                    || !PhraseBuilder::comparePhrases((string) $_SESSION['phrase'], $values['captcha'])
                ) {
                    $d['msgErreur'] .= $vocab['captcha_incorrect'].'<br/>';
                    $captchaError = true;
                }
                unset($_SESSION['phrase']);
            }

            if ($missingFields !== '' || $captchaError) {
                $d['msgErreur'] = "Erreur. Les champs suivants doivent être obligatoirement remplis :<br/><br/>"
                    .$d['msgErreur'].$missingFields;
            } else {
                $areaId = filter_var($values['area'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
                if ($areaId === false) {
                    throw new BadRequestHttpException('The "area" field must be a positive integer.');
                }

                $message = '<html><head></head><body> Message de :'.$values['prenom'].' '.$values['nom'].'<br/>'
                    .'Email : '.$values['email'].'<br/>'
                    .'Téléphone : '.$values['telephone'].'<br/><br/>'
                    .'<b> Sujet de la réservation :'.$values['sujet'].'</b><br/><br/>';
                $areaName = grr_sql_query1(
                    'SELECT area_name FROM '.TABLE_PREFIX.'_area WHERE id='.(int) $areaId
                );
                $message .= 'Domaines : '.$areaName.'<br/> '
                    .'Salle : '.$values['room'].'<br/><br/>'
                    .'Date  :'.$values['start_day'].'/'.$values['start_month'].'/'.$values['start_year'].' <br/>'
                    .'Heure réservation  : '.$values['heure'].'h '.$values['minutes'].'min<br/>'
                    .'Durée de la réservation : '.$values['duree'].' h '.$values['dureemin']." \n</body></html>";

                require_once dirname(__DIR__, 4).'/include/mail.class.php';
                $subject = "Réservation d'une salle";
                $sender = $values['email'];
                Email::Envois((string) Settings::get('mail_destinataire'), $subject, $message, $sender, '', '');
                if (Settings::get('mail_user_destinataire')) {
                    Email::Envois($sender, $subject, $message, $sender, '', '');
                }
                $d['msgOk'] = 'Votre demande a bien été envoyée';
            }
        }

        if (($context['userConnecte'] ?? 'no_session') === 'with_session') {
            $result = grr_sql_query(
                'SELECT nom, prenom, email FROM '.TABLE_PREFIX.'_utilisateurs WHERE login=\''
                .SecuChaine::ProtectDataSql(getUserName()).'\''
            );
            if (!$result) {
                throw new \RuntimeException('Unable to load the current user profile.');
            }
            $user = grr_sql_row_keyed($result, 0);
            grr_sql_free($result);
            if ($user) {
                $d['prenomConnecte'] = $user['prenom'];
                $d['nomConnecte'] = $user['nom'];
                $d['emailConnecte'] = $user['email'];
            }
        }

        $domains = [];
        $result = grr_sql_query('SELECT id, area_name, resolution_area FROM '.TABLE_PREFIX.'_area ORDER BY area_name');
        if (!$result) {
            throw new \RuntimeException('Unable to load the domains for the contact-reservation form.');
        }
        for ($index = 0; ($domain = grr_sql_row_keyed($result, $index)); ++$index) {
            if (SecuAccess::UserArea(getUserName(), $domain['id']) == 1) {
                $domains[] = [
                    'id' => $domain['id'],
                    'nom' => $domain['area_name'],
                    'resolution' => $domain['resolution_area'],
                ];
            }
        }
        grr_sql_free($result);
        $d['jQuery_DatePicker'] = jQuery_DatePickerTwig('start_');

        return new Response(
            $this->twig->render('contactresa.twig', [
                'd' => $d,
                'settings' => $context['AllSettings'] ?? [],
                'domaineDispo' => $domains,
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
}
