<?php
/**
 * admin_import_entries_csv_udt.php
 * Importe un fichier de réservations au format csv 
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2024-08-19 16:50$
 * @author    JeromeB & Yan Naessens & Denis Monasse & Laurent Delineau
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
$grr_script_name = "admin_import_entries_csv_udt.php";

include "../include/admin.inc.php";

$back = (isset($_SERVER['HTTP_REFERER']))? htmlspecialchars_decode($_SERVER['HTTP_REFERER'], ENT_QUOTES) : "./admin_accueil.php" ;
$_SESSION['chemin_retour'] = "admin_accueil.php";

// tableau des jours ordonnés
 $joursemaine=array("dim"=>0,"lun"=>1,"mar"=>2,"mer"=>3,"jeu"=>4,"ven"=>5,"sam"=>6);
 $journumero=array(0=>"dim",1=>"lundi",2=>"mardi",3=>"mercredi",4=>"jeudi",5=>"vendredi",6=>"samedi");
// $long_max : doit être plus grand que la plus grande ligne trouvée dans le fichier CSV
$long_max = 8000;
// fonctions locales
/* lit_udt_data()
   lit le fichier csv issu de UDT passé en paramètre dans le formulaire
   renvoie un tableau de données, un tableau d'erreurs et le nombre de lignes lues
*/
function lit_udt_data(){
  global $long_max,$joursemaine;
  // on commence par charger le fichier CSV dans une table provisoire grr_csv pour profiter des tris MySQL
  $erreur = "";
  // crée la table csv si elle n'existe pas, la nettoie si elle existe
  $sql  = "CREATE TABLE IF NOT EXISTS `".TABLE_PREFIX."_csv` (";
  $sql .= "`id` int(11) NOT NULL AUTO_INCREMENT,";
  $sql .= "`jour` tinyint(4) NOT NULL,";
  $sql .= "`heure_deb` tinyint(4) NOT NULL,";
  $sql .= "`minute_deb` tinyint(4) NOT NULL,";
  $sql .= "`heure_fin` tinyint(4) NOT NULL,";
  $sql .= "`minute_fin` tinyint(4) NOT NULL,";
  $sql .= "`classe` tinytext NOT NULL,";
  $sql .= "`matiere` tinytext NOT NULL,";
  $sql .= "`professeur` tinytext NOT NULL,";
  $sql .= "`salle` tinytext NOT NULL,";
  $sql .= "`groupe` tinytext NOT NULL,";
  $sql .= "`regroup` tinytext NOT NULL,";
  $sql .= "`eff` tinyint(4) NOT NULL,";
  $sql .= "`mo` tinytext NOT NULL,";
  $sql .= "`freq` tinytext NOT NULL,";
  $sql .= "`aire` tinytext NOT NULL,";
  $sql .= "PRIMARY KEY (`id`),";
  $sql .= "KEY `id` (`id`)";
  $sql .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci";
  if(grr_sql_command($sql) < 0){
    return [NULL,["Erreur dans la création de la table CSV"],0];
  }
  elseif(grr_sql_command("TRUNCATE TABLE `".TABLE_PREFIX."_csv` ")){
    return [NULL,["Erreur dans le nettoyage de la table CSV"],0];
  }
  else{
    $file_path = $_FILES['csv']['tmp_name'];
    if($file_path == ""){
      return [NULL,["Chemin de fichier vide"],0];
    }
    elseif(!$fp = fopen($file_path, 'r')){
      return [NULL,["Erreur de lecture du fichier"],0];
    }
    else{
      $donnees = array();
      $erreurs = array();
      $n = 0;
      while($reservation = fgetcsv($fp, $long_max, ";")){
        // le jour de la réservation 
        $jour=$joursemaine[substr(strtolower($reservation[0]),0,3)];
        // on décompose les heures de début et éventuellement de fin
        $heure=strtolower($reservation[1]);
        if($pos_tiret=strpos($heure,'-')){
          $heure1=substr($heure,0,$pos_tiret);
          $heure2=substr($heure,$pos_tiret+1,10);
          if($pos_h=strpos($heure1,'h')) {
            $heure_deb=intval(substr($heure1,0,$pos_h));
            $minute_deb=intval(substr($heure1,$pos_h+1,10));
          } 
          else {
            $heure_deb=intval($heure1);
            $minute_deb=0;
          }
          if($pos_h=strpos($heure2,'h')) {
            $heure_fin=intval(substr($heure2,0,$pos_h));
            $minute_fin=intval(substr($heure2,$pos_h+1,10));
          } 
          else {
            $heure_fin=intval($heure1);
            $minute_fin=0;
          }
        }
        else{
          if($pos_h=strpos($heure,'h')){
            $heure_deb=intval(substr($heure,0,$pos_h));
            $minute_deb=intval(substr($heure,$pos_h+1,10));
            $heure_fin=intval($heure_deb)+1;
            $minute_fin=intval($minute_deb);
          } 
          else{
            $heure_deb=intval($heure);
            $minute_deb=0;
            $heure_fin=intval($heure_deb)+1;
            $minute_fin=intval($minute_deb);
          }
        }
        // on traite la classe en se méfiant du 2E1 transformé par Excel
        $reservation[2]=str_replace(",00E+0","E",strtoupper($reservation[2])); // classe-division
        // les champs suivants : discipline, enseignant, salle
        $reservation[3]=addslashes($reservation[3]); // discipline
        $reservation[4]=addslashes($reservation[4]); // enseignant
        $reservation[5]=strtoupper($reservation[5]); // salle
        // on passe les repas et les salles non renseignées
        if(($reservation[3]=="repas") || ($reservation[5]=="")) continue;
        // et on insère dans la base de données
        //$sql_query="INSERT INTO ".TABLE_PREFIX."_csv (`id`, `jour`, `heure_deb`, `minute_deb`, `heure_fin`, `minute_fin`, `classe`, `matiere`, `professeur`, `salle`, `groupe`, `regroup`, `eff`, `mo`, `freq`, `aire`)";
        //$sql_query.=" VALUES ('DEFAULT' , '".$jour."' , '".$heure_deb."' , '".$minute_deb."' , '".$heure_fin."' , '".$minute_fin;
        //for($i=2; $i<12; $i++) $sql_query .= "' , '".$reservation[$i];
        //   $sql_query .= "');";
        //  echo $sql_query."<br />";
        $sql_query="INSERT INTO ".TABLE_PREFIX."_csv (`id`, `jour`, `heure_deb`, `minute_deb`, `heure_fin`, `minute_fin`, `classe`, `matiere`, `professeur`, `salle`, `groupe`, `regroup`, `eff`, `mo`, `freq`, `aire`)";
        $sql_query.=" VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
        $types="siiiiissssssisss";
        $data=['DEFAULT',$jour,$heure_deb,$minute_deb,$heure_fin,$minute_fin];
        for($i=2; $i<12; $i++)
          $data[]=$reservation[$i];
        if(grr_sql_command($sql_query,$types,$data)<0) 
          $erreurs[] = $n." (".$sql_query.")";
        $donnees[] = array($jour,$heure_deb,$minute_deb,$heure_fin,$minute_fin,$reservation[2],$reservation[3],$reservation[4],$reservation[5]);
        $n++;
      }
      return [$donnees,$erreurs,$n];
    }
  }
}
/* print_data($ligne)
 affiche en clair les données utiles d'une ligne lue
*/
function print_data($ligne){
  global $journumero;
  $jour = $journumero[$ligne[0]];
  $heure_deb = $ligne[1]."h".str_pad($ligne[2],2,0,STR_PAD_LEFT);
  $heure_fin = $ligne[3]."h".str_pad($ligne[4],2,0,STR_PAD_LEFT);
  $groupe = $ligne[5];
  $matiere = $ligne[6];
  $enseignant = $ligne[7];
  $salle = $ligne[8];
  echo $jour." ".$heure_deb." -> ".$heure_fin.", ".$groupe.", ".$matiere.", ".$enseignant.", salle : ".$salle;
}
/* ecrit_udt_data()
   lit la table grr_csv, tente la fusion avec les données suivantes, ou avec les groupes sur le même créneau dans la même salle,
   efface si nécessaire des données déjà présentes
   enregistre les réservations dans les tables grr_entry et grr_repeat
*/
function ecrit_udt_data(){
  global $beg_time, $end_time, $max_rep_entrys, $journumero;
  $donnees = array();
  $erreurs = array();
  $n = 0;
  // récupération des données enregistrées dans la table grr_csv triées par jour, salle, heure de début, minute de début, classe et matière
  $sql_query="SELECT jour,salle,heure_deb,minute_deb,heure_fin,minute_fin,classe,matiere,professeur,groupe FROM ".TABLE_PREFIX."_csv ";
  $sql_query .= "ORDER BY jour,salle,heure_deb,minute_deb,classe,matiere";
  $res=grr_sql_query($sql_query);
  if (!$res){
    $erreurs = ["Erreur de lecture en base de données"];
  }
  else{
    $i = 0; $erreur="";
    while($row = grr_sql_row($res, $i)){
      $jour=$row[0]; $salle=$row[1]; 
      $heure_deb=$row[2]; $minute_deb=$row[3];
      $heure_deb_i=$heure_deb; $minute_deb_i=$minute_deb;
      $heure_fin=$row[4]; $minute_fin=$row[5];
      $classe=$row[6]; $matiere=$row[7]; $lesclasses=$classe; $professeur=$row[8];
      $i++; $fin_fusion=false;
      while(($i < grr_sql_count($res))&& !$fin_fusion){
        $row = grr_sql_row($res,$i);
        if(($jour==$row[0]) && ($salle==$row[1]) && ($heure_deb_i==$row[2]) && ($minute_deb_i==$row[3])){
          // on elimine les doublons (meme jour, meme salle, meme heure) en concaténant les classes
          if(strpos($lesclasses,$row[6])===false) $lesclasses=$lesclasses." et ".$row[6];
          $i++; 
        } 
        else if(($row = grr_sql_row($res, $i)) && ($jour==$row[0]) && ($salle==$row[1]) && ($classe==$row[6]) && ($matiere==$row[7]) && ($heure_fin==$row[2]) && ($minute_fin==$row[3])){
          // et on fusionne les creneaux consecutifs
          $heure_fin=$row[4]; $minute_fin=$row[5]; $heure_deb_i=$row[2]; $minute_deb_i=$row[3]; $i++;
        } 
        else $fin_fusion = true;
      }
      $room_id = grr_sql_query1("SELECT id FROM ".TABLE_PREFIX."_room WHERE room_name=? ",'s',[$salle]);
      if($room_id == -1){
        // il y a une erreur ou la salle n'est pas connue
        $erreurs[] = $salle." Salle inconnue ou erreur de lecture en base de données";
      }
      else{
        //echo $room_id.",".$jour.",".$lesclasses." - ".$matiere.",".$professeur.",".$beg_time.",".$heure_deb.",".$minute_deb.",".$heure_fin.",".$minute_fin.",".$end_time;
        list($status,$erreur) = entre_reservation($room_id,$jour,$lesclasses." - ".$matiere,$professeur,$beg_time,$heure_deb,$minute_deb,$heure_fin,$minute_fin,$end_time,0);
        if(!$status)
        { // on affichera les réservations non faites en un format de type CSV pour faciliter un copier-coller
          $erreurs[] = "Erreur dans la réservation ($erreur): ".$journumero[$row[0]]."; ".$row[1]."; ".$row[2]."h".$row[3]." -> ".$row[4]."h".$row[5]."; ".$row[6]."; ".$row[7]."; ".$row[8];
        }
        else{// une réservation a été posée
          $n++;
        }
      }
    }
  }
  return [$donnees,$erreurs,$n];
}
function entre_reservation($room_id,$jour_semaine,$name,$description,$begin_time,$heure_deb,$minute_deb,$end_hour,$end_minute,$rep_end_time,$rep_semaine){
  global $max_rep_entrys;
  $erreur = "";
  $journee=86400; $semaine=86400*7;
  $id=null; // nouvelle réservation
	$type = "A"; // type de réservation (cours selon les réglages par défaut de GRR...)
  // vérification des dates de début et fin
  if($begin_time < Settings::get('begin_bookings'))
    $begin_time = Settings::get('begin_bookings');
  if($rep_end_time > Settings::get("end_bookings"))
    $rep_end_time = Settings::get("end_bookings");
  // début du créneau
	$starttime0 = mktime($heure_deb,$minute_deb, 0,date("m",$begin_time),date("d",$begin_time),date("Y",$begin_time));
	$jour_semaine_debut=date("w",$starttime0);
	if($jour_semaine>=$jour_semaine_debut){
    $starttime=$starttime0+($jour_semaine-$jour_semaine_debut)*$journee;
		if($rep_semaine==2) $starttime=$starttime+$semaine;
	}
  else{
    $starttime=$starttime0+($jour_semaine-$jour_semaine_debut+7)*$journee;
    if($rep_semaine==1) $starttime=$starttime+$semaine;
	}
	// fin du creneau
  $endtime0 = mktime($end_hour, $end_minute, 0,date("m",$begin_time),date("d",$begin_time),date("Y",$begin_time));
  if($jour_semaine>=$jour_semaine_debut) {
    $endtime=$endtime0+($jour_semaine-$jour_semaine_debut)*$journee;
    if($rep_semaine==2) $endtime=$endtime+$semaine;
  }
  else {
    $endtime=$endtime0+($jour_semaine-$jour_semaine_debut+7)*$journee;
    if($rep_semaine==1) $endtime=$endtime+$semaine;
  }
  if ($endtime <= $starttime)
    return [FALSE,"Erreur de début/fin de créneau"];

  $statut_entry = "-";
  $rep_jour_c = 0;

	// gestion de la périodicité
	$rep_type = 2; settype($rep_type,"integer"); // réservation hebdomadaire
	if($rep_semaine==0) 
    $rep_num_weeks = 1; // toutes les semaines
  else $rep_num_weeks = 2; // une semaine sur deux

	$rep_month = NULL;
	$rep_id = NULL; // id de la périodicité associée
	$rep_day = "";
  $create_by = "Administrateur";
	$beneficiaire = "Administrateur";
	$beneficiaire_ext = ""; //concat_nom_email($benef_ext_nom, $benef_ext_email);
	$room_back =NULL; if (isset($room_back)) settype($room_back,"integer");
	$option_reservation = -1;
  
	// On récupère la valeur de $area
	$area = mrbsGetRoomArea($room_id); 
	if(($room_id<=0) || ($area<=0)) 
    return [false,"Erreur de salle"];
	
	# For weekly repeat(2), build string of weekdays to repeat on:
	$rep_day=array(NULL,NULL,NULL,NULL,NULL,NULL,NULL);
	$rep_day[$jour_semaine]=1;
	$rep_opt = "";
	for ($i = 0; $i < 7; $i++) $rep_opt .= empty($rep_day[$i]) ? "0" : "1";

	// Expand a series into a list of start times:
	// $reps est un tableau des dates de début de réservation, les jours ouvrés et hors vacances scolaires
	$reps = mrbsGetRepeatEntryList($starttime, $rep_end_time, $rep_type, $rep_opt, $max_rep_entrys, $rep_num_weeks,0,$area,0,0,[2,2]);
  if(empty($reps))
    return [false,"Tableau des dates vide"];
	
  // On  vérifie que les différents créneaux ne se chevauchent pas.
	$diff = $endtime - $starttime;
  if (!grrCheckOverlap($reps, $diff))
    return [false,"Erreur de chevauchement de réservation"];
  
	# Acquire mutex to lock out others trying to book the same slot(s).
	if (!grr_sql_mutex_lock("".TABLE_PREFIX."_entry"))
		return [false,get_vocab('failed_to_acquire')];

  if(count($reps) < $max_rep_entrys) {
    $diff = $endtime - $starttime;
    for($i = 0; $i < count($reps); $i++) {
      if ($reps != ''){
        // Suppression des résa en conflit
        grrDelEntryInConflict($room_id, $reps[$i], $reps[$i] + $diff, 0, 0, 0);
        // On teste s'il reste des conflits
        if ($i == (count($reps)-1))
          $tmp = mrbsCheckFree($room_id, $reps[$i], $reps[$i] + $diff, 0, 0);
        else
          $tmp = mrbsCheckFree($room_id, $reps[$i], $reps[$i] + $diff, 0, 0);
        if(!empty($tmp)) $err = $err . $tmp;
      }
    }
  } 
  else {
    $err .= get_vocab("too_may_entrys") . "<p>";
    $hide_title  = 1;
  }
  if(empty($err)){
    $entry_moderate = 0;
		$send_mail_moderate = 0;
    $courrier = 0;
    $overload_data = array();
		grrCreateRepeatingEntrys($starttime,$endtime,$rep_type,$rep_end_time,$rep_opt,$room_id,$create_by,$beneficiaire,$beneficiaire_ext,$name,$type,$description,$rep_num_weeks,$option_reservation,$overload_data,$entry_moderate,$rep_jour_c,$courrier,0,0,0,$reps);
	}
	grr_sql_mutex_unlock("".TABLE_PREFIX."_entry");

  $area = mrbsGetRoomArea($room_id);

  if(!empty($err))
    return[false,$err];
  else // Retour au calendrier
    return [true,""];
}

