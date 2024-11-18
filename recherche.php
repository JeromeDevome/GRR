<?php
/**
 * recherche.php
 * interface permettant la recherche de ressources disponibles dans un domaine
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2024-11-18 16:02$
 * @author    Laurent Delineau & JeromeB & Yan Naessens
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
$grr_script_name = "recherche.php";

include "include/connect.inc.php";
include "include/config.inc.php";
include "include/misc.inc.php";
include "include/$dbsys.inc.php";
include "include/mrbs_sql.inc.php";
include "include/functions.inc.php";

require_once("./include/settings.class.php");
//Chargement des valeurs de la table settings
if (!Settings::load())
	die("Erreur chargement settings");
//Fonction relative à la session
require_once("./include/session.inc.php");
//Si il n'y a pas de session créée, on déconnecte l'utilisateur.
// Resume session
include "include/resume_session.php";
$user_id = getUserName();
// Paramètres langage
include "include/language.inc.php";
//Lien de retour
$back = (isset($_SERVER['HTTP_REFERER']))? htmlspecialchars_decode($_SERVER['HTTP_REFERER'], ENT_QUOTES): page_accueil();

// à discuter :
//Renseigne les droits de l'utilisateur, si les droits sont insuffisants, l'utilisateur est averti.
if (!verif_access_search($user_id))
{
	showAccessDenied($back);
	exit();
}
/* formulaire : 
- date de début et date de fin
- sélecteur de site et de domaine
réponse :
- tableau de ressources libres sur le créneau indiqué
- tableau des ressources occupées avec leur date de disponibilité (à partir de ...)
*/
// Intervalle de recherche
$From_day = getFormVar("From_day",'int',date("d"));
$From_month = getFormVar("From_month",'int',date("m"));
$From_year = getFormVar("From_year",'int',date("Y"));
$To_day = getFormVar("To_day",'int',date("d"));
$To_month = getFormVar("To_month",'int',date("m")+1);
$To_year = getFormVar("To_year",'int',date("Y"));
$multisite = Settings::get("module_multisite") == "Oui";
$id_area = getFormVar("id_area","int",-1);
$id_area = intval($id_area);
$id_site = getFormVar("id_site","int",-1);
if ($id_site != -1)
	$id_site = intval($id_site);
elseif (($id_area != -1) && $multisite)
    $id_site = mrbsGetAreaSite($id_area);
// sites
if ($multisite)
{
	$sites = grr_sql_query("SELECT id,sitecode,sitename FROM ".TABLE_PREFIX."_site ORDER BY sitename ASC");
  $nb_site = grr_sql_count($sites);
}
// domaines
if ($multisite)
{
    if ($id_site != -1)
        $sql = "SELECT id, area_name FROM ".TABLE_PREFIX."_area a 
        JOIN ".TABLE_PREFIX."_j_site_area j
        ON a.id = j.id_area
        WHERE j.id_site = $id_site
        ORDER BY order_display";
    else
        $sql ="";
}
else {
    $sql = "SELECT id, area_name FROM ".TABLE_PREFIX."_area ORDER BY order_display";
}
if ($sql != "")
    $areas = grr_sql_query($sql);
else 
    $areas = array();
