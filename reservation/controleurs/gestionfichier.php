<?php
/**
 * gestionfichier.php
 * Utilitaire de téléversement d'un fichier attaché à une réservation
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2026-18-01 21:30$
 * @author    Cédric Berthomé & Yan Naessens & JeromeB
 * @copyright Copyright 2003-206 Team DEVOME - JeromeB
 * @link      http://www.gnu.org/licenses/licenses.html
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */
$grr_script_name = 'gestionfichier.php'; 

include "./include/import.class.php";


$action = isset($_GET["action"]) ? intval($_GET["action"]) : NULL;
$id = getFormVar("id","int",-1);

$uploadDir = realpath(".")."/personnalisation/".$gcDossierDoc."/";


if($action == 1) // import d'un fichier
{
  $result = Import::DocumentResa($id);

  if($result != "")
  {
    $msg = $result[0];
    echo $msg;
    echo "<script>setTimeout(function() { window.location.href = '".$back."'; }, 5000);</script>";
  }


} elseif($action == 2) // suppression d'un fichier
{
  
  $msg = "";

  if ($id != -1){
    $id = intval($id);
    $sql = "SELECT file_name FROM ".TABLE_PREFIX."_files where id = $id";
    $res = grr_sql_query($sql);
    if($res){
      $name = grr_sql_row($res,0);
      // prépare chemin du fichier à effacer
      $toDelFile = $uploadDir.$name[0];
      //prépare la requête de suppression
      $delReq = "delete FROM ".TABLE_PREFIX."_files where id = $id";
      //vérifie si le fichier existe
      if (@file_exists($toDelFile)){
        // efface le fichier du serveur
        if (unlink($toDelFile)){
          if (grr_sql_command($delReq) < 0){
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
        if (grr_sql_command($delReq) < 0){
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
  header("Location: ./app.php?p=semaine_all&msg=".$msg);
}
else{
    echo "<br><span style='color:red'>Erreur, aucune action définie</span></p>";
}
?>