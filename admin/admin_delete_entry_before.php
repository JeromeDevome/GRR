<?php
/**
 * admin_delete_entry_before.php
 * Interface permettant à l'administrateur de supprimer des réservations avant une date donnée
 * Dernière modification : $Date: 2017-12-16 14:00$
 * @author    JeromeB & Yan Naessens & Denis Monasse
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
$grr_script_name = "admin_delete_entry_before.php";
$back = '';
if (isset($_SERVER['HTTP_REFERER']))
	$back = htmlspecialchars($_SERVER['HTTP_REFERER']);
$_SESSION['chemin_retour'] = "admin_config.php";

if (!Settings::load()) {
    die('Erreur chargement settings');
}

# print the page header
print_header('', '', '', $type = 'with_session');
// Affichage de la colonne de gauche
include 'admin_col_gauche.php';
//
// Affichage de la colonne de droite 
//
?>
<table class="table_adm">
    <tr>
    <?php
     if(isset($_POST['delete'])) {
         echo '<br>';
        $endtime=mktime(23, 59, 0, $_POST['end_month'],$_POST['end_day'],$_POST['end_year']);
        $sql="select id, area_name from ".TABLE_PREFIX."_area order by order_display";
        $res = grr_sql_query($sql);
	    if (! $res) fatal_error(0, grr_sql_error());
	    $nb_areas=grr_sql_count($res);
	    for($i=0;$i < $nb_areas;$i++)
	       if(isset($_POST["area".$i])){
	           $rooms=grr_sql_query("SELECT id, room_name FROM `".TABLE_PREFIX."_room` WHERE `area_id`=".$_POST["area".$i]);
			   for ($j = 0; ($row = grr_sql_row($rooms, $j)); $j++) 
				  if(grr_sql_query("DELETE FROM `".TABLE_PREFIX."_entry` WHERE `end_time`<=".$endtime." AND `room_id`=".$row[0]))
				         echo "Les réservations antérieures au ".$_POST['end_day']."/".$_POST['end_month']."/".$_POST['end_year']." à 23 heures 59 dans la salle ".$row[1]." ont été supprimées<br/>";
				  else 
				  		echo "Erreur dans la suppression des réservations dans la salle ".$row[1]."<br/>";
         }                   
         echo '</tr>';
     }
     else { //echo 'les paramètres ne sont pas acquis';
            echo '<h3>Supprimer des réservations se terminant avant une date donnée</h3><hr />'.PHP_EOL;
    ?>
    </tr>
    <tr>
    <p>
            Utiliser ce script pour supprimer des réservations dans des domaines donnés avant une date donnée :<br />
            toutes les réservations se terminant avant la date donnée à 23 heures 59 minutes dans les domaines cochés seront supprimées
            <hr />
        </p>
    </tr>
    <tr>
        <p>Il est conseillé de procéder à la sauvegarde de la base de données avant la suppression<br/>
              <form action="admin_save_mysql.php" method="get">
                <div>
                   <input type="hidden" name="flag_connect" value="yes" />
                   <input type="submit" value="Lancer une sauvegarde" />
                </div>
              </form>
        </p>
            <hr /> 
    </tr>
    <tr>
    <?php echo '<form enctype="multipart/form-data" action="./admin_delete_entry_before.php" id="nom_formulaire" method="post" style="width: 100%;">'.PHP_EOL;
         echo '<input type="hidden" name="delete" value="1" />'.PHP_EOL;
 
?>
    </tr>
    <tr>
    <?php
    echo "Sélectionnez les domaines concernés par la suppression"."</br>".PHP_EOL;
            // affichage et sélection des domaines concernés par la suppression 
                  $sql="select id, area_name from ".TABLE_PREFIX."_area order by order_display";
                  $res = grr_sql_query($sql);
                  if (! $res) fatal_error(0, grr_sql_error());

                  if (grr_sql_count($res) != 0) {
                          echo '<tr><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>';
                          for ($i = 0; ($row = grr_sql_row($res, $i)); $i++) {
                               echo '<input type="checkbox" name="area'.$i.'" value="'.$row[0].'"  />';
                               echo htmlspecialchars($row[1]);
                               echo "</br>\n";
                          }
                          echo "</td></tr>\n";
                   }
    ?>
    </tr>
	<tr>
		<td>
            Jour de fin de la suppression <i>(opération irréversible ! )</i>
		</td>
		<td>
			<?php
            $typeDate = 'end_';
            $day   = date("d");
            $month = date("m");
            $year  = date("Y"); //par défaut on propose la date du jour
            echo '<div class="col-xs-12">'.PHP_EOL;
            echo '<div class="form-inline">'.PHP_EOL;
            genDateSelector('end_', $day, $month, $year, 'more_years');
            echo '<input type="hidden" disabled="disabled" id="mydate_'.$typeDate.'">'.PHP_EOL;
            echo '</div>'.PHP_EOL;
            echo '</div>'.PHP_EOL;
            echo '<div id="fixe" style="text-align:center;">'.PHP_EOL;
echo '<td><input type="submit" id="delete" value=" Supprimer les réservations! " /></td>'.PHP_EOL;

echo '</form>';

// fin de l'affichage de la colonne de droite
echo '</td></tr>';
     }
echo '</table>';

echo "<a href=\"admin_calend.php\">".get_vocab('returnprev')."</a><p>";

include "../include/trailer.inc.php"; ?>