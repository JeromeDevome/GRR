<?php
/**
 * view_room.php
 * Fiche ressource
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2009-12-02 20:11:08 $
 * @author    Laurent Delineau <laurent.delineau@ac-poitiers.fr>
 * @copyright Copyright 2003-2008 Laurent Delineau
 * @link      http://www.gnu.org/licenses/licenses.html
 * @package   root
 * @version   $Id: view_room.php,v 1.12 2009-12-02 20:11:08 grr Exp $
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
$grr_script_name = "view_room.php";
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

$id_room = isset($_GET["id_room"]) ? $_GET["id_room"] : NULL;
if (isset($id_room))
	settype($id_room,"integer");
else
	$print = "all";
if ((Settings::get("authentification_obli") == 0) && (getUserName() == ''))
{
	$type_session = "no_session";
}
else
{
	$type_session = "with_session";
}
if (((authGetUserLevel(getUserName(),-1) < 1) && (Settings::get("authentification_obli") == 1)) || (!verif_acces_ressource(getUserName(), $id_room)))
{
	showAccessDenied('');
	exit();
}

echo begin_page(get_vocab("mrbs").get_vocab("deux_points").Settings::get("company"));
$res = grr_sql_query("SELECT * FROM ".TABLE_PREFIX."_room WHERE id=$id_room");
if (!$res)
	fatal_error(0, get_vocab('error_room') . $id_room . get_vocab('not_found'));
$row = grr_sql_row_keyed($res, 0);
grr_sql_free($res);
echo "<h3 style=\"text-align:center;\">";
echo get_vocab("room").get_vocab("deux_points")." ".htmlspecialchars($row["room_name"]);
$id_area = mrbsGetRoomArea($id_room);
$area_name = grr_sql_query1("select area_name from ".TABLE_PREFIX."_area where id='".$id_area."'");
$area_access = grr_sql_query1("select access from ".TABLE_PREFIX."_area where id='".$id_area."'");
echo "<br />(".$area_name;
if ($area_access == 'r')
	echo " - ".get_vocab("access");
echo ")";
echo "</h3>";

if ($row['statut_room'] == "0"){ //
	echo "<h2 style=\"text-align:center;\"><span class=\"avertissement\">".get_vocab("ressource_temporairement_indisponible")."</span></h2>";
}

// Description
if (authGetUserLevel($user,-1) >= Settings::get("visu_fiche_description"))
{
	echo "<h3>".get_vocab("description")."</h3>\n";
	echo "<div>".htmlspecialchars($row["description"])." </div>\n";
}

// Description complète
if ((authGetUserLevel($user,-1) >= Settings::get("acces_fiche_reservation")) && ($row["comment_room"] != ''))
{
	echo "<h3>".get_vocab("match_descr")."</h3>\n";
	echo "<div>".$row["comment_room"]."</div>\n";
}

// Afficher capacité
if ($row["capacity"] != '0')
{
	echo "<h3>".get_vocab("capacity_2")."</h3>\n";
	echo "<p>".$row["capacity"]."</p>\n";
}
if ($row["max_booking"] != "-1")
	echo "<p>".get_vocab("msg_max_booking").get_vocab("deux_points").$row["max_booking"]."</p>";
// Limitation par domaine
$max_booking_per_area = grr_sql_query1("SELECT max_booking FROM ".TABLE_PREFIX."_area WHERE id = '".protect_data_sql($id_area)."'");
if ($max_booking_per_area >= 0)
	echo "<p>".get_vocab("msg_max_booking_area").get_vocab("deux_points").$max_booking_per_area."</p>";
if ($row["delais_max_resa_room"] != "-1")
	echo "<p>".get_vocab("delais_max_resa_room_2")." <b>".$row["delais_max_resa_room"]."</b></p>";
if ($row["delais_min_resa_room"] != "0")
	echo "<p>".get_vocab("delais_min_resa_room_2")." <b>".$row["delais_min_resa_room"]."</b></p>";
$nom_picture = '';
if ($row['picture_room'] != '') $nom_picture = "./images/".$row['picture_room'];
echo "<div style=\"text-align:center; margin-top:30px\"><b>";
if (@file_exists($nom_picture) && $nom_picture)
	echo get_vocab("Image de la ressource").": </b><br /><img src=\"".$nom_picture."\" alt=\"logo\" />";
else
	echo get_vocab("Pas image disponible")."</b>";
echo "</div>";

include "include/trailer.inc.php";
?>
