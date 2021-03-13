<?php
/**
 * admin_overload.php
 * Interface de création/modification des champs additionnels.
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2021-03-13 11:45$
 * @author    JeromeB & Laurent Delineau & Marc-Henri PAMISEUX & Yan Naessens
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
$grr_script_name = "admin_overload.php";

include "../include/admin.inc.php";

$back = (isset($_SERVER['HTTP_REFERER']))? htmlspecialchars_decode($_SERVER['HTTP_REFERER'], ENT_QUOTES) : "./admin_accueil.php" ;
check_access(4, $back);
$use_prototype = 'y';
$use_tooltip_js = 'y';

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
    if (isset($_POST["mail_spec"]))
        $mail_spec = $_POST["mail_spec"];
    else
        $mail_spec = "";
    
    foreach ($userdomain as $key=>$value)
    {
        if ($key == $id_area)
            $arearight = true;
    }
    if ($arearight == true)
    {
		$sql = "INSERT INTO ".TABLE_PREFIX."_overload (id_area, fieldname, fieldtype, obligatoire, confidentiel, fieldlist, affichage, overload_mail, mail_spec) VALUES ($id_area, '".protect_data_sql($fieldname)."', '".protect_data_sql($fieldtype)."', '".$obligatoire."', '".$confidentiel."', '".protect_data_sql($fieldlist)."', '".$affichage."', '".$overload_mail."', '".$mail_spec."');";
        if (grr_sql_command($sql) < 0){
            $err_sql = grr_sql_error();
            $short_err = substr($err_sql,0,9);
            if ($short_err == "Duplicate"){
                affiche_pop_up(get_vocab("duplicate_field"),"force");
                // Header('Location: ./admin_overload.php');
            }
            else 
                fatal_error(0, "$sql \n\n" . $err_sql);
        }
    }
}
else if ($action == "delete")
{
	$arearight = false ;
	$id_overload = (isset($_POST["id_overload"]))? intval($_POST["id_overload"]) : NULL;
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
else if ($action == "change")
{
    $arearight = false ;
    $id_overload = (isset($_POST["id_overload"]))? intval($_POST["id_overload"]) : NULL;
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
    if (isset($_POST["mail_spec"]))
        $mail_spec = $_POST["mail_spec"];
    else
        $mail_spec = "";
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
            fieldlist='".protect_data_sql($fieldlist)."',
			mail_spec='".protect_data_sql($mail_spec)."'
            WHERE id=$id_overload;";
            if (grr_sql_command($sql) < 0)
                fatal_error(0, "$sql \n\n" . grr_sql_error());
        }
}
// calcul de la page à afficher    
$html = get_vocab("explication_champs_additionnels")."\n";
$html .= "<form method=\"post\" action=\"admin_overload.php\" >\n<table class='table-bordered'>";
$html .= "<thead><tr><td class='CC'>".get_vocab("match_area").get_vocab("deux_points")."</td>\n";
$html .= "<td class='CC'>".get_vocab("fieldname").get_vocab("deux_points")."</td>\n";
$html .= "<td class='CC'>".get_vocab("fieldtype").get_vocab("deux_points")."</td>\n";
$html .= "<td class='CC'>".get_vocab("champ_obligatoire")."</td>\n";
$html .= "<td class='CC'><span class='small'>".get_vocab("affiche_dans_les_vues")."</span></td>\n";
$html .= "<td class='CC'><span class='small'>".get_vocab("affiche_dans_les_mails")."</span></td>\n";
$html .= "<td class='CC'>".get_vocab("champ_confidentiel")."</td>\n";
$html .= "<td class='CC'><span class='small'>".get_vocab("envoy_mail_specifique")."*(1)(2)</span></td>\n";
$html .= "<td class='CC'></td></tr></thead>\n";
$html .= "\n<tbody><tr><td>";
$html .= "<select name=\"id_area\" size=\"1\">";
foreach ($userdomain as $key=>$value)
    $html .= "<option value=\"$key\">".$userdomain[$key]."</option>\n";
$html .= "</select></td>\n";
$html .= "<td><input type=\"text\" name=\"fieldname\" size=\"20\" /></td>\n";
$html .= "<td><select name=\"fieldtype\" size=\"1\">\n
<option value=\"text\">".get_vocab("type_text")."</option>\n
<option value=\"numeric\">".get_vocab("type_numeric")."</option>\n
<option value=\"textarea\">".get_vocab("type_area")."</option>\n
<option value=\"list\">".get_vocab("type_list")."</option>\n
</select></td>\n";
$html .= "<td class='CC'> ";
$html .= "<input type=\"checkbox\" id=\"obligatoire\" name=\"obligatoire\" title=\"".get_vocab("champ_obligatoire")."\" value=\"y\" />\n";
$html .= "<input type=\"hidden\" name=\"action\" value=\"add\" /></td>\n";
$html .= "<td class='CC'> ";
$html .= "<input type=\"checkbox\" id=\"affichage\" name=\"affichage\" title=\"\" value=\"n\" />\n";
$html .= "</td>\n";
$html .= "<td class='CC'> ";
$html .= "<input type=\"checkbox\" id=\"overload_mail\" name=\"overload_mail\" title=\"\" value=\"n\" />\n";
$html .= "<input type=\"hidden\" name=\"action\" value=\"add\" /></td>\n";
$html .= "<td class='CC'> ";
$html .= "<input type=\"checkbox\" id=\"confidentiel\" name=\"confidentiel\" title=\"".get_vocab("champ_confidentiel")."\" value=\"y\" />\n";
$html .= "<input type=\"hidden\" name=\"action\" value=\"add\" /></td>\n";
$html .= "<td><input type=\"text\" name=\"mail_spec\" size=\"20\" /></td>\n";
$html .= "<td><button name=\"submit\" title=\"".get_vocab('add')."\"><span class='glyphicon glyphicon-plus'></span></button></td>\n";
$html .= "</tr></tbody></table></form>\n"; // fin de la table "ajouter"
$html .= "<p class='small'>(1)".get_vocab("cas_fonctionnalite_mail_actif")."<br />";
$html .= "(2)".get_vocab("envois_mail_spec_exp")."</p>";
$breakkey = "";
$ouvre_table = false;
$ferme_table = false;
$ind_div = 0;
foreach ($userdomain as $key=>$value)
{
    $res = grr_sql_query("SELECT id, fieldname, fieldtype, obligatoire, fieldlist, affichage, overload_mail, confidentiel, mail_spec FROM ".TABLE_PREFIX."_overload WHERE id_area=$key ORDER BY fieldname;");
    if (!$res)
        fatal_error(0, grr_sql_error());
    if (($key != $breakkey) && (grr_sql_count($res) != 0))
    {
        if (!$ouvre_table)
        {
            $html .= "<table class='table-bordered'>";
            $ferme_table = true;
            $ouvre_table = true;
        }
        $html .= "<thead><tr>
        <td class='CC'>".get_vocab("match_area")."</td>
        <td class='CC'>".get_vocab("fieldname")."</td>
        <td class='CC'>".get_vocab("fieldtype")."</td>
        <td class='CC'>".get_vocab("champ_obligatoire")."</td>
        <td class='CC'><span class='small'>".get_vocab("affiche_dans_les_vues")."</span></td>
        <td class='CC'><span class='small'>".get_vocab("affiche_dans_les_mails")."</span></td>
        <td class='CC'>".get_vocab("champ_confidentiel")."</td>
        <td class='CC'><span class='small'>".get_vocab("envoy_mail_specifique")."</span></td>
        <td class='CC'>Actions</td></tr></thead>";
    }
    $breakkey = $key;
    if (grr_sql_count($res) != 0)
        for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
        {
            $html .= "<tr><form method=\"post\" action=\"admin_overload.php\">\n";
            $html .= "<td class='CC'>$userdomain[$key]</td>\n";
            $html .= "<td class='center CC'>".htmlspecialchars($row[1])."</td>\n";
            $html .= "<input type=\"hidden\" name=\"fieldname\" value=\"$row[1]\" />\n";
            $html .= "<td class='CL'><select name=\"fieldtype\">\n";
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
            $html .= "</select>";
            if ($row[2] == "list") {
                $html .= "<div><br />".get_vocab("Liste des champs").get_vocab("deux_points")."<br />";
                $html .= "<input type=\"text\" name=\"fieldlist\" value=\"".htmlspecialchars($row[4])."\" size=\"35\" /></div>";
            }
            $html .= "</td>\n";
            $ind_div++;
            $html .= "<td class='CC'><input type=\"checkbox\" id=\"obligatoire_".$ind_div."\" name=\"obligatoire\" title=\"".get_vocab("champ_obligatoire")."\" value=\"y\" ";
            if ($row[3] =="y")
                $html .= " checked=\"checked\" ";
            $html .= "/></td>\n";
            $html .= "<td class='CC'><input type=\"checkbox\" id=\"affichage_".$ind_div."\" name=\"affichage\" title=\"".get_vocab("affiche_dans_les_vues")."\" value=\"y\" ";
            if ($row[5] =="y")
                $html .= " checked=\"checked\" ";
            $html .= "/></td>\n";
            $html .= "<td class='CC'><input type=\"checkbox\" id=\"overload_mail_".$ind_div."\" name=\"overload_mail\" title=\"".get_vocab("affiche_dans_les_mails")."\" value=\"y\" ";
            if ($row[6] =="y")
                $html .= " checked=\"checked\" ";
            $html .= "/></td>\n";
            $html .= "<td class='CC'><input type=\"checkbox\" id=\"confidentiel_".$ind_div."\" name=\"confidentiel\" title=\"".get_vocab("champ_confidentiel")."\" value=\"y\" ";
            if ($row[7] =="y")
                $html .= " checked=\"checked\" ";
            $html .= "/></td>\n";
            $html .= '<td><input type="text" name="mail_spec" size="20" value="'.$row[8].'" /></td>';
            $html .= "<td class='CC'>\n";
            $html .= "<div><input type=\"hidden\" name=\"id_overload\" value=\"$row[0]\" />\n";
            $html .= "<input type=\"hidden\" name=\"action\" value=\"change\" />\n";
            $html .= "<button title=\"".get_vocab('change')."\"><span class='glyphicon glyphicon-edit'></span></button>";
            $html .= "</div></form>\n";
            $html .= "<form method=\"post\" action=\"admin_overload.php\">\n";
            $html .= "<div><button title=\"".get_vocab('del')."\" onclick=\"return confirmlink(this, '".addslashes(get_vocab("avertissement_suppression_champ_additionnel"))."', '".get_vocab("confirm_del")."')\"><span class='glyphicon glyphicon-trash'></span></button>\n";
            $html .= "<input type=\"hidden\" name=\"id_overload\" value=\"$row[0]\" />\n";
            $html .= "<input type=\"hidden\" name=\"action\" value=\"delete\" />\n";
            $html .= "</div></form></td></tr>\n";
        }
    }
// code HTML
start_page_w_header("", "", "", $type = "with_session");
include "admin_col_gauche2.php";
echo '<div class="col-md-9 col-sm-8 col-xs-12">';
echo "<h2>".get_vocab("admin_overload.php")."</h2>\n";        
echo $html;
if ($ferme_table)
    echo "</table>";
echo "<div class='tooltip' id='tooltip_affichage' style=\"display:none;\">\n";
echo get_vocab("affiche_dans_les_vues");
echo "</div>\n";
echo "<div class='tooltip' id='tooltip_overload_mail' style=\"display:none;\">\n";
echo get_vocab("affiche_dans_les_mails");
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
echo "</div>";
end_page();
?>
