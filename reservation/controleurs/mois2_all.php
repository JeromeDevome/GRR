<?php
/**
 * mois2_all.php
 * Interface d'accueil avec affichage par mois des réservations de toutes les ressources d'un domaine
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2024-03-30 17:15$
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
// cette page est partiellement internationalisée : à compléter

$grr_script_name = "mois2_all.php";

$trad = $vocab;

include "include/resume_session.php";
include "include/planning.php";

// Selection des ressources
$sql = "SELECT * FROM ".TABLE_PREFIX."_room WHERE area_id='".$area."' ORDER BY order_display, room_name";
$ressources = grr_sql_query($sql);
if (!$ressources)
	fatal_error(0, grr_sql_error());
// Contrôle s'il y a une ressource dans le domaine
if (grr_sql_count($ressources) == 0)
{
	$d['messageErreur'] = "<h1>".get_vocab("no_rooms_for_area")."</h1>";
    echo $twig->render('planningerreur.twig', array('trad' => $trad, 'd' => $d, 'settings' => $AllSettings));
	exit();
}
// calcul du contenu du planning2
$month_start = mktime(0, 0, 0, $month, 1, $year);
$weekday_start = (date("w", $month_start) - $weekstarts + 7) % 7;
$days_in_month = date("t", $month_start);
$month_end = mktime(23, 59, 59, $month, $days_in_month, $year);
if ($enable_periods=='y')
{
	$resolution = 60;
	$morningstarts = 12;
	$eveningends = 12;
	$eveningends_minutes = count($periods_name)-1;
}
$this_room_name = "";
$d['nomDomaine'] = grr_sql_query1("SELECT area_name FROM ".TABLE_PREFIX."_area WHERE id=$area");
$i = mktime(0,0,0,$month-1,1,$year);
$d['yy'] = date("Y",$i);
$d['ym'] = date("n",$i);
$i = mktime(0,0,0,$month+1,1,$year);
$d['ty'] = date("Y",$i);
$d['tm'] = date("n",$i);

$all_day = preg_replace("/ /", " ", get_vocab("all_day"));
//Get all meetings for this month in the area that we care about
$sql = "SELECT start_time, end_time, ".TABLE_PREFIX."_entry.id, name, beneficiaire, ".TABLE_PREFIX."_room.room_name,type, statut_entry, ".TABLE_PREFIX."_entry.description, ".TABLE_PREFIX."_entry.option_reservation, ".TABLE_PREFIX."_room.delais_option_reservation, ".TABLE_PREFIX."_entry.moderate, beneficiaire_ext, clef, ".TABLE_PREFIX."_entry.courrier, ".TABLE_PREFIX."_type_area.type_name, ".TABLE_PREFIX."_entry.overload_desc
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
*/
$res = grr_sql_query($sql);
if (!$res)
	echo grr_sql_error();
else
{
    $overloadFieldList = mrbsOverloadGetFieldslist($area);
	for ($i = 0; ($row = grr_sql_row_keyed($res, $i)); $i++)
	{
		if ($row["type_name"] <> (Settings::get('exclude_type_in_views_all')))   // Nom du type
		{
			if ($debug_flag)
				echo "<br />DEBUG: result $i, id $row[2], starts $row[0], ends $row[1]\n";
			$t = max((int)$row[0], $month_start);
			$end_t = min((int)$row[1], $month_end);
			$day_num = date("j", $t);
			if ($enable_periods == 'y')
				$midnight = mktime(12,0,0,$month,$day_num,$year);
			else
				$midnight = mktime(0, 0, 0, $month, $day_num, $year);
			while ($t < $end_t)
			{
				if ($debug_flag)
					echo "<br />DEBUG: Entry $row[2] day $day_num\n";
				$dr[$day_num]["id"][] = $row["id"];
				$dr[$day_num]["lien"][] = lien_compact($row);
				$dr[$day_num]["room"][] = $row["room_name"] ;
				$dr[$day_num]["color"][] = $row["type"];
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
				$dr[$day_num]["infobulle"][] = titre_compact($overloadFieldList, $row, $horaires);
                if ($row[1] <= $midnight_tonight)
					break;
				$day_num++;
				$t = $midnight = $midnight_tonight;
            }
		}
	}
}
grr_sql_free($res);

// Debut de la page
if ($debug_flag)
{
	echo '<p>DEBUG: Array of month day infobulle:<p><pre>'.PHP_EOL;
	for ($i = 1; $i <= $days_in_month; $i++)
	{
		if (isset($dr[$i]["id"]))
		{
			$n = count($dr[$i]["id"]);
			echo 'Day '.$i.' has '.$n.' entries:'.PHP_EOL;
			for ($j = 0; $j < $n; $j++)
				echo "  ID: " . $dr[$i]["id"][$j] .
			" Data: " . $dr[$i]["infobulle"][$j] . "\n";
		}
	}
	echo '</pre>'.PHP_EOL;
}


// montrer ou cacher le menu gauche
if ((!isset($d['pview'])) || ($d['pview'] != 1))
{
    $mode = Settings::get("menu_gauche");
    $d['positionMenu'] = ($mode != 0)? $mode : 1; // il faut bien que le menu puisse s'afficher, par défaut ce sera à gauche sauf choix autre par setting
}    

$d['moisActuel'] = ucfirst(utf8_strftime("%B ", $month_start));
$d['anneeActuel'] = ucfirst(utf8_strftime("%Y", $month_start));

