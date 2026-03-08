<?php
/**
 * move_resa.php
 * Ajax handler for dragging a reservation to a new day
 * Ce script fait partie de l'application GRR
 *
 * Parameters (GET or POST):
 *   id       : int, reservation id
 *   day      : int, destination day number
 *   month    : int, destination month
 *   year     : int, destination year
 *
 * The script checks that the user is allowed to modify the reservation
 * and then recomputes start_time/end_time keeping the same time of day and
 * duration.  Only the date is changed.  The response is simply "ok" or
 * an error message.
 */

$grr_script_name = "move_resa.php";

$trad = $vocab;

include "include/resume_session.php";
include "include/planning.php";

// fetch parameters
$id     = isset($_REQUEST['id'])     ? intval($_REQUEST['id'])     : 0;
$newHour   = isset($_REQUEST['hour'])   ? intval($_REQUEST['hour'])   : -1;
$newMinute = isset($_REQUEST['minute']) ? intval($_REQUEST['minute']) : -1;
$day    = isset($_REQUEST['day'])    ? intval($_REQUEST['day'])    : 0;
$month  = isset($_REQUEST['month'])  ? intval($_REQUEST['month'])  : 0;
$year   = isset($_REQUEST['year'])   ? intval($_REQUEST['year'])   : 0;
$ressource = isset($_REQUEST['ressource']) ? intval($_REQUEST['ressource']) : 0;

if (!$id || !$day || !$month || !$year) {
    echo "invalid parameters";
    exit;
}

// load existing entry
$sql = "SELECT start_time, end_time, room_id FROM " . TABLE_PREFIX . "_entry WHERE id=$id";
$res = grr_sql_query($sql);
if (!$res || !($row = grr_sql_row($res,0))) {
    echo "reservation not found";
    exit;
}

$start = (int)$row[0];
$end   = (int)$row[1];

if ($ressource <> 0) {
    $room = $ressource;
} else {
    $room  = (int)$row[2];
}

// permission check
$current_user = getUserName();
if (!getWritable($current_user, $id)) {
    echo "permission denied";
    exit;
}

if (est_hors_reservation(mktime(0, 0, 0, $month, $day, $year)))
{
    echo "out of reservation period";
    exit;
}

// build new start keeping time-of-day
if($newHour != -1 && $newMinute != -1) {
    $hour   = intval($newHour);
    $minute = intval($newMinute);
} else {
     // build new start keeping time-of-day
    $hour   = intval(date('G', $start));
    $minute = intval(date('i', $start));
}
$second = intval(date('s', $start));

$new_start = mktime($hour, $minute, $second, $month, $day, $year);
$duration = $end - $start;
$new_end   = $new_start + $duration;

// check for conflicts in the same room (excluding current entry)
$sql_conf = "SELECT id FROM " . TABLE_PREFIX . "_entry " .
            "WHERE room_id=$room AND id<>$id and supprimer=0 " .
            "AND NOT (end_time <= $new_start OR start_time >= $new_end)";
$res_conf = grr_sql_query($sql_conf);
// if we get at least one row, there is a conflict
if ($res_conf) {
    $row_conf = grr_sql_count($res_conf, 0);
    if ($row_conf > 0) {
        echo "conflict";
        exit;
    }
}

// update entry
$sql2 = "UPDATE " . TABLE_PREFIX . "_entry SET start_time=$new_start, end_time=$new_end, room_id=$room WHERE id=$id";
if (grr_sql_query($sql2)) {
    echo "ok";
} else {
    echo grr_sql_error();
}

?>