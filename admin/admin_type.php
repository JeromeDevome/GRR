<?php
/**
 * admin_type.php
 * Interface de gestion des types de réservations
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2024-11-03 18:37$
 * @author    JeromeB & Laurent Delineau & Yan Naessens & J.-P. Gay
 * @copyright Copyright 2003-2024 Team DEVOME - JeromeB
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
	$type_id = grr_sql_query1("SELECT type_letter FROM ".TABLE_PREFIX."_type_area WHERE id = ?","i",[$_GET['type_del']]);
	$test1 = grr_sql_query1("SELECT count(id) FROM ".TABLE_PREFIX."_entry WHERE type= ?","s",[$type_id]);
	$test2 = grr_sql_query1("SELECT count(id) FROM ".TABLE_PREFIX."_repeat WHERE type=?","s",[$type_id]);
	if (($test1 != 0) || ($test2 != 0))
	{
		$msg = get_vocab('admin_type_msg1');
	}
	else
	{
		$sql = "DELETE FROM ".TABLE_PREFIX."_type_area WHERE id=?";
		if (grr_sql_command($sql,"i",[$_GET['type_del']]) < 0)
			fatal_error(1, "<p>" .$_SESSION['msg_a_afficher']);
		$sql = "DELETE FROM ".TABLE_PREFIX."_j_type_area WHERE id_type=?";
		if (grr_sql_command($sql,"i",[$_GET['type_del']]) < 0)
			fatal_error(1, "<p>" .$_SESSION['msg_a_afficher']);
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
// tableau des types existants
$col = array();
$sql = "SELECT id, type_name, order_display, couleurhexa, type_letter, disponible, couleurtexte FROM ".TABLE_PREFIX."_type_area
ORDER BY order_display,type_letter";
$res = grr_sql_query($sql);
if(!$res)
  fatal_error(0,$grr_sql_error);
else{
  foreach($res as $row){
    $col[] = $row;
  }
  $nb_lignes = count($col);
}
// Test de cohérence des types de réservation
$missing_types = array();
$res = grr_sql_query("SELECT DISTINCT type FROM ".TABLE_PREFIX."_entry ORDER BY type");
if ($res)
{
	foreach($res as $row)
	{
		$test = grr_sql_query1("SELECT type_letter FROM ".TABLE_PREFIX."_type_area where type_letter=?","s",[$row['type']]);
		if ($test == -1)
			$missing_types[] = $row['type'];
	}
	if (!empty($missing_types))
	{
    $resas_ss_type = array();
    foreach($missing_types as $type){
      $sql = "SELECT id FROM ".TABLE_PREFIX."_entry WHERE type=?";
      $resa = grr_sql_query($sql,"s",[$type]);
      if($resa){
          foreach($resa as $no){
              $resas_ss_type[] = $no['id'];
          }
      }
    }
	}
}
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
echo '<div class="col col-sm-6 col-xs-12">'.PHP_EOL;
echo '<input class="form-control" type="text" id="exclude_type_in_views_all" name="exclude_type_in_views_all" value="'.Settings::get('exclude_type_in_views_all').'" size="30">'.PHP_EOL;
echo '</div>'.PHP_EOL;
echo '<div class="col col-sm-6 col-xs-12">'.PHP_EOL;
echo '<input class="btn btn-primary" type="submit" name="ok" value="'.get_vocab('save').'" style="font-variant: small-caps;"/>'.PHP_EOL;
echo '</div>'.PHP_EOL;
echo '</div>'.PHP_EOL;
echo "</div>\n";
echo "</form>".PHP_EOL;
// ------------------------------------------ ModifExclure Fin-2

echo "<hr />\n";
echo "<br /><p>".get_vocab('admin_type_explications')."</p>";

echo "<a class='btn btn-success' href=\"admin_type_modify.php?id=0\">".get_vocab("display_add_type")."</a>";
echo "<br />\n";
echo "<br />\n";
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
    foreach($col as $row)
        {
            echo "<tr>\n";
            echo "<td>".$row['type_letter']."</td>\n";
            echo "<td><a href='admin_type_modify.php?id_type=".$row['id']."'>".$row['type_name']."</a></td>\n";
            echo "<td style=\"background-color:".$row['couleurhexa']."; color:".$row['couleurtexte']."\">".$row['type_name']."</td>\n";
            echo "<td>".$row['order_display']."</td>\n";
            echo "<td>\n";
            if ($row['disponible'] == '2')
                echo get_vocab("all");
            if ($row['disponible'] == '3')
                echo get_vocab("gestionnaires_et_administrateurs");
            if ($row['disponible'] == '5')
                echo get_vocab("only_administrators");
            echo "</td>\n";
            $themessage = get_vocab("confirm_del");
            echo "<td><a href='admin_type.php?&amp;type_del=".$row['id']."&amp;action_del=yes' onclick='return confirmlink(this, \"".$row['type_name']."\", \"$themessage\")'><span class='glyphicon glyphicon-trash'></span></a></td>";
            echo "</tr>";
        }
    echo "</table>";
}
// Test de cohérence des types de réservation
	if (!empty($missing_types))
	{
    echo "<div class='alert alert-danger'><b>";
    echo "<p>".get_vocab('admin_type_msg2')."</p>";
    echo "<p>".get_vocab('admin_type_msg3')."</p>";
    if(in_array('',$missing_types)){
        echo "<p>".get_vocab('admin_type_msg6')."</p>";
        echo "<p>".get_vocab('admin_type_msg7')."</p>";
    }
    echo "<p>".get_vocab('admin_type_msg4');
    echo $missing_types[0];
    if(count($missing_types)>1)
      for($i = 1;$i < count($missing_types);$i++){
        echo ", ".$missing_types[$i];
      }
    echo "</p>";
    echo "<p>".get_vocab('admin_type_msg5')."</p>";
    foreach($resas_ss_type as $no){
        echo "<a href='../edit_entry.php?id=".$no."'>".get_vocab('admin_type_msg8').$no."</a><br/>";
    }
    echo "</b></div>";
	}
// fin de l'affichage de la colonne de droite et de la page
echo "</div></section></body></html>";
?>