<?php
/**
 * admin_config_sso.php
 * Interface permettant l'activation de la prise en compte d'un environnement SSO
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2017-12-18 22:00$
 * @author    Laurent Delineau & JeromeB & Yan Naessens
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

$grr_script_name = "admin_config_sso.php";

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
}


if ((authGetUserLevel(getUserName(), -1) < 6) && ($valid != 'yes'))
{
	showAccessDenied($back);
	exit();
}

$AllSettings = Settings::getAll();

get_vocab_admin("admin_config_sso");
get_vocab_admin("Ne_pas_activer_Service_sso");

get_vocab_admin("cas_serveur");
get_vocab_admin("cas_port");
get_vocab_admin("cas_racine");
get_vocab_admin("cas_proxy_explain");
get_vocab_admin("cas_proxy_server");
get_vocab_admin("cas_proxy_port");
get_vocab_admin("sso_environnement");

get_vocab_admin("autres_parametres_sso");
get_vocab_admin("sso_actif_cacher_lien_deconnecter");
get_vocab_admin("sso_IsNotAllowedModify");
get_vocab_admin("cacher_page_login");
get_vocab_admin("Url_portail_sso_explain");

get_vocab_admin("config_cas_title");
get_vocab_admin("CAS_SSO_explain");
get_vocab_admin("cas_version");
get_vocab_admin("Statut_par_defaut_utilisateurs_importes");
get_vocab_admin("choix_statut_CAS_SSO");
get_vocab_admin("statut_visitor");
get_vocab_admin("statut_user");
get_vocab_admin("sso_redirection_accueil_grr_text1");
get_vocab_admin("sso_redirection_accueil_grr_text2");
get_vocab_admin("sso_active_correspondance_profil_statut_text");
get_vocab_admin("sso_active_correspondance_profil_statut");

get_vocab_admin("config_lemon_title");
get_vocab_admin("lemon_SSO_explain");
get_vocab_admin("Statut_par_defaut_utilisateurs_importes");
get_vocab_admin("choix_statut_lemon_SSO");

get_vocab_admin("config_lasso_title");
get_vocab_admin("lasso_SSO_explain");
get_vocab_admin("choix_statut_lasso_SSO");

get_vocab_admin("config_http_title");
get_vocab_admin("http_SSO_explain");
get_vocab_admin("choix_statut_http_SSO");
get_vocab_admin("explain_champs_recherche");
get_vocab_admin("name");
get_vocab_admin("first_name");
get_vocab_admin("mail_user");
get_vocab_admin("Statut_pour_domaine_particulier");
get_vocab_admin("http_explain_statut_domaine");
get_vocab_admin("statut");

get_vocab_admin("save");

	echo $twig->render('admin_config_sso.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings));
?>