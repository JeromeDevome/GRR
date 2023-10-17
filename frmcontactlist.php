<?php
/**
 * frmcontactlist.php
 * calcule la liste des ressources visibles dans un domaine
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2023-10-17 18:41$
 * @author    JeromeB & Yan Naessens
 * @copyright Copyright 2003-2023 Team DEVOME - JeromeB
 * @link      http://www.gnu.org/licenses/licenses.html
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */

include "include/connect.inc.php";
include "include/mysql.inc.php";
include "include/misc.inc.php";
include "include/functions.inc.php";

$id = $_GET['id'];
if ($id != protect_data_sql($id))
    die('Donnée incorrecte');
$res = grr_sql_query("SELECT room_name,id FROM ".TABLE_PREFIX."_room WHERE area_id = '".protect_data_sql($id)."' ORDER BY room_name");
$nbresult = grr_sql_count($res);
$user_name = getUserName();
if ($nbresult != 0)
{
    $a = "";
	for ($t = 0; ($row_roomName = grr_sql_row($res, $t)); $t++)
	{
        $id_room = $row_roomName[1];
        if (verif_acces_ressource($user_name,$id_room)){
            $room_name = $row_roomName[0];
            $a .= " <option value =\"$id_room\">$room_name</option>";
        }
	}
    if ($a != "")
        echo $a;
    else 
        echo " <option value =\"1\">Aucune ressource accessible dans ce domaine</option>";
}
else
	echo " <option value =\"1\">Aucune ressource liée à ce domaine</option>";
?>