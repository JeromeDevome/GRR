<?php
/**
 * jour.php
 * Permet l'affichage de la page planning en mode d'affichage "jour".
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2026-02-08 11:48$
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
$grr_script_name = "jour.php";

$trad = $vocab;

//include "include/resume_session.php";
include "include/planning.php";

$ind = 1;
$test = 0;
$i = 0;
while (($test == 0) && ($ind <= 7))
{
	$i = mktime(0, 0, 0, $month, $day - $ind, $year);
	$test = $display_day[date("w",$i)];
	$ind++;
}
$d['yy'] = date("Y", $i);
$d['ym'] = date("m", $i);
$d['yd'] = date("d", $i);
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
$d['ty'] = date("Y",$i);
$d['tm'] = date("m",$i);
$d['td'] = date("d",$i);
$am7 = mktime($morningstarts, 0, 0, $month, $day, $year);
$pm7 = mktime($eveningends, $eveningends_minutes, 0, $month, $day, $year);
$d['nomDomaine'] = grr_sql_query1("SELECT area_name FROM ".TABLE_PREFIX."_area WHERE id='".protect_data_sql($area)."'"); // nom du domaine
// les réservations associées à notre recherche, ce jour dans ce domaine
$sql = "SELECT start_time, end_time, ".TABLE_PREFIX."_entry.id, name, beneficiaire, ".TABLE_PREFIX."_room.room_name,type, statut_entry, ".TABLE_PREFIX."_entry.description, ".TABLE_PREFIX."_entry.option_reservation, ".TABLE_PREFIX."_room.delais_option_reservation, ".TABLE_PREFIX."_entry.moderate, beneficiaire_ext, clef, ".TABLE_PREFIX."_entry.courrier, ".TABLE_PREFIX."_type_area.type_name, ".TABLE_PREFIX."_entry.overload_desc,".TABLE_PREFIX."_entry.room_id, nbparticipantmax, ".TABLE_PREFIX."_room.confidentiel_resa
FROM ".TABLE_PREFIX."_entry, ".TABLE_PREFIX."_room, ".TABLE_PREFIX."_area, ".TABLE_PREFIX."_type_area
WHERE
".TABLE_PREFIX."_entry.room_id=".TABLE_PREFIX."_room.id AND ".TABLE_PREFIX."_area.id = ".TABLE_PREFIX."_room.area_id";
if (isset($room) && $room != 0) 
    $sql .= " AND ".TABLE_PREFIX."_room.id = '".$room."' ";
else 
    $sql .= " AND ".TABLE_PREFIX."_room.area_id = '".$area."' ";
$sql .= " AND ".TABLE_PREFIX."_type_area.type_letter = ".TABLE_PREFIX."_entry.type 
AND start_time < ".($pm7+$resolution)." AND end_time > $am7 AND supprimer = 0 
ORDER BY start_time";
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
	$row[19]: confidentiel_resa
*/
$res = grr_sql_query($sql);
if (!$res)
	echo grr_sql_error();
else
{
    $overloadFieldList = mrbsOverloadGetFieldslist($area);
    $cellules = array();
    $compteur = array();
    $today = array();
	for ($i = 0; ($row = grr_sql_row_keyed($res, $i)); $i++)
	{
		$start_t = max(round_t_down($row["start_time"], $resolution, $am7), $am7);
		$end_t = min(round_t_up($row["end_time"], $resolution, $am7) - $resolution, $pm7);
		$cellules[$row["id"]] = ($end_t - $start_t) / $resolution + 1; // à vérifier YN le 14/10/18
		$compteur[$row["id"]] = 0;
		for ($t = $start_t; $t <= $end_t; $t += $resolution) // à vérifier YN le 14/10/18
		{
			$today[$row["room_id"]][$t]["id"]				= $row["id"];
			$today[$row["room_id"]][$t]["color"]			= $row["type"];
			$today[$row["room_id"]][$t]["data"]			    = "";
			$today[$row["room_id"]][$t]["who"]			    = "";
			$today[$row["room_id"]][$t]["beneficiaire"]		= $row["beneficiaire"];
		}
        $horaires = "";
        if ($enable_periods != 'y') {
            $heure_fin = date('H:i',min($pm7,$row["end_time"]));
            if ($heure_fin == '00:00') {$heure_fin = '24:00';}
            $horaires = date('H:i', max($am7,$row["start_time"])).get_vocab("to").$heure_fin;
        }
		if ($row["start_time"] < $am7)
		{
            $today[$row["room_id"]][$am7]["data"] = affichage_resa_planning_complet($overloadFieldList, 1, $row, $horaires);
			$today[$row["room_id"]][$am7]["who"] = affichage_resa_info_bulle($overloadFieldList, 1, $row, $horaires);
		}
		else
		{
            $today[$row["room_id"]][$start_t]["data"] = affichage_resa_planning_complet($overloadFieldList, 1, $row, $horaires);
			$today[$row["room_id"]][$start_t]["who"] = affichage_resa_info_bulle($overloadFieldList, 1, $row, $horaires);

		}
	}
}
grr_sql_free($res);


