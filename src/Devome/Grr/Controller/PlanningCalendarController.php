<?php

namespace Devome\Grr\Controller;

use Devome\Grr\Service\GrrApplicationContext;
use SecuAccess;
use SecuChaine;
use Settings;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Twig\Environment;
use Symfony\Contracts\Translation\TranslatorInterface;

final class PlanningCalendarController
{
    public function __construct(
        private readonly Environment $twig,
        private readonly GrrApplicationContext $applicationContext,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function jour(Request $request): Response
    {
        if ($request->query->get('p') !== 'jour') {
            throw new NotFoundHttpException();
        }

        $bootstrap = $this->applicationContext->boot();
        $context = $bootstrap['context'];
        global $gSessionName, $gSameSite, $room, $area, $id_site, $modulesActifs, $vocab, $d;
        global $resolution, $morningstarts, $eveningends, $eveningends_minutes;
        global $weekstarts, $twentyfourhour_format, $enable_periods, $periods_name;
        global $display_day, $nb_display_day, $description_breve, $description_complete, $locale, $user_name;

        extract($context, EXTR_SKIP);
        $user_name = $context['user_name'] ?? getUserName();
        $page = 'jour';
        $grr_script_name = $page.'.php';
        $twig = $this->twig;
        $projectDir = dirname(__DIR__, 4);
        $workingDirectory = getcwd();
        if ($workingDirectory === false || !chdir($projectDir)) {
            throw new \RuntimeException('Unable to prepare the GRR planning page.');
        }

        $bufferLevel = ob_get_level();
        ob_start();
        try {
            include $projectDir.'/include/resume_session.php';
            include $projectDir.'/include/planning.php';

/**
 * jour.php
 * Permet l'affichage de la page planning en mode d'affichage "jour".
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2026-04-03 11:25$
 * @author    Laurent Delineau & JeromeB & Yan Naessens
 * @copyright Since 2003 Team DEVOME - JeromeB
 * @link      http://www.gnu.org/licenses/licenses.html
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */
// accès à l'édition des ressources
$acces_config_level = (Settings::get('acces_config'))? Settings::get('acces_config') : 3;

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
$d['nomDomaine'] = grr_sql_query1("SELECT area_name FROM ".TABLE_PREFIX."_area WHERE id='".SecuChaine::ProtectDataSql($area)."'"); // nom du domaine
// les réservations associées à notre recherche, ce jour dans ce domaine
$sql = "SELECT start_time, end_time, ".TABLE_PREFIX."_entry.id, name, beneficiaire, ".TABLE_PREFIX."_room.room_name, type, statut_entry, ".TABLE_PREFIX."_entry.description, ".TABLE_PREFIX."_entry.option_reservation, ".TABLE_PREFIX."_room.delais_option_reservation, ".TABLE_PREFIX."_entry.moderate, beneficiaire_ext, clef, ".TABLE_PREFIX."_entry.courrier, ".TABLE_PREFIX."_type_area.type_name, ".TABLE_PREFIX."_entry.overload_desc, ".TABLE_PREFIX."_entry.room_id, ".TABLE_PREFIX."_entry.nbparticipantmax,
COALESCE(participants_count.nbparticipants, 0) AS nbparticipants,
".TABLE_PREFIX."_room.confidentiel_resa
FROM ".TABLE_PREFIX."_entry
INNER JOIN ".TABLE_PREFIX."_room ON ".TABLE_PREFIX."_entry.room_id = ".TABLE_PREFIX."_room.id
INNER JOIN ".TABLE_PREFIX."_area ON ".TABLE_PREFIX."_area.id = ".TABLE_PREFIX."_room.area_id
INNER JOIN ".TABLE_PREFIX."_type_area ON ".TABLE_PREFIX."_type_area.type_letter = ".TABLE_PREFIX."_entry.type
LEFT JOIN (
    SELECT idresa, COUNT(*) AS nbparticipants
    FROM ".TABLE_PREFIX."_participants
    GROUP BY idresa
) AS participants_count ON participants_count.idresa = ".TABLE_PREFIX."_entry.id
WHERE 1=1";
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
	$row[19]: nbparticipants
	$row[20]: confidentiel_resa
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
			$today[$row["room_id"]][$t]["nbparticipants"]	= (int)$row["nbparticipants"];
			$today[$row["room_id"]][$t]["nbparticipantmax"] = (int)$row["nbparticipantmax"];
		}
        $horaires = "";
        if ($enable_periods != 'y') {
            $heure_fin = date('H:i',min($pm7,$row["end_time"]));
            if ($heure_fin == '00:00') {$heure_fin = '24:00';}
            $horaires = date('H:i', max($am7,$row["start_time"])).$this->translator->trans("to").$heure_fin;
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
{
	$sql = "SELECT room_name, capacity, id, description, statut_room, show_fic_room, delais_option_reservation, moderate, who_can_book, show_comment, comment_room, confidentiel_resa FROM ".TABLE_PREFIX."_room WHERE id = '".SecuChaine::ProtectDataSql($room)."' ";
}
else // Toute les ressources du domaine
{
	$sql = "SELECT room_name, capacity, id, description, statut_room, show_fic_room, delais_option_reservation, moderate, who_can_book, show_comment, comment_room, confidentiel_resa FROM ".TABLE_PREFIX."_room WHERE area_id='".SecuChaine::ProtectDataSql($area)."' ORDER BY order_display, room_name";
}
$ressRetour = $room;
$ressources = grr_sql_query($sql);
if (!$ressources)
	throw new \RuntimeException(grr_sql_error());
// Contrôle si il y a une ressource dans le domaine
if (grr_sql_count($ressources) == 0)
{
	$d['messageErreur'] = "<h1>".$this->translator->trans("no_rooms_for_area")."</h1>";
    echo $twig->render('planningerreur.twig', array('d' => $d, 'settings' => $AllSettings));
	return $this->responseFromBuffer($bootstrap);
}

$title = "";

$now = mktime(0,0,0,$month,$day,$year);
if (($settings->get("show_feries") == 1) && isHoliday($now)){
	$d['classJour'] = 'ferie ';
}
elseif (($settings->get("show_holidays") == 1) && isSchoolHoliday($now)){
	$d['classJour'] = 'vacance ';
}

if ($settings->get("jours_cycles_actif") == 1 && intval($jour_cycle) >- 1)
{
	if (intval($jour_cycle) > 0)
		$d['jourCycle'] = ' - '.$this->translator->trans("rep_type_6")." ".$jour_cycle;
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
	if (SecuAccess::UserResource($user_name, $id_room[$i]))
	{
		$room_name[$i] = $row["room_name"];
		$statut_room[$id_room[$i]] =  $row["statut_room"];
		$statut_moderate[$id_room[$i]] = $row["moderate"];
        $who_can_book[$id_room[$i]] = $row["who_can_book"];
		$room_comment[$id_room[$i]] = $row["comment_room"];
		$show_comment[$id_room[$i]] = $row["show_comment"];
		$acces_fiche_reservation = SecuAccess::UserSheetReservation($user_name, $id_room[$i]);
		$confidentiel_resa[$id_room[$i]] = $row["confidentiel_resa"];
		$ficheRessource = SecuAccess::UserSheetResource($user_name, $id_room[$i]);
   		$acces_config = (SecuAccess::UserLevel($user_name,$id_room[$i]) >= $acces_config_level);

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
	echo '<br /><h1>'.$this->translator->trans("droits_insuffisants_pour_voir_ressources").'</h1><br />'.PHP_EOL;
	return $this->responseFromBuffer($bootstrap);
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
		if (SecuAccess::UserResource($user_name, $room))
		{
			$afficherCellule = 0;
			$statutCellule = 0; //0 vide, 1 réservable, 2 déjà une reservation, 3 hors résa
			$titre = "";
			$descr = "";
			$beneficiaire = "";
			$nbparticipants = 0;
			$nbparticipantmax = 0;
			$rowspan = 1;
            $authLevel = SecuAccess::UserLevel($user_name,$room);
            $user_can_book = $who_can_book[$room] || ($authLevel > 2) || (SecuAccess::UserBookingResourceRestrict($user_name,$room));
			$ficheResa = $acces_fiche_reservation;

			if (isset($today[$room][$t]["id"]))
			{
				$id    = $today[$room][$t]["id"];
				$color = $today[$room][$t]["color"];
				$descr = $today[$room][$t]["data"];
				$beneficiaire = $today[$room][$t]["beneficiaire"];
				$nbparticipants = isset($today[$room][$t]["nbparticipants"]) ? (int)$today[$room][$t]["nbparticipants"] : 0;
				$nbparticipantmax = isset($today[$room][$t]["nbparticipantmax"]) ? (int)$today[$room][$t]["nbparticipantmax"] : 0;
			}
			else
				$id = 0;

			if ($id > 0 && (!est_hors_reservation(mktime(0, 0, 0, $month, $day, $year), $area)))
			{
				$c = "type".$color;
				if ($nbparticipantmax > 0 && $nbparticipants >= $nbparticipantmax)
					$c = " quota-atteint";
			}
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
					if ((($authLevel > 1) || (SecuAccess::VisitorBookingResource($user_name, $room) == 1)) 
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

            	$cellulesJours[] = array('statut' => $statutCellule, 'class' => $c, 'rowspan' => $rowspan, 'ressource' => $room, 'idresa' => $id, 'titre' => $titre, 'descr' => $descr, 'ficheResa' => $ficheResa, 'ressRetour' => $ressRetour);
		
			}
		}
	}
    $lignesHoraires[] = array('classHoraire' => $classHoraire, 'horairePeriode' => $horairePeriode, 'cellulesJours' => $cellulesJours, 'periode' => $time_t_stripped, 'heure' => $hour, 'minute' => $minute);
	reset($rooms);
}

grr_sql_free($res);


echo $twig->render('jour.twig', array('d' => $d, 'settings' => $AllSettings, 'colonnesRess' => $colonnesRess, 'lignesHoraires' => $lignesHoraires));

            $content = ob_get_clean();
            if ($content === false) {
                throw new \RuntimeException('Unable to capture the GRR planning page.');
            }

            return new Response($content, $bootstrap['status'], $bootstrap['headers']);
        } finally {
            while (ob_get_level() > $bufferLevel) {
                ob_end_clean();
            }

            if (!chdir($workingDirectory)) {
                throw new \RuntimeException(sprintf('Unable to restore the working directory to "%s".', $workingDirectory));
            }
        }
    }

    public function mois(Request $request): Response
    {
        if ($request->query->get('p') !== 'mois') {
            throw new NotFoundHttpException();
        }

        $bootstrap = $this->applicationContext->boot();
        $context = $bootstrap['context'];
        global $gSessionName, $gSameSite, $room, $area, $id_site, $modulesActifs, $vocab, $d;
        global $resolution, $morningstarts, $eveningends, $eveningends_minutes;
        global $weekstarts, $twentyfourhour_format, $enable_periods, $periods_name;
        global $display_day, $nb_display_day, $description_breve, $description_complete, $locale, $user_name;

        extract($context, EXTR_SKIP);
        $user_name = $context['user_name'] ?? getUserName();
        $page = 'mois';
        $grr_script_name = $page.'.php';
        $twig = $this->twig;
        $projectDir = dirname(__DIR__, 4);
        $workingDirectory = getcwd();
        if ($workingDirectory === false || !chdir($projectDir)) {
            throw new \RuntimeException('Unable to prepare the GRR planning page.');
        }

        $bufferLevel = ob_get_level();
        ob_start();
        try {
            include $projectDir.'/include/resume_session.php';
            include $projectDir.'/include/planning.php';

/**
 * mois.php
 * Interface d'accueil avec affichage par mois
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2026-04-10 10:00$
 * @author    Laurent Delineau & JeromeB & Yan Naessens
 * @copyright Since 2003 Team DEVOME - JeromeB
 * @link      http://www.gnu.org/licenses/licenses.html
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */
// en l'absence du paramètre $room, indispensable pour mois.php, on renvoie à mois_all.php
if ((!isset($room))||($room == 0)){
    $msg = $this->translator->trans('choose_a_room');
    $lien = "mois_all?area=".$area."&month=".$month."&year=".$year;
    echo "<script type='text/javascript'>
        alert('$msg');
        document.location.href='$lien';
    </script>";
    echo "<p><br/>";
        echo $this->translator->trans('choose_a_room')."<a href='$lien'>".$this->translator->trans("link")."</a>";
    echo "</p>";
    return $this->responseFromBuffer($bootstrap);
}

// accès à l'édition des ressources
$acces_config_level = (Settings::get('acces_config'))? Settings::get('acces_config') : 3;
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
$resa_confidentiel = (isset($this_room['confidentiel_resa']))? $this_room['confidentiel_resa']:0;
grr_sql_free($res);


$i = mktime(0, 0, 0, $month - 1, 1, $year); // Mois précédent
$d['yy'] = date("Y", $i);
$d['ym'] = date("n", $i);
$i = mktime(0, 0, 0,$month + 1, 1, $year); // Mois suivant
$d['ty'] = date("Y", $i); 
$d['tm'] = date("n", $i);

$user_name = getUserName();
$UserLevel = SecuAccess::UserLevel($user_name,$room);
// si la ressource est restreinte, l'utilisateur peut-il réserver ?
$user_can_book = $who_can_book || ($UserLevel > 2) || (SecuAccess::UserBookingResourceRestrict($user_name,$room));

// calcul du contenu du planning
$all_day = preg_replace("/ /", " ", $this->translator->trans("all_day2"));
//Get all meetings for this month in the room that we care about
$sql = "SELECT start_time, end_time, ".TABLE_PREFIX."_entry.id, name, beneficiaire, ".TABLE_PREFIX."_room.room_name, type, statut_entry, ".TABLE_PREFIX."_entry.description, ".TABLE_PREFIX."_entry.option_reservation, ".TABLE_PREFIX."_room.delais_option_reservation, ".TABLE_PREFIX."_entry.moderate, beneficiaire_ext, clef, ".TABLE_PREFIX."_entry.courrier, ".TABLE_PREFIX."_type_area.type_name, ".TABLE_PREFIX."_entry.overload_desc, ".TABLE_PREFIX."_entry.room_id, ".TABLE_PREFIX."_entry.nbparticipantmax,
COALESCE(participants_count.nbparticipants, 0) AS nbparticipants,
".TABLE_PREFIX."_room.confidentiel_resa
FROM ((".TABLE_PREFIX."_entry INNER JOIN ".TABLE_PREFIX."_room ON ".TABLE_PREFIX."_entry.room_id = ".TABLE_PREFIX."_room.id)
  INNER JOIN ".TABLE_PREFIX."_type_area ON ".TABLE_PREFIX."_entry.type = ".TABLE_PREFIX."_type_area.type_letter)
LEFT JOIN (
    SELECT idresa, COUNT(*) AS nbparticipants
    FROM ".TABLE_PREFIX."_participants
    GROUP BY idresa
) AS participants_count ON participants_count.idresa = ".TABLE_PREFIX."_entry.id
WHERE (start_time <= $month_end AND end_time > $month_start AND room_id='".$room."' AND supprimer = 0)
ORDER by start_time, end_time ";
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
    $row[19]: nbparticipants
    $row[20]: confidentiel_resa
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
      $da[$day_num]["nbparticipantmax"][] = (int)$row[18];
      $da[$day_num]["nbparticipants"][] = (int)$row[19];
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
						$horaires = $start_str . $this->translator->trans("to") . $end_str;
					break;
					case "> = ":
					$horaires = $start_str . $this->translator->trans("to"). "24:00";
					break;
					case "> > ":
					$horaires = $start_str . $this->translator->trans("to") ."==>";
					break;
					case "= = ":
					$horaires = $all_day;
					break;
					case "= > ":
					$horaires = $all_day . "==>";
					break;
					case "< < ":
					$horaires = "<==".$this->translator->trans("to") . $end_str;
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
					$horaires = date(hour_min_format(), $row[0]) . $this->translator->trans("to") . date(hour_min_format(), $row[1]);
					break;
					case "> = ":
					$horaires = date(hour_min_format(), $row[0]) . $this->translator->trans("to")."24:00";
					break;
					case "> > ":
					$horaires = date(hour_min_format(), $row[0]) . $this->translator->trans("to")."==>";
					break;
					case "= = ":
					$horaires = $all_day;
					break;
					case "= > ":
					$horaires = $all_day . "==>";
					break;
					case "< < ":
					$horaires = "<==".$this->translator->trans("to") . date(hour_min_format(), $row[1]);
					break;
					case "< = ":
					$horaires = "<==" . $all_day;
					break;
					case "< > ":
					$horaires = "<" . $all_day . ">";
					break;
				}
			}
            $da[$day_num]["beneficiaire"][] = $row[4];
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


if ((!isset($d['pview'])) || ($d['pview'] != 1))
{
    $positionMenu = Settings::get("menu_gauche");
    $d['positionMenu'] = ($positionMenu != 0)? $positionMenu : 1; // il faut bien que le menu puisse s'afficher, par défaut ce sera à gauche sauf choix autre par setting
}

if ($this_room_max  && $d['pview'] != 1)
	$d['maxCapacite'] = '('.$this_room_max.' '.($this_room_max > 1 ? $this->translator->trans("number_max2") : $this->translator->trans("number_max")).')'.PHP_EOL;

$d['moisActuel'] = ucfirst(utf8_strftime("%B ", $month_start));
$d['anneeActuel'] = ucfirst(utf8_strftime("%Y ", $month_start));

if (SecuAccess::UserSheetResource($user_name, $room) && $d['pview'] != 1)
	$d['ficheRessource'] = true;

$d['acces_config'] = ($UserLevel >= $acces_config_level) && ($d['pview'] != 1);

$d['ressourceEmpruntee'] = affiche_ressource_empruntee_twig($room);

if ($this_room_show_comment == "y" && $d['pview'] != 1 && ($this_room_comment != "") && ($this_room_comment != -1))
	$d['commentaireRessource'] = $this_room_comment;

$nbJoursAffiche = 0;
$joursSemaine = array ();
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
// les X jours du mois précédent avant le premier jour du mois visualisé (grisés)
if ($weekcol != $weekday_start)
{
    for ($weekcol = 0; $weekcol < $weekday_start; $weekcol++)
    {
        $num_week_day = ($weekcol + $weekstarts) % 7;
        if ($display_day[$num_week_day] == 1)
            $cellulesMois[] = array('numJour' => 0);
    }
}
$acces_fiche_reservation = SecuAccess::UserSheetReservation($user_name, $room);
$userRoomMaxBooking = UserRoomMaxBooking($user_name, $room, 1);
$accessVisitorBookingResource = SecuAccess::VisitorBookingResource($user_name, $room);
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
         $now = $t;

        if ($settings->get("show_feries") == 1 && isHoliday($t)){
            $class .= 'ferie ';
        }

        if ($settings->get("show_holidays") == 1 && isSchoolHoliday($t)){
            $class .= 'vacance ';
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
                    $ficheResa = $acces_fiche_reservation;
					// On a plus de 11 résa dans le jour, on n'affiche pas tout
                    if ($i == Settings::get("max_resa_affiche") && $n >= Settings::get("max_resa_affiche"))
                    {
						$autreResa = true;
                        break;
                    }

                    // On n'affiche la fiche résa que si elle n'est pas confidentielle ou si on est l'auteur de la résa ou un gestionnaire
                    if($acces_fiche_reservation)
                    {
                        if($resa_confidentiel == 1 && getUserName() != $da[$cday]["beneficiaire"][$i] && $UserLevel < 3)
                            $ficheResa = false;
                    }
                    $classeReservation = $da[$cday]["color"][$i];
                    $nbParticipants = isset($da[$cday]["nbparticipants"][$i]) ? (int)$da[$cday]["nbparticipants"][$i] : 0;
                    $nbParticipantsMax = isset($da[$cday]["nbparticipantmax"][$i]) ? (int)$da[$cday]["nbparticipantmax"][$i] : 0;

                    if ($nbParticipantsMax > 0 && $nbParticipants >= $nbParticipantsMax)
                        $classeReservation .= " quota-atteint";

                    $reservations[] = array ('idresa' => $da[$cday]["id"][$i],'td' => tdcellT($classeReservation), 'titre' => $da[$cday]["infobulle"][$i], 'texte' => $da[$cday]["resa"][$i], 'lienFiche' => $ficheResa);
                }
            }
            if (Settings::get('calcul_plus_mois') == 0) {
                $plageLibre = true;
            } elseif (plages_libre_semaine_ressource($room, $month, $cday, $year)){
                $date_now = time();
                $heure = date("H",$date_now);
                $date_booking = mktime(23,59, 0, $month, $cday, $year);
                if ((($UserLevel > 1) || ($accessVisitorBookingResource == 1))
                    && ($userRoomMaxBooking != 0)
                    && verif_booking_date($user_name, -1, $room, $date_booking, $date_now, $enable_periods)
                    && verif_delais_max_resa_room($user_name, $room, $date_booking)
                    && verif_delais_min_resa_room($user_name, $room, $date_booking, $enable_periods)
                    && (($d['statutRessource'] == "1") || (($d['statutRessource'] == "0") && ($UserLevel > 2)))
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
    $weekcol++;
}
// Fin Première boucle sur les jours du mois !
// On grise les cellules appartenant au mois suivant
if ($weekcol > 0)
{
    for ($weekcol = 0; $weekcol < 7; $weekcol++)
    {
        $num_week_day = ($weekcol + $weekstarts)%7;
        if ($display_day[$num_week_day] == 1)
            $cellulesMois[] = array('numJour' => 0);
    }
}

echo $twig->render('mois.twig', array('d' => $d, 'settings' => $AllSettings, 'joursSemaine' => $joursSemaine, 'cellulesMois' => $cellulesMois));

            $content = ob_get_clean();
            if ($content === false) {
                throw new \RuntimeException('Unable to capture the GRR planning page.');
            }

            return new Response($content, $bootstrap['status'], $bootstrap['headers']);
        } finally {
            while (ob_get_level() > $bufferLevel) {
                ob_end_clean();
            }

            if (!chdir($workingDirectory)) {
                throw new \RuntimeException(sprintf('Unable to restore the working directory to "%s".', $workingDirectory));
            }
        }
    }

    public function semaineAll(Request $request): Response
    {
        if ($request->query->get('p') !== 'semaine_all') {
            throw new NotFoundHttpException();
        }

        $bootstrap = $this->applicationContext->boot();
        $context = $bootstrap['context'];
        global $gSessionName, $gSameSite, $room, $area, $id_site, $modulesActifs, $vocab, $d;
        global $resolution, $morningstarts, $eveningends, $eveningends_minutes;
        global $weekstarts, $twentyfourhour_format, $enable_periods, $periods_name;
        global $display_day, $nb_display_day, $description_breve, $description_complete, $locale, $user_name;

        extract($context, EXTR_SKIP);
        $user_name = $context['user_name'] ?? getUserName();
        $page = 'semaine_all';
        $grr_script_name = $page.'.php';
        $twig = $this->twig;
        $projectDir = dirname(__DIR__, 4);
        $workingDirectory = getcwd();
        if ($workingDirectory === false || !chdir($projectDir)) {
            throw new \RuntimeException('Unable to prepare the GRR planning page.');
        }

        $bufferLevel = ob_get_level();
        ob_start();
        try {
            include $projectDir.'/include/resume_session.php';
            include $projectDir.'/include/planning.php';

/**
 * semaine_all.php
 * Permet l'affichage du planning des réservations d'une semaine pour toutes les ressources d'un domaine.
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2026-04-03 11:31$
 * @author    Laurent Delineau & JeromeB & Yan Naessens
 * @copyright Since 2003 Team DEVOME - JeromeB
 * @link      http://www.gnu.org/licenses/licenses.html
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */

// Selection des ressources
$sql = "SELECT room_name, capacity, id, description, statut_room, show_fic_room, delais_option_reservation, moderate, who_can_book, confidentiel_resa FROM ".TABLE_PREFIX."_room WHERE area_id='".$area."' ORDER BY order_display, room_name";
$ressources = grr_sql_query($sql);

if (!$ressources)
	throw new \RuntimeException(grr_sql_error());

// Contrôle si il y a une ressource dans le domaine
if (grr_sql_count($ressources) == 0)
{
	$d['messageErreur'] = "<h1>".$this->translator->trans("no_rooms_for_area")."</h1>";
    echo $twig->render('planningerreur.twig', array('d' => $d, 'settings' => $AllSettings));
	return $this->responseFromBuffer($bootstrap);
}
grr_sql_free($ressources);

// accès à l'édition des ressources
$acces_config_level = (Settings::get('acces_config'))? Settings::get('acces_config') : 3;
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

$all_day = preg_replace("/ /", " ", $this->translator->trans("all_day2"));
$sql = "SELECT start_time, end_time, ".TABLE_PREFIX."_entry.id, name, beneficiaire, ".TABLE_PREFIX."_room.room_name, type, statut_entry, ".TABLE_PREFIX."_entry.description, ".TABLE_PREFIX."_entry.option_reservation, ".TABLE_PREFIX."_room.delais_option_reservation, ".TABLE_PREFIX."_entry.moderate, beneficiaire_ext, clef, ".TABLE_PREFIX."_entry.courrier, ".TABLE_PREFIX."_type_area.type_name, ".TABLE_PREFIX."_entry.overload_desc, ".TABLE_PREFIX."_entry.room_id, ".TABLE_PREFIX."_entry.nbparticipantmax,
COALESCE(participants_count.nbparticipants, 0) AS nbparticipants,
".TABLE_PREFIX."_room.confidentiel_resa
FROM ".TABLE_PREFIX."_entry
INNER JOIN ".TABLE_PREFIX."_room ON ".TABLE_PREFIX."_entry.room_id = ".TABLE_PREFIX."_room.id
INNER JOIN ".TABLE_PREFIX."_area ON ".TABLE_PREFIX."_area.id = ".TABLE_PREFIX."_room.area_id
INNER JOIN ".TABLE_PREFIX."_type_area ON ".TABLE_PREFIX."_type_area.type_letter = ".TABLE_PREFIX."_entry.type
LEFT JOIN (
    SELECT idresa, COUNT(*) AS nbparticipants
    FROM ".TABLE_PREFIX."_participants
    GROUP BY idresa
) AS participants_count ON participants_count.idresa = ".TABLE_PREFIX."_entry.id
WHERE
".TABLE_PREFIX."_area.id = '".$area."' AND
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
				$da[$day_num]["nbparticipantmax"][] = (int)$row['18'];
				$da[$day_num]["nbparticipants"][] = (int)$row['19'];

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
							$horaires = $start_str . $this->translator->trans("to") . $end_str;
						break;
						case "> = ":
						$horaires = $start_str . $this->translator->trans("to")."24:00";
						break;
						case "> > ":
						$horaires = $start_str . $this->translator->trans("to")."==>";
						break;
						case "= = ":
						$horaires = $all_day;
						break;
						case "= > ":
						$horaires = $all_day . "==>";
						break;
						case "< < ":
						$horaires = "<==".$this->translator->trans("to") . $end_str;
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
						$horaires = date(hour_min_format(), $row[0]) . $this->translator->trans("to") . date(hour_min_format(), $row[1]);
						break;
						case "> = ":
						$horaires = date(hour_min_format(), $row[0]) . $this->translator->trans("to")."24:00";
						break;
						case "> > ":
						$horaires = date(hour_min_format(), $row[0]) . $this->translator->trans("to")."==>";
						break;
						case "= = ":
						$horaires = $all_day;
						break;
						case "= > ":
						$horaires = $all_day . "==>";
						break;
						case "< < ":
						$horaires = "<==".$this->translator->trans("to") . date(hour_min_format(), $row[1]);
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
	$t = mktime(0, 0, 0, $temp_month, $num_day + 1, $temp_year);
	if ($display_day[$num_week_day] == 1)
	{
		$class = "";
		$title = "";
        $nomJour = day_name(($weekcol + $weekstarts) % 7) . ' '.$num_day.' '.$temp_month2;
        $nomCycle = "";

        if ($settings->get("show_feries") == 1 && isHoliday($tt)){
            $class .= 'ferie ';
        }

        if ($settings->get("show_holidays") == 1 && isSchoolHoliday($tt)){
            $class .= 'vacance ';
        }

        if (Settings::get("jours_cycles_actif") == 1 && intval($jour_cycle) >- 1)
		{
			if (intval($jour_cycle) > 0)
				$nomCycle = $this->translator->trans("rep_type_6")." ".$jour_cycle;
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
	$AccesUserResource = SecuAccess::UserResource($user_name, $row['id']);
	if ($AccesUserResource)
	{
		$acces_fiche_reservation = SecuAccess::UserSheetReservation($user_name, $row['id']);
		$UserRoomMaxBooking = UserRoomMaxBooking($user_name, $row['id'], 1);
		$UserLevel = SecuAccess::UserLevel($user_name, $row['id']);
		$accessVisitorBookingResource = SecuAccess::VisitorBookingResource($user_name, $row['id']);
        $ficheDescription = false;
		$resa_confidentiel = $row['confidentiel_resa'];
        // si la ressource est restreinte, l'utilisateur peut-il réserver ?
        $user_can_book = $row['who_can_book'] || ($UserLevel > 2) || (SecuAccess::UserBookingResourceRestrict($user_name,$row['id']));

		if ($li % 2 == 1)
			$classRess = "cell_hours";
		else
            $classRess = "cell_hours2";

		if (SecuAccess::UserSheetResource($user_name, $row['id']) && $d['pview'] != 1)
            $ficheDescription = true;

		$configRess = (SecuAccess::UserLevel($user_name,$row['id']) >= $acces_config_level) && ($d['pview'] != 1);

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
			$t2 = mktime(0, 0, 0, $cmonth, $cday + 1, $cyear);
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
								if($resa_confidentiel == 1 && getUserName() != $da[$cday]["beneficiaire"][$i] && $UserLevel < 3)
									$ficheResa = false;
							}

	                        	$classeReservation = $da[$cday]["color"][$i];
							if (isset($da[$cday]["nbparticipantmax"][$i]) && isset($da[$cday]["nbparticipants"][$i]) && ((int)$da[$cday]["nbparticipantmax"][$i] > 0) && ((int)$da[$cday]["nbparticipants"][$i] >= (int)$da[$cday]["nbparticipantmax"][$i]))
								$classeReservation = " quota-atteint";

	                        	$reservationsJour[] = array('idresa' => $da[$cday]["id"][$i], 'class' => $classeReservation, 'texte' => $da[$cday]["resa"][$i], 'bulle' => $da[$cday]["infobulle"][$i], 'lienFiche' => $ficheResa);
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

					if ((($UserLevel > 1) || ($accessVisitorBookingResource == 1)) && 
                    ($UserRoomMaxBooking != 0) && 
                    verif_booking_date($user_name, -1, $row['id'], $date_booking, $date_now, $enable_periods) && 
                    verif_delais_max_resa_room($user_name, $row['id'], $date_booking) && 
                    verif_delais_min_resa_room($user_name, $row['id'], $date_booking, $enable_periods) && 
                    (($row['statut_room'] == "1") || (($row['statut_room'] == "0") && (SecuAccess::UserLevel($user_name,$row['id']) > 2) )) && 
                    $user_can_book && 
                    $d['pview'] != 1)
					{
						if (Settings::get('calcul_plus_semaine_all') == 0) {
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

echo $twig->render('semaine_all.twig', array('d' => $d, 'settings' => $AllSettings, 'joursSemaine' => $joursSemaine, 'ressourcesSemaine' => $ressourcesSemaine));

            $content = ob_get_clean();
            if ($content === false) {
                throw new \RuntimeException('Unable to capture the GRR planning page.');
            }

            return new Response($content, $bootstrap['status'], $bootstrap['headers']);
        } finally {
            while (ob_get_level() > $bufferLevel) {
                ob_end_clean();
            }

            if (!chdir($workingDirectory)) {
                throw new \RuntimeException(sprintf('Unable to restore the working directory to "%s".', $workingDirectory));
            }
        }
    }

    public function moisAll(Request $request): Response
    {
        if ($request->query->get('p') !== 'mois_all') {
            throw new NotFoundHttpException();
        }

        $bootstrap = $this->applicationContext->boot();
        $context = $bootstrap['context'];
        global $gSessionName, $gSameSite, $room, $area, $id_site, $modulesActifs, $vocab, $d;
        global $resolution, $morningstarts, $eveningends, $eveningends_minutes;
        global $weekstarts, $twentyfourhour_format, $enable_periods, $periods_name;
        global $display_day, $nb_display_day, $description_breve, $description_complete, $locale, $user_name;

        extract($context, EXTR_SKIP);
        $user_name = $context['user_name'] ?? getUserName();
        $page = 'mois_all';
        $grr_script_name = $page.'.php';
        $twig = $this->twig;
        $projectDir = dirname(__DIR__, 4);
        $workingDirectory = getcwd();
        if ($workingDirectory === false || !chdir($projectDir)) {
            throw new \RuntimeException('Unable to prepare the GRR planning page.');
        }

        $bufferLevel = ob_get_level();
        ob_start();
        try {
            include $projectDir.'/include/resume_session.php';
            include $projectDir.'/include/planning.php';

/**
 * mois_all.php
 * Interface d'accueil avec affichage par mois des réservations de toutes les ressources d'un domaine
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2026-04-03 14:21$
 * @author    Laurent Delineau & JeromeB & Yan Naessens
 * @copyright Since 2003 Team DEVOME - JeromeB
 * @link      http://www.gnu.org/licenses/licenses.html
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */

// Selection des ressources
$sql = "SELECT room_name, capacity, id, description, statut_room, show_fic_room, delais_option_reservation, moderate, confidentiel_resa FROM ".TABLE_PREFIX."_room WHERE area_id='".$area."' ORDER BY order_display, room_name";
$ressources = grr_sql_query($sql);

if (!$ressources)
	throw new \RuntimeException(grr_sql_error());

// Contrôle si il y a une ressource dans le domaine
if (grr_sql_count($ressources) == 0)
{
	$d['messageErreur'] = "<h1>".$this->translator->trans("no_rooms_for_area")."</h1>";
	echo $twig->render('planningerreur.twig', array('d' => $d, 'settings' => $AllSettings));
    return $this->responseFromBuffer($bootstrap);
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

$all_day = preg_replace("/ /", " ", $this->translator->trans("all_day2"));
//Get all meetings for this month in the area that we care about
$sql = "SELECT start_time, end_time, ".TABLE_PREFIX."_entry.id, name, beneficiaire, ".TABLE_PREFIX."_room.room_name, type, statut_entry, ".TABLE_PREFIX."_entry.description, ".TABLE_PREFIX."_entry.option_reservation, ".TABLE_PREFIX."_room.delais_option_reservation, ".TABLE_PREFIX."_entry.moderate, beneficiaire_ext, clef, ".TABLE_PREFIX."_entry.courrier, ".TABLE_PREFIX."_type_area.type_name, ".TABLE_PREFIX."_entry.overload_desc, ".TABLE_PREFIX."_entry.room_id, ".TABLE_PREFIX."_entry.nbparticipantmax,
COALESCE(participants_count.nbparticipants, 0) AS nbparticipants,
".TABLE_PREFIX."_room.confidentiel_resa
FROM ((".TABLE_PREFIX."_entry INNER JOIN ".TABLE_PREFIX."_room ON ".TABLE_PREFIX."_entry.room_id = ".TABLE_PREFIX."_room.id)
  INNER JOIN ".TABLE_PREFIX."_type_area ON ".TABLE_PREFIX."_entry.type = ".TABLE_PREFIX."_type_area.type_letter)
LEFT JOIN (
    SELECT idresa, COUNT(*) AS nbparticipants
    FROM ".TABLE_PREFIX."_participants
    GROUP BY idresa
) AS participants_count ON participants_count.idresa = ".TABLE_PREFIX."_entry.id
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
    $row[19]: nbparticipants
    $row[20]: confidentiel_resa
*/

$res = grr_sql_query($sql);
if (!$res)
	echo grr_sql_error();
else  //Build an array of information about each day in the month.
{
    $overloadFieldList = mrbsOverloadGetFieldslist($area);
    $AccesUserResource = array();
    $acces_fiche_reservation = array();
	for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
	{
		if ($row['15'] <> (Settings::get('exclude_type_in_views_all')))
        {
            $AccesUserResource[$row[17]] = SecuAccess::UserResource($user_name, $row[17]);
            $acces_fiche_reservation[$row[17]] = SecuAccess::UserSheetReservation($user_name, $row[17]);
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
                $da[$day_num]["id_room"][] = $row[17];
                $da[$day_num]["room"][] = $row[5] ;
                $da[$day_num]["color"][] = $row[6];
                $da[$day_num]["nbparticipantmax"][] = (int)$row[18];
                $da[$day_num]["nbparticipants"][] = (int)$row[19];
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
                            $horaires = $start_str . $this->translator->trans("to") . $end_str;
                        break;
                        case "> = ":         //Starts after midnight, ends at midnight
                        $horaires = $start_str . $this->translator->trans("to")."24:00";
                        break;
                        case "> > ":         //Starts after midnight, continues tomorrow
                        $horaires = $start_str . $this->translator->trans("to")."&gt;";
                        break;
                        case "= = ":         //Starts at midnight, ends at midnight
                        $horaires = $all_day;
                        break;
                        case "= > ":         //Starts at midnight, continues tomorrow
                        $horaires = $all_day . "&gt;";
                        break;
                        case "< < ":         //Starts before today, ends before midnight
                        $horaires = "&lt;".$this->translator->trans("to") . $end_str;
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
                        $horaires = date(hour_min_format(), $row[0]) . $this->translator->trans("to") . date(hour_min_format(), $row[1]);
                        break;
                        case "> = ":         //Starts after midnight, ends at midnight
                        $horaires = date(hour_min_format(), $row[0]) . $this->translator->trans("to")."24:00";
                        break;
                        case "> > ":         //Starts after midnight, continues tomorrow
                        $horaires = date(hour_min_format(), $row[0]) . $this->translator->trans("to")."&gt;";
                        break;
                        case "= = ":         //Starts at midnight, ends at midnight
                        $horaires = $all_day;
                        break;
                        case "= > ":         //Starts at midnight, continues tomorrow
                        $horaires = $all_day . "&gt;";
                        break;
                        case "< < ":         //Starts before today, ends before midnight
                        $horaires = "&lt;".$this->translator->trans("to") . date(hour_min_format(), $row[1]);
                        break;
                        case "< = ":         //Starts before today, ends at midnight
                        $horaires = "&lt;" . $all_day;
                        break;
                        case "< > ":         //Starts before today, continues tomorrow
                        $horaires = "&lt;" . $all_day . "&gt;";
                        break;
                    }
                }
                $da[$day_num]["resa_confidentielle"][] = $row[20];
                $da[$day_num]["beneficiaire"][] = $row[4];
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
//Jour avant le mois, on grise !
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
    $reservations = array();

    if ($display_day[$num_week_day] == 1) // début condition "on n'affiche pas tous les jours de la semaine"
    {
        // On affiche les jours du mois dans le coin supérieur gauche de chaque cellule
        $heure = "";

        if ($settings->get("show_feries") == 1 && isHoliday($t)){
            $class .= 'ferie ';
        }

        if ($settings->get("show_holidays") == 1 && isSchoolHoliday($t)){
            $class .= 'vacance ';
        }

        if (est_hors_reservation(mktime(0,0,0,$month,$cday,$year),$area))
            $horsResa = true;
        else
        {
            $horsResa = false;

            // Des réservation à afficher pour ce jour ?
            if (isset($da[$cday]["id"][0]))
            {
                $n = count($da[$cday]["id"]);
                //Show the reservation information, linked to view_entry.
                //If there are 12 or fewer, show them, else show 11 and "...".
                for ($i = 0; $i < $n; $i++)
                {
                    $ficheResa = $acces_fiche_reservation[$da[$cday]["id_room"][$i]];

                    if ($AccesUserResource[$da[$cday]["id_room"][$i]]) // On n'affiche pas les réservations des ressources non visibles pour l'utilisateur.
                    {	
                        if ($i == Settings::get("max_resa_affiche") && $n >= Settings::get("max_resa_affiche"))
                        {
                            $autreResa = true;
                            break;
                        }

                        // On n'affiche la fiche résa que si elle n'est pas confidentielle ou si on est l'auteur de la résa ou un gestionnaire
                        if($acces_fiche_reservation[$da[$cday]["id_room"][$i]])
                        {
                            if($da[$cday]["resa_confidentielle"][$i] == 1 && getUserName() != $da[$cday]["beneficiaire"][$i] && SecuAccess::UserLevel(getUserName(),$da[$cday]["room"]) < 3)
                                $ficheResa = false;
                        }

                        $classeReservation = $da[$cday]["color"][$i];
                        $nbParticipants = isset($da[$cday]["nbparticipants"][$i]) ? (int)$da[$cday]["nbparticipants"][$i] : 0;
                        $nbParticipantsMax = isset($da[$cday]["nbparticipantmax"][$i]) ? (int)$da[$cday]["nbparticipantmax"][$i] : 0;

                        if ($nbParticipantsMax > 0 && $nbParticipants >= $nbParticipantsMax)
                            $classeReservation .= " quota-atteint";

                        $reservations[] = array ('idresa' => $da[$cday]["id"][$i],'td' => tdcellT($classeReservation), 'titre' => $da[$cday]["infobulle"][$i], 'texte' => $da[$cday]["resa"][$i], 'lienFiche' => $ficheResa);
                    }
                }
            /*
            envisager d'inclure ici un + pour ouvrir une fenêtre de réservation
            */
            }
            $plageLibre = false; // Aujourd'hui on ne le gère pas dans mois_all
        }
        
        // Une cellule par jour (Du 1er au 31)
        $cellulesMois[] = array('numJour' => $name_day, 'class' => $class, 'jourCycle' => intval($jour_cycle), 'horsResa' => $horsResa, 'plageLibre' => $plageLibre, "heure" => $heure, "reservations" => $reservations, 'autreResa' => $autreResa);

    } // fin condition "on n'affiche pas tous les jours de la semaine"
    $weekcol++;
    
}
// Fin Première boucle sur les jours du mois !
// On grise les cellules appartenant au mois suivant
if ($weekcol > 0)
{
    for ($weekcol = 0; $weekcol < 7; $weekcol++)
    {
        $num_week_day = ($weekcol + $weekstarts) % 7;
            // on n'affiche pas tous les jours de la semaine
        if ($display_day[$num_week_day] == 1)
            $cellulesMois[] = array('numJour' => 0);
    }
}

echo $twig->render('mois_all.twig', array('d' => $d, 'settings' => $AllSettings, 'joursSemaine' => $joursSemaine, 'cellulesMois' => $cellulesMois));

            $content = ob_get_clean();
            if ($content === false) {
                throw new \RuntimeException('Unable to capture the GRR planning page.');
            }

            return new Response($content, $bootstrap['status'], $bootstrap['headers']);
        } finally {
            while (ob_get_level() > $bufferLevel) {
                ob_end_clean();
            }

            if (!chdir($workingDirectory)) {
                throw new \RuntimeException(sprintf('Unable to restore the working directory to "%s".', $workingDirectory));
            }
        }
    }

    public function mois2All(Request $request): Response
    {
        if ($request->query->get('p') !== 'mois2_all') {
            throw new NotFoundHttpException();
        }

        $bootstrap = $this->applicationContext->boot();
        $context = $bootstrap['context'];
        global $gSessionName, $gSameSite, $room, $area, $id_site, $modulesActifs, $vocab, $d;
        global $resolution, $morningstarts, $eveningends, $eveningends_minutes;
        global $weekstarts, $twentyfourhour_format, $enable_periods, $periods_name;
        global $display_day, $nb_display_day, $description_breve, $description_complete, $locale, $user_name;

        extract($context, EXTR_SKIP);
        $user_name = $context['user_name'] ?? getUserName();
        $page = 'mois2_all';
        $grr_script_name = $page.'.php';
        $twig = $this->twig;
        $projectDir = dirname(__DIR__, 4);
        $workingDirectory = getcwd();
        if ($workingDirectory === false || !chdir($projectDir)) {
            throw new \RuntimeException('Unable to prepare the GRR planning page.');
        }

        $bufferLevel = ob_get_level();
        ob_start();
        try {
            include $projectDir.'/include/resume_session.php';
            include $projectDir.'/include/planning.php';

/**
 * mois2_all.php
 * Interface d'accueil avec affichage par mois des réservations de toutes les ressources d'un domaine
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2026-04-10 09:32$
 * @author    Laurent Delineau & JeromeB & Yan Naessens
 * @copyright Since 2003 Team DEVOME - JeromeB
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

// Selection des ressources
$sql = "SELECT * FROM ".TABLE_PREFIX."_room WHERE area_id='".$area."' ORDER BY order_display, room_name";
$ressources = grr_sql_query($sql);
if (!$ressources)
	throw new \RuntimeException(grr_sql_error());
// Contrôle s'il y a une ressource dans le domaine
if (grr_sql_count($ressources) == 0)
{
	$d['messageErreur'] = "<h1>".$this->translator->trans("no_rooms_for_area")."</h1>";
    echo $twig->render('planningerreur.twig', array('d' => $d, 'settings' => $AllSettings));
	return $this->responseFromBuffer($bootstrap);
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

$all_day = preg_replace("/ /", " ", $this->translator->trans("all_day"));
//Get all meetings for this month in the area that we care about
$sql = "SELECT start_time, end_time, ".TABLE_PREFIX."_entry.id, name, beneficiaire, ".TABLE_PREFIX."_room.room_name, type, statut_entry, ".TABLE_PREFIX."_entry.description, ".TABLE_PREFIX."_entry.option_reservation, ".TABLE_PREFIX."_room.delais_option_reservation, ".TABLE_PREFIX."_entry.moderate, beneficiaire_ext, clef, ".TABLE_PREFIX."_entry.courrier, ".TABLE_PREFIX."_type_area.type_name, ".TABLE_PREFIX."_entry.overload_desc, ".TABLE_PREFIX."_entry.room_id, ".TABLE_PREFIX."_entry.nbparticipantmax,
COALESCE(participants_count.nbparticipants, 0) AS nbparticipants,
".TABLE_PREFIX."_room.confidentiel_resa
FROM ((".TABLE_PREFIX."_entry INNER JOIN ".TABLE_PREFIX."_room ON ".TABLE_PREFIX."_entry.room_id = ".TABLE_PREFIX."_room.id)
  INNER JOIN ".TABLE_PREFIX."_type_area ON ".TABLE_PREFIX."_entry.type = ".TABLE_PREFIX."_type_area.type_letter)
LEFT JOIN (
    SELECT idresa, COUNT(*) AS nbparticipants
    FROM ".TABLE_PREFIX."_participants
    GROUP BY idresa
) AS participants_count ON participants_count.idresa = ".TABLE_PREFIX."_entry.id
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
    $row[19]: nbparticipants
    $row[20]: confidentiel_resa
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
        $dr[$day_num]["nbparticipantmax"][] = (int)$row[18];
        $dr[$day_num]["nbparticipants"][] = (int)$row[19];
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
							$horaires = $start_str . $this->translator->trans("to") . $end_str;
						break;
						case "> = ":
						$horaires = $start_str . $this->translator->trans("to")."24:00";
						break;
						case "> > ":
						$horaires = $start_str . $this->translator->trans("to")."==>";
						break;
						case "= = ":
						$horaires = $all_day;
						break;
						case "= > ":
						$horaires = $all_day . "==>";
						break;
						case "< < ":
						$horaires = "<==".$this->translator->trans("to") . $end_str;
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
						$horaires = date(hour_min_format(), $row[0]) . $this->translator->trans("to") . date(hour_min_format(), $row[1]);
						break;
						case "> = ":
						$horaires = date(hour_min_format(), $row[0]) . $this->translator->trans("to")."24:00";
						break;
						case "> > ":
						$horaires = date(hour_min_format(), $row[0]) . $this->translator->trans("to")."==>";
						break;
						case "= = ":
						$horaires = $all_day;
						break;
						case "= > ":
						$horaires = $all_day . "==>";
						break;
						case "< < ":
						$horaires = "<==".$this->translator->trans("to") . date(hour_min_format(), $row[1]);
						break;
						case "< = ":
						$horaires = "<==" . $all_day;
						break;
						case "< > ":
						$horaires = "<==" . $all_day . "==>";
						break;
					}
				}
				$dr[$day_num]["resa_confidentielle"][] = $row[20];
				$dr[$day_num]["beneficiaire"][] = $row[4];
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

        if (Settings::get("jours_cycles_actif") == 1 && intval($jour_cycle) > -1)
        {
            if (intval($jour_cycle) > 0)
                $nomCycle = ucfirst(substr($this->translator->trans("rep_type_6"), 0, 1)).$jour_cycle;
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
	$AccesUserResource = SecuAccess::UserResource($user_name, $row["id"]);
	if ($AccesUserResource)
	{
		$acces_fiche_reservation = SecuAccess::UserSheetReservation($user_name, $row["id"]);
        $UserLevel = SecuAccess::UserLevel($user_name, $row["id"]);
        // si la ressource est restreinte, l'utilisateur peut-il réserver ?
        $user_can_book = $row["who_can_book"] || ($UserLevel > 2) || (SecuAccess::UserBookingResourceRestrict($user_name,$row['id']));

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
			$nbResaAfficher = 0;

			if ($display_day[$cweek] == 1)
			{
				$estHorsReservation = est_hors_reservation(mktime(0, 0, 0, $month, $cday, $year), $area);
                if (isset($dr[$cday]["id"][0])) // il y a une réservation au moins à afficher
                {
                    $n = count($dr[$cday]["id"]);

					for ($i = 0; $i < $n; $i++)
                    {
						$autreResa = false;

                        if ($nbResaAfficher >= Settings::get("max_resa_affiche") )
                        {
                            $autreResa = true;
                            break;
                        }

						if ($dr[$cday]["room"][$i] == $row["room_name"])
						{
							// On n'affiche la fiche résa que si elle n'est pas confidentielle ou si on est l'auteur de la résa ou un gestionnaire
							$ficheResa = $acces_fiche_reservation;
							if($acces_fiche_reservation)
							{
								if($dr[$cday]["resa_confidentielle"][$i] == 1 && getUserName() != $dr[$cday]["beneficiaire"][$i] && $UserLevel < 3)
								{
									$ficheResa = false;
									$dr[$cday]["lien"][$i] = $dr[$cday]["id"][$i];
								}
							}

							$classeReservation = $dr[$cday]["color"][$i];
							$nbParticipants = isset($dr[$cday]["nbparticipants"][$i]) ? (int)$dr[$cday]["nbparticipants"][$i] : 0;
							$nbParticipantsMax = isset($dr[$cday]["nbparticipantmax"][$i]) ? (int)$dr[$cday]["nbparticipantmax"][$i] : 0;

							if ($nbParticipantsMax > 0 && $nbParticipants >= $nbParticipantsMax)
								$classeReservation = " quota-atteint";
						
							$nbResaAfficher++;
							$reservationsJour[] = array('idresa' => $dr[$cday]["id"][$i], 'class' => $classeReservation, 'texte' => $dr[$cday]["lien"][$i], 'bulle' => $dr[$cday]["infobulle"][$i], 'lienFiche' => $ficheResa);
						}
                    }
                    
                }
                // la ressource est-elle accessible en réservation ? on affiche le lien vers edit_entry
                $date_booking = mktime(23,59,0,$month,$k,$year) ; // le jour courant à presque minuit
                $hour =  date("H",$date_now); // l'heure courante, par défaut
                if (!$estHorsReservation){
                    if ((($UserLevel > 1) || (SecuAccess::VisitorBookingResource($user_name, $row["id"]) == 1)) 
                        && (UserRoomMaxBooking($user_name, $row["id"], 1) != 0) 
                        && verif_booking_date($user_name, -1, $row["id"], $date_booking, $date_now, $enable_periods) 
                        && verif_delais_max_resa_room($user_name, $row["id"], $date_booking) 
                        && verif_delais_min_resa_room($user_name, $row["id"], $date_booking, $enable_periods) 
                        && (($row["statut_room"] == "1") || (($row["statut_room"] == "0") && (SecuAccess::UserLevel($user_name,$row["id"]) > 2) )) 
                        && $user_can_book
                        && $d['pview'] != 1){
							if (Settings::get('calcul_plus_mois2_all') == 0) {
								$statutCellule = 1;
							} elseif(plages_libre_semaine_ressource($row["id"], $month, $cday, $year))
							{
								$statutCellule = 1;
							} else {
								$statutCellule = 2;
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

echo $twig->render('mois2_all.twig', array('d' => $d, 'settings' => $AllSettings, 'joursMois' => $joursMois, 'ressourcesMois' => $ressourcesMois));

            $content = ob_get_clean();
            if ($content === false) {
                throw new \RuntimeException('Unable to capture the GRR planning page.');
            }

            return new Response($content, $bootstrap['status'], $bootstrap['headers']);
        } finally {
            while (ob_get_level() > $bufferLevel) {
                ob_end_clean();
            }

            if (!chdir($workingDirectory)) {
                throw new \RuntimeException(sprintf('Unable to restore the working directory to "%s".', $workingDirectory));
            }
        }
    }

    private function responseFromBuffer(array $bootstrap): Response
    {
        $content = ob_get_clean();
        if ($content === false) {
            throw new \RuntimeException('Unable to capture the GRR planning page.');
        }

        return new Response($content, $bootstrap['status'], $bootstrap['headers']);
    }
}
