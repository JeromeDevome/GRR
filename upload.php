<?php
/**
 * upload.php
 * Utilitaire de téléversement d'un fichier attaché à une réservation
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2024-10-08 12:10$
 * @author    Cédric Berthomé & Yan Naessens
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
$grr_script_name = 'upload.php'; 

include "include/connect.inc.php";
include "include/config.inc.php";
include "include/misc.inc.php";
include "include/$dbsys.inc.php";
include "include/mrbs_sql.inc.php";
include "include/functions.inc.php";

//vérifie si des fichiers ont bien été transmis
if (isset ($_FILES) && is_array($_FILES)){
	//nombre de fichiers envoyés
	$nb = count($_FILES["myFiles"]["name"]);
  $id = getFormVar("id_entry","int",-1);
  if($id != -1) // une réservation est associée
  {
    $file = getFormVar("selectedfile","string");
    //chemin de destination
    $uploadDir = realpath(".")."/images/";
    //echo $uploadDir ;
    if ($nb > 0) {
      //echo "<br>received 1 or more files";
      for($i=0; $i<$nb ; $i++){
        echo "<p> Fichier : ".$_FILES["myFiles"]["name"][$i];
        echo "<br>Taille : ".$_FILES["myFiles"]["size"][$i];
        //Enregistre les fichiers sur le répertoire de destination et sa référence dans la bdd.
        $copie = move_uploaded_file($_FILES["myFiles"]["tmp_name"][$i], $uploadDir.$_FILES["myFiles"]["name"][$i]);
        //prepare le rename du fichier en concaténant l'id_entry de la réservation, un nombre aléatoire et l'extension du fichier.
        $fileExt = pathinfo($uploadDir.$_FILES["myFiles"]["name"][$i], PATHINFO_EXTENSION);
        $fileName = $id.mt_rand(0,9999).".".$fileExt;
        if (rename($uploadDir.$_FILES["myFiles"]["name"][$i], $uploadDir.$fileName)){
          //ajout dans la base de donnée.
          $req = "INSERT INTO ".TABLE_PREFIX."_files (id_entry, file_name, public_name) VALUES ('".$id."', '".$fileName."', '".$_FILES["myFiles"]["name"][$i]."')";
          if (grr_sql_command($req) < 0){
            echo "<br>erreur d'enregistrement sur base de donnée";
          }
          else{
            if ($copie){
              echo "<br> <span style='color:green'>Fichier enregistré</span></p>";
              header('Location: week_all.php?');
            }
            else{
              echo "<br><span style='color:red'>Erreur d'enregistrement</span></p>";
            }
          }
        }
        else{
          echo "<br><span style='color:red'>Erreur, le fichier n'a pu être renommé</span></p>";
        }
      }
    }
    else{
      echo "<br><span style='color:red'>Erreur, aucun fichier envoyé</span></p>";
    }
  }
  else
    echo '<p>'.'Erreur, aucune réservation associée</span>'."</p>";
}
else{
	echo "<br><span style='color:red'>Erreur, aucun fichier transmis</span></p>";
}
?>