// ruban des jours
$joursMois = array();
for ($k = 1; $k <= $days_in_month; $k++)
{
    $t2 = mktime(0, 0, 0, $month, $k, $year);
	$cday = date("j", $t2);
	$cweek = date("w", $t2);
	$name_day = ucfirst(utf8_strftime("%a %d", $t2));
	$temp = mktime(0, 0, 0, $month,$cday,$year);
	$jour_cycle = grr_sql_query1("SELECT Jours FROM ".TABLE_PREFIX."_calendrier_jours_cycle WHERE DAY='$temp'");
    $nomCycle = "";
    if ($display_day[$cweek] == 1)
	{
        if (isHoliday($temp))
            $class = "ferie cell_hours";
        else if (isSchoolHoliday($temp))
            $class = "cell_hours vacance";
        else
            $class = "cell_hours";

        if (Settings::get("jours_cycles_actif") == "Oui" && intval($jour_cycle) > -1)
        {
            if (intval($jour_cycle) > 0)
                $nomCycle = ucfirst(substr(get_vocab("rep_type_6"), 0, 1)).$jour_cycle;
            else
            {
                if (strlen($jour_cycle) > 5)
                    $jour_cycle = substr($jour_cycle, 0, 3)."..";
                $nomCycle = $jour_cycle;
            }
        }

        $joursMois[] = array('nom' => $name_day, 'class' => $class, 'nomCycle' => $nomCycle);
	}
}

$li = 0;
$ressourcesMois = array();
for ($ir = 0; ($row = grr_sql_row_keyed($ressources, $ir)); $ir++) // traitement d'une ressource sur le mois
{
	$verif_acces_ressource = verif_acces_ressource($user_name, $row["id"]);
	if ($verif_acces_ressource)
	{
		$acces_fiche_reservation = verif_acces_fiche_reservation($user_name, $row["id"]);
        $authGetUserLevel = authGetUserLevel($user_name, $row["id"]);
        // si la ressource est restreinte, l'utilisateur peut-il réserver ?
        $user_can_book = $row["who_can_book"] || ($authGetUserLevel > 2) || (authBooking($user_name,$row['id']));

		$li++;
        $joursRessource = array();
		for ($k = 1; $k <= $days_in_month; $k++)
		{
            $t2 = mktime(0, 0, 0,$month, $k, $year);
			$cday = date("j", $t2);
			$cweek = date("w", $t2);
            $autreResa = false;
            $reservationsJour = array();
			$estHorsReservation = false;
			$statutCellule = 0; //0 vide, 1 réservable, 2 déjà une reservation, 3 hors résa

			if ($display_day[$cweek] == 1)
			{
				$estHorsReservation = est_hors_reservation(mktime(0, 0, 0, $month, $cday, $year), $area);
                if (isset($dr[$cday]["id"][0])) // il y a une réservation au moins à afficher
                {
                    $n = count($dr[$cday]["id"]);
                    for ($i = 0; $i < $n; $i++)
                    {
                        if ($i == 11 && $n > 12)
                        {
                            $$autreResa = true;
                            break;
                        }
                        
                        for ($i = 0; $i < $n; $i++)
                        {
                            if ($dr[$cday]["room"][$i] == $row["room_name"])
                            {
                                $reservationsJour[] = array('idresa' => $dr[$cday]["id"][$i], 'class' => $dr[$cday]["color"][$i], 'texte' => $dr[$cday]["lien"][$i], 'bulle' => $dr[$cday]["infobulle"][$i], 'lienFiche' => $acces_fiche_reservation);
                            }
                        }
                    }
                }
                // la ressource est-elle accessible en réservation ? on affiche le lien vers edit_entry
                $date_booking = mktime(23,59,0,$month,$k,$year) ; // le jour courant à presque minuit
                $hour =  date("H",$date_now); // l'heure courante, par défaut
                if (!$estHorsReservation){
                    if ((($authGetUserLevel > 1) || (auth_visiteur($user_name, $row["id"]) == 1)) 
                        && (UserRoomMaxBooking($user_name, $row["id"], 1) != 0) 
                        && verif_booking_date($user_name, -1, $row["id"], $date_booking, $date_now, $enable_periods) 
                        && verif_delais_max_resa_room($user_name, $row["id"], $date_booking) 
                        && verif_delais_min_resa_room($user_name, $row["id"], $date_booking, $enable_periods) 
                        && (($row["statut_room"] == "1") || (($row["statut_room"] == "0") && (authGetUserLevel($user_name,$row["id"]) > 2) )) 
                        && $user_can_book
                        && $d['pview'] != 1){
							if (Settings::get('calcul_plus_semaine_all') == 'n') {
								$statutCellule = 1;
							} elseif(plages_libre_semaine_ressource($row["id"], $month, $cday, $year))
							{
								$statutCellule = 1;
							}
					}
				} else
				{
					$statutCellule = 3; // hors réservation
				}
			}
            $joursRessource[] = array('statut' => $statutCellule, 'jour' => $cday, 'reservations' => $reservationsJour, 'autreResa' => $autreResa);
		}
        $ressourcesMois[] = array('id' => $row['id'], 'nom' => $row['room_name'], 'joursRessource' => $joursRessource);

	}
}// fin  du traitement de la ressource
grr_sql_free($ressources);

echo $twig->render('mois2_all.twig', array('trad' => $trad, 'd' => $d, 'settings' => $AllSettings, 'joursMois' => $joursMois, 'ressourcesMois' => $ressourcesMois));
?>