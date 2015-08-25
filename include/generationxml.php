<?php
/**
 * generationxml.php
 *
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2009-01-20 07:19:17 $
 * @author    JeromeB
 * @copyright Copyright 201-2015 JeromeB
 * @link      http://www.gnu.org/licenses/licenses.html
 * @package   root
 * @version   $Id: generationxml.php,v 1.3 2009-01-20 07:19:17 grr Exp $
 * @filesource
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GRR is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GRR; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */
 
 /*
if (@file_exists('../include/connect.inc.php')){
	$racine = "../";
}else{
	$racine = "./";
}

	include $racine."include/connect.inc.php";
	include $racine."include/config.inc.php";
	include $racine."include/mrbs_sql.inc.php";
	include $racine."include/misc.inc.php";
	include $racine."include/functions.inc.php";
	include $racine."include/$dbsys.inc.php";
*//*
// Settings
require_once($racine."include/settings.class.php");
//Chargement des valeurs de la table settingS
if (!Settings::load())
	die("Erreur chargement settings");
// Session related functions
require_once($racine."include/session.inc.php");
// Resume session
if (!grr_resumeSession()) {
	header("Location: {$racine}logout.php?auto=1&url=$url");
	die();
};
// Paramètres langage
$use_admin = 'y';
include $racine."include/language.inc.php";
*/
$temp = time();
$result = grr_sql_query("SELECT * FROM ".TABLE_PREFIX."_entry WHERE end_time > '{$temp}';");

$export = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>\n"; 
$export="<RESERVATIONS>";

while($row = mysqli_fetch_array($result)){


	$beneficiaire = grr_sql_query("SELECT * FROM ".TABLE_PREFIX."_utilisateurs WHERE login = '".$row['beneficiaire']."';");
	$beneficiaire = mysqli_fetch_array($beneficiaire);

		$export.="<RESERVATION>";

			$groupe = $row['beneficiaire'];
			$nom = $beneficiaire['nom'];
			$prenom = $beneficiaire['prenom'];
			$arrive = date('d/m/Y', $row['start_time']).' '.date('H:i', $row['start_time']);
			$depart = date('d/m/Y', $row['end_time']).' '.date('H:i', $row['end_time']);

			$export.="<GROUPE>{$groupe}</GROUPE>";
			$export.="<NOM>{$nom}</NOM>";
			$export.="<PRENOM>{$prenom}</PRENOM>";
			$export.="<ARRIVEE>{$arrive}</ARRIVEE>";
			$export.="<DEPART>{$depart}</DEPART>";

		$export.="</RESERVATION>";
	
}
$export.="</RESERVATIONS>";

//file_put_contents("export.xml", $export);
//echo "<a href='export.xml' target='_blank'>Export database as XML</a>";

$txt_file = "./export/export.xml";

$create_xml = fopen($txt_file,"w"); 

fwrite($create_xml, $export);

fclose ($create_xml);

?>