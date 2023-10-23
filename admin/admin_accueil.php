<?php
/**
 * admin_accueil
 * Interface d'accueil de l'administration des domaines et des ressources
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2023-07-27 16:26$
 * @author    Laurent Delineau & JeromeB & Yan Naessens
 * @copyright Copyright 2003-2023 Team DEVOME - JeromeB
 * @link      http://www.gnu.org/licenses/licenses.html
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */
$grr_script_name = "admin_accueil.php";
 
include "../include/admin.inc.php";

$back = (isset($_SERVER['HTTP_REFERER']))? htmlspecialchars_decode($_SERVER['HTTP_REFERER'], ENT_QUOTES) : "../day.php" ;
$user = getUserName();
$area = getFormVar('area','int');
if (!isset($area) || ($area == 0))
    $area = -1;
$room = getFormVar('room','int');
if (!isset($room) || ($room == 0))
    $room = -1;
if ((authGetUserLevel($user, $area, 'area') < 4) && (authGetUserLevel($user, $room, 'room') < 3) && (authGetUserLevel($user, -1, 'user') !=  1))
{
	showAccessDenied($back);
	exit();
}
// existe-t-il des réservations à modérer sur le site ?
$listeModeration = resaToModerate($user);
$nbAModerer = count($listeModeration);    
// code html
start_page_w_header("", "", "", $type="with_session"); // affiche le header et la balise <section>

include "admin_col_gauche2.php";
// "colonne de droite"
// titre 
echo'    <div class="col col-md-5 col-sm-7 col-xs-12">';
echo'        <div class="center">';
echo'            <br /><br />';
echo'            <p style="font-size:20pt">';
echo get_vocab("admin");
echo'            </p>';
echo'            <p style="font-size:40pt">';
echo'                <i>GRR !</i>';
echo'            </p>';
// bouton sauvegarde
echo '<a href="admin_save_mysql.php?flag_connect=yes" class="btn btn-default">'.get_vocab("submit_backup").'</a>';
// réservations à modérer
if ($nbAModerer > 0)
{ 
    echo '<table class="table table-condensed table-bordered">';
    echo '<caption>'.$nbAModerer;
    if ($nbAModerer == 1){echo get_vocab('resaToModerate');}
    else {echo get_vocab('resasToModerate');}
    echo '</caption>';
    echo '<thead>';
    echo '<tr><th>'.get_vocab('room').'</th><th>'.get_vocab('start_of_the_booking').'</th><th>'.get_vocab('sum_by_creator').'</th><th></th>';
    echo '</thead>';
    echo '<tbody>';
    foreach($listeModeration as $no => $resa)
    {
        echo "<tr><td>".$resa['room']."</td>";
        echo "<td>".time_date_string($resa['start_time'], $dformat)."</td>";
        echo "<td>".$resa['beneficiaire']."</td>";
        echo "<td><a href='".$racine."view_entry.php?id=".$resa['id']."&mode=page'><span class='glyphicon glyphicon-new-window'></span></a></td></tr>";
    }
    echo "</tbody>";
	echo '</table>';
}
echo'        </div>    </div>';
// totem
echo'    <div class="col col-md-3 col-sm-4 col-xs-12">';
echo'        <img src="../img_grr/totem_grr.png" alt="GRR !" class="image" />';
echo'    </div>';
end_page();
?>
