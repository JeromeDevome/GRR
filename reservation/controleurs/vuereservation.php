<?php
/**
 * vuereservation.php
 * Interface de visualisation d'une réservation
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2026-01-20 15:53$
 * @author    Laurent Delineau & JeromeB & Yan Naessens
 * @author    Eric Lemeur pour les champs additionnels de type checkbox
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
$grr_script_name = 'vuereservation.php';

$trad = $vocab;
$page = verif_page();

$d["gcDossierDoc"] = $gcDossierDoc;

/* $fin_session */
$fin_session = 'n';
if (!grr_resumeSession())
	$fin_session = 'y';

if (($fin_session == 'y') && (Settings::get("authentification_obli") == 1))
{
	header("Location: ./app.php?p=deconnexion&auto=1&url=$url");
	die();
}

$userName = getUserName();

if ((Settings::get("authentification_obli") == 0) && ($userName == ''))
	$type_session = "no_session";
else
	$type_session = "with_session";

unset($reg_statut_id);

$reg_statut_id = isset($_GET["statut_id"]) ? htmlspecialchars($_GET["statut_id"]) : "";

if (isset($_GET["id"]))
{
	$id = clean_input($_GET["id"]);
	settype($id, "integer");
}
else
{
	header("Location: ./app.php?p=login");
	die();
}	

$back = (isset($_SERVER['HTTP_REFERER']))? htmlspecialchars_decode($_SERVER['HTTP_REFERER'], ENT_QUOTES) : page_accueil() ;
// echo $back;
// ici on a l'id de la réservation, on peut donc construire un lien de retour complet, à la bonne date et avec la ressource précise
$sql = "SELECT start_time, room_id FROM ".TABLE_PREFIX."_entry WHERE id=". $id;
$res = grr_sql_query($sql);
if (!$res)
    fatal_error(0, grr_sql_error());
if (grr_sql_count($res) >= 1)
{
    $row1 = grr_sql_row($res, 0);
    $year = date ('Y', $row1['0']);
    $month = date ('m', $row1['0']);
    $day = date ('d', $row1['0']);
}
grr_sql_free($res);
if (strstr ($back, 'view_entry.php'))
{
    if (isset($year)&&isset($month)&&isset($day)){
        $page = (isset($_GET['page']))? clean_input($_GET['page']) : "day";
        $back = 'app.php?p='.$page.'&year='.$year.'&month='.$month.'&day='.$day;
        if (($page == "semaine_all") || ($page == "mois_all") || ($page == "mois_all2") || ($page == "jour") || ($page == "annee") || ($page == "annee_all"))
            $back .= "&area=".mrbsGetRoomArea($row1['1']);
        if (($page == "semaine") || ($page == "mois"))
            $back .= "&room=".$row1['1'];
    }
    else
        $back = $page.".php";
}
if (isset($_GET["action_moderate"])){
	moderate_entry_do($id,$_GET["moderate"], $_GET["description"]);
	header("Location: ".$back);
    die();
}

$d['page'] = $page;
$d['back'] = $back;

$sql = "SELECT ".TABLE_PREFIX."_entry.name,
".TABLE_PREFIX."_entry.description,
".TABLE_PREFIX."_entry.beneficiaire,
".TABLE_PREFIX."_room.room_name,
".TABLE_PREFIX."_area.area_name,
".TABLE_PREFIX."_entry.type,
".TABLE_PREFIX."_entry.room_id,
".TABLE_PREFIX."_entry.repeat_id,
".grr_sql_syntax_timestamp_to_unix("".TABLE_PREFIX."_entry.timestamp").",
(".TABLE_PREFIX."_entry.end_time - ".TABLE_PREFIX."_entry.start_time),
".TABLE_PREFIX."_entry.start_time,
".TABLE_PREFIX."_entry.end_time,
".TABLE_PREFIX."_area.id,
".TABLE_PREFIX."_entry.statut_entry,
".TABLE_PREFIX."_room.delais_option_reservation,
".TABLE_PREFIX."_entry.option_reservation, " .
"".TABLE_PREFIX."_entry.moderate,
".TABLE_PREFIX."_entry.beneficiaire_ext,
".TABLE_PREFIX."_entry.create_by,
".TABLE_PREFIX."_entry.jours,
".TABLE_PREFIX."_room.active_ressource_empruntee,
".TABLE_PREFIX."_entry.clef,
".TABLE_PREFIX."_entry.courrier,
".TABLE_PREFIX."_room.active_cle,
".TABLE_PREFIX."_entry.nbparticipantmax,
".TABLE_PREFIX."_room.active_participant,
".TABLE_PREFIX."_room.inscription_participant,
".TABLE_PREFIX."_room.confidentiel_resa
FROM ".TABLE_PREFIX."_entry, ".TABLE_PREFIX."_room, ".TABLE_PREFIX."_area
WHERE ".TABLE_PREFIX."_entry.room_id = ".TABLE_PREFIX."_room.id
AND ".TABLE_PREFIX."_room.area_id = ".TABLE_PREFIX."_area.id
AND ".TABLE_PREFIX."_entry.id='".$id."'";

