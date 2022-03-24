<?php
/**
 * year_all.php
 * Interface d'accueil avec affichage par mois sur plusieurs mois des réservations de toutes les ressources d'un site
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2022-02-08 14:47 $
 * @author    Yan Naessens, Laurent Delineau 
 * @copyright Copyright 2003-2022 Yan Naessens, Laurent Delineau
 * @link      http://www.gnu.org/licenses/licenses.html
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
$grr_script_name = "year_all.php";
include "include/connect.inc.php";
include "include/config.inc.php";
include "include/misc.inc.php";
include "include/functions.inc.php";
include "include/$dbsys.inc.php";
include "include/mincals.inc.php";
include "include/mrbs_sql.inc.php";

// Settings
require_once("./include/settings.class.php");
//Chargement des valeurs de la table settingS
if (!Settings::load())
	die("Erreur chargement settings");
// Session related functions
require_once("./include/session.inc.php");
// Resume session
include "include/resume_session.php";
// Paramètres langage
include "include/language.inc.php";
// Construction des identifiants du domaine $area, du site $site
// est-il utile de calculer $area ?
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
        $area = NULL;
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
$from_month = isset($_GET["from_month"]) ? intval($_GET["from_month"]) : (isset($_GET['month'])? intval($_GET['month']) : NULL);
$from_year = isset($_GET["from_year"]) ? intval($_GET["from_year"]) : (isset($_GET['year'])? intval($_GET['year']) : NULL);
$to_month = isset($_GET["to_month"]) ? intval($_GET["to_month"]) : (isset($_GET['month'])? intval($_GET['month']) : NULL);
$to_year = isset($_GET["to_year"]) ? intval($_GET["to_year"]) : (isset($_GET['year'])? intval($_GET['year']) : NULL);
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

$user_name = getUserName();
if ((Settings::get("authentification_obli") == 0) && ($user_name == ''))
	$type_session = "no_session";
else
	$type_session = "with_session";

$back = 'year.php?year='.$from_year.'&amp;month='.$from_month.'&amp;day='.$day;
if (check_begin_end_bookings($day, $from_month, $from_year))
{
    start_page_w_header($day, $from_month, $from_year, $type_session);
	showNoBookings($day, $from_month, $from_year, $back);
	exit();
}
if (((authGetUserLevel($user_name,-1) < 1) && (Settings::get("authentification_obli") == 1)) || authUserAccesArea($user_name, $area) == 0)
{
    start_page_w_header($day, $from_month, $from_year, $type_session);
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

// définition de variables globales
global $racine, $racineAd, $desactive_VerifNomPrenomUser;
// autres initialisations
$adm = 0;
$racine = "./";
$racineAd = "./admin/";

if (!($desactive_VerifNomPrenomUser))
    $desactive_VerifNomPrenomUser = 'n';
// On vérifie que les noms et prénoms ne sont pas vides
VerifNomPrenomUser($type_session);

//Month view start time. This ignores morningstarts/eveningends because it
//doesn't make sense to not show all entries for the day, and it messes
//things up when entries cross midnight.
$month_start = mktime(0, 0, 0, $from_month, 1, $from_year);
$month_end = mktime(23, 59, 59, $to_month, 1, $to_year);
$days_in_to_month = date("t", $month_end);
$month_end = mktime(23,59,59,$to_month,$days_in_to_month,$to_year);
// options pour l'affichage
$opt = array('horaires','beneficiaire','short_desc','description','create_by','type','participants');
$options = decode_options(Settings::get('cell_year_all'),$opt);
$options_popup = decode_options(Settings::get('popup_year_all'),$opt);
// construit la liste des ressources et domaines
if ($site == -1) 
{   // cas 1 : le multisite n'est pas activé $site devrait être à -1
    $sql  = "SELECT DISTINCT r.id,r.room_name,a.id FROM ".TABLE_PREFIX."_room r JOIN ".TABLE_PREFIX."_area a ON r.area_id = a.id ORDER BY a.order_display,r.order_display";
}
else
{
    if ($site == 0){$site = get_default_site();} // si le site n'est pas défini, on le met à la valeur par défaut
    $sql  = "SELECT DISTINCT r.id,r.room_name,a.id FROM ".TABLE_PREFIX."_room r JOIN (SELECT * FROM ".TABLE_PREFIX."_area d JOIN ".TABLE_PREFIX."_j_site_area j ON j.id_area = d.id WHERE j.id_site = ".$site.") a ON r.area_id = a.id ORDER BY a.order_display,r.order_display";
}    
$res_dom = grr_sql_query($sql);
if (!$res_dom)
	echo grr_sql_error(); // sortie en cas d'erreur de lecture dans la base MySQL
else
{   // on stocke les résultats dans un tableau
    $data = array();
    for ($i = 0; ($row = grr_sql_row($res_dom, $i)); $i++)
    {
        $data[$i] = $row;
    }
    grr_sql_free($res_dom);
    $rooms = array(); // contiendra les ressources accessibles
    $areas = array(); // contiendra les domaines accessibles
	foreach ($data as $row)
    {
        $rooms[$row[0]] = FALSE;
        $areas[$row[2]] = FALSE;
    }
    foreach ($data as $row)
    {
        $verif_acces_ressource = verif_acces_ressource($user_name, $row[0]);
        if ($verif_acces_ressource)
        {
            $rooms[$row[0]] = TRUE;
            $areas[$row[2]] = TRUE;
        }
    }
    $v_areas = array(); // contient la suite des identifiants des domaines accessibles, ordonnés selon order_display
    $i = 0;
    foreach ($areas as $id => $val)
    {
        if ($val) {
            if ($i==0) {
                $v_areas[$i] = $id;
                $i++;
            }
            else if ($id != $v_areas[$i-1]) {
                $v_areas[$i] = $id;
                $i++;
            }
        }  
    }
    // nom du site
    if ($site > 0){
        $nom_site = grr_sql_query1("SELECT sitename FROM ".TABLE_PREFIX."_site WHERE id=".$site);
    }
    else $nom_site = get_vocab('any_area');
    $tables = array(); // contiendra les codes html des tables mensuelles
    // Boucle sur les mois
    $month_indice =  $month_start;
    while ($month_indice < $month_end)
    {
        $month_num = date("m", $month_indice);
        $year_num  = date("Y", $month_indice);
        $days_in_month = date("t", $month_indice);
        $begin_month = mktime(0, 0, 0, $month_num, 1, $year_num);
        $end_month = mktime(23,59,59,$month_num,$days_in_month,$year_num);
        $tables[$month_indice] = "<div class=\"titre_planning\">" . ucfirst(utf8_strftime("%B %Y", $month_indice)). "</div>\n";
        // boucle sur les domaines accessibles
        foreach ($v_areas as $area)
        {
            // Récupération des données concernant l'affichage du planning du domaine
            get_planning_area_values($area);
            $overloadFieldList = mrbsOverloadGetFieldslist($area);
            if ($enable_periods == 'y')
            {
                $resolution = 60;
                $morningstarts = 12;
                $eveningends = 12;
                $eveningends_minutes = count($periods_name) - 1;
            }
            $this_area_name = grr_sql_query1("SELECT area_name FROM ".TABLE_PREFIX."_area WHERE id=$area");
            $tables[$month_indice] .= "<table class='mois table-bordered'>\n";
            $tables[$month_indice] .= "<caption>";
            $tables[$month_indice] .= "<h4>".ucfirst($this_area_name)."</h4>";
            $tables[$month_indice] .= "</caption>";
            // Début affichage de la première ligne
            $tables[$month_indice] .= "<tr>";
            $tables[$month_indice] .= '<td class="cell_hours">';
            $tables[$month_indice] .= get_vocab('rooms')." </td>\n";
            $t2 = mktime(0, 0, 0, $month_num, 1, $year_num);
            for ($k = 0; $k < $days_in_month; $k++)
            {
                $cday = date("j", $t2);
                $cmonth = date("m", $t2);
                $cweek = date("w", $t2);
                $cyear = date("Y", $t2);
                $name_day = ucfirst(utf8_strftime("%a<br />%d", $t2)); // On inscrit le quantième du jour dans la deuxième ligne
                $temp = mktime(0,0,0,$cmonth,$cday,$cyear);
                $jour_cycle = grr_sql_query1("SELECT Jours FROM ".TABLE_PREFIX."_calendrier_jours_cycle WHERE DAY='$temp'");
                $t2 = mktime(0,0,0,$cmonth,$cday+1,$cyear);
                if ($display_day[$cweek] == 1)
                {
                    if (isHoliday($temp)) {$tables[$month_indice] .= '<td class="cell_hours ferie">';}
                    elseif (isSchoolHoliday($temp)) {$tables[$month_indice] .= '<td class="cell_hours vacance">';}
                    else {$tables[$month_indice] .= '<td class="cell_hours">';}
                    $tables[$month_indice] .= "<div><a title=\"".htmlspecialchars(get_vocab("see_all_the_rooms_for_the_day"))."\"   href=\"day.php?year=$year_num&amp;month=$month_num&amp;day=$cday&amp;area=$area\">$name_day</a>";
                    if (Settings::get("jours_cycles_actif") == "Oui" && intval($jour_cycle)>-1)
                    {
                        if (intval($jour_cycle) > 0)
                            $tables[$month_indice] .= "<br /><b><i>".ucfirst(substr(get_vocab("rep_type_6"),0,1)).$jour_cycle."</i></b>";
                        else
                        {
                            if (strlen($jour_cycle)>5)
                                $jour_cycle = substr($jour_cycle,0,3)."..";
                            $tables[$month_indice] .= "<br /><b><i>".$jour_cycle."</i></b>";
                        }
                    }
                    $tables[$month_indice] .= "</div></td>\n";
                }
            }
            $tables[$month_indice] .= "</tr>";   // Fin affichage de la première ligne
            // boucle sur les ressources accessibles
            foreach ($data as list($room_id,$room_name,$area_id))
            {
                if ($area == $area_id)
                {
                    //Used below: localized "all day" text but with non-breaking spaces:
                    $all_day = preg_replace("/ /", " ", get_vocab("all_day"));
                    //Get all meetings for this month in the room that we care about
                    $sql = "SELECT start_time, end_time, ".TABLE_PREFIX."_entry.id, name, beneficiaire, ".TABLE_PREFIX."_room.room_name,type, statut_entry, ".TABLE_PREFIX."_entry.description, ".TABLE_PREFIX."_entry.option_reservation, ".TABLE_PREFIX."_room.delais_option_reservation, ".TABLE_PREFIX."_entry.moderate, beneficiaire_ext, clef, ".TABLE_PREFIX."_entry.courrier, ".TABLE_PREFIX."_type_area.type_name, ".TABLE_PREFIX."_entry.overload_desc,".TABLE_PREFIX."_entry.room_id, ".TABLE_PREFIX."_entry.create_by, ".TABLE_PREFIX."_entry.nbparticipantmax 
                    FROM (".TABLE_PREFIX."_entry INNER JOIN ".TABLE_PREFIX."_room ON ".TABLE_PREFIX."_entry.room_id=".TABLE_PREFIX."_room.id ) 
                      INNER JOIN ".TABLE_PREFIX."_type_area ON ".TABLE_PREFIX."_entry.type=".TABLE_PREFIX."_type_area.type_letter
                    WHERE (start_time <= ".$end_month." AND end_time > ".$begin_month." AND ".TABLE_PREFIX."_entry.room_id=".$room_id.")
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
                        $row[15]: type_name
                        $row[16]: overload fields description
                        $row[17]: room_id
                        $row[18]: create_by
                        $row[19]: nbparticipantmax
                    */
					//Build an array of information about each day in the month.
                    //The information is stored as:
                    // $d[monthday]["id"][] = ID of each entry, for linking.
                    // $d[monthday]["data"][] = "start-stop" times of each entry.
                    $d = array();
                    $res = grr_sql_query($sql);
                    if (!$res)
                        echo grr_sql_error();
                    else
                    { // les données sont bien recueillies
                        foreach($res as $row)
                        {
							if ($row["type_name"] <> (Settings::get('exclude_type_in_views_all')))          // Nom du type à exclure  
                            {  //Fill in data for each day during the month that this meeting ($row) covers. 
                                $t = max((int)$row["start_time"], $begin_month);
                                $end_t = min((int)$row["end_time"], $end_month);
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
                                    $d[$day_num][$month_num][$year_num]["room"][]=$row["room_name"] ;
                                    $d[$day_num][$month_num][$year_num]["color"][] = $row["type"];
                                    $midnight_tonight = $midnight + 86400; // potentiellement problématique avec les jours de changement d'heure YN 
                                    //Describe the start and end time, accounting for "all day"
                                    //and for entries starting before/ending after today.
                                    //There are 9 cases, for start time < = or > midnight this morning,
                                    //and end time < = or > midnight tonight.
                                    //Use ~ (not -) to separate the start and stop times, because MSIE
                                    //will incorrectly line break after a -.
                                    if ($enable_periods == 'y')
                                    {
                                        $start_str = preg_replace("/ /", " ", period_time_string($row["start_time"]));
                                        $end_str   = preg_replace("/ /", " ", period_time_string($row["end_time"], -1));
                                        switch (cmp3($row['start_time'], $midnight) . cmp3($row['end_time'], $midnight_tonight))
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
                                            $horaires = $all_day;
                                            break;
                                            case "= > ":
                                            $horaires = $all_day . "==>";
                                            break;
                                            case "< < ":
                                            $horaires = "<==~" . $end_str;
                                            break;
                                            case "< = ":
                                            $horaires = "<==" . $all_day;
                                            break;
                                            case "< > ":
                                            $horaires = "<==" . $all_day . "==>";
                                            break;
                                        }
                                    }
                                    else
                                    {
                                        switch (cmp3($row['start_time'], $midnight) . cmp3($row['end_time'], $midnight_tonight))
                                        {
                                            case "> < ":
                                            case "= < ":
                                            $horaires = date(hour_min_format(), $row['start_time']) . "~" . date(hour_min_format(), $row['end_time']);
                                            break;
                                            case "> = ":
                                            $horaires = date(hour_min_format(), $row['start_time']) . "~24:00";
                                            break;
                                            case "> > ":
                                            $horaires = date(hour_min_format(), $row['start_time']) . "~==>";
                                            break;
                                            case "= = ":
                                            $horaires = $all_day;
                                            break;
                                            case "= > ":
                                            $horaires = $all_day . "==>";
                                            break;
                                            case "< < ":
                                            $horaires = "<==~" . date(hour_min_format(), $row['end_time']);
                                            break;
                                            case "< = ":
                                            $horaires = "<==" . $all_day;
                                            break;
                                            case "< > ":
                                            $horaires = "<==" . $all_day . "==>";
                                            break;
                                        }
                                    }
                                    $d[$day_num][$month_num][$year_num]["lien"][] = contenu_cellule($options, $overloadFieldList, 1, $row, $horaires);
                                    // Info-bulle
                                    $d[$day_num][$month_num][$year_num]["data"][] = contenu_popup($options_popup, 1, $row, $horaires);
                                    //Only if end time > midnight does the loop continue for the next day.
                                    if ($end_t <= $midnight_tonight)
                                        break;
                                    $t = $midnight = $midnight_tonight;
                                    $day_num = date("j", $t);
                                    $month_num = date("m", $t);
                                    $year_num  = date("Y", $t);
                                }
                            }           // ModifExclure Ajouté
						}// ici fin du traitement des données pour le domaine et le mois
                        // calcul du code html des données à afficher
                        $acces_fiche_reservation = verif_acces_fiche_reservation($user_name, $room_id);
                        $tables[$month_indice] .= "<tr>";
                        $tables[$month_indice] .= "<th>";
                        $tables[$month_indice] .= htmlspecialchars($room_name) ."</th>\n";
                        $t2 = mktime(0, 0, 0, $month_num, 1, $year_num);
                        for ($k = 0; $k < $days_in_month; $k++)
                        {
                            $cday = date("j", $t2);
                            $cweek = date("w", $t2);
                            $t2 = mktime(0,0,0,$month_num,$cday+1,$year_num);
                            if ($display_day[$cweek] == 1) // Début condition "on n'affiche pas tous les jours de la semaine"
                            {   
                                $tables[$month_indice] .= "<td> \n";
                                if (est_hors_reservation(mktime(0,0,0,$month_num,$cday,$year_num),$area))
                                {
                                    $tables[$month_indice] .= "<div class=\"empty_cell\">";
                                    $tables[$month_indice] .= "<img src=\"img_grr/stop.png\" alt=\"".get_vocab("reservation_impossible")."\"  title=\"".get_vocab("reservation_impossible")."\" width=\"16\" height=\"16\" class=\"".$class_image."\"  /></div>";
                                }
                                //Anything to display for this day?
                                elseif (isset($d[$cday][$cmonth][$cyear]["id"][0]))
                                {
                                    $n = count($d[$cday][$cmonth][$cyear]["id"]);
                                    //Show the start/stop times, 2 per line, linked to view_entry.
                                    //If there are 12 or fewer, show them, else show 11 and "...".
                                    for ($i = 0; $i < $n; $i++)
                                    {
                                        if ($i == 11 && $n > 12)
                                        {
                                            $tables[$month_indice] .= " ...\n";
                                            break;
                                        }
                                        for ($i = 0; $i < $n; $i++)
                                        {
                                            if ($d[$cday][$cmonth][$cyear]["room"][$i] == $room_name) // test peu fiable car c'est l'id qui est unique YN le 26/02/2018
                                            {
                                                $tables[$month_indice] .= "\n<table class='pleine table-bordered' ><tr>\n";
                                                $tables[$month_indice] .= "<td class='type".$d[$cday][$cmonth][$cyear]["color"][$i]."'>";
                                                if ($acces_fiche_reservation)
                                                {
                                                    if (Settings::get("display_level_view_entry") == 0)
                                                    {
                                                        $currentPage = 'year_all';
                                                        $id =   $d[$cday][$cmonth][$cyear]["id"][$i];
                                                        $tables[$month_indice] .= "<a title=\"".htmlspecialchars($d[$cday][$cmonth][$cyear]["data"][$i])."\" data-width=\"675\" onclick=\"request($id,$cday,$cmonth,$cyear,'all','$currentPage',readData);\" data-rel=\"popup_name\" class=\"poplight lienCellule\">" .$d[$cday][$cmonth][$cyear]["lien"][$i]."</a>";
                                                    }
                                                    else
                                                    {
                                                        $tables[$month_indice] .= "<a class=\"lienCellule\" title=\"".htmlspecialchars($d[$cday][$cmonth][$cyear]["data"][$i])."\" href=\"view_entry.php?id=" . $d[$cday][$cmonth][$cyear]["id"][$i]."&amp;page=year_all\">".$d[$cday][$cmonth][$cyear]["lien"][$i]. "</a>";
                                                    }
                                                }    
                                                else
                                                    $tables[$month_indice] .= $d[$cday][$cmonth][$cyear]["lien"][$i];
                                                $tables[$month_indice] .= "\n</td></tr></table>\n";
                                            }
                                        }
                                    }
                                }
                                $tables[$month_indice] .= "</td>\n";
                            }
                                // fin condition "on n'affiche pas tous les jours de la semaine"
                        }
                        $tables[$month_indice] .= "</tr>";
                    }
                } 
            grr_sql_free($res);
            }    // fin de boucle sur les ressources
        	$tables[$month_indice] .= "</table>\n";
        } // fin de boucle sur les domaines
        $month_indice = mktime(0, 0, 0, $month_num + 1, 1, $year_num);
    } // fin de boucle sur les mois
}

