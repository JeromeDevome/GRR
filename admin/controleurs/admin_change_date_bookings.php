<?php
/**
 * admin_change_date_bookings.php
 * interface de confirmation des changements de date de début et de fin de réservation
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2018-07-29 14:00$
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

$grr_script_name = "admin_change_date_bookings.php";

SecuAccess::CheckAccess(6, $back);

// les variables attendues et leur type
$form_vars = array(
    'valid' => 'int',
    'begin_bookings' => 'int',
    'end_bookings' => 'int'
);
// récupération des valeurs des variables passées en paramètres
foreach($form_vars as $var => $var_type)
    $$var = SecuChaine::GetFormVarSecure($var, $var_type);

if ($valid == 1)
{
	if (!Settings::set("begin_bookings", $begin_bookings))
		echo "Erreur lors de l'enregistrement de begin_bookings !<br />";
	else
	{
		$del = grr_sql_query("DELETE FROM ".TABLE_PREFIX."_entry WHERE (end_time < ".Settings::get('begin_bookings').")");
		$del = grr_sql_query("DELETE FROM ".TABLE_PREFIX."_repeat WHERE end_date < ".Settings::get("begin_bookings"));
		$del = grr_sql_query("DELETE FROM ".TABLE_PREFIX."_entry_moderate WHERE (end_time < ".Settings::get('begin_bookings').")");
		$del = grr_sql_query("DELETE FROM ".TABLE_PREFIX."_calendar WHERE DAY < ".Settings::get("begin_bookings"));
		$del = grr_sql_query("DELETE FROM ".TABLE_PREFIX."_calendrier_feries WHERE DAY < ".Settings::get("begin_bookings"));
		$del = grr_sql_query("DELETE FROM ".TABLE_PREFIX."_calendrier_vacances WHERE DAY < ".Settings::get("begin_bookings"));
	}
	if (!Settings::set("end_bookings", $end_bookings))
		echo "Erreur lors de l'enregistrement de end_bookings !<br />";
	else
	{
		$del = grr_sql_query("DELETE FROM ".TABLE_PREFIX."_entry WHERE start_time > ".Settings::get("end_bookings"));
		$del = grr_sql_query("DELETE FROM ".TABLE_PREFIX."_repeat WHERE start_time > ".Settings::get("end_bookings"));
		$del = grr_sql_query("DELETE FROM ".TABLE_PREFIX."_entry_moderate WHERE (start_time > ".Settings::get('end_bookings').")");
		$del = grr_sql_query("DELETE FROM ".TABLE_PREFIX."_calendar WHERE DAY > ".Settings::get("end_bookings"));
		$del = grr_sql_query("DELETE FROM ".TABLE_PREFIX."_calendrier_feries WHERE DAY > ".Settings::get("end_bookings"));
		$del = grr_sql_query("DELETE FROM ".TABLE_PREFIX."_calendrier_vacances WHERE DAY > ".Settings::get("end_bookings"));
	}
	header("Location: ?p=admin_page_reservation");

}

//data
$trad['dBegin_bookings']	= $begin_bookings;
$trad['dEnd_bookings']		= $end_bookings;
$trad['dPlageSelectionner']	= date("d/m/Y", $begin_bookings)." - ". date("d/m/Y", $end_bookings);

echo $twig->render($page.'.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings));
?>