$sql_backup = "SELECT ".TABLE_PREFIX."_entry_moderate.name,
".TABLE_PREFIX."_entry_moderate.description,
".TABLE_PREFIX."_entry_moderate.beneficiaire,
".TABLE_PREFIX."_room.room_name,
".TABLE_PREFIX."_area.area_name,
".TABLE_PREFIX."_entry_moderate.type,
".TABLE_PREFIX."_entry_moderate.room_id,
".TABLE_PREFIX."_entry_moderate.repeat_id,
".grr_sql_syntax_timestamp_to_unix("".TABLE_PREFIX."_entry_moderate.timestamp").",
(".TABLE_PREFIX."_entry_moderate.end_time - ".TABLE_PREFIX."_entry_moderate.start_time),
".TABLE_PREFIX."_entry_moderate.start_time,
".TABLE_PREFIX."_entry_moderate.end_time,
".TABLE_PREFIX."_area.id,
".TABLE_PREFIX."_entry_moderate.statut_entry,
".TABLE_PREFIX."_room.delais_option_reservation,
".TABLE_PREFIX."_entry_moderate.option_reservation, " .
"".TABLE_PREFIX."_entry_moderate.moderate,
".TABLE_PREFIX."_entry_moderate.beneficiaire_ext,
".TABLE_PREFIX."_entry_moderate.create_by
FROM ".TABLE_PREFIX."_entry_moderate, ".TABLE_PREFIX."_room, ".TABLE_PREFIX."_area
WHERE ".TABLE_PREFIX."_entry_moderate.room_id = ".TABLE_PREFIX."_room.id
AND ".TABLE_PREFIX."_room.area_id = ".TABLE_PREFIX."_area.id
AND ".TABLE_PREFIX."_entry_moderate.id='".$id."'";
$res = grr_sql_query($sql);
if (!$res)
	fatal_error(0, grr_sql_error());
if (grr_sql_count($res) < 1)
{
	$reservation_is_delete = 'y';
	$was_del = TRUE;
	$res_backup = grr_sql_query($sql_backup);
	if (!$res_backup)
		fatal_error(0, grr_sql_error());
	$row = grr_sql_row($res_backup, 0);
    if(!is_array($row))
        fatal_error(0,"pas de réservation sauvegardée");
	grr_sql_free($res_backup);
}
else
{
	$was_del = FALSE;
	$row = grr_sql_row($res, 0);
}
grr_sql_free($res);
$breve_description 	= $row[0];
$description  		= bbcode(htmlspecialchars($row[1]),'');
$beneficiaire    	= htmlspecialchars($row[2]);
$room_name    		= htmlspecialchars($row[3]);
$area_name    		= htmlspecialchars($row[4]);
$type         		= $row[5];
$room_id      		= $row[6];
$repeat_id    		= $row[7];
$updated      		= time_date_string($row[8], $dformat);
$duration     		= $row[9];
$area      			= $row[12];
$statut_id 			= $row[13];
$delais_option_reservation 	= $row[14];
$option_reservation 		= $row[15];
$moderate 					= $row[16];
$beneficiaire_ext   		= htmlspecialchars($row[17]);
$create_by    				= htmlspecialchars($row[18]);
$jour_cycle    				= htmlspecialchars($row[19]);
$active_ressource_empruntee = htmlspecialchars($row[20]);
$keys						= $row[21];
$courrier					= $row[22];
$active_cle					= $row[23];
$nbParticipantMax			= $row[24];
$quiPeutParticiper          = $row[26];
$resa_confidentielle        = $row[27];
$rep_type 					= 0;
$verif_display_email 		= verif_display_email($userName, $room_id);
if ($verif_display_email)
	$option_affiche_nom_prenom_email = "withmail";
