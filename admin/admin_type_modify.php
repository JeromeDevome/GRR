<?php
/**
 * admin_type_modify.php
 * interface de création/modification des types de réservations
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2021-09-19 14:24$
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
$grr_script_name = "admin_type_modify.php";

include "../include/admin.inc.php";

$ok = NULL;
$back = (isset($_SERVER['HTTP_REFERER']))? htmlspecialchars_decode($_SERVER['HTTP_REFERER'], ENT_QUOTES) : "./admin_accueil.php" ;
check_access(6, $back);
// Initialisation
$id_type = isset($_GET["id_type"]) ? $_GET["id_type"] : 0;
$type_name = isset($_GET["type_name"]) ? $_GET["type_name"] : NULL;
$order_display = isset($_GET["order_display"]) ? $_GET["order_display"] : NULL;
$type_letter = isset($_GET["type_letter"]) ? $_GET["type_letter"] : NULL;
$couleur_hexa = isset($_GET["couleurhexa"]) ? valid_color($_GET["couleurhexa"]) : NULL;
$disponible = isset($_GET["disponible"]) ? $_GET["disponible"] : NULL;
$couleurtexte = isset($_GET["couleurtexte"]) ? valid_color($_GET["couleurtexte"]) : "#000000";
$msg = '';

// Couleurs prédéfinies
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


if (isset($_GET["change_type_and_back"]))
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
    if ($couleurtexte == '')
        $couleurtexte = "#000000";
    if ($id_type > 0) // type existant : mise à jour
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
                $sql.=intval($order_display).",";
            else
                $sql.=" 0,";
            $sql.='type_letter="'.protect_data_sql($type_letter).'",';
            $sql.='couleur=\'1\',';
            $sql.='couleurhexa="'.protect_data_sql($couleur_hexa).'",';
            $sql.='disponible="'.protect_data_sql($disponible).'",';
            $sql.='couleurtexte="'.protect_data_sql($couleurtexte).'"';
            $sql.=" WHERE id=$id_type";
            if (grr_sql_command($sql) < 0)
            {
                fatal_error(0, get_vocab('update_type_failed') . grr_sql_error());
                $ok = 'no';
            }
            else
                $msg = get_vocab("message_records");
        }
    }
    else // nouveau type
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
                $sql.=intval($order_display).",";
            else
                $sql.=" 0,";
            $sql.='type_letter="'.protect_data_sql($type_letter).'",';
            $sql.='couleur=\'1\',';
            $sql.='couleurhexa="'.protect_data_sql($couleur_hexa).'",';
            $sql.='disponible="'.protect_data_sql($disponible).'",';
            $sql.='couleurtexte="'.protect_data_sql($couleurtexte).'"';
            if (grr_sql_command($sql) < 0)
            {
                fatal_error(1, "<p>" . grr_sql_error().$sql);
                $ok = 'no';
            }
            else
                $msg = get_vocab("message_records");
        }
    }
}
// enregistrement des couleurs dans la feuille de style
/* n'est plus utilisé
if ((isset($_GET['change_type'])) && (!isset($ok)))
{
    try {
        $fich=fopen("../themes/default/css/types.css","w+"); // première écriture
        fwrite($fich,"/* fichier de style reprenant les paramètres des types de réservation *//*");
        fclose($fich);
        $sql = "SELECT type_letter,couleurhexa,couleurtexte FROM ".TABLE_PREFIX."_type_area WHERE 1";
        $res = grr_sql_query($sql);
        $fich=fopen("../themes/default/css/types.css","a+");
        for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
        {
            fwrite($fich," 
td.type".$row[0]."{background:".$row[1]." !important;color:".$row[2]." !important;}
td.type".$row[0]." a.lienCellule{color:".$row[2]." !important;}
");
        }
        fclose($fich);
    }
    catch (Exception $e) {
        echo 'Exception reçue : ',  $e->getMessage(), "\n";
        $ok = 'no';
        die();
    }
}*/
// Si pas de problème, retour à la page d'accueil après enregistrement
if ((isset($_GET['change_done'])) && (!isset($ok)))
{
    $_SESSION['displ_msg'] = 'yes';
    Header("Location: "."admin_type.php?msg=".$msg);
    exit();
}
// les attributs du type à modifier
if ((isset($id_type)) && ($id_type > 0))
{
    $res = grr_sql_query("SELECT * FROM ".TABLE_PREFIX."_type_area WHERE id=$id_type");
    if (!$res)
        fatal_error(0, get_vocab('message_records_error'));
    $row = grr_sql_row_keyed($res, 0);
    grr_sql_free($res);
    $change_type = 'modif';
}
// type_letter déjà attribués
$res = grr_sql_query("SELECT type_letter FROM ".TABLE_PREFIX."_type_area ");
$types_predef = array();
if ($res)
    foreach($res as $t){
        if (($change_type == 'modif')&&($t['type_letter'] != $row['type_letter']))
            $types_predef[] = $t['type_letter'];
    }

