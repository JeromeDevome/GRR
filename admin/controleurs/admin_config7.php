<?php
/**
 * admin_config7.php
 * Interface permettant à l'administrateur la configuration de certaines fonctionnalités
 * Ce script fait partie de l'application GRR.
 * Dernière modification : $Date: 2023-04-23 11:39$
 * @author    Laurent Delineau & JeromeB &  Bouteillier Nicolas & Yan Naessens
 * @copyright Copyright 2003-2023 Team DEVOME - JeromeB
 * @link      http://www.gnu.org/licenses/licenses.html
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */

require_once("../include/pages.class.php");


get_vocab_admin("admin_config1");
get_vocab_admin("admin_config2");
get_vocab_admin("admin_config3");
get_vocab_admin("admin_config4");
get_vocab_admin("admin_config5");
get_vocab_admin("admin_config6");
get_vocab_admin("admin_config7");

$msg = '';


if (!Pages::load()) {
    die('Erreur chargement pages');
}

if (isset($_POST['p'])) { // On a validé le formulaire
   

// Périodicité
    if(isset($_POST['periodicite']) && $_POST['periodicite'] = 'on')
        $fonctionPeriodite = 'y';
    else
        $fonctionPeriodite = 'n';

   if (!Settings::set('periodicite', $fonctionPeriodite))
        $msg .= "Erreur lors de l'enregistrement de periodicite !<br />";

// Gestion courrier
    if(isset($_POST['show_courrier']) && $_POST['show_courrier'] = 'on')
        $fonctionCourrier = 'y';
    else
        $fonctionCourrier = 'n';

    if (!Settings::set('show_courrier', $fonctionCourrier))
        $msg .= "Erreur lors de l'enregistrement de show_courrier !<br />";

// Echange de réservation
    if(isset($_POST['fct_echange_resa']) && $_POST['fct_echange_resa'] = 'on')
         $fonctionEchangeResa = 'y';
    else
        $fonctionEchangeResa = 'n';

    if (!Settings::set('fct_echange_resa', $fonctionEchangeResa))
    $msg .= "Erreur lors de l'enregistrement de fct_echange_resa !<br />";

// Formulaire de contact pour réservation 
    if (isset($_POST['mail_destinataire'])) {
        if (!Settings::set('mail_destinataire', $_POST['mail_destinataire']))
            $msg .= "Erreur lors de l'enregistrement de mail_destinataire !<br />";
    }

    if (isset($_POST['mail_etat_destinataire'])) {
        if (!Settings::set('mail_etat_destinataire', $_POST['mail_etat_destinataire']))
            $msg .= "Erreur lors de l'enregistrement de mail_etat_destinataire !<br />";
    }

    if (isset($_POST['mail_user_destinataire']))
        $mail_user_destinataire = "y";
    else
        $mail_user_destinataire = "n";
    if (!Settings::set("mail_user_destinataire", $mail_user_destinataire))
        $msg .= "Erreur lors de l'enregistrement de mail_user_destinataire !<br />";

    if (isset($_POST['mail_contact_resa_captcha']))
        $mail_contact_resa_captcha = "y";
    else
        $mail_contact_resa_captcha = "n";
    if (!Settings::set("mail_contact_resa_captcha", $mail_contact_resa_captcha))
        $msg .= "Erreur lors de l'enregistrement de mail_contact_resa_captcha !<br />";

// Demande de création de compte
    if(isset($_POST['fct_crea_cpt']) && $_POST['fct_crea_cpt'] = 'on')
        $fonctionCreaCompte = 'y';
    else
        $fonctionCreaCompte = 'n';
    
    if (!Settings::set('fct_crea_cpt', $fonctionCreaCompte))
        $msg .= "Erreur lors de l'enregistrement de fct_crea_cpt !<br />";

    if (!Settings::set('fct_crea_cpt_login', $_POST['fct_crea_cpt_login']))
        $msg .= "Erreur lors de l'enregistrement de fct_crea_cpt_login !<br />";

    if (!Settings::set('fct_crea_cpt_statut', $_POST['fct_crea_cpt_statut']))
        $msg .= "Erreur lors de l'enregistrement de fct_crea_cpt_statut !<br />";

    if (isset($_POST['fct_crea_cpt_captcha']))
        $fct_crea_cpt_captcha = "y";
    else
        $fct_crea_cpt_captcha = "n";
    if (!Settings::set("fct_crea_cpt_captcha", $fct_crea_cpt_captcha))
        $msg .= "Erreur lors de l'enregistrement de fct_crea_cpt_captcha !<br />";       
}

/* Page formaulaire contactresa */
if (isset($_POST['textecontactresa'])) {
	VerifyModeDemo();
    if (!Pages::set("contactresa", "contactresa", $_POST['textecontactresa']))
        $msg .= "Erreur lors de l'enregistrement du texte contactresa !<br />";
}

if (!Settings::load()) {
    die('Erreur chargement settings');
}
$AllSettings = Settings::getAll();

if (!Pages::load()) {
    die('Erreur chargement pages');
}
$d['contactresa'] = Pages::get('contactresa');

// Si pas de problème, message de confirmation
if (isset($_POST['ok'])) {
    $_SESSION['displ_msg'] = 'yes';
    if ($msg == '') {
        $d['enregistrement'] = 1;
    } else{
        $d['enregistrement'] = $msg;
    }
}
if ((isset($_GET['msg'])) && isset($_SESSION['displ_msg']) && ($_SESSION['displ_msg'] == 'yes')) {
    $msg = $_GET['msg'];
} else {
    $msg = '';
}



get_vocab_admin('periodicite_msg');
get_vocab_admin('courrier_msg');
get_vocab_admin('swapentry');

get_vocab_admin('display_mail_etat_destinataire');
get_vocab_admin('display_mail_etat_destinataire_1');
get_vocab_admin('display_mail_etat_destinataire_2');
get_vocab_admin('display_mail_etat_destinataire_3');
get_vocab_admin('display_mail_etat_destinataire_4');
get_vocab_admin('display_mail_destinataire');
get_vocab_admin('mail_user_destinataire');
get_vocab_admin('captcha_utiliser');

get_vocab_admin('YES');
get_vocab_admin('NO');
get_vocab_admin('save');
get_vocab_admin('message_records');


echo $twig->render($page.'.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings));

?>