<?php
/**
 * admin_calend_ignore.php
 * Interface permettant la la réservation en bloc de journées entières
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2022-06-19 15:42$
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


$grr_script_name = "admin_calend_ignore.php";

check_access(6, $back);

get_vocab_admin('calendrier_des_jours_hors_reservation');
get_vocab_admin('les_journees_cochees_sont_ignorees');
get_vocab_admin('check_all_the');
get_vocab_admin('uncheck_all_the');
get_vocab_admin('admin_calend_ignore_vacances');
get_vocab_admin('admin_calend_ignore_feries');
get_vocab_admin('uncheck_all_');

get_vocab_admin('save');

if (isset($_POST['record']) && ($_POST['record'] == 'yes'))
{
	// On met de côté toutes les dates
	$day_old = array();
	$res_old = grr_sql_query("SELECT day FROM ".TABLE_PREFIX."_calendar");
	if ($res_old)
	{
		for ($i = 0; ($row_old = grr_sql_row($res_old, $i)); $i++)
			$day_old[$i] = $row_old[0];
	}
	// On vide la table ".TABLE_PREFIX."_calendar
	$sql = "truncate table ".TABLE_PREFIX."_calendar";
	if (grr_sql_command($sql) < 0)
		fatal_error(0, "<p>" . grr_sql_error());
	$result = 0;
	$end_bookings = Settings::get("end_bookings");
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
				// Le jour a été selectionné dans le calendrier
				$starttime = mktime($morningstarts, 0, 0, $month, $day  , $year);
				$endtime   = mktime($eveningends, 0, $resolution, $month, $day, $year);
				// Pour toutes les dates bon précédement enregistrées, on efface toutes les résa en conflit
				if (!in_array($n,$day_old))
				{
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
				}
				 	// On enregistre la valeur dans ".TABLE_PREFIX."_calendar
				$sql = "INSERT INTO ".TABLE_PREFIX."_calendar set DAY='".$n."'";
				if (grr_sql_command($sql) < 0)
					fatal_error(0, "<p>" . grr_sql_error());
			}
			$day++;
		}
		$month++;
		if ($month == 13) {
			$year++;
			$month = 1;
		}
	}
}


$basetime = mktime(12, 0, 0, 6, 11 + $weekstarts, 2000);
$jourssemaines = array();

for ($i = 0; $i < 7; $i++)
{
	$show = $basetime + ($i * 24 * 60 * 60);
	$jourssemaines[] = utf8_strftime('%A',$show);
}

if (Settings::get("show_holidays") == 'Oui'){ // on n'affiche ce choix que si les jours fériés et les vacances sont définis
    // définir les jours fériés
    $req = "SELECT * FROM ".TABLE_PREFIX."_calendrier_feries";
    $ans = grr_sql_query($req);
    $feries = array();
    foreach($ans as $val){$feries[] = $val['DAY'];}
    $trad['dCocheferies'] = "";
    foreach ($feries as &$value) {
        $trad['dCocheferies'] .= "setCheckboxesGrrName(document.getElementById('formulaire'), true, '{$value}'); ";
    }
    unset($feries);
    // définir les vacances
    $req = "SELECT * FROM ".TABLE_PREFIX."_calendrier_vacances";
    $ans = grr_sql_query($req);
    $vacances = array();
    foreach($ans as $val){$vacances[] = $val['DAY'];}
    $trad['dCocheVacances'] = "";
    foreach ($vacances as &$value) {
        $trad['dCocheVacances'] .= "setCheckboxesGrrName(document.getElementById('formulaire'), true, '{$value}'); ";
    }
    unset($vacances);
}

$n = Settings::get("begin_bookings");
$end_bookings = Settings::get("end_bookings");
$debligne = 1;
$month = date("m", Settings::get("begin_bookings"));
$year = date("Y", Settings::get("begin_bookings"));
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
	$n = mktime(0,0,0,$month,1,$year);
}
if ($inc < 3)
{
	$k=$inc;
	while ($k < 3)
	{
		$trad['dCalendrier'] .= "<td> </td>\n";
		$k++;
	}
	// while
	$trad['dCalendrier'] .= "</tr>";
}

	echo $twig->render('admin_calend_ignore.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings, 'jourssemaines' => $jourssemaines));
?>