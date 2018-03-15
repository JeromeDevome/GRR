<?php
/**
 * month.php
 * Interface d'accueil avec affichage par mois
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2018-02-24 17:00$
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
$grr_script_name = "month.php";

/*if (!isset($_GET['day'])){ // pour l'affichage du mois la variable jour n'est pas obligatoire dans l'url, cependant necessaire pour setdate.php
	$_GET['day'] = 1;
} */

include "include/planning_init.inc.php";
// en l'absence de paramètres, planning_init définit le site par défaut et le domaine par défaut mais pas la ressource, month.php en a besoin
if (!isset($room)){
    echo "<p><br/>";
        echo get_vocab('choose_room')."<a href='month_all.php'>".get_vocab("link")."</a>";
    echo "</p>";
    die();
}
//Heure de début du mois, cela ne sert à rien de reprendre les valeurs morningstarts/eveningends
$month_start = mktime(0, 0, 0, $month, 1, $year);
//Dans quel colonne l'affichage commence: 0 veut dire $weekstarts
$weekday_start = (date("w", $month_start) - $weekstarts + 7) % 7;
$days_in_month = date("t", $month_start);
$month_end = mktime(23, 59, 59, $month, $days_in_month, $year);
if ($enable_periods == 'y')
{
	$resolution = 60;
	$morningstarts = 12;
	$eveningends = 12;
	$eveningends_minutes = count($periods_name) - 1;
}
$this_area_name = grr_sql_query1("SELECT area_name FROM ".TABLE_PREFIX."_area WHERE id=$area");
$this_room_name = grr_sql_query1("SELECT room_name FROM ".TABLE_PREFIX."_room WHERE id=$room");
$this_room_max = grr_sql_query1("SELECT capacity FROM ".TABLE_PREFIX."_room WHERE id=$room");
$this_room_name_des = grr_sql_query1("SELECT description FROM ".TABLE_PREFIX."_room WHERE id=$room");
$this_statut_room = grr_sql_query1("SELECT statut_room FROM ".TABLE_PREFIX."_room WHERE id=$room");
$this_moderate_room = grr_sql_query1("SELECT moderate FROM ".TABLE_PREFIX."_room WHERE id=$room");
$this_delais_option_reservation = grr_sql_query1("SELECT delais_option_reservation FROM ".TABLE_PREFIX."_room WHERE id=$room");
$this_area_comment = grr_sql_query1("SELECT comment_room FROM ".TABLE_PREFIX."_room WHERE id=$room");
$this_area_show_comment = grr_sql_query1("SELECT show_comment FROM ".TABLE_PREFIX."_room WHERE id=$room");

if (($this_room_name_des) && ($this_room_name_des!="-1"))
	$this_room_name_des = " (".$this_room_name_des.")";
else
	$this_room_name_des = "";

$i = mktime(0, 0, 0, $month - 1, 1, $year);
$yy = date("Y", $i);
$ym = date("n", $i);
$i = mktime(0, 0, 0,$month + 1, 1, $year);
$ty = date("Y", $i);
$tm = date("n", $i);


echo '<div class="titre_planning">'.PHP_EOL;
echo '<table class="table-header">'.PHP_EOL;
if ((!isset($_GET['pview'])) || ($_GET['pview'] != 1))
{
	echo '<tr>'.PHP_EOL;
	echo '<td class="left">'.PHP_EOL;
	echo '<button class="btn btn-default btn-xs" onclick="charger();javascript: location.href=\'month.php?year='.$yy.'&amp;month='.$ym.'&amp;day=1&amp;room='.$room.'\';"><span class="glyphicon glyphicon-backward"></span> '.get_vocab("monthbefore").'</button>'.PHP_EOL;
	echo '</td>'.PHP_EOL;
	echo '<td>'.PHP_EOL;
	include "include/trailer.inc.php";
	echo '</td>'.PHP_EOL;
	echo '<td class="right">'.PHP_EOL;
	echo '<button class="btn btn-default btn-xs" onclick="charger();javascript: location.href=\'month.php?year='.$ty.'&amp;month='.$tm.'&amp;day=1&amp;room='.$room.'\';"> '.get_vocab('monthafter').'  <span class="glyphicon glyphicon-forward"></span></button>'.PHP_EOL;
	echo '</td>'.PHP_EOL;
	echo '</tr>'.PHP_EOL;
	echo '</table>'.PHP_EOL;
}
$maxCapacite = "";
if ($this_room_max  && $_GET['pview'] != 1)
	$maxCapacite = '('.$this_room_max.' '.($this_room_max > 1 ? get_vocab("number_max2") : get_vocab("number_max")).')'.PHP_EOL;

