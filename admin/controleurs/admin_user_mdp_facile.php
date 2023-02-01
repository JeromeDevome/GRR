<?php
/**
 * admin_user_mdp_facile.php
 * interface de gestion des utilisateurs de l'application GRR
 * Dernière modification : $Date: 2023-02-01 10:01$
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
                $affiche = ($display == 'tous');
                $affiche = $affiche || (($display == 'actifs')&&($row['etat'] == 'actif')) || (($display == 'inactifs')&&($row['etat'] != 'actif'));
                if($affiche)
                {
                    $data = array();
                    $data[1]=$row['login'];
                    $data[2]=htmlspecialchars($row['nom'])." ".htmlspecialchars($row['prenom']);
                    // Affichage du statut
                    if ($row['statut'] == "administrateur")
                    {
                        $data[4] = get_vocab("statut_administrator");
                    }
                    if ($row['statut'] == "visiteur")
                    {
                        $data[4] = get_vocab("statut_visitor");
                    }
                    if ($row['statut'] == "utilisateur")
                    {
                        $data[4] = get_vocab("statut_user");
                    }
                    if ($row['statut'] == "gestionnaire_utilisateur")
                    {
                        $data[4] = get_vocab("statut_user_administrator");
                    }
                    $data[6] = ($row['etat'] == 'actif')? "Actif": "Inactif";
                    $col[] = $data;
                }
            }
        }
	}
}

// Affichage d'un pop-up
affiche_pop_up($msg,"admin");

echo $twig->render($page.'.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings, 'utilisateurs' => $col));
?>