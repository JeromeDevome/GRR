<?php
/**
 * month.php
 * Interface d'accueil avec affichage par mois pour une ressource
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2024-02-06 16:13$
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
$grr_script_name = "month.php";

include "include/connect.inc.php";
include "include/config.inc.php";
include "include/misc.inc.php";
include "include/$dbsys.inc.php";
include "include/mrbs_sql.inc.php";
include "include/functions.inc.php";
include "include/mincals.inc.php";
require_once("./include/settings.class.php");
$settings = new Settings();
if (!$settings)
	die("Erreur chargement settings");
require_once("./include/session.inc.php");
include "include/resume_session.php";
include "include/language.inc.php";

//Construction des identifiants de la ressource $room, du domaine $area, du site $id_site
Definition_ressource_domaine_site();

//Récupération des données concernant l'affichage du planning du domaine
if($area >0)
    get_planning_area_values($area);

// Initilisation des variables
$affiche_pview = '1';
if (!isset($_GET['pview']))
	$_GET['pview'] = 0;
else
	$_GET['pview'] = 1;

if ($_GET['pview'] == 1)
	$class_image = "print_image";
else
	$class_image = "image";
// initialisation des paramètres de temps
$date_now = time();
$day = (isset($_GET['day']))? $_GET['day'] : date("d"); // ou 1 ? YN le 07/03/2018
$month = (isset($_GET['month']))? $_GET['month'] : date("m");
$year = (isset($_GET['year']))? $_GET['year'] : date("Y");
// définition de variables globales
global $racine, $racineAd, $desactive_VerifNomPrenomUser;

// Lien de retour
$back = (isset($_SERVER['HTTP_REFERER']))? htmlspecialchars_decode($_SERVER['HTTP_REFERER'], ENT_QUOTES) : page_accueil() ;
// Type de session
$user_name = getUserName();
if ((Settings::get("authentification_obli") == 0) && ($user_name == ''))
	$type_session = "no_session";
else
	$type_session = "with_session";
// autres initialisations
$adm = 0;
$racine = "./";
$racineAd = "./admin/";

if (!($desactive_VerifNomPrenomUser))
    $desactive_VerifNomPrenomUser = 'n';
// On vérifie que les noms et prénoms ne sont pas vides
VerifNomPrenomUser($type_session);
// Dans le cas d'une selection invalide
if ($area <= 0)
{
    start_page_w_header($day,$month,$year,$type_session);
	echo '<h1>'.get_vocab("noareas").'</h1>';
	echo '<a href="./admin/admin_accueil.php">'.get_vocab("admin").'</a>'.PHP_EOL.'</body>'.PHP_EOL.'</html>';
	exit();
}
// en l'absence du paramètre $room, indispensable pour month.php, on renvoie à month_all.php
if (!isset($room)){
    $msg = get_vocab('choose_a_room');
    $lien = "month_all.php?area=".$area."&month=".$month."&year=".$year;
    echo "<script type='text/javascript'>
        alert('$msg');
        document.location.href='$lien';
    </script>";
    echo "<p><br/>";
        echo get_vocab('choose_room')."<a href='month_all.php'>".get_vocab("link")."</a>";
    echo "</p>";
    die();
}
// vérifie si la date est dans la période réservable
if (check_begin_end_bookings($day, $month, $year))
{
    start_page_w_header($day,$month,$year,$type_session);
	showNoBookings($day, $month, $year, $back);
	exit();
}
//Heure de début du mois, cela ne sert à rien de reprendre les valeurs morningstarts/eveningends
$month_start = mktime(0, 0, 0, $month, 1, $year);
//Dans quel colonne l'affichage commence: 0 veut dire $weekstarts
$weekday_start = (date("w", $month_start) - $weekstarts + 7) % 7;
$days_in_month = date("t", $month_start);
$month_end = mktime(23, 59, 59, $month, $days_in_month, $year);
if ($enable_periods == 'y')
{
	$resolution = 60;
	$morningstarts = 12;
	$eveningends = 12;
	$eveningends_minutes = count($periods_name) - 1;
}
$this_area_name = grr_sql_query1("SELECT area_name FROM ".TABLE_PREFIX."_area WHERE id=?", "i", [$area]);
$sql = "SELECT * FROM ".TABLE_PREFIX."_room WHERE id=?";
$res = grr_sql_query($sql,"i",[$room]);
if ($res){
    $this_room = grr_sql_row_keyed($res,0);
}
$this_room_name = (isset($this_room['room_name']))? $this_room['room_name']:"";
$this_room_max = (isset($this_room['capacity']))? $this_room['capacity']:0;
$this_room_name_des = (isset($this_room['description']))? $this_room['description']:'';
$this_statut_room = (isset($this_room['statut_room']))? $this_room['statut_room']:1;
$this_moderate_room = (isset($this_room['moderate']))? $this_room['moderate']:0;
$this_delais_option_reservation = (isset($this_room['delais_option_reservation']))? $this_room['delais_option_reservation']:0;
$this_room_comment = (isset($this_room['comment_room']))? $this_room['comment_room']:'';
$this_room_show_comment = (isset($this_room['show_comment']))? $this_room['show_comment']:'n';
$who_can_book = (isset($this_room['who_can_book']))? $this_room['who_can_book']:1;
grr_sql_free($res);

if ($this_room_name_des!="")
	$this_room_name_des = " (".$this_room_name_des.")";

$i = mktime(0, 0, 0, $month - 1, 1, $year);
$yy = date("Y", $i);
$ym = date("n", $i);
$i = mktime(0, 0, 0,$month + 1, 1, $year);
$ty = date("Y", $i);
$tm = date("n", $i);

$auth_user_level = authGetUserLevel($user_name,$room);
// si la ressource est restreinte, l'utilisateur peut-il réserver ?
$user_can_book = $who_can_book || ($auth_user_level > 2) || (authBooking($user_name,$room));
// options pour l'affichage
$opt = array('horaires','beneficiaire','short_desc','description','create_by','type','participants');
$options = decode_options(Settings::get('cell_month'),$opt);
$options_popup = decode_options(Settings::get('popup_month'),$opt);
$all_day = preg_replace("/ /", " ", get_vocab("all_day2"));
// calcul du contenu du planning
$sql = "SELECT start_time, end_time, ".TABLE_PREFIX."_entry.id, name, beneficiaire, ".TABLE_PREFIX."_room.room_name,type, statut_entry, ".TABLE_PREFIX."_entry.description, ".TABLE_PREFIX."_entry.option_reservation, ".TABLE_PREFIX."_room.delais_option_reservation, ".TABLE_PREFIX."_entry.moderate, beneficiaire_ext, clef, ".TABLE_PREFIX."_entry.courrier, ".TABLE_PREFIX."_entry.overload_desc,".TABLE_PREFIX."_entry.room_id, ".TABLE_PREFIX."_entry.create_by, ".TABLE_PREFIX."_entry.nbparticipantmax 
FROM (".TABLE_PREFIX."_entry JOIN ".TABLE_PREFIX."_room ON ".TABLE_PREFIX."_entry.room_id=".TABLE_PREFIX."_room.id) 
WHERE ".TABLE_PREFIX."_entry.room_id = ?
AND start_time <= ? AND end_time > ?
ORDER BY start_time";
/* contenu de la réponse si succès :
    $row[0] : start_time
    $row[1] : end_time
    $row[2] : entry id
    $row[3] : name
    $row[4] : beneficiaire
    $row[5] : room name
    $row[6] : type
    $row[7] : statut_entry
    $row[8] : entry description
    $row[9] : entry option_reservation
    $row[10]: room delais_option_reservation
    $row[11]: entry moderate
    $row[12]: beneficiaire_ext
    $row[13]: clef
    $row[14]: courrier
	$row[15]: Type_name supprimé le 07/11/22
    $row[16]: overload fields description
    $row[17]: room_id
    $row[18]: create_by
    $row[19]: nbparticipantmax
*/
$res = grr_sql_query($sql,"iii",[$room,$month_end,$month_start]);
if (!$res)
	echo grr_sql_error();
