<?php
/**
 * admin_user_demandes.php
 * interface de gestion des utilisateurs de l'application GRR
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2023-02-24 16:15
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

include_once('../include/pages.class.php');

$grr_script_name = "admin_user_demandes.php";

if (!Pages::load())
	die('Erreur chargement pages');

$choix = isset($_GET["choix"]) ? intval($_GET["choix"]) : NULL;
$idemande = isset($_GET["iddemande"]) ? intval($_GET["iddemande"]) : NULL;
$getLogin = isset($_GET["login"]) ? $_GET["login"] : NULL;
$getStatut = isset($_GET["statut"]) ? $_GET["statut"] : 'visiteur';

$erreur = false;
$msg = "";
$col = array();

if ((authGetUserLevel(getUserName(), -1) < 6) && (authGetUserLevel(getUserName(), -1,'user') != 1))
{
	showAccessDenied($back);
	exit();
}


if (isset($choix) && $choix > 0)
{

	VerifyModeDemo();

	$sql = "SELECT * FROM ".TABLE_PREFIX."_utilisateurs_demandes WHERE idutilisateursdemandes = '".protect_data_sql($idemande)."' AND etat <> '1'";
	$resDemande = grr_sql_query($sql);
	$nombreligne = grr_sql_count ($resDemande);
	if ($nombreligne != 0)
	{
		$demande = grr_sql_row_keyed($resDemande, 0);

		require_once '../vendor/phpmailer/phpmailer/src/PHPMailer.php';
		require_once '../vendor/phpmailer/phpmailer/src/SMTP.php';
		require_once '../vendor/phpmailer/phpmailer/src/Exception.php';
		require_once '../include/mail.class.php';

		$expediteur = Settings::get("webmaster_email");

		//Infos générales
		$codes = [
			'%nomdusite%' => Settings::get('title_home_page'),
			'%nometablissement%' => Settings::get('company'),
			'%urlgrr%' =>  traite_grr_url("","y"),
			'%webmasteremail%' => Settings::get("webmaster_email"),
			'%formnom%' => $demande['nom'],
			'%formprenom%' => $demande['prenom'],
			'%formemail%' => $demande['email'],
			'%formtelephone%' => $demande['telephone'],
			'%formcommentaire%' => $demande['commentaire']
		];

		if($choix == 1) // Acceptation de la demande
		{
			$getLogin = trim($getLogin);

			$test_login = preg_replace("/([A-Za-z0-9_@.-])/","",$getLogin);
			if($test_login == ""){

				$sql = "SELECT * FROM ".TABLE_PREFIX."_utilisateurs WHERE login = '".$getLogin."'";
				$res = grr_sql_query($sql);
				$nombreligne = grr_sql_count ($res);
				if ($nombreligne != 0 || $getLogin == "")
				{
					$msg = get_vocab("error_exist_login");
					$erreur = true;
				}
				else
				{
					$sql = "INSERT INTO ".TABLE_PREFIX."_utilisateurs SET
					nom='".protect_data_sql($demande['nom'])."',
					prenom='".protect_data_sql($demande['prenom'])."',
					login='".protect_data_sql($getLogin)."',
					password='".protect_data_sql($demande['mdp'])."',
					changepwd='0',
					statut='".protect_data_sql($getStatut)."',
					email='".protect_data_sql($demande['email'])."',
					etat='actif',
					default_site = '-1',
					default_area = '-1',
					default_room = '-1',
					default_style = '',
					default_list_type = 'item',
					default_language = 'fr-fr',
					source='local'";

					if (grr_sql_command($sql) < 0)
					{
						fatal_error(0, get_vocab("msg_login_created_error") . grr_sql_error());
						$erreur = true;
					}

					$codes['%identifiant%'] = $getLogin;
			
					// Mail au demandeur
					$templateMail1 = Pages::get('mails_demandecompte2_'.$locale);
					$sujetEncode1 = str_replace(array_keys($codes), $codes, $templateMail1[0]);
					$msgEncode1 = str_replace(array_keys($codes), $codes, $templateMail1[1]);
					Email::Envois($demande['email'], $sujetEncode1, $msgEncode1, $expediteur, '', '');

				}
			}
			else
				$msg = get_vocab("erreur_caract_login");

		}
		elseif($choix == 2)
		{
			// Mail au demandeur
			$templateMail2 = Pages::get('mails_demandecompte3_'.$locale);
			$sujetEncode2 = str_replace(array_keys($codes), $codes, $templateMail2[0]);
			$msgEncode2 = str_replace(array_keys($codes), $codes, $templateMail2[1]);
			Email::Envois($demande['email'], $sujetEncode2, $msgEncode2, $expediteur, '', '');
		}

		// Dans tout les cas on met à jour le choix sauf si il y a eu une erreur
		if($erreur == false)
		{
			$sql = "UPDATE ".TABLE_PREFIX."_utilisateurs_demandes SET 
			etat='".$choix."',
			gestionnaire='".getUserName()."',
			datedemande='".date("Ymd")."'
			WHERE idutilisateursdemandes='".protect_data_sql($idemande)."'";
			if (grr_sql_command($sql) < 0)
			{
				fatal_error(0, get_vocab("message_records_error") . grr_sql_error());
			}
		}

	} else	{
		$msg = "Demande non trouvée !";
	}


}




// Si pas de problème, message de confirmation
if (isset($choix)) {
    $_SESSION['displ_msg'] = 'yes';
    if ($msg == '') {
        $d['enregistrement'] = 1;
    } else{
        $d['enregistrement'] = $msg;
    }
}


get_vocab_admin('admin_user');
get_vocab_admin('message_records');

get_vocab_admin("login_name");
get_vocab_admin("mail_user");
get_vocab_admin("names");
get_vocab_admin("privileges");
get_vocab_admin("statut");
get_vocab_admin("activ_user");
get_vocab_admin("authentification");
get_vocab_admin("action");

get_vocab_admin("confirm_del");
get_vocab_admin("cancel");
get_vocab_admin("delete");

$structureLogin = Settings::get('fct_crea_cpt_login');

// Affichage du tableau
$sql = "SELECT idutilisateursdemandes, nom, prenom, email, telephone, commentaire, datedemande FROM ".TABLE_PREFIX."_utilisateurs_demandes WHERE etat = '0' ORDER BY nom,prenom";
$res = grr_sql_query($sql);
if ($res)
{
	for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
	{
		$user_nom = htmlspecialchars($row[1]);
		$user_prenom = htmlspecialchars($row[2]);
		$user_mail = $row[3];
		$user_telephone = $row[4];
		$user_commentaire = $row[5];
		$user_datedemande = $row[6];

		if($structureLogin == 1)
			$loginPredefinis = strtoupper(remplacer_accents($user_nom));
		elseif($structureLogin == 2)
			$loginPredefinis = strtoupper(remplacer_accents($user_nom.".".$user_prenom));
		elseif($structureLogin == 3)
			$loginPredefinis = strtoupper(remplacer_accents($user_nom."".$user_prenom));
		elseif($structureLogin == 4)
			$loginPredefinis = strtoupper(remplacer_accents($user_prenom));
		elseif($structureLogin == 5)
			$loginPredefinis = strtoupper(remplacer_accents($user_prenom[0].".".$user_nom));
		elseif($structureLogin == 6)
			$loginPredefinis = strtoupper(remplacer_accents($user_prenom[0].".".$user_nom));
		elseif($structureLogin == 7)
			$loginPredefinis = strtoupper(remplacer_accents(substr($user_prenom, 0, 2).".".$user_nom));
		elseif($structureLogin == 8)
			$loginPredefinis = strtoupper(remplacer_accents($user_prenom.".".$user_nom));
		elseif($structureLogin == 9)
			$loginPredefinis = strtoupper(remplacer_accents($user_prenom."".$user_nom));
		else
			$loginPredefinis = strtoupper(remplacer_accents($user_nom.".".$user_prenom));

		$col[$i][0] = $row[0];
		$col[$i][1] = $loginPredefinis;
		$col[$i][2] = $user_nom;
		$col[$i][3] = $user_prenom;
		$col[$i][4] = $user_mail;
		$col[$i][5] = $user_telephone;
		$col[$i][6] = $user_commentaire;
		$col[$i][7] = $user_datedemande;
		$col[$i][8] = "choix profil";

	}
}



echo $twig->render($page.'.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings, 'utilisateurs' => $col));

?>