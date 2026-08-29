<?php
/**
 * admin_calend_ignore.php
 * Interface permettant la la réservation en bloc de journées entières
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2026-08-28 19:48$
 * @author    Laurent Delineau & JeromeB
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


$grr_script_name = "admin_calend_ignore.php";

// Accès à la page
SecuAccess::CheckAccess(6, $back);

// les variables attendues et leur type
$form_vars = array(
    'From_year' => 'int', // année de l'affichage du calendrier
	'p_submit' => 'int', // 1 : enregistrement des jours cochés
);
// récupération des valeurs des variables passées en paramètres
foreach($form_vars as $var => $var_type)
    $$var = SecuChaine::GetFormVarSecure($var, $var_type);


if(empty($From_year)){
	$From_year = date('Y');
}

$trad['TitrePage']	= $trad['calendrier_des_jours_hors_reservation'];
$d['liste_annees']	= genDateSelectorForm("From_", "", "", $From_year,"");
$d['From_year']		= $From_year;
$premier_jour_annee = mktime(0, 0, 0, 1, 1, $From_year);
$dernier_jour_annee = mktime(0, 0, 0, 12, 31, $From_year);
$begin_bookings		= Settings::get("begin_bookings");
$end_bookings		= Settings::get("end_bookings");

if($begin_bookings < $premier_jour_annee){
	$begin_bookings = $premier_jour_annee;
}

if($end_bookings > $dernier_jour_annee){
	$end_bookings = $dernier_jour_annee;
}

/** Actions **/
	/* Enregistrement */
	if (isset($submit) && $submit == 1)
	{
		// On met de côté toutes les dates
		$day_old = array();
		$res_old = grr_sql_query("SELECT day FROM ".TABLE_PREFIX."_calendar WHERE DAY >= '".$begin_bookings."' AND DAY <= '".$end_bookings."'");
		if ($res_old)
		{
			for ($i = 0; ($row_old = grr_sql_row($res_old, $i)); $i++)
				$day_old[$i] = $row_old[0];
		}
		// On supprime de la table ".TABLE_PREFIX."_calendar
		$sql = "DELETE FROM ".TABLE_PREFIX."_calendar WHERE DAY >= '".$begin_bookings."' AND DAY <= '".$end_bookings."'";
		if (grr_sql_command($sql) < 0)
			fatal_error(0, "<p>" . grr_sql_error());
		$result = 0;

		$n = $begin_bookings;
		$month = date('m', $begin_bookings);
		$year = date('Y', $begin_bookings);
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

		$d['enregistrement'] = 1;
	}

/** Affichage de la page **/
	// Jours à cocher / décocher dans le calendrier
	$basetime = mktime(12, 0, 0, 6, 11 + $weekstarts, 2000);
	$jourssemaines = array();

	for ($i = 0; $i < 7; $i++)
	{
		$show = $basetime + ($i * 24 * 60 * 60);
		$jourssemaines[] = utf8_strftime('%A',$show);
	}

	// Les jours fériés si activés
	if (Settings::get("show_feries") == 1){
		// définir les jours fériés
		$req = "SELECT * FROM ".TABLE_PREFIX."_calendrier_feries";
		$ans = grr_sql_query($req);
		$feries = array();
		foreach($ans as $val){
			$feries[] = $val['DAY'];
		}
		$d['Cocheferies'] = "";
		$d['Decocheferies'] = "";
		foreach ($feries as &$value) {
			$d['Cocheferies'] .= "setCheckboxesGrrName(document.getElementById('formulaire'), true, '{$value}'); ";
			$d['Decocheferies'] .= "setCheckboxesGrrName(document.getElementById('formulaire'), false, '{$value}'); ";
		}
		unset($feries);
	}

	// Les vacances si activés
	if (Settings::get("show_holidays") == 1){
		$req = "SELECT * FROM ".TABLE_PREFIX."_calendrier_vacances";
		$ans = grr_sql_query($req);
		$vacances = array();
		foreach($ans as $val){
			$vacances[] = $val['DAY'];
		}
		$d['CocheVacances'] = "";
		$d['DecocheVacances'] = "";
		foreach ($vacances as &$value) {
			$d['CocheVacances'] .= "setCheckboxesGrrName(document.getElementById('formulaire'), true, '{$value}'); ";
			$d['DecocheVacances'] .= "setCheckboxesGrrName(document.getElementById('formulaire'), false, '{$value}'); ";
		}
		unset($vacances);
	}


	// Affichage du calendrier
	$d['calendrier']	= "";
	$month				= date("m",$begin_bookings);
	$year				= date("Y", $begin_bookings);
	$n					= $begin_bookings;

	while ($n <= $end_bookings)
	{
		$d['calendrier'] .= "<div class=\"col-auto\">\n";
		$d['calendrier'] .= cal($month, $year, 1);
		$d['calendrier'] .= "</div>";
		$month++;
		$n = mktime(0,0,0,$month,1,$year);
	}

	echo $twig->render('admin_calend_ignore.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings, 'jourssemaines' => $jourssemaines));
?>