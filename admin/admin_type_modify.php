<?php
/**
 * admin_type_modify.php
 * interface de création/modification des types de réservations
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2017-12-16 14:00$
 * @author    Laurent Delineau & JeromeB
 * @copyright Copyright 2003-2018 Team DEVOME - JeromeB
 * @link      http://www.gnu.org/licenses/licenses.html
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */

include "../include/admin.inc.php";
$grr_script_name = "admin_type_modify.php";
$ok = NULL;
$back = '';
if (isset($_SERVER['HTTP_REFERER']))
	$back = htmlspecialchars($_SERVER['HTTP_REFERER']);
$day   = date("d");
$month = date("m");
$year  = date("Y");
check_access(6, $back);
// Initialisation
$id_type = isset($_GET["id_type"]) ? $_GET["id_type"] : 0;
$type_name = isset($_GET["type_name"]) ? $_GET["type_name"] : NULL;
$order_display = isset($_GET["order_display"]) ? $_GET["order_display"] : NULL;
$type_letter = isset($_GET["type_letter"]) ? $_GET["type_letter"] : NULL;
//$couleur = isset($_GET["couleur"]) ? $_GET["couleur"] : NULL;
$couleur_hexa = isset($_GET["couleurhexa"]) ? $_GET["couleurhexa"] : NULL;
$disponible = isset($_GET["disponible"]) ? $_GET["disponible"] : NULL;
$msg = '';

// Couleurs par défaut
$tab_couleur[1] = "#F49AC2"; # mauve pâle
$tab_couleur[2] = "#99CCCC"; # bleu
$tab_couleur[3] = "#FF9999"; # rose pâle
$tab_couleur[4] = "#95a5a6"; # concrete
$tab_couleur[5] = "#C0E0FF"; # bleu-vert
$tab_couleur[6] = "#FFCC99"; # pêche
$tab_couleur[7] = "#e74c3c"; # rouge
$tab_couleur[8] = "#3498db"; # bleu "aqua"
$tab_couleur[9] = "#DDFFDD"; # vert clair
$tab_couleur[10] = "#34495e"; # gris
$tab_couleur[11] = "#2ecc71"; # vert pâle
$tab_couleur[12] = "#9b59b6"; # violet
$tab_couleur[13] = "#f1c40f"; # jaune
$tab_couleur[14] = "#FF00DE"; # rose
$tab_couleur[15] = "#009900"; # vert
$tab_couleur[16] = "#e67e22"; # orange
$tab_couleur[17] = "#bdc3c7"; # gris clair
$tab_couleur[18] = "#C000FF"; # Mauve
$tab_couleur[19] = "#FF0000"; # rouge vif
$tab_couleur[20] = "#FFFFFF"; # blanc
$tab_couleur[21] = "#A0A000"; # Olive verte
$tab_couleur[22] = "#f39c12"; # marron goldenrod
$tab_couleur[23] = "#1abc9c"; # turquoise
$tab_couleur[24] = "#884DA7"; # amethyst
$tab_couleur[25] = "#4169E1"; # bleu royal
$tab_couleur[26] = "#6A5ACD"; # bleu ardoise
$tab_couleur[27] = "#AA5050"; # bordeaux
$tab_couleur[28] = "#FFBB20"; # pêche


