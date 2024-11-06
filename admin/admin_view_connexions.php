<?php
/**
 * admin_view_connexions.php
 * Interface de gestion des connexions
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2024-11-06 15:43$
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
$grr_script_name = "admin_view_connexions.php";
 
include "../include/admin.inc.php";

$back = (isset($_SERVER['HTTP_REFERER']))? htmlspecialchars_decode($_SERVER['HTTP_REFERER'], ENT_QUOTES) : "./admin_accueil.php" ;
check_access(6, $back);

if (isset($_POST['cleanDay']) && isset($_POST['cleanMonth']) && isset($_POST['cleanYear']))
{
  $cd = getFormVar('cleanDay','string');
  $cm = getFormVar('cleanMonth','string');
  $cy = getFormVar('cleanYear','string');
	$sql = "DELETE FROM ".TABLE_PREFIX."_log WHERE START < ? and END < NOW()";
	$res = grr_sql_query($sql,"s",[$cy."-".$cm."-".$cd]);
}
// lecture de la table de login pour afficher les utilisateurs connectés
$data = array();
$sql = "SELECT u.login, concat(u.prenom, ' ', u.nom) user, u.email FROM ".TABLE_PREFIX."_log l JOIN ".TABLE_PREFIX."_utilisateurs u ON l.LOGIN = u.login WHERE l.END > now()";
$res = grr_sql_query($sql);
if ($res)
{
    foreach($res as $row)
    {
        if ((Settings::get("sso_statut") != "") ||  (Settings::get("ldap_statut") != '') ||  (Settings::get("imap_statut") != ''))
          $data[] = [$row['login'],$row['user'],$row['email'],FALSE] ;
        else
          $data[] = [$row['login'],$row['user'],$row['email'],TRUE] ;
    }
}
else
  fatal_error(0,grr_sql_error() . get_vocab("failed_to_acquire"));
// historique de connexions
$hist_year = getFormVar('histYear','string',date("Y"));
$hist_month = getFormVar('histMonth','string',date("m"));
$hist_day = getFormVar('histday','string',date("d"));
$day_now   = date("d");
$month_now = date("m");
$year_now  = date("Y");
$hour_now  = date("H");
$minute_now = date("i");
$now = mktime($hour_now, $minute_now, 0, $month_now, $day_now, $year_now);
$hist = array();
$sql = "SELECT u.login, concat(prenom,' ',nom) user, l.START, l.SESSION_ID, l.REMOTE_ADDR, l.USER_AGENT, l.REFERER, l.AUTOCLOSE, l.END, u.email FROM ".TABLE_PREFIX."_log l JOIN ".TABLE_PREFIX."_utilisateurs u ON l.LOGIN = u.login WHERE l.START > '" .$hist_year."-".$hist_month."-".$hist_day."' ORDER BY START DESC";
$res = grr_sql_query($sql);
if ($res){
  foreach($res as $row){
    $annee = substr($row['END'],0,4);
    $mois =  substr($row['END'],5,2);
    $jour =  substr($row['END'],8,2);
    $heures = substr($row['END'],11,2);
    $minutes = substr($row['END'],14,2);
    $secondes = substr($row['END'],17,2);
    $end_time = mktime($heures, $minutes, $secondes, $mois, $jour, $annee);
    if($end_time > $now){
      $color = "green";
    }
    elseif($row['AUTOCLOSE']){
      $color = "red";
    }
    else
      $color = "black";
    $hist[] = [$row["user"],$row["email"],$row["START"],$row['END'],$row["REMOTE_ADDR"],$row["USER_AGENT"],$row["REFERER"],$color];
  }
}
else
  fatal_error(0,grr_sql_error().get_vocab("failed_to_acquire"));
// début de page
start_page_w_header("", "", "", $type="with_session");
include "admin_col_gauche2.php";
echo "<div class='col-md-9 col-sm-8 col-xs-12'>";
echo "<h2>".get_vocab('admin_view_connexions.php')."</h2>";
echo "<h3>".get_vocab("users_connected")."</h3>";
echo '<div title="Utilisateur connecté">';
echo '	<ul>';
foreach($data as list($u_login,$u_name,$u_mail,$u_link)){
  echo "<li>";
  echo $u_name." | <a href=\"mailto:".$u_mail."\">".get_vocab('send_a_mail')."</a>";
  if($u_link) 
    echo " | <a href=\"admin_change_pwd.php?user_login=" . $row[0] . "\">".get_vocab("deconnect_changing_pwd")."</a>";
  echo "</li>";
}
echo '	</ul>';
echo '</div>';
echo '<hr />';

echo '<form action="admin_view_connexions.php" method="post">';
echo '<fieldset>';
echo '<legend style="font-variant: small-caps;">'.get_vocab("start_history").'</legend>';
echo '<table style="border: 0; width: 5%; margin: auto;">
				<tr>
					<td style="text-align: center; width: 24%; font-variant: small-caps;">JJ</td>
					<td style="text-align: center; width: 1%;">/</td>
					<td style="text-align: center; width: 24%; font-variant: small-caps;">MM</td>
					<td style="text-align: center; width: 1%;">/</td>
					<td style="text-align: center; width: 50%; font-variant: small-caps;">AAAA</td>
				</tr>';
echo '				<tr>';
echo '					<td><input type="text" name="histDay" size="2" value="'.$hist_day.'" style="text-align: center;"/></td>';
echo '					<td>/</td>';
echo '					<td><input type="text" name="histMonth" size="2" value="'.$hist_month.'" style="text-align: center;"/></td>';
echo '					<td>/</td>';
echo '					<td><input type="text" name="histYear" size="4" value="'.$hist_year.'" style="text-align: center;"/></td>';
echo '				</tr>';
echo '			</table>';
echo '			<div class="center"><input class="btn btn-primary" type="submit" value="'.get_vocab("OK").'" style="font-variant: small-caps;"/></div>';
echo '		</fieldset>';
echo '</form>';
echo '<h3>'.get_vocab("log").$hist_day."/".$hist_month."/".$hist_year.'</h3>';
echo '<div title="log" >';
echo '<p>'.get_vocab("msg_explain_log").'</p>';
echo '<table class="col table">';
echo '<tr>'.PHP_EOL;
echo '<th class="col">'.get_vocab("login_name").'</th>';
echo '<th class="col">'.get_vocab("begining_of_session").'</th>';
echo '<th class="col">'.get_vocab("end_of_session").'</th>';
echo '<th class="col">'.get_vocab("ip_adress").'</th>';
echo '<th class="col">'.get_vocab("navigator").'</th>';
echo '<th class="col">'.get_vocab("referer").'</th>';
echo '</tr>'.PHP_EOL;

foreach($hist as list($n,$m,$s,$e,$a,$b,$r,$c)){
  echo "<tr style='color:".$c."'>".PHP_EOL;
  echo "<td class=\"col\"><a href=\"mailto:" .$m. "\">".$n."</a></td>\n";
  echo "<td class=\"col\">".$s."</td>";
  echo "<td class=\"col\">".$e."</td>";
  echo "<td class=\"col\">".$a."</td>";
  echo "<td class=\"col\">".$b."</td>";
  echo "<td class=\"col\">".$r."</td>";
  echo "</tr>".PHP_EOL;
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
	$_POST['cleanYear'] = date("Y");
if (!isset($_POST['cleanMonth']))
	$_POST['cleanMonth'] = date("m");
if (!isset($_POST['cleanDay']))
	$_POST['cleanDay'] = date("d");
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