// pour le traitement des modules
include $racine."/include/hook.class.php";

// début du code html
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
echo "<section>";
// Si format imprimable ($_GET['pview'] = 1), on n'affiche pas cette partie
if ($_GET['pview'] != 1)
{
    echo "<div class='row'>";
        echo "\n<div class=\"col-lg-2 col-md-3 col-xs-12\">\n".PHP_EOL; // lien de retour
            echo '&nbsp; <a title="'.htmlspecialchars(get_vocab('back')).'" href="'.$back.'">'.htmlspecialchars(get_vocab('back')).'</a>';
        echo "</div>";
        echo "<form method=\"get\" action=\"year_all.php\">";
            echo "\n<div class=\"col-lg-4 col-md-6 col-xs-12\">\n".PHP_EOL; // choix des dates 
            echo "<table>\n";
            echo "<tr><td>".get_vocab("report_start").get_vocab("deux_points")."&nbsp;</td>";
            echo "<td>";
            echo genDateSelector("from_", "", $from_month, $from_year,"");
            echo "</td></tr>";
            echo "<tr><td>".get_vocab("report_end").get_vocab("deux_points")."&nbsp;</td><td>\n";
            echo genDateSelector("to_", "", $to_month, $to_year,"");
            echo "</td></tr>\n";
            echo "</table>\n";
            echo "</div>";
            echo "<br><p>";
            echo "<input type=\"hidden\" name=\"site\" value=\"$site\" />\n";
            echo "<input type=\"hidden\" name=\"area\" value=\"$area\" />\n";
            echo "<input type=\"submit\" name=\"valider\" value=\"".$vocab["goto"]."\" /></p>";//</td></tr>\n";

        echo "</form>";
    echo "</div>";
}
echo '<div class="titre_planning"><h4>'.ucfirst($nom_site)." - ".get_vocab("all_areas").'</h4></div>';
// affichage des données mensuelles
$month_indice =  $month_start;
while ($month_indice < $month_end)
{
    echo $tables[$month_indice];
    $month_num = date("m", $month_indice);
    $year_num  = date("Y", $month_indice);
    $month_indice = mktime(0, 0, 0, $month_num + 1, 1, $year_num);
} // fin de boucle sur les mois

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