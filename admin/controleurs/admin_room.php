<?php
/**
 * admin_room.php
 * Interface d'accueil de Gestion des domaines et des ressources de l'application GRR
 * Dernière modification : $Date: 2026-02-08 15:15$
 * @author    JeromeB & Laurent Delineau & Marc-Henri PAMISEUX & Yan Naessens
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

$grr_script_name = "admin_room.php";

SecuAccess::CheckAccess(4, $back);

// les variables attendues et leur type
$form_vars = array(
    'id_site' => 'int',
	'id_area' => 'int',
	'ok' => 'int',
	'msg' => 'string',
	'p_module_multisite' => 'int'
);
// récupération des valeurs des variables passées en paramètres
foreach($form_vars as $var => $var_type)
    $$var = SecuChaine::GetFormVarSecure($var, $var_type);


if ((isset($id_area))&&($id_area > 0))
	$id_site = mrbsGetAreaSite($id_area);


$trad['TitrePage']	= $trad['admin_room'];


// Afffichage d'un éventuel message
if (isset($msg) && $msg != "")
{
	$d['enregistrement'] = $ok;
	$d['msgToast'] = $msg;
}

/** Actions **/
	/* Activation / désactivation du module multisite */
	if ($p_module_multisite == 1)
	{
		if (Settings::get("module_multisite") == 1)
			$activeModuleInt = 0;
		else
			$activeModuleInt = 1;

		if (!Settings::set("module_multisite", $activeModuleInt))
			echo "Erreur lors de l'enregistrement de module_multisite ! <br />";
		else
		{
			if ($activeModuleInt == 1)
			{
				// On crée un site par défaut s'il n'en existe pas
				$id_site = grr_sql_query1("SELECT min(id) FROM ".TABLE_PREFIX."_site");
				if ($id_site == -1)
				{
					$sql="INSERT INTO ".TABLE_PREFIX."_site SET sitecode='1', sitename='site par defaut'";
					if (grr_sql_command($sql) < 0)
						fatal_error(0,'<p>'.grr_sql_error().'</p>');
					$id_site = mysqli_insert_id($GLOBALS['db_c']);
				}
				// On affecte tous les domaines à un site.
				$sql = "SELECT id FROM ".TABLE_PREFIX."_area";
				$res = grr_sql_query($sql);
				if ($res)
				{
					for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
					{
						// l'area est-elle déjà affectée à un site ?
						$test_site = grr_sql_query1("SELECT count(id_area) FROM ".TABLE_PREFIX."_j_site_area WHERE id_area='".$row[0]."'");
						if ($test_site == 0)
						{
							$sql="INSERT INTO ".TABLE_PREFIX."_j_site_area SET id_site='".$id_site."', id_area='".$row[0]."'";
							if (grr_sql_command($sql) < 0)
								fatal_error(0,'<p>'.grr_sql_error().'</p>');
						}
					}
				}
			}
		}
	}


/** Affichage de la page **/

	$sites		= array();
	$domaines	= array();
	$ressources = array();

	// Liste des sites (si multisite activé)
	if (Settings::get("module_multisite") == 1)
	{
		if (SecuAccess::UserLevel(getUserName(),-1,'area') >= 6)
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
		$d['nbSite'] = $nb_site;
		
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
			$id_site = $row[0];
		}
	}


	$d['idSite'] = $id_site;
	$d['idDomaine'] = $id_area;

	// Seul l'administrateur a le droit d'ajouter des domaines
	if ((SecuAccess::UserLevel(getUserName(),-1,'area') >= 5) && $id_area != -1)
		$d["ajoutDomaine"] = 1;

	// Liste des domaines du site selectionné
	if ((Settings::get("module_multisite") == 1) && ($id_site > 0))
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
			if ((SecuAccess::UserLevel(getUserName(),$row[0],'area') >= 4))
				$tareas[] = $row ;
		}

		foreach($tareas as $row)
		{
			$domaines[] = array('id' => $row[0], 'nom' => $row[1], 'acces' => $row[2], 'droitsuser' => SecuAccess::UserLevel(getUserName(),$row[0],'area'));

			// RESSOURCES
			$sql = "SELECT id, room_name, description, capacity, max_booking, statut_room, area_id, who_can_book, confidentiel_resa from ".TABLE_PREFIX."_room where area_id=$row[0] ";
			// on ne cherche pas parmi les ressources invisibles pour l'utilisateur
			$tab_rooms_noaccess = SecuAccess::UserResource(getUserName(), 'all');
			foreach ($tab_rooms_noaccess as $key){
				$sql .= " and id != $key ";
			}
			$sql .= "order by order_display, room_name";
			$res = grr_sql_query($sql);
			if (!$res)
				fatal_error(0, grr_sql_error());
			if (grr_sql_count($res) != 0){
				for ($i = 0; ($row = grr_sql_row($res, $i)); $i++){
					$ressources[] = array('id' => $row[0], 'nom' => $row[1], 'description' => $row[2], 'capacite' => $row[3], 'maxbooking' => $row[4], 'statut' => $row[5], 'iddomaine' => $row[6], 'resteint' => $row[7], 'confidentiel_resa' => $row[8]);
				}
			}
		}
	}

	if (!Settings::load())
		die("Erreur chargement settings");
	$AllSettings = Settings::getAll();

	echo $twig->render($page.'.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings, 'sites' => $sites, 'domaines' => $domaines, 'ressources' => $ressources));
?>