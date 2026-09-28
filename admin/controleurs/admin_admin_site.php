<?php
/**
 * admin_admin_site.php
 * Interface de gestion des administrateurs de sites de l'application GRR
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2026-09-28 20:20$
 * @author    Laurent Delineau & JeromeB
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

$grr_script_name = "admin_admin_site.php";

// Accès à la page
SecuAccess::CheckAccess(6, $back);

if (Settings::get("module_multisite") != 1)
{
	showAccessDenied($back);
	exit();
}

// les variables attendues et leur type
$form_vars = array(
    'p_action' => array('int', 0), // 1 : mise a jour utilisateurs, 2 : mise à jour groupes
    'p_id_site' => array('int', -1),
	'p_utilisateurs' => array('array', array()), // tableau des utilisateurs sélectionnés
	'p_groupes' => array('array', array()), // tableau des groupes sélectionnés
);
// récupération des valeurs des variables passées en paramètres
foreach($form_vars as $var => $params)
    $$var = SecuChaine::GetFormVarSecure($var, $params[0], $params[1]);


/** Actions **/
	if($p_action == 1 && $p_id_site != -1) // Mise à jour des utilisateurs
	{
		// On efface tout les utilisateurs ayant les droits sur le site avant de les remettres
		$sql = "DELETE FROM ".TABLE_PREFIX."_j_useradmin_site WHERE id_site = '$p_id_site'";
		if (grr_sql_command($sql) < 0)
			fatal_error(1, "<p>" . grr_sql_error());
		else
		{
			$selectedUsers = array_unique(array_map(array('SecuChaine', 'CleanLogin'), $p_utilisateurs));

			foreach ($selectedUsers as $selectedUser)
			{
				$sql = "SELECT * FROM ".TABLE_PREFIX."_j_useradmin_site WHERE (login = '".$selectedUser."' and id_site = '$p_id_site')";
				$res = grr_sql_query($sql);
				$test = grr_sql_count($res);
				if ($test == 0)
				{
					if ($selectedUser != '')
					{
						$sql = "INSERT INTO ".TABLE_PREFIX."_j_useradmin_site SET login= '$selectedUser', id_site = '$p_id_site'";
						if (grr_sql_command($sql) < 0)
							fatal_error(1, "<p>" . grr_sql_error());
						else
						{
							$d['enregistrement'] = 1;
							$d['msgToast'] = get_vocab("add_multi_user_succeed");
						}
					}
				}
			}
		}

	}
	elseif($p_action == 2 && $p_id_site != -1) // Mise à jour des groupes
	{
		// TODO: Fonctionnalité à implémenter
	}


/** Affichage de la page **/
	$trad['TitrePage'] = $trad['admin_admin_site'];
	$d['idSite'] = $p_id_site;

	$utilisateursAdmin = array ();
	$utilisateursAjoutable = array ();
	$sites = array ();

	// Liste des sites
	$sql = "SELECT id, sitename FROM ".TABLE_PREFIX."_site ORDER BY sitename";
	$res = grr_sql_query($sql);
	if ($res)
	{
		for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
		{
			$sites[] = array('id' => $row[0], 'nom' => $row[1]);
		}
	}

	if ($p_id_site != -1)
	{
		// Liste des utilisateurs admin du site
		$sql = "SELECT login, nom, prenom FROM ".TABLE_PREFIX."_utilisateurs WHERE (statut='utilisateur' OR statut='gestionnaire_utilisateur')";
		$res = grr_sql_query($sql);
		if ($res)
		{
			for ($i = 0; ($row2 = grr_sql_row($res, $i)); $i++)
			{
				$sql3 = "SELECT login FROM ".TABLE_PREFIX."_j_useradmin_site WHERE (id_site='".$p_id_site."' AND login='".$row2[0]."')";
				$res3 = grr_sql_query($sql3);
				$nombre = grr_sql_count($res3);
				if ($nombre != 0)
					$utilisateursAdmin[] = array('login' => $row2[0], 'nom' => $row2[1], 'prenom' => $row2[2]);
			}
		}

		// Liste des utilisateurs pouvant être ajouté
		$sql = "SELECT login, nom, prenom FROM ".TABLE_PREFIX."_utilisateurs WHERE  (etat!='inactif' AND (statut='utilisateur' OR statut='gestionnaire_utilisateur')) ORDER BY nom, prenom";
		$res = grr_sql_query($sql);
		if ($res)
			for ($i = 0; ($row3 = grr_sql_row($res, $i)); $i++)
				$utilisateursAjoutable[] = array('login' => $row3[0], 'nom' => $row3[1], 'prenom' => $row3[2]);
	}

	echo $twig->render('admin_admin_site.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings, 'sites' => $sites, 'utilisateursadmin' => $utilisateursAdmin, 'utilisateursajoutable' => $utilisateursAjoutable));
?>