else
{
    $overloadFieldList = mrbsOverloadGetFieldslist($area);
    foreach($res as $row)
	{
		$t = max((int)$row['start_time'], $month_start);
		$end_t = min((int)$row['end_time'], $month_end);
		$day_num = date("j", $t);
		if ($enable_periods == 'y')
			$midnight = mktime(12,0,0,$month,$day_num,$year);
		else
			$midnight = mktime(0, 0, 0, $month, $day_num, $year);
		while ($t < $end_t)
		{
			$d[$day_num]["id"][] = $row['id'];
			$d[$day_num]["color"][] = $row['type'];
			$midnight_tonight = $midnight + 86400;
			if ($enable_periods == 'y')
			{
				$start_str = preg_replace("/ /", " ", period_time_string($row['start_time']));
				$end_str   = preg_replace("/ /", " ", period_time_string($row['end_time'], -1));
				switch (cmp3($row['start_time'], $midnight) . cmp3($row['end_time'], $midnight_tonight))
				{
					case "> < ":
					case "= < ":
					if ($start_str == $end_str)
						$horaires = $start_str;
					else
						$horaires = $start_str . get_vocab("to") . $end_str;
					break;
					case "> = ":
					$horaires = $start_str . get_vocab("to"). "24:00";
					break;
					case "> > ":
					$horaires = $start_str . get_vocab("to") ."==>";
					break;
					case "= = ":
					$horaires = $all_day;
					break;
					case "= > ":
					$horaires = $all_day . "==>";
					break;
					case "< < ":
					$horaires = "<==".get_vocab("to") . $end_str;
					break;
					case "< = ":
					$horaires = "<==" . $all_day;
					break;
					case "< > ":
					$horaires = "<" . $all_day . ">";
					break;
				}
			}
			else
			{
				switch (cmp3($row['start_time'], $midnight) . cmp3($row['end_time'], $midnight_tonight))
				{
					case "> < ":
					case "= < ":
					$horaires = date(hour_min_format(), $row['start_time']) . get_vocab("to") . date(hour_min_format(), $row['end_time']);
					break;
					case "> = ":
					$horaires = date(hour_min_format(), $row['start_time']) . get_vocab("to")."24:00";
					break;
					case "> > ":
					$horaires = date(hour_min_format(), $row['start_time']) . get_vocab("to")."==>";
					break;
					case "= = ":
					$horaires = $all_day;
					break;
					case "= > ":
					$horaires = $all_day . "==>";
					break;
					case "< < ":
					$horaires = "<==".get_vocab("to") . date(hour_min_format(), $row['end_time']);
					break;
					case "< = ":
					$horaires = "<==" . $all_day;
					break;
					case "< > ":
					$horaires = "<" . $all_day . ">";
					break;
				}
			}
            $d[$day_num]["resa"][] = contenu_cellule($options, $overloadFieldList, 1, $row, $horaires);
            $d[$day_num]["popup"][] = contenu_popup($options_popup, 1, $row, $horaires);

			//Seulement si l'heure de fin est après minuit, on continue le jour prochain.
			if ($row['end_time'] <= $midnight_tonight)
				break;
			$day_num++;
			$t = $midnight = $midnight_tonight;
		}
	}
    grr_sql_free($res);
}    

