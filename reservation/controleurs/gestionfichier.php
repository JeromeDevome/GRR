<?php
/**
 * gestionfichier.php
 * Utilitaire de téléversement d'un fichier attaché à une réservation
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2026-06-04 15:54$
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
$id = SecuChaine::GetFormVar("id","int",-1);
$area = SecuChaine::GetFormVar("area","int",-1);
$room_id = SecuChaine::GetFormVar("room_id","int",-1);
$userName = getUserName();
$uploadDir = realpath(".")."/personnalisation/".$gcDossierDoc."/";

// données liées aux fichiers attachés
$droit_acces = SecuAccess::UserLevel($userName, $room_id);
$res = grr_sql_query("SELECT access_file, user_right, upload_file FROM ".TABLE_PREFIX."_area WHERE id =$area");
$attached_files = array();
if(!$res)
  fatal_error(0,grr_sql_error());
else{
  $level = grr_sql_row($res,0);

  $access_file = $level[0];
  $user_right = $level[1];
  $upload_file = $level[2];
}

  if ($id != 0 && $droit_acces >= $user_right && $access_file==1){
  }
  else
  {
    echo "Erreur, vous n'avez pas les droits nécessaires pour gérer les fichiers attachés à cette ressource.";
    die();
  }



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

  //récupération des informations pour rediriger vers la page de la réservation
  $res = grr_sql_query("SELECT start_time, room_id FROM ".TABLE_PREFIX."_entry WHERE id = $resa");
  if(!$res){
    $msg .= '<p>'.'Erreur, réservation non trouvée'."</p>";
  }
  else{
    $row = grr_sql_row($res,0);
    $day = date("d",$row[0]);
    $month = date("m",$row[0]);
    $year = date("Y",$row[0]);
    $room_id = intval($row[1]);

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
  }
  $_SESSION['displ_msg'] = 'yes';
  affiche_pop_up($msg,"user");
  if(isset($room_id))
    header("Location: ./app.php?p=semaine&day=$day&month=$month&year=$year&room=$room_id&msg=$msg");
  else 
    header("Location: ./app.php?p=semaine_all&msg=$msg");
}
else{
    echo "<br><span style='color:red'>Erreur, aucune action définie</span></p>";
}
?>