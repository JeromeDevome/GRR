<?php
/**
 * admin_calend_feries.php
 * Interface permettant la définiton des jours fériés
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2026-06-06 16:00$
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

$grr_script_name = "admin_calend_feries.php";

SecuAccess::CheckAccess(6, $back);

// les variables attendues et leur type
$form_vars = array(
    'submitCalend' => 'int',
    'From_year' => 'int'
);
// récupération des valeurs des variables passées en paramètres
foreach($form_vars as $var => $var_type)
    $$var = SecuChaine::GetFormVarSecure($var, $var_type);


// premier test : l'affichage des fériés est-il activé ?
if (Settings::get("show_feries") == 1)
{

	$annee = isset($From_year) ? $From_year : (isset($From_year) ? intval($_From_year) : date('Y'));

	if (!isset($From_year))
		$From_year = $annee;

	$d['liste_annees']	= genDateSelectorForm("From_", "", "", $From_year,"");
	$d['From_year']		= $From_year;

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

	/** Traitement formulaire des jours fériés **/
	if ($submitCalend == 1)
	{
		
		// On met de côté toutes les dates
		$day_old = array();
		$res_old = grr_sql_query("SELECT day FROM ".TABLE_PREFIX."_calendrier_feries");
		if ($res_old)
		{
			for ($i = 0; ($row_old = grr_sql_row($res_old, $i)); $i++)
				$day_old[$i] = $row_old[0];
		}
		// On supprime de la table ".TABLE_PREFIX."_calendrier_feries
		$sql = "DELETE FROM ".TABLE_PREFIX."_calendrier_feries WHERE DAY >= '".$begin_bookings."' AND DAY <= '".$end_bookings."'";
		if (grr_sql_command($sql) < 0)
			fatal_error(0, "<p>" . grr_sql_error());

		$result = 0;
		$month	= date('m', $begin_bookings );
		$year	= date('Y', $begin_bookings );
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
					// On enregistre la valeur dans ".TABLE_PREFIX."_calendrier_feries
					$sql = "INSERT INTO ".TABLE_PREFIX."_calendrier_feries set DAY='".$n."'";
					if (grr_sql_command($sql) < 0)
						fatal_error(0, "<p>" . grr_sql_error());
				}
				$day++;
			}
			$month++;
		}
	}

	/** Chargemennt de l'affichage de la page **/
	$d['cocheFeries']	= "";
	$d['calendrier']	= "";

	$month	= date('n', $begin_bookings);
	$n		= $begin_bookings;

	// Génération du code javascript pour cocher les jours fériés
	$feries = setHolidays($annee);
	foreach ($feries as &$value) {
		$d['cocheFeries'] .= "setCheckboxesGrrName(document.getElementById('formulaireF'), true, '$value'); ";
	}
	unset($feries);

	// Affichage des calendriers
	while ($n <= $end_bookings)
	{
		$d['calendrier'] .= "<div class=\"col-auto\">\n";
		$d['calendrier'] .= cal($month, $annee, 2);
		$d['calendrier'] .= "</div>";
		$month++;
		$n = mktime(0,0,0,$month,1,$annee);
	}
}

echo $twig->render($page.'.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings));
?>