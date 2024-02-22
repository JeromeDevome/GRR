<?php
/**
 * view_rights_room.php
 * Liste des privilèges d'une ressource
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2024-02-22 14:41$
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
$grr_script_name = "view_rights_room.php";

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
$id_room = isset($_GET["id_room"]) ? intval($_GET["id_room"]) : NULL;
if ((authGetUserLevel(getUserName(),$id_room) < 4) || (!verif_acces_ressource(getUserName(), $id_room)))
{
	showAccessDenied('');
	exit();
}

$res = grr_sql_query("SELECT * FROM ".TABLE_PREFIX."_room WHERE id=?","i",[$id_room]);
if (!$res)
	fatal_error(0, get_vocab('error_room') . $id_room . get_vocab('not_found'));
$Room = grr_sql_row_keyed($res, 0);
grr_sql_free($res);
$id_area = mrbsGetRoomArea($id_room);
$area_name = grr_sql_query1("SELECT area_name FROM ".TABLE_PREFIX."_area WHERE id=?","i",[$id_area]);
$area_access = grr_sql_query1("SELECT access FROM ".TABLE_PREFIX."_area WHERE id=?","i",[$id_area]);
$a_privileges = 'n';
// on teste si des utilateurs administrent le domaine
$req_admin = "SELECT u.login, u.nom, u.prenom  FROM ".TABLE_PREFIX."_utilisateurs u left join ".TABLE_PREFIX."_j_useradmin_area j on u.login=j.login WHERE j.id_area =? order by u.nom, u.prenom";
$res_admin = grr_sql_query($req_admin,"i",[$id_area]);
$is_admin = '';
if ($res_admin)
{
	foreach($res_admin as $row)
		$is_admin .= $row["nom"]." ".$row["prenom"]." (".$row["login"].")<br />";
}
grr_sql_free($res_admin);
if ($is_admin != '')
	$a_privileges = 'y';
// On teste si des utilisateurs administrent la ressource
$req_room = "SELECT u.login, u.nom, u.prenom  FROM ".TABLE_PREFIX."_utilisateurs u left join ".TABLE_PREFIX."_j_user_room j on u.login=j.login WHERE j.id_room =? order by u.nom, u.prenom";
$res_room = grr_sql_query($req_room,"i",[$id_room]);
$is_gestionnaire = '';
if ($res_room)
{
	foreach($res_room as $row)
		$is_gestionnaire .= $row["nom"]." ".$row["prenom"]." (".$row["login"].")<br />";
}
grr_sql_free($res_room);
if ($is_gestionnaire != '')
	$a_privileges = 'y';
// On teste si des utilisateurs reçoivent des mails automatiques
$req_mail = "SELECT u.login, u.nom, u.prenom  FROM ".TABLE_PREFIX."_utilisateurs u left join ".TABLE_PREFIX."_j_mailuser_room j on u.login=j.login WHERE j.id_room =? order by u.nom, u.prenom";
$res_mail = grr_sql_query($req_mail,"i",[$id_room]);
$is_mail = '';
if ($res_mail)
{
	foreach($res_mail as $row)
		$is_mail .= $row["nom"]." ".$row["prenom"]." (".$row["login"].")<br />";
}
grr_sql_free($res_mail);
if ($is_mail != '')
	$a_privileges = 'y';
// Si le domaine est restreint, on teste si des utilateurs y ont accès
$is_restreint = '';
if ($area_access == 'r')
{
	$req_restreint = "SELECT u.login, u.nom, u.prenom  FROM ".TABLE_PREFIX."_utilisateurs u left join ".TABLE_PREFIX."_j_user_area j on u.login=j.login WHERE j.id_area =? order by u.nom, u.prenom";
	$res_restreint = grr_sql_query($req_restreint,"i",[$id_area]);
	if($res_restreint)
	{
		foreach($res_restreint as $row)
			$is_restreint .= $row["nom"]." ".$row["prenom"]." (".$row["login"].")<br />";
	}
    grr_sql_free($res_restreint);
	if ($is_restreint != '')
		$a_privileges = 'y';
}
// Si la ressource est restreinte, on affiche les utilisateurs ayant le droit de réserver
$can_book = '';
if ($Room['who_can_book'] == 0){// la ressource est à accès restreint
    $req = "SELECT u.login, u.nom, u.prenom  FROM ".TABLE_PREFIX."_utilisateurs u left join ".TABLE_PREFIX."_j_userbook_room j on u.login=j.login WHERE j.id_room =? order by u.nom, u.prenom";
    $res = grr_sql_query($req,"i",[$Room['id']]);
    if ($res){
        foreach($res as $user){
            $can_book .= $user['nom']." ".$user['prenom']." (".$user['login'].")<br />";
        }
    }
    grr_sql_free($res);
}
echo start_page_wo_header(Settings::get("company").get_vocab("deux_points").get_vocab("mrbs"),$type_session);
echo '<h3 class="center">';
echo get_vocab("room").get_vocab("deux_points")." ".clean_input($Room["room_name"]);
echo "<br />(".$area_name;
if ($area_access == 'r')
	echo " - <span class=\"avertissement\">".get_vocab("access")."</span>";
echo ")";
echo "</h3>";
// On affiche pour les administrateurs les utilisateurs ayant des privilèges sur cette ressource
echo "\n<h2>".get_vocab('utilisateurs_ayant_privileges')."</h2>";
if ($is_admin != '')
{
	$a_privileges = 'y';
	echo "\n<h3><b>".get_vocab('utilisateurs_administrateurs')."</b></h3>";
	echo "<p>".$is_admin."</p>";
}
if ($is_gestionnaire != '')
{
	$a_privileges = 'y';
	echo "\n<h3><b>".get_vocab('utilisateurs_gestionnaires_ressource')."</b></h3>";
	echo "<p>".$is_gestionnaire."</p>";
}if ($is_mail != '')
{
	$a_privileges = 'y';
	echo "\n<h3><b>".get_vocab('utilisateurs_mail_automatique')."</b></h3>";
	echo "<p>".$is_mail."</p>";
}
if ($is_restreint != '')
{
    $a_privileges = 'y';
    echo "\n<h3><b>".get_vocab('utilisateurs_acces_restreint')."</b></h3>\n";
    echo "<p>".$is_restreint."</p>";
}
if ($a_privileges == 'n')
	echo "<p>".get_vocab('aucun_utilisateur').".</p>";
if($Room["who_can_book"] == 0){
    if ($can_book != ''){
        echo "\n<h3><b>".get_vocab('utilisateurs_reservant')."</b></h3>\n";
        echo "<p>".$can_book."</p>";
    }
    else echo "<p>".get_vocab('aucun_utilisateur_reservant')."</p>";
}
end_page();
?>