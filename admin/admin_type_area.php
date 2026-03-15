<?php
/**
 * admin_type_area.php
 * interface de gestion des types de réservations pour un domaine
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2026-01-26 16:36$
 * @author    Laurent Delineau & JeromeB & Yan Naessens
 * @copyright Copyright 2003-2026 Team DEVOME - JeromeB
 * @link      http://www.gnu.org/licenses/licenses.html
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */
$grr_script_name = "admin_type_area.php";

include "../include/admin.inc.php";

// Initialisation
$back = (isset($_SERVER['HTTP_REFERER']))? htmlspecialchars_decode($_SERVER['HTTP_REFERER'], ENT_QUOTES) : "./admin_accueil.php" ;
$day   = date("d");
$month = date("m");
$year  = date("Y");
check_access(4, $back);
$id_area = isset($_GET["id_area"]) ? intval($_GET["id_area"]) : NULL;

// Gestion du retour à la page précédente sans enregistrement
if (isset($_GET['change_done']))
{
	Header("Location: "."admin_room.php?id_area=".$id_area);
	exit();
}
if ((isset($_GET['msg'])) && isset($_SESSION['displ_msg']) && ($_SESSION['displ_msg'] == 'yes') )
	$msg = $_GET['msg'];
else
	$msg = '';

