<?php
/**
 * admin_reservation_bloc.php
 * interface permettant la la réservation en bloc de journées entières
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2022-06-19 15:48$
 * @author    JeromeB & Laurent Delineau & Marc-Henri PAMISEUX & Yan Naessens
 * @copyright Copyright 2003-2022 Team DEVOME - JeromeB
 * @link      http://www.gnu.org/licenses/licenses.html
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */

$grr_script_name = "admin_reservation_bloc.php";

check_access(4, $back);

// Initialisation
$etape = isset($_POST["etape"]) ? $_POST["etape"] : NULL;
$areas = isset($_POST["areas"]) ? $_POST["areas"] : NULL;
$rooms = isset($_POST["rooms"]) ? $_POST["rooms"] : NULL;
$name = isset($_POST["name"]) ? $_POST["name"] : NULL;
$beneficiaire = isset($_POST["beneficiaire"]) ? $_POST["beneficiaire"] : NULL;
$description = isset($_POST["description"]) ? $_POST["description"] : NULL;
$type_ = isset($_POST["type_"]) ? $_POST["type_"] : NULL;
$type_resa = isset($_POST["type_resa"]) ? $_POST["type_resa"] : NULL;
$hour = isset($_POST["hour"]) ? $_POST["hour"] : NULL;
settype($hour,"integer");
$end_hour = isset($_POST["end_hour"]) ? $_POST["end_hour"] : NULL;
settype($end_hour,"integer");
$minute = isset($_POST["minute"]) ? $_POST["minute"] : NULL;
settype($minute,"integer");
$end_minute = isset($_POST["end_minute"]) ? $_POST["end_minute"] : NULL;
settype($end_minute,"integer");
$period = isset($_POST["period"]) ? $_POST["period"] : NULL;
$end_period = isset($_POST["end_period"]) ? $_POST["end_period"] : NULL;
$all_day = isset($_POST["all_day"]) ? $_POST["all_day"] : NULL;
$domaines = array();
$ressources = array();
$beneficiaires = array();
$types = array();
$jourssemaines = array();
$periodes = array();

$trad['dDebutJournee'] = $morningstarts;

get_vocab_admin('admin_calendar_title');
get_vocab_admin('etape_n');

// Etape 1
get_vocab_admin('admin_calendar_explain_1');
get_vocab_admin('choix_domaines');
get_vocab_admin('ctrl_click');
get_vocab_admin('choix_action');
get_vocab_admin('reservation_en_bloc');
get_vocab_admin('suppression_en_bloc');

// Etape 2
get_vocab_admin('reservation_en_bloc');
get_vocab_admin('suppression_en_bloc');
get_vocab_admin('reservation_au_nom_de');
get_vocab_admin('namebooker');
get_vocab_admin('fulldescription');
get_vocab_admin('rooms');
get_vocab_admin('type');
get_vocab_admin('choose');
get_vocab_admin('you_have_not_entered');
get_vocab_admin('brief_description');
get_vocab_admin('choose_a_room');
get_vocab_admin('choose_a_type');

// Etape 3
get_vocab_admin('check_all_the');
get_vocab_admin('uncheck_all_the');
get_vocab_admin('uncheck_all_');
get_vocab_admin('date');
get_vocab_admin('deux_points');
get_vocab_admin('period');
get_vocab_admin('time');
get_vocab_admin('fin_reservation');
get_vocab_admin('domaines_de_type_incompatibles');

// Etape 3
get_vocab_admin('suppression_en_bloc_result');
get_vocab_admin('reservation_en_bloc_result');
get_vocab_admin('reservation_en_bloc_result2');

get_vocab_admin('next');
get_vocab_admin('save');


