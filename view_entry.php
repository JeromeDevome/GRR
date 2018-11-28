<?php
/**
 * view_entry.php
 * Interface de visualisation d'une réservation
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2018-05-28 10:30$
 * @author    Laurent Delineau & JeromeB & Yan Naessens
 * @copyright Copyright 2003-2018 Team DEVOME - JeromeB
 * @link      http://www.gnu.org/licenses/licenses.html
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */
$grr_script_name = 'view_entry.php'; // echo '<div>'; ??? YN
include_once('include/connect.inc.php');
include_once('include/config.inc.php');
include_once('include/functions.inc.php');
include_once('include/'.$dbsys.'.inc.php');
include_once('include/misc.inc.php');
include_once('include/mrbs_sql.inc.php');
require_once('include/settings.class.php');
if (!Settings::load())
	die("Erreur chargement settings");
require_once("./include/session.inc.php");

// Paramètres langage
include "include/language.inc.php";

$page = verif_page();

$fin_session = 'n';
if (!grr_resumeSession())
	$fin_session = 'y';
if (($fin_session == 'y') && (Settings::get("authentification_obli") == 1))
{
	header("Location: ./logout.php?auto=1&url=$url");
	die();
}
if ((Settings::get("authentification_obli") == 0) && (getUserName() == ''))
	$type_session = "no_session";
else
	$type_session = "with_session";
unset($reg_statut_id);
$reg_statut_id = isset($_GET["statut_id"]) ? $_GET["statut_id"] : "";
if (isset($_GET["id"]))
{
	$id = $_GET["id"];
	settype($id, "integer");
}
else
	die();
$back = '';
if (isset($_SERVER['HTTP_REFERER']))
	$back = htmlspecialchars_decode($_SERVER['HTTP_REFERER']);
// echo $back;
if (isset($_GET["action_moderate"])){
    // ici on a l'id de la réservation, on peut donc construire un lien de retour complet, à la bonne date et avec la ressource précise
    /* $page = $_GET["page"];
    $sql = grr_sql_query("SELECT start_time,room_id FROM ".TABLE_PREFIX."_entry WHERE id ='".$id."'");
    $res = grr_sql_row($sql,0); // en principe on ne devrait avoir qu'une réponse ici
    print_r($res);
    exit;*/
    if (strstr ($back, 'view_entry.php'))
    {
        $sql = "SELECT start_time, room_id FROM ".TABLE_PREFIX."_entry WHERE id=". $id;
        $res = grr_sql_query($sql);
        if (!$res)
            fatal_error(0, grr_sql_error());
        if (grr_sql_count($res) >= 1)
        {
            $row1 = grr_sql_row($res, 0);
            $year = date ('Y', $row1['0']);
            $month = date ('m', $row1['0']);
            $day = date ('d', $row1['0']);
            $page = (isset($_GET['page']))? $_GET['page'] : "day";
            $back = $page.'.php?year='.$year.'&month='.$month.'&day='.$day;
            if (($page == "week_all") || ($page == "month_all") || ($page == "month_all2") || ($page == "day"))
                $back .= "&area=".mrbsGetRoomArea($row1['1']);
            if (($page == "week") || ($page == "month"))
                $back .= "&room=".$row1['1'];
        }
        else
            $back = $page.".php";
    }
	moderate_entry_do($id,$_GET["moderate"], $_GET["description"]);
//  echo $back;
	header("Location: ".$back);
    die();
}
$sql = "SELECT ".TABLE_PREFIX."_entry.name,
".TABLE_PREFIX."_entry.description,
".TABLE_PREFIX."_entry.beneficiaire,
".TABLE_PREFIX."_room.room_name,
".TABLE_PREFIX."_area.area_name,
".TABLE_PREFIX."_entry.type,
".TABLE_PREFIX."_entry.room_id,
".TABLE_PREFIX."_entry.repeat_id,
".grr_sql_syntax_timestamp_to_unix("".TABLE_PREFIX."_entry.timestamp").",
(".TABLE_PREFIX."_entry.end_time - ".TABLE_PREFIX."_entry.start_time),
".TABLE_PREFIX."_entry.start_time,
".TABLE_PREFIX."_entry.end_time,
".TABLE_PREFIX."_area.id,
".TABLE_PREFIX."_entry.statut_entry,
".TABLE_PREFIX."_room.delais_option_reservation,
".TABLE_PREFIX."_entry.option_reservation, " .
"".TABLE_PREFIX."_entry.moderate,
".TABLE_PREFIX."_entry.beneficiaire_ext,
".TABLE_PREFIX."_entry.create_by,
".TABLE_PREFIX."_entry.jours,
".TABLE_PREFIX."_room.active_ressource_empruntee,
".TABLE_PREFIX."_entry.clef,
".TABLE_PREFIX."_entry.courrier,
".TABLE_PREFIX."_room.active_cle
FROM ".TABLE_PREFIX."_entry, ".TABLE_PREFIX."_room, ".TABLE_PREFIX."_area
WHERE ".TABLE_PREFIX."_entry.room_id = ".TABLE_PREFIX."_room.id
AND ".TABLE_PREFIX."_room.area_id = ".TABLE_PREFIX."_area.id
AND ".TABLE_PREFIX."_entry.id='".$id."'";