echo '<h4 class="titre"> '. ucfirst($this_area_name).' - '.$this_room_name.' '.$this_room_name_des.' '.$maxCapacite.'<br>'.ucfirst(utf8_strftime("%B ", $month_start)).'<a href="year.php">'.ucfirst(utf8_strftime("%Y", $month_start)).'</a></h4>'.PHP_EOL;

if (verif_display_fiche_ressource(getUserName(), $room) && $_GET['pview'] != 1)
	echo '<a href="javascript:centrerpopup(\'view_room.php?id_room=',$room,'\',600,480,\'scrollbars=yes,statusbar=no,resizable=yes\')" title="',get_vocab("fiche_ressource"),'"><span class="glyphcolor glyphicon glyphicon-search"></span></a>';

if (authGetUserLevel(getUserName(),$room) > 2 && $_GET['pview'] != 1)
	echo "<a href='./admin/admin_edit_room.php?room=$room'><span class=\"glyphcolor glyphicon glyphicon-cog\"></span></a>";
affiche_ressource_empruntee($room);
if ($this_statut_room == "0")
	echo '<br><span class="texte_ress_tempo_indispo">',get_vocab("ressource_temporairement_indisponible"),'</span>';
if ($this_moderate_room == "1")
	echo '<br><span class="texte_ress_moderee">',get_vocab("reservations_moderees"),'</span>';
echo '</div>',PHP_EOL;

if (isset($_GET['precedent']))
{
	if ($_GET['pview'] == 1 && $_GET['precedent'] == 1)
	{
		echo '<span id="lienPrecedent">',PHP_EOL,'<button class="btn btn-default btn-xs" onclick="charger();javascript:history.back();">Précedent</button>',PHP_EOL,'</span>',PHP_EOL;
	}
}
if ($this_area_show_comment == "y" && $_GET['pview'] != 1 && ($this_area_comment != "") && ($this_area_comment != -1))
	echo '<div style="text-align:center;">',$this_area_comment,'</div>',PHP_EOL;
echo '<div class="contenu_planning">',PHP_EOL;
$all_day = preg_replace("/ /", " ", get_vocab("all_day2"));
$sql = "SELECT start_time, end_time, id, name, beneficiaire, description, type, moderate, beneficiaire_ext
FROM ".TABLE_PREFIX."_entry
WHERE room_id=$room
AND start_time <= $month_end AND end_time > $month_start
ORDER by 1";
$res = grr_sql_query($sql);
if (!$res)
	echo grr_sql_error();
