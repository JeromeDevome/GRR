<?php
/**
 * annee.php
 * Interface d'accueil avec affichage par mois sur plusieurs mois des réservation de toutes les ressources d'un domaine
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2024-04-14 17:20$
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

 class Annee {

    public static function Reservations($month_end, $month_start,$area){
        global $enable_periods;

        $da = array();
        //Used below: localized "all day" text but with non-breaking spaces:
        $all_day = preg_replace("/ /", " ", get_vocab("all_day"));
        // un type à exclure ?
        $type_exclu = Settings::get('exclude_type_in_views_all'); // nom du type exclu
        $sql = "SELECT type_letter FROM ".TABLE_PREFIX."_type_area WHERE ".TABLE_PREFIX."_type_area.type_name = '".$type_exclu."' ";
        $res = grr_sql_query($sql);
        $row = grr_sql_row($res,'0');
        $typeExclu = (isset($row[0]))? $row[0]:NULL; // lettre identifiant le type exclu
        grr_sql_free($res);
        //Get all meetings for these months in the area that we care about
        $sql = "SELECT start_time, end_time, ".TABLE_PREFIX."_entry.id, name, beneficiaire, ".TABLE_PREFIX."_room.room_name,type, statut_entry, ".TABLE_PREFIX."_entry.description, ".TABLE_PREFIX."_entry.option_reservation, ".TABLE_PREFIX."_room.delais_option_reservation, ".TABLE_PREFIX."_entry.moderate, beneficiaire_ext, clef, ".TABLE_PREFIX."_entry.courrier, ".TABLE_PREFIX."_type_area.type_name, ".TABLE_PREFIX."_entry.overload_desc, ".TABLE_PREFIX."_entry.room_id, nbparticipantmax, ".TABLE_PREFIX."_room.confidentiel_resa
        FROM (".TABLE_PREFIX."_entry INNER JOIN ".TABLE_PREFIX."_room ON ".TABLE_PREFIX."_entry.room_id=".TABLE_PREFIX."_room.id ) 
          INNER JOIN ".TABLE_PREFIX."_type_area ON ".TABLE_PREFIX."_entry.type=".TABLE_PREFIX."_type_area.type_letter
        WHERE (start_time <= $month_end AND end_time > $month_start AND area_id='".$area."' AND supprimer = 0)
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
            $row[18]: nbparticipantmax
            $row[19]: confidentiel_resa
        */
        //Build an array of information about each day in the month.
        //The information is stored as:
        // d[monthday]["id"][] = ID of each entry, for linking.
        // d[monthday]["data"][] = "start-stop" times of each entry.
        $res = grr_sql_query($sql);
        if (!$res)
            echo grr_sql_error();
        else
        {
            $overloadFieldList = mrbsOverloadGetFieldslist($area);
            for ($i = 0; ($row = grr_sql_row_keyed($res, $i)); $i++)
            {
                if ($row["type_name"] <> (Settings::get('exclude_type_in_views_all')))   // Nom du type exclu
                {
                    //Fill in data for each day during the month that this meeting covers.
                    //Note: int casts on database rows for min and max is needed for PHP3.
                    $t = max((int)$row["start_time"], $month_start);
                    $end_t = min((int)$row["end_time"], $month_end);
                    $day_num = date("j", $t);
                    $month_num = date("m", $t);
                    $year_num  = date("Y", $t);
                    if ($enable_periods == 'y')
                        $midnight = mktime(12,0,0,$month_num,$day_num,$year_num);
                    else
                        $midnight = mktime(0, 0, 0, $month_num, $day_num, $year_num);
                    while ($t < $end_t)
                    {
                        $da[$day_num][$month_num][$year_num]["id"][] = $row["id"];
                        // Info-bulle
                        $da[$day_num][$month_num][$year_num]["lien"][] = lien_compact($row);
                        $da[$day_num][$month_num][$year_num]["room"][]=$row[5] ;
                        $da[$day_num][$month_num][$year_num]["color"][] = $row["type"];
                        $da[$day_num][$month_num][$year_num]["beneficiaire"][] = $row[4];
                        $da[$day_num][$month_num][$year_num]["confidentiel_resa"][] = $row[19];
                        $midnight_tonight = $midnight + 86400;
                        //Describe the start and end time, accounting for "all day"
                        //and for entries starting before/ending after today.
                        //There are 9 cases, for start time < = or > midnight this morning,
                        //and end time < = or > midnight tonight.
                        //Use ~ (not -) to separate the start and stop times, because MSIE
                        //will incorrectly line break after a -.
                        $all_day2 = preg_replace("/ /", " ", $all_day);
                        if ($enable_periods == 'y')
                        {
                            $start_str = preg_replace("/ /", " ", period_time_string($row[0]));
                            $end_str   = preg_replace("/ /", " ", period_time_string($row[1], -1));
                            switch (cmp3($row[0], $midnight) . cmp3($row[1], $midnight_tonight))
                            {
                                case "> < ":
                                case "= < ":
                                    if ($start_str == $end_str)
                                        $horaires = $start_str;
                                    else
                                        $horaires = $start_str . "~" . $end_str;
                                    break;
                                case "> = ":
                                    $horaires = $start_str . "~24:00";
                                    break;
                                case "> > ":
                                    $horaires = $start_str . "~==>";
                                    break;
                                case "= = ":
                                    $horaires = $all_day2;
                                    break;
                                case "= > ":
                                    $horaires = $all_day2 . "==>";
                                    break;
                                case "< < ":
                                    $horaires = "<==~" . $end_str;
                                    break;
                                case "< = ":
                                    $horaires = "<==" . $all_day2;
                                    break;
                                case "< > ":
                                    $horaires = "<==" . $all_day2 . "==>";
                                    break;
                            }
                        }
                        else
                        {
                            switch (cmp3($row[0], $midnight) . cmp3($row[1], $midnight_tonight))
                            {
                                case "> < ":
                                case "= < ":
                                    $horaires = date(hour_min_format(), $row[0]) . "~" . date(hour_min_format(), $row[1]);
                                    break;
                                case "> = ":
                                    $horaires = date(hour_min_format(), $row[0]) . "~24:00";
                                    break;
                                case "> > ":
                                    $horaires = date(hour_min_format(), $row[0]) . "~==>";
                                    break;
                                case "= = ":
                                    $horaires = $all_day2;
                                    break;
                                case "= > ":
                                    $horaires = $all_day2 . "==>";
                                    break;
                                case "< < ":
                                    $horaires = "<==~" . date(hour_min_format(), $row[1]);
                                    break;
                                case "< = ":
                                    $horaires = "<==" . $all_day2;
                                    break;
                                case "< > ":
                                    $horaires = "<==" . $all_day2 . "==>";
                                    break;
                            }
                        }
                        $da[$day_num][$month_num][$year_num]["data"][] = titre_compact($overloadFieldList, $row, $horaires);
                        //Only if end time > midnight does the loop continue for the next day.
                        if ($row[1] <= $midnight_tonight)
                            break;
                        //$day_num++;
                        $t = $midnight = $midnight_tonight;
                        $day_num = date("j", $t);
                        $month_num = date("m", $t);
                        $year_num  = date("Y", $t);
                    }
                } // fin condition type exclu
            }
        }
        grr_sql_free($res);
        
        return $da;
    }

    public static function MoisAffichage($month_indice, $area, $display_day, $da){
       global $user_name;

       $month_num = date("m", $month_indice);
       $year_num  = date("Y", $month_indice);
       $days_in_month = date("t", $month_indice);
       
   
       $t2 = mktime(0, 0, 0, $month_num, 1, $year_num);
    
       $joursMois = array();
       for ($k = 0; $k < $days_in_month; $k++)
       {
           $cday = date("j", $t2);
           $cmonth =date("m", $t2);
           $cweek = date("w", $t2);
           $cyear = date("Y", $t2);
           $name_day = ucfirst(utf8_strftime("%a<br />%d", $t2)); // On inscrit le quantième du jour dans la deuxième ligne
           $temp = mktime(0,0,0,$cmonth,$cday,$cyear);
           $jour_cycle = grr_sql_query1("SELECT Jours FROM ".TABLE_PREFIX."_calendrier_jours_cycle WHERE DAY='$temp'");
           $t2 = mktime(0,0,0,$month_num,$cday+1,$year_num);
           if ($display_day[$cweek] == 1)
           {
               if (isHoliday($temp))
                   $class = "cell_hours ferie";
               else if (isSchoolHoliday($temp))
                   $class = "cell_hours vacance";
               else
                   $class = "cell_hours";
   
               $cycle = "";
               if (Settings::get("jours_cycles_actif") == "Oui" && intval($jour_cycle)>-1)
               {
                   if (intval($jour_cycle) > 0)
                       $cycle = ucfirst(substr(get_vocab("rep_type_6"),0,1)).$jour_cycle;
                   else
                   {
                       if (strlen($jour_cycle)>5)
                           $jour_cycle = substr($jour_cycle,0,3)."..";
                       $cycle = $jour_cycle;
                   }
               }
               $joursMois[] = array('num' => $cday, 'nom' => $name_day, 'class' => $class, 'cycle' => $cycle);
           }
       } // Fin affichage de la première ligne
   
       $sql = "SELECT room_name, capacity, id, description FROM ".TABLE_PREFIX."_room WHERE area_id=$area ORDER BY order_display,room_name";
       $res = grr_sql_query($sql);
       $li = 0;
       $ressourcesMois = array();
       for ($ir = 0; ($row = grr_sql_row($res, $ir)); $ir++)
       {
            // calcul de l'accès à la ressource en fonction du niveau de l'utilisateur
           $verif_acces_ressource = verif_acces_ressource($user_name, $row[2]);
           if ($verif_acces_ressource) // on n'affiche que les ressources accessibles
           {
               // Calcul du niveau d'accès aux fiches de réservation détaillées des ressources
               $acces_fiche_reservation = verif_acces_fiche_reservation($user_name, $row[2]);
               $li++;

               $joursRessource = array();
               for ($k = 1; $k <= $days_in_month; $k++)
               {
                   $reservationsJours = array();
                   $t2 = mktime(0, 0, 0,$month_num, $k, $year_num);
                   $cday = date("j", $t2);
                   $cweek = date("w", $t2);
                   $autreResa = false;
                   if ($display_day[$cweek] == 1) // Début condition "on n'affiche pas tous les jours de la semaine"
                   {
                       $horsResa = est_hors_reservation(mktime(0,0,0,$month_num,$cday,$year_num),$area);

                       //Anything to display for this day?
                       if (isset($da[$cday][$cmonth][$cyear]["id"][0]))
                       {
                           $n = count($da[$cday][$cmonth][$cyear]["id"]);
                               //If there are 12 or fewer, show them, else show 11 and "...".
                           for ($i = 0; $i < $n; $i++)
                           {
                               if ($i == 11 && $n > 12)
                               {
                                   $autreResa = true;
                                   break;
                               }
                               for ($i = 0; $i < $n; $i++)
                               {
                                   if ($da[$cday][$cmonth][$cyear]["room"][$i] == $row[0]) // test peu fiable car c'est l'id qui est unique YN le 26/02/2018
                                   {
                                       $id = $da[$cday][$cmonth][$cyear]["id"][$i];
                                       $type = $da[$cday][$cmonth][$cyear]["color"][$i];
                                       $lien = $da[$cday][$cmonth][$cyear]["lien"][$i];
                                       $data = $da[$cday][$cmonth][$cyear]["data"][$i];

                                       	// On n'affiche la fiche résa que si elle n'est pas confidentielle ou si on est l'auteur de la résa ou un gestionnaire
                                        $ficheResa = $acces_fiche_reservation;
                                        if($acces_fiche_reservation)
                                        {
                                            $authGetUserLevel = authGetUserLevel(getUserName(), $row[2]);
                                            if($da[$cday][$cmonth][$cyear]["confidentiel_resa"][$i] == 1 && getUserName() != $da[$cday][$cmonth][$cyear]["beneficiaire"][$i] && $authGetUserLevel < 3)
                                            {
                                                $ficheResa = false;
                                                $lien = $id;
                                            }
                                        }
   
                                       $reservationsJours[] = array('idresa' => $id, 'type' => $type, 'lien' => $lien, 'bulle' => $data, 'lienFiche' => $ficheResa);
                                   }
                               }
                           }
                       }
                       $joursRessource[] = array('jour' => $cday, 'horsResa' => $horsResa, 'autreResa' => $autreResa, 'reservationsJours' => $reservationsJours);
                   } // fin condition "on n'affiche pas tous les jours de la semaine"
               }
   
               $ressourcesMois[] = array('id' => $row[2], 'nom' => $row[0], 'joursRessource' => $joursRessource);
           } // fin ressources accessibles
       } // fin boucle ressources
       grr_sql_free($res);

       return array($joursMois, $ressourcesMois);
    }

}

?>