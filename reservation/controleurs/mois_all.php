<?php
/**
 * mois_all.php
 * Interface d'accueil avec affichage par mois des réservation de toutes les ressources d'un domaine
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2024-02-03 11:27$
 * @author    Laurent Delineau & JeromeB & Yan Naessens
 * @copyright Copyright 2003-2024 Team DEVOME - JeromeB
 * @link      http://www.gnu.org/licenses/licenses.html
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */

$grr_script_name = "mois_all.php";

$trad = $vocab;

include "include/resume_session.php";
include "include/planning.php";

// Selection des ressources
$sql = "SELECT room_name, capacity, id, description, statut_room, show_fic_room, delais_option_reservation, moderate FROM ".TABLE_PREFIX."_room WHERE area_id='".$area."' ORDER BY order_display, room_name";
$ressources = grr_sql_query($sql);

if (!$ressources)
	fatal_error(0, grr_sql_error());

// Contrôle si il y a une ressource dans le domaine
if (grr_sql_count($ressources) == 0)
{
	$d['messageErreur'] = "<h1>".get_vocab("no_rooms_for_area")."</h1>";
	echo $twig->render('planningerreur.twig', array('trad' => $trad, 'd' => $d, 'settings' => $AllSettings));
    exit;
}
grr_sql_free($ressources);
// calcul du contenu du planning
$month_start = mktime(0, 0, 0, $month, 1, $year);
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

$d['nomDomaine'] = grr_sql_query1("SELECT area_name FROM ".TABLE_PREFIX."_area WHERE id=$area");
$i = mktime(0,0,0,$month - 1, 1, $year);
$d['yy'] = date("Y",$i);
$d['ym'] = date("n",$i);
$i = mktime(0,0,0,$month + 1, 1, $year);
$d['ty'] = date("Y",$i);
$d['tm'] = date("n",$i);

$all_day = preg_replace("/ /", " ", get_vocab("all_day2"));
//Get all meetings for this month in the area that we care about
$sql = "SELECT start_time, end_time, ".TABLE_PREFIX."_entry.id, name, beneficiaire, ".TABLE_PREFIX."_room.room_name,type, statut_entry, ".TABLE_PREFIX."_entry.description, ".TABLE_PREFIX."_entry.option_reservation, ".TABLE_PREFIX."_room.delais_option_reservation, ".TABLE_PREFIX."_entry.moderate, beneficiaire_ext, clef, ".TABLE_PREFIX."_entry.courrier, ".TABLE_PREFIX."_type_area.type_name, ".TABLE_PREFIX."_entry.overload_desc,".TABLE_PREFIX."_entry.room_id, nbparticipantmax
FROM (".TABLE_PREFIX."_entry INNER JOIN ".TABLE_PREFIX."_room ON ".TABLE_PREFIX."_entry.room_id=".TABLE_PREFIX."_room.id ) 
  INNER JOIN ".TABLE_PREFIX."_type_area ON ".TABLE_PREFIX."_entry.type=".TABLE_PREFIX."_type_area.type_letter
WHERE (start_time <= $month_end AND end_time > $month_start AND area_id='".$area."' AND supprimer = 0)
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
    $row[17]: room_id
    $row[18]: nbparticipantmax
*/

$res = grr_sql_query($sql);
if (!$res)
	echo grr_sql_error();
