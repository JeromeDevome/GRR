<?php
/**
 * year.php
 * Interface d'accueil avec affichage par mois sur plusieurs mois des réservation de toutes les ressources d'un domaine
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2021-05-02 18:59$
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
$grr_script_name = "year.php";
include "include/connect.inc.php";
include "include/config.inc.php";
include "include/misc.inc.php";
include "include/functions.inc.php";
include "include/$dbsys.inc.php";
include "include/mincals.inc.php";
include "include/mrbs_sql.inc.php";
require_once("./include/settings.class.php");
$settings = new Settings();
if (!$settings)
	die("Erreur chargement settings");
require_once("./include/session.inc.php");
include "include/resume_session.php";
include "include/language.inc.php";

// Construction des identifiants du domaine $area, du site $site
global $area, $site;
// echo "paramètres ".$_GET['site']." ".$_GET['area'];
if (isset($_GET['area']))
{
    $area = mysqli_real_escape_string($GLOBALS['db_c'], $_GET['area']);
    settype($area, "integer");
    $site = mrbsGetAreaSite($area);
}
else
{
    if (isset($_GET["site"]))
    {
        $site = mysqli_real_escape_string($GLOBALS['db_c'], $_GET["site"]);
        settype($site, "integer");
        $area = get_default_area($site);
    }
    else
    {
        $site = get_default_site();
        $area = get_default_area($site);
    }
}
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
// définition de variables globales
global $racine, $racineAd, $desactive_VerifNomPrenomUser;

// Lien de retour
$back = (isset($_SERVER['HTTP_REFERER']))? htmlspecialchars($_SERVER['HTTP_REFERER']) :  page_accueil() ;

// Type de session
$user_name = getUserName();
if ((Settings::get("authentification_obli") == 0) && ($user_name == ''))
	$type_session = "no_session";
else
	$type_session = "with_session";
// autres initialisations
$adm = 0;
$racine = "./";
$racineAd = "./admin/";

if (!($desactive_VerifNomPrenomUser))
    $desactive_VerifNomPrenomUser = 'n';
// On vérifie que les noms et prénoms ne sont pas vides
VerifNomPrenomUser($type_session);

if (check_begin_end_bookings($day, $from_month, $from_year))
{
    start_page_w_header($day,$from_month,$from_year,$type_session);
	showNoBookings($day, $from_month, $from_year, $back);
	exit();
}
if (((authGetUserLevel($user_name,-1) < 1) && (Settings::get("authentification_obli") == 1)) || authUserAccesArea($user_name, $area) == 0)
{
    start_page_w_header($day,$from_month,$from_year,$type_session);
	showAccessDenied($back);
	exit();
}

// On vérifie une fois par jour si le délai de confirmation des réservations est dépassé
// Si oui, les réservations concernées sont supprimées et un mail automatique est envoyé.
// On vérifie une fois par jour que les ressources ont été rendues en fin de réservation
// Si non, une notification email est envoyée
if (Settings::get("verif_reservation_auto") == 0)
{
	verify_confirm_reservation();
	verify_retard_reservation();
}

//Month view start time. This ignores morningstarts/eveningends because it
//doesn't make sense to not show all entries for the day, and it messes
//things up when entries cross midnight.
$month_start = mktime(0, 0, 0, $from_month, 1, $from_year);
//What column the month starts in: 0 means $weekstarts weekday.
$weekday_start = (date("w", $month_start) - $weekstarts + 7) % 7;
$month_end = mktime(23, 59, 59, $to_month, 1, $to_year);
$days_in_to_month = date("t", $month_end);
$month_end = mktime(23,59,59,$to_month,$days_in_to_month,$to_year);

// calcul des données à afficher
get_planning_area_values($area);

if ($enable_periods == 'y')
{
	$resolution = 60;
	$morningstarts = 12;
	$eveningends = 12;
	$eveningends_minutes = count($periods_name) - 1;
}
//Used below: localized "all day" text but with non-breaking spaces:
$all_day = preg_replace("/ /", " ", get_vocab("all_day"));
// un type à exclure ?
$type_exclu = Settings::get('exclude_type_in_views_all'); // nom du type exclu
$sql = "SELECT type_letter FROM ".TABLE_PREFIX."_type_area WHERE ".TABLE_PREFIX."_type_area.type_name = '".$type_exclu."' ";
$res = grr_sql_query($sql);
$row = grr_sql_row($res,'0');
$typeExclu = (isset($row[0]))? $row[0]:NULL; // lettre identifiant le type exclu
grr_sql_free($res);
//Get all meetings for these months in the area that we care about
$sql = "SELECT start_time, end_time, ".TABLE_PREFIX."_entry.id, name, beneficiaire, ".TABLE_PREFIX."_room.room_name,type, statut_entry, ".TABLE_PREFIX."_entry.description, ".TABLE_PREFIX."_entry.option_reservation, ".TABLE_PREFIX."_room.delais_option_reservation, ".TABLE_PREFIX."_entry.moderate, beneficiaire_ext, clef, ".TABLE_PREFIX."_entry.courrier, ".TABLE_PREFIX."_type_area.type_name, ".TABLE_PREFIX."_entry.overload_desc
FROM (".TABLE_PREFIX."_entry INNER JOIN ".TABLE_PREFIX."_room ON ".TABLE_PREFIX."_entry.room_id=".TABLE_PREFIX."_room.id ) 
  INNER JOIN ".TABLE_PREFIX."_type_area ON ".TABLE_PREFIX."_entry.type=".TABLE_PREFIX."_type_area.type_letter
WHERE (start_time <= $month_end AND end_time > $month_start AND area_id='".$area."')
ORDER by ".TABLE_PREFIX."_room.order_display, room_name, start_time, end_time ";
/* contenu de la réponse si succès :
    $row[0] : start_time
    $row[1] : end_time
    $row[2] : entry id
    $row[3] : name
    $row[4] : beneficiaire
    $row[5] : room name
    $row[6] : type
    $row[7] : statut_entry
    $row[8] : entry description
    $row[9] : entry option_reservation
    $row[10]: room delais_option_reservation
    $row[11]: entry moderate
    $row[12]: beneficiaire_ext
    $row[13]: clef
    $row[14]: courrier
	$row[15]: Type_name
    $row[16]: overload fields description
*/
//Build an array of information about each day in the month.
//The information is stored as:
// d[monthday]["id"][] = ID of each entry, for linking.
// d[monthday]["data"][] = "start-stop" times of each entry.
$res = grr_sql_query($sql);
if (!$res)
	echo grr_sql_error();
