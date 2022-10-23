<?php
/**
 * admin_room.php
 * Interface d'accueil de Gestion des sites de l'application GRR
 * Dernière modification : $Date: 2017-12-16 14:00$
 * @author    JeromeB & Laurent Delineau & Marc-Henri PAMISEUX & Yan Naessens
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

$grr_script_name = "admin_room.php";

$id_area = isset($_POST["id_area"]) ? $_POST["id_area"] : (isset($_GET["id_area"]) ? $_GET["id_area"] : NULL);
if ((isset($id_area))&&($id_area != -1))
{
	settype($id_area,"integer");
	$id_site = mrbsGetAreaSite($id_area);
}
if (!isset($id_site))
	$id_site = isset($_POST['id_site']) ? $_POST['id_site'] : (isset($_GET['id_site']) ? $_GET['id_site'] : -1);
settype($id_site,"integer");

check_access(4, $back);

// Afffichage d'un éventuel message
if (isset($_GET['msg']))
{
	$msg = $_GET['msg'];
	affiche_pop_up($msg,"admin");
}
// If area is set but area name is not known, get the name.
if ((isset($id_area)) && ($id_area != -1))
{
	if (empty($area_name))
	{
		$res = grr_sql_query("SELECT area_name, access FROM ".TABLE_PREFIX."_area WHERE id=$id_area");
		if (!$res)
			fatal_error(0, grr_sql_error());
		if (grr_sql_count($res) == 1)
		{
			$row = grr_sql_row($res, 0);
			$area_name = $row[0];
		}
		else
			$area_name='';
		grr_sql_free($res);
	}
	else
		$area_name = unslashes($area_name);
}
else
	$area_name='';

// Affichage du contenu de la page
get_vocab_admin("admin_room");
get_vocab_admin('sites');
get_vocab_admin('site');
get_vocab_admin('choose_a_site');

get_vocab_admin('areas');
get_vocab_admin('rooms');

get_vocab_admin('admin_access_area');
get_vocab_admin('privileges');

get_vocab_admin('noarea');
get_vocab_admin("OU");
get_vocab_admin('show_all_rooms');
get_vocab_admin('fiche_ressource');

$sites = array();

if (Settings::get("module_multisite") == "Oui")
{
	if (authGetUserLevel(getUserName(),-1,'area') >= 6)
		$sql = "SELECT id,sitecode,sitename FROM ".TABLE_PREFIX."_site ORDER BY sitename ASC";
	else
	{
		// Administrateur de sites ou de domaines
		$sql = "SELECT DISTINCT id,sitecode,sitename FROM ".TABLE_PREFIX."_site s ";
		// l'utilisateur est-il administrateur d'un site ?
		$test1 = grr_sql_query1("SELECT count(login) FROM ".TABLE_PREFIX."_j_useradmin_site WHERE login='".getUserName()."'");
		if ($test1 > 0)
			$sql .=", ".TABLE_PREFIX."_j_useradmin_site u";
		// l'utilisateur est-il administrateur d'un domaine ?
		$test2 = grr_sql_query1("SELECT count(login) FROM ".TABLE_PREFIX."_j_useradmin_area WHERE login='".getUserName()."'");
		if ($test2 > 0)
			$sql .=", ".TABLE_PREFIX."_j_useradmin_area a, ".TABLE_PREFIX."_j_site_area j";
		$sql .=" WHERE (";
			if ($test1 > 0)
				$sql .= "(s.id=u.id_site AND u.login='".getUserName()."') ";
			if (($test1 > 0) && ($test2 > 0))
				$sql .= " or ";
			if ($test2 > 0)
				$sql .= "(j.id_site=s.id AND j.id_area=a.id_area AND a.login='".getUserName()."')";
			$sql .= ") ORDER BY s.sitename ASC";
    }
    $res = grr_sql_query($sql);
    $nb_site = grr_sql_count($res);
	$trad['dNbSite'] = $nb_site;
	
    if ($nb_site > 1)
    {
		for ($enr = 0; ($row = grr_sql_row($res, $enr)); $enr++) {
			$sites[] = array('idsite' => $row[0], 'nomsite' => $row[2]);
		}
		grr_sql_free($res);
    }
    else // un seul site
    {
        $row = grr_sql_row($res, 0);
		$trad['dNomSite'] =  $row[2];
		$id_site = $row[0];
    }
}

$trad['dIdSite'] = $id_site;
$trad['dIdDomaine'] = $id_area;

$domaines = array();
$ressources = array();

if ((isset($id_area)) && ($id_area != -1)) 
	$trad['dRessourceDe'] =  get_vocab('in') . " " .htmlspecialchars($area_name);

// Seul l'administrateur a le droit d'ajouter des domaines
if ((authGetUserLevel(getUserName(),-1,'area') >= 5) && $id_area != -1)
	$trad['dAjoutDomaine'] = "<a href=\"?p=admin_edit_domaine&id_site=".$id_site."&amp;add_area=yes\">".get_vocab('addarea')."</a>";

if ((isset($id_area))&&($id_area != -1))
	$trad['dAjoutRessource'] = "<a href=\"?p=admin_edit_room&id_site=".$id_site."&amp;area_id=$id_area\">".get_vocab('addroom')."</a>";

// A partir de ce niveau, on sait qu'il existe un site
if ((Settings::get("module_multisite") == "Oui") && ($id_site > 0))
	$sql="SELECT ".TABLE_PREFIX."_area.id,".TABLE_PREFIX."_area.area_name,".TABLE_PREFIX."_area.access
		FROM ".TABLE_PREFIX."_j_site_area,".TABLE_PREFIX."_area
		WHERE ".TABLE_PREFIX."_j_site_area.id_site='".$id_site."'
		AND ".TABLE_PREFIX."_area.id=".TABLE_PREFIX."_j_site_area.id_area
		ORDER BY order_display";
else
	$sql="select id, area_name, access from ".TABLE_PREFIX."_area order by order_display";

$res = grr_sql_query($sql);

if (!$res)
	fatal_error(0, grr_sql_error());

if (grr_sql_count($res) != 0)
{
	// on détermine les domaines accessibles à l'utilisateur -> rangés dans $tareas
	$tareas = array();
	for ($i = 0; ($row = grr_sql_row($res, $i)); $i++){
		if ((authGetUserLevel(getUserName(),$row[0],'area') >= 4))
			$tareas[] = $row ;
	}

	// CAS 1 : cas où le domaine n'est pas choisi ou UN domaine est sélectionné
	if (!isset($id_area) || $id_area != -1){
		$trad['dCasAfficher'] = 1;

		foreach($tareas as $row)
		{
			$domaines[] = array('id' => $row[0], 'nom' => $row[1], 'acces' => $row[2], 'droitsuser' => authGetUserLevel(getUserName(),$row[0],'area'));
		}

		// RESSOURCES
		if (isset($id_area)) // cas où UN domaine est choisi, on affiche toutes les ressources de ce domaine
		{
			$sql = "SELECT id, room_name, description, capacity, max_booking, statut_room, area_id from ".TABLE_PREFIX."_room where area_id=$id_area ";
			// on ne cherche pas parmi les ressources invisibles pour l'utilisateur
			$tab_rooms_noaccess = verif_acces_ressource(getUserName(), 'all');
			foreach ($tab_rooms_noaccess as $key){
				$sql .= " and id != $key ";
			}
			$sql .= "order by order_display, room_name";
			$res = grr_sql_query($sql);
			if (!$res)
				fatal_error(0, grr_sql_error());
			if (grr_sql_count($res) != 0){
			   // echo "<table class=\"table\">";
				for ($i = 0; ($row = grr_sql_row($res, $i)); $i++){
					$ressources[] = array('id' => $row[0], 'nom' => $row[1], 'description' => $row[2], 'capacite' => $row[3], 'maxbooking' => $row[4], 'statut' => $row[5], 'iddomaine' => $row[6]);
				}
			}  
			else 
				$trad['dNo_rooms_for_area'] = get_vocab("no_rooms_for_area");
		}
	}
	// CAS 2 : cas où il faut afficher toutes les ressources de tous les domaines
	elseif ($id_area == -1)
	{
		$trad['dCasAfficher'] = 2;

		foreach($tareas as $row)
		{
			$domaines[] = array('id' => $row[0], 'nom' => $row[1], 'acces' => $row[2], 'droitsuser' => authGetUserLevel(getUserName(),$row[0],'area'));

			// RESSOURCES
			$sql = "SELECT id, room_name, description, capacity, max_booking, statut_room, area_id from ".TABLE_PREFIX."_room where area_id=$row[0] ";
			// on ne cherche pas parmi les ressources invisibles pour l'utilisateur
			$tab_rooms_noaccess = verif_acces_ressource(getUserName(), 'all');
			foreach ($tab_rooms_noaccess as $key){
				$sql .= " and id != $key ";
			}
			$sql .= "order by order_display, room_name";
			$res = grr_sql_query($sql);
			if (!$res)
				fatal_error(0, grr_sql_error());
			if (grr_sql_count($res) != 0){
				for ($i = 0; ($row = grr_sql_row($res, $i)); $i++){
					$ressources[] = array('id' => $row[0], 'nom' => $row[1], 'description' => $row[2], 'capacite' => $row[3], 'maxbooking' => $row[4], 'statut' => $row[5], 'iddomaine' => $row[6]);
				}
			}  
			else 
				$trad['dNo_rooms_for_area'] = get_vocab("no_rooms_for_area");
		}
	} // Fin CAS 2
}

echo $twig->render($page.'.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings, 'sites' => $sites, 'domaines' => $domaines, 'ressources' => $ressources));
?>