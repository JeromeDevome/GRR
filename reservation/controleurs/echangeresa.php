<?php
/**
 * echangeresa.php
 * Interface d'échange d'une réservation avec une autre, à choisir
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2024-04-27 17:20$
 * @author    Laurent Delineau & JeromeB & Yan Naessens
 * @copyright Since 2003 Team DEVOME - JeromeB
 * @link      http://www.gnu.org/licenses/licenses.html
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */
 
$grr_script_name = "echangeresa.php";

$trad = $vocab;

$current_user = getUserName();
if ((Settings::get("authentification_obli") == 0) && ($current_user == ''))
	$type_session = "no_session";
else
	$type_session = "with_session";

$series = isset($_GET["series"]) ? $_GET["series"] : NULL;
if (isset($series))
	$series = intval($series);
$page = verif_page();

if (isset($_GET["id"]))
	$id = intval($_GET["id"]);
else {
    header("Location: ./app.php?p=jour");
	die();    
}

if (Settings::get("fct_echange_resa") != "y"){
    header("Location: ./app.php?p=jour");
	die();    
}


if (isset($_GET["id_alt"]))
    $idAlt = intval($_GET['id_alt']);

$d['cssTypeResa'] = cssTypeResa();

$resa1      = array(); // resa de base
$resaDispo  = array(); // resa pouvant être échangé
$resa2      = array(); // resa sélectionné pour échange

function libelle($type){ // rend la description du type_lettre de réservation
    $sql = "SELECT type_name FROM ".TABLE_PREFIX."_type_area WHERE type_letter ='".$type."' ";
    $res = grr_sql_query($sql);
    if ($res){
        return grr_sql_row($res,0)[0];
    }
    else 
        print(grr_sql_error($res));
    grr_sql_free($res);
}

function roomDesc($id_room){ // rend nom (description) à partir de l'identifiant de la ressource
    $sql = "SELECT room_name,description FROM ".TABLE_PREFIX."_room WHERE id = '".$id_room."' ";
    $res = grr_sql_query($sql);
    if ($res){
        $data = grr_sql_row($res,0);
        $desc = $data[0];
        if ($data[1]!=''){$desc .= ' ('.$data[1].')';}
        return $desc;
    }
    else 
        print(grr_sql_error($res));
    grr_sql_free($res);
}


