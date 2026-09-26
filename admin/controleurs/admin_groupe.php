<?php
/**
 * admin_groupe.php
 * interface de gestion des utilisateurs de l'application GRR
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2026-09-26 11:00$
 * @author    JeromeB
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


$grr_script_name = "admin_groupe.php";

// Accès à la page
if ((SecuAccess::UserLevel(getUserName(), -1) < 6) && (SecuAccess::UserLevel(getUserName(), -1,'user') != 1))
{
	showAccessDenied($back);
	exit();
}


// les variables attendues et leur type
$form_vars = array(
    'p_action' => array('int', 0), // 1 : supression, 2 : synchro
	'p_groupe' => array('int', 0), // Id du groupe à traiter
);
// récupération des valeurs des variables passées en paramètres
foreach($form_vars as $var => $params)
    $$var = SecuChaine::GetFormVarSecure($var, $params[0], $params[1]);



/** Actions **/
	if($p_action == 1) //Suppression
	{
		VerifyModeDemo();

		$sql = "DELETE FROM ".TABLE_PREFIX."_groupes WHERE idgroupes='$p_groupe'";
		if (grr_sql_command($sql) < 0)
		{
			fatal_error(1, "<p>" . grr_sql_error());
		}
		else
		{
			$d['enregistrement'] = 1;
			$d['msgToast'] = get_vocab('del_group_succeed');
		}
	}
	elseif($p_action == 2) // Synchronisation
	{
		synchro_groupe($p_groupe, 0);
		$d['enregistrement'] = 1;
		$d['msgToast'] = "Synchronisation du groupe terminé";
	}



/** Affichage de la page **/
	$trad['TitrePage']	= $trad['admin_groupe'];
	$groupes = array();
	$i = 0;
	$sql = "SELECT idgroupes, nom, description, archive FROM ".TABLE_PREFIX."_groupes ORDER BY nom ASC";
	$res = grr_sql_query($sql);
	if ($res)
	{
		foreach($res as $row)
		{
			$groupes[$i][0] = $row['idgroupes'];
			$groupes[$i][1] = $row['nom'];
			$groupes[$i][2] = $row['description'];
			if ($row['archive'] == 1)
				$groupes[$i][3] = "<span class=\"text-red\">".get_vocab("archiver")."</span>";
			$i++;
		}
	}

	echo $twig->render($page.'.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings, 'groupes' => $groupes));
?>