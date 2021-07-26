<?php
	$msg = "";

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


	// Personnalisation
	$dest = '../personnalisation/';
	$ok1 = false;

	if ($f = @fopen("$dest/.test", "w"))
	{
		@fputs($f, '<'.'?php $ok1 = true; ?'.'>');
		@fclose($f);
		include("$dest/.test");
	}
	if (!$ok1)
	{
		$msg .= "Répertoire \"personnalisation\" : ERREUR de droits.<br>";
	} else {
		$msg .= "Répertoire \"personnalisation\" : Droits OK.<br>";
	}


	echo $msg;

?>