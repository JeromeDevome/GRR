<?php
/**
 * admin_groupe.php
 * interface de gestion des utilisateurs de l'application GRR
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2022-07-13 16:10$
 * @author    Laurent Delineau & JeromeB & Yan Naessens
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


$grr_script_name = "admin_groupe.php";

$msg = '';

if ((authGetUserLevel(getUserName(), -1) < 6) && (authGetUserLevel(getUserName(), -1,'user') != 1))
{
	showAccessDenied($back);
	exit();
}
if ((isset($_GET['action_del'])) && isset($_GET['js_confirmed']) && ($_GET['js_confirmed'] == 1))
{
	VerifyModeDemo();
}

//
// Supression d'un utilisateur
//
if ((isset($_GET['action_del'])) and (isset($_GET['js_confirmed'])) and ($_GET['js_confirmed'] == 1))
{
	$id = $_GET['groupe_del'];

	$sql = "DELETE FROM ".TABLE_PREFIX."_groupes WHERE idgroupes='$id'";
	if (grr_sql_command($sql) < 0)
	{
		fatal_error(1, "<p>" . grr_sql_error());
	}
	else
	{
		$msg=get_vocab("del_user_succeed");
	}
}
if (isset($mess) and ($mess != ""))
	echo "<p>".$mess."</p>";

get_vocab_admin('admin_groupe');

get_vocab_admin('groupe_add');

get_vocab_admin("name");
get_vocab_admin("description");
get_vocab_admin("statut");
get_vocab_admin("action");

get_vocab_admin("confirm_del");
get_vocab_admin("cancel");
get_vocab_admin("delete");

if (authGetUserLevel(getUserName(),-1) >= 6)
	$trad['dEstAdministrateur'] = 1;


// Affichage du tableau
$groupes = array();
$i = 0;
$sql = "SELECT idgroupes, nom, description, archive FROM ".TABLE_PREFIX."_groupes ORDER BY nom ASC";
$res = grr_sql_query($sql);
if ($res)
{
	foreach($res as $row)
	{
		$nom = htmlspecialchars($row['nom']);
		$description = htmlspecialchars($row['description']);
		// Id groupe
		$groupes[$i][0] = $row['idgroupes'];
		// Nom
		$groupes[$i][1] = $nom;
		// Description
		$groupes[$i][2] = $description;
		// Affichage du statut
		if ($row['archive'] == 1)
			$groupes[$i][3] = "<span class=\"text-red\">".get_vocab("archiver")."</span>";
        $i++;
	}
}

affiche_pop_up($msg,"admin");

echo $twig->render($page.'.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings, 'groupes' => $groupes));
?>