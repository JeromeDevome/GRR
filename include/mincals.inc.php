<?php
/**
 * mincals.inc.php
 * Fonctions permettant d'afficher le mini calendrier
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2021-04-20 11:40$
 * @author    JeromeB & Laurent Delineau & Yan Naessens
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


	class Calendar
	{
		private $month;
		private $year;
		private $day;
		private $h;
		private $area;
		private $room;
		private $dmy;
		private $week;
		private $mois_precedent;
		private $mois_suivant;

		/**
		 * @param string $day
		 * @param string $month
		 * @param string $year
		 * @param integer $h
		 * @param integer $mois_precedent
		 * @param integer $mois_suivant
		 */
		public function __construct($day, $month, $year, $h, $area, $room, $dmy, $mois_precedent, $mois_suivant)
		{
			$this->day   = $day;
			$this->month = $month;
			$this->year  = $year;
			$this->h     = $h;
			$this->area  = $area;
			$this->room  = $room;
			$this->dmy   = $dmy;
			$this->mois_precedent = $mois_precedent;
			$this->mois_suivant = $mois_suivant;
		}
		/**
		 * @param integer $day
		 * @param double $month
		 * @param string $year
		 */
		private function getDateLink($day, $month, $year)
		{
			global $vocab;
            if (isset($this->room))
                return "<a onclick=\"charger();\" class=\"cellcalendar\" title=\"".htmlspecialchars(get_vocab("see_day_for_this_room"))."\" href=\"day.php?year=$year&amp;month=$month&amp;day=$day&amp;room=".$this->room."\"";
            return "<a onclick=\"charger();\" class=\"cellcalendar\" title=\"".htmlspecialchars(get_vocab("see_all_the_rooms_for_the_day"))."\" href=\"day.php?year=$year&amp;month=$month&amp;day=$day&amp;area=".$this->area."\"";
        }
