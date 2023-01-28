<?php
/**
 * moncompte.php
 * Interface permettant à l'utilisateur de gérer son compte dans l'application GRR
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2020-02-20 19:20$
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

$grr_script_name = 'moncompte.php';
if (!Settings::load())
	die('Erreur chargement settings');
$desactive_VerifNomPrenomUser='y';
if (!grr_resumeSession())
{
	header('Location: logout.php?auto=1&url=$url');
	die();
};
Definition_ressource_domaine_site();

include_once('../include/language.inc.php');
//include "include/resume_session.php";
$back = '';
if (isset($_SERVER['HTTP_REFERER']))
	$back = htmlspecialchars($_SERVER['HTTP_REFERER']);
$user_login = isset($_POST['user_login']) ? $_POST['user_login'] : ($user_login = isset($_GET['user_login']) ? $_GET['user_login'] : NULL);
$valid = isset($_POST['valid']) ? $_POST['valid'] : NULL;
$msg = '';
if ($valid == 'yes')
{
	if (IsAllowedToModifyMdp())
	{
		$reg_password_a = isset($_POST['reg_password_a']) ? $_POST['reg_password_a'] : NULL;
		$reg_password1 = isset($_POST['reg_password1']) ? $_POST['reg_password1'] : NULL;
		$reg_password2 = isset($_POST['reg_password2']) ? $_POST['reg_password2'] : NULL;
		if (($reg_password_a != '') && ($reg_password1 != ''))
		{
			$reg_password_a_c = password_verify($reg_password_a, $_SESSION['password']);
			if ($_SESSION['password'] == $reg_password_a_c)
			{
				if ($reg_password1 != $reg_password2)
					$msg = get_vocab('wrong_pwd2');
				else
				{
					VerifyModeDemo();
					$reg_password1 =  password_hash($reg_password1, PASSWORD_DEFAULT);
					$sql = "UPDATE ".TABLE_PREFIX."_utilisateurs SET password='".protect_data_sql($reg_password1)."' WHERE login='".getUserName()."'";
					if (grr_sql_command($sql) < 0)
						fatal_error(0, get_vocab('update_pwd_failed') . grr_sql_error());
					else
					{
						$msg = get_vocab('update_pwd_succeed');
						$_SESSION['password'] = $reg_password1;
					}
				}
			}
			else
				$msg = get_vocab('wrong_old_pwd');
		}
	}
	$sql = "SELECT email,source,nom,prenom
	FROM ".TABLE_PREFIX."_utilisateurs
	WHERE login='".getUserName()."'";
	$res = grr_sql_query($sql);
	if ($res)
	{
		for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
		{
			$user_email = $row[0];
			$user_source = $row[1];
			$user_nom = $row[2];
			$user_prenom = $row[3];
		}
	}
	$reg_email = isset($_POST['reg_email']) ? $_POST['reg_email'] : $user_email;
	$reg_nom = isset($_POST['reg_nom']) ? $_POST['reg_nom'] : $user_nom;
	$reg_prenom = isset($_POST['reg_prenom']) ? $_POST['reg_prenom'] : $user_prenom;
	$champ_manquant = 'n';
	if (trim($reg_nom) == '')
		$champ_manquant = 'y';
	if (trim($reg_prenom) == '')
		$champ_manquant = 'y';
	if (($user_email != $reg_email) || ($user_nom != $reg_nom) || ($user_prenom != $reg_prenom))
	{
		$sql = "UPDATE ".TABLE_PREFIX."_utilisateurs SET ";
		$flag_virgule = 'n';
		if (IsAllowedToModifyProfil())
		{
			if (trim($reg_nom) != '')
			{
				$sql.="nom = '" . protect_data_sql($reg_nom)."'";
				$flag_virgule = 'y';
				$_SESSION['nom'] = htmlspecialchars($reg_nom);
			}
			if (trim($reg_prenom) != '')
			{
				if ($flag_virgule == 'y') $sql .=",";
				$sql .= "prenom = '" . protect_data_sql($reg_prenom)."'";
				$flag_virgule = 'y';
				$_SESSION['prenom'] = htmlspecialchars($reg_prenom);
			}
		}
		if (IsAllowedToModifyEmail())
		{
			if(Settings::get('mail_user_obligatoire') != "y" || (Settings::get('mail_user_obligatoire') == "y" && $reg_email != "")){
				if ($flag_virgule == 'y')
					$sql .= ",";
				$sql .= "email = '" . protect_data_sql($reg_email)."'";
			} else{
				$msg = get_vocab('mail_user_obligatoire');
			}
		}
		$sql .= "WHERE login='".getUserName()."'";
		if ((IsAllowedToModifyProfil()) || (IsAllowedToModifyEmail()))
		{
			if (grr_sql_command($sql) < 0)
				fatal_error(0, get_vocab('message_records_error') . grr_sql_error());
			else
				$msg .= "\\n".get_vocab('message_records');
		}
	}
	if (IsAllowedToModifyProfil() && ($champ_manquant=='y'))
		$msg .= "\\n".str_replace("\'","'",get_vocab('required'));
}
if (($valid == 'yes') || ($valid=='reset'))
{
	$default_site = isset($_POST['id_site']) ? $_POST['id_site'] : NULL;
	$default_area = isset($_POST['id_area']) ? $_POST['id_area'] : NULL;
	$default_room = isset($_POST['id_room']) ? $_POST['id_room'] : NULL;
	$default_style = isset($_POST['default_css']) ? $_POST['default_css'] : NULL;
	$default_list_type = isset($_POST['area_item_format']) ? $_POST['area_item_format'] : NULL;
	$default_language = isset($_POST['default_language']) ? $_POST['default_language'] : NULL;
	$sql = "UPDATE ".TABLE_PREFIX."_utilisateurs
	SET default_site = '".protect_data_sql($default_site)."',
	default_area = '".protect_data_sql($default_area)."',
	default_room = '".protect_data_sql($default_room)."',
	default_style = '". protect_data_sql($default_style)."',
	default_list_type = '".protect_data_sql($default_list_type)."',
	default_language = '".protect_data_sql($default_language)."'
	WHERE login='".getUserName()."'";
	if (grr_sql_command($sql) < 0)
		fatal_error(0, get_vocab('message_records_error').grr_sql_error());
	else
	{
		if (($default_site != '') && ($default_site !='0'))
			$_SESSION['default_site'] = $default_site;
		else
			$_SESSION['default_site'] = Settings::get('default_site');
		if (($default_area != '') && ($default_area !='0'))
			$_SESSION['default_area'] = $default_area;
		else
			$_SESSION['default_area'] = Settings::get('default_area');
		if (($default_room != '') && ($default_room !='0'))
			$_SESSION['default_room'] = $default_room;
		else
			$_SESSION['default_room'] = Settings::get('default_room');
		if ($default_style != '')
			$_SESSION['default_style'] = $default_style;
		else
			$_SESSION['default_style'] = Settings::get('default_css');
		if ($default_list_type != '')
			$_SESSION['default_list_type'] = $default_list_type;
		else
			$_SESSION['default_list_type'] = Settings::get('area_list_format');
		if ($default_language != '')
			$_SESSION['default_language'] = $default_language;
		else
			$_SESSION['default_language'] = Settings::get('default_language');
	}
}
$use_prototype = 'y';

echo "\n    <!-- Repere ".$grr_script_name." -->\n";
if (Settings::get("module_multisite") == "Oui")
	$d['use_site'] = 'y';
else
	$d['use_site'] = 'n';
$sql = "SELECT nom,prenom,statut,email,default_site,default_area,default_room,default_style,default_list_type,default_language,source FROM ".TABLE_PREFIX."_utilisateurs WHERE login='".getUserName()."'";
$res = grr_sql_query($sql);
if ($res)
{
	for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
	{
		$user_nom = $row[0];
		$user_prenom = $row[1];
		$user_statut = $row[2];
		$user_email = $row[3];
		if (($row[4] != '') && ($row[4] !='0'))
			$default_site = $row[4];
		else
			$default_site = Settings::get('default_site');
		if (($row[5] != '') && ($row[5] !='0'))
			$default_area = $row[5];
		else
			$default_area = Settings::get('default_area');
		if (($row[6] != '') && ($row[6] !='0'))
			$default_room = $row[6];
		else
			$default_room = Settings::get('default_room');
		//if ($row[7] != '')
		$default_css = $row[7];
		//else
		//	$default_css = Settings::get('default_css');
		if ($row[8] != '')
			$default_list_type = $row[8];
		else
			$default_list_type = Settings::get('area_list_format');
		if ($row[9] != '')
			$default_language = $row[9];
		else
			$default_language = Settings::get('default_language');
		$user_source = $row[10];
	}
}

affiche_pop_up($msg,'admin');

if ($user_statut == "utilisateur")
	$text_user_statut = get_vocab("statut_user");
else if ($user_statut == "visiteur")
	$text_user_statut = get_vocab("statut_visitor");
else if ($user_statut == "gestionnaire_utilisateur")
	$text_user_statut = get_vocab("statut_user_administrator");
else if ($user_statut == "administrateur")
	$text_user_statut = get_vocab("statut_administrator");
else
	$text_user_statut = $user_statut;

get_vocab('nom_prenom_valides');
get_vocab_admin('login');
get_vocab_admin('last_name');
get_vocab_admin('first_name');
get_vocab_admin('mail_user');
get_vocab_admin('statut');
get_vocab_admin('required');

get_vocab_admin('pwd_msg_warning');
get_vocab_admin('old_pwd');
get_vocab_admin('new_pwd1');
get_vocab_admin('new_pwd2');
get_vocab_admin('pwd_strength');

get_vocab_admin('default_parameter_values_title');
get_vocab_admin('explain_area_list_format');

get_vocab_admin('liste_area_list_format');
get_vocab_admin('select_area_list_format');
get_vocab_admin('item_area_list_format');

get_vocab_admin('explain_default_area_and_room_and_site');
get_vocab_admin('default_site');
get_vocab_admin('choose_a_site');
get_vocab_admin('explain_default_area_and_room');

get_vocab_admin('explain_css');
get_vocab_admin('choose_css');

get_vocab_admin('choose_language');;

get_vocab_admin('save');;
get_vocab_admin('reset');;

$d['identifiant'] = getUserName();
$d['modificationNom'] = IsAllowedToModifyProfil();
$d['modificationMail'] = IsAllowedToModifyEmail();
$d['modificationMDP'] = IsAllowedToModifyMdp();
$d['user_nom'] = $user_nom;
$d['user_prenom'] = $user_prenom;
$d['user_email'] = $user_email;
$d['text_user_statut'] = $text_user_statut;
$d['default_list_type'] = $default_list_type;
$d['default_site'] = $default_site;
$d['default_area'] = $default_area;
$d['default_css'] = $default_css;
$d['default_language'] = $default_language;
$d['default_room'] = $default_room;

	if (IsAllowedToModifyProfil())
	{
		if ((trim($user_nom) == "") || (trim($user_prenom) == ''))
			$d['AvertissementNomPrenom'] = 1;
	}

	// Liste des sites
	$sql = "SELECT id,sitecode,sitename
	FROM ".TABLE_PREFIX."_site
	ORDER BY id ASC";
	$resultat = grr_sql_query($sql);

	$sites = array();
	for ($enr = 0; ($row = grr_sql_row($resultat, $enr)); $enr++) {
		$sites[] = array('idsite' => $row[0], 'nomsite' => $row[2]);
	}

	$i = 0;
	while ($i < count($liste_themes))
	{
		$themes[] = array('idtheme' => $liste_themes[$i], 'nomtheme' => encode_message_utf8($liste_name_themes[$i]));
		$i++;
	}

	$i = 0;
	while ($i < count($liste_language))
	{
		$langues[] = array('idlangue' => $liste_language[$i], 'nomlangue' => encode_message_utf8($liste_name_language[$i]));
		$i++;
	}


	echo $twig->render('moncompte.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings, 'sites' => $sites, 'themes' => $themes, 'langues' => $langues));
?>