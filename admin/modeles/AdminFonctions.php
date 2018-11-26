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


}

?>