else  //Build an array of information about each day in the month.
{
    $overloadFieldList = mrbsOverloadGetFieldslist($area);
    $verif_acces_ressource = array();
    $acces_fiche_reservation = array();
	for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
	{
		if ($row['15'] <> (Settings::get('exclude_type_in_views_all')))
        {
            $verif_acces_ressource[$row[5]] = verif_acces_ressource($user_name, $row[5]);
            $acces_fiche_reservation[$row[5]] = verif_acces_fiche_reservation($user_name, $row[5]);
            $t = max((int)$row[0], $month_start);
            $end_t = min((int)$row[1], $month_end);
            $day_num = date("j", $t);
            if ($enable_periods == 'y')
                $midnight = mktime(12, 0, 0, $month, $day_num, $year);
            else
                $midnight = mktime(0, 0, 0, $month, $day_num, $year);
            while ($t < $end_t)
            {
                $da[$day_num]["id"][] = $row[2];
                $da[$day_num]["id_room"][] = $row[5];
                $da[$day_num]["room"][] = $row[5] ;
                $da[$day_num]["color"][] = $row[6];
                $midnight_tonight = $midnight + 86400;
            //Describe the start and end time, accounting for "all day"
            //and for entries starting before/ending after today.
            //There are 9 cases, for start time < = or > midnight this morning,
            //and end time < = or > midnight tonight.
            //Use ~ (not -) to separate the start and stop times, because MSIE
            //will incorrectly line break after a -.
                if ($enable_periods == 'y')
                {
                    $start_str = preg_replace("/ /", " ", period_time_string($row[0]));
                    $end_str   = preg_replace("/ /", " ", period_time_string($row[1], -1));
                // Debug
                //echo affiche_date($row[0])." ".affiche_date($midnight)." ".affiche_date($row[1])." ".affiche_date($midnight_tonight)."<br />";
                    switch (cmp3($row[0], $midnight) . cmp3($row[1], $midnight_tonight))
                    {
                        case "> < ":         //Starts after midnight, ends before midnight
                        case "= < ":         //Starts at midnight, ends before midnight
                        if ($start_str == $end_str)
                            $horaires = $start_str;
                        else
                            $horaires = $start_str . get_vocab("to") . $end_str;
                        break;
                        case "> = ":         //Starts after midnight, ends at midnight
                        $horaires = $start_str . get_vocab("to")."24:00";
                        break;
                        case "> > ":         //Starts after midnight, continues tomorrow
                        $horaires = $start_str . get_vocab("to")."&gt;";
                        break;
                        case "= = ":         //Starts at midnight, ends at midnight
                        $horaires = $all_day;
                        break;
                        case "= > ":         //Starts at midnight, continues tomorrow
                        $horaires = $all_day . "&gt;";
                        break;
                        case "< < ":         //Starts before today, ends before midnight
                        $horaires = "&lt;".get_vocab("to") . $end_str;
                        break;
                        case "< = ":         //Starts before today, ends at midnight
                        $horaires = "&lt;" . $all_day;
                        break;
                        case "< > ":         //Starts before today, continues tomorrow
                        $horaires = "&lt;" . $all_day . "&gt;";
                        break;
                    }
                }
                else
                {
                    switch (cmp3($row[0], $midnight) . cmp3($row[1], $midnight_tonight))
                    {
                        case "> < ":         //Starts after midnight, ends before midnight
                        case "= < ":         //Starts at midnight, ends before midnight
                        $horaires = date(hour_min_format(), $row[0]) . get_vocab("to") . date(hour_min_format(), $row[1]);
                        break;
                        case "> = ":         //Starts after midnight, ends at midnight
                        $horaires = date(hour_min_format(), $row[0]) . get_vocab("to")."24:00";
                        break;
                        case "> > ":         //Starts after midnight, continues tomorrow
                        $horaires = date(hour_min_format(), $row[0]) . get_vocab("to")."&gt;";
                        break;
                        case "= = ":         //Starts at midnight, ends at midnight
                        $horaires = $all_day;
                        break;
                        case "= > ":         //Starts at midnight, continues tomorrow
                        $horaires = $all_day . "&gt;";
                        break;
                        case "< < ":         //Starts before today, ends before midnight
                        $horaires = "&lt;".get_vocab("to") . date(hour_min_format(), $row[1]);
                        break;
                        case "< = ":         //Starts before today, ends at midnight
                        $horaires = "&lt;" . $all_day;
                        break;
                        case "< > ":         //Starts before today, continues tomorrow
                        $horaires = "&lt;" . $all_day . "&gt;";
                        break;
                    }
                }
                $da[$day_num]["resa"][] = affichage_resa_planning_complet($overloadFieldList, 2, $row, $horaires);
				$da[$day_num]["infobulle"][] = affichage_resa_info_bulle($overloadFieldList, 1, $row, $horaires);
                //Only if end time > midnight does the loop continue for the next day.
                if ($row[1] <= $midnight_tonight)
                    break;
                $day_num++;
                $t = $midnight = $midnight_tonight;
            }
        }
	}
    grr_sql_free($res);
}


// Debut de la page
$d['moisActuel'] = ucfirst(utf8_strftime("%B ", $month_start));
$d['anneeActuel'] = ucfirst(utf8_strftime("%Y ", $month_start));

