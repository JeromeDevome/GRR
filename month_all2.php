<?php
/**
 * month_all2.php
 * Interface d'accueil avec affichage par mois des réservations de toutes les ressources d'un domaine
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2019-04-03 14:30$
 * @author    Laurent Delineau & JeromeB & Yan Naessens
 * @copyright Copyright 2003-2019 Team DEVOME - JeromeB
 * @link      http://www.gnu.org/licenses/licenses.html
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */
// cette page est partiellement internationalisée : à compléter

$grr_script_name = "month_all2.php";

include "include/connect.inc.php";
include "include/config.inc.php";
include "include/misc.inc.php";
include "include/functions.inc.php";
include "include/$dbsys.inc.php";
include "include/mincals.inc.php";
include "include/mrbs_sql.inc.php";
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
$back = (isset($_SERVER['HTTP_REFERER']))? $_SERVER['HTTP_REFERER'] : page_accueil() ;

// Type de session
if ((Settings::get("authentification_obli") == 0) && (getUserName() == ''))
	$type_session = "no_session";
else
	$type_session = "with_session";
// autres initialisations
if (@file_exists('./admin_access_area.php')){
    $adm = 1;
    $racine = "../";
    $racineAd = "./";
}else{
    $adm = 0;
    $racine = "./";
    $racineAd = "./admin/";
}
// pour le traitement des modules
include $racine."/include/hook.class.php";

if (!($desactive_VerifNomPrenomUser))
    $desactive_VerifNomPrenomUser = 'n';
// On vérifie que les noms et prénoms ne sont pas vides
VerifNomPrenomUser($type_session);

// code html
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
// Debut de la page
echo '<section>'.PHP_EOL;

// Affichage du menu
include("menu_gauche2.php");
include("chargement.php");

// Dans le cas d'une selection invalide
if ($area <= 0)
{
	echo '<h1>'.get_vocab("noareas").'</h1>';
	echo '<a href="./admin/admin_accueil.php">'.get_vocab("admin").'</a>'.PHP_EOL.'</body>'.PHP_EOL.'</html>';
	exit();
}

