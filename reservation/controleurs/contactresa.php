<?php
/**
 * contactresa.php
 * Formulaire d'envoi de mail
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2020-04-27 15:40$
 * @author    JeromeB & Yan Naessens
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
$grr_script_name = "contactresa.php";
/*
include "./personnalisation/connect.inc.php";
include "./include/config.inc.php";
include "./include/misc.inc.php";
include "./include/functions.inc.php";
include "./include/$dbsys.inc.php";
include "./include/mincals.inc.php";
include "./include/mrbs_sql.inc.php";

require_once("./include/settings.class.php");
if (!Settings::load())
	die("Erreur chargement settings");
//require_once("./include/session.inc.php");
//include "./include/resume_session.php";
include "./include/language.inc.php";

// pour le traitement des modules
include "./include/hook.class.php";
*/
// code HTML
//header('Content-Type: text/html; charset=utf-8');
//if (!isset($_COOKIE['open']))
//{
//    setcookie("open", "true", time()+3600, "", "", false, false);
//}
// echo '<!DOCTYPE html>'.PHP_EOL;
// echo '<html lang="fr">'.PHP_EOL;
//section <head>
// echo pageHead2(Settings::get("company"),"no_session");
//section <body>
// echo "<body>";
//Menu du haut = section <header>
// echo "<header>";
// pageHeader2($day, $month, $year, "no_session");
// echo "</header>";
//Debut de la page
// echo '<section>'.PHP_EOL;
// bouton_retour_haut();

//include "phpmailer/class.phpmailer.php";

$msg_erreur = "Erreur. Les champs suivants doivent être obligatoirement
remplis :<br/><br/>";
$msg_ok = "Votre demande a bien été prise en compte.";
$message = $msg_erreur;


if(isset($_POST['nom'])){ 

	require_once 'vendor/phpmailer/phpmailer/src/PHPMailer.php';
	require_once 'vendor/phpmailer/phpmailer/src/SMTP.php';
	require_once 'vendor/phpmailer/phpmailer/src/Exception.php';
	require_once 'include/mail.class.php';

	if (empty($_POST['nom']))
		$message .= "Votre nom";
	if (empty($_POST['prenom']))
		$message .= "Votre prénom<br/>";
	if (empty($_POST['email']))
		$message .= "Votre adresse email<br/>";
	if (empty($_POST['subject']))
		$message .= "Le sujet de votre demande<br/>";
	if (empty($_POST['area']))
		$message .= "Le domaine n'est pas rempli<br/>";
	if (empty($_POST['room']))
		$message .= "Aucune salle de choisie<br/>";
	if (empty($_POST['jours']))
		$message .= "Aucune jours choisi <br/>";
	if (empty($_POST['mois']))
		$message .= "Aucune mois choisi <br/>";
	if (empty($_POST['année']))
		$message .= "Aucune année choisie <br/>";
	if (empty($_POST['duree']))
		$message .= "Aucune durée choisie <br/>";
	foreach ($_POST as $index => $valeur)
		$index = stripslashes(trim($valeur));


	$mail_entete  = "MIME-Version: 1.0\r\n";
	$mail_entete .= "From: {$_POST['nom']} "
	."<{$_POST['email']}>\r\n";
	$mail_entete .= 'Reply-To: '.$_POST['email']."\r\n";
	$mail_entete .= 'Content-Type: text/plain; charset="iso-8859-1"';
	$mail_entete .= "\r\nContent-Transfer-Encoding: 8bit\r\n";
	$mail_entete .= 'X-Mailer:PHP/' . phpversion()."\r\n";

	$mail_corps  = "<html><head></head><body> Message de :" .$_POST['prenom']." " .$_POST['nom'] . "<br/>";
	$mail_corps  .= "Email : ".$_POST['email']. "<br/>";
	$mail_corps  .= "Téléphone : ".$_POST['telephone']. "<br/><br/>";
	$mail_corps  .= "<b> Sujet de la réservation :".$_POST['sujet']. "</b><br/><br/>";

	$id = $_POST['area'] ;
	$sql_areaName = "SELECT area_name FROM ".TABLE_PREFIX."_area where id = \"$id\" ";
	$res_areaName = grr_sql_query1($sql_areaName);
	$mail_corps  .= "Domaines : ".$res_areaName. "<br/> ";
	$mail_corps  .= "Salle : ".$_POST['room']. "<br/><br/>";
	$mail_corps  .= "Date  :".$_POST['start_day']."/".$_POST['start_month']."/".$_POST['start_year']. " <br/>";
	$mail_corps  .= "Heure réservation  : ".$_POST['heure']. "h  ".$_POST['minutes']. "min<br/>";
	$mail_corps  .= "Durée de la réservation : ".$_POST['duree']. " \n";
	$mail_corps  .= " h ".$_POST['dureemin']. " \n</body></html>";

	$sujet ="Réservation d'une salle";
	$destinataire = Settings::get("mail_destinataire");

	Email::Envois($destinataire, $sujet, $mail_corps, $_POST['email'], '', '');
	if(Settings::get("mail_user_destinataire"))
		Email::Envois($_POST['email'], $sujet, $mail_corps, $_POST['email'], '', '');

	header('Location: week_all.php');
}


$domaineDispo = array();

$sql_areaName = "SELECT id, area_name,resolution_area FROM ".TABLE_PREFIX."_area ORDER BY area_name";
$res_areaName = grr_sql_query($sql_areaName);
for ($i = 0; ($row_areaName = grr_sql_row($res_areaName, $i)); $i++)
{
	if (authUserAccesArea(getUserName(),$row_areaName[0]) == 1)
	{
		$domaineDispo[] = array('id' => $row_areaName[0], 'nom' => $row_areaName[1], 'resolution' => $row_areaName[2]);
	}
}

$d['jQuery_DatePicker'] = jQuery_DatePickerTwig('start');



echo $twig->render('contactresa.twig', array('trad' => $trad, 'd' => $d, 'settings' => $AllSettings, 'domaineDispo' => $domaineDispo));
?>	
