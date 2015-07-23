<?php
include "include/connect.inc.php";
include "include/mysql.inc.php";
include "include/misc.inc.php";

$id = $_GET['id'];
echo "<optgroup label=\"Salles\">";
$res = grr_sql_query("SELECT room_name FROM ".TABLE_PREFIX."_room WHERE area_id = '".$id."' ORDER BY room_name");
$nbresult = mysqli_num_rows($res);
if ($nbresult != 0)
{
	for ($t = 0; ($row_roomName = grr_sql_row($res, $t)); $t++)
	{
		$room_name = $row_roomName[0];
		echo " <option value =\"$room_name\">$room_name</option>";
	}
}
else
	echo " <option value =\"1\">Aucune ressource liée à ce domaine</option>";
?>
