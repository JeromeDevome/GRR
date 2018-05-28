<?php
/**
 * swap_entry.php
 * Interface d'échange d'une réservation avec une autre, à choisir
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2018-05-28 10:30$
 * @author    Laurent Delineau & JeromeB & Yan Naessens
 * @copyright Copyright 2003-2018 Team DEVOME - JeromeB
 * @link      http://www.gnu.org/licenses/licenses.html
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */
// cette page doit encore être internationalisée
$grr_script_name = "swap_entry.php";

include "include/connect.inc.php";
include "include/config.inc.php";
include "include/functions.inc.php";
include "include/$dbsys.inc.php";
include_once('include/misc.inc.php');
include "include/mrbs_sql.inc.php";
require_once("./include/settings.class.php");
if (!Settings::load())
	die("Erreur chargement settings");
require_once("./include/session.inc.php");
if (!grr_resumeSession())
{
	header("Location: ./logout.php?auto=1&url=$url");
	die();
};
include "include/language.inc.php";
$series = isset($_GET["series"]) ? $_GET["series"] : NULL;
if (isset($series))
	settype($series,"integer");
$page = verif_page();
if (isset($_GET["id"]))
{
	$id = $_GET["id"];
	settype($id,"integer");
}
else {
    header("Location: ./day.php");
	die();    
}
// début de code html, commun à tous les cas
begin_page("Echange de réservations", "with_session");
print_header('', '', '', 'with_session');