else
	$option_affiche_nom_prenom_email = "nomail";
$msg='';

$d['room_back'] = isset($_GET['room_back']) ? $_GET['room_back'] : $room_id ;

// traitement du formulaire d'inscription d'un autre participant
if(isset($_GET["reg_part"]))
{
    $reg_participant = array();
    if(isset($_GET["reg_participant"]))
        $reg_participant = array_map('clean_input',$_GET['reg_participant']);
    // tester s'il est possible d'inscrire tout ce monde !
    $reg_users = array(); // participants déjà inscrits
    $resp = grr_sql_query("SELECT beneficiaire FROM ".TABLE_PREFIX."_participants WHERE idresa=$id");
    if (!$resp)
        fatal_error(0, "<p>".grr_sql_error());
    foreach($resp as $rowp)
        $reg_users[] = $rowp['beneficiaire'];
    if(count($reg_participant)<=$nbParticipantMax){
        $ex_users = array_diff($reg_users, $reg_participant); // à désincrire
        foreach($ex_users as $u){
            $sql = "DELETE FROM ".TABLE_PREFIX."_participants WHERE idresa='$id' AND beneficiaire='$u' ";
            if(grr_sql_command($sql) < 0)
                fatal_error(1, $sql."<p>" . grr_sql_error());
        }
        $new_users = array_diff($reg_participant, $reg_users); // à inscrire, ne sont pas inscrits
        foreach ($new_users as $user)
        {
            $sql = "INSERT INTO ".TABLE_PREFIX."_participants (idresa, beneficiaire) values ('$id','$user')";
            if (grr_sql_command($sql) < 0)
                fatal_error(1, "<p>" . grr_sql_error());
            else
                $msg = get_vocab("add_multi_user_succeed");
        }
    }
    else{
        $nbPlacesRestantes = $nbParticipantMax - count($reg_users);
        $msg = "Enregistrement impossible : vous ne pouvez inscrire que ".$nbPlacesRestantes." nouveaux participants !";
    }
}
// réservation pour laquelle la fonctionnalité participants est activée
$nbParticipantInscrit = 0;
$listeParticipants = "";

if($nbParticipantMax > 0){
	$userParticipe = false;
	$reg_users = array(); // peut-être différent du précédent si un formulaire a été traité
	// Compte nb de participants actuel
	$resp = grr_sql_query("SELECT beneficiaire FROM ".TABLE_PREFIX."_participants WHERE idresa=$id");
    if (!$resp)
        fatal_error(0, grr_sql_error());
	$nbParticipantInscrit = grr_sql_count($resp);
	// Liste des participants
	if ($nbParticipantInscrit > 0)
	{
		foreach($resp as $rowp)
		{
			if( $rowp['beneficiaire'] == $userName)
				$userParticipe = true;
			$listeParticipants .= affiche_nom_prenom_email($rowp['beneficiaire'], "", $option_affiche_nom_prenom_email)."<br />";
            $reg_users[] = $rowp['beneficiaire'];
		}
	}
	grr_sql_free($resp);
    $all_users = array();
    $resu = grr_sql_query("SELECT login FROM ".TABLE_PREFIX."_utilisateurs WHERE 1");
    if (!$resu)
        fatal_error(0, grr_sql_error());
    foreach($resu as $rowu){
        $all_users[] = $rowu['login'];
    }
    grr_sql_free($resu);
    $av_users = array_diff($all_users, $reg_users);
}