// Détermination des ressources à afficher
if($room != 0) // Une seul ressrouce
	$sql = "SELECT room_name, capacity, id, description, statut_room, show_fic_room, delais_option_reservation, moderate, who_can_book, show_comment, comment_room, confidentiel_resa FROM ".TABLE_PREFIX."_room WHERE id = '".protect_data_sql($room)."' ";
else // Toute les ressources du domaine
	$sql = "SELECT room_name, capacity, id, description, statut_room, show_fic_room, delais_option_reservation, moderate, who_can_book, show_comment, comment_room, confidentiel_resa FROM ".TABLE_PREFIX."_room WHERE area_id='".protect_data_sql($area)."' ORDER BY order_display, room_name";
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

// code HTML
$title = "";
if ($settings->get("show_holidays") == "Oui")
{   
	$now = mktime(0,0,0,$month,$day,$year);
	if (isHoliday($now)){
		$d['classJour'] = 'ferie ';
	}
	elseif (isSchoolHoliday($now)){
		$d['classJour'] = 'vacance ';
	}
}

if ($settings->get("jours_cycles_actif") == "Oui" && intval($jour_cycle) >- 1)
{
	if (intval($jour_cycle) > 0)
		$d['jourCycle'] = ' - '.get_vocab("rep_type_6")." ".$jour_cycle;
	else
        $d['jourCycle'] = ' - '.$jour_cycle;
}
$d['jourActuel'] = ucfirst(utf8_strftime($dformat, $am7));

$colonnesRess = array ();

if(grr_sql_count($ressources) != 0)
	$room_column_width = (int)(90 / grr_sql_count($ressources));
else
	$room_column_width = 90;

$nbcol = 0;
$rooms = array();
$a = 0;
//Pour chaque ressource...
for ($i = 0; ($row = grr_sql_row_keyed($ressources, $i)); $i++)
{
	$id_room[$i] = $row["id"];
	$nbcol++;
	if (verif_acces_ressource($user_name, $id_room[$i]))
	{
		$room_name[$i] = $row["room_name"];
		$statut_room[$id_room[$i]] =  $row["statut_room"];
		$statut_moderate[$id_room[$i]] = $row["moderate"];
        $who_can_book[$id_room[$i]] = $row["who_can_book"];
		$room_comment[$id_room[$i]] = $row["comment_room"];
		$show_comment[$id_room[$i]] = $row["show_comment"];
		$acces_fiche_reservation = verif_acces_fiche_reservation($user_name, $id_room[$i]);
		$confidentiel_resa[$id_room[$i]] = $row["confidentiel_resa"];
		$ficheRessource = verif_display_fiche_ressource($user_name, $id_room[$i]);
    $acces_config = (authGetUserLevel($user_name,$id_room[$i]) > 2);

		$ressourceEmpruntee = affiche_ressource_empruntee_twig($id_room[$i]);

        if (htmlspecialchars($row["description"]) != '')
		{
			if (htmlspecialchars($row["description"]) != '')
				$saut = '<br />';
			else
				$saut = '';
		}
		$rooms[] = $row["id"];
		$delais_option_reservation[$row["id"]] = $row["delais_option_reservation"];

        $colonnesRess[] = array("a" => $a, "ress" => $row, "largeur" => $room_column_width, "ficheRessource" => $ficheRessource, "acces_config" => $acces_config, "ressourceEmpruntee" => $ressourceEmpruntee);
	}
}
if (count($rooms) == 0)
{
	echo '<br /><h1>'.get_vocab("droits_insuffisants_pour_voir_ressources").'</h1><br />'.PHP_EOL;
	die();
}

$tab_ligne = 3;
$iii = 0;
$lignesHoraires = array();

if ($enable_periods == 'y')
    $pm7++; // correctif pour domaine sur créneaux prédéfinis

