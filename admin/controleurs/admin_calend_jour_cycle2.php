<?php
/**
 * admin_config_calend2.php
 * interface permettant la la réservation en bloc de journées entières
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2022-06-19 15:43$
 * @author    Laurent Delineau & JeromeB
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

$grr_script_name = "admin_calend_jour_cycle2.php";

check_access(6, $back);

get_vocab_admin("titre_config_Jours_Cycles");

get_vocab_admin("admin_config_calend1");
get_vocab_admin("admin_config_calend2");
get_vocab_admin("admin_config_calend3");

get_vocab_admin("les_journees_cochees_sont_valides");
get_vocab_admin("nombre_jours_Jours_Cycles");
get_vocab_admin("debut_Jours_Cycles");
get_vocab_admin("uncheck_all_the");

get_vocab_admin("check_all_the");
get_vocab_admin("deux_points");
get_vocab_admin("uncheck_all_");

get_vocab_admin("deux_points");
get_vocab_admin("save");

//
// Enregistrement
//
if (isset($_POST['record']) && ($_POST['record'] == 'yes'))
{
	// On vide la table
	$sql = "truncate table ".TABLE_PREFIX."_calendrier_jours_cycle";
	if (grr_sql_command($sql) < 0)
		fatal_error(1, "<p>" . grr_sql_error());
	$result = 0;
	$end_bookings = Settings::get("end_bookings");
	$n = Settings::get("begin_bookings");
	$month = date('m', Settings::get("begin_bookings"));
	$year = date('Y', Settings::get("begin_bookings"));
	$day = 1;
	// Pour aller chercher le Jour cycle qui débutera le premier cycle de jours
	$m = Settings::get("jour_debut_Jours_Cycles");
	while ($n <= $end_bookings)
	{
		$daysInMonth = getDaysInMonth($month, $year);
		$day = 1;
		while ($day <= $daysInMonth)
		{
			$n = mktime(0, 0, 0, $month, $day, $year);
			if (isset($_POST[$n]))
			{
				// Le jour a été selectionné dans le calendrier
				$starttime = mktime($morningstarts, 0, 0, $month, $day  , $year);
				$endtime   = mktime($eveningends, 0, $resolution, $month, $day, $year);
				 // On efface toutes les résa en conflit
				$sql = "select id from ".TABLE_PREFIX."_room";
				$res = grr_sql_query($sql);
				if ($res)
				{
					for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
					{
						$grrDelEntryInConflict = grrDelEntryInConflict($row[0], $starttime, $endtime, 0, 0, 1);
						if( !is_numeric($grrDelEntryInConflict) )
							$grrDelEntryInConflict = 0;

						$result += $grrDelEntryInConflict;
					}
				}
				// On enregistre la valeur
				$m = cree_calendrier_date_valide($n,$m);
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

//
// Affichage
//
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
	$k=$inc;
	while ($k < 3)
	{
		$trad['dCalendrier'] .= "<td> </td>\n";
		$k++;
	}
	$trad['dCalendrier'] .= "</tr>";
}

echo $twig->render('admin_calend_jour_cycle2.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings, 'jourssemaines' => $jourssemaines));
?>