if (($fin_session == 'n') && ($userName!='') && (authGetUserLevel($userName, $room_id) >= 3) && (isset($_GET['commit'])))
{
	if (!$was_del)
	{
		if ($reg_statut_id != "")
		{
			$upd1 = "UPDATE ".TABLE_PREFIX."_entry SET statut_entry='-' WHERE room_id = '".$room_id."'";
			if (grr_sql_command($upd1) < 0)
				fatal_error(0, grr_sql_error());
			$upd2 = "UPDATE ".TABLE_PREFIX."_entry SET statut_entry='$reg_statut_id' WHERE id = '".$id."'";
			if (grr_sql_command($upd2) < 0)
				fatal_error(0, grr_sql_error());
		}
		if (isset($_GET['clef']))
		{
			$clef = 1;
			$upd = "UPDATE ".TABLE_PREFIX."_entry SET clef='$clef' WHERE id = '".$id."'";
			if (grr_sql_command($upd) < 0)
				fatal_error(0, grr_sql_error());
		}
		else
		{
			$clef = 0;
			$upd = "UPDATE ".TABLE_PREFIX."_entry SET clef='$clef' WHERE id = '".$id."'";
			if (grr_sql_command($upd) < 0)
				fatal_error(0, grr_sql_error());
		}
		if (isset($_GET['courrier']))
		{
			$courrier = 1;
			$upd = "UPDATE ".TABLE_PREFIX."_entry SET courrier='$courrier' WHERE id = '".$id."'";
			if (grr_sql_command($upd) < 0)
				fatal_error(0, grr_sql_error());
		}
		else
		{
			$courrier = 0;
			$upd = "UPDATE ".TABLE_PREFIX."_entry SET courrier='$courrier' WHERE id = '".$id."'";
			if (grr_sql_command($upd) < 0)
				fatal_error(0, grr_sql_error());
		}
		if ((isset($_GET["envoyer_mail"])) && (Settings::get("automatic_mail") == 'yes'))
		{
			$_SESSION['session_message_error'] = send_mail($id, 7, $dformat);
			if ($_SESSION['session_message_error'] == "")
			{
				$_SESSION['displ_msg'] = "yes";
				$_SESSION["msg_a_afficher"] = get_vocab("un email envoye")." ".clean_input($_GET["mail_exist"]);
			}
            else
                display_mail_msg();
		}
        $back = filter_var($_GET['back'], FILTER_SANITIZE_URL);
		header("Location: ".$back."");
		die();
	}
}
if (!isset($day) || !isset($month) || !isset($year))
{
	$day   = date("d");
	$month = date("m");
	$year  = date("Y");
}

if (@file_exists("../personnalisation/langue/lang_subst_".$area."_".$locale.".php"))
	include "../personnalisation/langue/lang_subst_".$area."_".$locale.".php";
if ((authGetUserLevel($userName, -1) < 1) and (Settings::get("authentification_obli") == 1))
{
	showAccessDenied($back);
	exit();
}

// Vérification des droits d'accès à la fiche réservation
$acces_fiche_reservation = (verif_acces_fiche_reservation($userName, $room_id))||($userName == $create_by);
if($acces_fiche_reservation)
    if(($resa_confidentielle == 1) && ($userName != $beneficiaire) && (authGetUserLevel($userName, $room_id) < 3))
        $acces_fiche_reservation = false;

if (!$acces_fiche_reservation)
{
	showAccessDenied($back);
	exit();
}

if (authUserAccesArea($userName, $area) == 0)
{
	if (isset($reservation_is_delete))
        $d['messageErreur'] = showNoReservation($day, $month, $year, $back);
	else
		$d['messageErreur'] = showAccessDenied_twig($back);
    
    echo $twig->render('erreur.twig', array('trad' => $trad, 'd' => $d, 'settings' => $AllSettings));
	exit();
}
$date_now = time();
$resa = array();
$participantsDisponible = array();
$participantsEnregistrer = array();
$champsComp = array();

$d['day'] = $day;
$d['month'] = $month;
$d['year'] = $year;

get_planning_area_values($area);
if ($enable_periods == 'y')
{
	list( $start_period, $start_date) = period_date_string($row[10]);
	list( , $end_date) =  period_date_string($row[11], -1);
	toPeriodString($start_period, $duration, $dur_units);
}
else
{
	$start_date = time_date_string($row[10],$dformat);
	$end_date = time_date_string($row[11], $dformat);
	toTimeString($duration, $dur_units);
}	
if ($beneficiaire != "")
	$mail_exist = grr_sql_query1("SELECT email FROM ".TABLE_PREFIX."_utilisateurs WHERE login='$beneficiaire'");
else
{
	$tab_benef = donne_nom_email($beneficiaire_ext);
	$mail_exist = $tab_benef["email"];
}
$mode = isset($_GET['mode'])? $_GET['mode'] : NULL;

$d['mode'] = $mode;


$d['mailBeneficiaire'] = $mail_exist;

if ((Settings::get("display_level_view_entry") == '1')||($mode == 'page')) // haut de page si mode page
    $d['modePage'] = 1;

$type_name = grr_sql_query1("SELECT type_name from ".TABLE_PREFIX."_type_area where type_letter='".$type."'");
if ($type_name == -1)
    $type_name = "?$type?";

