<?php
/**
 * admin_config_calend1.php
 * Interface permettant à l'administrateur la configuration des paramètres pour le module Jours Cycles
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2009-04-09 14:52:31 $
 * @author    Laurent Delineau <laurent.delineau@ac-poitiers.fr>
 * @copyright Copyright 2003-2008 Laurent Delineau
 * @link      http://www.gnu.org/licenses/licenses.html
 * @package   root
 * @version   $Id: admin_config_calend1.php,v 1.4 2009-04-09 14:52:31 grr Exp $
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
if (!Settings::load())
	die("Erreur chargement settings");
# print the page header
print_header("", "", "", $type="with_session");
// Affichage de la colonne de gauche
include "admin_col_gauche.php";
// Affichage du tableau de choix des sous-configuration
$grr_script_name = "admin_calend_jour_cycle.php";
include "../include/admin_calend_jour_cycle.inc.php";
// Met à jour dans la BD le nombre de jours par cycle
if (isset($_GET['nombreJours']))
{
	if (!Settings::set("nombre_jours_Jours/Cycles", $_GET['nombreJours']))
		echo "Erreur lors de l'enregistrement de nombre_jours_Jours/Cycles ! <br />";
}
// Met à jour dans la BD le premier jour du premier cycle
if (isset($_GET['jourDebut']))
{
	if (!Settings::set("jour_debut_Jours/Cycles", $_GET['jourDebut']))
		echo "Erreur lors de l'enregistrement de jour_debut_Jours/Cycles ! <br />";
}
//
// Configurations du nombre de jours par Jours/Cycles et du premier jour du premier Jours/Cycles
//******************************
//
echo "<h3>".get_vocab("titre_config_Jours/Cycles")."</h3>\n";
echo "<form action=\"./admin_calend_jour_cycle.php\"  method=\"get\" style=\"width: 100%;\" onsubmit=\"return verifierJoursCycles(false);\">\n";
echo "<p>".get_vocab("explication_Jours_Cycles1");
echo "<br />".get_vocab("explication_Jours_Cycles2");
?>
<br /><br />
</p>
<table border="1" cellpadding="5" cellspacing="1">
	<tr>
		<td>
			<?php echo get_vocab("nombre_jours_Jours/Cycles").get_vocab("deux_points"); ?>
		</td>
		<td>
			<!-- Pour sélectionner le nombre de jours par Cycle  -->
			<?php
			echo "<select name='nombreJours' id='nombreJours'>\n";
			for($i = 1; $i < 21; $i++)
			{
				if ($i == Settings::get("nombre_jours_Jours/Cycles"))
					echo "<option selected=\"selected\">".$i."</option>\n";
				else
					echo "<option>".$i."</option>\n";
			}
			echo "</select>\n";
			?>
		</td>
	</tr>
	<!-- Pour sélectionner le jour_cycle qui débutera le premier Jours/Cycles  -->
	<tr>
		<td>
			<?php
			echo get_vocab("debut_Jours/Cycles").get_vocab("deux_points")."<br /><i>".get_vocab("explication_debut_Jours_Cycles")."</i>"; 
			?>
		</td>
		<td>
			<?php
			echo "<select name='jourDebut' id='jourDebut'>";
			for($i = 1; $i < 21; $i++)
			{
				if ($i == Settings::get("jour_debut_Jours/Cycles"))
					echo "<option selected=\"selected\">".$i."</option>\n";
				else
					echo "<option>".$i."</option>\n";
			}
			?>
		</select>
	</td>
</tr>
</table>
<?php
echo "<div id=\"fixe\" style=\"text-align:center;\"><input type=\"submit\" value=\"".get_vocab("save")."\" style=\"font-variant: small-caps;\"/></div>\n";
echo "<div><input type=\"hidden\" value=\"1\" name=\"page_calend\" /></div>\n";
echo "</form>";
// fin de l'affichage de la colonne de droite
echo "</td></tr></table></body>
</html>";
?>
