<?php
/**
 * edit_entry_handler.php
 * Permet de vérifier la validitée de l'édition ou de la création d'une réservation
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2010-03-03 14:41:34 $
 * @author    Laurent Delineau <laurent.delineau@ac-poitiers.fr>
 * @copyright Copyright 2003-2008 Laurent Delineau
 * @link      http://www.gnu.org/licenses/licenses.html
 * @package   root
 * @version   $Id: edit_entry_handler.php,v 1.12 2010-03-03 14:41:34 grr Exp $
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
include "include/mrbs_sql.inc.php"; include 'include/twigInit.php';
include "include/misc.inc.php";
$grr_script_name = "edit_entry_handler.php";
// Settings
require_once("./include/settings.class.php");
//Chargement des valeurs de la table settingS
if (!Settings::load())
	die("Erreur chargement settings");
// Session related functions
require_once("./include/session.inc.php");
// Resume session
if (!grr_resumeSession())
{
	header("Location: ./logout.php?auto=1&url=$url");
	die();
}
// Paramètres langage
include "include/language.inc.php";
$erreur = 'n';
$message_error = "";
if (isset($_GET["id"]))
{
	$id = $_GET["id"];
	settype($id,"integer");
}
else
	$id = NULL;
$name = isset($_GET["name"]) ? $_GET["name"] : NULL;
if ((!isset($name) or (trim($name) == "")) && (Settings::get("remplissage_description_breve") == '1'))
{
	print_header();
	echo "<h2>".get_vocab("required")."</h2>";
	include "include/trailer.inc.php";
	die();
}
$description = isset($_GET["description"]) ? $_GET["description"] : NULL;
$ampm = isset($_GET["ampm"]) ? $_GET["ampm"] : NULL;
$keys = isset($_GET["keys"]) ? $_GET["keys"] : NULL;
if ($keys == 'y')
	$keys = 1;
else
	$keys = 0;
$courrier = isset($_GET["courrier"]) ? $_GET["courrier"] : NULL;
if ($courrier == 'y')
	$courrier = 1;
else
	$courrier = 0;
$day = isset($_GET["start_day"]) ? $_GET["start_day"] : NULL;
$month = isset($_GET["start_month"]) ? $_GET["start_month"] : NULL;
$year = isset($_GET["start_year"]) ? $_GET["start_year"] : NULL;
$duration = isset($_GET["duration"]) ? $_GET["duration"] : NULL;
$duration = str_replace(",", ".", "$duration ");
$debut = array();
$debut = explode(':', $_GET["start_"]);
$hour = $debut[0];
$minute = $debut[1];
if (isset($hour))
{
	settype($hour, "integer");
	if ($hour > 23)
		$hour = 23;
}
if (isset($minute))
{
	settype($minute, "integer");
	if ($minute > 59)
		$minute = 59;
}
$statut_entry = isset($_GET["statut_entry"]) ? $_GET["statut_entry"] : "-";
$rep_jour_c = isset($_GET["rep_jour_"]) ? $_GET["rep_jour_"] : 0;
$type = isset($_GET["type"]) ? $_GET["type"] : NULL;
$rep_type = isset($_GET["rep_type"]) ? $_GET["rep_type"] : NULL;
if (isset($rep_type))
	settype($rep_type,"integer");
$rep_num_weeks = isset($_GET["rep_num_weeks"]) ? $_GET["rep_num_weeks"] : NULL;
if (isset($rep_num_weeks))
	settype($rep_num_weeks,"integer");
if ($rep_num_weeks < 2)
	$rep_num_weeks = 1;
$rep_month = isset($_GET["rep_month"]) ? $_GET["rep_month"] : NULL;
if (($rep_type == 3) && ($rep_month == 3))
	$rep_type = 3;
if (($rep_type == 3) && ($rep_month == 5))
	$rep_type = 5;
$rep_month_abs1 = isset($_GET["rep_month_abs1"]) ? $_GET["rep_month_abs1"] : NULL;
$rep_month_abs2 = isset($_GET["rep_month_abs2"]) ? $_GET["rep_month_abs2"] : NULL;
if (isset($rep_month_abs1))
	settype($rep_month_abs1,"integer");
if (isset($rep_month_abs2))
	settype($rep_month_abs2,"integer");
$create_by = isset($_GET["create_by"]) ? $_GET["create_by"] : NULL;
$beneficiaire = isset($_GET["beneficiaire"]) ? $_GET["beneficiaire"] : "";
$benef_ext_nom = isset($_GET["benef_ext_nom"]) ? $_GET["benef_ext_nom"] : "";
$benef_ext_email = isset($_GET["benef_ext_email"]) ? $_GET["benef_ext_email"] : "";
$beneficiaire_ext = concat_nom_email($benef_ext_nom, $benef_ext_email);
$rep_id = isset($_GET["rep_id"]) ? $_GET["rep_id"] : NULL;
$rep_day = isset($_GET["rep_day"]) ? $_GET["rep_day"] : NULL;
$rep_end_day = isset($_GET["rep_end_day"]) ? $_GET["rep_end_day"] : NULL;
$rep_end_month = isset($_GET["rep_end_month"]) ? $_GET["rep_end_month"] : NULL;
$rep_end_year = isset($_GET["rep_end_year"]) ? $_GET["rep_end_year"] : NULL;
$room_back = isset($_GET["room_back"]) ? $_GET["room_back"] : NULL;
if (isset($room_back))
	settype($room_back,"integer");
$page = verif_page();
if ($page == '')
	$page = "day";
$option_reservation = isset($_GET["option_reservation"]) ? $_GET["option_reservation"] : NULL;
if (isset($option_reservation))
	settype($option_reservation,"integer");
else
	$option_reservation = -1;
if (isset($_GET["confirm_reservation"]))
	$option_reservation = -1;
$type_affichage_reser = isset($_GET["type_affichage_reser"]) ? $_GET["type_affichage_reser"] : NULL;
if ($beneficiaire == "-1")
	$beneficiaire = getUserName();
if (($beneficiaire) == "")
{
	if ($beneficiaire_ext == "-1")
	{
		print_header();
		echo "<h2>".get_vocab("required")."</h2>";
		include "include/trailer.inc.php";
		die();
	}
	if ($beneficiaire_ext == "-2")
	{
		print_header();
		echo "<h2>Adresse email du bénéficiaire incorrecte</h2>";
		include "include/trailer.inc.php";
		die();
	}
}
else
	$beneficiaire_ext = "";
if (!isset($_GET['rooms'][0]))
{
	print_header();
	echo "<h2>".get_vocab("choose_a_room")."</h2>";
	include "include/trailer.inc.php";
	die();
}
if (isset($_SERVER['HTTP_REFERER']))
	$back = htmlspecialchars($_SERVER['HTTP_REFERER']);
$area = mrbsGetRoomArea($_GET['rooms'][0]);
$overload_data = array();
$overload_fields_list = mrbsOverloadGetFieldslist($area);
foreach ($overload_fields_list as $overfield=>$fieldtype)
{
	$id_field = $overload_fields_list[$overfield]["id"];
	$fieldname = "addon_".$id_field;
	if (($overload_fields_list[$overfield]["obligatoire"] == 'y') && ((!isset($_GET[$fieldname])) || (trim($_GET[$fieldname]) == "")))
	{
		print_header();
		echo "<h2>".get_vocab("required")."</h2>";
		echo "<a href=\"".$back."&amp;Err=yes\">".get_vocab('returnprev')."</a>";
		include "include/trailer.inc.php";
		die();
	}
	if (($overload_fields_list[$overfield]["type"] == "numeric") && (isset($_GET[$fieldname])) && ($_GET[$fieldname] != '') && (!preg_match("`^[0-9]*\.{0,1}[0-9]*$`",$_GET[$fieldname])))
	{
		print_header();
		echo "<h2>".$overload_fields_list[$overfield]["name"].get_vocab("deux_points").get_vocab("is_not_numeric")."</h2>";
		echo "<a href=\"".$back."&amp;Err=yes\">".get_vocab('returnprev')."</a>";
		include "include/trailer.inc.php";
		die();
	}
	if (isset($_GET[$fieldname]))
		$overload_data[$id_field] = $_GET[$fieldname];
	else
		$overload_data[$id_field] = "";
}
if (!isset($day) || !isset($month) || !isset($year))
{
	$day   = date("d");
	$month = date("m");
	$year  = date("Y");
}
get_planning_area_values($area);
if (authGetUserLevel(getUserName(), -1) < 1)
{
	showAccessDenied($back);
	exit();
}
if (check_begin_end_bookings($day, $month, $year))
{
	if ((Settings::get("authentification_obli") == 0) && (getUserName() == ''))
		$type_session = "no_session";
	else
		$type_session = "with_session";
	showNoBookings($day, $month, $year, $back."&amp;Err=yes");
	exit();
}
if ($type_affichage_reser == 0)
{
	$period = isset($_GET["period"]) ? $_GET["period"] : NULL;
	if (isset($period))
		settype($period,"integer");
	$dur_units = isset($_GET["dur_units"]) ? $_GET["dur_units"] : NULL;
	$all_day = isset($_GET["all_day"]) ? $_GET["all_day"] : NULL;
	if ($enable_periods == 'y')
	{
		$resolution = 60;
		$hour = 12;
		$minute = $period;
		$max_periods = count($periods_name);
		if ( $dur_units == "periods" && ($minute + $duration) > $max_periods)
			$duration = (24 * 60 * floor($duration / $max_periods)) + ($duration % $max_periods);
	}
	$units = 1.0;
	switch($dur_units)
	{
		case "years":
		$units *= 52;
		case "weeks":
		$units *= 7;
		case "days":
		$units *= 24;
		case "hours":
		$units *= 60;
		case "periods":
		case "minutes":
		$units *= 60;
		case "seconds":
		break;
	}
	if (isset($all_day) && ($all_day == "yes") && ($dur_units != "days"))
	{
		if ($enable_periods == 'y')
		{
			$starttime = mktime(12, 0, 0, $month, $day, $year);
			$endtime   = mktime(12, $max_periods, 0, $month, $day, $year);
		}
		else
		{
			$starttime = mktime($morningstarts, 0, 0, $month, $day  , $year);
			$endtime   = mktime($eveningends, 0, $resolution, $month, $day, $year);
		}
	}
	else
	{
		if (!$twentyfourhour_format)
		{
			if (isset($ampm) && ($ampm == "pm"))
				$hour += 12;
		}
		$starttime = mktime($hour, $minute, 0, $month, $day, $year);
		$endtime   = mktime($hour, $minute, 0, $month, $day, $year) + ($units * $duration);
		if ($endtime <= $starttime)
			$erreur = 'y';
		$diff = $endtime - $starttime;
		if (($tmp = $diff % $resolution) != 0 || $diff == 0)
			$endtime += $resolution - $tmp;
	}
}
else
{
	if ($enable_periods == 'y')
	{
		$resolution = 60;
		$hour = 12;
		$_GET["end_hour"] = 12;
		if (isset($_GET["period"]))
			$minute = $_GET["period"];
		else
			$erreur = 'y';
		if (isset($_GET["end_period"]))
			$_GET["end_minute"] = $_GET["end_period"] + 1;
		else
			$erreur = 'y';
	}
	if (!isset($_GET["end_day"]) || !isset($_GET["end_month"]) || !isset($_GET["end_year"]) || !isset($_GET["end_"]))
		$erreur = 'y';
	else
	{
		$end_day = $_GET["end_day"];
		$end_year = $_GET["end_year"];
		$end_month = $_GET["end_month"];
		$fin = array();
		$fin = explode(':', $_GET["end_"]);
		$end_hour = $fin[0];
		$end_minute = $fin[1];
		settype($end_month, "integer");
		settype($end_day, "integer");
		settype($end_year, "integer");
		settype($end_minute, "integer");
		settype($end_hour, "integer");
		$minyear = strftime("%Y", Settings::get("begin_bookings"));
		$maxyear = strftime("%Y", Settings::get("end_bookings"));
		if ($end_day < 1)
			$end_day = 1;
		if ($end_day > 31)
			$end_day = 31;
		if ($end_month < 1)
			$end_month = 1;
		if ($end_month > 12)
			$end_month = 12;
		if ($end_year < $minyear)
			$end_year = $minyear;
		if ($end_year > $maxyear)
			$end_year = $maxyear;
		if (!checkdate($end_month, $end_day, $end_year))
			$erreur = 'y';
		$starttime = mktime($hour, $minute, 0, $month, $day, $year);
		$endtime   = mktime($end_hour, $end_minute, 0, $end_month, $end_day, $end_year);
		if ($endtime <= $starttime)
			$erreur = 'y';
		$diff = $endtime - $starttime;
		if (($tmp = $diff % $resolution) != 0 || $diff == 0)
			$endtime += $resolution - $tmp;
	}
}
if ($endtime <= $starttime)
	$erreur = 'y';
if ($erreur == 'y')
{
	print_header();
	echo "<h2>Erreur dans la date de fin de r&eacute;servation</h2>";
	echo "<a href=\"".$back."&amp;Err=yes\">".get_vocab('returnprev')."</a>";
	include "include/trailer.inc.php";
	die();
}
if (isset($rep_type) && isset($rep_end_month) && isset($rep_end_day) && isset($rep_end_year))
{
	$rep_enddate = mktime($hour, $minute, 0, $rep_end_month, $rep_end_day, $rep_end_year);
	if ($rep_enddate > Settings::get("end_bookings"))
		$rep_enddate = Settings::get("end_bookings");
}
else
	$rep_type = 0;
if (!isset($rep_day))
	$rep_day = "";
$day_temp   = date("d",$starttime);
$month_temp = date("m",$starttime);
$year_temp  = date("Y",$starttime);
$starttime_midnight = mktime(0, 0, 0, $month_temp, $day_temp, $year_temp);
$day_temp   = date("d",$endtime);
$month_temp = date("m",$endtime);
$year_temp  = date("Y",$endtime);
$endtime_midnight = mktime(0, 0, 0, $month_temp, $day_temp, $year_temp);
if (resa_est_hors_reservation($starttime_midnight , $endtime_midnight))
{
	print_header();
	echo "<h2>Erreur dans la date de début ou de fin de réservation</h2>";
	echo "<a href=\"".$back."&amp;Err=yes\">".get_vocab('returnprev')."</a>";
	include "include/trailer.inc.php";
	die();
}
$rep_opt = "";
if ($rep_type == 2)
{
	for ($i = 0; $i < 7; $i++)
		$rep_opt .= empty($rep_day[$i]) ? "0" : "1";
}
if ($rep_type != 0)
	$reps = mrbsGetRepeatEntryList($starttime, isset($rep_enddate) ? $rep_enddate : 0, $rep_type, $rep_opt, $max_rep_entrys, $rep_num_weeks, $rep_jour_c, $area, $rep_month_abs1, $rep_month_abs2);
$repeat_id = 0;
if (isset($id) && ($id != 0))
{
	$ignore_id = $id;
	$repeat_id = grr_sql_query1("SELECT repeat_id FROM ".TABLE_PREFIX."_entry WHERE id=$id");
	if ($repeat_id < 0)
		$repeat_id = 0;
}
else
	$ignore_id = 0;
if (!grr_sql_mutex_lock("".TABLE_PREFIX."_entry"))
	fatal_error(1, get_vocab('failed_to_acquire'));
$date_now = time();
$error_booking_in_past = 'no';
$error_booking_room_out = 'no';
$error_duree_max_resa_area = 'no';
$error_delais_max_resa_room = 'no';
$error_delais_min_resa_room = 'no';
$error_date_option_reservation = 'no';
$error_chevaussement = 'no';
$error_qui_peut_reserver_pour = 'no';
$error_heure_debut_fin = 'no';
foreach ( $_GET['rooms'] as $room_id )
{
	if ($rep_type != 0 && !empty($reps))
	{
		$diff = $endtime - $starttime;
		if (!grrCheckOverlap($reps, $diff))
			$error_chevaussement = 'yes';
		$i = 0;
		while (($i < count($reps)) && ($error_booking_in_past == 'no') && ($error_duree_max_resa_area == 'no') && ($error_delais_max_resa_room == 'no') && ($error_delais_min_resa_room == 'no') && ($error_date_option_reservation == 'no') && ($error_qui_peut_reserver_pour == 'no') && ($error_heure_debut_fin == 'no'))
		{
			if ((authGetUserLevel(getUserName(),-1) < 2) && (auth_visiteur(getUserName(),$room_id) == 0))
				$error_booking_room_out = 'yes';
			if (!(verif_booking_date(getUserName(), -1, $room_id, $reps[$i], $date_now, $enable_periods)))
				$error_booking_in_past = 'yes';
			if (!(verif_duree_max_resa_area(getUserName(), $room_id, $starttime, $endtime)))
				$error_duree_max_resa_aera = 'yes';
			if (!(verif_delais_max_resa_room(getUserName(), $room_id, $reps[$i])))
				$error_delais_max_resa_room = 'yes';
			if (!(verif_delais_min_resa_room(getUserName(), $room_id, $reps[$i])))
				$error_delais_min_resa_room = 'yes';
			if (!(verif_date_option_reservation($option_reservation, $reps[$i])))
				$error_date_option_reservation = 'yes';
			if (!(verif_qui_peut_reserver_pour($room_id, getUserName(), $beneficiaire)))
				$error_qui_peut_reserver_pour = 'yes';
			if (!(verif_heure_debut_fin($reps[$i], $reps[$i]+$diff, $area)))
				$error_heure_debut_fin = 'yes';
			$i++;
		}
	}
	else
	{
		if ((authGetUserLevel(getUserName(),-1) < 2) && (auth_visiteur(getUserName(),$room_id) == 0))
			$error_booking_room_out = 'yes';
		if (isset($id) && ($id != 0))
		{
			if (!(verif_booking_date(getUserName(), $id, $room_id, $starttime, $date_now, $enable_periods, $endtime)))
				$error_booking_in_past = 'yes';
		}
		else
		{
			if (!(verif_booking_date(getUserName(), -1, $room_id, $starttime, $date_now, $enable_periods)))
				$error_booking_in_past = 'yes';
		}
		if (!(verif_duree_max_resa_area(getUserName(), $room_id, $starttime, $endtime)))
			$error_duree_max_resa_area = 'yes';
		if (!(verif_delais_max_resa_room(getUserName(), $room_id, $starttime)))
			$error_delais_max_resa_room = 'yes';
		if (!(verif_delais_min_resa_room(getUserName(), $room_id, $starttime)))
			$error_delais_min_resa_room = 'yes';
		if (!(verif_date_option_reservation($option_reservation, $starttime)))
			$error_date_option_reservation = 'yes';
		if (!(verif_qui_peut_reserver_pour($room_id, getUserName(), $beneficiaire)))
			$error_qui_peut_reserver_pour = 'yes';
		if (!(verif_heure_debut_fin($starttime, $endtime, $area)))
			$error_heure_debut_fin = 'yes';
		if (resa_est_hors_reservation2($starttime, $endtime, $area))
			$error_heure_debut_fin = 'yes';
	}
	$statut_room = grr_sql_query1("SELECT statut_room from ".TABLE_PREFIX."_room where id = '$room_id'");
	if (($statut_room == "0") && authGetUserLevel(getUserName(),$room_id) < 3)
		$error_booking_room_out = 'yes';
	if (!verif_acces_ressource(getUserName(), $room_id))
		$error_booking_room_out = 'yes';
}
$err = "";
if (($error_booking_in_past == 'no') && ($error_chevaussement == 'no') && ($error_duree_max_resa_area == 'no') && ($error_delais_max_resa_room == 'no') && ($error_delais_min_resa_room == 'no')  && ($error_date_option_reservation == 'no') && ($error_qui_peut_reserver_pour == 'no') && ($error_heure_debut_fin == 'no'))
{
	foreach ($_GET['rooms'] as $room_id)
	{
		if ($rep_type != 0 && !empty($reps))
		{
			if (count($reps) < $max_rep_entrys)
			{
				$diff = $endtime - $starttime;
				for ($i = 0; $i < count($reps); $i++)
				{
					if (isset($_GET['del_entry_in_conflict']) && ($_GET['del_entry_in_conflict'] == 'yes'))
						grrDelEntryInConflict($room_id, $reps[$i], $reps[$i] + $diff, $ignore_id, $repeat_id, 0);
					if ($i == (count($reps) - 1))
						$tmp = mrbsCheckFree($room_id, $reps[$i], $reps[$i] + $diff, $ignore_id, $repeat_id);
					else
						$tmp = mrbsCheckFree($room_id, $reps[$i], $reps[$i] + $diff, $ignore_id, $repeat_id);
					if (!empty($tmp))
						$err = $err . $tmp;
				}
			}
			else
			{
				$err .= get_vocab("too_may_entrys") . "<p>";
				$hide_title  = 1;
			}
		}
		else
		{
			if (isset($_GET['del_entry_in_conflict']) && ($_GET['del_entry_in_conflict'] == 'yes'))
				grrDelEntryInConflict($room_id, $starttime, $endtime-1, $ignore_id, $repeat_id, 0);
			$err .= mrbsCheckFree($room_id, $starttime, $endtime - 1, $ignore_id, $repeat_id);
		}
	}
}
if (empty($err) && ($error_booking_in_past == 'no') && ($error_duree_max_resa_area == 'no') && ($error_delais_max_resa_room == 'no') && ($error_delais_min_resa_room == 'no') && ($error_booking_room_out == 'no') && ($error_date_option_reservation == 'no') && ($error_chevaussement == 'no') && ($error_qui_peut_reserver_pour == 'no') && ($error_heure_debut_fin == 'no'))
{
	$compt_room = 0;
	foreach ($_GET['rooms'] as $room_id)
	{
		$area = mrbsGetRoomArea($room_id);
		if (isset($id) && ($id != 0))
		{
			if (!getWritable($beneficiaire, getUserName(), $id))
			{
				showAccessDenied($back);
				exit;
			}
		}
		if (authUserAccesArea(getUserName(), $area) == 0)
		{
			showAccessDenied($back);
			exit();
		}
		if (isset($id) and ($id != 0))
			$compt = 0;
		else
			$compt = 1;
		if ($rep_type != 0 && !empty($reps))
		{
			if (UserRoomMaxBooking(getUserName(), $room_id, count($reps) - 1 + $compt + $compt_room) == 0)
			{
				showAccessDeniedMaxBookings($day, $month, $year, $room_id, $back);
				exit();
			}
			else
				$compt_room += 1;
		}
		else
		{
			if (UserRoomMaxBooking(getUserName(), $room_id, $compt + $compt_room) == 0)
			{
				showAccessDeniedMaxBookings($day, $month, $year, $room_id, $back);
				exit();
			}
			else
				$compt_room += 1;
		}
	}
	foreach ($_GET['rooms'] as $room_id)
	{
		$moderate = grr_sql_query1("SELECT moderate FROM ".TABLE_PREFIX."_room WHERE id = '".$room_id."'");
		if ($moderate == 1)
		{
			$send_mail_moderate = 1;
			if (isset($id))
			{
				$old_entry_moderate =  grr_sql_query1("SELECT moderate FROM ".TABLE_PREFIX."_entry where id='".$id."'");
				if (authGetUserLevel(getUserName(),$room_id) < 3)
					$entry_moderate = 1;
				else
					$entry_moderate = $old_entry_moderate;
				if ($entry_moderate != 1)
					$send_mail_moderate = 0;
			}
			else
			{
				if (authGetUserLevel(getUserName(),$room_id) < 3)
					$entry_moderate = 1;
				else
				{
					$entry_moderate = 0;
					$send_mail_moderate = 0;
				}
			}
		}
		else
		{
			$entry_moderate = 0;
			$send_mail_moderate = 0;
		}
		if ($rep_type != 0)
		{
			mrbsCreateRepeatingEntrys($starttime, $endtime, $rep_type, $rep_enddate, $rep_opt, $room_id, $create_by, $beneficiaire, $beneficiaire_ext, $name, $type, $description, $rep_num_weeks, $option_reservation, $overload_data, $entry_moderate, $rep_jour_c, $courrier, $rep_month_abs1, $rep_month_abs2);
			if (Settings::get("automatic_mail") == 'yes')
			{
				if (isset($id) && ($id != 0))
				{
					if ($send_mail_moderate)
						$message_error = send_mail($id_first_resa, 5, $dformat);
					else
						$message_error = send_mail($id_first_resa, 2, $dformat);
				}
				else
				{
					if ($send_mail_moderate)
						$message_error = send_mail($id_first_resa, 5, $dformat);
					else
						$message_error = send_mail($id_first_resa, 1, $dformat);
				}
			}
		}
		else
		{
			if ($repeat_id > 0)
				$entry_type = 2;
			else
				$entry_type = 0;
			mrbsCreateSingleEntry($starttime, $endtime, $entry_type, $repeat_id, $room_id, $create_by, $beneficiaire, $beneficiaire_ext, $name, $type, $description, $option_reservation, $overload_data, $entry_moderate, $rep_jour_c, $statut_entry, $keys, $courrier);
			$new_id = grr_sql_insert_id();
			if (Settings::get("automatic_mail") == 'yes')
			{
				if (isset($id) && ($id != 0))
				{
					if ($send_mail_moderate)
						$message_error = send_mail($new_id,5,$dformat);
					else
						$message_error = send_mail($new_id,2,$dformat);
				}
				else
				{
					if ($send_mail_moderate)
						$message_error = send_mail($new_id,5,$dformat);
					else
						$message_error = send_mail($new_id,1,$dformat);
				}
			}
		}
	}
	if (isset($id) && ($id != 0))
	{
		if ($rep_type != 0)
			mrbsDelEntry(getUserName(), $id, "series", 1);
		else
			mrbsDelEntry(getUserName(), $id, NULL, 1);
	}
	grr_sql_mutex_unlock("".TABLE_PREFIX."_entry");
	$area = mrbsGetRoomArea($room_id);
	$_SESSION['displ_msg'] = 'yes';
	if ($message_error != "")
		$_SESSION['session_message_error'] = $message_error;
	Header("Location: ".$page.".php?year=$year&month=$month&day=$day&area=$area&room=$room_back");
	exit;
}

grr_sql_mutex_unlock("".TABLE_PREFIX."_entry");

if ($error_booking_in_past == 'yes')
{
	$str_date = utf8_strftime("%d %B %Y, %H:%M", $date_now);
	print_header();
	echo "<h2>" . get_vocab("booking_in_past") . "</h2>";
	if ($rep_type != 0 && !empty($reps))
		echo "<p>" . get_vocab("booking_in_past_explain_with_periodicity") . $str_date."</p>";
	else
		echo "<p>" . get_vocab("booking_in_past_explain") . $str_date."</p>";
	echo "<a href=\"".$back."&amp;Err=yes\">".get_vocab('returnprev')."</a>";
	include "include/trailer.inc.php";
	die();
}
if ($error_duree_max_resa_area == 'yes')
{
	$area_id = grr_sql_query1("SELECT area_id FROM ".TABLE_PREFIX."_room WHERE id='".protect_data_sql($room_id)."'");
	$duree_max_resa_area = grr_sql_query1("SELECT duree_max_resa_area FROM ".TABLE_PREFIX."_area WHERE id='".$area_id."'");
	print_header();
	$temps_format = $duree_max_resa_area*60;
	toTimeString($temps_format, $dur_units, true);
	echo "<h2>" . get_vocab("error_duree_max_resa_area").$temps_format ." " .$dur_units."</h2>";
	echo "<a href=\"".$back."&amp;Err=yes\">".get_vocab('returnprev')."</a>";
	include "include/trailer.inc.php";
	die();
}

if ($error_delais_max_resa_room == 'yes')
{
	print_header();
	echo "<h2>" . get_vocab("error_delais_max_resa_room") ."</h2>";
	echo "<a href=\"".$back."&amp;Err=yes\">".get_vocab('returnprev')."</a>";
	include "include/trailer.inc.php";
	die();
}
if ($error_chevaussement == 'yes')
{
	print_header();
	echo "<h2>" . get_vocab("error_chevaussement") ."</h2>";
	echo "<a href=\"".$back."&amp;Err=yes\">".get_vocab('returnprev')."</a>";
	include "include/trailer.inc.php";
	die();
}
if ($error_delais_min_resa_room == 'yes')
{
	print_header();
	echo "<h2>" . get_vocab("error_delais_min_resa_room") ."</h2>";
	echo "<a href=\"".$back."&amp;Err=yes\">".get_vocab('returnprev')."</a>";
	include "include/trailer.inc.php";
	die();
}
if ($error_date_option_reservation == 'yes')
{
	print_header();
	echo "<h2>" . get_vocab("error_date_confirm_reservation") ."</h2>";
	echo "<a href=\"".$back."&amp;Err=yes\">".get_vocab('returnprev')."</a>";
	include "include/trailer.inc.php";
	die();
}
if ($error_booking_room_out == 'yes')
{
	print_header();
	echo "<h2>" . get_vocab("norights") . "</h2>";
	echo "<p><b>" . get_vocab("tentative_reservation_ressource_indisponible") . "</b></p>";
	echo "<a href=\"".$back."&amp;Err=yes\">".get_vocab('returnprev')."</a>";
	include "include/trailer.inc.php";
	die();
}
if ($error_qui_peut_reserver_pour == 'yes')
{
	print_header();
	echo "<h2>" . get_vocab("error_qui_peut_reserver_pour") ."</h2>";
	echo "<a href=\"".$back."&amp;Err=yes\">".get_vocab('returnprev')."</a>";
	include "include/trailer.inc.php";
	die();
}
if ($error_heure_debut_fin == 'yes')
{
	print_header();
	echo "<h2>" . get_vocab("error_heure_debut_fin") ."</h2>";
	echo $start_day;
	echo "<a href=\"".$back."&amp;Err=yes\">".get_vocab('returnprev')."</a>";
	include "include/trailer.inc.php";
	die();
}
if (strlen($err))
{
	print_header();
	echo "<h2>" . get_vocab("sched_conflict") . "</h2>";
	if (!isset($hide_title))
	{
		echo get_vocab("conflict");
		echo "<UL>";
	}
	echo $err;
	if (!isset($hide_title))
		echo "</UL>";
	if (authGetUserLevel(getUserName(),$area,'area') >= 4)
		echo "<center><table border=\"1\" cellpadding=\"10\" cellspacing=\"1\"><tr><td class='avertissement'><h3><a href='".traite_grr_url("","y")."edit_entry_handler.php?".$_SERVER['QUERY_STRING']."&amp;del_entry_in_conflict=yes'>".get_vocab("del_entry_in_conflict")."</a></h4></td></tr></table></center><br />";
}
echo "<a href=\"".$back."&amp;Err=yes\">".get_vocab('returnprev')."</a><p>";
include "include/trailer.inc.php"; ?>
