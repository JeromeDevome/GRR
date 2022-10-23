<?php
/**
 * admin_import_entries_csv_udt.php
 * Importe un fichier de réservations au format csv 
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2022-06-19 15:46$
 * @author    JeromeB & Yan Naessens & Denis Monasse & Laurent Delineau
 * @copyright Copyright 2003-2022 Team DEVOME - JeromeB
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

$_SESSION['chemin_retour'] = "admin_config.php";

if (!Settings::load()) {
    die('Erreur chargement settings');
}
// tableau des jours ordonnés
$joursemaine=array("dim"=>0,"lun"=>1,"mar"=>2,"mer"=>3,"jeu"=>4,"ven"=>5,"sam"=>6);
$journumero=array(0=>"dim",1=>"lundi",2=>"mardi",3=>"mercredi",4=>"jeudi",5=>"vendredi",6=>"samedi");
$trad['dResultatEtape1'] = "";
$trad['dResultatEtape2'] = "";

get_vocab_admin("admin_import_users_csv0");

get_vocab_admin("back");
get_vocab_admin("submit");

//
// Affichage de la colonne de droite 
//

// $long_max : doit être plus grand que la plus grande ligne trouvée dans le fichier CSV
$long_max = 8000;
if(isset($_POST['import'])) {
	$trad['dFichierEnvoye'] = 1;

	// on commence par charger le fichier CSV dans une table provisoire grr_csv pour profiter des tris MySQL
	// ETAPE 1
	$temps_debut=time();
	$erreur=""; $nb_reservations=0;
	$fp = fopen($_FILES['csv']['tmp_name'], 'r');
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
	$sql .= ") ENGINE=MyISAM DEFAULT CHARSET=latin1";
	if(!grr_sql_query($sql)){
		echo "Erreur dans la création de la table CSV";
		die();
	}
	if(!grr_sql_query("TRUNCATE TABLE `".TABLE_PREFIX."_csv` ")){
		echo "Erreur dans le nettoyage de la table CSV";
		die();
	}

	while($reservation = fgetcsv($fp, $long_max, ";")) {
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
			} else {
				$heure_deb=intval($heure1);
				$minute_deb=0;
			}
			if($pos_h=strpos($heure2,'h')) {
				$heure_fin=intval(substr($heure2,0,$pos_h));
				$minute_fin=intval(substr($heure2,$pos_h+1,10));
			} else {
				$heure_fin=intval($heure1);
				$minute_fin=0;
			}
		} else {
			if($pos_h=strpos($heure,'h')) { 
				$heure_deb=intval(substr($heure,0,$pos_h));
				$minute_deb=intval(substr($heure,$pos_h+1,10));
				$heure_fin=intval($heure_deb)+1;
				$minute_fin=intval($minute_deb);
			} else {
				$heure_deb=intval($heure);
				$minute_deb=0;
				$heure_fin=intval($heure_deb)+1;
				$minute_fin=intval($minute_deb);
			}
		}
		// on traite la classe en se méfie du 2E1 transformé par Excel
		$reservation[2]=str_replace(",00E+0","E",strtoupper($reservation[2])); // classe-division
		// les champs suivants : discipline, enseignant, salle
		$reservation[3]=addslashes($reservation[3]); // discipline
		$reservation[4]=addslashes($reservation[4]); // enseignant
		$reservation[5]=strtoupper($reservation[5]); // salle
		// on sucre les repas et les salles inexistantes
		if(($reservation[3]=="repas") || ($reservation[5]==""))
			continue;
		// et on insère dans la base de données
		$sql_query="INSERT INTO ".TABLE_PREFIX."_csv (`id`, `jour`, `heure_deb`, `minute_deb`, `heure_fin`, `minute_fin`, `classe`, `matiere`, `professeur`, `salle`, `groupe`, `regroup`, `eff`, `mo`, `freq`, `aire`)";
		$sql_query.=" VALUES ('DEFAULT' , '".$jour."' , '".$heure_deb."' , '".$minute_deb."' , '".$heure_fin."' , '".$minute_fin;
		for($i=2; $i<12; $i++)
			$sql_query .= "' , '".$reservation[$i];
		$sql_query .= "');";
		//  echo $sql_query."</br>";
		if(!grr_sql_query($sql_query))
			$trad['dResultatEtape1'] .= "<p class=\"text-red\">Erreur dans la ligne ".$n."(".$sql_query.")</p>";
		else
			$trad['dResultatEtape1'] .= "<p class=\"text-green\">".$jour." ".$heure_deb."h".$minute_deb." à ".$heure_fin."h".$minute_fin."</p>";

		$nb_reservations++;
	}
	$nb_erreurs=0;

	// ETAPE 2
	// on récupère les données triées par jour, salle, heure de début, minute de début, classe et matière
	$sql_query="SELECT jour,salle,heure_deb,minute_deb,heure_fin,minute_fin,classe,matiere,professeur,groupe FROM ".TABLE_PREFIX."_csv ";
	$sql_query .= "ORDER BY jour,salle,heure_deb,minute_deb,classe,matiere";
	$res=grr_sql_query($sql_query);
	//echo $sql_query."</br>";
	if ($res) { 
		$i = 0; $erreur=""; $n=0;
		$trad['dResultatEtape2'] .= (grr_sql_count ($res))." réservations à effectuer"."<br>";
		while($row = grr_sql_row($res, $i)){
			$jour		= $row[0];
			$salle		= $row[1];
			$heure_deb	= $row[2];
			$minute_deb	= $row[3];
			$heure_deb_i = $heure_deb;
			$minute_deb_i = $minute_deb;
			$heure_fin	= $row[4];
			$minute_fin	= $row[5];
			$classe		= $row[6];
			$matiere	= $row[7];
			$lesclasses = $classe;
			$professeur	= $row[8];
			$fin_fusion=false;
			$i++;

			while(($i < grr_sql_count($res))&& !$fin_fusion){
				$row = grr_sql_row($res,$i);
				if(($jour==$row[0]) && ($salle==$row[1]) && ($heure_deb_i==$row[2]) && ($minute_deb_i==$row[3]))
				{
					if(strpos($lesclasses,$row[6])===false) $lesclasses=$lesclasses." et ".$row[6];
						$i++; 
				} // on elimine les doublons (meme jour, meme salle, meme heure) en concaténant les classes
				else if(($row = grr_sql_row($res, $i)) && ($jour==$row[0]) && ($salle==$row[1]) && ($classe==$row[6]) && ($matiere==$row[7]) && ($heure_fin==$row[2]) && ($minute_fin==$row[3]))
				{
					$heure_fin=$row[4]; $minute_fin=$row[5]; $heure_deb_i=$row[2]; $minute_deb_i=$row[3]; $i++;
				} // et on fusionne les creneaux consecutifs
				else
					$fin_fusion = true;
			}
			$room_id = grr_sql_query1("SELECT id FROM ".TABLE_PREFIX."_room WHERE room_name='".$salle."'");

			list($etat, $resultatTxt) = entre_reservation($room_id,$jour,$lesclasses." - ".$matiere,$professeur,$_POST['beg_day'],$_POST['beg_month'],$_POST['beg_year'],$heure_deb,$minute_deb,$_POST['beg_day'],$_POST['beg_month'],$_POST['beg_year'],$heure_fin,$minute_fin,$_POST['end_day'],$_POST['end_month'],$_POST['end_year'],0);

			if(!etat)
			{
				// on affiche les réservations non faites en un format de type CSV pour faciliter un copier-coller
				$trad['dResultatEtape2'] .= "<p class=\"text-red\">".$i.": ".$resultatTxt." Erreur dans la réservation ($erreur): ".$journumero[$row[0]]."; ".$row[1]."; ".$row[2]."h".$row[3]." -> ".$row[4]."h".$row[5]."; ".$row[6]."; ".$row[7]."; ".$row[8]."\n</p>";
				$nb_erreurs++;
			} else
				$trad['dResultatEtape2'] .= "<p class=\"text-green\">".$i.": ".$resultatTxt."</p>";
		}      
		$trad['dResultatEtape2'] .= "<h2>Importation de ".($i-$nb_erreurs)."/$nb_reservations réservations terminée au bout de ".(time()-$temps_debut)." secondes</h2>";
		$trad['dResultatEtape2'] .= "<p>Vérifiez que l'importation est bien complète (aux erreurs près), sinon restaurez la base de données, scindez le fichier CSV et recommencez.</p>";
	}
}
else
{
	//par défaut on propose la date du jour
	$day   = date("d");
	$month = date("m");
	$year  = date("Y"); 
	$trad['dDateDebut'] = genDateSelectorForm('beg_', $day, $month, $year, 'more_years');
	$trad['dDateFin'] = genDateSelectorForm('end_', $day, $month, $year, 'more_years');
}

function entre_reservation($room_id,$jour_semaine,$name,$description, $day,$month,$year,$hour,$minute,$end_day,$end_month,$end_year,$end_hour,$end_minute,
            $rep_end_day,$rep_end_month,$rep_end_year,$rep_semaine){
		global $max_rep_entrys, $erreur;

	//echo $room_id.",".$jour_semaine.",".$name.",".$description.",".$hour."h".$minute."->".$end_hour."h".$end_minute."</br>";
	// return true;

	$journee	= 86400;
	$semaine	= 86400*7;
	$erreur		= '';
	$id			= null; // nouvelle réservation
	$ampm		= NULL; // AMPM ou 24h
	$txtRetour	= "";
	$type		= "A"; // type de réservation (cours, colle, ...)

	// $duration = 2; $duration = str_replace(",", ".", "$duration ");
	settype($hour,"integer"); if ($hour > 23) $hour = 23;
	settype($minute,"integer"); if ($minute > 59) $minute = 59;
	$starttime0 = mktime($hour, $minute, 0, $month, $day, $year);
	$jour_semaine_debut=date("w",$starttime0);
	if($jour_semaine>=$jour_semaine_debut) { 
		$starttime=$starttime0+($jour_semaine-$jour_semaine_debut)*$journee;
		if($rep_semaine==2)
			$starttime=$starttime+$semaine;
	} else {
		$starttime=$starttime0+($jour_semaine-$jour_semaine_debut+7)*$journee;
		if($rep_semaine==1)
			$starttime=$starttime+$semaine;
	}
	
	// fin du creneau
	settype($end_month,"integer");
	settype($end_day,"integer");
	settype($end_year,"integer");
	settype($end_minute,"integer");
	settype($end_hour,"integer");
	$minyear = date('Y', Settings::get('begin_bookings'));
	$maxyear = date('Y', Settings::get("end_bookings"));
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
		if($rep_semaine==2)
			$endtime=$endtime+$semaine;
	} else {
		$endtime=$endtime0+($jour_semaine-$jour_semaine_debut+7)*$journee;
		if($rep_semaine==1)
			$endtime=$endtime+$semaine;
	}
	// echo date("c",$starttime)."->".date("c",$endtime);
	//echo $endtime."\n";

	if ($endtime <= $starttime)
		$erreur = 'y';

	$statut_entry = "-";
	$rep_jour_c = 0;

	// gestion de la périodicité
	$rep_type = 2; settype($rep_type,"integer"); // réservation hebdomadaire
	if($rep_semaine==0)
		$rep_num_weeks = 1;
	else
		$rep_num_weeks = 2; 
	settype($rep_num_weeks,"integer");
	if ($rep_num_weeks < 2)
		$rep_num_weeks = 1; // toutes les semaines
	$rep_month = NULL;
	// if (($rep_type==3) and ($rep_month == 3)) $rep_type =3;
	// if (($rep_type==3) and ($rep_month == 5)) $rep_type =5;
	$rep_id = NULL; // id de la périodicité associée
	$rep_day = "";
	$rep_enddate = mktime($hour, $minute, 0, $rep_end_month, $rep_end_day, $rep_end_year);
	// Cas où la date de fin de périodicité est supérieure à la date de fin de réservation
	if ($rep_enddate > Settings::get("end_bookings"))
		$rep_enddate = Settings::get("end_bookings");
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
	if(($room_id<=0) || ($area<=0)) {
		$erreur="Erreur de salle";
		return array(false, $txtRetour);
	}

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
		if ($repeat_id < 0)
			$repeat_id = 0;
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
		$error_chevauchement = 'yes';
		$erreur="<h2>Chevauchement de réservation</h2>";
		return array(false, $txtRetour);
	}
	$i = 0;
	while (($i < count($reps)) and ($error_booking_in_past == 'no') and ($error_duree_max_resa_area == 'no') and ($error_delais_max_resa_room == 'no') and ($error_delais_min_resa_room == 'no') and ($error_date_option_reservation=='no') and ($error_qui_peut_reserver_pour=='no') and ($error_heure_debut_fin=='no')) 
	{   if ($reps != '')
		{
			if (!(verif_date_option_reservation($option_reservation, $reps[$i])))
				$error_date_option_reservation = 'yes';
			if (!(verif_heure_debut_fin($reps[$i], $reps[$i]+$diff, $area)))
				$error_heure_debut_fin = 'yes';
		}
		$i++;
	}
	
	// Si le test précédent est passé avec succès,
	# Check for any schedule conflicts in each room we're going to try and
	# book in
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
					if(!empty($tmp))
						$err = $err . $tmp;
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
		// toujours des initialisations au hasard
		$courrier = 0;
		$overload_data = '';
		mrbsCreateRepeatingEntrys($starttime, $endtime,   $rep_type, $rep_enddate, $rep_opt,$room_id, $create_by, $beneficiaire, $beneficiaire_ext, $name, $type, $description, 
						$rep_num_weeks, $option_reservation,$overload_data, $entry_moderate,$rep_jour_c, $courrier, $rep_month_abs1, $rep_month_abs2);
	}

	// Delete the original entry
	grr_sql_mutex_unlock("".TABLE_PREFIX."_entry");

	$area = mrbsGetRoomArea($room_id);

	// Si il y a tentative de réserver dans le passé
	if ($error_booking_in_past == 'yes') {
		$str_date = utf8_strftime("%d %B %Y, %H:%M", $date_now);
		//print_header();
		$erreur=get_vocab("booking_in_past");
		if ($rep_type != 0 && !empty($reps))  {
			$erreur.= ",".get_vocab("booking_in_past_explain_with_periodicity");
		} else {
			$erreur.= ",".get_vocab("booking_in_past_explain") . $str_date;
		}
		return array(false, $txtRetour);
	}
	
	// Si il y a tentative de réserver pendant une durée dépassant la durée max
	if ($error_duree_max_resa_area == 'yes') {
		$area_id = grr_sql_query1("select area_id from ".TABLE_PREFIX."_room where id='".protect_data_sql($room_id)."'");
		$duree_max_resa_area = grr_sql_query1("select duree_max_resa_area from ".TABLE_PREFIX."_area where id='".$area_id."'");
		//print_header();
		$temps_format = $duree_max_resa_area*60;
		toTimeString($temps_format, $dur_units);
		$erreur.=  get_vocab("error_duree_max_resa_area").$temps_format ." " .$dur_units;
		// $erreur.=  "<a href=\"".$back."&amp;Err=yes\">".get_vocab('returnprev')."</a>";
		return array(false, $txtRetour);
	}
	
	// Si il y a tentative de réserver au delà du temps limite
	if ($error_delais_max_resa_room == 'yes') {
		//print_header();
		$erreur.=  get_vocab("error_delais_max_resa_room");
		// $erreur.=  "<a href=\"".$back."&amp;Err=yes\">".get_vocab('returnprev')."</a>";
		return array(false, $txtRetour);
	}
	
	// Dans le cas d'une réservation avec périodicité, s'il y a des créneaux qui se chevauchent
	if ($error_chevauchement == 'yes') {
		//print_header();
		$erreur.=  get_vocab("error_chevauchement");
		// $erreur.=  "<a href=\"".$back."&amp;Err=yes\">".get_vocab('returnprev')."</a>";
		return array(false, $txtRetour);
	}
	
	// Si il y a tentative de réserver en-deça du temps limite
	if ($error_delais_min_resa_room == 'yes') {
		//print_header();
		$erreur.=  get_vocab("error_delais_min_resa_room");
		// $erreur.=  "<a href=\"".$back."&amp;Err=yes\">".get_vocab('returnprev')."</a>";
		return array(false, $txtRetour);
	}
	
	// Si la date confirmation est supérieure à la date de début de réservation
	if ($error_date_option_reservation == 'yes') {
		//print_header();
		$erreur.= get_vocab("error_date_confirm_reservation");
		// $erreur.=  "<a href=\"".$back."&amp;Err=yes\">".get_vocab('returnprev')."</a>";
		return array(false, $txtRetour);
	}
	
	// Si l'utilisateur tente de réserver une ressource non disponible
	if ($error_booking_room_out == 'yes') {
		//print_header();
		$erreur.=  get_vocab("norights");
		$erreur.=  ", <b>" . get_vocab("tentative_reservation_ressource_indisponible") . "</b>";
		// $erreur.=  "<a href=\"".$back."&amp;Err=yes\">".get_vocab('returnprev')."</a>";
		return array(false, $txtRetour);
	}
	
	// Si l'utilisateur tente de réserver au nom d'une autre personne pour une ressource pour laquelle il n'a pas le droit
	if ($error_qui_peut_reserver_pour == 'yes') {
		//print_header();
		$erreur.=  get_vocab("error_qui_peut_reserver_pour");
		// $erreur.=  "<a href=\"".$back."&amp;Err=yes\">".get_vocab('returnprev')."</a>";
		return array(false, $txtRetour);
	}
	
	// L'heure de début ou l'heure de fin de réservation est en dehors des créneaux autorisés.
	if ($error_heure_debut_fin == 'yes') {
		//print_header();
		$erreur.=get_vocab("error_heure_debut_fin");
		// $erreur.=  "<a href=\"".$back."&amp;Err=yes\">".get_vocab('returnprev')."</a>";
		return array(false, $txtRetour);
	}
	
	if(strlen($err))
	{
		//print_header();
		$txtRetour .= "<h2>" . get_vocab("sched_conflict") . "</h2>";
		if(!isset($hide_title))
		{
			$txtRetour .= get_vocab("conflict");
			$txtRetour .= "<UL>";
		}
		$txtRetour .= $err;
	
		if(!isset($hide_title))
			$txtRetour .= "</UL>";
			// possibilité de supprimer la (les) réservation(s) afin de valider la nouvelle réservation.
			if(authGetUserLevel(getUserName(),$area,'area') >= 4)
				$txtRetour .= "<center><table border=\"1\" cellpadding=\"10\" cellspacing=\"1\"><tr><td class='avertissement'><h3><a href='".traite_grr_url("","y")."edit_entry_handler.php?".$_SERVER['QUERY_STRING']."&amp;del_entry_in_conflict=yes'>".get_vocab("del_entry_in_conflict")."</a></h4></td></tr></table></center><br />";
	}
	return array(true, $txtRetour);
}

echo $twig->render('admin_import_entries_csv_udt.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings));
?>