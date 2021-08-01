<?php
/**
 * participation_entry.php
 * Script de traitement de l'inscription/désincription à une réservation acceptant les participants
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2021-08-01 18:24$
 * @author    JeromeB & Yan Naessens
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
$grr_script_name = "participation_entry.php";
include "include/connect.inc.php";
include "include/config.inc.php";
include_once('include/misc.inc.php');
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
}
include "include/language.inc.php";
$page = verif_page();
if (isset($_GET["id"]))
	$id = intval($_GET["id"]);
else
{
	header("Location: ./logout.php?auto=1&url=$url");
	die();
}
if ($info = mrbsGetEntryInfo($id))
{
	$day   = strftime("%d", $info["start_time"]);
	$month = strftime("%m", $info["start_time"]);
	$year  = strftime("%Y", $info["start_time"]);
	$area  = mrbsGetRoomArea($info["room_id"]);
	$back = isset($_SERVER['HTTP_REFERER'])? htmlspecialchars($_SERVER['HTTP_REFERER']): page_accueil();
	$user_name = getUserName();
	$authGetUserLevel = authGetUserLevel($user_name, -1);
	if ($authGetUserLevel < 1)
	{
		showAccessDenied($back);
		exit();
	}
    
	$lvl_participation = grr_sql_query1("SELECT r.active_participant FROM ".TABLE_PREFIX."_entry e JOIN ".TABLE_PREFIX."_room r ON e.room_id = r.id WHERE e.id='".$id."'");
	if ($lvl_participation < 1)
	{
        echo 'lvl_participationInf1';
		showAccessDenied($back);
		exit;
	}
	if($authGetUserLevel < $lvl_participation)
	{
		showAccessDenied($back);
		exit;
	}	
	if (authUserAccesArea($user_name, $area) == 0)
	{
		showAccessDenied($back);
		exit();
	}
    
	$room_id = $info['room_id'];
	$date_now = time();
	get_planning_area_values($area);
	if ((!(verif_participation_date($user_name, $id, $room_id, -1, $date_now, $enable_periods))) || ((verif_participation_date($user_name, $id, $room_id, -1, $date_now, $enable_periods)) && ($can_delete_or_create != "y")))
	{
		showAccessDenied($back);
		exit();
	}

	$res = grr_sql_query("SELECT * FROM ".TABLE_PREFIX."_participants WHERE idresa=$id AND participant='$user_name'");
    if (!$res)
        fatal_error(0, grr_sql_error());

    if (grr_sql_count($res) >= 1)
		ParticipationAnnulation($id, $user_name);
	else
	{
		$resp = grr_sql_query("SELECT participant FROM ".TABLE_PREFIX."_participants WHERE idresa=$id");
		if (!$resp)
			fatal_error(0, grr_sql_error());
		
		$maxParticipant = grr_sql_query1("SELECT nbparticipantmax FROM ".TABLE_PREFIX."_entry WHERE id='".$id."'");

		if (grr_sql_count($resp) < $maxParticipant)
			ParticipationAjout($id, $user_name);
		else
		{
			showAccessDenied($back);
			exit();
		}
	}

	grr_sql_free($res);

    $room_back = isset($_GET['room_back']) ? $_GET['room_back'] : $info['room_id'];
    $_SESSION['displ_msg'] = 'yes';
    $ress = '';
    if ($room_back != 'all')  {$ress = "&room=".$room_back;}
    Header("Location: ".$page.".php?day=$day&month=$month&year=$year&area=$area".$ress);
    exit();

}
else
    die('erreur de lecture en base de données');
?>