/* inutile de faire un test pour finalement faire la même chose YN le 07/03/2018
			if ($this->dmy == 'day')
			{
				if (isset($this->room))
					return "<a onclick=\"charger();\" class=\"calendar\" title=\"".htmlspecialchars(get_vocab("see_day_for_this_room"))."\" href=\"".$this->dmy.".php?year=$year&amp;month=$month&amp;day=$day&amp;room=".$this->room."\"";
				return "<a onclick=\"charger();\" class=\"calendar\" title=\"".htmlspecialchars(get_vocab("see_all_the_rooms_for_the_day"))."\" href=\"".$this->dmy.".php?year=$year&amp;month=$month&amp;day=$day&amp;area=".$this->area."\"";
			}
			if ($this->dmy != 'day')
			{
				if (isset($this->room))
					return "<a onclick=\"charger();\" class=\"calendar\" title=\"".htmlspecialchars(get_vocab("see_day_for_this_room"))."\" href=\"day.php?year=$year&amp;month=$month&amp;day=$day&amp;room=".$this->room."\"";
				return "<a onclick=\"charger();\" class=\"calendar\" title=\"".htmlspecialchars(get_vocab("see_all_the_rooms_for_the_day"))."\" href=\"day.php?year=$year&amp;month=$month&amp;day=$day&amp;area=".$this->area."\"";
			}
		} */

		/**
		 * @param integer $m
		 * @param integer $y
		 * @param string $month
		 * @param string $year
		 * @param string $text
		 * @param string $glyph
		 */
		private function createlink($m, $y, $month, $year, $dmy, $room, $area, $text, $glyph)
		{
			global $vocab, $type_month_all;
			$tmp = mktime(0, 0, 0, ($month) + $m, 1, ($year) + $y);
			$lastmonth = date("m", $tmp);
			$lastyear = date("Y", $tmp);
			if (($dmy != 'day') && ($dmy != 'week_all') && ($dmy != 'month_all') && ($dmy != 'month_all2'))
				return "<button type=\"button\" title=\"".htmlspecialchars(get_vocab($text))."\" class=\"btn btn-default btn-xs\" onclick=\"charger();javascript: location.href='month.php?year=$lastyear&amp;month=$lastmonth&amp;day=1&amp;area=$this->area&amp;room=$room';\"><span class=\"glyphicon glyphicon-$glyph\"></span></button>\n";
			else
				return "<button type=\"button\" title=\"".htmlspecialchars(get_vocab($text))."\" class=\"btn btn-default btn-xs\" onclick=\"charger();javascript: location.href='".$type_month_all.".php?year=$lastyear&amp;month=$lastmonth&amp;day=1&amp;area=$area';\"><span class=\"glyphicon glyphicon-$glyph\"></span></button>\n";
		}

		private function getNumber($weekstarts, $d, $daysInMonth)
		{
			global $display_day;
			$s = '';
			for ($i = 0; $i < 7; $i++)
			{
				$j = ($i + 7 + $weekstarts) % 7;
				if ($display_day[$j] == "1")
				{
					if (($this->dmy == 'day') && ($d == $this->day) && ($this->h))
						$s .= "<td class=\"week\">";
					else
						$s .= "<td>";
					if ($d > 0 && $d <= $daysInMonth)
					{
						$link = $this->getDateLink($d, $this->month, $this->year);
						if ($link == "")
							$s .= $d;
						elseif (($d == $this->day) && ($this->h))
							$s .= $link."><span class=\"cal_current_day\">$d</span></a>";
						else
							$s .= $link.">$d</a>";
					}
					else
						$s .= " ";
					$s .= "</td>\n";
				}
				$d++;
			}
			return array($d, $s);
		}

		/**
		 * @param integer $d
		 * @param integer $daysInMonth
		 * @param string $week_today
		 * @param string $week
		 * @param integer $temp
		 * @return string $s
		 */
		private function DayOfMonth($d, $daysInMonth, $week_today, $week, $temp)
		{
			global $weekstarts;
			$s = '';
			while ($d <= $daysInMonth)
			{
				$bg_lign = '';
				if (($week_today == $week) && ($this->h) && (($this->dmy == 'week_all') || ($this->dmy == 'week')))
					$bg_lign = " class=\"week\"";
				$s .= "<tr ".$bg_lign."><td class=\"calendarcol1\">";
				$t = "<a onclick=\"charger();\" class=\"cellcalendar\" title=\"".htmlspecialchars(get_vocab("see_week_for_this_area"))."\" href=\"week_all.php?year=$this->year&amp;month=$this->month&amp;day=$temp&amp;area=$this->area\">".sprintf("%02d",$week)."</a>";
				if (($this->dmy != 'day') && ($this->dmy != 'week_all') && ($this->dmy != 'month_all') && ($this->dmy != 'month_all2'))
					$t = "<a onclick=\"charger();\" class=\"cellcalendar\" title=\"".htmlspecialchars(get_vocab("see_week_for_this_room"))."\" href=\"week.php?year=$this->year&amp;month=$this->month&amp;day=$temp&amp;area=$this->area&amp;room=$this->room\">".sprintf("%02d",$week)."</a>";
				$s .= $t;
				$temp = $temp + 7;
				while ((!checkdate($this->month, $temp, $this->year)) && ($temp > 0))
					$temp--;
				$date = mktime(12, 0, 0, $this->month, $temp, $this->year);
				$week = $this->getWeekNumber($date);
				$s .= "</td>\n";
				$ret = $this->getNumber($weekstarts, $d, $daysInMonth);
				$d = $ret[0];
				$s .= $ret[1];
				$s .= "</tr>\n";
			}
			return $s;
		}

		private function GetAction()
		{
			$action = "day.php?year=".date('Y',time())."&amp;month=".date('m',time())."&amp;day=".date('d',time());
			if (isset($_GET['area']) && $_GET['area'] != null)
				$action .= "&amp;area=".$_GET['area'] ;
			if (isset($_GET['room']) && $_GET['room'] != null)
				$action .= "&amp;room=".$_GET['room'] ;
			if (isset($_GET['id_site']) && $_GET['id_site'] != null)
				$action .= "&amp;site=".$_GET['id_site'] ;
			return $action;
		}

		private function getDaysInMonth($month, $year)
		{
			return date('t', mktime(0, 0, 0, $month, 1, $year));
		}

		private function getFirstDays()
		{
			global $weekstarts, $display_day, $nb_display_day;
            if ($nb_display_day == 0){// aucun jour à afficher ? on force l'affichage
                for ($i=0;$i<7;$i++){
                    $display_day[$i] = 1;
                }
            }
			$basetime = mktime(12, 0, 0, 6, 11 + $weekstarts, 2000);
			for ($i = 0, $s = ""; $i < 7; $i++)
			{
				$j = ($i + 7 + $weekstarts) % 7;
				$show = $basetime + ($i * 24 * 60 * 60);
				$fl = ucfirst(utf8_strftime('%a',$show));
				if ($display_day[$j] == 1)
					//$s .= "<td class=\"calendarcol1\">$fl</td>\n";
                    $s .= "<th>$fl</th>\n";
				else
					$s .= "";
			}
			return $s;
		}

		private function getWeekNumber($date)
		{
			return date('W', $date);
		}

		public function getHTML()
		{
			global $weekstarts, $vocab, $type_month_all, $display_day, $nb_display_day;
			$date_today = mktime(12, 0, 0, $this->month, $this->day, $this->year);
			$week_today = $this->getWeekNumber($date_today);
			if (!isset($weekstarts))
				$weekstarts = 0;
			$s = "";
			$daysInMonth = $this->getDaysInMonth($this->month, $this->year);
			$date = mktime(12, 0, 0, $this->month, 1, $this->year);
			$first = (strftime("%w",$date) + 7 - $weekstarts) % 7;
			$monthName = ucfirst(utf8_strftime("%B", $date));
			if(Settings::get("menu_gauche") == 2){
				$s .= "\n<div class=\"col-lg-4 col-md-6 col-xs-12\">\n".PHP_EOL;
			} else{
				$s .= "\n<div class=\"col-xs-12\">\n".PHP_EOL;
			}
			$s .= "\n<table class=\"calendar\">\n";
			$s .= "<caption>";
			$week = $this->getWeekNumber($date);
			$weekd = $week;
			$s .= "<div class=\"btn-group\">";
			$s .= $this->createlink(0, -1, $this->month, $this->year, $this->dmy, $this->room, $this->area, "previous_year", "backward");
			$s .= $this->createlink(-1, 0, $this->month, $this->year, $this->dmy, $this->room, $this->area, "monthbefore", "chevron-left");
			if (($this->dmy != 'day') && ($this->dmy != 'week_all') && ($this->dmy != 'month_all') && ($this->dmy != 'month_all2'))
				$s .= "<button type=\"button\" title=\"".htmlspecialchars(get_vocab("see_month_for_this_room"))."\" class=\"btn btn-default btn-xs\" onclick=\"charger();javascript: location.href='month.php?year=$this->year&amp;month=$this->month&amp;day=1&amp;area=$this->area&amp;room=$this->room';\">$monthName $this->year</button>\n";
			else
				$s .= "<button type=\"button\" title=\"".htmlspecialchars(get_vocab("see_all_the_rooms_for_the_month"))."\" class=\"btn btn-default btn-xs\" onclick=\"charger();javascript: location.href='".$type_month_all.".php?year=$this->year&amp;month=$this->month&amp;day=1&amp;area=$this->area';\">$monthName $this->year</button>\n";
			$s .= $this->createlink(1, 0, $this->month, $this->year, $this->dmy, $this->room, $this->area, "monthafter", "chevron-right");
			$s .= $this->createlink(0, 1, $this->month, $this->year, $this->dmy, $this->room, $this->area, "following_year", "forward");
			$s .= "</div>";
			$action = $this->GetAction();
			$s .= "<br/><button type=\"button\" title=\"".htmlspecialchars(get_vocab("gototoday"))."\" class=\"btn btn-default btn-xs\" onclick=\"charger();javascript: location.href='".$action."';\">".get_vocab("gototoday")."</button>";
			$s .= "</caption>";
			$s .= "<thead><tr><td class=\"calendarcol1\">".get_vocab("semaine")."</td>\n";
			$s .= $this->getFirstDays();
			$s .= "</tr></thead>\n";
			$d = 1 - $first;
			$temp = 1;
			$s .= $this->DayOfMonth($d, $daysInMonth, $week_today, $week, $temp);
			if ($week - $weekd < 6)
				$s .= "";
			$s .= "</table>\n";
			$s .= "</div>\n";
			return $s;
		}
	}