if (isset($idAlt)){ // les paramètres sont connus
    if (isset($_GET['choix'])){ // Etape 3: la demande d'échange est confirmée
        $sql1 = "SELECT * FROM ".TABLE_PREFIX."_entry WHERE id=".$id;
        $res1 = grr_sql_query($sql1);
        if ($res1){
            $data1 = grr_sql_row($res1,0);
            $sql2 = "SELECT * FROM ".TABLE_PREFIX."_entry WHERE id=".$idAlt;
            $res2 = grr_sql_query($sql2);
            if ($res2){
                $data2 = grr_sql_row($res2,0);
                $sql3 = " UPDATE ".TABLE_PREFIX."_entry SET ";
                $sql3 .= "room_id = '".$data1[5]."' ";
                $sql3 .= "WHERE id = ".$data2[0]; 
                $res3 = grr_sql_query($sql3);
                if ($res3){                    
                    $sql4 = " UPDATE ".TABLE_PREFIX."_entry SET ";
                    $sql4 .= "room_id = '".$data2[5]."' ";//"', ";
                    $sql4 .= "WHERE id = ".$data1[0]; 
                    $res4 = grr_sql_query($sql4);
                    if ($res4){
                        $d['etape'] = 3; // échange réussi, envoyer un mail si programmé
                        if (Settings::get("automatic_mail") == 'yes'){
                            $_SESSION['session_message_error'] = send_mail($data1[0],2,$dformat,array(),$data1[5]);
                            $_SESSION['session_message_error'] = send_mail($data2[0],2,$dformat,array(),$data2[5]);
                        }
                    }
                }
            }
        }
        if (!$res1 || !$res2 || !$res3 || !$res4){
            $_SESSION['session_message_error'] = grr_sql_error();
        }
    }
    else { // Etape 2: on demande confirmation
        $d['etape'] = 2;
        $resa1 = mrbsGetEntryInfo($id);
        $resa1['resaId'] = $id;
        $resa1['resaDebut'] = time_date_string($resa1['start_time'],$dformat);
        $resa1['resaFin'] = time_date_string($resa1['end_time'],$dformat);
        $resa1['resaRessource'] = roomDesc($resa1['room_id']);
        $resa1['resaType'] = libelle($resa1['type']);

        $resa2 = mrbsGetEntryInfo($idAlt);
        $resa2['resaId'] = $idAlt;
        $resa2['resaDebut'] = time_date_string($resa2['start_time'],$dformat);
        $resa2['resaFin'] = time_date_string($resa2['end_time'],$dformat);
        $resa2['resaRessource'] = roomDesc($resa2['room_id']);
        $resa2['resaType'] = libelle($resa2['type']);
    }
}
else { // Etape 1: on connaît $id de la réservation à échanger, on va en chercher une autre pour l'échange
    $back = page_accueil();
    if ($resa1 = mrbsGetEntryInfo($id))
    {
        $back = (isset($_SERVER['HTTP_REFERER']))? htmlspecialchars_decode($_SERVER['HTTP_REFERER'], ENT_QUOTES) : page_accueil() ;
        $day   = date("d", $resa1["start_time"]);
        $month = date("m", $resa1["start_time"]);
        $year  = date("Y", $resa1["start_time"]);
        $area  = mrbsGetRoomArea($resa1["room_id"]);
        $beneficiaire = $resa1['beneficiaire'];
        // on commence par vérifier les droits d'accès
        if (authGetUserLevel($current_user, -1) < 1)
        {
            showAccessDenied($back);
            exit();
        }
        if (!getWritable($beneficiaire, $id))
        {
            showAccessDenied($back);
            exit;
        }
        if (authUserAccesArea($current_user, $area) == 0)
        {
            showAccessDenied($back);
            exit();
        }
        // est-il encore temps, ou l'utilisateur peut-il modifier la réservation ?
        $room_id = $resa1["room_id"];
        $date_now = time();
        get_planning_area_values($area);
        if ((!(verif_booking_date($current_user, $id, $room_id, -1, $date_now, $enable_periods))) || ((verif_booking_date($current_user, $id, $room_id, -1, $date_now, $enable_periods)) && ($can_delete_or_create != "y")))
        {
            showAccessDenied($back);
            exit();
        }
        // définit l'adresse de retour, à passer à swap_entry et à cancel
        $room_back = isset($_GET['room_back'])? $_GET['room_back'] : $room_id ;
        $_SESSION['ret_page'] = "app.php?p=".$page."&amp;year=".$year."&amp;month=".$month."&amp;day=".$day."&amp;area=".$area;
        if ((!strpos($page,"all"))&&($room_back != 'all')){
           $_SESSION['ret_page'] .= "&amp;room=".$room_back;
        }
        // recherche les réservations qui ont les mêmes heures de début et de fin
        $sql = "SELECT id FROM ".TABLE_PREFIX."_entry WHERE (start_time = '".$resa1['start_time']."' AND end_time = '".$resa1['end_time']."' AND id != '".$id."' AND supprimer = 0)";
        $reps = grr_sql_query($sql);
        if (!$reps)
            grr_sql_error($reps);
        // déterminer quelles réservations sont modifiables par l'utilisateur
        // on parcourt les résultats de la requête
        foreach($reps as $a){
            $resaPossible = mrbsGetEntryInfo($a['id']);
            $who_can_book = grr_sql_query1("SELECT who_can_book FROM ".TABLE_PREFIX."_room WHERE id='".$resaPossible['room_id']."' ");
            $user_can_book = $who_can_book || (authBooking($current_user,$resaPossible['room_id']));
            if (verif_acces_ressource($current_user,$resaPossible['room_id']) 
                && verif_acces_fiche_reservation($current_user,$resaPossible['room_id']) 
                && $user_can_book
                && UserRoomMaxBooking($current_user,$resaPossible['room_id'],1)){ // si l'utilisateur peut accéder à la ressource et la modifier, on l'affiche

                    $resaPossible['resaId'] = $a['id'];
                    $resaPossible['resaDebut'] = time_date_string($resaPossible['start_time'],$dformat);
                    $resaPossible['resaFin'] = time_date_string($resaPossible['end_time'],$dformat);
                    $resaPossible['resaRessource'] = roomDesc($resaPossible['room_id']);
                    $resaPossible['resaType'] = libelle($resaPossible['type']);

                    $resaDispo[] = $resaPossible;
                }
        }

        $resa1['resaId'] = $id;
        $resa1['resaDebut'] = time_date_string($resa1['start_time'],$dformat);
        $resa1['resaFin'] = time_date_string($resa1['end_time'],$dformat);
        $resa1['resaRessource'] = roomDesc($resa1['room_id']);
        $resa1['resaType'] = libelle($resa1['type']);

        $d['etape'] = 1;
    }
    else 
        showAccessDenied(page_accueil()); // l'utilisateur ne peut accéder à cette réservation... on le renvoie vers la page d'accueil
}

if(isset($_SESSION['ret_page']))
    $d['ret_page'] = $_SESSION['ret_page'];

echo $twig->render('echangeresa.twig', array('trad' => $trad, 'd' => $d, 'settings' => $AllSettings, 'resa1' => $resa1, 'resa2' => $resa2, 'resaDispo' => $resaDispo));
?>