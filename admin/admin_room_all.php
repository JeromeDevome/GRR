<?php
/**
 * admin_room_all.php
 * affichage de tous les sites de l'application GRR
 * Dernière modification : $Date: 2017-12-16 14:00$
 * @author    JeromeB & Laurent Delineau & Marc-Henri PAMISEUX & Yan Naessens
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
$grr_script_name = "admin_room_all.php";

if (!Settings::load()) {
    die('Erreur chargement settings');
}

if (!isset($id_site))
	$id_site = isset($_POST['id_site']) ? $_POST['id_site'] : (isset($_GET['id_site']) ? $_GET['id_site'] : -1);
settype($id_site,"integer");
$back = '';
if (isset($_SERVER['HTTP_REFERER']))
	$back = htmlspecialchars($_SERVER['HTTP_REFERER']);
$day   = date("d");
$month = date("m");
$year  = date("Y");
// l'accès est limité aux administrateurs
check_access(5, $back);
//print the page header
print_header("", "", "", $type="with_session");
// Affichage de la colonne de gauche
include "admin_col_gauche.php";
// Affichage d'un message éventuel
if (isset($_GET['msg']))
{
	$msg = $_GET['msg'];
	affiche_pop_up($msg,"admin");
}
// début affichage colonne droite
echo '<h2>'.get_vocab("admin_room_all.php").'</h2>';

if (Settings::get('module_multisite') == "Non")
{
    check_access(6,$back);
    $sql  = "SELECT id,area_name FROM `".TABLE_PREFIX."_area` ORDER BY order_display";
    $res = grr_sql_query($sql);
    //print_r($res);
    if (!$res){
        $msg = "erreur de lecture";
        echo "<meta http-equiv=\"refresh\" content=\"0;URL='admin_room_all.php?msg=".$msg."\">"; // à voir à l'usage
    }
    else{
        echo '<table class="table table-bordered">';
            echo '<tr>';
                echo '<th  style="text-align:center; width:50%;"><b class="titre">'.get_vocab('areas').'</b></th>';
                echo '<th  style="text-align:center; width:50%;"><b class="titre">'.get_vocab('rooms').' :</b></th>';
            echo '</tr>';
            if (grr_sql_count($res) != 0)
            {  // il y a des domaines à afficher
                for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
                {
					echo "<tr>";
                        echo "<td>";
                            echo $row[1]; // affiche le nom du domaine
                        echo "</td>";
                        echo "<td>";
                            $id_area = $row[0];
                            $sql1 = "SELECT room_name FROM ".TABLE_PREFIX."_room where area_id=$id_area ";
                            $res1 = grr_sql_query($sql1);
                            if (!$res1){
                                $msg = "erreur de lecture";
                                echo "<meta http-equiv=\"refresh\" content=\"0;URL='admin_room_all.php?msg=".$msg."\">"; // à voir à l'usage
                            }
                            else{
                                if (grr_sql_count($res1) == 0){
                                    echo get_vocab('no_rooms_for_area');
                                }
                                else{ //afficher les ressources du domaine
                                    for ($j=0; ($row1 = grr_sql_row($res1, $j)); $j++){
                                        echo $row1[0]."<br>";
                                    }
                                }
                            }
                        echo "</td>";
                    echo "</tr>";
                }
            }
            else{
                echo get_vocab($noarea);
            }
        echo '</table>';
    }
}
else{
    echo 'multisite activé';
}

// fin de l'affichage de la colonne de droite
echo "</td></tr></table>\n";
	
echo '</body>';
echo '</html>';
?>       