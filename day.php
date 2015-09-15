<?php
/**
 * day.php
 * Permet l'affichage de la page d'accueil lorsque l'on est en mode d'affichage "jour".
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2009-12-02 20:11:07 $.
 *
 * @author    Laurent Delineau <laurent.delineau@ac-poitiers.fr>
 * @copyright Copyright 2003-2008 Laurent Delineau
 *
 * @link      http://www.gnu.org/licenses/licenses.html
 *
 * @version   $Id: day.php,v 1.20 2009-12-02 20:11:07 grr Exp $
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
include 'include/connect.inc.php';
include 'include/config.inc.php';
include 'include/misc.inc.php';
include 'include/functions.inc.php';
include "include/$dbsys.inc.php";
include 'include/mincals.inc.php';
include 'include/mrbs_sql.inc.php';
include 'include/twigInit.php';
$grr_script_name = 'day.php';
require_once './include/settings.class.php';
$settings = new Settings();
if (!$settings) {
    die('Erreur chargement settings');
}
require_once './include/session.inc.php';
include 'include/resume_session.php';
include 'include/language.inc.php';
include 'include/setdate.php';
/**
 * crée et peuple global $room, $area, $id_site;
 */
Definition_ressource_domaine_site();
/**
 * init du tableau qui contient les données a envoyer au template twig
 * $tplArray
 */
$tplArray = [];
/*$tplArray['area'] = $area;*/

$affiche_pview = '1';
if (!isset($_GET['pview'])) {
    $_GET['pview'] = 0;
    $class_image = 'image';
    $tplArray['pview'] = false;
} else {
    $_GET['pview'] = 1;
    $class_image = 'print_image';
    $tplArray['pview'] = true;
}
/*if ($_GET['pview'] == 1) {
    $class_image = 'print_image';
} else {
    $class_image = 'image';
}*/
$back = '';
if (isset($_SERVER['HTTP_REFERER'])) {
    $back = htmlspecialchars($_SERVER['HTTP_REFERER']);
}
if (($settings->get('authentification_obli') == 0) && (getUserName() == '')) {
    $type_session = 'no_session';
} else {
    $type_session = 'with_session';
}
get_planning_area_values($area);

print_header($day, $month, $year, $type_session, false);

