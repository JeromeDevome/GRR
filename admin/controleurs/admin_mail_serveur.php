<?php
/**
 * admin_mail_serveur.php
 * Interface permettant à l'administrateur la configuration de certains paramètres généraux
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2026-05-03 21:00$
 * @author    JeromeB
 * @copyright Since 2003 Team DEVOME - JeromeB
 * @link      http://www.gnu.org/licenses/licenses.html
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */


$msg = "";
$trad = $vocab;

// Automatic mail
if (isset($_GET['automatic_mail']))
{
	if (!Settings::set("automatic_mail", $_GET['automatic_mail']))
		$msg .= "Erreur lors de l'enregistrement de automatic_mail !<br />";
}
if (isset($_GET['mail_serveur_from']))
{
	if (!Settings::set("mail_serveur_from", $_GET['mail_serveur_from']))
		$msg .= "Erreur lors de l'enregistrement de mail_serveur_from !<br />";
}
if (isset($_GET['grr_mail_method']))
{
	if (!Settings::set("grr_mail_method", $_GET['grr_mail_method']))
		$msg .= "Erreur lors de l'enregistrement de grr_mail_method !<br />";
}
if (isset($_GET['grr_mail_smtp']))
{
	if (!Settings::set("grr_mail_smtp", $_GET['grr_mail_smtp']))
		$msg .= "Erreur lors de l'enregistrement de grr_mail_smtp !<br />";
}
if (isset($_GET['grr_mail_Username']))
{
	if (!Settings::set("grr_mail_Username", $_GET['grr_mail_Username']))
		$msg .= "Erreur lors de l'enregistrement de grr_mail_Username !<br />";
}
if (isset($_GET['grr_mail_Password']))
{
	if (!Settings::set("grr_mail_Password", $_GET['grr_mail_Password']))
		$msg .= "Erreur lors de l'enregistrement de grr_mail_Password !<br />";
}

if (isset($_GET['grr_mail_from']))
{
	if (!Settings::set("grr_mail_from", $_GET['grr_mail_from']))
		$msg .= "Erreur lors de l'enregistrement de grr_mail_from !<br />";
}
if (isset($_GET['grr_mail_fromname']))
{
	if (!Settings::set("grr_mail_fromname", $_GET['grr_mail_fromname']))
		$msg .= "Erreur lors de l'enregistrement de grr_mail_fromname !<br />";
}
if (isset($_GET['smtp_secure']))
{
	if (!Settings::set("smtp_secure", $_GET['smtp_secure']))
		$msg .= "Erreur lors de l'enregistrement de smtp_secure !<br />";
}
if (isset($_GET['smtp_port']))
{
	if (!Settings::set("smtp_port", $_GET['smtp_port']))
		$msg .= "Erreur lors de l'enregistrement de smtp_port !<br />";
}
if (isset($_GET['smtp_allow_self_signed']))
{
	if (!Settings::set("smtp_allow_self_signed", $_GET['smtp_allow_self_signed']))
		$msg .= "Erreur lors de l'enregistrement de smtp_allow_self_signed !<br />";
}
if (isset($_GET['smtp_cafile']))
{
	if (!Settings::set("smtp_cafile", $_GET['smtp_cafile']))
		$msg .= "Erreur lors de l'enregistrement de smtp_cafile !<br />";
}
if (isset($_GET['smtp_verify_peer_name']))
{
	if (!Settings::set("smtp_verify_peer_name", $_GET['smtp_verify_peer_name']))
		$msg .= "Erreur lors de l'enregistrement de smtp_verify_peer_name !<br />";
}
if (isset($_GET['smtp_verify_peer']))
{
	if (!Settings::set("smtp_verify_peer", $_GET['smtp_verify_peer']))
		$msg .= "Erreur lors de l'enregistrement de smtp_verify_peer !<br />";
}
if (isset($_GET['smtp_verify_depth']))
{
	if (!Settings::set("smtp_verify_depth", $_GET['smtp_verify_depth']))
		$msg .= "Erreur lors de l'enregistrement de smtp_verify_depth !<br />";
}

// Si Email test renseigné on y envois un mail
if (isset($_GET['mail_test']) && !empty($_GET['mail_test']))
{
	require_once '../include/pages.class.php';
	require_once '../include/mail.class.php';
	if (!Pages::load())
		die('Erreur chargement pages');
	
	$templateMail = Pages::get('mails_test_'.$locale);
	$codes = ['%nomdusite%' => Settings::get('title_home_page'), '%nometablissement%' => Settings::get('company'),'%urlgrr%' =>  traite_grr_url("","y")];
	$sujetMail = str_replace(array_keys($codes), $codes, $templateMail[0]);
	$txtMail = str_replace(array_keys($codes), $codes, $templateMail[1]);
	
	$resultat_mail = Email::Envois($_GET['mail_test'], $sujetMail, $txtMail, Settings::get('grr_mail_from'), '', '', '', 'mails_test_'.$locale);
	if (!$resultat_mail['success']) {
		$msg .= "Erreur envoi mail de test: " . htmlspecialchars($resultat_mail['error']) . "<br />";
	} else {
		$msg .= "Mail de test envoyé avec succès<br />";
	}
}
if (isset($_GET['ok']))
{
	if (isset($_GET['grr_mail_Bcc']))
		$grr_mail_Bcc = "y";
	else
		$grr_mail_Bcc = "n";
	if (!Settings::set("grr_mail_Bcc", $grr_mail_Bcc))
		$msg .= "Erreur lors de l'enregistrement de grr_mail_Bcc !<br />";
}


if (!Settings::load())
	die("Erreur chargement settings");

// Si pas de problème, message de confirmation
if (isset($_GET['ok'])) {
    $_SESSION['displ_msg'] = 'yes';
    if ($msg == '') {
        $d['enregistrement'] = 1;
    } else{
        $d['enregistrement'] = $msg;
    }
}


// Affichage
$AllSettings = Settings::getAll();


$d['gMailExpediteur'] = $gMailExpediteur;

$trad['dFctMailRestriction'] = $fonction_mail_restrictions;

echo $twig->render($page.'.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings));

?>