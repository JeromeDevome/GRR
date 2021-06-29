<?php
/**
 * admin_type_area.php
 * interface de gestion des types de réservations pour un domaine
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2021-03-13 11:36$
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
$grr_script_name = "admin_type_area.php";

include "../include/admin.inc.php";

// Initialisation
$back = (isset($_SERVER['HTTP_REFERER']))? htmlspecialchars_decode($_SERVER['HTTP_REFERER'], ENT_QUOTES) : "./admin_accueil.php" ;
$day   = date("d");
$month = date("m");
$year  = date("Y");
check_access(4, $back);
$id_area = isset($_GET["id_area"]) ? intval($_GET["id_area"]) : NULL;

// Gestion du retour à la page précédente sans enregistrement
if (isset($_GET['change_done']))
{
	Header("Location: "."admin_room.php?id_area=".$id_area);
	exit();
}
if ((isset($_GET['msg'])) && isset($_SESSION['displ_msg']) && ($_SESSION['displ_msg'] == 'yes') )
	$msg = $_GET['msg'];
else
	$msg = '';

$sql = "SELECT id, type_name, order_display, couleurhexa, type_letter, couleurtexte FROM ".TABLE_PREFIX."_type_area
ORDER BY order_display, type_letter";
//
// Enregistrement
//
if (isset($_GET['valider']))
{
	$res = grr_sql_query($sql);
	$nb_types_valides = 0;
	if ($res)
	{
		for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
		{
			if (isset($_GET[$row[0]]))
			{
				$nb_types_valides ++;
				$del = grr_sql_query("DELETE FROM ".TABLE_PREFIX."_j_type_area WHERE id_area='".$id_area."' AND id_type = '".$row[0]."'");
			}
			else
			{
				$type_si_aucun = $row[0];
				$test = grr_sql_query1("SELECT count(id_type) FROM ".TABLE_PREFIX."_j_type_area WHERE id_area = '".$id_area."' AND id_type = '".$row[0]."'");
				if ($test == 0)
				{
					//faire le test si il existe une réservation en cours avec ce type de réservation
					//$type_id = grr_sql_query1("select type_letter from ".TABLE_PREFIX."_type_area where id = '".$row[0]."'");
					//$test1 = grr_sql_query1("select count(id) from ".TABLE_PREFIX."_entry where type= '".$type_id."'");
					//$test2 = grr_sql_query1("select count(id) from ".TABLE_PREFIX."_repeat where type= '".$type_id."'");
					//if (($test1 != 0) or ($test2 != 0)) {
					//$msg =  "Suppression impossible : des réservations ont été enregistrées avec ce type.";
					//} else {
					$sql1 = "INSERT INTO ".TABLE_PREFIX."_j_type_area SET id_area='".$id_area."', id_type = '".$row[0]."'";
					if (grr_sql_command($sql1) < 0)
						fatal_error(1, "<p>" . grr_sql_error());
					//}
				}
			}
		}
	}
	if ($nb_types_valides == 0)
	{
		// Aucun type n'a été sélectionné. Dans ce cas, on impose au moins un type :
		$del = grr_sql_query("DELETE FROM ".TABLE_PREFIX."_j_type_area WHERE id_area='".$id_area."' AND id_type = '".$type_si_aucun."'");
		$msg = "Vous devez au définir au moins un type valide !";
	}
	// Type par défaut :
	// On enregistre le nouveau type par défaut :
	$reg_type_par_defaut = grr_sql_query("UPDATE ".TABLE_PREFIX."_area SET id_type_par_defaut='".$_GET['id_type_par_defaut']."' WHERE id='".$id_area."'");
}

// code HTML
start_page_w_header("", "", "", $type="with_session");
include "admin_col_gauche2.php";
affiche_pop_up($msg,"admin");
$area_name = grr_sql_query1("SELECT area_name FROM ".TABLE_PREFIX."_area WHERE id='".$id_area."'");
echo "<div class=\"col-md-9 col-sm-8 col-xs-12\">";
echo "<h2>".get_vocab('admin_type.php')."</h2>";
echo "<h3>".get_vocab("match_area").get_vocab('deux_points')." ".$area_name."</h3>";
$res = grr_sql_query($sql);
$nb_lignes = grr_sql_count($res);
if ($nb_lignes == 0)
{
	echo "</div></section></body></html>";
	die();
}
echo "<form action=\"admin_type_area.php\" id=\"type\" method=\"get\">\n";
echo "<p>";
if (authGetUserLevel(getUserName(),-1) >= 6)
	echo "<a href=\"admin_type_modify.php?id=0\">".get_vocab("display_add_type")."</a>";
echo "</p><p>".get_vocab("explications_active_type")."</p>";

// Affichage du tableau
echo "<table class='table table-bordered'><tr>\n";
echo "<td><b>".get_vocab("type_num")."</b></td>\n";
echo "<td><b>".get_vocab("type_name")."</b></td>\n";
echo "<td><b>".get_vocab("type_apercu")."</b></td>\n";
echo "<td><b>".get_vocab("type_order")."</b></td>\n";
echo "<td><b>".get_vocab("type_valide_domaine")."</b></td>";
echo "<td><b>".get_vocab("type_par_defaut")."</b></td>";
echo "</tr>";
if ($res)
{
	for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
	{
		$id_type        = $row[0];
		$type_name      = $row[1];
		$order_display     = $row[2];
		$couleur = $row[3];
		$type_letter = $row[4];
        $couleurtexte = $row[5];
		// Affichage des numéros et descriptions
		$col[$i][1] = $type_letter;
		$col[$i][2] = $id_type;
		$col[$i][3] = $type_name;
		// Affichage de l'ordre
		$col[$i][4]= $order_display;
		$col[$i][5]= $couleur;
		echo "<tr>\n";
		echo "<td>{$col[$i][1]}</td>\n";
		echo "<td>{$col[$i][3]}</td>\n";
		echo "<td style=\"background-color:".$couleur."; color:".$couleurtexte."\">".$type_name."</td>\n";
		echo "<td>{$col[$i][4]}</td>\n";
		echo "<td><input type=\"checkbox\" name=\"".$col[$i][2]."\" value=\"y\" ";
		$test = grr_sql_query1("SELECT count(id_type) FROM ".TABLE_PREFIX."_j_type_area WHERE id_area = '".$id_area."' AND id_type = '".$row[0]."'");
		if ($test < 1)
			echo " checked=\"checked\"";
		echo " /></td>";
		echo "<td><input type=\"radio\" name=\"id_type_par_defaut\" value=\"".$col[$i][2]."\" ";
		$test = grr_sql_query1("SELECT id_type_par_defaut FROM ".TABLE_PREFIX."_area WHERE id = '".$id_area."'");
		if ($test == $col[$i][2])
			echo " checked=\"checked\"";
		echo " /></td>";
		// Fin de la ligne courante
		echo "</tr>";
	}
	echo "<tr><td> </td>\n";
	echo "<td> </td>\n";
	echo "<td> </td>\n";
	echo "<td> </td>\n";
	echo "<td> </td>\n";
	echo "<td><input type=\"radio\" name=\"id_type_par_defaut\" value=\"-1\" ";
	$test = grr_sql_query1("SELECT id_type_par_defaut FROM ".TABLE_PREFIX."_area WHERE id = '".$id_area."'");
	if ($test <= 0)
		echo " checked=\"checked\"";
	echo " />".$vocab["nobody"]."    </td>";
	echo "</tr>";
}
echo "</table>";
echo "<div class='center'><input type=\"hidden\" name=\"id_area\" value=\"".$id_area."\" />";
echo "<input type=\"submit\" name=\"valider\" value=\"".get_vocab("save")."\" />\n";
echo "   <input type=\"submit\" name=\"change_done\" value=\"".get_vocab("back")."\" />";
echo "</div>";
echo "</form>\n";
echo "</div>";
end_page();
?>