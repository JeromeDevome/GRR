<?php
/**
 * vuedroitsdomaine.php
 * Liste des privilèges d'un domaine
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2023-12-29 17:00$
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

 $grr_script_name = "vuedroitsdomaine.php";

 $trad = $vocab;

if ((Settings::get("authentification_obli") == 0) && (getUserName() == ''))
	$type_session = "no_session";
else
	$type_session = "with_session";
$area_id = isset($_GET["area_id"]) ? intval($_GET["area_id"]) : NULL;
if (isset($area_id))
	settype($area_id,"integer");

if (authGetUserLevel(getUserName(),$area_id,"area") < 4)
{
	showAccessDenied($back);
	exit();
}

$res = grr_sql_query("SELECT * FROM ".TABLE_PREFIX."_area WHERE id='".$area_id."'");
if (!$res)
	fatal_error(0, get_vocab('error_room') . $id_room . get_vocab('not_found'));
$domaine = grr_sql_row_keyed($res, 0);
grr_sql_free($res);

$adminDomaine = array();
$accesDomaine = array();

// 1: On teste si des utilateurs administre le domaine
$req_admin = "SELECT u.login, u.nom, u.prenom, u.etat FROM ".TABLE_PREFIX."_utilisateurs u left join ".TABLE_PREFIX."_j_useradmin_area j on u.login=j.login WHERE j.id_area = '".$area_id."' ORDER BY u.nom, u.prenom";
$res_admin = grr_sql_query($req_admin);

if ($res_admin)
{
	for ($j = 0; ($domaine_admin = grr_sql_row($res_admin, $j)); $j++)
	{
		$adminDomaine[] = array('login' => $domaine_admin[0], 'nom' => $domaine_admin[1], 'prenom' => $domaine_admin[2], 'etat' => $domaine_admin[3]);
	}
}

// 2: Si le domaine est restreint, on teste si des utilateurs y ont accès
if ($domaine['access'] == 'r')
{
	$req_restreint = "SELECT u.login, u.nom, u.prenom, u.etat  FROM ".TABLE_PREFIX."_utilisateurs u left join ".TABLE_PREFIX."_j_user_area j on u.login=j.login WHERE j.id_area = '".$area_id."' ORDER BY u.nom, u.prenom";
	$res_restreint = grr_sql_query($req_restreint);
	if ($res_restreint)
	{
		for ($j = 0; ($domaine_restreint = grr_sql_row($res_restreint, $j)); $j++)
		{
			$accesDomaine[] = array('login' => $domaine_restreint[0], 'nom' => $domaine_restreint[1], 'prenom' => $domaine_restreint[2], 'etat' => $domaine_restreint[3]);
		}
	}
}

echo $twig->render('vuedroitsdomaine.twig', array('trad' => $trad, 'd' => $d, 'settings' => $AllSettings, 'domaine' => $domaine, 'admindomaine' => $adminDomaine, 'accesdomaine' => $accesDomaine));
?>