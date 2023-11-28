<?php
/**
 * del_entry.php
 * Interface de suppression d'une réservation
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2023-11-22 16:22$
 * @author    Laurent Delineau & JeromeB & Yan Naessens
 * @copyright Copyright 2003-2023 Team DEVOME - JeromeB
 * @link      http://www.gnu.org/licenses/licenses.html
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */
$grr_script_name = "del_entry.php";

include "include/connect.inc.php";
include "include/config.inc.php";
include "include/misc.inc.php";
include "include/$dbsys.inc.php";
include "include/mrbs_sql.inc.php";
include "include/functions.inc.php";

require_once("./include/settings.class.php");
if (!Settings::load())
	die("Erreur chargement settings");
require_once("./include/session.inc.php");
if (!grr_resumeSession())
{
	header("Location: ./logout.php?auto=1&url=$url");
	die();
};
$user_name = getUserName();
include "include/language.inc.php";
$series = getFormVar("series","integer");
$page = verif_page();
$id = getFormVar("id","integer");
$id = clean_input($id);
if(is_null($id)){
	header("Location: ./login.php");
	die();
}
if ($info = mrbsGetEntryInfo($id))
{
	$day   = date("d", $info["start_time"]);
	$month = date("m", $info["start_time"]);
	$year  = date("Y", $info["start_time"]);
    $room_id = intval($info["room_id"]);
	$area  = mrbsGetRoomArea($room_id);
	$back = (isset($_SERVER['HTTP_REFERER']))? htmlspecialchars_decode($_SERVER['HTTP_REFERER'], ENT_QUOTES) : page_accueil() ;
	if (authGetUserLevel($user_name, -1) < 1)
	{
		showAccessDenied($back);
		exit();
	}
    if (!getWritable($user_name, $id))
	{
		showAccessDenied($back);
		exit;
	}
	if (authUserAccesArea($user_name, $area) == 0)
	{
		showAccessDenied($back);
		exit();
	}
	if (Settings::get("automatic_mail") == 'yes')
		$_SESSION['session_message_error'] = send_mail($id,3,$dformat);
    // traitement des réservations modérées : envoie un mail au modérateur
    if ($info['moderate'] != 0){ // cette réservation est à modérer ou a été modérée
        $_SESSION['session_message_error'] .= send_mail($id,3,$dformat);
    }
    display_mail_msg();
	$date_now = time();
	get_planning_area_values($area);
    $who_can_book = grr_sql_query1("SELECT who_can_book FROM ".TABLE_PREFIX."_room WHERE id='".$room_id."' ");
    $user_can_book = $who_can_book || (authBooking($current_user,$room_id));
	if ((!(verif_booking_date($user_name, $id, $room_id, -1, $date_now, $enable_periods))) || ((verif_booking_date($user_name, $id, $room_id, -1, $date_now, $enable_periods)) && ($can_delete_or_create != "y")) && $user_can_book)
	{
		showAccessDenied($back);
		exit();
	}
	$result = mrbsDelEntry($user_name, $id, $series, 1);
	if ($result)
	{
        $room_back = isset($_GET['room_back']) ? clean_input($_GET['room_back']) : $info['room_id'];
		$_SESSION['displ_msg'] = 'yes';
        $ress = '';
        if ($room_back != 'all')  {$ress = "&room=".$room_back;}
		Header("Location: ".$page.".php?day=$day&month=$month&year=$year&area=$area".$ress);
		exit();
	}
}
showAccessDenied($back);
?>