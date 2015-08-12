<?php
/**
 * year.php
 * Interface d'accueil avec affichage par mois sur plusieurs mois des réservation de toutes les ressources d'un domaine
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2009-12-02 20:11:08 $
 * @author    Laurent Delineau <laurent.delineau@ac-poitiers.fr>
 * @copyright Copyright 2003-2008 Laurent Delineau
 * @link      http://www.gnu.org/licenses/licenses.html
 * @package   root
 * @version   $Id: year.php,v 1.16 2009-12-02 20:11:08 grr Exp $
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
include "include/misc.inc.php";
include "include/functions.inc.php";
include "include/$dbsys.inc.php";
include "include/mincals.inc.php";
include "include/mrbs_sql.inc.php"; include 'include/twigInit.php';
$grr_script_name = "year.php";
// Settings
require_once("./include/settings.class.php");
//Chargement des valeurs de la table settingS
if (!Settings::load())
	die("Erreur chargement settings");
// Session related functions
require_once("./include/session.inc.php");
// Resume session
include "include/resume_session.php";
// Construction des identifiants de la ressource $room, du domaine $area, du site $id_site
Definition_ressource_domaine_site();
// Récupération des données concernant l'affichage du planning du domaine
get_planning_area_values($area);
// Paramètres langage
include "include/language.inc.php";
// On affiche le lien "format imprimable" en bas de la page
$affiche_pview = '1';
if (!isset($_GET['pview']))
	$_GET['pview'] = 0;
else
	$_GET['pview'] = 1;
if ($_GET['pview'] == 1)
	$class_image = "print_image";
else
	$class_image = "image";
$from_month = isset($_GET["from_month"]) ? $_GET["from_month"] : NULL;
$from_year = isset($_GET["from_year"]) ? $_GET["from_year"] : NULL;
$to_month = isset($_GET["to_month"]) ? $_GET["to_month"] : NULL;
$to_year = isset($_GET["to_year"]) ? $_GET["to_year"] : NULL;
$day = 1;
$date_now = time();
//Default parameters:
if (empty($debug_flag))
	$debug_flag = 0;
if (empty($from_month) || empty($from_year) || !checkdate($from_month, 1, $from_year))
{
	if ($date_now < Settings::get('begin_bookings'))
		$date_ = Settings::get('begin_bookings');
	else if ($date_now > Settings::get('end_bookings'))
		$date_ = Settings::get('end_bookings');
	else
		$date_ = $date_now;
	$day   = date('d',$date_);
	$from_month = date('m',$date_);
	$from_year  = date('Y',$date_);
}
else
{
	$date_ = mktime(0, 0, 0, $from_month, $day, $from_year);
	if ($date_ < Settings::get('begin_bookings'))
		$date_ = Settings::get('begin_bookings');
	else if ($date_ > Settings::get('end_bookings'))
		$date_ = Settings::get('end_bookings');
	$day   = date('d',$date_);
	$from_month = date('m',$date_);
	$from_year  = date('Y',$date_);
}
if (empty($to_month) || empty($to_year) || !checkdate($to_month, 1, $to_year))
{
	$to_month = $from_month;
	$to_year  = $from_year;
}
else
{
	$date_ = mktime(0, 0, 0, $to_month, 1, $to_year);
	if ($date_ < Settings::get('begin_bookings'))
		$date_ = Settings::get('begin_bookings');
	else if ($date_ > Settings::get('end_bookings'))
		$date_ = Settings::get('end_bookings');
	$to_month = date('m',$date_);
	$to_year  = date('Y',$date_);
}
if ((Settings::get("authentification_obli") == 0) && (getUserName() == ''))
	$type_session = "no_session";
else
	$type_session = "with_session";
$back = '';
if (isset($_SERVER['HTTP_REFERER']))
	$back = htmlspecialchars($_SERVER['HTTP_REFERER']);
if (check_begin_end_bookings($day, $from_month, $from_year))
{
	showNoBookings($day, $from_month, $from_year, $back);
	exit();
}
if (((authGetUserLevel(getUserName(),-1) < 1) && (Settings::get("authentification_obli") == 1)) || authUserAccesArea(getUserName(), $area) == 0)
{
	showAccessDenied($back);
	exit();
}

// On vérifie une fois par jour si le délai de confirmation des réservations est dépassé
// Si oui, les réservations concernées sont supprimées et un mail automatique est envoyé.
// On vérifie une fois par jour que les ressources ont été rendue en fin de réservation
// Si non, une notification email est envoyée
if (Settings::get("verif_reservation_auto") == 0)
{
	verify_confirm_reservation();
	verify_retard_reservation();
}
//print the page header
print_header($day, $from_month, $from_year, $type_session);
//Month view start time. This ignores morningstarts/eveningends because it
//doesn't make sense to not show all entries for the day, and it messes
//things up when entries cross midnight.
$month_start = mktime(0, 0, 0, $from_month, 1, $from_year);
//What column the month starts in: 0 means $weekstarts weekday.
$weekday_start = (date("w", $month_start) - $weekstarts + 7) % 7;
$days_in_to_month = date("t", $to_month);
$month_end = mktime(23, 59, 59, $to_month, $days_in_to_month, $to_year);
if ($enable_periods == 'y')
{
	$resolution = 60;
	$morningstarts = 12;
	$eveningends = 12;
	$eveningends_minutes = count($periods_name) - 1;
}
// Si format imprimable ($_GET['pview'] = 1), on n'affiche pas cette partie
if ($_GET['pview'] != 1)
{
	echo "<table width=\"100%\" cellspacing=\"15\" border=\"0\"><tr>";
	if (isset($_SESSION['default_list_type']) || (Settings::get("authentification_obli") == 1))
		$area_list_format = $_SESSION['default_list_type'];
	else
		$area_list_format = Settings::get("area_list_format");
	//show either a select box or the normal html list
	if ($area_list_format != "list")
	{
		echo "<td>\n";
		echo make_site_select_html('year.php',$id_site,$from_year,$from_month,$day,getUserName());
		echo make_area_select_html('year.php',$id_site, $area, $from_year, $from_month, $day, getUserName());
		echo "</td>\n";
	}
	else
	{
		echo "<td>\n";
		echo make_site_list_html('year.php',$id_site,$from_year,$from_month,$day,getUserName());
		echo "</td><td>";
		echo make_area_list_html('year.php',$id_site, $area, $from_year, $from_month, $day, getUserName());
		echo "</td>\n";
	}
	echo "\n<td><form method=\"get\" action=\"year.php\">";
	echo "<table border=\"0\">\n";
	echo "<tr><td>".get_vocab("report_start").get_vocab("deux_points")."</td>";
	echo "<td>";
	echo genDateSelector("from_", "", $from_month, $from_year,"");
	echo "</td></tr>";
	echo "<tr><td>".get_vocab("report_end").get_vocab("deux_points");
	echo "</td><td>\n";
	echo genDateSelector("to_", "", $to_month, $to_year,"");
	echo "</td></tr>\n";
	echo "<tr><td>\n";
	echo "<input type=\"hidden\" name=\"area\" value=\"$area\" />\n";
	echo "<input type=\"submit\" name=\"valider\" value=\"".$vocab["goto"]."\" /></td><td> </td></tr>\n";
	echo "</table>\n";
	echo "</form></td>\n";
	echo '<td><a title="'.htmlspecialchars(get_vocab('back')).'" href="'.page_accueil('no').'">'.$vocab['back'].'</a></td>';
	echo "</tr></table>\n";
}
$this_area_name = grr_sql_query1("SELECT area_name FROM ".TABLE_PREFIX."_area WHERE id=$area");
echo "<div class=\"titre_planning\">".ucfirst($this_area_name)." - ".get_vocab("all_areas")." </div>\n";
//Used below: localized "all day" text but with non-breaking spaces:
$all_day = preg_replace("/ /", " ", get_vocab("all_day"));
//Get all meetings for this month in the room that we care about
//row[0] = Start time
//row[1] = End time
//row[2] = Entry ID
//row[3] = Entry name (brief description)
//row[4] = beneficiaire of the booking
//row[5] = Nom de la ressource
//row[6] = statut
//row[7] = Description complète
$sql = "SELECT start_time, end_time,".TABLE_PREFIX."_entry.id, name, beneficiaire, room_name, statut_entry, ".TABLE_PREFIX."_entry.description, ".TABLE_PREFIX."_entry.option_reservation, ".TABLE_PREFIX."_room.delais_option_reservation, type, ".TABLE_PREFIX."_entry.moderate, beneficiaire_ext
FROM ".TABLE_PREFIX."_entry inner join ".TABLE_PREFIX."_room on ".TABLE_PREFIX."_entry.room_id=".TABLE_PREFIX."_room.id
WHERE (start_time <= $month_end AND end_time > $month_start and area_id='".$area."')
ORDER by start_time, end_time, ".TABLE_PREFIX."_room.room_name";
//Build an array of information about each day in the month.
//The information is stored as:
// d[monthday]["id"][] = ID of each entry, for linking.
// d[monthday]["data"][] = "start-stop" times of each entry.
$res = grr_sql_query($sql);
if (!$res)
	echo grr_sql_error();
else
{
	for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
	{
		//Fill in data for each day during the month that this meeting covers.
		//Note: int casts on database rows for min and max is needed for PHP3.
		$t = max((int)$row[0], $month_start);
		$end_t = min((int)$row[1], $month_end);
		$day_num = date("j", $t);
		$month_num = date("m", $t);
		$year_num  = date("Y", $t);
		if ($enable_periods == 'y')
			$midnight = mktime(12,0,0,$month_num,$day_num,$year_num);
		else
			$midnight = mktime(0, 0, 0, $month_num, $day_num, $year_num);
		while ($t < $end_t)
		{
			$d[$day_num][$month_num][$year_num]["id"][] = $row[2];
			// Info-bulle
			$temp = "";
			if (Settings::get("display_info_bulle") == 1)
				$temp = get_vocab("reservee au nom de").affiche_nom_prenom_email($row[4],$row[12],"nomail");
			else if (Settings::get("display_info_bulle") == 2)
				$temp = $row[7];
			if ($temp != "")
				$temp = " - ".$temp;
			$d[$day_num][$month_num][$year_num]["who1"][] = affichage_lien_resa_planning($row[3],$row[2]);
			$d[$day_num][$month_num][$year_num]["room"][]=$row[5] ;
			$d[$day_num][$month_num][$year_num]["res"][] = $row[6];
			$d[$day_num][$month_num][$year_num]["color"][] = $row[10];
			if ($row[9] > 0)
				$d[$day_num][$month_num][$year_num]["option_reser"][] = $row[8];
			else
				$d[$day_num][$month_num][$year_num]["option_reser"][] = -1;
			$d[$day_num][$month_num][$year_num]["moderation"][] = $row[11];
			$midnight_tonight = $midnight + 86400;
			//Describe the start and end time, accounting for "all day"
			//and for entries starting before/ending after today.
			//There are 9 cases, for start time < = or > midnight this morning,
			//and end time < = or > midnight tonight.
			//Use ~ (not -) to separate the start and stop times, because MSIE
			//will incorrectly line break after a -.
			$all_day2 = preg_replace("/ /", " ", $all_day);
			if ($enable_periods == 'y')
			{
				$start_str = preg_replace("/ /", " ", period_time_string($row[0]));
				$end_str   = preg_replace("/ /", " ", period_time_string($row[1], -1));
				switch (cmp3($row[0], $midnight) . cmp3($row[1], $midnight_tonight))
				{
					case "> < ":
					case "= < ":
					if ($start_str == $end_str)
						$d[$day_num][$month_num][$year_num]["data"][] = $start_str." - ".$row[3].$temp;
					else
						$d[$day_num][$month_num][$year_num]["data"][] = $start_str . "~" . $end_str." - ".$row[3].$temp;
					break;
					case "> = ":
					$d[$day_num][$month_num][$year_num]["data"][] = $start_str . "~24:00"." - ".$row[3].$temp;
					break;
					case "> > ":
					$d[$day_num][$month_num][$year_num]["data"][] = $start_str . "~====>"." - ".$row[3].$temp;
					break;
					case "= = ":
					$d[$day_num][$month_num][$year_num]["data"][] = $all_day2.$temp;
					break;
					case "= > ":
					$d[$day_num][$month_num][$year_num]["data"][] = $all_day2 . "====>"." - ".$row[3].$temp;
					break;
					case "< < ":
					$d[$day_num][$month_num][$year_num]["data"][] = "<====~" . $end_str." - ".$row[3].$temp;
					break;
					case "< = ":
					$d[$day_num][$month_num][$year_num]["data"][] = "<====" . $all_day2." - ".$row[3].$temp;
					break;
					case "< > ":
					$d[$day_num][$month_num][$year_num]["data"][] = "<====" . $all_day2 . "====>"." - ".$row[3].$temp;
					break;
				}
			}
			else
			{
				switch (cmp3($row[0], $midnight) . cmp3($row[1], $midnight_tonight))
				{
					case "> < ":
					case "= < ":
					$d[$day_num][$month_num][$year_num]["data"][] = date(hour_min_format(), $row[0]) . "~" . date(hour_min_format(), $row[1])." - ".$row[3].$temp;
					break;
					case "> = ":
					$d[$day_num][$month_num][$year_num]["data"][] = date(hour_min_format(), $row[0]) . "~24:00"." - ".$row[3].$temp;
					break;
					case "> > ":
					$d[$day_num][$month_num][$year_num]["data"][] = date(hour_min_format(), $row[0]) . "~====>"." - ".$row[3].$temp;
					break;
					case "= = ":
					$d[$day_num][$month_num][$year_num]["data"][] = $all_day2.$temp;
					break;
					case "= > ":
					$d[$day_num][$month_num][$year_num]["data"][] = $all_day2 . "====>"." - ".$row[3].$temp;
					break;
					case "< < ":
					$d[$day_num][$month_num][$year_num]["data"][] = "<====~" . date(hour_min_format(), $row[1])." - ".$row[3].$temp;
					break;
					case "< = ":
					$d[$day_num][$month_num][$year_num]["data"][] = "<====" . $all_day2." - ".$row[3].$temp;
					break;
					case "< > ":
					$d[$day_num][$month_num][$year_num]["data"][] = "<====" . $all_day2 . "====>"." - ".$row[3].$temp;
					break;
				}
			}
			//Only if end time > midnight does the loop continue for the next day.
			if ($row[1] <= $midnight_tonight)
				break;
			//$day_num++;
			$t = $midnight = $midnight_tonight;
			$day_num = date("j", $t);
			$month_num = date("m", $t);
			$year_num  = date("Y", $t);
		}
	}
}
// Boucle sur les mois
$month_indice =  $month_start;
while ($month_indice < $month_end)
{
	$month_num = date("m", $month_indice);
	$year_num  = date("Y", $month_indice);
	$days_in_month = date("t", $month_indice);
	$weekcol = 0;
	echo "<div class=\"titre_planning\">" . ucfirst(utf8_strftime("%B %Y", $month_indice)). "</div>\n";
	echo "<table border=\"2\">\n";
	$sql = "select room_name, capacity, id, description from ".TABLE_PREFIX."_room where area_id=$area order by order_display,room_name";
	$res = grr_sql_query($sql);
	// Début affichage de la première ligne
	echo "<tr>";
	tdcell("cell_hours");
	echo " </td>\n";
	//Corrige un bug avec certains fuseaux horaires (par exemple GMT-05:00 celui du Québec) :
	//plusieurs mois débutent par le dernier jours du mois précédent.
	//En changeant "gmmktime" par "mktime" le bug est corrigé
	//$t2=gmmktime(0,0,0,$month_num,1,$year_num);
	$t2 = mktime(0, 0, 0, $month_num, 1, $year_num);
	for ($k = 0; $k < $days_in_month; $k++)
	{
		$cday = date("j", $t2);
		$cmonth =date("m", $t2);
		$cweek = date("w", $t2);
		$cyear = date("Y", $t2);
		$name_day = ucfirst(utf8_strftime("%a<br />%d", $t2));
		$temp = mktime(0,0,0,$cmonth,$cday,$cyear);
		$jour_cycle = grr_sql_query1("SELECT Jours FROM ".TABLE_PREFIX."_calendrier_jours_cycle WHERE DAY='$temp'");
		$t2 += 86400;
		// On inscrit le numéro du mois dans la deuxième ligne
		if ($display_day[$cweek] == 1)
		{
			echo tdcell("cell_hours");
			echo "<div><a title=\"".htmlspecialchars(get_vocab("see_all_the_rooms_for_the_day"))."\"   href=\"day.php?year=$year_num&amp;month=$month_num&amp;day=$cday&amp;area=$area\">$name_day</a>";
			if (Settings::get("jours_cycles_actif") == "Oui" && intval($jour_cycle)>-1)
			{
				if (intval($jour_cycle) > 0)
					echo "<br /><b><i>".ucfirst(substr(get_vocab("rep_type_6"),0,1)).$jour_cycle."</i></b>";
				else
				{
					if (strlen($jour_cycle)>5)
						$jour_cycle = substr($jour_cycle,0,3)."..";
					echo "<br /><b><i>".$jour_cycle."</i></b>";
				}
			}
			echo "</div></td>\n";
		}
	}
	echo "</tr>";
		// Fin affichage de la première ligne
	$li = 0;
	for ($ir = 0; ($row = grr_sql_row($res, $ir)); $ir++)
	{
   			// calcul de l'accès à la ressource en fonction du niveau de l'utilisateur
		$verif_acces_ressource = verif_acces_ressource(getUserName(), $row[2]);
		if ($verif_acces_ressource)
		{
   				// on n'affiche pas toutes les ressources
				// Calcul du niveau d'accès aux fiche de réservation détaillées des ressources
			$acces_fiche_reservation = verif_acces_fiche_reservation(getUserName(), $row[2]);
			echo "<tr>";
			tdcell("cell_hours");
			echo htmlspecialchars($row[0]) ."</td>\n";
			$li++;
				//Corrige un bug avec certains fuseaux horaires (par exemple GMT-05:00 celui du Québec) :
				//plusieurs mois débutent par le dernier jours du mois précédent.
				//En changeant "gmmktime" par "mktime" le bug est corrigé
				//$t2=gmmktime(0,0,0,$month_num,1,$year_num);
			$t2 = mktime(0, 0, 0, $month_num, 1, $year_num);
			for ($k = 0; $k < $days_in_month; $k++)
			{
				$cday = date("j", $t2);
				$cweek = date("w", $t2);
				$t2 += 86400;
				if ($display_day[$cweek] == 1)
				{
	   					// Début condition "on n'affiche pas tous les jours de la semaine"
					echo "<td valign=\"top\" class=\"cell_month\"> \n";
					if (est_hors_reservation(mktime(0,0,0,$month_num,$cday,$year_num),$area))
					{
						echo "<div class=\"empty_cell\">";
						echo "<img src=\"img_grr/stop.png\" alt=\"".get_vocab("reservation_impossible")."\"  title=\"".get_vocab("reservation_impossible")."\" width=\"16\" height=\"16\" class=\"".$class_image."\"  /></div>";
					}
						//Anything to display for this day?
					if (isset($d[$cday][$cmonth][$cyear]["id"][0]))
					{
						$n = count($d[$cday][$cmonth][$cyear]["id"]);
							//Show the start/stop times, 2 per line, linked to view_entry.
							//If there are 12 or fewer, show them, else show 11 and "...".
						for ($i = 0; $i < $n; $i++)
						{
							if ($i == 11 && $n > 12)
							{
								echo " ...\n";
								break;
							}
							for ($i = 0; $i < $n; $i++)
							{
								if ($d[$cday][$cmonth][$cyear]["room"][$i] == $row[0])
								{
										//if ($i > 0 && $i % 2 == 0) echo "<br />"; else echo " ";
									echo "\n<br />\n<table width=\"100%\" border=\"0\" ><tr>\n";
									tdcell($d[$cday][$cmonth][$cyear]["color"][$i]);
									if ($d[$cday][$cmonth][$cyear]["res"][$i] != '-')
										echo " <img src=\"img_grr/buzy.png\" alt=\"".get_vocab("ressource actuellement empruntee")."\" title=\"".get_vocab("ressource actuellement empruntee")."\" width=\"20\" height=\"20\" class=\"image\" /> \n";
		   								// si la réservation est à confirmer, on le signale
									if ((isset($d[$cday][$cmonth][$cyear]["option_reser"][$i])) && ($d[$cday][$cmonth][$cyear]["option_reser"][$i] != -1))
										echo " <img src=\"img_grr/small_flag.png\" alt=\"".get_vocab("reservation_a_confirmer_au_plus_tard_le")."\" title=\"".get_vocab("reservation_a_confirmer_au_plus_tard_le")." ".time_date_string_jma($d[$cday][$cmonth][$cyear]["option_reser"][$i],$dformat)."\" width=\"20\" height=\"20\" class=\"image\" /> \n";
		   								// si la réservation est à modérer, on le signale
									if ((isset($d[$cday][$cmonth][$cyear]["moderation"][$i])) && ($d[$cday][$cmonth][$cyear]["moderation"][$i] == 1))
										echo " <img src=\"img_grr/flag_moderation.png\" alt=\"".get_vocab("en_attente_moderation")."\" title=\"".get_vocab("en_attente_moderation")."\" class=\"image\" /> \n";
									if ($acces_fiche_reservation)
										echo "<a title=\"".htmlspecialchars($d[$cday][$cmonth][$cyear]["data"][$i])."\" href=\"view_entry.php?id=" . $d[$cday][$cmonth][$cyear]["id"][$i]."&amp;page=month\">"
									.$d[$cday][$cmonth][$cyear]["who1"][$i]{0}
									. "</a>";
									else
										echo $d[$cday][$cmonth][$cyear]["who1"][$i]{0};
									echo "\n</td></tr></table>\n";
								}
							}
						}
					}
					echo "</td>\n";
				}
					// fin condition "on n'affiche pas tous les jours de la semaine"
					//    if (++$weekcol == 7) $weekcol = 0;
			}
			echo "</tr>\n";
		}
	}
	echo "</table>\n";
	$month_indice = mktime(0, 0, 0, $month_num + 1, 2, $year_num);
		// Fin de la boucle sur les mois
}
show_colour_key($area);
	// Affichage d'un message pop-up
affiche_pop_up(get_vocab("message_records"),"user");
include "include/trailer.inc.php";
include "footer.php";
?>
