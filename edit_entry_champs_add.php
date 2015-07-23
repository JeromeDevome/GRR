<?php
/**
 * edit_entry_champs_add.php
 * Page "Ajax" utilisée pour générer les champs additionnels dans la page de réservation
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2009-09-29 18:02:56 $
 * @author    Laurent Delineau <laurent.delineau@ac-poitiers.fr>
 * @copyright Copyright 2003-2008 Laurent Delineau
 * @link      http://www.gnu.org/licenses/licenses.html
 * @package   root
 * @version   $Id: edit_entry_champs_add.php,v 1.6 2009-09-29 18:02:56 grr Exp $
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
/**
 * $Log: edit_entry_champs_add.php,v $
 * Revision 1.6  2009-09-29 18:02:56  grr
 * *** empty log message ***
 *
 * Revision 1.5  2008-11-16 22:00:58  grr
 * *** empty log message ***
 *
 * Revision 1.4  2008-11-11 22:01:14  grr
 * *** empty log message ***
 *
 *
 */
include "include/admin.inc.php";
/* Ce script a besoin de trois arguments passés par la méthode GET :
$id : l'identifiant de la réservation (0 si nouvelle réservation)
$areas : l'identifiant du domaine
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
if (isset($_GET['areas']))
{
	$areas = $_GET['areas'];
	settype($areas,"integer");
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
if ((authGetUserLevel(getUserName(), -1) < 2) && (auth_visiteur(getUserName(), $room) == 0))
{
	showAccessDenied("");
	exit();
}
if (authUserAccesArea(getUserName(), $areas) == 0)
{
	showAccessDenied("");
	exit();
}
// Champs additionneles : on récupère les données de la réservation si il y en a
if ($id != 0)
	$overload_data = mrbsEntryGetOverloadDesc($id);
if ($unicode_encoding)
	header("Content-Type: text/html;charset=utf-8");
else
	header("Content-Type: text/html;charset=".$charset_html);
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
// Boucle sur les areas
$overload_fields = mrbsOverloadGetFieldslist($areas);
foreach ($overload_fields as $fieldname=>$fieldtype)
{
	if ($overload_fields[$fieldname]["obligatoire"] == "y")
		$flag_obli = " *" ;
	else
		$flag_obli = "";
	echo "<table width=\"100%\" id=\"id_".$areas."_".$overload_fields[$fieldname]["id"]."\">";
	echo "<tr><td class=E><b>".removeMailUnicode($fieldname).$flag_obli."</b></td></tr>\n";
	if (isset($overload_data[$fieldname]["valeur"]))
		$data = $overload_data[$fieldname]["valeur"];
	else
		$data = "";
	if ($overload_fields[$fieldname]["type"] == "textarea" )
		echo "<tr><td><div class=\"col-xs-12\"><textarea class=\"form-control\" cols=\"80\" rows=\"2\" name=\"addon_".$overload_fields[$fieldname]["id"]."\">".htmlspecialchars($data)."</textarea></div></td></tr>\n";
	else if ($overload_fields[$fieldname]["type"] == "text" )
		echo "<tr><td><div class=\"col-xs-12\"><input class=\"form-control\" size=\"80\" type=\"text\" name=\"addon_".$overload_fields[$fieldname]["id"]."\" value=\"".htmlspecialchars($data)."\" /></div></td></tr>\n";
	else if ($overload_fields[$fieldname]["type"] == "numeric" )
		echo "<tr><td><div class=\"col-xs-12\"><input class=\"form-control\" size=\"20\" type=\"text\" name=\"addon_".$overload_fields[$fieldname]["id"]."\" value=\"".htmlspecialchars($data)."\" /></div></td></tr>\n";
	else
	{
		echo "<tr><td><div class=\"col-xs-12\"><select class=\"form-control\" name=\"addon_".$overload_fields[$fieldname]["id"]."\" size=\"1\">\n";
		if ($overload_fields[$fieldname]["obligatoire"] == 'y')
			echo '<option value="">'.get_vocab('choose').'</option>';
		foreach ($overload_fields[$fieldname]["list"] as $value)
		{
			echo "<option ";
			if (htmlspecialchars($data) == trim($value,"&") || ($data == "" && $value[0]=="&"))
				echo " selected=\"selected\"";
			echo ">".trim($value,"&")."</option>\n";
		}
		echo "</select></div>\n</td></tr>\n";
	}
	echo "</table>\n";
}
?>
