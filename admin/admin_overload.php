<?php
/**
 * admin_overload.php
 * Interface de création/modification des champs additionnels.
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2023-07-27 16:32$
 * @author    JeromeB & Laurent Delineau & Marc-Henri PAMISEUX & Yan Naessens
 * @copyright Copyright 2003-2023 Team DEVOME - JeromeB
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

$action="";
if (isset($_POST['add']))
    $action = 'add';
elseif (isset($_POST['change']))
    $action = 'change';
elseif (isset($_POST['delete']))
    $action = 'delete';

$res = grr_sql_query("SELECT id, area_name, access FROM ".TABLE_PREFIX."_area ORDER BY order_display");
if (!$res)
    fatal_error(0, grr_sql_error());
$username = getUserName();
$userdomain = array();
if (grr_sql_count($res) != 0)
{
    foreach($res as $row)
    {
        if (authGetUserLevel($username, $row['id'], 'area') >= 4)
            $userdomain[$row['id']] = $row['area_name'];
    }
}
if ($action == "add")
{
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
        $mail_spec = protect_data_sql($_POST["mail_spec"]);
    else
        $mail_spec = "";
    
    foreach ($userdomain as $key=>$value)
    {
        if ($key == $id_area)
        {
            $sql = "INSERT INTO ".TABLE_PREFIX."_overload (id_area, fieldname, fieldtype, obligatoire, confidentiel, fieldlist, affichage, overload_mail, mail_spec) VALUES ($id_area, '".protect_data_sql($fieldname)."', '".protect_data_sql($fieldtype)."', '".$obligatoire."', '".$confidentiel."', '".protect_data_sql($fieldlist)."', '".$affichage."', '".$overload_mail."', '".$mail_spec."');";
            if (grr_sql_command($sql) < 0){
                $err_sql = grr_sql_error();
                $short_err = substr($err_sql,0,9);
                if ($short_err == "Duplicate"){
                    affiche_pop_up(get_vocab("duplicate_field"),"force");
                }
                else 
                    fatal_error(0, "$sql \n\n" . $err_sql);
            }
        }
    }
}
else if ($action == "delete")
{
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
                {
                    grrDelOverloadFromEntries($id_overload);
                    $sql = "DELETE FROM ".TABLE_PREFIX."_overload WHERE id=$id_overload;";
                    if (grr_sql_command($sql) < 0)
                        fatal_error(0, "$sql \n\n" . grr_sql_error());
                }
            }
        }
}
elseif ($action == "change")
{
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
        }
}
// recherche des champs additionnels déjà définis
$breakkey = "";
$ouvre_table = false;
$ferme_table = false;
$ovlfdata = array();
foreach ($userdomain as $key=>$value)
{
    $res = grr_sql_query("SELECT * FROM ".TABLE_PREFIX."_overload WHERE id_area=$key ORDER BY fieldname;");
    if (!$res)
        fatal_error(0, grr_sql_error());
    else 
        foreach($res as $row)
        {
            $ovlfdata[] = $row;
        }
}
// code HTML
start_page_w_header("", "", "", $type = "with_session");
include "admin_col_gauche2.php";
echo '<div class="col col-sm-9 col-xs-12">';
echo "<h2>".get_vocab("admin_overload.php")."</h2>\n";
echo get_vocab("explication_champs_additionnels")."\n";
echo "<form method=\"post\" action=\"admin_overload.php\" >\n";
echo "<div class='table'>";
echo "<div class='tr'>";
echo "<span class='td CC'>".get_vocab("match_area").get_vocab("deux_points")."</span>\n";
echo "<span class='td CC'>".get_vocab("fieldname").get_vocab("deux_points")."</span>\n";
echo "<span class='td CC'>".get_vocab("fieldtype").get_vocab("deux_points")."</span>\n";
echo "<span class='td CC'>".get_vocab("champ_obligatoire")."</span>\n";
echo "<span class='td CC'><span class='small'>".get_vocab("affiche_dans_les_vues")."</span></span>\n";
echo "<span class='td CC'><span class='small'>".get_vocab("affiche_dans_les_mails")."</span></span>\n";
echo "<span class='td CC'>".get_vocab("champ_confidentiel")."</span>\n";
echo "<span class='td CC'><span class='small'>".get_vocab("envoy_mail_specifique")."(1)(2)</span></span>\n";
echo "<span class='td CC'></span></div>\n";
echo "\n<div class='tr'>";
echo "\n<span class='td'>";
echo "<select name=\"id_area\" size=\"1\">";
foreach ($userdomain as $key=>$value)
    echo "<option value=\"$key\">".$value."</option>\n";
echo "</select></span>\n";
echo "<span class='td'><input type=\"text\" name=\"fieldname\" size=\"20\" pattern=\"[A-z0-9À-ž][A-z0-9À-ž ]*\" data-toggle=\"tooltip\" title=\"".get_vocab('alphanumeric')."\" required /></span>\n";
echo "<span class='td CC'><select name=\"fieldtype\" size=\"1\">\n
<option value=\"text\">".get_vocab("type_text")."</option>\n
<option value=\"numeric\">".get_vocab("type_numeric")."</option>\n
<option value=\"textarea\">".get_vocab("type_area")."</option>\n
<option value=\"list\">".get_vocab("type_list")."</option>\n
</select></span>\n";
echo "<span class='td CC'> ";
echo "<input type=\"checkbox\" id=\"obligatoire\" name=\"obligatoire\" value=\"y\" />\n";
echo "<input type=\"hidden\" name=\"action\" value=\"add\" /></span>\n";
echo "<span class='td CC'> ";
echo "<input type=\"checkbox\" id=\"affichage\" name=\"affichage\" value=\"n\" />\n";
echo "</span>\n";
echo "<span class='td CC'> ";
echo "<input type=\"checkbox\" id=\"overload_mail\" name=\"overload_mail\" value=\"n\" />\n";
echo "<input type=\"hidden\" name=\"action\" value=\"add\" /></span>\n";
echo "<span class='td CC'> ";
echo "<input type=\"checkbox\" id=\"confidentiel\" name=\"confidentiel\" value=\"y\" /></span>\n";
echo "<span class='td CC'><input type=\"text\" name=\"mail_spec\" size=\"20\" /></span>\n";
echo "<span class='td CC'><button type=\"submit\" data-toggle=\"tooltip\" title=\"".get_vocab('add')."\" name=\"add\" ><span class='glyphicon glyphicon-plus'></span></button></span>\n";
echo "</div></div></form>\n"; // fin de la table "ajouter"
echo "<p class='small'>(1)".get_vocab("cas_fonctionnalite_mail_actif")."<br />";
echo "(2)".get_vocab("envois_mail_spec_exp")."</p>";

if(!empty($ovlfdata)){ // il existe des champs additionnels déjà définis
    echo '<div class="table">';
    // entête
    echo '<div class="tr">';
    echo "<span class='td CC'>".get_vocab("match_area")."</span>
        <span class='td CC'>".get_vocab("fieldname")."</span>
        <span class='td CC'>".get_vocab("fieldtype")."</span>
        <span class='td CC'>".get_vocab("champ_obligatoire")."</span>
        <span class='td CC'><span class='small'>".get_vocab("affiche_dans_les_vues")."</span></span>
        <span class='td CC'><span class='small'>".get_vocab("affiche_dans_les_mails")."</span></span>
        <span class='td CC'>".get_vocab("champ_confidentiel")."</span>
        <span class='td CC'><span class='small'>".get_vocab("envoy_mail_specifique")."</span></span>
        <span class='td CC'>Actions</span>";
    echo '</div>';
    // corps du formulaire
    $ind_div = 0;
    foreach($ovlfdata as $key=>$row){
        echo "<form class='tr' method=\"post\" action=\"admin_overload.php\">\n";
        echo "<span class='td CC'>".$userdomain[$row['id_area']]."</span>\n";
        echo "<span class='td CC'>".htmlspecialchars($row['fieldname'])."</span>\n";
        echo "<input type=\"hidden\" name=\"fieldname\" value=\"".$row['fieldname']."\" />\n";
        echo "<span class='td CL'><select name=\"fieldtype\">\n";
        echo "<option value=\"textarea\" ";
        if ($row['fieldtype'] =="textarea")
            echo " selected=\"selected\"";
        echo " >".get_vocab("type_area")."</option>\n";
        echo "<option value=\"text\" ";
        if ($row['fieldtype'] =="text")
            echo  " selected=\"selected\"";
        echo  " >".get_vocab("type_text")."</option>\n";
        echo  "<option value=\"list\" ";
        if ($row['fieldtype'] =="list")
            echo  " selected=\"selected\"";
        echo  " >".get_vocab("type_list")."</option>\n";
        echo  "<option value=\"numeric\" ";
        if ($row['fieldtype'] =="numeric")
            echo  " selected=\"selected\"";
        echo  " >".get_vocab("type_numeric")."</option>\n";
        echo  "</select>";
        if ($row['fieldtype'] == "list") {
            echo  "<div><br />".get_vocab("Liste_des_champs").get_vocab("deux_points")."<br />";
                echo  "<input type=\"text\" name=\"fieldlist\" value=\"".htmlspecialchars($row['fieldlist'])."\" size=\"35\" /></div>";
        }
        echo  "</span>\n";
        $ind_div++;
        echo  "<span class='td CC'><input type=\"checkbox\" id=\"obligatoire_".$ind_div."\" name=\"obligatoire\" data-toggle=\"tooltip\" title=\"".get_vocab("champ_obligatoire")."\" value=\"y\" ";
        if ($row['obligatoire'] =="y")
            echo  " checked=\"checked\" ";
        echo  "/></span>\n";
        echo  "<span class='td CC'><input type=\"checkbox\" id=\"affichage_".$ind_div."\" name=\"affichage\" data-toggle=\"tooltip\" title=\"".get_vocab("affiche_dans_les_vues")."\" value=\"y\" ";
        if ($row['affichage'] =="y")
            echo  " checked=\"checked\" ";
        echo  "/></span>\n";
        echo  "<span class='td CC'><input type=\"checkbox\" id=\"overload_mail_".$ind_div."\" name=\"overload_mail\" data-toggle=\"tooltip\" title=\"".get_vocab("affiche_dans_les_mails")."\" value=\"y\" ";
        if ($row['overload_mail'] =="y")
            echo  " checked=\"checked\" ";
        echo  "/></span>\n";
        echo  "<span class='td CC'><input type=\"checkbox\" id=\"confidentiel_".$ind_div."\" name=\"confidentiel\" data-toggle=\"tooltip\" title=\"".get_vocab("champ_confidentiel")."\" value=\"y\" ";
        if ($row['confidentiel'] =="y")
            echo  " checked=\"checked\" ";
        echo  "/></span>\n";
        echo  '<span class="td CC"><input type="text" name="mail_spec" size="20" value="'.$row['mail_spec'].'" /></span>';
        echo  "<span class='td CC'>\n";
        echo  "<div><input type=\"hidden\" name=\"id_overload\" value=\"".$row['id']."\" />\n";
        echo  "<button type=\" submit\" data-toggle=\"tooltip\" title=\"".get_vocab('change')."\" name=\"change\"><span class='glyphicon glyphicon-edit'></span></button>";
        echo  "</div>";
        echo  "<div><button type=\"submit\" data-toggle=\"tooltip\" title=\"".get_vocab('del')."\" name=\"delete\" onclick=\"return confirmlink(this, '".addslashes(get_vocab("avertissement_suppression_champ_additionnel"))."', '".get_vocab("confirm_del")."')\"><span class='glyphicon glyphicon-trash'></span></button>\n";
        echo  "</div>";
        echo "</span>\n";
        echo "</form>\n";
    }
    echo "</div>";
}

echo "<script>
$(document).ready(function(){
  $('[data-toggle=\"tooltip\"]').tooltip();
});
</script>";
end_page();
?>