<?php

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
		$msg = "Probleme ecriture repertoire";
	} else {
		$msg = "Droit OK !";
	}

	echo $msg;

?>