<?php
/**
 * Mise à jour de la base lors du passage en UTF-8
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2025-12-28 20:50$
 * @author    Yan Naessens & JeromeB
 * @copyright Copyright 2003-2025 Team DEVOME - JeromeB
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
define('SET_OK', 'utf8mb4_unicode_ci');
define('SET_DEST', 'utf8mb4 COLLATE utf8mb4_unicode_ci');

//! conversion de la base 
//$result.="<br /><strong>Passage de la base en ".SET_DEST."</strong><br />";

$donneesBase = array();
$query = mysqli_query($GLOBALS['db_c'], "SHOW VARIABLES LIKE 'character_set_%'");
if ($query) {
  while ($row = mysqli_fetch_object($query)) {
    $donneesBase[] = $row;
  }
} else {
  die ('Erreur de lecture de la base');
}
// Test préliminaire : si la base et toutes les tables sont déjà en utf8mb4, ne rien faire
$dbOk = false;
foreach ($donneesBase as $donnees) {
    if ($donnees->Variable_name == 'character_set_database') {
        if (strpos($donnees->Value, 'utf8mb4') !== false) {
            $dbOk = true;
        }
        break;
    }
}
$tablesNotOk = array();
$q = mysqli_query($GLOBALS['db_c'], "SHOW table status");
if ($q) {
    while ($row = mysqli_fetch_array($q, MYSQLI_ASSOC)) {
        if (empty($row['Collation']) || (strpos($row['Collation'], 'utf8mb4') === false)) {
            $tablesNotOk[] = $row['Name'];
        }
    }
}
if ($dbOk && empty($tablesNotOk)) {
    $result .= "Base déjà encodée en ".SET_DEST." ";
    $result .= formatResult("Ok !","<span style='color:green;'>","</span>");
} else {
    foreach ($donneesBase as $donnees) {
        if (($donnees->Variable_name == 'character_set_database')&&($donnees->Value != SET_DEST)) {
            $result.=$donnees->Variable_name." est réglé à ".$donnees->Value."<br />";
            $result.="Passage de ".$donnees->Variable_name." à ".SET_DEST." ";
            $result_inter = traiteRequete("ALTER DATABASE  CHARACTER SET ".SET_DEST.";");
            if ($result_inter == '')
                $result .= formatResult("Ok !","<span style='color:green;'>","</span>");
            else
                $result .= $result_inter;
            $result_inter = '';
        }
    }
}
unset ($donnees, $donneesBase);

//! conversion des tables */
// recherche les tables à convertir
$donneesTable = array();

// `$liste_tables` est présumé défini (inclus ailleurs). Le `table_prefix`
// doit être défini par l'appelant ; on l'utilise directement.
$allowedTables = array();
foreach ($liste_tables as $t) {
    $allowedTables[] = $table_prefix . $t;
}
$allowedLookup = array_map('strtolower', $allowedTables);

$query = mysqli_query($GLOBALS['db_c'], "SHOW table status");
if ($query) {
    while ($row = mysqli_fetch_array($query,  MYSQLI_ASSOC)) {
        if ($row['Collation'] != SET_OK ) {
            if (in_array(strtolower($row['Name']), $allowedLookup, true)) {
                $donneesTable[] = $row['Name'];
            }
        }
    }
} else {
    die ('Erreur de lecture de la base');
}

// conversion des tables et des données
if (empty($donneesTable) ){
    $result .= "Tables déjà encodées en ".SET_DEST." ";
    $result .= formatResult("Ok !","<span style='color:green;'>","</span>");
} 
else {
    $result_inter = '';
    $ok = TRUE;
    foreach ($donneesTable as $table) {
        $result.="Passage de $table en ".SET_DEST." en cours. ";
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