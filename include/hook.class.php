<?php

/**
 * hook.class.php
 * Permet l'exportation des ressources au format ics
 * Dernière modification : $Date: 2023-09-24 17:00$
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

class Hook{

	public static function Actifs(){
		global $modulesActifs;

		$modulesActifs2 = array();
		$modulesActifs2 = $modulesActifs;

		$sql = "SELECT `nom` FROM ".TABLE_PREFIX."_modulesext WHERE `actif` = 1;";
		$res = grr_sql_query($sql);
		if ($res)
		{
			for ($i = 0; ($row=grr_sql_row($res,$i));$i++)
			{
				$modulesActifs2[] = $row[0];
			}
		}

		return $modulesActifs2;
	}
	
	public static function Appel($identifiant_hook){
		global $niveauDossier;

		$CtnHook[$identifiant_hook] = "";

		$modulesActifs2 = Hook::Actifs();

		foreach ($modulesActifs2 as &$nomModule)
		{
			if(file_exists('../personnalisation/modules/'.$nomModule.'/controleur.php'))
				include('../personnalisation/modules/'.$nomModule.'/controleur.php');
			elseif(file_exists('./personnalisation/modules/'.$nomModule.'/controleur.php'))
				include('./personnalisation/modules/'.$nomModule.'/controleur.php');
		}

		return $CtnHook;
	}


}

?>