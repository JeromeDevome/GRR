<?php
/**
 * admin_import_entries_csv_direct.php
 * Importe un fichier de réservations au format csv comprenant les champs : date du jour, heure de début, heure de fin, ressource, description et type
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2021-07-25 19:15$
 * @author    JeromeB & Yan Naessens & Denis Monasse & Laurent Delineau
 * @copyright Copyright 2003-2021 Team DEVOME - JeromeB
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
$journumero=array(0=>"dim",1=>"lundi",2=>"mardi",3=>"mercredi",4=>"jeudi",5=>"vendredi",6=>"samedi");

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
        $type_id = grr_sql_query1("SELECT id FROM ".TABLE_PREFIX."_type_area WHERE type_name='".$type."'");
        // voir pour traiter les erreurs ?
        
        // on convertit la date en année, mois, jour
        $year = substr($date,0,4);settype($year,"integer");
        $month = substr($date,5,2);settype($month,"integer");
        $day = substr($date,8,2);settype($day,"integer");
        // détermination du starttime
        settype($heure_deb,"integer");settype($minute_deb,"integer");
        $starttime = mktime($heure_deb, $minute_deb, 0, $month, $day, $year);
        // vérification du starttime 
        if($starttime < Settings::get("begin_bookings"))
            $erreur = 'Créneau hors limites';
        // détermination de endtime
        settype($heure_fin,"integer");settype($minute_fin,"integer");
        $endtime = mktime($heure_fin,$minute_fin,0,$month,$day,$year);
        // vérification de endtime
        if($endtime > Settings::get("end_bookings")){
            $erreur = 'Créneau hors limites';
        }
        if ($endtime <= $starttime)
            $erreur = 'Erreur début/fin';
        // On récupère la valeur de $area
        $area = mrbsGetRoomArea($room_id); 
        if(($room_id<=0) || ($area<=0)) 
            $erreur="Erreur de salle";
        
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
                return array(FALSE, "créneau occupé".$occupied."</br>");
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
               return array(FALSE, "Erreur lors de la création de la réservation");
           }
           else {
               $room_name = grr_sql_query1("SELECT room_name FROM ".TABLE_PREFIX."_room WHERE id =".$room_id);
               $message = "Réservation ".$ecriture." posée ";
               $message .= date('c',$starttime)." ".date('c',$endtime)." ".$room_name." ".$description." ".$type."</br>";
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
    // on commence par charger le fichier CSV dans une table provisoire grr_csv pour profiter des tris MySQL
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
    $fp = fopen($_FILES['csv']['tmp_name'], 'r');
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
         $sql_query.=" VALUES ('DEFAULT' , '".$date."' , '".$heure_deb."' , '".$minute_deb."' , '".$heure_fin."' , '".$minute_fin."' , '".$ressource."' , '".$description."' , '".$type."');";
         // echo $sql_query."</br>";
         if(!grr_sql_query($sql_query)) 
             $erreurs[] = $nb_lignes." (".$sql_query.")";
         $donnees[] = "<p class='text-success'>".$date." ".$heure_deb."h".$minute_deb." - ".$heure_fin."h".$minute_fin." => ".$ressource." : ".$description." ; ".$type."</p>";
         $nb_lignes++;
     }
     return [$donnees,$erreurs,$nb_lignes];
}
/* ecrit_csv_data()
   récupère les données de la table csv2 pour inscrire les réservations dans grr_entry
   renvoie une chaîne d'information, une chaîne d'erreurs et le nombre de réservations posées
*/
function ecrit_csv_data(){
    // on récupère les données : date, heure de début, minute de début, heure de fin, minute de fin, ressource, description, type
    $sql_query="SELECT date,heure_deb,minute_deb,heure_fin,minute_fin,ressource,description,type FROM ".TABLE_PREFIX."_csv2 ";
    $res=grr_sql_query($sql_query);
    if ($res) { // on a des données à traiter
        $i = 0; $erreur=""; $n=0; $info='';
        while($row = grr_sql_row($res, $i)){
            $date=$row[0]; 
            $heure_deb=$row[1]; $minute_deb=$row[2];
            $heure_fin=$row[3]; $minute_fin=$row[4];
            $ressource=$row[5]; $description=$row[6]; $type=$row[7];
            $i++;
            $room_id = grr_sql_query1("SELECT id FROM ".TABLE_PREFIX."_room WHERE room_name LIKE'".$ressource."'");
            list($succes,$message) = ajoute_reservation($room_id,$date,$heure_deb,$minute_deb,$heure_fin,$minute_fin,$description,$type);
            if(!$succes){ 
                $erreur .= $message." ".$row[0]."; ".$row[1]."h".$row[2]." -> ".$row[3]."h".$row[4]."; ".$row[5]."; ".$row[6]."; ".$row[7]."</br>";
            }
            else {
                $info .= $message;
                $n++;
            }
        }
        return [$info,$erreur,$n];
    }
    else 
        return ["","aucune donnée trouvée",0];
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
echo "<h2>Importation d'un fichier de réservations dans GRR</h2><hr />";
if(isset($_POST['import'])) {
    echo "<h2>Première étape de l'importation en cours, ne fermez pas la page</h2>";
    $temps_debut=time();
    list($donnees,$erreurs,$nb_lignes) = lit_csv_data();
    echo $nb_lignes." lignes de données ont été lues en ".(time()-$temps_debut)." secondes.<br />";
    if (count($erreurs)>0) print_r($erreurs);
    foreach ($donnees as $ligne){
        echo $ligne;
    }
    echo '<form action="admin_import_entries_csv_direct.php" method="POST">';
    echo '<div class="center">'.PHP_EOL;
    echo '<input type="submit" id="continue" value=" Importer les données lues ! " />'.PHP_EOL;
    echo '</div>';
    echo '<input type="hidden" name="continue" value="1" />'.PHP_EOL;
    echo '</form>';
}
elseif(isset($_POST['continue'])){
    echo "<h2>Deuxième étape de l'importation : enregistrement des réservations</h2>";
    $temps_debut=time();
    list($info,$erreurs,$nb_resas) = ecrit_csv_data();
    echo "<p>Importation de ".$nb_resas." réservations terminée au bout de ".(time()-$temps_debut)." secondes</p>";
    if ($nb_resas != 0){
        echo $info;
    }
    if ($erreurs!=''){
        echo "Des réservations n'ont pas pu être posées, veuillez consulter la liste ci-après :<br />";
        echo $erreurs;
    }
}
else
{   // show upload form
    echo '<p>Utiliser ce script pour importer un fichier de réservation dans GRR</p>';
    echo '<p class="text-warning">Il est conseillé de procéder à la sauvegarde de la base de données avant l\'importation</p>';
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
?>
