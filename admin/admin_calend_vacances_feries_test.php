<?php
/**
 * admin_calend_vacances_feries.php
 * Interface permettant la la réservation en bloc de journées entières
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2009-06-04 15:30:17 $
 * @author    Laurent Delineau <laurent.delineau@ac-poitiers.fr>
 * @copyright Copyright 2003-2008 Laurent Delineau
 * @link      http://www.gnu.org/licenses/licenses.html
 * @package   root
 * @version   $Id: admin_calend_vacances_feries.php,v 1.1 2009-06-04 15:30:17 grr Exp $
 * @filesource
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GRR is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GRR; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

include "../include/admin.inc.php";
$grr_script_name = "admin_calend_vacances_feries.php";
$back = '';
if (isset($_SERVER['HTTP_REFERER']))
	$back = htmlspecialchars($_SERVER['HTTP_REFERER']);
check_access(6, $back);
# print the page header
print_header("", "", "", $type="with_session");
// Affichage de la colonne de gauche
include "admin_col_gauche.php";
// affichage de la colonne de droite
echo "<h2>".get_vocab('admin_calend_vacances_feries.php')."</h2>\n";
echo "\n<p>".get_vocab("vacances_feries_description")."</p>";
// print_r($_POST);
// premier test : l'affichage des vacances et fériés est-il activé ?
if ((Settings::get("show_holidays") == 'Non')||(Settings::get("show_holidays") == '')){
    echo "<p>Il faut activer l'affichage des vacances et jours fériés pour continuer...</p>";
    echo '<p><a href="admin_config.php" >Cliquer ici pour activer l\'affichage des vacances et jours fériés</a>';
}
// deuxième test : le choix entre vacances et fériés est-il fait ?
else if (!isset($_POST['define_holidays'])){
    # bascule entre vacances et jours fériés
    echo '<form action="admin_calend_vacances_feries.php" method="POST" name="bascule">';
    echo '<div>'.PHP_EOL;
    echo '<p>'."<input type='radio' name='define_holidays' value='F'";
    if ((!isset($_POST['define_holidays']))||($_POST['define_holidays']=='Oui')){
        echo 'checked="checked"';
    }
    echo " />".PHP_EOL;
    echo "Définir les jours fériés".'&nbsp'.PHP_EOL;
    echo "<input type='radio' name='define_holidays' value='V' />".PHP_EOL;
    echo "Définir les vacances"."&nbsp".PHP_EOL;
    echo '<input type="submit" value="Choisir">';
    echo "</p></div>";
    echo '</form>';
    }
    else 
// troisième test : le choix est fait, on détermine lequel et on traite
        if ((isset($_POST['define_holidays'])) && ($_POST['define_holidays'] == 'F')){
            // traiter les jours fériés
            // echo "traitement des jours fériés<br>";
            if (isset($_POST['recordFeries']) && ($_POST['recordFeries'] == 'yes'))
            {
                // On met de côté toutes les dates
                $day_old = array();
                $res_old = grr_sql_query("SELECT day FROM ".TABLE_PREFIX."_calendrier_feries");
                if ($res_old)
                {
                    for ($i = 0; ($row_old = grr_sql_row($res_old, $i)); $i++)
                        $day_old[$i] = $row_old[0];
                }
                // On vide la table ".TABLE_PREFIX."_calendrier_feries
                $sql = "truncate table ".TABLE_PREFIX."_calendrier_feries";
                if (grr_sql_command($sql) < 0)
                    fatal_error(0, "<p>" . grr_sql_error());
                $result = 0;
                $end_bookings = Settings::get("end_bookings");
                $n = Settings::get("begin_bookings");
                $month = strftime("%m", $n );
                $year = strftime("%Y", $n );
                $day = 1;
                while ($n <= $end_bookings)
                {
                    $daysInMonth = getDaysInMonth($month, $year);
                    $day = 1;
                    while ($day <= $daysInMonth)
                    {
                        $n = mktime(0, 0, 0, $month, $day, $year);
                        if (isset($_POST[$n]))
                        {
                            // On enregistre la valeur dans ".TABLE_PREFIX."_calendrier_feries
                            $sql = "INSERT INTO ".TABLE_PREFIX."_calendrier_feries set DAY='".$n."'";
                            // echo "jour ".$n.'<br>';
                            if (grr_sql_command($sql) < 0)
                                fatal_error(0, "<p>" . grr_sql_error());
                        }
                        $day++;
                    }
                    $month++;
                    if ($month == 13) {
                        $year++;
                        $month = 1;
                    }
                }
            }
            $n = Settings::get("begin_bookings");
            $end_bookings = Settings::get("end_bookings");
            //$basetime = mktime(12, 0, 0, 6, 11 + $weekstarts, 2000);
            $month = utf8_encode(strftime("%m", $n));
            $year = strftime("%Y", $n);
            $yearFin = strftime("%Y", $end_bookings);
            $i = $year;
            $cocheFeries = "";
            while ($i <= $yearFin)
            {
                $feries = getHolidays($i);
                print_r($feries);
                foreach ($feries as &$value) {
                    $cocheFeries .= "setCheckboxesGrrName(document.getElementById('formulaireF'), true, '{$value}'); ";
                }
                unset($feries);
                $i++;
            }

            echo "<span class='small'><a href='admin_calend_vacances_feries.php' onclick=\"{$cocheFeries} return false;\">".get_vocab("vacances_feries_FR")."</a></span> || ";
            echo "<span class='small'><a href='admin_calend_vacances_feries.php' onclick=\"setCheckboxesGrr(document.getElementById('formulaireF'), false, 'all'); return false;\">".get_vocab("uncheck_all_")."</a></span>\n";

            echo "<form action=\"admin_calend_vacances_feries.php\" method=\"post\" id=\"formulaireF\">\n";
            echo "<table cellspacing=\"20\">\n";
            $debligne = 1;
            $inc = 0;
            while ($n <= $end_bookings)
            {
                if ($debligne == 1)
                {
                    echo "<tr>\n";
                    $inc = 0;
                    $debligne = 0;
                }
                $inc++;
                echo "<td>\n";
                echo cal($month, $year, 2);
                echo "</td>";
                if ($inc == 3)
                {
                    echo "</tr>";
                    $debligne = 1;
                }
                $month++;
                if ($month == 13)
                {
                    $year++;
                    $month = 1;
                }
                $n = mktime(0,0,0,$month,1,$year);
            }
            if ($inc < 3)
            {
                $k=$inc;
                while ($k < 3)
                {
                    echo "<td> </td>\n";
                    $k++;
                }
                echo "</tr>";
            }
            echo "</table>";
            echo "<div id=\"fixe\" style=\"text-align:center;\"><input class=\"btn btn-primary\" type=\"submit\" name=\"".get_vocab('save')."\" value=\"".get_vocab("save")."\" />\n";
            echo "<input class=\"btn btn-primary\" type=\"hidden\" name=\"recordFeries\" value=\"yes\" />\n";
            echo "<input class=\"btn btn-primary\" type=\"hidden\" name=\"define_holidays\" value=\"F\" />\n";
            echo "</div>";
            echo "</form>";
        }    
        else if ((isset($_POST['define_holidays'])) && ($_POST['define_holidays'] == 'V')) {
    // traitement des jours de vacances (scolaires)
                echo "traitement des vacances scolaires<br>";
                if (isset($_POST['recordVacances']) && ($_POST['recordVacances'] == 'yes'))
                { //phase d'enregistrement des jours de vacances scolaires
                    // On met de côté toutes les dates
                    $day_old = array();
                    $res_old = grr_sql_query("SELECT day FROM ".TABLE_PREFIX."_calendrier_vacances");
                    if ($res_old)
                    {
                        for ($i = 0; ($row_old = grr_sql_row($res_old, $i)); $i++)
                            $day_old[$i] = $row_old[0];
                    }
                    // On vide la table ".TABLE_PREFIX."_calendrier_vacances
                    $sql = "truncate table ".TABLE_PREFIX."_calendrier_vacances";
                    if (grr_sql_command($sql) < 0)
                        fatal_error(0, "<p>" . grr_sql_error());
                    $result = 0;
                    $end_bookings = Settings::get("end_bookings");
                    $n = Settings::get("begin_bookings");
                    $month = strftime("%m", $n);
                    $year = strftime("%Y", $n);
                    $day = 1;
                    while ($n <= $end_bookings)
                    {
                        $daysInMonth = getDaysInMonth($month, $year);
                        $day = 1;
                        while ($day <= $daysInMonth)
                        {
                            $n = mktime(0, 0, 0, $month, $day, $year);
                            if (isset($_POST[$n]))
                            {
                                // On enregistre la valeur dans ".TABLE_PREFIX."_calendrier_vacances
                                $sql = "INSERT INTO ".TABLE_PREFIX."_calendrier_vacances set DAY='".$n."'";
                                // echo "jour ".$n.'<br>';
                                if (grr_sql_command($sql) < 0)
                                    fatal_error(0, "<p>" . grr_sql_error());
                            }
                            $day++;
                        }
                        $month++;
                        if ($month == 13) {
                            $year++;
                            $month = 1;
                        }
                    }
                }
                $n = Settings::get("begin_bookings");
                $begin_bookings = $n;
                $end_bookings = Settings::get("end_bookings");
                //$basetime = mktime(12, 0, 0, 6, 11 + $weekstarts, 2000);
                $month = utf8_encode(strftime("%m", $n));
                $year = strftime("%Y", $n);
                $yearFin = strftime("%Y", $end_bookings);
                $i = $year;
                $cocheVacances = "";
                $zone = Settings::get("holidays_zone"); // en principe la zone est définie, au moins par défaut à A
                $schoolHoliday = array();
                $vacances = simplexml_load_file('../vacances.xml');
                $libelle = $vacances->libelles->children();
                $node = $vacances->calendrier->children();
                foreach ($node as $key => $value)
                {
                if ($value['libelle'] == $zone)
                    {
                        foreach ($value->vacances as $key => $value)
                        {
                            // print_r($value);
                            $y = date('Y', strtotime($value['debut'])); // année de début des vacances
                            if (($y >= $year-1) && ($y <= $yearFin)){ // on n'étudie que les années pertinentes
                                // echo $y.'&nbsp';
                                $t = strtotime($value['debut'])+86400; // la date du fichier est celle de la fin des cours
                                // echo "début ".$value['debut'].'&nbsp'."fin ".$value['fin'].'<br>';
                                while ($t < strtotime($value['fin'])){ // la date du fichier est celle de la reprise des cours
                                    if (($t >= $begin_bookings) && ($t <= $end_bookings)) {
                                        // echo "jour ".strftime($t)."<br>";
                                        $schoolHoliday[] = $t ; }
                                    $t += 86400; 
                                }
                            }
                        }
                    }
                }
                print_r($schoolHoliday);

                foreach ($schoolHoliday as &$value) {
                    $cocheVacances .= "setCheckboxesGrrName(document.getElementById('formulaireV'), true, '{$value}'); ";
                    // $cocheVacances .= "setCheckboxesGrr(document.getElementById('formulaire'), true, '{$value}'); "; ne checke pas
                }
                unset($schoolHoliday);
                echo $cocheVacances;
                echo "<span class='small'><a href='admin_calend_vacances_feries.php' onclick=\"{$cocheVacances} return false;\">".get_vocab("vacances_FR").$zone."</a></span> || ";
                echo "<span class='small'><a href='admin_calend_vacances_feries.php' onclick=\"setCheckboxesGrr(document.getElementById('formulaireV'), false, 'all'); return false;\">".get_vocab("uncheck_all_")."</a></span>\n";

                echo "<form action=\"admin_calend_vacances_feries.php\" method=\"post\" id=\"formulaireV\">\n";
                echo "<table cellspacing=\"20\">\n";
                $debligne = 1;
                $inc = 0;
                while ($n <= $end_bookings)
                {
                    if ($debligne == 1)
                    {
                        echo "<tr>\n";
                        $inc = 0;
                        $debligne = 0;
                    }
                    $inc++;
                    echo "<td>\n";
                    echo cal($month, $year, 3);
                    echo "</td>";
                    if ($inc == 3)
                    {
                        echo "</tr>";
                        $debligne = 1;
                    }
                    $month++;
                    if ($month == 13)
                    {
                        $year++;
                        $month = 1;
                    }
                    $n = mktime(0,0,0,$month,1,$year);
                }
                if ($inc < 3)
                {
                    $k=$inc;
                    while ($k < 3)
                    {
                        echo "<td> </td>\n";
                        $k++;
                    }
                    echo "</tr>";
                }
                echo "</table>";
                echo "<div id=\"fixe\" style=\"text-align:center;\"><input class=\"btn btn-primary\" type=\"submit\" name=\"".get_vocab('save')."\" value=\"".get_vocab("save")."\" />\n";
                echo "<input class=\"btn btn-primary\" type=\"hidden\" name=\"recordVacances\" value=\"yes\" />\n";
                echo "<input class=\"btn btn-primary\" type=\"hidden\" name=\"define_holidays\" value=\"V\" />\n";
                echo "</div>";
                echo "</form>";
            }
      //  else echo "ne devrait pas arriver";
 // fin de l'affichage de la colonne de droite
echo "</td></tr></table>\n";
?>
</body>
</html>