// pour le traitement des modules
include "./include/hook.class.php";
// code html de la page
header('Content-Type: text/html; charset=utf-8');
if (!isset($_COOKIE['open']))
{
	header('Set-Cookie: open=true; SameSite=Lax');
}
echo '<!DOCTYPE html>'.PHP_EOL;
echo '<html lang="fr">'.PHP_EOL;
// section <head>
if ($type_session == "with_session")
    echo pageHead2(Settings::get("company"),"with_session");
else
    echo pageHead2(Settings::get("company"),"no_session");
// section <body>
echo "<body>";
// Menu du haut = section <header>
echo "<header>";
pageHeader2($day, $month, $year, $type_session);
echo "</header>";
echo '<div id="chargement"></div>'.PHP_EOL; // à éliminer ?
// Debut de la page
echo "<section>".PHP_EOL;
// Affichage du menu en haut ou à gauche
include("menuHG.php");
// affichage du planning
if ($_GET['pview'] != 1){
    echo "<div id='planning2'>";
}
else{
	echo '<div id="print_planning">'.PHP_EOL;
}
echo '<table class="mois table-bordered table-striped">',PHP_EOL;
// le titre de la table
echo "<caption>";
// liens mois avant-après et imprimante si page non imprimable
if ((!isset($_GET['pview'])) or ($_GET['pview'] != 1))
{
	echo "\n
	<div class='ligne23'>
		<div class=\"left\">
			<button class=\"btn btn-default btn-xs\" onclick=\"javascript: location.href='month.php?year=$yy&amp;month=$ym&amp;room=$room';\" ><span class=\"glyphicon glyphicon-backward\"></span> ".get_vocab("monthbefore")." </button>
		</div>";
		include "./include/trailer.inc.php";
		echo "<div class=\"right\">
			<button class=\"btn btn-default btn-xs\" onclick=\"javascript: location.href='month.php?year=$ty&amp;month=$tm&amp;room=$room';\">".get_vocab('monthafter')." <span class=\"glyphicon glyphicon-forward\"></span></button>
		</div>
	</div>";
}
// montrer ou cacher le menu gauche
echo "<div>";
if ((!isset($_GET['pview'])) || ($_GET['pview'] != 1))
{
    echo "<div class=\"left\"> "; // afficher ou cacher le menu
    $mode = Settings::get("menu_gauche");
    $alt = ($mode != 0)? $mode : 1; // il faut bien que le menu puisse s'afficher, par défaut ce sera à gauche sauf choix autre par setting
    echo "<div id='voir'><button class=\"btn btn-default btn-sm\" onClick=\"afficheMenuHG($alt)\" title='".get_vocab('show_left_menu')."'><span class=\"glyphicon glyphicon-chevron-right\"></span></button></div> ";
    echo "<div id='cacher'><button class=\"btn btn-default btn-sm\" onClick=\"afficheMenuHG(0)\" title='".get_vocab('hide_left_menu')."'><span class=\"glyphicon glyphicon-chevron-left\"></span></button></div> "; 
	echo "</div>";
}    
$maxCapacite = "";
if ($this_room_max  && $_GET['pview'] != 1)
	$maxCapacite = '('.$this_room_max.' '.($this_room_max > 1 ? get_vocab("number_max2") : get_vocab("number_max")).')'.PHP_EOL;
