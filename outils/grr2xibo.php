<?php
/**
 * grr2xibo.php
 * script produisant un objet JSON pour l'application XIBO, décrivant les réservations incluses dans l'intervalle [début, fin]
 * paramètres requis : debut, fin au format "jj-mm-aaaa hh:mm" et ressource par son id
 * appel typique : grr2xibo.php?ressource=1&debut=01-01-2000 12:00&fin=01-01-2030 12:00
 * rend un tableau d'objets JSON décrivant les informations : "date_deb","date_fin","plage_horaire","domaine","ressource","description_ressource","description_breve","description_complete"
 * ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2025-07-23 15:50$
 * @author    Yan Naessens
 * @copyright Copyright 2003-2025 Team DEVOME - JeromeB
 * @link      http://www.gnu.org/licenses/licenses.html
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */
/*
include "../include/misc.inc.php";
// teste la version de GRR
$_famille = substr($version_grr,0,1);
if($_famille == "4")
  include "../personnalisation/connect.inc.php";
else 
  include "../include/connect.inc.php";
include "../include/config.inc.php";
include "../include/mysql.inc.php";
include "../include/mrbs_sql.inc.php";

// paramètres requis : debut, fin au format "jj-mm-aaaa hh:mm" et ressource par son id
$debut = (isset($_GET["debut"]))? $_GET["debut"] : "01-01-2000 12:00";
$fin = (isset($_GET["fin"]))? $_GET["fin"] : "01-01-2030 12:00";
$ressource_id = (isset($_GET["ressource"]))? intval($_GET["ressource"]) : "1";

try{
  $Debut = explode(" ",$debut);
  $dDebut = $Debut[0];
  $tdDebut = explode("-",$dDebut);
  $hDebut = $Debut[1];
  $thDebut = explode(":",$hDebut);
  $Fin = explode(" ",$fin);
  $dFin = $Fin[0];
  $tdFin = explode("-",$dFin);
  $hFin = $Fin[1];
  $thFin = explode(":",$hFin);
  $start_time = mktime(intval($thDebut[0]),intval($thDebut[1]),0,intval($tdDebut[1]),intval($tdDebut[0]),intval($tdDebut[2]));
  $end_time = mktime(intval($thFin[0]),intval($thFin[1]),0,intval($tdFin[1]),intval($tdFin[0]),intval($tdFin[2]));

  $req = "SELECT area_name, room_name, r.description as rd, name, e.start_time, e.end_time, e.description as ed FROM  (grr_entry e JOIN grr_room r ON r.id = e.room_id) JOIN grr_area a ON a.id = r.area_id   WHERE (e.start_time > $start_time AND e.end_time < $end_time AND e.room_id = $ressource_id)  LIMIT 10";

  $res = grr_sql_query($req);

  if(($res)&&(grr_sql_count($res)>0)){
    $data = array();
    foreach ($res as $resdata) {
      $data[] = array(
                "date_deb" => date("d/m/Y H:i",$resdata["start_time"]),
                "date_fin" =>  date("d/m/Y H:i",$resdata["end_time"]),
                "plage_horaire" => date("H:i",$resdata["start_time"])."-".date("H:i",$resdata["end_time"]),
                "domaine" => $resdata["area_name"],
                "ressource" => $resdata["room_name"],
                "description_ressource" => $resdata["rd"],
                "description_breve" => $resdata["name"],
                "description_complete" => $resdata["ed"]
                );
    }
  }
  else{
    $data = array(
                  "date_deb" => $tdDebut[0]."/".$tdDebut[1]."/".$tdDebut[2],
                  "date_fin" => $tdFin[0]."/".$tdFin[1]."/".$tdFin[2],
                  "plage_horaire" => $thDebut[0]."h".$thDebut[1]."-".$thFin[0]."h".$thFin[1],
                  "domaine" => "",
                  "ressource" => "ressource no.".$ressource_id,
                  "description_ressource" => "",
                  "description_breve" => "xxxxx",
                  "description_complete" => ""
                  );
  }
}
catch(Exception $e){
  $data = array(
                "date_deb" => $tdDebut[0]."/".$tdDebut[1]."/".$tdDebut[2],
                "date_fin" => $tdFin[0]."/".$tdFin[1]."/".$tdFin[2],
                "plage_horaire" => $thDebut[0]."h".$thDebut[1]."-".$thFin[0]."h".$thFin[1],
                "domaine" => "",
                "ressource" => "ressource no.".$ressource_id,
                "description_ressource" => "",
                "description_breve" => "",
                "description_complete" => $e -> getMessage()
                );
}

$Jsondata = json_encode($data);
header("Content-Type: application/json; charset=UTF-8");
echo $Jsondata;
*/
?>