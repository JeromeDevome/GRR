<?php
/**
 * supreservation.php
 * Interface de suppression d'une réservation
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2024-04-28 16:45$
 * @author    Laurent Delineau & JeromeB & Yan Naessens
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
$grr_script_name = "supreservation.php";

$series = isset($_GET["series"]) ? $_GET["series"] : NULL;
if (isset($series))
	$series = intval($series);
$page = verif_page();

if (isset($_GET["id"]))
	$id = intval(clean_input($_GET["id"]));
else{
	header("Location: ./app.php?p=login");
	die();
}
if ($info = mrbsGetEntryInfo($id))
{
	$day   = date("d", $info["start_time"]);
	$month = date("m", $info["start_time"]);
	$year  = date("Y", $info["start_time"]);
	$area  = mrbsGetRoomArea($info["room_id"]);
	$back = (isset($_SERVER['HTTP_REFERER']))? htmlspecialchars_decode($_SERVER['HTTP_REFERER'], ENT_QUOTES) : page_accueil() ;
	if (authGetUserLevel(getUserName(), -1) < 1)
	{
		showAccessDenied($back);
		exit();
	}
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
	// ! Sup en version 4.5.2 car doublons dans l'envois au modérateur
/* if ($info['moderate'] != 0){ // cette réservation est à modérer ou a été modérée
    //    $_SESSION['session_message_error'] .= send_mail($id,3,$dformat);
}*/

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
    //echo "33";
	$result = mrbsDelEntry(getUserName(), $id, $series, 1);
	if ($result)
	{
        //echo "44";
        $room_back = isset($_GET['room_back']) ? clean_input($_GET['room_back']) : $info['room_id'];
		$_SESSION['displ_msg'] = 'yes';
        $ress = '';
        if ($room_back != 'all')  {$ress = "&room=".$room_back;}
		Header("Location: app.php?p=".$page."&day=$day&month=$month&year=$year&area=$area".$ress);
		exit();
	}
}
showAccessDenied($back);
?>