$sql_backup = "SELECT ".TABLE_PREFIX."_entry_moderate.name,
".TABLE_PREFIX."_entry_moderate.description,
".TABLE_PREFIX."_entry_moderate.beneficiaire,
".TABLE_PREFIX."_room.room_name,
".TABLE_PREFIX."_area.area_name,
".TABLE_PREFIX."_entry_moderate.type,
".TABLE_PREFIX."_entry_moderate.room_id,
".TABLE_PREFIX."_entry_moderate.repeat_id,
".grr_sql_syntax_timestamp_to_unix("".TABLE_PREFIX."_entry_moderate.timestamp").",
(".TABLE_PREFIX."_entry_moderate.end_time - ".TABLE_PREFIX."_entry_moderate.start_time),
".TABLE_PREFIX."_entry_moderate.start_time,
".TABLE_PREFIX."_entry_moderate.end_time,
".TABLE_PREFIX."_area.id,
".TABLE_PREFIX."_entry_moderate.statut_entry,
".TABLE_PREFIX."_room.delais_option_reservation,
".TABLE_PREFIX."_entry_moderate.option_reservation, " .
"".TABLE_PREFIX."_entry_moderate.moderate,
".TABLE_PREFIX."_entry_moderate.beneficiaire_ext,
".TABLE_PREFIX."_entry_moderate.create_by
FROM ".TABLE_PREFIX."_entry_moderate, ".TABLE_PREFIX."_room, ".TABLE_PREFIX."_area
WHERE ".TABLE_PREFIX."_entry_moderate.room_id = ".TABLE_PREFIX."_room.id
AND ".TABLE_PREFIX."_room.area_id = ".TABLE_PREFIX."_area.id
AND ".TABLE_PREFIX."_entry_moderate.id='".$id."'";
$res = grr_sql_query($sql);
if (!$res)
	fatal_error(0, grr_sql_error());
if (grr_sql_count($res) < 1)
{
	$reservation_is_delete = 'y';
	$was_del = TRUE;
	$res_backup = grr_sql_query($sql_backup);
	if (!$res_backup)
		fatal_error(0, grr_sql_error());
	$row = grr_sql_row($res_backup, 0);
	grr_sql_free($res_backup);
}
else
{
	$was_del = FALSE;
	$row = grr_sql_row($res, 0);
}
grr_sql_free($res);
$breve_description 	= $row[0];
$description  		= bbcode(htmlspecialchars($row[1]),'');
$beneficiaire    	= htmlspecialchars($row[2]);
$room_name    		= htmlspecialchars($row[3]);
$area_name    		= htmlspecialchars($row[4]);
$type         		= $row[5];
$room_id      		= $row[6];
$repeat_id    		= $row[7];
$updated      		= time_date_string($row[8], $dformat);
$duration     		= $row[9];
$area      			= $row[12];
$statut_id 			= $row[13];
$delais_option_reservation 	= $row[14];
$option_reservation 		= $row[15];
$moderate 					= $row[16];
$beneficiaire_ext   		= htmlspecialchars($row[17]);
$create_by    				= htmlspecialchars($row[18]);
$jour_cycle    				= htmlspecialchars($row[19]);
$active_ressource_empruntee = htmlspecialchars($row[20]);
$keys						= $row[21];
$courrier					= $row[22];
$active_cle					= $row[23];
$rep_type 					= 0;
$verif_display_email 		= verif_display_email(getUserName(), $room_id);
if ($verif_display_email)
	$option_affiche_nom_prenom_email = "withmail";
else
	$option_affiche_nom_prenom_email = "nomail";
