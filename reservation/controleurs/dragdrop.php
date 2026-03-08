<?php
/**
 * dragdrop.php
 * Page appelée depuis le JS planning en ajax pour déplacer la réservation
 * et effectuer les vérifications nécessaires. Les seules informations envoyées
 * à editentreetrt.php sont celles qui changent (date, heure, ressource, id).
 * Ce script fait partie de l'application GRR.
 * Dernière modification : $Date: 2026-03-07 10:30$
 * @author    JeromeB
 * @copyright Since 2003 Team DEVOME - JeromeB
 * @link      http://www.gnu.org/licenses/licenses.html
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */

$grr_script_name = "dragdrop.php";

$trad = $vocab;

include "include/resume_session.php";
include "include/planning.php";

// récupérer les paramètres
$id         = isset($_REQUEST['id'])     ? intval($_REQUEST['id'])     : 0;
$newHour    = isset($_REQUEST['hour'])   ? intval($_REQUEST['hour'])   : -1;
$newMinute  = isset($_REQUEST['minute']) ? intval($_REQUEST['minute']) : -1;
$day        = isset($_REQUEST['day'])    ? intval($_REQUEST['day'])    : 0;
$month      = isset($_REQUEST['month'])  ? intval($_REQUEST['month'])  : 0;
$year       = isset($_REQUEST['year'])   ? intval($_REQUEST['year'])   : 0;
$ressource  = isset($_REQUEST['ressource']) ? intval($_REQUEST['ressource']) : 0;

if (!$id || !$day || !$month || !$year) {
    echo "Paramètres manquants";
    exit;
}

// charger l'entrée pour connaître la date/heure et la ressource d'origine
$sql = "SELECT start_time, end_time, room_id FROM " . TABLE_PREFIX . "_entry WHERE id=$id";
$res = grr_sql_query($sql);
if (!$res || !($row = grr_sql_row_keyed($res,0))) {
    echo "Réservation non trouvée";
    exit;
}
$start = (int)$row['start_time'];
$end   = (int)$row['end_time'];

// la ressource de l'entrée sert de valeur par défaut si aucune nouvelle ressource n'est passée en paramètre
$room_from_entry = intval($row['room_id']);

// contrôle permission écrire
$current_user = getUserName();
if (!getWritable($current_user, $id)) {
    echo "Permission refusée";
    exit;
}

// vérification déplacement hors période autorisée
if (est_hors_reservation(mktime(0, 0, 0, $month, $day, $year)))
{
    echo "Hors période de réservation";
    exit;
}

// déterminer ressource finalle
if ($ressource <> 0) {
    $room = $ressource;
} else {
    $room = $room_from_entry;
}

// Nouvelle date de début ou pas...
if($newHour != -1 && $newMinute != -1) {
    $hour   = intval($newHour);
    $minute = intval($newMinute);
} else {
    $hour   = intval(date('G', $start));
    $minute = intval(date('i', $start));
}
$second = intval(date('s', $start));

$new_start = mktime($hour, $minute, $second, $month, $day, $year);
$duration = $end - $start;
$new_end   = $new_start + $duration;

// plutôt que de réimplémenter les vérifications / la mise à jour ici est déléguée à editentreetrt.php
$_REQUEST['id']          = $id;
$_REQUEST['room']        = $room;
$_REQUEST['rooms']       = array($room);
$_REQUEST['start_day']   = $day;
$_REQUEST['start_month'] = $month;
$_REQUEST['start_year']  = $year;
$_REQUEST['end_day']     = $day;
$_REQUEST['end_month']   = $month;
$_REQUEST['end_year']    = $year;
$_REQUEST['start_hour']  = intval(date('G', $new_start));
$_REQUEST['start_minute']= intval(date('i', $new_start));
// lorsque des créneaux sont utilisés, l'indice de période est stocké dans le champ minute ;
// s'assurer que le formulaire reçoit toujours une valeur période pour que la validation passe
$_REQUEST['period']      = intval(date('i', $new_start));

$_REQUEST['end_hour']    = intval(date('G', $new_end));
$_REQUEST['end_minute']  = intval(date('i', $new_end));
// indicateur pour que editentreetrt puisse renvoyer une réponse concise
$_REQUEST['dragdrop']    = '1';

// copier aussi toutes les valeurs préparées dans POST, car
// editentreetrt.php utilise getFormVar qui lit uniquement $_GET/$_POST
foreach ($_REQUEST as $key => $val) {
    $_POST[$key] = $val;
}

// inclure le gestionnaire de réservation classique, qui affichera ok/conflit
include __DIR__ . '/editentreetrt.php';
exit;

?>