if (isset($_GET["change_room_and_back"]))
{
	$_GET['change_type'] = "yes";
	$_GET['change_done'] = "yes";
}
// Enregistrement
if (isset($_GET['change_type']))
{
	$_SESSION['displ_msg'] = "yes";
	if ($type_name == '')
		$type_name = "A définir";
	if ($type_letter == '')
		$type_letter = "A";
	if ($couleur_hexa == '')
		$couleur_hexa = "#2ECC71";
	if ($disponible == '')
		$disponible = "2";
	if ($id_type > 0)
	{
		// Test sur $type_letter
		$test = grr_sql_query1("SELECT count(id) FROM ".TABLE_PREFIX."_type_area WHERE type_letter='".$type_letter."' AND id!='".$id_type."'");
		if ($test > 0)
			$msg = "Enregistrement impossible : Un type portant la même lettre existe déjà.";
		else
		{
			$sql = "UPDATE ".TABLE_PREFIX."_type_area SET
			type_name='".protect_data_sql($type_name)."',
			order_display =";
			if (is_numeric($order_display))
				$sql= $sql .intval($order_display).",";
			else
				$sql= $sql ."0,";
			$sql = $sql . 'type_letter="'.$type_letter.'",';
			$sql = $sql . 'couleur=\'1\',';
			$sql = $sql . 'couleurhexa="'.$couleur_hexa.'",';
			$sql = $sql . 'disponible="'.$disponible.'"';
			$sql = $sql . " WHERE id=$id_type";
			if (grr_sql_command($sql) < 0)
			{
				fatal_error(0, get_vocab('update_type_failed') . grr_sql_error());
				$ok = 'no';
			}
			else
				$msg = get_vocab("message_records");
		}
	}
	else
	{
		// Test sur $type_letter
		$test = grr_sql_query1("SELECT count(id) FROM ".TABLE_PREFIX."_type_area WHERE type_letter='".$type_letter."'");
		if ($test > 0)
			$msg = "Enregistrement impossible : Un type portant la même lettre existe déjà.";
		else
		{
			$sql = "INSERT INTO ".TABLE_PREFIX."_type_area SET
			type_name='".protect_data_sql($type_name)."',
			order_display =";
			if (is_numeric($order_display))
				$sql= $sql .intval($order_display).",";
			else
				$sql= $sql ."0,";
			$sql = $sql . 'type_letter="'.$type_letter.'",';
			$sql = $sql . 'couleur=\'1\',';
			$sql = $sql . 'couleurhexa="'.$couleur_hexa.'"';
			if (grr_sql_command($sql) < 0)
			{
				fatal_error(1, "<p>" . grr_sql_error());
				$ok = 'no';
			}
			else
				$msg = get_vocab("message_records");
		}

	}
}
// Si pas de problème, retour à la page d'accueil après enregistrement
if ((isset($_GET['change_done'])) && (!isset($ok)))
{
	$_SESSION['displ_msg'] = 'yes';
	Header("Location: "."admin_type.php?msg=".$msg);
	exit();
}
# print the page header
print_header("", "", "", $type="with_session");
include "admin_col_gauche.php";
echo "<div class=\"page_sans_col_gauche\">";
affiche_pop_up($msg,"admin");
if ((isset($id_type)) && ($id_type > 0))
{
	$res = grr_sql_query("SELECT * FROM ".TABLE_PREFIX."_type_area WHERE id=$id_type");
	if (!$res)
		fatal_error(0, get_vocab('message_records_error'));
	$row = grr_sql_row_keyed($res, 0);
	grr_sql_free($res);
	$change_type = 'modif';
	echo "<h2>".get_vocab("admin_type_modify_modify.php")."</h2>";
}
else
{
	$row["id"] = '0';
	$row["type_name"] = '';
	$row["type_letter"] = '';
	$row["order_display"]  = 0;
	$row["disponible"]  = 2;
	$row["couleur"]  = '';
	$row["couleurhexa"] = '';
	echo "<h2>".get_vocab('admin_type_modify_create.php')."</h2>";
}
echo get_vocab('admin_type_explications')."<br /><br />";
?>
<form action="admin_type_modify.php" method='get'>
	<?php
	echo "<div><input type=\"hidden\" name=\"id_type\" value=\"".$id_type."\" /></div>\n";

	echo "<table border=\"1\">\n";
	echo "<tr>";
	echo "<td>".get_vocab("type_name").get_vocab("deux_points")."</td>\n";
	echo "<td><input type=\"text\" name=\"type_name\" value=\"".htmlspecialchars($row["type_name"])."\" size=\"20\" /></td>\n";
	echo "</tr><tr>\n";
	echo "<td>".get_vocab("type_num").get_vocab("deux_points")."</td>\n";
	echo "<td>";
	echo "<select name=\"type_letter\" size=\"1\">\n";
	echo "<option value=''>".get_vocab("choose")."</option>\n";
	$letter = "A";
	for ($i = 1; $i <= 702; $i++)
	{
		echo "<option value='".$letter."' ";
		if ($row['type_letter'] == $letter)
			echo " selected=\"selected\"";
		echo ">".$letter."</option>\n";
		$letter++;
	}

	echo "</select>";
	echo "</td>\n";
	echo "</tr><tr>\n";
	echo "<td>".get_vocab("type_order").get_vocab("deux_points")."</td>\n";
	echo "<td><input type=\"text\" name=\"order_display\" value=\"".htmlspecialchars($row["order_display"])."\" size=\"20\" /></td>\n";
	echo "</tr>";
	echo "<tr><td>".get_vocab("disponible_pour").get_vocab("deux_points")."</td>\n";
	echo "<td>"."<select name=\"disponible\" size=\"1\">\n";
	echo "<option value = '2' ";
	if ($row['disponible']=='2')
		echo " selected=\"selected\"";
	echo ">".get_vocab("all")."</option>\n";
	echo "<option value = '3' ";
	if ($row['disponible']=='3')
		echo " selected=\"selected\"";
	echo ">".get_vocab("gestionnaires_et_administrateurs")."</option>\n";
	echo "<option value = '5' ";
	if ($row['disponible']=='5')
		echo " selected=\"selected\"";
	echo ">".get_vocab("only_administrators")."</option>\n";
	echo "</select>";
	echo "</td></tr>";
	if ($row["couleurhexa"]  != '')
	{
		echo "<tr>\n";
		echo "<td>".get_vocab("type_color_actuel").get_vocab("deux_points")."</td>\n";
		echo "<td bgcolor=\"".$row["couleurhexa"]."\"> </td>";
		echo "</tr>";
	}
	echo "<tr>\n";
	echo "<td>".get_vocab("type_color_hexa").get_vocab("deux_points")."</td>\n";
	echo "<td><input type=\"text\" name=\"couleurhexa\" value=\"".$row["couleurhexa"]."\" maxlength=\"7\" size=\"10\" onKeyUp='visuCouleurHexaPerso()' /><input type=\"text\" style=\"background-color:".$row["couleurhexa"].";\" name=\"visucouleurhexa\" value=\"\" size=\"10\" disabled/></td>\n";
	echo "</tr>";
	echo "</table>\n";
	echo "<p>".get_vocab("type_color_predefinie").get_vocab("deux_points")."</p>";
	echo "<table border=\"2\"><tr>\n";
	$nct = 0;
	foreach ($tab_couleur as $key=>$value)
	{
		if (++$nct > 4)
		{
			$nct = 1;
			echo "</tr><tr>";
		}
		echo "<td  style=\"background-color:".$tab_couleur[$key].";\"><input type=\"radio\" name=\"couleur\" value=\"".$tab_couleur[$key]."\" class=\"target\" />______________</td>";
	}
	echo "</tr></table>\n";
	echo "<table><tr><td>\n";
	echo "<input type=\"submit\" name=\"change_type\"  value=\"".get_vocab("save")."\" />\n";
	echo "</td><td>\n";
	echo "<input type=\"submit\" name=\"change_done\" value=\"".get_vocab("back")."\" />";
	echo "</td><td>\n";
	echo "<input type=\"submit\" name=\"change_room_and_back\" value=\"".get_vocab("save_and_back")."\" />";
	echo "</td></tr></table>";
	?>
</form>
</div>
<script>
$( ".target" ).change(function() {
	var laCouleur = $('input[name=couleur]:checked').val();
	document.getElementsByName('couleurhexa')[0].value = laCouleur;
	document.getElementsByName('visucouleurhexa')[0].style.backgroundColor=laCouleur;
});
function visuCouleurHexaPerso(laCouleur) {
	var laCouleur = document.getElementsByName('couleurhexa')[0].value;
	document.getElementsByName('visucouleurhexa')[0].style.backgroundColor=laCouleur;
}
</script>
</body>
</html>
