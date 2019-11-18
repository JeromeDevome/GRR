<?php
/**
 * admin_config3.php
 * Interface permettant à l'administrateur la configuration de certains paramètres généraux
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2017-12-16 14:00$
 * @author    Laurent Delineau & JeromeB
 * @copyright Copyright 2003-2018 Team DEVOME - JeromeB
 * @link      http://www.gnu.org/licenses/licenses.html
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */


get_vocab_admin("admin_config1");
get_vocab_admin("admin_config2");
get_vocab_admin("admin_config3");
get_vocab_admin("admin_config4");
get_vocab_admin("admin_config5");
get_vocab_admin("admin_config6");

$msg = "";
// Automatic mail
if (isset($_GET['automatic_mail']))
{
	if (!Settings::set("automatic_mail", $_GET['automatic_mail']))
	{
		echo "Erreur lors de l'enregistrement de automatic_mail !<br />";
		die();
	}
}
//envoyer_email_avec_formulaire
if (isset($_GET['envoyer_email_avec_formulaire']))
{
	if (!Settings::set("envoyer_email_avec_formulaire", $_GET['envoyer_email_avec_formulaire']))
	{
		echo "Erreur lors de l'enregistrement de envoyer_email_avec_formulaire !<br />";
		die();
	}
}
// javascript_info_disabled
if (isset($_GET['javascript_info_disabled']))
{
	if (!Settings::set("javascript_info_disabled", $_GET['javascript_info_disabled']))
	{
		echo "Erreur lors de l'enregistrement de javascript_info_disabled !<br />";
		die();
	}
}
// javascript_info_admin_disabled
if (isset($_GET['javascript_info_admin_disabled']))
{
	if (!Settings::set("javascript_info_admin_disabled", $_GET['javascript_info_admin_disabled']))
	{
		echo "Erreur lors de l'enregistrement de javascript_info_admin_disabled !<br />";
		die();
	}
}
if (isset($_GET['grr_mail_method']))
{
	if (!Settings::set("grr_mail_method", $_GET['grr_mail_method']))
	{
		echo "Erreur lors de l'enregistrement de grr_mail_method !<br />";
		die();
	}
}
if (isset($_GET['grr_mail_smtp']))
{
	if (!Settings::set("grr_mail_smtp", $_GET['grr_mail_smtp']))
	{
		echo "Erreur lors de l'enregistrement de grr_mail_smtp !<br />";
		die();
	}
}
if (isset($_GET['grr_mail_Username']))
{
	if (!Settings::set("grr_mail_Username", $_GET['grr_mail_Username']))
	{
		echo "Erreur lors de l'enregistrement de grr_mail_Username !<br />";
		die();
	}
}
if (isset($_GET['grr_mail_Password']))
{
	if (!Settings::set("grr_mail_Password", $_GET['grr_mail_Password']))
	{
		echo "Erreur lors de l'enregistrement de grr_mail_Password !<br />";
		die();
	}
}

if (isset($_GET['grr_mail_from']))
{
	if (!Settings::set("grr_mail_from", $_GET['grr_mail_from']))
	{
		echo "Erreur lors de l'enregistrement de grr_mail_from !<br />";
		die();
	}
}
if (isset($_GET['grr_mail_fromname']))
{
	if (!Settings::set("grr_mail_fromname", $_GET['grr_mail_fromname']))
	{
		echo "Erreur lors de l'enregistrement de grr_mail_fromname !<br />";
		die();
	}
}
if (isset($_GET['smtp_secure']))
{
	if (!Settings::set("smtp_secure", $_GET['smtp_secure']))
	{
		echo "Erreur lors de l'enregistrement de smtp_secure !<br />";
		die();
	}
}
if (isset($_GET['smtp_port']))
{
	if (!Settings::set("smtp_port", $_GET['smtp_port']))
	{
		echo "Erreur lors de l'enregistrement de smtp_port !<br />";
		die();
	}
}
// Si Email test renseigné on y envois un mail
if (isset($_GET['mail_test']) && !empty($_GET['mail_test']))
{
	require_once '../include/mail.class.php';
	require_once '../phpmailer/PHPMailerAutoload.php';
	Email::Envois($_GET['mail_test'], 'Votre GRR', "Ceci est un test depuis l'administration de votre GRR.<br>Le mail est arrivé à destination.", Settings::get('grr_mail_from'), '', '');
}
if (isset($_GET['ok']))
{
	if (isset($_GET['grr_mail_Bcc']))
		$grr_mail_Bcc = "y";
	else
		$grr_mail_Bcc = "n";
	if (!Settings::set("grr_mail_Bcc", $grr_mail_Bcc))
	{
		echo "Erreur lors de l'enregistrement de grr_mail_Bcc !<br />";
		die();
	}
}

