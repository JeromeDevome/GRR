<?php
/**
 * view_rights_room.php
 * Liste des privilèges d'une ressource
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2009-12-02 20:11:08 $
 * @author    Laurent Delineau <laurent.delineau@ac-poitiers.fr>
 * @copyright Copyright 2003-2008 Laurent Delineau
 * @link      http://www.gnu.org/licenses/licenses.html
 * @package   root
 * @version   $Id: view_rights_room.php,v 1.8 2009-12-02 20:11:08 grr Exp $
 * @filesource
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GRR is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GRR; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */
include "include/connect.inc.php";
include "include/config.inc.php";
include "include/functions.inc.php";
include "include/$dbsys.inc.php";
include_once('include/misc.inc.php');
include "include/mrbs_sql.inc.php"; include 'include/twigInit.php';
include 'include/twigInit.php';
$grr_script_name = "view_rights_room.php";
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
$id_room = isset($_GET["id_room"]) ? $_GET["id_room"] : NULL;
if (isset($id_room))
	settype($id_room,"integer");
if ((authGetUserLevel(getUserName(),$id_room) < 4) || (!verif_acces_ressource(getUserName(), $id_room)))
{
	showAccessDenied('');
	exit();
}
echo begin_page(Settings::get("company").get_vocab("deux_points").get_vocab("mrbs"));
$res = grr_sql_query("SELECT * FROM ".TABLE_PREFIX."_room WHERE id=$id_room");
if (!$res)
	fatal_error(0, get_vocab('error_room') . $id_room . get_vocab('not_found'));
$row = grr_sql_row_keyed($res, 0);
grr_sql_free($res);
echo '<h3 style="text-align:center;">';
echo get_vocab("room").get_vocab("deux_points")." ".htmlspecialchars($row["room_name"]);
$id_area = mrbsGetRoomArea($id_room);
$area_name = grr_sql_query1("SELECT area_name FROM ".TABLE_PREFIX."_area WHERE id='".$id_area."'");
$area_access = grr_sql_query1("SELECT access FROM ".TABLE_PREFIX."_area WHERE id='".$id_area."'");
echo "<br />(".$area_name;
if ($area_access == 'r')
	echo " - <span class=\"avertissement\">".get_vocab("access")."</span>";
echo ")";
echo "</h3>";
// On affiche pour les administrateurs les utilisateurs ayant des privilèges sur cette ressource
echo "\n<h2>".get_vocab('utilisateurs ayant privileges')."</h2>";
$a_privileges = 'n';
// on teste si des utilateurs administre le domaine
$req_admin = "SELECT u.login, u.nom, u.prenom  FROM ".TABLE_PREFIX."_utilisateurs u left join ".TABLE_PREFIX."_j_useradmin_area j on u.login=j.login WHERE j.id_area = '".$id_area."' order by u.nom, u.prenom";
$res_admin = grr_sql_query($req_admin);
$is_admin = '';
if ($res_admin)
{
	for ($j = 0; ($row_admin = grr_sql_row($res_admin, $j)); $j++)
		$is_admin .= $row_admin[1]." ".$row_admin[2]." (".$row_admin[0].")<br />";
}
if ($is_admin != '')
{
	$a_privileges = 'y';
	echo "\n<h3><b>".get_vocab("utilisateurs administrateurs")."</b></h3>";
	echo "<p>".$is_admin."</p>";
}
// On teste si des utilisateurs administrent la ressource
$req_room = "SELECT u.login, u.nom, u.prenom  FROM ".TABLE_PREFIX."_utilisateurs u left join ".TABLE_PREFIX."_j_user_room j on u.login=j.login WHERE j.id_room = '".$id_room."' order by u.nom, u.prenom";
$res_room = grr_sql_query($req_room);
$is_gestionnaire = '';
if ($res_room)
{
	for ($j = 0; ($row_room = grr_sql_row($res_room, $j)); $j++)
		$is_gestionnaire .= $row_room[1]." ".$row_room[2]." (".$row_room[0].")<br />";
}
if ($is_gestionnaire != '')
{
	$a_privileges = 'y';
	echo "\n<h3><b>".get_vocab("utilisateurs gestionnaires ressource")."</b></h3>";
	echo "<p>".$is_gestionnaire."</p>";
}
// On teste si des utilisateurs reçoivent des mails automatiques
$req_mail = "SELECT u.login, u.nom, u.prenom  FROM ".TABLE_PREFIX."_utilisateurs u left join ".TABLE_PREFIX."_j_mailuser_room j on u.login=j.login WHERE j.id_room = '".$id_room."' order by u.nom, u.prenom";
$res_mail = grr_sql_query($req_mail);
$is_mail = '';
if ($res_mail)
{
	for ($j = 0; ($row_mail = grr_sql_row($res_mail, $j)); $j++)
		$is_mail .= $row_mail[1]." ".$row_mail[2]." (".$row_mail[0].")<br />";
}
if ($is_mail != '')
{
	$a_privileges = 'y';
	echo "\n<h3><b>".get_vocab("utilisateurs mail automatique")."</b></h3>";
	echo "<p>".$is_mail."</p>";
}
// Si le domaine est restreint, on teste si des utilateurs y ont accès
if ($area_access == 'r')
{
	$req_restreint = "SELECT u.login, u.nom, u.prenom  FROM ".TABLE_PREFIX."_utilisateurs u left join ".TABLE_PREFIX."_j_user_area j on u.login=j.login WHERE j.id_area = '".$id_area."' order by u.nom, u.prenom";
	$res_restreint = grr_sql_query($req_restreint);
	$is_restreint = '';
	if ($res_restreint)
	{
		for ($j = 0; ($row_restreint = grr_sql_row($res_restreint, $j)); $j++)
			$is_restreint .= $row_restreint[1]." ".$row_restreint[2]." (".$row_restreint[0].")<br />";
	}
	if ($is_restreint != '')
	{
		$a_privileges = 'y';
		echo "\n<h3><b>".get_vocab("utilisateurs acces restreint")."</b></h3>\n";
		echo "<p>".$is_restreint."</p>";
	}
}
if ($a_privileges == 'n')
	echo "<p>".get_vocab("aucun autilisateur").".</p>";
include "include/trailer.inc.php";
