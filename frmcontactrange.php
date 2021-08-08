<?php
/*
 * frmcontactrange.php
 * calcule le code html de la partie intervalle du formulaire de contact
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2021-08-08 17:33$
 * @author    JeromeB & Yan Naessens
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
 
include "include/config.inc.php";
include "include/connect.inc.php";
include "include/mysql.inc.php";
include "include/misc.inc.php";
include "include/functions.inc.php";
require_once("./include/settings.class.php");
if (!Settings::load())
	die("Erreur chargement settings");
require_once("./include/session.inc.php");
include "./include/resume_session.php";
include "./include/language.inc.php";

$id = $_GET['id'];
if ($id != protect_data_sql($id))
    die('Donnée incorrecte');
$query = "SELECT access,morningstarts_area,eveningends_area,eveningends_minutes_area,enable_periods,resolution_area FROM ".TABLE_PREFIX."_area
    WHERE id = '".protect_data_sql($id)."' ";
// echo $query."<br />";
$res = grr_sql_query($query);
$val= grr_sql_row($res,0);
// print_r($val);
$enable_periods = $val[4]=='y';
if ($enable_periods)
{
    // echo 'mode créneaux';
    // déterminer la liste des créneaux
    $sql_periode = grr_sql_query("SELECT num_periode, nom_periode FROM ".TABLE_PREFIX."_area_periodes where id_area='".$id."' order by num_periode");
    $num_periodes = grr_sql_count($sql_periode);
    echo '<div class="form-group">';
    // sélectionner parmi les créneaux
    echo '<label for="start" >'.get_vocab('Creneau_initial').get_vocab('deux_points').' &nbsp;</label>';
    echo '<select name="start">';
        for ($i = 0; $i < $num_periodes; $i++)
        {
            $val = grr_sql_row($sql_periode,$i);
            echo '<option value="'.$val[0].'">'.$val[1].'</option>';
        }
    echo '</select>';
    // choisir le nombre de créneaux
    echo '  <label for="dureemin" >'.get_vocab('Nombre_de_creneaux').get_vocab('deux_points').'&nbsp;</label>';
    echo '  <input type="number" id="dureemin" size="2" name="dureemin" value="1" min="1" required />';
    echo '</div>';
}
else 
{	// mode temps
    $res_min = $val[5]/60;
    $nbiteration = 60/$res_min; // nb iterations sur une heure
    echo '<div class="form-group">';
    echo '    <div class="input-group">';
    echo '        <label for="heure">'.get_vocab('start_time').get_vocab('deux_points').'</label>';
    echo "        <select name=\"heure\" id=\"heure\"> ";
    for ($h = $val[1] ; $h < $val[2]+$val[3]/60-$val[5]/3600 ; $h++)
    {
        echo "<option value =\"$h\"> ".sprintf("%02d",$h)."h </option>".PHP_EOL;
    }
    echo "        </select>";
    echo " <select id='debdureemin' name=\"minutes\">";
    $valeur = 0;
    for ($i=0;$i<$nbiteration;$i++)
    {
        echo "<option value='$valeur'>";
        if ($i == 0)
        { $valeur ='00';}
        else {$valeur += $res_min;}
        echo $valeur." min.";
        echo "</option>";
    }
    echo " </select>";
    echo '    </div>';
    echo '<div class="input-group">';
    echo '  <label for="duree" >'.get_vocab('duree_heures').get_vocab('deux_points').'</label>';
    echo '  <input type="number" id="duree" size="2" name="duree" value="1" min="0" required />';
    echo '  <label for="dureemin">&nbsp;'.get_vocab('et').'&nbsp; </label>';
    echo '  <select id="dureemin" name="dureemin">';
    $valeur = 0;
    for ($i=0;$i<$nbiteration;$i++)
    {
        echo "<option value='$valeur'>".$valeur." min</option>";
        $valeur += $res_min;
    }
    echo '  </select>';
    echo ' </div>';
    echo '</div>';
    
}
echo "<br /> ";
echo "<div>";
echo '<input class="btn btn-primary" type="submit" name="submit" value="'.get_vocab('Envoyer_demande').'">';
echo '<input class="btn btn-danger" type="button" name="retouraccueil" value="'.get_vocab('back').'" onClick="javascript:location.href=\'javascript:history.go(-1)\'">';
echo '</div>';
?>