$result = 0;
if (isset($_POST['record']) && ($_POST['record'] == 'yes'))
{
	$end_bookings = Settings::get("end_bookings");
	// On reconstitue le tableau des ressources
	$sql = "SELECT id FROM ".TABLE_PREFIX."_room";
	$res = grr_sql_query($sql);
	if ($res)
	{
		for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
		{
			$temp = "id_room_".$row[0];
			if ((isset($_POST[$temp])) && verif_acces_ressource(getUserName(),$row[0]))
			{
			// La ressource est selectionnée
			// $rooms[] = $id;
			// On récupère les données du domaine
				$area_id = grr_sql_query1("SELECT area_id FROM ".TABLE_PREFIX."_room WHERE id = '".$row[0]."'");
				$id_site = grr_sql_query1("SELECT id_site FROM ".TABLE_PREFIX."_j_site_area WHERE id_area = '".$area_id."'");
				//if (authGetUserLevel(getUserName(),$id_site,'site') >= 5)
				if (1)
				{
					get_planning_area_values($area_id);
					$n = Settings::get("begin_bookings");
					$month = date('m', Settings::get("begin_bookings"));
					$year = date('Y', Settings::get("begin_bookings"));
					$day = 1;

					while ($n <= $end_bookings)
					{
						$daysInMonth = getDaysInMonth($month, $year);
						$day = 1;
						while ($day <= $daysInMonth)
						{
							$n = mktime(0, 0, 0, $month, $day, $year);
							if (isset($_POST[$n]))
							{
								$erreur = 'n';
								// Le jour a été selectionné dans le calendrier
								if (!isset($all_day))
								{
								// Cas des réservation par créneaux pré-définis
									if ($enable_periods=='y')
									{
										$resolution = 60;
										$hour = 12;
										$end_hour = 12;
										if (isset($period))
											$minute = $period;
										else
											$minute = 0;
										if (isset($end_period))
											$end_minute = $end_period + 1;
										else
											$end_minute = $eveningends_minutes + 1;
									}
									$starttime = mktime($hour, $minute, 0, $month, $day, $year);
									$endtime   = mktime($end_hour, $end_minute, 0, $month, $day, $year);
									if ($endtime <= $starttime)
										$erreur = 'y';
								}
								else
								{
									$starttime = mktime($morningstarts, 0, 0, $month, $day, $year);
									$endtime   = mktime($eveningends, $eveningends_minutes , 0, $month, $day, $year);
								}
								if ($erreur != 'y')
								{
									// On efface toutes les résa en conflit
									$grrDelEntryInConflict = grrDelEntryInConflict($row[0], $starttime, $endtime, 0, 0, 1);
									if( !is_numeric($grrDelEntryInConflict) )
										$grrDelEntryInConflict = 0;

									$result += $grrDelEntryInConflict;

									// S'il s'agit d'une action de réservation, on réserve !
									if ($type_resa == "resa")
									{
										// Par sécurité, on teste quand même s'il reste des conflits
										$err = mrbsCheckFree($row[0], $starttime, $endtime, 0,0);
										if (!$err)
											mrbsCreateSingleEntry(0,$starttime, $endtime, 0, 0, $row[0], getUserName(), $beneficiaire, "", $name, $type_, $description, -1,array(),0,0,'-', 0, 0,0);
									}
								}
							}
							$day++;
						}
						$month++;
						if ($month == 13)
						{
							$year++;
							$month = 1;
						}
					}
				}
			}
		}
	}
}
if ($etape == 4)
{
	$trad['dEtape'] = 4;
	$trad['dTypeResa'] = $type_resa;

	if ($result == '')
		$trad['dResult'] = 0;
	else
		$trad['dResult'] = $result;
}

if ($etape == 3)
{
	$trad['dEtape'] = 3;
	$trad['dTypeResa'] = $type_resa;
	$trad['dName'] = $name;
	$trad['dDescription'] = $description;
	$trad['dBeneficiaire'] = $beneficiaire;
	$trad['dType'] = $type_;

	if (!isset($rooms))
	{
		echo "<h3>".get_vocab("noarea")."</h3>\n";
		die();
	}

	$test_enable_periods_y = 0;
	$test_enable_periods_n = 0;
	foreach ( $rooms as $room_id )
	{
		$ressources[] = array('id' => $room_id);
		$area_id = grr_sql_query1("SELECT area_id FROM ".TABLE_PREFIX."_room WHERE id = '".$room_id."'");
		$test_enable_periods_y += grr_sql_query1("SELECT count(enable_periods) FROM ".TABLE_PREFIX."_area WHERE (id = '".$area_id."' and enable_periods='y')");
		$test_enable_periods_n += grr_sql_query1("SELECT count(enable_periods) FROM ".TABLE_PREFIX."_area WHERE (id = '".$area_id."' and enable_periods='n')");
	}
	// On teste si tous les domaines selectionnés sont du même type d'affichage à savoir :
	// soit des créneaux de réservation basés sur le temps,
	// soit des créneaux de réservation basés sur des intitulés pré-définis.

	if ($test_enable_periods_y == 0)
		$trad['dPeriode'] = 'n';
	else if ($test_enable_periods_n == 0)
		$trad['dPeriode'] = 'y';
	else
		$trad['dPeriode'] = 'incompatible';

	if ($trad['dPeriode'] != "incompatible")
	{
		// On prend comme domaine de référence le dernier domaine de la boucle  foreach ( $rooms as $room_id ) {
		// C'est pas parfait mais bon !
		get_planning_area_values($area_id);
		if ($trad['dPeriode'] == 'y')
		{
			// Créneaux basés sur les intitulés pré-définis
			foreach ($periods_name as $p_num => $p_val)
				$periodes[] = array('num' => $p_num, 'val' => $p_val);

		}
	}

	$basetime = mktime(12, 0, 0, 6, 11 + $weekstarts, 2000);
	for ($i = 0; $i < 7; $i++)
	{
		$show = $basetime + ($i * 24 * 60 * 60);
		$jourssemaines[] = utf8_strftime('%A',$show);
	}

	$n = Settings::get("begin_bookings");
	$end_bookings = Settings::get("end_bookings");
	$debligne = 1;
	$month = date('m', Settings::get("begin_bookings"));
	$year = date('Y', Settings::get("begin_bookings"));
	$inc = 0;
	$trad['dCalendrier'] = "";

	while ($n <= $end_bookings)
	{
		if ($debligne == 1)
		{
			$trad['dCalendrier'] .= "<tr>\n";
			$inc = 0;
			$debligne = 0;
		}
		$inc++;
		$trad['dCalendrier'] .= "<td>\n";
		$trad['dCalendrier'] .= cal($month, $year, 1);
		$trad['dCalendrier'] .= "</td>";
		if ($inc == 3)
		{
			$trad['dCalendrier'] .= "</tr>";
			$debligne = 1;
		}
		$month++;
		if ($month == 13)
		{
			$year++;
			$month = 1;
		}
		$n = mktime(0, 0, 0, $month, 1, $year);
	}
	if ($inc < 3)
	{
		$k = $inc;
		while ($k < 3)
		{
			$trad['dCalendrier'] .= "<td> </td>\n";
			$k++;
		}
		$trad['dCalendrier'] .= "</tr>";
	}
}

