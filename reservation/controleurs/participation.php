<?php
/**
 * participation.php
 * Interface de suppression d'une réservation
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2025-01-25 18:40$
 * @author    JeromeB & Yan Naessens
 * @copyright Copyright 2003-2025 Team DEVOME - JeromeB
 * @link      http://www.gnu.org/licenses/licenses.html
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */
$grr_script_name = "participation.php";

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
	$day   = date('d', $info["start_time"]);
	$month = date('m', $info["start_time"]);
	$year  = date('Y', $info["start_time"]);
	$area  = mrbsGetRoomArea($info["room_id"]);
	$back = "";
	if (isset($_SERVER['HTTP_REFERER']))
		$back = htmlspecialchars($_SERVER['HTTP_REFERER']);
	
	$authGetUserLevel = authGetUserLevel(getUserName(), $id);
	if ($authGetUserLevel < 1)
	{
		showAccessDenied($back, 'authGetUserLevel');
		exit();
	}

	$lvl_participation = grr_sql_query1("SELECT ".TABLE_PREFIX."_room.inscription_participant FROM ".TABLE_PREFIX."_entry, ".TABLE_PREFIX."_room WHERE ".TABLE_PREFIX."_entry.room_id = ".TABLE_PREFIX."_room.id AND ".TABLE_PREFIX."_entry.id='".$id."'");

	if ($lvl_participation < 1)
	{
		showAccessDenied($back, 'lvl_participationInf1');
		exit;
	}
	if($authGetUserLevel < $lvl_participation)
	{
		showAccessDenied($back, 'authGetUserLevel_Inf_Lvl_participation;'.$authGetUserLevel.';'.$lvl_participation);
		exit;
	}	

	if (authUserAccesArea(getUserName(), $area) == 0)
	{
		showAccessDenied($back, 'authUserAccesArea');
		exit();
	}
	//if (Settings::get("automatic_mail") == 'yes')
	//	$_SESSION['session_message_error'] = send_mail($id,3,$dformat);
	$room_id = grr_sql_query1("SELECT ".TABLE_PREFIX."_entry.room_id FROM ".TABLE_PREFIX."_entry, ".TABLE_PREFIX."_room WHERE ".TABLE_PREFIX."_entry.room_id = ".TABLE_PREFIX."_room.id AND ".TABLE_PREFIX."_entry.id='".$id."'");
	$date_now = time();
	get_planning_area_values($area);
	if ((!(verif_participation_date(getUserName(), $id, $room_id, -1, $date_now, $enable_periods))) || ((verif_participation_date(getUserName(), $id, $room_id, -1, $date_now, $enable_periods)) && ($can_delete_or_create != "y")))
	{
		showAccessDenied($back, 'verif_participation_date');
		exit();
	}

	$result = true;
	
	$res = grr_sql_query("SELECT id_participation FROM ".TABLE_PREFIX."_participants WHERE idresa=$id AND beneficiaire='".getUserName()."'");
    if (!$res)
        fatal_error(0, grr_sql_error());

    if (grr_sql_count($res) >= 1)
		PaticipationAnnulation($id, getUserName());
	else
	{
		$resp = grr_sql_query("SELECT beneficiaire FROM ".TABLE_PREFIX."_participants WHERE idresa=$id");
		if (!$resp)
			fatal_error(0, grr_sql_error());
		
		$maxParticipant = grr_sql_query1("SELECT nbparticipantmax FROM ".TABLE_PREFIX."_entry WHERE id='".$id."'");

		if (grr_sql_count($resp) < $maxParticipant)
			PaticipationAjout($id, getUserName(), getUserName(),'');
		else
		{
			showAccessDenied($back, 'maxParticipant');
			exit();
		}
		
	}

	grr_sql_free($res);

	if ($result)
	{
        $room_back = isset($_GET['room_back']) ? $_GET['room_back'] : $info['room_id'];
		$_SESSION['displ_msg'] = 'yes';
        $ress = '';
        if ($room_back != 'all')  {$ress = "&room=".$room_back;}
		Header("Location: app.php?p=".$page."&day=$day&month=$month&year=$year&area=$area".$ress);
		exit();
	}
}
showAccessDenied($back, 'defaut');
?>
