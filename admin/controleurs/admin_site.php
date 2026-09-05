<?php
/**
 * admin_site.php
 * Interface d'accueil de Gestion des sites de l'application GRR
 * Dernière modification : $Date: 2026-02-08 15:57$
 * @author    JeromeB & Laurent Delineau & Marc-Henri PAMISEUX
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
$grr_script_name = 'admin_site.php';

// Accès à la page
if (SecuAccess::UserLevel(getUserName(), -1, 'site') < 4)
{
	showAccessDenied($back);
	exit();
}

include_once('modeles/site.class.php');

// les variables attendues et leur type
$form_vars = array(
    'p_idsite' => 'int',
	'p_action' => 'int', // 1 : create, 2 : read, 3 : update, 4 : delete, 5 : check_right
	'p_submit' => 'int',
);
// récupération des valeurs des variables passées en paramètres
foreach($form_vars as $var => $var_type)
    $$var = SecuChaine::GetFormVarSecure($var, $var_type);

$trad['TitrePage']	= $trad['admin_site'];
$d['action']		= $p_action;
$d['idsite']		= $p_idsite;


switch($p_action)
{
	case 1:
		$trad['SousTitrePage']	= $trad['addsite'];
		$site = array();
		Adm_Site::create_site($p_idsite);
		echo $twig->render('admin_site_modif.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings, 'site' => $site));
		break;
	case 2:
		$sites = Adm_Site::read_sites();
		echo $twig->render('admin_site.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings, 'sites' => $sites));
		break;
	case 3:
		$trad['SousTitrePage']	= $trad['modifier_site'];
		$site = Adm_Site::update_site($p_idsite);
		echo $twig->render('admin_site_modif.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings, 'site' => $site));
		break;
	case 4:
		Adm_Site::delete_site($p_idsite);
		break;
	case 5:
		Adm_Site::check_right($p_idsite);
		break;
	default:
		$sites = Adm_Site::read_sites();
		echo $twig->render('admin_site.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings, 'sites' => $sites));
		break;
}

?>