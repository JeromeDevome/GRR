<?php
/**
 * day.php
 * Permet l'affichage de la page d'accueil lorsque l'on est en mode d'affichage "jour".
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2017-12-16 14:00$
 * @author    Laurent Delineau & JeromeB
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
$grr_script_name = "day.php";

include "include/planning_init.inc.php";


$ind = 1;
$test = 0;
$i = 0;
while (($test == 0) && ($ind <= 7))
{
	$i = mktime(0, 0, 0, $month, $day - $ind, $year);
	$test = $display_day[date("w",$i)];
	$ind++;
}
$yy = date("Y", $i);
$ym = date("m", $i);
$yd = date("d", $i);
$i = mktime(0, 0, 0, $month, $day, $year);
$jour_cycle = grr_sql_query1("SELECT Jours FROM ".TABLE_PREFIX."_calendrier_jours_cycle WHERE day='$i'");
$ind = 1;
$test = 0;
while (($test == 0) && ($ind <= 7))
{
	$i = mktime(0, 0, 0, $month, $day + $ind, $year);
	$test = $display_day[date("w", $i)];
	$ind++;
}
$ty = date("Y",$i);
$tm = date("m",$i);
$td = date("d",$i);
$am7 = mktime($morningstarts, 0, 0, $month, $day, $year);
$pm7 = mktime($eveningends, $eveningends_minutes, 0, $month, $day, $year);
$this_area_name = grr_sql_query1("SELECT area_name FROM ".TABLE_PREFIX."_area WHERE id='".protect_data_sql($area)."'");
$sql = "SELECT ".TABLE_PREFIX."_room.id, start_time, end_time, name, ".TABLE_PREFIX."_entry.id, type, beneficiaire, statut_entry, ".TABLE_PREFIX."_entry.description, ".TABLE_PREFIX."_entry.option_reservation, ".TABLE_PREFIX."_entry.moderate, beneficiaire_ext
FROM ".TABLE_PREFIX."_entry, ".TABLE_PREFIX."_room
WHERE ".TABLE_PREFIX."_entry.room_id = ".TABLE_PREFIX."_room.id
AND area_id = '".protect_data_sql($area)."'
AND start_time < ".($pm7+$resolution)." AND end_time > $am7 ORDER BY start_time";
$res = grr_sql_query($sql);
if (!$res)
	echo grr_sql_error();
else
{
	for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
	{
		$start_t = max(round_t_down($row['1'], $resolution, $am7), $am7);
		$end_t = min(round_t_up($row['2'], $resolution, $am7) - $resolution, $pm7);
		$cellules[$row['4']] = ($end_t - $start_t) / $resolution + 1;
		$compteur[$row['4']] = 0;
		for ($t = $start_t; $t <= $end_t; $t += $resolution)
		{
			$today[$row['0']][$t]["id"]				= $row['4'];
			$today[$row['0']][$t]["color"]			= $row['5'];
			$today[$row['0']][$t]["data"]			= "";
			$today[$row['0']][$t]["who"]			= "";
			$today[$row['0']][$t]["statut"]			= $row['7'];
			$today[$row['0']][$t]["moderation"]		= $row['10'];
			$today[$row['0']][$t]["option_reser"]	= $row['9'];
			$today[$row['0']][$t]["description"]	= affichage_resa_planning($row['8'], $row['4']);
		}
		if ($row['1'] < $am7)
		{
			$today[$row['0']][$am7]["data"] = affichage_lien_resa_planning($row['3'], $row['4']);
			if ($settings->get("display_info_bulle") == 1)
				$today[$row['0']][$am7]["who"] = get_vocab("reservation au nom de").affiche_nom_prenom_email($row['6'], $row['11'], "nomail");
			else if ($settings->get("display_info_bulle") == 2)
				$today[$row['0']][$am7]["who"] = $row['8'];
			else
				$today[$row['0']][$am7]["who"] = "";
		}
		else
		{
			$today[$row['0']][$start_t]["data"] = affichage_lien_resa_planning($row['3'], $row['4']);
			if ($settings->get("display_info_bulle") == 1)
				$today[$row['0']][$start_t]["who"] = get_vocab("reservation au nom de").affiche_nom_prenom_email($row['6'], $row['11']);
			else if ($settings->get("display_info_bulle") == 2)
				$today[$row['0']][$start_t]["who"] = $row['8'];
			else
				$today[$row['0']][$start_t]["who"] = "";
		}
	}
}
grr_sql_free($res);

// Dans le cas de l'affichange d'une ressource, dans l'autre cas on garde la requete dans planning_init.inc.php
if(!empty($_GET['room'])){
	$sql = "SELECT room_name, capacity, id, description, statut_room, show_fic_room, delais_option_reservation, moderate FROM ".TABLE_PREFIX."_room WHERE area_id='".protect_data_sql($area)."' and id = '".protect_data_sql($_GET['room'])."' ORDER BY order_display, room_name";
	$ressources = grr_sql_query($sql);
	if (!$ressources)
		fatal_error(0, grr_sql_error());
}


$ferie_true = 0;
$class = "";
$title = "";
if ($settings->get("show_holidays") == "Oui")
{   
	$now = mktime(0,0,0,$month,$day,$year);
	if (isHoliday($now)){
		$class .= 'ferie ';
	}
	elseif (isSchoolHoliday($now)){
		$class .= 'vacance ';
	}
}
echo '<div class="titre_planning '.$class.'">'.PHP_EOL;
if ((!isset($_GET['pview'])) || ($_GET['pview'] != 1))
{
	echo '<table class="table-header">',PHP_EOL,'<tr>',PHP_EOL,'<td class="left">',PHP_EOL,'<button class="btn btn-default btn-xs" onclick="charger();javascript: location.href=\'day.php?year='.$yy.'&amp;month='.$ym.'&amp;day='.$yd.'&amp;area='.$area.'\';"> <span class="glyphicon glyphicon-backward"></span> ',get_vocab("daybefore"),'</button>',PHP_EOL,'</td>',PHP_EOL,'<td>',PHP_EOL;
	include "include/trailer.inc.php";
	echo '</td>',PHP_EOL,'<td class="right">',PHP_EOL,'<button class="btn btn-default btn-xs" onclick="charger();javascript: location.href=\'day.php?year='.$ty.'&amp;month='.$tm.'&amp;day='.$td.'&amp;area='.$area.'\';">  '.get_vocab('dayafter').'  <span class="glyphicon glyphicon-forward"></span></button>',PHP_EOL,'</td>',PHP_EOL,'</tr>',PHP_EOL,'</table>',PHP_EOL;
}
echo '<h4 class="titre">' . ucfirst($this_area_name).' - '.get_vocab("all_areas");
if ($settings->get("jours_cycles_actif") == "Oui" && intval($jour_cycle) >- 1)
{
	if (intval($jour_cycle) > 0)
		echo ' - '.get_vocab("rep_type_6")." ".$jour_cycle;
	else
		echo ' - '.$jour_cycle;
}
echo '<br>'.ucfirst(utf8_strftime($dformat, $am7)).'</h4>'.PHP_EOL;
echo '</div>'.PHP_EOL;
if (isset($_GET['precedent']))
{
	if ($_GET['pview'] == 1 && $_GET['precedent'] == 1)
		echo '<span id="lienPrecedent"><button class="btn btn-default btn-xs" onclick="charger();javascript:history.back();">Précedent</button></span>'.PHP_EOL;
}
echo '<div class="contenu_planning">'.PHP_EOL;
echo '<table class="table-bordered table-striped">'.PHP_EOL;
echo '<tr>'.PHP_EOL.'<th style="width:5%;">'.PHP_EOL;
if ($enable_periods == 'y')
	echo get_vocab("period");
else
	echo get_vocab("time");
echo  '</th>'.PHP_EOL;
$room_column_width = (int)(90 / grr_sql_count($ressources));
$nbcol = 0;
$rooms = array();
$a = 0;
for ($i = 0; ($row = grr_sql_row($ressources, $i)); $i++)
{
	$id_room[$i] = $row['2'];
	$nbcol++;
	if (verif_acces_ressource(getUserName(), $id_room[$i]))
	{
		$room_name[$i] = $row['0'];
		$statut_room[$id_room[$i]] =  $row['4'];
		$statut_moderate[$id_room[$i]] =  $row['7'];
		$acces_fiche_reservation = verif_acces_fiche_reservation(getUserName(), $id_room[$i]);
		if ($row['1']  && $_GET['pview'] != 1)
			$temp = '<br /><span class="small">('.$row['1'].' '.($row['1'] > 1 ? get_vocab("number_max2") : get_vocab("number_max")).')</span>'.PHP_EOL;
		else
			$temp = '';
		if ($statut_room[$id_room[$i]] == "0"  && $_GET['pview'] != 1)
			$temp .= '<br /><span class="texte_ress_tempo_indispo">'.get_vocab("ressource_temporairement_indisponible").'</span>'.PHP_EOL;
		if ($statut_moderate[$id_room[$i]] == "1"  && $_GET['pview'] != 1)
			$temp .= '<br /><span class="texte_ress_moderee">'.get_vocab("reservations_moderees").'</span>'.PHP_EOL;
		echo '<th style="width:'.$room_column_width.'%;" ';
		if ($statut_room[$id_room[$i]] == "0")
			echo 'class="avertissement" ';
		$a = $a + 1;
		echo '><a id="afficherBoutonSelection'.$a.'" class="lienPlanning" href="#" onclick="afficherMoisSemaine('.$a.')" style="display:inline;">'.htmlspecialchars($row['0']).'</a>'.PHP_EOL;
		echo '<a id="cacherBoutonSelection'.$a.'" class="lienPlanning" href="#" onclick="cacherMoisSemaine('.$a.')" style="display:none;">'.htmlspecialchars($row['0']).'</a>'.PHP_EOL;
		if (htmlspecialchars($row['3']).$temp != '')
		{
			if (htmlspecialchars($row['3']) != '')
				$saut = '<br />';
			else
				$saut = '';
			echo $saut.htmlspecialchars($row['3']).$temp."\n";
		}
		echo '<br />';
		if (verif_display_fiche_ressource(getUserName(), $id_room[$i]) && $_GET['pview'] != 1)
			echo '<a href="javascript:centrerpopup(\'view_room.php?id_room='.$id_room[$i].'\',600,480,\'scrollbars=yes,statusbar=no,resizable=yes\')" title="'.get_vocab("fiche_ressource").'\">
		<span class="glyphcolor glyphicon glyphicon-search"></span></a>'.PHP_EOL;
		if (authGetUserLevel(getUserName(),$id_room[$i]) > 2 && $_GET['pview'] != 1)
			echo '<a href="./admin/admin_edit_room.php?room='.$id_room[$i].'"><span class="glyphcolor glyphicon glyphicon-cog"></span></a><br/>'.PHP_EOL;
		affiche_ressource_empruntee($id_room[$i]);
		echo '<span id="boutonSelection'.$a.'" style="display:none;">'.PHP_EOL;
		echo '<input type="button" class="btn btn-default btn-xs" title="'.htmlspecialchars(get_vocab("see_week_for_this_room")).'" onclick="charger();javascript: location.href=\'week.php?year='.$year.'&amp;month='.$month.'&amp;cher='.$day.'&amp;room='.$id_room[$i].'\';" value="'.get_vocab('week').'"/>'.PHP_EOL;
		echo '<input type="button" class="btn btn-default btn-xs" title="'.htmlspecialchars(get_vocab("see_month_for_this_room")).'" onclick="charger();javascript: location.href=\'month.php?year='.$year.'&amp;month='.$month.'&amp;day='.$day.'&amp;room='.$id_room[$i].'\';" value="'.get_vocab('month').'"/>'.PHP_EOL;
		echo '</span>'.PHP_EOL;
		echo '</th>'.PHP_EOL;
		if (htmlspecialchars($row['3']).$temp != '')
		{
			if (htmlspecialchars($row['3']) != '')
				$saut = '<br />';
			else
				$saut = '';
		}
		$rooms[] = $row['2'];
		$delais_option_reservation[$row['2']] = $row['6'];
	}
}
if (count($rooms) == 0)
{
	echo '<br /><h1>'.get_vocab("droits_insuffisants_pour_voir_ressources").'</h1><br />'.PHP_EOL;
	include "include/trailer.inc.php";
	die();
}
echo '<tr>'.PHP_EOL;
echo '<th style="width:5%;">'.PHP_EOL;
echo '</tr>'.PHP_EOL;
$tab_ligne = 3;
$iii = 0;
for ($t = $am7; $t <= $pm7; $t += $resolution)
{
	echo '<tr>'.PHP_EOL;
	if ($iii % 2 == 1)
		tdcell("cell_hours");
	else
		tdcell("cell_hours2");
	$iii++;
	if ($enable_periods == 'y')
	{
		$time_t = date("i", $t);
		$time_t_stripped = preg_replace( "/^0/", "", $time_t );
		echo $periods_name[$time_t_stripped] .'</td>'.PHP_EOL;
	}
	else
	{
		echo affiche_heure_creneau($t,$resolution).'</td>'.PHP_EOL;
	}
	while (list($key, $room) = each($rooms))
	{
		if (verif_acces_ressource(getUserName(), $room))
		{
			if (isset($today[$room][$t]["id"]))
			{
				$id    = $today[$room][$t]["id"];
				$color = $today[$room][$t]["color"];
				$descr = $today[$room][$t]["data"];
			}
			else
				unset($id);
			if ((isset($id)) && (!est_hors_reservation(mktime(0, 0, 0, $month, $day, $year), $area)))
				$c = $color;
			else if ($statut_room[$room] == "0")
				$c = "avertissement";
			else
				$c = "empty_cell";
			if ((isset($id)) && (!est_hors_reservation(mktime(0, 0, 0, $month, $day, $year), $area)))
			{
				if ( $compteur[$id] == 0 )
				{
					if ($cellules[$id] != 1)
					{
						if (isset($today[$room][$t + ($cellules[$id] - 1) * $resolution]["id"]))
						{
							$id_derniere_ligne_du_bloc = $today[$room][$t + ($cellules[$id] - 1) * $resolution]["id"];
							if ($id_derniere_ligne_du_bloc != $id)
								$cellules[$id] = $cellules[$id]-1;
						}
					}
					tdcell_rowspan ($c, $cellules[$id]);
				}
				$compteur[$id] = 1;
			}
			else
				tdcell ($c);
			if ((!isset($id)) || (est_hors_reservation(mktime(0, 0, 0, $month, $day, $year), $area)))
			{
				$hour = date("H", $t);
				$minute = date("i", $t);
				$date_booking = mktime($hour, $minute, 0, $month, $day, $year);
				if (est_hors_reservation(mktime(0, 0, 0, $month, $day, $year), $area))
				{
					echo '<img src="img_grr/stop.png" alt="'.get_vocab("reservation_impossible").'"  title="'.get_vocab("reservation_impossible").'" width="16" height="16" class="'.$class_image.'" />'.PHP_EOL;
				}
				else
				{
					if (((authGetUserLevel(getUserName(), -1) > 1) || (auth_visiteur(getUserName(), $room) == 1)) && (UserRoomMaxBooking(getUserName(), $room, 1) != 0) && verif_booking_date(getUserName(), -1, $room, $date_booking, $date_now, $enable_periods) && verif_delais_max_resa_room(getUserName(), $room, $date_booking) && verif_delais_min_resa_room(getUserName(), $room, $date_booking) && (($statut_room[$room] == "1") || (($statut_room[$room] == "0") && (authGetUserLevel(getUserName(),$room) > 2) )) && $_GET['pview'] != 1)
					{
						if ($enable_periods == 'y')
						{
							echo '<a href="edit_entry.php?room='.$room.'&amp;period='.$time_t_stripped.'&amp;year='.$year.'&amp;month='.$month.'&amp;day='.$day.'&amp;page=day" title="'.get_vocab("cliquez_pour_effectuer_une_reservation").'" ><span class="glyphicon glyphicon-plus"></span></a>'.PHP_EOL;
						}
						else
						{
							echo '<a href="edit_entry.php?room='.$room.'&amp;hour='.$hour.'&amp;minute='.$minute.'&amp;year='.$year.'&amp;month='.$month.'&amp;day='.$day.'&amp;page=day" title="'.get_vocab("cliquez_pour_effectuer_une_reservation").'" ><span class="glyphicon glyphicon-plus"></span></a>'.PHP_EOL;
						}
					}
					else
					{
						echo ' ';
					}
				}
				echo '</td>'.PHP_EOL;
			}
			else if ($descr != "")
			{
				if ((isset($today[$room][$t]["statut"])) && ($today[$room][$t]["statut"] != '-'))
				{
					echo '<img src="img_grr/buzy.png" alt="'.get_vocab("ressource actuellement empruntee").'" title="'.get_vocab("ressource actuellement empruntee").'" width="20" height="20" class="image" />'.PHP_EOL;
				}
				if (($delais_option_reservation[$room] > 0) && (isset($today[$room][$t]["option_reser"])) && ($today[$room][$t]["option_reser"] != -1))
				{
					echo '<img src="img_grr/small_flag.png" alt="'.get_vocab("reservation_a_confirmer_au_plus_tard_le").'" title="'.get_vocab("reservation_a_confirmer_au_plus_tard_le").' '.time_date_string_jma($today[$room][$t]["option_reser"],$dformat).'" width="20" height="20" class="image" />'.PHP_EOL;
				}
				if ((isset($today[$room][$t]["moderation"])) && ($today[$room][$t]["moderation"] == '1'))
				{
					echo '<img src="img_grr/flag_moderation.png" alt="'.get_vocab("en_attente_moderation").'" title="'.get_vocab("en_attente_moderation").'" class="image" />'.PHP_EOL;
				}
				if (($statut_room[$room] == "1") || (($statut_room[$room] == "0") && (authGetUserLevel(getUserName(), $room) > 2) ))
				{
					if ($acces_fiche_reservation)
					{
						if ($settings->get("display_level_view_entry") == 0)
						{
							$currentPage = 'day';
							echo '<a title="'.htmlspecialchars($today[$room][$t]["who"]).'" data-width="675" onclick="request('.$id.','.$day.','.$month.','.$year.',\''.$currentPage.'\',readData);" data-rel="popup_name" class="poplight">'.$descr.PHP_EOL;
						}
						else
						{
							echo '<a class="lienCellule" title="',htmlspecialchars($today[$room][$t]["who"]),'" href="view_entry.php?id=',$id,'&amp;day=',$day,'&amp;month=',$month,'&amp;year=',$year,'&amp;page=day">',$descr;
						}
					}
					else
					{
						echo ' '.$descr;
					}
					$sql = "SELECT type_name,start_time,end_time,clef,courrier FROM ".TABLE_PREFIX."_type_area ,".TABLE_PREFIX."_entry  WHERE  ".TABLE_PREFIX."_entry.id= ".$today[$room][$t]["id"]." AND ".TABLE_PREFIX."_entry.type= ".TABLE_PREFIX."_type_area.type_letter";
					$res = grr_sql_query($sql);
					for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
					{
						$type_name  = $row['0'];
						$start_time = $row['1'];
						$end_time   = $row['2'];
						$clef 		= $row['3'];
						$courrier	= $row['4'];
						if ($enable_periods != 'y') {
							echo '<br/>',date('H:i', $start_time),get_vocab("to"),date('H:i', $end_time),'<br/>';
						}
						if ($type_name != -1)
							echo  $type_name;
						echo '<br>'.PHP_EOL;
						if ($clef == 1)
							echo '<img src="img_grr/skey.png" alt="clef">'.PHP_EOL;
						if (Settings::get('show_courrier') == 'y')
						{
							if ($courrier == 1)
								echo '<img src="img_grr/scourrier.png" alt="courrier">'.PHP_EOL;
							else
								echo '<img src="img_grr/hourglass.png" alt="buzy">'.PHP_EOL;
						}
					}
					if ($today[$room][$t]["description"]!= "")
					{
						echo '<br /><i>',$today[$room][$t]["description"],'</i>';
					}
				}
				else
				{
					echo ' '.$descr;
				}
				if ($acces_fiche_reservation)
					echo '</a>'.PHP_EOL;
				echo '</td>'.PHP_EOL;
			}
		}
	}
	echo '</tr>'.PHP_EOL;
	reset($rooms);
}
echo '</table>'.PHP_EOL;

grr_sql_free($res);
if ($_GET['pview'] != 1)
{
	echo '<div id="toTop">'.PHP_EOL;
	echo '<b>'.get_vocab('top_of_page').'</b>'.PHP_EOL;
	bouton_retour_haut ();
	echo '</div>'.PHP_EOL;
}
echo '</div>'.PHP_EOL;
echo '</div>'.PHP_EOL;
affiche_pop_up(get_vocab('message_records'), 'user');

?>
<script type="text/javascript">
	$(document).ready(function(){
		$('table.table-bordered td').each(function(){
			var $row = $(this);
			var height = $row.height();
			var h2 = $row.find('a').height();
			$row.find('a').css('min-height', height);
			$row.find('a').css('padding-top', height/2 - h2/2);

		});
	});
	jQuery(document).ready(function($){
		$("#popup_name").draggable({containment: "#container"});
		$("#popup_name").resizable();
	});

</script>
<?php
unset($row);
echo '</div>'.PHP_EOL;
echo '</div>'.PHP_EOL;
echo '<div id="popup_name" class="popup_block"></div>'.PHP_EOL;
include "footer.php";
?>
