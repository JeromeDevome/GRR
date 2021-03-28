<?php
/*
 * traitementcontact.php
 * envoie l'email suite au formulaire
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2021-03-16 09:56$
 * @author    Laurent Delineau & JeromeB & Yan Naessens
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
$grr_script_name = "traitementcontact.php";

include "include/connect.inc.php";
include "include/config.inc.php";
include "include/functions.inc.php";
include "include/misc.inc.php";
include "include/$dbsys.inc.php";
include "include/mrbs_sql.inc.php";
include "include/language.inc.php";
include "phpmailer/class.phpmailer.php";

// Settings
require_once("./include/settings.class.php");
if (!Settings::load())
	die("Erreur chargement settings");

// contrôle d'accès 
if (!acces_formulaire_reservation()){
    start_page_w_header('','','','no_session');
    showAccessDenied(page_accueil());
    die();
}
// vérification des paramètres
$msg_erreur = "Erreur. Les champs suivants doivent être obligatoirement remplis : \\n  \\n  ";
$msg_ok = "Votre demande a bien été prise en compte.";
$message = "";
if (empty($_POST['nom']))
	$message .= "Votre nom";
//if (empty($_POST['prenom']))
//	$message .= "Votre prénom  \\n  ";
if (empty($_POST['email']))
	$message .= "Votre adresse email  \\n  ";
if (!validate_email($_POST['email']))
	$message .= "Adresse email non valide<br />";
if (empty($_POST['sujet']))
	$message .= "Le sujet de votre demande  \\n  ";
if (empty($_POST['area']))
	$message .= "Le domaine \\n  ";
if (empty($_POST['room']))
	$message .= "La ressource  \\n  ";
if (empty($_POST['start_day']))
	$message .= "Le jour  \\n  ";
if (empty($_POST['start_month']))
	$message .= "Le mois  \\n  ";
if (empty($_POST['start_year']))
	$message .= "L'année  \\n  ";
if (!(isset($_POST['heure']) && isset($_POST['minutes'])) && (!isset($_POST['start'])))
    $message .= "L'heure de début \\n";
if (empty($_POST['duree']) && empty($_POST['dureemin']))
	$message .= "La durée  \\n  ";
//$message .="OK?";
if ($message !='')
    $message = $msg_erreur.$message;
else {// les paramètres sont vérifiés, le créneau demandé est-il libre ?
    $input = array_map('clean_input',$_POST);
    if (isset($_POST['heure']) && isset($_POST['minutes'])){// plage basée sur le temps
        $starttime = mktime($input['heure'],$input['minutes'],0,$input['start_month'],$input['start_day'],$input['start_year']);
        $endtime = $starttime + $input['duree']*3600 + $input['dureemin']*60;
    }
    elseif (isset($_POST['start'])){// plage basée sur des créneaux
        $starttime = mktime(12,$input['start'],0,$input['start_month'],$input['start_day'],$input['start_year']);
        $endtime = $starttime + $input['dureemin']*60;
    }
    else 
        fatal_error(0,"ne devrait pas être atteint");
    //echo $input['room'],'/',strftime("%c",$starttime),'/',strftime("%c",$endtime);
    $plage_libre = mrbsCheckFree($input['room'],$starttime,$endtime,0,0);
    //echo '<br>'.$plage_libre ;
    if ($plage_libre == ""){// la plage est libre, on pose une réservation modérée et on envoie un courrier
        $benef_ext = concat_nom_email($input['nom'].' '.$input['prenom'],$input['email']);
        $entry_id = mrbsCreateSingleEntry($starttime, $endtime, -1, 0, $input['room'], '', '', $benef_ext, $input['nom'].' '.$input['prenom'], 'A', $input['sujet'], -1,array(), 1, 0, '-', 0, 0);
        if ($entry_id != 0){ // l'insertion a réussi
            $message = "réservation posée sous réserve";
            // on envoie un message pour avertir de la demande
            $DE = $input['email']; // a été filtrée

            $mail_corps  = "<html><head></head><body> Message de :" .$input['prenom']." " .$input['nom'] . "<br/>";
            $mail_corps  .= "Email : ".$input['email']. "<br/>";
            $mail_corps  .= "Téléphone : ".$input['telephone']. "<br/><br/>";
            $mail_corps  .= "<b> Sujet de la réservation :".$input['sujet']. "</b><br/><br/>";

            $id = $input['area'] ;
            $sql_areaName = "SELECT area_name FROM ".TABLE_PREFIX."_area where id = \"$id\" ";
            $res_areaName = grr_sql_query1($sql_areaName);
            $mail_corps  .= "Domaine : ".$res_areaName. "<br/> ";
            $mail_corps  .= "Salle : ".$input['room']. "<br/><br/>";
            $mail_corps  .= "Date  :".$input['start_day']."/".$input['start_month']."/".$input['start_year']. " <br/>";
            if (isset($_POST['heure']) && isset($_POST['minutes'])){// plage basée sur le temps
                $mail_corps  .= "Heure réservation  : ".$input['heure']. "h  ".$input['minutes']. "min<br/>";
                $mail_corps  .= "Durée de la réservation : ".$input['duree'];
                $mail_corps  .= " h ".$input['dureemin']. " min \n";
            }
            elseif (isset($_POST['start'])){// plage basée sur des créneaux
                $periods_name = array();
                $pnres = grr_sql_query("SELECT nom_periode FROM ".TABLE_PREFIX."_area_periodes WHERE id_area='".$input['area']."' ORDER BY num_periode ASC");
                if ($pnres){
                    $i = 0; 
                    while (($a = grr_sql_row($pnres, $i++))) 
                    { $periods_name[$i] = $a[0];}
                }
                //print_r($periods_name);
                $start_period = isset($periods_name[$input['start']])? $periods_name[$input['start']] :"";
                $mail_corps .= "Premier créneau : ". $start_period." \n";
                $mail_corps .= "Durée de la réservation : ".$input['dureemin']." créneau(x) \n";
            }
            // lien de validation, cf functions.inc.php, ligne 2964
            $mail_corps .= $vocab["subject_a_moderer"];
			$mail_corps .= "\n".traite_grr_url("","y")."validation.php?id=".$entry_id;
            $mail_corps .= "</body></html>";
            $sujet ="Réservation d'une salle";
            $destinataire = "";
            $tab_emails = find_active_user_room($_POST['room']);
            foreach ($tab_emails as $value)
            {
                $destinataire .= $value.";";
            }
            require_once 'phpmailer/PHPMailerAutoload.php';
            require_once 'include/mail.class.php';

            Email::Envois($destinataire, $sujet, $mail_corps, $DE, '', '');
        }
        else
            $message = "échec de la réservation";
    }
    else
        $message = "au moins une partie de la plage demandée est occupée";
}
// une page toute simple pour revenir au planning si on a suivi le chemin normal
echo '<!DOCTYPE html>
<html><body>
<script>
    alert("'.$message.'");
    window.history.go(-2);
</script>
</body></html>';
die();
?>