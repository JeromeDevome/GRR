<?php
/**
 * admin_config5.php
 * Interface permettant à l'administrateur la configuration des paramètres pour le module Jours Cycles
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2009-04-14 12:59:17 $
 * @author    Laurent Delineau <laurent.delineau@ac-poitiers.fr>
 * @copyright Copyright 2003-2008 Laurent Delineau
 * @link      http://www.gnu.org/licenses/licenses.html
 * @package   root
 * @version   $Id: admin_config5.php,v 1.8 2009-04-14 12:59:17 grr Exp $
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
// Met à jour dans la BD le champ qui détermine si les fonctionnalités Jours/Cycles sont activées ou désactivées
if (isset($_GET['jours_cycles']))
{
	if (!Settings::set("jours_cycles_actif", $_GET['jours_cycles']))
		echo "Erreur lors de l'enregistrement de jours_cycles_actif ! <br />";
}
// Met à jour dans la BD du champ qui détermine si la fonctionnalité "multisite" est activée ou non
if (isset($_GET['module_multisite']))
{
	if (!Settings::set("module_multisite", $_GET['module_multisite']))
		echo "Erreur lors de l'enregistrement de module_multisite ! <br />";
	else
	{
		if ($_GET['module_multisite'] == 'Oui')
		{
			// On crée un site par défaut s'il n'en existe pas
			$id_site = grr_sql_query1("SELECT min(id) FROM ".TABLE_PREFIX."_site");
			if ($id_site == -1)
			{
				$sql="INSERT INTO ".TABLE_PREFIX."_site
				SET sitecode='1', sitename='site par defaut'";
				if (grr_sql_command($sql) < 0)
					fatal_error(0,'<p>'.grr_sql_error().'</p>');
				$id_site = mysqli_insert_id($GLOBALS['db_c']);
			}
						// On affecte tous les domaines à un site.
			$sql = "SELECT id FROM ".TABLE_PREFIX."_area";
			$res = grr_sql_query($sql);
			if ($res)
			{
				for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
				{
					// l'area est-elle déjà affectée à un site ?
					$test_site = grr_sql_query1("SELECT count(id_area) FROM ".TABLE_PREFIX."_j_site_area WHERE id_area='".$row[0]."'");
					if ($test_site == 0)
					{
						$sql="INSERT INTO ".TABLE_PREFIX."_j_site_area SET id_site='".$id_site."', id_area='".$row[0]."'";
						if (grr_sql_command($sql) < 0)
							fatal_error(0,'<p>'.grr_sql_error().'</p>');
					}
				}
			}
		}
	}
}
// Export XML
if (isset($_GET['export_xml']))
{
	if (!Settings::set("export_xml_actif", $_GET['export_xml']))
		echo "Erreur lors de l'enregistrement de l'export XML ! <br />";
}
// Export XML Plus
if (isset($_GET['export_xml_plus']))
{
	if (!Settings::set("export_xml_plus_actif", $_GET['export_xml_plus']))
		echo "Erreur lors de l'enregistrement de l'export XML Plus ! <br />";
}
// use_fckeditor
if (isset($_GET['use_fckeditor']))
{
	if (!Settings::set("use_fckeditor", $_GET['use_fckeditor']))
	{
		echo "Erreur lors de l'enregistrement de use_fckeditor !<br />";
		die();
	}
}
print_header("", "", "", $type="with_session");
if (isset($_GET['ok']))
{
	$msg = get_vocab("message_records");
	affiche_pop_up($msg, "admin");
}
include "admin_col_gauche.php";
include "../include/admin_config_tableau.inc.php";

// Jour de Cycle
echo "<form action=\"./admin_config.php\"  method=\"get\" style=\"width: 100%;\" onsubmit=\"return verifierJoursCycles(false);\">\n";
echo "<h3>".get_vocab("Activer_module_jours_cycles")."</h3>\n";
echo "<table border='0'>\n<tr>\n<td>\n";
echo get_vocab("Activer_module_jours_cycles").get_vocab("deux_points");
echo "<select name='jours_cycles'>\n";
if (Settings::get("jours_cycles_actif") == "Oui")
{
	echo "<option value=\"Oui\" selected=\"selected\">".get_vocab('YES')."</option>\n";
	echo "<option value=\"Non\">".get_vocab('NO')."</option>\n";
}
else
{
	echo "<option value=\"Oui\">".get_vocab('YES')."</option>\n";
	echo "<option value=\"Non\" selected=\"selected\">".get_vocab('NO')."</option>\n";
}
echo "</select>\n</td>\n</tr>\n</table><hr />\n";

// Multisite
echo "<h3>".get_vocab("Activer_module_multisite")."</h3>\n";
echo "<table border='0'>\n<tr>\n<td>\n";
echo get_vocab("Activer_module_multisite").get_vocab("deux_points");
echo "<select name='module_multisite'>\n";
if (Settings::get("module_multisite") == "Oui")
{
	echo "<option value=\"Oui\" selected=\"selected\">".get_vocab('YES')."</option>\n";
	echo "<option value=\"Non\">".get_vocab('NO')."</option>\n";
}
else
{
	echo "<option value=\"Oui\">".get_vocab('YES')."</option>\n";
	echo "<option value=\"Non\" selected=\"selected\">".get_vocab('NO')."</option>\n";
}
echo "</select>\n</td>\n</tr>\n</table>\n";

// Export XML
echo "<h3>".get_vocab("Activer_export_xml")."</h3>\n";
echo "<table border='0'>\n<tr>\n<td>\n";
echo get_vocab("Activer_export_xml").get_vocab("deux_points");
echo "<select name='export_xml'>\n";
if (Settings::get("export_xml_actif") == "Oui")
{
	echo "<option value=\"Oui\" selected=\"selected\">".get_vocab('YES')."</option>\n";
	echo "<option value=\"Non\">".get_vocab('NO')."</option>\n";
}
else
{
	echo "<option value=\"Oui\">".get_vocab('YES')."</option>\n";
	echo "<option value=\"Non\" selected=\"selected\">".get_vocab('NO')."</option>\n";
}
echo "</select>\n</td>\n</tr>\n</table>\n";

// Export XML PLUS
echo "<h3>".get_vocab("Activer_export_plus_xml")."</h3>\n";
echo "<table border='0'>\n<tr>\n<td>\n";
echo get_vocab("Activer_export_plus_xml").get_vocab("deux_points");
echo "<select name='export_xml_plus'>\n";
if (Settings::get("export_xml_plus_actif") == "Oui")
{
	echo "<option value=\"Oui\" selected=\"selected\">".get_vocab('YES')."</option>\n";
	echo "<option value=\"Non\">".get_vocab('NO')."</option>\n";
}
else
{
	echo "<option value=\"Oui\">".get_vocab('YES')."</option>\n";
	echo "<option value=\"Non\" selected=\"selected\">".get_vocab('NO')."</option>\n";
}
echo "</select>\n</td>\n</tr>\n</table>\n";

// fckeditor
echo "\n<hr /><h3>".get_vocab("use_fckeditor_msg")."</h3>";
echo "\n<p>".get_vocab("use_fckeditor_explain")."</p>";
echo "\n<table>";
echo "\n<tr><td>".get_vocab("use_fckeditor0")."</td><td>";
echo "\n<input type='radio' name='use_fckeditor' value='0' ";
if (Settings::get("use_fckeditor") == '0')
	echo "checked=\"checked\"";
echo " />";
echo "\n</td></tr>";
echo "\n<tr><td>".get_vocab("use_fckeditor1")."</td><td>";
echo "\n<input type='radio' name='use_fckeditor' value='1' ";
if (Settings::get("use_fckeditor") == '1')
	echo "checked=\"checked\"";
echo " />";
echo "\n</td></tr>";
echo "\n</table>";


echo "\n<div id=\"fixe\" style=\"text-align:center;\"><input class=\"btn btn-primary\" type=\"submit\" name=\"ok\" value=\"".get_vocab("save")."\" style=\"font-variant: small-caps;\"/>\n";
echo "<input type=\"hidden\" value=\"5\" name=\"page_config\" /></div>\n";
echo "</form>";
// fin de l'affichage de la colonne de droite
echo "</td></tr></table>";
?>
