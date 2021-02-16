<?php
/**
 * edit_entry_champs_add.php
 * Page "Ajax" utilisée pour générer les champs additionnels dans la page de réservation
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2021-02-16 11:52$
 * @author    Laurent Delineau & JeromeB & Yan Naessens
 * @copyright Copyright 2003-2021 Team DEVOME - JeromeB
 * @link      http://www.gnu.org/licenses/licenses.html
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */
include "include/admin.inc.php";
/* Ce script a besoin de trois arguments passés par la méthode GET :
$id : l'identifiant de la réservation (0 si nouvelle réservation)
$area : l'identifiant du domaine
$room : l'identifiant de la ressource
*/
// Initialisation
if (isset($_GET["id"]))
{
	$id = $_GET["id"];
	settype($id,"integer");
}
else
	die();
if (isset($_GET['area']))
{
	$area = $_GET['area'];
	settype($area,"integer");
}
else
	die();
if (isset($_GET['room']))
{
	$room = $_GET['room'];
	if ($room != "")
		settype($room,"integer");
}
else
	die();
if (isset($_GET['overloadFields']))
    $overloadFields = $_GET['overloadFields'];
else 
    $overloadFields = array();
if ((authGetUserLevel(getUserName(), -1) < 2) && (auth_visiteur(getUserName(), $room) == 0))
{
	showAccessDenied("");
	exit();
}
if (authUserAccesArea(getUserName(), $area) == 0)
{
	showAccessDenied("");
	exit();
}
// Champs additionnels : on récupère les données de la réservation si il y en a
if ($id != 0)
	$overload_data = mrbsEntryGetOverloadDesc($id);
if ($unicode_encoding)
	header("Content-Type: text/html;charset=utf-8");
else
	header("Content-Type: text/html;charset=".$charset_html);
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
// Boucle sur les champs additionnels de l'area
$overload_fields = mrbsOverloadGetFieldslist($area);
foreach ($overload_fields as $fieldname=>$fieldtype)
{
	if ($overload_fields[$fieldname]["obligatoire"] == "y")
		$flag_obli = " *" ;
	else
		$flag_obli = "";
	echo "<div id=\"id_".$area."_".$overload_fields[$fieldname]["id"]."\" class='form-group'>";
	echo "<label for='addon_".$overload_fields[$fieldname]["id"]."'>".removeMailUnicode($fieldname).$flag_obli."</label>\n";
	if (isset($overload_data[$fieldname]["valeur"]))
		$data = $overload_data[$fieldname]["valeur"];
    elseif (isset($overloadFields[$overload_fields[$fieldname]["id"]]))
        $data = $overloadFields[$overload_fields[$fieldname]["id"]];
	else
		$data = "";
	if ($overload_fields[$fieldname]["type"] == "textarea" )
		echo "<div class=\"col-xs-12\"><textarea class=\"form-control\" name=\"addon_".$overload_fields[$fieldname]["id"]."\">".htmlspecialchars($data,ENT_SUBSTITUTE)."</textarea></div>\n";
	else if ($overload_fields[$fieldname]["type"] == "text" )
		echo "<input class=\"form-control\" type=\"text\" name=\"addon_".$overload_fields[$fieldname]["id"]."\" value=\"".htmlspecialchars($data,ENT_SUBSTITUTE)."\" />";
	else if ($overload_fields[$fieldname]["type"] == "numeric" )
		echo "<input class=\"form-control\" size=\"20\" type=\"text\" name=\"addon_".$overload_fields[$fieldname]["id"]."\" value=\"".htmlspecialchars($data,ENT_SUBSTITUTE)."\" />\n";
	else
	{
		echo "<div class=\"col-xs-12\"><select class=\"form-control\" name=\"addon_".$overload_fields[$fieldname]["id"]."\" size=\"1\">\n";
		if ($overload_fields[$fieldname]["obligatoire"] == 'y')
			echo '<option value="">'.get_vocab('choose').'</option>';
		foreach ($overload_fields[$fieldname]["list"] as $value)
		{
			echo "<option ";
			if (htmlspecialchars($data) == trim($value,"&") || ($data == "" && $value[0]=="&"))
				echo " selected=\"selected\"";
			echo ">".trim($value,"&")."</option>\n";
		}
		echo "</select></div>\n";
	}
	echo "</div>\n";
}
?>