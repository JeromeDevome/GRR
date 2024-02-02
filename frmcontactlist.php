<?php
/**
 * frmcontactlist.php
 * calcule la liste des ressources visibles dans un domaine
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2024-02-02 18:01$
 * @author    JeromeB & Yan Naessens
 * @copyright Copyright 2003-2024 Team DEVOME - JeromeB
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
$res = grr_sql_query("SELECT room_name,id FROM ".TABLE_PREFIX."_room WHERE area_id = ? ORDER BY room_name","i",[$id]);
$nbresult = grr_sql_count($res);
$user_name = getUserName();
if ($nbresult != 0)
{
    $a = "";
	foreach($res as $row)
	{
        $id_room = $row['id'];
        if (verif_acces_ressource($user_name,$id_room)){
            $a .= " <option value =\"$id_room\">".$row['room_name']."</option>";
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