echo '<h4 class="titre"> '. ucfirst($this_area_name).' - '.$this_room_name.' '.$this_room_name_des.' '.$maxCapacite.'<br>'.ucfirst(utf8_strftime("%B ", $month_start)).'<a href="year.php?area='.$area.'" title="'.get_vocab('see_all_the_rooms_for_several_months').'">'.ucfirst(utf8_strftime("%Y", $month_start)).'</a></h4>'.PHP_EOL;
if (verif_display_fiche_ressource($user_name, $room) && $_GET['pview'] != 1)
	echo '<a href="javascript:centrerpopup(\'view_room.php?id_room=',$room,'\',600,480,\'scrollbars=yes,statusbar=no,resizable=yes\')" title="',get_vocab("fiche_ressource"),'"><span class="glyphcolor glyphicon glyphicon-search"></span></a>';
if ($auth_user_level > 2 && $_GET['pview'] != 1)
	echo "<a href=\"./admin/admin_edit_room.php?room=$room\" title='".get_vocab('editroom')."'><span class=\"glyphcolor glyphicon glyphicon-cog\"></span></a>";
affiche_ressource_empruntee($room);
if ($this_statut_room == "0")
	echo '<br><span class="texte_ress_tempo_indispo">',get_vocab("ressource_temporairement_indisponible"),'</span>';
if ($this_moderate_room == "1")
	echo '<br><span class="texte_ress_moderee">',get_vocab("reservations_moderees"),'</span>';
