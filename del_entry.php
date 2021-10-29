<?php
/**
 * del_entry.php
 * Interface de suppression d'une réservation
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2021-10-22 11:50$
 * @author    Laurent Delineau & JeromeB & Yan Naessens
 * @copyright Copyright 2003-2021 Team DEVOME - JeromeB
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
include "include/functions.inc.php";
include "include/$dbsys.inc.php";
include_once('include/misc.inc.php');
include "include/mrbs_sql.inc.php";

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
	$id = clean_input($_GET["id"]);
	settype($id,"integer");
}
else{
	header("Location: ./login.php");
	die();
}
if ($info = mrbsGetEntryInfo($id))
{
	$day   = strftime("%d", $info["start_time"]);
	$month = strftime("%m", $info["start_time"]);
	$year  = strftime("%Y", $info["start_time"]);
	$area  = mrbsGetRoomArea($info["room_id"]);
	$back = (isset($_SERVER['HTTP_REFERER']))? htmlspecialchars_decode($_SERVER['HTTP_REFERER'], ENT_QUOTES) : page_accueil() ;
	if (authGetUserLevel(getUserName(), -1) < 1)
	{
		showAccessDenied($back);
		exit();
	}
	//if (!getWritable($info["beneficiaire"], getUserName(), $id))
    if (!getWritable(getUserName(), $id))
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
    // traitement des réservations modérées : envoie un mail au modérateur
    if ($info['moderate'] != 0){ // cette réservation est à modérer ou a été modérée
        $_SESSION['session_message_error'] .= send_mail($id,3,$dformat);
    }
    display_mail_msg();
	$room_id = grr_sql_query1("SELECT ".TABLE_PREFIX."_entry.room_id FROM ".TABLE_PREFIX."_entry WHERE ".TABLE_PREFIX."_entry.id='".$id."'");
	$date_now = time();
	get_planning_area_values($area);
    $who_can_book = grr_sql_query1("SELECT who_can_book FROM ".TABLE_PREFIX."_room WHERE id='".$room_id."' ");
    $user_can_book = $who_can_book || (authBooking($current_user,$info_alt['room_id']));
	if ((!(verif_booking_date(getUserName(), $id, $room_id, -1, $date_now, $enable_periods))) || ((verif_booking_date(getUserName(), $id, $room_id, -1, $date_now, $enable_periods)) && ($can_delete_or_create != "y")) && $user_can_book)
	{
		showAccessDenied($back);
		exit();
	}
	$result = mrbsDelEntry(getUserName(), $id, $series, 1);
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
