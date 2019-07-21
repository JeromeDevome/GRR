<?php
/**
 * admin_config4.php
 * Interface permettant à l'administrateur
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

if (isset($_GET['motdepasse_backup']))
{
	if (!Settings::set("motdepasse_backup", $_GET['motdepasse_backup']))
	{
		echo "Erreur lors de l'enregistrement de motdepasse_backup !<br />";
		die();
	}
}
if (isset($_GET['disable_login']))
{
	if (!Settings::set("disable_login", $_GET['disable_login']))
	{
		echo "Erreur lors de l'enregistrement de disable_login !<br />";
		die();
	}
}
if (isset($_GET['url_disconnect']))
{
	if (!Settings::set("url_disconnect", $_GET['url_disconnect']))
		echo "Erreur lors de l'enregistrement de url_disconnect ! <br />";
}
if (isset($_GET['redirection_https']))
{
	if (!Settings::set("redirection_https", $_GET['redirection_https']))
		echo "Erreur lors de l'enregistrement de redirection_https ! <br />";
}
// Restriction iP
if (isset($_GET['ip_autorise']))
{
	if (!Settings::set("ip_autorise", $_GET['ip_autorise']))
		echo "Erreur lors de l'enregistrement de ip_autorise !<br />";
}
// Max session length
if (isset($_GET['sessionMaxLength']))
{
	settype($_GET['sessionMaxLength'], "integer");
	if ($_GET['sessionMaxLength'] < 1)
		$_GET['sessionMaxLength'] = 30;
	if (!Settings::set("sessionMaxLength", $_GET['sessionMaxLength']))
		echo "Erreur lors de l'enregistrement de sessionMaxLength !<br />";
}
// pass_leng
if (isset($_GET['pass_leng']))
{
	settype($_GET['pass_leng'], "integer");
	if ($_GET['pass_leng'] < 1)
		$_GET['pass_leng'] = 1;
	if (!Settings::set("pass_leng", $_GET['pass_leng']))
		echo "Erreur lors de l'enregistrement de pass_leng !<br />";
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

$trad['dDbSys'] = $dbsys;
$trad['dRestaureBBD'] = $restaureBBD;

get_vocab_admin("title_backup");
get_vocab_admin("explain_backup");
get_vocab_admin("warning_message_backup");
get_vocab_admin("submit_backup");

get_vocab_admin("Restauration_de_la_base_GRR");
get_vocab_admin("Restaurer_la_sauvegarde");
get_vocab_admin("Restauration_de_la_base_GRR");

get_vocab_admin("execution_automatique_backup");
get_vocab_admin("execution_automatique_backup_explications");
get_vocab_admin("execution_automatique_backup_mdp");

get_vocab_admin("title_disable_login");
get_vocab_admin("explain_disable_login");
get_vocab_admin("disable_login_on");
get_vocab_admin("disable_login_off");

get_vocab_admin("redirection_https");
get_vocab_admin("YES");
get_vocab_admin("NO");

get_vocab_admin("title_ip_autorise");
get_vocab_admin("explain_ip_autorise");

get_vocab_admin("title_session_max_length");
get_vocab_admin("session_max_length");
get_vocab_admin("explain_session_max_length");

get_vocab_admin("pwd");
get_vocab_admin("pass_leng_explain");

get_vocab_admin("Url_de_deconnexion");
get_vocab_admin("Url_de_deconnexion_explain");
get_vocab_admin("Url_de_deconnexion_explain2");

get_vocab_admin("save");


?>