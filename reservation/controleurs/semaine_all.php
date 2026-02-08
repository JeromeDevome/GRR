<?php
/**
 * semaine_all.php
 * Permet l'affichage du planning des réservations d'une semaine pour toutes les ressources d'un domaine.
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2026-02-08 12:03$
 * @author    Laurent Delineau & JeromeB & Yan Naessens
 * @copyright Copyright 2003-2026 Team DEVOME - JeromeB
 * @link      http://www.gnu.org/licenses/licenses.html
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */

$grr_script_name = "semaine_all.php";

$trad = $vocab;

include "include/resume_session.php";
include "include/planning.php";

// Selection des ressources
$sql = "SELECT room_name, capacity, id, description, statut_room, show_fic_room, delais_option_reservation, moderate, who_can_book, confidentiel_resa FROM ".TABLE_PREFIX."_room WHERE area_id='".$area."' ORDER BY order_display, room_name";
$ressources = grr_sql_query($sql);

if (!$ressources)
	fatal_error(0, grr_sql_error());

// Contrôle si il y a une ressource dans le domaine
if (grr_sql_count($ressources) == 0)
{
	$d['messageErreur'] = "<h1>".get_vocab("no_rooms_for_area")."</h1>";
    echo $twig->render('planningerreur.twig', array('trad' => $trad, 'd' => $d, 'settings' => $AllSettings));
	exit();
}
grr_sql_free($ressources);
// calcul du contenu du planning
if ($enable_periods == 'y')
{
	$resolution = 60;
	$morningstarts = 12;
	$morningstarts_minutes = 0;
	$eveningends = 12;
	$eveningends_minutes = count($periods_name)-1;
}
$time = mktime(0, 0, 0, $month, $day, $year);
if (($weekday = (date("w", $time) - $weekstarts + 7) % 7) > 0)
    $time = mktime(0,0,0,$month,$day-$weekday,$year); // recule de $weekday jours, php corrigera en fonction du changement d'heure

$day_week   = date("d", $time);
$month_week = date("m", $time);
$year_week  = date("Y", $time);
$date_start = mktime($morningstarts, 0, 0, $month_week, $day_week, $year_week);
$days_in_month = date("t", $date_start);
$date_end = mktime($eveningends, $eveningends_minutes, 0, $month_week, $day_week + 6, $year_week);
$d['nomDomaine'] = grr_sql_query1("SELECT area_name FROM ".TABLE_PREFIX."_area WHERE id=$area");
switch ($dateformat)
{
	case "en":
	$dformat = "%A, %b %d";
	break;
	case "fr":
	$dformat = "%A %d %b";
	break;
}
$i = mktime(0, 0, 0, $month_week, $day_week - 7, $year_week);
$d['yy'] = date("Y", $i);
$d['ym'] = date("m", $i);
$d['yd'] = date("d", $i);
$i = mktime(0, 0, 0, $month_week, $day_week +7 , $year_week);
$d['ty'] = date("Y", $i);
$d['tm'] = date("m", $i);
$d['td'] = date("d", $i);

$all_day = preg_replace("/ /", " ", get_vocab("all_day2"));
$sql = "SELECT start_time, end_time, ".TABLE_PREFIX."_entry.id, name, beneficiaire, ".TABLE_PREFIX."_room.room_name,type, statut_entry, ".TABLE_PREFIX."_entry.description, ".TABLE_PREFIX."_entry.option_reservation, ".TABLE_PREFIX."_room.delais_option_reservation, ".TABLE_PREFIX."_entry.moderate, beneficiaire_ext, clef, ".TABLE_PREFIX."_entry.courrier, ".TABLE_PREFIX."_type_area.type_name, ".TABLE_PREFIX."_entry.overload_desc, ".TABLE_PREFIX."_entry.room_id, nbparticipantmax, ".TABLE_PREFIX."_room.confidentiel_resa
FROM ".TABLE_PREFIX."_entry, ".TABLE_PREFIX."_room, ".TABLE_PREFIX."_area, ".TABLE_PREFIX."_type_area
where
".TABLE_PREFIX."_entry.room_id=".TABLE_PREFIX."_room.id and
".TABLE_PREFIX."_area.id = ".TABLE_PREFIX."_room.area_id and
".TABLE_PREFIX."_area.id = '".$area."' and
".TABLE_PREFIX."_type_area.type_letter = ".TABLE_PREFIX."_entry.type AND
start_time <= $date_end AND
end_time > $date_start AND
supprimer = 0 
ORDER by start_time, end_time, ".TABLE_PREFIX."_entry.id";
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
    $row[17]: room id
	$row[18]: nbparticipantmax
	$row[19]: confidentiel_resa
