<?php
/**
 * admin_config4.php
 * Interface permettant à l'administrateur
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2017-12-16 14:00$
 * @author    Laurent Delineau & JeromeB
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

get_vocab_admin("admin_config1");
get_vocab_admin("admin_config2");
get_vocab_admin("admin_config3");
get_vocab_admin("admin_config4");
get_vocab_admin("admin_config5");
get_vocab_admin("admin_config6");
get_vocab_admin("admin_config7");

$msg = "";
$trad = $vocab;

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
	$ctrlIp = true;
	if($_GET['ip_autorise'] != "")
		$ctrlIp = valide_ip_adr($_GET['ip_autorise']);

	if ($ctrlIp == false || !Settings::set("ip_autorise", $_GET['ip_autorise']))
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
if (isset($_GET['pass_leng'])) {
	settype($_GET['pass_leng'], "integer");
	settype($_GET['pass_nb_min'], "integer");
	settype($_GET['pass_nb_maj'], "integer");
	settype($_GET['pass_nb_ch'], "integer");
	settype($_GET['pass_nb_sp'], "integer");

	if($_GET['pass_leng'] >= ($_GET['pass_nb_min'] + $_GET['pass_nb_maj'] + $_GET['pass_nb_ch'] + $_GET['pass_nb_sp']))
	{
		if (isset($_GET['pass_leng']))
		{
			if ($_GET['pass_leng'] < 1)
				$_GET['pass_leng'] = 1;
			if (!Settings::set("pass_leng", $_GET['pass_leng']))
				$msg .= "Erreur lors de l'enregistrement de pass_leng !<br />";
		}
		// pass_nb_min
		if (isset($_GET['pass_nb_min']))
		{
			if (!Settings::set("pass_nb_min", $_GET['pass_nb_min']))
				$msg .= "Erreur lors de l'enregistrement de pass_nb_min !<br />";
		}
		// pass_nb_maj
		if (isset($_GET['pass_nb_maj']))
		{
			if (!Settings::set("pass_nb_maj", $_GET['pass_nb_maj']))
				$msg .= "Erreur lors de l'enregistrement de pass_nb_maj !<br />";
		}
		// pass_nb_ch
		if (isset($_GET['pass_nb_ch']))
		{
			if (!Settings::set("pass_nb_ch", $_GET['pass_nb_ch']))
				$msg .= "Erreur lors de l'enregistrement de pass_nb_ch !<br />";
		}
		// pass_nb_sp
		if (isset($_GET['pass_nb_sp']))
		{
			if (!Settings::set("pass_nb_sp", $_GET['pass_nb_sp']))
				$msg .= "Erreur lors de l'enregistrement de pass_nb_sp !<br />";
		}
	} else {
		$msg .= $trad['pass_leng_error']."<br />";
	}
}

// Log des mails
if (isset($_GET['log_mail']))
{
	if (!Settings::set("log_mail", $_GET['log_mail']))
		$msg .= "Erreur lors de l'enregistrement de log_mail !<br />";
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

echo $twig->render($page.'.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings));

?>