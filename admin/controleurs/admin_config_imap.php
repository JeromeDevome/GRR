<?php
/**
 * admin_config_imap.php
 * Interface permettant l'activation de la configuration de l'authentification pop/imap  
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2017-12-16 14:00$
 * @author    Laurent Delineau & JeromeB & Gilles Martin
 * @copyright Copyright 2003-2020 Team DEVOME - JeromeB
 * @link      http://www.gnu.org/licenses/licenses.html
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */

$grr_script_name = "admin_config_imap.php";

if (isset($_POST['imap_statut']))
{
	VerifyModeDemo();

	if (!Settings::set("imap_statut", $_POST['imap_statut']))
		echo encode_message_utf8("Erreur lors de l'enregistrement de imap_statut !<br />");

	if (!Settings::set("imap_domaine", $_POST['imap_domaine']))
		echo encode_message_utf8("Erreur lors de l'enregistrement de imap_domaine !<br />");

	if (!Settings::set("imap_adresse", $_POST['imap_adresse']))
		echo encode_message_utf8("Erreur lors de l'enregistrement de imap_adresse !<br />");

	if (!Settings::set("imap_port", $_POST['imap_port']))
		echo encode_message_utf8("Erreur lors de l'enregistrement de imap_port !<br />");

	if (!Settings::set("imap_type", $_POST['imap_type']))
		echo encode_message_utf8("Erreur lors de l'enregistrement de imap_type !<br />");

	if (!Settings::set("imap_ssl", $_POST['imap_ssl']))
		echo encode_message_utf8("Erreur lors de l'enregistrement de imap_ssl !<br />");

	if (!Settings::set("imap_cert", $_POST['imap_cert']))
		echo encode_message_utf8("Erreur lors de l'enregistrement de imap_cert !<br />");

	if (!Settings::set("imap_tls", $_POST['imap_tls']))
		echo encode_message_utf8("Erreur lors de l'enregistrement de imap_tls !<br />");
}

if ((isset($imap_restrictions)) && ($imap_restrictions == true))
{
	showAccessDenied($back);
	exit();
}
if (authGetUserLevel(getUserName(),-1) < 5)
{
	showAccessDenied($back);
	exit();
}

$AllSettings = Settings::getAll();

get_vocab_admin("statut_visitor");
get_vocab_admin("statut_user");

get_vocab_admin("save");


	if (!(function_exists("imap_open")))
		$trad['dCompatibiliteServeur'] = "Les fonctions liées à l'authentification IMAP/POP ne sont pas activées sur votre serveur PHP. La configuration IMAP/POP est donc actuellement impossible.";

	if (Settings::get("imap_statut") != '' && Settings::get("imap_adresse") != '' && Settings::get("imap_port") != '')
	{
		if ((isset($_POST['Valider2'])) && $_POST['Valider2'] == "Test")
			grr_connect_imap(Settings::get("imap_adresse"),Settings::get("imap_port"),$_POST['imap_login'],$_POST['imap_password'],Settings::get("imap_type"),Settings::get("imap_ssl"),Settings::get("imap_cert"),Settings::get("imap_tls"),"diag");
	}


echo $twig->render('admin_config_imap.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings));
?>