<?php
/**
 * admin_config_calend3.php
 * interface permettant la configuration des jours-cycles (étape 3)
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2021-05-21 14:35$
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
$grr_script_name = "admin_config_calend3.php";

function cal3($month, $year)
{
    global $weekstarts;
    if (!isset($weekstarts)) $weekstarts = 0;
    $s = "";
    $daysInMonth = getDaysInMonth($month, $year);
    $date = mktime(12, 0, 0, $month, 1, $year);
    $first = (strftime("%w",$date) + 7 - $weekstarts) % 7;
    $monthName = utf8_strftime("%B",$date);
    $s .= "<table class=\"calendar2 table-bordered\" >\n";
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
                        $s .= "<img src=\"../img_grr/stop.png\" class=\"image\" width=\"16\" height=\"16\" alt=\"no\"/>";
                    else
                        $s .= "<span class=\"jour-cycle\">".$jour."</span>";
                } else {
                    if (($day < 0))
                        $s .= "<a href=\"admin_calend_jour_cycle.php?page_calend=3&amp;date=".$temp."\"><img src=\"../img_grr/stop.png\" class=\"image\" alt=\"(aucun)\"  width=\"16\" height=\"16\" /></a>";
                    else
                        $s .= "<a class=\"jour-cycle\" href=\"admin_calend_jour_cycle.php?page_calend=3&amp;date=".$temp."\" title=\"".$alt."\" >".$jour."</a>";
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
$back = (isset($_SERVER['HTTP_REFERER']))? htmlspecialchars_decode($_SERVER['HTTP_REFERER'], ENT_QUOTES) : "./admin_accueil.php" ;
check_access(6, $back);
// code HTML
start_page_w_header("", "", "", $type = "with_session");
// Affichage de la colonne de gauche
if (!isset($_GET['pview']))
    include "admin_col_gauche2.php";
// affichage de la colonne de droite
// Affichage du menu de choix des sous-configurations des jours/cycles (créer et voir le calendrier des jours/cycles)
if (!isset($_GET['pview']))
{
    echo "<div class='col-md-9 col-sm-8 col-xs-12'>";
    include "../include/admin_calend_jour_cycle.inc.php";
}
else echo"<div class='col-xs-12'>";
echo "<h3>".get_vocab('calendrier_jours/cycles')."</h3>\n";
if (!isset($_GET['pview']))
{
		echo get_vocab("explication_Jours_Cycles3");
		echo "<br />".get_vocab("explication_Jours_Cycles4")."<br />\n";
	}
// Modification d'un jour cycle
// intval($jour)=-1 : pas de jour cycle
// intval($jour)=0 : Titre
// intval($jour)>0 : Jour cycle
	if (!isset($_GET['pview']) && isset($_GET['date']))
	{
		$jour_cycle = grr_sql_query1("select Jours from ".TABLE_PREFIX."_calendrier_jours_cycle  WHERE DAY = ".$_GET['date']."");
		echo "<fieldset style=\"padding-top: 8px; padding-bottom: 8px; width: 80%; margin-left: auto; margin-right: auto;\">\n";
		echo "<legend>".get_vocab('Journee du')." ".affiche_date($_GET['date'])."</legend>\n";
		echo "<form id=\"main\" method=\"get\" action=\"admin_calend_jour_cycle.php\">\n";
		echo "<div><input type='radio' name='selection' value='0'";
		if (intval($jour_cycle) == -1)
			echo " checked=\"checked\"";
		echo " />".get_vocab('Cette journee ne correspond pas a un jour cycle')."<br />\n";
		echo "<input type='radio' name='selection' value='1'";
		if (intval($jour_cycle) > 0)
			echo " checked=\"checked\"";
		echo " />\n".get_vocab("nouveau_jour_cycle");
		echo "<select name=\"newDay\" size=\"1\" onclick=\"check(1)\">";
		for ($i = 1; $i < (Settings::get("nombre_jours_Jours_Cycles") + 1); $i++)
		{
			echo "<option value=\"".$i."\" ";
			if ($jour_cycle == $i)
				echo " selected=\"selected\"";
			echo " >j".$i."</option>";
		}
		echo "</select>\n";
		echo "<input name=\"newdate\" type=\"hidden\" value=\"".$_GET['date']."\" />";
		echo "<input type=\"hidden\" value=\"3\" name=\"page_calend\" /><br />";
		echo "<input type='radio' name='selection' value='2'";
		if (intval($jour_cycle) == 0)
			echo " checked=\"checked\"";
		echo " />".get_vocab('Nommer_journee_par_le_titre_suivant').get_vocab('deux_points');
		echo "<input type=\"text\" name=\"titre\" onfocus=\"check(2)\"";
		if (!intval($jour_cycle) > 0)
			echo " value=\"".$jour_cycle."\"";
		echo "/><br /><br /><div class=\"center\"><input type=\"submit\" value=\"Enregistrer\" /></div>\n";
		echo "</div></form>\n";
		echo "</fieldset>\n";
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
	$basetime = mktime(12, 0, 0, 6, 11 + $weekstarts, 2000);
	echo "<table class='table-noborder'>\n";
	$n = Settings::get("begin_bookings");
	$end_bookings = Settings::get("end_bookings");
	$debligne = 1;
	$month = strftime("%m", Settings::get("begin_bookings"));
	$year = strftime("%Y", Settings::get("begin_bookings"));
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
		echo cal3($month, $year);
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
		$n = mktime(0, 0, 0, $month, 1, $year);
	}
	if ($inc < 3)
	{
		$k = $inc;
		while ($k < 3)
		{
			echo "<td> </td>\n";
			$k++;
		} // while
		echo "</tr>";
	}
	echo "</table>";
	if (!isset($_GET['pview']))
	{
		echo "\n<div class=\"center\"><a href=\"admin_calend_jour_cycle.php?page_calend=3&amp;pview=1\" title=\"Format Imprimable\"><span class='glyphicon glyphicon-print'></span></a></div>\n";
	}
echo "</div>";	// fin de l'affichage de la colonne de droite    
?>
	<script type="text/javascript" >
		function check (select)
		{
			document.getElementById('main').selection[select].checked=true;
		}
	</script>
</section>
</body>
</html>
