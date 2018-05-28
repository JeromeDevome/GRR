<?php

/**
 * hook.class.php
 * Permet l'exportation des ressources au format ics
 * Dernière modification : $Date: 2018-03-03 18:00$
 * @author    JeromeB
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

class Hook{
	
	public static function Appel($identifiant_hook){
	
		$sql = "SELECT `nom` FROM ".TABLE_PREFIX."_modulesext WHERE `actif` = 1;";
		$res = grr_sql_query($sql);
		if ($res)
		{
			for ($i = 0; ($row=grr_sql_row($res,$i));$i++)
			{
				include(dirname(__FILE__).'/../modules/'.$row[0].'/controleur.php');
			}
		}

	}


}

?>