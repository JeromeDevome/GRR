<?php
/**
 * semaine.php
 * Affichage du planning en mode "semaine" pour une ressource.
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2025-11-24 15:48$
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

$grr_script_name = "semaine.php";

$trad = $vocab;

include "include/resume_session.php";
include "include/planning.php";

$debug_flag = FALSE;
// le paramètre $room est obligatoire
if (!isset($room) || ($room == 0)){
  $msg = get_vocab('choose_a_room');
  if ($area == 0) $area = 1;
  $lien = "app.php?p=semaine_all&area=".$area."&day=".$day."&month=".$month."&year=".$year;
  echo "<script type='text/javascript'>
    alert('$msg');
    document.location.href='$lien';
  </script>";
  echo "<p><br/>";
  echo $msg."<a href='app.php?p=semaine_all'>".get_vocab("link")."</a>";
  echo "</p>";
  die();
}
$time = mktime(0, 0, 0, $month, $day, $year);

if (($weekday = (date("w", $time) - $weekstarts + 7) % 7) > 0)
  $time = mktime(0,0,0,$month,$day-$weekday,$year); // recule de $weekday jours, php corrigera en fonction du changement d'heure

$day_week   = date("d", $time); // premier jour de la semaine
$month_week = date("m", $time);
$year_week  = date("Y", $time);

$am7 = mktime($morningstarts, 0, 0, $month_week, $day_week, $year_week);
$pm7 = mktime($eveningends, $eveningends_minutes, 0, $month, $day_week, $year_week);
$week_midnight = mktime(0, 0, 0, $month_week, $day_week, $year_week);
$week_start = $am7;
$week_end = mktime($eveningends, $eveningends_minutes, 0, $month_week, $day_week + 6, $year_week);

$sql= "SELECT area_name, resolution_area FROM ".TABLE_PREFIX."_area WHERE id=$area";
$res = grr_sql_query($sql);
if ($res){
    $this_area = grr_sql_row($res,0);
}
$d['nomDomaine'] = (isset($this_area[0]))? $this_area[0]:"";
$this_area_resolution = (isset($this_area[1]))? $this_area[1]:"";
grr_sql_free($res);

$sql = "SELECT * FROM ".TABLE_PREFIX."_room WHERE id=$room";
$res = grr_sql_query($sql);
if ($res){
  $this_room = grr_sql_row_keyed($res,0);
}
$d['nomRessource'] = (isset($this_room['room_name']))? $this_room['room_name']:"";
$d['capaciteRessource'] = (isset($this_room['capacity']))? $this_room['capacity']:0;
$d['nomRessourceDesc'] = (isset($this_room['description']))? $this_room['description']:'';
$d['statutRessource'] = (isset($this_room['statut_room']))? $this_room['statut_room']:1;
$d['moderationResa'] = (isset($this_room['moderate']))? $this_room['moderate']:0;
$this_delais_option_reservation = (isset($this_room['delais_option_reservation']))? $this_room['delais_option_reservation']:0;
$d['commentaireRessource'] = (isset($this_room['comment_room']))? $this_room['comment_room']:'';
$d['afficherCommentaire'] = (isset($this_room['show_comment']))? $this_room['show_comment']:'n';
$who_can_book = (isset($this_room['who_can_book']))? $this_room['who_can_book']:1;
$resa_confidentiel = (isset($this_room['confidentiel_resa']))? $this_room['confidentiel_resa']:0;
grr_sql_free($res);

//Pour vérifier si la plage de fin arrive sur un créneau ou non.
$minutesFinCreneaux = array();
for($h=0; $h<7200; $h+=$this_area_resolution) {
  $minutesFinCreneaux[] = date('i', $h);
}

switch ($dateformat) {
  case "en":
  $dformat = "%A, %b %d";
  break;
  case "fr":
  $dformat = "%A %d %b";
  break;
}
$i = mktime(0, 0, 0, $month_week,$day_week - 7, $year_week);
$d['yy'] = date("Y", $i);
$d['ym'] = date("m", $i);
$d['yd'] = date("d", $i);
$i = mktime(0, 0, 0, $month_week, $day_week + 7, $year_week);
$d['ty'] = date("Y", $i);
$d['tm'] = date("m", $i);
$d['td'] = date("d", $i);

// Calcul du niveau de droit de réservation
$authGetUserLevel = authGetUserLevel($user_name, $room);
// Determine si un visiteur peut réserver une ressource
$auth_visiteur = auth_visiteur($user_name, $room);
// si la ressource est restreinte, l'utilisateur peut-il réserver ?
$user_can_book = $who_can_book || ($authGetUserLevel > 2) || (authBooking($user_name,$room));
// Calcul du niveau d'accès aux fiches de réservation détaillées des ressources
$acces_fiche_reservation = verif_acces_fiche_reservation($user_name, $room);
// Teste si l'utilisateur a la possibilité d'effectuer une réservation, compte tenu des limitations éventuelles de la ressource et du nombre de réservations déjà effectuées.
$UserRoomMaxBooking = UserRoomMaxBooking($user_name, $room, 1);
// calcul des cellules du planning
$sql = "SELECT start_time, end_time, ".TABLE_PREFIX."_entry.id, name, beneficiaire, ".TABLE_PREFIX."_room.room_name,type, statut_entry, ".TABLE_PREFIX."_entry.description, ".TABLE_PREFIX."_entry.option_reservation, ".TABLE_PREFIX."_room.delais_option_reservation, ".TABLE_PREFIX."_entry.moderate, beneficiaire_ext, clef, ".TABLE_PREFIX."_entry.courrier, ".TABLE_PREFIX."_type_area.type_name, ".TABLE_PREFIX."_entry.overload_desc,".TABLE_PREFIX."_entry.room_id, nbparticipantmax, ".TABLE_PREFIX."_room.confidentiel_resa
FROM ".TABLE_PREFIX."_entry, ".TABLE_PREFIX."_room, ".TABLE_PREFIX."_area, ".TABLE_PREFIX."_type_area
WHERE
".TABLE_PREFIX."_entry.room_id=".TABLE_PREFIX."_room.id AND
".TABLE_PREFIX."_area.id = ".TABLE_PREFIX."_room.area_id AND
".TABLE_PREFIX."_room.id = '".$room."' AND
".TABLE_PREFIX."_type_area.type_letter = ".TABLE_PREFIX."_entry.type AND
start_time <= $week_end AND
end_time > $week_start AND
".TABLE_PREFIX."_entry.supprimer = 0 
ORDER by start_time";
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
if ($enable_periods == 'y')
{
    $first_slot = 0;
    $last_slot = count($periods_name)-1; 
}
else
{
    $first_slot = $morningstarts * 3600 / $resolution; 
    $last_slot = ($eveningends * 3600 + $eveningends_minutes * 60) / $resolution -1; 
}
if ($debug_flag)
  echo "<br />DEBUG: query=$sql <br />first_slot=$first_slot - last_slot=$last_slot\n";
$res = grr_sql_query($sql);
if (!$res)
  echo grr_sql_error();
else
{
    $overloadFieldList = mrbsOverloadGetFieldslist($area);
  // Pour toutes les réservations
  for ($i = 0; ($row = grr_sql_row_keyed($res, $i)); $i++)
    //foreach($res as $row) incompatible avec la fonction affichage_resa_planning_complet
  {
    if ($debug_flag)
      echo "<br />DEBUG: result ".$i.", id ".$row[4].", starts ".$row["start_time"]." (".affiche_date($row["start_time"])."), ends ".$row["end_time"]." (".affiche_date($row["end_time"]).")\n";
    $month_debut = date("m",$row["start_time"]);
    $day_debut = date("d",$row["start_time"]);
    $year_debut = date("Y",$row["start_time"]); 
    $debut_jour = mktime($morningstarts,0,0,$month_debut,$day_debut,$year_debut);
    $t = max(round_t_down($row["start_time"], $resolution, $debut_jour), $week_start); // instant de départ de la tranche de résa
    $month_current = date("m",$t);
    $day_current = date("d",$t);
    $year_current = date("Y",$t);
    $end_t = min(round_t_up($row["end_time"],$resolution, $debut_jour), $week_end+$this_area_resolution); // instant de fin de la tranche de résa
    $weekday = (date("w", $t) + 7 - $weekstarts) % 7;
    $prev_weekday = -1;
    $firstday = date("d", $t);
    $lastday = date("d", $end_t);
    // on sépare les plages définies par horaires et celles définies par créneaux
    if ($enable_periods != 'y') // cas des plages définies par horaires
    {
      $day_midnight = mktime(0,0,0,$month_current,$day_current,$year_current);
      $slot = ($t - $day_midnight) % 86400 / $this_area_resolution; // premier slot à afficher
      $heigthSlotHoure = 60/($this_area_resolution/60);
      do
      {
        if ($debug_flag)
          echo "<br />DEBUG: t=$t (".affiche_date($t)."), end_t=$end_t (".affiche_date($end_t)."), weekday=$weekday, slot=$slot\n";
        if ($slot < $first_slot)
        {
          $slot = $first_slot;
          $t = $weekday * 86400 + $am7;
          continue;
        }
        if ($slot <= $last_slot)
        {
          $d[$weekday][$slot]["beneficiaire"] = $row["beneficiaire"];
          $d[$weekday][$slot]["color"] = $row["type"];
          if (($end_t) > mktime(24, 0, 0, date('m',$t), date('d',$t), date('Y',$t))) // fin de réservation dépassant la fin de journée
          {
            if (date("d", $t) == $firstday) // Premier jour de réservation, Hdebut = Heure debut résa / Hfin = heure fin de journée / duree = (nb bloc d'une journée - nb bloc vides)
            {   
              $d[$weekday][$slot]["horaireDebut"] = $t;
              $d[$weekday][$slot]["horaireFin"] = mktime($eveningends, $eveningends_minutes, 0, date('m',$t), date('d',$t), date('Y',$t));
              $d[$weekday][$slot]["duree"] = (mktime($eveningends, $eveningends_minutes, 0, date('m',$t), date('d',$t), date('Y',$t))-$t)/$this_area_resolution;
            }
            // Les jours entre les deux , Hdebut = Heure debut journée/ Hfin = heure fin journée / duree = ( h fin journée - h debut journée) * nb bloc pr 1h ) 
            else
            {
              $d[$weekday][$slot]["horaireDebut"] = mktime($morningstarts, 0, 0, date('m',$t), date('d',$t), date('Y',$t));
              $d[$weekday][$slot]["horaireFin"] = mktime($eveningends, $eveningends_minutes, 0, date('m',$t), date('d',$t), date('Y',$t));
              $d[$weekday][$slot]["duree"] = (($eveningends+$eveningends_minutes/60-$morningstarts)*$heigthSlotHoure);
            }
          }
          else // fin de réservation ne dépassant pas la fin de la journée ou dernière journée
          {
            $d[$weekday][$slot]["horaireDebut"] = $t;
            $d[$weekday][$slot]["horaireFin"] = $end_t;
            $d[$weekday][$slot]["duree"] = ($end_t- $t)/ $this_area_resolution;
          }
          // affichage pour debug
          if ($debug_flag)
          {
            echo date('j-m-Y H:i:s',$d[$weekday][$slot]["horaireDebut"])." --- ";
            echo date('j-m-Y H:i:s',$d[$weekday][$slot]["horaireFin"])." --- ";
            echo $d[$weekday][$slot]["duree"]." --- ";
            echo "C2C<br>";
          }
          //Si la plage de fin dépasse le créneau, augmenter la durée
          if(!in_array(date('i', $d[$weekday][$slot]["horaireFin"]), $minutesFinCreneaux)) {
            $d[$weekday][$slot]["duree"] += 1;
          }
          if ($prev_weekday != $weekday)
          {
            $prev_weekday = $weekday;
            $heure_fin = date('H:i',$d[$weekday][$slot]["horaireFin"]);
            if (($heure_fin == '00:00') && ((date('w',$d[$weekday][$slot]["horaireFin"]) - $weekday) != 1)) 
              {$heure_fin = '24:00';}
            $horaires = date('H:i',$d[$weekday][$slot]["horaireDebut"]).get_vocab("to"). $heure_fin."";
            $d[$weekday][$slot]["data"] = affichage_resa_planning_complet($overloadFieldList, 1, $row, $horaires);
            $d[$weekday][$slot]["id"] = $row["id"];
            $d[$weekday][$slot]["who"] = affichage_resa_info_bulle($overloadFieldList, 1, $row, $horaires);
          }
        }
        $t += $this_area_resolution;
        $slot++;
        if ($slot > $last_slot)
        {
          $weekday++;
          $slot = $first_slot;
          $t = $weekday * 86400 + $am7; // pb à prévoir avec changement d'heure ? YN
        }
      } while ($t < $end_t);
    }
    else  // cas des plages définies par créneaux
    {
      $slot = 0;
      do
      {
        if ($debug_flag)
          echo "<br />DEBUG: t=$t (".affiche_date($t)."), end_t=$end_t (".affiche_date($end_t)."), weekday=$weekday, slot=$slot\n";
        $start_slot = date('i',$t)+60*date('H',$t)-720;
        $end_slot = date('i',$end_t)+60*date('H',$end_t)-720;
        if ($slot < $start_slot)
        {
            $slot = $start_slot;
            continue;
        }
        if ($slot <= $last_slot)
        {
          $d[$weekday][$slot]["beneficiaire"] = $row["beneficiaire"];
          $d[$weekday][$slot]["color"] = $row["type"];
          if (($end_t) > mktime($eveningends, $eveningends_minutes, 0, date('m',$t), date('d',$t), date('Y',$t))) // fin de réservation dépassant la fin de journée
          {
            if (date("d", $t) == $firstday) // Premier jour de réservation, Hdebut = Heure debut résa / Hfin = heure fin de journée / duree = (nb bloc d'une journée - nb bloc vides)
            {   
              $d[$weekday][$slot]["horaireDebut"] = $t;
              $d[$weekday][$slot]["horaireFin"] = mktime($eveningends, $eveningends_minutes, 0, date('m',$t), date('d',$t), date('Y',$t));
              $d[$weekday][$slot]["duree"] = $last_slot+1-$start_slot; 
            }
            // Les jours entre les deux , Hdebut = Heure debut journée/ Hfin = heure fin journée / duree = nb de créneaux dans une journée 
            else
            {
              $d[$weekday][$slot]["horaireDebut"] = mktime($morningstarts, 0, 0, date('m',$t), date('d',$t), date('Y',$t));
              $d[$weekday][$slot]["horaireFin"] = mktime($eveningends, $eveningends_minutes, 0, date('m',$t), date('d',$t), date('Y',$t))+3600;
              $d[$weekday][$slot]["duree"] = count($periods_name); 
            }
          }
          else // fin de réservation ne dépassant pas la fin de la journée ou dernière journée
          {
            $d[$weekday][$slot]["horaireDebut"] = $t;
            $d[$weekday][$slot]["horaireFin"] = $end_t;
            $d[$weekday][$slot]["duree"] = $end_slot-$start_slot; 
          } 
          // affichage pour debug
          if ($debug_flag)
          {
            echo date('j-m-Y H:i:s',$d[$weekday][$slot]["horaireDebut"])." --- ";
            echo date('j-m-Y H:i:s',$d[$weekday][$slot]["horaireFin"])." --- ";
            echo $d[$weekday][$slot]["duree"]." --- ";
            echo "C2C<br>";
          }
          if ($prev_weekday != $weekday)
          {
            $prev_weekday = $weekday;
            $horaires = "";
            $d[$weekday][$slot]["data"] = affichage_resa_planning_complet($overloadFieldList, 1, $row, $horaires);
            $d[$weekday][$slot]["id"] = $row["id"];
            $d[$weekday][$slot]["who"] = affichage_resa_info_bulle($overloadFieldList, 1, $row, $horaires);
          }
        }
        $t += 60; 
        $slot++;
        if ($slot > $last_slot) // passe au lendemain
        {
          $weekday++;
          $day_current++;
          $slot = $first_slot;
          $t = mktime(12,0,0,$month_current,$day_current,$year_current);
        }
      } while ($t < $end_t);
    }    
  }
}
grr_sql_free($res);

// paramètres d'affichage pour twig
if ((!isset($d['pview'])) || ($d['pview'] != 1))
{
    $positionMenu = Settings::get("menu_gauche");
    $d['positionMenu'] = ($positionMenu != 0)? $positionMenu : 1; // il faut bien que le menu puisse s'afficher, par défaut ce sera à gauche sauf choix autre par setting
}
if (verif_display_fiche_ressource($user_name, $room) && $d['pview'] != 1)
{
    $d['ficheRessource'] = true;
}
if ($authGetUserLevel > 2 && $d['pview'] != 1)
{
    $d['accessConfig'] = true;
}
$d['ressourceEmpruntee'] = affiche_ressource_empruntee_twig($room);
$d['semaineActuel'] = get_vocab("week").get_vocab("deux_points").utf8_strftime($dformat, $week_start).' - '.utf8_strftime($dformat, $week_end);


if ($debug_flag)
{
  print_r($d);
  /*
  echo "<p>DEBUG:<p><pre>\n";
  if (gettype($d) == "array")
  {
        foreach($d as $w_k=>$w_v)
    {
          foreach($w_v as $t_k=>$t_v)
      {
              foreach($t_v as $k_k=>$k_v)
                    echo "$d[$w_k][$t_k][$k_k] =", $k_v ,"<br/>";
      }
    }
  }
  else echo "d is not an array!\n";
  echo "</pre><p>\n";*/
} 

