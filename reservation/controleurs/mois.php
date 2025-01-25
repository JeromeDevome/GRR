<?php
/**
 * mois.php
 * Interface d'accueil avec affichage par mois
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2024-02-04 15:00$
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
$grr_script_name = "mois.php";

$trad = $vocab;

include "include/resume_session.php";
include "include/planning.php";

// en l'absence du paramètre $room, indispensable pour mois.php, on renvoie à mois_all.php
if (!isset($room)){
    $msg = get_vocab('choose_a_room');
    $lien = "?p=mois_all&area=".$area."&month=".$month."&year=".$year;
    echo "<script type='text/javascript'>
        alert('$msg');
        document.location.href='$lien';
    </script>";
    echo "<p><br/>";
        echo get_vocab('choose_room')."<a href='?p=mois_all'>".get_vocab("link")."</a>";
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
$d['nomDomaine'] = grr_sql_query1("SELECT area_name FROM ".TABLE_PREFIX."_area WHERE id=$area");
$sql = "SELECT * FROM ".TABLE_PREFIX."_room WHERE id=$room";
$res = grr_sql_query($sql);
if ($res){
    $this_room = grr_sql_row_keyed($res,0);
}
$d['nomRessource'] = (isset($this_room['room_name']))? $this_room['room_name']:"";
$this_room_max = (isset($this_room['capacity']))? $this_room['capacity']:0;
$d['descriptionRessource'] = (isset($this_room['description']))? $this_room['description']:'';
$d['statutRessource'] = (isset($this_room['statut_room']))? $this_room['statut_room']:1;
$d['reservationModere'] = (isset($this_room['moderate']))? $this_room['moderate']:0;
$this_delais_option_reservation = (isset($this_room['delais_option_reservation']))? $this_room['delais_option_reservation']:0;
$this_room_comment = (isset($this_room['comment_room']))? $this_room['comment_room']:'';
$this_room_show_comment = (isset($this_room['show_comment']))? $this_room['show_comment']:'n';
$who_can_book = (isset($this_room['who_can_book']))? $this_room['who_can_book']:1;
grr_sql_free($res);


$i = mktime(0, 0, 0, $month - 1, 1, $year); // Mois précédent
$d['yy'] = date("Y", $i);
$d['ym'] = date("n", $i);
$i = mktime(0, 0, 0,$month + 1, 1, $year); // Mois suivant
$d['ty'] = date("Y", $i); 
$d['tm'] = date("n", $i);

$user_name = getUserName();
$authGetUserLevel = authGetUserLevel($user_name,$room);
// si la ressource est restreinte, l'utilisateur peut-il réserver ?
$user_can_book = $who_can_book || ($authGetUserLevel > 2) || (authBooking($user_name,$room));

// calcul du contenu du planning
$all_day = preg_replace("/ /", " ", get_vocab("all_day2"));
$sql = "SELECT start_time, end_time, ".TABLE_PREFIX."_entry.id, name, beneficiaire, ".TABLE_PREFIX."_room.room_name,type, statut_entry, ".TABLE_PREFIX."_entry.description, ".TABLE_PREFIX."_entry.option_reservation, ".TABLE_PREFIX."_room.delais_option_reservation, ".TABLE_PREFIX."_entry.moderate, beneficiaire_ext, clef, ".TABLE_PREFIX."_entry.courrier, ".TABLE_PREFIX."_type_area.type_name, ".TABLE_PREFIX."_entry.overload_desc
FROM ".TABLE_PREFIX."_entry, ".TABLE_PREFIX."_room, ".TABLE_PREFIX."_area, ".TABLE_PREFIX."_type_area
where
".TABLE_PREFIX."_entry.room_id = '".$room."' and
".TABLE_PREFIX."_entry.room_id=".TABLE_PREFIX."_room.id and
".TABLE_PREFIX."_area.id = ".TABLE_PREFIX."_room.area_id and
".TABLE_PREFIX."_type_area.type_letter = ".TABLE_PREFIX."_entry.type AND
start_time <= $month_end AND
end_time > $month_start AND
supprimer = 0 
ORDER by start_time, end_time";
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
	for ($i = 0; ($row = grr_sql_row($res, $i)); $i++) // foreach ne peut être utilisé à cause de affichage_resa_planning_complet
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
			$da[$day_num]["id"][] = $row[2];
			$da[$day_num]["color"][] = $row[6];
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
					$horaires = $start_str . get_vocab("to"). "24:00";
					break;
					case "> > ":
					$horaires = $start_str . get_vocab("to") ."==>";
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
					$horaires = "<" . $all_day . ">";
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
					$horaires = "<" . $all_day . ">";
					break;
				}
			}
            $da[$day_num]["resa"][] = affichage_resa_planning_complet($overloadFieldList, 1, $row, $horaires);
            $da[$day_num]["infobulle"][] = affichage_resa_info_bulle($overloadFieldList, 1, $row, $horaires);

			//Seulement si l'heure de fin est après minuit, on continue le jour prochain.
			if ($row[1] <= $midnight_tonight)
				break;
			$day_num++;
			$t = $midnight = $midnight_tonight;
		}
	}
    grr_sql_free($res);
}


// code html de la page

if ((!isset($d['pview'])) || ($d['pview'] != 1))
{
    $positionMenu = Settings::get("menu_gauche");
    $d['positionMenu'] = ($positionMenu != 0)? $positionMenu : 1; // il faut bien que le menu puisse s'afficher, par défaut ce sera à gauche sauf choix autre par setting
}

if ($this_room_max  && $d['pview'] != 1)
	$d['maxCapacite'] = '('.$this_room_max.' '.($this_room_max > 1 ? get_vocab("number_max2") : get_vocab("number_max")).')'.PHP_EOL;

$d['moisActuel'] = ucfirst(utf8_strftime("%B ", $month_start));
$d['anneeActuel'] = ucfirst(utf8_strftime("%Y ", $month_start));

if (verif_display_fiche_ressource($user_name, $room) && $d['pview'] != 1)
	$d['ficheRessource'] = true;

if ($authGetUserLevel > 2 && $d['pview'] != 1)
    $d['configRessource'] = true;

$d['ressourceEmpruntee'] = affiche_ressource_empruntee_twig($room);

if ($this_room_show_comment == "y" && $d['pview'] != 1 && ($this_room_comment != "") && ($this_room_comment != -1))
	$d['commentaireRessource'] = $this_room_comment;

$nbJoursAffiche = 0;
$joursSemaine = array ();
for ($weekcol = 0; $weekcol < 7; $weekcol++)
{
    $num_week_day = ($weekcol + $weekstarts) % 7;
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
        $num_week_day = ($weekcol + $weekstarts) % 7;
        if ($display_day[$num_week_day] == 1)
            $cellulesMois[] = array('numJour' => 0);
    }
}
$acces_fiche_reservation = verif_acces_fiche_reservation($user_name, $room);
$userRoomMaxBooking = UserRoomMaxBooking($user_name, $room, 1);
$auth_visiteur = auth_visiteur($user_name, $room);
for ($cday = 1; $cday <= $days_in_month; $cday++)
{
    $class = "";
    $num_week_day = ($weekcol + $weekstarts) % 7;
    $t = mktime(0, 0, 0, $month, $cday,$year);
    $name_day = ucfirst(utf8_strftime("%d", $t));
    $jour_cycle = grr_sql_query1("SELECT Jours FROM ".TABLE_PREFIX."_calendrier_jours_cycle WHERE DAY='$t'");
	$autreResa = false;
    $plageLibre = false;
    $reservations = array();

    if ($display_day[$num_week_day] == 1)
    {
        $heure = "";

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

        if (est_hors_reservation(mktime(0, 0, 0, $month, $cday, $year), $area))
            $horsResa = true;
        else
        {
            $horsResa = false;

            if (isset($da[$cday]["id"][0]))
            {
                $n = count($da[$cday]["id"]);
                for ($i = 0; $i < $n; $i++)
                {
					// On a plus de 11 résa dans le jour, on n'affiche pas tout
                    if ($i == 11 && $n > 12)
                    {
						$autreResa = true;
                        break;
                    }

                    $reservations[] = array ('idresa' => $da[$cday]["id"][$i],'td' => tdcellT($da[$cday]["color"][$i]), 'titre' => $da[$cday]["infobulle"][$i], 'texte' => $da[$cday]["resa"][$i], 'lienFiche' => $acces_fiche_reservation);
                }
            }
            if (Settings::get('calcul_plus_mois') == 'n') {
                $plageLibre = true;
            } elseif (plages_libre_semaine_ressource($room, $month, $cday, $year)){
                $date_now = time();
                $heure = date("H",$date_now);
                $date_booking = mktime(23,59, 0, $month, $cday, $year);
                if ((($authGetUserLevel > 1) || ($auth_visiteur == 1))
                    && ($userRoomMaxBooking != 0)
                    && verif_booking_date($user_name, -1, $room, $date_booking, $date_now, $enable_periods)
                    && verif_delais_max_resa_room($user_name, $room, $date_booking)
                    && verif_delais_min_resa_room($user_name, $room, $date_booking, $enable_periods)
                    && (($d['statutRessource'] == "1") || (($d['statutRessource'] == "0") && ($authGetUserLevel > 2)))
                    && $user_can_book
                    && $d['pview'] != 1)
                {
                   $plageLibre = true;
                }
            }
        }
		// Une cellule par jour (Du 1er au 31)
        $cellulesMois[] = array('numJour' => $name_day, 'class' => $class, 'jourCycle' => intval($jour_cycle), 'horsResa' => $horsResa, 'plageLibre' => $plageLibre, "heure" => $heure, "reservations" => $reservations, 'autreResa' => $autreResa);
    }
}
if ($weekcol > 0)
{
    for (; $weekcol < 7; $weekcol++)
    {
        $num_week_day = ($weekcol + $weekstarts)%7;
        if ($display_day[$num_week_day] == 1)
            $cellulesMois[] = array('numJour' => 0);
    }
}

echo $twig->render('mois.twig', array('trad' => $trad, 'd' => $d, 'settings' => $AllSettings, 'joursSemaine' => $joursSemaine, 'cellulesMois' => $cellulesMois));
?>