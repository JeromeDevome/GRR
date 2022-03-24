<?php
/**
 * month_all.php
 * Interface d'accueil avec affichage par mois des réservation de toutes les ressources d'un domaine
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2022-02-08 14:57$
 * @author    Laurent Delineau & JeromeB & Yan Naessens
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

$grr_script_name = "month_all.php";

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

// Calcul du niveau de droit de réservation
$authGetUserLevel = authGetUserLevel($user_name, -1);
// vérifie si la date est dans la période réservable
if (check_begin_end_bookings($day, $month, $year))
{
    start_page_w_header($day,$month,$year,$type_session);
	showNoBookings($day, $month, $year, $back);
	exit();
}
//Renseigne les droits de l'utilisateur, si les droits sont insuffisants, l'utilisateur est averti.
if ((($authGetUserLevel < 1) && (Settings::get("authentification_obli") == 1)) || authUserAccesArea($user_name, $area) == 0)
{
    start_page_w_header($day,$month,$year,$type_session);
	showAccessDenied($back);
	exit();
}

// On vérifie une fois par jour si le délai de confirmation des réservations est dépassé
	// Si oui, les réservations concernées sont supprimées et un mail automatique est envoyé.
	// On vérifie une fois par jour que les ressources ont été rendue en fin de réservation
	// Si non, une notification email est envoyée
if (Settings::get("verif_reservation_auto") == 0)
{
	verify_confirm_reservation();
	verify_retard_reservation();
}

// Selection des ressources
$sql = "SELECT room_name, capacity, id, description, statut_room, show_fic_room, delais_option_reservation, moderate FROM ".TABLE_PREFIX."_room WHERE area_id='".$area."' ORDER BY order_display, room_name";
$ressources = grr_sql_query($sql);
if (!$ressources)
	fatal_error(0, grr_sql_error());
grr_sql_free($ressources);

// options pour l'affichage
$opt = array('horaires','beneficiaire','short_desc','description','create_by','type','participants');
$options = decode_options(Settings::get('cell_month_all'),$opt);
$options_popup = decode_options(Settings::get('popup_month_all'),$opt);
// calcul du contenu du planning
$month_start = mktime(0, 0, 0, $month, 1, $year);
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
$this_area_name = "";
$this_room_name = "";
$this_area_name = grr_sql_query1("SELECT area_name FROM ".TABLE_PREFIX."_area WHERE id=$area");
$i = mktime(0,0,0,$month - 1, 1, $year);
$yy = date("Y",$i);
$ym = date("n",$i);
$i = mktime(0,0,0,$month + 1, 1, $year);
$ty = date("Y",$i);
$tm = date("n",$i);
$all_day = preg_replace("/ /", " ", get_vocab("all_day2"));
//Get all meetings for this month in the area that we care about
$sql = "SELECT start_time, end_time, ".TABLE_PREFIX."_entry.id, name, beneficiaire, ".TABLE_PREFIX."_room.room_name,type, statut_entry, ".TABLE_PREFIX."_entry.description, ".TABLE_PREFIX."_entry.option_reservation, ".TABLE_PREFIX."_room.delais_option_reservation, ".TABLE_PREFIX."_entry.moderate, beneficiaire_ext, clef, ".TABLE_PREFIX."_entry.courrier, ".TABLE_PREFIX."_type_area.type_name, ".TABLE_PREFIX."_entry.overload_desc,".TABLE_PREFIX."_entry.room_id, ".TABLE_PREFIX."_entry.create_by, ".TABLE_PREFIX."_entry.nbparticipantmax 
FROM (".TABLE_PREFIX."_entry INNER JOIN ".TABLE_PREFIX."_room ON ".TABLE_PREFIX."_entry.room_id=".TABLE_PREFIX."_room.id ) 
  INNER JOIN ".TABLE_PREFIX."_type_area ON ".TABLE_PREFIX."_entry.type=".TABLE_PREFIX."_type_area.type_letter
WHERE (start_time <= $month_end AND end_time > $month_start AND area_id='".$area."')
ORDER by ".TABLE_PREFIX."_room.order_display, room_name, start_time, end_time ";
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
	$row[15]: Type_name
    $row[16]: overload fields description
    $row[17]: room_id
    $row[18]: create_by
    $row[19]: nbparticipantmax
*/

$res = grr_sql_query($sql);
if (!$res)
	echo grr_sql_error();
