<?php
/**
 * deleteFile.php
 * Utilitaire de suppression d'un fichier attaché à une réservation
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2026-06-04 16:33$
 * @author    Cédric Berthomé & Yan Naessens
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
$grr_script_name = 'deleteFile.php';

include "include/connect.inc.php";
include "include/config.inc.php";
include "include/misc.inc.php";
include "include/$dbsys.inc.php";
include "include/mrbs_sql.inc.php";
include "include/functions.inc.php";
include "include/settings.class.php";

$id = getFormVar("id","int",-1);
$resa = getFormVar("resa","int",-1);
$msg = "";

if ($id != -1){
  $id = intval($id);
  $sql = "SELECT file_name FROM ".TABLE_PREFIX."_files where id = ?";
  $res = grr_sql_query($sql,"i",[$id]);
  if($res){
    $name = grr_sql_row($res,0);
    // prépare chemin du fichier à effacer
    $uploadDir = realpath(".")."/images/";
    $toDelFile = $uploadDir.$name[0];
    //prépare la requête de suppression
    $delReq = 'delete FROM '.TABLE_PREFIX.'_files where id = ?';
    //vérifie si le fichier existe
    if (@file_exists($toDelFile)){
      // efface le fichier du serveur
      if (unlink($toDelFile)){
        if (grr_sql_command($delReq,"i",[$id]) < 0){
          $msg = "Erreur de suppression dans la base de donnée.";
        }
        else{
          $msg = "Fichier supprimé.";
        }
      }
      else{
        $msg = "Erreur, le fichier n'a pu être supprimé.";
      }
    }
    else{
      $msg = "Le fichier n'existe pas, maj de la base de donnée.";
      // fichier n'existe pas, efface sa référence de la base de donnée.
      if (grr_sql_command($delReq,"i",[$id]) < 0){
        $msg.= "<br/>Erreur de suppression dans la base de donnée.";
      }
      else{
        $msg.= "La base de donnée à été corrigée avec succès.";
      }
    }
    grr_sql_free($res);
  }
  else
    $msg = "Erreur de lecture en base de données.";
}
else {
  $msg = "Erreur, aucune donnée reçue.";
}
$_SESSION['displ_msg'] = 'yes';
affiche_pop_up($msg,"user");
// calcul du chemin de retour vers la page de la réservation
$res = grr_sql_query("SELECT start_time, room_id FROM ".TABLE_PREFIX."_entry WHERE id = ?","i",[$resa]);
if(!$res){
  $msg .= '<br/>'.'Erreur, réservation non trouvée';
  header("Location: week_all.php?msg=$msg");
  die();
}
else{
  $row = grr_sql_row($res,0);
  $day = date("d",$row[0]);
  $month = date("m",$row[0]);
  $year = date("Y",$row[0]);
  $room_id = intval($row[1]);
  header("Location: week.php?day=$day&month=$month&year=$year&room=$room_id&msg=$msg");
  die();
}
?>