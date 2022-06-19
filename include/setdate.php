<?php
/* 
 * setdate.php
 * Definis la date à afficher
 * Dernière modification : $Date: 2022-06-19 16:00$
 * @author    JeromeB & Laurent Delineau & Yan Naessens
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

$date_now = time();
if (!isset($day) || !isset($month) || !isset($year))
{
	if ($date_now < Settings::get("begin_bookings"))
		$date_ = Settings::get("begin_bookings");
	else if ($date_now > Settings::get("end_bookings"))
		$date_ = Settings::get("end_bookings");
	else
		$date_ = $date_now;

	$day   = date("d",$date_);
	$month = date("m",$date_);
	$year  = date("Y",$date_);
}
else
{
	settype($month, "integer");
	settype($day, "integer");
	settype($year, "integer");
	$minyear = date('Y', Settings::get("begin_bookings"));
	$maxyear = date('Y', Settings::get("end_bookings"));
	if ($day < 1)
		$day = 1;
	if ($day > 31)
		$day = 31;
	if ($month < 1)
		$month = 1;
	if ($month > 12)
		$month = 12;
	if ($year < $minyear)
		$year = $minyear;
	if ($year > $maxyear)
		$year = $maxyear;
	while (!checkdate($month, $day, $year))
		$day--;
}

?>
