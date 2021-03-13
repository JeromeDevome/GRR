<?php
/**
 * admin_user_mdp_facile.php
 * interface de gestion des utilisateurs de l'application GRR
 * Dernière modification : $Date: 2021-03-13 11:34$
 * @author    JeromeB & Yan Naessens
 * @copyright Copyright 2003-2021 Team DEVOME - JeromeB
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

include "../include/admin.inc.php";

$back = (isset($_SERVER['HTTP_REFERER']))? htmlspecialchars_decode($_SERVER['HTTP_REFERER'], ENT_QUOTES) : "./admin_accueil.php" ;
$display = isset($_GET["display"]) ? $_GET["display"] : NULL;
$order_by = isset($_GET["order_by"]) ? $_GET["order_by"] : NULL;
$msg = '';
if ((authGetUserLevel(getUserName(), -1) < 6) && (authGetUserLevel(getUserName(), -1,'user') != 1))
{
	showAccessDenied($back);
	exit();
}
// code HTML
start_page_w_header("", "", "", $type="with_session");
// colonne de gauche
include "admin_col_gauche2.php";
// colonne de droite
echo "<div class='col-md-9 col-sm-8 col-xs-12'>";
echo "<h2>".get_vocab('admin_user_mdp_facile')."</h2>";
echo '<a href="admin_user.php" type="button" class="btn btn-primary">'.get_vocab("back").'</a>';
echo "<p>".get_vocab('admin_user_mdp_facile_description')."</p>";
if (empty($display))
{
	$display = 'actifs';
}
if (empty($order_by))
{
	$order_by = 'nom,prenom';
}
// Affichage du tableau
echo "<table class=\"table table-striped table-bordered\">";
echo "<tr><td><b><a href='admin_user_mdp_facile.php?order_by=login&amp;display=$display'>".get_vocab("login_name")."</a></b></td>";
echo "<td><b><a href='admin_user_mdp_facile.php?order_by=nom,prenom&amp;display=$display'>".get_vocab("names")."</a></b></td>";
echo "<td><b><a href='admin_user_mdp_facile.php?order_by=statut,nom,prenom&amp;display=$display'>".get_vocab("statut")."</a></b></td>";
echo "<td><b><a href='admin_user_mdp_facile.php?order_by=source,nom,prenom&amp;display=$display'>".get_vocab("authentification")."</a></b></td>";
echo "</tr>";
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
					$color[$i] = 'style_admin';
					$col[$i][4] = get_vocab("statut_administrator");
				}
				if ($user_statut == "visiteur")
				{
					$color[$i] = 'style_visiteur';
					$col[$i][4] = get_vocab("statut_visitor");
				}
				if ($user_statut == "utilisateur")
				{
					$color[$i] = 'style_utilisateur';
					$col[$i][4] = get_vocab("statut_user");
				}
				if ($user_statut == "gestionnaire_utilisateur")
				{
					$color[$i] = 'style_gestionnaire_utilisateur';
					$col[$i][4] = get_vocab("statut_user_administrator");
				}
				if ($user_etat[$i] == 'actif')
					$fond = 'fond1';
				else
					$fond = 'fond2';
				// Affichage de la source
				if (($user_source == 'local') || ($user_source == ''))
				{
					$col[$i][5] = "Locale";
				}
				else
				{
					$col[$i][5] = "Ext.";
				}
				echo "\n<tr><td class=\"".$fond."\">{$col[$i][1]}</td>\n";
			// un gestionnaire d'utilisateurs ne peut pas modifier un administrateur général ou un gestionnaire d'utilisateurs
				if ((authGetUserLevel(getUserName(), -1, 'user') ==  1) && (($user_statut == "gestionnaire_utilisateur") || ($user_statut == "administrateur")))
					echo "<td class=\"".$fond."\">{$col[$i][2]}</td>\n";
				else
					echo "<td class=\"".$fond."\"><a href=\"admin_user_modify.php?user_login=".urlencode($user_login)."&amp;display=$display\">{$col[$i][2]}</a></td>\n";
				echo "<td class=\"".$fond."\"><span class=\"".$color[$i]."\">{$col[$i][4]}</span></td>\n";
				echo "<td class=\"".$fond."\">{$col[$i][5]}</td>\n";

			// Fin de la ligne courante
				echo "</tr>";
			}
		}
	}
}
echo "</table>";
// fin de l'affichage de la colonne de droite
echo "</div>";
// Affichage d'un pop-up
affiche_pop_up($msg,"admin");
// fin de la page
end_page();
?>
