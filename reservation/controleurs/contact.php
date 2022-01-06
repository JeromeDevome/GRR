<?php
/**
 * contact.php
 * Formulaire d'envoi de mail
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2018-07-30 18:30$
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
$grr_script_name = 'contact.php';

$type_session = "no_session";


$cible = isset($_POST["cible"]) ? $_POST["cible"] : (isset($_GET["cible"]) ? $_GET["cible"] : '');
$d['cible'] = htmlentities($cible);
$type_cible = isset($_POST["type_cible"]) ? $_POST["type_cible"] : (isset($_GET["type_cible"]) ? $_GET["type_cible"] : '');
if ($type_cible != 'identifiant:non')
	$type_cible = '';
$d['type_cible'] = $type_cible;
$action = isset($_POST["action"]) ? $_POST["action"] : '';
$corps_message = isset($_POST["message"]) ? $_POST["message"] : '';
$email_reponse = isset($_POST["email_reponse"]) ? $_POST["email_reponse"] : '';
$error_subject = 'n';
if (isset($_POST["objet_message"]))
{
	$objet_message = trim($_POST["objet_message"]);
	if ($objet_message == '')
		$error_subject = 'y';
}
$casier = isset($_POST["casier"]) ? $_POST["casier"] : '';
if ($error_subject == 'y')
	$action='';

$d['action'] = $action;

get_vocab_admin("Envoi_d_un_courriel");

get_vocab_admin("Message_poste_par");
get_vocab_admin("deux_points");
get_vocab_admin("webmaster_name");
get_vocab_admin("company");
get_vocab_admin("Email_pour_la_reponse");
get_vocab_admin("Objet_du_message");
get_vocab_admin("submit");

if($action == "envoi") //envoi du message
{

	$destinataire = "";
	if ($type_cible == "identifiant:non")
	{
		if ($cible == "contact_administrateur")
			$destinataire = Settings::get("webmaster_email");
		else if ($cible == "contact_support")
			$destinataire = Settings::get("technical_support_email");
	}
	else
	{
		$destinataire = grr_sql_query1("SELECT email FROM ".TABLE_PREFIX."_utilisateurs WHERE login = '".protect_data_sql($cible)."'");
		if ($destinataire == -1)
			$destinataire = "";
	}
	if ($destinataire == "")
	{
		$d['resultat'] = "L'envoi de messages est impossible car l'adresse email du destinataire n'a pas été renseignée par l'administrateur.";
	}
	else
	{
		//N.B. pour peaufiner, mettre un script de vérification de l'adresse email et du contenu du message !
		$message = "";
		if (getUserName()!='')
		{
			$message .= "Nom et prénom du demandeur : ".affiche_nom_prenom_email(getUserName(),"","nomail")."\n";
			$user_email = grr_sql_query1("select email from ".TABLE_PREFIX."_utilisateurs where login='".getUserName()."'");
			if (($user_email != "") && ($user_email != -1))
				$message .= "Email du demandeur : ".$user_email."\n";
			$message .= $vocab["statut"].preg_replace("/ /", " ",$vocab["deux_points"]).$_SESSION['statut']."\n";
		}
		$message .= $vocab["company"].preg_replace("/ /", " ",$vocab["deux_points"]).removeMailUnicode(Settings::get("company"))."\n";
		$message .= $vocab["email"].preg_replace("/ /", " ",$vocab["deux_points"]).$email_reponse."\n";
		$message.="\n".$corps_message."\n";
		$sujet = $vocab["subject_mail1"]." - ".$objet_message;

		require_once 'vendor/phpmailer/phpmailer/src/PHPMailer.php';
		require_once 'vendor/phpmailer/phpmailer/src/SMTP.php';
		require_once 'vendor/phpmailer/phpmailer/src/Exception.php';
		require_once 'include/mail.class.php';

		$destinataire = Settings::get("webmaster_email");
		Email::Envois($destinataire, $sujet, $message, $email_reponse, '', '');

		$d['resultat'] = "Votre message a été envoyé !";
	}
}
else
{

	if (getUserName() != ''){
		$d['UserNomPrenomEmail'] = affiche_nom_prenom_email(getUserName(), "", $type = "nomail");

		$user_email = grr_sql_query1("SELECT email FROM ".TABLE_PREFIX."_utilisateurs WHERE login='".getUserName()."'");
		if (($user_email != "") && ($user_email != -1))
			$d['MailReponse'] = $user_email;
	}

}

echo $twig->render('contact.twig', array('trad' => $trad, 'd' => $d, 'settings' => $AllSettings));
?>