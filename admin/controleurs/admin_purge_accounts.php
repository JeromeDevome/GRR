<?php
/**
 * admin_purge_accounts.php
 * interface de purge des comptes et réservations
 * Dernière modification : $Date: 2017-12-16 14:00$
 * @author    JeromeB & Laurent Delineau & Christian Daviau
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

$grr_script_name = "admin_purge_accounts.php";

include('./modeles/admin_purge_accounts.php');

$display = isset($_GET["display"]) ? $_GET["display"] : NULL;
$order_by = isset($_GET["order_by"]) ? $_GET["order_by"] : NULL;

if (((Settings::get("ldap_statut") == "") && (Settings::get("sso_statut") == "") && (Settings::get("imap_statut") == "")) || (authGetUserLevel(getUserName(), -1) < 6) && (authGetUserLevel(getUserName(), -1,'user') !=  1))
{
	showAccessDenied($back);
	exit();
}


get_vocab_admin('admin_purge_accounts');
get_vocab_admin('admin_backup_recommande');
get_vocab_admin('admin_clean_accounts_desc');
get_vocab_admin('admin_purge_tables_liaison');
get_vocab_admin('admin_purge_accounts_desc');
get_vocab_admin('admin_purge_accounts_sauf_privileges');
get_vocab_admin('admin_purge_accounts_with_bookings');

get_vocab_admin('admin_purge_accounts_confirm');
get_vocab_admin('admin_purge_accounts_confirm2');
get_vocab_admin('admin_purge_tables_confirm');
get_vocab_admin('admin_purge_accounts_confirm4');


	if (isset($_POST['do_purge_table_liaison']))
	{
		if ($_POST['do_purge_table_liaison'] == 1)
		{
			$trad['dNettoyageLiaison'] = PurgeComptes::NettoyerTablesJointure();
		}
	}
	if (isset($_POST['do_purge_sauf_privileges']))
	{
		if ($_POST['do_purge_sauf_privileges'] == 1)
		{
			$trad['dNettoyageLiaison'] = PurgeComptes::supprimerReservationsUtilisateursEXT("n","n");
		}
	}
	if (isset($_POST['do_purge']))
	{
		if ($_POST['do_purge'] == 1)
		{
			$trad['dNettoyageLiaison'] = PurgeComptes::supprimerReservationsUtilisateursEXT("n","y");
		}
	}
	if (isset($_POST['do_purge_avec_resa']))
	{
		if ($_POST['do_purge_avec_resa'] == 1)
		{
			$trad['dNettoyageLiaison'] = PurgeComptes::supprimerReservationsUtilisateursEXT("y","y");
		}
	}

	echo $twig->render('admin_purge_accounts.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings));
?>