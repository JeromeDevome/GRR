<?php
/**
 * admin_calend_ignore.php
 * Interface gérant la définition des jours hors réservation
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2024-02-28  10:55$
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
$grr_script_name = "admin_calend_ignore.php";

include "../include/admin.inc.php";

$back = (isset($_SERVER['HTTP_REFERER']))? htmlspecialchars_decode($_SERVER['HTTP_REFERER'], ENT_QUOTES) : "./admin_accueil.php" ;
check_access(6, $back);
if (isset($_POST['record']) && ($_POST['record'] == 'yes'))
{
    // On met de côté toutes les dates
    $day_old = array();
    $res_old = grr_sql_query("SELECT day FROM ".TABLE_PREFIX."_calendar");
    if ($res_old)
    {
        foreach($res_old as $row)
            $day_old[] = $row['day'];
    }
    // On vide la table ".TABLE_PREFIX."_calendar
    $sql = "truncate table ".TABLE_PREFIX."_calendar";
    if (grr_sql_command($sql) < 0)
        fatal_error(0, "<p>" . grr_sql_error());
    $result = 0;
    $end_bookings = Settings::get("end_bookings");
    $n = Settings::get("begin_bookings");
    $month = date("m", Settings::get("begin_bookings"));
    $year = date("Y", Settings::get("begin_bookings"));
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
                 // Le jour a été selectionné dans le calendrier
                $starttime = mktime($morningstarts, 0, 0, $month, $day  , $year);
                $endtime   = mktime($eveningends, 0, $resolution, $month, $day, $year);
                 // Pour toutes les dates non précédement enregistrées, on efface toutes les résa en conflit
                if (!in_array($n,$day_old))
                {
                    $sql = "select id from ".TABLE_PREFIX."_room";
                    $res = grr_sql_query($sql);
                    if ($res)
                    {
                        foreach($res as $row)
                            $result += grrDelEntryInConflict($row['id'], $starttime, $endtime, 0, 0, 1);
                    }
                }
                    // On enregistre la valeur dans ".TABLE_PREFIX."_calendar
                $sql = "INSERT INTO ".TABLE_PREFIX."_calendar set DAY=? ";
                if (grr_sql_command($sql,"i",[$n]) < 0)
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
    // on traite le cas des jours hors réservation
    if (isset($_POST['delai_ouvert'])){
        if ($_POST['delai_ouvert']==1)
            Settings::set('delai_ouvert',1);
    }
    else 
        Settings::set('delai_ouvert',0);
    $test = grr_sql_query1("SELECT COUNT(*) FROM ".TABLE_PREFIX."_setting WHERE name='delai_ouvert' ");
    if ($test == -1)
        echo get_vocab('message_records_error');
}
// jours de vacances scolaires ou fériés
if (Settings::get("show_holidays") == 'Oui'){
    // définir les jours fériés
    $feries = array();
    $req = "SELECT * FROM ".TABLE_PREFIX."_calendrier_feries";
    if(!$req)
      fatal_error(0, "<p>" . grr_sql_error());
    else{
      $ans = grr_sql_query($req);
      foreach($ans as $val){$feries[] = $val['DAY'];}
    }
    // les vacances scolaires
    $vacances = array();
    $req = "SELECT * FROM ".TABLE_PREFIX."_calendrier_vacances";
    if(!$req)
      fatal_error(0, "<p>" . grr_sql_error());
    else{
      $ans = grr_sql_query($req);
      foreach($ans as $val){$vacances[] = $val['DAY'];}
    }
}
// code HTML
# print the page header
start_page_w_header("", "", "", $type="with_session");
// Affichage de la colonne de gauche
include "admin_col_gauche2.php";
echo "<div class='col-md-9 col-sm-8 col-xs-12'>";
echo "<h2>".get_vocab('calendrier_des_jours_hors_reservation')."</h2>\n";
echo "\n<p>".get_vocab("les_journees_cochees_sont_ignorees")."</p>";
echo "<form action=\"admin_calend_ignore.php\" method=\"post\" id=\"formulaire\" name=\"formulaire\">\n";
echo "<p><b>Option :</b> Cochez la case ci-contre pour ajouter au délai minimum avant réservation la durée des jours hors réservation ";
echo "<input type='checkbox' name='delai_ouvert' value='1' ";
if (Settings::get('delai_ouvert') == 1) echo 'checked="checked"';
echo " /><br />";
echo "<em>Ainsi si le dimanche est hors réservation et que le délai avant réservation est de trois jours (4320 minutes), une ressource ne peut pas être réservée du vendredi au lundi.</em>";
echo "</p>";
echo "\n<table class='table-noborder'>\n";
$basetime = mktime(12, 0, 0, 6, 11 + $weekstarts, 2000);
for ($i = 0; $i < 7; $i++)
{
    $show = $basetime + ($i * 24 * 60 * 60);
    $lday = utf8_strftime('%A',$show);
    echo "<tr>\n";
    echo "<td><span class='small'><a href='admin_calend_ignore.php' onclick=\"setCheckboxesGrr('formulaire', true, '$lday' ); return false;\">".get_vocab("check_all_the").$lday."s</a></span></td>\n";
    echo "<td><span class='small'><a href='admin_calend_ignore.php' onclick=\"setCheckboxesGrr('formulaire', false, '$lday' ); return false;\">".get_vocab("uncheck_all_the").$lday."s</a></span></td>\n";
    echo "</tr>\n";
}
if (Settings::get("show_holidays") == 'Oui'){ // on n'affiche ce choix que si les jours fériés et les vacances sont définis
    // jours fériés
    $cocheferies = "";
    foreach ($feries as &$value) {
        $cocheferies .= "setCheckboxesGrrName(document.getElementById('formulaire'), true, '{$value}'); ";
    }
    unset($feries);
    // vacances scolaires
    $cocheVacances = "";
    foreach ($vacances as &$value) {
        $cocheVacances .= "setCheckboxesGrrName(document.getElementById('formulaire'), true, '{$value}'); ";
    }
    unset($vacances);
    echo "<tr>";
    echo "<td>";
    echo "<span class='small'><a href='admin_calend_ignore.php' onclick=\"{$cocheVacances} return false;\">".get_vocab("admin_calend_ignore_vacances")."&nbsp </a></span>";
    echo "</td><td></td></tr><tr><td>";
    echo "<span class='small'><a href='admin_calend_ignore.php' onclick=\"{$cocheferies} return false;\">".get_vocab("admin_calend_ignore_feries")."</a></span>";
    echo "</td>";
    echo "</tr>";
}
echo "<tr>\n<td></td><td><span class='small'><a href='admin_calend_ignore.php' onclick=\"setCheckboxesGrr('formulaire', false, 'all'); return false;\">".get_vocab("uncheck_all_")."</a></span></td>\n";
echo "</tr>\n";
echo "</table>\n";

echo "<table>\n";
$n = Settings::get("begin_bookings");
$end_bookings = Settings::get("end_bookings");
$debligne = 1;
$month = date("m", Settings::get("begin_bookings"));
$year = date("Y", Settings::get("begin_bookings"));
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
    echo cal($month, $year, 1);
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
    // while
    echo "</tr>";
}
echo "</table>";
echo "<div id=\"fixe\"><input class=\"btn btn-primary\" type=\"submit\" name=\"".get_vocab('save')."\" value=\"".get_vocab("save")."\" />\n";
echo "<input type=\"hidden\" name=\"record\" value=\"yes\" />\n";
echo "</div>";
echo "</form>";
// fin de l'affichage de la colonne de droite 
echo "</div>";
// et de la page
end_page();
?>