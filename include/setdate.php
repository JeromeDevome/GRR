<?php
$date_now = time();
if (!isset($day) || !isset($month) || !isset($year)) {
	if ($date_now < Settings::get("begin_bookings")) {
		$date_ = Settings::get("begin_bookings");
	} elseif ($date_now > Settings::get("end_bookings")) {
		$date_ = Settings::get("end_bookings");
	} else {
		$date_ = $date_now;
	}
	$day   = date("d",$date_);
	$month = date("m",$date_);
	$year  = date("Y",$date_);
} else {
	settype($month, "integer");
	settype($day, "integer");
	settype($year, "integer");
	$minyear = strftime("%Y", Settings::get("begin_bookings"));
	$maxyear = strftime("%Y", Settings::get("end_bookings"));
	if ($day < 1) {
        $day = 1;
    }
	if ($day > 31) {
        $day = 31;
    }
	if ($month < 1) {
        $month = 1;
    }
	if ($month > 12) {
        $month = 12;
    }
	if ($year < $minyear) {
        $year = $minyear;
    }
	if ($year > $maxyear) {
        $year = $maxyear;
    }
	while (!checkdate($month, $day, $year)) {
        $day--;
    }
}

