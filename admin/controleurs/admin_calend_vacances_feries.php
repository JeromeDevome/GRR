<?php
/**
 * admin_calend_vacances_feries.php
 * Interface permettant la définiton des jours fériés ou de vacances
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2022-06-19 15:44$
 * @author    Laurent Delineau & JeromeB & Yan Naessens
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

$grr_script_name = "admin_calend_vacances_feries.php";

check_access(6, $back);

get_vocab_admin('admin_calend_vacances_feries');
get_vocab_admin('vacances_feries_description');

get_vocab_admin('vacances_feries_FR');
get_vocab_admin('uncheck_all_');
get_vocab_admin('vacances_FR');
get_vocab_admin('returnprev');

get_vocab_admin('save');

// premier test : l'affichage des vacances et fériés est-il activé ?
if (Settings::get("show_holidays") == 'Oui' && isset($_POST['define_holidays']))
{

	if ((isset($_POST['define_holidays'])) && ($_POST['define_holidays'] == 'F')){

		$trad['dTypeDefinition'] = "F";
		// traiter les jours fériés
		if (isset($_POST['recordFeries']) && ($_POST['recordFeries'] == 'yes'))
		{
			// On met de côté toutes les dates
			$day_old = array();
			$res_old = grr_sql_query("SELECT day FROM ".TABLE_PREFIX."_calendrier_feries");
			if ($res_old)
			{
				for ($i = 0; ($row_old = grr_sql_row($res_old, $i)); $i++)
					$day_old[$i] = $row_old[0];
			}
			// On vide la table ".TABLE_PREFIX."_calendrier_feries
			$sql = "truncate table ".TABLE_PREFIX."_calendrier_feries";
			if (grr_sql_command($sql) < 0)
				fatal_error(0, "<p>" . grr_sql_error());
			$result = 0;
			$end_bookings = Settings::get("end_bookings");
			$begin_bookings = Settings::get("begin_bookings");
			$month = date('m', $begin_bookings );
			$year = date('Y', $begin_bookings );
			$day = 1;
			$n = $begin_bookings;
			while ($n <= $end_bookings)
			{
				$daysInMonth = getDaysInMonth($month, $year);
				$day = 1;
				while ($day <= $daysInMonth)
				{
					$n = mktime(0, 0, 0, $month, $day, $year);
					if (isset($_POST[$n]))
					{
						// On enregistre la valeur dans ".TABLE_PREFIX."_calendrier_feries
						$sql = "INSERT INTO ".TABLE_PREFIX."_calendrier_feries set DAY='".$n."'";
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
		$begin_bookings = Settings::get("begin_bookings");
		$end_bookings = Settings::get("end_bookings");
		$month = utf8_encode(date('m', $begin_bookings));
		$year = date('Y', $begin_bookings);
		$yearFin = date('Y', $end_bookings);
		$i = $year;
		$trad['dCocheFeries'] = "";
		while ($i <= $yearFin)
		{
			$feries = setHolidays($i);
			foreach ($feries as &$value) {
				$trad['dCocheFeries'] .= "setCheckboxesGrrName(document.getElementById('formulaireF'), true, '$value'); ";
			}
			unset($feries);
			$i++;
		}

		$debligne = 1;
		$inc = 0;
		$n = $begin_bookings;
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
			$trad['dCalendrier'] .= cal($month, $year, 2);
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
			$trad['dCalendrier'] .= "</tr>";
		}

	}    
	else if ((isset($_POST['define_holidays'])) && ($_POST['define_holidays'] == 'V'))
	{
		
		$trad['dTypeDefinition'] = "V";
		// traitement des jours de vacances (scolaires)
		if (isset($_POST['recordVacances']) && ($_POST['recordVacances'] == 'yes'))
		{
			// On met de côté toutes les dates
			$day_old = array();
			$res_old = grr_sql_query("SELECT day FROM ".TABLE_PREFIX."_calendrier_vacances");
			if ($res_old)
			{
				for ($i = 0; ($row_old = grr_sql_row($res_old, $i)); $i++)
					$day_old[$i] = $row_old[0];
			}
			// On vide la table ".TABLE_PREFIX."_calendrier_vacances
			$sql = "truncate table ".TABLE_PREFIX."_calendrier_vacances";
			if (grr_sql_command($sql) < 0)
				fatal_error(0, "<p>" . grr_sql_error());
			$result = 0;
			$end_bookings = Settings::get("end_bookings");
			$begin_bookings = Settings::get("begin_bookings");
			$month = date('m', $begin_bookings);
			$year = date('Y', $begin_bookings);
			$day = 1;
			$n = $begin_bookings;
			while ($n <= $end_bookings)
			{
				$daysInMonth = getDaysInMonth($month, $year);
				$day = 1;
				while ($day <= $daysInMonth)
				{
					$n = mktime(0, 0, 0, $month, $day, $year);
					if (isset($_POST[$n]))
					{
						// On enregistre la valeur dans ".TABLE_PREFIX."_calendrier_vacances
						$sql = "INSERT INTO ".TABLE_PREFIX."_calendrier_vacances set DAY='".$n."'";
						// echo "jour ".$n.'<br>';
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
		$begin_bookings = Settings::get("begin_bookings");
		$end_bookings = Settings::get("end_bookings");
		$month = utf8_encode(date('m', $begin_bookings));
		$year = date('Y', $begin_bookings);
		$yearFin = date('Y', $end_bookings);
		$i = $year;
		$trad['dCocheVacances'] = "";
		$zone = Settings::get("holidays_zone"); // en principe la zone est définie, au moins par défaut à A
		$schoolHoliday = array();
		$vacances = simplexml_load_file('../vacances.xml');
		$libelle = $vacances->libelles->children();
		$node = $vacances->calendrier->children();
		foreach ($node as $key => $value)
		{
		if ($value['libelle'] == $zone)
			{
				foreach ($value->vacances as $key => $value)
				{
					$y = date('Y', strtotime($value['debut'])); // année de début des vacances
					if (($y >= $year-1) && ($y <= $yearFin)){ // on n'étudie que les années pertinentes
						$t = strtotime($value['debut'])+86400; // la date du fichier est celle de la fin des cours
						$t_fin = strtotime($value['fin']);
						while ($t < $t_fin){ // la date du fichier est celle de la reprise des cours
							if (($t >= $begin_bookings) && ($t <= $end_bookings)) {
								$schoolHoliday[] = $t ; }
							$jour = date('d',$t);
							$mois = date('m',$t);
							$annee = date('Y',$t);
							$t = mktime(0,0,0,$mois,$jour+1,$annee);
						}
					}
				}
			}
		}

		foreach ($schoolHoliday as &$value) {
			$trad['dCocheVacances'] .= "setCheckboxesGrrName(document.getElementById('formulaireV'), true, '{$value}'); ";
		}
		unset($schoolHoliday);

		$debligne = 1;
		$inc = 0;
		$n = $begin_bookings;
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
			$trad['dCalendrier'] .= cal($month, $year, 3);
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
			$trad['dCalendrier'] .= "</tr>";
		}
	}
}

	echo $twig->render('admin_calend_vacances_feries.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings));
?>