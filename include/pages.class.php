<?php
/* pages.class.php
 * Permet de lire et d'écrire les paramètres dans la BDD (Table setting)
 * Dernière modification : $Date: 2018-02-10 18:00$
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

class Pages {

	static $grrPages;

	public function __construct()
	{
		return self::load();
	}


	static function load()
	{
		$test = grr_sql_query1("SELECT nom FROM ".TABLE_PREFIX."_page WHERE nom = 'CGU'");
		if ($test != -1)
			$sql = "SELECT `nom`, `valeur` FROM ".TABLE_PREFIX."_page";
		else
			$sql = "SELECT `nom`, `valeur` FROM page";
		$res = grr_sql_query($sql);
		if (!$res)
			return false;
		if (grr_sql_count($res) == 0)
			return false;
		else
		{
			for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
				self::$grrPages[$row[0]] = $row[1];
			return true;
		}
	}

	static function get($_name)
	{
		if (isset(self::$grrPages[$_name]))
			return self::$grrPages[$_name];
	}

	static function set($_name, $_value)
	{
		if (isset(self::$grrPages[$_name]))
		{
			$sql = "UPDATE ".TABLE_PREFIX."_page set valeur = '" . protect_data_sql($_value) . "' where nom = '" . protect_data_sql($_name) . "'";
			$res = grr_sql_query($sql);
			if (!$res)
				return false;
		}
		else
		{
			$sql = "INSERT INTO ".TABLE_PREFIX."_page set nom = '" . protect_data_sql($_name) . "', valeur = '" . protect_data_sql($_value) . "'";
			$res = grr_sql_query($sql);
			if (!$res)
				return (false);
		}
		self::$grrPages[$_name] = $_value;
		return true;
	}
}
?>
