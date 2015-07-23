<?php
class Settings {

	static $grrSettings;

	public function __construct()
	{
		return self::load();
	}


	static function load()
	{
		$test = grr_sql_query1("SELECT NAME FROM ".TABLE_PREFIX."_setting WHERE NAME='version'");
		if ($test != -1)
			$sql = "SELECT `NAME`, `VALUE` FROM ".TABLE_PREFIX."_setting";
		else
			$sql = "SELECT `NAME`, `VALUE` FROM setting";
		$res = grr_sql_query($sql);
		if (!$res)
			return false;
		if (grr_sql_count($res) == 0)
			return false;
		else
		{
			for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
				self::$grrSettings[$row[0]] = $row[1];
			return true;
		}
	}

	static function get($_name)
	{
		if (isset(self::$grrSettings[$_name]))
			return self::$grrSettings[$_name];
	}

	static function set($_name, $_value)
	{
		if (isset(self::$grrSettings[$_name]))
		{
			$sql = "UPDATE ".TABLE_PREFIX."_setting set VALUE = '" . protect_data_sql($_value) . "' where NAME = '" . protect_data_sql($_name) . "'";
			$res = grr_sql_query($sql);
			if (!$res)
				return false;
		}
		else
		{
			$sql = "INSERT INTO ".TABLE_PREFIX."_setting set NAME = '" . protect_data_sql($_name) . "', VALUE = '" . protect_data_sql($_value) . "'";
			$res = grr_sql_query($sql);
			if (!$res)
				return (false);
		}
		self::$grrSettings[$_name] = $_value;
		return true;
	}
}
?>