// Pour l'ensemble des horaires ou des créneaux
for ($t = $am7; $t < $pm7; $t += $resolution)
{
	$cellulesJours = array();
    // Pour la colonne horaire
	if ($iii % 2 == 1)
		$classHoraire = "cell_hours";
	else
		$classHoraire = "cell_hours2";
	$iii++;

	if ($enable_periods == 'y')
	{
		$time_t = date("i", $t);
		$time_t_stripped = preg_replace( "/^0/", "", $time_t );
		$horairePeriode = $periods_name[$time_t_stripped];
	}
	else
	{
		$time_t_stripped = "";
		$horairePeriode = affiche_heure_creneau($t,$resolution);
	}

	$hour = date("H", $t);
	$minute = date("i", $t);

    // Pour les ressources
    foreach($rooms as $key=>$room)
	{
		if (verif_acces_ressource($user_name, $room))
		{
			$afficherCellule = 0;
			$statutCellule = 0; //0 vide, 1 réservable, 2 déjà une reservation, 3 hors résa
			$titre = "";
			$descr = "";
			$beneficiaire = "";
			$rowspan = 1;
            $authLevel = authGetUserLevel($user_name,$room);
            $user_can_book = $who_can_book[$room] || ($authLevel > 2) || (authBooking($user_name,$room));
			$ficheResa = $acces_fiche_reservation;

			if (isset($today[$room][$t]["id"]))
			{
				$id    = $today[$room][$t]["id"];
				$color = $today[$room][$t]["color"];
				$descr = $today[$room][$t]["data"];
				$beneficiaire = $today[$room][$t]["beneficiaire"];
			}
			else
				$id = 0;

			if ($id > 0 && (!est_hors_reservation(mktime(0, 0, 0, $month, $day, $year), $area)))
				$c = "type".$color;
			else if ($statut_room[$room] == "0")
				$c = "avertissement";
			else
				$c = "empty_cell";

			if ($id > 0 && (!est_hors_reservation(mktime(0, 0, 0, $month, $day, $year), $area)))
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
						$afficherCellule = 1;
					}
					$rowspan = $cellules[$id];
				}
				$compteur[$id] = 1;
			}
			else
				$afficherCellule = 1;
				

			if ( $id == 0 || (est_hors_reservation(mktime(0, 0, 0, $month, $day, $year), $area)))
			{
				$afficherCellule = 1;
				$date_booking = mktime($hour, $minute, 0, $month, $day, $year);
                if ($enable_periods == 'y')
                    $date_booking = mktime(23,59,0,$month,$day,$year);
				if (est_hors_reservation(mktime(0, 0, 0, $month, $day, $year), $area))
				{
					$statutCellule = 3;
				}
				else // plage libre
				{
					if ((($authLevel > 1) || (auth_visiteur($user_name, $room) == 1)) 
                        && (UserRoomMaxBooking($user_name, $room, 1) != 0) 
                        && verif_booking_date($user_name, -1, $room, $date_booking, $date_now, $enable_periods) 
                        && verif_delais_max_resa_room($user_name, $room, $date_booking) 
                        && verif_delais_min_resa_room($user_name, $room, $date_booking, $enable_periods) 
                        && (($statut_room[$room] == "1") || (($statut_room[$room] == "0") && ($authLevel > 2) )) 
                        && $user_can_book
                        && $d['pview'] != 1)
					{
						$statutCellule = 1;
					}

				}

			}
			else if ($descr != "")
			{
				$afficherCellule = 1;
				$statutCellule = 2;
                if (($statut_room[$room] == "1") || (($statut_room[$room] == "0") && ($authLevel > 2) ))
				{
					$titre = $today[$room][$t]["who"];
				}
			}
			if($afficherCellule == 1)
			{
				// On n'affiche la fiche résa que si elle n'est pas confidentielle ou si on est l'auteur de la résa ou un gestionnaire
				if($ficheResa)
				{
					if($confidentiel_resa[$room] == 1 && getUserName() != $beneficiaire && $authLevel < 3)
						$ficheResa = false;
				}

            	$cellulesJours[] = array('statut' => $statutCellule, 'class' => $c, 'rowspan' => $rowspan, 'ressource' => $room, 'idresa' => $id, 'titre' => $titre, 'descr' => $descr, 'ficheResa' => $ficheResa);
		
			}
		}
	}
    $lignesHoraires[] = array('classHoraire' => $classHoraire, 'horairePeriode' => $horairePeriode, 'cellulesJours' => $cellulesJours, 'periode' => $time_t_stripped, 'heure' => $hour, 'minute' => $minute);
	reset($rooms);
}

grr_sql_free($res);


echo $twig->render('jour.twig', array('trad' => $trad, 'd' => $d, 'settings' => $AllSettings, 'colonnesRess' => $colonnesRess, 'lignesHoraires' => $lignesHoraires));
?>