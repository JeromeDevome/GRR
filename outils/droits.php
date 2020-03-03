<?php
/**
 * droits.php
 * script testant l'accès en écriture dans les dossiers qui doivent l'être
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2019-12-29 16:20$
 * @author    Laurent Delineau & JeromeB & Yan Naessens
 * @copyright Copyright 2003-2019 Team DEVOME - JeromeB
 * @link      http://www.gnu.org/licenses/licenses.html
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */
$grr_script_name = "droits.php";

	$msg = "";
	
	// Images
	$dest = '../images/';
	$ok1 = false;

	if ($f = @fopen("$dest/.test", "w"))
	{
		@fputs($f, '<'.'?php $ok1 = true; ?'.'>');
		@fclose($f);
		include("$dest/.test");
	}
	if (!$ok1)
	{
		$msg .= "Répertoire \"images\" : ERREUR de droits.<br>";
	} else {
		$msg .= "Répertoire \"images\" : Droits OK.<br>";
	}

	// Export
	$dest = '../export/';
	$ok1 = false;

	if ($f = @fopen("$dest/.test", "w"))
	{
		@fputs($f, '<'.'?php $ok1 = true; ?'.'>');
		@fclose($f);
		include("$dest/.test");
	}
	if (!$ok1)
	{
		$msg .= "Répertoire \"export\" : ERREUR de droits.<br>";
	} else {
		$msg .= "Répertoire \"export\" : Droits OK.<br>";
	}


	// Temp
	$dest = '../temp/';
	$ok1 = false;

	if ($f = @fopen("$dest/.test", "w"))
	{
		@fputs($f, '<'.'?php $ok1 = true; ?'.'>');
		@fclose($f);
		include("$dest/.test");
	}
	if (!$ok1)
	{
		$msg .= "Répertoire \"temp\" : ERREUR de droits.<br>";
	} else {
		$msg .= "Répertoire \"temp\" : Droits OK.<br>";
	}

	// Types
	$dest = '../themes/default/css/';
	$ok1 = false;

	if ($f = @fopen("$dest/.test", "w"))
	{
		@fputs($f, '<'.'?php $ok1 = true; ?'.'>');
		@fclose($f);
		include("$dest/.test");
	}
	if (!$ok1)
	{
		$msg .= "Répertoire \"themes/default/css\" : ERREUR de droits.<br>";
	} else {
		$msg .= "Répertoire \"themes/default/css\" : Droits OK.<br>";
	}
	
		// Thème perso
	$dest = '../themes/perso/css/';
	$ok1 = false;

	if ($f = @fopen("$dest/.test", "w"))
	{
		@fputs($f, '<'.'?php $ok1 = true; ?'.'>');
		@fclose($f);
		include("$dest/.test");
	}
	if (!$ok1)
	{
		$msg .= "Répertoire \"themes/perso/css\" : ERREUR de droits.<br>";
	} else {
		$msg .= "Répertoire \"themes/perso/css\" : Droits OK.<br>";
	}
	
	echo $msg;

?>