// Affichage d'un pop-up
affiche_pop_up($msg,"admin");


$resa['id'] = $id;
$resa['breveDescription'] = affichage_lien_resa_planning($breve_description, $id);
$resa['description'] = $description;
$resa['domaine'] = $area_name;
$resa['ressource'] = $room_name;
$resa['datedepart'] = $start_date;
$resa['duree'] = $duration;
$resa['dureeunite'] = $dur_units;
$resa['datefin'] = $end_date;
$resa['type'] = $type_name;
$resa['createur'] = affiche_nom_prenom_email($create_by, "", $option_affiche_nom_prenom_email);
$resa['derniereMAJ'] = $updated;
$resa['clef'] = $keys;
$resa['courrier'] = $courrier;
$resa['moderation'] = $moderate;
$resa['nbParticipantMax'] = $nbParticipantMax;
$resa['nbParticipantInscrit'] = $nbParticipantInscrit;
$resa['listeParticipants'] = $listeParticipants;
$resa['idRepetition'] = $repeat_id;
$resa['idStatut'] = $statut_id;
$resa['courrier'] = $courrier;
$resa['ressourceClef'] = $active_cle;

if ($beneficiaire != $create_by)
    $resa['beneficiaire'] = affiche_nom_prenom_email($beneficiaire, $beneficiaire_ext, $option_affiche_nom_prenom_email);

if ($active_ressource_empruntee == 'y')
{
    $id_resa = grr_sql_query1("SELECT id from ".TABLE_PREFIX."_entry where room_id = '".$room_id."' and statut_entry='y'");
    if ($id_resa ==$id)
        $resa['emprunte'] = 1;
}
// Les champs add :
$overload_data = mrbsEntryGetOverloadDesc($id);
/* les champs additionnels s'affichent dans la description si
    (le champ est affiché sur les plannings ou le champ n'est pas confidentiel) et (la valeur est non vide)
    ou (l'utilisateur est administrateur du domaine ou le bénéficiaire ou le créateur de la réservation)
*/


foreach ($overload_data as $fieldname=>$field)
{
    if (((($field["affichage"] == 'y')||($field['confidentiel'] == 'n')) && ($field["valeur"]!=""))
        ||((($fin_session == 'n')&&($userName != ''))&&(authGetUserLevel($userName, $room_id) >= 4) 
            || ($beneficiaire == $userName)||($create_by == $userName)))
    {
        // ELM - Gestion des champs additionnels multivalués
		$valeurs = explode('|', $field["valeur"]);
		if (count($valeurs) > 1)
			$valeurs = implode(" - ", $valeurs);
		else
			$valeurs = $field["valeur"];

        $champsComp[] = array('nom' => $fieldname, 'valeur' => $valeurs);
    }
}

if (($delais_option_reservation > 0) && ($option_reservation != -1))
    $resa['delaisOption'] = time_date_string_jma($option_reservation, $dformat);

elseif ($moderate == 2 || $moderate == 3)
{
    $sql = "SELECT motivation_moderation, login_moderateur FROM ".TABLE_PREFIX."_entry_moderate WHERE id=".$id;
    $res = grr_sql_query($sql);
    if (!$res)
        fatal_error(0, grr_sql_error());
    $row2 = grr_sql_row($res, 0);

    $sql ="SELECT nom, prenom FROM ".TABLE_PREFIX."_utilisateurs WHERE login = '".$row2[1]."'";
    $res = grr_sql_query($sql);
    if (!$res)
        fatal_error(0, grr_sql_error());
    $row3 = grr_sql_row($res, 0);

    $resa['nomModo'] = $row3[1].' '.$row3[0];
    $resa['commentaireModo'] = $row2[0];
}