function minicals($year, $month, $day, $area, $room, $dmy)
{
	global $display_day, $vocab;
	get_planning_area_values($area);
	$nb_calendar = Settings::get("nb_calendar");
	if ($nb_calendar >= 1)
	{
		$month_ = array();
		$milieu = ($nb_calendar % 2 == 1) ? ($nb_calendar + 1) / 2 : $nb_calendar / 2;
		for ($k = 1; $k < $milieu; $k++)
			$month_[] = mktime(0, 0, 0, $month + $k - $milieu, 1, $year);
		$month_[] = mktime(0, 0, 0, $month, $day, $year);
		for ($k = $milieu; $k < $nb_calendar; $k++)
			$month_[] = mktime(0, 0, 0, $month + $k - $milieu + 1, 1, $year);
		$ind = 1;
		foreach ($month_ as $key)
		{
			if ($ind == 1)
				$mois_precedent = 1;
			else
				$mois_precedent = 0;
			if ($ind == $nb_calendar)
				$mois_suivant = 1;
			else
				$mois_suivant = 0;
			if ($ind == $milieu)
				$flag_surlignage = 1;
			else
				$flag_surlignage = 0;
			$cal = new Calendar(date("d",$key), date("m",$key), date("Y",$key), $flag_surlignage, $area, $room, $dmy, $mois_precedent, $mois_suivant);
			echo $cal->getHTML();
			$ind++;
		}
	}
}
?>