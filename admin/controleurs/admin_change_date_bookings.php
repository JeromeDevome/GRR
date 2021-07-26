<?php
/**
 * admin_change_date_bookings.php
 * interface de confirmation des changements de date de début et de fin de réservation
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2018-07-29 14:00$
 * @author    Laurent Delineau & JeromeB
 * @copyright Copyright 2003-2020 Team DEVOME - JeromeB
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

check_access(6, $back);

if (isset($_GET['valid']) && ($_GET['valid'] == "yes"))
{
	if (!Settings::set("begin_bookings", $_GET['begin_bookings']))
		echo "Erreur lors de l'enregistrement de begin_bookings !<br />";
	else
	{
		$del = grr_sql_query("DELETE FROM ".TABLE_PREFIX."_entry WHERE (end_time < ".Settings::get('begin_bookings').")");
		$del = grr_sql_query("DELETE FROM ".TABLE_PREFIX."_repeat WHERE end_date < ".Settings::get("begin_bookings"));
		$del = grr_sql_query("DELETE FROM ".TABLE_PREFIX."_entry_moderate WHERE (end_time < ".Settings::get('begin_bookings').")");
		$del = grr_sql_query("DELETE FROM ".TABLE_PREFIX."_calendar WHERE DAY < ".Settings::get("begin_bookings"));
	}
	if (!Settings::set("end_bookings", $_GET['end_bookings']))
		echo "Erreur lors de l'enregistrement de end_bookings !<br />";
	else
	{
		$del = grr_sql_query("DELETE FROM ".TABLE_PREFIX."_entry WHERE start_time > ".Settings::get("end_bookings"));
		$del = grr_sql_query("DELETE FROM ".TABLE_PREFIX."_repeat WHERE start_time > ".Settings::get("end_bookings"));
		$del = grr_sql_query("DELETE FROM ".TABLE_PREFIX."_entry_moderate WHERE (start_time > ".Settings::get('end_bookings').")");
		$del = grr_sql_query("DELETE FROM ".TABLE_PREFIX."_calendar WHERE DAY > ".Settings::get("end_bookings"));
	}
	header("Location: ?p=admin_config");

}
else if (isset($_GET['valid']) && ($_GET['valid'] == "no"))
	header("Location: ?p=admin_config");


get_vocab_admin("admin_confirm_change_date_bookings");
get_vocab_admin("msg_del_bookings");
get_vocab_admin("save");
get_vocab_admin("cancel");

//data
$trad['dBegin_bookings']	= $_GET['begin_bookings'];
$trad['dEnd_bookings']		= $_GET['end_bookings'];
$trad['dPlageSelectionner']	= date("d/m/Y", $_GET['begin_bookings'])." - ". date("d/m/Y", $_GET['end_bookings']);

?>