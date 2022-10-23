<?php
/**
 * admin_config4.php
 * Interface permettant à l'administrateur
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2017-12-16 14:00$
 * @author    Laurent Delineau & JeromeB
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

get_vocab_admin("admin_config1");
get_vocab_admin("admin_config2");
get_vocab_admin("admin_config3");
get_vocab_admin("admin_config4");
get_vocab_admin("admin_config5");
get_vocab_admin("admin_config6");

$msg = "";

if (isset($_GET['motdepasse_backup']))
{
	if (!Settings::set("motdepasse_backup", $_GET['motdepasse_backup']))
		$msg = "Erreur lors de l'enregistrement de motdepasse_backup !<br />";
}
if (isset($_GET['disable_login']))
{
	if (!Settings::set("disable_login", $_GET['disable_login']))
		$msg .= "Erreur lors de l'enregistrement de disable_login !<br />";
}
if (isset($_GET['url_disconnect']))
{
	if (!Settings::set("url_disconnect", $_GET['url_disconnect']))
		$msg .= "Erreur lors de l'enregistrement de url_disconnect ! <br />";
}
if (isset($_GET['redirection_https']))
{
	if (!Settings::set("redirection_https", $_GET['redirection_https']))
		$msg .= "Erreur lors de l'enregistrement de redirection_https ! <br />";
}
// Restriction iP
if (isset($_GET['ip_autorise']))
{
	if (!Settings::set("ip_autorise", $_GET['ip_autorise']))
		$msg .= "Erreur lors de l'enregistrement de ip_autorise !<br />";
}
// Heure de connexion
if (isset($_GET['horaireconnexionde']))
{
	if (!Settings::set("horaireconnexionde", $_GET['horaireconnexionde']))
		$msg .= "Erreur lors de l'enregistrement de horaireconnexionde !<br />";
}
if (isset($_GET['horaireconnexiona']))
{
	if (!Settings::set("horaireconnexiona", $_GET['horaireconnexiona']))
		$msg .= "Erreur lors de l'enregistrement de horaireconnexiona !<br />";
}
// Max session length
if (isset($_GET['sessionMaxLength']))
{
	settype($_GET['sessionMaxLength'], "integer");
	if ($_GET['sessionMaxLength'] < 1)
		$_GET['sessionMaxLength'] = 30;
	if (!Settings::set("sessionMaxLength", $_GET['sessionMaxLength']))
		$msg .= "Erreur lors de l'enregistrement de sessionMaxLength !<br />";
}
// pass_leng
if (isset($_GET['pass_leng']))
{
	settype($_GET['pass_leng'], "integer");
	if ($_GET['pass_leng'] < 1)
		$_GET['pass_leng'] = 1;
	if (!Settings::set("pass_leng", $_GET['pass_leng']))
		$msg .= "Erreur lors de l'enregistrement de pass_leng !<br />";
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

get_vocab_admin("admin_menu_various");
get_vocab_admin("redirection_https");
get_vocab_admin("Activer_log_email");
get_vocab_admin("YES");
get_vocab_admin("NO");

get_vocab_admin("title_ip_autorise");
get_vocab_admin("explain_ip_autorise");

get_vocab_admin("title_horaire_autorise");
get_vocab_admin("explain_horaire_autorise");

get_vocab_admin("title_session_max_length");
get_vocab_admin("session_max_length");
get_vocab_admin("explain_session_max_length");

get_vocab_admin("pwd");
get_vocab_admin("pass_leng_explain");

get_vocab_admin("Url_de_deconnexion");
get_vocab_admin("Url_de_deconnexion_explain");
get_vocab_admin("Url_de_deconnexion_explain2");

get_vocab_admin("save");
get_vocab_admin('message_records');

echo $twig->render($page.'.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings));

?>