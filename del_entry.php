<?php
/**
 * del_entry.php
 * Interface de suppresssion d'une réservation
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2009-06-04 15:30:17 $
 * @author    Laurent Delineau <laurent.delineau@ac-poitiers.fr>
 * @copyright Copyright 2003-2008 Laurent Delineau
 * @link      http://www.gnu.org/licenses/licenses.html
 * @package   root
 * @version   $Id: del_entry.php,v 1.7 2009-06-04 15:30:17 grr Exp $
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
include "include/mrbs_sql.inc.php"; include 'include/twigInit.php'; include 'include/twigInit.php';
$grr_script_name = "del_entry.php";
require_once("./include/settings.class.php");
if (!Settings::load())
	die("Erreur chargement settings");
require_once("./include/session.inc.php");
if (!grr_resumeSession())
{
	header("Location: ./logout.php?auto=1&url=$url");
	die();
};
include "include/language.inc.php";
$series = isset($_GET["series"]) ? $_GET["series"] : NULL;
if (isset($series))
	settype($series,"integer");
$page = verif_page();
if (isset($_GET["id"]))
{
	$id = $_GET["id"];
	settype($id,"integer");
}
else
	die();
if ($info = mrbsGetEntryInfo($id))
{
	$day   = strftime("%d", $info["start_time"]);
	$month = strftime("%m", $info["start_time"]);
	$year  = strftime("%Y", $info["start_time"]);
	$area  = mrbsGetRoomArea($info["room_id"]);
	$back = "";
	if (isset($_SERVER['HTTP_REFERER']))
		$back = htmlspecialchars($_SERVER['HTTP_REFERER']);
	if (authGetUserLevel(getUserName(), -1) < 1)
	{
		showAccessDenied($back);
		exit();
	}
	if (!getWritable($info["beneficiaire"], getUserName(), $id))
	{
		showAccessDenied($back);
		exit;
	}
	if (authUserAccesArea(getUserName(), $area) == 0)
	{
		showAccessDenied($back);
		exit();
	}
	if (Settings::get("automatic_mail") == 'yes')
		$_SESSION['session_message_error'] = send_mail($id,3,$dformat);
	$room_id = grr_sql_query1("SELECT ".TABLE_PREFIX."_entry.room_id FROM ".TABLE_PREFIX."_entry, ".TABLE_PREFIX."_room WHERE ".TABLE_PREFIX."_entry.room_id = ".TABLE_PREFIX."_room.id AND ".TABLE_PREFIX."_entry.id='".$id."'");
	$date_now = time();
	get_planning_area_values($area);
	if ((!(verif_booking_date(getUserName(), $id, $room_id, -1, $date_now, $enable_periods))) || ((verif_booking_date(getUserName(), $id, $room_id, -1, $date_now, $enable_periods)) && ($can_delete_or_create != "y")))
	{
		showAccessDenied($back);
		exit();
	}
	$result = mrbsDelEntry(getUserName(), $id, $series, 1);
	if ($result)
	{
		$_SESSION['displ_msg'] = 'yes';
		Header("Location: ".$page.".php?day=$day&month=$month&year=$year&area=$area&room=".$info["room_id"]);
		exit();
	}
}
showAccessDenied($back);
?>
