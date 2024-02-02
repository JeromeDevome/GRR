<?php
/**
 * edit_entry_types.php
 * Page "Ajax" utilisée pour générer les types
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2024-02-02 16:54$
 * @author    Laurent Delineau & JeromeB & Yan Naessens
 * @copyright Copyright 2003-2024 Team DEVOME - JeromeB
 * @link      http://www.gnu.org/licenses/licenses.html
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */
include "include/admin.inc.php";
// Initialisation
if (!empty($_GET["type"]))
	$type = $_GET["type"];
else
	$type = "";
// paramètres $area et $room requis
if (isset($_GET['area']))
	$area = intval($_GET['area']);
else
	die();
if (isset($_GET['room']))
{
	$room = $_GET['room'];
	if ($room != "")
		$room = intval($room);
}
else
	die();

$user_name = getUserName();
if ((authGetUserLevel($user_name,-1) < 2) && (auth_visiteur($user_name,$room) == 0))
{
	showAccessDenied("");
	exit();
}

if (authUserAccesArea($user_name, $area) == 0)
{
	showAccessDenied("");
	exit();
}
//header("Content-Type: text/html;charset=utf-8");
//header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
// Type de réservation
$qui_peut_reserver_pour = grr_sql_query1("SELECT qui_peut_reserver_pour FROM ".TABLE_PREFIX."_room WHERE id='".$room."'");
$aff_default = ((authGetUserLevel($user_name,-1,"room") >= $qui_peut_reserver_pour) || (authGetUserLevel($user_name,$area,"area") >= $qui_peut_reserver_pour));
$aff_type = max(authGetUserLevel($user_name,-1,"room"),authGetUserLevel($user_name,$area,"area"));
// Avant d'afficher la liste déroulante des types, on stocke dans $display_type et on teste le nombre de types à afficher
// Si ne nombre est égal à 1, on ne laisse pas le choix
$nb_type = 0;
$type_nom_unique = "??";
$type_id_unique = "??";
$display_type = '<div class="E form-inline">'.PHP_EOL;
$display_type .= '<div class="form-group"><label for="type" class="control-label">'.get_vocab("type")." *".get_vocab("deux_points").'</label>'.PHP_EOL;
$display_type .= '<select id="type" class="form-control" name="type" size="1" onclick="setdefault(\'type_default\',\'\')">'.PHP_EOL;
$display_type .= '<option value="0">'.get_vocab("choose").PHP_EOL;

$sql = "SELECT DISTINCT t.type_name, t.type_letter, t.id, t.order_display FROM ".TABLE_PREFIX."_type_area t
LEFT JOIN ".TABLE_PREFIX."_j_type_area j ON j.id_type=t.id
WHERE (j.id_area IS NULL OR j.id_area != ?) AND (t.disponible<=?)
ORDER BY t.order_display";
$res = grr_sql_query($sql,"ii",[$area,$aff_type]);

if (!$res)
	fatal_error(0, grr_sql_error());
else
{
    if (grr_sql_count($res) != 0){
        foreach($res as $row) // t.type_name, t.type_letter, t.id, t.order_display
        {
            $id_type_par_defaut = grr_sql_query1("SELECT id_type_par_defaut FROM ".TABLE_PREFIX."_area WHERE id = ?","i",[$area]);
            // La requête sql précédente laisse passer les cas où un type est non valide
            // dans le domaine concerné ET au moins dans un autre domaine, d'où le test suivant
            $test = grr_sql_query1("SELECT id_type FROM ".TABLE_PREFIX."_j_type_area WHERE id_type = ? AND id_area= ? ","ii",[$row['id'],$area]);
            if ($test == -1)
            {
                $nb_type ++;
                $type_nom_unique = $row['type_name'];
                $type_id_unique = $row['type_letter'];
                $display_type .= '<option value="'.$row['type_letter'].'" ';
                
                if ($type != "") // Modification d'une réservation
                {
                    if ($type == $row['type_letter'])
                        $display_type .=  ' selected="selected"';
                }
                else // Nouvelle réservation
                {
                    //Récupère le cookie par defaut
                    if ($aff_default && isset($_COOKIE['type_default'])){
                        $cookie = $_COOKIE['type_default'];
                    } 
                    else{
                        $cookie = "";
                    }
                    if ((!$cookie && ($id_type_par_defaut == $row['id'])) || ($cookie && $cookie == $row['type_name']))
                        $display_type .=  ' selected="selected"';
                }
                $display_type .=  ' >'.$row['type_name'].'</option>'.PHP_EOL;
            }
        }
    }
}

$display_type .=  '</select></div>'.PHP_EOL;
if ($aff_default)
	$display_type .= '<input type="button" class="btn btn-primary" value="'.get_vocab("definir par defaut").'" onclick="setdefault(\'type_default\',document.getElementById(\'main\').type.options[document.getElementById(\'main\').type.options.selectedIndex].text)" />'.PHP_EOL;
$display_type .= '</div>'.PHP_EOL;
if ($nb_type > 1)
	echo $display_type;
else
	echo '<div class="E"><b>'.get_vocab("type").get_vocab("deux_points").htmlentities($type_nom_unique).'</b>'.PHP_EOL.'<input name="type" type="hidden" value="'.$type_id_unique.'" /></div>'.PHP_EOL;
?>