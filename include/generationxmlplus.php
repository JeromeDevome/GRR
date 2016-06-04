<?php
/**
 * generationxmlplus.php
 *
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2009-01-20 07:19:17 $
 * @author    JeromeB
 * @copyright Copyright 201-2015 JeromeB
 * @link      http://www.gnu.org/licenses/licenses.html
 * @package   root
 * @version   $Id: generationxmlplus.php,v 1.3 2009-01-20 07:19:17 grr Exp $
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
 

$temp = time();
$result = grr_sql_query("SELECT * FROM ".TABLE_PREFIX."_entry WHERE end_time > '{$temp}';");

$export = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>\n"; 
$export.="<versionModule>1.0</versionModule>";
$export.="<RESERVATIONS>";

while($row = mysqli_fetch_array($result)){


	$beneficiaire = grr_sql_query("SELECT * FROM ".TABLE_PREFIX."_utilisateurs WHERE login = '".$row['beneficiaire']."';");
	$beneficiaire = mysqli_fetch_array($beneficiaire);

	$salle = grr_sql_query("SELECT * FROM ".TABLE_PREFIX."_room WHERE id = '".$row['room_id']."' LIMIT 1;");
	$salle = mysqli_fetch_array($salle);

		$export.="<RESERVATION>";

			$groupe = $row['beneficiaire'];
			$nom = $beneficiaire['nom'];
			$prenom = $beneficiaire['prenom'];
			$arrive = date('d/m/Y', $row['start_time']).' '.date('H:i', $row['start_time']);
			$depart = date('d/m/Y', $row['end_time']).' '.date('H:i', $row['end_time']);

			$export.="<groupe>{$groupe}</groupe>";
			$export.="<ressource>{$salle['room_name']}</ressource>";
			$export.="<description>{$row['description']}</description>";
			$export.="<nom>{$nom}</nom>";
			$export.="<prenom>{$prenom}</prenom>";
			$export.="<arrivee>{$arrive}</arrivee>";
			$export.="<depart>{$depart}</depart>";

		$export.="</RESERVATION>";
	
}
$export.="</RESERVATIONS>";

//file_put_contents("export.xml", $export);
//echo "<a href='export.xml' target='_blank'>Export database as XML</a>";

$txt_file = "./export/exportplus.xml";

$create_xml = fopen($txt_file,"w"); 

fwrite($create_xml, $export);

fclose ($create_xml);

?>