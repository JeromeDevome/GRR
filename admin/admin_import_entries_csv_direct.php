<?php
/**
 * admin_import_entries_csv_direct.php
 * Importe un fichier de réservations au format csv comprenant les champs : date du jour, heure de début, heure de fin, ressource, description et type
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2020-03-23 11:50$
 * @author    JeromeB & Yan Naessens & Denis Monasse & Laurent Delineau
 * @copyright Copyright 2003-2020 Team DEVOME - JeromeB
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
echo "<h2>Importation d'un fichier de réservations dans GRR</h2><hr />";

 $back = "./admin_accueil.php?";
 $joursemaine=array("dim"=>0,"lun"=>1,"mar"=>2,"mer"=>3,"jeu"=>4,"ven"=>5,"sam"=>6);
 $journumero=array(0=>"dim",1=>"lundi",2=>"mardi",3=>"mercredi",4=>"jeudi",5=>"vendredi",6=>"samedi");

// $long_max : doit être plus grand que la plus grande ligne trouvée dans le fichier CSV
    $long_max = 8000;
    
 if(isset($_POST['import'])) 
 {
    // on commence par charger le fichier CSV dans une table provisoire grr_csv pour profiter des tris MySQL
    echo "<h2>Première étape de l'importation en cours, ne fermez pas la page</h2>";
    $temps_debut=time();
    $erreur=""; $nb_reservations=0;
    $fp = fopen($_FILES['csv']['tmp_name'], 'r');
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
    $sql .= ") ENGINE=MyISAM DEFAULT CHARSET=latin1";
    if(!grr_sql_query($sql)){
        echo "Erreur dans la création de la table CSV";
        die();
    }
    if(!grr_sql_query("TRUNCATE TABLE `".TABLE_PREFIX."_csv2` ")){
        echo "Erreur dans le nettoyage de la table CSV";
        die();
    }
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
                $heure_deb=intval($fin);
                $minute_deb=0;
              }
         $ressource=strtoupper($reservation[3]);
         $description=$reservation[4];
         $type=$reservation[5];
         // et on insère dans la base de données
         $sql_query="INSERT INTO ".TABLE_PREFIX."_csv2 (`id`, `date`, `heure_deb`, `minute_deb`, `heure_fin`, `minute_fin`, `ressource`, `description`, `type`)";
         $sql_query.=" VALUES ('DEFAULT' , '".$date."' , '".$heure_deb."' , '".$minute_deb."' , '".$heure_fin."' , '".$minute_fin."' , '".$ressource."' , '".$description."' , '".$type."');";
          echo $sql_query."</br>";
         if(!grr_sql_query($sql_query)) echo "erreur dans la ligne ".$nb_reservations."(".$sql_query.")</br>";
         $nb_reservations++;
     }
     $nb_erreurs=0;
    echo "<h2>Deuxième étape de l'importation en cours, ne fermez pas la page</h2>";
        // on récupère les données : date, heure de début, minute de début, heure de fin, minute de fin, ressource, description, type
        $sql_query="SELECT date,heure_deb,minute_deb,heure_fin,minute_fin,ressource,description,type FROM ".TABLE_PREFIX."_csv2 ";
        // $sql_query .= "ORDER BY jour,salle,heure_deb,minute_deb,classe,matiere"; a priori pas besoin de trier
        $res=grr_sql_query($sql_query);
        if ($res) { 
         // on a des données à traiter
            $i = 0; $erreur=""; $n=0;
            while($row = grr_sql_row($res, $i)){
                $date=$row[0]; 
                $heure_deb=$row[1]; $minute_deb=$row[2];
                $heure_fin=$row[3]; $minute_fin=$row[4];
                $ressource=$row[5]; $description=$row[6]; $type=$row[7];
                $i++; $fin_fusion=false;
                $room_id = grr_sql_query1("SELECT id FROM ".TABLE_PREFIX."_room WHERE room_name LIKE'".$ressource."'");
                echo $i." ";
                if(!ajoute_reservation($room_id,$date,$heure_deb,$minute_deb,$heure_fin,$minute_fin,$description,$type))
                  { echo "Erreur dans la réservation ($erreur): "; //  numéro ".$n.": ".$erreur.":";
                   // on affiche les réservations non faites en un format de type CSV pour faciliter un copier-coller
                   echo $row[0].", ".$row[1]."h".$row[2]." -> ".$row[3]."h".$row[4].", ".$row[5].", ".$row[6].", ".$row[7]."</br>";
                   // for($k=0;$k<8;$k++) {echo "\"".$row[$k]."\",";}
                   $nb_erreurs++;
                   }
                    //     else echo "Réservation effectuée</br>";
            }      
            echo "<h2>Importation de ".($i-$nb_erreurs)."/$nb_reservations réservations terminée au bout de ".(time()-$temps_debut)." secondes</h2>";
            echo "Vérifiez que l'importation est bien complète (aux erreurs près), sinon restaurez la base de données, scindez le fichier CSV et recommencez.<br/>";
        }
} 
else 
{   // show upload form
    echo '<p>
            Utiliser ce script pour importer un fichier de réservation dans GRR<br />
            Il est conseillé de procéder à la sauvegarde de la base de données avant l\'importation</p>';
    echo '<form action="admin_save_mysql.php" method="get">';
    echo '<input type="hidden" name="flag_connect" value="yes" />';
    echo '<input type="submit" value="Lancer une sauvegarde" />';
    echo '</form>';
    echo '<hr />';
    echo '<p>Télécharger un fichier CSV au format suivant:</p>';
    echo '<code>date du jour; heure de début; heure de fin; ressource; description; type</code>';
    echo '<p>par exemple</p>';
    echo '<code>2001-01-01;12h00;14h00;Salle 1;Test;A</code>';
    echo '<p>Le temps d\'importation est en général limité par le serveur à quelques minutes par fichier. 
            Pour éviter une erreur de type "timeout" qui conduirait à une importation incomplète, 
            scindez votre fichier en fichiers plus petits que vous importerez successivement
        </p>';
    echo '<hr />';
    
    echo '<form enctype="multipart/form-data" action="./admin_import_entries_csv_direct.php" id="nom_formulaire" method="post" style="width: 100%;">'.PHP_EOL;
    echo '<label for="import">Fichier CSV</label>';
    echo '<input type="file" name="csv" />';
    echo '<input type="hidden" name="import" id="import" value="1" />'.PHP_EOL;
    echo '<div class="center">'.PHP_EOL;
    echo '<input type="submit" id="import" value=" Importer le fichier de réservation! " />'.PHP_EOL;
    echo '</div>';
    echo '</form>';
}
echo "</div>"; // fin de la colonne droite
end_page();  // fin de la page
// fonction php  
function ajoute_reservation($room_id,$date,$heure_deb,$minute_deb,$heure_fin,$minute_fin,$description,$type)
        {
        //echo $room_id.",".$date.",".$heure_deb."h".$minute_deb."->".$heure_fin."h".$minute_fin.",".$description.",".$type."</br>";
        //return true;
        global $max_rep_entrys, $erreur;
        $journee=86400; $semaine=86400*7;
             
        // Initialisation du test d'erreur
        $erreur = '';
        
        $id=null; // nouvelle réservation
        $ampm=NULL; // AMPM ou 24h
        // détermination du type de réservation
        $type_id = grr_sql_query1("SELECT id FROM ".TABLE_PREFIX."_type_area WHERE type_name='".$type."'");
        // voir pour traiter les erreurs    
        
        // détermination du starttime
        // on convertit la date en année, mois, jour
        $year = substr($date,0,4);settype($year,"integer");
        $month = substr($date,5,2);settype($month,"integer");
        $day = substr($date,8,2);settype($day,"integer");
        // echo $date." ".$day." ".$month." ".$year."</br>";
        settype($heure_deb,"integer");settype($minute_deb,"integer");
        $starttime = mktime($heure_deb, $minute_deb, 0, $month, $day, $year);
        //echo $starttime ;
        //echo "</br>";
        // vérification du starttime 
        if($starttime < Settings::get("begin_bookings")){
            $erreur = 'y';
        }
        // détermination de endtime
        settype($heure_fin,"integer");settype($minute_fin,"integer");
        $endtime = mktime($heure_fin,$minute_fin,0,$month,$day,$year);
        // vérification de endtime
        if($endtime > Settings::get("end_bookings")){
            $erreur = 'y';
        }
        if ($endtime <= $starttime)
            $erreur = 'y';

       
        $statut_entry = "-";
        $rep_jour_c = 0;
                
        $create_by = "Administrateur";
        $beneficiaire = "Administrateur";
        $benef_ext_nom = "Administrateur";
        $benef_ext_email = "";
        $beneficiaire_ext = concat_nom_email($benef_ext_nom, $benef_ext_email);
        
        $room_back =NULL; if (isset($room_back)) settype($room_back,"integer");
        $option_reservation = NULL;
        if (isset($option_reservation))
            settype($option_reservation,"integer");
        else
            $option_reservation = -1;
        
        // On récupère la valeur de $area
        $area = mrbsGetRoomArea($room_id); 
        if(($room_id<=0) || ($area<=0)) { $erreur="Erreur de salle"; return false;}
        
        # When checking for overlaps, for Edit (not New), ignore this entry and series:
        // $repeat_id = 0;
        // if (isset($id) and ($id!=0)) {
        //     $ignore_id = $id;
        //     $repeat_id = grr_sql_query1("SELECT repeat_id FROM ".TABLE_PREFIX."_entry WHERE id=$id");
        //     if ($repeat_id < 0) $repeat_id = 0;
        // } else     
        $ignore_id = 0;
        
        # Acquire mutex to lock out others trying to book the same slot(s).
        if (!grr_sql_mutex_lock("".TABLE_PREFIX."_entry"))
            fatal_error(1, get_vocab('failed_to_acquire'));
        
        $date_now = time();
        $error_booking_in_past = false;
        $error_booking_room_out = false; //note si la ressource est disponible
        $error_duree_max_resa_area = 'no';
        $error_delais_max_resa_room = 'no';
        $error_delais_min_resa_room = 'no';
        $error_date_option_reservation = 'no';
        $error_chevauchement = 'no';
        $error_qui_peut_reserver_pour = 'no';
        $error_heure_debut_fin = false;
        $err = '';
        
        // on vérifie que le créneau est bien libre
        $occupied=mrbsCheckFree($room_id, $starttime, $endtime, 0, 0,"../");
        // echo date('c',$starttime)." ".date('c',$endtime)."</br>";
        // echo $libre ;
        //echo "créneau libre ";
        if($occupied){ 
            echo "créneau occupé";
            echo $occupied."</br>";
            $error_booking_room_out = true ;
        }
        else{echo "créneau libre </br>";}
        
        // on vérifie qu'on ne réserve pas dans le passé
        $error_booking_in_past = $starttime < $date_now ;
        // Si il y a tentative de réserver dans le passé
        if ($error_booking_in_past) {
            $str_date = utf8_strftime("%d %B %Y, %H:%M", $date_now);
            // print_header();
            // echo "<h2>" . get_vocab("booking_in_past") . "</h2>";
            $erreur=get_vocab("booking_in_past");
            $erreur.= ",".get_vocab("booking_in_past_explain") . $str_date;
            // $erreur.= "<a href=\"".$back."&amp;Err=yes\">".get_vocab('returnprev')."</a>";
            return false;
        }
        
        if (empty($err)
            and (!$error_booking_in_past)
            and ($error_duree_max_resa_area == 'no')
            and ($error_delais_max_resa_room == 'no')
            and ($error_delais_min_resa_room == 'no')
            and (!$error_booking_room_out)
            and ($error_date_option_reservation == 'no')
            and ($error_chevauchement == 'no')
            and ($error_qui_peut_reserver_pour == 'no')
            and (!$error_heure_debut_fin)
            )
           {
               // l'utilisateur est gestionnaire ou admin de la ressource donc on ne modère pas !
               $entry_moderate = 0;
               $send_mail_moderate = 0; 
               $entry_type = 0; // réservation isolée
               $repeat_id = 0; 
               $name = $description;
               $overload_data = '';
               $moderate = 0;
               $keys = 0;
               $courrier = 0;
               echo date('c',$starttime)." ".date('c',$endtime)."</br>";
               $ecriture=mrbsCreateSingleEntry($starttime, $endtime, $entry_type, $repeat_id, $room_id,
                               $create_by, $beneficiaire, $beneficiaire_ext, $name, $type, $description, $option_reservation,$overload_data, $moderate, $rep_jour_c, $statut_entry, 
                               $keys, $courrier);
               echo $ecriture."</br>";
               return true ;
               //return !($ecriture == 0);
            }
        
        grr_sql_mutex_unlock("".TABLE_PREFIX."_entry");
        
        // Si l'utilisateur tente de réserver une ressource non disponible
        if ($error_booking_room_out) {
            // print_header();
            $erreur.=  get_vocab("norights");
            $erreur.=  ", <b>" . get_vocab("tentative_reservation_ressource_indisponible") . "</b>";
            // $erreur.=  "<a href=\"".$back."&amp;Err=yes\">".get_vocab('returnprev')."</a>";
            return false;
        }
                
        if(strlen($err))
        {
            print_header();
        
            echo "<h2>" . get_vocab("sched_conflict") . "</h2>";
            if(!isset($hide_title))
            {
                echo get_vocab("conflict");
                echo "<UL>";
            }
            echo $err;
        
            if(!isset($hide_title))
                echo "</UL>";
                // possibilité de supprimer la (les) réservation(s) afin de valider la nouvelle réservation.
                if(authGetUserLevel(getUserName(),$area,'area') >= 4)
                    echo "<center><table border=\"1\" cellpadding=\"10\" cellspacing=\"1\"><tr><td class='avertissement'><h3><a href='".traite_grr_url("","y")."edit_entry_handler.php?".$_SERVER['QUERY_STRING']."&amp;del_entry_in_conflict=yes'>".get_vocab("del_entry_in_conflict")."</a></h4></td></tr></table></center><br />";
        
        }
        return true;
        // Retour au calendrier
}
?>
