<?php
/**
 * participation_entry.php
 * Interface de suppression d'une réservation
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2022-06-19 16:00$
 * @author    JeromeB & Yan Naessens
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
$grr_script_name = "participation_entry.php";
include "personnalisation/connect.inc.php";
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
	
	$authGetUserLevel = authGetUserLevel(getUserName(), -1);
	if ($authGetUserLevel < 1)
	{
		showAccessDenied($back, 'authGetUserLevel');
		exit();
	}

	$lvl_participation = grr_sql_query1("SELECT ".TABLE_PREFIX."_room.active_participant FROM ".TABLE_PREFIX."_entry, ".TABLE_PREFIX."_room WHERE ".TABLE_PREFIX."_entry.room_id = ".TABLE_PREFIX."_room.id AND ".TABLE_PREFIX."_entry.id='".$id."'");

	if ($lvl_participation < 1)
	{
		showAccessDenied($back, 'lvl_participationInf1');
		exit;
	}
	if($authGetUserLevel < $lvl_participation)
	{
		showAccessDenied($back, 'authGetUserLevel_Inf_Lvl_participation');
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
		Header("Location: ".$page.".php?day=$day&month=$month&year=$year&area=$area".$ress);
		exit();
	}
}
showAccessDenied($back, 'defaut');
?>