if($nbParticipantMax > 0){ // réservation pour laquelle la fonctionnalité participants est activée
    if(!$userParticipe)
    {
        if( ($nbParticipantInscrit < $nbParticipantMax) && ((verif_participation_date($userName, $id, $room_id, -1, $date_now, $enable_periods)) || !(verif_participation_date($userName, $id, $room_id, -1, $date_now, $enable_periods))) && (authGetUserLevel($userName, $room_id) >= $quiPeutParticiper) )
            $d["participationValidation"] = 1;
    } 
    else{
        if( (verif_participation_date($userName, $id, $room_id, -1, $date_now, $enable_periods) || !(verif_participation_date($userName, $id, $room_id, -1, $date_now, $enable_periods))) && (authGetUserLevel($userName, $room_id) >= $quiPeutParticiper) )
           $d["participationAnnulation"] = 1;
    }

    // selon les droits qui_peut_reserver_pour, affichage d'un bloc permettant l'inscription d'un tiers à l'événement
    if (verif_qui_peut_reserver_pour($room_id, $userName, '-1'))
    {
        $d["quiPeutReserverPour"] = 1;

        $utilisateurConnecte = array();
        $participantsEnregistrer = array();

        foreach($av_users as $u){
            $participantsDisponible[] = array('login' => $u, 'nomPrenom' => affiche_nom_prenom_email($u,"no_mail"));
        }

        foreach($reg_users as $u){
            $participantsEnregistrer[] = array('login' => $u, 'nomPrenom' => affiche_nom_prenom_email($u,"no_mail"));
        }
    }
}

$can_book = verif_booking_date($userName, $id, $room_id, -1, $date_now, $enable_periods) && verif_delais_min_resa_room($userName, $room_id, $row[10], $enable_periods) && getWritable($userName, $id);
$can_copy = verif_acces_ressource($userName, $room_id);

if (($can_book || $can_copy) && (!$was_del))
{
    $d['accesBoutons'] = 1;
    echo "<div>";
        $room_back = isset($_GET['room_back']) ? $_GET['room_back'] : $room_id ;
        if ($can_book)
            $d['lienModifier'] = "app.php?p=editentree&id=$id&amp;day=$day&amp;month=$month&amp;year=$year&amp;page=$page&amp;room_back=$room_back";
        if ($can_copy)
            $d['lienCopier'] = "app.php?p=editentree&id=$id&amp;day=$day&amp;month=$month&amp;year=$year&amp;page=$page&amp;room_back=$room_back&amp;copier=copier";
       if ($can_book)
            $d['lienEchanger'] = "app.php?p=echangeresa&amp;id=$id&amp;page=$page&amp;room_back=$room_back";
       if (($can_delete_or_create == "y")&& $can_book)
        {
            $d['lienSupprimer'] = "app.php?p=supreservation&amp;id=".$id."&amp;series=0&amp;page=".$page."&amp;room_back=".$room_back;
        }
}

// Données de la périodicité
if ($repeat_id != 0)
{
    $res = grr_sql_query("SELECT rep_type, end_date, rep_opt, rep_num_weeks, start_time, end_time FROM ".TABLE_PREFIX."_repeat WHERE id=$repeat_id");
    if (!$res)
        fatal_error(0, grr_sql_error());
    if (grr_sql_count($res) == 1)
    {
        $row6 			= grr_sql_row($res, 0);
        $rep_type     	= $row6[0];
        $rep_end_date 	= utf8_strftime($dformat,$row6[1]);
        $rep_opt      	= $row6[2];
        $rep_num_weeks 	= $row6[3];
        $start_time 	= $row6[4];
        $end_time 		= $row6[5];
        $duration 		= $row6[5] - $row6[4];
    }
    grr_sql_free($res);
    if ($enable_periods == 'y')
	{
        list( $start_period, $start_date) = period_date_string($start_time);
		toPeriodString($start_period, $duration, $dur_units);
	}
    else
	{
        $start_date = time_date_string($start_time, $dformat);
		toTimeString($duration, $dur_units);
	}
    $weeklist = array("unused", "every_week", "week_1_of_2", "week_1_of_3", "week_1_of_4", "week_1_of_5");
    if ($rep_type == 2)
        $resa['typePeriode'] = get_vocab($weeklist[$rep_num_weeks]);
    else
        $resa['typePeriode'] = get_vocab('rep_type_'.$rep_type);

    $resa['debutPeriode'] = $start_date;
    $resa['finPeriode'] = $rep_end_date;
    $resa['dureePeriode'] = $duration;
    $resa['dureeUnitePeriode'] = $dur_units;

    if ($rep_type != 0)
    {
        if ($rep_type == 2)
        {
            $opt = "";
            $nb = 0;
            for ($i = 0; $i < 7; $i++)
            {
                $daynum = ($i + $weekstarts) % 7;
                if ($rep_opt[$daynum])
                {
                    if ($opt != '')
                        $opt .=', ';
                    $opt .= day_name($daynum);
                    $nb++;
                }
            }
            if ($opt)
                if ($nb == 1)
                    $d['repDay'] = get_vocab("rep_rep_day");
                else
                    $d['repDay'] = get_vocab("rep_rep_days");

                $resa['opt'] = $opt;
            }
		elseif ($rep_type == 6)
		{
			if (Settings::get("jours_cycles_actif") == "Oui" && intval($jour_cycle) >- 1)
            {
                $d['repDay'] = get_vocab("rep_rep_day");
                $resa['opt'] = get_vocab('jour_cycle')." ".$jour_cycle;
            }
		}

	}
    if ((getWritable($userName, $id)) && verif_booking_date($userName, $id, $room_id, -1, $date_now, $enable_periods) && verif_delais_min_resa_room($userName, $room_id, $row[10], $enable_periods) && (!$was_del))
	{	
        $d['lienPeriodeModifier'] = "app.php?p=editentree&id=".$id."&amp;edit_type=series&amp;day=".$day."&amp;month=".$month."&amp;year=".$year."&amp;page=".$page;
        $d['lienPeriodeSupprimer'] = "app.php?p=supreservation&amp;id=".$id."&amp;series=1&amp;day=".$day."&amp;month=".$month."&amp;year=".$year."&amp;page=".$page;
        $d['lienPeriodeSupprimerPosterieure'] = "app.php?p=supreservation&amp;id=".$id."&amp;series=2&amp;day=".$day."&amp;month=".$month."&amp;year=".$year."&amp;page=".$page;
    }
}

