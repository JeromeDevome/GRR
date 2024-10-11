<?php
/**
 * AdminFonctions.php
 * Fonctions Général de l'administration
 * Dernière modification : $Date: 2023-02-01 16:59$
 * @author    JeromeB & Yan Naessens
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


	public static function NombreUtilisateursMDPfacile() // Nombre d'utilisateurs avec un mot de passe trop simple
	{
		global $mdpFacile;
        $nb_facile = 0;
        // les utilisateurs à identification externe ont un mot de passe vide dans la base GRR, il est inutile de les compter
        $sql = "SELECT nom, prenom, statut, login, etat, source, password FROM ".TABLE_PREFIX."_utilisateurs WHERE source = 'local'";
        $res = grr_sql_query($sql);
        if ($res)
        {
            foreach($res as $row)
            {
                // Les mdp faciles
                // Tableau défini dans include/mdp_faciles.inc.php : $mdpFacile . On y ajoute les variables en liaison avec l'utilisateur
                // on adjoint à $mdpFacile : login, login en majuscule, login en minuscule
                $mdpPerso = array();
                $mdpPerso[] = $row['login'];
                $mdpPerso[] = strtoupper($row['login']);
                $mdpPerso[] = strtolower($row['login']);
                $mdpFaciles = $mdpFacile+ $mdpPerso;
                foreach($mdpFaciles as $mdp)
                {
                    if(checkPassword($mdp, $row['password'], $row['login'], FALSE))// c'est un mot de passe facile
                    {
                        $nb_facile++;
                    }
                }
            }
        }
        return $nb_facile;
	}


	public static function Warning() // Alerte
	{
        global $versionReposite, $version_grr, $warningBackup;

        $alerteTDB = array();

        if ( stristr($version_grr, 'a') || stristr($version_grr, 'b') || stristr($version_grr, 'RC') || stristr($versionReposite, 'github') || stristr($versionReposite, 'alpha')|| stristr($versionReposite, 'beta') || stristr($versionReposite, 'RC') ){
            $alerteTDB[] = array('type' =>"danger", 'MessageWarning' => "Version de développement, ne pas utiliser en production !", 'NomLien' => "Trouver une autre version", 'lien' => "https://github.com/JeromeDevome/GRR/releases");
		}

		if ( time() < Settings::get("begin_bookings") || time() > Settings::get("end_bookings"))
		{
            $alerteTDB[] = array('type' =>"danger", 'MessageWarning' => "Les dates d'ouverture des réservations sont actuellements fermées !", 'NomLien' => "Configurer les dates", 'lien' => "?p=admin_config");
		} elseif( (time() + 2592000) < Settings::get("begin_bookings") || (time() + 2592000) > Settings::get("end_bookings"))
		{
            $alerteTDB[] = array('type' =>"warning", 'MessageWarning' => "Les dates d'ouverture des réservations seront prochainement fermées.", 'NomLien' => "Configurer les dates", 'lien' => "?p=admin_config");
		}

		if ( $warningBackup == 1  && (time() - 2592000) > Settings::get("backup_date") ){
            $alerteTDB[] = array('type' =>"warning", 'MessageWarning' => "La dernière sauvegarde de la BDD date de plus d'un mois !", 'NomLien' => "Faire une sauvegarde", 'lien' => "admin_save_mysql.php?flag_connect=yes");
		}

		return $alerteTDB;
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
            $sql = "SELECT e.id,r.room_name,e.start_time,create_by,beneficiaire FROM ".TABLE_PREFIX."_entry e JOIN ".TABLE_PREFIX."_room r ON e.room_id = r.id WHERE e.moderate = 1 AND e.supprimer = 0 ORDER BY e.start_time ASC";
        }
        elseif (isset($_GET['id_site']) && (authGetUserLevel($user,intval($_GET['id_site']),'site') > 4)) // admin du site
        {
            $sql = "SELECT e.id,r.room_name,e.start_time,create_by,beneficiaire FROM ".TABLE_PREFIX."_entry e JOIN ".TABLE_PREFIX."_room r ON e.room_id = r.id JOIN ".TABLE_PREFIX."_j_site_area j ON r.area_id = j.id_area WHERE (j.id_site = ".protect_data_sql($_GET['id_site'])." AND e.moderate = 1 AND e.supprimer = 0) ORDER BY e.start_time ASC";
        }
        elseif (isset($_GET['area']) && (authGetUserLevel($user,intval($_GET['area']),'area') > 3)) // admin du domaine
        {
            $sql = "SELECT e.id,r.room_name,e.start_time,create_by,beneficiaire FROM ".TABLE_PREFIX."_entry e JOIN ".TABLE_PREFIX."_room r ON e.room_id = r.id JOIN ".TABLE_PREFIX."_area a ON r.area_id = a.id WHERE (a.id = ".protect_data_sql($_GET['area'])." AND e.moderate = 1 AND e.supprimer = 0) ORDER BY e.start_time ASC";
        }
        elseif (isset($_GET['room']) && (authGetUserLevel($user,intval($_GET['room']),'room') > 2)) // gestionnaire de la ressource
        {
            $sql = "SELECT e.id,r.room_name,e.start_time,create_by,beneficiaire FROM ".TABLE_PREFIX."_entry e JOIN ".TABLE_PREFIX."_room r ON e.room_id = r.id WHERE (e.room_id = ".protect_data_sql($_GET['room'])." AND e.moderate = 1  AND e.supprimer = 0 ) ORDER BY e.start_time ASC";
        }

        if ($sql != ""){

            $res = grr_sql_query($sql);
            if ($res)
            {
                foreach($res as $row) 
                {
                    $link = "../app.php?p=vuereservation&id=".$row['id']."&mode=page";
                    $listeModeration[] = array('ressource' => $row['room_name'], 'debut' => time_date_string($row['start_time'], $dformat), 'createur' => $row['create_by'], 'beneficiaire' => $row['beneficiaire'], 'lien' => $link );
                }
            }
        }
        $nbAModerer = count($listeModeration);
        return array($nbAModerer, $listeModeration);
    }

}

?>