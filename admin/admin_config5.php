<?php
/**
 * admin_config5.php
 * Interface permettant à l'administrateur la configuration des paramètres pour le module Jours Cycles
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2021-03-13 11:58$
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
// cette page reste à internationaliser
$grr_script_name = "admin_config5.php";

include "../include/admin.inc.php";

$back = (isset($_SERVER['HTTP_REFERER']))? htmlspecialchars_decode($_SERVER['HTTP_REFERER'], ENT_QUOTES) : "./admin_accueil.php" ;
$_SESSION['chemin_retour'] = "admin_accueil.php";
$day   = date("d");
$month = date("m");
$year  = date("Y");
check_access(6, $back);
// vérifications
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
start_page_w_header("", "", "", $type="with_session");
if (isset($_GET['ok']))
{
	$msg = get_vocab("message_records");
	affiche_pop_up($msg, "admin");
}
include "admin_col_gauche2.php";

echo "<div class='col-md-9 col-sm-8 col-xs-12'>";
// Jour de Cycle
echo "<form action=\"./admin_config5.php\"  method=\"get\"  onsubmit=\"return verifierJoursCycles(false);\">\n";
echo "<h3>".get_vocab("Activer_module_jours_cycles")."</h3>\n";
echo "<table>\n<tr>\n<td>\n";
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
echo "<table>\n<tr>\n<td>\n";
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
echo "<hr />";
// Export XML
echo "<h3>".get_vocab("Activer_export_xml")."</h3>\n";
echo "<table>\n<tr>\n<td>\n";
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
echo "</select> (./export/export".TABLE_PREFIX.".xml)\n</td>\n</tr>\n</table>\n";

// Export XML PLUS
echo "<h3>".get_vocab("Activer_export_plus_xml")."</h3>\n";
echo "<table>\n<tr>\n<td>\n";
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
echo "</select> (./export/exportplus".TABLE_PREFIX.".xml)\n</td>\n</tr>\n</table>\n";

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


echo "\n<div id=\"fixe\" ><input class=\"btn btn-primary\" type=\"submit\" name=\"ok\" value=\"".get_vocab("save")."\" style=\"font-variant: small-caps;\"/>\n";
echo "<input type=\"hidden\" value=\"5\" name=\"page_config\" /></div>\n";
echo "</form>";
// fin de l'affichage de la colonne de droite et de la page
echo "</div></section></body></html>";
?>
