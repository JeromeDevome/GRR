<?php
/**
 * admin_config3.php
 * Interface permettant à l'administrateur la configuration de certains paramètres généraux (interactivité)
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2021-03-13 12:00$
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

$grr_script_name = "admin_config3.php";

include "../include/admin.inc.php";

$back = (isset($_SERVER['HTTP_REFERER']))? htmlspecialchars_decode($_SERVER['HTTP_REFERER'], ENT_QUOTES) : "./admin_accueil.php" ;
$_SESSION['chemin_retour'] = "admin_accueil.php";
$day   = date("d");
$month = date("m");
$year  = date("Y");
check_access(6, $back);
$msg = "";
// Automatic mail
if ((isset($_POST['automatic_mail']))&&(($_POST['automatic_mail']=='yes')||($_POST['automatic_mail']=='no')))
{
	if (!Settings::set("automatic_mail", $_POST['automatic_mail']))
	{
		echo get_vocab('automatic_mail_save_err');
		die();
	}
}
//envoyer_email_avec_formulaire
if ((isset($_POST['envoyer_email_avec_formulaire']))&&(($_POST['envoyer_email_avec_formulaire']=='yes')||($_POST['envoyer_email_avec_formulaire']=='no')))
{
	if (!Settings::set("envoyer_email_avec_formulaire", $_POST['envoyer_email_avec_formulaire']))
	{
		echo get_vocab('envoyer_email_avec_formulaire_save_err');
		die();
	}
}
// javascript_info_disabled
if ((isset($_POST['javascript_info_disabled']))&&(($_POST['javascript_info_disabled']==0)||($_POST['javascript_info_disabled']==1)))
{
	if (!Settings::set("javascript_info_disabled", $_POST['javascript_info_disabled']))
	{
		echo get_vocab('javascript_info_disabled_save_err');
		die();
	}
}
// javascript_info_admin_disabled
if ((isset($_POST['javascript_info_admin_disabled']))&&(($_POST['javascript_info_admin_disabled']==0)||($_POST['javascript_info_admin_disabled']==1)))
{
	if (!Settings::set("javascript_info_admin_disabled", $_POST['javascript_info_admin_disabled']))
	{
		echo get_vocab('javascript_info_admin_disabled_save_err');
		die();
	}
}
if ((isset($_POST['grr_mail_method']))&&(($_POST['grr_mail_method']=='mail')||($_POST['grr_mail_method']=='smtp')))
{
	if (!Settings::set("grr_mail_method", $_POST['grr_mail_method']))
	{
		echo get_vocab('grr_mail_method_save_err');
		die();
	}
}
if (isset($_POST['grr_mail_smtp'])) // à filtrer mieux?
{
	if (!Settings::set("grr_mail_smtp", clean_input($_POST['grr_mail_smtp'])))
	{
		echo get_vocab('grr_mail_smtp_save_err');
		die();
	}
}
if (isset($_POST['grr_mail_Username']))
{
    $grrMailUserNameValid = TRUE;
    $grrMailUserName = clean_input($_POST['grr_mail_Username']); // clean_input enlève les \, ce qui peut être gênant dans un domaine AD
    if ($grrMailUserName != $_POST['grr_mail_Username']){ // rattrapage pour domaine AD
        $regexAD = '/^[a-zA-Z][a-zA-Z0-9\-\.]{1,61}[a-zA-Z]\\\\[a-zA-Z0-9]{2,}$/i'; // domaine AD
        if(preg_match($regexAD, $_POST['grr_mail_Username']))
            $grrMailUserName = $_POST['grr_mail_Username'];
        else $grrMailUserNameValid = FALSE;
    }
	if (!$grrMailUserNameValid || (!Settings::set("grr_mail_Username", $grrMailUserName)))
	{
		echo get_vocab('grr_mail_Username_save_err');
		die();
	}
}
if (isset($_POST['grr_mail_Password']))
{
	if (!Settings::set("grr_mail_Password", clean_input($_POST['grr_mail_Password'])))
	{
		echo get_vocab('grr_mail_Password_save_err');
		die();
	}
}
if (isset($_POST['grr_mail_from']))
{
	if (!Settings::set("grr_mail_from", clean_input($_POST['grr_mail_from'])))
	{
		echo get_vocab('grr_mail_from_save_err');
		die();
	}
}
if (isset($_POST['grr_mail_fromname']))
{
	if (!Settings::set("grr_mail_fromname", clean_input($_POST['grr_mail_fromname'])))
	{
		echo get_vocab('grr_mail_fromname_save_err');
		die();
	}
}
if ((isset($_POST['smtp_secure']))&&(in_array($_POST['smtp_secure'],["","ssl","tls"])))
{
	if (!Settings::set("smtp_secure", $_POST['smtp_secure']))
	{
		echo get_vocab('save_err')." smtp_secure !<br />";
		die();
	}
}
if ((isset($_POST['smtp_port']))&&(is_numeric($_POST['smtp_port'])))
{
	if (!Settings::set("smtp_port", intval($_POST['smtp_port'])))
	{
		echo get_vocab('smtp_port_save_err');
		die();
	}
}
// Si Email test renseigné on y envoie un mail
if (isset($_POST['mail_test']) && !empty($_POST['mail_test']))
{
    $mail_test = clean_input($_POST['mail_test']);
    if (!validate_email($mail_test)){
        echo get_vocab('invalid_test_mail_address');
        die();
    }
	require_once '../include/mail.class.php';
	require_once '../phpmailer/PHPMailerAutoload.php';
	Email::Envois($mail_test, 'GRR, votre système de réservations', "Ceci est un test depuis l'administration de votre GRR.<br>Le mail est arrivé à destination.", Settings::get('grr_mail_from'), '', '');
}
if (isset($_POST['ok']))
{
	if (isset($_POST['grr_mail_Bcc']))
		$grr_mail_Bcc = "y";
	else
		$grr_mail_Bcc = "n";
	if (!Settings::set("grr_mail_Bcc", $grr_mail_Bcc))
	{
		echo get_vocab['save_err']." grr_mail_Bcc !<br />";
		die();
	}
    $grr_mail_sender = (isset($_POST['grr_mail_sender']))? 1 : 0;
    if (!Settings::set("grr_mail_sender", $grr_mail_sender))
	{
		echo get_vocab['save_err']." grr_mail_sender !<br />";
		die();
	}
}
if ((isset($_POST['verif_reservation_auto']))&&(($_POST['verif_reservation_auto']==0)||($_POST['verif_reservation_auto']==1)))
{
	if (!Settings::set("verif_reservation_auto", $_POST['verif_reservation_auto']))
	{
		echo get_vocab('save_err')." verif_reservation_auto !<br />";
		die();
	}
	if ($_POST['verif_reservation_auto'] == 0)
	{
		$_POST['motdepasse_verif_auto_grr'] = "";
		$_POST['chemin_complet_grr'] = "";
	}
}
if (isset($_POST['motdepasse_verif_auto_grr']))
{
	if (($_POST['verif_reservation_auto'] == 1) && ($_POST['motdepasse_verif_auto_grr'] == ""))
		$msg .= "l'exécution du script verif_auto_grr.php requiert un mot de passe !\\n";
	if (!Settings::set("motdepasse_verif_auto_grr", clean_input($_POST['motdepasse_verif_auto_grr'])))
	{
		echo $vocab['save_err']." motdepasse_verif_auto_grr !<br />";
		die();
	}
}
if (isset($_POST['chemin_complet_grr']))
{
	if (!Settings::set("chemin_complet_grr", clean_input($_POST['chemin_complet_grr'])))
	{
		echo $vocab['save_err']." chemin_complet_grr !<br />";
		die();
	}
}
if (!Settings::load())
	die(get_vocab('error_settings_load'));
# print the page header
start_page_w_header("", "", "", $type="with_session");
if (isset($_POST['ok']))
{
	$msg = get_vocab("message_records");
	affiche_pop_up($msg,"admin");
}
// Affichage de la colonne de gauche
include "admin_col_gauche2.php";
// colonne de droite
echo '<div class="col-sm-9 col-xs-12">';
echo "<h2>".get_vocab('admin_config3.php')."</h2>";
echo "<form action=\"./admin_config3.php\"  method=\"POST\" >\n";
//
// Automatic mail
//********************************
//
echo "<h3>".get_vocab('title_automatic_mail')."</h3>\n";
echo "<p><i>".get_vocab("warning_message_mail")."</i></p>\n";
echo "<p>".get_vocab("explain_automatic_mail")."\n";
echo "<br />
<input type='radio' name='automatic_mail' value='yes' id='label_3' ";
if (Settings::get("automatic_mail") == 'yes') echo "checked=\"checked\"";
echo "/>
<label for='label_3'>";
echo get_vocab("mail_admin_on");
if (Settings::get("automatic_mail") == 'yes')
	echo " - <a href='admin_email_manager.php'>".get_vocab('admin_email_manager.php')."</a>\n";
echo "
</label>
<br />
<input type='radio' name='automatic_mail' value='no' id='label_4'";
 if (Settings::get("automatic_mail") == 'no') echo "checked=\"checked\"";
echo "/>
<label for='label_4'>".get_vocab("mail_admin_off")."</label>";
// Configuration des liens adresses
echo "</p><hr /><h3>".get_vocab('configuration_liens_adresses')."</h3>\n";
echo "<p>";
echo "<input type='radio' name='envoyer_email_avec_formulaire' value='yes' id='label_5' ";
if (Settings::get("envoyer_email_avec_formulaire") == 'yes') echo "checked=\"checked\"";
echo "/>
    <label for='label_5'>";
    echo get_vocab("envoyer_email_avec_formulaire_oui");
    echo "</label>
<br /><input type='radio' name='envoyer_email_avec_formulaire' value='no' id='label_6' ";
if (Settings::get("envoyer_email_avec_formulaire") == 'no') echo "checked=\"checked\"";
echo "/> <label for='label_6'>";
echo get_vocab("envoyer_email_avec_formulaire_non");
echo "</label>";
// Paramètres de configuration de l'envoi automatique des mails
echo "</p><hr /><h3>".get_vocab('Parametres_configuration_envoi_automatique_mails')."</h3>\n";
echo "<p>".get_vocab('Explications_des_Parametres_configuration_envoi_automatique_mails');
// Choix mail ou smtp
echo "<br /><br /><input type=\"radio\" name=\"grr_mail_method\" value=\"mail\" ";
if (Settings::get('grr_mail_method') == "mail")
	echo " checked=\"checked\" ";
echo "/>\n";
echo get_vocab('methode_mail');
echo "  <input type=\"radio\" name=\"grr_mail_method\" value=\"smtp\" ";
if (Settings::get('grr_mail_method') == "smtp")
	echo " checked=\"checked\" ";
echo "/>\n";
echo get_vocab('methode_smtp');
// Serveur SMTP:
echo "\n<br /><br />".get_vocab('Explications_methode_smtp_1').get_vocab('deux_points');
echo "\n<input type = \"text\" name=\"grr_mail_smtp\" value =\"".Settings::get('grr_mail_smtp')."\" />";
echo "\n<br />".get_vocab('Explications_methode_smtp_2');
// Utilisateur SMTP:
echo "\n<br />".get_vocab('utilisateur_smtp').get_vocab('deux_points');
echo "\n<input type = \"text\" name=\"grr_mail_Username\" value =\"".Settings::get('grr_mail_Username')."\" />";
// MDP SMTP:
echo "\n<br />".get_vocab('pwd').get_vocab('deux_points');
echo "\n<input type = \"password\" name=\"grr_mail_Password\" value =\"".Settings::get('grr_mail_Password')."\" />";
// @ expediteur:
echo "\n<br />".get_vocab('Email_expediteur_messages_automatiques').get_vocab('deux_points');
if (trim(Settings::get('grr_mail_from')) == "")
	$grr_mail_from = "noreply@mon.site.fr";
else
	$grr_mail_from = Settings::get('grr_mail_from');
echo "\n<input type = \"text\" name=\"grr_mail_from\" value =\"".$grr_mail_from."\" size=\"30\" />";
// Nom expediteur
echo "\n<br />".get_vocab('Nom_expediteur_messages_automatiques').get_vocab('deux_points');
echo "\n<input type = \"text\" name=\"grr_mail_fromname\" value =\"".Settings::get('grr_mail_fromname')."\" size=\"30\" />";
// smtpauth
//echo "\n<br />".get_vocab('smtp_auth').get_vocab('deux_points');
//echo "\n<input type = \"text\" name=\"smtp_auth\" value =\"".Settings::get('smtp_auth')."\" size=\"30\" />";
// smtpsecure
echo "\n<br />".get_vocab('smtp_secure').get_vocab('deux_points');
echo "\n<input type = \"text\" name=\"smtp_secure\" value =\"".Settings::get('smtp_secure')."\" size=\"30\" />";
// Port
echo "\n<br />".get_vocab('smtp_port').get_vocab('deux_points');
echo "\n<input type = \"text\" name=\"smtp_port\" value =\"".Settings::get('smtp_port')."\" size=\"30\" />";
// Mail Test
echo "\n<br />".get_vocab('mail_test').get_vocab('deux_points');
echo "\n<input type = \"email\" name=\"mail_test\" value =\"\" size=\"30\" />";
// Expéditeur imposé (cas où le relai smtp ne fonctionne pas)
echo "\n<br /><em>".get_vocab('grr_mail_sender_explain')."</em>";
echo "\n<br /><input type=\"checkbox\" name=\"grr_mail_sender\" value='1' ";
if (Settings::get('grr_mail_sender') == 1)
    echo " checked=\"checked\" ";
echo "/>";
echo get_vocab('grr_mail_sender');
// Copie CCi
echo "\n<br /><br />";
echo "\n<input type=\"checkbox\" name=\"grr_mail_Bcc\" value=\"y\" ";
if (Settings::get('grr_mail_Bcc') == "y")
	echo " checked=\"checked\" ";
echo "/>";
echo get_vocab('copie_cachee');
# Désactive les messages javascript (pop-up) après la création/modificatio/suppression d'une réservation
# 1 = Oui, 0 = Non
echo "\n</p><hr /><h3>".get_vocab("javascript_info_disabled_msg")."</h3>";
echo "<p>";
echo "\n<input id='label_7' type='radio' name='javascript_info_disabled' value='0' ";
if (Settings::get("javascript_info_disabled") == '0')
	echo "checked=\"checked\"";
echo " />";
echo "\n<label for='label_7'>&nbsp;".get_vocab("javascript_info_disabled0")."</label>";
echo "<br />";
echo "\n<input id='label_8' type='radio' name='javascript_info_disabled' value='1' ";
if (Settings::get("javascript_info_disabled") == '1')
	echo "checked=\"checked\"";
echo " />";
echo "\n<label for='label_8'>&nbsp;".get_vocab("javascript_info_disabled1")."</label>";
echo "</p>";
# Désactive les messages javascript d'information (pop-up) dans les menus d'administration
# 1 = Oui, 0 = Non
echo "\n<hr /><h3>".get_vocab("javascript_info_admin_disabled_msg")."</h3>";
echo "<p>";
echo "\n<input id='label_9' type='radio' name='javascript_info_admin_disabled' value='0' ";
if (Settings::get("javascript_info_admin_disabled") == '0')
	echo "checked=\"checked\"";
echo " />";
echo "\n<label for='label_9'>&nbsp;".get_vocab("javascript_info_admin_disabled0")."</label>";
echo "\n<br />";
echo "\n<input id='label_10' type='radio' name='javascript_info_admin_disabled' value='1' ";
if (Settings::get("javascript_info_admin_disabled") == '1')
	echo "checked=\"checked\"";
echo " />";
echo "\n<label for='label_10'>&nbsp;".get_vocab("javascript_info_admin_disabled1")."</label>";
echo "</p>";
# tâche automatique de suppression
echo "\n<hr /><h3>".get_vocab("suppression_automatique_des_reservations")."</h3>";
echo "\n<p>".get_vocab('Explications_suppression_automatique_des_reservations')."</p>";
echo "<p>";
echo "\n<input id ='label_11' type='radio' name='verif_reservation_auto' value='0' ";
if (Settings::get("verif_reservation_auto") == '0')
	echo "checked=\"checked\"";
echo " />";
echo "\n<label for='label_11'>&nbsp;".get_vocab("verif_reservation_auto0")."</label>";
echo "<br />";
echo "\n<input id ='label_12' type='radio' name='verif_reservation_auto' value='1' ";
if (Settings::get("verif_reservation_auto") == '1')
	echo "checked=\"checked\"";
echo " />";
echo "\n<label for='label_12'>&nbsp;".get_vocab("verif_reservation_auto1")."</label>";
echo "<br />";
echo "<div class='col-xs-12'>";
echo "\n<label class='col-sm-9 col-xs-12' for='label-13'>".get_vocab("verif_reservation_auto2").get_vocab("deux_points")."</label>";
echo "\n<input class='col-sm-3 col-xs-12' id='label-13' type=\"text\" name=\"motdepasse_verif_auto_grr\" value=\"".Settings::get("motdepasse_verif_auto_grr")."\" size=\"20\" />";
echo "\n</div>";
echo "<div class='col-xs-12'>";
echo "\n<label class='col-sm-9 col-xs-12' for='label_14'>".get_vocab("verif_reservation_auto3").get_vocab("deux_points")."</label>";
echo "\n<input class='col-sm-3 col-xs-12' id='label_14' type=\"text\" name=\"chemin_complet_grr\" value=\"".Settings::get("chemin_complet_grr")."\" size=\"20\" />";
echo "\n</div>";
echo "\n<br /><br /></p>";
echo "\n<div id=\"fixe\" style=\"text-align:center;\"><input class=\"btn btn-primary\" type=\"submit\" name=\"ok\" value=\"".get_vocab("save")."\" style=\"font-variant: small-caps;\"/></div>";
echo "\n</form>";
// fin de l'affichage de la colonne de droite et de la page
echo "</div>";
end_page();
?>