$num_week_day = $weekstarts;
$k = $day_week;
$i = $time;

// Les X jours de la semaine à afficher
$joursSemaine = array ();
for ($t = $week_start; $t < $week_end; $t += 86400)
{
  $num_day = date('d', $t);
  $month_actuel = date('m', $t);
  $year_actuel  = date("Y",$t);
  $nomCycle = "";
  $tt = mktime(0, 0, 0, $month_actuel, $num_day,$year_actuel);
  $jour_cycle = grr_sql_query1("SELECT Jours FROM ".TABLE_PREFIX."_calendrier_jours_cycle WHERE DAY='$i'");
  if ($display_day[$num_week_day] == 1)
  {
    $class = "cell_hours";
    $title = "";
    if ($settings->get("show_holidays") == "Oui")
    {
      if (isHoliday($tt)){
        $class .= ' ferie';
      }
      elseif (isSchoolHoliday($tt)){
        $class .= ' vacance';
      }
    }
    $nomJour = utf8_strftime($dformat, $t);
    if (Settings::get("jours_cycles_actif") == "Oui" && intval($jour_cycle) >- 1)
      if (intval($jour_cycle) > 0)
        $nomCycle = get_vocab("rep_type_6")." ".$jour_cycle;
      else
        $nomCycle =  "<br />".$jour_cycle;

    $joursSemaine[] = array('numJour' => $num_day, 'nomJour' => $nomJour, 'nomCycle' => $nomCycle, 'class' => $class, 'annee' => $year_actuel, 'mois' => $month_actuel);
  }
  if (!isset($correct_heure_ete_hiver) || ($correct_heure_ete_hiver == 1))
  {
    $num_day = date('d', $t);
    if (heure_ete_hiver("hiver", $year, 0) == mktime(0, 0, 0, $month, $num_day, $year))
      $t += 3600;
    if ((date("H",$t) == "13") || (date("H",$t) == "02"))
      $t -= 3600;
  }
  $i += 86400;
  $k++;
  $num_week_day++;
  $num_week_day = $num_week_day % 7;
}

