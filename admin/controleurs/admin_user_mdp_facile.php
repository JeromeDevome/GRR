<?php
/**
 * admin_user_mdp_facile.php
 * interface de gestion des utilisateurs de l'application GRR
 * Dernière modification : $Date: 2017-12-16 14:00$
 * @author    JeromeB & Yan Naessens
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


$grr_script_name = "admin_user_mdp_facile.php";


$msg = '';
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
		// Valeurs 1- Mot de passe = login en majuscule || 2- Mot de passe = login en minuscule || 3- azerty  || 4- Vide || 6- 123456  || 7- 1234567 || 8- 12345678 || 9- 000000 || 10- 00000000 
		$mdpFacile = array(md5(strtoupper($row[3])), md5(strtolower($row[3])), "ab4f63f9ac65152575886860dde480a1", "", "e10adc3949ba59abbe56e057f20f883e", "fcea920f7412b5da7be0cf42b8c93759", "25d55ad283aa400af464c76d713c07ad", "670b14728ad9902aecba32e22fa4f6bd", "dd4b21e9ef71e1291183a46b913ae6f2");

		if(in_array($row[6], $mdpFacile)){

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
?>