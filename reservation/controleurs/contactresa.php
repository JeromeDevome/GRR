<?php
/**
 * contactresa.php
 * Formulaire de demande de réservation
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2026-01-31 11:36$
 * @author    JeromeB & Yan Naessens
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

$grr_script_name = "contactresa.php";

include_once('include/pages.class.php');

$msg_erreur = "Erreur. Les champs suivants doivent être obligatoirement
remplis :<br/><br/>";
$message = "";


if (!Pages::load())
	die('Erreur chargement pages');

$infosPage = Pages::get("contactresa");
$d['TitrePage'] = $infosPage[0];
$d['CtnPage'] =  $infosPage[1];

/*  */
use Gregwar\Captcha\PhraseBuilder;

// Traitement du formulaire
if(isset($_POST['nom'])){ 

	require_once 'include/mail.class.php';

	if (empty($_POST['nom']))
		$message .= "Votre nom<br/>";
	if (empty($_POST['prenom']))
		$message .= "Votre prénom<br/>";
	if (empty($_POST['email']))
		$message .= "Votre adresse email<br/>";
	if (empty($_POST['sujet']))
		$message .= "Le sujet de votre demande<br/>";
	if (empty($_POST['area']))
		$message .= "Le domaine n'est pas rempli<br/>";
	if (empty($_POST['room']))
		$message .= "Aucune salle de choisie<br/>";
	if (empty($_POST['start_day']))
		$message .= "Aucun jour choisi <br/>";
	if (empty($_POST['start_month']))
		$message .= "Aucun mois choisi <br/>";
	if (empty($_POST['start_year']))
		$message .= "Aucune année choisie <br/>";
	if (empty($_POST['duree']))
		$message .= "Aucune durée choisie <br/>";
	if(Settings::get("mail_contact_resa_captcha") == 'y')
	{
		// Checking that the posted captcha match the captcha stored in the session
		if (isset($_SESSION['phrase']) && PhraseBuilder::comparePhrases($_SESSION['phrase'], $_POST['captcha'])) {
			// Le captcha est bon
		} else {
			$message .= $vocab["captcha_incorrect"]."<br/>";
		}
		// The captcha can't be used twice
		unset($_SESSION['phrase']);
	}

	foreach ($_POST as $index => $valeur)
		$index = stripslashes(trim($valeur));

	if($message == "")
	{

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

		$id = intval($_POST['area']);
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

		//header('Location: week_all.php');
		$d['msgOk'] = "Votre demande a bien été envoyée";

	}
	else
	{
		$d['msgErreur'] = $msg_erreur.$message;
	}
}


if($userConnecte == "with_session"){
	$sql_user = "SELECT nom,prenom,email FROM ".TABLE_PREFIX."_utilisateurs WHERE login='".getUserName()."'";
	$sql_user = grr_sql_query($sql_user);

	$sql_user = grr_sql_row( $sql_user, 0 );  
	grr_sql_free( $sql_user );
	$d['prenomConnecte'] = $sql_user[0];
	$d['nomConnecte'] = $sql_user[1];
	$d['emailConnecte'] = $sql_user[2];
}

$domaineDispo = array();

$sql_areaName = "SELECT id, area_name,resolution_area FROM ".TABLE_PREFIX."_area ORDER BY area_name";
$res_areaName = grr_sql_query($sql_areaName);
foreach($res_areaName as $row)
{
	if (authUserAccesArea(getUserName(),$row['id']) == 1)
	{
		$domaineDispo[] = array('id' => $row['id'], 'nom' => $row['area_name'], 'resolution' => $row['resolution_area']);
	}
}

$d['jQuery_DatePicker'] = jQuery_DatePickerTwig('start_');


echo $twig->render('contactresa.twig', array('trad' => $trad, 'd' => $d, 'settings' => $AllSettings, 'domaineDispo' => $domaineDispo));
?>