if(($id_area != -1)&& isset($_POST["valid"])){
  if(isset($From_day) && isset($From_month) && isset($From_year))
    $debut = mktime(0,0,0,$From_month,$From_day,$From_year);
  else
    $debut = mktime(0,0,0,date("m"),date("d"),date("Y"));
  if(isset($To_day) && isset($To_month) && isset($To_year))
    $fin = mktime(23,59,59,$To_month,$To_day,$To_year);
  else
    $fin = mktime(23,59,59,date("m"),date("d"),date("Y"));
  $occupe = array();// tableau contenant FALSE si la ressource est libre sur le créneau, sinon la date de fin de la dernière réservation
  $sql = "SELECT id, room_name FROM ".TABLE_PREFIX."_room WHERE area_id=? ORDER BY order_display";
  $res = grr_sql_query($sql,"i",[$id_area]);
  $nb_occ = 0;
  if($res){
    $nb_ress = grr_sql_count($res);
    foreach($res as $room){
      $sql = "SELECT id,name,end_time FROM ".TABLE_PREFIX."_entry WHERE start_time < ? AND end_time > ? AND room_id = ? ORDER BY start_time ";
      $resas = grr_sql_query($sql,"iii",[$fin,$debut,$room["id"]]);
      if($resas){
        if(grr_sql_count($resas) == 0)
          $occupe[$room["id"]] = [$room["room_name"],FALSE];
        else{
          $last_resa = grr_sql_row($resas,grr_sql_count($resas)-1);
          $occupe[$room["id"]] = [$room["room_name"],TRUE,$last_resa[1],$last_resa[2]];
          $nb_occ++;
        }
      }
      else
        fatal_error(0,grr_sql_error());
    }
  }
  else
    fatal_error(0,grr_sql_error());
}
// html
start_page_w_header();
// formulaire
if(isset($_GET["choix"]) || ($id_site != -1) || ($id_area != -1)){
  echo "<div class='center'><h1>".get_vocab('recherche_titre')."</h1></div>";
  // sélecteur de site si multisite
  if ($multisite){
      if ($nb_site > 1)
      {
          echo '<div class="center">
          <form id="site" action="./recherche.php">
          <label for="liste_site">'.get_vocab('sites').get_vocab('deux_points').'</label>
               <select id="liste_site" name="id_site" onchange="site_go()">
                 <option value="-1">'.get_vocab('choose_a_site').'</option>'."\n";
                  foreach($sites as $s)
                  {
                      echo '<option value="'.$s['id'].'"';
                      if ($id_site == $s['id'])
                          echo ' selected ';
                      echo '>'.htmlspecialchars($s['sitename']);
                      echo '</option>'."\n";
                  }
          echo '</select>
          <script type="text/javascript">
          <!--
              function site_go()
              {
                  box = document.getElementById("site").id_site;
                  destination = "./recherche.php"+"?id_site="+box.options[box.selectedIndex].value;
                  location.href = destination;
              }
          // -->
          </script>
          </form>
          </div>';
      }
      else
      { // un seul site accessible
          $row = grr_sql_row($sites, 0);
          echo '<p>
                  <b>'.get_vocab('site').get_vocab('deux_points').$row[2].'</b>
              </p>';
      $id_site = $row[0];
      }
      grr_sql_free($sites);
  }
  // sélecteur de domaine (en mode multisite, si le site est choisi)
  if (!empty($areas)){
    echo "<div class='center'>";
    echo '<form id="area" action="./recherche.php">';
    echo "<label for='area_list'>".get_vocab("areas")."&nbsp;</label>";
    echo "<select id='area_list' name=\"area\" onchange=\"area_go()\">\n";
    echo "<option value=\"recherche.php?id_area=-1\">".get_vocab('select')."</option>\n";
    foreach($areas as $a){
        $selected = ($a['id'] == $id_area) ? " selected " : "";
    echo "<option $selected value=\"".$a['id']."\">" . htmlspecialchars($a['area_name'])."</option>\n";
    }
    echo "</select>";
    grr_sql_free($areas);
  }
  echo '  
      <script type="text/javascript">
      <!--
          function area_go()
          {
              box = document.getElementById("area").area;
              destination = "./recherche.php"+"?id_area="+box.options[box.selectedIndex].value;
              location.href = destination;
          }
      // -->
      </script>
  </form></div>';
  // intervalle de recherche et validation
  if($id_area != -1){
    echo "<div class='center'>";
    echo "<form id='main' action=\"./recherche.php\" method='POST'>";
    echo "<div>";
    echo "<label>".get_vocab('report_start').get_vocab("deux_points")."<br/>";
    genDateSelector("From_", $From_day, $From_month, $From_year,"");
    echo "</label>";
    echo "</div>";
    echo "<div>";
    echo "<label>".get_vocab('report_end').get_vocab("deux_points")."<br/>";
    genDateSelector("To_", $To_day, $To_month, $To_year,"");
    echo "</label>";
    echo "</div>";
    echo "<input type='hidden' name='id_area' value='".$id_area."' />";
    echo "<input type='submit' name='valid' value='".get_vocab("OK")."' />";
    echo "</form>";
    echo "</div>";
  }
  // tableau des résultats
  if(isset($occupe)){
    echo "<div class='container'>";
    echo "<table class='table table-bordered'>";
    echo "<caption class='bg-info'>";
    echo get_vocab('recherche_res1').$From_day."/".$From_month."/".$From_year.get_vocab('recherche_res2').$To_day."/".$To_month."/".$To_year.", ".$nb_ress - $nb_occ.get_vocab('recherche_res3').$nb_ress;
    echo "</caption>";
    echo "<tbody>";
    foreach($occupe as $key => $value){
      if($value[1] === FALSE){
        echo "<tr class='vacance'><td>".$value[0]."</td><td class='CC'>"."libre"."</td></tr>";
      }
      else{
        echo "<tr class='ferie'><td>".$value[0]."</td><td class='CC'>".get_vocab('recherche_res4').time_date_string($value[3],$dformat)."<br/>";
        echo get_vocab('recherche_res5').get_vocab("deux_points").$value[2]."</td></tr>";
      }
    }
    echo "</tbody>";
    echo "</table>";
    echo "</div>";
  }
}
// choix initial
else{
  echo "<div class='center'>";
  echo "<a class='btn btn-default' href=\"./recherche.php?choix=ressource\">".get_vocab("recherche_choix_r")."</a>";
  echo "<a class='btn btn-default' href=\"./report.php\">".get_vocab("recherche_choix_s")."</a>";
  echo "</div>";
}
end_page();
?>