<?php
/**
 * admin_type.php
 * Interface de gestion des types de réservations
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2022-01-25 10:11$
 * @author    JeromeB & Laurent Delineau & Yan Naessens & J.-P. Gay
 * @copyright Copyright 2003-2022 Team DEVOME - JeromeB
 * @link      http://www.gnu.org/licenses/licenses.html
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */
$grr_script_name = "admin_type.php";

include "../include/admin.inc.php";

$back = (isset($_SERVER['HTTP_REFERER']))? htmlspecialchars_decode($_SERVER['HTTP_REFERER'], ENT_QUOTES) : "./admin_accueil.php" ;
check_access(6, $back);
if ((isset($_GET['msg'])) && isset($_SESSION['displ_msg']) && ($_SESSION['displ_msg'] == 'yes') )
	$msg = $_GET['msg'];
else
	$msg = '';

if ((isset($_GET['action_del'])) && ($_GET['js_confirmed'] == 1) && ($_GET['action_del'] = 'yes'))
{
	// faire le test si il existe une réservation en cours avec ce type de réservation
	$type_id = grr_sql_query1("SELECT type_letter FROM ".TABLE_PREFIX."_type_area WHERE id = '".$_GET['type_del']."'");
	$test1 = grr_sql_query1("SELECT count(id) FROM ".TABLE_PREFIX."_entry WHERE type= '".$type_id."'");
	$test2 = grr_sql_query1("SELECT count(id) FROM ".TABLE_PREFIX."_repeat WHERE type= '".$type_id."'");
	if (($test1 != 0) || ($test2 != 0))
	{
		$msg = get_vocab('admin_type_msg1');
	}
	else
	{
		$sql = "DELETE FROM ".TABLE_PREFIX."_type_area WHERE id='".$_GET['type_del']."'";
		if (grr_sql_command($sql) < 0)
			fatal_error(1, "<p>" . grr_sql_error());
		$sql = "DELETE FROM ".TABLE_PREFIX."_j_type_area WHERE id_type='".$_GET['type_del']."'";
		if (grr_sql_command($sql) < 0)
			fatal_error(1, "<p>" . grr_sql_error());
	}
}
if (!Settings::load())
	die("Erreur chargement settings");
// -------------------------------------------- ModifExclure Début-1
if (isset($_GET['exclude_type_in_views_all']))
{
	if (!Settings::set("exclude_type_in_views_all", $_GET['exclude_type_in_views_all']))
		echo get_vocab('save_err')." exclude_type_in_views_all !<br />"; // bien géré ?
}
if (isset($_GET['ok']))                       
 { $msg = get_vocab("message_records"); }     
// ------------------------------------------- ModifExclure Fin-1

// code HTML
start_page_w_header("", "", "", $type="with_session");
include "admin_col_gauche2.php";
echo "<div class='col-md-9 col-sm-8 col-xs-12'>";
affiche_pop_up($msg,"admin");
echo "<h2>".get_vocab('admin_type.php')."</h2>";

// ------------------------------------------ ModifExclure Début-2
echo '<form action="./admin_type.php" method="get">'.PHP_EOL;
echo '<div>'.PHP_EOL;
echo '<p>'.get_vocab('exclude_type_in_views_all').'</p>'.PHP_EOL; 
echo '<div>'.PHP_EOL;
echo '<div class="col-sm-6 col-xs-12">'.PHP_EOL;
echo '<input class="form-control" type="text" id="exclude_type_in_views_all" name="exclude_type_in_views_all" value="'.Settings::get('exclude_type_in_views_all').'" size="30">'.PHP_EOL;
echo '</div>'.PHP_EOL;
echo '<div class="col-sm-6 col-xs-12">'.PHP_EOL;
echo '<input class="btn btn-primary" type="submit" name="ok" value="'.get_vocab('save').'" style="font-variant: small-caps;"/>'.PHP_EOL;
echo '</div>'.PHP_EOL;
echo '</div>'.PHP_EOL;
echo "</div>\n";
echo "</form>".PHP_EOL;
// ------------------------------------------ ModifExclure Fin-2

