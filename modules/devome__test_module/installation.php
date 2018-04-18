<?php

class Module{


	public static function Installation($nom, $versionBDD) {

		$sql = "INSERT INTO ".TABLE_PREFIX."_modulesext SET nom='".$nom."', actif ='0', version='".$versionBDD."'";
	
		if (grr_sql_command($sql) < 0)
			fatal_error(1, "<p>" . grr_sql_error());

	}

	public static function MiseAJours() {


	}


}


?>