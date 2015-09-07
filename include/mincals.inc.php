<?php

/**
 * mincals.inc.php
 * Fonctions permettant d'afficher le mini calendrier
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2010-01-06 10:21:20 $.
 *
 * @author    Laurent Delineau <laurent.delineau@ac-poitiers.fr>
 * @copyright Copyright 2003-2008 Laurent Delineau
 *
 * @link      http://www.gnu.org/licenses/licenses.html
 *
 * @version   $Id: mincals.inc.php,v 1.7 2010-01-06 10:21:20 grr Exp $
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

/* todo nicolas : cleanup */
function minicals($year, $month, $day, $area, $room, $dmy)
{
    global $display_day, $vocab;
    get_planning_area_values($area);
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
         * @param int    $h
         * @param int    $mois_precedent
         * @param int    $mois_suivant
         */
        public function Calendar($day, $month, $year, $h, $area, $room, $dmy, $mois_precedent, $mois_suivant)
        {
            $this->day = $day;
            $this->month = $month;
            $this->year = $year;
            $this->h = $h;
            $this->area = $area;
            $this->room = $room;
            $this->dmy = $dmy;
            $this->mois_precedent = $mois_precedent;
            $this->mois_suivant = $mois_suivant;
        }
        /**
         * @param int    $day
         * @param float  $month
         * @param string $year
         */
        private function getDateLink($day, $month, $year)
        {
            global $vocab;
            $tplArray['vocab']['see_all_the_rooms_for_the_day'] = htmlspecialchars(get_vocab('see_all_the_rooms_for_the_day'));

            if ($this->dmy == 'day') {
                if (isset($this->room)) {
                    //return '<a onclick="charger();" class="calendar" title="'.htmlspecialchars(get_vocab('see_all_the_rooms_for_the_day')).'" href="'.$this->dmy.".php?year=$year&amp;month=$month&amp;day=$day&amp;room=".$this->room.'"';

                    $tplArray['dateLink'] = $this->dmy.'.php?year='.$year.'&month='.$month.'&day='.$day.'&room='.$this->room;
                    return $tplArray['dateLink'];
                }

                $tplArray['dateLink'] = $this->dmy.'.php?year='.$year.'&month='.$month.'&day='.$day.'&area='.$this->area;
                //return '<a onclick="charger();" class="calendar" title="'.htmlspecialchars(get_vocab('see_all_the_rooms_for_the_day')).'" href="'.$this->dmy.".php?year=$year&amp;month=$month&amp;day=$day&amp;area=".$this->area.'"';
                return $tplArray['dateLink'];
            }
            if ($this->dmy != 'day') {
                if (isset($this->room)) {
                    //return '<a onclick="charger();" class="calendar" title="'.htmlspecialchars(get_vocab('see_all_the_rooms_for_the_day'))."\" href=\"day.php?year=$year&amp;month=$month&amp;day=$day&amp;room=".$this->room.'"';
                    $tplArray['dateLink'] = 'day.php?year='.$year.'&month='.$month.'&day='.$day.'&room='.$this->room;
                    return $tplArray['dateLink'];
                }

                //return '<a onclick="charger();" class="calendar" title="'.htmlspecialchars(get_vocab('see_all_the_rooms_for_the_day'))."\" href=\"day.php?year=$year&amp;month=$month&amp;day=$day&amp;area=".$this->area.'"';
                $tplArray['dateLink'] = 'day.php?year='.$year.'&month='.$month.'&day='.$day.'&area='.$this->area;
                return $tplArray['dateLink'];
            }
        }

        /**
         * @param int    $m
         * @param int    $y
         * @param string $month
         * @param string $year
         * @param string $text
         * @param string $glyph
         */
        private function createlink($m, $y, $month, $year, $dmy, $room, $area, $text, $glyph)
        {
            global $vocab, $type_month_all;
            $tplArray['vocab'][$text] = htmlspecialchars(get_vocab($text));
            $tmp = mktime(0, 0, 0, ($month) + $m, 1, ($year) + $y);
            $lastmonth = date('m', $tmp);
            $lastyear = date('Y', $tmp);
            if (($dmy != 'day') && ($dmy != 'week_all') && ($dmy != 'month_all') && ($dmy != 'month_all2')) {

                $tplArray['link'] = 'month.php?year='.$lastyear.'&month='.$lastmonth.'&day=1&area='.$this->area.'&room='.$room;
                //return '<button type="button" title="'.htmlspecialchars(get_vocab($text))."\" class=\"btn btn-default btn-xs\" onclick=\"charger();javascript: location.href='month.php?year=$lastyear&amp;month=$lastmonth&amp;day=1&amp;area=$this->area&amp;room=$room';\"><span class=\"glyphicon glyphicon-$glyph\"></span></button>\n";
                return $tplArray['link'];
            } else {
                $tplArray['link'] = $type_month_all.'.php?year='.$lastyear.'&month='.$lastmonth.'&day=1&area='.$area;
                //return '<button type="button" title="'.htmlspecialchars(get_vocab($text))."\" class=\"btn btn-default btn-xs\" onclick=\"charger();javascript: location.href='".$type_month_all.".php?year=$lastyear&amp;month=$lastmonth&amp;day=1&amp;area=$area';\"><span class=\"glyphicon glyphicon-$glyph\"></span></button>\n";
                return $tplArray['link'];
            }
        }

        private function getNumber($weekstarts, $d, $daysInMonth)
        {
            global $display_day;
            $tplArray = [];
            $s = '';
            for ($i = 0; $i < 7; $i++) {
                $j = ($i + 7 + $weekstarts) % 7;
                if ($display_day[$j] == '1') {
                    if (($this->dmy == 'day') && ($d == $this->day) && ($this->h)) {
                        $tplArray['numbers'][$d][$i]['week'] = true;
                        //$s .= '<td class="week">';
                    } else {
                        $tplArray['numbers'][$d][$i]['week'] = false;
                        //$s .= '<td class="cellcalendar">';
                    }
                    if ($d > 0 && $d <= $daysInMonth) {
                        $link = $this->getDateLink($d, $this->month, $this->year);
                        $tplArray['numbers'][$d][$i]['link'] = $link;
                        if ($link == '') {
                            $s .= $d;
                            $tplArray['numbers'][$d][$i]['current'] = false;
                        } elseif (($d == $this->day) && ($this->h)) {
                            $tplArray['numbers'][$d][$i]['current'] = true;
                            //$s .= $link."><span class=\"cal_current_day\">$d</span></a>";
                        } else {
                            //$s .= $link.">$d</a>";
                            $tplArray['numbers'][$d][$i]['current'] = false;
                        }
                    } else {
                        //$s .= ' ';
                        $tplArray['numbers'][$d][$i]['link'] = false;
                    }
                    //$s .= "</td>\n";
                }
                $d++;
            }

            //return array($d, $s);
            $retour['data']['day'] = $d;
            $retour['data']['numbers'] = $tplArray;
            /* tableau de jour dans la semaine avec lien */
            return $retour;
        }

        /**
         * @param int    $d
         * @param int    $daysInMonth
         * @param string $week_today
         * @param string $week
         * @param int    $temp
         *
         * @return string $s
         */
        private function DayOfMonth($d, $daysInMonth, $week_today, $week, $temp)
        {
            global $weekstarts;
            $tplArray = [];
            $s = '';
            $i = 0;

            while ($d <= $daysInMonth) {

                //$bg_lign = '';
                if (($week_today == $week) && ($this->h) && (($this->dmy == 'week_all') || ($this->dmy == 'week'))) {
                    $tplArray['dayInMonth'][$i]['week'] = true;
                    //$bg_lign = ' class="week"';
                } else {
                    $tplArray['dayInMonth'][$i]['week'] = false;
                }
                //$s .= '<tr '.$bg_lign.'><td class="calendarcol1 lienSemaine">';
                $tplArray['dayInMonth'][$i]['linkToWeekArea'] = 'week_all.php?year='.$this->year.'&month='.$this->month.'&day='.$temp.'&area='.$this->area;
                $tplArray['dayInMonth'][$i]['textToWeekArea'] = sprintf('%02d', $week);
                //$t = '<a onclick="charger();" title="'.htmlspecialchars(get_vocab('see_week_for_this_area'))."\" href=\"week_all.php?year=$this->year&amp;month=$this->month&amp;day=$temp&amp;area=$this->area\">".sprintf('%02d', $week).'</a>';
                if (($this->dmy != 'day') && ($this->dmy != 'week_all') && ($this->dmy != 'month_all') && ($this->dmy != 'month_all2')) {

                    $tplArray['dayInMonth'][$i]['linkToWeekRoom'] = 'week.php?year='.$this->year.'&month='.$this->month.'&day='.$temp.'&area='.$this->area.'&room='.$this->room;
                    $tplArray['dayInMonth'][$i]['textToWeekRoom'] = sprintf('%02d', $week);
                    //$t = '<a onclick="charger();" title="'.htmlspecialchars(get_vocab('see_week_for_this_room'))."\" href=\"week.php?year=$this->year&amp;month=$this->month&amp;day=$temp&amp;area=$this->area&amp;room=$this->room\">".sprintf('%02d', $week).'</a>';

                }
                //$s .= $t;
                $temp = $temp + 7;
                while ((!checkdate($this->month, $temp, $this->year)) && ($temp > 0)) {
                    $temp--;
                }
                $date = mktime(12, 0, 0, $this->month, $temp, $this->year);
                $week = $this->getWeekNumber($date);
                //$s .= "</td>\n";

                $return = $this->getNumber($weekstarts, $d, $daysInMonth);
                $tplArray['dayInMonth'][$i]['numbers'] = $return['data']['numbers'];
                /*var_dump($tplArray);*/
                $d = $return['data']['day'];
                //$s .= $ret[1];
                //$s .= "</tr>\n";
                ++$i;
                /*if ($i == 50) {
                    break;
                }*/
            }

            return $tplArray;
        }

        private function GetAction()
        {
            $action = 'day.php?year='.date('Y', time()).'&amp;month='.date('m', time()).'&amp;day='.date('d', time());
            if (isset($_GET['area']) && $_GET['area'] != null) {
                $action .= '&amp;area='.$_GET['area'];
            }
            if (isset($_GET['room']) && $_GET['room'] != null) {
                $action .= '&amp;room='.$_GET['room'];
            }
            if (isset($_GET['id_site']) && $_GET['id_site'] != null) {
                $action .= '&amp;site='.$_GET['id_site'];
            }

            return $action;
        }

        private function getDaysInMonth($month, $year)
        {
            return date('t', mktime(0, 0, 0, $month, 1, $year));
        }

        private function getFirstDays()
        {
            global $weekstarts, $display_day;
            $basetime = mktime(12, 0, 0, 6, 11 + $weekstarts, 2000);
            $s = '';
            for ($i = 0; $i < 7; ++$i) {
                $j = ($i + 7 + $weekstarts) % 7;
                $show = $basetime + ($i * 24 * 60 * 60);
                $fl = ucfirst(utf8_strftime('%a', $show));
                if ($display_day[$j] == 1) {
                    $tplArray['firstDays'][$i] = $fl;
                    $s .= "<td class=\"calendarcol1\">$fl</td>\n";
                } else {
                    $s .= '';
                    $tplArray['firstDays'][$i] = false;
                }
            }

            return $tplArray;
        }

        private function getWeekNumber($date)
        {
            return date('W', $date);
        }

        public function getHTML()
        {
            global $weekstarts, $vocab, $type_month_all, $display_day, $nb_display_day;
            $tplArray = [];
            $date_today = mktime(12, 0, 0, $this->month, $this->day, $this->year);
            $week_today = $this->getWeekNumber($date_today);
            if (!isset($weekstarts)) {
                $weekstarts = 0;
            }
            //$s = '';
            $daysInMonth = $this->getDaysInMonth($this->month, $this->year);
            $date = mktime(12, 0, 0, $this->month, 1, $this->year);
            $first = (strftime('%w', $date) + 7 - $weekstarts) % 7;
            $monthName = ucfirst(utf8_strftime('%B', $date));
            //$s .= "\n<table class=\"calendar\">\n";
            //$s .= '<caption>';
            $week = $this->getWeekNumber($date);
            //$weekd = $week;
            //$s .= '<div class="btn-group">';

            //$s .= $this->createlink(0, -1, $this->month, $this->year, $this->dmy, $this->room, $this->area, 'previous_year', 'backward');
            $tplArray['previousYear'] = $this->createlink(0, -1, $this->month, $this->year, $this->dmy, $this->room, $this->area, 'previous_year', 'backward');

            //$s .= $this->createlink(-1, 0, $this->month, $this->year, $this->dmy, $this->room, $this->area, 'see_month_for_this_room', 'chevron-left');
            $tplArray['monthForThisRoomLeft'] = $this->createlink(-1, 0, $this->month, $this->year, $this->dmy, $this->room, $this->area, 'see_month_for_this_room', 'chevron-left');

            $tplArray['TextAllRoomsForTheMonth'] = $monthName . ' ' . $this->year;
            if (($this->dmy != 'day') && ($this->dmy != 'week_all') && ($this->dmy != 'month_all') && ($this->dmy != 'month_all2')) {
                $tplArray['linkAllRoomsForTheMonth'] = 'month.php?year='.$this->year.'&month='.$this->month.'&day=1&area='.$this->area.'&room='.$this->room;

                //$s .= '<button type="button" title="'.htmlspecialchars(get_vocab('see_all_the_rooms_for_the_month'))."\" class=\"btn btn-default btn-xs\" onclick=\"charger();javascript: location.href='month.php?year=$this->year&amp;month=$this->month&amp;day=1&amp;area=$this->area&amp;room=$this->room';\">$monthName $this->year</button>\n";
            } else {
                //$s .= '<button type="button" title="'.htmlspecialchars(get_vocab('see_all_the_rooms_for_the_month'))."\" class=\"btn btn-default btn-xs\" onclick=\"charger();javascript: location.href='".$type_month_all.".php?year=$this->year&amp;month=$this->month&amp;day=1&amp;area=$this->area';\">$monthName $this->year</button>\n";
                $tplArray['linkAllRoomsForTheMonth'] = $type_month_all.'.php?year='.$this->year.'&month='.$this->month.'&day=1&area='.$this->area;
            }
            //$s .= $this->createlink(1, 0, $this->month, $this->year, $this->dmy, $this->room, $this->area, 'see_month_for_this_room', 'chevron-right');
            $tplArray['monthForThisRoomRight'] = $this->createlink(1, 0, $this->month, $this->year, $this->dmy, $this->room, $this->area, 'see_month_for_this_room', 'chevron-right');

            //$s .= $this->createlink(0, 1, $this->month, $this->year, $this->dmy, $this->room, $this->area, 'following_year', 'forward');
            $tplArray['nextYear'] = $this->createlink(0, 1, $this->month, $this->year, $this->dmy, $this->room, $this->area, 'following_year', 'forward');

            //$s .= '</div>';
            $tplArray['action'] = $this->GetAction();

            //$s .= '<br/><button type="button" title="'.htmlspecialchars(get_vocab('gototoday'))."\" class=\"btn btn-default btn-xs\" onclick=\"charger();javascript: location.href='".$action."';\">".get_vocab('gototoday').'</button>';
            //$s .= '</caption>';
            //$s .= '<tr><td class="calendarcol1">'.get_vocab('semaine')."</td>\n";
            //$s .= $this->getFirstDays();
            /*echo "avant dayOfMonth<br>";
            var_dump($tplArray);*/

            $tplArray = $this->getFirstDays();
            //$s .= "</tr>\n";
            $d = 1 - $first;
            $temp = 1;
            $tplArray['daysOfTheMonth'] = $this->DayOfMonth($d, $daysInMonth, $week_today, $week, $temp);
            //$s .= $this->DayOfMonth($d, $daysInMonth, $week_today, $week, $temp);
            /*if ($week - $weekd < 6) {
                $s .= '';
            }
            $s .= "</table>\n";*/

            return $tplArray;
        }
    }

    $nb_calendar = Settings::get('nb_calendar');
    if ($nb_calendar >= 1) {
        $month_ = array();
        $milieu = ($nb_calendar % 2 == 1) ? ($nb_calendar + 1) / 2 : $nb_calendar / 2;
        for ($k = 1; $k < $milieu; ++$k) {
            $month_[] = mktime(0, 0, 0, $month + $k - $milieu, 1, $year);
        }
        $month_[] = mktime(0, 0, 0, $month, $day, $year);
        for ($k = $milieu; $k < $nb_calendar; ++$k) {
            $month_[] = mktime(0, 0, 0, $month + $k - $milieu + 1, 1, $year);
        }
        $ind = 1;

        foreach ($month_ as $key) {
            if ($ind == 1) {
                $mois_precedent = 1;
            } else {
                $mois_precedent = 0;
            }
            if ($ind == $nb_calendar) {
                $mois_suivant = 1;
            } else {
                $mois_suivant = 0;
            }
            if ($ind == $milieu) {
                $flag_surlignage = 1;
            } else {
                $flag_surlignage = 0;
            }
            $cal = new Calendar(date('d', $key), date('m', $key), date('Y', $key), $flag_surlignage, $area, $room, $dmy, $mois_precedent, $mois_suivant);

            $tplArray['month'][$ind] = $cal->getHTML();
            ++$ind;
        }

        return $tplArray;
    }
}
