<?php
/**
 * edit_entry_beneficiaire.php
 * Page "Ajax" utilisée dans edit_entry.php
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2010-04-07 17:49:56 $
 * @author    Laurent Delineau <laurent.delineau@ac-poitiers.fr>
 * @copyright Copyright 2003-2008 Laurent Delineau
 * @link      http://www.gnu.org/licenses/licenses.html
 * @package   root
 * @version   $Id: edit_entry_types.php,v 1.10 2010-04-07 17:49:56 grr Exp $
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
 * $Log: edit_entry_beneficiaire.php,v $
 *
 */
include_once "include/admin.inc.php";
/* Ce script a besoin d'un argument passés par la méthode GET :
$login : l'identifiant de l'utilisateur
*/
// Initialisation
if (isset($_GET["beneficiaire"]))
	$beneficiaire = $_GET["beneficiaire"];
else
	die();
if (isset($_GET["identifiant_beneficiaire"]))
	$identifiant_beneficiaire = $_GET["identifiant_beneficiaire"];
else
	die();
if ($unicode_encoding)
	header("Content-Type: text/html;charset=utf-8");
else
	header("Content-Type: text/html;charset=".$charset_html);
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
if ((authGetUserLevel(getUserName(),-1) < 2))
{
	showAccessDenied("");
	exit();
}
$sql = "SELECT nom, login, etat, statut FROM ".TABLE_PREFIX."_utilisateurs WHERE  (login='".$identifiant_beneficiaire."')";
$res = grr_sql_query($sql);
if ($res)
{
	$nb_result = grr_sql_count($res);
	if ($nb_result > 1)
	{
		echo "<span class=\"avertissement\">Plusieurs utilisateur ont le même identifiants que l'utilisateur ci-dessus. Signalez ce problème à l'administrateur.</span>";
	}
	else if ($nb_result == 1)
	{
		$row = grr_sql_row($res, 0);
		if ($row[2]=='inactif')
			echo "<span class=\"avertissement\">".get_vocab('utilisateur_rendu_inactif').get_vocab('login').get_vocab('deux_points').$row[1]."</span>";
		else if ($row[3]=='visiteur')
			echo "<span class=\"avertissement\">".get_vocab('utilisateur_simple_visiteur').get_vocab('login').get_vocab('deux_points').$row[1]."</span>";
	}
	else if (($nb_result == 0))
	{
		if ($identifiant_beneficiaire != "")
			echo "<span class=\"avertissement\">".get_vocab('utilisateur_supprime').get_vocab('login').get_vocab('deux_points').$identifiant_beneficiaire."</span>";
	}
}
?>