else
{
    $overloadFieldList = mrbsOverloadGetFieldslist($area);
	for ($i = 0; ($row = grr_sql_row_keyed($res, $i)); $i++)
	{
		if ($row["type_name"] <> (Settings::get('exclude_type_in_views_all')))   // Nom du type exclu
		{
            //Fill in data for each day during the month that this meeting covers.
            //Note: int casts on database rows for min and max is needed for PHP3.
            $t = max((int)$row["start_time"], $month_start);
            $end_t = min((int)$row["end_time"], $month_end);
            $day_num = date("j", $t);
            $month_num = date("m", $t);
            $year_num  = date("Y", $t);
            if ($enable_periods == 'y')
                $midnight = mktime(12,0,0,$month_num,$day_num,$year_num);
            else
                $midnight = mktime(0, 0, 0, $month_num, $day_num, $year_num);
            while ($t < $end_t)
            {
                $d[$day_num][$month_num][$year_num]["id"][] = $row["id"];
                // Info-bulle
                $d[$day_num][$month_num][$year_num]["lien"][] = lien_compact($row);
                $d[$day_num][$month_num][$year_num]["room"][]=$row[5] ;
                $d[$day_num][$month_num][$year_num]["color"][] = $row["type"];
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
                                $horaires = $start_str;
                            else
                                $horaires = $start_str . "~" . $end_str;
                            break;
                        case "> = ":
                            $horaires = $start_str . "~24:00";
                            break;
                        case "> > ":
                            $horaires = $start_str . "~==>";
                            break;
                        case "= = ":
                            $horaires = $all_day2.$temp;
                            break;
                        case "= > ":
                            $horaires = $all_day2 . "==>";
                            break;
                        case "< < ":
                            $horaires = "<==~" . $end_str;
                            break;
                        case "< = ":
                            $horaires = "<==" . $all_day2;
                            break;
                        case "< > ":
                            $horaires = "<==" . $all_day2 . "==>";
                            break;
                    }
                }
                else
                {
                    switch (cmp3($row[0], $midnight) . cmp3($row[1], $midnight_tonight))
                    {
                        case "> < ":
                        case "= < ":
                            $horaires = date(hour_min_format(), $row[0]) . "~" . date(hour_min_format(), $row[1]);
                            break;
                        case "> = ":
                            $horaires = date(hour_min_format(), $row[0]) . "~24:00";
                            break;
                        case "> > ":
                            $horaires = date(hour_min_format(), $row[0]) . "~==>";
                            break;
                        case "= = ":
                            $horaires = $all_day2;
                            break;
                        case "= > ":
                            $horaires = $all_day2 . "==>";
                            break;
                        case "< < ":
                            $horaires = "<==~" . date(hour_min_format(), $row[1]);
                            break;
                        case "< = ":
                            $horaires = "<==" . $all_day2;
                            break;
                        case "< > ":
                            $horaires = "<==" . $all_day2 . "==>";
                            break;
                    }
                }
                $d[$day_num][$month_num][$year_num]["data"][] = titre_compact($overloadFieldList, $row, $horaires);
                //Only if end time > midnight does the loop continue for the next day.
                if ($row[1] <= $midnight_tonight)
                    break;
                //$day_num++;
                $t = $midnight = $midnight_tonight;
                $day_num = date("j", $t);
                $month_num = date("m", $t);
                $year_num  = date("Y", $t);
            }
        } // fin condition type exclu
	}
}
grr_sql_free($res);

