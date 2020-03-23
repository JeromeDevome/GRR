<?php
/**
 * view_rights_area.php
 * Liste des privilèges d'un domaine
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2020-03-20 11:10$
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
$grr_script_name = "view_rights_area.php";

include "include/connect.inc.php";
include "include/config.inc.php";
include "include/functions.inc.php";
include "include/$dbsys.inc.php";
include_once('include/misc.inc.php');
include "include/mrbs_sql.inc.php";

// Settings
require_once("./include/settings.class.php");
//Chargement des valeurs de la table settingS
if (!Settings::load())
	die("Erreur chargement settings");
// Session related functions
require_once("./include/session.inc.php");
// Resume session
include "include/resume_session.php";
// Paramètres langage
include "include/language.inc.php";
if ((Settings::get("authentification_obli") == 0) && (getUserName() == ''))
	$type_session = "no_session";
else
	$type_session = "with_session";
$area_id = isset($_GET["area_id"]) ? $_GET["area_id"] : NULL;
if (isset($area_id))
	settype($area_id,"integer");
if (authGetUserLevel(getUserName(),$area_id,"area") < 4)
{
	showAccessDenied($back);
	exit();
}
echo start_page_wo_header(Settings::get("company").get_vocab("deux_points").get_vocab("mrbs"));
$res = grr_sql_query("SELECT * FROM ".TABLE_PREFIX."_area WHERE id='".$area_id."'");
if (!$res)
	fatal_error(0, get_vocab('error_room') . $id_room . get_vocab('not_found'));
$row = grr_sql_row_keyed($res, 0);
grr_sql_free($res);
echo '<h3 style="text-align:center;">';
echo get_vocab("match_area").get_vocab("deux_points")." ".clean_input($row["area_name"]);
$area_access = $row["access"];
if ($area_access == 'r')
	echo " (<span class=\"avertissement\">".get_vocab("access")."</span>)";
echo "</h3>";
// On affiche pour les administrateurs les utilisateurs ayant des privilèges sur cette ressource
echo "\n<h2>".get_vocab('utilisateurs ayant privileges sur domaine')."</h2>";
$a_privileges = 'n';
	// on teste si des utilateurs administrent le domaine
$req_admin = "SELECT u.login, u.nom, u.prenom, u.etat FROM ".TABLE_PREFIX."_utilisateurs u left join ".TABLE_PREFIX."_j_useradmin_area j on u.login=j.login WHERE j.id_area = '".$area_id."' ORDER BY u.nom, u.prenom";
$res_admin = grr_sql_query($req_admin);
$is_admin = '';
if ($res_admin)
{
	for ($j = 0; ($row_admin = grr_sql_row($res_admin, $j)); $j++)
	{
		$is_admin .= $row_admin[1]." ".$row_admin[2]." (".$row_admin[0].")";
		if ($row_admin[3] == 'inactif')
			$is_admin .= "<b> -> ".get_vocab("no_activ_user")."</b>";
		$is_admin .= "<br />";
	}
}
if ($is_admin != '')
{
	$a_privileges = 'y';
	echo "\n<h3><b>".get_vocab("utilisateurs administrateurs domaine")."</b></h3>";
	echo $is_admin;
}
// Si le domaine est restreint, on teste si des utilateurs y ont accès
if ($area_access == 'r')
{
	$req_restreint = "SELECT u.login, u.nom, u.prenom, u.etat  FROM ".TABLE_PREFIX."_utilisateurs u left join ".TABLE_PREFIX."_j_user_area j on u.login=j.login WHERE j.id_area = '".$area_id."' ORDER BY u.nom, u.prenom";
	$res_restreint = grr_sql_query($req_restreint);
	$is_restreint = '';
	if ($res_restreint)
	{
		for ($j = 0; ($row_restreint = grr_sql_row($res_restreint, $j)); $j++)
		{
			$is_restreint .= $row_restreint[1]." ".$row_restreint[2]." (".$row_restreint[0].")";
			if ($row_restreint[3] == 'inactif')
				$is_restreint .= "<b> -> ".get_vocab("no_activ_user")."</b>";
			$is_restreint .= "<br />";
		}
	}
	if ($is_restreint != '')
	{
		$a_privileges = 'y';
		echo "\n<h3>".get_vocab("utilisateurs acces restreint domaine")."</h3>\n";
		echo "<p>".$is_restreint."</p>";
	}
}
if ($a_privileges == 'n')
	echo "<p>".get_vocab("aucun autilisateur").".</p>";
//include "include/trailer.inc.php";
end_page();
?>
