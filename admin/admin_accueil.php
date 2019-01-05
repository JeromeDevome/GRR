<?php
/**
 * admin_accueil
 * Interface d'accueil de l'administration des domaines et des ressources
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2018-08-19 15:15$
 * @author    Laurent Delineau & JeromeB & Yan Naessens
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
$grr_script_name = "admin_accueil.php";
 
include "../include/admin.inc.php";
$back = '';
$user = getUserName();
if (isset($_SERVER['HTTP_REFERER']))
	$back = htmlspecialchars($_SERVER['HTTP_REFERER']);
if ((authGetUserLevel($user, -1, 'area') < 4) && (authGetUserLevel($user, -1, 'user') !=  1))
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
// titre et réservations à modérer
echo'    <div class="col-md-3 col-sm-4 col-xs-12">';
echo'        <div class="center">';
echo'            <br /><br />';
echo'            <p style="font-size:20pt">';
echo get_vocab("admin");
echo'            </p>';
echo'            <p style="font-size:40pt">';
echo'                <i>GRR !</i>';
echo'            </p>';
if ($nbAModerer > 0)
{ 
    echo '<table class="table table-condensed">';
    echo '<caption>'.$nbAModerer.' réservation';
    if ($nbAModerer > 1){echo "s";}
    echo ' à modérer</caption>';
    echo '<tbody>';
    foreach($listeModeration as $no => $resa)
    {
        echo "<tr><td>".$resa['room']."</td>";
        echo "<td>".time_date_string($resa['start_time'], $dformat)."</td>";
        echo "<td><a href='".$racine."view_entry.php?id=".$resa['id']."'><span class='glyphicon glyphicon-new-window'></span></a></td></tr>";
    }
    echo "</tbody>";
	echo '</table>';
}
echo'        </div>    </div>';
// totem
echo'    <div class="col-md-3 col-sm-4 col-xs-12">';
echo'        <img src="../img_grr/totem_grr.png" alt="GRR !" class="image" />';
echo'    </div>';
end_page();
?>