$nb_lignes = 0;
$sql = "SELECT id, type_name, order_display, couleurhexa, type_letter, couleurtexte FROM ".TABLE_PREFIX."_type_area
ORDER BY order_display, type_letter";
$res = grr_sql_query($sql);
if($res){
  //
  // Enregistrement
  //
  if (isset($_GET['valider']))
  {
    $nb_types_valides = 0;
    if ($res)
    {
      foreach($res as $row)
      {
        if (isset($_GET[$row['id']]))
        {
          $nb_types_valides ++;
          $del = grr_sql_command("DELETE FROM ".TABLE_PREFIX."_j_type_area WHERE id_area=? AND id_type =?","ii",[$id_area,$row['id']]);
        }
        else
        {
          $type_si_aucun = $row['id'];
          $test = grr_sql_query1("SELECT count(id_type) FROM ".TABLE_PREFIX."_j_type_area WHERE id_area =? AND id_type =?","ii",[$id_area,$row['id']]);
          if ($test == 0)
          {
            $sql1 = "INSERT INTO ".TABLE_PREFIX."_j_type_area SET id_area=?, id_type =?";
            if (grr_sql_command($sql1,"ii",[$id_area,$row['id']]) < 0)
              fatal_error(1, "<p>" . $_SESSION['msg_a_afficher']);
            //}
          }
        }
      }
    }
    if ($nb_types_valides == 0)
    {
      // Aucun type n'a été sélectionné. Dans ce cas, on impose au moins un type :
      $del = grr_sql_command("DELETE FROM ".TABLE_PREFIX."_j_type_area WHERE id_area=? AND id_type =? ","ii",[$id_area,$type_si_aucun]);
      $msg = get_vocab("def_type_non_valide");
    }
    // Type par défaut :
    // On enregistre le nouveau type par défaut :
    $reg_type_par_defaut = grr_sql_command("UPDATE ".TABLE_PREFIX."_area SET id_type_par_defaut=? WHERE id=?","ii",[intval($_GET['id_type_par_defaut']),$id_area]);
  }
  $area_name = grr_sql_query1("SELECT area_name FROM ".TABLE_PREFIX."_area WHERE id=?","i",[$id_area]);
  // données initiales
  $nb_lignes = grr_sql_count($res);
  $col = array();
  foreach($res as $row){
    $col[$row['id']] = [$row['type_letter'],$row['type_name'],$row['order_display'],$row['couleurhexa'],$row['couleurtexte']];
    $test = grr_sql_query1("SELECT count(id_type) FROM ".TABLE_PREFIX."_j_type_area WHERE id_area =? AND id_type =? ","ii",[$id_area,$row['id']]);
    $col[$row['id']]['valide'] = ($test < 1);
    $test = grr_sql_query1("SELECT id_type_par_defaut FROM ".TABLE_PREFIX."_area WHERE id =?","i",[$id_area]);
    $col[$row['id']]['defaut'] = ($test == $row['id']);
  }
  // aucun type par défaut ?
  $aucun_type_par_defaut = FALSE;
  $test = grr_sql_query1("SELECT id_type_par_defaut FROM ".TABLE_PREFIX."_area WHERE id =?","i",[$id_area]);
  $aucun_type_par_defaut = ($test <= 0);
}
else{
  $_SESSION['displ_msg']='yes';
  $msg = get_vocab('failed_to_acquire');
}
// code HTML
start_page_w_header("", "", "", $type="with_session");
include "admin_col_gauche2.php";
affiche_pop_up($msg,"admin");
echo "<div class=\"col-md-9 col-sm-8 col-xs-12\">";
echo "<h2>".get_vocab('admin_type.php')."</h2>";
echo "<h3>".get_vocab("match_area").get_vocab('deux_points')." ".$area_name."</h3>";
if ($nb_lignes > 0)
{
  echo "<form action=\"admin_type_area.php\" id=\"type\" method=\"get\">\n";
  if (authGetUserLevel(getUserName(),-1) >= 6){
    echo "<p>";
    echo "<a class='btn btn-default' href=\"admin_type_modify.php?id=0\">".get_vocab("display_add_type")."</a>";
    echo "</p>";
  }
  echo "<p>".get_vocab("explications_active_type")."</p>";

  // Affichage du tableau
  echo "<table class='table table-bordered'><tr>\n";
  echo "<td><b>".get_vocab("type_num")."</b></td>\n";
  echo "<td><b>".get_vocab("type_name")."</b></td>\n";
  echo "<td><b>".get_vocab("type_apercu")."</b></td>\n";
  echo "<td><b>".get_vocab("type_order")."</b></td>\n";
  echo "<td><b>".get_vocab("type_valide_domaine")."</b></td>";
  echo "<td><b>".get_vocab("type_par_defaut")."</b></td>";
  echo "</tr>";
  foreach($col as $key => $row){
    echo "<tr>\n";
    echo "<td>".$row[0]."</td>\n";
    echo "<td>".$row[1]."</td>\n";
    echo "<td style=\"background-color:".$row[3]."; color:".$row[4]."\">".$row[1]."</td>\n";
    echo "<td>".$row[2]."</td>\n";
    echo "<td><input type=\"checkbox\" name=\"".$key."\" value=\"y\" ";
    if($row['valide']) echo "checked";
    echo  " /></td>";
    echo "<td><input type=\"radio\" name=\"id_type_par_defaut\" value=\"".$key."\" ";
    if($row['defaut']) echo "checked";
    echo " /></td>";
    echo "</tr>";
  }
  // type par défaut
  echo "<tr><td> </td>\n";
  echo "<td> </td>\n";
  echo "<td> </td>\n";
  echo "<td> </td>\n";
  echo "<td> </td>\n";
  echo "<td><input type=\"radio\" name=\"id_type_par_defaut\" value=\"-1\" ";
  if($aucun_type_par_defaut) echo "checked";
  echo " />".$vocab["nobody"]."    </td>";
  echo "</tr>";
  echo "</table>";
  echo "<div class='center'><input type=\"hidden\" name=\"id_area\" value=\"".$id_area."\" />";
  echo "<input class='btn btn-default' type=\"submit\" name=\"valider\" value=\"".get_vocab("save")."\" />\n";
  echo "&nbsp;<input class='btn btn-default' type=\"submit\" name=\"change_done\" value=\"".get_vocab("back")."\" />";
  echo "</div>";
  echo "</form>\n";
}
echo "</div>";
end_page();
?>