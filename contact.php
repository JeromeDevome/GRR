<?php
/**
 * contact.php
 * Formulaire d'envoi de mail
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2022-01-14 18:59$
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
include_once('include/connect.inc.php');
include_once('include/config.inc.php');
include_once('include/misc.inc.php');
include_once('include/'.$dbsys.'.inc.php');
include_once('include/mrbs_sql.inc.php');
include_once('include/functions.inc.php');
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
$user_name = getUserName();
// traitement des données
$cible = isset($_POST["cible"]) ? $_POST["cible"] : (isset($_GET["cible"]) ? $_GET["cible"] : '');
$cible = htmlentities($cible, ENT_QUOTES);
$type_cible = isset($_POST["type_cible"]) ? $_POST["type_cible"] : (isset($_GET["type_cible"]) ? $_GET["type_cible"] : '');
if ($type_cible != 'identifiant:non')
	$type_cible = '';
$action = isset($_POST["action"]) ? $_POST["action"] : '';
$corps_message = isset($_POST["corps_message"]) ? clean_input($_POST["corps_message"]) : '';
$email_reponse = isset($_POST["email_reponse"]) ? clean_input($_POST["email_reponse"]) : '';
if (!validate_email($email_reponse))
    $email_reponse = '';
$error_subject = FALSE;
if (isset($_POST["objet_message"]))
{
	$objet_message = trim($_POST["objet_message"]);
	$error_subject = ($objet_message == '');
}
if ($error_subject)
	$action='';
$casier = isset($_POST["casier"]) ? clean_input($_POST["casier"]) : '';
if($action == "envoi")
{ //envoi du message
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
        $alerte = "<h1 class=\"avertissement\">".get_vocab('mail_sending_impossible')."</h1>";
	}
    else 
    {
        $message = "";
        if (($fin_session == 'n') && ($user_name!=''))
        {
            $message .= get_vocab('nom_complet_demandeur').get_vocab('deux_points').affiche_nom_prenom_email($user_name,"","nomail")."\n";
            $user_email = grr_sql_query1("select email from ".TABLE_PREFIX."_utilisateurs where login='".$user_name."'");
            if (($user_email != "") && ($user_email != -1))
                $message .= get_vocab('mail_demandeur').get_vocab('deux_points').$user_email."\n";
            $message .= $vocab["statut"].preg_replace("/ /", " ",$vocab["deux_points"]).$_SESSION['statut']."\n";
        }
        $message .= $vocab["company"].preg_replace("/ /", " ",$vocab["deux_points"]).removeMailUnicode(Settings::get("company"))."\n";
        $message .= $vocab["email"].preg_replace("/ /", " ",$vocab["deux_points"]).$email_reponse."\n";
        $message .="\n".$corps_message."\n";
        $sujet = $vocab["subject_mail1"]." - ".$objet_message;

        require_once 'phpmailer/PHPMailerAutoload.php';
        require_once 'include/mail.class.php';

        if (Settings::get('grr_mail_sender'))
            $expediteur = Settings::get('grr_mail_from');
        else 
            $expediteur = $email_reponse;

        if (is_null(Email::Envois($destinataire, $sujet, $message, $expediteur, '', '', $email_reponse)))
            $alerte = "<p style=\"text-align: center\">".get_vocab('mail_sent')."</p>";
        else 
            $alerte = "<p style=\"text-align: center\" class='avertissement' >".get_vocab('mail_not_sent')."</p>";
    }
}
// formulaire
//header('Content-Type: text/html; charset=utf-8');
//echo begin_page(Settings::get("company"));
echo '<!DOCTYPE html>
<html lang="fr">
	<head>
		<meta charset="utf-8">
		<title></title>
		<link rel="stylesheet" type="text/css" href="bootstrap/css/bootstrap.min.css">
	</head>
	<body>';
if(isset($alerte) && ($alerte !='')){
    echo $alerte;
}
else {
    echo "<div class=\"container\">";
    echo "<h1>".get_vocab("Envoi d_un courriel")."</h1>";
    if (($fin_session == 'n') && ($user_name != ''))
        echo "<div class='row'><div class='col-xs-6'>".get_vocab("Message poste par").get_vocab("deux_points")."</div><div class='col-xs-6'><b> ".affiche_nom_prenom_email($user_name, "", $type = "nomail")."</b></div></div>\n";
    echo "<div class='row'><div class='col-xs-6'>".get_vocab("webmaster_name").get_vocab("deux_points")."</div><div class='col-xs-6'><b> ".Settings::get("webmaster_name")."</b></div></div>\n";
    echo "<div class='row'><div class='col-xs-6'>".get_vocab("company").get_vocab("deux_points")."</div><div class='col-xs-6'><b> ".Settings::get("company")."</b></div></div>\n";
    echo "<br /><div class='row'><strong>".get_vocab("Redigez votre message ci-dessous").get_vocab("deux_points")."</strong></div>";
    echo "<form action=\"contact.php\" method=\"post\" id=\"doc\">\n";
    echo "<input type=\"hidden\" name=\"action\" value=\"envoi\" />\n";
    if ($cible != '')
        echo "<input type=\"hidden\" name=\"cible\" value=\"".$cible."\" />\n";
    if ($type_cible != '')
        echo "<input type=\"hidden\" name=\"type_cible\" value=\"".$type_cible."\" />\n";
    echo '<div class="form-group">';
    echo '<div class="input-group">';
    echo '<span class="input-group-addon">@</span>';
    echo '<input class="form-control" type="email" id="email_reponse" name="email_reponse" placeholder="'.get_vocab("E-mail pour la reponse").'" ';
    if ($email_reponse != '')
        echo 'value="'.$email_reponse.'" ';
    else if (($fin_session == 'n') && ($user_name!=''))
    {
        $user_email = grr_sql_query1("SELECT email FROM ".TABLE_PREFIX."_utilisateurs WHERE login='".$user_name."'");
        if (($user_email != "") && ($user_email != -1))
            echo 'value="'.$user_email.'" ';
    }
    echo 'required />';
    echo '</div></div>'.PHP_EOL;
    echo '<div class="form-group">';
    echo '<div class="input-group">';
    echo '<span class="input-group-addon"><span class="glyphicon glyphicon-header"></span>  '.get_vocab("Objet du message").'</span>';
    echo '<input class="form-control" type="text" id="objet_message" name="objet_message" maxlength="256" required />';
    echo '</div></div>'.PHP_EOL;
    echo '<div class="form-group">';
    echo '<div class="input-group">';
    echo '<span class="input-group-addon"><span class="glyphicon glyphicon-align-left"></span></span>';
    echo '<textarea class="form-control" name="corps_message" placeholder="'.get_vocab('Votre_message').'" cols="50" rows="5" required >'.$corps_message.'</textarea>';
    echo '</div></div>';
    echo "<br />\n";
    echo "<p style=\"text-align:center;\">";
    echo "<input type='submit' value='".get_vocab("submit")."' />\n";
    echo "</p>\n";
    echo "</form>\n";
    echo "</div>";
}
echo "</body></html>";
?>