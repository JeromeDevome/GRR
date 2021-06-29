
<?php
/**
 * admin_confirm_change_date_bookings.php
 * interface de confirmation des changements de date de début et de fin de réservation
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2021-03-13 11:52$
 * @author    Laurent Delineau & JeromeB & Yan Naessens
 * @copyright Copyright 2003-2021 Team DEVOME - JeromeB
 * @link      http://www.gnu.org/licenses/licenses.html
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */
$grr_script_name = "admin_confirm_change_date_bookings.php";

include "../include/admin.inc.php";

$back = (isset($_SERVER['HTTP_REFERER']))? htmlspecialchars_decode($_SERVER['HTTP_REFERER'], ENT_QUOTES) : "./admin_accueil.php" ;
unset($display);
$display = isset($_GET["display"]) ? $_GET["display"] : NULL;
$day   = date("d");
$month = date("m");
$year  = date("Y");
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
	header("Location: ./admin_config1.php");

}
else if (isset($_GET['valid']) && ($_GET['valid'] == "no"))
	header("Location: ./admin_config1.php");
# print the page header
start_page_w_header("", "", "", $type="with_session");
echo "<div class='container'>";
echo "<h2>".get_vocab('admin_confirm_change_date_bookings.php')."</h2>";
echo "<p>".get_vocab("msg_del_bookings")."</p>";
?>
<form action="admin_confirm_change_date_bookings.php" method='get'>
	<div>
		<input class="btn btn-primary" type="submit" value="<?php echo get_vocab("save");?>" />
		<input type="hidden" name="valid" value="yes" />
		<input type="hidden" name="begin_bookings" value=" <?php echo $_GET['begin_bookings']; ?>" />
		<input type="hidden" name="end_bookings" value=" <?php echo $_GET['end_bookings']; ?>" />
	</div>
</form>

<form action="admin_confirm_change_date_bookings.php" method='get'>
	<div>
		<input class="btn btn-primary" type="submit" value="<?php echo get_vocab("cancel");?>" />
		<input type="hidden" name="valid" value="no" />
	</div>
</form>
</div>
</section>
</body>
</html>
