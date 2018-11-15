<?php
/**
 * menu_gauche.php
 * Menu calendrier & domaines & ressource & légende
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2018-06-11 12:00$
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

if ($_GET['pview'] != 1)
{
	$path = $_SERVER['PHP_SELF'];
	$file = basename ($path);
    echo "<div id='menuGauche2'>";
	if ( $file== 'month_all2.php' or Settings::get("menu_gauche") == 0){
		echo "<script>";
        // echo "cacherMenuGauche()";
        // echo "afficheMenuGauche(0)";
        echo 'document.getElementById("menuGauche2").style.display = "none"';
        echo "</script>";
    }
    elseif (Settings::get("menu_gauche") == 2){
        echo "<script>";
        echo 'document.getElementById("menuGauche2").style.width = "100%"';
        // echo "afficheMenuGauche(2)";
        echo "</script>";
    }
    else {
        echo "<script>";
        echo 'document.getElementById("menuGauche2").style.display = "inline-block"';
        echo "</script>";
    }
    /*    
        echo '<div id="menuGaucheMonthAll2">';
	} elseif ( Settings::get("menu_gauche") == 2){
		echo '<div class="col-lg-12 col-md-12 col-xs-12">';
	} else{
		echo '<div class="col-lg-3 col-md-12 col-xs-12">';
	}
	echo '<div id="menuGauche">'; */

	$pageActuel = str_replace(".php","",basename($_SERVER['PHP_SELF']));
    // détermine le contexte d'appel : jour, semaine ou mois
    $pageSimple = str_replace(".php","",$file);
    $pageSimple = str_replace("_all","",$pageSimple);
    $pageSimple = str_replace("2","",$pageSimple);
    if ($pageSimple == "day") {
        $pageTout = "day.php";
    }
    else $pageTout = $pageSimple."_all.php";
    // $pageSimple .= '.php';
	
    // Calendrier
	minicals($year, $month, $day, $area, $room, $pageActuel);
	
	// Liste sites, domaines, ressources
	if (isset($_SESSION['default_list_type']) || (Settings::get("authentification_obli") == 1))
		$area_list_format = $_SESSION['default_list_type'];
	else
		$area_list_format = Settings::get("area_list_format");

	if(Settings::get("menu_gauche") == 2){
		echo "\n<div class=\"col-lg-3 col-md-4 col-xs-12\">\n".PHP_EOL;
	} else{
		echo "\n<div class=\"col-lg-12 col-md-12 col-xs-12\">\n".PHP_EOL;
	}

	if ($area_list_format != "list")
	{
		if ($area_list_format == "select")
		{
			echo make_site_select_html($pageTout, $id_site, $year, $month, $day, getUserName());
			echo make_area_select_html($pageTout, $id_site, $area, $year, $month, $day, getUserName());
			echo make_room_select_html($pageSimple, $area, $room, $year, $month, $day);
		}
		else
		{
			echo make_site_item_html($pageTout, $id_site, $year, $month, $day, getUserName());
			echo make_area_item_html($pageTout,$id_site, $area, $year, $month, $day, getUserName());
			echo make_room_item_html($pageSimple, $area, $room, $year, $month, $day);
		}
	}
	else
	{
		echo make_site_list_html($pageTout,$id_site,$year,$month,$day,getUserName());
		echo make_area_list_html($pageTout,$id_site, $area, $year, $month, $day, getUserName());
		echo make_room_list_html($pageSimple, $area, $room, $year, $month, $day);
	}

	echo "\n</div>\n".PHP_EOL;

	//Legende
	if (Settings::get("legend") == '0'){
		if(Settings::get("menu_gauche") == 2){
			echo "\n<div class=\"col-lg-3 col-md-4 col-xs-12\">\n".PHP_EOL;
		} else{
			echo "\n<div class=\"col-lg-12 col-md-12 col-xs-12\">\n".PHP_EOL;
		}
		show_colour_key($area);
		echo "\n</div>\n".PHP_EOL;
	}

	//
	echo '</div>';
	// echo '</div>';

}
?>