<?php
/**
 * admin_delete_entry_before.php
 * Interface permettant à l'administrateur de supprimer des réservations avant une date donnée
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2021-03-13 11:50$
 * @author    JeromeB & Yan Naessens & Denis Monasse
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

$grr_script_name = "admin_delete_entry_before.php";

include "../include/admin.inc.php";

$back = (isset($_SERVER['HTTP_REFERER']))? htmlspecialchars_decode($_SERVER['HTTP_REFERER'], ENT_QUOTES) : "./admin_accueil.php" ;
$_SESSION['chemin_retour'] = "admin_config.php";

if (!Settings::load()) {
    die(get_vocab('error_settings_load'));
}

# print the page header
start_page_w_header('', '', '', $type = 'with_session');
// Affichage de la colonne de gauche
include 'admin_col_gauche2.php';
// Affichage de la colonne de droite 
echo "<div class='col-md-9 col-sm-8 col-xs-12'>";
echo '<h3>'.get_vocab('delete_entry_before').'</h3><hr />'.PHP_EOL;
if(isset($_POST['delete'])) {
    echo '<br />';
    $endtime=mktime(23, 59, 59, $_POST['end_month'],$_POST['end_day'],$_POST['end_year']);
    $sql="select id, area_name from ".TABLE_PREFIX."_area order by order_display";
    $res = grr_sql_query($sql);
    if (! $res) fatal_error(0, grr_sql_error());
    $nb_areas=grr_sql_count($res);
    for($i=0;$i < $nb_areas;$i++)
       if(isset($_POST["area".$i])){
           $rooms=grr_sql_query("SELECT id, room_name FROM `".TABLE_PREFIX."_room` WHERE `area_id`=".$_POST["area".$i]);
           for ($j = 0; ($row = grr_sql_row($rooms, $j)); $j++) 
              if(grr_sql_query("DELETE FROM `".TABLE_PREFIX."_entry` WHERE `end_time`<=".$endtime." AND `room_id`=".$row[0]))
                     echo get_vocab('delete_entry_before1').$_POST['end_day']."/".$_POST['end_month']."/".$_POST['end_year'].get_vocab('delete_entry_before2').$row[1].get_vocab('delete_entry_before3')."<br/>";
              else 
                    echo get_vocab('delete_entry_error').$row[1]."<br/>"; 
       }                    
 }
 else { //echo 'les paramètres ne sont pas acquis';
    echo '<p>'.get_vocab('delete_entry_before_expl').'</p><hr />';
    echo '<p>'.get_vocab('delete_entry_warn').'</p>';
    echo '<form action="admin_save_mysql.php" method="get">
              <input type="hidden" name="flag_connect" value="yes" />
              <input type="submit" value="'.get_vocab('submit_backup').'" />
          </form>';
    echo '<hr />';
    echo '<form enctype="multipart/form-data" action="./admin_delete_entry_before.php" id="nom_formulaire" method="post" >'.PHP_EOL;
    echo '<input type="hidden" name="delete" value="1" />'.PHP_EOL;
    echo get_vocab('delete_entry_areas')."<br />".PHP_EOL;
    // affichage et sélection des domaines concernés par la suppression 
          $sql="select id, area_name from ".TABLE_PREFIX."_area order by order_display";
          $res = grr_sql_query($sql);
          if (! $res) fatal_error(0, grr_sql_error());

          if (grr_sql_count($res) != 0) {
                  echo '<table>';
                  for ($i = 0; ($row = grr_sql_row($res, $i)); $i++) {
                      echo '<tr><td>';
                       echo '<input type="checkbox" name="area'.$i.'" value="'.$row[0].'"  />';
                       echo "&nbsp;".htmlspecialchars($row[1]);
                       echo "</td></tr>";
                  }
                  echo "</table>\n";
           }
 	echo '<p><br />'.get_vocab('delete_entry_end').'</p>';
    $typeDate = 'end_';
    $day   = date("d");
    $month = date("m");
    $year  = date("Y"); //par défaut on propose la date du jour
    echo '<div class="col-xs-12">'.PHP_EOL;
    echo '<div class="form-inline">'.PHP_EOL;
    genDateSelector('end_', $day, $month, $year, 'more_years');
    echo '<input type="hidden" disabled="disabled" id="mydate_'.$typeDate.'">'.PHP_EOL;
    echo '</div>'.PHP_EOL;
    echo '</div>'.PHP_EOL;
    echo '<div class="center">'.PHP_EOL;
    echo '<input type="submit" id="delete" value="'.get_vocab('delete_entry_go').'" /></div>'.PHP_EOL;
    echo '</form>';
    // fin de l'affichage de la colonne de droite
    echo "</div>";
    // et de la page
    end_page();
 }
?>