if ($area <= 0) {
    /* todo refacto avec twig */
    echo '<h1>'.get_vocab('noareas').'</h1>';
    echo '<a href="./admin/admin_accueil.php">'.get_vocab('admin').'</a>'.PHP_EOL.'</body>'.PHP_EOL.'</html>';
    exit();
}
//print_header($day, $month, $year, $type_session);
if ((authGetUserLevel(getUserName(), -1) < 1) && ($settings->get('authentification_obli') == 1)) {
    showAccessDenied($back);
    exit();
}
if (authUserAccesArea(getUserName(), $area) == 0) {
    showAccessDenied($back);
    exit();
}
if (check_begin_end_bookings($day, $month, $year)) {
    showNoBookings($day, $month, $year, $back);
    exit();
}
if ($settings->get('verif_reservation_auto') == 0) {
    verify_confirm_reservation();
    verify_retard_reservation();
}
$ind = 1;
$test = 0;
$i = 0;
while (($test == 0) && ($ind <= 7)) {
    $i = mktime(0, 0, 0, $month, $day - $ind, $year);
    $test = $display_day[date('w', $i)];
    ++$ind;
}
$yy = date('Y', $i);
$ym = date('m', $i);
$yd = date('d', $i);
/*$tplArray['yy'] = $yy;
$tplArray['ym'] = $ym;
$tplArray['yd'] = $yd;*/
$i = mktime(0, 0, 0, $month, $day, $year);
$jour_cycle = grr_sql_query1('SELECT Jours FROM '.TABLE_PREFIX."_calendrier_jours_cycle WHERE day='$i'");
$ind = 1;
$test = 0;
while (($test == 0) && ($ind <= 7)) {
    $i = mktime(0, 0, 0, $month, $day + $ind, $year);
    $test = $display_day[date('w', $i)];
    ++$ind;
}
$ty = date('Y', $i);
$tm = date('m', $i);
$td = date('d', $i);
$am7 = mktime($morningstarts, 0, 0, $month, $day, $year);
$pm7 = mktime($eveningends, $eveningends_minutes, 0, $month, $day, $year);
$this_area_name = grr_sql_query1('SELECT area_name FROM '.TABLE_PREFIX."_area WHERE id='".protect_data_sql($area)."'");
$sql = 'SELECT '.TABLE_PREFIX.'_room.id, start_time, end_time, name, '.TABLE_PREFIX.'_entry.id, type, beneficiaire, statut_entry, '.TABLE_PREFIX.'_entry.description, '.TABLE_PREFIX.'_entry.option_reservation, '.TABLE_PREFIX.'_entry.moderate, beneficiaire_ext
FROM '.TABLE_PREFIX.'_entry, '.TABLE_PREFIX.'_room
WHERE '.TABLE_PREFIX.'_entry.room_id = '.TABLE_PREFIX."_room.id
AND area_id = '".protect_data_sql($area)."'
AND start_time < ".($pm7 + $resolution)." AND end_time > $am7 ORDER BY start_time";
$res = grr_sql_query($sql);
if (!$res) {
    echo grr_sql_error();
} else {
    for ($i = 0; ($row = grr_sql_row($res, $i)); ++$i) {
        $start_t = max(round_t_down($row['1'], $resolution, $am7), $am7);
        $end_t = min(round_t_up($row['2'], $resolution, $am7) - $resolution, $pm7);
        $cellules[$row['4']] = ($end_t - $start_t) / $resolution + 1;
        $compteur[$row['4']] = 0;
        for ($t = $start_t; $t <= $end_t; $t += $resolution) {
            $today[$row['0']][$t]['id'] = $row['4'];
            $today[$row['0']][$t]['color'] = $row['5'];
            $today[$row['0']][$t]['data'] = '';
            $today[$row['0']][$t]['who'] = '';
            $today[$row['0']][$t]['statut'] = $row['7'];
            $today[$row['0']][$t]['moderation'] = $row['10'];
            $today[$row['0']][$t]['option_reser'] = $row['9'];
            $today[$row['0']][$t]['description'] = affichage_resa_planning($row['8'], $row['4']);
        }
        if ($row['1'] < $am7) {
            $today[$row['0']][$am7]['data'] = affichage_lien_resa_planning($row['3'], $row['4']);
            if ($settings->get('display_info_bulle') == 1) {
                $today[$row['0']][$am7]['who'] = get_vocab('reservation au nom de').affiche_nom_prenom_email($row['6'], $row['11'], 'nomail');
            } elseif ($settings->get('display_info_bulle') == 2) {
                $today[$row['0']][$am7]['who'] = $row['8'];
            } else {
                $today[$row['0']][$am7]['who'] = '';
            }
        } else {
            $today[$row['0']][$start_t]['data'] = affichage_lien_resa_planning($row['3'], $row['4']);
            if ($settings->get('display_info_bulle') == 1) {
                $today[$row['0']][$start_t]['who'] = get_vocab('reservation au nom de').affiche_nom_prenom_email($row['6'], $row['11']);
            } elseif ($settings->get('display_info_bulle') == 2) {
                $today[$row['0']][$start_t]['who'] = $row['8'];
            } else {
                $today[$row['0']][$start_t]['who'] = '';
            }
        }
    }
}
grr_sql_free($res);
$sql = 'SELECT room_name, capacity, id, description, statut_room, show_fic_room, delais_option_reservation, moderate FROM '.TABLE_PREFIX."_room WHERE area_id='".protect_data_sql($area)."' ORDER BY order_display, room_name";
$res = grr_sql_query($sql);
if (!$res) {
    fatal_error(0, grr_sql_error());
}
if (grr_sql_count($res) == 0) {
    $tplArray['room'] = false;
    $tplArray['vocab']['no_room_for_area'] = get_vocab('no_rooms_for_area');
    //echo '<h1>'.get_vocab('no_rooms_for_area').'</h1>';
    grr_sql_free($res);
} else {
    $tplArray['room'] = true;
    //echo '<div class="row">'.PHP_EOL;
    /* menu_gauche fait un echo render du template menuGauche */
    include 'menu_gauche.php';
    /**
     * todo voir pour transformer ces includes en fonction ? Vérifier portée des var par rapport à l'include
     * menu gauche crée la var tplArrayMenuGauche
     */
     $tplArray['tplArrayMenuGauche'] = $tplArrayMenuGauche;


    /**
     * intégré direct au template twig fixme voir le fonctionnement de base et debugger
     * include 'chargement.php';
     */
    $ferie_true = 0;
    $class = '';
    $title = '';
    if ($settings->get('show_holidays') == 'Oui') {
        $ferie = getHolidays($year);
        /* init de ferier a false */
        $tplArray['ferie'] = false;
        $tt = mktime(0, 0, 0, $month, $day, $year);
        foreach ($ferie as $key => $value) {
            if ($tt == $value) {
                //$ferie_true = 1;
                $tplArray['ferie'] = true;
                break;
            }
        }
        $sh = getSchoolHolidays($tt, $year);
        if ($sh['0'] == true) {
            $tplArray['vacances'] = true;
            $tplArray['vacancesTitle'] = $sh['1'];
            //$class .= 'vacance ';
            //$title = ' '.$sh['1'];
        } else {
            $tplArray['vacances'] = false;
        }

    }

    if ((!isset($_GET['pview'])) || ($_GET['pview'] != 1)) {
        $tplArray['vocab']['daybefore'] = get_vocab('daybefore');
        $tplArray['vocab']['dayafter'] = get_vocab('dayafter');
        $tplArray['linkBefore'] = 'day.php?year='.$yy.'&month='.$ym.'&day='.$yd.'&area='.$area;
        $tplArray['linkAfter'] = 'day.php?year='.$ty.'&month='.$tm.'&day='.$td.'&area='.$area;

    }
    $tplArray['vocab']['all_areas'] = get_vocab('all_areas');
    $tplArray['thisAreaName'] = $this_area_name;
    //echo '<h4 class="titre">'.ucfirst($this_area_name).' - '.get_vocab('all_areas');

    if ($settings->get('jours_cycles_actif') == 'Oui' && intval($jour_cycle) > -1) {
        $tplArray['jourCycleInt'] = intval($jour_cycle);
        $tplArray['jourCycle'] = $jour_cycle;
        $tplArray['vocab']['rep_type_6'] = get_vocab('rep_type_6');

    } else {
        $tplArray['jourCycleInt'] = false;
    }
    $tplArray['time'] = utf8_strftime($dformat, $am7);
    /*echo '<br>'.ucfirst(utf8_strftime($dformat, $am7)).'</h4>'.PHP_EOL;
    echo '</div>'.PHP_EOL;*/
    if (isset($_GET['precedent'])) {
        if ($_GET['pview'] == 1 && $_GET['precedent'] == 1) {
            $tplArray['afficheLienPrecedant'] = true;
            //echo '<span id="lienPrecedent"><button class="btn btn-default btn-xs" onclick="charger();javascript:history.back();">Précedent</button></span>'.PHP_EOL;
        }
    } else {
        $tplArray['afficheLienPrecedant'] = false;
    }

    if ($enable_periods == 'y') {
        $tplArray['timeOuPeriod'] = get_vocab('period');
        $tplArray['period'] = true;

    } else {
        $tplArray['period'] = false;
        $tplArray['timeOuPeriod'] = get_vocab('time');

    }

    $room_column_width = (int) (90 / grr_sql_count($res));
    $nbcol = 0;
    $rooms = array();
    $a = 0;

    $roomVisibleForUser = 0;
    /* je remplis le tableau du vocab avant la boucle pour éviter les appels multiples à get_vocab dans le for */
    $tplArray['vocab']['number_max2'] = get_vocab('number_max2');
    $tplArray['vocab']['number_max'] = get_vocab('number_max');
    $tplArray['vocab']['week'] = get_vocab('week');
    $tplArray['vocab']['month'] = get_vocab('month');
    $tplArray['vocab']['fiche_ressource'] = get_vocab('fiche_ressource');
    $tplArray['vocab']['ressource_temporairement_indisponible'] = get_vocab('ressource_temporairement_indisponible');
    $tplArray['vocab']['reservations_moderees'] = get_vocab('reservations_moderees');
    $tplArray['vocab']['see_week_for_this_room'] = htmlspecialchars(get_vocab('see_week_for_this_room'));
    $tplArray['vocab']['see_month_for_this_room'] = htmlspecialchars(get_vocab('see_month_for_this_room'));


    for ($i = 0; ($row = grr_sql_row($res, $i)); ++$i) {
        $id_room[$i] = $row['2'];
        ++$nbcol;
        /**
         * une row = une room
         * row[0] = room_name
         * row[1] = capacity
         * row[2] = id
         * row[3] = description
         * row[4] = statut_room
         * row[5] = show_fic_room
         * row[6] = delais_option_reservation
         * row[7] = moderate
         */
        /* dans le for on tri les rooms pour ne garder que celles sur lesquelles l'utilisateur courant a les droits */
        if (verif_acces_ressource(getUserName(), $id_room[$i])) {
            /* informations depuis la bdd */
            $tplArray['rooms'][$roomVisibleForUser]['name'] = htmlspecialchars($row['0']);
            $tplArray['rooms'][$roomVisibleForUser]['capacity'] = $row['1'];
            $tplArray['rooms'][$roomVisibleForUser]['id'] = $row['2'];
            $tplArray['rooms'][$roomVisibleForUser]['description'] = htmlspecialchars($row['3']);
            $tplArray['rooms'][$roomVisibleForUser]['status'] = $row['4'];
            $tplArray['rooms'][$roomVisibleForUser]['showFic'] = $row['5'];
            $tplArray['rooms'][$roomVisibleForUser]['delaisOptionReservation'] = $row['6'];
            $tplArray['rooms'][$roomVisibleForUser]['moderate'] = $row['7'];
            /* informations supplémentaires */
            $tplArray['rooms'][$roomVisibleForUser]['accessFicheReservation'] = verif_acces_fiche_reservation(getUserName(), $tplArray['rooms'][$roomVisibleForUser]['id']);

            $room_name[$i] = $row['0'];
            $statut_room[$id_room[$i]] = $row['4'];
            $statut_moderate[$id_room[$i]] = $row['7'];
            $acces_fiche_reservation = verif_acces_fiche_reservation(getUserName(), $id_room[$i]);

            if (verif_display_fiche_ressource(getUserName(), $id_room[$i]) && $_GET['pview'] != 1) {
                $tplArray['rooms'][$roomVisibleForUser]['displayFicheRessourceOk'] = true;

            } else {
                $tplArray['rooms'][$roomVisibleForUser]['displayFicheRessourceOk'] = false;
            }
            if (authGetUserLevel(getUserName(), $id_room[$i]) > 2 && $_GET['pview'] != 1) {
                $tplArray['rooms'][$roomVisibleForUser]['userLevel'] = authGetUserLevel(getUserName(), $id_room[$i]);

            }
            $tplArray['rooms'][$roomVisibleForUser]['afficheRessourceEmprunte'] = affiche_ressource_empruntee($id_room[$i]);

            $tplArray['rooms'][$roomVisibleForUser]['linkToSeeWeek'] = 'week.php?year='.$year.'&month='.$month.'&cher='.$day.'&room='.$tplArray['rooms'][$roomVisibleForUser]['id'];
            $tplArray['rooms'][$roomVisibleForUser]['linkToSeeMonth'] = 'month.php?year='.$year.'&month='.$month.'&cher='.$day.'&room='.$tplArray['rooms'][$roomVisibleForUser]['id'];

            $rooms[] = $row['2'];
            $delais_option_reservation[$row['2']] = $row['6'];
            /* j'incrémente $roomVisibleForUser */
            $roomVisibleForUser++;
        }
    }

    if (count($rooms) == 0) {
        /* todo intégration twig */
        echo '<br /><h1>'.get_vocab('droits_insuffisants_pour_voir_ressources').'</h1><br />'.PHP_EOL;
        include 'include/trailer.inc.php';
        die();
    }

    $tab_ligne = 3;
    $indexArray = 0;
    /* todo refacto le for, pour éviter de faire $t = $am7 à tous les tour de boucle */

    /* get_vocab en dehors de la boucle */
    $tplArray['vocab']['reservation_impossible'] = get_vocab('reservation_impossible');
    $tplArray['vocab']['cliquez_pour_effectuer_une_reservation'] = get_vocab('cliquez_pour_effectuer_une_reservation');
    $tplArray['vocab']['ressource_actuellement_empruntee'] = get_vocab('ressource_actuellement_empruntee');
    $tplArray['vocab']['reservation_a_confirmer_au_plus_tard_le'] = get_vocab('reservation_a_confirmer_au_plus_tard_le');
    $tplArray['vocab']['en_attente_moderation'] = get_vocab('en_attente_moderation');
    $tplArray['vocab']['to'] = get_vocab('to');


    for ($t = $am7; $t <= $pm7; $t += $resolution) {

        if ($enable_periods == 'y') {
            $time_t = date('i', $t);
            $time_t_stripped = preg_replace('/^0/', '', $time_t);
            //echo $periods_name[$time_t_stripped].'</td>'.PHP_EOL;
            $tplArray['creneauxHoraire'][$indexArray]['periodeNameOrHeure'] = $periods_name[$time_t_stripped];
        } else {
            $tplArray['creneauxHoraire'][$indexArray]['periodeNameOrHeure'] = affiche_heure_creneau($t, $resolution);
            //echo affiche_heure_creneau($t, $resolution).'</td>'.PHP_EOL;
        }
        while (list($key, $room) = each($rooms)) {
            if (verif_acces_ressource(getUserName(), $room)) {
                if (isset($today[$room][$t]['id'])) {
                    $id = $today[$room][$t]['id'];
                    $color = $today[$room][$t]['color'];
                    $descr = $today[$room][$t]['data'];
                } else {
                    unset($id);
                }
                if ((isset($id)) && (!est_hors_reservation(mktime(0, 0, 0, $month, $day, $year), $area))) {
                    $tplArray['creneauxHoraire'][$indexArray]['color'] = getColor($color);
                    $tplArray['creneauxHoraire'][$indexArray]['class'] = false;
                    $c = $color;
                } elseif ($statut_room[$room] == '0') {
                    $tplArray['creneauxHoraire'][$indexArray]['color'] = false;
                    $tplArray['creneauxHoraire'][$indexArray]['class'] = 'avertissement';
                    $c = 'avertissement';
                } else {
                    $tplArray['creneauxHoraire'][$indexArray]['color'] = false;
                    $tplArray['creneauxHoraire'][$indexArray]['class'] = 'empty_cell';
                    $c = 'empty_cell';
                }
                if ((isset($id)) && (!est_hors_reservation(mktime(0, 0, 0, $month, $day, $year), $area))) {
                    if ($compteur[$id] == 0) {
                        if ($cellules[$id] != 1) {
                            if (isset($today[$room][$t + ($cellules[$id] - 1) * $resolution]['id'])) {
                                $id_derniere_ligne_du_bloc = $today[$room][$t + ($cellules[$id] - 1) * $resolution]['id'];
                                if ($id_derniere_ligne_du_bloc != $id) {
                                    $cellules[$id] = $cellules[$id] - 1;
                                    /* pas sur que ce soit necessaire, mais présent dans la fonction tdcell_rowspan, a vérifier */
                                    $step = $cellules[$id] - 1;
                                    if ( $step < 1 ) {
                                        $step = 1;
                                    }
                                    $tplArray['creneauxHoraire'][$indexArray]['notHorsReservationStep'] =  $step;
                                }
                            }
                        }
                        //tdcell_rowspan($c, $cellules[$id]);
                    }
                    $compteur[$id] = 1;
                } else {
                    $tplArray['creneauxHoraire'][$indexArray]['notHorsReservationStep'] = false;
                    //tdcell($c);
                }
                /* utilisation d'une var pour éviter deux appels à est_hors_reservation et éconnomiser 2 reqêtes sql */
                $estHorsResa = est_hors_reservation(mktime(0, 0, 0, $month, $day, $year), $area);
                if ( (!isset($id)) || $estHorsResa ) {

                    $hour = date('H', $t);
                    $minute = date('i', $t);
                    $date_booking = mktime($hour, $minute, 0, $month, $day, $year);
                    if ( $estHorsResa ) {
                        $tplArray['creneauxHoraire'][$indexArray]['EstHorsReservation'] = true;
                        //$tplArray['vocab']['reservation_impossible'] = get_vocab('reservation_impossible');
                        //echo '<img src="img_grr/stop.png" alt="'.get_vocab('reservation_impossible').'"  title="'.get_vocab('reservation_impossible').'" width="16" height="16" class="'.$class_image.'" />'.PHP_EOL;
                    } else {
                        if (((authGetUserLevel(getUserName(), -1) > 1) || (auth_visiteur(getUserName(), $room) == 1))
                            && (UserRoomMaxBooking(getUserName(), $room, 1) != 0) && verif_booking_date(getUserName(), -1, $room, $date_booking, $date_now, $enable_periods)
                            && verif_delais_max_resa_room(getUserName(), $room, $date_booking)
                            && verif_delais_min_resa_room(getUserName(), $room, $date_booking)
                            && (($statut_room[$room] == '1') || (($statut_room[$room] == '0')
                            && (authGetUserLevel(getUserName(), $room) > 2))) && $_GET['pview'] != 1) {

                            $tplArray['creneauxHoraire'][$indexArray]['EstHorsReservation'] = false;

                            if ($enable_periods == 'y') {
                                $tplArray['creneauxHoraire'][$indexArray]['period'] = true;
                                $tplArray['creneauxHoraire'][$indexArray]['linkToResa'] = 'edit_entry.php?room='.$room.'&period='.$time_t_stripped.'&year='.$year.'&month='.$month.'&day='.$day.'&page=day';
                                //echo '<a href="" title="'.get_vocab('cliquez_pour_effectuer_une_reservation').'" ><span class="glyphicon glyphicon-plus"></span></a>'.PHP_EOL;
                            } else {
                                $tplArray['creneauxHoraire'][$indexArray]['period'] = true;
                                $tplArray['creneauxHoraire'][$indexArray]['linkToResa'] = 'edit_entry.php?room='.$room.'&hour='.$hour.'&minute='.$minute.'&year='.$year.'&month='.$month.'&day='.$day.'&page=day';
                                //echo '<a href="edit_entry.php?room='.$room.'&amp;hour='.$hour.'&amp;minute='.$minute.'&amp;year='.$year.'&amp;month='.$month.'&amp;day='.$day.'&amp;page=day" title="'.get_vocab('cliquez_pour_effectuer_une_reservation').'" ><span class="glyphicon glyphicon-plus"></span></a>'.PHP_EOL;
                            }
                        } else {
                            //echo ' ';
                            $tplArray['creneauxHoraire'][$indexArray]['EstHorsReservation'] = 'empty';
                        }
                    }
                    //echo '</td>'.PHP_EOL;
                    $tplArray['creneauxHoraire'][$indexArray]['roomIdSet'] = false;
                } elseif ($descr != '') {
                    $tplArray['creneauxHoraire'][$indexArray]['roomIdSet'] = true;
                    $tplArray['creneauxHoraire'][$indexArray]['descRoomAccessRoomText'] = $descr;

                    if ((isset($today[$room][$t]['statut'])) && ($today[$room][$t]['statut'] != '-')) {
                        $tplArray['creneauxHoraire'][$indexArray]['descRoomEmpruntee'] = true;
                        //echo '<img src="img_grr/buzy.png" alt="'.get_vocab('ressource actuellement empruntee').'" title="'.get_vocab('ressource actuellement empruntee').'" width="20" height="20" class="image" />'.PHP_EOL;
                    } else {
                        $tplArray['creneauxHoraire'][$indexArray]['descRoomEmpruntee'] = false;
                    }
                    if (($delais_option_reservation[$room] > 0) && (isset($today[$room][$t]['option_reser'])) && ($today[$room][$t]['option_reser'] != -1)) {
                        $tplArray['creneauxHoraire'][$indexArray]['descRoomResaPlusTard'] = true;
                        /* todo gérer l'ffichage de la date avec twig */
                        $tplArray['creneauxHoraire'][$indexArray]['descRoomResaPlusTardDate'] = time_date_string_jma($today[$room][$t]['option_reser'], $dformat);

                        //echo '<img src="img_grr/small_flag.png" alt="'.get_vocab('reservation_a_confirmer_au_plus_tard_le').'" title="'.get_vocab('reservation_a_confirmer_au_plus_tard_le').' '.time_date_string_jma($today[$room][$t]['option_reser'], $dformat).'" width="20" height="20" class="image" />'.PHP_EOL;
                    } else {
                        $tplArray['creneauxHoraire'][$indexArray]['descRoomResaPlusTard'] = false;
                    }
                    if ((isset($today[$room][$t]['moderation'])) && ($today[$room][$t]['moderation'] == '1')) {
                        $tplArray['creneauxHoraire'][$indexArray]['descRoomResaModeration'] = true;
                        //echo '<img src="img_grr/flag_moderation.png" alt="'.get_vocab('en_attente_moderation').'" title="'.get_vocab('en_attente_moderation').'" class="image" />'.PHP_EOL;
                    } else {
                        $tplArray['creneauxHoraire'][$indexArray]['descRoomResaModeration'] = false;
                    }
                    if (($statut_room[$room] == '1') || (($statut_room[$room] == '0') && (authGetUserLevel(getUserName(), $room) > 2))) {
                        $tplArray['creneauxHoraire'][$indexArray]['descRoomAccessRoom'] = true;
                        if ($acces_fiche_reservation) {
                            $tplArray['creneauxHoraire'][$indexArray]['descRoomAccessRoomAndFicheResa'] = true;
                            $tplArray['creneauxHoraire'][$indexArray]['descRoomAccessRoomAndFicheResaWho'] = htmlspecialchars($today[$room][$t]['who']);
                            if ($settings->get('display_level_view_entry') == 0) {
                                $currentPage = 'day';
                                $tplArray['creneauxHoraire'][$indexArray]['descRoomAccessRoomAndFicheResaDisplay'] = 'levelViewEntry';
                                $tplArray['creneauxHoraire'][$indexArray]['descRoomAccessRoomAndFicheResaLink'] = 'request('.$id.','.$day.','.$month.','.$year."','".$currentPage."',readData);" ;
                                //echo '<a title="'.htmlspecialchars($today[$room][$t]['who']).'" data-width="675" onclick="request('.$id.','.$day.','.$month.','.$year.',\''.$currentPage.'\',readData);" data-rel="popup_name" class="poplight">'.$descr.PHP_EOL;
                            } else {
                                $tplArray['creneauxHoraire'][$indexArray]['descRoomAccessRoomAndFicheResaDisplay'] = false;
                                $tplArray['creneauxHoraire'][$indexArray]['descRoomAccessRoomAndFicheResaLink'] = 'view_entry.php?id='.$id.'&day='.$day.'&month='.$month.'&year='.$year.'&page=day';
                               // echo '<a class="lienCellule" title="',htmlspecialchars($today[$room][$t]['who']),'" href="view_entry.php?id=',$id,'&amp;day=',$day,'&amp;month=',$month,'&amp;year=',$year,'&amp;page=day\>',$descr;
                            }
                        } else {
                            $tplArray['creneauxHoraire'][$indexArray]['descRoomAccessRoomAndFicheResa'] = false;
                            //echo ' '.$descr;
                        }
                        $sql = 'SELECT type_name,start_time,end_time,clef,courrier FROM '.TABLE_PREFIX.'_type_area ,'.TABLE_PREFIX.'_entry  WHERE  '.TABLE_PREFIX.'_entry.id= '.$today[$room][$t]['id'].' AND '.TABLE_PREFIX.'_entry.type= '.TABLE_PREFIX.'_type_area.type_letter';
                        $res = grr_sql_query($sql);
                        for ($i = 0; ($row = grr_sql_row($res, $i)); ++$i) {
                            $type_name = $row['0'];
                            $start_time = $row['1'];
                            $end_time = $row['2'];
                            $clef = $row['3'];
                            $courrier = $row['4'];
                            $tplArray['creneauxHoraire'][$indexArray]['entries'][$i]['typeName'] = $row['0'];
                            $tplArray['creneauxHoraire'][$indexArray]['entries'][$i]['clef'] = $row['3'];
                            $tplArray['creneauxHoraire'][$indexArray]['entries'][$i]['courrier'] = $row['4'];

                            if ($enable_periods != 'y') {
                                $tplArray['creneauxHoraire'][$indexArray]['entries'][$i]['startTime'] = date('H:i',$row['1']);
                                $tplArray['creneauxHoraire'][$indexArray]['entries'][$i]['endTime'] = date('H:i',$row['2']);
                                //echo '<br/>',date('H:i', $start_time),get_vocab('to'),date('H:i', $end_time),'<br/>';
                            }
                            if (Settings::get('show_courrier') == 'y') {
                                $tplArray['creneauxHoraire'][$indexArray]['entries'][$i]['showCourrier'] = true;
                            } else {
                                $tplArray['creneauxHoraire'][$indexArray]['entries'][$i]['showCourrier'] = false;
                            }
                        }
                        if ($today[$room][$t]['description'] != '') {
                            $tplArray['creneauxHoraire'][$indexArray]['entries'][$i]['todayDesc'] = $today[$room][$t]['description'];
                            //echo '<br /><i>',$today[$room][$t]['description'],'</i>';
                        } else {
                            $tplArray['creneauxHoraire'][$indexArray]['entries'][$i]['todayDesc'] = false;
                        }
                    } else {
                        $tplArray['creneauxHoraire'][$indexArray]['descRoomAccessRoom'] = false;
                        //echo ' '.$descr;
                    }

                }
            }
        }
        reset($rooms);
        $indexArray++;
    }

}
grr_sql_free($res);
if ($_GET['pview'] != 1) {
    $tplArray['vocab']['top_of_page'] = get_vocab('top_of_page');
}