if (isset($_GET['precedent']))
{
	if ($_GET['pview'] == 1 && $_GET['precedent'] == 1)
	{
		echo '<span id="lienPrecedent">',PHP_EOL,'<button class="btn btn-default btn-xs" onclick="javascript:history.back();">Précedent</button>',PHP_EOL,'</span>',PHP_EOL;
	}
}
if ($this_room_show_comment == "y" && $_GET['pview'] != 1 && ($this_room_comment != "") && ($this_room_comment != -1))
	echo '<div class="center">',$this_room_comment,'</div>',PHP_EOL;
echo "</div>";
echo "</caption>";
echo "<thead><tr>";
for ($weekcol = 0; $weekcol < 7; $weekcol++)
{
    $num_week_day = ($weekcol + $weekstarts) % 7;
    if ($display_day[$num_week_day] == 1)
        echo '<th class="jour_sem">',day_name(($weekcol + $weekstarts) % 7),'</th>',PHP_EOL;
}
echo '</tr></thead>',PHP_EOL;
echo '<tbody>',PHP_EOL;
$weekcol = 0;
if ($weekcol != $weekday_start)
{
    echo '<tr>',PHP_EOL;
    for ($weekcol = 0; $weekcol < $weekday_start; $weekcol++)
    {
        $num_week_day = ($weekcol + $weekstarts) % 7;
        if ($display_day[$num_week_day] == 1)
            echo '<td class="cell_month_o">',PHP_EOL,'</td>',PHP_EOL;
    }
}
$acces_fiche_reservation = verif_acces_fiche_reservation($user_name, $room);
$userRoomMaxBooking = UserRoomMaxBooking($user_name, $room, 1);
$auth_visiteur = auth_visiteur($user_name, $room);
for ($cday = 1; $cday <= $days_in_month; $cday++)
{
    $class = "";
    $title = "";
    $num_week_day = ($weekcol + $weekstarts) % 7;
    $t = mktime(0, 0, 0, $month, $cday,$year);
    $name_day = ucfirst(utf8_strftime("%d", $t));
    $jour_cycle = grr_sql_query1("SELECT Jours FROM ".TABLE_PREFIX."_calendrier_jours_cycle WHERE DAY=?","i",[$t]);
    if ($weekcol == 0)
        echo '<tr>',PHP_EOL;
    if ($display_day[$num_week_day] == 1)
    {
        if ($settings->get("show_holidays") == "Oui")
        {   
            $now = $t;
            if (isHoliday($now)){
                $class .= 'ferie ';
            }
            elseif (isSchoolHoliday($now)){
                $class .= 'vacance ';
            }
        }
        echo '<td >',PHP_EOL,'<div class="monthday ',$class,'">',PHP_EOL,'<a title="',htmlspecialchars(get_vocab("see_all_the_rooms_for_the_day")),$title,'" href="day.php?year=',$year,'&amp;month=',$month,'&amp;day=',$cday,'&amp;area=',$area,'">',$name_day;
        if (Settings::get("jours_cycles_actif") == "Oui" && intval($jour_cycle) >- 1)
        {
            if (intval($jour_cycle) > 0)
                echo '<span class="tiny"> - ',get_vocab("rep_type_6"),' ',$jour_cycle,'</span>';
            else
                echo '<span class="tiny"> - ',$jour_cycle,'</span>';
        }
        echo '</a>',PHP_EOL,'</div>',PHP_EOL;
        if (est_hors_reservation(mktime(0, 0, 0, $month, $cday, $year), $area))
        {
            echo '<div class="empty_cell">',PHP_EOL;
            echo '<img src="img_grr/stop.png" alt="',get_vocab("reservation_impossible"),'" title="',get_vocab("reservation_impossible"),'" width="16" height="16" class="',$class_image,'" />',PHP_EOL;
            echo '</div>',PHP_EOL;
        }
        else
        {
            if (isset($d[$cday]["id"][0]))
            {
                $n = count($d[$cday]["id"]);
                for ($i = 0; $i < $n; $i++)
                {
                    if ($i == 11 && $n > 12)
                    {
                        echo " ...\n";
                        break;
                    }
                    echo '<table class="pleine table-bordered table-striped">',PHP_EOL,'<tr>',PHP_EOL;
                    tdcell($d[$cday]["color"][$i]);
                    echo '<span class="small_planning">';
                    if ($acces_fiche_reservation)
                    {
                        if (Settings::get("display_level_view_entry") == 0)
                        {
                            $currentPage = 'month';
                            $id = $d[$cday]["id"][$i];
                            echo '<a title="'.$d[$cday]["popup"][$i].'" data-width="675" onclick="request(',$id,',',$cday,',',$month,',',$year.','.$room.',\''.$currentPage,'\',readData);" data-rel="popup_name" class="poplight lienCellule">';
                        }
                        else
                        {
                            echo '<a class="lienCellule" title="'.$d[$cday]["popup"][$i].'" href="view_entry.php?id=',$d[$cday]["id"][$i],'&amp;day=',$cday,'&amp;month=',$month,'&amp;year=',$year,'&amp;page=month">';
                        }
                    }
                    echo $d[$cday]["resa"][$i];
                    if ($acces_fiche_reservation)
                        echo '</a>',PHP_EOL;
                    echo '</span>',PHP_EOL,'</td>',PHP_EOL,'</tr>',PHP_EOL,'</table>',PHP_EOL;
                }
            }
            if (plages_libre_semaine_ressource($room, $month, $cday, $year)){
                echo '<div class="empty_cell">'.PHP_EOL;
                $date_now = time();
                $hour = date("H",$date_now);
                $date_booking = mktime(23,59, 0, $month, $cday, $year);
                if ((($auth_user_level > 1) || ($auth_visiteur == 1))
                    && ($userRoomMaxBooking != 0)
                    && verif_booking_date($user_name, -1, $room, $date_booking, $date_now, $enable_periods)
                    && verif_delais_max_resa_room($user_name, $room, $date_booking)
                    && verif_delais_min_resa_room($user_name, $room, $date_booking, $enable_periods)
                    && (($this_statut_room == "1") || (($this_statut_room == "0") && ($auth_user_level > 2)))
                    && $user_can_book
                    && $_GET['pview'] != 1)
                {
                    if ($enable_periods == 'y')
                        echo '<a href="edit_entry.php?room=',$room,'&amp;period=&amp;year=',$year,'&amp;month=',$month,'&amp;day=',$cday,'&amp;page=month" title="',get_vocab("cliquez_pour_effectuer_une_reservation"),'"><span class="glyphicon glyphicon-plus"></span></a>',PHP_EOL;
                    else
                        echo '<a href="edit_entry.php?room=',$room,'&amp;hour=',$hour,'&amp;minute=0&amp;year=',$year,'&amp;month=',$month,'&amp;day=',$cday,'&amp;page=month" title="',get_vocab("cliquez_pour_effectuer_une_reservation"),'"><span class="glyphicon glyphicon-plus"></span></a>',PHP_EOL;
                }
                echo '</div>'.PHP_EOL;
            }
        }
        echo '</td>'.PHP_EOL;
    }
    if (++$weekcol == 7)
    {
        $weekcol = 0;
        echo '</tr>'.PHP_EOL;
    }
}
if ($weekcol > 0)
{
    for (; $weekcol < 7; $weekcol++)
    {
        $num_week_day = ($weekcol + $weekstarts)%7;
        if ($display_day[$num_week_day] == 1)
            echo '<td class="cell_month_o" > </td>'.PHP_EOL;
    }
}
echo '</tr>'.PHP_EOL.'</tbody></table>'.PHP_EOL;
if ($_GET['pview'] != 1)
{
    echo '<div id="toTop">',PHP_EOL,'<b>',get_vocab("top_of_page"),'</b>',PHP_EOL;
    bouton_retour_haut ();
    echo '</div>',PHP_EOL;
}
echo "</div>"; // fin de planning2
affiche_pop_up(get_vocab("message_records"),"user");
echo '<div id="popup_name" class="popup_block"></div>'.PHP_EOL;
echo "</section>";
?>
<script type="text/javascript">
	$(document).ready(function(){
        afficheMenuHG(<?php echo $mode; ?>);
        $("#popup_name").draggable({containment: "#container"});
		$("#popup_name").resizable();
        if ( $(window).scrollTop() == 0 )
            $("#toTop").hide(1);
	});
</script>
</body>
</html>