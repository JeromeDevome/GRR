<?php
/**
 * admin_view_connexions.php
 * Interface de gestion des connexions
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2021-03-13 11:30$
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
$grr_script_name = "admin_view_connexions.php";
 
include "../include/admin.inc.php";

$back = (isset($_SERVER['HTTP_REFERER']))? htmlspecialchars_decode($_SERVER['HTTP_REFERER'], ENT_QUOTES) : "./admin_accueil.php" ;
check_access(6, $back);
if (isset($_POST['cleanDay']) && isset($_POST['cleanMonth']) && isset($_POST['cleanYear']))
{
	$sql = "DELETE FROM ".TABLE_PREFIX."_log WHERE START < '" . $_POST['cleanYear'] . "-" . $_POST['cleanMonth'] . "-" . $_POST['cleanDay'] . "' and END < now()";
	$res = grr_sql_query($sql);
}
// début de page
start_page_w_header("", "", "", $type="with_session");
include "admin_col_gauche2.php";
echo "<div class='col-md-9 col-sm-8 col-xs-12'>";
echo "<h2>".get_vocab('admin_view_connexions.php')."</h2>";
echo "<h3>".get_vocab("users_connected")."</h3>";
echo '<div title="Utilisateur connecté">';
echo '	<ul>';
$sql = "SELECT u.login, concat(u.prenom, ' ', u.nom) utilisa, u.email FROM ".TABLE_PREFIX."_log l, ".TABLE_PREFIX."_utilisateurs u WHERE (l.LOGIN = u.login and l.END > now())";
$res = grr_sql_query($sql);
if ($res)
{
    for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
    {
        if ((Settings::get("sso_statut") != "") ||  (Settings::get("ldap_statut") != '') ||  (Settings::get("imap_statut") != ''))
            echo ("<li>" . $row[1]. " | <a href=\"mailto:" . $row[2] . "\">".get_vocab("sen_a_mail")."</a> |</li>") ;
        else
            echo ("<li>" . $row[1]. " | <a href=\"mailto:" . $row[2] . "\">".get_vocab("sen_a_mail")."</a> | <a href=\"admin_change_pwd.php?user_login=" . $row[0] . "\">".get_vocab("deconnect_changing_pwd")."</a></li>");
    }
}
echo '	</ul>';
echo '</div>';
echo '<hr />';
if (!isset($_POST['histYear']))
	$_POST['histYear'] = strftime("%Y");
if (!isset($_POST['histMonth']))
	$_POST['histMonth'] = strftime("%m");
if (!isset($_POST['histDay']))
	$_POST['histDay'] = strftime("%d");
echo '<form action="admin_view_connexions.php" method="post">';
echo '<fieldset>';
echo '<legend style="font-variant: small-caps;">'.get_vocab("start_history").'</legend>';
echo '			<table style="border: 0; width: 5%; margin: auto;" cellpadding="5" cellspacing="0">
				<tr>
					<td style="text-align: center; width: 24%; font-variant: small-caps;">JJ</td>
					<td style="text-align: center; width: 1%;">/</td>
					<td style="text-align: center; width: 24%; font-variant: small-caps;">MM</td>
					<td style="text-align: center; width: 1%;">/</td>
					<td style="text-align: center; width: 50%; font-variant: small-caps;">AAAA</td>
				</tr>';
echo '				<tr>';
echo '					<td><input type="text" name="histDay" size="2" value="'.$_POST['histDay'].'" style="text-align: center;"/></td>';
echo '					<td>/</td>';
echo '					<td><input type="text" name="histMonth" size="2" value="'.$_POST['histMonth'].'" style="text-align: center;"/></td>';
echo '					<td>/</td>';
echo '					<td><input type="text" name="histYear" size="4" value="'.$_POST['histYear'].'" style="text-align: center;"/></td>';
echo '				</tr>';
echo '			</table>';
echo '			<div class="center"><input class="btn btn-primary" type="submit" value="'.get_vocab("OK").'" style="font-variant: small-caps;"/></div>';
echo '		</fieldset>';
echo '</form>';
echo '<h3>'.get_vocab("log").$_POST['histDay']."/".$_POST['histMonth']."/".$_POST['histYear'].'</h3>';
echo '<div title="log" >';
echo '<p>'.get_vocab("msg_explain_log").'</p>';
echo '<table class="col table">';
echo '<tr><th class="col">';
echo get_vocab("login_name");
echo '</th><th class="col">';
echo get_vocab("begining_of_session");
echo '</th><th class="col">';
echo get_vocab("end_of_session");
echo '</th><th class="col">';
echo get_vocab("ip_adress");
echo '</th><th class="col">';
echo get_vocab("navigator");
echo '</th><th class="col">';
echo get_vocab("referer");
echo '</th></tr>';
$sql = "SELECT u.login, concat(prenom, ' ', nom) utili, l.START, l.SESSION_ID, l.REMOTE_ADDR, l.USER_AGENT, l.REFERER, l.AUTOCLOSE, l.END, u.email FROM ".TABLE_PREFIX."_log l, ".TABLE_PREFIX."_utilisateurs u WHERE l.LOGIN = u.login and l.START > '" . $_POST['histYear'] . "-" . $_POST['histMonth'] . "-" . $_POST['histDay'] . "' ORDER by START desc";
$day_now   = date("d");
$month_now = date("m");
$year_now  = date("Y");
$hour_now  = date("H");
$minute_now = date("i");
$now = mktime($hour_now, $minute_now, 0, $month_now, $day_now, $year_now);
$res = grr_sql_query($sql);
if ($res)
{
    for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
    {
        $annee = substr($row[8],0,4);
        $mois =  substr($row[8],5,2);
        $jour =  substr($row[8],8,2);
        $heures = substr($row[8],11,2);
        $minutes = substr($row[8],14,2);
        $secondes = substr($row[8],17,2);
        $end_time = mktime($heures, $minutes, $secondes, $mois, $jour, $annee);
        $temp1 = '';
        $temp2 = '';
        if ($end_time > $now)
        {
            $temp1 = "<span style=\"color:green;\">";
            $temp2 = "</span>";
        }
        echo "<tr>\n";
        echo "<td class=\"col\">".$temp1."<a href=\"mailto:" .$row[9]. "\">".$row[1] . "</a>".$temp2."</td>\n";
        echo "<td class=\"col\">".$temp1.$row[2].$temp2."</td>";
        if ($end_time > $now)
            echo "<td class=\"col\" style=\"color:green;\">" .$row[8]. "</td>\n";
        else if ($row[7])
            echo "<td class=\"col\" style=\"color:red;\">" .$row[8]. "</td>\n";
        else
            echo "<td class=\"col\">" .$row[8]. "</td>\n";
        echo "<td class=\"col\">".$temp1.$row[4].$temp2. "</td>\n";
        echo "<td class=\"col\">".$temp1. $row[5] .$temp2. "</td>\n";
        echo "<td class=\"col\">".$temp1. $row[6] .$temp2. "</td>\n";
        echo "</tr>\n";
    }
}
echo '</table>';
echo '</div>';
echo '<hr />';
echo '<h3>'.get_vocab("cleaning_log").'</h3>';
$sql = "select START from ".TABLE_PREFIX."_log order by END";
$res = grr_sql_query($sql);
$logs_number = grr_sql_count($res);
$row = grr_sql_row($res, 0);
$annee = substr($row[0],0,4);
$mois =  substr($row[0],5,2);
$jour =  substr($row[0],8,2);
echo "<p>".get_vocab("logs_number")."<b>".$logs_number."</b><br />";
echo get_vocab("older_date_log")."<b>".$jour."/".$mois."/".$annee."</b></p>";
if (!isset($_POST['cleanYear']))
	$_POST['cleanYear'] = strftime("%Y");
if (!isset($_POST['cleanMonth']))
	$_POST['cleanMonth'] = strftime("%m");
if (!isset($_POST['cleanDay']))
	$_POST['cleanDay'] = strftime("%d");
echo' <div title="Nettoyage du journal" >';
echo '<p>'.get_vocab("erase_log").'</p>';
echo '<form action="admin_view_connexions.php" method="post">';
echo '<fieldset>';
echo '<legend style="font-variant: small-caps;">'.get_vocab("delete_up_to").'</legend>';
echo '<table style="border: 0; width: 5%; margin: auto;">';
echo '				<tr>';
echo '					<td style="text-align: center; width: 24%; font-variant: small-caps;">JJ</td>';
echo '					<td style="text-align: center; width: 1%;">/</td>';
echo '					<td style="text-align: center; width: 24%; font-variant: small-caps;">MM</td>';
echo '					<td style="text-align: center; width: 1%;">/</td>';
echo '					<td style="text-align: center; width: 50%; font-variant: small-caps;">AAAA</td>';
echo '				</tr>';
echo '				<tr>';
echo '					<td><input type="text" name="cleanDay" size="2" value="'.$_POST['cleanDay'].'" style="text-align: center;"/></td>';
echo '					<td>/</td>';
echo '					<td><input type="text" name="cleanMonth" size="2" value="'.$_POST['cleanMonth'].'" style="text-align: center;"/></td>';
echo '					<td>/</td>';
echo '					<td><input type="text" name="cleanYear" size="4" value="'.$_POST['cleanYear'].'" style="text-align: center;"/></td>';
echo '				</tr>';
echo '</table>';
echo '<div class="center"><input class="btn btn-primary" type="submit" value="'.get_vocab("OK").'" style="font-variant: small-caps;" /></div>';
echo '</fieldset>';
echo '</form>';
echo '</div>';
echo "</div>";
end_page();
?>