if (!(Settings::get('javascript_info_disabled'))) {
   if($msg = sessionDisplayMessage()) {
       if ( $msg === true ) {
           $tplArray['popupMessageRecords'] = get_vocab('message_records');
       } else {
           $tplArray['popupMessageRecords'] = $msg;
       }
   } $tplArray['popupMessageRecords'] = false;

} else {
    $tplArray['popupMessageRecords'] = false;
}
//affiche_pop_up(get_vocab('message_records'), 'user');

?>
<?php
/*<script type="text/javascript">
	$(document).ready(function(){
		$('table.table-bordered td').each(function(){
			var $row = $(this);
			var height = $row.height();
			var h2 = $row.find('a').height();
			$row.find('a').css('height', height);
			$row.find('a').css('padding-top', height/2 - h2/2);

		});
	});
	jQuery(document).ready(function($){
		$("#popup_name").draggable({containment: "#container"});
		$("#popup_name").resizable();
	});

</script>*/
?>
<?php
unset($row);

if ((!isset($_GET['pview'])) || ($_GET['pview'] != 1)) {

    include 'include/printAction.inc.php';
    if ( $tplArrayTrailer['affichePrintableViewNonGet'] !== false ) {
        $tplArray['printButton'] = $tplArrayTrailer;
    } else {
        $tplArray['printButton'] = false;
    }

}

echo $twig->render('day.html.twig', $tplArray);
?>