$nbJoursAffiche = 0;
$joursSemaine = array ();
// Début affichage première ligne (intitulé des jours)
for ($weekcol = 0; $weekcol < 7; $weekcol++)
{
    $num_week_day = ($weekcol + $weekstarts) % 7;
    // on n'affiche pas tous les jours de la semaine
    if ($display_day[$num_week_day] == 1)
    {
        $joursSemaine[] = day_name(($weekcol + $weekstarts) % 7);
        $nbJoursAffiche++;
    }
}
$d['nbJoursAffiche'] = $nbJoursAffiche;

$cellulesMois = array();

$weekcol = 0;
if ($weekcol != $weekday_start)
{
    for ($weekcol = 0; $weekcol < $weekday_start; $weekcol++)
    {
        $num_week_day = ($weekcol + $weekstarts)%7;
        if ($display_day[$num_week_day] == 1)
            $cellulesMois[] = array('numJour' => 0);
    }
}
// Début Première boucle sur les jours du mois
for ($cday = 1; $cday <= $days_in_month; $cday++)
{
    $class = "";
    $num_week_day = ($weekcol + $weekstarts) % 7;
    $t = mktime(0, 0, 0, $month,$cday,$year);
    $name_day = ucfirst(utf8_strftime("%d", $t));
    $jour_cycle = grr_sql_query1("SELECT Jours FROM ".TABLE_PREFIX."_calendrier_jours_cycle WHERE DAY='$t'");
    $autreResa = false;

    if ($display_day[$num_week_day] == 1) // début condition "on n'affiche pas tous les jours de la semaine"
    {
        // On affiche les jours du mois dans le coin supérieur gauche de chaque cellule
        $heure = "";

        if ($settings->get("show_holidays") == "Oui")
            {   
                if (isHoliday($t)){
                    $class .= 'ferie ';
                }
                elseif (isSchoolHoliday($t)){
                    $class .= 'vacance ';
                }
            }

       /* if (est_hors_reservation(mktime(0,0,0,$month,$cday,$year),$area))
            $horsResa = true;
        else
        {*/
            $horsResa = false;
            $reservations = array();

            // Des réservation à afficher pour ce jour ?
            if (isset($da[$cday]["id"][0]))
            {
                $n = count($da[$cday]["id"]);
                //Show the reservation information, linked to view_entry.
                //If there are 12 or fewer, show them, else show 11 and "...".
                for ($i = 0; $i < $n; $i++)
                {
                    if ($verif_acces_ressource[$da[$cday]["id_room"][$i]]) // On n'affiche pas les réservations des ressources non visibles pour l'utilisateur.
                    {	
                        if ($i == 11 && $n > 12)
                        {
                            $autreResa = true;
                            break;
                        }

                        $reservations[] = array ('idresa' => $da[$cday]["id"][$i],'td' => tdcellT($da[$cday]["color"][$i]), 'titre' => $da[$cday]["infobulle"][$i], 'texte' => $da[$cday]["resa"][$i], 'lienFiche' => $acces_fiche_reservation);
                    }
                }
            /*
            envisager d'inclure ici un + pour ouvrir une fenêtre de réservation
            */
            }
            $plageLibre = false; // Aujourd'hui on ne le gère pas dans mois_all
       // }
        
    } // fin condition "on n'affiche pas tous les jours de la semaine"
    // Une cellule par jour (Du 1er au 31)
    $cellulesMois[] = array('numJour' => $name_day, 'class' => $class, 'jourCycle' => intval($jour_cycle), 'horsResa' => $horsResa, 'plageLibre' => $plageLibre, "heure" => $heure, "reservations" => $reservations, 'autreResa' => $autreResa);
}
// Fin Première boucle sur les jours du mois !
// On grise les cellules appartenant au mois suivant
if ($weekcol > 0)
{
    for (; $weekcol < 7; $weekcol++)
    {
        $num_week_day = ($weekcol + $weekstarts) % 7;
            // on n'affiche pas tous les jours de la semaine
        if ($display_day[$num_week_day] == 1)
            $cellulesMois[] = array('numJour' => 0);
    }
}

echo $twig->render('mois_all.twig', array('trad' => $trad, 'd' => $d, 'settings' => $AllSettings, 'joursSemaine' => $joursSemaine, 'cellulesMois' => $cellulesMois));
?>