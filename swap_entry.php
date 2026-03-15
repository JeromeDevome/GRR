<?php
/**
 * swap_entry.php
 * Interface d'échange d'une réservation avec une autre, à choisir
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2026-01-19 16:44$
 * @author    Laurent Delineau & JeromeB & Yan Naessens
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
 
$grr_script_name = "swap_entry.php";

include "include/connect.inc.php";
include "include/config.inc.php";
include "include/misc.inc.php";
include "include/$dbsys.inc.php";
include "include/mrbs_sql.inc.php";
include "include/functions.inc.php";
require_once("./include/settings.class.php");
if (!Settings::load())
	die("Erreur chargement settings");
require_once("./include/session.inc.php");
if (!grr_resumeSession())
{
	header("Location: ./logout.php?auto=1&url=$url");
	die();
};
$current_user = getUserName();
if ((Settings::get("authentification_obli") == 0) && ($current_user == ''))
	$type_session = "no_session";
else
	$type_session = "with_session";
require_once "./include/language.inc.php";
$series = isset($_GET["series"]) ? $_GET["series"] : NULL;
if (isset($series))
	$series = intval($series);
$page = verif_page();
if (isset($_GET["id"]))
	$id = intval($_GET["id"]);
else {
    header("Location: ./day.php");
	die();    
}
function libelle($type){ // rend la description du type_lettre de réservation
    $sql = "SELECT type_name FROM ".TABLE_PREFIX."_type_area WHERE type_letter =?";
    $res = grr_sql_query($sql,"s",[$type]);
    if ($res){
        return grr_sql_row($res,0)[0];
    }
    else 
        print(grr_sql_error($res));
    grr_sql_free($res);
}
function roomDesc($id_room){ // rend nom (description) à partir de l'identifiant de la ressource
    $sql = "SELECT room_name,description FROM ".TABLE_PREFIX."_room WHERE id =?";
    $res = grr_sql_query($sql,"i",[$id_room]);
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

if (isset($_GET['id_alt'])){ // les paramètres sont connus
    if (isset($_GET['choix'])){ // la demande d'échange est confirmée
        $sql1 = "SELECT * FROM ".TABLE_PREFIX."_entry WHERE id=?";
        $res1 = grr_sql_query($sql1,"i",[$id]);
        if ($res1){
            $data1 = grr_sql_row($res1,0);
            $res2 = grr_sql_query($sql1,"i",[$_GET['id_alt']]);
            if ($res2){
                $data2 = grr_sql_row($res2,0);
                $sql3 = " UPDATE ".TABLE_PREFIX."_entry SET room_id =? WHERE id =? "; 
                $res3 = grr_sql_command($sql3,"ii",[$data1[5],$data2[0]]);
                if ($res3 != -1){
                    $res4 = grr_sql_command($sql3,"ii",[$data2[5],$data1[0]]);
                    if ($res4 != -1){
                        $etape = 3; // échange réussi, envoyer un mail si programmé
                        if (Settings::get("automatic_mail") == 'yes'){
                            $_SESSION['session_message_error'] = send_mail($data1[0],2,$dformat,array(),$data1[5]);
                            $_SESSION['session_message_error'] = send_mail($data2[0],2,$dformat,array(),$data2[5]);
                        }
                    }
                }
            }
        }
        if (!$res1 || !$res2 || ($res3 == -1) || ($res4 == -1)){
            // fatal_error(0,grr_sql_error());
            $_SESSION['session_message_error'] = grr_sql_error();
        }
    }
    else { // on demande confirmation
        $etape = 2;
        $info = mrbsGetEntryInfo($id);
        $info_alt = mrbsGetEntryInfo($_GET['id_alt']);
    }
}
else { // on connaît $id de la réservation à échanger, on va en chercher une autre pour l'échange
    $back = page_accueil();
    if ($info = mrbsGetEntryInfo($id))
    {
        $back = (isset($_SERVER['HTTP_REFERER']))? htmlspecialchars_decode($_SERVER['HTTP_REFERER'], ENT_QUOTES) : page_accueil() ;
        $day   = date("d", $info["start_time"]);
        $month = date("m", $info["start_time"]);
        $year  = date("Y", $info["start_time"]);
        $area  = mrbsGetRoomArea($info["room_id"]);
        // on commence par vérifier les droits d'accès
        if (authGetUserLevel($current_user, -1) < 1)
        {
            showAccessDenied($back);
            exit();
        }
        if (!getWritable($current_user, $id))
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
        $room_id = $info["room_id"];
        $date_now = time();
        get_planning_area_values($area);
        if ((!(verif_booking_date($current_user, $id, $room_id, -1, $date_now, $enable_periods))) || ((verif_booking_date($current_user, $id, $room_id, -1, $date_now, $enable_periods)) && ($can_delete_or_create != "y")))
        {
            showAccessDenied($back);
            exit();
        }
        // définit l'adresse de retour, à passer à swap_entry et à cancel
        $room_back = isset($_GET['room_back'])? $_GET['room_back'] : $room_id ;
        $ret_page = $page.".php?year=".$year."&amp;month=".$month."&amp;day=".$day."&amp;area=".$area;
        if ((!strpos($page,"all"))&&($room_back != 'all')){
            $ret_page .= "&amp;room=".$room_back;
        }
        // recherche les réservations qui ont les mêmes heures de début et de fin
        $sql = "SELECT id FROM ".TABLE_PREFIX."_entry WHERE (start_time =? AND end_time =? AND id !=?)";
        $reps = grr_sql_query($sql,"iii",[$info['start_time'],$info['end_time'],$id]);
        if (!$reps)
            grr_sql_error($reps);
        // déterminer quelles réservations sont modifiables par l'utilisateur
        $resa_access = array();
        // on parcourt les résultats de la requête
        foreach($reps as $a){
            $info_alt = mrbsGetEntryInfo($a['id']);
            if (verif_acces_ressource($current_user,$info_alt['room_id']) && verif_acces_fiche_reservation($current_user,$info_alt['room_id']) && UserRoomMaxBooking($current_user,$info_alt['room_id'],1)) // si l'utilisateur peut accéder à la ressource et la modifier, on la garde
                $resa_access[$a['id']] = $info_alt;
        }
        grr_sql_free($reps);
        $etape = 1;
    }
    else 
        showAccessDenied(page_accueil()); // l'utilisateur ne peut accéder à cette réservation... on le renvoie vers la page d'accueil
}
// début de code html, commun à tous les cas
// pour le traitement des modules
$adm = 0;
$racine = "./";
$racineAd = "./admin/";
include $racine."/include/hook.class.php";
// code HTML
header('Content-Type: text/html; charset=utf-8');
if (!isset($_COOKIE['open']))
{
	header('Set-Cookie: open=true; SameSite=Strict');
}
echo '<!DOCTYPE html>'.PHP_EOL;
echo '<html lang="fr">'.PHP_EOL;
// section <head>
echo pageHead2(get_vocab('swap_entry'),$type_session);
// section <body>
echo "<body>";
// Menu du haut = section <header>
echo "<header>";
pageHeader2('', '', '', $type_session);
echo "</header>";
// Debut de la page
echo '<section>'.PHP_EOL;
if ($etape == 1){
    echo get_vocab('swap_entry_choose');
    echo '<form method="GET" action="swap_entry.php" >';
    echo "<p style='text-align:center;'>";
    echo "<input type='hidden' name='ret_page' value='".$ret_page."' />";
    echo "<input type='hidden' name='id' value='".$id."' />";
    echo "<input class='btn btn-primary' type='submit' value='".get_vocab('OK')."' />";
    echo "<input type='button' class='btn btn-danger' value='".get_vocab("cancel")."' onclick='window.location.href=\" ".$ret_page."\"'/>";
    echo "</p>"; 
    // tableau donnant la réservation à échanger et celles avec lesquelles échanger
    echo "<table class='table table-bordered'>";
        echo "<thead>";
            echo "<tr>";
                echo "<th>".get_vocab('Choose')."</th>"; // colonne pour les choix
                echo "<th>".get_vocab('description')."</th>";
                echo "<th>".get_vocab('date')."</th>";
                echo "<th>".get_vocab('fin_reservation')."</th>";
                echo "<th>".get_vocab('room')."</th>";
                echo "<th>".get_vocab('sum_by_creator')."</th>";
                echo "<th>".get_vocab('type')."</th>";
            echo "</tr>";
            echo "<tr>";
                echo "<th><span class='glyphicon glyphicon-arrow-down'></span></th>"; // colonne pour les choix
                echo "<th>".$info['name']."</th>";
                echo "<th>".time_date_string($info['start_time'],$dformat)."</th>";
                echo "<th>".time_date_string($info['end_time'],$dformat)."</th>";
                echo "<th>".roomDesc($info['room_id'])."</th>"; 
                echo "<th>".$info['beneficiaire']."</th>";
                echo "<th>".libelle($info['type'])."</th>";
            echo "</tr>";
        echo "</thead>";
        echo "<tbody>";
        // on parcourt le tableau des données à afficher
        foreach($resa_access as $k => $d){
            echo "<tr class='center'>";
            echo "<td><input type='radio' name='id_alt' value=".$k." /></td>"; // colonne pour les choix
            echo "<td>".$d['name']."</td>";
            echo "<td>".time_date_string($d['start_time'],$dformat)."</td>";
            echo "<td>".time_date_string($d['end_time'],$dformat)."</td>";
            echo "<td>".roomDesc($d['room_id'])."</td>";
            echo "<td>".$d['beneficiaire']."</td>";
            echo "<td>".libelle($d['type'])."</td>";
            echo "</tr>";
        }
        echo "</tbody>";
        echo "</table>";
    echo "</form>";
}
if ($etape == 2){
    echo "<p><strong>".get_vocab('swap_entry_confirm')."</strong></p>";
    echo "<table class='table table-bordered'>";
        echo "<tr>";
            echo "<th>".get_vocab('description')."</th>";
            echo "<th>".get_vocab('date')."</th>";
            echo "<th>".get_vocab('fin_reservation')."</th>";
            echo "<th>".get_vocab('room')."</th>";
            echo "<th>".get_vocab('sum_by_creator')."</th>";
            echo "<th>".get_vocab('type')."</th>";
        echo "</tr>";
        echo "<tr style='text-align:center;'>";
            echo "<td>".$info['name']."</td>";
            echo "<td>".time_date_string($info['start_time'],$dformat)."</td>";
            echo "<td>".time_date_string($info['end_time'],$dformat)."</td>";
            echo "<td>".roomDesc($info['room_id'])."</td>"; 
            echo "<td>".$info['beneficiaire']."</td>";
            echo "<td>".libelle($info['type'])."</td>";
        echo "</tr>";
    echo "</table>";
    echo "<p><strong>".get_vocab('swap_entry_confirm1')."</strong></p>";
    echo "<table class='table table-bordered'>";
        echo "<tr>";
            echo "<th>".get_vocab('description')."</th>";
            echo "<th>".get_vocab('date')."</th>";
            echo "<th>".get_vocab('fin_reservation')."</th>";
            echo "<th>".get_vocab('room')."</th>";
            echo "<th>".get_vocab('sum_by_creator')."</th>";
            echo "<th>".get_vocab('type')."</th>";
        echo "</tr>";
        echo "<tr style='text-align:center;'>";
            echo "<td>".$info_alt['name']."</td>";
            echo "<td>".time_date_string($info_alt['start_time'],$dformat)."</td>";
            echo "<td>".time_date_string($info_alt['end_time'],$dformat)."</td>";
            echo "<td>".roomDesc($info_alt['room_id'])."</td>"; 
            echo "<td>".$info_alt['beneficiaire']."</td>";
            echo "<td>".libelle($info_alt['type'])."</td>";
        echo "</tr>";
    echo "</table>";
    echo '<form method="GET" action="swap_entry.php" >';
    echo "<p style='text-align:center;'>";
    echo "<input type='hidden' name='ret_page' value='".$_GET['ret_page']."' />";
    echo "<input type='hidden' name='id' value='".$id."' />";
    echo "<input type='hidden' name='id_alt' value='".$_GET['id_alt']."' />";
    echo "<input type='hidden' name='choix' value='Valider' />";
    echo "<input class='btn btn-primary' type='submit' value='Confirmer' />";
    echo "<input type='button' class='btn btn-danger' value='".get_vocab("cancel")."' onclick='window.location.href=\" ".$_GET['ret_page']."\"'/>";
    echo "</p>";
    echo "</form>";
    end_page();
}
if($etape == 3){
    echo '<script type="text/javascript">';
    echo 'alert("Echange effectué correctement");';
    echo 'document.location.href="'.$_GET['ret_page'].'"';
    echo '</script>';
}
// bas de page
display_mail_msg();
end_page();
?>