<?php
/**
 * view_rights_site.php
 * Liste des privilèges d'un domaine
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2020-04-09 16:00$
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
$grr_script_name = "view_rights_site.php";

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
// contrôle d'accès
$site_id = isset($_GET["site_id"]) ? intval(clean_input($_GET["site_id"])) : NULL;
if (authGetUserLevel(getUserName(),$site_id,"site") < 5)
{
	showAccessDenied($back);
	exit();
}
$res = grr_sql_query("SELECT * FROM ".TABLE_PREFIX."_site WHERE id='".$site_id."'");
if (!$res)
	fatal_error(0, get_vocab('site') . $site_id . get_vocab('not_found'));
$row = grr_sql_row_keyed($res, 0);
grr_sql_free($res);
// on teste si des utilisateurs administrent le site
$req_admin = "SELECT u.login, u.nom, u.prenom, u.etat FROM ".TABLE_PREFIX."_utilisateurs u left join ".TABLE_PREFIX."_j_useradmin_site j on u.login=j.login WHERE j.id_site = '".$site_id."' ORDER BY u.nom, u.prenom";
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
else fatal_error(0, get_vocab('failed_to_acquire'));
// code html
echo start_page_wo_header(Settings::get("company").get_vocab("deux_points").get_vocab("mrbs"));
echo '<h3 style="text-align:center;">';
echo get_vocab("site").get_vocab("deux_points")." ".clean_input($row["sitename"]);
echo "</h3>";
// On affiche pour les administrateurs les utilisateurs ayant des privilèges sur ce site
echo "\n<h2>".get_vocab('utilisateurs_ayant_privileges_sur_site')."</h2>";
if ($is_admin != '')
{
	echo "\n<h3><b>".get_vocab("utilisateurs_administrateurs_site")."</b></h3>";
	echo $is_admin;
}
else 
    echo "<p>".get_vocab("aucun autilisateur").".</p>";
end_page();
?>