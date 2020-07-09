<?php
/**
 * contact.php
 * Formulaire d'envoi de mail
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2020-07-09 18:40$
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
$grr_script_name = 'contact.php';
include_once('include/connect.inc.php');
include_once('include/config.inc.php');
include_once('include/functions.inc.php');
include_once('include/'.$dbsys.'.inc.php');
include_once('include/misc.inc.php');
include_once('include/mrbs_sql.inc.php');
// Settings
require_once('include/settings.class.php');
//Chargement des valeurs de la table settingS
if (!Settings::load())
	die("Erreur chargement settings");
// Session related functions
require_once('include/session.inc.php');
// Paramètres langage
include_once('include/language.inc.php');
// Resume session
$fin_session = 'n';
if (!grr_resumeSession())
	$fin_session = 'y';
if (($fin_session == 'y') && (Settings::get("authentification_obli") == 1))
{
	header("Location: ./logout.php?auto=1&url=$url");
	die();
}

$type_session = "no_session";

header('Content-Type: text/html; charset=utf-8');
echo begin_page(Settings::get("company"));
echo "<div class=\"container\">";
$cible = isset($_POST["cible"]) ? $_POST["cible"] : (isset($_GET["cible"]) ? $_GET["cible"] : '');
$cible = htmlentities($cible, ENT_QUOTES);
$type_cible = isset($_POST["type_cible"]) ? $_POST["type_cible"] : (isset($_GET["type_cible"]) ? $_GET["type_cible"] : '');
if ($type_cible != 'identifiant:non')
	$type_cible = '';
$action = isset($_POST["action"]) ? $_POST["action"] : '';
$corps_message = isset($_POST["message"]) ? $_POST["message"] : '';
$email_reponse = isset($_POST["email_reponse"]) ? clean_input($_POST["email_reponse"]) : '';
if (!validate_email($email_reponse))
    $email_reponse = '';
$error_subject = FALSE;
if (isset($_POST["objet_message"]))
{
	$objet_message = trim($_POST["objet_message"]);
	$error_subject = ($objet_message == '');
}
$casier = isset($_POST["casier"]) ? $_POST["casier"] : '';
if ($error_subject)
	$action='';
echo "<h1>".get_vocab("Envoi d_un courriel")."</h1>";
switch ($action)
{
//envoi du message
	case "envoi":
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
		echo "<h1 class=\"avertissement\">L'envoi de messages est impossible car l'adresse email du destinataire n'a pas été renseignée.</h1>";
		include "include/trailer.inc.php";
		exit;
	}
	//N.B. pour peaufiner, mettre un script de vérification de l'adresse email et du contenu du message !
	$message = "";
	if (($fin_session == 'n') && (getUserName()!=''))
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

	require_once 'phpmailer/PHPMailerAutoload.php';
	require_once 'include/mail.class.php';

	$destinataire = Settings::get("webmaster_email");
	Email::Envois($destinataire, $sujet, $message, $email_reponse, '', '');

	echo "<p style=\"text-align: center\">Votre message a été envoyé !</p>";

	break;
	default:
	echo "<table>";
	if (($fin_session == 'n') && (getUserName() != ''))
		echo "<tr><td>".get_vocab("Message poste par").get_vocab("deux_points")."</td><td><b> ".affiche_nom_prenom_email(getUserName(), "", $type = "nomail")."</b></td></tr>\n";
	echo "<tr><td>".get_vocab("webmaster_name").get_vocab("deux_points")."</td><td><b> ".Settings::get("webmaster_name")."</b></td></tr>\n";
	echo "<tr><td>".get_vocab("company").get_vocab("deux_points")."</td><td><b> ".Settings::get("company")."</b></td></tr>\n";
	echo "<tr><td colspan=\"2\">".get_vocab("Redigez votre message ci-dessous").get_vocab("deux_points")."</td></tr>\n";
	echo "</table>\n";
	echo "<form action=\"contact.php\" method=\"post\" id=\"doc\">\n";
	echo "<div>\n";
	echo "<input type=\"hidden\" name=\"action\" value=\"envoi\" />\n";
	if ($cible != '')
		echo "<input type=\"hidden\" name=\"cible\" value=\"".$cible."\" />\n";
	if ($type_cible != '')
		echo "<input type=\"hidden\" name=\"type_cible\" value=\"".$type_cible."\" />\n";
	echo get_vocab("Objet du message").get_vocab("deux_points");
	echo "<br /><input type=\"text\" name=\"objet_message\" id=\"objet_message\" size=\"40\" maxlength=\"256\" value='' placeholder=\"Objet\" required/>\n";
	echo "<br /><textarea name=\"message\" cols=\"50\" rows=\"5\" placeholder=\"Votre message\">".$corps_message."</textarea><br />";
	echo get_vocab("E-mail pour la reponse").get_vocab("deux_points");
	echo "<input type=\"text\" name=\"email_reponse\" id=\"email_reponse\" size=\"40\" maxlength=\"256\" ";
	if ($email_reponse != '')
		echo "value='".$email_reponse."' ";
	else if (($fin_session == 'n') && (getUserName()!=''))
	{
		$user_email = grr_sql_query1("SELECT email FROM ".TABLE_PREFIX."_utilisateurs WHERE login='".getUserName()."'");
		if (($user_email != "") && ($user_email != -1))
			echo "value='".$user_email."' ";
	}
	echo "required />\n";
	echo "<br />\n";
	echo "<p style=\"text-align:center;\">";
	echo "<input type='button' value='".get_vocab("submit")."' onclick='verif_et_valide_envoi();' />\n";
	echo "</p>\n";
	echo "</div>\n";
	echo "</form>\n";
	echo "<script type='text/javascript'>
	function verif_et_valide_envoi() {
		if (document.getElementById('objet_message')) {
			objet=document.getElementById('objet_message').value;
			if (objet=='') {
				alert('Vous n\\'avez pas saisi d\\'objet au message. Ce champ est obligatoire.');
				return;
			}
		}
		if (document.getElementById('email_reponse')) {
			email=document.getElementById('email_reponse').value;
			if (email=='') {
				confirmation=confirm('Vous n\\'avez pas saisi d\\'adresse courriel/email.\\nVous ne pourrez pas recevoir de réponse par courrier électronique.\\nSouhaitez-vous néanmoins poster le message?');
				if (confirmation) {
					document.getElementById('doc').submit();
				}
			}
			else {
				var verif = /^[a-zA-Z0-9_.-]+@[a-zA-Z0-9.-]{2,}[.][a-zA-Z]{2,3}$/
				if (verif.exec(email) == null) {
					confirmation=confirm('L\\'adresse courriel/email saisie ne semble pas valide.\\nVeuillez contrôler la saisie et confirmer votre envoi si l\\'adresse est correcte.\\nSouhaitez-vous néanmoins poster le message?');
					if (confirmation) {
						document.getElementById('doc').submit();
					}
				}
				else {
					document.getElementById('doc').submit();
				}
			}
		}
		else {
			document.getElementById('doc').submit();
		}
	}
</script>\n";
break;
}
echo "</div>";
echo "</body></html>";
?>