<?php
/**
 * admin_corresp_statut.php
 * interface de gestion de la correspondance entre profil LDAP et statut GRR
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2021-03-13 11:51$
 * @author    Laurent Delineau & JeromeB & Christian Daviau & Yan Naessens
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
$grr_script_name = "admin_corresp_statut.php";

include "../include/admin.inc.php";

$back = (isset($_SERVER['HTTP_REFERER']))? htmlspecialchars_decode($_SERVER['HTTP_REFERER'], ENT_QUOTES) : "./admin_accueil.php" ;
if (Settings::get("sso_ac_corr_profil_statut") != 'y')
{
	showAccessDenied($back);
	exit();
}
check_access(5, $back);
$themessage = str_replace("'" , "\\'" , get_vocab("confirmdel"));
$themessage2 = str_replace("'" , "\\'" , get_vocab("confirm_del"));
//
// Ajout d'une correspondance fonction/statut
//
$msg = "";
if ( isset($_GET['action_add']) && ($_GET['action_add'] == 'yes'))
{
	if (($_POST['codefonc'] != "") && ($_POST['libfonc'] != "") && ($_POST['statutgrr'] != ""))
	{
		$sql = "INSERT INTO ".TABLE_PREFIX."_correspondance_statut (code_fonction, libelle_fonction, statut_grr) VALUES ('".strtoupper(protect_data_sql($_POST['codefonc']))."', '".ucfirst(protect_data_sql($_POST['libfonc']))."','".$_POST['statutgrr']."')";
		if (grr_sql_command($sql) < 0)
			fatal_error(0, "<p>" . grr_sql_error());
		else
			$msg = get_vocab("message_records");
	}
	else
		$msg = get_vocab("champs_non_remplis");
}
//
// Modification d'une correspondance fonction/statut
//
if (isset($_GET['action_mod']) && ($_GET['action_mod'] = 'yes'))
{
	if (isset($_POST['idselect']))
	{
		$select = "statut".$_POST['idselect'];
		if (($_POST['idfonc'] != "") && ($_POST[$select] != ""))
		{
			$sql = "UPDATE ".TABLE_PREFIX."_correspondance_statut SET statut_grr = '".$_POST[$select]."' WHERE id='".$_POST['idfonc']."'";
			if (grr_sql_command($sql) < 0)
				fatal_error(0, "<p>" . grr_sql_error());
			else
				$msg = get_vocab("message_records");
		}
		else
			$msg = get_vocab("champs_non_remplis");
	}
}
//
// Suppression d'une correspondance fonction/statut
//
if ((isset($_GET['action_del'])) && ($_GET['js_confirmed'] == 1) && ($_GET['action_del'] = 'yes'))
{
	$sql = "DELETE FROM ".TABLE_PREFIX."_correspondance_statut WHERE id='".$_GET['id']."'";
	if (grr_sql_command($sql) < 0)
		fatal_error(0, "<p>" . grr_sql_error());
	else
		$msg = get_vocab("message_records");
}

//print the page header
start_page_w_header("", "", "", $type="with_session");
// Affichage de la colonne de gauche
include "admin_col_gauche2.php";
// colonne de droite
echo "<div class='col-md-9 col-sm-8 col-xs-12'>";
echo "<h2>".get_vocab('admin_corresp_statut.php')."</h2>";
echo get_vocab('admin_corresp_statut_desc');
echo "<br />\n";
echo "<br />\n";
$sql = "SELECT code_fonction, libelle_fonction, statut_grr, id FROM  ".TABLE_PREFIX."_correspondance_statut";
$res = grr_sql_query($sql);
$nb_lignes = grr_sql_count($res);
if ($nb_lignes == 0)
{
	// Si aucune ligne à afficher
    // fin de l'affichage de la colonne de droite
	echo get_vocab('aucune_correspondance');
}
else
{
	// S'il y a des lignes à afficher
	// Affichage du tableau
	echo "<table border=\"1\" cellpadding=\"3\" style=\"text-align:center;vertical-align:middle;\"><tr>\n";
	echo "<td><b>".get_vocab("code_fonction")."</b></td>\n";
	echo "<td><b>".get_vocab("libelle_fonction")."</b></td>\n";
	echo "<td><b>".get_vocab("statut_grr")."</b></td>\n";
	echo "<td><b>".get_vocab("statut_grr_modif")."</b></td>\n";
	echo "<td> </td>\n";
	echo "</tr>";
	if ($res)
	{
		for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
		{
			$codefonc = $row[0];
			$libfonc = $row[1];
			$statutgrr = $row[2];
			// Affichage des numéros et descriptions
			$col[$i][1] = $codefonc;
			$col[$i][2] = $libfonc;
			$col[$i][3] = $statutgrr;
			echo "<tr>\n";
			echo "<td>{$col[$i][1]}</td>\n";
			echo "<td>{$col[$i][2]}</td>\n";
			echo "<td>{$col[$i][3]}</td>\n";
			echo "<td><form action=\"admin_corresp_statut.php?action_mod=yes\" method=\"post\">\n<div><input type=\"hidden\" name=\"idfonc\" value=\"$row[3]\" />\n<input type=\"hidden\" name=\"idselect\" value=\"$i\" />\n<select name=\"statut$i\">\n<option value=\"visiteur\">".get_vocab("statut_visitor")."</option>\n<option value=\"utilisateur\">".get_vocab("statut_user")."</option>\n<option value=\"gestionnaire_utilisateur\">".get_vocab("statut_user_administrator")."</option>\n<option value=\"administrateur\">".get_vocab("statut_administrator")."</option>\n</select><br />\n<input type=\"submit\" value=\"".get_vocab("edit")."\" /></div></form></td>\n";
			echo "<td><a href=\"admin_corresp_statut.php?id=$row[3]&amp;action_del=yes\" onclick=\"return confirmlink(this, '$themessage2', '$themessage')\" >".get_vocab("delete");
			echo "</a></td>";
			// Fin de la ligne courante
			echo "</tr>";
		}
	}
	echo "</table>";
}
echo "<br /><hr /><br /><div class='center'><b>".get_vocab("ajout_correspondance_profil_statut")."</b>\n";
echo "<br /><form action=\"admin_corresp_statut.php?action_add=yes\" method=\"post\"><div>\n";
echo get_vocab("code_fonction").get_vocab("deux_points")."<input name=\"codefonc\" type=\"text\" size=\"6\" /><br />";
echo get_vocab("libelle_fonction").get_vocab("deux_points")."<input name=\"libfonc\" type=\"text\" size=\"25\" /><br />";
echo get_vocab("statut_grr").get_vocab("deux_points");
echo "<select name=\"statutgrr\">";
echo "<option value=\"visiteur\">".get_vocab("statut_visitor")."</option>\n";
echo "<option value=\"utilisateur\">".get_vocab("statut_user")."</option>\n";
echo "<option value=\"gestionnaire_utilisateur\">".get_vocab("statut_user_administrator")."</option>\n";
echo "<option value=\"administrateur\">".get_vocab("statut_administrator")."</option>\n";
echo "</select><br /><br />\n";
echo "<input type=\"submit\" value=\"".get_vocab("OK")."\" /></div></form></div>\n";
// fin de l'affichage de la colonne de droite
echo "</div>";
// Affichage d'un pop-up
affiche_pop_up($msg,"admin");
end_page();
?>
