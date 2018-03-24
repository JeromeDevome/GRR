<?php
include "include/connect.inc.php";
include "include/mysql.inc.php";
include "include/misc.inc.php";
include "include/functions.inc.php";

$id = $_GET['id'];
echo "<optgroup label=\"Salles\">";
$res = grr_sql_query("SELECT room_name,id FROM ".TABLE_PREFIX."_room WHERE area_id = '".$id."' ORDER BY room_name");
$nbresult = mysqli_num_rows($res);
$user_name = getUserName();
if ($nbresult != 0)
{
    $a = "";
	for ($t = 0; ($row_roomName = grr_sql_row($res, $t)); $t++)
	{
        $id_room = $row_roomName[1];
        if (verif_acces_ressource($user_name,$id_room)){
            $room_name = $row_roomName[0];
            $a .= " <option value =\"$room_name\">$room_name</option>";
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
