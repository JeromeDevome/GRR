<?php
/**
 * admin_import_xml_edt.php
 * Importe un fichier de réservations au format xml issu du logiciel EDT Index Education
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2021-03-13 11:46$
 * @author    JeromeB & Yan Naessens & Laurent Delineau
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
$grr_script_name = "admin_import_xml_edt.php";

include "../include/admin.inc.php";

$back = (isset($_SERVER['HTTP_REFERER']))? htmlspecialchars_decode($_SERVER['HTTP_REFERER'], ENT_QUOTES) : "./admin_accueil.php" ;
$_SESSION['chemin_retour'] = "admin_config.php";

if (!Settings::load()) {
    die('Erreur chargement settings');
}
// tableau des jours ordonnés
 $joursemaine=array("dim"=>0,"lun"=>1,"mar"=>2,"mer"=>3,"jeu"=>4,"ven"=>5,"sam"=>6);
 $journumero=array(0=>"dim",1=>"lundi",2=>"mardi",3=>"mercredi",4=>"jeudi",5=>"vendredi",6=>"samedi");
 
# print the page header
start_page_w_header('', '', '', $type = 'with_session');
// Affichage de la colonne de gauche
include 'admin_col_gauche2.php';
// Affichage de la colonne de droite 
echo "<div class='col-md-9 col-sm-8 col-xs-12'>";
echo '<h2>Import XML EDT</h2>';
if(!isset($_POST['step'])) { //rien n'est défini, ouvre le dialogue pour charger le fichier
    echo "<p><b>Charger un nouveau fichier</b></p>\n";
    echo "<form enctype='multipart/form-data' action='".$_SERVER['PHP_SELF']."' method='post'>\n";
    echo "<p>Veuillez fournir le fichier EXP_COURS.xml &nbsp;:</p>\n";
    echo "<input type=\"file\" size=\"65\" name=\"edt_xml_file\" /><br />\n";
    echo "<input type='hidden' name='step' value='1' />\n";
    echo "<input type='hidden' name='is_posted' value='yes' />\n";
    echo "<p><input type='submit' value='Valider' /></p>\n";
    echo "</form>\n";
}
elseif ($_POST['step']==1){ // on commence par vérifier que le fichier est bien chargé et de type adéquat
    $xml_file = isset($_FILES["edt_xml_file"]) ? $_FILES["edt_xml_file"] : NULL;
    $split = explode('.', $xml_file['name']);
    if(is_uploaded_file($xml_file['tmp_name'])&&(count($split) == 2)&&(strtolower(end($split)) =='xml')) {
        echo "<p> Chargement réussi ! </p>";

            $source_file=$xml_file['tmp_name'];
            $dest_file="../images/edt.xml";
            $res_copy=copy("$source_file" , "$dest_file"); 
            if(!$res_copy){
                echo "<p class='avertissement'>La copie du fichier a échoué.</p><br /><p>Vérifiez que le dossier /images est accessible en écriture.</p>\n";
//						require("../lib/footer.inc.php");
//					die();
            }
            else{
                echo "<p>La copie du fichier vers le dossier temporaire a réussi.</p>\n";
                echo '<br />';
                echo "<form enctype='multipart/form-data' action='".$_SERVER['PHP_SELF']."' method='post'>\n";
                echo "<input type='hidden' name='step' value='2' />\n";
                echo "<input type='hidden' name='is_posted' value='yes' />\n";
                echo "<p><input type='submit' value='Continuer' /></p>\n";
                echo "</form>\n";
            }
    }
    else { 
        echo "<p class='avertissement'>Le chargement du fichier a échoué.</p>\n";
        //require("../include/trailer.inc.php");
        //die();
    }
}
elseif ($_POST['step']==2) { // premier test sur le fichier xml récupéré
    echo "étape ";
    echo $_POST['step']; //$step;
    $dest_file="../images/edt.xml";
    $fp = fopen($dest_file, 'r');
    $edt_xml=simplexml_load_file($dest_file);
    if(!$edt_xml) {
        echo "<p class='avertissement'>ECHEC du chargement du fichier avec simpleXML.</p>\n";
    }
    else { // le fichier a pu être chargé, on va l'analyser
        echo "<p> Début de l'analyse du fichier </p>";
        $nom_racine=$edt_xml->getName();
        if(mb_strtoupper($nom_racine)!='TABLE') {
            echo "<p class='avertissement'>ERREUR: Le fichier XML fourni n'a pas l'air d'être un fichier XML EDT.<br />Sa racine devrait être 'TABLE'.</p>\n";
        }
        else { // premier test réussi, choisir les dates de début et fin des réservations
            echo "<p>Choisir les dates de début et fin :</p>";
            echo '<form enctype="multipart/form-data" action="./admin_import_xml_edt.php" id="nom_formulaire" method="post" style="width: 100%;">'.PHP_EOL;
            echo "<input type='hidden' name='step' value='3' />".PHP_EOL;
            echo '<p><br /><label for="mydate_beg_">Jour de début d\'importation : &nbsp;</label>';
            $day   = date("d");
            $month = date("m");
            $year  = date("Y"); //par défaut on propose la date du jour
            genDateSelector('beg_', $day, $month, $year, 'more_years');
            echo '<input type="hidden" disabled="disabled" id="mydate_beg_">'.PHP_EOL;
            echo '</p>';
            echo "<p><label for='mydate_end_'>Jour de fin d'importation : &nbsp;</label>";
            $day   = date("d");
            $month = date("m");
            $year  = date("Y"); //par défaut on propose la date du jour
            genDateSelector('end_', $day, $month, $year, 'more_years');
            echo '<input type="hidden" disabled="disabled" id="mydate_end_">'.PHP_EOL;
            echo '</p>';
            echo '<div class="center">'.PHP_EOL;
            echo '<input type="submit" id="import" value=" Importer les réservations! " />'.PHP_EOL;
            echo '</div>';
            echo '</form>';
        }
            
    }
}
else { // les tests sont passés, on réserve
    echo "étape ".$_POST['step'].' : réservation <br />';
    $dest_file="../images/edt.xml";
    $fp = fopen($dest_file, 'r');
    $edt_xml=simplexml_load_file($dest_file);
    if(!$edt_xml) {
        echo "<p class='avertissement'>ECHEC du chargement du fichier avec simpleXML.</p>\n";
    }
    else { // le fichier a été chargé, on va entrer les réservations
        $i=0;
        foreach ($edt_xml->children() as $cours) { // les entités de premier niveau s'appellent 'cours'
                //echo $cours;
                //echo("<p><b>Structure</b><br />");
            foreach($cours->attributes() as $key => $value) {
                echo(" Cours $key -&gt;".$value."<br />");
                $i++;
                $tab_cours[$i]=array();
                $tab_cours[$i]['attribut'][$key]=$value;
                $tab_cours[$i]['enfant']=array();

                foreach($cours->children() as $key => $value) {
                    $tab_cours[$i]["enfant"][mb_strtolower($key)]=trim($value);
                }
                        //print_r($tab_cours[$i]);
                $salle = $tab_cours[$i]['enfant']['salle']; // traiter le cas d'une salle vide ?
                $room_id = grr_sql_query1("SELECT id FROM ".TABLE_PREFIX."_room WHERE room_name='".$salle."'");
                $jour_semaine = $joursemaine[substr(strtolower($tab_cours[$i]['enfant']['jour']),0,3)]; 
                $name = $tab_cours[$i]['enfant']['classe'].' - '.$tab_cours[$i]['enfant']['mat_libelle']; // nettoyer le code classe pour les groupes complexes 
                $description = $tab_cours[$i]['enfant']['prof_nom'].' '.$tab_cours[$i]['enfant']['prof_prenom'];
                $h_deb = $tab_cours[$i]['enfant']['h.debut'];
                $pos_h = strpos($h_deb,'h');
                $hour = intval(substr($h_deb,0,$pos_h));
                $minute = intval(substr($h_deb,$pos_h+1,5));
                $duree = $tab_cours[$i]['enfant']['duree'];
                $pos_h = strpos($duree,'h');
                $end_hour = $hour + intval(substr($duree,0,$pos_h));
                $end_minute = $minute + intval(substr($duree,$pos_h+1,5));
                if ($tab_cours[$i]['enfant']['frequence'] == 'H'){ $rep_semaine = 0;}
                elseif ($tab_cours[$i]['enfant']['frequence'] == 'Q1') {$rep_semaine = 1;}
                else {$rep_semaine = 2;}
                 
                // echo $room_id.', '.$jour_semaine.', '.$name.', '.$description.', '.$day.', '.$month.', '.$year.', '.$hour.', '.$minute.', '.$end_day.', '.$end_month.', '.$end_year.', '.$end_hour.', '.$end_minute.', '.$rep_end_day.', '.$rep_end_month.', '.$rep_end_year.', '.$rep_semaine;
                echo '<br />';
                $keys = ['beg_day','beg_month','beg_year','end_day','end_month','end_year'];
                $data = array();
                foreach ($keys as $key){
                    $data[$key] = clean_input($_POST[$key]);
                }
                if(!entre_reservation($room_id,$jour_semaine,$name,$description,$data['beg_day'],$data['beg_month'],$data['beg_year'],$hour,$minute,$data['beg_day'],$data['beg_month'],$data['beg_year'],$end_hour,$end_minute,$data['end_day'],$data['end_month'],$data['end_year'],$rep_semaine)){ 
                    echo "Erreur dans la réservation numéro ".$i.": ".$erreur.'<br />' ;
                       // on affiche les réservations non faites en un format de type CSV pour faciliter un copier-coller
                       //echo $journumero[$row[0]]."; ".$row[1]."; ".$row[2]."h".$row[3]." -> ".$row[4]."h".$row[5]."; ".$row[6]."; ".$row[7];
                       // for($k=0;$k<8;$k++) {echo "\"".$row[$k]."\",";}
                       //echo "; ".$row[8]."\n<br/>";
                       //$nb_erreurs++;
                   }
                else echo "Réservation effectuée</br>";
            }
        }
        // on va nettoyer le fichier
        fclose($dest_file);
        unlink($dest_file);
    }
}

// fin du code de la colonne de droite
// fermeture des balises ouvertes dans admin_col_gauche.php
echo '</div></section></body></html>'; 

function entre_reservation($room_id,$jour_semaine,$name,$description,
             $day,$month,$year,$hour,$minute,
             $end_day,$end_month,$end_year,$end_hour,$end_minute,
             $rep_end_day,$rep_end_month,$rep_end_year,$rep_semaine){
        //echo $room_id.",".$jour_semaine.",".$name.",".$description.",".$hour."h".$minute."->".$end_hour."h".$end_minute."</br>";
        // return true;
        global $max_rep_entrys, $erreur;
        $journee=86400; $semaine=86400*7;
             
		// Initialisation du test d'erreur
		$erreur = 'n';
		
		// Initialisation
		$message_error = "";
		
		$id=null; // nouvelle réservation
		$ampm=NULL; // AMPM ou 24h
		$type = "A"; // type de réservation (cours, colle, ...)
		
		// $duration = 2; $duration = str_replace(",", ".", "$duration ");
		settype($hour,"integer"); if ($hour > 23) $hour = 23;
		settype($minute,"integer"); if ($minute > 59) $minute = 59;
		$starttime0 = mktime($hour, $minute, 0, $month, $day, $year);
		$jour_semaine_debut=date("w",$starttime0);
		if($jour_semaine>=$jour_semaine_debut) { 
					$starttime=$starttime0+($jour_semaine-$jour_semaine_debut)*$journee;
					if($rep_semaine==2) $starttime=$starttime+$semaine;
		} else {
		            $starttime=$starttime0+($jour_semaine-$jour_semaine_debut+7)*$journee;
		            if($rep_semaine==1) $starttime=$starttime+$semaine;
		}
		
		// fin du creneau
		settype($end_month,"integer");
		settype($end_day,"integer");
		settype($end_year,"integer");
		settype($end_minute,"integer");
		settype($end_hour,"integer");
		$minyear = strftime("%Y", Settings::get('begin_bookings'));
		$maxyear = strftime("%Y", Settings::get("end_bookings"));
		if ($end_day < 1) $end_day = 1;
		if ($end_day > 31) $end_day = 31;
		if ($end_month < 1) $end_month = 1;
		if ($end_month > 12) $end_month = 12;
		//Si la date n'est pas valide on arrête
		if (!checkdate($end_month, $end_day, $end_year))
			$erreur = 'y';
		if ($end_year < $minyear) $end_year = $minyear;
		if ($end_year > $maxyear) $end_year = $maxyear;
		$endtime0   = mktime($end_hour, $end_minute, 0, $end_month, $end_day, $end_year);
		if($jour_semaine>=$jour_semaine_debut) {
		               $endtime=$endtime0+($jour_semaine-$jour_semaine_debut)*$journee;
		               if($rep_semaine==2) $endtime=$endtime+$semaine;
		} else {
		               $endtime=$endtime0+($jour_semaine-$jour_semaine_debut+7)*$journee;
		               if($rep_semaine==1) $endtime=$endtime+$semaine;
		}
		// echo date("c",$starttime)."->".date("c",$endtime);
		
		//echo $endtime."\n";
		
		if ($endtime <= $starttime)
			$erreur = 'y';
				
		$statut_entry = "-";
		$rep_jour_c = 0;
		
		// gestion de la périodicité
		$rep_type = 2; settype($rep_type,"integer"); // réservation hebdomadaire
		if($rep_semaine==0) $rep_num_weeks = 1; else $rep_num_weeks = 2; 
		settype($rep_num_weeks,"integer"); if ($rep_num_weeks < 2) $rep_num_weeks = 1; // toutes les semaines
		$rep_month = NULL;
		// if (($rep_type==3) and ($rep_month == 3)) $rep_type =3;
		// if (($rep_type==3) and ($rep_month == 5)) $rep_type =5;
		$rep_id = NULL; // id de la périodicité associée
		$rep_day = "";
		$rep_enddate = mktime($hour, $minute, 0, $rep_end_month, $rep_end_day, $rep_end_year);
			// Cas où la date de fin de périodicité est supérieure à la date de fin de réservation
		if ($rep_enddate > Settings::get("end_bookings")) $rep_enddate = Settings::get("end_bookings");
		//echo $rep_enddate."\r";
		
		$create_by = "Administrateur";
		$beneficiaire = "Administrateur";
		$benef_ext_nom = "Administrateur";
		$benef_ext_email = "";
		$beneficiaire_ext = ""; //concat_nom_email($benef_ext_nom, $benef_ext_email);
		
		$room_back =NULL; if (isset($room_back)) settype($room_back,"integer");
		$option_reservation = NULL;
		if (isset($option_reservation))
			settype($option_reservation,"integer");
		else
			$option_reservation = -1;
		
		// On récupère la valeur de $area
		$area = mrbsGetRoomArea($room_id); 
		if(($room_id<=0) || ($area<=0)) { $erreur="Erreur de salle"; return false;}
		
		# For weekly repeat(2), build string of weekdays to repeat on:
		$rep_day=array(NULL,NULL,NULL,NULL,NULL,NULL,NULL);
		// $jour_semaine=date("w",$starttime)."\n"; // à corriger
		$rep_day[$jour_semaine]=1;
		$rep_opt = "";
		if ($rep_type == 2)
			for ($i = 0; $i < 7; $i++) $rep_opt .= empty($rep_day[$i]) ? "0" : "1";
		
		
		# Expand a series into a list of start times:
		// $reps est un tableau des dates de début de réservation
        // $rep_month_abs1 et $rep_month_abs2 semblent utilisés pour les jours cycles, je les initialise au hasard
        $rep_month_abs1 = 0;
        $rep_month_abs2 = 0;
		$reps = mrbsGetRepeatEntryList($starttime, $rep_enddate,
				$rep_type, $rep_opt, $max_rep_entrys, $rep_num_weeks,$rep_jour_c,$area,$rep_month_abs1, $rep_month_abs2);
		
		
		# When checking for overlaps, for Edit (not New), ignore this entry and series:
		$repeat_id = 0;
		if (isset($id) and ($id!=0)) {
		    $ignore_id = $id;
		    $repeat_id = grr_sql_query1("SELECT repeat_id FROM ".TABLE_PREFIX."_entry WHERE id=$id");
		    if ($repeat_id < 0) $repeat_id = 0;
		} else     
		$ignore_id = 0;
		
		# Acquire mutex to lock out others trying to book the same slot(s).
		if (!grr_sql_mutex_lock("".TABLE_PREFIX."_entry"))
			fatal_error(1, get_vocab('failed_to_acquire'));
		
		$date_now = mktime();
		$error_booking_in_past = 'no';
		$error_booking_room_out = 'no';
		$error_duree_max_resa_area = 'no';
		$error_delais_max_resa_room = 'no';
		$error_delais_min_resa_room = 'no';
		$error_date_option_reservation = 'no';
		$error_chevauchement = 'no';
		$error_qui_peut_reserver_pour = 'no';
		$error_heure_debut_fin = 'no';
	
		$diff = $endtime - $starttime;
		// On  vérifie que les différents créneaux ne se chevauchent pas.
		if (!grrCheckOverlap($reps, $diff)){ 
        $error_chevauchement = 'yes'; $erreur="<h2>Chevauchement de réservation</h2>"; return false;
        }
		$i = 0;
		while (($i < count($reps)) and ($error_booking_in_past == 'no') and ($error_duree_max_resa_area == 'no') and ($error_delais_max_resa_room == 'no') and ($error_delais_min_resa_room == 'no') and ($error_date_option_reservation=='no') and ($error_qui_peut_reserver_pour=='no') and ($error_heure_debut_fin=='no')) 
        {   if ($reps != '')
            {
                if (!(verif_date_option_reservation($option_reservation, $reps[$i]))) $error_date_option_reservation = 'yes';
                if (!(verif_heure_debut_fin($reps[$i], $reps[$i]+$diff, $area))) $error_heure_debut_fin = 'yes';
            }
			$i++;
		}
		
		// Si le test précédent est passé avec succès,
		# Check for any schedule conflicts in each room we're going to try and book in
		$err = "";
		
		if (($error_booking_in_past == 'no') and ($error_chevauchement=='no') and ($error_duree_max_resa_area == 'no') and ($error_delais_max_resa_room == 'no') and ($error_delais_min_resa_room == 'no')  and ($error_date_option_reservation == 'no') and ($error_qui_peut_reserver_pour == 'no') and ($error_heure_debut_fin=='no')) {
			if(count($reps) < $max_rep_entrys) {
				$diff = $endtime - $starttime;
				for($i = 0; $i < count($reps); $i++) {
                    if ($reps != ''){
					// Suppression des résa en conflit
							// if (isset($_GET['del_entry_in_conflict']) and ($_GET['del_entry_in_conflict']=='yes'))
					grrDelEntryInConflict($room_id, $reps[$i], $reps[$i] + $diff, $ignore_id, $repeat_id, 0);
					// On teste s'il reste des conflits
                        if ($i == (count($reps)-1)) {
                        $tmp = mrbsCheckFree($room_id, $reps[$i], $reps[$i] + $diff, $ignore_id, $repeat_id);
                        } else
                        $tmp = mrbsCheckFree($room_id, $reps[$i], $reps[$i] + $diff, $ignore_id, $repeat_id);
                        if(!empty($tmp)) $err = $err . $tmp;
                    }
				}
			} else {
				$err .= get_vocab("too_may_entrys") . "<p>";
				$hide_title  = 1;
			}
		}
		
		if (empty($err)
			and ($error_booking_in_past == 'no')
			and ($error_duree_max_resa_area == 'no')
			and ($error_delais_max_resa_room == 'no')
			and ($error_delais_min_resa_room == 'no')
			and ($error_booking_room_out == 'no')
			and ($error_date_option_reservation == 'no')
			and ($error_chevauchement == 'no')
			and ($error_qui_peut_reserver_pour == 'no')
			and ($error_heure_debut_fin == 'no')
			)
		   {
			   // l'utilisateur est gestionnaire ou admin de la ressource donc on ne modère pas !
			   $entry_moderate = 0;
			   $send_mail_moderate = 0;	
			   echo $room_id.",".$jour_semaine.",".$name.",".$description.",".$hour."h".$minute."->".$end_hour."h".$end_minute."</br>";
// 			   grr_sql_mutex_unlock("".TABLE_PREFIX."_entry");
//                return true;
// toujours des initialisations au hasard
               $courrier = 0;
               $overload_data = '';
			   mrbsCreateRepeatingEntrys($starttime, $endtime,   $rep_type, $rep_enddate, $rep_opt,
						$room_id, $create_by, $beneficiaire, $beneficiaire_ext, $name, $type, $description, $rep_num_weeks, $option_reservation,$overload_data, $entry_moderate,$rep_jour_c, $courrier, $rep_month_abs1, $rep_month_abs2);
		
			}
		
		  // Delete the original entry
		
		grr_sql_mutex_unlock("".TABLE_PREFIX."_entry");
		
		$area = mrbsGetRoomArea($room_id);
		
		// Si il y a tentative de réserver dans le passé
		if ($error_booking_in_past == 'yes') {
			$str_date = utf8_strftime("%d %B %Y, %H:%M", $date_now);
			print_header();
			// echo "<h2>" . get_vocab("booking_in_past") . "</h2>";
			$erreur=get_vocab("booking_in_past");
			if ($rep_type != 0 && !empty($reps))  {
				$erreur.= ",".get_vocab("booking_in_past_explain_with_periodicity");
			} else {
				$erreur.= ",".get_vocab("booking_in_past_explain") . $str_date;
			}
			// $erreur.= "<a href=\"".$back."&amp;Err=yes\">".get_vocab('returnprev')."</a>";
			return false;
		}
		
		// Si il y a tentative de réserver pendant une durée dépassant la durée max
		if ($error_duree_max_resa_area == 'yes') {
			$area_id = grr_sql_query1("select area_id from ".TABLE_PREFIX."_room where id='".protect_data_sql($room_id)."'");
			$duree_max_resa_area = grr_sql_query1("select duree_max_resa_area from ".TABLE_PREFIX."_area where id='".$area_id."'");
			print_header();
			$temps_format = $duree_max_resa_area*60;
			toTimeString($temps_format, $dur_units);
			$erreur.=  get_vocab("error_duree_max_resa_area").$temps_format ." " .$dur_units;
			// $erreur.=  "<a href=\"".$back."&amp;Err=yes\">".get_vocab('returnprev')."</a>";
			return false;
		}
		
		// Si il y a tentative de réserver au delà du temps limite
		if ($error_delais_max_resa_room == 'yes') {
			print_header();
			$erreur.=  get_vocab("error_delais_max_resa_room");
			// $erreur.=  "<a href=\"".$back."&amp;Err=yes\">".get_vocab('returnprev')."</a>";
			return false;
		}
		
		// Dans le cas d'une réservation avec périodicité, s'il y a des créneaux qui se chevauchent
		if ($error_chevauchement == 'yes') {
			print_header();
			$erreur.=  get_vocab("error_chevauchement");
			// $erreur.=  "<a href=\"".$back."&amp;Err=yes\">".get_vocab('returnprev')."</a>";
			return false;
		}
		
		// Si il y a tentative de réserver en-deça du temps limite
		if ($error_delais_min_resa_room == 'yes') {
			print_header();
			$erreur.=  get_vocab("error_delais_min_resa_room");
			// $erreur.=  "<a href=\"".$back."&amp;Err=yes\">".get_vocab('returnprev')."</a>";
			return false;
		}
		
		// Si la date confirmation est supérieure à la date de début de réservation
		if ($error_date_option_reservation == 'yes') {
			print_header();
			$erreur.= get_vocab("error_date_confirm_reservation");
			// $erreur.=  "<a href=\"".$back."&amp;Err=yes\">".get_vocab('returnprev')."</a>";
			return false;
		}
		
		// Si l'utilisateur tente de réserver une ressource non disponible
		if ($error_booking_room_out == 'yes') {
			print_header();
			$erreur.=  get_vocab("norights");
			$erreur.=  ", <b>" . get_vocab("tentative_reservation_ressource_indisponible") . "</b>";
			// $erreur.=  "<a href=\"".$back."&amp;Err=yes\">".get_vocab('returnprev')."</a>";
			return false;
		}
		
		// Si l'utilisateur tente de réserver au nom d'une autre personne pour une ressource pour laquelle il n'a pas le droit
		if ($error_qui_peut_reserver_pour == 'yes') {
			print_header();
			$erreur.=  get_vocab("error_qui_peut_reserver_pour");
			// $erreur.=  "<a href=\"".$back."&amp;Err=yes\">".get_vocab('returnprev')."</a>";
			return false;
		}
		
		// L'heure de début ou l'heure de fin de réservation est en dehors des créneaux autorisés.
		if ($error_heure_debut_fin == 'yes') {
			print_header();
			$erreur.=get_vocab("error_heure_debut_fin");
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