*/
$res2 = grr_sql_query($sql);
if (!$res2)
	echo grr_sql_error();
else
{
    $overloadFieldList = mrbsOverloadGetFieldslist($area);
	for ($i = 0; ($row = grr_sql_row($res2, $i)); $i++)
	{
		if ($row[15] <> (Settings::get('exclude_type_in_views_all'))) // Nom du type à exclure  
		{
			$t = max((int)$row['0'], $date_start);
			$end_t = min((int)$row['1'], $date_end);
			$day_num = date("j", $t);
			$month_num = date("m", $t);
			$year_num = date("Y", $t);
			if ($enable_periods == 'y')
				$midnight = mktime(12, 0, 0, $month_num, $day_num, $year_num);
			else
				$midnight = mktime(0, 0, 0, $month_num, $day_num, $year_num);
			while ($t <= $end_t)
			{
				$da[$day_num]["id"][] = $row['2'];
				$da[$day_num]["id_room"][]=$row['17'] ;
				$da[$day_num]["color"][]=$row['6'];

				$midnight_tonight = $midnight + 86400;
				if (!isset($correct_heure_ete_hiver) || ($correct_heure_ete_hiver == 1))
				{
					if (heure_ete_hiver("hiver",$year_num, 0) == mktime(0, 0, 0, $month_num, $day_num, $year_num))
						$midnight_tonight += 3600;
					if (date("H",$midnight_tonight) == "01")
						$midnight_tonight -= 3600;
				}
				if ($enable_periods == 'y')
				{
					$start_str = preg_replace("/ /", " ", period_time_string($row['0']));
					$end_str   = preg_replace("/ /", " ", period_time_string($row['1'], -1));
					switch (cmp3($row['0'], $midnight) . cmp3($row['1'], $midnight_tonight))
					{
						case "> < ":
						case "= < ":
						if ($start_str == $end_str)
							$horaires = $start_str;
						else
							$horaires = $start_str . get_vocab("to") . $end_str;
						break;
						case "> = ":
						$horaires = $start_str . get_vocab("to")."24:00";
						break;
						case "> > ":
						$horaires = $start_str . get_vocab("to")."==>";
						break;
						case "= = ":
						$horaires = $all_day;
						break;
						case "= > ":
						$horaires = $all_day . "==>";
						break;
						case "< < ":
						$horaires = "<==".get_vocab("to") . $end_str;
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
					switch (cmp3($row[0], $midnight) . cmp3($row[1], $midnight_tonight))
					{
						case "> < ":
						case "= < ":
						$horaires = date(hour_min_format(), $row[0]) . get_vocab("to") . date(hour_min_format(), $row[1]);
						break;
						case "> = ":
						$horaires = date(hour_min_format(), $row[0]) . get_vocab("to")."24:00";
						break;
						case "> > ":
						$horaires = date(hour_min_format(), $row[0]) . get_vocab("to")."==>";
						break;
						case "= = ":
						$horaires = $all_day;
						break;
						case "= > ":
						$horaires = $all_day . "==>";
						break;
						case "< < ":
						$horaires = "<==".get_vocab("to") . date(hour_min_format(), $row[1]);
						break;
						case "< = ":
						$horaires = "<==" . $all_day;
						break;
						case "< > ":
						$horaires = "<==" . $all_day . "==>";
						break;
					}
				}
				$da[$day_num]["beneficiaire"][] = $row['4'];
				$da[$day_num]["resa"][] = affichage_resa_planning_complet($overloadFieldList, 1, $row, $horaires);
				$da[$day_num]["infobulle"][] = affichage_resa_info_bulle($overloadFieldList, 1, $row, $horaires);
				if ($row[1] <= $midnight_tonight)
					break;
				$t = $midnight = $midnight_tonight;
				$day_num = date("j", $t);
			}   // ModifExclure Ajouté
		}
	}
    grr_sql_free($res2);
}

// Debut de la page
$d['semaineDebut'] = utf8_strftime($dformat, $date_start);
$d['semaineFin'] = utf8_strftime($dformat, $date_end);

// montrer ou cacher le menu gauche
if ((!isset($d['pview'])) || ($d['pview'] != 1))
{
    $positionMenu = Settings::get("menu_gauche");
    $d['positionMenu'] = ($positionMenu != 0)? $positionMenu : 1; // il faut bien que le menu puisse s'afficher, par défaut ce sera à gauche sauf choix autre par setting
}

$t = $time;
$num_week_day = $weekstarts;

// Les X jours de la semaine à afficher
$joursSemaine = array ();
for ($weekcol = 0; $weekcol < 7; $weekcol++)
{
	$num_day = date('d', $t);
	$temp_month = date('m', $t);
	$temp_month2 = utf8_strftime("%b", $t);
	$temp_year = date('Y', $t);
	$tt = mktime(0, 0, 0, $temp_month, $num_day, $temp_year);
	$jour_cycle = grr_sql_query1("SELECT Jours FROM ".TABLE_PREFIX."_calendrier_jours_cycle WHERE day='$t'");
	$t += 86400;
	if (!isset($correct_heure_ete_hiver) || ($correct_heure_ete_hiver == 1))
	{
		if (heure_ete_hiver("hiver",$temp_year,0) == mktime(0, 0, 0, $temp_month, $num_day, $temp_year))
			$t += 3600;
		if (date("H", $t) == "01")
			$t -= 3600;
	}
	if ($display_day[$num_week_day] == 1)
	{
		$class = "";
		$title = "";
        $nomJour = day_name(($weekcol + $weekstarts) % 7) . ' '.$num_day.' '.$temp_month2;
        $nomCycle = "";
		if ($settings->get("show_holidays") == "Oui")
		{   
			if (isHoliday($tt)){
				$class = 'ferie ';
			}
			elseif (isSchoolHoliday($tt)){
				$class = 'vacance ';
			}
		}

        if (Settings::get("jours_cycles_actif") == "Oui" && intval($jour_cycle) >- 1)
		{
			if (intval($jour_cycle) > 0)
				$nomCycle = get_vocab("rep_type_6")." ".$jour_cycle;
			else
                $nomCycle = $jour_cycle;
		}
        $joursSemaine[] = array('numJour' => $num_day, 'nomJour' => $nomJour, 'nomCycle' => $nomCycle, 'class' => $class, 'annee' => $temp_year, 'mois' => $temp_month);
    }
	$num_week_day++;
	$num_week_day = $num_week_day % 7;

 
}

$li = 0;
foreach($ressources as $row)
{
	$verif_acces_ressource = verif_acces_ressource($user_name, $row['id']);
	if ($verif_acces_ressource)
	{
		$acces_fiche_reservation = verif_acces_fiche_reservation($user_name, $row['id']);
		$UserRoomMaxBooking = UserRoomMaxBooking($user_name, $row['id'], 1);
		$authGetUserLevel = authGetUserLevel($user_name, $row['id']);
		$auth_visiteur = auth_visiteur($user_name, $row['id']);
        $ficheDescription = false;
		$resa_confidentiel = $row['confidentiel_resa'];
        // si la ressource est restreinte, l'utilisateur peut-il réserver ?
        $user_can_book = $row['who_can_book'] || ($authGetUserLevel > 2) || (authBooking($user_name,$row['id']));

		if ($li % 2 == 1)
			$classRess = "cell_hours";
		else
            $classRess = "cell_hours2";

		if (verif_display_fiche_ressource($user_name, $row['id']) && $d['pview'] != 1)
            $ficheDescription = true;

		$configRess = (authGetUserLevel($user_name,$row['id']) > 2 && $d['pview'] != 1);

		$emprunte = affiche_ressource_empruntee_twig($row['id']);

		$li++;
		$t = $time;
		$t2 = $time;
		$num_week_day = $weekstarts;

        $joursRessource = array();

        // Les résa (ou non) dans les X jours
		for ($k = 0; $k <= 6; $k++)
		{
            $classJour= "";
            $plageLibre = false;
			$cday = date("j", $t2);
			$cmonth = date('m', $t2);
			$cyear = date('Y', $t2);
			$t2 += 86400;

			if (!isset($correct_heure_ete_hiver) || ($correct_heure_ete_hiver == 1))
			{
				$temp_day = date('d', $t2);
				$temp_month = date('m', $t2);
				$temp_year = date('Y', $t2);
				if (heure_ete_hiver("hiver", $temp_year,0) == mktime(0, 0, 0, $temp_month, $temp_day, $temp_year))
					$t2 += 3600;
				if (date("H", $t2) == "01")
					$t2 -= 3600;
			}
			if ($display_day[$num_week_day] == 1)
			{
				$no_td = TRUE;
				$estHorsReservation = est_hors_reservation(mktime(0, 0, 0, $cmonth, $cday, $cyear), $area);
                $reservationsJour = array();
				
				if ((isset($da[$cday]["id"][0])) && !$estHorsReservation)
				{
					$n = count($da[$cday]["id"]);
					for ($i = 0; $i < $n; $i++)
					{
						$ficheResa = $acces_fiche_reservation;
						if ($da[$cday]["id_room"][$i]==$row['id'])
						{
							if ($no_td)
								$no_td = FALSE;

							// On n'affiche la fiche résa que si elle n'est pas confidentielle ou si on est l'auteur de la résa ou un gestionnaire
							if($acces_fiche_reservation)
							{
								if($resa_confidentiel == 1 && getUserName() != $da[$cday]["beneficiaire"][$i] && $authGetUserLevel < 3)
									$ficheResa = false;
							}

                        	$reservationsJour[] = array('idresa' => $da[$cday]["id"][$i], 'class' => $da[$cday]["color"][$i], 'texte' => $da[$cday]["resa"][$i], 'bulle' => $da[$cday]["infobulle"][$i], 'lienFiche' => $ficheResa);
						}
					}

				}
				if ($no_td)
				{
					if ($row['statut_room'] == 1)
						$classJour = "empty_cell";
					else
                        $classJour = "avertissement";
				}
				$hour = date("H", $date_now);
				$date_booking = mktime(23,59, 0, $cmonth, $cday, $cyear);
				if (!$estHorsReservation){

					if ((($authGetUserLevel > 1) || ($auth_visiteur == 1)) && 
                    ($UserRoomMaxBooking != 0) && 
                    verif_booking_date($user_name, -1, $row['id'], $date_booking, $date_now, $enable_periods) && 
                    verif_delais_max_resa_room($user_name, $row['id'], $date_booking) && 
                    verif_delais_min_resa_room($user_name, $row['id'], $date_booking, $enable_periods) && 
                    (($row['statut_room'] == "1") || (($row['statut_room'] == "0") && (authGetUserLevel($user_name,$row['id']) > 2) )) && 
                    $user_can_book && 
                    $d['pview'] != 1)
					{
						if (Settings::get('calcul_plus_semaine_all') == 'n') {
							$plageLibre = true;
						} elseif(plages_libre_semaine_ressource($row['id'], $cmonth, $cday, $cyear))
						{
							$plageLibre = true;
						}
                	}
				}

                $joursRessource[] = array('class' => $classJour, 'horsResa' => $estHorsReservation, 'plageLibre' => $plageLibre, 'annee' => $cyear, 'mois' => $cmonth, 'jour' => $cday, 'heure' => $hour, 'reservations' => $reservationsJour);
			}
			$num_week_day++;
			$num_week_day = $num_week_day % 7;
		}

        $ressourcesSemaine[] = array('id' => $row['id'], 'nom' => $row['room_name'], 'class' => $classRess, 'annee' => $temp_year, 'mois' => $temp_month, 'capacite' => $row['capacity'], 'fiche' => $ficheDescription, 'acces_config' => $configRess, 'statut' => $row['statut_room'], 'joursRessource' => $joursRessource);
	}
}

unset($row);

echo $twig->render('semaine_all.twig', array('trad' => $trad, 'd' => $d, 'settings' => $AllSettings, 'joursSemaine' => $joursSemaine, 'ressourcesSemaine' => $ressourcesSemaine));
?>