// pour le traitement des modules
include $racine."/include/hook.class.php";
// code html
header('Content-Type: text/html; charset=utf-8');
if (!isset($_COOKIE['open']))
{
	header('Set-Cookie: open=true; SameSite=Lax');
}
echo '<!DOCTYPE html>'.PHP_EOL;
echo '<html lang="fr">'.PHP_EOL;
// section <head>
if ($type_session == "with_session")
    echo pageHead2(Settings::get("company"),"with_session");
else
    echo pageHead2(Settings::get("company"),"no_session");
// section <body>
echo "<body>";
// Menu du haut = section <header>
echo "<header>";
pageHeader2('', '', '', $type_session);
echo "</header>";
echo '<div id="chargement"></div>'.PHP_EOL; // à éliminer ?
// Debut de la page
echo "<section>";
$this_area_name = grr_sql_query1("SELECT area_name FROM ".TABLE_PREFIX."_area WHERE id=$area");
// Si format imprimable ($_GET['pview'] = 1), on n'affiche pas cette partie 
if ($_GET['pview'] != 1)
{
    echo "<div class='row'>";
        echo "\n<div class=\"col-lg-3 col-md-4 col-xs-12\">\n".PHP_EOL; // choix du site et du domaine
            	echo make_site_select_html('year.php',$site,$from_year,$from_month,$day,$user_name);
                echo make_area_select_all_html('year',$site, $area, $from_year, $from_month, $day, $user_name);
        echo "</div>";
        echo "\n<div class=\"col-lg-4 col-md-6 col-xs-12\">\n".PHP_EOL; // choix des dates 
            echo "<form method=\"get\" action=\"year.php\">";
            echo "<table border=\"0\">\n";
            echo "<tr><td>".get_vocab("report_start").get_vocab("deux_points")."&nbsp</td>";
            echo "<td>";
            echo genDateSelector("from_", "", $from_month, $from_year,"");
            echo "</td></tr>";
            echo "<tr><td>".get_vocab("report_end").get_vocab("deux_points")."&nbsp</td><td>\n";
            echo genDateSelector("to_", "", $to_month, $to_year,"");
            echo "</td></tr>\n";
            echo "<tr><td class=\"CR\">\n";
            echo "<br><p>";
            echo "<input type=\"hidden\" name=\"site\" value=\"$site\" />\n";
            echo "<input type=\"hidden\" name=\"area\" value=\"$area\" />\n";
            echo "<input type=\"submit\" name=\"valider\" value=\"".$vocab["goto"]."\" /></p></td></tr>\n";
            echo "</table>\n";
            echo "</form>";
        echo "</div>";
    echo "</div>";
}
//echo "<div class=\"titre_planning\"><h4>".ucfirst($this_area_name)." - ".get_vocab("all_areas")."</h4></div>\n";
// à revoir ?
echo "<div class=\"col-xs-12 center\"><h4>".ucfirst($this_area_name)." - ".get_vocab("all_areas")."</h4></div>\n";
// Boucle sur les mois
$month_indice =  $month_start;
while ($month_indice < $month_end)
{
	$month_num = date("m", $month_indice);
	$year_num  = date("Y", $month_indice);
	$days_in_month = date("t", $month_indice);
    echo "<table class='mois table-bordered table-striped'>";
    echo "<caption>";
    echo "<h4><a href='month_all2.php?month=".$month_num."&year=".$year_num."&area=".$area."'>".ucfirst(utf8_strftime("%B", $month_indice))."</a>".utf8_strftime(" %Y", $month_indice)."</h4>";
    echo "</caption>";
	// Début affichage de la première ligne
	echo "<thead><tr>";
	tdcell("cell_hours");
	echo get_vocab('rooms')."</td>\n";
	$t2 = mktime(0, 0, 0, $month_num, 1, $year_num);
	for ($k = 0; $k < $days_in_month; $k++)
	{
		$cday = date("j", $t2);
		$cmonth =date("m", $t2);
		$cweek = date("w", $t2);
		$cyear = date("Y", $t2);
		$name_day = ucfirst(utf8_strftime("%a<br />%d", $t2)); // On inscrit le quantième du jour dans la deuxième ligne
		$temp = mktime(0,0,0,$cmonth,$cday,$cyear);
		$jour_cycle = grr_sql_query1("SELECT Jours FROM ".TABLE_PREFIX."_calendrier_jours_cycle WHERE DAY='$temp'");
        $t2 = mktime(0,0,0,$month_num,$cday+1,$year_num);
		if ($display_day[$cweek] == 1)
		{
			if (isHoliday($temp)) {echo tdcell("cell_hours ferie");}
            else if (isSchoolHoliday($temp)) {echo tdcell("cell_hours vacance");}
            else {echo tdcell("cell_hours");}
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
	echo "</tr></thead>";
	// Fin affichage de la première ligne
	$sql = "SELECT room_name, capacity, id, description FROM ".TABLE_PREFIX."_room WHERE area_id=$area ORDER BY order_display,room_name";
	$res = grr_sql_query($sql);
	$li = 0;
	for ($ir = 0; ($row = grr_sql_row($res, $ir)); $ir++)
	{
   			// calcul de l'accès à la ressource en fonction du niveau de l'utilisateur
		$verif_acces_ressource = verif_acces_ressource($user_name, $row[2]);
		if ($verif_acces_ressource) // on n'affiche que les ressources accessibles
		{
				// Calcul du niveau d'accès aux fiches de réservation détaillées des ressources
			$acces_fiche_reservation = verif_acces_fiche_reservation($user_name, $row[2]);
			echo "<tr><th>";
			echo htmlspecialchars($row[0]) ."</th>\n";
			$li++;
			for ($k = 1; $k <= $days_in_month; $k++)
			{
                $t2 = mktime(0, 0, 0,$month_num, $k, $year_num);
				$cday = date("j", $t2);
				$cweek = date("w", $t2);
				if ($display_day[$cweek] == 1) // Début condition "on n'affiche pas tous les jours de la semaine"
				{
					echo "<td> \n";
					if (est_hors_reservation(mktime(0,0,0,$month_num,$cday,$year_num),$area))
					{
						echo "<div class=\"empty_cell\">";
						echo "<img src=\"img_grr/stop.png\" alt=\"".get_vocab("reservation_impossible")."\"  title=\"".get_vocab("reservation_impossible")."\" width=\"16\" height=\"16\" class=\"".$class_image."\"  /></div>";
					}
						//Anything to display for this day?
					elseif (isset($d[$cday][$cmonth][$cyear]["id"][0]))
					{
						$n = count($d[$cday][$cmonth][$cyear]["id"]);
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
								if ($d[$cday][$cmonth][$cyear]["room"][$i] == $row[0]) // test peu fiable car c'est l'id qui est unique YN le 26/02/2018
								{
										//if ($i > 0 && $i % 2 == 0) echo "<br />"; else echo " ";
									echo "\n<table class='pleine table-bordered' ><tr>\n";
									tdcell($d[$cday][$cmonth][$cyear]["color"][$i]);
									if ($acces_fiche_reservation)
									{
										if (Settings::get("display_level_view_entry") == 0)
										{
											$currentPage = 'year';
											$id =   $d[$cday][$cmonth][$cyear]["id"][$i];
											echo "<a title=\"".htmlspecialchars($d[$cday][$cmonth][$cyear]["data"][$i])."\" data-width=\"675\" onclick=\"request($id,$cday,$cmonth,$cyear,'all','$currentPage',readData);\" data-rel=\"popup_name\" class=\"poplight lienCellule\">" .$d[$cday][$cmonth][$cyear]["lien"][$i]."</a>";
										}
										else
										{
											echo "<a class=\"lienCellule\" title=\"".htmlspecialchars($d[$cday][$cmonth][$cyear]["data"][$i])."\" href=\"view_entry.php?id=" . $d[$cday][$cmonth][$cyear]["id"][$i]."&amp;page=year\">"
											.$d[$cday][$cmonth][$cyear]["lien"][$i]. "</a>";
										}
									}
									else
										echo $d[$cday][$cmonth][$cyear]["lien"][$i];
									echo "\n</td></tr></table>\n";
								}
							}
						}
					}
					echo "</td>\n";
				} // fin condition "on n'affiche pas tous les jours de la semaine"
			} // fin boucle jours
			echo "</tr>\n";
		} // fin ressources accessibles
	} // fin boucle ressources
	echo "</table>\n";
    grr_sql_free($res);
	$month_indice = mktime(0, 0, 0, $month_num + 1, 1, $year_num);
} // Fin de la boucle sur les mois
echo "<div class='pleine center'>";
echo "<div class='col-lg-3 col-md-4 col-sm-6 col-xs-12'>";
show_colour_key($area);
echo "</div>";
echo "<div class='col-xs-12'>";
include "include/trailer.inc.php";
echo "</div>";
echo "</div>";
// Affichage d'un message pop-up
affiche_pop_up(get_vocab("message_records"),"user");
echo  "<div id=\"popup_name\" class=\"popup_block\" ></div>";
if ($_GET['pview'] != 1)
{
	echo '<div id="toTop">'.PHP_EOL;
	echo '<b>'.get_vocab('top_of_page').'</b>'.PHP_EOL;
	bouton_retour_haut ();
	echo '</div>'.PHP_EOL;
}
echo "</section>";
?>
<script type="text/javascript">
	$(document).ready(function(){
        if ( $(window).scrollTop() == 0 )
            $("#toTop").hide(1);
		$("#popup_name").draggable({containment: "#container"});
		$("#popup_name").resizable();
    });
</script>
</body>
</html>