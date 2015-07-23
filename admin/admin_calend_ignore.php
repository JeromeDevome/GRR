<?php
/**
 * admin_calend_ignore.php
 * Interface permettant la la réservation en bloc de journées entières
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2009-06-04 15:30:17 $
 * @author    Laurent Delineau <laurent.delineau@ac-poitiers.fr>
 * @copyright Copyright 2003-2008 Laurent Delineau
 * @link      http://www.gnu.org/licenses/licenses.html
 * @package   root
 * @version   $Id: admin_calend_ignore.php,v 1.8 2009-06-04 15:30:17 grr Exp $
 * @filesource
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GRR is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GRR; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

include "../include/admin.inc.php";
$grr_script_name = "admin_calend_ignore.php";
$back = '';
if (isset($_SERVER['HTTP_REFERER']))
	$back = htmlspecialchars($_SERVER['HTTP_REFERER']);
check_access(6, $back);
# print the page header
print_header("", "", "", $type="with_session");
// Affichage de la colonne de gauche
include "admin_col_gauche.php";
echo "<h2>".get_vocab('calendrier_des_jours_hors_reservation')."</h2>\n";
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
	$month = strftime("%m", Settings::get("begin_bookings"));
	$year = strftime("%Y", Settings::get("begin_bookings"));
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
							$result += grrDelEntryInConflict($row[0], $starttime, $endtime, 0, 0, 1);
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
echo "\n<p>".get_vocab("les_journees_cochees_sont_ignorees")."</p>";
echo "\n<table cellpadding=\"3\">\n";
$basetime = mktime(12, 0, 0, 6, 11 + $weekstarts, 2000);
for ($i = 0; $i < 7; $i++)
{
	$show = $basetime + ($i * 24 * 60 * 60);
	$lday = utf8_strftime('%A',$show);
	echo "<tr>\n";
	echo "<td><span class='small'><a href='admin_calend_ignore.php' onclick=\"setCheckboxesGrr(document.getElementById('formulaire'), true, '$lday' ); return false;\">".get_vocab("check_all_the").$lday."s</a></span></td>\n";
	echo "<td><span class='small'><a href='admin_calend_ignore.php' onclick=\"setCheckboxesGrr(document.getElementById('formulaire'), false, '$lday' ); return false;\">".get_vocab("uncheck_all_the").$lday."s</a></span></td>\n";
	echo "</tr>\n";
}
echo "<tr>\n<td><span class='small'><a href='admin_calend_ignore.php' onclick=\"setCheckboxesGrr(document.getElementById('formulaire'), false, 'all'); return false;\">".get_vocab("uncheck_all_")."</a></span></td>\n";
echo "<td> </td></tr>\n";
echo "</table>\n";
echo "<form action=\"admin_calend_ignore.php\" method=\"post\" id=\"formulaire\">\n";
echo "<table cellspacing=\"20\">\n";
$n = Settings::get("begin_bookings");
$end_bookings = Settings::get("end_bookings");
$debligne = 1;
$month = utf8_encode(strftime("%m", Settings::get("begin_bookings")));
$year = strftime("%Y", Settings::get("begin_bookings"));
$inc = 0;
while ($n <= $end_bookings)
{
	if ($debligne == 1)
	{
		echo "<tr>\n";
		$inc = 0;
		$debligne = 0;
	}
	$inc++;
	echo "<td>\n";
	echo cal($month, $year);
	echo "</td>";
	if ($inc == 3)
	{
		echo "</tr>";
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
		echo "<td> </td>\n";
		$k++;
	}
	// while
	echo "</tr>";
}
echo "</table>";
echo "<div id=\"fixe\" style=\"text-align:center;\"><input class=\"btn btn-primary\" type=\"submit\" name=\"".get_vocab('save')."\" value=\"".get_vocab("save")."\" />\n";
echo "<input class=\"btn btn-primary\" type=\"hidden\" name=\"record\" value=\"yes\" />\n";
echo "</div>";
echo "</form>";
// fin de l'affichage de la colonne de droite
echo "</td></tr></table>\n";
?>
</body>
</html>