else  //Build an array of information about each day in the month.
{
    $overloadFieldList = mrbsOverloadGetFieldslist($area);
    $verif_acces_ressource = array();
    $acces_fiche_reservation = array();
    foreach($res as $row)
	{
		if ($row['type_name'] <> (Settings::get('exclude_type_in_views_all')))
        {
            $verif_acces_ressource[$row['room_name']] = verif_acces_ressource($user_name, $row['room_name']);
            $acces_fiche_reservation[$row['room_name']] = verif_acces_fiche_reservation($user_name, $row['room_name']);
            $t = max((int)$row['start_time'], $month_start);
            $end_t = min((int)$row['end_time'], $month_end);
            $day_num = date("j", $t);
            if ($enable_periods == 'y')
                $midnight = mktime(12, 0, 0, $month, $day_num, $year);
            else
                $midnight = mktime(0, 0, 0, $month, $day_num, $year);
            while ($t < $end_t)
            {
                $d[$day_num]["id"][] = $row['id'];
                $d[$day_num]["id_room"][] = $row['room_name'];
                $d[$day_num]["room"][] = $row['room_name'] ;
                $d[$day_num]["color"][] = $row['type'];
                $midnight_tonight = $midnight + 86400;
            //Describe the start and end time, accounting for "all day"
            //and for entries starting before/ending after today.
            //There are 9 cases, for start time < = or > midnight this morning,
            //and end time < = or > midnight tonight.
            //Use ~ (not -) to separate the start and stop times, because MSIE
            //will incorrectly line break after a -.
                if ($enable_periods == 'y')
                {
                    $start_str = preg_replace("/ /", " ", period_time_string($row['start_time']));
                    $end_str   = preg_replace("/ /", " ", period_time_string($row['end_time'], -1));
                    switch (cmp3($row['start_time'], $midnight) . cmp3($row['end_time'], $midnight_tonight))
                    {
                        case "> < ":         //Starts after midnight, ends before midnight
                        case "= < ":         //Starts at midnight, ends before midnight
                        if ($start_str == $end_str)
                            $horaires = $start_str;
                        else
                            $horaires = $start_str . get_vocab("to") . $end_str;
                        break;
                        case "> = ":         //Starts after midnight, ends at midnight
                        $horaires = $start_str . get_vocab("to")."24:00";
                        break;
                        case "> > ":         //Starts after midnight, continues tomorrow
                        $horaires = $start_str . get_vocab("to")."&gt;";
                        break;
                        case "= = ":         //Starts at midnight, ends at midnight
                        $horaires = $all_day;
                        break;
                        case "= > ":         //Starts at midnight, continues tomorrow
                        $horaires = $all_day . "&gt;";
                        break;
                        case "< < ":         //Starts before today, ends before midnight
                        $horaires = "&lt;".get_vocab("to") . $end_str;
                        break;
                        case "< = ":         //Starts before today, ends at midnight
                        $horaires = "&lt;" . $all_day;
                        break;
                        case "< > ":         //Starts before today, continues tomorrow
                        $horaires = "&lt;" . $all_day . "&gt;";
                        break;
                    }
                }
                else
                {
                    switch (cmp3($row['start_time'], $midnight) . cmp3($row['end_time'], $midnight_tonight))
                    {
                        case "> < ":         //Starts after midnight, ends before midnight
                        case "= < ":         //Starts at midnight, ends before midnight
                        $horaires = date(hour_min_format(), $row['start_time']) . get_vocab("to") . date(hour_min_format(), $row['end_time']);
                        break;
                        case "> = ":         //Starts after midnight, ends at midnight
                        $horaires = date(hour_min_format(), $row['start_time']) . get_vocab("to")."24:00";
                        break;
                        case "> > ":         //Starts after midnight, continues tomorrow
                        $horaires = date(hour_min_format(), $row['start_time']) . get_vocab("to")."&gt;";
                        break;
                        case "= = ":         //Starts at midnight, ends at midnight
                        $horaires = $all_day;
                        break;
                        case "= > ":         //Starts at midnight, continues tomorrow
                        $horaires = $all_day . "&gt;";
                        break;
                        case "< < ":         //Starts before today, ends before midnight
                        $horaires = "&lt;".get_vocab("to") . date(hour_min_format(), $row['end_time']);
                        break;
                        case "< = ":         //Starts before today, ends at midnight
                        $horaires = "&lt;" . $all_day;
                        break;
                        case "< > ":         //Starts before today, continues tomorrow
                        $horaires = "&lt;" . $all_day . "&gt;";
                        break;
                    }
                }
                $d[$day_num]["resa"][] = contenu_cellule($options, $overloadFieldList, 2, $row, $horaires);
                $d[$day_num]["popup"][] = contenu_popup($options_popup, 2, $row, $horaires);
                //Only if end time > midnight does the loop continue for the next day.
                if ($row['end_time'] <= $midnight_tonight)
                    break;
                $day_num++;
                $t = $midnight = $midnight_tonight;
            }
        }
	}
    grr_sql_free($res);
}
// pour le traitement des modules
include $racine."/include/hook.class.php";
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
			<button class=\"btn btn-default btn-xs\" onclick=\"charger();javascript: location.href='month_all.php?year=$yy&amp;month=$ym&amp;area=$area';\" \"><span class=\"glyphicon glyphicon-backward\"></span> ".get_vocab("monthbefore")." </button>
		</div>";
		include "./include/trailer.inc.php";
		echo "<div class=\"right\">
			<button class=\"btn btn-default btn-xs\" onclick=\"charger();javascript: location.href='month_all.php?year=$ty&amp;month=$tm&amp;area=$area';\" \">".get_vocab('monthafter')." <span class=\"glyphicon glyphicon-forward\"></span></button>
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
echo '<h4 class="titre"> '. ucfirst($this_area_name).' - '.get_vocab("all_areas").'<br>'.ucfirst(utf8_strftime("%B ", $month_start)).'<a href="year.php?area='.$area.'" title="'.get_vocab('see_all_the_rooms_for_several_months').'">'.ucfirst(utf8_strftime("%Y", $month_start)).'</a></h4>'.PHP_EOL;
if ($_GET['pview'] != 1)
    echo " <a href=\"month_all2.php?year=$year&amp;month=$month&amp;area=$area\" title=\" ".get_vocab('default_room_month_all_bis')."\"><span class='glyphicon glyphicon-refresh'></span></a>";
