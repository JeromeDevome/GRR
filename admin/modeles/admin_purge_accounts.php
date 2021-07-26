<?php
/**
 * admin_purge_accounts.php
 * interface de purge des comptes et réservations
 * Dernière modification : $Date: 2018-05-22 15:00$
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

class PurgeComptes
{

	public static function NettoyerTablesJointure() //  Supprime les lignes inutiles dans les tables de liaison
	{
		$nb = 0;
		// Table grr_j_mailuser_room
		$req = "SELECT j.login FROM ".TABLE_PREFIX."_j_mailuser_room j
		LEFT JOIN ".TABLE_PREFIX."_utilisateurs u on u.login=j.login
		WHERE (u.login  IS NULL)";
		$res = grr_sql_query($req);
		if ($res)
		{
			for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
			{
				$nb++;
				grr_sql_command("delete from ".TABLE_PREFIX."_j_mailuser_room where login='".$row[0]."'");
			}
		}
		// Table grr_j_user_area
		$req = "SELECT j.login FROM ".TABLE_PREFIX."_j_user_area j
		LEFT JOIN ".TABLE_PREFIX."_utilisateurs u on u.login=j.login
		WHERE (u.login  IS NULL)";
		$res = grr_sql_query($req);
		if ($res)
		{
			for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
			{
				$nb++;
				grr_sql_command("delete from ".TABLE_PREFIX."_j_user_area where login='".$row[0]."'");
			}
		}
		// Table grr_j_user_room
		$req = "SELECT j.login FROM ".TABLE_PREFIX."_j_user_room j
		LEFT JOIN ".TABLE_PREFIX."_utilisateurs u on u.login=j.login
		WHERE (u.login  IS NULL)";
		$res = grr_sql_query($req);
		if ($res)
		{
			for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
			{
				$nb++;
				grr_sql_command("DELETE FROM ".TABLE_PREFIX."_j_user_room WHERE login='".$row[0]."'");
			}
		}
		// Table grr_j_useradmin_area
		$req = "SELECT j.login FROM ".TABLE_PREFIX."_j_useradmin_area j
		LEFT JOIN ".TABLE_PREFIX."_utilisateurs u on u.login=j.login
		WHERE (u.login  IS NULL)";
		$res = grr_sql_query($req);
		if ($res)
		{
			for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
			{
				$nb++;
				grr_sql_command("DELETE FROM ".TABLE_PREFIX."_j_useradmin_area WHERE login='".$row[0]."'");
			}
		}
		// Table grr_j_useradmin_site
		$req = "SELECT j.login FROM ".TABLE_PREFIX."_j_useradmin_site j
		LEFT JOIN ".TABLE_PREFIX."_utilisateurs u on u.login=j.login
		WHERE (u.login  IS NULL)";
		$res = grr_sql_query($req);
		if ($res)
		{
			for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
			{
				$nb++;
				grr_sql_command("DELETE FROM ".TABLE_PREFIX."_j_useradmin_site WHERE login='".$row[0]."'");
			}
		}
		// Suppression effective
		$dNettoyageLiaison = get_vocab("tables_liaison").get_vocab("deux_points").$nb.get_vocab("entres_supprimees");

		return $dNettoyageLiaison;
	}


	public static function supprimerReservationsUtilisateursEXT($avec_resa,$avec_privileges) // Supprime les réservations des membres qui proviennent d'une source "EXT"
	{

		// Récupération de tous les utilisateurs de la source EXT
		$requete_users_ext = "SELECT login FROM ".TABLE_PREFIX."_utilisateurs WHERE source='ext' and statut<>'administrateur'";
		$res = grr_sql_query($requete_users_ext);
		$logins = array();
		$logins_liaison  = array();
		if ($res)
		{
			for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
			{
				$logins[]=$row[0];
			}
		}
		// Construction des requêtes de suppression à partir des différents utilisateurs à supprimer
		if ($avec_resa == 'y')
		{
			// Pour chaque utilisateur, on supprime les réservations qu'il a créées et celles dont il est bénéficiaire
			// Table grr_entry
			$req_suppr_table_entry = "DELETE FROM ".TABLE_PREFIX."_entry WHERE create_by = ";
			$first = 1;
			foreach ($logins as $log)
			{
				if ($first == 1)
				{
					$req_suppr_table_entry .= "'$log' OR beneficiaire='$log'";
					$first = 0;
				}
				else
					$req_suppr_table_entry .= " OR create_by = '$log' OR beneficiaire = '$log' ";
			}
			// Pour chaque utilisateur, on supprime les réservations périodiques qu'il a créées et celles dont il est bénéficiaire
			// Table grr_repeat
			$req_suppr_table_repeat = "DELETE FROM ".TABLE_PREFIX."_repeat WHERE create_by = ";
			$first = 1;
			foreach ($logins as $log)
			{
				if ($first == 1)
				{
					$req_suppr_table_repeat .= "'$log' OR beneficiaire='$log'";
					$first = 0;
				}
				else
					$req_suppr_table_repeat .= " OR create_by = '$log' OR beneficiaire = '$log' ";
			}
			// Pour chaque utilisateur, on supprime les réservations périodiques qu'il a créées et celles dont il est bénéficiaire
			// Table grr_entry_moderate
			$req_suppr_table_entry_moderate = "DELETE FROM ".TABLE_PREFIX."_entry_moderate WHERE create_by = ";
			$first = 1;
			foreach ($logins as $log)
			{
				if ($first == 1)
				{
					$req_suppr_table_entry_moderate .= "'$log' OR beneficiaire='$log'";
					$first = 0;
				}
				else
					$req_suppr_table_entry_moderate .= " OR create_by = '$log' OR beneficiaire = '$log' ";
			}
		}
		$req_j_mailuser_room = "";
		$req_j_user_area = "";
		$req_j_user_room = "";
		$req_j_useradmin_area = "";
		$req_j_useradmin_site = "";
		foreach ($logins as $log)
		{
			// Table grr_j_mailuser_room
			$test = grr_sql_query1("SELECT count(login) FROM ".TABLE_PREFIX."_j_mailuser_room WHERE login='".$log."'");
			if ($test >=1)
			{
				if ($avec_privileges == "y")
				{
					if ($req_j_mailuser_room == "")
						$req_j_mailuser_room = "DELETE FROM ".TABLE_PREFIX."_j_mailuser_room WHERE login='".$log."'";
					else
						$req_j_mailuser_room .= " OR login = '".$log."'";
				}
				else
					$logins_liaison[] = strtolower($log);
			}
			// Table grr_j_user_area
			$test = grr_sql_query1("SELECT count(login) FROM ".TABLE_PREFIX."_j_user_area WHERE login='".$log."'");
			if ($test >=1)
			{
				if ($avec_privileges == "y")
				{
					if ($req_j_user_area == "")
						$req_j_user_area = "DELETE FROM ".TABLE_PREFIX."_j_user_area WHERE login='".$log."'";
					else
						$req_j_user_area .= " OR login = '".$log."'";
				}
				else
					$logins_liaison[] = strtolower($log);
			}
			// Table grr_j_user_room
			$test = grr_sql_query1("SELECT count(login) FROM ".TABLE_PREFIX."_j_user_room WHERE login='".$log."'");
			if ($test >= 1)
			{
				if ($avec_privileges == "y")
				{
					if ($req_j_user_room == "")
						$req_j_user_room = "DELETE FROM ".TABLE_PREFIX."_j_user_room WHERE login='".$log."'";
					else
						$req_j_user_room .= " OR login = '".$log."'";
				}
				else
					$logins_liaison[] = strtolower($log);
			}
			// Table grr_j_useradmin_area
			$test = grr_sql_query1("SELECT count(login) FROM ".TABLE_PREFIX."_j_useradmin_area WHERE login='".$log."'");
			if ($test >= 1)
			{
				if ($avec_privileges == "y")
				{
					if ($req_j_useradmin_area == "")
						$req_j_useradmin_area = "DELETE FROM ".TABLE_PREFIX."_j_useradmin_area WHERE login='".$log."'";
					else
						$req_j_useradmin_area .= " OR login = '".$log."'";
				}
				else
					$logins_liaison[] = strtolower($log);
			}
			// Table grr_j_useradmin_site
			$test = grr_sql_query1("SELECT count(login) FROM ".TABLE_PREFIX."_j_useradmin_site WHERE login='".$log."'");
			if ($test >= 1)
			{
				if ($avec_privileges == "y")
				{
					if ($req_j_useradmin_site == "")
						$req_j_useradmin_site = "DELETE FROM ".TABLE_PREFIX."_j_useradmin_site WHERE login='".$log."'";
					else
						$req_j_useradmin_site .= " OR login = '".$log."'";
				}
				else
					$logins_liaison[] = strtolower($log);
			}
		}
		
		// Suppression effective
		$dPurgeCompte = '';
		if ($avec_resa == 'y')
		{
			$nb = 0;
			$s = grr_sql_command($req_suppr_table_entry);
			if ($s != -1)
				$nb += $s;
			$s = grr_sql_command($req_suppr_table_repeat);
			if ($s != -1)
				$nb += $s;
			$s = grr_sql_command($req_suppr_table_entry_moderate);
			if ($s != -1)
				$nb += $s;
			$dPurgeCompte .= get_vocab("tables_reservations").get_vocab("deux_points").$nb.get_vocab("entres_supprimees")."<br>";
		}
		$nb = 0;
		if ($avec_privileges == "y")
		{
			if ($req_j_mailuser_room != "")
			{
				$s = grr_sql_command($req_j_mailuser_room);
				if ($s != -1)
					$nb += $s;
			}
			if ($req_j_user_area != "")
			{
				$s = grr_sql_command($req_j_user_area);
				if ($s != -1)
					$nb += $s;
			}
			if ($req_j_user_room != "")
			{
				$s = grr_sql_command($req_j_user_room);
				if ($s != -1)
					$nb += $s;
			}
			if ($req_j_useradmin_area != "")
			{
				$s = grr_sql_command($req_j_useradmin_area);
				if ($s != -1)
					$nb += $s;
			}
			if ($req_j_useradmin_site != "")
			{
				$s = grr_sql_command($req_j_useradmin_site);
				if ($s != -1)
					$nb += $s;
			}
		}
		$dPurgeCompte = get_vocab("tables_liaison").get_vocab("deux_points").$nb.get_vocab("entres_supprimees")."<br>";
		if ($avec_privileges == "y")
		{
			// Enfin, suppression des utilisateurs de la source EXT qui ne sont pas administrateur
			$requete_suppr_users_ext = "DELETE FROM ".TABLE_PREFIX."_utilisateurs WHERE source='ext' and statut<>'administrateur'";
			$s = grr_sql_command($requete_suppr_users_ext);
			if ($s == -1)
				$s = 0;
			$dPurgeCompte = get_vocab("table_utilisateurs").get_vocab("deux_points").$s.get_vocab("entres_supprimees")."<br>";
		}
		else
		{
			$n = 0;
			foreach ($logins as $log)
			{
				if (!in_array(strtolower($log), $logins_liaison))
				{
					grr_sql_command("DELETE FROM ".TABLE_PREFIX."_utilisateurs WHERE login='".$log."'");
					$n++;
				}
			}
			$dPurgeCompte = get_vocab("table_utilisateurs").get_vocab("deux_points").$n.get_vocab("entres_supprimees")."<br>";
		}

		return $dPurgeCompte;
	}


}

?>