else
	for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
	{
		$t = max((int)$row[0], $month_start);
		$end_t = min((int)$row[1], $month_end);
		$day_num = date("j", $t);
		if ($enable_periods == 'y')
			$midnight = mktime(12,0,0,$month,$day_num,$year);
		else
			$midnight = mktime(0, 0, 0, $month, $day_num, $year);
		while ($t < $end_t)
		{
			$d[$day_num]["id"][] = $row[2];
			if (Settings::get("display_info_bulle") == 1)
				$d[$day_num]["who"][] = get_vocab("reservee au nom de").affiche_nom_prenom_email($row[4],$row[8],"nomail");
			else if (Settings::get("display_info_bulle") == 2)
				$d[$day_num]["who"][] = $row[5];
			else
				$d[$day_num]["who"][] = "";
			$d[$day_num]["who1"][] = affichage_lien_resa_planning($row[3],$row[2]);
			$d[$day_num]["color"][] = $row[6];
			$d[$day_num]["description"][] =  affichage_resa_planning($row[5],$row[2]);
			$d[$day_num]["moderation"][] = $row[7];
			$midnight_tonight = $midnight + 86400;
			if ($enable_periods == 'y')
			{
				$start_str = preg_replace("/ /", " ", period_time_string($row[0]));
				$end_str   = preg_replace("/ /", " ", period_time_string($row[1], -1));
				switch (cmp3($row[0], $midnight) . cmp3($row[1], $midnight_tonight))
				{
					case "> < ":
					case "= < ":
					if ($start_str == $end_str)
						$d[$day_num]["data"][] = $start_str;
					else
						$d[$day_num]["data"][] = $start_str . get_vocab("to") . $end_str;
					break;
					case "> = ":
					$d[$day_num]["data"][] = $start_str . get_vocab("to"). "24:00";
					break;
					case "> > ":
					$d[$day_num]["data"][] = $start_str . get_vocab("to") ."&gt;";
					break;
					case "= = ":
					$d[$day_num]["data"][] = $all_day;
					break;
					case "= > ":
					$d[$day_num]["data"][] = $all_day . "&gt;";
					break;
					case "< < ":
					$d[$day_num]["data"][] = "&lt;".get_vocab("to") . $end_str;
					break;
					case "< = ":
					$d[$day_num]["data"][] = "&lt;" . $all_day;
					break;
					case "< > ":
					$d[$day_num]["data"][] = "&lt;" . $all_day . "&gt;";
					break;
				}
			}
			else
			{
				switch (cmp3($row[0], $midnight) . cmp3($row[1], $midnight_tonight))
				{
					case "> < ":
					case "= < ":
					$d[$day_num]["data"][] = date(hour_min_format(), $row[0]) . get_vocab("to") . date(hour_min_format(), $row[1]);
					break;
					case "> = ":
					$d[$day_num]["data"][] = date(hour_min_format(), $row[0]) . get_vocab("to")."24:00";
					break;
					case "> > ":
					$d[$day_num]["data"][] = date(hour_min_format(), $row[0]) . get_vocab("to")."&gt;";
					break;
					case "= = ":
					$d[$day_num]["data"][] = $all_day;
					break;
					case "= > ":
					$d[$day_num]["data"][] = $all_day . "&gt;";
					break;
					case "< < ":
					$d[$day_num]["data"][] = "&lt;".get_vocab("to") . date(hour_min_format(), $row[1]);
					break;
					case "< = ":
					$d[$day_num]["data"][] = "&lt;" . $all_day;
					break;
					case "< > ":
					$d[$day_num]["data"][] = "&lt;" . $all_day . "&gt;";
					break;
				}
			}
				//Seulement si l'heure de fin est après minuit, on continue le jour prochain.
			if ($row[1] <= $midnight_tonight)
				break;
			$day_num++;
			$t = $midnight = $midnight_tonight;
		}
	}
	echo '<table class="table-bordered table-striped">',PHP_EOL,'<tr>',PHP_EOL;
	for ($weekcol = 0; $weekcol < 7; $weekcol++)
	{
		$num_week_day = ($weekcol + $weekstarts) % 7;
		if ($display_day[$num_week_day] == 1)
			echo '<th style="width:14%;">',day_name(($weekcol + $weekstarts) % 7),'</th>',PHP_EOL;
	}
	echo '</tr>',PHP_EOL;
	$weekcol = 0;
	if ($weekcol != $weekday_start)
	{
		echo '<tr>',PHP_EOL;
		for ($weekcol = 0; $weekcol < $weekday_start; $weekcol++)
		{
			$num_week_day = ($weekcol + $weekstarts) % 7;
			if ($display_day[$num_week_day] == 1)
				echo '<td class="cell_month_o">',PHP_EOL,'</td>',PHP_EOL;
		}
	}
	
	for ($cday = 1; $cday <= $days_in_month; $cday++)
	{
		$class = "";
		$title = "";
		$num_week_day = ($weekcol + $weekstarts) % 7;
		$t = mktime(0, 0, 0, $month, $cday,$year);
		$name_day = ucfirst(utf8_strftime("%d", $t));
		$jour_cycle = grr_sql_query1("SELECT Jours FROM ".TABLE_PREFIX."_calendrier_jours_cycle WHERE DAY='$t'");
		if ($weekcol == 0)
			echo '<tr>',PHP_EOL;
		if ($display_day[$num_week_day] == 1)
		{
           	if ($settings->get("show_holidays") == "Oui")
            {   
                $now = $t;
                if (isHoliday($now)){
                    $class .= 'ferie ';
                }
                elseif (isSchoolHoliday($now)){
                    $class .= 'vacance ';
                }
            }
			echo '<td class="cell_month">',PHP_EOL,'<div class="monthday ',$class,'">',PHP_EOL,'<a title="',htmlspecialchars(get_vocab("see_all_the_rooms_for_the_day")),$title,'" href="day.php?year=',$year,'&amp;month=',$month,'&amp;day=',$cday,'&amp;area=',$area,'">',$name_day;
			if (Settings::get("jours_cycles_actif") == "Oui" && intval($jour_cycle) >- 1)
			{
				if (intval($jour_cycle) > 0)
					echo ' - ',get_vocab("rep_type_6"),' ',$jour_cycle;
				else
					echo ' - ',$jour_cycle;
			}
			echo '</a>',PHP_EOL,'</div>',PHP_EOL;
			if (est_hors_reservation(mktime(0, 0, 0, $month, $cday, $year), $area))
			{
				echo '<div class="empty_cell">',PHP_EOL;
				echo '<img src="img_grr/stop.png" alt="',get_vocab("reservation_impossible"),'" title="',get_vocab("reservation_impossible"),'" width="16" height="16" class="',$class_image,'" />',PHP_EOL;
				echo '</div>',PHP_EOL;
			}
			else
			{
				if (isset($d[$cday]["id"][0]))
				{
					$n = count($d[$cday]["id"]);
					for ($i = 0; $i < $n; $i++)
					{
						if ($i == 11 && $n > 12)
						{
							echo " ...\n";
							break;
						}
						echo '<table class="table-bordered table-striped">',PHP_EOL,'<tr>',PHP_EOL;
						tdcell($d[$cday]["color"][$i]);
						echo '<span class="small_planning">';
						if ($acces_fiche_reservation)
						{
							if (Settings::get("display_level_view_entry") == 0)
							{
								$currentPage = 'month_all2';
								$id = $d[$cday]["id"][$i];
								echo '<a title="',htmlspecialchars($d[$cday]["who"][$i]),'" data-width="675" onclick="request(',$id,',',$cday,',',$month,',',$year,',\'',$currentPage,'\',readData);" data-rel="popup_name" class="poplight">';
							}
							else
							{
								echo '<a class="lienCellule" title="',htmlspecialchars($d[$cday]["who"][$i]),'" href="view_entry.php?id=',$d[$cday]["id"][$i],'&amp;day=',$cday,'&amp;month=',$month,'&amp;year=',$year,'&amp;page=month">';
							}
						}
						echo $d[$cday]["data"][$i],'<br/>';
						if ((isset($d[$cday]["moderation"][$i])) && ($d[$cday]["moderation"][$i] == 1))
							echo '<img src="img_grr/flag_moderation.png" alt="',get_vocab("en_attente_moderation"),'" title="',get_vocab("en_attente_moderation"),'" class="image" />',PHP_EOL;
						echo $d[$cday]["who1"][$i],'<br/>';
						$Son_GenreRepeat = grr_sql_query1("SELECT type_name FROM ".TABLE_PREFIX."_type_area ,".TABLE_PREFIX."_entry  WHERE  ".TABLE_PREFIX."_entry.id= ".$d[$cday]["id"][$i]." AND ".TABLE_PREFIX."_entry.type= ".TABLE_PREFIX."_type_area.type_letter");
						echo $Son_GenreRepeat,'<br/>';
						if ($d[$cday]["description"][$i] != "")
							echo '<br /><i>(',$d[$cday]["description"][$i],')</i>';
						if ($acces_fiche_reservation)
							echo '</a>',PHP_EOL;
						echo '</span>',PHP_EOL,'</td>',PHP_EOL,'</tr>',PHP_EOL,'</table>',PHP_EOL;
					}
				}
				$date_now = time();
				$hour = date("H",$date_now);
				$date_booking = mktime(24, 0, 0, $month, $cday, $year);
				if ((($authGetUserLevel > 1) || ($auth_visiteur == 1))
					&& ($UserRoomMaxBooking != 0)
					&& verif_booking_date(getUserName(), -1, $room, $date_booking, $date_now, $enable_periods)
					&& verif_delais_max_resa_room(getUserName(), $room, $date_booking)
					&& verif_delais_min_resa_room(getUserName(), $room, $date_booking)
					&& plages_libre_semaine_ressource($room, $month, $cday, $year)
					&& (($this_statut_room == "1") ||
						(($this_statut_room == "0") && (authGetUserLevel(getUserName(),$room) > 2)))
					&& $_GET['pview'] != 1)
				{
					echo '<div class="empty_cell">',PHP_EOL;
					if ($enable_periods == 'y')
						echo '<a href="edit_entry.php?room=',$room,'&amp;period=&amp;year=',$year,'&amp;month=',$month,'&amp;day=',$cday,'&amp;page=month" title="',get_vocab("cliquez_pour_effectuer_une_reservation"),'"><span class="glyphicon glyphicon-plus"></span></a>',PHP_EOL;
					else
						echo '<a href="edit_entry.php?room=',$room,'&amp;hour=',$hour,'&amp;minute=0&amp;year=',$year,'&amp;month=',$month,'&amp;day=',$cday,'&amp;page=month" title="',get_vocab("cliquez_pour_effectuer_une_reservation"),'"><span class="glyphicon glyphicon-plus"></span></a>',PHP_EOL;
					echo '</div>'.PHP_EOL;
				}
				else
					echo ' ';
			}
			echo '</td>'.PHP_EOL;
		}
		if (++$weekcol == 7)
		{
			$weekcol = 0;
			echo '</tr>'.PHP_EOL;
		}
	}
	if ($weekcol > 0)
	{
		for (; $weekcol < 7; $weekcol++)
		{
			$num_week_day = ($weekcol + $weekstarts)%7;
			if ($display_day[$num_week_day] == 1)
				echo '<td class="cell_month_o" > </td>'.PHP_EOL;
		}
	}
	echo '</tr>'.PHP_EOL.'</table>'.PHP_EOL;
	if ($_GET['pview'] != 1)
	{
		echo '<div id="toTop">',PHP_EOL,'<b>',get_vocab("top_of_page"),'</b>',PHP_EOL;
		bouton_retour_haut ();
		echo '</div>',PHP_EOL;
	}
	?>
</div>
</div>

<script type="text/javascript">
	jQuery(document).ready(function($){
		$("#popup_name").draggable({containment: "#container"});
		$("#popup_name").resizable();
	});
		
</script>
<?php


affiche_pop_up(get_vocab("message_records"),"user");
echo '</div>'.PHP_EOL;
echo '</div>'.PHP_EOL;
echo  "<div id=\"popup_name\" class=\"popup_block\" ></div>";
include "footer.php";

?>