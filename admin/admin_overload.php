<?php
/**
 * admin_overload.php
 * Interface de création/modification des champs additionnels.
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2009-09-29 18:02:56 $
 * @author    Laurent Delineau <laurent.delineau@ac-poitiers.fr>
 * @author    Marc-Henri PAMISEUX <marcori@users.sourceforge.net>
 * @copyright Copyright 2003-2008 Laurent Delineau
 * @copyright Copyright 2008 Marc-Henri PAMISEUX
 * @link      http://www.gnu.org/licenses/licenses.html
 * @package   admin
 * @version   $Id: admin_overload.php,v 1.7 2009-09-29 18:02:56 grr Exp $
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
$grr_script_name = "admin_overload.php";
$back = '';
if (isset($_SERVER['HTTP_REFERER']))
	$back = htmlspecialchars($_SERVER['HTTP_REFERER']);
$day   = date("d");
$month = date("m");
$year  = date("Y");
check_access(4, $back);
$use_prototype = 'y';
$use_tooltip_js = 'y';
print_header("", "", "", $type = "with_session");
include "admin_col_gauche.php";
echo "<h2>".get_vocab("admin_overload.php")."</h2>\n";
if (isset($_POST["action"]))
	$action = $_POST["action"];
else
	$action = "default";
$res = grr_sql_query("SELECT id, area_name, access FROM ".TABLE_PREFIX."_area ORDER BY order_display");
if (!$res)
	fatal_error(0, grr_sql_error());
$userdomain = array();
if (grr_sql_count($res) != 0)
{
	for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
	{
		if (authGetUserLevel(getUserName(), $row[0], 'area') >= 4)
			$userdomain[$row[0]] = $row[1];
	}
}
if ($action == "add")
{
	$arearight = false;
	if (isset($_POST["id_area"]))
		$id_area = $_POST["id_area"];
	else
		$id_area = 0;
	settype($id_area,"integer");
	if (isset($_POST["fieldname"]))
		$fieldname = $_POST["fieldname"];
	else
		$fieldname = "";
	if (isset($_POST["fieldtype"]))
		$fieldtype = $_POST["fieldtype"];
	else
		$fieldtype = "";
	$fieldlist = "";
	if (isset($_POST["obligatoire"]))
		$obligatoire = "y";
	else
		$obligatoire = "n";
	if (isset($_POST["affichage"]))
		$affichage = "y";
	else
		$affichage = "n";
	if (isset($_POST["overload_mail"]))
		$overload_mail = "y";
	else
		$overload_mail = "n";
	if (isset($_POST["confidentiel"]))
		$confidentiel = "y";
	else
		$confidentiel = "n";
	if ($confidentiel == "y")
	{
		$affichage = "n";
		$overload_mail = "n";
	}
	foreach ($userdomain as $key=>$value)
	{
		if ($key == $id_area)
			$arearight = true;
	}
	if ($arearight == true)
	{
		$sql = "INSERT INTO ".TABLE_PREFIX."_overload (id_area, fieldname, fieldtype, obligatoire, confidentiel, fieldlist, affichage, overload_mail) VALUES ($id_area, '".protect_data_sql($fieldname)."', '".protect_data_sql($fieldtype)."', '".$obligatoire."', '".$confidentiel."', '".protect_data_sql($fieldlist)."', '".$affichage."', '".$overload_mail."');";
		if (grr_sql_command($sql) < 0)
			fatal_error(0, "$sql \n\n" . grr_sql_error());
	}
}
if ($action == "delete")
{
	$arearight = false ;
	if (isset($_POST["id_overload"]))
		$id_overload = $_POST["id_overload"];
	else
		$id_overload = "";
	$sql = "SELECT id_area FROM ".TABLE_PREFIX."_overload WHERE id=$id_overload;";
	$resquery = grr_sql_query($sql);
	if (!$resquery)
		fatal_error(0, grr_sql_error());
	if (grr_sql_count($resquery) > 0)
		for ($i = 0; ($row = grr_sql_row($resquery, $i)); $i++)
		{
			foreach ($userdomain as $key=>$value)
			{
				if ($key == $row[0])
					$arearight = true;
			}
		}
		if ($arearight == true)
		{
			grrDelOverloadFromEntries($id_overload);
			$sql = "DELETE FROM ".TABLE_PREFIX."_overload WHERE id=$id_overload;";
			if (grr_sql_command($sql) < 0)
				fatal_error(0, "$sql \n\n" . grr_sql_error());
		}
	}
	if ($action == "change")
	{
		$arearight = false ;
		if (isset($_POST["id_overload"]))
			$id_overload = $_POST["id_overload"];
		else
			$id_overload = "";
		settype($id_overload,"integer");
		if (isset($_POST["fieldname"]))
			$fieldname = $_POST["fieldname"];
		else
			$fieldname = "";
		if (isset($_POST["fieldtype"]))
			$fieldtype = $_POST["fieldtype"];
		else
			$fieldtype = "";
		if (isset($_POST["fieldlist"]))
			$fieldlist = $_POST["fieldlist"];
		else
			$fieldlist = "";
		if ($fieldtype != "list")
			$fieldlist = "";
		if (isset($_POST["obligatoire"]))
			$obligatoire = "y";
		else
			$obligatoire = "n";
		if (isset($_POST["affichage"]))
			$affichage = "y";
		else
			$affichage = "n";
		if (isset($_POST["overload_mail"]))
			$overload_mail = "y";
		else
			$overload_mail = "n";
		if (isset($_POST["confidentiel"]))
			$confidentiel = "y";
		else
			$confidentiel = "n";
		if ($confidentiel == "y")
		{
			$affichage = "n";
			$overload_mail = "n";
		}
		$sql = "SELECT id_area FROM ".TABLE_PREFIX."_overload WHERE id=$id_overload;";
		$resquery = grr_sql_query($sql);
		if (!$resquery)
			fatal_error(0, grr_sql_error());
		if (grr_sql_count($resquery) > 0)
			for ($i = 0; ($row = grr_sql_row($resquery, $i)); $i++)
			{
				foreach ($userdomain as $key=>$value)
				{
					if ($key == $row[0] )
						$arearight = true;
				}
			}
			if ($arearight == true)
			{
				$sql = "UPDATE ".TABLE_PREFIX."_overload SET
				fieldname='".protect_data_sql($fieldname)."',
				fieldtype='".protect_data_sql($fieldtype)."',
				obligatoire='".$obligatoire."',
				confidentiel='".$confidentiel."',
				affichage='".$affichage."',
				overload_mail='".$overload_mail."',
				fieldlist='".protect_data_sql($fieldlist)."'
				WHERE id=$id_overload;";
				if (grr_sql_command($sql) < 0)
					fatal_error(0, "$sql \n\n" . grr_sql_error());
			}
		}
		$html = get_vocab("explication_champs_additionnels")."\n";
		$html .= "<form method=\"post\" action=\"admin_overload.php\" >\n<table border=\"1\">";
		$html .= "<tr><td>".get_vocab("match_area").get_vocab("deux_points")."</td>\n";
		$html .= "<td>".get_vocab("fieldname").get_vocab("deux_points")."</td>\n";
		$html .= "<td>".get_vocab("fieldtype").get_vocab("deux_points")."</td>\n";
		$html .= "<td><span class='small'>".get_vocab("champ_obligatoire")."</span></td>\n";
		$html .= "<td><span class='small'>".get_vocab("affiche_dans_les vues")."</span></td>\n";
		$html .= "<td><span class='small'>".get_vocab("affiche_dans_les mails")."</span></td>\n";
		$html .= "<td><span class='small'>".get_vocab("champ_confidentiel")."</span></td>\n";
		$html .= "<td> </td></tr>\n";
		$html .= "\n<tr><td>";
		$html .= "<select name=\"id_area\" size=\"1\">";
		foreach ($userdomain as $key=>$value)
			$html .= "<option value=\"$key\">".$userdomain[$key]."</option>\n";
		$html .= "</select></td>\n";
		$html .= "<td><div><input type=\"text\" name=\"fieldname\" size=\"20\" /></div></td>\n";
		$html .= "<td><div><select name=\"fieldtype\" size=\"1\">\n
		<option value=\"text\">".get_vocab("type_text")."</option>\n
		<option value=\"numeric\">".get_vocab("type_numeric")."</option>\n
		<option value=\"textarea\">".get_vocab("type_area")."</option>\n
		<option value=\"list\">".get_vocab("type_list")."</option>\n
	</select></div></td>\n";
	$html .= "<td><div> ";
	$html .= "<input type=\"checkbox\" id=\"obligatoire\" name=\"obligatoire\" title=\"".get_vocab("champ_obligatoire")."\" value=\"y\" />\n";
	$html .= "<input type=\"hidden\" name=\"action\" value=\"add\" /></div></td>\n";
	$html .= "<td><div> ";
	$html .= "<input type=\"checkbox\" id=\"affichage\" name=\"affichage\" title=\"\" value=\"n\" />\n";
	$html .= "</div></td>\n";
	$html .= "<td><div> ";
	$html .= "<input type=\"checkbox\" id=\"overload_mail\" name=\"overload_mail\" title=\"\" value=\"n\" />\n";
	$html .= "<input type=\"hidden\" name=\"action\" value=\"add\" /></div></td>\n";
	$html .= "<td><div> ";
	$html .= "<input type=\"checkbox\" id=\"confidentiel\" name=\"confidentiel\" title=\"".get_vocab("champ_confidentiel")."\" value=\"y\" />\n";
	$html .= "<input type=\"hidden\" name=\"action\" value=\"add\" /></div></td>\n";
	$html .= "<td><div><input type=\"submit\" name=\"submit\" value=\"".get_vocab('add')."\" /></div></td>\n";
	$html .= "</tr></table></form>\n";
	$breakkey = "";
	$ouvre_table = false;
	$ferme_table = false;
	$ind_div = 0;
	foreach ($userdomain as $key=>$value)
	{
		$res = grr_sql_query("SELECT id, fieldname, fieldtype, obligatoire, fieldlist, affichage, overload_mail, confidentiel FROM ".TABLE_PREFIX."_overload WHERE id_area=$key ORDER BY fieldname;");
		if (!$res)
			fatal_error(0, grr_sql_error());
		if (($key != $breakkey) && (grr_sql_count($res) != 0))
		{
			if (!$ouvre_table)
			{
				$html .= "<table cellpadding=\"3\" border=\"1\">";
				$ferme_table = true;
				$ouvre_table = true;
			}
			$html .= "<tr><th>".get_vocab("match_area")."</th><th>".get_vocab("fieldname")."</th><th>".get_vocab("fieldtype")."</th><th>".get_vocab("champ_obligatoire")."</th><th>".get_vocab("affiche_dans_les vues")."</th><th>".get_vocab("affiche_dans_les mails")."</th><th>".get_vocab("champ_confidentiel")."</th><th colspan='2'>Actions</th></tr>";
		}
		$breakkey = $key;
		if (grr_sql_count($res) != 0)
			for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
			{
				$html .= "<tr>\n";
				$html .= "<td style=\"vertical-align:middle;\">$userdomain[$key]</td>\n";
				$html .= "<form method=\"post\" action=\"admin_overload.php\">\n";
				$html .= "<div><input type=\"hidden\" name=\"id_overload\" value=\"$row[0]\" />\n";
				$html .= "<input type=\"hidden\" name=\"action\" value=\"change\" />\n";
				$html .= "<td><input type=\"text\" name=\"fieldname\" value=\"".htmlspecialchars($row[1])."\" /></td>\n";
				$html .= "<td><select name=\"fieldtype\">\n";
				$html .= "<option value=\"textarea\" ";
				if ($row[2] =="textarea")
					$html .= " selected=\"selected\"";
				$html .= " >".get_vocab("type_area")."</option>\n";
				$html .= "<option value=\"text\" ";
				if ($row[2] =="text")
					$html .= " selected=\"selected\"";
				$html .= " >".get_vocab("type_text")."</option>\n";
				$html .= "<option value=\"list\" ";
				if ($row[2] =="list")
					$html .= " selected=\"selected\"";
				$html .= " >".get_vocab("type_list")."</option>\n";
				$html .= "<option value=\"numeric\" ";
				if ($row[2] =="numeric")
					$html .= " selected=\"selected\"";
				$html .= " >".get_vocab("type_numeric")."</option>\n";
				$html .= "</select></td>\n";
				$ind_div++;
				$html .= "<td><input type=\"checkbox\" id=\"obligatoire_".$ind_div."\" name=\"obligatoire\" title=\"".get_vocab("champ_obligatoire")."\" value=\"y\" ";
				if ($row[3] =="y")
					$html .= " checked=\"checked\" ";
				$html .= "/></td>\n";
				$html .= "<td><input type=\"checkbox\" id=\"affichage_".$ind_div."\" name=\"affichage\" title=\"".get_vocab("affiche_dans_les vues")."\" value=\"y\" ";
				if ($row[5] =="y")
					$html .= " checked=\"checked\" ";
				$html .= "/></td>\n";
				$html .= "<td><input type=\"checkbox\" id=\"overload_mail_".$ind_div."\" name=\"overload_mail\" title=\"".get_vocab("affiche_dans_les mails")."\" value=\"y\" ";
				if ($row[6] =="y")
					$html .= " checked=\"checked\" ";
				$html .= "/></td>\n";
				$html .= "<td><input type=\"checkbox\" id=\"confidentiel_".$ind_div."\" name=\"confidentiel\" title=\"".get_vocab("champ_obligatoire")."\" value=\"y\" ";
				if ($row[7] =="y")
					$html .= " checked=\"checked\" ";
				$html .= "/></td>\n";
				$html .= "<td><input type=\"submit\" value=\"".get_vocab('change')."\" /></td>";
				if ($row[2] == "list") {
					$html .= "<br />".get_vocab("Liste des champs").get_vocab("deux_points")."<br />";
					$html .= "<input type=\"text\" name=\"fieldlist\" value=\"".htmlspecialchars($row[4])."\" size=\"50\" />";
				}
				$html .= "</div></form></td>\n";
				$html .= "<td><form method=\"post\" action=\"admin_overload.php\">\n";
				$html .= "<div><input type=\"submit\" value=\"".get_vocab('del')."\" onclick=\"return confirmlink(this, '".addslashes(get_vocab("avertissement_suppression_champ_additionnel"))."', '".get_vocab("confirm_del")."')\" />\n";
				$html .= "<input type=\"hidden\" name=\"id_overload\" value=\"$row[0]\" />\n";
				$html .= "<input type=\"hidden\" name=\"action\" value=\"delete\" />\n";
				$html .= "</div></form></td></tr>\n";
			}
		}
		echo $html;
		if ($ferme_table)
			echo "</table>";
		echo "<div class='tooltip' id='tooltip_affichage' style=\"display:none;\">\n";
		echo get_vocab("affiche_dans_les vues");
		echo "</div>\n";
		echo "<div class='tooltip' id='tooltip_overload_mail' style=\"display:none;\">\n";
		echo get_vocab("affiche_dans_les mails");
		echo "</div>\n";
		echo "<div class='tooltip' id='tooltip_obligatoire' style=\"display:none;\">\n";
		echo get_vocab("champ_obligatoire");
		echo "</div>\n";
		echo "<div class='tooltip' id='tooltip_confidentiel' style=\"display:none;\">\n";
		echo get_vocab("champ_confidentiel");
		echo "</div>\n";
		echo "<script type=\"text/javascript\">\n";
		echo "var my_tooltip_aff = new Tooltip('affichage', 'tooltip_affichage');\n";
		echo "var my_tooltip_aff = new Tooltip('overload_mail', 'tooltip_overload_mail');\n";
		echo "var my_tooltip_obli = new Tooltip('obligatoire', 'tooltip_obligatoire');\n";
		echo "var my_tooltip_obli = new Tooltip('confidentiel', 'tooltip_confidentiel');\n";
		for ($i = 1; $i <= $ind_div; $i++)
		{
			echo "var my_tooltip_aff = new Tooltip('affichage_".$i."', 'tooltip_affichage');\n";
			echo "var my_tooltip_aff = new Tooltip('overload_mail_".$i."', 'tooltip_overload_mail');\n";
			echo "var my_tooltip_obli = new Tooltip('obligatoire_".$i."', 'tooltip_obligatoire');\n";
			echo "var my_tooltip_obli = new Tooltip('confidentiel_".$i."', 'tooltip_confidentiel');\n";
		}
		echo "</script>\n";
		echo "</td></tr></table>\n";
		?>
	</body>
	</html>
