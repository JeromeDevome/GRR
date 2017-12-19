<?php
/**
 * modif_listes.php
 * Page "Ajax" utilisée pour générer les listes de domaines et de ressources
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2017-12-16 14:00$
 * @author    Laurent Delineau & JeromeB
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

//Arguments passés par la méthode GET :
//$id_site : l'identifiant du site
//$area : domaine
//$room : ressource par défaut
//$session_login : identifiant
//$type : 'ressource'-> on actualise la liste des ressources
//				'domaine'-> on actualise la liste des domaines
//$action : 1-> on actualise la liste des ressources
//					2-> on vide la liste des ressouces
include "include/admin.inc.php";
if ((authGetUserLevel(getUserName(), -1) < 1) && (Settings::get("authentification_obli") == 1))
{
	showAccessDenied("");
	exit();
}
// Actualiser la liste des domaines
if ($_GET['type'] == "domaine")
{
 // Initialisation
	if (isset($_GET["id_site"]))
	{
		$id_site = $_GET["id_site"];
		settype($id_site,"integer");
	}
	else
		die();
	if (isset($_GET["area"]))
	{
		$area = $_GET["area"];
		settype($area,"integer");
	}
	else
		die();
	if (isset($_GET["session_login"]))
	{
		$session_login = $_GET["session_login"];
	}
	else
		die();
	if (Settings::get("module_multisite") == "Oui")
	{
 	// on a activé les sites
		if ($id_site != -1)
			$sql = "SELECT a.id, a.area_name
		FROM ".TABLE_PREFIX."_area a, ".TABLE_PREFIX."_j_site_area j
		WHERE a.id=j.id_area and j.id_site=$id_site
		ORDER BY a.order_display, a.area_name";
		else
			$sql = "";
	}
	else
	{
		$sql = "SELECT id, area_name
		FROM ".TABLE_PREFIX."_area
		ORDER BY order_display, area_name";
	}
	if (($id_site!=-1) || (Settings::get("module_multisite") == "Oui"))
		$resultat = grr_sql_query($sql);
	$display_liste = '<table border="0"><tr><td><b><i>'.get_vocab('areas').'</i></b></td><tr></tr><td><select id="id_area" name="id_area"  onchange="modifier_liste_ressources(1)">'."\n";
	if (($id_site!=-1) || (Settings::get("module_multisite") == "Oui"))
	{
		for ($enr = 0; ($row = grr_sql_row($resultat, $enr)); $enr++)
		{
			if (authUserAccesArea($session_login, $row[0]) != 0)
			{
				$display_liste .=  '              <option value="'.$row[0].'"';
				if ($area == $row[0])
					$display_liste .= ' selected="selected" ';
				$display_liste .= '>'.htmlspecialchars($row[1]);
				$display_liste .= '</option>'."\n";
			}
		}
	}
	$display_liste .= '            </select>';
	$id_area = 5;
	$display_liste .=  '</td>
</tr></table>'."\n";
}
//Actualiser la liste des ressources
if ($_GET['type']=="ressource")
{
	if (isset($_GET["room"]))
	{
		$room = $_GET["room"];
		settype($room,"integer");
	}
	else
		die();
	if ($_GET['action'] == 2)
	{
	//on vide la liste des ressources
		$display_liste = '<table border="0"><tr><td>'.get_vocab('default_room').'</td><td><select name="id_room"><option value="-1">'.get_vocab('default_room_all').'</option></select></td></tr></table>'."\n";
	}
	else
	{
		if (isset($_GET["id_area"]))
		{
			$id_area = $_GET["id_area"];
			settype($id_area,"integer");
		}
		else
			die();

		$sql = "SELECT id, room_name
		FROM ".TABLE_PREFIX."_room
		WHERE area_id='".$id_area."'";
		// on ne cherche pas parmi les ressources invisibles pour l'utilisateur
		$tab_rooms_noaccess = verif_acces_ressource(getUserName(), 'all');
		foreach ($tab_rooms_noaccess as $key){
			$sql .= " and id != $key ";
		}
		$sql .= " ORDER BY order_display,room_name";
		$resultat = grr_sql_query($sql);
		$display_liste = '<table border="0"><tr><td>'.get_vocab('default_room').'</td><td><select name="id_room">';
		for ($enr = 0; ($row = grr_sql_row($resultat, $enr)); $enr++)
		{
			$display_liste .=  '              <option value="'.$row[0].'"';
			if ($room == $row[0])
				$display_liste .= ' selected="selected" ';
			$display_liste .= '>'.htmlspecialchars($row[1]);
			$display_liste .= '</option>'."\n";
		}

		$display_liste .= '</select></td></tr></table>'."\n";
	}
}
header("Content-Type: text/html;charset=".$charset_html);
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
echo $display_liste;
?>
