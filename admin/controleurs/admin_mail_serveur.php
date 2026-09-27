<?php
/**
 * admin_mail_serveur.php
 * Interface permettant à l'administrateur la configuration de certains paramètres généraux
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2026-09-27 15:00$
 * @author    JeromeB
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

$grr_script_name = 'admin_mail_serveur.php';

// Accès à la page
SecuAccess::CheckAccess(6, $back);

// les variables attendues et leur type
$form_vars = array(
    'p_submit' => array('int', 0),
    'p_automatic_mail' => array('int', 0),
    'p_mail_serveur_from' => array('string', ''),
    'p_grr_mail_method' => array('alphanumeric', 'bloque'),
    'p_grr_mail_smtp' => array('string', ''),
    'p_grr_mail_Username' => array('string', ''),
    'p_grr_mail_Password' => array('string', ''),
    'p_grr_mail_from' => array('string', ''),
    'p_grr_mail_fromname' => array('string', ''),
    'p_smtp_secure' => array('alphanumeric', ''),
    'p_smtp_port' => array('int', 0),
    'p_smtp_allow_self_signed' => array('int', 0),
    'p_smtp_cafile' => array('string', ''),
    'p_smtp_verify_peer_name' => array('int', 1),
    'p_smtp_verify_peer' => array('int', 1),
    'p_smtp_verify_depth' => array('int', 3),
    'p_grr_mail_Bcc' => array('int', 0),
    'p_log_mail' => array('int', 1),
    'p_mail_test' => array('string', '')
);
// récupération des valeurs des variables passées en paramètres
foreach($form_vars as $var => $params)
    $$var = SecuChaine::GetFormVarSecure($var, $params[0], $params[1]);


/** Enregistrement **/
	if($p_submit == 1)
	{
		$settings_results[] = Settings::set2("automatic_mail", $p_automatic_mail);
		$settings_results[] = Settings::set2("mail_serveur_from", $p_mail_serveur_from);
		$settings_results[] = Settings::set2("grr_mail_method", $p_grr_mail_method);
		$settings_results[] = Settings::set2("grr_mail_smtp", $p_grr_mail_smtp);
		$settings_results[] = Settings::set2("grr_mail_Username", $p_grr_mail_Username);
		$settings_results[] = Settings::set2("grr_mail_Password", $p_grr_mail_Password);
		$settings_results[] = Settings::set2("grr_mail_from", $p_grr_mail_from);
		$settings_results[] = Settings::set2("grr_mail_fromname", $p_grr_mail_fromname);
		$settings_results[] = Settings::set2("smtp_secure", $p_smtp_secure);
		$settings_results[] = Settings::set2("smtp_port", $p_smtp_port);
		$settings_results[] = Settings::set2("smtp_allow_self_signed", $p_smtp_allow_self_signed);
		$settings_results[] = Settings::set2("smtp_cafile", $p_smtp_cafile);
		$settings_results[] = Settings::set2("smtp_verify_peer_name", $p_smtp_verify_peer_name);
		$settings_results[] = Settings::set2("smtp_verify_peer", $p_smtp_verify_peer);
		$settings_results[] = Settings::set2("smtp_verify_depth", $p_smtp_verify_depth);
		$settings_results[] = Settings::set2("grr_mail_Bcc", $p_grr_mail_Bcc);
		$settings_results[] = Settings::set2("log_mail", $p_log_mail);
	}


	if($p_mail_test != "")
	{
		require_once '../include/pages.class.php';
		require_once '../include/mail.class.php';
		if (!Pages::load())
			die('Erreur chargement pages');
		
		$templateMail = Pages::get('mails_test_'.$locale);
		$codes = ['%nomdusite%' => Settings::get('title_home_page'), '%nometablissement%' => Settings::get('company'),'%urlgrr%' =>  traite_grr_url("","y")];
		$sujetMail = str_replace(array_keys($codes), $codes, $templateMail[0]);
		$txtMail = str_replace(array_keys($codes), $codes, $templateMail[1]);
		
		$resultat_mail = Email::Envois($p_mail_test, $sujetMail, $txtMail, Settings::get('grr_mail_from'), '', '', '', 'mails_test_'.$locale);
		if (!$resultat_mail['success']) {
			$d['message'] .= "Erreur envoi mail de test: " . htmlspecialchars($resultat_mail['error']) . "<br />";
		} else {
			$d['message'] .= "Mail de test envoyé avec succès<br />";
		}
	}

/** Résultat de l'enregistrement **/
	if ($p_submit == 1){
		$d['settings_results'] = $settings_results;
	}

/** Affichage de la page **/
	$AllSettings = Settings::getAll();
	$d['gMailExpediteur'] = $gMailExpediteur;
	$d['fctMailRestriction'] = $fonction_mail_restrictions;

	echo $twig->render($page.'.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings));

?>