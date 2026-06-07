<?php
/**
 * admin_calend_vacances.php
 * Interface permettant la définiton des jours fériés ou de vacances
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2026-06-07 11:30$
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

$grr_script_name = "admin_calend_vacances.php";

SecuAccess::CheckAccess(6, $back);

// les variables attendues et leur type
$form_vars = array(
    'submitCalend' => 'int',
    'From_year' => 'int'
);
// récupération des valeurs des variables passées en paramètres
foreach($form_vars as $var => $var_type)
    $$var = SecuChaine::GetFormVarSecure($var, $var_type);


// premier test : l'affichage des vacances est-il activé ?
if (Settings::get("show_holidays") == 1)
{

	$annee = isset($From_year) ? $From_year : (isset($From_year) ? intval($From_year) : date('Y'));

	if (!isset($From_year))
		$From_year = $annee;

	$d['liste_annees'] = genDateSelectorForm("From_", "", "", $From_year,"");
	$d['From_year'] = $From_year;

	$premier_jour_annee = mktime(0, 0, 0, 1, 1, $annee);
	$dernier_jour_annee = mktime(0, 0, 0, 12, 31, $annee);
	$begin_bookings		= Settings::get("begin_bookings");
	$end_bookings		= Settings::get("end_bookings");

	if($begin_bookings < $premier_jour_annee){
		$begin_bookings = $premier_jour_annee;
	}

	if($end_bookings > $dernier_jour_annee){
		$end_bookings = $dernier_jour_annee;
	}

	/** Traitement formulaire des jours de vacances **/
	if ($submitCalend == 1)
	{

		// On met de côté toutes les dates
		$day_old = array();
		$res_old = grr_sql_query("SELECT day FROM ".TABLE_PREFIX."_calendrier_vacances");
		if ($res_old)
		{
			for ($i = 0; ($row_old = grr_sql_row($res_old, $i)); $i++)
				$day_old[$i] = $row_old[0];
		}
		// On supprime de la table ".TABLE_PREFIX."_calendrier_vacances
		$sql = "DELETE FROM ".TABLE_PREFIX."_calendrier_vacances WHERE DAY >= '".$begin_bookings."' AND DAY <= '".$end_bookings."'";
		if (grr_sql_command($sql) < 0)
			fatal_error(0, "<p>" . grr_sql_error());

		$result	= 0;
		$month	= date('m', $begin_bookings);
		$year	= date('Y', $begin_bookings);
		$day	= 1;
		$n		= $begin_bookings;
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
					if (grr_sql_command($sql) < 0)
						fatal_error(0, "<p>" . grr_sql_error());
				}
				$day++;
			}
			$month++;
		}
	}

	/** Chargemennt de l'affichage de la page **/
	$d['cocheVacances']	= "";
	$d['calendrier'] 	= "";

	$month	= date('n', $begin_bookings);
	$n		= $begin_bookings;

	// Génération du code javascript pour cocher les jours de vacances
	$zone = Settings::get("holidays_zone"); // en principe la zone est définie, au moins par défaut à A
	$schoolHoliday = array();
	$vacances = simplexml_load_file('../include/vacances.xml');
	$libelle = $vacances->libelles->children();
	$node = $vacances->calendrier->children();
	foreach ($node as $key => $value)
	{
	if ($value['libelle'] == $zone)
		{
			foreach ($value->vacances as $key => $value)
			{
				$y = date('Y', strtotime($value['debut'])); // année de début des vacances
				if (($y >= $year-1) && ($y <= $annee)){ // on n'étudie que les années pertinentes
					$t = strtotime($value['debut'])+86400; // la date du fichier est celle de la fin des cours
					$t_fin = strtotime($value['fin']);
					while ($t < $t_fin){ // la date du fichier est celle de la reprise des cours
						if (($t >= $begin_bookings) && ($t <= $end_bookings)) {
							$schoolHoliday[] = $t ; }
						$jour = date('d',$t);
						$mois = date('m',$t);
						$anneeF = date('Y',$t);
						$t = mktime(0,0,0,$mois,$jour+1,$anneeF);
					}
				}
			}
		}
	}

	foreach ($schoolHoliday as &$value) {
		$d['cocheVacances'] .= "setCheckboxesGrrName(document.getElementById('formulaireV'), true, '{$value}'); ";
	}
	unset($schoolHoliday);

	// Affichage des calendriers
	while ($n <= $end_bookings)
	{
		$d['calendrier'] .= "<div class=\"col-auto\">\n";
		$d['calendrier'] .= cal($month, $annee, 3);
		$d['calendrier'] .= "</div>";
		$month++;
		$n = mktime(0,0,0,$month,1,$annee);
	}
}

echo $twig->render($page.'.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings));
?>