<?php
/**
 * admin_col_gauche.php
 * colonne de gauche des écrans d'administration des sites, des domaines et des ressources de l'application GRR
 * Dernière modification : $Date: 2018-07-22 13:30$
 * @author    JeromeB & Laurent Delineau & Marc-Henri PAMISEUX
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


function afficheLienNiveau1($lien, $image, $niveau = 1)
{
	global $twig, $page, $menuAdminT;

	if($page == $lien){
		$classActive = " active";
	} else{
		$classActive = "";
	}

	$menuAdminT[] = array('niveau' => 1, 'nom' => get_vocab($lien), 'lien' => '?p='.$lien, 'classLi' => $classActive, 'image' => $image);
}

function afficheLienNiveau2($nomSection,$image,$liste,$iN2)
{
	global $chaine, $menuAdminT, $menuAdminTN2, $page;

	$classLi = "";
	$classA = "";

	if (count($liste) > 0)
	{
		foreach ($liste as $key){
			$classALien = "";
			if($page == $key){
				$classLi = " menu-open";
				$classA = " active";
				$classALien = " active";
			}
			$menuAdminTN2[] = array('niveau' => 2, 'niveau1' => $iN2, 'nom' => get_vocab($key), 'lien' => '?p='.$key, 'classLi' => $classALien);
		}
		unset($liste);

		$menuAdminT[] = array('niveau' => 2, 'niveau1' => $iN2, 'nom' => get_vocab($nomSection), 'lien' => '', 'classLi' => $classLi, 'classA' => $classA, 'image' => $image);
	}
}

if (get_request_uri() != ''){
	//$url_ = parse_url(get_request_uri());
	//$pos = strrpos($url_['path'], "/") + 1;
	//$chaine = substr($url_['path'], $pos);
} else {
	$chaine = '';
}

//Construction du menu
$iN2 = 0;
$liste = array();
if (authGetUserLevel(getUserName(), -1, 'area') >= 4)
	afficheLienNiveau1('admin_accueil', 'fa fa-tachometer-alt', 1);
if (authGetUserLevel(getUserName(), -1, 'area') >= 6)
	afficheLienNiveau1('admin_config', 'fa fa-cogs', 1);
if (authGetUserLevel(getUserName(), -1, 'area') >= 6)
	afficheLienNiveau1('admin_type', 'fa fa-tags', 1);
if (authGetUserLevel(getUserName(), -1, 'area') >= 6)
	afficheLienNiveau1('admin_calend_ignore', 'fa fa-calendar-times', 1);
if ((authGetUserLevel(getUserName(), -1, 'area') >= 6) && (Settings::get('show_holidays') == 'Oui'))
	afficheLienNiveau1('admin_calend_vacances_feries', 'fa fa-calendar-minus', 1);
if ((authGetUserLevel(getUserName(), -1, 'area') >= 6) && (Settings::get("jours_cycles_actif") == "Oui"))
	afficheLienNiveau1('admin_calend_jour_cycle1', 'fa fa-redo ', 1);


if ((authGetUserLevel(getUserName(), -1, 'area') >= 6) && (Settings::get("module_multisite") == "Oui"))
	afficheLienNiveau1('admin_site', 'fa fa-building', 1);
if (authGetUserLevel(getUserName(), -1, 'area') >= 4)
	afficheLienNiveau1('admin_room', 'fa fa-folder', 1);
if (authGetUserLevel(getUserName(), -1, 'area') >= 4)
	afficheLienNiveau1('admin_overload', 'fa fa-object-group', 1);


// Utilisateurs
if ((authGetUserLevel(getUserName(), -1, 'area') >= 6) || (authGetUserLevel(getUserName(), -1, 'user') == 1))
	$liste[] = 'admin_user';
if ((authGetUserLevel(getUserName(), -1, 'area') >= 6) || (authGetUserLevel(getUserName(), -1, 'user') == 1))
	$liste[] = 'admin_groupe';
if ((Settings::get("module_multisite") == "Oui") && (authGetUserLevel(getUserName(), -1, 'area') >= 6))
	$liste[] = 'admin_admin_site';
if (authGetUserLevel(getUserName(), -1, 'area') >= 6)
	$liste[] = 'admin_right_admin';
if (authGetUserLevel(getUserName(), -1, 'area') >= 4)
	$liste[] = 'admin_access_area';
if (authGetUserLevel(getUserName(), -1, 'area') >= 4)
	$liste[] = 'admin_right';
if ( (Settings::get("sso_ac_corr_profil_statut") == 'y') && (authGetUserLevel(getUserName(), -1, 'area') >= 5) )
	$liste[] = 'admin_corresp_statut';

afficheLienNiveau2("admin_menu_user", "fa fa-users",$liste,$iN2++);


// Divers
$liste = array();
if (authGetUserLevel(getUserName(), -1, 'area') >= 4)
	$liste[] = 'admin_email_manager';
if (authGetUserLevel(getUserName(), -1, 'area') >= 6)
	$liste[] = 'admin_view_connexions';
if (authGetUserLevel(getUserName(), -1, 'area') >= 6)
	$liste[] = 'admin_view_emails';
if (authGetUserLevel(getUserName(), -1, 'area') >= 6)
	$liste[] = 'admin_log_resa_liste';
if (authGetUserLevel(getUserName(), -1, 'area') >= 4)
	$liste[] = 'admin_calend';
if (authGetUserLevel(getUserName(), -1, 'area') >= 6)
	$liste[] = 'admin_cgu';
if (authGetUserLevel(getUserName(), -1, 'area') >= 6)
	$liste[] = 'admin_couleurs';
if (authGetUserLevel(getUserName(), -1, 'area') >= 6)
	$liste[] = 'admin_infos';

afficheLienNiveau2("admin_menu_various", "fa fa-database",$liste,$iN2++);


// Connexion externe
$liste = array();
if ( (authGetUserLevel(getUserName(), -1, 'area') >= 6) && ((!isset($sso_restrictions)) || ($ldap_restrictions == false)) )
	$liste[] = 'admin_config_ldap';
if ( (authGetUserLevel(getUserName(), -1, 'area') >= 6) && ((!isset($sso_restrictions)) || ($sso_restrictions == false)) )
	$liste[] = 'admin_config_sso';
if ( (authGetUserLevel(getUserName(), -1, 'area') >= 6) && ((!isset($sso_restrictions)) || ($imap_restrictions == false)) )
	$liste[] = 'admin_config_imap';

afficheLienNiveau2("admin_menu_connexion_externe", "fa fa-sign-out-alt",$liste,$iN2++);
?>