// code HTML
start_page_w_header("", "", "", $type="with_session");
include "admin_col_gauche2.php";
echo "<div class=\"col-md-9 col-sm-8 col-xs-12\">";
affiche_pop_up($msg,"admin");
if ((isset($id_type)) && ($id_type > 0))
{
    echo "<h2>".get_vocab("admin_type_modify_modify.php")."</h2>";
}
else
{
    $row = array();
    $row["id"] = '0';
    $row["type_name"] = '';
    $row["type_letter"] = '';
    $row["order_display"]  = 0;
    $row["disponible"]  = 2;
    $row["couleur"]  = '';
    $row["couleurhexa"] = '';
    $row["couleurtexte"] = "#000000";
    echo "<h2>".get_vocab('admin_type_modify_create.php')."</h2>";
}
echo get_vocab('admin_type_explications')."<br /><br />";
echo '<form action="admin_type_modify.php" method="get">';
echo "<div><input type=\"hidden\" name=\"id_type\" value=\"".$id_type."\" /></div>\n";
echo "<table class='table-bordered'>\n";
    echo "<tr>";
    echo "<td>".get_vocab("type_name").get_vocab("deux_points")."</td>\n";
    echo "<td><input type=\"text\" name=\"type_name\" value=\"".clean_input($row["type_name"])."\" size=\"20\" /></td>\n";
    echo "</tr><tr>\n";
    echo "<td>".get_vocab("type_num").get_vocab("deux_points")."</td>\n";
    echo "<td>";
    echo "<select name=\"type_letter\" size=\"1\">\n";
    echo "<option value=''>".get_vocab("choose")."</option>\n";
    $letter = "A";
    for ($i = 1; $i <= 256; $i++) // limitation arbitraire, lié à tdcell()
    {
        if (!in_array($letter,$types_predef)){
            echo "<option value='".$letter."' ";
            if ($row['type_letter'] == $letter)
                echo " selected ";
            echo ">".$letter."</option>\n";
        }
        $letter++;
    }
    echo "</select>";
    echo "</td>\n";
    echo "</tr><tr>\n";
    echo "<td>".get_vocab("type_order").get_vocab("deux_points")."</td>\n";
    echo "<td><input type=\"text\" name=\"order_display\" value=\"".intval($row["order_display"])."\" size=\"20\" /></td>\n";
    echo "</tr>";
    echo "<tr><td>".get_vocab("disponible_pour").get_vocab("deux_points")."</td>\n";
    echo "<td>"."<select name=\"disponible\" size=\"1\">\n";
    echo "<option value = '2' ";
    if ($row['disponible']=='2')
        echo " selected ";
    echo ">".get_vocab("all")."</option>\n";
    echo "<option value = '3' ";
    if ($row['disponible']=='3')
        echo " selected ";
    echo ">".get_vocab("gestionnaires_et_administrateurs")."</option>\n";
    echo "<option value = '5' ";
    if ($row['disponible']=='5')
        echo " selected ";
    echo ">".get_vocab("only_administrators")."</option>\n";
    echo "</select>";
    echo "</td></tr>";
    echo "<tr><td>";
    echo get_vocab("type_apercu");
    echo "</td>";
    echo "<td style='background-color:".clean_input($row["couleurhexa"])."; color:".clean_input($row["couleurtexte"])."' class='CC' id='test'>";
    echo "<b>test</b>";
    echo '</td></tr>';
    echo "<tr>\n";
    echo "<td>".get_vocab("type_color_text").get_vocab("deux_points")."</td>\n";
    echo "<td><input name=\"couleurtexte\" id=\"fgcolor\" value='".clean_input($row['couleurtexte'])."'>";
    echo "</tr>";
    echo "<tr>\n";
    echo "<td>".get_vocab("type_color_fond").get_vocab("deux_points")."</td>\n";
    echo "<td><input name=\"couleurhexa\" id=\"bgcolor\" value='".clean_input($row['couleurhexa'])."'>";
    echo "</tr>";
    echo "</table>\n";
    echo "<p>".get_vocab("type_color_predefinie").get_vocab("deux_points")."</p>";
    echo "<table class='table table-bordered' id='couleurs_predefinies'><tr>\n";
    $nct = 0;
    foreach ($tab_couleur as $key=>$value)
    {
        if (++$nct > 7)
        {
            $nct = 1;
            echo "</tr><tr>";
        }
        echo "<td  style=\"background-color:".$value.";\"><input type=\"radio\" name=\"couleur\" value=\"".$value."\" class=\"target\" /></td>";
    }
    echo "</tr></table>\n";
    echo "<br />";
    echo "<div class='center'>\n";
    echo "<input type=\"submit\" name=\"change_type\"  value=\"".get_vocab("save")."\" />";
    echo "<input type=\"submit\" name=\"change_done\" value=\"".get_vocab("back")."\" />";
    echo "<input type=\"submit\" name=\"change_type_and_back\" value=\"".get_vocab("save_and_back")."\" />";
    echo "</div>";