$t = $am7;
$nb_case = 0;
$semaine_changement_heure_ete = 'no';
$semaine_changement_heure_hiver = 'no';

$lignesHoraires = array();

// Pour l'ensemble des horaires ou des créneaux
for ($slot = $first_slot; $slot <= $last_slot; $slot++)
{
    // 1ere colonne heure ou nom créneau
    if ($slot % 2 == 1)
        $classHoraire = "cell_hours";
    else
        $classHoraire = "cell_hours2";
    if ($enable_periods=='y')
    {
        $time_t = date("i", $t);
        $time_t_stripped = preg_replace( "/^0/", "", $time_t );
        $horairePeriode = $periods_name[$time_t_stripped];
    }
    else
        $horairePeriode = affiche_heure_creneau($t,$this_area_resolution);

    $wt = $t;
    $num_week_day = $weekstarts;

    // X colonnes (1 par jour)
    $cellulesJours = array();
    for ($weekday = 0; $weekday < 7; $weekday++)
    {
        $wday = date("d", $wt);
        $wmonth = date("m", $wt);
        $wyear = date("Y", $wt);
        $hour = date("H",$wt);
        $minute  = date("i",$wt);
        $heureete1 = heure_ete_hiver("ete", $wyear,0);
        $heurehiver1 = heure_ete_hiver("hiver",$wyear, 0);
        $heureete2 = heure_ete_hiver("ete", $wyear,2);
        $heurehiver2 = heure_ete_hiver("hiver", $wyear, 2);
        $statutCellule = 0; //0 vide, 1 réservable, 2 déjà une reservation, 3 hors résa
        $ficheResa = $acces_fiche_reservation;
        $titre = "";
        $descr = "";
        $beneficiaire = "";
        $rowspan = 1;
        $c = "";
        $id = 0;

        if (!isset($correct_heure_ete_hiver) || ($correct_heure_ete_hiver == 1))
        {
            $temp =   mktime(0, 0, 0, $wmonth, $wday,$wyear);
            if ($heureete1 == $temp)
            {
                $semaine_changement_heure_ete = 'yes';
                $temp2 =   mktime($hour, 0, 0, $wmonth, $wday, $wyear);
                if ($heureete2 == $temp2)
                {
                    if ($display_day[$num_week_day] == 1)
                        $c ="empty_cell";
                    $nb_case++;
                    $insere_case = 'y';
                }
                else if ($heureete2 < $temp2)
                {
                    $hour = date("H", $wt - 3600);
                    $decale_slot = 1;
                    $insere_case = 'n';
                }
            }
            else if ($heurehiver1 == $temp)
            {
                $semaine_changement_heure_hiver = 'yes';
                $temp2 =   mktime($hour, 0, 0, $wmonth, $wday, $wyear);
                if ($heurehiver2 == $temp2)
                {
                    $nb_case = $nb_case + 0.5;
                    $insere_case = 'n';
                }
                else if ($heurehiver2 < $temp2)
                {
                    $hour = date("H", $wt + 3600);
                    $decale_slot = -1;
                    $insere_case = 'n';
                }
            }
            else
            {
                $decale_slot = 0;
                $insere_case = 'n';
                if (($semaine_changement_heure_ete == 'yes') && ($heureete1 < $temp))
                {
                    $decale_slot = 1;
                    $hour = date("H", $wt - 3600);
                }
                if (($semaine_changement_heure_hiver == 'yes') && ($heurehiver1 < $temp))
                {
                    $decale_slot = -1;
                    $hour = date("H", $wt + 3600);
                }
            }
        }
        else
        {
            $decale_slot = 0;
            $insere_case = 'n';
        }
        if (($insere_case == 'n') && ($display_day[$num_week_day] == 1))
        {
            if (!isset($d[$weekday][$slot - $decale_slot * $nb_case]["color"])) // pas de réservation sur ce slot
            {
                $date_booking = mktime($hour, $minute, 0, $wmonth, $wday, $wyear);
                if ($enable_periods == 'y')
                    $date_booking = mktime(23,59,0,$wmonth,$wday,$wyear);
                if ($d['statutRessource'] == "0")
                    $c = "avertissement";
                else
                    $c = "empty_cell";
                 if (est_hors_reservation(mktime(0, 0, 0, $wmonth, $wday, $wyear), $area))
                    $statutCellule = 3;
               else
                {
                    if ((($authGetUserLevel > 1) || ($auth_visiteur == 1)) && ($UserRoomMaxBooking != 0) && verif_booking_date($user_name, -1, $room, $date_booking, $date_now, $enable_periods) && verif_delais_max_resa_room($user_name, $room, $date_booking) && verif_delais_min_resa_room($user_name, $room, $date_booking, $enable_periods) && (($d['statutRessource'] == "1") || (($d['statutRessource'] == "0") && ($authGetUserLevel > 2))) && $user_can_book && $d['pview'] != 1)
                        $statutCellule = 1;
                }
            }
            else
            {
                if (est_hors_reservation(mktime(0, 0, 0, $wmonth, $wday, $wyear), $area))
                    $statutCellule = 3;
               else
                {
                    if (isset($d[$weekday][$slot - $decale_slot * $nb_case]["id"]))
                    {
                        $statutCellule = 2;
                        $id =  $d[$weekday][$slot - $decale_slot * $nb_case]["id"];
                        if ($enable_periods == 'y'){ // Nb de case pour créneau
                            $rowspan = $d[$weekday][$slot - $decale_slot * $nb_case]["duree"];
                        } 
                        else {
                            $rowspan = $d[$weekday][$slot - $decale_slot * $nb_case]["duree"];
                        }
                        $c = "type".$d[$weekday][$slot - $decale_slot * $nb_case]["color"];
                        $titre = $d[$weekday][$slot - $decale_slot * $nb_case]["who"];
                        $descr = $d[$weekday][$slot - $decale_slot * $nb_case]["data"];
                        $beneficiaire = $d[$weekday][$slot - $decale_slot * $nb_case]["beneficiaire"];
                    }
                }
            }
        }

        $wt += 86400;
        $num_week_day++; // Pour le calcul des jours à afficher
        $num_week_day = $num_week_day % 7; // Pour le calcul des jours à afficher

        // On n'affiche la fiche résa que si elle n'est pas confidentielle ou si on est l'auteur de la résa ou un gestionnaire
        if($ficheResa)
        {
            if($resa_confidentiel == 1 && getUserName() != $beneficiaire && $authGetUserLevel < 3)
                $ficheResa = false;
        }


        $cellulesJours[] = array('statut' => $statutCellule, 'class' => $c, 'rowspan' => $rowspan, 'ressource' => $room, 'idresa' => $id, 'titre' => $titre, 'descr' => $descr, 'ficheResa' => $ficheResa, 'annee' => $wyear, 'mois' => $wmonth, 'jour' => $wday);
    } // Fin colonne du jour
    $time_t_stripped = "";
    if ($enable_periods == 'y')
    {
        $time_t = date("i", $t);
        $time_t_stripped = preg_replace( "/^0/", "", $time_t);
    }
    $t += $resolution;

    $lignesHoraires[] = array('classHoraire' => $classHoraire, 'horairePeriode' => $horairePeriode, 'cellulesJours' => $cellulesJours, 'periode' => $time_t_stripped, 'heure' => $hour, 'minute' => $minute);
}
//echo "lignesHoraires  ";
//print_r($lignesHoraires);
echo $twig->render('semaine.twig', array('trad' => $trad, 'd' => $d, 'settings' => $AllSettings, 'joursSemaine' => $joursSemaine, 'lignesHoraires' => $lignesHoraires));
?>