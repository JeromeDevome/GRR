<?php
/**
 * view_entry.php
 * Interface de visualisation d'une réservation
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2022-02-07 10:24$
 * @author    Laurent Delineau & JeromeB & Yan Naessens
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
$grr_script_name = 'view_entry.php'; 

include_once('include/connect.inc.php');
include_once('include/config.inc.php');
include_once('include/functions.inc.php');
include_once('include/'.$dbsys.'.inc.php');
include_once('include/misc.inc.php');
include_once('include/mrbs_sql.inc.php');
require_once('include/settings.class.php');
if (!Settings::load())
	die("Erreur chargement settings");
require_once("./include/session.inc.php");

$page = verif_page();

$fin_session = 'n';
if (!grr_resumeSession())
	$fin_session = 'y';
if (($fin_session == 'y') && (Settings::get("authentification_obli") == 1))
{
	header("Location: ./logout.php?auto=1&url=$url");
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
	header("Location: ./login.php");
	die();
}	

// Paramètres langage
include "include/language.inc.php";

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
        $back = $page.'.php?year='.$year.'&month='.$month.'&day='.$day;
        if (($page == "week_all") || ($page == "month_all") || ($page == "month_all2") || ($page == "day") || ($page == "year") || ($page == "year_all"))
            $back .= "&area=".mrbsGetRoomArea($row1['1']);
        if (($page == "week") || ($page == "month"))
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
".TABLE_PREFIX."_entry.nbparticipantmax
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
$rep_type 					= 0;
$verif_display_email 		= verif_display_email($userName, $room_id);
if ($verif_display_email)
	$option_affiche_nom_prenom_email = "withmail";
else
	$option_affiche_nom_prenom_email = "nomail";
$msg='';
// traitement du formulaire d'inscription d'un autre participant
if (isset($_GET["reg_participant"]))
{
    $reg_participant = $_GET['reg_participant']; // tester s'il est possible d'inscrire tout ce monde !
    $reg_users = array(); // participants déjà inscrits
    $resp = grr_sql_query("SELECT participant FROM ".TABLE_PREFIX."_participants WHERE idresa=$id");
    if (!$resp)
        fatal_error(0, "<p>".grr_sql_error());
    foreach($resp as $rowp)
        $reg_users[] = $rowp['participant'];
    if(count($reg_participant)<=$nbParticipantMax){
        $ex_users = array_diff($reg_users, $reg_participant); // à désincrire
        foreach($ex_users as $u){
            $sql = "DELETE FROM ".TABLE_PREFIX."_participants WHERE idresa='$id' AND participant='$u' ";
            if(grr_sql_command($sql) < 0)
                fatal_error(1, $sql."<p>" . grr_sql_error());
        }
        $new_users = array_diff($reg_participant, $reg_users); // à inscrire, ne sont pas inscrits
        foreach ($new_users as $user)
        {
            $sql = "INSERT INTO ".TABLE_PREFIX."_participants (idresa, participant) values ('$id','$user')";
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
if($nbParticipantMax > 0){
	$userParticipe = false;
    $listeParticipants = "";
	$reg_users = array(); // peut-être différent du précédent si un formulaire a été traité
	// Compte nb de participants actuel
	$resp = grr_sql_query("SELECT participant FROM ".TABLE_PREFIX."_participants WHERE idresa=$id");
    if (!$resp)
        fatal_error(0, grr_sql_error());
	$nbParticipantInscrit = grr_sql_count($resp);
	// Liste des participants
	if ($nbParticipantInscrit > 0)
	{
		foreach($resp as $rowp)
		{
			if( $rowp['participant'] == $userName)
				$userParticipe = true;
			$listeParticipants .= affiche_nom_prenom_email($rowp['participant'], "", $option_affiche_nom_prenom_email)."<br />";
            $reg_users[] = $rowp['participant'];
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

if (@file_exists("language/lang_subst_".$area.".".$locale))
	include "language/lang_subst_".$area.".".$locale;
if ((authGetUserLevel($userName, -1) < 1) and (Settings::get("authentification_obli") == 1))
{
	showAccessDenied($back);
	exit();
}
if (authUserAccesArea($userName, $area) == 0)
{
	if (isset($reservation_is_delete))
		showNoReservation($day, $month, $year, $back);
	else
		showAccessDenied($back);
	exit();
}
$date_now = time();

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
if ((Settings::get("display_level_view_entry") == '1')||($mode == 'page')) // haut de page si mode page
{
    $racineAd = "./admin/";
	start_page_w_header($day, $month, $year, $type_session);
	echo '<div class="container">';
	if ($back != "")
		echo '<div><a href="',$back,'">',get_vocab("returnprev"),'</a></div>',PHP_EOL;
}
// Affichage d'un pop-up
affiche_pop_up($msg,"admin");
echo '<fieldset><legend style="font-size:12pt;font-weight:bold">'.get_vocab('entry').get_vocab('deux_points').affichage_lien_resa_planning($breve_description, $id).'</legend>'."\n";
echo '<table>';
echo '	<tr>';
echo '		<td><b>'.get_vocab("description").'</b></td>';
echo '		<td>'.nl2br($description).'</td>';
echo '	</tr>';
echo '	<tr>';
echo '		<td><b>'.get_vocab("room"),get_vocab("deux_points").'</b></td>';
echo '		<td>'.nl2br($area_name . " - " . $room_name).'</td>';
echo '	</tr>';
echo '	<tr>';
echo '		<td><b>'.get_vocab("start_date"),get_vocab("deux_points").'</b></td>';
echo '		<td>'.$start_date.'</td>';
echo '	</tr>';
echo '	<tr>';
echo '		<td><b>'.get_vocab("duration").'</b></td>';
echo '		<td>'.$duration .' '.$dur_units.'</td>';
echo '	</tr>';
echo '	<tr>';
echo '		<td><b>'.get_vocab("end_date").'</b></td>';
echo '		<td>'.$end_date.'</td>';
echo '	</tr>';
echo '<tr>',PHP_EOL,'<td><b>',get_vocab("type"),get_vocab("deux_points"),'</b></td>',PHP_EOL;
$type_name = grr_sql_query1("SELECT type_name from ".TABLE_PREFIX."_type_area where type_letter='".$type."'");
if ($type_name == -1)
    $type_name = "?$type?";
echo '<td>',$type_name,'</td>',PHP_EOL,'</tr>',PHP_EOL;
if ($beneficiaire != $create_by)
{
    echo '<tr>';
    echo '    <td><b>'.get_vocab("reservation_au_nom_de").get_vocab("deux_points").'</b></td>';
    echo '    <td>'.affiche_nom_prenom_email($beneficiaire, $beneficiaire_ext, $option_affiche_nom_prenom_email).'</td>';
    echo '</tr>';
}
echo '	<tr>';
echo '		<td><b>'.get_vocab("created_by").get_vocab("deux_points").'</b></td>';
echo '		<td>'.affiche_nom_prenom_email($create_by, "", $option_affiche_nom_prenom_email);
			if ($active_ressource_empruntee == 'y')
			{
				$id_resa = grr_sql_query1("SELECT id from ".TABLE_PREFIX."_entry where room_id = '".$room_id."' and statut_entry='y'");
				if ($id_resa ==$id)
				echo '<span class="avertissement">(',get_vocab("reservation_en_cours"),') <img src="img_grr/buzy_big.png" align=middle alt="',get_vocab("ressource actuellement empruntee"),'" title="',get_vocab("ressource actuellement empruntee"),'" border="0" width="30" height="30" class="print_image" /></span>',PHP_EOL;
			}
echo '		</td>';
echo '	</tr>';
echo '	<tr>';
echo '		<td><b>'.get_vocab("lastupdate"),get_vocab("deux_points").'</b></td>';
echo '		<td>'.$updated.'</td>';
echo '	</tr>';
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
        echo "<tr><td><b>".htmlspecialchars($fieldname,ENT_NOQUOTES).get_vocab("deux_points")."</b></td>";
        echo "<td>".htmlspecialchars($field["valeur"],ENT_NOQUOTES)."</td></tr>";
    }
}
// Gestion des clefs :
if ($keys == 1)
{
    echo '<tr>';
    echo '    <td><b>'.get_vocab("clef").get_vocab("deux_points").'</b></td>';
    echo '    <td><img src="img_grr/key.png" alt="clef"></td>';
    echo '</tr>';
}
if ($courrier == 1)
{
    echo'<tr>';
    echo '    <td><b>'.get_vocab("courrier").get_vocab("deux_points").'</b></td>';
    echo '    <td><img src="img_grr/courrier.png" alt="courrier"></td>';
    echo '</tr>';
}
if (($delais_option_reservation > 0) && ($option_reservation != -1))
{
    echo '<tr>',PHP_EOL,'<td colspan="2">',PHP_EOL,'<div class="alert alert-danger" role="alert"><b>',get_vocab("reservation_a_confirmer_au_plus_tard_le"),PHP_EOL;
    echo time_date_string_jma($option_reservation, $dformat),'</b></div>',PHP_EOL;
    echo '</td>',PHP_EOL,'</tr>',PHP_EOL;
}
if ($moderate == 1)
{
    echo '<tr>',PHP_EOL,'<td><b>',get_vocab("moderation"),get_vocab("deux_points"),'</b></td>',PHP_EOL;
    tdcell("avertissement");
    echo '<strong>',get_vocab("en_attente_moderation"),'</strong></td>',PHP_EOL,'</tr>',PHP_EOL;
}
elseif ($moderate == 2)
{
    $sql = "SELECT motivation_moderation, login_moderateur FROM ".TABLE_PREFIX."_entry_moderate WHERE id=".$id;
    $res = grr_sql_query($sql);
    if (!$res)
        fatal_error(0, grr_sql_error());
    $row2 = grr_sql_row($res, 0);
    $description = $row2[0];
    $sql ="SELECT nom, prenom FROM ".TABLE_PREFIX."_utilisateurs WHERE login = '".$row2[1]."'";
    $res = grr_sql_query($sql);
    if (!$res)
        fatal_error(0, grr_sql_error());
    $row3 = grr_sql_row($res, 0);
    $nom_modo = $row3[1].' '.$row3[0];
    if (authGetUserLevel($userName, -1) > 1)
    {
        echo '<tr>',PHP_EOL,'<td><b>'.get_vocab("moderation").get_vocab("deux_points").'</b></td><td><strong>'.get_vocab("moderation_acceptee_par").' '.$nom_modo.'</strong>';
        if ($description != "")
            echo ' : <br />('.$description.')';
        echo '</td>',PHP_EOL,'</tr>',PHP_EOL;
    }
}
elseif ($moderate == 3)
{
    $sql = "SELECT motivation_moderation, login_moderateur from ".TABLE_PREFIX."_entry_moderate where id=".$id;
    $res = grr_sql_query($sql);
    if (!$res)
        fatal_error(0, grr_sql_error());
    $row4 = grr_sql_row($res, 0);
    $description = $row4[0];
    $sql ="SELECT nom, prenom from ".TABLE_PREFIX."_utilisateurs where login = '".$row4[1]."'";
    $res = grr_sql_query($sql);
    if (!$res)
        fatal_error(0, grr_sql_error());
    $row5 = grr_sql_row($res, 0);
    $nom_modo = $row5[1].' '.$row5[0];
    if (authGetUserLevel($userName, -1) > 1)
    {
        echo '<tr><td><b>'.get_vocab("moderation").get_vocab("deux_points").'</b></td>';
        tdcell("avertissement");
        echo '<strong>'.get_vocab("moderation_refusee").'</strong> par '.$nom_modo;
        if ($description != "")
            echo ' : <br />('.$description.')';
        echo '</td>',PHP_EOL,'</tr>',PHP_EOL;;
    }
}
echo '</table>',PHP_EOL;
if($nbParticipantMax > 0){ // réservation pour laquelle la fonctionnalité participants est activée
    echo '<div>';
    echo "<h4>".get_vocab("participants").get_vocab("deux_points")."</h4>";
    if(!$userParticipe)
    {
        if( ($nbParticipantInscrit < $nbParticipantMax) && (verif_participation_date($userName, $id, $room_id, -1, $date_now, $enable_periods)) || (!(verif_participation_date($userName, $id, $room_id, -1, $date_now, $enable_periods)) && ($can_delete_or_create != "y")) )
        {
            $room_back = isset($_GET['room_back']) ? $_GET['room_back'] : $room_id ;
            $message_confirmation = str_replace("'", "\\'", get_vocab("participant_confirm_validation"));
            echo "<a class=\"btn btn-primary btn-xs\" type=\"button\" href=\"participation_entry.php?id=".$id."&amp;series=0&amp;page=".$page."&amp;room_back=".$room_back." \"  onclick=\"return confirm('$message_confirmation');\" />".get_vocab("participant_validation")."</a>";
        }
    } 
    else{
        if( verif_participation_date($userName, $id, $room_id, -1, $date_now, $enable_periods) || (!(verif_participation_date($userName, $id, $room_id, -1, $date_now, $enable_periods)) && ($can_delete_or_create != "y")))
        {
            $room_back = isset($_GET['room_back']) ? $_GET['room_back'] : $room_id ;
            $message_confirmation = str_replace("'", "\\'", get_vocab("participant_confirm_annulation"));
            echo "<a class=\"btn btn-warning btn-xs\" type=\"button\" href=\"participation_entry.php?id=".$id."&amp;series=0&amp;page=".$page."&amp;room_back=".$room_back." \"  onclick=\"return confirm('$message_confirmation');\" />".get_vocab("participant_annulation")."</a>";
        }
    }
    echo '<div>';
    echo "<p><b>".get_vocab("participant_inscrit").get_vocab("deux_points")."</b>";
	echo $nbParticipantInscrit." / ".$nbParticipantMax."</p>";
    echo "<button type='button' class='btn btn-primary btn-sm' id='btn_liste_participe' onclick=\"toggle_visibility('liste_participants');toggle_visibility('btn_liste_participe');toggle_visibility('btn_close_liste');\">".get_vocab('participant_list').'</button>';
    echo "<button type='button' class='btn btn-primary btn-sm' id='btn_close_liste' style='display:none' onclick=\"toggle_visibility('liste_participants');toggle_visibility('btn_liste_participe');toggle_visibility('btn_close_liste'); \">".get_vocab('participant_list_hide')."</button>";
	echo "<p id='liste_participants' style='display:none'>".$listeParticipants."</p>".PHP_EOL;
    echo '</div>';
    // selon les droits qui_peut_reserver_pour, affichage d'un bloc permettant l'inscription d'un tiers à l'événement
    if (verif_qui_peut_reserver_pour($room_id, $userName, '-1'))
    {
        echo "<button type='button' class='btn btn-primary btn-sm' id='btn_participe' onclick=\"toggle_visibility('form_participant');toggle_visibility('btn_participe');toggle_visibility('btn_close_participe');\">".get_vocab('participant_register_form').'</button>';
        echo "<button type='button' class='btn btn-primary btn-sm' id='btn_close_participe' style='display:none' onclick=\"toggle_visibility('form_participant');toggle_visibility('btn_participe');toggle_visibility('btn_close_participe'); \">".get_vocab('participant_register_form_hide')."</button>";
        echo '<div id="form_participant" style="display:none">';
        echo '<h3>'.get_vocab("add_multiple_user_to_list").get_vocab("deux_points").'</h3>';
        echo '<form action="view_entry.php" method="GET">';
        echo '<select name="agent" size="8" style="width:200px;" multiple="multiple" ondblclick="Deplacer(this.form.agent,this.form.elements[\'reg_participant[]\'])">';
        foreach($av_users as $u){
            echo "<option value='".$u."'>".affiche_nom_prenom_email($u,"no_mail")."</option>";
        }
        echo '</select>';
        echo '<input type="button" value="&lt;&lt;" onclick="Deplacer(this.form.elements[\'reg_participant[]\'],this.form.agent)"/>';
        echo '<input type="button" value="&gt;&gt;" onclick="Deplacer(this.form.agent,this.form.elements[\'reg_participant[]\'])"/>';
        echo '<select name="reg_participant[]" id="reg_participant" size="8" style="width:200px;" multiple="multiple" ondblclick="Deplacer(this.form.elements[\'reg_participant[]\'],this.form.agent)">';
        foreach($reg_users as $u){
            echo "<option value='".$u."'>".affiche_nom_prenom_email($u,"no_mail")."</option>";
        }
        echo '</select>';
        echo '<input type="hidden" name="id" value="'.$id.'" >';
        echo '<input type="hidden" name="mode" value="page" >';
        echo '<input type="submit" value="Enregistrer" onclick="selectionner_liste(this.form.reg_participant);" />';
        echo '</form>';
        echo "</div>".PHP_EOL;
        echo '<p><hr></p>'.PHP_EOL;
    }
    echo '</div>';
}

$can_book = verif_booking_date($userName, $id, $room_id, -1, $date_now, $enable_periods) && verif_delais_min_resa_room($userName, $room_id, $row[10], $enable_periods) && getWritable($userName, $id);
$can_copy = verif_acces_ressource($userName, $room_id);
if (($can_book || $can_copy) && (!$was_del))
{
    echo "<div>";
            $room_back = isset($_GET['room_back']) ? $_GET['room_back'] : $room_id ;
            if ($can_book)
                echo "<input class=\"btn btn-primary\" type=\"button\" onclick=\"location.href='edit_entry.php?id=$id&amp;day=$day&amp;month=$month&amp;year=$year&amp;page=$page&amp;room_back=$room_back'\" value=\"".get_vocab("editentry")."\"/>";
            if ($can_copy)
                echo "<input class=\"btn btn-info\" type=\"button\" onclick=\"location.href='edit_entry.php?id=$id&amp;day=$day&amp;month=$month&amp;year=$year&amp;page=$page&amp;room_back=$room_back&amp;copier=copier'\" value=\"".get_vocab("copyentry")."\"/>";
            if ($can_book)
                echo "<input class=\"btn btn-warning\" type=\"button\" onclick=\"location.href='swap_entry.php?id=$id&amp;page=$page&amp;room_back=$room_back'\" value=\"".get_vocab("swapentry")."\"/>";
            if (($can_delete_or_create == "y")&& $can_book)
            {
                $message_confirmation = str_replace("'", "\\'", get_vocab("confirmdel").get_vocab("deleteentry"));
               // $room_back = isset($_GET['room_back']) ? $_GET['room_back'] : $room_id ;
            echo '<a class="btn btn-danger" type="button" href="del_entry.php?id='.$id.'&amp;series=0&amp;page='.$page.'&amp;room_back='.$room_back.' "  onclick="return confirm(\''.$message_confirmation.'\');">'.get_vocab("deleteentry").'</a>';
            }
        echo "</div>",PHP_EOL;
}
echo '</fieldset>',PHP_EOL;
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
    $weeklist = array("unused", "every week", "week 1/2", "week 1/3", "week 1/4", "week 1/5");
    if ($rep_type == 2)
        $affiche_period = get_vocab($weeklist[$rep_num_weeks]);
    else
        $affiche_period = get_vocab('rep_type_'.$rep_type);
    echo '<fieldset><legend style="font-weight:bold">'.get_vocab('periodicite_associe')."</legend>\n";
    echo '<table>';
    echo '<tr><td><b>'.get_vocab("rep_type").'</b></td><td>'.$affiche_period.'</td></tr>';
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
                    echo '<tr>',PHP_EOL,'<td><b>',get_vocab("rep_rep_day"),'</b></td>',PHP_EOL,'<td>',$opt,'</td>',PHP_EOL,'</tr>',PHP_EOL;
                else
                    echo '<tr>',PHP_EOL,'<td><b>',get_vocab("rep_rep_days"),'</b></td>',PHP_EOL,'<td>',$opt,'</td>',PHP_EOL,'</tr>',PHP_EOL;
            }
		if ($rep_type == 6)
		{
			if (Settings::get("jours_cycles_actif") == "Oui" && intval($jour_cycle) >- 1)
				echo '<tr>',PHP_EOL,'<td><b>',get_vocab("rep_rep_day"),'</b></td>',PHP_EOL,'<td>',get_vocab('jour_cycle'),' ',$jour_cycle,'</td>',PHP_EOL,'</tr>',PHP_EOL;
		}

		echo '<tr><td><b>'.get_vocab("date").get_vocab("deux_points").'</b></td><td>'.$start_date.'</td></tr>';
		echo '<tr><td><b>'.get_vocab("duration").'</b></td><td>'.$duration .' '. $dur_units.'</td></tr>';
		echo '<tr><td><b>'.get_vocab('rep_end_date').'</b></td><td>'.$rep_end_date.'</td></tr>';
	}
    if ((getWritable($userName, $id)) && verif_booking_date($userName, $id, $room_id, -1, $date_now, $enable_periods) && verif_delais_min_resa_room($userName, $room_id, $row[10], $enable_periods) && (!$was_del))
	{	
        $message_confirmation = str_replace ( "'"  , "\\'"  ,get_vocab('confirmdel').get_vocab('alterseries'));
        echo '<tr>',PHP_EOL,'<td colspan="2">',PHP_EOL,'<a class="btn btn-primary" type="button" href="edit_entry.php?id=',$id,'&amp;edit_type=series&amp;day=',$day,'&amp;month=',$month,'&amp;year=',$year,'&amp;page=',$page,'" onclick="return confirm(\'',$message_confirmation,'\');">',get_vocab("editseries"),'</a></td>',PHP_EOL,'</tr>',PHP_EOL;
		$message_confirmation = str_replace ( "'"  , "\\'"  , get_vocab("confirmdel").get_vocab("deleteseries"));
		echo '<tr>',PHP_EOL,'<td colspan="2">',PHP_EOL,'<a class="btn btn-danger" type="button" href="del_entry.php?id=',$id,'&amp;series=1&amp;day=',$day,'&amp;month=',$month,'&amp;year=',$year,'&amp;page=',$page,'" onclick="return confirm(\'',$message_confirmation,'\');">',get_vocab("deleteseries"),'</a></td>',PHP_EOL,'</tr>',PHP_EOL;
	}
    echo '</table>',PHP_EOL,'</fieldset>',PHP_EOL;
}
if (!isset($area_id))
    $area_id = 1;
if (!isset($room))
    $room = 1;
if (Settings::get("pdf") == '1'){
    if ((authGetUserLevel($userName, $area_id, "area") > 1) || (authGetUserLevel($userName, $room) >= 4))
       echo '<br><input class="btn btn-primary" onclick="popUpPdf(',$id,')" value="',get_vocab("Generer_pdf"),'" />',PHP_EOL;
}
// début du formulaire, n'a lieu d'être affiché que pour un utilisateur autorisé
if ($fin_session == 'n'){
    echo "<form action=\"view_entry.php\" method=\"get\">\n";
    echo "<input type=\"hidden\" name=\"id\" value=\"".$id."\" />\n";
    if (isset($_GET['page']))
        echo "<input type=\"hidden\" name=\"page\" value=\"".clean_input($_GET['page'])."\" />\n";
    if (($userName != '') && (authGetUserLevel($userName, $room_id) >= 3) && ($moderate == 1))
    {
        echo "<input type=\"hidden\" name=\"action_moderate\" value=\"y\" />\n";
        echo "<fieldset><legend style=\"font-weight:bold\">".get_vocab("moderate_entry")."</legend>\n";
        echo "<p>";
        echo "<input type=\"radio\" name=\"moderate\" value=\"1\" checked=\"checked\" />".get_vocab("accepter_resa");
        echo "<br /><input type=\"radio\" name=\"moderate\" value=\"0\" />".get_vocab("refuser_resa");
        if ($repeat_id)
        {
            echo "<br /><input type=\"radio\" name=\"moderate\" value=\"S1\" />".get_vocab("accepter_resa_serie");
            echo "<br /><input type=\"radio\" name=\"moderate\" value=\"S0\" />".get_vocab("refuser_resa_serie");
        }
        echo "</p><p>";
        echo "<label for=\"description\">".get_vocab("justifier_decision_moderation").get_vocab("deux_points")."</label>\n";
        echo "<textarea class=\"form-control\" name=\"description\" id=\"description\" cols=\"40\" rows=\"3\"></textarea>";
        echo "</p>";
        echo "</fieldset>\n";
    }
    if ($active_ressource_empruntee == 'y')
    {
        if ((!$was_del) && ($moderate != 1) && ($userName != '') && (authGetUserLevel($userName,$room_id) >= 3))
        {
            echo "<fieldset><legend style=\"font-weight:bold\">".get_vocab("reservation_en_cours")."</legend>\n";
            echo "<span class=\"larger\">".get_vocab("signaler_reservation_en_cours")."</span>".get_vocab("deux_points");
            echo "<br />".get_vocab("explications_signaler_reservation_en_cours");
            affiche_ressource_empruntee($room_id, "texte");
            echo "<br /><input type=\"radio\" name=\"statut_id\" value=\"-\" ";
            if ($statut_id == '-')
            {
                if (!affiche_ressource_empruntee($room_id,"autre") == 'yes')
                    echo " checked=\"checked\" ";
            }
            echo " />".get_vocab("signaler_reservation_en_cours_option_0");
            echo "<br /><br /><input type=\"radio\" name=\"statut_id\" value=\"y\" ";
            if ($statut_id == 'y')
                echo " checked=\"checked\" ";
            echo " />".get_vocab("signaler_reservation_en_cours_option_1");
            echo "<br /><br /><input type=\"radio\" name=\"statut_id\" value=\"e\" ";
            if ($statut_id == 'e')
                echo " checked=\"checked\" ";
            if ((!(Settings::get("automatic_mail") == 'yes')) || ($mail_exist == ""))
                echo " disabled ";
            echo " />".get_vocab("signaler_reservation_en_cours_option_2");
            if ((!(Settings::get("automatic_mail") == 'yes')) || ($mail_exist == ""))
                echo "<br /><i>(".get_vocab("necessite fonction mail automatique").")</i>";
            if (Settings::get("automatic_mail") == 'yes')
            {
                echo "<br /><br /><input type=\"checkbox\" name=\"envoyer_mail\" value=\"y\" ";
                if ($mail_exist == "")
                    echo " disabled ";
                echo " />".get_vocab("envoyer maintenant mail retard");
                echo "<input type=\"hidden\" name=\"mail_exist\" value=\"".$mail_exist."\" />";
            }
            echo "</fieldset>\n";
        }
    }
    if (isset($keys) && isset($courrier))
    {
        echo "<fieldset>\n";
        if ($active_cle == 'y'){
            echo "<span class=\"larger\">".get_vocab("status_clef").get_vocab("deux_points")."</span>";
            echo "<br /><input type=\"checkbox\" name=\"clef\" value=\"y\" ";
            if ($keys == 1)
                echo " checked ";
            echo " /> ".get_vocab("msg_clef");
        }
        
        if (Settings::get('show_courrier') == 'y')
        {
            echo "<br /><span class=\"larger\">".get_vocab("status_courrier").get_vocab("deux_points")."</span>";
            echo "<br /><input type=\"checkbox\" name=\"courrier\" value=\"y\" ";
            if ($courrier == 1)
                echo " checked ";
            echo " /> ".get_vocab("msg_courrier");
        }
        echo "</fieldset>";
    }
    echo '<input type="hidden" name="day" value="',$day,'" />',PHP_EOL;
    echo '<input type="hidden" name="month" value="',$month,'" />',PHP_EOL;
    echo '<input type="hidden" name="year" value="',$year,'" />',PHP_EOL;
    echo '<input type="hidden" name="page" value="',$page,'" />',PHP_EOL;
    echo '<input type="hidden" name="id" value="',$id,'" />',PHP_EOL;
    echo '<input type="hidden" name="back" value="',$back,'" />',PHP_EOL;
    echo "<br /><div style=\"text-align:center;\"><input class=\"btn btn-primary\" type=\"submit\" name=\"commit\" value=\"".get_vocab("save")."\" /></div>\n";
    echo '</form>',PHP_EOL;
} // fin du formulaire
if ((Settings::get("display_level_view_entry") == '1')||($mode == 'page')) // si mode page, on ferme le container
{
	echo '</div>',PHP_EOL;
}
end_page();
?>