<?php
/**
 * admin_config_calend2.php
 * interface permettant la configuration des jours-cycles (étape 2)
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2021-03-13 11:57$
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
$grr_script_name = "admin_config_calend2.php";

$back = (isset($_SERVER['HTTP_REFERER']))? htmlspecialchars_decode($_SERVER['HTTP_REFERER'], ENT_QUOTES) : "./admin_accueil.php" ;
check_access(6, $back);
if (isset($_POST['record']) && ($_POST['record'] == 'yes'))
{
	// On vide la table
	$sql = "truncate table ".TABLE_PREFIX."_calendrier_jours_cycle";
	if (grr_sql_command($sql) < 0)
		fatal_error(1, "<p>" . grr_sql_error());
	//$result = 0; // ce $result n'est pas utilisé ?
	$end_bookings = Settings::get("end_bookings");
	$n = Settings::get("begin_bookings");
	$month = strftime("%m", Settings::get("begin_bookings"));
	$year = strftime("%Y", Settings::get("begin_bookings"));
	$day = 1;
	// Pour aller chercher le Jour cycle qui débutera le premier cycle de jours
	$m = Settings::get("jour_debut_Jours_Cycles");
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
				 // On efface toutes les résa en conflit
				$sql = "select id from ".TABLE_PREFIX."_room";
				$res = grr_sql_query($sql);
				if ($res)
				{
					for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
						//$result += grrDelEntryInConflict($row[0], $starttime, $endtime, 0, 0, 1);
						grrDelEntryInConflict($row[0], $starttime, $endtime, 0, 0, 1);
				}
				// On enregistre la valeur
				$m = cree_calendrier_date_valide($n,$m);
			}
			$day++;
		}
		$month++;
		if ($month == 13)
		{
			$year++;
			$month = 1;
		}
	}
}
// code HTML
# print the page header
start_page_w_header("", "", "", $type="with_session");
// Affichage de la colonne de gauche
include "admin_col_gauche2.php";
echo "<div class='col-md-9 col-sm-8 col-xs-12'>";
// Affichage du menu de choix des sous-configurations pour les Jours_Cycles (Créer et voir calendrier Jours/Cycle)
include "../include/admin_calend_jour_cycle.inc.php";
echo "<h3>".get_vocab('calendrier_jours/cycles')."</h3>";
echo "<p>".get_vocab("les_journees_cochees_sont_valides").get_vocab("deux_points");
echo "<br />* ".get_vocab("nombre_jours_Jours_Cycles").get_vocab("deux_points").Settings::get("nombre_jours_Jours_Cycles");
echo "<br />* ".get_vocab("debut_Jours_Cycles").get_vocab("deux_points").Settings::get("jour_debut_Jours_Cycles");
echo "<br /><br />".get_vocab("explication_Jours_Cycles2")."</p>";
echo "<table class='table-noborder'>\n";
$basetime = mktime(12, 0, 0, 6, 11 + $weekstarts, 2000);
for ($i = 0; $i < 7; $i++)
{
	$show = $basetime + ($i * 24 * 60 * 60);
	$lday = utf8_strftime('%A',$show);
	echo "<tr>\n";
	echo "<td><span class='small'><a href='admin_calend_jour_cycle.php' onclick=\"setCheckboxesGrr('formulaire', true, '$lday' ); return false;\">".get_vocab("check_all_the").$lday."s</a></span></td>\n";
	echo "<td><span class='small'><a href='admin_calend_jour_cycle.php' onclick=\"setCheckboxesGrr('formulaire', false, '$lday' ); return false;\">".get_vocab("uncheck_all_the").$lday."s</a></span></td>\n";
	echo "</tr>\n";
}
echo "<tr>\n<td><span class='small'><a href='admin_calend_jour_cycle.php' onclick=\"setCheckboxesGrr('formulaire', false, 'all'); return false;\">".get_vocab("uncheck_all_")."</a></span></td>\n";
echo "<td></td></tr>\n";
echo "</table>\n";
echo "<form action=\"admin_calend_jour_cycle.php?page_calend=2\" method=\"post\" id=\"formulaire\" name=\"formulaire\">\n";
echo "<table>\n";
$n = Settings::get("begin_bookings");
$end_bookings = Settings::get("end_bookings");
$debligne = 1;
$month = strftime("%m", Settings::get("begin_bookings"));
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
	echo cal($month, $year, 1);
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
	$n = mktime(0, 0, 0, $month, 1, $year);
}
if ($inc < 3)
{
	$k=$inc;
	while ($k < 3)
	{
		echo "<td> </td>\n";
		$k++;
	} // while
	echo "</tr>";
}
echo "</table>";
echo "<div id=\"fixe\"><input type=\"submit\" onclick=\"return confirmlink(this, '".AddSlashes(get_vocab("avertissement_effacement"))."', '".get_vocab("admin_config_calend1.php")."')\" name=\"ok\" value=\"".get_vocab("save")."\" />\n";
echo "<input type=\"hidden\" name=\"record\" value=\"yes\" /></div>\n";
echo "</form>";
// fin de l'affichage de la colonne de droite
echo "</div>";
// et de la page
end_page();
?>