else if ($etape == 2)
{
	$trad['dEtape'] = 2;
	$trad['dTypeResa'] = $type_resa;

	if (!isset($areas))
	{
		echo "<h3>".get_vocab("noarea")."</h3>\n";
		die();
	}

	if ($type_resa == "resa")
	{
		$sql = "SELECT DISTINCT login, nom, prenom FROM ".TABLE_PREFIX."_utilisateurs WHERE  (etat!='inactif' and statut!='visiteur' ) order by nom, prenom";
		$res = grr_sql_query($sql);
		if ($res)
		{
			for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
			{
				$select = 0;
				if (getUserName() == $row[0])
					$select = 1;

				$beneficiaires[] = array('id' => $row[0], 'nom' => $row[1], 'prenom' => $row[2], 'select' => $select);
			}
		}
	}

	foreach ( $areas as $area_id )
	{
		# then select the rooms in that area
		$sql = "SELECT id, room_name FROM ".TABLE_PREFIX."_room WHERE area_id=$area_id ";
		// tableau des ressources auxquelles l'utilisateur n'a pas accès
		$tab_rooms_noaccess = verif_acces_ressource(getUserName(), 'all');
		// on ne cherche pas parmi les ressources invisibles pour l'utilisateur
		foreach ($tab_rooms_noaccess as $key)
			$sql .= " and id != $key ";
		$sql .= "order by order_display,room_name";
		$res = grr_sql_query($sql);
		if ($res)
		{
			for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
				$ressources[] = array('id' => $row[0], 'nom' => $row[1]);
		}
	}

	if ($type_resa == "resa")
	{

		$sql = "SELECT DISTINCT t.type_name, t.type_letter FROM ".TABLE_PREFIX."_type_area t
		LEFT JOIN ".TABLE_PREFIX."_j_type_area j on j.id_type=t.id
		WHERE (j.id_area  IS NULL or (";
			$ind = 0;
			foreach ( $areas as $area_id )
			{
				if ($ind != 0)
					$sql .= " and ";
				$sql .= "j.id_area != '".$area_id."'";
				$ind = 1;
			}
			$sql .= ")) ORDER BY order_display";
		$res = grr_sql_query($sql);
		if ($res)
		{
			for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
			{
				$select = 0;
				if ($type_ == $row[1])
					$select = 1;

				$types[] = array('id' => $row[0], 'nom' => $row[1], 'select' => $select);
			}
		}
	}
}
else if (!$etape)
{
	$trad['dEtape'] = 1;

	if (authGetUserLevel(getUserName(), -1) >= 2)
		$sql = "SELECT id, area_name FROM ".TABLE_PREFIX."_area
	ORDER BY order_display, area_name";
	else
		$sql = "SELECT a.id, a.area_name FROM ".TABLE_PREFIX."_area a, ".TABLE_PREFIX."_j_site_area j, ".TABLE_PREFIX."_site s, ".TABLE_PREFIX."_j_useradmin_site u
	WHERE a.id=j.id_area and j.id_site = s.id and s.id=u.id_site and u.login='".getUserName()."'
	ORDER BY a.order_display, a.area_name";

	$res = grr_sql_query($sql);

	if ($res)
	{
		for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
		{
			if (authUserAccesArea(getUserName(),$row[0]) == 1)
				$domaines[] = array('id' => $row[0], 'nom' => $row[1]);
		}
	}

}

echo $twig->render('admin_reservation_bloc.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings, 'domaines' => $domaines, 'ressources' => $ressources, 'beneficiaires' => $beneficiaires, 'types' => $types, 'jourssemaines' => $jourssemaines, 'periodes' => $periodes));
?>