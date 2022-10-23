<?php
/**
 * mesconnexions.php
 * Interface permettant à l'utilisateur de gérer son compte dans l'application GRR
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2020-14-20 17:20$
 * @author    JeromeB
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

$grr_script_name = 'mesconnexions.php';
if (!Settings::load())
	die('Erreur chargement settings');
$desactive_VerifNomPrenomUser='y';
if (!grr_resumeSession())
{
	header('Location: logout.php?auto=1&url=$url');
	die();
};

include_once('../include/language.inc.php');

$back = '';
if (isset($_SERVER['HTTP_REFERER']))
	$back = htmlspecialchars($_SERVER['HTTP_REFERER']);
$user_login = isset($_POST['user_login']) ? $_POST['user_login'] : ($user_login = isset($_GET['user_login']) ? $_GET['user_login'] : NULL);
$valid = isset($_POST['valid']) ? $_POST['valid'] : NULL;
$msg = '';


// on commence par récupérer les données de connexion
$sql = "SELECT START, SESSION_ID, REMOTE_ADDR, USER_AGENT, REFERER, AUTOCLOSE, END FROM ".TABLE_PREFIX."_log WHERE LOGIN = '".getUserName()."' ORDER by START desc";
$res = grr_sql_query($sql);
if (!$res){
	grr_sql_error();
}
else {

	$connexions = array();

	// affichage des résultats
	$now = time();
	for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
	{
		$annee = substr($row[6],0,4);
		$mois =  substr($row[6],5,2);
		$jour =  substr($row[6],8,2);
		$heures = substr($row[6],11,2);
		$minutes = substr($row[6],14,2);
		$secondes = substr($row[6],17,2);
		$end_time = mktime($heures, $minutes, $secondes, $mois, $jour, $annee);

		if ($end_time > $now)
			$couleur = 'green';
		else if ($row[5])
			$couleur = 'red';
		else
			$couleur = 'black';

		$connexions[] = array('debut' => $row[0], 'fin' => $row[6], 'ip' => $row[2], 'navigateur' => $row[3], 'provenance' => $row[4], 'couleur' => $couleur);
	}
}

get_vocab_admin('see_connexions_explain');

get_vocab_admin('begining_of_session');
get_vocab_admin('end_of_session');
get_vocab_admin('ip_adress');
get_vocab_admin('navigator');
get_vocab_admin('referer');


	echo $twig->render('mesconnexions.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings, 'connexions' => $connexions));
?>