if(isset($_POST['import'])){
  $temps_debut=time();
  $beg_day = intval($_POST['beg_day']);
  $beg_month = intval($_POST['beg_month']);
  $beg_year = intval($_POST['beg_year']);
  $end_day = intval($_POST['end_day']);
  $end_month = intval($_POST['end_month']);
  $end_year = intval($_POST['end_year']);
  $beg_time = mktime(0,0,0,$beg_month,$beg_day,$beg_year);
  $end_time = mktime(23,59,59,$end_month,$end_day,$end_year);
  if($end_time < $beg_time){
    $donnees = NULL;
    $erreurs = ["Date de fin antérieure à la date de début !"];
    $nb_lignes = 0;
  }
  else{
    list($donnees,$erreurs,$nb_lignes) = lit_udt_data();
  }
  $temps_ecoule=(time()-$temps_debut);
}
elseif(isset($_POST['continue'])){
  $temps_debut=time();
  $beg_time = intval($_POST['beg_time']);
  $end_time = intval($_POST['end_time']);
  list($info,$erreurs,$nb_resas) = ecrit_udt_data();
  $temps_ecoule=(time()-$temps_debut);  
}
else{// formulaire initial
  // par défaut on pose les dates à la date du jour
  $day = date("d");
  $month = date("m");
  $year = date("Y");
}
// code html
# print the page header
start_page_w_header("","","","",$type="with_session", $page="admin");
if (isset($_GET['ok'])) {
    $msg = get_vocab("message_records");
    affiche_pop_up($msg,"admin");
}
//print_r($_POST);
//echo '<br/>';
// Affichage de la colonne de gauche
include "admin_col_gauche2.php";
// affichage de la colonne de droite
echo "<div class='col-md-9 col-sm-8 col-xs-12'>";
echo "<h2>Importation d'un fichier CSV issu de UnDeuxTemps dans GRR</h2><hr />";
if(isset($_POST['import'])){
    echo "<h2>Première étape de l'importation en cours, ne fermez pas la page</h2>";
    echo $nb_lignes." lignes de données ont été lues en ".$temps_ecoule." secondes.<br />";
    if (count($erreurs)>0)
      foreach($erreurs as $erreur){
        echo $erreur."<br/>";
      }
    if($donnees != NULL){
      foreach ($donnees as $ligne){
          print_data($ligne);
          echo "<br/>";
      }
      echo '<form action="admin_import_entries_csv_udt.php" method="POST">';
      echo '<div class="center">'.PHP_EOL;
      echo '<input type="submit" id="continue" value=" Importer les données lues ! " />'.PHP_EOL;
      echo '</div>';
      echo '<input type="hidden" name="continue" value="1" />'.PHP_EOL;
      echo '<input type="hidden" name="beg_time" value="'.$beg_time.'" />'.PHP_EOL;
      echo '<input type="hidden" name="end_time" value="'.$end_time.'" />'.PHP_EOL;
      echo '</form>';
    }
    else{
      echo '<form action="admin_import_entries_csv_udt.php" method="POST">';
      echo '<div class="center">'.PHP_EOL;
      echo '<input type="submit" value=" Reprendre au début ! " />'.PHP_EOL;
      echo '</div>';
      echo '</form>';
    }
}
elseif(isset($_POST['continue'])){
    echo "<h2>Deuxième étape de l'importation : enregistrement des réservations</h2>";
    echo "<p class='alert alert-info'>Importation de ".$nb_resas." réservations terminée au bout de ".$temps_ecoule." secondes</p>";
    if ($nb_resas != 0){
        foreach($info as $inf){
          echo $inf."<br/>";
        }
    }
    if (count($erreurs)>0){
      echo "<br /><p class='alert alert-warning'>Des réservations n'ont pas pu être posées, veuillez consulter la liste ci-après :</p>";
        foreach($erreurs as $erreur){
          echo $erreur."<br/>";
        }
    }
}
else
{// formulaire initial
  echo '<p>Utiliser ce script pour importer un fichier issu de UnDeuxTemps dans GRR</p>';
  echo '<p class="text-warning">Il est conseillé de procéder à la sauvegarde de la base de données avant l\'importation</p>';
  echo '<form action="admin_save_mysql.php" method="get">
            <input type="hidden" name="flag_connect" value="yes" />
            <input type="submit" value="Lancer une sauvegarde" />
        </form>';
  echo '<hr /> ';
  echo '<p>Télécharger un fichier CSV au format suivant:<br />';
  echo "<code>
              jour de la semaine; heure au format: 12h00 (pour un créneau d'une heure) ou 12h00-13h30 (pour un créneau
              différent); classe ou division; discipline; enseignant; 
              salle; groupe; regroupement; effectif; mode; fréquence; aire (ces 6 derniers champs ne sont pas exploités
              pour le moment mais doivent figurer: c'est le format d'exportation UnDeuxTEMPS)
          </code></p>";
  echo "<p>Le temps d'importation est en général limité par le serveur à quelques minutes par fichier. 
          Pour éviter des erreurs de type \"timeout\" qui conduirait à une importation incomplète, 
          scindez votre fichier en fichiers plus petits (par exemple suivant
          les jours de la semaine) que vous importerez successivement.
      </p>";
  echo '<form enctype="multipart/form-data" action="./admin_import_entries_csv_udt.php" id="nom_formulaire" method="post">'.PHP_EOL;
  echo '<p><b>Fichier CSV</b>';
  echo '<input type="file" name="csv" />';
  echo '</p>'.PHP_EOL;
  echo '<p><br /><b>Jour de début d\'importation : &nbsp;</b>';
  genDateSelector('beg_', $day, $month, $year, 'more_years');
  echo '<input type="hidden" disabled="disabled" id="mydate_beg_">'.PHP_EOL;
  echo '</p>';
  echo "<p><b>Jour de fin d'importation : &nbsp;</b>";
  genDateSelector('end_', $day, $month, $year, 'more_years');
  echo '<input type="hidden" disabled="disabled" id="mydate_end_">'.PHP_EOL;
  echo '</p>';
  echo '<div class="center">'.PHP_EOL;
  echo '<input type="hidden" name="import" id="import" value="1" /></p>'.PHP_EOL;
  echo '<input type="submit" id="import" value=" Lire les données ! " />'.PHP_EOL;
  echo '</div>';
  echo '</form>';
}
echo "</div>"; // fin de la colonne droite
end_page();  // fin de la page
die();
if($etape == 0){}
elseif($etape == 1){
  if($erreur != ""){
    echo "<p class='bg-danger'>".PHP_EOL;
    echo $erreur."</p>";
    echo '<form enctype="multipart/form-data" action="./admin_import_entries_csv_udt.php" id="nom_formulaire" method="post">'.PHP_EOL;
    echo '<p><b>Fichier CSV</b>';
    echo '<input type="file" name="csv" />';
    echo '</p>'.PHP_EOL;
    echo '<p><br /><b>Jour de début d\'importation : &nbsp;</b>';
    genDateSelector('beg_', $beg_day, $beg_month, $beg_year, 'more_years');
    echo '<input type="hidden" disabled="disabled" id="mydate_beg_">'.PHP_EOL;
    echo '</p>';
    echo "<p><b>Jour de fin d'importation : &nbsp;</b>";
    genDateSelector('end_', $end_day, $end_month, $end_year, 'more_years');
    echo '<input type="hidden" disabled="disabled" id="mydate_end_">'.PHP_EOL;
    echo '</p>';
    echo '<div class="center">'.PHP_EOL;
    echo '<input type="hidden" name="etape" value=1 />'.PHP_EOL;
    echo '<input type="submit" id="import" value=" Lire les données ! " />'.PHP_EOL;
    echo '</div>';
    echo '</form>';
  }
  else{
    echo '<p>Première étape : analyse des données entrées. Veuillez patienter...</p>';
    echo '<form action="./admin_import_entries_csv_udt.php" method="post">'.PHP_EOL;
    echo '<input type="hidden" name="file_name" value="'.$_FILES['csv']['tmp_name'].'" />';
    echo '<input type="hidden" name="beg_time" value="'.$beg_time.'" />';
    echo '<input type="hidden" name="end_time" value="'.$end_time.'" />';
    echo '<input type="hidden" name="etape" value=2 />'.PHP_EOL;
    echo '<div class="center">'.PHP_EOL;
    echo '<input type="submit" id="import" value=" Analyser les données ! " />'.PHP_EOL;
    echo '</div>';
    echo '</form>';
  }
}
elseif($etape == 2){
  if($erreur != ""){
    echo $erreur;
    echo "<a type='button' href='./admin_accueil.php'>Retour à l'accueil</a>";
  }
  else{
    echo "<p>Les données lues vont être importées dans la base de données de GRR. Veuillez patienter...</p>";
    echo '<form action="./admin_import_entries_csv_udt.php" method="post">'.PHP_EOL;
    echo '<input type="hidden" name="beg_time" value="'.$beg_time.'" />';
    echo '<input type="hidden" name="end_time" value="'.$end_time.'" />';
    echo '<input type="hidden" name="etape" value=3 />'.PHP_EOL;
    echo '<div class="center">'.PHP_EOL;
    echo '<input type="submit" id="import" value=" Importer les données ! " />'.PHP_EOL;
    echo '</div>';
    echo '</form>';
  }
}
end_page();