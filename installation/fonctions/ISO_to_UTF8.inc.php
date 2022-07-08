<?php
/**
 * Mise à jour de la base lors du passage en UTF-8
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2022-04-22 16:32$
 * @author    Yan Naessens
 * @copyright Copyright 2003-2022 Team DEVOME - JeromeB
 * @link      http://www.gnu.org/licenses/licenses.html
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * 
 */

define('SET_ORIGINE', 'latin1');
define('SET_DEST', 'utf8mb4 COLLATE utf8mb4_unicode_ci');

// conversion de la base 
$result.="<br /><strong>Passage de la base en ".SET_DEST."</strong><br />";

$donneesBase = array();
$query = mysqli_query($GLOBALS['db_c'], "SHOW VARIABLES LIKE 'character_set_%'");
if ($query) {
  while ($row = mysqli_fetch_object($query)) {
    $donneesBase[] = $row;
  }
} else {
  die ('Erreur de lecture de la base');
}
foreach ($donneesBase as $donnees) {
    if (($donnees->Variable_name == 'character_set_database')&&($donnees->Value != SET_DEST)) {
        $result.=$donnees->Variable_name." est réglé à ".$donnees->Value."<br />";
        $result.="Passage de ".$donnees->Variable_name." à  ".SET_DEST."<br />";
        //$queryBase = mysqli_query($GLOBALS['db_c'], "ALTER DATABASE  CHARACTER SET ".SET_DEST.";");
        $result_inter = traiteRequete("ALTER DATABASE  CHARACTER SET ".SET_DEST.";");
   		if ($result_inter == '')
			$result .= formatResult("Ok !","<span style='color:green;'>","</span>");
		else
			$result .= $result_inter;
		$result_inter = '';
    }
}
unset ($donnees, $donneesBase);

/* conversion des tables */
$result.="Passage des tables en ".SET_DEST."<br />";
// recherche les tables à convertir
$donneesTable=array();
$query = mysqli_query($GLOBALS['db_c'], "SHOW table status");
if ($query) {
	while ($row = mysqli_fetch_array($query,  MYSQLI_ASSOC)) {
        if (mb_substr($row['Collation'],0,6) == SET_ORIGINE ) {
            $donneesTable[] = $row['Name'];
        }
	}
} else {
	die ('Erreur de lecture de la base');
}
// certains champs sont potentiellement trop longs pour la conversion en utf8mb4 (longueur des clés limitée à 191 en UTF8mb4 pour InnoDB)
/*$req = "ALTER TABLE ".TABLE_PREFIX."_participants
  CONVERT TO CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci,
  MODIFY participant varchar(189) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;";
$res = mysqli_query($GLOBALS['db_c'], $req);
if($res){
    $result.="Conversion de ".TABLE_PREFIX."_participants à varchar(189) ".SET_DEST."<br/>";
}
else{
    die('Erreur de modification de la base');
}*/
// conversion des tables et des données
if (empty($donneesTable) ){
    $result .= "Tables déjà encodées en ".SET_DEST."<br/>";
    $result .= formatResult("Ok !","<span style='color:green;'>","</span>");
} 
else {
    $result_inter = '';
    $ok = TRUE;
    foreach ($donneesTable as $table) {
        $result.="Passage de $table en ".SET_DEST." en cours. ";
    	//$querytable = mysqli_query($GLOBALS['db_c'], 'ALTER TABLE '.$table.' CONVERT TO CHARACTER SET '.SET_DEST);
        //$querytable = mysqli_query($GLOBALS['db_c'], 'ALTER TABLE '.$table.' CHARACTER SET '.SET_DEST);
        $result_inter.= traiteRequete('ALTER TABLE '.$table.' CONVERT TO CHARACTER SET '.SET_DEST);
        $result_inter.= traiteRequete('ALTER TABLE '.$table.' CHARACTER SET '.SET_DEST);
        if ($result_inter == '')
			$result .= formatResult("Ok !","<span style='color:green;'>","</span>");
		else{
			$result .= $result_inter;
            $ok = FALSE;
        }
		$result_inter = '';
    }
    unset ( $table);
    if($ok){
        $result .= "Migration terminée : Tables encodées en ".SET_DEST."<br/>";
        $result .= formatResult("Ok !","<span style='color:green;'>","</span>");
    }    
}
?>