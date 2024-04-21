<?php
/**
 * login.php
 * interface de connexion
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2024-04-20 18:20$
 * @author    Laurent Delineau & JeromeB & Yan Naessens
 * @copyright Copyright 2003-2024 Team DEVOME - JeromeB
 * @link      http://www.gnu.org/licenses/licenses.html
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */

$grr_script_name = 'login.php';

$trad = $vocab;

if (isset($_GET['url']))
	$d['url'] = rawurlencode($_GET['url']);

if(Settings::get("redirection_https") == "yes"){
	if(!isset($_SERVER["HTTPS"]) || $_SERVER["HTTPS"] != "on")
	{
		header("Location: https://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"], true, 301);
		exit;
	}
}

// Vérification du numéro de version et renvoi automatique vers la page de mise à jour
if (verif_version())
{
	header("Location: ./installation/maj.php");
	exit();
}

// Si Token  n'est pas initialisé on le fait ici car s'est la 1ere page affiché 
if(Settings::get("tokenprivee") == "")
	Settings::set("tokenprivee",  generationToken());

if(Settings::get("tokenpublic") == "")
	Settings::set("tokenpublic",  generationToken());

if(Settings::get("tokenapi") == "")
	Settings::set("tokenapi",  generationToken());

if(Settings::get("tokenuser") == "")
	Settings::set("tokenuser",  generationToken());

// User wants to be authentified
if (isset($_POST['login']) && isset($_POST['password']))
{
	// Détruit toutes les variables de session au cas où une session existait auparavant
	$_SESSION = array();
	$result = grr_opensession($_POST['login'], unslashes($_POST['password']));
	// On écrit les données de session et ferme la session
	session_write_close();
	if ($result=="2")
	{
		$d['messageLogin'] = get_vocab("echec_connexion_GRR");
		$d['messageLogin'] .= " ".get_vocab("wrong_pwd");
	}
	else if ($result == "3")
	{
		$d['messageLogin'] = get_vocab("echec_connexion_GRR");
		$d['messageLogin'] .= "<br />". get_vocab("importation_impossible");
	}
	else if ($result == "4")
	{
		//$d['messageLogin'] = get_vocab("importation_impossible");
		$d['messageLogin'] = get_vocab("echec_connexion_GRR");
		$d['messageLogin'] .= " ".get_vocab("causes_possibles");
		$d['messageLogin'] .= "<br />- ".get_vocab("wrong_pwd");
		$d['messageLogin'] .= "<br />- ". get_vocab("echec_authentification_ldap");
	}
	else if ($result == "5")
	{
		$d['messageLogin'] = get_vocab("echec_connexion_GRR");
		$d['messageLogin'] .= "<br />". get_vocab("connexion_a_grr_non_autorisee");
	}
	else if ($result == "6")
	{
		$d['messageLogin'] = get_vocab("echec_connexion_GRR");
		$d['messageLogin'] .= "<br />". get_vocab("connexion_a_grr_non_autorisee");
		$d['messageLogin'] .= "<br />". get_vocab("format_identifiant_incorrect");
	}
	else if ($result == "7")
	{
		$d['messageLogin'] = get_vocab("echec_connexion_GRR");
		$d['messageLogin'] .= "<br />". get_vocab("connexion_a_grr_non_autorisee");
		$d['messageLogin'] .= "<br />". get_vocab("echec_authentification_ldap");
		$d['messageLogin'] .= "<br />". get_vocab("ldap_chemin_invalide");
	}
	else if ($result == "8")
	{
		$d['messageLogin'] = get_vocab("echec_connexion_GRR");
		$d['messageLogin'] .= "<br />". get_vocab("connexion_a_grr_non_autorisee");
		$d['messageLogin'] .= "<br />". get_vocab("echec_authentification_ldap");
		$d['messageLogin'] .= "<br />". get_vocab("ldap_recherche_identifiant_aucun_resultats");
	}
	else if ($result == "9")
	{
		$d['messageLogin'] = get_vocab("echec_connexion_GRR");
		$d['messageLogin'] .= "<br />". get_vocab("connexion_a_grr_non_autorisee");
		$d['messageLogin'] .= "<br />". get_vocab("echec_authentification_ldap");
		$d['messageLogin'] .= "<br />". get_vocab("ldap_doublon_identifiant");
	}
	else if ($result == "10")
	{
		$d['messageLogin'] = get_vocab("echec_connexion_GRR");
		$d['messageLogin'] .= "<br />". get_vocab("connexion_a_grr_non_autorisee");
		$d['messageLogin'] .= "<br />". get_vocab("echec_authentification_imap");
	}
	else if ($result == "11")
	{

	}
	else if ($result == "12")
	{
		header("Location: ./changepwd.php");
	}
	else if ($result == "13")
	{
		$d['messageLogin'] = get_vocab("echec_connexion_GRR");
		$d['messageLogin'] .= "<br />". get_vocab("echec_authentification_horaire")." ".Settings::get("horaireconnexionde")." - ".Settings::get("horaireconnexiona");
	}
	else // la session est ouverte
	{
        // si c'est un administrateur qui se connecte, on efface les données anciennes du journal
        nettoieLogConnexion($nbMaxJoursLogConnexion);
		nettoieLogEmail($nbMaxJoursLogEmail);
		if (isset($_POST['url']))
		{
			$url=rawurldecode($_POST['url']);
			header("Location: ".$url);
			die();
		}
		else
		{
			header("Location: ./".htmlspecialchars_decode(page_accueil())."");
			die();
		}
	}
}

// Dans le cas d'une démo, on met à jour la base une fois par jour.
MajMysqlModeDemo();

//si on a interdit l'acces a la page login
if ((Settings::get("Url_cacher_page_login") != "") && ((!isset($sso_super_admin)) || ($sso_super_admin == false)) && (!isset($_GET["local"])))
	header("Location: ./index.php");

$nom_picture = "./personnalisation/".$gcDossierImg."/logos/".Settings::get("logo");
if ((Settings::get("logo") != '') && (@file_exists($nom_picture)))
	$d['logo'] = $nom_picture;

// HOOK
$resulHook = Hook::Appel("hookLienConnexion1");
$d['hookLienConnexion1'] = $resulHook['hookLienConnexion1'];

if (Settings::get("webmaster_email") != "")
{
	$lien = affiche_lien_contact("contact_administrateur","identifiant:non","seulement_si_email");
	if ($lien != "")
		$d['contactAdmin'] = "[".$lien."] ";
}

$d['lienGRR'] = $grr_devel_url;

echo $twig->render('login1.twig', array('trad' => $trad, 'd' => $d, 'settings' => $AllSettings));
?>