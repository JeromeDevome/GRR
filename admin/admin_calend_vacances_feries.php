<?php
/**
 * admin_calend_vacances_feries.php
 * Interface permettant la définiton des jours fériés ou de vacances
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2021-03-13 12:05$
 * @author    Laurent Delineau & JeromeB & Yan Naessens
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
$grr_script_name = "admin_calend_vacances_feries.php";

include "../include/admin.inc.php";

$back = (isset($_SERVER['HTTP_REFERER']))? htmlspecialchars_decode($_SERVER['HTTP_REFERER'], ENT_QUOTES) : "./admin_accueil.php" ;
check_access(6, $back);
# print the page header
start_page_w_header("", "", "", $type="with_session");
// Affichage de la colonne de gauche
include "admin_col_gauche2.php";
// affichage de la colonne de droite
echo "<div class='col-md-9 col-sm-8 col-xs-12'>";
echo "<h2>".get_vocab('admin_calend_vacances_feries.php')."</h2>\n";
echo "\n<p>".get_vocab("vacances_feries_description")."</p>";
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
    echo '<p>'.'<input type="submit" value="Définir">'.'&nbsp;';
    echo "<input type='radio' name='define_holidays' value='F' ";
    if ((!isset($_POST['define_holidays']))||($_POST['define_holidays']=='Oui')){
        echo 'checked="checked"';
    }
    echo " />".PHP_EOL;
    echo " les jours fériés".'&nbsp;'.PHP_EOL;
    echo "<input type='radio' name='define_holidays' value='V' />".PHP_EOL;
    echo " les vacances"."&nbsp;".PHP_EOL;
    echo "</p></div>";
    echo '</form>';
    }
    else 
// troisième test : le choix est fait, on détermine lequel et on traite
        if ((isset($_POST['define_holidays'])) && ($_POST['define_holidays'] == 'F')){
            // traiter les jours fériés
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
                $begin_bookings = Settings::get("begin_bookings");
                $month = strftime("%m", $begin_bookings );
                $year = strftime("%Y", $begin_bookings );
                $day = 1;
                $n = $begin_bookings;
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
            $begin_bookings = Settings::get("begin_bookings");
            $end_bookings = Settings::get("end_bookings");
            $month = utf8_encode(strftime("%m", $begin_bookings));
            $year = strftime("%Y", $begin_bookings);
            $yearFin = strftime("%Y", $end_bookings);
            $i = $year;
            $cocheFeries = "";
            while ($i <= $yearFin)
            {
                $feries = setHolidays($i);
                foreach ($feries as &$value) {
                    $cocheFeries .= "setCheckboxesGrrName(document.getElementById('formulaireF'), true, '$value'); ";
                }
                unset($feries);
                $i++;
            }

            echo "<span class='small'><a href='admin_calend_vacances_feries.php' onclick=\"{$cocheFeries} return false;\">".get_vocab("vacances_feries_FR")."</a></span> || ";
            echo "<span class='small'><a href='admin_calend_vacances_feries.php' onclick=\"setCheckboxesGrr('formulaireF', false, 'all'); return false;\">".get_vocab("uncheck_all_")."</a></span> || ";
            echo "<span class='small'><a href='admin_calend_vacances_feries.php' >".get_vocab("returnprev")."</a></span>";

            echo "<form action=\"admin_calend_vacances_feries.php\" method=\"post\" id=\"formulaireF\" name=\"formulaireF\">\n";
            echo "<table cellspacing=\"20\">\n";
            $debligne = 1;
            $inc = 0;
            $n = $begin_bookings;
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
                    $begin_bookings = Settings::get("begin_bookings");
                    $month = strftime("%m", $begin_bookings);
                    $year = strftime("%Y", $begin_bookings);
                    $day = 1;
                    $n = $begin_bookings;
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
                $begin_bookings = Settings::get("begin_bookings");
                $end_bookings = Settings::get("end_bookings");
                $month = utf8_encode(strftime("%m", $begin_bookings));
                $year = strftime("%Y", $begin_bookings);
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
                            $y = date('Y', strtotime($value['debut'])); // année de début des vacances
                            if (($y >= $year-1) && ($y <= $yearFin)){ // on n'étudie que les années pertinentes
                                $t = strtotime($value['debut'])+86400; // la date du fichier est celle de la fin des cours
                                $t_fin = strtotime($value['fin']);
                                while ($t < $t_fin){ // la date du fichier est celle de la reprise des cours
                                    if (($t >= $begin_bookings) && ($t <= $end_bookings)) {
                                        $schoolHoliday[] = $t ; }
                                    $jour = strftime("%d",$t);
                                    $mois = strftime("%m",$t);
                                    $annee = strftime("%y",$t);
                                    $t = mktime(0,0,0,$mois,$jour+1,$annee);
                                }
                            }
                        }
                    }
                }

                foreach ($schoolHoliday as &$value) {
                    $cocheVacances .= "setCheckboxesGrrName(document.getElementById('formulaireV'), true, '{$value}'); ";
                }
                unset($schoolHoliday);

                echo "<span class='small'><a href='admin_calend_vacances_feries.php' onclick=\"{$cocheVacances} return false;\">".get_vocab("vacances_FR").$zone."</a></span> || ";
                echo "<span class='small'><a href='admin_calend_vacances_feries.php' onclick=\"setCheckboxesGrr('formulaireV', false, 'all'); return false;\">".get_vocab("uncheck_all_")."</a></span> || ";
                echo "<span class='small'><a href='admin_calend_vacances_feries.php' >".get_vocab("returnprev")."</a></span>\n";

                echo "<form action=\"admin_calend_vacances_feries.php\" method=\"post\" id=\"formulaireV\" name=\"formulaireV\">\n";
                echo "<table cellspacing=\"20\">\n";
                $debligne = 1;
                $inc = 0;
                $n = $begin_bookings;
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
        else echo "ne devrait pas arriver";
 // fin de l'affichage de la colonne de droite
echo "</div>\n";
// et de la page
end_page();
?>
