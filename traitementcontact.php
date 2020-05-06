<?php
/*
 * traitementcontact.php
 * envoie l'email suite au formulaire
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2020-05-06 18:41$
 * @author    Laurent Delineau & JeromeB & Yan Naessens
 * @copyright Copyright 2003-2020 Team DEVOME - JeromeB
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
// cette page doit être internationalisée
include "include/connect.inc.php";
include "include/config.inc.php";
include "include/functions.inc.php";
include "include/misc.inc.php";
include "include/$dbsys.inc.php";
include "include/mrbs_sql.inc.php";
include "include/language.inc.php";
include "phpmailer/class.phpmailer.php";
//print_r($_POST);
// Settings
require_once("./include/settings.class.php");
if (!Settings::load())
	die("Erreur chargement settings");
// $link = page_accueil();
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
if( !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL) )
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
    echo $input['room'],'/',strftime("%c",$starttime),'/',strftime("%c",$endtime);
    $plage_libre = mrbsCheckFree($input['room'],$starttime,$endtime,0,0);
    echo '<br>'.$plage_libre ;
    if ($plage_libre == ""){// la plage est libre, on pose une réservation modérée et on envoie un courrier
        $entry_id = mrbsCreateSingleEntry($starttime, $endtime, 0, 0, $input['room'], '', '', $input['nom'].' '.$input['prenom'], $input['nom'].' '.$input['prenom'], 'A', $input['sujet'], -1,array(), 1, 0, '-', 0, 0);
        if ($entry_id != 0){ // l'insertion a réussi
            $message = "réservation posée sous réserve";
            // on envoie un message pour averir de la demande
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
                print_r($periods_name);
                $start_period = isset($periods_name[$input['start']])? $periods_name[$input['start']] :"";
                $mail_corps .= "Premier créneau : ". $start_period." \n";
                $mail_corps .= "Durée de la réservation : ".$input['dureemin']." créneau(x) \n";
            }
            // lien de validation, cf functions.inc.php, ligne 2964
            $mail_corps .= $vocab["subject_a_moderer"];
			$mail_corps .= "\n".traite_grr_url("","y")."validation.php?id=".$entry_id;
            $mail_corps .= "</body></html>";
            $sujet ="Réservation d'une salle";
            $destinataire = Settings::get("mail_destinataire");

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

echo '<!DOCTYPE html>
<html><body>
<script>
    alert("'.$message.'");
    window.history.go(-2);
</script>
</body></html>';
die();

// recherche si la plage demandée est libre
$room_id = protect_data_sql($_POST['room']);
$starttime = mktime($_POST['heure'],$_POST['minutes'],0,$_POST['start_month'],$_POST['start_day'],$_POST['start_year']);
$endtime = $starttime + $_POST['duree']*3600 + $_POST['dureemin']*60;
$plage_libre = mrbsCheckFree($room_id,$starttime,$endtime,0,0);
if ($plage_libre != "") // la plage n'est pas libre
{
	// echo "la plage est au moins partiellement occupée";
    start_page_w_header('','','','no_session');
	echo "<script type=\"text/javascript\">";
	echo "<!--\n";
	echo " alert(\"la plage est au moins partiellement occupée\");";
    echo "window.location.assign('$link');";
	echo "//-->";
	echo "</script>";
    end_page();
	die();
}
else 
{	// la plage est libre, on préréserve le créneau sous forme d'une réservation à modérer, à compléter
	$id_resa = grr_sql_insert_id(); // récupère l'id de la résa juste créée -> mail au modérateur
	echo "plage libre";
	die();
}
// traitement des erreurs
$message = "ok?";
//if ($message != "")
//{
	$message = $msg_erreur.$message; 
	echo "<br />".$message;
	affiche_pop_up($message);
	//die();
//}

foreach ($_POST as $index => $valeur)
	$index = stripslashes(trim($valeur));
// $mail_entete n'est plus utilisé, phpmailer s'en charge
/* $mail_entete  = "MIME-Version: 1.0\r\n";
$mail_entete .= "From: {$_POST['nom']} "
."<{$_POST['email']}>\r\n";
$mail_entete .= 'Reply-To: '.$_POST['email']."\r\n";
$mail_entete .= 'Content-Type: text/plain; charset="iso-8859-1"';
$mail_entete .= "\r\nContent-Transfer-Encoding: 8bit\r\n";
$mail_entete .= 'X-Mailer:PHP/' . phpversion()."\r\n"; */

$DE = $_POST['email']; // a été filtrée

$mail_corps  = "<html><head></head><body> Message de :" .$_POST['prenom']." " .$_POST['nom'] . "<br/>";
$mail_corps  .= "Email : ".$_POST['email']. "<br/>";
$mail_corps  .= "Téléphone : ".$_POST['telephone']. "<br/><br/>";
$mail_corps  .= "<b> Sujet de la réservation :".$_POST['sujet']. "</b><br/><br/>";

$id = $_POST['area'] ;
$sql_areaName = "SELECT area_name FROM ".TABLE_PREFIX."_area where id = \"$id\" ";
$res_areaName = grr_sql_query1($sql_areaName);
$mail_corps  .= "Domaine : ".$res_areaName. "<br/> ";
$mail_corps  .= "Salle : ".$_POST['room']. "<br/><br/>";
$mail_corps  .= "Date  :".$_POST['start_day']."/".$_POST['start_month']."/".$_POST['start_year']. " <br/>";
$mail_corps  .= "Heure réservation  : ".$_POST['heure']. "h  ".$_POST['minutes']. "min<br/>";
$mail_corps  .= "Durée de la réservation : ".$_POST['duree'];
$mail_corps  .= " h ".$_POST['dureemin']. " min \n";
// ici insérer un lien de validation, cf functions.inc.php, ligne 2964
if (isset($id_resa)){
	$mail_corps .="";
}
$mail_corps .= "</body></html>";
$sujet ="Réservation d'une salle";
$destinataire = Settings::get("mail_destinataire");

require_once 'phpmailer/PHPMailerAutoload.php';
require_once 'include/mail.class.php';

Email::Envois($destinataire, $sujet, $mail_corps, $DE, '', '');

// retour vers la page d'accueil
header('Location: '.$link);
?>