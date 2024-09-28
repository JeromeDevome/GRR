<?php
/**
 * creationcompte.php
 * Formulaire d'envoi de mail
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2023-02-23 19:30$
 * @author    JeromeB
 * @copyright Copyright 2003-2023 Team DEVOME - JeromeB
 * @link      http://www.gnu.org/licenses/licenses.html
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */

include_once('include/pages.class.php');

$grr_script_name = "creationcompte.php";


if (!Pages::load())
	die('Erreur chargement pages');

$trad = $vocab;
$d['caractMini'] = $pass_leng." caractères minimum"; // $pass_leng est définit dans language.inc.php

/*  */
use Gregwar\Captcha\PhraseBuilder;

if( Settings::get("fct_crea_cpt") == "y" && isset($_POST["nom"])){

	/// Init des variables
	$reg_nom = isset($_POST["nom"]) ? $_POST["nom"] : NULL;
	$reg_prenom = isset($_POST["prenom"]) ? $_POST["prenom"] : NULL;
	$reg_mdp1 = isset($_POST["mdp1"]) ? unslashes($_POST["mdp1"]) : NULL;
	$reg_mdp2 = isset($_POST["mdp2"]) ? unslashes($_POST["mdp2"]) : NULL;
	$reg_email = isset($_POST["email"]) ? $_POST["email"] : NULL;
	$reg_telephone = isset($_POST["telephone"]) ? $_POST["telephone"] : NULL;
	$reg_commentaire = isset($_POST["commentaire"]) ? $_POST["commentaire"] : "";

	$erreur = false;
	$d['msgErreur'] = "";
	$msg_erreurIncomplet = "";


	if (empty($reg_nom))
		$msg_erreurIncomplet .= "Votre nom<br/>";
	if (empty($reg_prenom))
		$msg_erreurIncomplet .= "Votre prénom<br/>";
	if (empty($reg_email))
		$msg_erreurIncomplet .= "Votre adresse email<br/>";
	if (empty($reg_mdp1) || empty($reg_mdp2))
		$msg_erreurIncomplet .= "Le mot de passe<br/>";

	if(Settings::get("fct_crea_cpt_captcha") == 'y')
	{
		// Checking that the posted captcha match the captcha stored in the session
		if (isset($_SESSION['phrase']) && PhraseBuilder::comparePhrases($_SESSION['phrase'], $_POST['captcha'])) {
			// Le captcha est bon
		} else {
			$d['msgErreur'] .= $vocab["captcha_incorrect"]."<br/>";
			$erreur = true;
		}
		// The captcha can't be used twice
		unset($_SESSION['phrase']);
	}

	if($msg_erreurIncomplet != ""){
		$erreur = true;
		$d['msgErreur'] .= "Erreur. Les champs suivants doivent être obligatoirement remplis :<br/><br/>".$msg_erreurIncomplet;
	}

	if($reg_mdp1 != $reg_mdp2){
		$erreur = true;
		$d['msgErreur'] .= "Les mots de passe sont différents.<br/><br/>";
	}

	if (check_password_difficult($reg_mdp1) == false)
	{
		$erreur = true;
		$d['msgErreur'] .= get_vocab('mdp_taille').$pass_leng.".<br/><br/>";
	}

	if($erreur == false){

		// Peut-être y ajouter un contrôle si une demande est déjà en attente avec l'adesse mail

		$reg_mdp1 = password_hash($reg_mdp1, PASSWORD_DEFAULT);
		$expediteur = Settings::get("webmaster_email");

		$sql = "INSERT INTO ".TABLE_PREFIX."_utilisateurs_demandes SET
		nom='".protect_data_sql($reg_nom)."',
		prenom='".protect_data_sql($reg_prenom)."',
		email='".protect_data_sql($reg_email)."',
		telephone='".protect_data_sql($reg_telephone)."',
		mdp='".$reg_mdp1."',
		commentaire='".protect_data_sql($reg_commentaire)."',
		datedemande='".date("Ymd")."'";

		if (grr_sql_command($sql) < 0)
		{
			fatal_error(0, get_vocab("msg_login_created_error") . grr_sql_error());
		}

		require_once 'vendor/phpmailer/phpmailer/src/PHPMailer.php';
		require_once 'vendor/phpmailer/phpmailer/src/SMTP.php';
		require_once 'vendor/phpmailer/phpmailer/src/Exception.php';
		require_once 'include/mail.class.php';


		//Infos générales
		$codes = [
			'%nomdusite%' => Settings::get('title_home_page'),
			'%nometablissement%' => Settings::get('company'),
			'%urlgrr%' =>  traite_grr_url("","y"),
			'%webmasteremail%' => Settings::get("webmaster_email"),
			'%formnom%' => $reg_nom,
			'%formprenom%' => $reg_prenom,
			'%formemail%' => $reg_email,
			'%formtelephone%' => $reg_telephone,
			'%formcommentaire%' => $reg_commentaire
		];

		$templateMail1 = Pages::get('mails_demandecompte_'.$locale);
		$sujetEncode1 = str_replace(array_keys($codes), $codes, $templateMail1[0]);
		$msgEncode1 = str_replace(array_keys($codes), $codes, $templateMail1[1]);

		// Mail au demandeur
		Email::Envois($_POST['email'], $sujetEncode1, $msgEncode1, $_POST['email'], '', '');

		// Mail au gestionnaire user si il y en a
		$sql = "SELECT email FROM ".TABLE_PREFIX."_utilisateurs WHERE statut ='gestionnaire_utilisateur'";
		$res = grr_sql_query($sql);
		$nombre = grr_sql_count($res);
		if ($nombre > 0)
		{
			$destinataire = "";
			$tab_destinataire = array();
			for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
			{
				if ($row[0] != "")
					$tab_destinataire[] = $row[0];
			}
			foreach ($tab_destinataire as $value){
				$destinataire .= $value.";";
			}
			Email::Envois($destinataire, $sujetEncode1, $msgEncode1, $expediteur, '', '', $expediteur);
		} 
		else // si pas de gestionnaire d'utilisateur, aux admins
		{
			$sql = "SELECT email FROM ".TABLE_PREFIX."_utilisateurs WHERE statut ='administrateur'";
			$res = grr_sql_query($sql);
			$nombre = grr_sql_count($res);
			if ($nombre > 0)
			{
				$destinataire = "";
				$tab_destinataire = array();
				for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
				{
					if ($row[0] != "")
						$tab_destinataire[] = $row[0];
				}
				foreach ($tab_destinataire as $value){
					$destinataire .= $value.";";
				}
				Email::Envois($destinataire, $sujetEncode1, $msgEncode1, '', '', $expediteur);
			} 
		}




		
		$d['msgOk'] = "Votre demande a bien été prise en compte. Vous recevrez un mail après que votre demande soit traitée.";

	}
}

if( Settings::get("fct_crea_cpt") == "y")
	echo $twig->render('creationcompte.twig', array('trad' => $trad, 'd' => $d, 'settings' => $AllSettings));
else
	echo "<h3>Erreur : Fonction non active</h3>"
?>	
