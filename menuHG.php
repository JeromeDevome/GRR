<?php
/**
 * menuHG.php
 * Menus haut et gauche calendrier & domaines & ressource & légende
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2022-06-19 15:54$
 * @author    Laurent Delineau & JeromeB & Yan Naessens
 * @copyright Copyright 2003-2022 Team DEVOME - JeromeB
 * @link      http://www.gnu.org/licenses/licenses.html
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */

if ($_GET['pview'] != 1) // en mode prévisualisation de page imprimable, on n'affiche pas les menus
{
	$path = isset($_SERVER['PHP_SELF'])? $_SERVER['PHP_SELF']:(isset($_SERVER['SCRIPT_NAME'])? $_SERVER['SCRIPT_NAME']:"day");
	$file = basename($path);
    $pageActuel = str_replace(".php","",$file);
    // détermine le contexte d'appel : jour, semaine ou mois
    $pageSimple = str_replace(".php","",$file);
    $pageSimple = str_replace("_all","",$pageSimple);
    $pageSimple = str_replace("2","",$pageSimple);
    if ($pageSimple == "day") 
        $pageTout = "day.php";
    else 
        $pageTout = $pageSimple."_all.php";
    // les sélecteurs de ressource
    if (isset($_SESSION['default_list_type']) || (Settings::get("authentification_obli") == 1))
        $area_list_format = $_SESSION['default_list_type'];
    else
        $area_list_format = Settings::get("area_list_format");
    $selecteursH = "";
    $selecteursG = "";
    if ($area_list_format != "list")
    {
        if ($area_list_format == "select")
        {
            $selecteursH .= make_site_select_html($pageTout, $id_site, $year, $month, $day, getUserName(),"H");
            $selecteursH .= make_area_select_html($pageTout, $id_site, $area, $year, $month, $day, getUserName(),"H");
            $selecteursH .= make_room_select_html($pageSimple, $area, $room, $year, $month, $day,"H");
            $selecteursG .= make_site_select_html($pageTout, $id_site, $year, $month, $day, getUserName(),"G");
            $selecteursG .= make_area_select_html($pageTout, $id_site, $area, $year, $month, $day, getUserName(),"G");
            $selecteursG .= make_room_select_html($pageSimple, $area, $room, $year, $month, $day,"G");
        }
        else
        {
            $selecteurs = "";
            $selecteurs .= make_site_item_html($pageTout, $id_site, $year, $month, $day, getUserName());
            $selecteurs .= make_area_item_html($pageTout,$id_site, $area, $year, $month, $day, getUserName());
            $selecteurs .= make_room_item_html($pageSimple, $area, $room, $year, $month, $day);
            $selecteursG = $selecteurs;
            $selecteursH = $selecteurs;
        }
    }
    else
    {
        $selecteurs = "";
        $selecteurs .= make_site_list_html($pageTout,$id_site,$year,$month,$day,getUserName());
        $selecteurs .= make_area_list_html($pageTout,$id_site, $area, $year, $month, $day, getUserName());
        $selecteurs .= make_room_list_html($pageSimple, $area, $room, $year, $month, $day);
        $selecteursG = $selecteurs;
        $selecteursH = $selecteurs;
    }
	//récupération des valeurs 
	$bday = date('%d', Settings::get('begin_bookings'));
    $bmonth = date('%m', Settings::get('begin_bookings'));
	$byear = date('%Y', Settings::get('begin_bookings'));
	
	$eday = date('%d', Settings::get('end_bookings'));
    $emonth = date('%m', Settings::get('end_bookings'));
	$eyear = date('%Y', Settings::get('end_bookings'));
    // le menu haut
    echo "<div id ='menuHaut' class='row'>";
    echo "<div id ='resource_selectorH' class='col-lg-2 col-md-3 col-xs-12'>";
    echo $selecteursH;
    echo "</div>";
    if(Settings::get('select_date_directe') == 'y'){
        echo "<form method='GET' action='day.php' style='text-align: left;'>";
        jQuery_DatePicker('');
        echo "<input type='hidden' name='area' value='$area'>";
        echo "<button class='btn btn-default btn-sm' type='submit'>
        <span class='glyphicon glyphicon-chevron-right'></span>
        </button>";
        echo "</form>";
    }
    echo "<div id ='calendriersH' class='col-lg-8 col-md-6 col-xs-12'>";
    minicals($year, $month, $day, $area, $room, $pageActuel);
    echo "</div>";
    if (Settings::get('legend') == '0'){
        echo "<div id ='legendeH' class='col-lg-2 col-md-3 col-xs-12'>";
        show_colour_key($area);
        echo "</div>";
    }
    echo "</div>";
     // le menu gauche
    echo "<div id='menuGauche2'>";
        // Selection date
        if(Settings::get('select_date_directe') == 'y'){
            echo "<form method='GET' action='day.php'><center>";
            jQuery_DatePicker('');
            echo "<input type='hidden' name='area' value='$area'>";
            echo "<button class='btn btn-default btn-sm' type='submit'>
            <span class='glyphicon glyphicon-chevron-right'></span>
            </button>";
            echo "</center></form>";
        }

        // Mini calendrier(s)
        echo "<div id ='calendriersG'>";
        minicals($year, $month, $day, $area, $room, $pageActuel);
        echo "</div>";
        // Sites, domaine et ressources
        echo "<div class='col-lg-12 col-md-12 col-xs-12'>";
        echo "<div id ='resource_selectorG'>";
        echo $selecteursG;
        echo "</div>";
        // Légende
        if (Settings::get('legend') == '0'){
            echo "<div id ='legendeG'>";
            show_colour_key($area);
            echo "</div>";
        }
        //
    echo "</div>";
    echo "</div>";
}
// à associer à un script JS gérant l'affichage du menu haut/gauche selon les paramètres et le contexte
?>