if (($fin_session == 'n') && (getUserName()!='') && (authGetUserLevel(getUserName(), $room_id) >= 3) && (isset($_GET['ok'])))
{
	if (!$was_del)
	{
		if ($reg_statut_id != "")
		{
			$upd1 = "UPDATE ".TABLE_PREFIX."_entry SET statut_entry='-' WHERE room_id = '".$room_id."'";
			if (grr_sql_command($upd1) < 0)
				fatal_error(0, grr_sql_error());
			$upd2 = "UPDATE ".TABLE_PREFIX."_entry SET statut_entry='$reg_statut_id' WHERE id = '".$id."'";
			if (grr_sql_command($upd2) < 0)
				fatal_error(0, grr_sql_error());
		}
		if (isset($_GET['clef']))
		{
			$clef = 1;
			$upd = "UPDATE ".TABLE_PREFIX."_entry SET clef='$clef' WHERE id = '".$id."'";
			if (grr_sql_command($upd) < 0)
				fatal_error(0, grr_sql_error());
		}
		else
		{
			$clef = 0;
			$upd = "UPDATE ".TABLE_PREFIX."_entry SET clef='$clef' WHERE id = '".$id."'";
			if (grr_sql_command($upd) < 0)
				fatal_error(0, grr_sql_error());
		}
		if (isset($_GET['courrier']))
		{
			$courrier = 1;
			$upd = "UPDATE ".TABLE_PREFIX."_entry SET courrier='$courrier' WHERE id = '".$id."'";
			if (grr_sql_command($upd) < 0)
				fatal_error(0, grr_sql_error());
		}
		else
		{
			$courrier = 0;
			$upd = "UPDATE ".TABLE_PREFIX."_entry SET courrier='$courrier' WHERE id = '".$id."'";
			if (grr_sql_command($upd) < 0)
				fatal_error(0, grr_sql_error());
		}
		if ((isset($_GET["envoyer_mail"])) && (Settings::get("automatic_mail") == 'yes'))
		{
			$_SESSION['session_message_error'] = send_mail($id, 7, $dformat);
			if ($_SESSION['session_message_error'] == "")
			{
				$_SESSION['displ_msg'] = "yes";
				$_SESSION["msg_a_afficher"] = get_vocab("un email envoye")." ".$_GET["mail_exist"];
			}
		}
		header("Location: ".$_GET['back']."");
		die();
	}
}
if (!isset($day) || !isset($month) || !isset($year))
{
	$day   = date("d");
	$month = date("m");
	$year  = date("Y");
}

if (@file_exists("language/lang_subst_".$area.".".$locale))
	include "language/lang_subst_".$area.".".$locale;
if ((authGetUserLevel(getUserName(), -1) < 1) and (Settings::get("authentification_obli") == 1))
{
	showAccessDenied($back);
	exit();
}
if (authUserAccesArea(getUserName(), $area) == 0)
{
	if (isset($reservation_is_delete))
		showNoReservation($day, $month, $year, $back);
	else
		showAccessDenied($back);
	exit();
}
$date_now = time();

get_planning_area_values($area);
if ($enable_periods == 'y')
	list( $start_period, $start_date) = period_date_string($row[10]);
else
	$start_date = time_date_string($row[10],$dformat);
if ($enable_periods == 'y')
	list( , $end_date) =  period_date_string($row[11], -1);
else
	$end_date = time_date_string($row[11], $dformat);
if ($beneficiaire != "")
	$mail_exist = grr_sql_query1("SELECT email FROM ".TABLE_PREFIX."_utilisateurs WHERE login='$beneficiaire'");
else
{
	$tab_benef = donne_nom_email($beneficiaire_ext);
	$mail_exist = $tab_benef["email"];
}
if ($enable_periods == 'y')
	toPeriodString($start_period, $duration, $dur_units);
else
	toTimeString($duration, $dur_units);