echo "</div>";
echo "</caption>";
	if (isset($_GET['precedent']))
	{
		if ($_GET['pview'] == 1 && $_GET['precedent'] == 1)
		{
			echo '<span id="lienPrecedent">'.PHP_EOL;
			echo '<button class="btn btn-default btn-xs" onclick="charger();javascript:history.back();">Précedent</button>'.PHP_EOL;
			echo '</span>'.PHP_EOL;
		}
	}
// domaine vide ?
if (grr_sql_count($ressources) == 0){
    echo "<tbody><tr><td><strong>".get_vocab("no_rooms_for_area")."</strong></td></tr></tbody>";
}
else{
	// Début affichage première ligne (intitulé des jours)
	echo '<thead>',PHP_EOL;
	for ($weekcol = 0; $weekcol < 7; $weekcol++)
	{
		$num_week_day = ($weekcol + $weekstarts) % 7;
		// on n'affiche pas tous les jours de la semaine
		if ($display_day[$num_week_day] == 1)
			echo '<th class="jour_sem">',day_name($num_week_day),'</th>',PHP_EOL;
	}
	echo '</thead>',PHP_EOL;
	// Fin affichage première ligne (intitulé des jours)
	// Début affichage des lignes affichant les réservations
	// On grise les cellules appartenant au mois précédent
	echo "<tbody>";
    $weekcol = 0;
	if ($weekcol != $weekday_start)
	{
		echo '<tr>',PHP_EOL;
		for ($weekcol = 0; $weekcol < $weekday_start; $weekcol++)
		{
			$num_week_day = ($weekcol + $weekstarts)%7;
			if ($display_day[$num_week_day] == 1)
				echo '<td class="cell_month_o"></td>',PHP_EOL;
		}
	}
	// Début Première boucle sur les jours du mois
	for ($cday = 1; $cday <= $days_in_month; $cday++)
	{
		$num_week_day = ($weekcol + $weekstarts) % 7;
		$t = mktime(0, 0, 0, $month,$cday,$year);
		$name_day = ucfirst(utf8_strftime("%d", $t));
		$jour_cycle = grr_sql_query1("SELECT Jours FROM ".TABLE_PREFIX."_calendrier_jours_cycle WHERE DAY='$t'");
		if ($weekcol == 0)
			echo '<tr>',PHP_EOL;
		if ($display_day[$num_week_day] == 1) // début condition "on n'affiche pas tous les jours de la semaine"
		{
			echo '<td >',PHP_EOL;
			// On affiche les jours du mois dans le coin supérieur gauche de chaque cellule
			$ferie_true = 0;
			$class = "";
			$title = "";
			if ($settings->get("show_holidays") == "Oui")
                {   
                    if (isHoliday($t)){
                        $class .= 'ferie ';
                    }
                    elseif (isSchoolHoliday($t)){
                        $class .= 'vacance ';
                    }
                }
			echo '<div class="monthday ',$class,'">',PHP_EOL,'<a title="',htmlspecialchars(get_vocab("see_all_the_rooms_for_the_day")),$title,'" href="day.php?year=',$year,'&amp;month=',$month,'&amp;day=',$cday,'&amp;area=',$area,'">',$name_day;
			if (Settings::get("jours_cycles_actif") == "Oui" && intval($jour_cycle) > -1)
			{
				if (intval($jour_cycle) > 0)
					echo "<span class='tiny'> - ".get_vocab("rep_type_6")." ".$jour_cycle."</span>";
				else
					echo "<span class='tiny'> - ".$jour_cycle."</span>";
			}
			echo '</a>',PHP_EOL,'</div>',PHP_EOL;
			if (est_hors_reservation(mktime(0,0,0,$month,$cday,$year),$area))
			{
				echo '<div class="empty_cell">',PHP_EOL;
				echo '<img src="img_grr/stop.png" alt="',get_vocab("reservation_impossible"),'" title="',get_vocab("reservation_impossible"),'" width="16" height="16" class="',$class_image,'" />',PHP_EOL,'</div>',PHP_EOL;
			}
			else
			{
				// Des réservation à afficher pour ce jour ?
				if (isset($d[$cday]["id"][0]))
				{
					$n = count($d[$cday]["id"]);
					//Show the reservation information, linked to view_entry.
					//If there are 12 or fewer, show them, else show 11 and "...".
					for ($i = 0; $i < $n; $i++)
					{
						if ($verif_acces_ressource[$d[$cday]["id_room"][$i]]) // On n'affiche pas les réservations des ressources non visibles pour l'utilisateur.
						{	
							if ($i == 11 && $n > 12)
							{
								echo " ...\n";
								break;
							}
							echo '<table class="pleine table-bordered table-striped">',PHP_EOL,'<tr>',PHP_EOL;
							tdcell($d[$cday]["color"][$i]);
							echo '<span class="small_planning">',PHP_EOL;
							if ($acces_fiche_reservation[$d[$cday]["id_room"][$i]])
							{
								if (Settings::get("display_level_view_entry") == 0)
								{
									$currentPage = 'month_all';
									$id =   $d[$cday]["id"][$i];
									echo '<a title="',$d[$cday]["popup"][$i],'" data-width="675" onclick="request(',$id,',',$cday,',',$month,',',$year.',\'all\',\''.$currentPage,'\',readData);" data-rel="popup_name" class="poplight lienCellule">',PHP_EOL;
								}
								else
								{
									echo '<a class="lienCellule" title="',$d[$cday]["popup"][$i],'" href="view_entry.php?id=',$d[$cday]["id"][$i],'&amp;day=',$cday,'&amp;month=',$month,'&amp;year=',$year,'&amp;page=month_all">',PHP_EOL;
								}
							}
                            echo $d[$cday]["resa"][$i];
							if ($acces_fiche_reservation[$d[$cday]["id_room"][$i]])
								echo '</a>',PHP_EOL;
							echo '</span>',PHP_EOL,'</td>',PHP_EOL,'</tr>',PHP_EOL,'</table>',PHP_EOL;
						}
					}
				/*
                envisager d'inclure ici un + pour ouvrir une fenêtre de réservation
				*/
				}
                else 
                    echo "<div class='empty_cell'> </div>";
            }
			echo '</td>',PHP_EOL;
		} // fin condition "on n'affiche pas tous les jours de la semaine"
		if (++$weekcol == 7)
		{
			$weekcol = 0;
			echo '</tr>',PHP_EOL;
		}
	}
	// Fin Première boucle sur les jours du mois !
	// On grise les cellules appartenant au mois suivant
	if ($weekcol > 0)
	{
		for (; $weekcol < 7; $weekcol++)
		{
			$num_week_day = ($weekcol + $weekstarts) % 7;
				// on n'affiche pas tous les jours de la semaine
			if ($display_day[$num_week_day] == 1)
				echo '<td class="cell_month_o" ></td>',PHP_EOL;
		}
        echo "</tr>";
	}
    echo "</tbody>";
}
	echo '</table>',PHP_EOL;
//Fermeture DIV Planning2
echo " </div>";
echo  "<div id=\"popup_name\" class=\"popup_block\" ></div>";
if ($_GET['pview'] != 1)
{
	echo '<div id="toTop">'.PHP_EOL;
	echo '<b>'.get_vocab('top_of_page').'</b>'.PHP_EOL;
	bouton_retour_haut ();
	echo '</div>'.PHP_EOL;
}
affiche_pop_up(get_vocab("message_records"),"user");
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