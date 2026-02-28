<?php
/**
 * moderationsliste.php
 * Formulaire d'envoi de mail
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2025-07-20 12:00$
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

$grr_script_name = "moderationsliste.php";

$trad = $vocab;


$acces = false;
$listeModeration = array();
$res = false;
if (authGetUserLevel($d['gNomUser'],-1) > 5) // admin général
{
	$acces = true;
	$sql = "SELECT e.id,r.room_name,e.start_time,create_by,beneficiaire FROM ".TABLE_PREFIX."_entry e JOIN ".TABLE_PREFIX."_room r ON e.room_id = r.id WHERE e.moderate = 1 AND e.supprimer = 0 ";
	$res = grr_sql_query($sql);
}
elseif (isset($_GET['id_site']) && (authGetUserLevel($d['gNomUser'],intval($_GET['id_site']),'site') > 4)) // admin du site
{
	$acces = true;
	$sql = "SELECT e.id,r.room_name,e.start_time,create_by,beneficiaire FROM ".TABLE_PREFIX."_entry e JOIN ".TABLE_PREFIX."_room r ON e.room_id = r.id JOIN ".TABLE_PREFIX."_j_site_area j ON r.area_id = j.id_area WHERE (j.id_site = ".intval($_GET['id_site'])." AND e.moderate = 1  AND e.supprimer = 0)";
	$res = grr_sql_query($sql);
}
elseif (isset($_GET['area']) && (authGetUserLevel($d['gNomUser'],intval($_GET['area']),'area') > 3)) // admin du domaine
{
	$acces = true;
	$sql = "SELECT e.id,r.room_name,e.start_time,create_by,beneficiaire FROM ".TABLE_PREFIX."_entry e JOIN ".TABLE_PREFIX."_room r ON e.room_id = r.id JOIN ".TABLE_PREFIX."_area a ON r.area_id = a.id WHERE (a.id = ".intval($_GET['area'])." AND e.moderate = 1 AND e.supprimer = 0)";
	$res = grr_sql_query($sql);
}
elseif (isset($_GET['room']) && (authGetUserLevel($d['gNomUser'],intval($_GET['room']),'room') > 2)) // gestionnaire de la ressource
{
	$acces = true;
	$sql = "SELECT e.id,r.room_name,e.start_time,create_by,beneficiaire FROM ".TABLE_PREFIX."_entry e JOIN ".TABLE_PREFIX."_room r ON e.room_id = r.id WHERE (e.moderate = 1 AND e.supprimer = 0 AND e.room_id = ".intval($_GET['room']).") ";
	$res = grr_sql_query($sql);
}

if ($acces)
{
	$d['acces'] = 1;
	if ($res)
	{
		if ($sql != ""){
			$res = grr_sql_query($sql);
			if ($res)
			{
				foreach($res as $row) 
				{
					$link = "?p=vuereservation&id=".$row['id']."&mode=page";
					$listeModeration[] = array('ressource' => $row['room_name'], 'debut' => time_date_string($row['start_time'], $dformat), 'createur' => $row['create_by'], 'beneficiaire' => $row['beneficiaire'], 'lien' => $link );
				}
			}
		}
		$d['nbResaAModerer'] = count($listeModeration);
	}
}



echo $twig->render('moderationsliste.twig', array('trad' => $trad, 'd' => $d, 'settings' => $AllSettings, 'resas' => $listeModeration));
?>