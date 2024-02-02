<?php
/*
 * frmcontactrange.php
 * calcule le code html de la partie intervalle du formulaire de contact
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2024-02-02 18:39$
 * @author    JeromeB & Yan Naessens
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
 
include "include/connect.inc.php";
include "include/mysql.inc.php";
include "include/misc.inc.php";
include "include/functions.inc.php";

$id = $_GET['id'];
if ($id != protect_data_sql($id))
    die('Donnée incorrecte');
$query = "SELECT access,morningstarts_area,eveningends_area,eveningends_minutes_area,enable_periods,resolution_area FROM ".TABLE_PREFIX."_area
    WHERE id = ? ";
$res = grr_sql_query($query,"i",[$id]);
$val= grr_sql_row($res,0);
$enable_periods = $val[4]=='y';
if ($enable_periods)
{
    // echo 'mode créneaux';
    // déterminer la liste des créneaux
    $sql_periode = grr_sql_query("SELECT num_periode, nom_periode FROM ".TABLE_PREFIX."_area_periodes where id_area=? order by num_periode","i",[$id]);
    $num_periodes = grr_sql_count($sql_periode);
    echo '<div class="form-group">';
    // sélectionner parmi les créneaux
    echo '<label>Créneau initial : &nbsp;';
    echo '<select name="start">';
        foreach($sql_periode as $val)
        {
            echo '<option value="'.$val['num_periode'].'">'.$val['nom_periode'].'</option>';
        }
    echo '</select></label>';
    // choisir le nombre de créneaux
    echo '  <label>Nombre de créneaux : &nbsp;';
    echo '  <input type="number" id="dureemin" size="2" name="dureemin" value="1" min="1" required /></label>';
    echo '</div>';
}
else 
{	// mode temps
    $res_min = $val[5]/60;
    $nbiteration = 60/$res_min; // nb iterations sur une heure
    echo '<div class="form-group">';
    echo '    <div class="input-group">';
    echo '        <label for="heure">Heure début :</label>';
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
    echo '  <label for="duree" >Durée en heure :</label>';
    echo '  <input type="number" id="duree" size="2" name="duree" value="1" min="0" required />';
    echo '  <label for="dureemin"> et </label>';
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

echo '<div id="formulaire_captcha">'.PHP_EOL;
echo '<p><img id="captcha" src="./captcha.php" border="1" alt="CAPTCHA">'.PHP_EOL;
echo "<small><a href=\"#\" onclick=\"
                        document.getElementById('captcha').src = './captcha.php?' + Math.random();
                        document.getElementById('captcha_code_input').value = '';
                        return false;
                        \">";
echo 'Renouveler le code captcha'.'</a></small></p>'.PHP_EOL;
echo '<p>
        <input id="captcha_code_input" type="text" name="captcha" size="5" maxlength="5" /> 
          <small>'.PHP_EOL;
echo 'Entrer le code captcha dans la fenêtre ci-contre'.'</small>
                </p>
                </div>'.PHP_EOL;
echo "<div>";
echo '<input class="btn btn-primary" type="submit" name="submit" value="Envoyer la demande de réservation">';
echo '<input class="btn btn-danger" type="button" name="retouraccueil" value="Retour" onClick="javascript:location.href=\'javascript:history.go(-1)\'">';
echo '</div>';
?>