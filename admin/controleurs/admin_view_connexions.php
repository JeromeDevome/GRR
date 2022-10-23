<?php
/**
 * admin_view_connexions.php
 * Interface de gestion des connexions
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2018-08-17 11:30$
 * @author    Laurent Delineau & JeromeB
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

$grr_script_name = "admin_view_connexions.php";

check_access(6, $back);

// Action : Suppression des logs
if (isset($_POST['cleanlog']))
{
	$dateMax = date( 'Y-m-d', strtotime( $_POST['cleanlog'] ) );
	$sql = "DELETE FROM ".TABLE_PREFIX."_log WHERE START < '".$dateMax."' and END < now()";
	$res = grr_sql_query($sql);
}

// Action : Déconnecter un utilisateur
if (isset($_GET['user_login']))
{
	$datefin = date("Y-m-d H:i:s");
	$sql = "UPDATE ".TABLE_PREFIX."_log SET END = '".$datefin."' WHERE LOGIN = '".$_GET['user_login']."'";
	$res = grr_sql_query($sql);
}

get_vocab_admin('admin_view_connexions');

get_vocab_admin('users_connected');
get_vocab_admin('login_name');
get_vocab_admin('names');
get_vocab_admin('sen_a_mail');
get_vocab_admin('action');
get_vocab_admin('disconnect2');

$utilisateurConnecte = array();

// Afficher : Utilisateurs connecté
$sql = "SELECT u.login, concat(u.prenom, ' ', u.nom) utilisa, u.email, u.source FROM ".TABLE_PREFIX."_log l, ".TABLE_PREFIX."_utilisateurs u WHERE (l.LOGIN = u.login and l.END > now())";
$res = grr_sql_query($sql);
if ($res)
{
	for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
	{
		if ( $row[3] != 'local' )
			$deconnexionPossible = 0;
		else
			$deconnexionPossible = 1;

		$utilisateurConnecte[] = array('login' => $row[0], 'nomprenom' => $row[1], 'email' => $row[2], 'deconnexion' => $deconnexionPossible );
	}
}

// Afficher : Logs
get_vocab_admin('msg_explain_log');
get_vocab_admin('login_name');
get_vocab_admin('names');
get_vocab_admin('begining_of_session');
get_vocab_admin('end_of_session');
get_vocab_admin('ip_adress');
get_vocab_admin('navigator');
get_vocab_admin('referer');

get_vocab_admin('users_connected');

$sql = "SELECT u.login, concat(prenom, ' ', nom) utili, l.START, l.SESSION_ID, l.REMOTE_ADDR, l.USER_AGENT, l.REFERER, l.AUTOCLOSE, l.END, u.email FROM ".TABLE_PREFIX."_log l, ".TABLE_PREFIX."_utilisateurs u WHERE l.LOGIN = u.login ORDER by START desc";
$res = grr_sql_query($sql);

$logsConnexion = array ();

if ($res)
{
	for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
	{
		if (strtotime($row[8]) > time())
			$clos = 0;
		else
			$clos = 1;

		$logsConnexion[] = array('login' => $row[0], 'nomprenom' => $row[1], 'debut' => $row[2], 'fin' => $row[8], 'ip' => $row[4], 'navigateur' => $row[5], 'provenance' => $row[6], 'clos' => $clos );
	}
}

// Afficher : Sup logs
get_vocab_admin("cleaning_log");
get_vocab_admin("logs_number");
get_vocab_admin("older_date_log");
get_vocab_admin("erase_log");
get_vocab_admin("delete_up_to");
get_vocab_admin("del");

$sql = "select START from ".TABLE_PREFIX."_log order by END";
$res = grr_sql_query($sql);

if($res) {
	$trad['dNombreLog'] = grr_sql_count($res);
	$row = grr_sql_row($res, 0);
	$annee = substr($row[0],0,4);
	$mois =  substr($row[0],5,2);
	$jour =  substr($row[0],8,2);
	$trad['dDatePlusAncienne'] = $jour."/".$mois."/".$annee;
} else{
	$trad['dNombreLog'] = 0;
	$trad['dDatePlusAncienne'] = "-";
}

$trad['dTitreDateLog'] = get_vocab("log").$trad['dDatePlusAncienne'];

echo $twig->render('admin_view_connexions.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings, 'utilisateursconnecte' => $utilisateurConnecte, 'logsconnexion' => $logsConnexion ));
?>