echo '</form>';
echo '</div>';
?>
<script>
$( ".target" ).change(function() {
    var laCouleur = $('input[name=couleur]:checked').val();
    var textColor = document.getElementById('fgcolor').value;
    document.getElementById('bgcolor').value = laCouleur;
    document.getElementById('test').style.backgroundColor=laCouleur;
    document.getElementById('test').style.color=textColor;
    document.getElementById('bgcolor').style.backgroundColor=laCouleur;
});

var options = {
    valueElement: null,
    width: 300,
    height: 120,
    sliderSize: 20,
    borderColor: '#CCC',
    insetColor: '#CCC',
    backgroundColor: '#202020'
};

var pickers = {};

pickers.bgcolor = new jscolor('bgcolor', options);
pickers.bgcolor.onFineChange = "updateBG()";
pickers.bgcolor.fromString('<?php echo $row["couleurhexa"]; ?>');

pickers.fgcolor = new jscolor('fgcolor', options);
pickers.fgcolor.onFineChange = "updateFG()";
pickers.fgcolor.fromString('<?php echo $row["couleurtexte"]; ?>');

function updateBG() {
    document.getElementById('bgcolor').value = 
    document.getElementById('test').style.backgroundColor =
        pickers.bgcolor.toHEXString();
    var couleur = document.getElementById('couleurs_predefinies');
    if (couleur){
        var tab = new Array();
        tab = couleur.getElementsByTagName('input');
        for (i=0; i<tab.length; i++){
            if (tab[i].type == 'radio')
                tab[i].checked = false;
        }
    }
}
function updateFG() {
    document.getElementById('fgcolor').value = 
    document.getElementById('test').style.color =
    document.getElementById('test').style.borderColor =
        pickers.fgcolor.toHEXString();
    var laCouleur = $('input[name=couleur]:checked').val();
    if (laCouleur)
    {
        document.getElementById('bgcolor').value = laCouleur;
        document.getElementById('test').style.backgroundColor=laCouleur;
    }
    else 
    {
        document.getElementById('bgcolor').value = 
        document.getElementById('test').style.backgroundColor =
            pickers.bgcolor.toHEXString();
    }
}
</script>
</section>
</body>
</html>