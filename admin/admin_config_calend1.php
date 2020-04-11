<?php
/**
 * admin_config_calend1.php
 * Interface permettant à l'administrateur la configuration des paramètres pour le module Jours Cycles
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2020-04-10 14:35$
 * @author    Laurent Delineau & JeromeB & Yan Naessens
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
$grr_script_name = "admin_calend_jour_cycle.php";

if (!Settings::load())
	die("Erreur chargement settings");

// Met à jour dans la BD le nombre de jours par cycle
if (isset($_GET['nombreJours']))
{
	if (!Settings::set("nombre_jours_Jours_Cycles", $_GET['nombreJours']))
		echo "Erreur lors de l'enregistrement de nombre_jours_Jours_Cycles ! <br />";
}
// Met à jour dans la BD le premier jour du premier cycle
if (isset($_GET['jourDebut']))
{
	if (!Settings::set("jour_debut_Jours_Cycles", $_GET['jourDebut']))
		echo "Erreur lors de l'enregistrement de jour_debut_Jours_Cycles ! <br />";
}
// Configurations du nombre de jours par Jours_Cycles et du premier jour du premier Jours_Cycles
//
# print the page header
start_page_w_header("", "", "", $type="with_session");
// Affichage de la colonne de gauche
include "admin_col_gauche2.php";
// colonne de droite
echo "<div class='col-md-9 col-sm-8 col-xs-12'>";
// Affichage du menu de choix des sous-configurations
include "../include/admin_calend_jour_cycle.inc.php";
echo "<h3>".get_vocab("titre_config_Jours_Cycles")."</h3>\n";
echo "<form action=\"./admin_calend_jour_cycle.php\"  method=\"get\" style=\"width: 100%;\" onsubmit=\"return verifierJoursCycles(false);\">\n";
echo "<p>".get_vocab("explication_Jours_Cycles1");
echo "<br />".get_vocab("explication_Jours_Cycles2");
echo '<br /><br /></p>';
echo '<table>';
echo	'<tr>
		<td>';
		echo get_vocab("nombre_jours_Jours_Cycles").get_vocab("deux_points");
echo 	'</td>
		<td>';
//			<!-- Pour sélectionner le nombre de jours par Cycle  -->
			echo "<select name='nombreJours' id='nombreJours'>\n";
			for($i = 1; $i < 21; $i++)
			{
				if ($i == Settings::get("nombre_jours_Jours_Cycles"))
					echo "<option selected=\"selected\">".$i."</option>\n";
				else
					echo "<option>".$i."</option>\n";
			}
			echo "</select>\n";
		echo "</td>
	</tr>";
	// Pour sélectionner le jour_cycle qui débutera le premier Jours_Cycles
	echo "<tr>
		<td>";
			echo get_vocab("debut_Jours_Cycles").get_vocab("deux_points")."<br /><i>".get_vocab("explication_debut_Jours_Cycles")."</i>"; 
		echo "</td>
		<td class='CC'>";
			echo "<select name='jourDebut' id='jourDebut'>";
			for($i = 1; $i < 21; $i++)
			{
				if ($i == Settings::get("jour_debut_Jours_Cycles"))
					echo "<option selected=\"selected\">".$i."</option>\n";
				else
					echo "<option>".$i."</option>\n";
			}
	echo "</select>
	</td>
</tr>
</table>";
echo "<div id=\"fixe\" style=\"text-align:center;\"><input type=\"submit\" value=\"".get_vocab("save")."\" style=\"font-variant: small-caps;\"/></div>\n";
echo "<div><input type=\"hidden\" value=\"1\" name=\"page_calend\" /></div>\n";
echo "</form>";
// fin de l'affichage de la colonne de droite et de la page
echo "</div></section></body></html>";
?>
