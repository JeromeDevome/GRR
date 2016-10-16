<?php
/**
 * admin_corresp_statut.php
 * interface de gestion de la correspondance entre profil LDAP et statut GRR
 * Dernière modification : $Date: 2009-12-16 14:52:31 $
 * @author    Christian Daviau (GIP RECIA - Esco-Portail)
 * @author    Laurent Delineau <laurent.delineau@ac-poitiers.fr>
 * @copyright Copyright 2003-2008 Laurent Delineau
 * @link      http://www.gnu.org/licenses/licenses.html
 * @package   admin
 * @version   $Id: admin_corresp_statut.php,v 1.2 2009-12-16 14:52:31 grr Exp $
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
$grr_script_name = "admin_config_sso.php";
$back = '';
if (isset($_SERVER['HTTP_REFERER']))
	$back = htmlspecialchars($_SERVER['HTTP_REFERER']);
if (Settings::get("sso_ac_corr_profil_statut") != 'y')
{
	showAccessDenied($back);
	exit();
}
check_access(5, $back);
$themessage = str_replace("'" , "\\'" , get_vocab("confirmdel"));
$themessage2 = str_replace("'" , "\\'" , get_vocab("confirm_del"));
//
// Ajout d'une correspondance fonction/statut
//
$msg = "";
if ( isset($_GET['action_add']) && ($_GET['action_add'] == 'yes'))
{
	if (($_POST['codefonc'] != "") && ($_POST['libfonc'] != "") && ($_POST['statutgrr'] != ""))
	{
		$sql = "INSERT INTO ".TABLE_PREFIX."_correspondance_statut (code_fonction, libelle_fonction, statut_grr) VALUES ('".strtoupper(protect_data_sql($_POST['codefonc']))."', '".ucfirst(protect_data_sql($_POST['libfonc']))."','".$_POST['statutgrr']."')";
		if (grr_sql_command($sql) < 0)
			fatal_error(0, "<p>" . grr_sql_error());
		else
			$msg = get_vocab("message_records");
	}
	else
		$msg = get_vocab("champs_non_remplis");
}
//
// Modification d'une correspondance fonction/statut
//
if (isset($_GET['action_mod']) && ($_GET['action_mod'] = 'yes'))
{
	if (isset($_POST['idselect']))
	{
		$select = "statut".$_POST['idselect'];
		if (($_POST['idfonc'] != "") && ($_POST[$select] != ""))
		{
			$sql = "UPDATE ".TABLE_PREFIX."_correspondance_statut SET statut_grr = '".$_POST[$select]."' WHERE id='".$_POST['idfonc']."'";
			if (grr_sql_command($sql) < 0)
				fatal_error(0, "<p>" . grr_sql_error());
			else
				$msg = get_vocab("message_records");
		}
		else
			$msg = get_vocab("champs_non_remplis");
	}
}
//
// Suppression d'une correspondance fonction/statut
//
if ((isset($_GET['action_del'])) && ($_GET['js_confirmed'] == 1) && ($_GET['action_del'] = 'yes'))
{
	$sql = "DELETE FROM ".TABLE_PREFIX."_correspondance_statut WHERE id='".$_GET['id']."'";
	if (grr_sql_command($sql) < 0)
		fatal_error(0, "<p>" . grr_sql_error());
	else
		$msg = get_vocab("message_records");
}
$back = "";
if (isset($_SERVER['HTTP_REFERER']))
	$back = htmlspecialchars($_SERVER['HTTP_REFERER']);
//print the page header
print_header("", "", "", $type="with_session");
// Affichage de la colonne de gauche
include "admin_col_gauche.php";
?>
<script src="../js/functions.js" type="text/javascript"></script>
<?php
echo "<h2>".get_vocab('admin_corresp_statut.php')."</h2>";
echo get_vocab('admin_corresp_statut_desc');
echo "<br />\n";
echo "<br />\n";
$sql = "SELECT code_fonction, libelle_fonction, statut_grr, id FROM  ".TABLE_PREFIX."_correspondance_statut";
$res = grr_sql_query($sql);
$nb_lignes = grr_sql_count($res);
if ($nb_lignes == 0)
{
	// Si aucune ligne à afficher
    // fin de l'affichage de la colonne de droite
	echo get_vocab('aucune_correspondance');
}
else
{
	// S'il y a des lignes à afficher
	// Affichage du tableau
	echo "<div><table border=\"1\" cellpadding=\"3\" style=\"text-align:center;vertical-align:middle;\"><tr>\n";
	echo "<td><b>".get_vocab("code_fonction")."</b></td>\n";
	echo "<td><b>".get_vocab("libelle_fonction")."</b></td>\n";
	echo "<td><b>".get_vocab("statut_grr")."</b></td>\n";
	echo "<td><b>".get_vocab("statut_grr_modif")."</b></td>\n";
	echo "<td> </td>\n";
	echo "</tr>";
	if ($res)
	{
		for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
		{
			$codefonc = $row[0];
			$libfonc = $row[1];
			$statutgrr = $row[2];
			// Affichage des numéros et descriptions
			$col[$i][1] = $codefonc;
			$col[$i][2] = $libfonc;
			$col[$i][3] = $statutgrr;
			echo "<tr>\n";
			echo "<td>{$col[$i][1]}</td>\n";
			echo "<td>{$col[$i][2]}</td>\n";
			echo "<td>{$col[$i][3]}</td>\n";
			echo "<td><form action=\"admin_corresp_statut.php?action_mod=yes\" method=\"post\">\n<div><input type=\"hidden\" name=\"idfonc\" value=\"$row[3]\" />\n<input type=\"hidden\" name=\"idselect\" value=\"$i\" />\n<select name=\"statut$i\">\n<option value=\"visiteur\">".get_vocab("statut_visitor")."</option>\n<option value=\"utilisateur\">".get_vocab("statut_user")."</option>\n<option value=\"gestionnaire_utilisateur\">".get_vocab("statut_user_administrator")."</option>\n<option value=\"administrateur\">".get_vocab("statut_administrator")."</option>\n</select><br />\n<input type=\"submit\" value=\"".get_vocab("edit")."\" /></div></form></td>\n";
			echo "<td><a href=\"admin_corresp_statut.php?id=$row[3]&amp;action_del=yes\" onclick=\"return confirmlink(this, '$themessage2', '$themessage')\" >".get_vocab("delete");
			echo "</a></td>";
			// Fin de la ligne courante
			echo "</tr>";
		}
	}
	echo "</table></div>";
}
echo "<br /><hr /><br /><div style=\"text-align:center;\"><b>".get_vocab("ajout_correspondance_profil_statut")."</b>\n";
echo "<br /><form action=\"admin_corresp_statut.php?action_add=yes\" method=\"post\"><div>\n";
echo get_vocab("code_fonction").get_vocab("deux_points")."<input name=\"codefonc\" type=\"text\" size=\"6\" /><br />";
echo get_vocab("libelle_fonction").get_vocab("deux_points")."<input name=\"libfonc\" type=\"text\" size=\"25\" /><br />";
echo get_vocab("statut_grr").get_vocab("deux_points");
echo "<select name=\"statutgrr\">";
echo "<option value=\"visiteur\">".get_vocab("statut_visitor")."</option>\n";
echo "<option value=\"utilisateur\">".get_vocab("statut_user")."</option>\n";
echo "<option value=\"gestionnaire_utilisateur\">".get_vocab("statut_user_administrator")."</option>\n";
echo "<option value=\"administrateur\">".get_vocab("statut_administrator")."</option>\n";
echo "</select><br /><br />\n";
echo "<input type=\"submit\" value=\"".get_vocab("OK")."\" /></div></form></div>\n";
// fin de l'affichage de la colonne de droite
echo "</td></tr></table>\n";
// Affichage d'un pop-up
affiche_pop_up($msg,"admin");
?>
</body>
</html>
