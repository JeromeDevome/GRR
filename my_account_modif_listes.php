<?php
/**
 * my_account_modif_listes.php
 * Page "Ajax" utilisée pour générer les listes de domaines et de ressources, en liaison avec my_account.php
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2026-10-26 09:25$
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
//Arguments passés par la méthode GET :
//$use_site : 'y' (fonctionnalité multisite activée) ou 'n' (fonctionnalité multisite désactivée)
//$id_site : l'identifiant du site
//$id_area : identifiant du domaine
//$default_area : domaine par défaut
//$default_room : ressource par défaut
//$session_login : identifiant
//$type :   'ressource'-> on actualise la liste des ressources
//      'domaine'-> on actualise la liste des domaines
//$action : 1-> on actualise la liste des ressources
//      2-> on vide la liste des ressources

include "include/admin.inc.php";
// vérification du droit d'accès
$session_login = getFormVar('session_login',"string",getUserName());
$user_id = clean_input($session_login);
if ((authGetUserLevel($user_id, -1) < 1)||(!isAjax()))
{
  showAccessDenied("");
  exit();
}
// chargement et vérification des autres paramètres
$use_site = getFormVar("use_site","string","n");
$id_site  = getFormVar("id_site","int",-1);
$id_site  = intval($id_site);
$id_area  = getFormVar("id_area",'int',0);
$id_area  = intval($id_area);
$default_area = getFormVar("default_area","int",0);
$default_area = intval($default_area);
$default_room = getFormVar("default_room","int",0);
$default_room = intval($default_room);
$type = getFormVar("type","string","");
$type = clean_input($type);
$action = getFormVar("action",'int',2);

if ($type == "domaine")
{
  if ($use_site == 'y'){
    // on a activé les sites
    if ($id_site != -1){
      $sql = "SELECT a.id, a.area_name,a.access,a.order_display
    FROM ".TABLE_PREFIX."_area a JOIN ".TABLE_PREFIX."_j_site_area j
        ON a.id=j.id_area
    WHERE j.id_site=$id_site
    ORDER BY a.order_display, a.area_name";
    } 
    else{
      $sql = "";
    }
  }
  else{
    $sql = "SELECT id, area_name,access
    FROM ".TABLE_PREFIX."_area
    ORDER BY order_display, area_name";
  }
  if (($id_site!=-1) || ($use_site=='n')){
    $resultat = grr_sql_query($sql);
  }
  $display_liste = '<div class="form-group"><label class="control-label col-md-3 col-sm-3 col-xs-4" for="id_area">'.get_vocab('default_area').'</label><div class="col col-md-4 col-sm-6 col-xs-8"><select class="form-control" id="id_area" name="id_area" onchange="modifier_liste_ressources(1)"><option value="-1">'.get_vocab('choose_an_area').'</option>'."\n";
  if (($id_site!=-1) || ($use_site=='n')){

    foreach($resultat as $row)
    {
      if (authUserAccesArea($session_login, $row['id']) != 0)
      {
        $display_liste .= '<option value="'.$row['id'].'"';
        if ($default_area == $row['id'])
          $display_liste .= ' selected="selected" ';
        $display_liste .= '>'.htmlspecialchars($row['area_name']);
        if ($row['access']=='r')
          $display_liste .= ' ('.get_vocab('restricted').')';
        $display_liste .= '</option>'."\n";
      }
    }
  }
  $display_liste .= '</select>';
  $display_liste .= '</div>'.PHP_EOL.'</div>'.PHP_EOL;
}
if ($_GET['type'] == "ressource")
{
  // affichage minimal : choix des plannings généraux par défaut
  $display_liste = '<div class="form-group"><label class="control-label col-md-3 col-sm-3 col-xs-4" for="id_room">'.get_vocab('default_room').'</label><div class="col col-md-4 col-sm-6 col-xs-8"><select class="form-control" name="id_room" id="id_room"><option value="-1"';
    if ($default_room == -1)
      $display_liste .= ' selected="selected" ';
    $display_liste .= ' >'.get_vocab('default_room_all').'</option>'."\n".
    '<option value="-2"';
    if ($default_room == -2)
      $display_liste .= ' selected="selected" ';
    $display_liste .= ' >'.get_vocab('default_room_week_all').'</option>'."\n".
    '<option value="-3"';
    if ($default_room == -3)
      $display_liste .= ' selected="selected" ';
    $display_liste .= ' >'.get_vocab('default_room_month_all').'</option>'."\n".
    '<option value="-4"';
    if ($default_room == -4)
      $display_liste .= ' selected="selected" ';
    $display_liste .= ' >'.get_vocab('default_room_month_all_bis').'</option>'."\n";
  if (($action == 1)&&($id_area != 0)) // on affiche alors les ressources accessibles dans le domaine sélectionné
  {
    // on ne cherche pas parmi les ressources invisibles pour l'utilisateur
    $tab_rooms_noaccess = verif_acces_ressource($user_id, 'all');
    if(empty($tab_rooms_noaccess))
      $list_noaccess = "(0)";
    else
      $list_noaccess = array2list($tab_rooms_noaccess);
    //impossible d'utiliser une requête préparée ici
    $sql = "SELECT id, room_name 
            FROM ".TABLE_PREFIX."_room 
            WHERE area_id=$id_area AND id NOT IN $list_noaccess
            ORDER BY order_display,room_name ";
    $resultat = grr_sql_query($sql);
    if($resultat){
      foreach($resultat as $row){
        $display_liste .=  '<option value="'.$row['id'].'"';
        if ($default_room == $row['id'])
          $display_liste .= ' selected="selected" ';
        $display_liste .= '>'.htmlspecialchars($row['room_name']).' '.get_vocab('display_week');
        $display_liste .= '</option>'."\n";
      }
    }
  }
  $display_liste .= '</select></div></div>'."\n";
}
header("Content-Type: text/html;charset=utf-8");
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
echo $display_liste;
?>