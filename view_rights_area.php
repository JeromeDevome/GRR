<?php
/**
 * view_rights_area.php
 * Liste des privilèges d'un domaine
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2024-02-22 12:02$
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
$grr_script_name = "view_rights_area.php";

include "include/connect.inc.php";
include "include/config.inc.php";
include_once('include/misc.inc.php');
include "include/$dbsys.inc.php";
include "include/mrbs_sql.inc.php";
include "include/functions.inc.php";

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
$area_id = isset($_GET["area_id"]) ? intval($_GET["area_id"]) : NULL;
if (authGetUserLevel(getUserName(),$area_id,"area") < 4)
{
	showAccessDenied($back);
	exit();
}
// les propriétés du domaine
$res = grr_sql_query("SELECT * FROM ".TABLE_PREFIX."_area WHERE id='".$area_id."'");
if (!$res)
	fatal_error(0, get_vocab('error_room') . $area_id . get_vocab('not_found'));
$Area = grr_sql_row_keyed($res, 0);
grr_sql_free($res);

// utilisateurs ayant des privilèges sur cette ressource
$a_privileges = 'n';
	// on teste si des utilateurs administrent le domaine
$req_admin = "SELECT u.login, u.nom, u.prenom, u.etat FROM ".TABLE_PREFIX."_utilisateurs u left join ".TABLE_PREFIX."_j_useradmin_area j on u.login=j.login WHERE j.id_area =? ORDER BY u.nom, u.prenom";
$res_admin = grr_sql_query($req_admin,"i",[$area_id]);
$is_admin = '';
if ($res_admin)
{
	foreach($res_admin as $row)
	{
		$is_admin .= $row["nom"]." ".$row["prenom"]." (".$row["login"].")";
		if ($row["etat"] == 'inactif')
			$is_admin .= "<b> -> ".get_vocab("no_activ_user")."</b>";
		$is_admin .= "<br />";
	}
}
grr_sql_free($res_admin);
if ($is_admin != '')
	$a_privileges = 'y';
// Si le domaine est restreint, on teste si des utilateurs y ont accès
if ($Area["access"] == 'r')
{
	$req_restreint = "SELECT u.login, u.nom, u.prenom, u.etat  FROM ".TABLE_PREFIX."_utilisateurs u JOIN ".TABLE_PREFIX."_j_user_area j ON u.login=j.login WHERE j.id_area =? ORDER BY u.nom, u.prenom";
	$res_restreint = grr_sql_query($req_restreint,"i",[$area_id]);
	$is_restreint = '';
	if ($res_restreint)
	{
		foreach($res_restreint as $row)
		{
			$is_restreint .= $row["nom"]." ".$row["prenom"]." (".$row["login"].")";
			if ($row["etat"] == 'inactif')
				$is_restreint .= "<b> -> ".get_vocab("no_activ_user")."</b>";
			$is_restreint .= "<br />";
		}
	}
    grr_sql_free($res_restreint);
	if ($is_restreint != '')
		$a_privileges = 'y';
}
// code html
echo start_page_wo_header(Settings::get("company").get_vocab("deux_points").get_vocab("mrbs"));
echo '<h3 style="text-align:center;">';
echo get_vocab("match_area").get_vocab("deux_points")." ".clean_input($Area["area_name"]);
if ($Area["access"] == 'r')
	echo " (<span class=\"avertissement\">".get_vocab("access")."</span>)";
echo "</h3>";
echo "\n<h2>".get_vocab('utilisateurs_ayant_privileges_sur_domaine')."</h2>";
if ($a_privileges == 'y')
{
    if($is_admin != ''){
        echo "\n<h3><b>".get_vocab('utilisateurs_administrateurs_domaine')."</b></h3>";
        echo $is_admin;
    }
    if($is_restreint != ''){
        echo "\n<h3>".get_vocab('utilisateurs_acces_restreint_domaine')."</h3>\n";
        echo "<p>".$is_restreint."</p>";
    }
}
elseif ($a_privileges == 'n')
	echo "<p>".get_vocab('aucun_utilisateur').".</p>";

end_page();
?>