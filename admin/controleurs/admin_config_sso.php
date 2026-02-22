<?php
/**
 * admin_config_sso.php
 * Interface permettant l'activation de la prise en compte d'un environnement SSO
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2023-06-30 18:12$
 * @author    Laurent Delineau & JeromeB & Yan Naessens
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

$grr_script_name = "admin_config_sso.php";

$trad = $vocab;

if ((isset($sso_restrictions)) && ($sso_restrictions==true))
{
	showAccessDenied($back);
	exit();
}
if (authGetUserLevel(getUserName(), -1) < 6)
{
	showAccessDenied($back);
	exit();
}
if (isset($_POST['valid']))
{
	VerifyModeDemo();

	if (!Settings::set("cas_serveur", $_POST['cas_serveur']))
		echo "Erreur lors de l'enregistrement de cas_serveur !<br />";
	if (!Settings::set("cas_port", $_POST['cas_port']))
		echo "Erreur lors de l'enregistrement de cas_port !<br />";
	if (!Settings::set("cas_racine", $_POST['cas_racine']))
		echo "Erreur lors de l'enregistrement de cas_racine !<br />";
    if (!Settings::set("cas_proxy_server", $_POST['cas_proxy_server']))
		echo "Erreur lors de l'enregistrement de cas_proxy_server !<br />";
    if (!Settings::set("cas_proxy_port", $_POST['cas_proxy_port']))
		echo "Erreur lors de l'enregistrement de cas_proxy_port !<br />";
	if (!Settings::set("cas_version", $_POST['cas_version']))
		echo "Erreur lors de l'enregistrement de cas_version !<br />";
	
	if (!isset($_POST['cacher_lien_deconnecter']))
		$cacher_lien_deconnecter = "n";
	else
		$cacher_lien_deconnecter = "y";
	if (!Settings::set("cacher_lien_deconnecter", $cacher_lien_deconnecter))
		echo "Erreur lors de l'enregistrement de cacher_lien_deconnecter !<br />";
	if (isset($_POST['Url_portail_sso']))
	{
		if (!Settings::set("Url_portail_sso", $_POST['Url_portail_sso']))
			echo "Erreur lors de l'enregistrement de Url_portail_sso ! <br />";
	}
	if ($_POST['sso_statut'] == "no_sso")
	{
		$req = grr_sql_query("delete from ".TABLE_PREFIX."_setting where NAME = 'sso_statut'");
		$grrSettings['sso_statut'] = '';
	}
	else
	{
		if (!Settings::set("sso_statut", $_POST['sso_statut']))
			echo "Erreur lors de l'enregistrement de sso_statut !<br />";
		$grrSettings['sso_statut'] = $_POST['sso_statut'];
	}
	if (!Settings::set("http_champ_email", $_POST['http_champ_email']))
		echo "Erreur lors de l'enregistrement de http_champ_email !<br />";
	$grrSettings['http_champ_email'] = $_POST['http_champ_email'];
	if (!Settings::set("http_champ_nom", $_POST['http_champ_nom']))
		echo "Erreur lors de l'enregistrement de http_champ_nom !<br />";
	$grrSettings['http_champ_nom'] = $_POST['http_champ_nom'];
	if (!Settings::set("http_champ_prenom", $_POST['http_champ_prenom']))
		echo "Erreur lors de l'enregistrement de http_champ_prenom !<br />";
	$grrSettings['http_champ_prenom'] = $_POST['http_champ_prenom'];
	if ($_POST['http_sso_domain'] == "")
		$_POST['http_sso_statut_domain'] = "";
	else 
	{
		if ((!isset($_POST['http_sso_statut_domain'])) || ($_POST['http_sso_statut_domain'] == ""))
			$_POST['http_sso_statut_domain'] = "visiteur";
	}
	if (!Settings::set("http_sso_domain", $_POST['http_sso_domain']))
		echo "Erreur lors de l'enregistrement de http_sso_domain !<br />";
	$grrSettings['http_sso_domain'] = $_POST['http_sso_domain'];
	if (isset($_POST['http_sso_domain']))
	{
		if (!Settings::set("http_sso_statut_domain", $_POST['http_sso_statut_domain']))
			echo "Erreur lors de l'enregistrement de http_sso_statut_domain !<br />";
		$grrSettings['http_sso_statut_domain'] = $_POST['http_sso_statut_domain'];
	}
	if (isset($_POST['Url_cacher_page_login']))
	{
		if (!Settings::set("Url_cacher_page_login", $_POST['Url_cacher_page_login']))
			echo "Erreur lors de l'enregistrement de Url_cacher_page_login ! <br />";
	}
	if (!isset($_POST['sso_IsNotAllowedModify']))
		$sso_IsNotAllowedModify = "n";
	else
		$sso_IsNotAllowedModify = "y";
	if (!Settings::set("sso_IsNotAllowedModify", $sso_IsNotAllowedModify))
		echo "Erreur lors de l'enregistrement de sso_IsNotAllowedModify !<br />";
	if (($_POST['sso_statut'] != "cas_visiteur") && ($_POST['sso_statut'] != "cas_utilisateur"))
		$sso_active_correspondance_profil_statut = "n";
	else
	{
		if (!isset($_POST['sso_active_correspondance_profil_statut']))
			$sso_active_correspondance_profil_statut = "n";
		else
			$sso_active_correspondance_profil_statut = "y";
	}
	if (!Settings::set("sso_ac_corr_profil_statut", $sso_active_correspondance_profil_statut))
		echo "Erreur lors de l'enregistrement de sso_active_correspondance_profil_statut !<br />";
	if (($_POST['sso_statut'] != "cas_visiteur") && ($_POST['sso_statut'] != "cas_utilisateur"))
		$sso_redirection_accueil_grr = "n";
	else
	{
		if (!isset($_POST['sso_redirection_accueil_grr']))
			$sso_redirection_accueil_grr = "n";
		else
			$sso_redirection_accueil_grr = "y";
	}
	if (!Settings::set("sso_redirection_accueil_grr", $sso_redirection_accueil_grr))
		echo "Erreur lors de l'enregistrement de sso_redirection_accueil_grr !<br />";

	//Prise en compte des champs d'attributs CAS
	if (!Settings::set("cas_nom", $_POST['cas_nom']))
		echo "Erreur lors de l'enregistrement de cas_nom !<br />";
	if (!Settings::set("cas_prenom", $_POST['cas_prenom']))
		echo "Erreur lors de l'enregistrement de cas_prenom !<br />";
	if (!Settings::set("cas_language", $_POST['cas_language']))
		echo "Erreur lors de l'enregistrement de cas_language !<br />";
	if (!Settings::set("cas_code_fonction", $_POST['cas_code_fonction']))
		echo "Erreur lors de l'enregistrement de cas_code_fonction !<br />";
	if (!Settings::set("cas_libelle_fonction", $_POST['cas_libelle_fonction']))
		echo "Erreur lors de l'enregistrement de cas_libelle_fonction !<br />";
	if (!Settings::set("cas_mail", $_POST['cas_mail']))
		echo "Erreur lors de l'enregistrement de cas_mail !<br />";

}


if ((authGetUserLevel(getUserName(), -1) < 6) && ($valid != 'yes'))
{
	showAccessDenied($back);
	exit();
}

$AllSettings = Settings::getAll();


	echo $twig->render('admin_config_sso.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings));
?>