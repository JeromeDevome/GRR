<?php
/**
 * admin_import_entries_csv_direct.php
 * Importe un fichier de réservations au format csv comprenant les champs : date du jour, heure de début, heure de fin, ressource, description et type
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2026-06-02 18:10$
 * @author    JeromeB & Yan Naessens & Denis Monasse & Laurent Delineau
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
$grr_script_name = "admin_import_entries_csv_direct.php";

include "../include/admin.inc.php";

$back = "./admin_accueil.php?";
$joursemaine=array("dim"=>0,"lun"=>1,"mar"=>2,"mer"=>3,"jeu"=>4,"ven"=>5,"sam"=>6);
$journumero=array(0=>"dimanche",1=>"lundi",2=>"mardi",3=>"mercredi",4=>"jeudi",5=>"vendredi",6=>"samedi");

// $long_max : doit être plus grand que la plus grande ligne trouvée dans le fichier CSV
$long_max = 8000;
    
/* ajoute_reservation()
    paramètres :
    $room_id
    $date
    $heure_deb, $minute_deb
    $heure_fin, $minute_fin
    $type
    
    fait les vérifications et si possible crée une réservation par mrbsCreateSingleEntry
    
    renvoie (TRUE, descriptif de la réservation) si une réservation a bien été créée, 
            (FALSE, descriptif de l'erreur) sinon
*/
function ajoute_reservation($room_id,$date,$heure_deb,$minute_deb,$heure_fin,$minute_fin,$description,$type)
        {
        // Initialisation du descriptif d'erreur
        $erreur = '';
        // détermination du type de réservation
        $type_id = grr_sql_query1("SELECT id FROM ".TABLE_PREFIX."_type_area WHERE type_letter=? ","s",[$type]);
        if($type_id == -1){
            $erreur .= get_vocab('type_inconnu');
        }
        // on convertit la date en année, mois, jour
        $year = substr($date,0,4);settype($year,"integer");
        $month = substr($date,5,2);settype($month,"integer");
        $day = substr($date,8,2);settype($day,"integer");
        // détermination du starttime
        $heure_deb = intval($heure_deb);
        $minute_deb = intval($minute_deb);
        $starttime = mktime($heure_deb, $minute_deb, 0, $month, $day, $year);
        // vérification du starttime 
        if($starttime < Settings::get("begin_bookings"))
            $erreur .= get_vocab('out_of_bounds');
        // détermination de endtime
        $heure_fin = intval($heure_fin);
        $minute_fin = intval($minute_fin);
        $endtime = mktime($heure_fin,$minute_fin,0,$month,$day,$year);
        // vérification de endtime
        if($endtime > Settings::get("end_bookings")){
            $erreur .= get_vocab('out_of_bounds');
        }
        if ($endtime <= $starttime)
            $erreur .= get_vocab("error_begin_end_date");
        // On récupère la valeur de $area
        $area = mrbsGetRoomArea($room_id); 
        if(($room_id<=0) || ($area<=0)) 
            $erreur .= get_vocab("error_room");
        
        if ($erreur != ''){// il y a une erreur, on sort
            return array(FALSE,$erreur);
        }
        else {
            $statut_entry = "-";
            $rep_jour_c = 0;
            $create_by = "Administrateur";
            $beneficiaire = "Administrateur"; // pourrait être passé en paramètre ?
            $benef_ext_nom = "Administrateur";
            $benef_ext_email = "";
            $beneficiaire_ext = concat_nom_email($benef_ext_nom, $benef_ext_email);
            
            $option_reservation = -1;
            
            # Acquire mutex to lock out others trying to book the same slot(s).
            if (!grr_sql_mutex_lock("".TABLE_PREFIX."_entry"))
                fatal_error(1, get_vocab('failed_to_acquire'));
            
            $date_now = time();
            $error_booking_in_past = false;
            
            // on vérifie que le créneau est bien libre
            $occupied=mrbsCheckFree($room_id, $starttime, $endtime, 0, 0,"../");
            if($occupied){ 
                return array(FALSE, get_vocab("occupied_slot").$occupied."</br>");
                $error_booking_room_out = true ;
            }
            //else{echo "créneau libre </br>";}
            // on vérifie qu'on ne réserve pas dans le passé
            $error_booking_in_past = $starttime < $date_now ;
            // Si il y a tentative de réserver dans le passé
            if ($error_booking_in_past) {
                $str_date = utf8_strftime("%d %B %Y, %H:%M", $date_now);
                $erreur=get_vocab("booking_in_past");
                $erreur.= ",".get_vocab("booking_in_past_explain") . $str_date;
                return array(FALSE,$erreur);
            }
            // l'utilisateur est gestionnaire ou admin de la ressource donc on ne modère pas !
           $entry_moderate = 0;
           $send_mail_moderate = 0; 
           $entry_type = 0; // réservation isolée
           $repeat_id = 0; 
           $name = $description;
           $overload_data = array();
           $moderate = 0;
           $keys = 0;
           $courrier = 0;
           $nbmaxparticipant = 0;
           $ecriture=mrbsCreateSingleEntry($starttime, $endtime, $entry_type, $repeat_id, $room_id, $create_by, $beneficiaire, $beneficiaire_ext, $name, $type, "", $option_reservation,$overload_data, $moderate, $rep_jour_c, $statut_entry, $keys, $courrier, $nbmaxparticipant);
           if ($ecriture == 0){// erreur lors de la création de la réservation
               return array(FALSE, get_vocab("error_creating_entry"));
           }
           else {
               $room_name = grr_sql_query1("SELECT room_name FROM ".TABLE_PREFIX."_room WHERE id =? ","i",[$room_id]);
               $message = sprintf(get_vocab('entry_recorded'),$ecriture);
               $message .= date('c',$starttime)." -> ".date('c',$endtime)." ; ".$room_name." ; ".$description." ; ".$type."</br>";
               return array(true,$message);
           }
           grr_sql_mutex_unlock("".TABLE_PREFIX."_entry");
           // Si l'utilisateur tente de réserver une ressource non disponible
            if ($error_booking_room_out) {
                $erreur.=  get_vocab("norights");
                $erreur.=  ", <b>" . get_vocab("tentative_reservation_ressource_indisponible") . "</b>";
                return array(false,$erreur);
            }
        }
}
/* lit_csv_data()
   lit le fichier csv passé en paramètre dans le formulaire
   renvoie un tableau de données, un tableau d'erreurs et le nombre de lignes lues
*/
function lit_csv_data(){
    global $long_max;
    // on commence par charger le fichier CSV dans une table provisoire grr_csv2 pour profiter des tris MySQL
    // crée la table csv2 si elle n'existe pas, la nettoie si elle existe
    $sql  = "CREATE TABLE IF NOT EXISTS `".TABLE_PREFIX."_csv2` (";
    $sql .= "`id` int(11) NOT NULL AUTO_INCREMENT,";
    $sql .= "`date` DATE NOT NULL,";
    $sql .= "`heure_deb` tinyint(4) NOT NULL,";
    $sql .= "`minute_deb` tinyint(4) NOT NULL,";
    $sql .= "`heure_fin` tinyint(4) NOT NULL,";
    $sql .= "`minute_fin` tinyint(4) NOT NULL,";
    $sql .= "`ressource` tinytext NOT NULL,";
    $sql .= "`description` tinytext NOT NULL,";
    $sql .= "`type` tinytext ,";
    $sql .= "PRIMARY KEY (`id`),";
    $sql .= "KEY `id` (`id`)";
    $sql .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci";
    if(!grr_sql_query($sql)){
        echo get_vocab("error_creating_csv_table");
        die();
    }
    if(!grr_sql_query("TRUNCATE TABLE `".TABLE_PREFIX."_csv2` ")){
        echo get_vocab("error_cleaning_csv_table");
        die();
    }
    $file_path = $_FILES['csv']['tmp_name'];
    if($file_path == ""){
      return [NULL,[get_vocab("empty_file_path")],0];
    }
    elseif(!$fp = fopen($file_path, 'r')){
      return [NULL,[get_vocab("error_reading_file")],0];
    }
    else{
      $donnees = array();
      $erreurs = array();
      $nb_lignes = 0;
      while($reservation = fgetcsv($fp, $long_max, ";")) {
           // le jour de la réservation 
           $date=$reservation[0];
           // on décompose les heures de début et de fin
           $debut=strtolower($reservation[1]);
           if($pos_h=strpos($debut,'h')) { 
                  $heure_deb=intval(substr($debut,0,$pos_h));
                  $minute_deb=intval(substr($debut,$pos_h+1,10));
                } else {
                  $heure_deb=intval($debut);
                  $minute_deb=0;
                }
           $fin=strtolower($reservation[2]);
           if($pos_h=strpos($fin,'h')) { 
                  $heure_fin=intval(substr($fin,0,$pos_h));
                  $minute_fin=intval(substr($fin,$pos_h+1,10));
                } else {
                  $heure_fin=intval($fin);
                  $minute_fin=0;
                }
           $ressource=strtoupper($reservation[3]);
           $description=$reservation[4];
           $type=$reservation[5];
           // et on insère dans la base de données
           $sql_query="INSERT INTO ".TABLE_PREFIX."_csv2 (`id`, `date`, `heure_deb`, `minute_deb`, `heure_fin`, `minute_fin`, `ressource`, `description`, `type`)";
           $sql_query.=" VALUES ('DEFAULT' , ? , ? , ? , ? , ? , '".$ressource."' , '".$description."' , '".$type."');";
           if(!grr_sql_query($sql_query,"siiii",[$date,$heure_deb,$minute_deb,$heure_fin,$minute_fin])) 
               $erreurs[] = $nb_lignes." (".$sql_query.")";
           $donnees[] = "<p class='text-success'>".$date." ".$heure_deb."h".$minute_deb." - ".$heure_fin."h".$minute_fin." => ".$ressource." : ".$description." ; ".$type."</p>";
           $nb_lignes++;
       }
       return [$donnees,$erreurs,$nb_lignes];
    }
}
/* ecrit_csv_data()
   récupère les données de la table csv2 pour inscrire les réservations dans grr_entry
   renvoie une chaîne d'information, une chaîne d'erreurs et le nombre de réservations posées
*/
function ecrit_csv_data(){
    // on récupère les données : date, heure de début, minute de début, heure de fin, minute de fin, ressource, description, type
    $sql_query="SELECT * FROM ".TABLE_PREFIX."_csv2 ";
    $res=grr_sql_query($sql_query);
    if ($res) { // on a des données à traiter
        $i = 0; $erreur=""; $n=0; $info='';
        foreach($res as $row){
            $room_id = grr_sql_query1("SELECT id FROM ".TABLE_PREFIX."_room WHERE room_name LIKE'".$row['ressource']."'");
            list($succes,$message)=ajoute_reservation($room_id,$row['date'],$row['heure_deb'],$row['minute_deb'],$row['heure_fin'],$row['minute_fin'],$row['description'],$row['type']);
            if(!$succes){ 
                $erreur .= $message." ".$row['date']."; ".$row['heure_deb']."h".$row['minute_deb']." -> ".$row['heure_fin']."h".$row['minute_fin']."; ".$row['ressource']."; ".$row['description']."; ".$row['type']."</br>";
            }
            else {
                $info .= $message;
                $n++;
            }
        }
        return [$info,$erreur,$n];
    }
    else 
        return ["",get_vocab("no_data_found"),0];
}
# print the page header
start_page_w_header("","","","",$type="with_session", $page="admin");
if (isset($_GET['ok'])) {
    $msg = get_vocab("message_records");
    affiche_pop_up($msg,"admin");
}
// Affichage de la colonne de gauche
include "admin_col_gauche2.php";
// affichage de la colonne de droite
echo "<div class='col-md-9 col-sm-8 col-xs-12'>";
echo "<h2>".get_vocab("csv_entries_title")."</h2><hr />";
if(isset($_POST['import'])){
    echo "<h3>".get_vocab("csv_entries_intro1")."</h3>";
    $temps_debut=time();
    list($donnees,$erreurs,$nb_lignes) = lit_csv_data();
    echo sprintf(get_vocab("csv_entries_read_time"),$nb_lignes,(time()-$temps_debut))."<br />";
    if (count($erreurs)>0)
      foreach($erreurs as $erreur){
        echo $erreur."<br/>";
      }
    if($donnees != NULL){
      foreach ($donnees as $ligne){
          echo $ligne;
      }
      echo '<form action="admin_import_entries_csv_direct.php" method="POST">';
      echo '<div class="center">'.PHP_EOL;
      echo '<input type="submit" id="continue" value="'.get_vocab("import_data").'" />'.PHP_EOL;
      echo '</div>';
      echo '<input type="hidden" name="continue" value="1" />'.PHP_EOL;
      echo '</form>';
    }
    else{
      echo '<form action="admin_import_entries_csv_direct.php" method="POST">';
      echo '<div class="center">'.PHP_EOL;
      echo '<input type="submit" value="'.get_vocab("redo_from_start").'" />'.PHP_EOL;
      echo '</div>';
      echo '</form>';
    }
}
elseif(isset($_POST['continue'])){
    echo "<h3>".get_vocab("csv_entries_intro2")."</h3>";
    $temps_debut=time();
    list($info,$erreurs,$nb_resas) = ecrit_csv_data();
    echo "<p class='alert alert-info'>".sprintf(get_vocab("csv_entries_import_time"),$nb_resas,time()-$temps_debut)."</p>";
    if ($nb_resas != 0){
        echo $info;
    }
    if ($erreurs!=''){
        echo "<br /><p class='alert alert-warning'>".get_vocab("csv_entries_missed")."</p>";
        echo $erreurs;
    }
}
else
{   // show upload form
    echo '<h3>'.get_vocab('csv_entries_intro').'</h3>';
    echo '<p class="text-warning">'.get_vocab("admin_backup_recommande").'</p>';
    echo '<form action="admin_save_mysql.php" method="get">';
    echo '<input type="hidden" name="flag_connect" value="yes" />';
    echo '<input type="submit" value="'.get_vocab("submit_backup").'" />';
    echo '</form>';
    echo '<hr />';
    echo get_vocab('csv_entries_format');
    echo '<form enctype="multipart/form-data" action="./admin_import_entries_csv_direct.php" id="nom_formulaire" method="post" style="width: 100%;">'.PHP_EOL;
    echo '<p><b>'.get_vocab("admin_import_users_csv0").'</b>';
    echo '<input type="file" name="csv" />';
    echo '<input type="hidden" name="import" id="import" value="1" /></p>'.PHP_EOL;
    echo '<div class="center">'.PHP_EOL;
    echo '<input type="submit" id="import" value="'.get_vocab('csv_entries_import').'" />'.PHP_EOL;
    echo '</div>';
    echo '</form>';
}
echo "</div>"; // fin de la colonne droite
end_page();  // fin de la page
?>