// données liées aux fichiers attachés
$droit_acces = authGetUserLevel($userName, $room_id);
$res = grr_sql_query("SELECT access_file, user_right, upload_file FROM ".TABLE_PREFIX."_area WHERE id =$area");
$attached_files = array();
if(!$res)
  fatal_error(0,grr_sql_error());
else{
  $level = grr_sql_row($res,0);
  $access_file = $level[0];
  $user_right = $level[1];
  $upload_file = $level[2];
  // gestion fichiers joints si l'utilisateur a les droits et la fonctionnalité est activée
  if ($id != 0 && $droit_acces>=$user_right && $access_file==1){
    // récupère la liste des fichiers associé à la réservation.
    $fRes = grr_sql_query("SELECT id, file_name, public_name from ".TABLE_PREFIX."_files where id_entry =$id");
    if (!$fRes){
      fatal_error(0, grr_sql_error());
    }
    else{
      foreach($fRes as $frow){
        $attached_files[] = $frow;
      }
    }
    grr_sql_free($fRes);
  }
}



if (!isset($area_id))
    $area_id = 1;
if (!isset($room))
    $room = 1;
if (Settings::get("pdf") == '1'){
    if ((authGetUserLevel($userName, $area_id, "area") > 1) || (authGetUserLevel($userName, $room) >= 4))
        $d['lienPDF'] = 1;
}
// début du formulaire, n'a lieu d'être affiché que pour un utilisateur autorisé

$d['fin_session'] = $fin_session;
$d['back'] = $back;

if ($id != 0 && $droit_acces >= $user_right && $access_file==1){
    $d['accessFile'] = 1;
    $d['countFile'] = count($attached_files);


    if ($droit_acces >= $upload_file) { // droit de téléverser
        $d['uploadFile'] = 1;
    }
}

if ($fin_session == 'n'){
    if (($userName != '') && (authGetUserLevel($userName, $room_id) >= 3) && ($moderate == 1))
    {
        $d['choixModeration'] = 1;
    }
    if ($active_ressource_empruntee == 'y')
    {
        if ((!$was_del) && ($moderate != 1) && ($userName != '') && (authGetUserLevel($userName,$room_id) >= 3))
        {
            $d['choixEmprunter'] = 1;
            $d['ressourceEmpruntee'] = affiche_ressource_empruntee_twig($room_id, "texte");
            $d['ressourceEmprunteeYes'] = affiche_ressource_empruntee_twig($room_id, "autre");
        }
    }
    if (isset($keys) && isset($courrier))
    {
        $d['clefCourrier'] = 1;
    }

} // fin du formulaire


echo $twig->render('vuereservation.twig', array('trad' => $trad, 'd' => $d, 'settings' => $AllSettings, 'resa' => $resa, 'participantsDisponible' => $participantsDisponible, 'participantsEnregistrer' => $participantsEnregistrer, 'champscomp' => $champsComp, 'attached_files' => $attached_files));
?>