if (strstr ($back, 'view_entry.php'))
{
	$sql = "SELECT start_time, room_id FROM ".TABLE_PREFIX."_entry WHERE id=". $id;
	$res = grr_sql_query($sql);
	if (!$res)
		fatal_error(0, grr_sql_error());
	if (grr_sql_count($res) >= 1)
	{
		$row1 = grr_sql_row($res, 0);
		$year = date ('Y', $row1['0']);
		$month = date ('m', $row1['0']);
		$day = date ('d', $row1['0']);
		$back = $page.'.php?year='.$year.'&month='.$month.'&day='.$day;
		if ((isset($_GET["page"])) && (($_GET["page"] == "week") || ($_GET["page"] == "month") || ($_GET["page"] == "week_all") || ($_GET["page"] == "month_all")))
			$back .= "&area=".mrbsGetRoomArea($row1['1']);
		if ((isset($_GET["page"])) && (($_GET["page"] == "week") || ($_GET["page"] == "month")))
			$back .= "&room=".$row1['1'];
	}
	else
		$back = "";
}
if (Settings::get("display_level_view_entry") == '1')
{
	print_header($day, $month, $year, $type_session);
	if ($back != "")
		echo '<div><a href="',$back,'">',get_vocab("returnprev"),'</a></div>',PHP_EOL;
}
echo '<fieldset><legend style="font-size:12pt;font-weight:bold">'.get_vocab('entry').get_vocab('deux_points').affichage_lien_resa_planning($breve_description, $id).'</legend>'."\n"; ?>
<table border="0">
	<tr>
		<td>
			<b>
				<?php
				echo get_vocab("description");
				?>
			</b>
		</td>
		<td>
			<?php
			echo nl2br($description);
			?>
		</td>
	</tr>
	<?php
	if (!$was_del)
	{
		$overload_data = mrbsEntryGetOverloadDesc($id);
		foreach ($overload_data as $fieldname=>$fielddata)
		{
			if ($fielddata["confidentiel"] == 'n')
				$affiche_champ = 'y';
			else
			{
				if (($fin_session != 'n') || (getUserName()==''))
					$affiche_champ = 'n';
				else
				{
					if ((authGetUserLevel(getUserName(), $room_id) >= 4) || ($beneficiaire == getUserName()))
						$affiche_champ = 'y';
					else
						$affiche_champ = 'n';
					if ($affiche_champ == 'y')
					{
						echo "<tr><td><b>".bbcode(htmlspecialchars($fieldname).get_vocab("deux_points"), '')."</b></td>\n";
						echo "<td>".bbcode(htmlspecialchars($fielddata["valeur"]), '')."</td></tr>\n";
					}
				}
			}
		}
	}
	?>
	<tr>
		<td>
			<b>
				<?php
				echo get_vocab("room"),get_vocab("deux_points");
				?>
			</b>
		</td>
		<td>
			<?php
			echo nl2br($area_name . " - " . $room_name);
			?>
		</td>
	</tr>
	<tr>
		<td>
			<b>
				<?php
				echo get_vocab("start_date"),get_vocab("deux_points");
				?>
			</b>
		</td>
		<td>
			<?php
			echo $start_date;
			?>
		</td>
	</tr>
	<tr>
		<td>
			<b>
				<?php
				echo get_vocab("duration");
				?>
			</b>
		</td>
		<td>
			<?php
			echo $duration ,' ',$dur_units;
			?>
		</td>
	</tr>
	<tr>
		<td>
			<b>
				<?php
				echo get_vocab("end_date");
				?>
			</b>
		</td>
		<td>
			<?php
			echo $end_date;
			?>
		</td>
	</tr>
	<?php
	echo '<tr>',PHP_EOL,'<td><b>',get_vocab("type"),get_vocab("deux_points"),'</b></td>',PHP_EOL;
	$type_name = grr_sql_query1("SELECT type_name from ".TABLE_PREFIX."_type_area where type_letter='".$type."'");
	if ($type_name == -1)
		$type_name = "?$type?";
	echo '<td>',$type_name,'</td>',PHP_EOL,'</tr>',PHP_EOL;
	if ($beneficiaire != $create_by)
	{
		?>
		<tr>
			<td>
				<b>
					<?php
					echo get_vocab("reservation_au_nom_de"),get_vocab("deux_points");
					?>
				</b>
			</td>
			<td>
				<?php
				echo affiche_nom_prenom_email($beneficiaire, $beneficiaire_ext, $option_affiche_nom_prenom_email);
				?>
			</td>
		</tr>
		<?php
	}
	?>
	<tr>
		<td>
			<b>
				<?php
				echo get_vocab("created_by"),get_vocab("deux_points")
				?>
			</b>
		</td>
		<td>
			<?php
			echo affiche_nom_prenom_email($create_by, "", $option_affiche_nom_prenom_email);
			if ($active_ressource_empruntee == 'y')
			{
				$id_resa = grr_sql_query1("SELECT id from ".TABLE_PREFIX."_entry where room_id = '".$room_id."' and statut_entry='y'");
				if ($id_resa ==$id)
				echo '<span class="avertissement">(',get_vocab("reservation_en_cours"),') <img src="img_grr/buzy_big.png" align=middle alt="',get_vocab("ressource actuellement empruntee"),'" title="',get_vocab("ressource actuellement empruntee"),'" border="0" width="30" height="30" class="print_image" /></span>',PHP_EOL;
			}
			?>
		</td>
	</tr>
	<tr>
		<td>
			<b>
				<?php
				echo get_vocab("lastupdate"),get_vocab("deux_points");
				?>
			</b>
		</td>
		<td>
			<?php
			echo $updated;
			?>
		</td>
	</tr>
	<?php
	// Les champs add :
	$overload_data = mrbsEntryGetOverloadDesc($id);
	foreach ($overload_data as $fieldname=>$field)
	{
		if (($field["affichage"] == 'y') and ($field["valeur"]!=""))
		{
			echo "<tr><td>".htmlspecialchars($fieldname,ENT_NOQUOTES).get_vocab("deux_points")."</td><td>".htmlspecialchars($field["valeur"],ENT_NOQUOTES)."</tr>";
		}
	}

	// Gestion des clef :
	if ($keys == 1)
	{
		?>
		<tr>
			<td>
				<b>
					<?php
					echo get_vocab("clef"),get_vocab("deux_points");
					?>
				</b>
			</td>
			<td>
				<?php
				echo '<img src="img_grr/key.png" alt="clef">';
				?>
			</td>
		</tr>
		<?php
	}
	if ($courrier == 1)
	{
		?>
		<tr>
			<td>
				<b>
					<?php
					echo get_vocab("courrier"),get_vocab("deux_points");
					?>
				</b>
			</td>
			<td>
				<?php
				echo '<img src="img_grr/courrier.png" alt="courrier">';
				?>
			</td>
		</tr>
		<?php
	}
	if (($delais_option_reservation > 0) && ($option_reservation != -1))
	{
		echo '<tr>',PHP_EOL,'<td colspan="2">',PHP_EOL,'<div class="alert alert-danger" role="alert"><b>',get_vocab("reservation_a_confirmer_au_plus_tard_le"),PHP_EOL;
		echo time_date_string_jma($option_reservation, $dformat),'</b></div>',PHP_EOL;
		echo '</td>',PHP_EOL,'</tr>',PHP_EOL;
	}
	if ($moderate == 1)
	{
		echo '<tr>',PHP_EOL,'<td><b>',get_vocab("moderation"),get_vocab("deux_points"),'</b></td>',PHP_EOL;
		tdcell("avertissement");
		echo '<strong>',get_vocab("en_attente_moderation"),'</strong></td>',PHP_EOL,'</tr>',PHP_EOL;
	}
	else if ($moderate == 2)
	{
		$sql = "SELECT motivation_moderation, login_moderateur FROM ".TABLE_PREFIX."_entry_moderate WHERE id=".$id;
		$res = grr_sql_query($sql);
		if (!$res)
			fatal_error(0, grr_sql_error());
		$row2 = grr_sql_row($res, 0);
		$description = $row2[0];
		$sql ="SELECT nom, prenom FROM ".TABLE_PREFIX."_utilisateurs WHERE login = '".$row2[1]."'";
		$res = grr_sql_query($sql);
		if (!$res)
			fatal_error(0, grr_sql_error());
		$row3 = grr_sql_row($res, 0);
		$nom_modo = $row3[1]. ' '. $row3[0];
		if (authGetUserLevel(getUserName(), -1) > 1)
		{
			echo '<tr>',PHP_EOL,'<td><b>'.get_vocab("moderation").get_vocab("deux_points").'</b></td><td><strong>'.get_vocab("moderation_acceptee_par").' '.$nom_modo.'</strong>';
			if ($description != "")
				echo ' : <br />('.$description.')';
			echo '</td>',PHP_EOL,'</tr>',PHP_EOL;
		}
	}
	elseif ($moderate == 3)
	{
		$sql = "SELECT motivation_moderation, login_moderateur from ".TABLE_PREFIX."_entry_moderate where id=".$id;
		$res = grr_sql_query($sql);
		if (!$res)
			fatal_error(0, grr_sql_error());
		$row4 = grr_sql_row($res, 0);
		$description = $row4[0];
		$sql ="SELECT nom, prenom from ".TABLE_PREFIX."_utilisateurs where login = '".$row4[1]."'";
		$res = grr_sql_query($sql);
		if (!$res)
			fatal_error(0, grr_sql_error());
		$row5 = grr_sql_row($res, 0);
		$nom_modo = $row5[1]. ' '. $row5[0];
		if (authGetUserLevel(getUserName(), -1) > 1)
		{
			echo '<tr><td><b>'.get_vocab("moderation").get_vocab("deux_points").'</b></td>';
			tdcell("avertissement");
			echo '<strong>'.get_vocab("moderation_refusee").'</strong> par '.$nom_modo;
			if ($description != "")
				echo ' : <br />('.$description.')';
			echo '</td>',PHP_EOL,'</tr>',PHP_EOL;;
		}
	}
	if ((getWritable($beneficiaire, getUserName(), $id)) && verif_booking_date(getUserName(), $id, $room_id, -1, $date_now, $enable_periods) && verif_delais_min_resa_room(getUserName(), $room_id, $row[10]) && (!$was_del))
	{
		echo "<tr>";
			echo "<td colspan='2'>";
				echo "&nbsp;";
			echo "</td>";
		echo "</tr>";
		echo "<tr>";
			echo "<td colspan='2'>";
                $room_back = isset($_GET['room_back']) ? $_GET['room_back'] : $room_id ;
				echo "<input class=\"btn btn-primary\" type=\"button\" onclick=\"location.href='edit_entry.php?id=$id&amp;day=$day&amp;month=$month&amp;year=$year&amp;page=$page&amp;room_back=$room_back'\" value=\"".get_vocab("editentry")."\">";
				echo "<input class=\"btn btn-info\" type=\"button\" onclick=\"location.href='edit_entry.php?id=$id&amp;day=$day&amp;month=$month&amp;year=$year&amp;page=$page&amp;room_back=$room_back&amp;copier'\" value=\"".get_vocab("copyentry")."\">";
                echo "<input class=\"btn btn-warning\" type=\"button\" onclick=\"location.href='swap_entry.php?id=$id&amp;page=$page&amp;room_back=$room_back'\" value=\"".get_vocab("swapentry")."\">";
				if ($can_delete_or_create == "y")
				{
					$message_confirmation = str_replace("'", "\\'", get_vocab("confirmdel").get_vocab("deleteentry"));
                   // $room_back = isset($_GET['room_back']) ? $_GET['room_back'] : $room_id ;
                echo '<a class="btn btn-danger" type="button" href="del_entry.php?id='.$id.'&amp;series=0&amp;page='.$page.'&amp;room_back='.$room_back.' "  onclick="return confirm(\''.$message_confirmation.'\');">'.get_vocab("deleteentry").'</a>';
				}
            echo "</td>";
		echo '</tr>',PHP_EOL;
	}
