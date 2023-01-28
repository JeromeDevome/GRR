<?php
/**
 * admin_user_mdp_facile.php
 * interface de gestion des utilisateurs de l'application GRR
 * Dernière modification : $Date: 2017-12-16 14:00$
 * @author    JeromeB & Yan Naessens
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


$grr_script_name = "admin_user_mdp_facile.php";

$msg = '';
$col = array();

if ((authGetUserLevel(getUserName(), -1) < 6) && (authGetUserLevel(getUserName(), -1,'user') != 1))
{
	showAccessDenied($back);
	exit();
}

get_vocab_admin('admin_user_mdp_facile');
get_vocab_admin('admin_user_mdp_facile_description');

get_vocab_admin('login_name');
get_vocab_admin('names');
get_vocab_admin('statut');
get_vocab_admin('authentification');

if (empty($display))
{
	$display = 'actifs';
}
if (empty($order_by))
{
	$order_by = 'nom,prenom';
}


// les utilisateurs à identification externe ont un mot de passe vide dans la base GRR, il est inutile de les afficher
$sql = "SELECT nom, prenom, statut, login, etat, source, password FROM ".TABLE_PREFIX."_utilisateurs WHERE source = 'local' ORDER BY $order_by";
$res = grr_sql_query($sql);
if ($res)
{
	for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
	{

		// Les mdp facile
		// Tableau définit dans config.inc.php : $mdpFacile . On y ajoute les varibales en liason avec l'utilisateur
		// on ajoute à $mdpFacile : login, login en majuscule, login en minuscule
        $mdpPerso = array();
        $mdpPerso[] = md5($row[3]);
		$mdpPerso[] = md5(strtoupper($row[3]));
        $mdpPerso[] = md5(strtolower($row[3]));
        $mdpPerso[] = password_hash($row[3],PASSWORD_DEFAULT);
		$mdpPerso[] = password_hash(strtoupper($row[3]),PASSWORD_DEFAULT);
        $mdpPerso[] = password_hash(strtolower($row[3]),PASSWORD_DEFAULT);
		if(in_array($row[6], $mdpFacile+ $mdpPerso)){

			$user_nom = htmlspecialchars($row[0]);
			$user_prenom = htmlspecialchars($row[1]);
			$user_statut = $row[2];
			$user_login = $row[3];
			$user_etat[$i] = $row[4];
			$user_source = $row[5];
			if (($user_etat[$i] == 'actif') && (($display == 'tous') || ($display == 'actifs')))
				$affiche = 'yes';
			else if (($user_etat[$i] != 'actif') && (($display == 'tous') || ($display == 'inactifs')))
				$affiche = 'yes';
			else
				$affiche = 'no';
			if ($affiche == 'yes')
			{
			// Affichage des login, noms et prénoms
				$col[$i][1] = $user_login;
				$col[$i][2] = "$user_nom $user_prenom";
			// Affichage du statut
				if ($user_statut == "administrateur")
				{
					$col[$i][4] = get_vocab("statut_administrator");
				}
				if ($user_statut == "visiteur")
				{
					$col[$i][4] = get_vocab("statut_visitor");
				}
				if ($user_statut == "utilisateur")
				{
					$col[$i][4] = get_vocab("statut_user");
				}
				if ($user_statut == "gestionnaire_utilisateur")
				{
					$col[$i][4] = get_vocab("statut_user_administrator");
				}
				if ($user_etat[$i] == 'actif')
					$col[$i][6] = 'Actif';
				else
					$col[$i][6] = 'Inactif';
			}

		}
	}
}

// Affichage d'un pop-up
affiche_pop_up($msg,"admin");

echo $twig->render($page.'.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings, 'utilisateurs' => $col));
?>