if (isset($_GET['id_alt'])){ // cas où tout est décidé
    if (isset($_GET['choix'])){
        //print_r($_GET);
        //echo "prêt à l'échange";
        //echo $_GET["ret_page"];
        // ici échanger
        $sql1 = "SELECT * FROM ".TABLE_PREFIX."_entry WHERE id=".$id;
        $res1 = grr_sql_query($sql1);
        if ($res1){
            $data1 = grr_sql_row($res1,0);
            //print_r($data1);
            $sql2 = "SELECT * FROM ".TABLE_PREFIX."_entry WHERE id=".$_GET['id_alt'];
            $res2 = grr_sql_query($sql2);
            if ($res2){
                $data2 = grr_sql_row($res2,0);
                //print_r($data2);
                $sql3 = " UPDATE ".TABLE_PREFIX."_entry SET ";
            /*    $sql3 .= "entry_type = '".$data1[3]."', ";
                $sql3 .= "repeat_id = '".$data1[4]."', "; */
                $sql3 .= "room_id = '".$data1[5]."' ";//"', ";
            /*    $sql3 .= "create_by = '".getUserName()."', ";
                $sql3 .= "beneficiaire_ext = '".$data1[8]."', ";
                $sql3 .= "beneficiaire = '".$data1[9]."', ";
                $sql3 .= "name = '".$data1[10]."', ";
                $sql3 .= "type = '".$data1[11]."', ";
                $sql3 .= "description = '".$data1[12]."', ";
                $sql3 .= "statut_entry = '".$data1[13]."', ";
                $sql3 .= "option_reservation = '".$data1[14]."', ";
                $sql3 .= "overload_desc = '".$data1[15]."', ";
                $sql3 .= "moderate = '".$data1[16]."', ";
                $sql3 .= "jours = '".$data1[17]."', ";
                $sql3 .= "clef = '".$data1[18]."', ";
                $sql3 .= "courrier = '".$data1[19]."' "; */
                $sql3 .= "WHERE id = ".$data2[0]; 
                $res3 = grr_sql_query($sql3);
                if ($res3){                    
                    $sql4 = " UPDATE ".TABLE_PREFIX."_entry SET ";
            /*        $sql4 .= "entry_type = '".$data2[3]."', ";
                    $sql4 .= "repeat_id = '".$data2[4]."', "; */
                    $sql4 .= "room_id = '".$data2[5]."' ";//"', ";
            /*        $sql4 .= "create_by = '".getUserName()."', ";
                    $sql4 .= "beneficiaire_ext = '".$data2[8]."', ";
                    $sql4 .= "beneficiaire = '".$data2[9]."', ";
                    $sql4 .= "name = '".$data2[10]."', ";
                    $sql4 .= "type = '".$data2[11]."', ";
                    $sql4 .= "description = '".$data2[12]."', ";
                    $sql4 .= "statut_entry = '".$data2[13]."', ";
                    $sql4 .= "option_reservation = '".$data2[14]."', ";
                    $sql4 .= "overload_desc = '".$data2[15]."', ";
                    $sql4 .= "moderate = '".$data2[16]."', ";
                    $sql4 .= "jours = '".$data2[17]."', ";
                    $sql4 .= "clef = '".$data2[18]."', ";
                    $sql4 .= "courrier = '".$data2[19]."' "; */
                    $sql4 .= "WHERE id = ".$data1[0]; 
                    $res4 = grr_sql_query($sql4);
                    if ($res4){
                        // echo "échange réalisé";
                        echo '<script type="text/javascript">';
                        echo 'alert("Echange effectué correctement");';
                        echo 'document.location.href="'.$_GET['ret_page'].'"';
                        echo '</script>';
                        die();
                    }
                }
            }
        }
        if (!$res1 || !$res2 || !$res3 || !$res4){
            echo grr_sql_error();
        }
    }
    else { // on demande confirmation
        $info = mrbsGetEntryInfo($id);
        $info_alt = mrbsGetEntryInfo($_GET['id_alt']);
        echo "<p><strong> Etes-vous sûr de vouloir échanger la réservation </strong></p>";
        //print_r($info);
        echo "<table class='table-bordered'>";
            echo "<tr>";
                echo "<th>Description</th>";
                echo "<th>Début</th>";
                echo "<th>Fin</th>";
                echo "<th>Ressource</th>";
                echo "<th>Bénéficiaire</th>";
                echo "<th>Type</th>";
            echo "</tr>";
            echo "<tr style='text-align:center;'>";
                echo "<td>".$info['description']."</td>";
                echo "<td>".time_date_string($info['start_time'],$dformat)."</td>";
                echo "<td>".time_date_string($info['end_time'],$dformat)."</td>";
                echo "<td>".roomDesc($info['room_id'])."</td>"; 
                echo "<td>".$info['beneficiaire']."</td>";
                echo "<td>".libelle($info['type'])."</td>";
            echo "</tr>";
        echo "</table>";
        echo "<p><strong> avec la réservation </strong></p>";
        // print_r($info_alt);
        echo "<table class='table-bordered'>";
            echo "<tr>";
                echo "<th>Description</th>";
                echo "<th>Début</th>";
                echo "<th>Fin</th>";
                echo "<th>Ressource</th>";
                echo "<th>Bénéficiaire</th>";
                echo "<th>Type</th>";
            echo "</tr>";
            echo "<tr style='text-align:center;'>";
                echo "<td>".$info_alt['description']."</td>";
                echo "<td>".time_date_string($info_alt['start_time'],$dformat)."</td>";
                echo "<td>".time_date_string($info_alt['end_time'],$dformat)."</td>";
                echo "<td>".roomDesc($info_alt['room_id'])."</td>"; 
                echo "<td>".$info_alt['beneficiaire']."</td>";
                echo "<td>".libelle($info_alt['type'])."</td>";
            echo "</tr>";
        echo "</table>";
        echo "</p>";
        // $link = "./swap_entry.php?id=".$id."&id_alt=".$_GET['id_alt']."&choix&ret_page=".$_GET['ret_page']; // ret_page ne passe pas à la suite => passer les détails ?
        // echo "<p style='text-align:center;'>";
        // echo "<input class='btn btn-primary' value='Valider' onclick='window.location.href=\"".$link."\"'/>";
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
        // echo "</p>";
    }
}
else { // on connaît $id de la réservation à échanger, on va en chercher une autre pour l'échange
    $back = "";
    if ($info = mrbsGetEntryInfo($id))
    {
        $day   = strftime("%d", $info["start_time"]);
        $month = strftime("%m", $info["start_time"]);
        $year  = strftime("%Y", $info["start_time"]);
        $area  = mrbsGetRoomArea($info["room_id"]);
        // on commence par vérifier les droits d'accès
        // $back = "";
        if (isset($_SERVER['HTTP_REFERER']))
            $back = htmlspecialchars($_SERVER['HTTP_REFERER']);
        if (authGetUserLevel(getUserName(), -1) < 1)
        {
            showAccessDenied($back);
            exit();
        }
        if (!getWritable($info["beneficiaire"], getUserName(), $id))
        {
            showAccessDenied($back);
            exit;
        }
        if (authUserAccesArea(getUserName(), $area) == 0)
        {
            showAccessDenied($back);
            exit();
        }
        // faut-il envoyer un mail automatique ?
        if (Settings::get("automatic_mail") == 'yes')
            $_SESSION['session_message_error'] = send_mail($id,3,$dformat);
        // est-il encore temps, ou l'utilisateur peut-il modifier la réservation ?
        $room_id = $info["room_id"];
        $date_now = time();
        get_planning_area_values($area);
        if ((!(verif_booking_date(getUserName(), $id, $room_id, -1, $date_now, $enable_periods))) || ((verif_booking_date(getUserName(), $id, $room_id, -1, $date_now, $enable_periods)) && ($can_delete_or_create != "y")))
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
        // echo $ret_page;
        // recherche les réservations qui ont les mêmes heures de début et de fin
        $sql = "SELECT id FROM ".TABLE_PREFIX."_entry WHERE (start_time = '".$info['start_time']."' AND end_time = '".$info['end_time']."' AND id != '".$id."')";
        $reps = grr_sql_query($sql);
        if (!$reps){grr_sql_error($reps);}
        // print_r($info);
        // libelle($info['type']);
        // echo $dformat."<hr />";
        // echo grr_sql_count($reps);
        echo "Dans le tableau ci-dessous, cochez la ligne correspondant à la réservation que vous voulez échanger avec la réservation courante<br/>";
        echo "puis Validez, ou Annulez pour revenir au planning.<br/>";
        // echo $ret_page;
        echo '<form method="GET" action="swap_entry.php" >';
        echo "<p style='text-align:center;'>";
        echo "<input type='hidden' name='ret_page' value='".$ret_page."' />";
        echo "<input type='hidden' name='id' value='".$id."' />";
        echo "<input class='btn btn-primary' type='submit' value='Valider' />";
        echo "<input type='button' class='btn btn-danger' value='".get_vocab("cancel")."' onclick='window.location.href=\" ".$ret_page."\"'/>";
        echo "</p>"; 
        // tableau donnant la réservation à échanger et celles avec lesquelles échanger
        echo "<table class='table-bordered'>";
            echo "<thead>";
                echo "<tr>";
                    echo "<th>Choisir</th>"; // colonne pour les choix
                    echo "<th>Description</th>";
                    echo "<th>Début</th>";
                    echo "<th>Fin</th>";
                    echo "<th>Ressource</th>";
                    echo "<th>Bénéficiaire</th>";
                    echo "<th>Type</th>";
                echo "</tr>";
                echo "<tr>";
                    echo "<th><span class='glyphicon glyphicon-arrow-down'></span></th>"; // colonne pour les choix
                    echo "<th>".$info['description']."</th>";
                    echo "<th>".time_date_string($info['start_time'],$dformat)."</th>";
                    echo "<th>".time_date_string($info['end_time'],$dformat)."</th>";
                    echo "<th>".roomDesc($info['room_id'])."</th>"; 
                    echo "<th>".$info['beneficiaire']."</th>";
                    echo "<th>".libelle($info['type'])."</th>";
                echo "</tr>";
            echo "</thead>";
           // echo "<form method='GET' action='swap_entry.php' >"; // le formulaire sera traité si une valeur est entrée
            echo "<tbody>";
            // on parcourt les résultats de la requête
                $i = 0; 
                while (($a = grr_sql_row($reps, $i++))) 
                { 
                    $id_alt = $a[0]; // id de la résa alternative
                    $info_alt = mrbsGetEntryInfo($id_alt);
                    $current_user = getUserName();
                    if (verif_acces_ressource($current_user,$info_alt['room_id']) && verif_acces_fiche_reservation($current_user,$info_alt['room_id']) && UserRoomMaxBooking($current_user,$info_alt['room_id'],1)){ // si l'utilisateur peut accéder à la ressource et la modifier, on l'affiche
                        echo "<tr style='text-align:center;'>";
                        echo "<td><input type='radio' name='id_alt' value=".$id_alt." /></td>"; // colonne pour les choix
                        echo "<td>".$info_alt['description']."</td>";
                        echo "<td>".time_date_string($info_alt['start_time'],$dformat)."</td>";
                        echo "<td>".time_date_string($info_alt['end_time'],$dformat)."</td>";
                        echo "<td>".roomDesc($info_alt['room_id'])."</td>";
                        echo "<td>".$info_alt['beneficiaire']."</td>";
                        echo "<td>".libelle($info_alt['type'])."</td>";
                        echo "</tr>";
                    }
                }
            echo "</tbody>";
            echo "</form>";
        echo "</table>";           
        // bas de page
        echo "</body>";
        echo "</html>";
    }
    else 
        showAccessDenied(page_accueil()); // l'utilisateur ne peut accéder à cette réservation... on le renvoie vers la page d'accueil
}
function libelle($type){ // rend la description du type_lettre de réservation
    $sql = "SELECT type_name FROM ".TABLE_PREFIX."_type_area WHERE type_letter ='".$type."' ";
    $res = grr_sql_query($sql);
    if ($res){
        return grr_sql_row($res,0)[0];
    }
    else 
        print(grr_sql_error($res));
}
function roomDesc($id_room){ // rend nom + description à partir de l'identifiant de la ressource
    $sql = "SELECT room_name,description FROM ".TABLE_PREFIX."_room WHERE id = '".$id_room."' ";
    $res = grr_sql_query($sql);
    if ($res){
        $data = grr_sql_row($res,0);
        return $data[0]." ".$data[1];
    }
    else 
        print(grr_sql_error($res));
}
?>
