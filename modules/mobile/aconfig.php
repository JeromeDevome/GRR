<?php

include('../../include/connect.inc.php');

try {
	$options[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
	$bdd = new PDO("mysql:host=".$dbHost.";dbname=".$dbDb."", $dbUser, $dbPass, $options);
}

catch (Exception $e){
	die('Erreur : ' . $e->getMessage());
}

?>