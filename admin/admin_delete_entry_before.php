<?php
/**
 * admin_delete_entry_before.php
 * Interface permettant à l'administrateur de supprimer des réservations après une date donnée
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2024-03-17 14:22$
 * @author    JeromeB & Yan Naessens & Denis Monasse
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

$grr_script_name = "admin_delete_entry_before.php";

include "../include/admin.inc.php";

$back = (isset($_SERVER['HTTP_REFERER']))? htmlspecialchars_decode($_SERVER['HTTP_REFERER'], ENT_QUOTES) : "./admin_accueil.php" ;
$_SESSION['chemin_retour'] = "admin_accueil.php";

check_access(5, $back);

$etape = 0;
$sql="SELECT id, area_name FROM ".TABLE_PREFIX."_area ORDER BY order_display";
$res = grr_sql_query($sql);
if (!$res) 
  fatal_error(0, grr_sql_error());
else{
  $All_areas = array();
  foreach($res as $row){
    $All_areas[$row['id']] = $row['area_name'];
  }
}
grr_sql_free($res);
if(isset($_POST['select'])){
  $etape = 1;
  $selected_areas = array();
  foreach($All_areas as $i => $area_name){
    if(isset($_POST["area".$i]))
      $selected_areas[$i] = $area_name;
  }
  $end_month = intval($_POST['end_month']);
  $end_day = intval($_POST['end_day']);
  $end_year = intval($_POST['end_year']);
  $endtime=mktime(0, 0, 0, $end_month,$end_day,$end_year);
}
elseif(isset($_POST['delete'])){// suppression effective
  $etape = 2;
  $rapport ="";
  $selected_areas = array();
  foreach($All_areas as $i => $area_name){
    if(isset($_POST["area".$i]))
      $selected_areas[$i] = $area_name;
  }
  $endtime = intval($_POST['endtime']);
  foreach($selected_areas as $i => $area_name){
    $res = grr_sql_query("SELECT id, room_name FROM `".TABLE_PREFIX."_room` WHERE `area_id`=? ","i",[$i]);
    foreach($res as $room){
      $cmd = grr_sql_command("DELETE FROM `".TABLE_PREFIX."_entry` WHERE `end_time`<=? AND `room_id`=? ","ii",[$endtime,$room['id']]);
      if($cmd < 0)
        $rapport .= get_vocab('delete_entry_error').$room['room_name']."<br/>";
      elseif($cmd > 0)
        $rapport .= get_vocab('delete_entry_before1').date_time_string($endtime,$dformat).get_vocab('delete_entry_before2').$room['room_name'].get_vocab('delete_entry_before3')."<br/>";
    }
  }
  if($rapport == "")
    $rapport = get_vocab('no_entry_deleted');
}

// code html
# print the page header
start_page_w_header('', '', '', $type = 'with_session');
// Affichage de la colonne de gauche
include 'admin_col_gauche2.php';
// Affichage de la colonne de droite 
echo "<div class='col-md-9 col-sm-8 col-xs-12'>";
echo '<h3>'.get_vocab('delete_entry_before').'</h3><hr />'.PHP_EOL;
// étape 0 : entrer les paramètres
if($etape == 0){
  echo '<p>'.get_vocab('delete_entry_before_expl').'</p><hr />';
  echo '<p>'.get_vocab('delete_entry_warn').'</p>';
  echo '<form action="admin_save_mysql.php" method="get">
            <input type="hidden" name="flag_connect" value="yes" />
            <input type="submit" value="'.get_vocab('submit_backup').'" />
        </form>';
  echo '<hr />';
  echo '<form enctype="multipart/form-data" action="./admin_delete_entry_before.php" id="nom_formulaire" method="post" >'.PHP_EOL;
  echo '<input type="hidden" name="select" value="1" />'.PHP_EOL;
  echo get_vocab('delete_entry_areas')."<br />".PHP_EOL;
  echo '<br />';
  // affichage et sélection des domaines concernés par la suppression 
  if(count($All_areas) != 0) {
    echo '<p>';
    foreach($All_areas as $i => $area_name) {
      echo '<input type="checkbox" name="area'.$i.'" value="'.$i.'"  />';
      echo "&nbsp;".htmlspecialchars($area_name);
      echo "<br />".PHP_EOL;
    }
    echo "</p>\n";
  }
 	echo '<p><br />'.get_vocab('delete_entry_start').'</p>';
  $typeDate = 'end_';
  $day   = date("d");
  $month = date("m");
  $year  = date("Y"); //par défaut on propose la date du jour
  echo '<div class="col col-xs-12">'.PHP_EOL;
  echo '<div class="form-inline">'.PHP_EOL;
  genDateSelector('end_', $day, $month, $year, 'more_years');
  echo '<input type="hidden" disabled="disabled" id="mydate_'.$typeDate.'">'.PHP_EOL;
  echo '</div>'.PHP_EOL;
  echo '</div>'.PHP_EOL;
  echo '<div class="center">'.PHP_EOL;
  echo '<input type="submit" id="select" value="'.get_vocab('delete_entry_go').'" /></div>'.PHP_EOL;
  echo '</form>';
}
elseif($etape == 1){
  echo '<p>'.get_vocab('delete_entry_before_confirm').get_vocab('deux_points');
  echo time_date_string($endtime,$dformat)."</p>";
  echo '<form action="./admin_delete_entry_before.php" method="POST">';
  echo '<p>';
  foreach($selected_areas as $i => $area_name){
    echo '<input type="hidden" name="area'.$i.'" value="'.$i.'" />';
    echo $area_name.'<br />';
  }
  echo '</p>';
  echo '<input type="hidden" name="delete" value="1" />';
  echo '<input type="hidden" name="endtime" value="'.$endtime.'" />';
  echo '<div class="center">'.PHP_EOL;
  echo '<input type="submit" class="btn btn-danger" id="select" value="'.get_vocab('delete_entry_go').'" />&nbsp;';
  echo '<input type="button" class="btn btn-primary" value="'.get_vocab("cancel").'" onclick=\'window.location.href="./admin_accueil.php"\' />';
  echo '</div>'.PHP_EOL;
  echo '</form>';
}
elseif($etape == 2){
  echo '<p>'.get_vocab('delete_entry_before_report').get_vocab('deux_points').time_date_string($endtime,$dformat).'<br />';
  echo $rapport;
  echo '</p>';
}
// fin de l'affichage de la colonne de droite
echo "</div>";
// et de la page
end_page();
?>