echo "<hr />\n";
echo "<br /><p>".get_vocab('admin_type_explications')."</p>";

echo "| <a href=\"admin_type_modify.php?id=0\">".get_vocab("display_add_type")."</a> |\n";
echo "<br />\n";
echo "<br />\n";
$sql = "SELECT id, type_name, order_display, couleurhexa, type_letter, disponible, couleurtexte FROM ".TABLE_PREFIX."_type_area
ORDER BY order_display,type_letter";
$res = grr_sql_query($sql);
$nb_lignes = grr_sql_count($res);
if ($nb_lignes > 0)
{
    // Affichage du tableau
    echo "<table class='table table-bordered'><tr>\n";
    echo "<td><b>".get_vocab("type_num")."</b></td>\n";
    echo "<td><b>".get_vocab("type_name")."</b></td>\n";
    echo "<td><b>".get_vocab("type_apercu")."</b></td>\n";
    echo "<td><b>".get_vocab("type_order")."</b></td>\n";
    echo "<td><b>".get_vocab("disponible_pour")."</b></td>\n";
    echo "<td><b>".get_vocab("delete")."</b></td>";
    echo "</tr>";
    if ($res)
    {
        for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
        {
            $id_type            = $row[0];
            $type_name          = $row[1];
            $order_display      = $row[2];
            $couleur            = $row[3];
            $type_letter        = $row[4];
            $disponible         = $row[5];
            $couleurtexte      = $row[6];
        // Affichage des numéros et descriptions
            $col[$i][1] = $type_letter;
            $col[$i][2] = $id_type;
            $col[$i][3] = $type_name;
        // Affichage de l'ordre
            $col[$i][4] = $order_display;
            $col[$i][5] = $couleur;
            $col[$i][6] = $couleurtexte;
            echo "<tr>\n";
            echo "<td>{$col[$i][1]}</td>\n";
            echo "<td><a href='admin_type_modify.php?id_type={$col[$i][2]}'>{$col[$i][3]}</a></td>\n";
            echo "<td style=\"background-color:".$couleur."; color:".$couleurtexte."\">".$type_name."</td>\n";
            echo "<td>{$col[$i][4]}</td>\n";
            echo "<td>\n";
            if ($disponible == '2')
                echo get_vocab("all");
            if ($disponible == '3')
                echo get_vocab("gestionnaires_et_administrateurs");
            if ($disponible == '5')
                echo get_vocab("only_administrators");
            echo "</td>\n";
            $themessage = get_vocab("confirm_del");
            echo "<td><a href='admin_type.php?&amp;type_del={$col[$i][2]}&amp;action_del=yes' onclick='return confirmlink(this, \"{$col[$i][1]}\", \"$themessage\")'><span class='glyphicon glyphicon-trash'></span></a></td>";
        // Fin de la ligne courante
            echo "</tr>";
        }
    }
    echo "</table>";
}
// Test de cohérence des types de réservation
$res = grr_sql_query("SELECT DISTINCT type FROM ".TABLE_PREFIX."_entry ORDER BY type");
if ($res)
{
	$liste = "";
	foreach($res as $row)
	{
		$test = grr_sql_query1("SELECT type_letter FROM ".TABLE_PREFIX."_type_area where type_letter='".$row['type']."'");
		if ($test == -1)
			$liste .= $row['type']." ";
	}
	if ($liste != "")
	{
		echo "<br /><table border=\"1\" cellpadding=\"5\"><tr><td><p><font color=\"red\"><b>".get_vocab('admin_type_msg2')."</b></font></p>";
		echo "<p>".get_vocab('admin_type_msg3')."<b>".get_vocab('admin_type_msg4').$liste."</b>";
		echo "<br /><br />".get_vocab('admin_type_msg5')."</p></td></tr></table>";
	}
}
// fin de l'affichage de la colonne de droite et de la page
echo "</div></section></body></html>";
?>