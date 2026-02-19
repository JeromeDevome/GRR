<?php
/**
 * upload.php
 * Utilitaire de téléversement d'un fichier attaché à une réservation
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2026-02-19 11:23$
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
        // rejette les fichiers à double extension
        if (count(explode('.', $_FILES["myFiles"]['name'][$i])) > 2) {
          echo "<br>type de fichier inconnu";
        }
        else{
          $fileExt = strtolower(pathinfo($uploadDir.$_FILES["myFiles"]["name"][$i], PATHINFO_EXTENSION));
          if(in_array($fileExt,["jpg","png","gif","pdf"])){
            //Enregistre les fichiers sur le répertoire de destination et sa référence dans la bdd.
            $copie = move_uploaded_file($_FILES["myFiles"]["tmp_name"][$i], $uploadDir.$_FILES["myFiles"]["name"][$i]);
            //prepare le rename du fichier en concaténant l'id_entry de la réservation, un nombre aléatoire et l'extension du fichier.
            $strf = ""; $str = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789_";
            srand(time()*$id);			
            for($c=0; $c<12; $c++) 
            {
              $strf .= $str[rand(0,strlen($str)-1)];
            }
            $fileName = $id.$strf.".".$fileExt;
            if (rename($uploadDir.$_FILES["myFiles"]["name"][$i], $uploadDir.$fileName)){
              //ajout dans la base de donnée.
              $req = "INSERT INTO ".TABLE_PREFIX."_files (id_entry, file_name, public_name) VALUES (?,?,?)";
              if (grr_sql_command($req,"iss",[$id,protect_data_sql($fileName),protect_data_sql(substr($_FILES["myFiles"]["name"][$i],0,50))]) < 0){
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
          else{ // rejette les fichiers d'extension différente de jpg, png, gif ou pdf
            echo "<br>type de fichier inconnu";
          }
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