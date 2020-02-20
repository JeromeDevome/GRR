<?php
/**
 * AdminFonctions.php
 * Fonctions Général de l'administration
 * Dernière modification : $Date: 2018-11-26 16:00$
 * @author    JeromeB
 * @copyright Copyright 2003-2018 Team DEVOME - JeromeB
 * @link      http://www.gnu.org/licenses/licenses.html
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */

class AdminFonctions
{

	public static function NombreUtilisateurs() // Nombre d'utilisateur enregisté et actif
	{
		$sql = "SELECT login FROM ".TABLE_PREFIX."_utilisateurs WHERE etat = 'actif'";
		$res = grr_sql_query($sql);
		$nb_utilisateur = grr_sql_count($res);
		grr_sql_free($res);

		return $nb_utilisateur;
	}


	public static function NombreDeConnecter() // Nombre d'utilisateur actuellement connecté
	{
		$sql = "SELECT login FROM ".TABLE_PREFIX."_log WHERE end > now()";
		$res = grr_sql_query($sql);
		$nb_connect = grr_sql_count($res);
		grr_sql_free($res);

		return $nb_connect;
	}


	public static function NombreUtilisateursMDPfacile() // Nombre d'utilisateur avec un mot de passe trop simple
	{
		global $mdpFacile;

		// les utilisateurs à identification externe ont un mot de passe vide dans la base GRR, il est inutile de les afficher
		$sql = "SELECT nom, prenom, login, etat, password FROM ".TABLE_PREFIX."_utilisateurs WHERE source = 'local'";
		$res = grr_sql_query($sql);

		if ($res)
		{
			$nb_facile = 0;
			for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
			{
				// Tableau définit dans config.inc.php : $mdpFacile . On y ajoute les varibales en liason avec l'utilisateur
				$mdpFacile[] = md5(strtoupper($row[2])); // Mot de passe = login en majuscule
				$mdpFacile[] = md5(strtolower($row[2])); // Mot de passe = login en minuscule

				if(in_array($row[4], $mdpFacile))
					$nb_facile++;
			}
		}

		return $nb_facile;
	}


	public static function Warning() // Alerte
	{

		$type = "";
		$MessageWarning = "";
		$NomLien = "";
		$lien = "";

		if ( time() < Settings::get("begin_bookings") || time() > Settings::get("end_bookings"))
		{
			$type = "danger";
			$MessageWarning = "Les dates d'ouverture des réservations sont actuellements fermées !";
			$NomLien = "Configurer les dates";
			$lien = "?p=admin_config";
		} elseif( (time() + 2592000) < Settings::get("begin_bookings") || (time() + 2592000) > Settings::get("end_bookings"))
		{
			$type = "warning";
			$MessageWarning = "Les dates d'ouverture des réservations seront prochainement fermées.";
			$NomLien = "Configurer les dates";
			$lien = "?p=admin_config";
		}

		return array($type, $MessageWarning, $NomLien, $lien);
	}


	public static function DernieresConnexion($nbAretouner) // Liste des dernières connexions
	{

		// les X utilisateurs sui ce sont connectés en derniers
		$sql = "SELECT u.login, l.START, l.END FROM ".TABLE_PREFIX."_log l, ".TABLE_PREFIX."_utilisateurs u WHERE l.LOGIN = u.login ORDER by START desc LIMIT ".$nbAretouner;
		$res = grr_sql_query($sql);
		if ($res)
		{
			for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
			{
				if (strtotime($row[2]) > time())
					$clos = 0;
				else
					$clos = 1;

				$logsConnexion[] = array('login' => $row[0], 'debut' => $row[2], 'clos' => $clos );
			}
		}

		return $logsConnexion;
	}


	public static function ReservationsAModerer() // Liste des réservations à modérer
	{
		global $dformat;

		$listeModeration = array();
		$sql = "SELECT r.room_name, e.start_time FROM ".TABLE_PREFIX."_entry e JOIN ".TABLE_PREFIX."_room r ON e.room_id = r.id JOIN ".TABLE_PREFIX."_j_site_area j ON r.area_id = j.id_area WHERE e.moderate = 1";
		$resa_mode = grr_sql_query($sql);
		$nbAModerer = grr_sql_count($resa_mode);
 
		if ($resa_mode)
		{
			for ($i = 0; ($row = grr_sql_row($resa_mode, $i)); $i++)
			{
				$listeModeration[] = array('ressource' => $row[0], 'debut' => time_date_string($row[1], $dformat) );
			}
		}

		return array($nbAModerer, $listeModeration);
	}


}

?>