echo '</table>',PHP_EOL;
echo '</fieldset>',PHP_EOL;
if ($repeat_id != 0)
{
    $res = grr_sql_query("SELECT rep_type, end_date, rep_opt, rep_num_weeks, start_time, end_time FROM ".TABLE_PREFIX."_repeat WHERE id=$repeat_id");
    if (!$res)
        fatal_error(0, grr_sql_error());
    if (grr_sql_count($res) == 1)
    {
        $row6 			= grr_sql_row($res, 0);
        $rep_type     	= $row6[0];
        $rep_end_date 	= utf8_strftime($dformat,$row6[1]);
        $rep_opt      	= $row6[2];
        $rep_num_weeks 	= $row6[3];
        $start_time 	= $row6[4];
        $end_time 		= $row6[5];
        $duration 		= $row6[5] - $row6[4];
    }
    grr_sql_free($res);
    if ($enable_periods == 'y')
        list( $start_period, $start_date) = period_date_string($start_time);
    else
        $start_date = time_date_string($start_time, $dformat);
    if ($enable_periods == 'y')
        toPeriodString($start_period, $duration, $dur_units);
    else
        toTimeString($duration, $dur_units);
    $weeklist = array("unused", "every week", "week 1/2", "week 1/3", "week 1/4", "week 1/5");
    if ($rep_type == 2)
        $affiche_period = get_vocab($weeklist[$rep_num_weeks]);
    else
        $affiche_period = get_vocab('rep_type_'.$rep_type);
    echo '<fieldset><legend style="font-weight:bold">'.get_vocab('periodicite_associe')."</legend>\n";
    echo '<table cellpadding="1">';
    echo '<tr><td><b>'.get_vocab("rep_type").'</b></td><td>'.$affiche_period.'</td></tr>';
    if ($rep_type != 0)
    {
        if ($rep_type == 2)
        {
            $opt = "";
            $nb = 0;
            for ($i = 0;
                $i < 7;
                $i++)
            {
                $daynum = ($i + $weekstarts) % 7;
                if ($rep_opt[$daynum])
                {
                    if ($opt != '')
                        $opt .=', ';
                    $opt .= day_name($daynum);
                    $nb++;
                }
            }
            if ($opt)
                if ($nb == 1)
                    echo '<tr>',PHP_EOL,'<td><b>',get_vocab("rep_rep_day"),'</b></td>',PHP_EOL,'<td>',$opt,'</td>',PHP_EOL,'</tr>',PHP_EOL;
                else
                    echo '<tr>',PHP_EOL,'<td><b>',get_vocab("rep_rep_days"),'</b></td>',PHP_EOL,'<td>',$opt,'</td>',PHP_EOL,'</tr>',PHP_EOL;
            }
            if ($rep_type == 6)
            {
                if (Settings::get("jours_cycles_actif") == "Oui" && intval($jour_cycle) >- 1)
                    echo '<tr>',PHP_EOL,'<td><b>',get_vocab("rep_rep_day"),'</b></td>',PHP_EOL,'<td>',get_vocab('jour_cycle'),' ',$jour_cycle,'</td>',PHP_EOL,'</tr>',PHP_EOL;
            }

            echo '<tr><td><b>'.get_vocab("date").get_vocab("deux_points").'</b></td><td>'.$start_date.'</td></tr>';
            echo '<tr><td><b>'.get_vocab("duration").'</b></td><td>'.$duration .' '. $dur_units.'</td></tr>';
            echo '<tr><td><b>'.get_vocab('rep_end_date').'</b></td><td>'.$rep_end_date.'</td></tr>';
        }
        if ((getWritable($beneficiaire, getUserName(), $id)) && verif_booking_date(getUserName(), $id, $room_id, -1, $date_now, $enable_periods) && verif_delais_min_resa_room(getUserName(), $room_id, $row[10]) && (!$was_del))
        {
            $message_confirmation = str_replace ( "'"  , "\\'"  , get_vocab("confirmdel").get_vocab("deleteseries"));
            echo '<tr>',PHP_EOL,'<td colspan="2">',PHP_EOL,'<input class="btn btn-primary" type="button" onclick="location.href=\'edit_entry.php?id=',$id,'&amp;edit_type=series&amp;day=',$day,'&amp;month=',$month,'&amp;year=',$year,'&amp;page=',$page,'\'" value="',get_vocab("editseries"),'"></td>',PHP_EOL,'</tr>',PHP_EOL;
            echo '<tr>',PHP_EOL,'<td colspan="2">',PHP_EOL,'<a class="btn btn-danger" type="button" href="del_entry.php?id=',$id,'&amp;series=1&amp;day=',$day,'&amp;month=',$month,'&amp;year=',$year,'&amp;page=',$page,'" onclick="return confirm(\'',$message_confirmation,'\');">',get_vocab("deleteseries"),'</a></td>',PHP_EOL,'</tr>',PHP_EOL;
        }
        echo '</table>',PHP_EOL,'</fieldset>',PHP_EOL;
    }
    if (!isset($area_id))
        $area_id = 1;
    if (!isset($room))
        $room = 1;
    if ((authGetUserLevel(getUserName(), $area_id, "area") > 1) || (authGetUserLevel(getUserName(), $room) >= 4))
        echo '<br><input class="btn btn-primary" onclick="myFunction(',$id,')" value="',get_vocab("Generer_pdf"),'" >',PHP_EOL;
    if ((getUserName() != '') && (authGetUserLevel(getUserName(), $room_id) >= 3) && ($moderate == 1))
    {
        echo "<form action=\"view_entry.php\" method=\"get\">\n";
        echo "<input type=\"hidden\" name=\"action_moderate\" value=\"y\" />\n";
        echo "<input type=\"hidden\" name=\"id\" value=\"".$id."\" />\n";
        if (isset($_GET['page']))
            echo "<input type=\"hidden\" name=\"page\" value=\"".$_GET['page']."\" />\n";
        echo "<fieldset><legend style=\"font-weight:bold\">".get_vocab("moderate_entry")."</legend>\n";
        echo "<p>";
        echo "<input type=\"radio\" name=\"moderate\" value=\"1\" checked=\"checked\" />".get_vocab("accepter_resa");
        echo "<br /><input type=\"radio\" name=\"moderate\" value=\"0\" />".get_vocab("refuser_resa");
        if ($repeat_id)
        {
            echo "<br /><input type=\"radio\" name=\"moderate\" value=\"S1\" />".get_vocab("accepter_resa_serie");
            echo "<br /><input type=\"radio\" name=\"moderate\" value=\"S0\" />".get_vocab("refuser_resa_serie");
        }
        echo "</p><p>";
        echo "<label for=\"description\">".get_vocab("justifier_decision_moderation").get_vocab("deux_points")."</label>\n";
        echo "<textarea class=\"form-control\" name=\"description\" id=\"description\" cols=\"40\" rows=\"3\"></textarea>";
        echo "</p>";
        echo "<br /><div style=\"text-align:center;\"><input class=\"btn btn-primary\" type=\"submit\" name=\"commit\" value=\"".get_vocab("save")."\" /></div>\n";
        echo "</fieldset></form>\n";
    }
    if ($active_ressource_empruntee == 'y')
    {
        if ((!$was_del) && ($moderate != 1) && (getUserName() != '') && (authGetUserLevel(getUserName(),$room_id) >= 3))
        {
            echo "<form action=\"view_entry.php\" method=\"get\">";
            echo "<fieldset><legend style=\"font-weight:bold\">".get_vocab("reservation_en_cours")."</legend>\n";
            echo "<span class=\"larger\">".get_vocab("signaler_reservation_en_cours")."</span>".get_vocab("deux_points");
            echo "<br />".get_vocab("explications_signaler_reservation_en_cours");
            affiche_ressource_empruntee($room_id, "texte");
            echo "<br /><input type=\"radio\" name=\"statut_id\" value=\"-\" ";
            if ($statut_id == '-')
            {
                if (!affiche_ressource_empruntee($room_id,"autre") == 'yes')
                    echo " checked=\"checked\" ";
            }
            echo " />".get_vocab("signaler_reservation_en_cours_option_0");
            echo "<br /><br /><input type=\"radio\" name=\"statut_id\" value=\"y\" ";
            if ($statut_id == 'y')
                echo " checked=\"checked\" ";
            echo " />".get_vocab("signaler_reservation_en_cours_option_1");
            echo "<br /><br /><input type=\"radio\" name=\"statut_id\" value=\"e\" ";
            if ($statut_id == 'e')
                echo " checked=\"checked\" ";
            if ((!(Settings::get("automatic_mail") == 'yes')) || ($mail_exist == ""))
                echo " disabled ";
            echo " />".get_vocab("signaler_reservation_en_cours_option_2");
            if ((!(Settings::get("automatic_mail") == 'yes')) || ($mail_exist == ""))
                echo "<br /><i>(".get_vocab("necessite fonction mail automatique").")</i>";
            if (Settings::get("automatic_mail") == 'yes')
            {
                echo "<br /><br /><input type=\"checkbox\" name=\"envoyer_mail\" value=\"y\" ";
                if ($mail_exist == "")
                    echo " disabled ";
                echo " />".get_vocab("envoyer maintenant mail retard");
                echo "<input type=\"hidden\" name=\"mail_exist\" value=\"".$mail_exist."\" />";
            }
         /*   if ((!(Settings::get("automatic_mail") == 'yes')) || ($mail_exist == ""))
                echo "<br /><i>(".get_vocab("necessite fonction mail automatique").")</i>"; en doublon ? YN */ 
            echo "<br /><div style=\"text-align:center;\"><input class=\"btn btn-primary\" type=\"submit\" name=\"ok\" value=\"".get_vocab("save")."\" /></div></fieldset>\n";
            echo "<div><input type=\"hidden\" name=\"day\" value=\"".$day."\" />";
            echo "<input type=\"hidden\" name=\"month\" value=\"".$month."\" />";
            echo "<input type=\"hidden\" name=\"year\" value=\"".$year."\" />";
            echo "<input type=\"hidden\" name=\"page\" value=\"".$page."\" />";
            echo "<input type=\"hidden\" name=\"id\" value=\"".$id."\" />";
            echo "<input type=\"hidden\" name=\"back\" value=\"".$back."\" /></div>";
            echo "</form>";
        }
    }
    if (isset($keys) && isset($courrier))
    {
        echo '<form action="view_entry.php" method="get">',PHP_EOL;
        // echo "<fieldset><legend style=\"font-weight:bold\">".get_vocab("reservation_en_cours")."</legend>\n";
        echo "<fieldset>\n";
        if ($active_cle == 'y'){
            echo "<span class=\"larger\">".get_vocab("status_clef").get_vocab("deux_points")."</span>";
            echo "<br /><input type=\"checkbox\" name=\"clef\" value=\"y\" ";
            if ($keys == 1)
                echo " checked ";
            echo " /> ".get_vocab("msg_clef");
        }
        
        if (Settings::get('show_courrier') == 'y')
        {
            echo "<br /><span class=\"larger\">".get_vocab("status_courrier").get_vocab("deux_points")."</span>";
            echo "<br /><input type=\"checkbox\" name=\"courrier\" value=\"y\" ";
            if ($courrier == 1)
                echo " checked ";
            echo " /> ".get_vocab("msg_courrier");
        }
        //echo "<br /><br /><div style=\"text-align:center;\"><input class=\"btn btn-primary\" type=\"submit\" name=\"ok\" value=\"".get_vocab("save")."\" /></div></fieldset>\n";
        echo "</fieldset>";
        echo '<div><input type="hidden" name="day" value="',$day,'" />',PHP_EOL;
        echo '<input type="hidden" name="month" value="',$month,'" />',PHP_EOL;
        echo '<input type="hidden" name="year" value="',$year,'" />',PHP_EOL;
        echo '<input type="hidden" name="page" value="',$page,'" />',PHP_EOL;
        echo '<input type="hidden" name="id" value="',$id,'" />',PHP_EOL;
        echo '<input type="hidden" name="back" value="',$back,'" /></div>',PHP_EOL;
        echo '</form>',PHP_EOL;
    }
    include_once('include/trailer.inc.php');
    // echo '</div>',PHP_EOL; ??? YN
    echo "</body>\n</html>";
    ?>
