<?php
/**
 * admin_config_calend3.php
 * Interface permettant la la réservation en bloc de journées entières
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2022-06-19 15:43$
 * @author    Laurent Delineau & JeromeB
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

$grr_script_name = "admin_calend_jour_cycle3.php";

function cal3($month, $year)
{
    global $weekstarts;

    if (!isset($weekstarts))
		$weekstarts = 0;
    $s = "";
    $daysInMonth = getDaysInMonth($month, $year);
    $date = mktime(12, 0, 0, $month, 1, $year);
    $first = (date('w',$date) + 7 - $weekstarts) % 7;
    $monthName = utf8_strftime("%B",$date);
	
    $s .= "<table class=\"calendar2\" border=\"1\" cellspacing=\"2\">\n";
    $s .= "<tr>\n";
    $s .= "<td class=\"calendarHeader2\" colspan=\"8\">$monthName&nbsp;$year</td>\n";
    $s .= "</tr>\n";
    $d = 1 - $first;
    $is_ligne1 = 'y';
    while ($d <= $daysInMonth)
    {
        $s .= "<tr>\n";
        for ($i = 0; $i < 7; $i++)
        {
            $basetime = mktime(12,0,0,6,11+$weekstarts,2000);
            $show = $basetime + ($i * 24 * 60 * 60);
            $nameday = utf8_strftime('%A',$show);
            $temp = mktime(0,0,0,$month,$d,$year);
            if ($i==0) $s .= "<td class=\"calendar2\" style=\"vertical-align:bottom;\"><b>S".getWeekNumber($temp)."</b></td>\n";
            if ($d > 0 && $d <= $daysInMonth)
            {
                $temp = mktime(0,0,0,$month,$d,$year);
                $day = grr_sql_query1("SELECT day FROM ".TABLE_PREFIX."_calendrier_jours_cycle WHERE day='$temp'");
                $jour = grr_sql_query1("SELECT Jours FROM ".TABLE_PREFIX."_calendrier_jours_cycle WHERE DAY='$temp'");
				        if (intval($jour)>0) {
                  $alt=get_vocab('jour_cycle')." ".$jour;
                  $jour=ucfirst(substr(get_vocab("rep_type_6"),0,1)).$jour;
                } else {
                  $alt=get_vocab('jour_cycle').' '.$jour;
                  if (strlen($jour)>5)
                    $jour = substr($jour,0,3)."..";
                }
                if (!isset($_GET["pview"]))
                    if (($day < 0))
                        $s .= "<td class=\"calendar2\" valign=\"top\" style=\"background-color:#FF8585\">";
                    else
                        $s .= "<td class=\"calendar2\" valign=\"top\" style=\"background-color:#C0FF82\">";
                else
                    $s .= "<td style=\"text-align:center;\" valign=\"top\">";
                if ($is_ligne1 == 'y') $s .=  '<b>'.ucfirst(substr($nameday,0,1)).'</b><br />';
                $s .= "<b>".$d."</b>";
                // Pour aller checher la date ainsi que son Jour cycle
                $s .= "<br />";
                if (isset($_GET["pview"])) {
                    if (($day < 0))
                        $s .= "<span class=\"fa fa-times\"></span>";
                    else
                        $s .= "<span class=\"jour-cycle\">".$jour."</span>";
                } else {
                    if (($day < 0))
                        $s .= "<a href=\"?p=admin_calend_jour_cycle3&amp;date=".$temp."\"><span class=\"fa fa-times red\"></span></a>";
                    else
                        $s .= "<a class=\"jour-cycle\" href=\"?p=admin_calend_jour_cycle3&amp;date=".$temp."\" title=\"".$alt."\" >".$jour."</a>";
                }

            } else {
                if (!isset($_GET["pview"]))
                    $s .= "<td class=\"calendar2\" valign=\"top\">";
                else
                    $s .= "<td style=\"text-align:center;\" valign=\"top\">";
                if ($is_ligne1 == 'y') $s .=  '<b>'.ucfirst(substr($nameday,0,1)).'</b><br />';
                $s .= "&nbsp;";
            }
            $s .= "</td>\n";
            $d++;
        }
        $s .= "</tr>\n";
        $is_ligne1 = 'n';
    }
    $s .= "</table>\n";
    return $s;
}

if (isset($_SERVER['HTTP_REFERER']))
	check_access(6, $back);

get_vocab_admin("titre_config_Jours_Cycles");

get_vocab_admin("admin_config_calend1");
get_vocab_admin("admin_config_calend2");
get_vocab_admin("admin_config_calend3");

get_vocab_admin("explication_Jours_Cycles3");
get_vocab_admin("explication_Jours_Cycles4");

get_vocab_admin("Journee_du");
get_vocab_admin("Cette_journee_ne_correspond_pas_a_un_jour_cycle");
get_vocab_admin("nouveau_jour_cycle");
get_vocab_admin("Nommer_journee_par_le_titre_suivant");

get_vocab_admin("save");

// Modification d'un jour cycle
// intval($jour)=-1 : pas de jour cycle
// intval($jour)=0 : Titre
// intval($jour)>0 : Jour cycle

	if (isset($_GET['date']))
	{
		$trad['dDate'] = $_GET['date'];
		$trad['dDateJour'] = affiche_date($_GET['date']);
		$trad['dJourCycle'] = grr_sql_query1("select Jours from ".TABLE_PREFIX."_calendrier_jours_cycle WHERE DAY = ".$_GET['date']."");
	}

	// Enregistrement du nouveau jour cycle
	if (isset($_GET['selection']))
	{
		if ($_GET['selection'] == 0)
		{
			grr_sql_query("delete from ".TABLE_PREFIX."_calendrier_jours_cycle WHERE DAY = ".$_GET['newdate']."");
		}
		elseif ($_GET['selection'] == 1)
		{
			grr_sql_query("delete from ".TABLE_PREFIX."_calendrier_jours_cycle WHERE DAY = ".$_GET['newdate']."");
			grr_sql_query("insert into ".TABLE_PREFIX."_calendrier_jours_cycle set Jours =".$_GET['newDay'].", DAY = ".$_GET['newdate']."");
		}
		elseif ($_GET['selection'] == 2)
		{
			grr_sql_query("delete from ".TABLE_PREFIX."_calendrier_jours_cycle WHERE DAY = ".$_GET['newdate']."");
			grr_sql_query("insert into ".TABLE_PREFIX."_calendrier_jours_cycle set Jours ='".protect_data_sql($_GET['titre'])."', DAY = ".$_GET['newdate']."");
		}
	}

	$n = Settings::get("begin_bookings");
	$end_bookings = Settings::get("end_bookings");
	$debligne = 1;
	$month = date('m', Settings::get("begin_bookings"));
	$year = date('Y', Settings::get("begin_bookings"));
	$inc = 0;
	$trad['dCalendrier'] = "";

	while ($n <= $end_bookings)
	{
		if ($debligne == 1)
		{
			$trad['dCalendrier'] .= "<tr>\n";
			$inc = 0;
			$debligne = 0;
		}
		$inc++;
		$trad['dCalendrier'] .= "<td>\n";
		$trad['dCalendrier'] .= cal3($month, $year);
		$trad['dCalendrier'] .= "</td>";
		if ($inc == 3)
		{
			$trad['dCalendrier'] .= "</tr>";
			$debligne = 1;
		}
		$month++;
		if ($month == 13)
		{
			$year++;
			$month = 1;
		}
		$n = mktime(0, 0, 0, $month, 1, $year);
	}
	if ($inc < 3)
	{
		$k = $inc;
		while ($k < 3)
		{
			$trad['dCalendrier'] .= "<td> </td>\n";
			$k++;
		}
		$trad['dCalendrier'] .= "</tr>";
	}
	
echo $twig->render('admin_calend_jour_cycle3.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings));
?>