// Calcul du niveau de droit de réservation
$authGetUserLevel = authGetUserLevel(getUserName(), -1);
// vérifie si la date est dans la période réservable
if (check_begin_end_bookings($day, $month, $year))
{
	showNoBookings($day, $month, $year, $back);
	exit();
}
//Renseigne les droits de l'utilisateur, si les droits sont insuffisants, l'utilisateur est averti.
if ((($authGetUserLevel < 1) && (Settings::get("authentification_obli") == 1)) || authUserAccesArea(getUserName(), $area) == 0)
{
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

// Contrôle si il y a une ressource dans le domaine
if (grr_sql_count($ressources) == 0)
{
	echo "<h1>".get_vocab("no_rooms_for_area")."</h1>";
	die();
}

// calcul du contenu du planning2
$month_start = mktime(0, 0, 0, $month, 1, $year);
$weekday_start = (date("w", $month_start) - $weekstarts + 7) % 7;
$days_in_month = date("t", $month_start);
$month_end = mktime(23, 59, 59, $month, $days_in_month, $year);
if ($enable_periods=='y')
{
	$resolution = 60;
	$morningstarts = 12;
	$eveningends = 12;
	$eveningends_minutes = count($periods_name)-1;
}
$this_area_name = "";
$this_room_name = "";
$this_area_name = grr_sql_query1("SELECT area_name FROM ".TABLE_PREFIX."_area WHERE id=$area");
$i = mktime(0,0,0,$month-1,1,$year);
$yy = date("Y",$i);
$ym = date("n",$i);
$i = mktime(0,0,0,$month+1,1,$year);
$ty = date("Y",$i);
$tm = date("n",$i);

$all_day = preg_replace("/ /", " ", get_vocab("all_day"));
$sql = "SELECT start_time, end_time,".TABLE_PREFIX."_entry.id, name, beneficiaire, room_name, statut_entry, ".TABLE_PREFIX."_entry.description, ".TABLE_PREFIX."_entry.option_reservation, ".TABLE_PREFIX."_room.delais_option_reservation, type, ".TABLE_PREFIX."_entry.moderate
FROM ".TABLE_PREFIX."_entry inner join ".TABLE_PREFIX."_room on ".TABLE_PREFIX."_entry.room_id=".TABLE_PREFIX."_room.id
WHERE (start_time <= $month_end AND end_time > $month_start and area_id='".$area."')
ORDER by start_time, end_time, ".TABLE_PREFIX."_room.room_name";
$res = grr_sql_query($sql);
if (!$res)
	echo grr_sql_error();
else
{
	for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
	{
		$sql_beneficiaire = "SELECT prenom, nom FROM ".TABLE_PREFIX."_utilisateurs WHERE login='$row[4]'";
		$res_beneficiaire = grr_sql_query($sql_beneficiaire);
		if ($res_beneficiaire)
			$row_user = grr_sql_row($res_beneficiaire, 0);
		if ($debug_flag)
			echo "<br />DEBUG: result $i, id $row[2], starts $row[0], ends $row[1]\n";
		$t = max((int)$row[0], $month_start);
		$end_t = min((int)$row[1], $month_end);
		$day_num = date("j", $t);
		if ($enable_periods == 'y')
			$midnight = mktime(12,0,0,$month,$day_num,$year);
		else
			$midnight = mktime(0, 0, 0, $month, $day_num, $year);
		while ($t < $end_t)
		{
			if ($debug_flag)
				echo "<br />DEBUG: Entry $row[2] day $day_num\n";
			$d[$day_num]["id"][] = $row[2];
			$temp = "";
			if (Settings::get("display_info_bulle") == 1)
				$temp = get_vocab("reservee au nom de").$row_user[0]." ".$row_user[1];
			else if (Settings::get("display_info_bulle") == 2)
				$temp = $row[7];
			if ($temp != "")
				$temp = " - ".$temp;
			$d[$day_num]["who1"][] = affichage_lien_resa_planning($row[3],$row[2]);
			$d[$day_num]["room"][] = $row[5] ;
			$d[$day_num]["res"][] = $row[6];
			$d[$day_num]["color"][] = $row[10];
			if ($row[9] > 0)
				$d[$day_num]["option_reser"][] = $row[8];
			else
				$d[$day_num]["option_reser"][] = -1;
			$d[$day_num]["moderation"][] = $row[11];
			$midnight_tonight = $midnight + 86400;
			if ($enable_periods == 'y')
			{
				$start_str = preg_replace("/ /", " ", period_time_string($row[0]));
				$end_str   = preg_replace("/ /", " ", period_time_string($row[1], -1));
				switch (cmp3($row[0], $midnight) . cmp3($row[1], $midnight_tonight))
				{
					case "> < ":
					case "= < ":
					if ($start_str == $end_str)
						$d[$day_num]["data"][] = $start_str;
					else
						$d[$day_num]["data"][] = $start_str . get_vocab("to") . $end_str;
					break;
					case "> = ":
					$d[$day_num]["data"][] = $start_str . get_vocab("to")."24:00";
					break;
					case "> > ":
					$d[$day_num]["data"][] = $start_str . get_vocab("to")."==>";
					break;
					case "= = ":
					$d[$day_num]["data"][] = $all_day;
					break;
					case "= > ":
					$d[$day_num]["data"][] = $all_day . "==>";
					break;
					case "< < ":
					$d[$day_num]["data"][] = "<==".get_vocab("to") . $end_str;
					break;
					case "< = ":
					$d[$day_num]["data"][] = "<==" . $all_day;
					break;
					case "< > ":
					$d[$day_num]["data"][] = "<==" . $all_day . "==>";
					break;
				}
			}
			else
			{
				switch (cmp3($row[0], $midnight) . cmp3($row[1], $midnight_tonight))
				{
					case "> < ":
					case "= < ":
					$d[$day_num]["data"][] = date(hour_min_format(), $row[0]) . get_vocab("to") . date(hour_min_format(), $row[1]);
					break;
					case "> = ":
					$d[$day_num]["data"][] = date(hour_min_format(), $row[0]) . get_vocab("to")."24:00";
					break;
					case "> > ":
					$d[$day_num]["data"][] = date(hour_min_format(), $row[0]) . get_vocab("to")."==>";
					break;
					case "= = ":
					$d[$day_num]["data"][] = $all_day;
					break;
					case "= > ":
					$d[$day_num]["data"][] = $all_day . "==>";
					break;
					case "< < ":
					$d[$day_num]["data"][] = "<==".get_vocab("to") . date(hour_min_format(), $row[1]);
					break;
					case "< = ":
					$d[$day_num]["data"][] = "<==" . $all_day;
					break;
					case "< > ":
					$d[$day_num]["data"][] = "<==" . $all_day . "==>";
					break;
				}
			}
			if ($row[1] <= $midnight_tonight)
				break;
			$day_num++;
			$t = $midnight = $midnight_tonight;
		}
	}
}
if ($debug_flag)
{
	echo '<p>DEBUG: Array of month day data:<p><pre>'.PHP_EOL;
	for ($i = 1; $i <= $days_in_month; $i++)
	{
		if (isset($d[$i]["id"]))
		{
			$n = count($d[$i]["id"]);
			echo 'Day '.$i.' has '.$n.' entries:'.PHP_EOL;
			for ($j = 0; $j < $n; $j++)
				echo "  ID: " . $d[$i]["id"][$j] .
			" Data: " . $d[$i]["data"][$j] . "\n";
		}
	}
	echo '</pre>'.PHP_EOL;
}
// affichage du contenu
if ($_GET['pview'] != 1){
    echo "<div id='planning2'>";
}
else{
	echo '<div id="print_planning">'.PHP_EOL;
}
echo "<table class='mois table-bordered table-striped'>";
// le titre de la table
echo "<caption>";
// liens mois avant-après et imprimante si page non imprimable
if ((!isset($_GET['pview'])) or ($_GET['pview'] != 1))
{
	echo "\n
	<div class='ligne23'>
		<div class=\"left\">
			<button class=\"btn btn-default btn-xs\" onclick=\"charger();javascript: location.href='month_all2.php?year=$yy&amp;month=$ym&amp;area=$area';\" \"><span class=\"glyphicon glyphicon-backward\"></span> ".get_vocab("monthbefore")." </button>
		</div>";
		include "./include/trailer.inc.php";
		echo "<div class=\"right\">
			<button class=\"btn btn-default btn-xs\" onclick=\"charger();javascript: location.href='month_all2.php?year=$ty&amp;month=$tm&amp;area=$area';\" \">".get_vocab('monthafter')." <span class=\"glyphicon glyphicon-forward\"></button>
		</div>
	</div>";
}
// montrer ou cacher le menu gauche
echo "<div>";
if ((!isset($_GET['pview'])) || ($_GET['pview'] != 1))
{
	echo "<div class=\"left\"> ";
    $mode = Settings::get("menu_gauche");
    if ($mode == 0) $mode = 1; // il faut bien que le menu puisse s'afficher, par défaut ce sera à gauche sauf choix autre par setting
    echo "<div id='voir'><button class=\"btn btn-default btn-sm\" onClick=\"afficheMenuGauche($mode)\" title='".get_vocab('show_left_menu')."'><span class=\"glyphicon glyphicon-chevron-right\"></span></button></div> ";
    echo "<div id='cacher'><button class=\"btn btn-default btn-sm\" onClick=\"afficheMenuGauche(0)\" title='".get_vocab('hide_left_menu')."'><span class=\"glyphicon glyphicon-chevron-left\"></span></button></div> "; 
	echo "</div>";
}    
    echo '<h4 class="titre"> '. ucfirst($this_area_name).' - '.get_vocab("all_areas").'<br>'.ucfirst(utf8_strftime("%B ", $month_start)).'<a href="year.php" title="'.get_vocab('see_all_the_rooms_for_several_months').'">'.ucfirst(utf8_strftime("%Y", $month_start)).'</a></h4>'.PHP_EOL;
    if ($_GET['pview'] != 1)
        echo " <a href=\"month_all.php?year=$year&amp;month=$month&amp;area=$area\"><span class='glyphicon glyphicon-refresh'></a>";
echo "</div>";
echo "</caption>";
if ($_GET['pview'] == 1 && (isset($_GET['precedent']) && $_GET['precedent'] == 1))
{
	echo "<span id=\"lienPrecedent\">
	<button class=\"btn btn-default btn-xs\" onclick=\"charger();javascript:history.back();\">Précedent</button>
</span>";
}
// le corps de la table 
$sql = "SELECT room_name, capacity, id, description, statut_room FROM ".TABLE_PREFIX."_room WHERE area_id=$area ORDER BY order_display,room_name";
$res = grr_sql_query($sql);
echo "<thead><tr>";
echo "<th class='cell_hours'>".get_vocab('rooms');
echo "</th>";
for ($k = 1; $k <= $days_in_month; $k++)
{
    $t2 = mktime(0, 0, 0, $month, $k, $year);
	$cday = date("j", $t2);
	$cweek = date("w", $t2);
	$name_day = ucfirst(utf8_strftime("%a %d", $t2));
	$temp = mktime(0, 0, 0, $month,$cday,$year);
	$jour_cycle = grr_sql_query1("SELECT Jours FROM ".TABLE_PREFIX."_calendrier_jours_cycle WHERE DAY='$temp'");
    if ($display_day[$cweek] == 1)
	{
        if (isHoliday($temp)) {echo tdcell("ferie cell_hours");}
        else if (isSchoolHoliday($temp)) {echo tdcell("cell_hours vacance");}
        else {echo tdcell("cell_hours");}
        echo $name_day;
        if (Settings::get("jours_cycles_actif") == "Oui" && intval($jour_cycle) > -1)
        {
            if (intval($jour_cycle) > 0)
                echo "<br /></><i> ".ucfirst(substr(get_vocab("rep_type_6"), 0, 1)).$jour_cycle."</i>";
            else
            {
                if (strlen($jour_cycle) > 5)
                    $jour_cycle = substr($jour_cycle, 0, 3)."..";
                echo "<br /></><i>".$jour_cycle."</i>";
            }
        }
        echo "</td>";
	}
}
echo "</tr></thead>";
echo "<tfoot><tr>";
echo tdcell("cell_hours");
echo "</td>";
for ($k = 1; $k <= $days_in_month; $k++)
{
    $t2 = mktime(0, 0, 0, $month, $k, $year);
	$cday = date("j", $t2);
	$cweek = date("w", $t2);
	$name_day = ucfirst(utf8_strftime("%a %d", $t2));
	$temp = mktime(0, 0, 0, $month,$cday,$year);
	$jour_cycle = grr_sql_query1("SELECT Jours FROM ".TABLE_PREFIX."_calendrier_jours_cycle WHERE DAY='$temp'");
    if ($display_day[$cweek] == 1)
	{
        if (isHoliday($temp)) {echo tdcell("ferie cell_hours");}
        else if (isSchoolHoliday($temp)) {echo tdcell("cell_hours vacance");}
        else {echo tdcell("cell_hours");}
        echo $name_day;
        if (Settings::get("jours_cycles_actif") == "Oui" && intval($jour_cycle) > -1)
        {
            if (intval($jour_cycle) > 0)
                echo "<br /></><i> ".ucfirst(substr(get_vocab("rep_type_6"), 0, 1)).$jour_cycle."</i>";
            else
            {
                if (strlen($jour_cycle) > 5)
                    $jour_cycle = substr($jour_cycle, 0, 3)."..";
                echo "<br /></><i>".$jour_cycle."</i>";
            }
        }
        echo "</td>";
	}
}
echo "</tr></tfoot>";

echo "<tbody>";
$li = 0;
for ($ir = 0; ($row = grr_sql_row($res, $ir)); $ir++) // traitement d'une ressource sur le mois
{
	$verif_acces_ressource = verif_acces_ressource(getUserName(), $row[2]);
	if ($verif_acces_ressource)
	{
		$acces_fiche_reservation = verif_acces_fiche_reservation(getUserName(), $row[2]);
		echo "<tr><th >" . htmlspecialchars($row[0]) ."</th>\n";
		$li++;
		for ($k = 1; $k <= $days_in_month; $k++)
		{
            $t2 = mktime(0, 0, 0,$month, $k, $year);
			$cday = date("j", $t2);
			$cweek = date("w", $t2);
			if ($display_day[$cweek] == 1)
			{
				echo "<td > ";
				if (est_hors_reservation(mktime(0, 0, 0, $month, $cday, $year), $area))
				{
					echo "<div class=\"empty_cell\">";
					echo "<img src=\"img_grr/stop.png\" alt=\"".get_vocab("reservation_impossible")."\"  title=\"".get_vocab("reservation_impossible")."\" width=\"16\" height=\"16\" class=\"".$class_image."\"  /></div>\n";
				}
				else
				{
					if (isset($d[$cday]["id"][0])) // il y a une réservation au moins à afficher
					{
                        echo "<table class='table-header table-bordered'>";
						$n = count($d[$cday]["id"]);
						for ($i = 0; $i < $n; $i++)
						{
							if ($i == 11 && $n > 12)
							{
								echo " ...\n";
								break;
							}
							for ($i = 0; $i < $n; $i++)
							{
								if ($d[$cday]["room"][$i] == $row[0])
								{
                                    echo "<tr>";
									tdcell($d[$cday]["color"][$i]);
                                    echo "<span class=\"small_planning\">";
									if ($d[$cday]["res"][$i] != '-')
										echo " <img src=\"img_grr/buzy.png\" alt=\"".get_vocab("ressource actuellement empruntee")."\" title=\"".get_vocab("ressource actuellement empruntee")."\" width=\"20\" height=\"20\" class=\"image\" /> \n";
									if ((isset($d[$cday]["option_reser"][$i])) && ($d[$cday]["option_reser"][$i] != -1))
										echo " <img src=\"img_grr/small_flag.png\" alt=\"".get_vocab("reservation_a_confirmer_au_plus_tard_le")."\" title=\"".get_vocab("reservation_a_confirmer_au_plus_tard_le")." ".time_date_string_jma($d[$cday]["option_reser"][$i],$dformat)."\" width=\"20\" height=\"20\" class=\"image\" /> \n";
									if ((isset($d[$cday]["moderation"][$i])) && ($d[$cday]["moderation"][$i] == 1))
										echo " <img src=\"img_grr/flag_moderation.png\" alt=\"".get_vocab("en_attente_moderation")."\" title=\"".get_vocab("en_attente_moderation")."\" class=\"image\" /> \n";
									
									if ($acces_fiche_reservation)
									{
										if (Settings::get("display_level_view_entry") == 0)
										{
											$currentPage = 'month_all2';
											$id =   $d[$cday]["id"][$i];
											echo "<a title=\"".htmlspecialchars($d[$cday]["data"][$i])."\" data-width=\"675\" onclick=\"request($id,$cday,$month,$year,'all','$currentPage',readData);\" data-rel=\"popup_name\" class=\"poplight\">" .substr($d[$cday]["who1"][$i],0,4)."</a>";
										}
										else
										{
											echo "<a class=\"lienCellule\" title=\"".htmlspecialchars($d[$cday]["data"][$i])."\" href=\"view_entry.php?id=" . $d[$cday]["id"][$i]."&amp;page=month_all2\">"
											.substr($d[$cday]["who1"][$i],0,4)
											. "</a>";
										}
									}
									else
										echo substr($d[$cday]["who1"][$i],0,4);
                                    echo "</span></td></tr>";
								}
							}
						}
                        echo '</table>';
                    }
                    // la ressource est-elle accessible en réservation ? on affiche le lien vers edit_entry
                    $date_booking = $t2 +86400 ; // le jour courant à minuit
                    $hour =  date("H",$date_now); // l'heure courante, par défaut
                    if ((($authGetUserLevel > 1) || (auth_visiteur(getUserName(), $row['2']) == 1)) && (UserRoomMaxBooking(getUserName(), $row['2'], 1) != 0) && verif_booking_date(getUserName(), -1, $row['2'], $date_booking, $date_now, $enable_periods) && verif_delais_max_resa_room(getUserName(), $row['2'], $date_booking) && verif_delais_min_resa_room(getUserName(), $row['2'], $date_booking) && plages_libre_semaine_ressource($row['2'], $month, $cday, $year) && (($row['4'] == "1") || (($row['4'] == "0") && (authGetUserLevel(getUserName(),$row['2']) > 2) )) && $_GET['pview'] != 1)
					{
						if ($enable_periods == 'y')
							echo '<a href="edit_entry.php?room=',$row["2"],'&amp;period=&amp;year=',$year,'&amp;month=',$month,'&amp;day=',$cday,'&amp;page=month_all2" title="',get_vocab("cliquez_pour_effectuer_une_reservation"),'"><span class="glyphicon glyphicon-plus"></span></a>',PHP_EOL;
						else
							echo '<a href="edit_entry.php?room=',$row["2"],'&amp;hour=',$hour,'&amp;minute=0&amp;year=',$year,'&amp;month=',$month,'&amp;day=',$cday,'&amp;page=month_all2" title="',get_vocab("cliquez_pour_effectuer_une_reservation"),'"><span class="glyphicon glyphicon-plus"></span></a>',PHP_EOL;;
					}
				}
				echo "</td>\n";
			}
		}
		echo "</tr>";
	}
}// fin  du traitement de la ressource
echo "</tbody>";
echo "</table>";
echo " </div>"; //fin du planning
echo  "<div id=\"popup_name\" class=\"popup_block\" ></div>";
if ($_GET['pview'] != 1)
{
	echo "<div id=\"toTop\"> ^ Haut de la page";
    bouton_retour_haut ();
    echo " </div>";
}

affiche_pop_up(get_vocab("message_records"),"user");
echo "</section>";
include "footer.php";
?>