if (isset($_GET['verif_reservation_auto']))
{
	if (!Settings::set("verif_reservation_auto", $_GET['verif_reservation_auto']))
	{
		echo "Erreur lors de l'enregistrement de verif_reservation_auto !<br />";
		die();
	}
	if ($_GET['verif_reservation_auto'] == 0)
	{
		$_GET['motdepasse_verif_auto_grr'] = "";
		$_GET['chemin_complet_grr'] = "";
	}
}

if (isset($_GET['motdepasse_verif_auto_grr']))
{
	if (($_GET['verif_reservation_auto'] == 1) && ($_GET['motdepasse_verif_auto_grr'] == ""))
		$msg .= "l'exécution du script verif_auto_grr.php requiert un mot de passe !\\n";
	if (!Settings::set("motdepasse_verif_auto_grr", $_GET['motdepasse_verif_auto_grr']))
	{
		echo "Erreur lors de l'enregistrement de motdepasse_verif_auto_grr !<br />";
		die();
	}
}
if (isset($_GET['chemin_complet_grr']))
{
	if (!Settings::set("chemin_complet_grr", $_GET['chemin_complet_grr']))
	{
		echo "Erreur lors de l'enregistrement de chemin_complet_grr !<br />";
		die();
	}
}
if (!Settings::load())
	die("Erreur chargement settings");

if (isset($_GET['ok']))
{
	$msg = get_vocab("message_records");
	affiche_pop_up($msg,"admin");
}

// Affichage

$AllSettings = Settings::getAll();

get_vocab_admin("title_automatic_mail");
get_vocab_admin("warning_message_mail");
get_vocab_admin("explain_automatic_mail");
get_vocab_admin("admin_email_manager");
get_vocab_admin("mail_admin_off");
get_vocab_admin("admin_email_manager");

get_vocab_admin("configuration_liens_adresses");
get_vocab_admin("envoyer_email_avec_formulaire_oui");
get_vocab_admin("envoyer_email_avec_formulaire_non");

get_vocab_admin("Parametres_configuration_envoi_automatique_mails");
get_vocab_admin("Explications_des_Parametres_configuration_envoi_automatique_mails");
get_vocab_admin("methode_mail");
get_vocab_admin("methode_mail_desactive");
get_vocab_admin("methode_smtp");
get_vocab_admin("Explications_methode_smtp_1");
get_vocab_admin("utilisateur_smtp");
get_vocab_admin("pwd");
get_vocab_admin("Email_expediteur_messages_automatiques");
get_vocab_admin("Nom_expediteur_messages_automatiques");
get_vocab_admin("smtp_secure");
get_vocab_admin("smtp_port");
get_vocab_admin("mail_test");
get_vocab_admin("copie_cachee");

get_vocab_admin("javascript_info_disabled_msg");
get_vocab_admin("javascript_info_disabled0");
get_vocab_admin("javascript_info_disabled1");

get_vocab_admin("javascript_info_admin_disabled_msg");

get_vocab_admin("suppression_automatique_des_reservations");
get_vocab_admin("Explications_suppression_automatique_des_reservations");
get_vocab_admin("verif_reservation_auto0");
get_vocab_admin("verif_reservation_auto1");
get_vocab_admin("verif_reservation_auto2");
get_vocab_admin("verif_reservation_auto3");

get_vocab_admin("save");

$trad['dFctMailRestriction'] = $fonction_mail_restrictions;

echo $twig->render($page.'.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'trad' => $trad, 'settings' => $AllSettings));

?>