<?php
/**
 * admin_import_entries_csv_direct.php
 * Importe un fichier de réservations au format csv comprenant les champs : date du jour, heure de début, heure de fin, ressource, description et type
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2019-02-16 16:40$
 * @author    JeromeB & Yan Naessens & Denis Monasse & Laurent Delineau
 * @copyright Copyright 2003-2019 Team DEVOME - JeromeB
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
 

if (isset($_GET['ok'])) {
    $msg = get_vocab("message_records");
	affiche_pop_up($msg,"admin");
}

$back = "./admin_calend.php?";
$joursemaine=array("dim"=>0,"lun"=>1,"mar"=>2,"mer"=>3,"jeu"=>4,"ven"=>5,"sam"=>6);
$journumero=array(0=>"dim",1=>"lundi",2=>"mardi",3=>"mercredi",4=>"jeudi",5=>"vendredi",6=>"samedi");
$trad['dResultatEtape1'] = "";
$trad['dResultatEtape2'] = "";

get_vocab_admin("admin_import_users_csv0");

get_vocab_admin("back");
get_vocab_admin("submit");
 
// $long_max : doit être plus grand que la plus grande ligne trouvée dans le fichier CSV
$long_max = 8000;
    
if(isset($_POST['import'])) {
	$trad['dFichierEnvoye'] = 1;

	// on commence par charger le fichier CSV dans une table provisoire grr_csv2 pour profiter des tris MySQL
	// ETAPE 1
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
		$sql_query="INSERT INTO ".TABLE_PREFIX."_csv2 (`date`, `heure_deb`, `minute_deb`, `heure_fin`, `minute_fin`, `ressource`, `description`, `type`)";
		$sql_query.=" VALUES ('".$date."' , '".$heure_deb."' , '".$minute_deb."' , '".$heure_fin."' , '".$minute_fin."' , '".$ressource."' , '".$description."' , '".$type."');";
		$trad['dResultatEtape1'] .= "<p class=\"text-green\">".$date." ".$heure_deb."h".$minute_deb." à ".$heure_fin."h".$minute_fin." => ".$ressource." : ".$description." ; ".$type."</p>";
		if(!grr_sql_query($sql_query))
			$trad['dResultatEtape1'] .= "<p class=\"text-red\">Erreur dans la ligne ".$nb_reservations."(".$sql_query.")</p>";
		$nb_reservations++;
	}
	$nb_erreurs=0;
    // ETAPE 2
	// on récupère les données : date, heure de début, minute de début, heure de fin, minute de fin, ressource, description, type
	$sql_query="SELECT date,heure_deb,minute_deb,heure_fin,minute_fin,ressource,description,type FROM ".TABLE_PREFIX."_csv2 ";
	$res=grr_sql_query($sql_query);
	if ($res) { 
	 // on a des données à traiter
		  $i = 0;
		  $n = 0;
		  $erreur = ""; 
		  while($row = grr_sql_row($res, $i)){
			$date		= $row[0]; 
			$heure_deb	= $row[1];
			$minute_deb	= $row[2];
			$heure_fin	= $row[3];
			$minute_fin	= $row[4];
			$ressource	= $row[5];
			$description= $row[6];
			$type		= $row[7];
			$fin_fusion	= false;
			$room_id = grr_sql_query1("SELECT id FROM ".TABLE_PREFIX."_room WHERE room_name='".$ressource."'");
			$i++;
			
			list($etat, $resultatTxt) = ajoute_reservation($room_id,$date,$heure_deb,$minute_deb,$heure_fin,$minute_fin,$description,$type);
			
			if(!$etat)
			{
				// on affiche les réservations non faites en un format de type CSV pour faciliter un copier-coller
				$trad['dResultatEtape2'] .= "<p class=\"text-red\">".$i.": ".$resultatTxt." Erreur dans la réservation ($erreur): ".$row[0].", ".$row[1]."h".$row[2]." -> ".$row[3]."h".$row[4].", ".$row[5].", ".$row[6].", ".$row[7]."</p>";
				$nb_erreurs++;
			} else
				$trad['dResultatEtape2'] .= "<p class=\"text-green\">".$i.": ".$resultatTxt."</p>";
		}      
		$trad['dResultatEtape2'] .= "<h2>Importation de ".($i-$nb_erreurs)."/$nb_reservations réservations terminée au bout de ".(time()-$temps_debut)." secondes</h2>";
		$trad['dResultatEtape2'] .= "<p>Vérifiez que l'importation est bien complète (aux erreurs près), sinon vous pouvez restaurer la base de données, scinder le fichier CSV et recommencer.</p>";
	}
}


function ajoute_reservation($room_id,$date,$heure_deb,$minute_deb,$heure_fin,$minute_fin,$description,$type)
{
	global $max_rep_entrys, $erreur;

	$journee	= 86400;
	$semaine	= 86400*7;
	$erreur		= '';
	$id			= null; // nouvelle réservation
	$ampm		= NULL; // AMPM ou 24h
	$txtRetour	= "";
	// détermination du type de réservation
	$type_id = grr_sql_query1("SELECT id FROM ".TABLE_PREFIX."_type_area WHERE type_name='".$type."'");
	// voir pour traiter les erreurs 	

	// détermination du starttime
	// on convertit la date en année, mois, jour
	$year	= substr($date,0,4);settype($year,"integer");
	$month	= substr($date,5,2);settype($month,"integer");
	$day	= substr($date,8,2);settype($day,"integer");

	settype($heure_deb,"integer");settype($minute_deb,"integer");
	$starttime = mktime($heure_deb, $minute_deb, 0, $month, $day, $year);

	// vérification du starttime 
	if($starttime < Settings::get("begin_bookings"))
		$erreur = 'y';

	// détermination de endtime
	settype($heure_fin,"integer");settype($minute_fin,"integer");
	$endtime = mktime($heure_fin,$minute_fin,0,$month,$day,$year);

	// vérification de endtime
	if($endtime > Settings::get("end_bookings"))
		$erreur = 'y';

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
	if(($room_id<=0) || ($area<=0))
	{
		$erreur="Erreur de salle";
		return array(false, $txtRetour);
	}
    
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
		$txtRetour .= "Créneau occupé".$occupied."</br>";
		$error_booking_room_out = true ;
	}
	else
		$txtRetour .= "Créneau libre </br>";

	// on vérifie qu'on ne réserve pas dans le passé
	$error_booking_in_past = $starttime < $date_now ;
	// Si il y a tentative de réserver dans le passé
	if ($error_booking_in_past) {
		$str_date = utf8_strftime("%d %B %Y, %H:%M", $date_now);
		$erreur=get_vocab("booking_in_past");
		$erreur.= ",".get_vocab("booking_in_past_explain") . $str_date;
		return array(false, $txtRetour);
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
		$nbmaxparticipant = 0;
		$txtRetour .= date('c',$starttime)." ".date('c',$endtime)."</br>";
		$ecriture=mrbsCreateSingleEntry(0,$starttime, $endtime, $entry_type, $repeat_id, $room_id, $create_by, $beneficiaire, $beneficiaire_ext, 
						$name, $type, $description, $option_reservation,$overload_data, $moderate, $rep_jour_c, $statut_entry, $keys, $courrier, $nbmaxparticipant);
		$txtRetour .= $ecriture."</br>";
		return array(true, $txtRetour);
	}

	grr_sql_mutex_unlock("".TABLE_PREFIX."_entry");

	// Si l'utilisateur tente de réserver une ressource non disponible
	if ($error_booking_room_out) {
		$erreur.=  get_vocab("norights");
		$erreur.=  ", <b>" . get_vocab("tentative_reservation_ressource_indisponible") . "</b>";
		return array(false, $txtRetour);
	}

	if(strlen($err))
	{

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

echo $twig->render('admin_import_entries_csv_direct.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings));
?>