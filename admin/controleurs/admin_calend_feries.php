<?php
/**
 * admin_calend_feries.php
 * Interface permettant la définiton des jours fériés
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2026-08-28 19:48$
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
    'p_submitCalend' => 'int',
    'From_year' => 'int'
);
// récupération des valeurs des variables passées en paramètres
foreach($form_vars as $var => $var_type)
    $$var = SecuChaine::GetFormVarSecure($var, $var_type);


if(empty($From_year)){
	$From_year = date('Y');
}

$trad['TitrePage']	= $trad['admin_calend_feries'];

// premier test : l'affichage des fériés est-il activé ?
if (Settings::get("show_feries") == 1)
{
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
}

/** Actions **/
	/* Enregistrement */
	if ((Settings::get("show_feries") == 1) && ($p_submitCalend == 1))
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
		$d['enregistrement'] = 1;
	}

/** Affichage de la page **/
	$d['cocheFeries']	= "";
	$d['calendrier']	= "";

	$month	= date('n', $begin_bookings);
	$n		= $begin_bookings;

	// Génération du code javascript pour cocher les jours fériés
	$feries = setHolidays($From_year);
	foreach ($feries as &$value) {
		$d['cocheFeries'] .= "setCheckboxesGrrName(document.getElementById('formulaireF'), true, '$value'); ";
	}
	unset($feries);

	// Affichage des calendriers
	while ($n <= $end_bookings)
	{
		$d['calendrier'] .= "<div class=\"col-auto\">\n";
		$d['calendrier'] .= cal($month, $From_year, 2);
		$d['calendrier'] .= "</div>";
		$month++;
		$n = mktime(0,0,0,$month,1,$From_year);
	}


echo $twig->render($page.'.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings));
?>