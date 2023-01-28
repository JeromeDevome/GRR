<?php
/**
 * AdminFonctions.php
 * Fonctions Général de l'administration
 * Dernière modification : $Date: 2022-02-01 11:54$
 * @author    JeromeB & Yan Naessens
 * @copyright Copyright 2003-2022 Team DEVOME - JeromeB
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
/*		$sql = "SELECT nom, prenom, login, etat, password FROM ".TABLE_PREFIX."_utilisateurs WHERE source = 'local'";
		$res = grr_sql_query($sql);

		if ($res)
		{
			$nb_facile = 0;
			for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
			{
				// Tableau définit dans config.inc.php : $mdpFacile . On y ajoute les varibales en liason avec l'utilisateur
				$mdpFacile[] = md5($row[2]);
				$mdpFacile[] = md5(strtoupper($row[2]));
				$mdpFacile[] = md5(strtolower($row[2]));
				$mdpFacile[] = password_hash($row[2],PASSWORD_DEFAULT);
				$mdpFacile[] = password_hash(strtoupper($row[2]),PASSWORD_DEFAULT);
				$mdpFacile[] = password_hash(strtolower($row[2]),PASSWORD_DEFAULT);

				if(in_array($row[4], $mdpFacile))
					$nb_facile++;
			}
		}

		return $nb_facile;*/
		return "Indisponible";
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

		if ( (time() - 2592000) > Settings::get("backup_date") ){
			$type = "warning";
			$MessageWarning = "La dernière sauvegarde de la BDD date de plus d'un mois !";
			$NomLien = "Faire une sauvegarde";
			$lien = "admin_save_mysql.php?flag_connect=yes";
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

    /**
     * Fonction : ReservationsAModerer($user) 
     * Description : si c'est un admin ou un gestionnaire de ressource qui est connecté, retourne un tableau contenant le nombre de réservations à modérer et un sous-tableau contenant, pour chaque réservation à modérer, [id,room_id,start_time,create_by,beneficiaire]
    */
    public static function ReservationsAModerer($user)
    {   
        global $dformat;
        $listeModeration = array();
        $sql = "";
        if (authGetUserLevel($user,-1) > 5) // admin général
        {
            $sql = "SELECT e.id,r.room_name,e.start_time,create_by,beneficiaire FROM ".TABLE_PREFIX."_entry e JOIN ".TABLE_PREFIX."_room r ON e.room_id = r.id WHERE e.moderate = 1 AND e.supprimer = 0";
        }
        elseif (isset($_GET['id_site']) && (authGetUserLevel($user,$_GET['id_site'],'site') > 4)) // admin du site
        {
            $sql = "SELECT e.id,r.room_name,e.start_time,create_by,beneficiaire FROM ".TABLE_PREFIX."_entry e JOIN ".TABLE_PREFIX."_room r ON e.room_id = r.id JOIN ".TABLE_PREFIX."_j_site_area j ON r.area_id = j.id_area WHERE (j.id_site = ".protect_data_sql($_GET['id_site'])." AND e.moderate = 1 AND e.supprimer = 0)";
        }
        elseif (isset($_GET['area']) && (authGetUserLevel($user,$_GET['area'],'area') > 3)) // admin du domaine
        {
            $sql = "SELECT e.id,r.room_name,e.start_time,create_by,beneficiaire FROM ".TABLE_PREFIX."_entry e JOIN ".TABLE_PREFIX."_room r ON e.room_id = r.id JOIN ".TABLE_PREFIX."_area a ON r.area_id = a.id WHERE (a.id = ".protect_data_sql($_GET['area'])." AND e.moderate = 1 AND e.supprimer = 0)";
        }
        elseif (isset($_GET['room']) && (authGetUserLevel($user,$_GET['room'],'room') > 2)) // gestionnaire de la ressource
        {
            $sql = "SELECT e.id,r.room_name,e.start_time,create_by,beneficiaire FROM ".TABLE_PREFIX."_entry e JOIN ".TABLE_PREFIX."_room r ON e.room_id = r.id WHERE (e.room_id = ".protect_data_sql($_GET['room'])." AND e.moderate = 1  AND e.supprimer = 0 ) ";
        }
        if ($sql != ""){
            $res = grr_sql_query($sql);
            if ($res)
            {
                foreach($res as $row) 
                {
                    $link = "../view_entry.php?id=".$row['id']."&mode=page";
                    $listeModeration[] = array('ressource' => $row['room_name'], 'debut' => time_date_string($row['start_time'], $dformat), 'createur' => $row['create_by'], 'beneficiaire' => $row['beneficiaire'], 'lien' => $link );
                }
            }
        }
        $nbAModerer = count($listeModeration);
        return array($nbAModerer, $listeModeration);
    }

}

?>