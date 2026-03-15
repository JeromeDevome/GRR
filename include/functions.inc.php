<?php
/**
 * include/functions.inc.php
 * fichier Bibliothèque de fonctions de GRR
 * Dernière modification : $Date: 2026-01-19 16:35$
 * @author    JeromeB & Laurent Delineau & Marc-Henri PAMISEUX & Yan Naessens
 * @copyright Copyright 2003-2026 Team DEVOME - JeromeB
 * @link      http://www.gnu.org/licenses/licenses.html
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */
 
// auxiliaires
/**
 * @param integer $need_header
 */
function fatal_error($need_header, $message, $show_form_data = true)
{
  if ($need_header)
    start_page_w_header(0, 0, 0, 0);
  error_log("GRR: ".$message);
  if ($show_form_data)
  {
    if (!empty($_GET))
    {
      error_log("GRR GET: ".print_r($_GET, true));
    }
    if (!empty($_POST))
    {
      error_log("GRR POST: ".print_r($_POST, true));
    }
  }
  if (!empty($_SESSION))
  {
    error_log("GRR SESSION: ".print_r($_SESSION, true));
  }
  echo '<p>',$message,'</p>'.PHP_EOL;
  end_page();
  exit;
}
/* récupère les variables passées par GET ou POST ou bien par COOKIE, et leur affecte le type indiqué (int ou string)
 * rend $default si la valeur recherchée n'est pas référencée
*/
function getFormVar($nom,$type='',$default=NULL){
  $valeur = isset($_GET[$nom])? $_GET[$nom] : (isset($_POST[$nom])? $_POST[$nom] : (isset($_COOKIE['nom'])? $_COOKIE['nom'] : $default));
  if ((isset($valeur)) && (($type =='int')||($type =='string')))
    settype($valeur,$type);
  return $valeur;
}
/* fonction clean_input
* pour réduire le risque XSS
*/
function clean_input($data){
    if ($data != NULL){
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
    }
    return $data;
}
/** function getUserName()
 * retourne le login de l'utilisateur connecté (et pas son nom), une chaîne vide sinon
*/
function getUserName()
{
  if (isset($_SESSION['login']))
    return $_SESSION['login'];
  else
    return '';
}
/**
 * @param string $type
 */
function VerifNomPrenomUser($type)
{
  // ne pas prendre en compte la page my_account.php
  global $desactive_VerifNomPrenomUser;
  if (($type == "with_session") && ($desactive_VerifNomPrenomUser != 'y') && (IsAllowedToModifyProfil()))
  {
    $test = grr_sql_query1("SELECT login FROM ".TABLE_PREFIX."_utilisateurs WHERE (login = '".getUserName()."' AND (nom='' or prenom = ''))");
    if ($test != -1)
    {
      header("Location:my_account.php");
      die();
    }
  }
}
/* Dans le cas ou $unicode_encoding = 1 (UTF-8) cette fonction encode les chaînes présentes dans
 le code "en dur", en UTF-8 avant affichage */
function encode_message_utf8($tag)
{
  global $charset_html, $unicode_encoding;
  if ($unicode_encoding)
    return iconv($charset_html,"utf-8",$tag);
  else
    return $tag;
}
// transforme une chaine de caractères en couleur hexadécimale valide
function valid_color($entry)
{
  $out = preg_replace('/[^a-fA-F0-9]/','',$entry);
  if (strlen($out)<4)
  {
    $out = '#'.substr($out.'000',0,3);
  }
  else //if (strlen($out)<7)
  {
    $out = '#'.substr($out.'000',0,6);
  }
  return($out);
}
function traite_grr_url($grr_script_name = "", $force_use_grr_url = "n")
{
  // Dans certaines configuration (reverse proxy, ...) les variables $_SERVER["SCRIPT_NAME"] ou $_SERVER['PHP_SELF']
  // sont mal interprétées entraînant des liens erronés sur certaines pages.
  if (((Settings::get("use_grr_url") == "y") && (Settings::get("grr_url") != "")) || ($force_use_grr_url == "y"))
  {
    if (substr(Settings::get("grr_url"), -1) != "/")
      $ad_signe = "/";
    else
      $ad_signe = "";
    return Settings::get("grr_url").$ad_signe.$grr_script_name;
  }
  else
    return filter_var($_SERVER['PHP_SELF'], FILTER_SANITIZE_URL);
}
/**
 * @param integer $action
 * $action = 1 -> Création
 * $action = 2 -> Modification
 * $action = 3 -> Suppression
 * $action = 4 -> Suppression automatique
 * $action = 5 -> Réservation en attente de modération
 * $action = 6 -> Résultat d'une décision de modération
 * $action = 7 -> Notification d'un retard dans la restitution d'une ressource.
*/
function send_mail($id_entry, $action, $dformat, $tab_id_moderes = array(), $oldRessource = '')
{
  global $vocab, $grrSettings, $locale, $weekstarts, $enable_periods, $periods_name;

  $message_erreur = '';

  if (@file_exists('include/mail.class.php')){
    require_once 'phpmailer/PHPMailerAutoload.php';
    require_once 'include/mail.class.php';
  }else{
    require_once '../phpmailer/PHPMailerAutoload.php';
    require_once '../include/mail.class.php';
  }

  $sql = "SELECT e.name, e.description, e.beneficiaire, r.room_name, a.area_name, e.type,
  e.room_id, e.repeat_id, 
  " . grr_sql_syntax_timestamp_to_unix("e.timestamp") . ",
  (e.end_time - e.start_time),
  e.start_time, e.end_time, r.area_id, r.delais_option_reservation, e.option_reservation,
  e.moderate, e.beneficiaire_ext, e.jours, e.clef, e.courrier
    FROM (".TABLE_PREFIX."_entry e JOIN ".TABLE_PREFIX."_room r ON e.room_id = r.id)
     JOIN ".TABLE_PREFIX."_area a ON r.area_id = a.id 
    WHERE e.id = ? ";
  $res = grr_sql_query($sql,"i",[$id_entry]);
  if (!$res)
    fatal_error(0, grr_sql_error());
  if (grr_sql_count($res) < 1)
    fatal_error(0, get_vocab('invalid_entry_id'));
  $row = grr_sql_row($res, 0);
  grr_sql_free($res);

  get_planning_area_values($row[12]);
  $breve_description      = bbcode(removeMailUnicode(htmlspecialchars($row[0])), 'nobbcode');
  $description          = bbcode(removeMailUnicode(htmlspecialchars($row[1])), 'nobbcode');
  $beneficiaire         = htmlspecialchars($row[2]);
  $room_name            = removeMailUnicode(htmlspecialchars($row[3]));
  $area_name            = removeMailUnicode(htmlspecialchars($row[4]));
  $room_id              = $row[6];
  $repeat_id            = $row[7];
  $date_avis            = utf8_strftime("%Y/%m/%d", $row[10]);
  $delais_option_reservation  = $row[13];
  $option_reservation     = $row[14];
  $moderate           = $row[15];
  $beneficiaire_ext     = htmlspecialchars($row[16]);
  $jours_cycle        = htmlspecialchars($row[17]);
  $duration             = $row[9];
  if ($enable_periods == 'y')
    list($start_period, $start_date) = period_date_string($row[10]);
  else
    $start_date = time_date_string($row[10],$dformat);
  $rep_type = 0;

  // Recherche du nom de l'ancienne ressource si besoin
  if($oldRessource != '' && $oldRessource != $room_id)
  {
    $sql = "SELECT room_name FROM ".TABLE_PREFIX."_room WHERE id= ? ";
        $nomAncienneSalle = grr_sql_query1($sql,"i",[$oldRessource]);
  }
  else
    $nomAncienneSalle = "";
  //

  if ($repeat_id != 0)
  {
    $res = grr_sql_query("SELECT rep_type, end_date, rep_opt, rep_num_weeks FROM ".TABLE_PREFIX."_repeat WHERE id= ? ","i",[$repeat_id]);
    if (!$res)
      fatal_error(0, grr_sql_error());
    $test = grr_sql_count($res);
    if ($test != 1)
      fatal_error(0, "Deux réservations ont le même ID.");
    else
    {
      $row2 = grr_sql_row($res, 0);
      $rep_type     = $row2[0];
      $rep_end_date = utf8_strftime($dformat,$row2[1]);
      $rep_opt      = $row2[2];
      $rep_num_weeks = $row2[3];
    }
    grr_sql_free($res);
  }
  if ($enable_periods == 'y')
    toPeriodString($start_period, $duration, $dur_units);
  else
    toTimeString($duration, $dur_units);
  $weeklist = array("unused", "every week", 'week_1_of_2', 'week_1_of_3', 'week_1_of_4', 'week_1_of_5');
  if ($rep_type == 2)
    $affiche_period = $vocab[$weeklist[$rep_num_weeks]];
  else
    $affiche_period = $vocab['rep_type_'.$rep_type];

  // Le bénéficiaire
  $beneficiaire_email = affiche_nom_prenom_email($beneficiaire,$beneficiaire_ext,"onlymail");
  if ($beneficiaire != "")
  {
    $beneficiaire_actif = grr_sql_query1("SELECT etat FROM ".TABLE_PREFIX."_utilisateurs WHERE login= ? ","s",[protect_data_sql($beneficiaire)]);
    if ($beneficiaire_actif == -1)
      $beneficiaire_actif = 'actif'; // Cas des admins
  }
  else if (($beneficiaire_ext != "") && ($beneficiaire_email != ""))
    $beneficiaire_actif = "actif";
  else
    $beneficiaire_actif = "inactif";

  // Utilisateur ayant agi sur la réservation
  $user_login = getUserName();
  $user_email = grr_sql_query1("SELECT email FROM ".TABLE_PREFIX."_utilisateurs WHERE login= ? ","s",[$user_login]);
  //
  // Elaboration du message destiné aux utilisateurs désignés par l'admin dans la partie "Mails automatiques"
  //
  // Nom d'expéditeur (si != adresse de réponse, cas des serveurs SMTP refusant le relai)
  $expediteur = '';
  if (Settings::get('grr_mail_sender'))
    {$expediteur = Settings::get('grr_mail_from');}
  //Nom de l'établissement et mention "mail automatique"
  $message = removeMailUnicode(Settings::get("company"))." - ".$vocab["title_mail"];
  // Url de GRR
  $message .= traite_grr_url("","y")."\n\n";
  $sujet = $vocab["subject_mail1"].$room_name." - ".$date_avis;

  if ($action == 1){ // Création
    $sujet .= $vocab["subject_mail_creation"];
    $message .= $vocab["the_user"].affiche_nom_prenom_email($user_login,"","formail");
    $message .= $vocab["creation_booking"];
    $message .= $vocab["the_room"].$room_name." (".$area_name.") \n";
    $repondre = $user_email;
  }
  elseif ($action == 2){ // Modification
    $sujet .= $vocab["subject_mail_modify"];
    if ($moderate == 1)
      $sujet .= " (".$vocab["en_attente_moderation"].")";
    $message .= $vocab["the_user"].affiche_nom_prenom_email($user_login,"","formail");
    $message .= $vocab["modify_booking"];
    if ($room_name != $oldRessource)
      $message .= $vocab["the_room"]." ".$oldRessource." => ".$room_name." (".$area_name.") \n";
    else
      $message .= $vocab["the_room"].$room_name." (".$area_name.") \n";
    $message .= $vocab["reservee au nom de"];
    $message .= $vocab["the_user"].affiche_nom_prenom_email($beneficiaire,$beneficiaire_ext,"formail")." \n";
    $repondre = $user_email;
  }
  elseif ($action == 3){ // Suppression
    $sujet .= $vocab["subject_mail_delete"];
    if ($moderate == 1)
      $sujet .= " (".$vocab["en_attente_moderation"].")";
    $message .= $vocab["the_user"].affiche_nom_prenom_email($user_login,"","formail");
    $message .= $vocab["delete_booking"];
    $message .= $vocab["the_room"].$room_name." (".$area_name.") \n";
    $message .= $vocab["reservee au nom de"];
    $message .= $vocab["the_user"].affiche_nom_prenom_email($beneficiaire,$beneficiaire_ext,"formail")." \n";
    $repondre = $user_email;
  }
  elseif ($action == 4){ // Suppression automatique
    $sujet .= $vocab["subject_mail_delete"];
    $message .= $vocab["suppression_automatique"];
    $message .= $vocab["the_room"].$room_name." (".$area_name.") \n";
    $repondre = $user_email;
  }
  elseif ($action == 5){ // Réservation en attente de modération
    $sujet .= $vocab["subject_mail_moderation"];
    $message .= $vocab["reservation_en_attente_de_moderation"];
    $message .= $vocab["the_room"].$room_name." (".$area_name.") \n";
    $repondre = Settings::get("webmaster_email");
  }
  elseif ($action == 6){ // Résultat d'une décision de modération
    $sujet .= $vocab["subject_mail_decision_moderation"];
    $resmoderate = grr_sql_query("SELECT moderate, motivation_moderation FROM ".TABLE_PREFIX."_entry_moderate WHERE id = ? ","i",[$id_entry]);
    if (!$resmoderate)
      fatal_error(0, grr_sql_error());
    if (grr_sql_count($resmoderate) < 1)
      fatal_error(0, get_vocab('invalid_entry_id'));
    $rowModerate = grr_sql_row($resmoderate, 0);
    grr_sql_free($resmoderate);
    $moderate_decision = $rowModerate[0];
    $moderate_description = $rowModerate[1];
    $message .= $vocab["the_user"].affiche_nom_prenom_email($user_login,"","formail");
    $message .= $vocab["traite_moderation"];
    $message .= $vocab["the_room"].$room_name." (".$area_name.") \n";
    $message .= $vocab["reservee au nom de"];
    $message .= $vocab["the_user"].affiche_nom_prenom_email($beneficiaire,$beneficiaire_ext,"formail")." \n";
    if ($moderate_decision == 2)
      $message .= "\n".$vocab["moderation_acceptee"];
    else if ($moderate_decision == 3)
      $message .= "\n".$vocab["moderation_refusee"];
    if ($moderate_description != "")
    {
      $message .= "\n".$vocab["motif"].$vocab["deux_points"];
      $message .= $moderate_description." \n----";
    }
    $message .= "\n".$vocab["voir_details"].$vocab["deux_points"]."\n";
    if (count($tab_id_moderes) == 0 )
      $message .= "\n".traite_grr_url("","y")."view_entry.php?id=".$id_entry;
    else
    {
      foreach ($tab_id_moderes as $id_moderes)
        $message .= "\n".traite_grr_url("","y")."view_entry.php?id=".$id_moderes;
    }
    $message .= "\n\n".$vocab["rappel_de_la_demande"].$vocab["deux_points"]."\n";
    $repondre = $user_email;
  }
  elseif ($action == 7){ // Notification d'un retard dans la restitution d'une ressource
    $sujet .= $vocab["subject_mail_retard"];
    $message .= $vocab["message_mail_retard"].$vocab["deux_points"]." \n";
    $message .= $room_name." (".$area_name.") \n";
    $message .= $vocab["nom_emprunteur"].$vocab["deux_points"];
    $message .= affiche_nom_prenom_email($beneficiaire,$beneficiaire_ext,"formail")." \n";
    if ($beneficiaire_email != "")
      $message .= $vocab["un email envoye"].$beneficiaire_email." \n";
    $message .= "\n".$vocab["changer_statut_lorsque_ressource_restituee"].$vocab["deux_points"];
    $message .= "\n".traite_grr_url("","y")."view_entry.php?id=".$id_entry." \n";
    $repondre = Settings::get("webmaster_email");
  }
  //
  // Infos sur la réservation
  //    
  $destinataire_spec = '';
  $reservation = '';
  $reservation .= $vocab["start_of_the_booking"]." ".$start_date."\n";
  $reservation .= $vocab["duration"]." ".$duration." ".$dur_units."\n";
  if (trim($breve_description) != "")
    $reservation .= $vocab["namebooker"].preg_replace("/ /", " ",$vocab["deux_points"])." ".$breve_description."\n";
  else
    $reservation .= $vocab["entryid"].$room_id."\n";
  if ($description !='')
    $reservation .= $vocab["description"]." ".$description."\n";
  // Champs additionnels
  $reservation .= affichage_champ_add_mails($id_entry);
  $destinataire_spec .= envois_spec_champ_add_mails($id_entry);
  // Type de réservation
  $temp = grr_sql_query1("SELECT type_name FROM ".TABLE_PREFIX."_type_area WHERE type_letter= ? ","s",[$row[5]]);
  if ($temp == -1)
    $temp = "?".$row[5]."?";
  else
    $temp = removeMailUnicode($temp);
  $reservation .= $vocab["type"].preg_replace("/ /", " ",$vocab["deux_points"])." ".$temp."\n";
  if ($rep_type != 0)
  {
    $reservation .= $vocab["rep_type"]." ".$affiche_period."\n";
    if ($rep_type == 2)
    {
      $opt = "";
      for ($i = 0; $i < 7; $i++)
      {
        $daynum = ($i + $weekstarts) % 7;
        if ($rep_opt[$daynum])
          $opt .= day_name($daynum) . " ";
      }
      if ($opt)
        $reservation .= $vocab["rep_rep_day"]." ".$opt."\n";
    }
    if ($rep_type == 6)
    {
      if (Settings::get("jours_cycles_actif") == "Oui")
        $reservation .= $vocab["rep_type_6"].preg_replace("/ /", " ",$vocab["deux_points"]).ucfirst(substr($vocab["rep_type_6"],0,1)).$jours_cycle."\n";
    }
    $reservation .= $vocab["rep_end_date"]." ".$rep_end_date."\n";
  }
  if (($delais_option_reservation > 0) && ($option_reservation != -1))
    $reservation .= "*** ".$vocab["reservation_a_confirmer_au_plus_tard_le"]." ".time_date_string_jma($option_reservation,$dformat)." ***\n";
  $reservation .= "-----\n";
  $message .= $reservation;
  $message .= $vocab["msg_no_email"].Settings::get("webmaster_email");
  $message = html_entity_decode($message);
  $sql = "SELECT u.email FROM ".TABLE_PREFIX."_utilisateurs u JOIN ".TABLE_PREFIX."_j_mailuser_room j ON u.login=j.login WHERE (j.id_room= ? AND u.etat='actif') ORDER BY u.nom, u.prenom";
  $res = grr_sql_query($sql,"i",[$room_id]);
  $nombre = grr_sql_count($res);
  if ($nombre > 0)
  {
    $destinataire = "";
    $tab_destinataire = array();
    foreach($res as $row)
    {
      if ($row["email"] != "")
        $tab_destinataire[] = $row["email"];
    }
    foreach ($tab_destinataire as $value){
      $destinataire .= $value.";";
    }
    $destinataire = $destinataire .";". $destinataire_spec;
        if ($expediteur ==''){$expediteur = $repondre;}
    Email::Envois($destinataire, $sujet, $message, $expediteur, '', '', $repondre);
  }

  if ($action == 7){
    $mail_admin = find_user_room($room_id);
    $destinataire = "";
    if (count($mail_admin) > 0)
    {
      foreach ($mail_admin as $value){
        $destinataire .= $value.";";
      }
    }
    $sujet7 = $vocab["subject_mail1"].$room_name." - ".$date_avis;
    $sujet7 .= $vocab["subject_mail_retard"];
    $message7 = removeMailUnicode(Settings::get("company"))." - ".$vocab["title_mail"];
    $message7 .= traite_grr_url("","y")."\n\n";
    $message7 .= $vocab["ressource_empruntee_non_restituee"]."\n";
    $message7 .= $room_name." (".$area_name.")";
    $message7 .= "\n".$reservation;
    $message7 = html_entity_decode($message7);
    $destinataire7 = $beneficiaire_email.";". $destinataire_spec;
    $repondre7 = Settings::get("webmaster_email");
      if ($expediteur ==''){$expediteur = $repondre7;}
    Email::Envois($destinataire7, $sujet7, $message7, $expediteur, '', '', $repondre7);
  }
  if ($action == 4)
  {
    $destinataire4 = $beneficiaire_email;
    $repondre4 = Settings::get("webmaster_email");
    if ($expediteur ==''){$expediteur = $repondre;}
    Email::Envois($destinataire4, $sujet, $message, $expediteur, '', '', $repondre4);
  }
  if ($action == 5)
  {
    $mail_admin = find_active_user_room ($room_id);
    $destinataire = "";
    if (count($mail_admin) > 0){
      foreach ($mail_admin as $value){
        $destinataire .= $value.";";
      }
      $sujet5 = $vocab["subject_mail1"].$room_name." - ".$date_avis;
      $sujet5 .= $vocab["subject_mail_moderation"];
      $message5 = removeMailUnicode(Settings::get("company"))." - ".$vocab["title_mail"];
      $message5 .= traite_grr_url("","y")."\n\n";
      $message5 .= $vocab["subject_a_moderer"];
      $message5 .= "\n".traite_grr_url("","y")."validation.php?id=".$id_entry;
      $message5 .= "\n\n".$vocab['created_by'].affiche_nom_prenom_email($user_login,"","formail");
      $message5 .= "\n".$vocab['room'].$vocab['deux_points'].$room_name." (".$area_name.") \n";
      $message5 .= "\n".affichage_champ_add_mails($id_entry)."\n";
      $message5 = html_entity_decode($message5);
      $repondre5 = Settings::get("webmaster_email");
      if ($expediteur ==''){$expediteur = $repondre5;}
      Email::Envois($destinataire, $sujet5, $message5, $expediteur, '', '', $repondre5);
    }
  }
  if (($action == 5) && ($beneficiaire_email != '') && ($beneficiaire_actif == 'actif'))
  {
    $sujet5 = $vocab["subject_mail1"].$room_name." - ".$date_avis;
    $sujet5 .= $vocab["subject_mail_moderation"];
    $message5 = removeMailUnicode(Settings::get("company"))." - ".$vocab["title_mail"];
    $message5 .= traite_grr_url("","y")."\n\n";
    $message5 .= $vocab["texte_en_attente_de_moderation"];
    $message5 .= "\n".$vocab["rappel_de_la_demande"].$vocab["deux_points"];
    $message5 .= "\n".$vocab["the_room"].$room_name." (".$area_name.")";
    $message5 .= "\n".$reservation;
    $message5 = html_entity_decode($message5);
    $destinataire5 = $beneficiaire_email;
    $repondre5 = Settings::get("webmaster_email");
    if ($expediteur ==''){$expediteur = $repondre5;}
    Email::Envois($destinataire5, $sujet5, $message5, $expediteur, '', '', $repondre5);
  }
  if (($action == 6) && ($beneficiaire_email != '') && ($beneficiaire_actif=='actif'))
  {
    $sujet6 = $vocab["subject_mail1"].$room_name." - ".$date_avis;
    $sujet6 .= $vocab["subject_mail_decision_moderation"];
    $message6 = $message;
    $destinataire6 = $beneficiaire_email;
    $repondre6 = $user_email;
      if ($expediteur ==''){$expediteur = $repondre6;}
    Email::Envois($destinataire6, $sujet6, $message6, $expediteur, '', '', $repondre6);
  }
  // Cas d'une création, modification ou suppression d'un message par un utilisateur différent du bénéficiaire :
  // On envoie un message au bénéficiaire de la réservation pour l'avertir d'une modif ou d'une suppression
  if ((($action == 1) || ($action == 2) || ($action == 3)) && ((strtolower($user_login) != strtolower($beneficiaire)) || (Settings::get('send_always_mail_to_creator') == '1')) && ($beneficiaire_email != '') && ($beneficiaire_actif == 'actif'))
  {
    $sujet2 = $vocab["subject_mail1"].$room_name." - ".$date_avis;
    $message2 = removeMailUnicode(Settings::get("company"))." - ".$vocab["title_mail"];
    $message2 .= traite_grr_url("","y")."\n\n";
      if(strtolower($user_login) != strtolower($beneficiaire))
        $message2 .= $vocab["the_user"].affiche_nom_prenom_email($user_login,"","formail");
    if ($action == 1){
      $sujet2 .= $vocab["subject_mail_creation"];
      if(strtolower($user_login) != strtolower($beneficiaire))
        $message2 .= $vocab["creation_booking_for_you"];
      else
        $message2 .= get_vocab('Vous_avez_reserve');
      $message2 .= $vocab["the_room"].$room_name." (".$area_name.").";
    }
    elseif ($action == 2){
      $sujet2 .= $vocab["subject_mail_modify"];
      if(strtolower($user_login) != strtolower($beneficiaire))
        $message2 .= $vocab["modify_booking"];
      else
        $message2 .= get_vocab('Vous_avez_modifie');
      if ($room_id != $oldRessource)
        $message2 .= $vocab["the_room"]." ".$nomAncienneSalle." => ".$room_name." (".$area_name.") \n";
      else
        $message2 .= $vocab["the_room"].$room_name." (".$area_name.") \n";
      $message2 .= $vocab["created_by_you"];
    }
    else{
      $sujet2 .= $vocab["subject_mail_delete"];
      if(strtolower($user_login) != strtolower($beneficiaire))
        $message2 .= $vocab["delete_booking"];
      else
        $message2 .= get_vocab('Vous_avez_supprime');
      $message2 .= $vocab["the_room"].$room_name." (".$area_name.") \n";
      $message2 .= $vocab["created_by_you"];
    }
    $message2 .= "\n".$reservation;
    $message2 = html_entity_decode($message2);
    $destinataire2 = $beneficiaire_email.";".$destinataire_spec;
    $repondre2 = $user_email;
      if ($expediteur ==''){$expediteur = $repondre2;}
    Email::Envois($destinataire2, $sujet2, $message2, $expediteur, '', '', $repondre2);
  }
  // Cas d'une réservation modérée : le bénéficiaire peut éventuellement la supprimer, mais on prévient le modérateur
  if (($action == 3)&&($moderate >0)){
    $sql = "SELECT email FROM ".TABLE_PREFIX."_utilisateurs u JOIN ".TABLE_PREFIX."_entry_moderate e ON e.login_moderateur = u.login WHERE e.id = ? ";
    $mail_modo = grr_sql_query1($sql,"i",[$id_entry]);
    if (($mail_modo != -1)&&($mail_modo != '')){// on a le mail du modérateur
      $sujet2 .= $vocab["subject_mail_delete"];
      $message2 .= $vocab["delete_booking"];
      $message2 .= $vocab["the_room"].$room_name." (".$area_name.") \n";
      $message2 .= "\n".$reservation;
      $message2 = html_entity_decode($message2);
      $destinataire2 = $mail_modo;
      $repondre2 = $user_email;
      if (!isset($expediteur)||($expediteur =='')){$expediteur = $repondre2;}
      Email::Envois($destinataire2, $sujet2, $message2, $expediteur, '', '', $repondre2);
    }
  }
  return $message_erreur;
} // Fin fonction send_mail

/**
 * Fonction qui compare 2 valeurs
 * @param integer $a
 * @param integer $b
 * @return string
 */
function cmp3($a, $b)
{
  if ($a < $b)
    return "< ";
  if ($a == $b)
    return "= ";
  return "> ";
}
/**
 * @param string $string
 * @return string
 */
function removeMailUnicode($string)
{
  global $unicode_encoding, $charset_html;
  if ($unicode_encoding)
    return @iconv("utf-8", $charset_html, $string);
  else
    return $string;
}
function decode_options($a,$modele){
    // suppose que l'on a une chaîne $a de {V,F} de longueur égale à celle du $modele
    // renvoie un tableau de booléens True, False indexé par les valeurs du modèle
    $choix = array();
    $l = count($modele);
    for($i=0; $i<$l; $i++){
        $choix[$modele[$i]] = ((isset($a))&&('V' == $a[$i]))? TRUE: FALSE;
    }
    return $choix;
}
/** validate_email ($email)
 * Détermine si l'adresse mail en paramètre est syntaxiquement valable
 * Rend un booléen
*/
function validate_email($email)
{
  $atom   = '[-a-z0-9!#$%&\'*+\\/=?^_`{|}~]';
    // caractères autorisés avant l'arobase
  $domain = '([a-z0-9]([-a-z0-9]*[a-z0-9]+)?)';
    // caractères autorisés après l'arobase (nom de domaine)
  $regex = '/^' . $atom . '+' . '(\.' . $atom . '+)*' . '@' . '(' . $domain . '{1,63}\.)+' . $domain . '{2,63}$/i';
  if (preg_match($regex, $email))
    return true;
  else {
    $regex2 = '/^' . $atom . '+' . '(\.' . $atom . '+)*' . '@' . 'localhost/i';
    return preg_match($regex2, $email);
  }
}
/** filter_multi_emails($email_sequence)
 * extrait de la suite $email_sequence les adresses mail valables et les range dans une suite séparée par des ;
*/
function filter_multi_emails($es){
  $ea = explode(';',$es);
  $out = "";
  foreach($ea as $addr){
    if (validate_email($addr))
      $out.=$addr.";";
  }
  $out = rtrim($out,";");
  return $out;
}
/**
 * @param integer $time
 */
function est_hors_reservation($time,$area="-1")
{
  // Premier test : s'agit-il d'un jour du calendrier "hors réservation" ?
  $test = grr_sql_query1("SELECT DAY FROM ".TABLE_PREFIX."_calendar WHERE DAY = ? ","i",[$time]);
  if ($test != -1)
    return true;
  // 2ème test : s'agit-il d'une journée qui n'est pas affichée pour le domaine considéré ?
  if ($area!=-1)
  {
    $sql = "SELECT display_days FROM ".TABLE_PREFIX."_area WHERE id = ? ";
    $result = grr_sql_query1($sql,"i",[$area]);
    $jour_semaine = date("w",$time);
    if (substr($result,$jour_semaine,1) == 'n')
      return true;
  }
  return false;
}
/* Remove backslash-escape quoting if PHP is configured to do it with
 magic_quotes_gpc. Use this whenever you need the actual value of a GET/POST
 form parameter (which might have special characters) regardless of PHP's
 magic_quotes_gpc setting.*/
function unslashes($s)
{
  if (function_exists('get_magic_quotes_gpc') && @get_magic_quotes_gpc())
    return stripslashes($s);
  else
    return $s;
}
/* fonction qui rend -1 (interprétable comme TRUE) lorsque la date proposée est en dehors de la période réservable
*/
function check_begin_end_bookings($day, $month, $year)
{
  $date = mktime(0,0,0,$month,$day,$year);
  if (($date < Settings::get("begin_bookings")) || ($date > Settings::get("end_bookings")))
    return -1;
}
// Traite les données envoyées par la methode GET|POST de la variable $_GET|POST["page"], renvoie "day" si la page n'est pas définie
function verif_page()
{
  $pages = array("day", "week", "month", "week_all", "month_all", "month_all2", "year", "year_all");
  $page = (isset($_GET["page"]))? $_GET["page"]:((isset($_POST["page"]))? $_POST["page"]:NULL);
  if (isset($page))
  {
    if (in_array($page, $pages))
      return $page;
    else
      return "day";
  }
  else
    return "day";
}
// Corrige les caracteres degoutants utilises par les Windozeries
function corriger_caracteres($texte)
{
  // 145,146,180 = simple quote ; 147,148 = double quote ; 150,151 = tiret long
  $texte = strtr($texte, chr(145).chr(146).chr(180).chr(147).chr(148).chr(150).chr(151), "'''".'""--');
  return $texte;
}
/* Cette fonction vérifie une fois par jour si le délai de confirmation des réservations est dépassé
 * Si oui, les réservations concernées sont supprimées et un mail automatique est envoyé. */
function verify_confirm_reservation()
{
  global $dformat;
  $day   = date("d");
  $month = date("m");
  $year  = date("Y");
  $date_now = mktime(0,0,0,$month,$day,$year);
  if ((Settings::get("date_verify_reservation") == "") || (Settings::get("date_verify_reservation") < $date_now ))
  {
    $res = grr_sql_query("SELECT id FROM ".TABLE_PREFIX."_room WHERE delais_option_reservation > 0");
    if (!$res)
    {
      //fatal_error(0, grr_sql_error());
      include "trailer.inc.php";
      exit;
    }
    else
    {
      foreach($res as $row)
      {
        $res2 = grr_sql_query("SELECT id FROM ".TABLE_PREFIX."_entry WHERE option_reservation < ? AND option_reservation != '-1' AND room_id= ? ","ii",[$date_now,$row['id']]);
        if (!$res2)
        {
          //fatal_error(0, grr_sql_error());
          include "trailer.inc.php";
          exit;
        }
        else
        {
          foreach($res2 as $row2)
          {
            if (Settings::get("automatic_mail") == 'yes')
              $_SESSION['session_message_error'] = send_mail($row2['id'],4,$dformat);
            // On efface la réservation
            grr_sql_command("DELETE FROM ".TABLE_PREFIX."_entry WHERE id= ?","i",[$row2['id']]);
            // On efface le cas écheant également  dans ".TABLE_PREFIX."_entry_moderate
            grr_sql_command("DELETE FROM ".TABLE_PREFIX."_entry_moderate WHERE id= ?","i",[$row2['id']]);
          }
        }
      }
    }
    if (!Settings::set("date_verify_reservation", $date_now))
    {
      echo get_vocab('save_err')." date_verify_reservation !<br />";
      die();
    }
  }
}

function get_request_uri()
{
  global $grr_script_name;
  $RequestUri = "";
  if (isset($_SERVER['REQUEST_URI']))
    $RequestUri = $_SERVER['REQUEST_URI'];
  else if (isset($_ENV['REQUEST_URI']))
    $RequestUri = $_ENV['REQUEST_URI'];
  else if (isset($_SERVER['HTTP_X_REWRITE_URL']))
    $RequestUri = $_SERVER['HTTP_X_REWRITE_URL'];
  else
  {
    if (!isset($_SERVER['QUERY_STRING']))
      $_SERVER['QUERY_STRING'] = "";
    if ((Settings::get("use_grr_url") == "y") && (Settings::get("grr_url") != ""))
    {
      if (substr(Settings::get("grr_url"), -1) != "/")
        $ad_signe = "/";
      else
        $ad_signe = "";
      $RequestUri = Settings::get("grr_url").$ad_signe.$grr_script_name.$_SERVER['QUERY_STRING'];
    }
    else
    {
      if (isset($_SERVER['PHP_SELF']))
        $RequestUri = $_SERVER['PHP_SELF'].$_SERVER['QUERY_STRING'];
    }
  }
  return $RequestUri;
}

function verif_version()
{
  global $version_grr, $version_grr_RC;
  $_version_grr = $version_grr;
  $_version_grr_RC = $version_grr_RC;
  $version_old = Settings::get("version");
  $versionRC_old = Settings::get("versionRC");
  if ($versionRC_old == "")
    $versionRC_old = 9;
  if ($_version_grr_RC == "")
    $_version_grr_RC = 9;
  if (($version_old == '') || ($_version_grr > $version_old) || (($_version_grr == $version_old) && ($_version_grr_RC > $versionRC_old)))
    return true;
  else
    return false;
}
/*
Retourne un tableau contenant les nom et prénom et l'email de $_beneficiaire
*/
function donne_nom_email($_beneficiaire)
{
  $tab_benef = array();
  $tab_benef["nom"] = "";
  $tab_benef["email"] = "";
  if ($_beneficiaire == "")
    return $tab_benef;
  $temp = explode("|",$_beneficiaire);
  if (isset($temp[0]))
    $tab_benef["nom"] = $temp[0];
  if (isset($temp[1]))
    $tab_benef["email"] = $temp[1];
  return $tab_benef;
}
/*
Retourne une chaine concaténée des nom et prénom et l'email
*/
function concat_nom_email($_nom, $_email)
{
  // On supprime les caractères | de $_nom
  $_nom = trim(str_replace("|","",$_nom));
  if ($_nom == "")
    return "-1";
  $_email = trim($_email);
  if ($_email != "")
  {
    if (strstr($_email,"|"))
      return "-2";
  }
  $chaine = $_nom."|".$_email;
  return $chaine;
}
/** function iptobin($ip)
 * paramètre : une adresse iP v4 ou v6 supposée valable
 * rend : une chaîne de 0 ou 1 codant l'adresse sur 32 ou 128 digits 
 */
function iptobin($ip){
  $hex = unpack("H*", inet_pton($ip));
  $out = "";
  foreach(str_split($hex[1]) as $char)
    $out = $out.str_pad( base_convert($char,16,2),4,"0",STR_PAD_LEFT );
  return $out;
}
/** function compare_ip_adr($ip1, $ips2)
 * paramètres : 
 *   $ip1 : une adresse iP
 *   $ips2 : une liste d'adresses iP ou de plages au format CIDR séparées par des points-virgules
 * rend :
 *   TRUE ou FALSE 
 * teste si l'adresse $ip1 est dans la liste $ips2 ou est dans l'une des plages de $ips2
 * n'a pas été testé avec les plages d'IPv6
 */
function compare_ip_adr($ip1, $ips2)
{
  $ipCorrespondante = false;
  $ip2 = explode(';', $ips2);
  $resultIP = in_array($ip1,$ip2,true); // teste si l'adresse est dans la liste
  if(!$resultIP){ // cherche si l'adresse est dans une plage CIDR p.ex. 192.168.1.0/24 --> 192.168.1.0 à 192.168.1.255
    foreach ($ip2 as $ip){
      $slash = strpos($ip,'/');
      if ($slash !== false){ // $ip2 est une plage CIDR
        list($net,$mask) = preg_split("~/~",$ip);
        $binnet=iptobin($net);
        $firstpart=substr($binnet,0,(int)$mask);
        $binip=iptobin($ip1);
        $firstip=substr($binip,0,(int)$mask);
        $resultIP = (strcmp($firstpart,$firstip)==0);
      }
      if ($resultIP){
        $ipCorrespondante = true;
        break;
      }
    }
  }
  else{
    $ipCorrespondante = true;
  }
  return $ipCorrespondante;
}

function grrCheckRangeQuota($user,$id_room,$start_time,$end_time,$booking_range,$max_booking_on_range,$id,$reps){
  if ($booking_range > 0 ){
    $this_day = date("d",$start_time);
    $this_month = date("m",$start_time);
    $this_year = date("Y",$start_time);
    // mktime(0,0,0,$this_month,$this_day,$this_year); le jour de début de la réservation 
    for($k=1;$k<=$booking_range;$k++){
      $min_int = mktime(0,0,0,$this_month,$this_day+$k-$booking_range,$this_year);
      $max_int = mktime(0,0,0,$this_month,$this_day+$k,$this_year);
      $sql = "SELECT count(id) FROM ".TABLE_PREFIX."_entry WHERE (
      room_id = '".protect_data_sql($id_room)."'
      AND beneficiaire = '".protect_data_sql($user)."'
      AND end_time > '".$min_int."'
      AND start_time < '".$max_int;
      if($id > 0)// réservation à ne pas compter
        $sql .= "' AND id <> '".$id;
      $sql .= "')";
      $nb_bookings = grr_sql_query1($sql);
      if(empty($reps)) // ce n'est pas une série
        $nb_bookings += 1; 
      else{ // cas d'une série
        $b = array_filter($reps, function($t){global $min_int,$max_int; return ($t >= $min_int) && ($t <= $max_int);});
        $nb_bookings += count($b);
      }
      if ($nb_bookings > $max_booking_on_range)
        return FALSE;
      else return TRUE;
    }
  }
  elseif($booking_range == 0)
    return FALSE;
  else 
    return TRUE;
}
/* function no_book_rooms($user)
* détermine les ressources (rooms) dans lesquelles $user ne peut pas réserver (droits insuffisants ou ressource restreinte)
* renvoie un tableau des indices des ressources inaccessibles
*/
function no_book_rooms($user){
  $rooms_no_book = array();
  $sql = "SELECT id,who_can_see,who_can_book FROM ".TABLE_PREFIX."_room";
  $rooms = grr_sql_query($sql);
  if (!$rooms)
    fatal_error(0,grr_sql_error());
  foreach($rooms as $room){
    $auth_level = authGetUserLevel($user,$room['id']);
    if ($auth_level < $room['who_can_see'])
      $rooms_no_book[] = $room['id'];
    elseif (!$room['who_can_book']){ // ressource restreinte
      $sql = "SELECT login FROM ".TABLE_PREFIX."_j_userbook_room j WHERE j.login = ? AND j.id_room = ? ";
      $login = grr_sql_query1($sql,"si",[$user,$room['id']]);
      if ((strtoupper($login) != strtoupper($user)) && ($auth_level < 3)){ // un gestionnaire de ressource peut toujours accéder !
        $rooms_no_book[] = $room['id'];
      }
    }
  }
  return $rooms_no_book;
}
// trouve les mails des utilisateurs gestionnaires de ressource
function find_user_room($id_room)
{
  $emails = array ();
  $sql = "SELECT email FROM ".TABLE_PREFIX."_utilisateurs u JOIN ".TABLE_PREFIX."_j_user_room j ON u.login = j.login
  WHERE id_room= ? ";
  $res = grr_sql_query($sql,"i",[$id_room]);
  if ($res)
  {
    foreach($res as $row)
    {
      if (validate_email($row['email']))
        $emails[] = $row['email'];
    }
  }
  // Si la table des emails des gestionnaires de la ressource est vide, on avertit les administrateurs du domaine
  if (count($emails) == 0)
  {
    $id_area = mrbsGetRoomArea($id_room);
    $sql_admin = grr_sql_query("SELECT email FROM ".TABLE_PREFIX."_utilisateurs u JOIN ".TABLE_PREFIX."_j_useradmin_area j ON u.login = j.login
      WHERE j.id_area= ? ","i",[$id_area]);
    if ($sql_admin)
    {
      foreach($sql_admin as $row)
      {
        if (validate_email($row['email']))
          $emails[] = $row['email'];
      }
    }
  }
  // Si la table des emails des administrateurs du domaine est vide, on avertit les administrateurs des sites
  if (Settings::get("module_multisite") == "Oui")
  {
    if (count($emails) == 0)
    {
      $id_area = mrbsGetRoomArea($id_room);
      $id_site = mrbsGetAreaSite($id_area);
      $sql_admin = grr_sql_query("SELECT email FROM ".TABLE_PREFIX."_utilisateurs u JOIN ".TABLE_PREFIX."_j_useradmin_site j ON u.login = j.login
        WHERE j.id_site= ? ","i",[$id_site]);
      if ($sql_admin)
      {
        foreach($sql_admin as $row)
        {
          if (validate_email($row['email']))
            $emails[] = $row['email'];
        }
      }
    }
  }
  // Si la table des emails des administrateurs des sites est vide, on avertit les administrateurs généraux
  if (count($emails) == 0)
  {
    $sql_admin = grr_sql_query("SELECT email FROM ".TABLE_PREFIX."_utilisateurs WHERE statut = 'administrateur'");
    if ($sql_admin)
    {
      foreach($sql_admin as $row)
      {
        if (validate_email($row['email']))
          $emails[] = $row['email'];
      }
    }
  }
  return $emails;
}
// trouve les mails des utilisateurs actifs gestionnaires de ressource
function find_active_user_room($id_room)
{
  $emails = array ();
  $sql = "SELECT email FROM ".TABLE_PREFIX."_utilisateurs u JOIN ".TABLE_PREFIX."_j_user_room j ON u.login = j.login
  WHERE u.etat = 'actif' AND id_room= ? ";
  $res = grr_sql_query($sql,"i",[$id_room]);
  if ($res)
  {
    foreach($res as $row)
    {
      if (validate_email($row['email']))
        $emails[] = $row['email'];
    }
  }
  // Si la table des emails des gestionnaires de la ressource est vide, on avertit les administrateurs du domaine
  if (count($emails) == 0)
  {
    $id_area = mrbsGetRoomArea($id_room);
    $sql_admin = grr_sql_query("SELECT email FROM ".TABLE_PREFIX."_utilisateurs u JOIN ".TABLE_PREFIX."_j_useradmin_area j ON u.logi, = j.login
    WHERE u.etat = 'actif' AND j.id_area= ?","i",[$id_area]);
    if ($sql_admin)
    {
      foreach($sql_admin as $row)
      {
        if (validate_email($row['email']))
          $emails[] = $row['email'];
      }
    }
  }
  // Si la table des emails des administrateurs du domaines est vide, on avertit les administrateurs des sites
  if (Settings::get("module_multisite") == "Oui")
  {
    if (count($emails) == 0)
    {
      $id_area = mrbsGetRoomArea($id_room);
      $id_site = mrbsGetAreaSite($id_area);
      $sql_admin = grr_sql_query("SELECT email FROM ".TABLE_PREFIX."_utilisateurs u JOIN ".TABLE_PREFIX."_j_useradmin_site j ON u.login=j.login
      WHERE u.etat = 'actif' AND j.id_site=?","i",[$id_site]);
      if ($sql_admin)
      {
        foreach($sql_admin as $row)
        {
          if (validate_email($row['email']))
            $emails[] = $row['email'];
        }
      }
    }
  }
  // Si la table des emails des administrateurs des sites est vide, on avertit les administrateurs généraux
  if (count($emails) == 0)
  {
    $sql_admin = grr_sql_query("SELECT email FROM ".TABLE_PREFIX."_utilisateurs WHERE etat = 'actif' AND statut = 'administrateur'");
    if ($sql_admin)
    {
      foreach($sql_admin as $row)
      {
        if (validate_email($row['email']))
          $emails[] = $row['email'];
      }
    }
  }
  return $emails;
}
/* MajMysqlModeDemo()
 * dans le cas le mode demo est activé :
 * Met à jour la base mysql une fois par jour, lors de la première connexion
 */
function MajMysqlModeDemo() {
    // Nom du fichier sql à exécuter
  $fic_sql = "grr_maj_quotidienne.sql";
  if ((Settings::get("ActiveModeDemo") == 'y') && (file_exists($fic_sql)))
  {
    $date_now = mktime(0,0,0,date("m"),date("d"),date("Y"));
    if ((Settings::get("date_verify_demo") == "") || (Settings::get("date_verify_demo") < $date_now))
    {
      $fd = fopen($fic_sql, "r");
      while (!feof($fd))
      {
        $query = fgets($fd, 5000);
        $query = trim($query);
        if ($query != '')
          @mysqli_query($GLOBALS['db_c'], $query);
      }
      fclose($fd);
      if (!Settings::set("date_verify_demo", $date_now))
      {
        echo get_vocab('save_err')." date_verify_demo !<br />";
        die();
      }
    }
  }
}
// Teste si la requête est un appel par AJAX
function isAjax()
{
  return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
          (utf8_strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'));
}
/** NettoyerTablesJointure()
 * Supprime les lignes inutiles dans les tables de liaison
 */
function NettoyerTablesJointure()
{
  $nb = 0;
  // Table grr_j_mailuser_room
  $req = "SELECT j.login FROM ".TABLE_PREFIX."_j_mailuser_room j
  LEFT JOIN ".TABLE_PREFIX."_utilisateurs u ON u.login=j.login
  WHERE (u.login  IS NULL)";
  $res = grr_sql_query($req);
  if($res)
  {
    foreach($res as $row)
    {
      $nb++;
      grr_sql_command("delete FROM ".TABLE_PREFIX."_j_mailuser_room WHERE login= ?","s",[$row['login']]);
    }
  }
  // Table grr_j_user_area
  $req = "SELECT j.login FROM ".TABLE_PREFIX."_j_user_area j
  LEFT JOIN ".TABLE_PREFIX."_utilisateurs u on u.login=j.login
  WHERE (u.login  IS NULL)";
  $res = grr_sql_query($req);
  if ($res)
  {
    foreach($res as $row)
    {
      $nb++;
      grr_sql_command("delete FROM ".TABLE_PREFIX."_j_user_area WHERE login= ?","s",[$row['login']]);
    }
  }
  // Table grr_j_user_room
  $req = "SELECT j.login FROM ".TABLE_PREFIX."_j_user_room j
  LEFT JOIN ".TABLE_PREFIX."_utilisateurs u on u.login=j.login
  WHERE (u.login  IS NULL)";
  $res = grr_sql_query($req);
  if ($res)
  {
    foreach($res as $row)
    {
      $nb++;
      grr_sql_command("DELETE FROM ".TABLE_PREFIX."_j_user_room WHERE login= ?","s",[$row['login']]);
    }
  }
  // Table grr_j_useradmin_area
  $req = "SELECT j.login FROM ".TABLE_PREFIX."_j_useradmin_area j
  LEFT JOIN ".TABLE_PREFIX."_utilisateurs u on u.login=j.login
  WHERE (u.login  IS NULL)";
  $res = grr_sql_query($req);
  if ($res)
  {
    foreach($res as $row)
    {
      $nb++;
      grr_sql_command("DELETE FROM ".TABLE_PREFIX."_j_useradmin_area WHERE login= ?","s",[$row['login']]);
    }
  }
  // Table grr_j_useradmin_site
  $req = "SELECT j.login FROM ".TABLE_PREFIX."_j_useradmin_site j
  LEFT JOIN ".TABLE_PREFIX."_utilisateurs u on u.login=j.login
  WHERE (u.login  IS NULL)";
  $res = grr_sql_query($req);
  if ($res)
  {
    foreach($res as $row)
    {
      $nb++;
      grr_sql_command("DELETE FROM ".TABLE_PREFIX."_j_useradmin_site WHERE login=?","s",[$row['login']]);
    }
  }
  // Suppression effective
  echo "<hr />\n";
  echo "<p class='avertissement'>".get_vocab("tables_liaison").get_vocab("deux_points").$nb.get_vocab("entres_supprimees")."</p>\n";
}
/*
 Fonction permettant d'effectuer une correspondance entre
 le profil lu sous LDAP et les statuts existants dans GRR
*/
function effectuer_correspondance_profil_statut($codefonction, $libellefonction) {
  # On récupère le statut par défaut des utilisateurs CAS
  $sso = Settings::get("sso_statut");
  if ($sso == "cas_visiteur")
    $_statut = "visiteur";
  else if ($sso == "cas_utilisateur")
    $_statut = "utilisateur";
  # Le code fonction est défini
  if ($codefonction != "")
  {
    $sql = grr_sql_query1("SELECT statut_grr FROM ".TABLE_PREFIX."_correspondance_statut WHERE code_fonction=?","s",[$codefonction]);
    if ($sql != -1)
    {
      // Si la fonction existe dans la table de correspondance, on retourne le statut_grr associé
      return $sql;
    }
    else
    {
      // Le code n'existe pas dans la base, alors on l'insère en lui attribuant le statut par défaut.
      $libellefonction = protect_data_sql($libellefonction);
      grr_sql_command("INSERT INTO grr_correspondance_statut(code_fonction,libelle_fonction,statut_grr) VALUES ('$codefonction', '$libellefonction', '$_statut')");
      return $_statut;
    }
  }
  else //Le code fonction n'est pas défini, alors on retourne le statut par défaut.
    return $_statut;
}
/*
Destinaire specifique champ additionnel
*/
function envois_spec_champ_add_mails($id_resa)
{
  $destinataire = "";
  // Les champs add :
  $overload_data = mrbsEntryGetOverloadDesc($id_resa);
  foreach ($overload_data as $fieldname=>$field)
  {
    if (isset($field["mail_spec"]) && ($field["mail_spec"] != '') && ($field["valeur"] != "") && ($field["valeur"] != 0))
      $destinataire .= htmlspecialchars($field["mail_spec"]).";";
  }
  return $destinataire;
}
/* Pour les Jours/Cycles
 * Crée le calendrier Jours/Cycles */
function cree_calendrier_date_valide($n, $i)
{
  if ($i <= Settings::get("nombre_jours_Jours_Cycles"))
  {
    $sql = "INSERT INTO ".TABLE_PREFIX."_calendrier_jours_cycle SET DAY=?, Jours = ?";
    if (grr_sql_command($sql,"ii",[$n,$i]) < 0)
      fatal_error(1, "<p>" . grr_sql_error());
    $i++;
  }
  else
  {
    $i = 1;
    $sql = "INSERT INTO ".TABLE_PREFIX."_calendrier_jours_cycle set DAY=?, Jours = ?";
    if (grr_sql_command($sql,"ii",[$n,$i]) < 0)
      fatal_error(1, "<p>" . grr_sql_error());
    $i++;
  }
  return $i;
}
/*
* @param integer $delai : nombre de jours de rétention des logs de connexion
* nettoieLogConnexion efface les entrées de la table _log antérieures au jour courant moins le délai
*/
function nettoieLogConnexion($delai){
  // est-ce un administrateur ?
  if (authGetUserLevel(getUserName(), -1) >= 6){
    $dateMax = new DateTime('NOW');
    $dateMax->sub(new DateInterval('P'.$delai.'D'));
    $dateMax = $dateMax->format('Y-m-d H:i:s');
    $sql = "DELETE FROM ".TABLE_PREFIX."_log WHERE START < '" . $dateMax . "';";
    grr_sql_query($sql);
  }
}

// sécurité
/* Traite les données avant insertion dans une requête SQL
 * mise à jour pour les versions de php 5.6+
*/
function protect_data_sql($_value)
{
  if ($_value != NULL){
        $_value = stripslashes($_value);
        $_value = mysqli_real_escape_string($GLOBALS['db_c'], $_value);
    }
    return $_value;
}
/*authGetUserLevel($user,$id,$type)
 * Determine le niveau d'accès de l'utilisateur
 * $user - l'identifiant de l'utilisateur
 * $id -   l'identifiant de la ressource ou du domaine
 * $type - argument optionnel : 
 *  'room' (par défaut) si $id désigne une ressource, 
 *  'area' si $id désigne un domaine, 
 *  'site' si $id désigne un site,
 *  'user' si on cherche un gestionnaire d'utilisateurs.
 * Retourne le niveau d'accès de l'utilisateur :
 *  0 NC / 1 Visiteur / 2 Utilisateur / 3 gestionnaire de ressource / 4 administrateur de domaine / 5 administrateur de site / 6 Admin général
*/
function authGetUserLevel($user, $id, $type = 'room')
{
  //user level '0': User not logged in, or User value is NULL (getUserName()='')
  if (!isset($user) || ($user == ''))
    return 0;
  // On vient lire le statut de l'utilisateur courant dans la database
  $sql = "SELECT statut FROM ".TABLE_PREFIX."_utilisateurs WHERE login=? AND etat='actif'";
  $res = grr_sql_query($sql,"s",[protect_data_sql($user)]);
  $nbraw = grr_sql_count($res);
  //user level '0': User not defined in database
  if (!$res || $nbraw == 0)
    return 0;
  //user level '0': Same User defined multiple time in database !!!
  if ($nbraw > 1)
    return 0;
  // On vient lire le résultat de la requète
  $status = grr_sql_row($res,$nbraw-1);
  $statut = strtolower($status[0]);
  // Teste si le type concerne la gestion des utilisateurs
  if ($type === 'user')
  {
    if ($statut == 'gestionnaire_utilisateur')
      return 1;
    else
      return 0;
  }
  else { // ressource, domaine ou site
    switch ($statut)
    {
      case 'visiteur':
      return 1;
      case 'administrateur':
      return 6;
      default:
      break;
    }
    if (($statut == 'utilisateur') || ($statut == 'gestionnaire_utilisateur'))
    {
      if ($type == 'room')
      {
        // On regarde si l'utilisateur est administrateur du site auquel la ressource $id appartient
        // calcul de l'id du domaine
        $id_area = grr_sql_query1("SELECT area_id FROM ".TABLE_PREFIX."_room WHERE id=?","i",[$id]);
        // calcul de l'id du site
        $id_site = grr_sql_query1("SELECT id_site FROM ".TABLE_PREFIX."_j_site_area  WHERE id_area=?","i",[$id_area]);
        if (Settings::get("module_multisite") == "Oui")
        {
          $res3 = grr_sql_query("SELECT login FROM ".TABLE_PREFIX."_j_useradmin_site j WHERE j.id_site=? AND j.login=?","is",[$id_site,protect_data_sql($user)]);
          if (grr_sql_count($res3) > 0)
          {
            grr_sql_free($res3);
            return 5;
          }
        }
        // On regarde si l'utilisateur est administrateur du domaine auquel la ressource $id appartient
        $res3 = grr_sql_query("SELECT u.login FROM ".TABLE_PREFIX."_utilisateurs u JOIN ".TABLE_PREFIX."_j_useradmin_area j ON u.login = j.login
              WHERE (j.id_area=? AND u.login=?)","is",[$id_area,protect_data_sql($user)]);
        if (grr_sql_count($res3) > 0)
          return 4;
        // On regarde si l'utilisateur est gestionnaire des réservations pour une ressource
        $str_res2 = "SELECT * FROM ".TABLE_PREFIX."_utilisateurs u JOIN ".TABLE_PREFIX."_j_user_room j ON u.login=j.login
        WHERE u.login='".protect_data_sql($user)."' ";
        if ($id!=-1)
          $str_res2.="AND j.id_room='".protect_data_sql($id)."'";
        $res2 = grr_sql_query($str_res2);
        if (grr_sql_count($res2) > 0)
          return 3;
        // Sinon il s'agit d'un simple utilisateur
        return 2;
      }
      // On regarde si l'utilisateur est administrateur d'un domaine
      if ($type == 'area')
      {
        if ($id == '-1')
        {
          if (Settings::get("module_multisite") == "Oui")
          {
          //On regarde si l'utilisateur est administrateur d'un site quelconque
            $res2 = grr_sql_query("SELECT u.login FROM ".TABLE_PREFIX."_utilisateurs u JOIN ".TABLE_PREFIX."_j_useradmin_site j ON u.login = j.login
              WHERE u.login=?","s",[protect_data_sql($user)]);
            if (grr_sql_count($res2) > 0)
              return 5;
          }
          //On regarde si l'utilisateur est administrateur d'un domaine quelconque
          $res2 = grr_sql_query("SELECT u.login FROM ".TABLE_PREFIX."_utilisateurs u JOIN ".TABLE_PREFIX."_j_useradmin_area j ON u.login = j.login
              WHERE u.login=?","s",[protect_data_sql($user)]);
          if (grr_sql_count($res2) > 0)
            return 4;
        }
        else
        {
          if (Settings::get("module_multisite") == "Oui")
          {
          // On regarde si l'utilisateur est administrateur du site auquel le domaine $id appartient
            $id_site = grr_sql_query1("SELECT id_site FROM ".TABLE_PREFIX."_j_site_area  WHERE id_area=?","i",[$id]);
            $res3 = grr_sql_query("SELECT login FROM ".TABLE_PREFIX."_j_useradmin_site j WHERE j.id_site=? AND j.login=?","is",[$id_site,protect_data_sql($user)]);
            if (grr_sql_count($res3) > 0)
              return 5;
          }
          //On regarde si l'utilisateur est administrateur du domaine dont l'id est $id
          $res3 = grr_sql_query("SELECT u.login FROM ".TABLE_PREFIX."_utilisateurs u JOIN ".TABLE_PREFIX."_j_useradmin_area j ON u.login=j.login
              WHERE (j.id_area=? AND u.login=?)","is",[$id,protect_data_sql($user)]);
          if (grr_sql_count($res3) > 0)
            return 4;
        }
        // Sinon il s'agit d'un simple utilisateur
        return 2;
      }
      // On regarde si l'utilisateur est administrateur d'un site
      if (($type == 'site') and (Settings::get("module_multisite") == "Oui"))
      {
        if ($id == '-1')
        {
          //On regarde si l'utilisateur est administrateur d'un site quelconque
          $res2 = grr_sql_query("SELECT u.login FROM ".TABLE_PREFIX."_utilisateurs u JOIN ".TABLE_PREFIX."_j_useradmin_site j ON u.login=j.login
              WHERE u.login=?","s",[protect_data_sql($user)]);
          if (grr_sql_count($res2) > 0)
            return 5;
        }
        else
        {
          //On regarde si l'utilisateur est administrateur du site dont l'id est $id
          $res3 = grr_sql_query("SELECT u.login FROM ".TABLE_PREFIX."_utilisateurs u JOIN ".TABLE_PREFIX."_j_useradmin_site j ON u.login=j.login
              WHERE j.id_site=? AND u.login=?","is",[$id,protect_data_sql($user)]);
          if (grr_sql_count($res3) > 0)
            return 5;
        }
        // Sinon il s'agit d'un simple utilisateur
        return 2;
      }
    }
  }
}
/* function verif_acces_ressource : vérifier l'accès à la ressource
 * $user : le login de l'utilisateur
 * $id_room : l'id de la ressource ou 'all'
 * si $id_room est entier, renvoie le booléen indiquant si la ressource est accessible
 * si $id_room est 'all', renvoie le tableau des ressources inaccessibles à $user
 */
function verif_acces_ressource($user, $id_room)
{
  if ($id_room != 'all')
  {
    $who_can_see = grr_sql_query1("SELECT who_can_see FROM ".TABLE_PREFIX."_room WHERE id=?","i",[$id_room]);
    if (authGetUserLevel($user,$id_room) >= $who_can_see)
      return true;
    else
      return false;
  }
  else
  {
    $tab_rooms_noaccess = array();
    $sql = "SELECT id, who_can_see FROM ".TABLE_PREFIX."_room";
    $res = grr_sql_query($sql);
    if (!$res)
      fatal_error(0, grr_sql_error());
    foreach($res as $row)
    {
      if (authGetUserLevel($user,$row['id']) < $row['who_can_see'])
        $tab_rooms_noaccess[] = $row['id'];
    }
    return $tab_rooms_noaccess;
  }
}
/**
 * Fonction de vérification d'accès 
 * @param int $level (niveau requis)
 */
function check_access($level, $back)
{
  if (authGetUserLevel(getUserName(), -1, 'area') < $level)
  {
    showAccessDenied($back);
    exit();
  }
}
/* authUserAccesArea($user,$id)
 * Determines if the user access area
 * $user - The user name
 * $id -   Which area are we checking
 */
function authUserAccesArea($user,$id)
{
  if ($id == '')
    return 0;
  $sql = "SELECT login FROM ".TABLE_PREFIX."_utilisateurs WHERE (login = ? AND statut='administrateur')";
  $res = grr_sql_query($sql,"s",[protect_data_sql($user)]);
  if (grr_sql_count($res) != "0")
    return 1;
  if (Settings::get("module_multisite") == "Oui")
  {
    $id_site = mrbsGetAreaSite($id);
    $sql = "SELECT login FROM ".TABLE_PREFIX."_j_useradmin_site j WHERE j.id_site=? AND j.login=?";
    $res = grr_sql_query($sql,"is",[$id_site,protect_data_sql($user)]);
    if (grr_sql_count($res) != "0")
      return 1;
  }
  $sql = "SELECT id FROM ".TABLE_PREFIX."_area WHERE (id = ? AND access='r')";
  $res = grr_sql_query($sql,"i",[$id]);
  $test = grr_sql_count($res);
  if ($test == "0")
    return 1;
  else
  {
    $sql2 = "SELECT login FROM ".TABLE_PREFIX."_j_user_area WHERE (login = ? and id_area = ?)";
    $res2 = grr_sql_query($sql2,"si",[protect_data_sql($user),$id]);
    $test2 = grr_sql_count($res2);
    if ($test2 != "0")
      return 1;
    else
      return 0;
  }
}
/* function UserRoomMaxBooking
 * Cette fonction teste si l'utilisateur a la possibilité d'effectuer une réservation, compte tenu
 * des limitations éventuelles de la ressource et du nombre de réservations déjà effectuées.
*/
function UserRoomMaxBooking($user, $id_room, $number)
{
  global $enable_periods,$id_room_autorise;
  $level = authGetUserLevel($user,$id_room);
  if ($id_room == '')
    return 0;
  if ($level >= 3)
    return 1;
  else if (($level == 1 ) &&  !((in_array($id_room,$id_room_autorise)) && ($id_room_autorise != "")))
    return 0;
  else if ($level  < 1 )
    return 0;
  // A ce niveau, l'utilisateur est simple utilisateur ou bien simple visiteur sur un domaine autorisé
  // On regarde si le nombre de réservation de la ressource est limité
  $max_booking_per_room = grr_sql_query1("SELECT max_booking FROM ".TABLE_PREFIX."_room WHERE id = ?","i",[$id_room]);
  // Calcul de l'id de l'area de la ressource.
  $id_area = mrbsGetRoomArea($id_room);
  // On regarde si le nombre de réservation du domaine est limité
  $max_booking_per_area = grr_sql_query1("SELECT max_booking FROM ".TABLE_PREFIX."_area WHERE id = ?","i",[$id_area]);
  // On regarde si le nombre de réservation pour l'ensemble des ressources est limité
  $max_booking = Settings::get("UserAllRoomsMaxBooking");
  // Si aucune limitation
  if (($max_booking_per_room < 0) && ($max_booking_per_area < 0) && ($max_booking < 0))
    return 1;
  // A ce niveau, il s'agit d'un utilisateur et il y a au moins une limitation
  $day   = date("d");
  $month = date("m");
  $year  = date("Y");
  $hour  = date("H");
  $minute = date("i");
  if ($enable_periods == 'y')
    $now = mktime(0, 0, 0, $month, $day, $year);
  else
    $now = mktime($hour, $minute, 0, $month, $day, $year);
  // y-a-t-il dépassement pour l'ensemble des ressources ?
  if ($max_booking > 0)
  {
    $nb_bookings = grr_sql_query1("SELECT count(id) FROM ".TABLE_PREFIX."_entry r WHERE (beneficiaire = ? and end_time > ?)","si",[protect_data_sql($user),$now]);
    $nb_bookings += $number;
    if ($nb_bookings > $max_booking)
      return 0;
  }
  else if ($max_booking == 0)
    return 0;
  // y-a-t-il dépassement pour l'ensemble des ressources du domaine ?
  if ($max_booking_per_area > 0)
  {
    $nb_bookings = grr_sql_query1("SELECT count(e.id) FROM ".TABLE_PREFIX."_entry e JOIN ".TABLE_PREFIX."_room r ON e.room_id=r.id WHERE (r.area_id=? AND e.beneficiaire = ? and e.end_time > ?)","isi",[$id_area,protect_data_sql($user),$now]);
    $nb_bookings += $number;
    if ($nb_bookings > $max_booking_per_area)
      return 0;
  }
  else if ($max_booking_per_area == 0)
    return 0;
  // y-a-t-il dépassement pour la ressource
  if ($max_booking_per_room > 0)
  {
    $nb_bookings = grr_sql_query1("SELECT count(id) FROM ".TABLE_PREFIX."_entry WHERE (room_id = ? and beneficiaire = ? and end_time > ?)","isi",[$id_room,protect_data_sql($user),$now]);
    $nb_bookings += $number;
    if ($nb_bookings > $max_booking_per_room)
      return 0;
  }
  else if ($max_booking_per_room == 0)
    return 0;
  // A ce stade, il s'agit d'un utilisateur et il n'y a pas eu de dépassement, ni pour l'ensemble des domaines, ni pour le domaine, ni pour la ressource
  return 1;
}
/* auth_visiteur($user,$id_room)
 * Determine si un visiteur peut réserver une ressource
 * $user - l'identifiant de l'utilisateur
 * $id_room -   l'identifiant de la ressource
 * Retourne 0 (accès refusé) ou 1 (accès autorisé)
*/
function auth_visiteur($user,$id_room)
{
  global $id_room_autorise;
  if ((!isset($user)) || (!isset($id_room)))
    return 0;
  $res = grr_sql_query("SELECT statut FROM ".TABLE_PREFIX."_utilisateurs WHERE login = ?","s",[protect_data_sql($user)]);
  if (!$res || grr_sql_count($res) == 0)
    return 0;
  $status = grr_sql_row($res,0);
  if (strtolower($status[0]) == 'visiteur')
  {
    if ((in_array($id_room,$id_room_autorise)) && ($id_room_autorise != ""))
      return 1;
    else
      return 0;
  }
  return 0;
}
/* function verif_booking_date($user, $id, $id_room, $date_booking, $date_now, $enable_periods, $endtime = '')
 $user : le login de l'utilisateur
 $id : l'id de la résa. Si -1, il s'agit d'une nouvelle réservation
 $id_room : id de la ressource
 @param string $date_booking : la date de la réservation (n'est utile que si $id=-1)
 @param integer $date_now : la date actuelle
*/
function verif_booking_date($user, $id, $id_room, $date_booking, $date_now, $enable_periods, $endtime = ''){
  global $correct_diff_time_local_serveur, $can_delete_or_create;
  $can_delete_or_create = "y";
  // On teste si l'utilisateur est administrateur
  $statut_user = grr_sql_query1("SELECT statut FROM ".TABLE_PREFIX."_utilisateurs WHERE login = ?","s",[protect_data_sql($user)]);
  if ($statut_user == 'administrateur')
    return true;
  // A-t-on le droit d'agir dans le passé ?
  $allow_action_in_past = grr_sql_query1("SELECT allow_action_in_past FROM ".TABLE_PREFIX."_room WHERE id = ?","i",[$id_room]);
  if ($allow_action_in_past == 'y')
    return true;
  // $user est-il gestionnaire de la ressource avec le droit de modification universel
  if((Settings::get('allow_gestionnaire_modify_del'))&&(authGetUserLevel($user,$id_room)>2))
    return TRUE;
  // Correction de l'avance en nombre d'heure du serveur sur les postes clients
  if ((isset($correct_diff_time_local_serveur)) && ($correct_diff_time_local_serveur!=0))
    $date_now -= 3600 * $correct_diff_time_local_serveur;
  // Créneaux basés sur les intitulés
  // Dans ce cas, on prend comme temps présent le jour même à minuit.
  // Cela signifie qu'il est possible de modifier/réserver/supprimer tout au long d'une journée
  // même si l'heure est passée.
  // Cela demande donc à être amélioré en introduisant pour chaque créneau une heure limite de réservation.
  if ($enable_periods == "y")
  {
    $month = date("m",$date_now);
    $day = date("d",$date_now);
    $year = date("Y",$date_now);
    $date_now = mktime(0, 0, 0, $month, $day, $year);
  }
  if ($id != -1)
  {
    // il s'agit de l'edition d'une réservation existante
    if (($endtime != '') && ($endtime < $date_now))
      return false;
    if ((Settings::get("allow_user_delete_after_begin") == 1) || (Settings::get("allow_user_delete_after_begin") == 2))
      $sql = "SELECT end_time FROM ".TABLE_PREFIX."_entry WHERE id = ?";
    else
      $sql = "SELECT start_time FROM ".TABLE_PREFIX."_entry WHERE id = ?";
    $date_booking = grr_sql_query1($sql,"i",[$id]);
    if ($date_booking < $date_now)
      return false;
    else
    {
      // dans le cas où le créneau est entamé, on teste si l'utilisateur a le droit de supprimer la réservation
      // Si oui, on transmet la variable $only_modify = true avant que la fonction de retourne true.???
      if (Settings::get("allow_user_delete_after_begin") == 2)
      {
        $date_debut = grr_sql_query1("SELECT start_time FROM ".TABLE_PREFIX."_entry WHERE id = ?","i",[id]);
        if ($date_debut < $date_now)
          $can_delete_or_create = "n";
        else
          $can_delete_or_create = "y";
      }
      return true;
    }
  }
  else
  {
    if (Settings::get("allow_user_delete_after_begin") == 1)
    {
      //$id_area = grr_sql_query1("SELECT area_id FROM ".TABLE_PREFIX."_room WHERE id = '".protect_data_sql($id_room)."'");
      $resolution_area = grr_sql_query1("SELECT a.resolution_area FROM ".TABLE_PREFIX."_area a JOIN ".TABLE_PREFIX."_room r ON r.area_id = a.id WHERE r.id = ?","i",[$id_room]);
      if ($date_booking > $date_now - $resolution_area)
        return true;
      return false;
    }
    else
    {
      if ($date_booking > $date_now)
        return true;
      return false;
    }
  }
}
/* function verif_acces_fiche_reservation : vérifier l'accès à la fiche de réservation d'une ressource
 * $user : le login de l'utilisateur
 * $id_room : l'id de la ressource.*/
function verif_acces_fiche_reservation($user, $id_room)
{
  if (authGetUserLevel($user,$id_room) >= Settings::get("acces_fiche_reservation"))
    return true;
  return false;
}
/* function authBooking($user,$room)
à utiliser avec une ressource restreinte : détermine si $user est autorisé à réserver dans $room
utilise la table grr_j_userbook_room
*/
function authBooking($user,$room){
  $sql = "SELECT COUNT(*) FROM ".TABLE_PREFIX."_j_userbook_room WHERE (login = ? AND id_room = ?";
  $test = grr_sql_query1($sql,"si",[protect_data_sql($user),$room]);
  return ($test > 0);
}
/** function getWritable($user, $id)
* paramètres :
* - $user : l'utilisateur connecté
* - $id : l'identifiant de la réservation
* retourne 0 ou 1, 1 lorsque la réservation est modifiable par $user, 0 sinon
*/
function getWritable($user, $id)
{
  if (Settings::get("allow_gestionnaire_modify_del") == 0)
  $temp = 3;
  else
    $temp = 2;
  $sql = "SELECT room_id, create_by, beneficiaire, dont_allow_modify, who_can_book, qui_peut_reserver_pour 
          FROM ".TABLE_PREFIX."_entry e JOIN ".TABLE_PREFIX."_room r ON e.room_id = r.id
          WHERE e.id = ?";
  $res = grr_sql_query($sql,"i",[$id]);
  if (!$res)
    fatal_error(0, grr_sql_error());
  elseif (grr_sql_count($res) == 0) // réservation inconnue
    fatal_error(1, get_vocab('invalid_entry_id'));
  else {
    $data = grr_sql_row_keyed($res,0);
    grr_sql_free($res);
    if (authGetUserLevel($user,$data['room_id']) > $temp)
      return 1; // Modifications permises si l'utilisateur a les droits suffisants
    else {
      $user_can_book = $data['who_can_book'] || authBooking($user,$data['room_id']);
      $createur = strtolower($data['create_by']);
      $beneficiaire = strtolower($data['beneficiaire']);
      $utilisateur = strtolower($user);
      /* Dans l'étude du cas d'un utilisateur sans droits particuliers, quatre possibilités :
      Cas 1 : l'utilisateur (U) n'est ni le créateur (C) ni le bénéficiaire (B)
        R1 -> on retourne 0
      Cas 2 : U=B et U<>C  ou ...
      Cas 3 : U=B et U=C
        R2 -> on retourne 0 si personne hormis les gestionnaires et les administrateurs ne peut modifier ou supprimer ses propres réservations.
        R3 -> on retourne $user_can_book selon les droits de l'utilisateur sur la ressource
      Cas 4 : U=C et U<>B
        R4 -> on retourne 0 si personne hormis les gestionnaires et les administrateurs ne peut modifier ou supprimer ses propres réservations.
        -> sinon
          R5 -> on retourne $user_can_book selon les droits de l'utilisateur U sur la ressource et s'il peut réserver la ressource pour B
          R6 -> on retourne 0 sinon (si on permettait à U d'éditer la résa, il ne pourrait de toute façon pas la modifier)*/
      if (($utilisateur != $beneficiaire) && ($utilisateur != $createur)) // cas 1
        return 0;
      elseif ($utilisateur == $beneficiaire) // cas 2 et 3
      {
        if (authGetUserLevel($user, $data['room_id']) > 2) 
          return 1; // un gestionnaire de ressource peut toujours modifier ses propres réservations
        elseif ($data['dont_allow_modify'] == 'y')
          return 0; // un simple utilisateur ne peut pas modifier ses propres réservations
        else 
          return $user_can_book;
      }
      elseif ($utilisateur == $createur) // cas 4
      {
        if (authGetUserLevel($user, $data['room_id']) > 2) 
          return 1; // un gestionnaire de ressource peut toujours modifier ses propres réservations
        elseif ($data['dont_allow_modify'] == 'y')
          return 0; // un simple utilisateur ne peut pas modifier ses propres réservations
        else
        {
          if (authGetUserLevel($user, $data['room_id']) >= $data['qui_peut_reserver_pour'])
            return $user_can_book;
          else
            return 0;
        }
      }
    }
  }
}
/* function verif_display_fiche_ressource : vérifier l'accès à la visualisation de la fiche d'une ressource
 * $user : le login de l'utilisateur
 * $id_room : l'id de la ressource. */
function verif_display_fiche_ressource($user, $id_room)
{
  $show_fic_room = grr_sql_query1("SELECT show_fic_room FROM ".TABLE_PREFIX."_room WHERE id=?","i",[$id_room]);
  if ($show_fic_room == "y")
  {
    if (authGetUserLevel($user,$id_room) >= Settings::get("visu_fiche_description"))
      return true;
    return false;
  }
  return false;
}
/* function verif_access_search : vérifier l'accès à l'outil de recherche
 * $user : le login de l'utilisateur
 * $id_room : l'id de la ressource. */
function verif_access_search($user)
{
  if (authGetUserLevel($user,-1) >= Settings::get("allow_search_level"))
    return true;
  return false;
}
/* fonction acces_formulaire_reservation
* détermine si le quota de réservations par formulaire non modérées est atteint
* rend TRUE si le formulaire est accessible ou FALSE (y compris si l'accès à la base est impossible)
*/
function acces_formulaire_reservation(){
  if (null == Settings::get('nb_max_resa_form'))
    return FALSE;
  elseif (Settings::get('nb_max_resa_form') == '-1')
    return TRUE;
  else {
    $quota = grr_sql_query1("SELECT COUNT(*) FROM ".TABLE_PREFIX."_entry WHERE (entry_type = -1 AND moderate = 1)");
    // echo $quota;
    if ($quota == -1)
      return FALSE;
    else 
      return ((Settings::get('nb_max_resa_form') - $quota) > 0);
  }
}
/* function verif_display_email : vérifier l'accès à l'adresse email
 *$user : le login de l'utilisateur
 * $id_room : l'id de la ressource.
 */
function verif_display_email($user, $id_room)
{
  if (authGetUserLevel($user,$id_room) >= Settings::get("display_level_email"))
    return true;
  else
    return false;
}
//Vérifie si un utilisateur identifié par SSO est autorisé à changer ses nom, prénom et mail
//Renvoie true (peut changer ses nom, prénom et email) ou false (ne peut pas)
function sso_IsAllowedModify()
{
  if (Settings::get("sso_IsNotAllowedModify")=="y")
  {
    $source = grr_sql_query1("SELECT source FROM ".TABLE_PREFIX."_utilisateurs WHERE login = ?","s",[getUserName()]);
    if ($source == "ext")
      return false;
    else
      return true;
  }
  else
    return true;
}
//Vérifie que l'utilisateur est autorisé à changer ses nom et prénom
//Renvoie true (peut changer ses nom et prénom) ou false (ne peut pas)
function IsAllowedToModifyProfil()
{
  if (!(sso_IsAllowedModify()))
    return false;
    // l'utilisateur connecté n'a pas le niveau suffisant pour modifier son compte
  if (authGetUserLevel(getUserName(),-1) < Settings::get("allow_users_modify_profil"))
    return false;
  else
    return true;
}
//Vérifie que l'utilisateur est autorisé à changer son mot de passe
//Renvoie true (peut changer) ou false (ne peut pas)
function IsAllowedToModifyMdp() {
    // l'utilisateur connecté n'a pas le niveau suffisant pour modifier son compte
  if (authGetUserLevel(getUserName(), -1) < Settings::get("allow_users_modify_mdp"))
    return false;
  else if ((Settings::get("sso_statut") != "") or (Settings::get("ldap_statut") != '') or (Settings::get("imap_statut") != ''))
  {
    // ou bien on est dans un environnement SSO ou ldap et l'utilisateur n'est pas un utilisateur local
    $source = grr_sql_query1("SELECT source FROM ".TABLE_PREFIX."_utilisateurs WHERE login = ?","s",[getUserName()]);
    if ($source == "ext")
      return false;
    else
      return true;
  }
  else
    return true;
}
//Vérifie que l'utilisateur est autorisé à changer son email
//Renvoie true (peut changer son email) ou false (ne peut pas)
function IsAllowedToModifyEmail()
{
  if (!(sso_IsAllowedModify()))
    return false;
    // l'utilisateur connecté n'a pas le niveau suffisant pour modifier son compte
  if (authGetUserLevel(getUserName(),-1) < Settings::get("allow_users_modify_email"))
    return false;
  else
    return true;
}
/** function checkPassword($pwd, $pwd_hash, $login)
* vérifie que le mot de passe fourni $pwd correspond au $pwd_hash issu de la BDD pour l'utilisateur associé au $login
* si le mot de passe n'a pas été enregistré par la fonction password_hash, mais est valide pour md5, alors, si la base est en version 3.5.1+, la fonction le convertit au passage et l'enregistre au nouveau format
* renvoie TRUE si le mot de passe est valable, FALSE sinon ; déclenche une erreur si l'enregistrement du nouveau mot de passe échoue
*/
function checkPassword($pwd, $pwd_hash, $login){
  $result = false;
  $do_rehash = false;
  /* si $pwd_hash commence par '$' il est censé être issu de password_hash */
  if (substr($pwd_hash, 0, 1) == '$')
  {
    if (password_verify($pwd, $pwd_hash))
    { // c'est un mot de passe codé par password_hash, voyons s'il faut le mettre à jour
      $result = true;
      if (password_needs_rehash($pwd_hash, PASSWORD_DEFAULT))
      {
        $do_rehash = true;
      }
    }
  }
  /* sinon $pwd_hash est censé être issu de MD5 */
  else
  {
    if (md5($pwd) == $pwd_hash)
    {
      $result = true;
      // si la base est 3.5.1+, on mettra à jour le mot de passe
      $ver = grr_sql_query1("SELECT VALUE FROM ".TABLE_PREFIX."_setting WHERE NAME='version';");
      if("3.5.1" <= $ver) 
        $do_rehash = true;
    }
  }
  if ($do_rehash)
  {
    $pwd_hash = password_hash($pwd, PASSWORD_DEFAULT);
    $sql = "UPDATE ".TABLE_PREFIX."_utilisateurs SET password = '$pwd_hash' WHERE login = '".strtoupper($login)."';";
    if (grr_sql_command($sql) < 0)
    fatal_error(0, "<p>".$sql."<br>" . grr_sql_error());
  }
  return $result;
}
/* function UserRoomMaxBookingRange
 * Cette fonction teste si l'utilisateur $user a la possibilité d'effectuer une réservation, compte tenu
 * des limitations éventuelles de la ressource $id_room et du nombre $number de réservations à effectuer, sans que le quota défini sur l'intervalle [$start_time - $range, $start_time] dépasse la limite.
*/
function UserRoomMaxBookingRange($user, $id_room, $number, $start_time)
{
  global $enable_periods,$id_room_autorise;
  $level = authGetUserLevel($user,$id_room);
  if ($id_room == '')
    return 1;
  if ($level >= 3)
    return 0;
  else if (($level == 1 ) &&  !((in_array($id_room,$id_room_autorise)) && ($id_room_autorise != "")))
    return 1;
  else if ($level  < 1 )
    return 1;
  // A ce niveau, l'utilisateur est simple utilisateur ou bien simple visiteur sur un domaine autorisé
  // On regarde si le nombre de réservation de la ressource est limité
  $max_booking_per_room = grr_sql_query1("SELECT max_booking FROM ".TABLE_PREFIX."_room WHERE id = ?","i",[$id_room]);
  // limitation dans le temps
  $booking_range = grr_sql_query1("SELECT booking_range FROM ".TABLE_PREFIX."_room WHERE id = ?","i",[$id_room]); // jours
  $min_int = $start_time - $booking_range * 86400 ;// approximatif, mais devrait être convenable
  $max_booking_on_range = grr_sql_query1("SELECT max_booking_on_range FROM ".TABLE_PREFIX."_room WHERE id = ?","i",[$id_room]);
  // Calcul de l'id de l'area de la ressource.
  $id_area = mrbsGetRoomArea($id_room);
  // On regarde si le nombre de réservation du domaine est limité
  $max_booking_per_area = grr_sql_query1("SELECT max_booking FROM ".TABLE_PREFIX."_area WHERE id = ?","i",[$id_area]);
  // On regarde si le nombre de réservation pour l'ensemble des ressources est limité
  $max_booking = Settings::get("UserAllRoomsMaxBooking");
  // Si aucune limitation
  if (($max_booking_per_room < 0) && ($max_booking_on_range < 0) && ($max_booking_per_area < 0) && ($max_booking < 0))
    return 0;
  // A ce niveau, il s'agit d'un utilisateur et il y a au moins une limitation
  $day   = date("d");
  $month = date("m");
  $year  = date("Y");
  $hour  = date("H");
  $minute = date("i");
  if ($enable_periods == 'y')
    $now = mktime(0, 0, 0, $month, $day, $year);
  else
    $now = mktime($hour, $minute, 0, $month, $day, $year);
  // y-a-t-il dépassement pour l'ensemble des ressources ?
  if ($max_booking > 0)
  {
    $nb_bookings = grr_sql_query1("SELECT count(id) FROM ".TABLE_PREFIX."_entry r WHERE (beneficiaire = ? AND end_time > ?)","si",[protect_data_sql($user),$now]);
    $nb_bookings += $number;
    if ($nb_bookings > $max_booking)
      return 2;
  }
  else if ($max_booking == 0)
    return 2;
  // y-a-t-il dépassement pour l'ensemble des ressources du domaine ?
  if ($max_booking_per_area > 0)
  {
    $nb_bookings = grr_sql_query1("SELECT count(e.id) FROM ".TABLE_PREFIX."_entry e JOIN ".TABLE_PREFIX."_room r ON e.room_id=r.id WHERE (r.area_id=? AND e.beneficiaire = ? AND e.end_time > ?)","isi",[$id_area,'".protect_data_sql($user)."',$now]);
    $nb_bookings += $number;
    if ($nb_bookings > $max_booking_per_area)
      return 3;
  }
  else if ($max_booking_per_area == 0)
    return 3;
  // y-a-t-il dépassement pour la ressource
  if ($max_booking_per_room > 0)
  {
    $nb_bookings = grr_sql_query1("SELECT count(id) FROM ".TABLE_PREFIX."_entry WHERE (room_id = ? AND beneficiaire = ? AND end_time > ?)","isi",[$id_room,protect_data_sql($user),$now]);
    $nb_bookings += $number;
    if ($nb_bookings > $max_booking_per_room)
      return 4;
  }
  else if ($max_booking_per_room == 0)
    return 4;
    // limitation sur l'intervalle
    if ($booking_range > 0 ){
      $this_day = date("d",$start_time);
      $this_month = date("m",$start_time);
      $this_year = date("Y",$start_time);
      // mktime(0,0,0,$this_month,$this_day,$this_year); le jour de début de la réservation 
      for($k=1;$k<=$booking_range;$k++){
        $min_int = mktime(0,0,0,$this_month,$this_day+$k-$booking_range,$this_year);
        $max_int = mktime(0,0,0,$this_month,$this_day+$k,$this_year);
        $nb_bookings = grr_sql_query1("SELECT count(id) FROM ".TABLE_PREFIX."_entry WHERE (room_id = ? AND beneficiaire = ? AND end_time > ? AND start_time < ?)","isii",[$id_room,protect_data_sql($user),$min_int,$max_int]);
        $nb_bookings += $number; // réservations existantes + réservations à effectuer
        if ($nb_bookings > $max_booking_on_range)
          return 5;
      }
    }
  // A ce stade, il s'agit d'un utilisateur et il n'y a pas eu de dépassement, ni pour l'ensemble des domaines, ni pour le domaine, ni pour la ressource, ni sur l'intervalle de temps
  return 0;
}

// affichages
function pageHead2($title, $page = "with_session") 
{
  if ($page == "with_session")
  {
    if($_SESSION['changepwd'] == 1 && $grr_script_name != 'changepwd.php'){
      header("Location: ./changepwd.php");
    } // est-ce bien placé ? YN le 27/02/2020

    if (isset($_SESSION['default_style']))
      $sheetcss = 'themes/'.$_SESSION['default_style'].'/css';

    else {
      if (Settings::get("default_css"))
        $sheetcss = 'themes/'.Settings::get("default_css").'/css'; // thème global par défaut
      else
        $sheetcss = 'themes/default/css'; // utilise le thème par défaut s'il n'a pas été défini
    }
    if (isset($_GET['default_language']))
    {
      $_SESSION['default_language'] = clean_input($_GET['default_language']);
      if (isset($_SESSION['chemin_retour']) && ($_SESSION['chemin_retour'] != ''))
        header("Location: ".$_SESSION['chemin_retour']);
      else
        header("Location: ".traite_grr_url());
      die();
    }
  }
  else
  {
    if (Settings::get("default_css"))
      $sheetcss = 'themes/'.Settings::get("default_css").'/css';
    else
      $sheetcss = 'themes/default/css';
    if (isset($_GET['default_language']))
    {
      $_SESSION['default_language'] = clean_input($_GET['default_language']);
      if (isset($_SESSION['chemin_retour']) && ($_SESSION['chemin_retour'] != ''))
        header("Location: ".$_SESSION['chemin_retour']);
      else
        header("Location: ".traite_grr_url());
      die();
    }
  }
  global $clock_file, $use_select2, $use_admin;
  // récupération des couleurs des types
  $types = '';
  $sql = "SELECT type_letter,couleurhexa,couleurtexte FROM ".TABLE_PREFIX."_type_area WHERE 1";
  $res = grr_sql_query($sql);
  if ($res && ($res->num_rows > 0)) {
    $types = "<style>".PHP_EOL;
    while($row = $res->fetch_assoc()) {
        $types .= "td.type".$row["type_letter"]."{background:".$row["couleurhexa"]." !important;color:".$row["couleurtexte"]." !important;}".PHP_EOL;
        $types .= "td.type".$row["type_letter"]." a.lienCellule{color:".$row["couleurtexte"]." !important;}".PHP_EOL;
    }
    $types .= "</style>".PHP_EOL;
  }
  grr_sql_free($res);
  // code de la partie <head> 
  $a  = '<head>'.PHP_EOL;
  $a .= '<meta charset="utf-8">'.PHP_EOL;
  $a .= '<meta http-equiv="X-UA-Compatible" content="IE=edge">'.PHP_EOL;
  $a .= '<meta name="viewport" content="width=device-width, initial-scale=1">'.PHP_EOL;
  $a .= '<meta name="Robots" content="noindex" />'.PHP_EOL;
  $a .= '<title>'.$title.'</title>'.PHP_EOL;

  if (@file_exists('admin_accueil.php') || @file_exists('install_mysql.php')){ // Si on est dans l'administration ou en initialisation
    $a .= '<link rel="shortcut icon" href="../favicon.ico" />'.PHP_EOL;
    $a .= '<link rel="stylesheet" type="text/css" href="../bootstrap/css/bootstrap.min.css" />'.PHP_EOL;
    $a .= '<link rel="stylesheet" type="text/css" href="../include/admin_grr.css" />'.PHP_EOL;
    $a .= '<link rel="stylesheet" type="text/css" href="../bootstrap/css/select2.css" />'.PHP_EOL;
    $a .= '<link rel="stylesheet" type="text/css" href="../bootstrap/css/select2-bootstrap.css" />'.PHP_EOL;
    $a .= '<link rel="stylesheet" type="text/css" href="../bootstrap/css/jquery-ui.min.css" />'.PHP_EOL;
    $a .= '<link rel="stylesheet" type="text/css" href="../bootstrap/css/jquery-ui-timepicker-addon.css" >'.PHP_EOL;
    $a .= '<link rel="stylesheet" type="text/css" href="../bootstrap/css/bootstrap-multiselect.css">'.PHP_EOL;
    $a .= '<link rel="stylesheet" type="text/css" href="../bootstrap/css/bootstrap-clockpicker.min.css">'.PHP_EOL;
    $a .= '<link rel="stylesheet" type="text/css" href="../themes/default/css/style.css" />'.PHP_EOL; // le style par défaut
    $a .= '<link rel="stylesheet" type="text/css" href="../'.$sheetcss.'/style.css" />'.PHP_EOL; // le style personnalisé
        //$a .= '<link rel="stylesheet" type="text/css" href="../themes/default/css/types.css" />'.PHP_EOL; // les couleurs des types de réservation
    $a .= $types;
    if ((isset($_GET['pview'])) && ($_GET['pview'] == 1))
      $a .= '<link rel="stylesheet" type="text/css" href="../themes/print/css/style.css" />'.PHP_EOL;
    $a .= '<script type="text/javascript" src="../js/jquery-3.7.1.min.js"></script>'.PHP_EOL;
    $a .= '<script type="text/javascript" src="../js/jquery-ui.min.js"></script>'.PHP_EOL;
    $a .= '<script type="text/javascript" src="../js/jquery.validate.js"></script>'.PHP_EOL;
    $a .= '<script type="text/javascript" src="../js/jquery-ui-timepicker-addon.js"></script>'.PHP_EOL;
    $a .= '<script type="text/javascript" src="../bootstrap/js/bootstrap.min.js"></script>'.PHP_EOL;
    $a .= '<script type="text/javascript" src="../js/bootstrap-clockpicker.js"></script>'.PHP_EOL;
    $a .= '<script type="text/javascript" src="../js/bootstrap-multiselect.js"></script>'.PHP_EOL;
    $a .= '<script type="text/javascript" src="../js/html2canvas.js"></script>'.PHP_EOL;
    $a .= '<script type="text/javascript" src="../js/menu.js"></script>'.PHP_EOL;
    $a .= '<script type="text/javascript" src="../js/jquery.floatThead.min.js"></script>'.PHP_EOL;
    $a .= '<script type="text/javascript" src="../js/planning2Thead.js"></script>'.PHP_EOL;
    $a .= '<script type="text/javascript" src="../js/jspdf.min.js"></script>'.PHP_EOL;
    // $a .= '<script type="text/javascript" src="../js/pdf.js" ></script>'.PHP_EOL;
    $a .= '<script type="text/javascript" src="../js/popup.js" charset="utf-8"></script>'.PHP_EOL;
    $a .= '<script type="text/javascript" src="../js/functions.js" ></script>'.PHP_EOL;
    $a .= '<script type="text/javascript" src="../js/select2.js"></script>'.PHP_EOL;
    $a .= '<script type="text/javascript" src="../js/select2_locale_fr.js"></script>'.PHP_EOL;
    //if (isset($use_tooltip_js))
      //echo '<script type="text/javascript" src="../js/tooltip.js"></script>'.PHP_EOL;
    //if (!isset($_SESSION['selection']))
      // $a .= '<script type="text/javascript" src="../js/selection.js" ></script>'.PHP_EOL;
    if (@file_exists('../js/'.$clock_file))
      $a .= '<script type="text/javascript" src="../js/'.$clock_file.'"></script>'.PHP_EOL;
    $a .= '<script type="text/javascript" src="../js/jscolor.js"></script>';
    if (substr(phpversion(), 0, 1) == 3)
      $a .= get_vocab('not_php3');
  } 
  else {
    $a .= '<link rel="shortcut icon" href="./favicon.ico" />'.PHP_EOL;
    $a .= '<link rel="stylesheet" type="text/css" href="bootstrap/css/bootstrap.min.css" />'.PHP_EOL;
    if (isset($use_select2))
    {
      $a .= '<link rel="stylesheet" type="text/css" href="bootstrap/css/select2.css" />'.PHP_EOL;
      $a .= '<link rel="stylesheet" type="text/css" href="bootstrap/css/select2-bootstrap.css" />'.PHP_EOL;
      $a .= '<link rel="stylesheet" type="text/css" href="bootstrap/css/bootstrap-multiselect.css">'.PHP_EOL;
      $a .= '<link rel="stylesheet" type="text/css" href="bootstrap/css/bootstrap-clockpicker.min.css">'.PHP_EOL;
    }
    $a .= '<link rel="stylesheet" type="text/css" href="bootstrap/css/jquery-ui.min.css" />'.PHP_EOL;
    $a .= '<link rel="stylesheet" type="text/css" href="bootstrap/css/jquery.timepicker.min.css" />';
    //$a .= '<link rel="stylesheet" type="text/css" href="bootstrap/css/jquery-ui-timepicker-addon.css" >'.PHP_EOL;
    $a .= '<link rel="stylesheet" type="text/css" href="themes/default/css/style.css" />'.PHP_EOL; // le style par défaut
    $a .= '<link rel="stylesheet" type="text/css" href="'.$sheetcss.'/style.css" />'.PHP_EOL; // le style personnalisé
    //$a .= '<link rel="stylesheet" type="text/css" href="themes/default/css/types.css" />'.PHP_EOL; // les couleurs des types de réservation        
    $a .= $types;
    if (isset($use_admin))
      $a .= '<link rel="stylesheet" type="text/css" href="include/admin_grr.css" />'.PHP_EOL;
    if ((isset($_GET['pview'])) && ($_GET['pview'] == 1))
      $a .= '<link rel="stylesheet" type="text/css" href="themes/print/css/style.css" />'.PHP_EOL;
    $a .= '<script type="text/javascript" src="js/jquery-3.7.1.min.js"></script>'.PHP_EOL;
    $a .= '<script type="text/javascript" src="js/jquery-ui.min.js"></script>'.PHP_EOL;
    $a .= '<script type="text/javascript" src="js/jquery-ui-i18n.min.js"></script>'.PHP_EOL;
    $a .= '<script type="text/javascript" src="js/jquery.validate.js"></script>'.PHP_EOL;
    $a .= '<script type="text/javascript" src="js/jquery-ui-timepicker-addon.js"></script>'.PHP_EOL;
    $a .= '<script type="text/javascript" src="bootstrap/js/bootstrap.min.js"></script>'.PHP_EOL;
    $a .= '<script type="text/javascript" src="js/html2canvas.js"></script>'.PHP_EOL;
    $a .= '<script type="text/javascript" src="js/jquery.floatThead.min.js"></script>'.PHP_EOL;
    $a .= '<script type="text/javascript" src="js/menu.js"></script>'.PHP_EOL;     
    $a .= '<script type="text/javascript" src="js/planning2Thead.js"></script>'.PHP_EOL;
    $a .= '<script type="text/javascript" src="js/jspdf.min.js"></script>'.PHP_EOL;
    // $a .= '<script type="text/javascript" src="js/pdf.js" ></script>'.PHP_EOL;
    $a .= '<script type="text/javascript" src="js/popup.js" charset="utf-8"></script>'.PHP_EOL;
    $a .= '<script type="text/javascript" src="js/functions.js" ></script>'.PHP_EOL;
    $a .= '<script type="text/javascript" src="js/jquery.timepicker.min.js"></script>';
    if (isset($use_select2))
    {
      $a .= '<script type="text/javascript" src="js/bootstrap-clockpicker.js"></script>'.PHP_EOL;
      $a .= '<script type="text/javascript" src="js/bootstrap-multiselect.js"></script>'.PHP_EOL;
      $a .= '<script type="text/javascript" src="js/select2.js"></script>'.PHP_EOL;
      $a .= '<script type="text/javascript" src="js/select2_locale_fr.js"></script>'.PHP_EOL;
    }
    //if (isset($use_tooltip_js))
      //echo '<script type="text/javascript" src="./js/tooltip.js"></script>'.PHP_EOL;
    //if (!isset($_SESSION['selection']))
      //$a .= '<script type="text/javascript" src="js/selection.js" ></script>'.PHP_EOL;
    if (@file_exists('js/'.$clock_file))
      $a .= '<script type="text/javascript" src="js/'.$clock_file.'"></script>'.PHP_EOL;
    if (substr(phpversion(), 0, 1) == 3)
      $a .= get_vocab('not_php3');
  }

  $a .= '</head>'.PHP_EOL;
  return $a;
}

/*
** Fonction qui affiche le header = bandeau du haut de page
*/
function pageHeader2($day = '', $month = '', $year = '', $type_session = 'with_session', $adm=0)
{
  global $search_str, $grrSettings, $clock_file, $desactive_VerifNomPrenomUser, $grr_script_name, $racine, $racineAd;
  global $use_prototype, $use_admin, $use_tooltip_js, $desactive_bandeau_sup, $id_site, $use_select2;
    $parametres_url = '';
    if (isset($_SERVER['QUERY_STRING']) && ($_SERVER['QUERY_STRING'] != ''))
        $parametres_url = htmlspecialchars($_SERVER['QUERY_STRING']);

  Hook::Appel("hookHeader2");
  // Si nous ne sommes pas dans un format imprimable
  if ((!isset($_GET['pview'])) || ($_GET['pview'] != 1))
  {
    // If we dont know the right date then make it up
    if (!isset($day) || !isset($month) || !isset($year) || ($day == '') || ($month == '') || ($year == ''))
    {
      $date_ = time();
      $day   = date("d",$date_);
      $month = date("m",$date_);
      $year  = date("Y",$date_);
    }
        // On fabrique une date valide pour la réservation si ce n'est pas le cas
        $date_ = mktime(0, 0, 0, $month, $day, $year);
        if ($date_ < Settings::get("begin_bookings"))
            $date_ = Settings::get("begin_bookings");
        else if ($date_ > Settings::get("end_bookings"))
            $date_ = Settings::get("end_bookings");
        $day   = date("d",$date_);
        $month = date("m",$date_);
        $year  = date("Y",$date_);

        echo '<div id="panel">'.PHP_EOL;
        if (!(isset($search_str)))
      $search_str = get_vocab("search_for");
    if (empty($search_str))
      $search_str = "";
    if (!(isset($desactive_bandeau_sup) && ($desactive_bandeau_sup == 1) && ($type_session != 'with_session')))
    {
      // HOOK
      Hook::Appel("hookHeader1");
      // Génération XML
      $generationXML = 1;
      if ((Settings::get("export_xml_actif") == "Oui") && ($adm == 0)){
        include $racine."include/generationxml.php";
      }
      if ((Settings::get("export_xml_plus_actif") == "Oui") && ($adm == 0)){
        include $racine."include/generationxmlplus.php";
      }
      //Logo
      $nom_picture = $racine."images/".Settings::get("logo");
      if ((Settings::get("logo") != '') && (@file_exists($nom_picture)))
        echo '<div class="logo" height="100">'.PHP_EOL.'<a href="'.$racine.page_accueil('yes').'day='.$day.'&amp;year='.$year.'&amp;month='.$month.'"><img src="'.$nom_picture.'" alt="logo"/></a>'.PHP_EOL.'</div>'.PHP_EOL;
      //Accueil
      echo '<div class="accueil ">',PHP_EOL,'<h2>',PHP_EOL,'<a href="'.$racine.page_accueil('yes'),'day=',$day,'&amp;year=',$year,'&amp;month=',$month,'">',Settings::get("company"),'</a>',PHP_EOL,'</h2>',PHP_EOL, Settings::get('message_accueil'),'</div>',PHP_EOL;
      //Mail réservation
      $sql = "SELECT value FROM ".TABLE_PREFIX."_setting WHERE name='mail_etat_destinataire'";
      $res = grr_sql_query1($sql);
      if ((( $res == 1 && $type_session == "no_session" ) || ( ( $res == 1 || $res == 2) && $type_session == "with_session" && (authGetUserLevel(getUserName(), -1, 'area')) == 1  ) )&& acces_formulaire_reservation())
      {
        echo '<div class="contactformulaire">',PHP_EOL,'<input class="btn btn-default" type="submit" rel="popup_name" value="'.get_vocab('Reserver').'" onClick="javascript:location.href=\'contactFormulaire.php?day=',$day,'&amp;month=',$month,'&amp;year=',$year,'\'" >',PHP_EOL,'</div>',PHP_EOL;
      }
      // Administration
      if ($type_session == "with_session")
      {
                $user_name = getUserName();
                $resaAModerer = resaToModerate($user_name);
                $nbResaAModerer = count($resaAModerer);
                $mess_resa = '';
                if ($nbResaAModerer > 1){$mess_resa = $nbResaAModerer.get_vocab('resasToModerate');}
                if ($nbResaAModerer == 1){$mess_resa = $nbResaAModerer.get_vocab('resaToModerate');}
        if ((authGetUserLevel($user_name, -1, 'area') >= 4) || (authGetUserLevel($user_name, -1, 'user') == 1) || ($mess_resa != '')) // trop large ? YN le 06/01/19
        {
          echo '<div class="administration">'.PHP_EOL;
          if ((authGetUserLevel($user_name, -1, 'area') >= 4) || (authGetUserLevel($user_name, -1, 'user') == 1))
                        echo "<br><a href='{$racineAd}admin_accueil.php?day={$day}&amp;month={$month}&amp;year={$year}'>".get_vocab('admin')."</a>".PHP_EOL;
          if (authGetUserLevel(getUserName(), -1, 'area') >= 6)
          {
            echo '<br />'.PHP_EOL;
            how_many_connected();
                        echo "<br />";
          }
                    echo "<p class='avertissement'><a href='".$racine."admin/admin_accueil.php?".$parametres_url."' class='avertissement' >".$mess_resa."</a></p>";
          echo '</div>'.PHP_EOL;
        }
      }
      echo '<div class="configuration" >'.PHP_EOL;
      if (@file_exists($racine.'js/'.$clock_file))
      {
        echo '<div class="clock">'.PHP_EOL;
        echo '<div id="Date">'.PHP_EOL;
        echo '&nbsp;<span id="hours"></span>'.PHP_EOL;
        echo 'h'.PHP_EOL;
        echo '<span id="min"></span>'.PHP_EOL;
        echo '</div></div>'.PHP_EOL;
      }
      $_SESSION['chemin_retour'] = '';
      if (isset($_SERVER['QUERY_STRING']) && ($_SERVER['QUERY_STRING'] != ''))
        $_SESSION['chemin_retour'] = traite_grr_url($grr_script_name)."?". $_SERVER['QUERY_STRING'];
            echo '<a  href="'.traite_grr_url($grr_script_name).'?'.$parametres_url.'&amp;default_language=fr"><img src="'.$racine.'img_grr/fr_dp.png" alt="France" title="Français" width="20" height="13" class="image" /></a>'.PHP_EOL;
            echo '<a  href="'.traite_grr_url($grr_script_name).'?'.$parametres_url.'&amp;default_language=de"><img src="'.$racine.'img_grr/de_dp.png" alt="Deutch" title="Deutch" width="20" height="13" class="image" /></a>'.PHP_EOL;
            echo '<a  href="'.traite_grr_url($grr_script_name).'?'.$parametres_url.'&amp;default_language=en"><img src="'.$racine.'img_grr/en_dp.png" alt="English" title="English" width="20" height="13" class="image" /></a>'.PHP_EOL;
            echo '<a  href="'.traite_grr_url($grr_script_name).'?'.$parametres_url.'&amp;default_language=it"><img src="'.$racine.'img_grr/it_dp.png" alt="Italiano" title="Italiano" width="20" height="13" class="image" /></a>'.PHP_EOL;
            echo '<a  href="'.traite_grr_url($grr_script_name).'?'.$parametres_url.'&amp;default_language=es"><img src="'.$racine.'img_grr/es_dp.png" alt="Español" title="Español" width="20" height="13" class="image" /></a>'.PHP_EOL;
            $url = urlencode(traite_grr_url($grr_script_name).'?'.$parametres_url);
      if ($type_session == 'no_session')
      {
        if ((Settings::get('sso_statut') == 'cas_visiteur') || (Settings::get('sso_statut') == 'cas_utilisateur'))
        {
          echo '<br /> <a href="index.php?force_authentification=y">'.get_vocab("authentification").'</a>'.PHP_EOL;
          echo '<br /> <small><i><a href="login.php?url='.$url.'">'.get_vocab("connect_local").'</a></i></small>'.PHP_EOL;
        }
        else {
          echo '<br /> <a href="login.php?url='.$url.'">'.get_vocab("connect").'</a>'.PHP_EOL;
        }
      }
      else
      {
        if( strlen(htmlspecialchars($_SESSION['prenom']).' '.htmlspecialchars($_SESSION['nom'])) > 40 )
          $nomAffichage =  htmlspecialchars($_SESSION['nom']);
        else
          $nomAffichage =  htmlspecialchars($_SESSION['prenom']).' '.htmlspecialchars($_SESSION['nom']);
      
        echo '<br /><a href="'.$racine.'my_account.php?day='.$day.'&amp;year='.$year.'&amp;month='.$month.'">'. $nomAffichage .' - '.get_vocab("manage_my_account").'</a>'.PHP_EOL;
        if (verif_access_search(getUserName()))
          echo '<br/><a href="'.$racine.'recherche.php">'.get_vocab("report").'</a>'.PHP_EOL;
        $disconnect_link = false;
        if (!((Settings::get("cacher_lien_deconnecter") == 'y') && (isset($_SESSION['est_authentifie_sso']))))
        {
          $disconnect_link = true;
          if (Settings::get("authentification_obli") == 1)
            echo '<br /> <a href="'.$racine.'logout.php?auto=0" >'.get_vocab('disconnect').'</a>'.PHP_EOL;
          else
            echo '<br /> <a href="'.$racine.'logout.php?auto=0&amp;redirect_page_accueil=yes" >'.get_vocab('disconnect').'</a>'.PHP_EOL;
        }
        if ((Settings::get("Url_portail_sso") != '') && (isset($_SESSION['est_authentifie_sso'])))
        {
          if ($disconnect_link)
            echo ' - '.PHP_EOL;
          else
            echo '<br />'.PHP_EOL;
          echo '<a href="'.Settings::get("Url_portail_sso").'">'.get_vocab("Portail_accueil").'</a>'.PHP_EOL;
        }
        if ((Settings::get('sso_statut') == 'lasso_visiteur') || (Settings::get('sso_statut') == 'lasso_utilisateur'))
        {
          echo '<br />';
          if ($_SESSION['lasso_nameid'] == NULL)
            echo '<a href="lasso/federate.php">'.get_vocab('lasso_federate_this_account').'</a>'.PHP_EOL;
          else
            echo '<a href="lasso/defederate.php">'.get_vocab('lasso_defederate_this_account').'</a>'.PHP_EOL;
        }
      }
      echo '</div>'.PHP_EOL;
      echo '</div>'.PHP_EOL;
      echo '<a id="open" class="open" href="#"><span class="glyphicon glyphicon-arrow-up"><span class="glyphicon glyphicon-arrow-down"></span></span></a>'.PHP_EOL;
    }
  }
}
/*
** Fonction qui affiche le début d'une page avec entête et balise <section>
*/
function start_page_w_header($day = '', $month = '', $year = '', $type_session = 'with_session')
{
    global $racine,$racineAd;
    // pour le traitement des modules
    if (@file_exists('./admin_access_area.php')){
        $adm = 1;
        $racine = "../";
        $racineAd = "./";
    }
    else{
        $adm = 0;
        $racine = "./";
        $racineAd = "./admin/";
    }
    include $racine."/include/hook.class.php";
    // code HTML
    header('Content-Type: text/html; charset=utf-8'); // en liaison avec la modification de pageHead2
    if (!isset($_COOKIE['open']))
    {
        header('Set-Cookie: open=true; SameSite=Strict');
    }
    echo '<!DOCTYPE html>'.PHP_EOL;
    echo '<html lang="fr">'.PHP_EOL;
    // section <head>
    if ($type_session == "with_session")
        echo pageHead2(Settings::get("company"),"with_session");
    else
        echo pageHead2(Settings::get("company"),"no_session");
    // section <body>
    echo "<body>";
    // Menu du haut = section <header>
    echo "<header>";
    pageHeader2($day, $month, $year, $type_session, $adm);
    echo "</header>";
    // Debut de la page
    echo '<section>'.PHP_EOL;
    // doit être fermé par la fonction end_page
}   
/*
** Fonction qui affiche le début d'une page sans entête et avec une balise <section>
*/
function start_page_wo_header($titre, $type_session = 'with_session')
{
    // pour le traitement des modules
    if (@file_exists('./admin_access_area.php')){
        $adm = 1;
        $racine = "../";
        $racineAd = "./";
    }
    else{
        $adm = 0;
        $racine = "./";
        $racineAd = "./admin/";
    }
    include $racine."/include/hook.class.php";
    // code HTML
    header('Content-Type: text/html; charset=utf-8');
    if (!isset($_COOKIE['open']))
    {
        header('Set-Cookie: open=true; SameSite=Strict');
    }
    echo '<!DOCTYPE html>'.PHP_EOL;
    echo '<html lang="fr">'.PHP_EOL;
    // section <head>
    if ($type_session == "with_session")
        echo pageHead2(Settings::get("company"),"with_session");
    else
        echo pageHead2(Settings::get("company"),"no_session");
    // section <body>
    echo "<body>";
    // Debut de la page
    echo '<section>'.PHP_EOL;
    // doit être fermé par la fonction end_page
} 
/* Fonction qui ferme les balises restées ouvertes dans les deux précédentes 
*/
function end_page()
{
    echo '</section></body></html>';
}
/* showAccessDenied()
 * Displays an appropriate message when access has been denied
 * Returns: Nothing
 */
function showAccessDenied($back)
{
  echo '<h1>'.get_vocab("accessdenied").'</h1>';
  echo '<p>'.get_vocab("norights").'</p>';
  echo '<p><a href="'.$back.'">'.get_vocab("returnprev").'</a></p>';
  end_page();
}
function page_accueil($param = 'no')
{
  // existe-t-il une page d'accueil imposée ?
  $page = grr_sql_query1("SELECT nom FROM ".TABLE_PREFIX."_page WHERE nom='accueil';");
  if($page != -1)
    return("page.php?page=accueil&amp;");
  // Definition de $defaultroom
  if (isset($_SESSION['default_room']))// && ($_SESSION['default_room'] > -5))
    $defaultroom = $_SESSION['default_room'];
  else
    $defaultroom = Settings::get("default_room");
  // Definition de $defaultsite
  if (isset($_SESSION['default_site']) && ($_SESSION['default_site'] > 0))
    $defaultsite = $_SESSION['default_site'];
  else if (Settings::get("default_site") > 0)
    $defaultsite = Settings::get("default_site");
  else
    $defaultsite = get_default_site();
  // Definition de $defaultarea
  if (isset($_SESSION['default_area']) && ($_SESSION['default_area'] > 0))
    $defaultarea = $_SESSION['default_area'];
  else if (Settings::get("default_area") > 0)
    $defaultarea = Settings::get("default_area");
  else
    $defaultarea = get_default_area($defaultsite);
    // on vérifie que le domaine est accessible à l'utilisateur connecté
    $user = getUserName();
    if (($user != '')&&(!authUserAccesArea($user,$defaultarea)))
        $defaultarea = get_default_area($defaultsite);
  // Calcul de $page_accueil
  if ($defaultarea == - 1)
    $page_accueil = 'day.php?noarea=';
  // le paramètre noarea ne sert à rien, il est juste là pour éviter un cas particulier à traiter avec &amp;id_site= et $param
  else if ($defaultroom == - 1)
    $page_accueil = 'day.php?area='.$defaultarea;
  else if ($defaultroom == - 2)
    $page_accueil = 'week_all.php?area='.$defaultarea;
  else if ($defaultroom == - 3)
    $page_accueil = 'month_all.php?area='.$defaultarea;
  else if ($defaultroom == -4)
    $page_accueil = 'month_all2.php?area='.$defaultarea;
  else
    $page_accueil = 'week.php?area='.$defaultarea.'&amp;room='.$defaultroom;
  if ((Settings::get("module_multisite") == "Oui") && ($defaultsite > 0))
    $page_accueil .= '&amp;id_site='.$defaultsite;
  if ($param == 'yes')
    $page_accueil .= '&amp;';
  return $page_accueil ;
}
/*
Affiche un message pop-up
$type_affichage = "user" -> Affichage des "pop-up" de confirmation après la création/modification/suppression d'une réservation
Dans ce cas, l'affichage n'a lieu que si $_SESSION['displ_msg']='yes'
$type_affichage = "admin" -> Affichage des "pop-up" de confirmation dans les menus d'administration
$type_affichage = "force" -> On force l'affichage du pop-up même si javascript_info_admin_disabled est true
*/
function affiche_pop_up($msg = "",$type_affichage = "user")
{
  // Si $_SESSION["msg_a_afficher"] est défini, on l'affiche, sinon, on affiche $msg passé en variable
  if ((isset($_SESSION["msg_a_afficher"])) and ($_SESSION["msg_a_afficher"] != ""))
    $msg = $_SESSION["msg_a_afficher"];
  if ($msg != "")
  {
    if ($type_affichage == "user")
    {
      if (!(Settings::get("javascript_info_disabled")))
      {
        echo "<script type=\"text/javascript\">";
        if ((isset($_SESSION['displ_msg'])) && ($_SESSION['displ_msg'] == 'yes'))
          echo " alert(\"".$msg."\")";
        echo "</script>";
      }
    }
    else if ($type_affichage == "admin")
    {
      if (!(Settings::get("javascript_info_admin_disabled")))
      {
        echo "<script type=\"text/javascript\">";
        echo "<!--\n";
        echo " alert(\"".$msg."\")";
        echo "//-->";
        echo "</script>";
      }
    }
    else
    {
      echo "<script type=\"text/javascript\">";
      echo "<!--\n";
      echo " alert(\"".$msg."\")";
      echo "//-->";
      echo "</script>";
    }
  }
  $_SESSION['displ_msg'] = "";
  $_SESSION["msg_a_afficher"] = "";
}
/**
* @param string $prefix
* @param string $option
*/
//Output a start table cell tag <td> with color class and fallback color.
function tdcell($colclass, $width = '')
{
  if ($width != "")
    $temp = ' style="width:'.$width.'%;" ';
  else
    $temp = "";
  // global $tab_couleur;
  // static $ecolors;
  if (($colclass >= "A") && ($colclass <= "Z"))
    echo '<td class="type'.$colclass.'"'.$temp.'>'.PHP_EOL;
  else
    echo '<td class="'.$colclass.'" '.$temp.'>'.PHP_EOL;
}
/*
Formate les noms, prénom et email du bénéficiaire ou du bénéficiaire extérieur
$type = nomail -> on affiche les prénom et nom sans le mail.
$type = withmail -> on affiche un lien avec le mail sur les prénom et nom.
$type = formail -> on formate en utf8 pour l'envoi par mail (utilisé dans l'envoi de mails automatiques)
$type = onlymail -> on affiche uniquement le mail (utilisé dans l'envoi de mails automatiques)
 * @return string
 */
function affiche_nom_prenom_email($_beneficiaire, $_beneficiaire_ext, $type = "nomail")
{
  if ($_beneficiaire != "")
  {
    $sql_beneficiaire = "SELECT prenom, nom, email FROM ".TABLE_PREFIX."_utilisateurs WHERE login = ?";
    $res_beneficiaire = grr_sql_query($sql_beneficiaire,"s",[$_beneficiaire]);
    if ($res_beneficiaire)
    {
      $nb_result = grr_sql_count($res_beneficiaire);
      if ($nb_result == 0){
        $chaine = get_vocab("utilisateur_inconnu").$_beneficiaire;
      } 
      else
      {
        $row_user = grr_sql_row($res_beneficiaire, 0);
        if ($type == "formail")
        {
          $chaine = removeMailUnicode($row_user[0])." ".removeMailUnicode($row_user[1]);
          if ($row_user[2] != "")
            $chaine .= " (".$row_user[2].")";
        }
        else if ($type == "onlymail")
        {
          // Cas où en envoie uniquement le mail
          $chaine = $row_user[2];
        }
        else if (($type == "withmail") and ($row_user[2] != ""))
        {
          // Cas où en envoie les noms, prénoms et mail
          $chaine = affiche_lien_contact($_beneficiaire,"identifiant:oui","afficher_toujours");
        }
        else
        {
          // Cas où en envoie les noms, prénoms sans le mail
          $chaine = $row_user[0]." ".$row_user[1];
        }
      }
      return $chaine;
    }
    else
      return "";
  }
  else
  {// cas d'un bénéficiaire extérieur
    // On récupère le tableau des nom et emails
    $tab_benef = donne_nom_email($_beneficiaire_ext);
    if ($type == "onlymail")
    {// Cas où en envoie uniquement le mail
      $chaine = $tab_benef["email"];
    }
    else if (($type == "withmail") && ($tab_benef["email"] != ""))
    {// Cas où en envoie les noms, prénoms et mail
      if (validate_email($tab_benef['email']))
        $chaine = '<a href="mailto:'.$tab_benef['email'].'">'.$tab_benef['nom'].'</a>';
      else
        $chaine = $tab_benef["nom"];
    }
    else
    {// Cas où en envoie les noms, prénoms sans le mail
      $chaine = $tab_benef["nom"];
    }
    return $chaine;
  }
}
/**
 * @param string $prefix
 * @param string $option
 */
function genDateSelector($prefix, $day, $month, $year, $option)
{
  global $nb_year_calendar;
  $selector_data = "<div class='btn-group'>";
  if (!isset($nb_year_calendar))
    $nb_year_calendar = 5;
  if (($day == 0) && ( $day != ""))
    $day = date("d");
  if ($month == 0)
    $month = date("m");
  if ($year == 0)
    $year = date("Y");
  if ($day != "")
  {
    $selector_data .= "<select class='btn btn-default btn-xs' name=\"{$prefix}day\" id=\"{$prefix}day\">\n";
    for ($i = 1; $i <= 31; $i++)
    {
      if ($i < 10)
        $selector_data .= "<option" . ($i == $day ? " selected=\"selected\"" : "") . ">0$i</option>\n";
      else
        $selector_data .= "<option" . ($i == $day ? " selected=\"selected\"" : "") . ">$i</option>\n";
    }
    $selector_data .= "</select>";
  }
  $selector_data .= "<select class='btn btn-default btn-xs' name=\"{$prefix}month\" id=\"{$prefix}month\">\n";
  for ($i = 1; $i <= 12; $i++)
  {
    $m = utf8_strftime("%b", mktime(0, 0, 0, $i, 1, $year));
    if ($i < 10)
    {
      $selector_data .=  "<option value=\"0$i\"" . ($i == $month ? " selected=\"selected\"" : "") . ">$m</option>\n";
    }
    else
    {
      $selector_data .=  "<option value=\"$i\"" . ($i == $month ? " selected=\"selected\"" : "") . ">$m</option>\n";
    }
  }
  $selector_data .=  "</select>";
  $selector_data .=  "<select class='btn btn-default btn-xs' name=\"{$prefix}year\" id=\"{$prefix}year\">\n";
  $min = date("Y", Settings::get("begin_bookings"));
  if ($option == "more_years")
    $min = date("Y") - $nb_year_calendar;
  $max = date("Y", Settings::get("end_bookings"));
  if ($option == "more_years")
    $max = date("Y") + $nb_year_calendar;
  for($i = $min; $i <= $max; $i++)
    $selector_data .= "<option value=\"$i\" " . ($i == $year ? " selected=\"selected\"" : "") . ">$i</option>\n";
  $selector_data .= "</select> \n</div>\n";
  
  echo $selector_data;
}
// affiche une date au format jj/mm/aaaa
function affiche_date($x)
{
  $j = date("d", $x);
  $m = date("m", $x);
  $a = date("Y", $x);
  $result = $j."/".$m."/".$a;
  return $result;
}
/*
* contenu_popup calcule le texte à inclure dans l'info-bulle d'une cellule de planning pour une réservation
* paramètres :
*  $options : les éléments à afficher selon la page planning, suivant le modèle 
*     $opt = array('horaires','beneficiaire','short_desc','description','create_by','type','participants')
*  $vue = 1 pour une ressource / 2 vue multiple ressource
*  $resa : tableau issu de la requête SQL sélectionnant les informations relatives à la réservation
*  $heures : plage horaire calculée dans le script page
*/
function contenu_popup($options, $vue, $resa, $heures)
{
  global $dformat;
  $affichage = "";
  // Heures ou créneaux + symboles <== ==>
  if (($heures != "") && ($options['horaires']))
    $affichage .= $heures."\n";
  // Bénéficiaire
  if ($options['beneficiaire'])
    $affichage .= affiche_nom_prenom_email($resa['beneficiaire'], $resa['beneficiaire_ext'], "nomail")."\n";
  // Type
  if ($options["type"])
  {
    $typeResa = grr_sql_query1("SELECT t.type_name FROM ".TABLE_PREFIX."_type_area t JOIN ".TABLE_PREFIX."_entry e ON e.type=t.type_letter WHERE e.id = ?","i",[$resa['id']]);
    if ($typeResa != -1)
      $affichage .= $typeResa."\n";
    else
      $affichage .= "???<br>";
  }
  // Brève description
  if (($options["short_desc"]) && ($resa['name'] != ""))
    $affichage .= htmlspecialchars($resa['name'],ENT_NOQUOTES)."\n";
  // Description Complète
  if (($options["description"]) && ($resa['description'] != ""))
    $affichage .= htmlspecialchars($resa['description'],ENT_NOQUOTES)."\n";
    // créateur
  if (($options["create_by"]) && ($resa['create_by'] != ""))
    $affichage .= htmlspecialchars($resa['create_by'],ENT_NOQUOTES)."\n";
  // nombre de participants
  if (($options["participants"]) && ($resa['nbparticipantmax'] != 0)){
    $inscrits = grr_sql_query1("SELECT COUNT(`participant`) FROM ".TABLE_PREFIX."_participants WHERE `idresa` = ?","i",[$resa['id']]);
    if ($inscrits >= 0)
      $affichage .= get_vocab('participant_inscrit').get_vocab('deux_points').$inscrits." / ".htmlspecialchars($resa['nbparticipantmax'],ENT_NOQUOTES)."\n";
  }
  // cas où aucune option n'est activée : afficher le texte "voir les détails"
  if ($affichage == '')
    $affichage .= get_vocab("voir_details");

  return $affichage;
}
/*
* contenu_cellule calcule le code html à inclure dans une cellule de planning pour une réservation
* paramètres :
*  $options : les éléments à afficher selon la page planning, suivant le modèle 
*     $opt = array('horaires','beneficiaire','short_desc','description','create_by','type','participants')
*  $ofl = overload fields list, ne dépend que du domaine, donc est constant dans la boucle d'affichage
*  $vue = 1 pour une ressource / 2 vue multiple ressource
*  $resa : tableau associatif issu de la requête SQL sélectionnant les informations relatives à la réservation
*  $heures : plage horaire calculée dans le script $page
*/
function contenu_cellule($options, $ofl, $vue, $resa, $heures)
{
  global $dformat;
  $affichage = "";
  // Ressource seulement dans les vues globales
  if($vue == 2)
    $affichage .= $resa['room_name']."<br>";
  // Heures ou créneaux + symboles <== ==>
  if (($heures != "") && ($options['horaires']))
        $affichage .= $heures."<br>";
  // Bénéficiaire
  if ($options['beneficiaire'])
    $affichage .= affiche_nom_prenom_email($resa['beneficiaire'], $resa['beneficiaire_ext'], "nomail")."<br>";
  // Type
  if ($options["type"])
  {
    $typeResa = grr_sql_query1("SELECT t.type_name FROM ".TABLE_PREFIX."_type_area t JOIN ".TABLE_PREFIX."_entry e ON e.type=t.type_letter WHERE e.id = ?","i",[$resa['id']]);
    if ($typeResa != -1)
      $affichage .= $typeResa."<br>";
    else 
      $affichage .= "???<br>";
  }
  // Brève description ou le numéro de la réservation
  if (($options["short_desc"]) && ($resa['name'] != ""))
    $affichage .= htmlspecialchars($resa['name'],ENT_NOQUOTES)."<br>";
  // Description Complète
  if (($options["description"]) && ($resa['description'] != ""))
    $affichage .= htmlspecialchars($resa['description'],ENT_NOQUOTES)."<br>";
    // créateur
  if (($options["create_by"]) && ($resa['create_by'] != ""))
    $affichage .= htmlspecialchars($resa['create_by'],ENT_NOQUOTES)."<br>";
    // nombre de participants
    if (($options["participants"]) && ($resa['nbparticipantmax'] != 0)){
      $inscrits = grr_sql_query1("SELECT COUNT(`participant`) FROM ".TABLE_PREFIX."_participants WHERE `idresa` = ?","i",[$resa['id']]);
      if ($inscrits >= 0)
        $affichage .= get_vocab('participant_inscrit').get_vocab('deux_points').$inscrits." / ".htmlspecialchars($resa['nbparticipantmax'],ENT_NOQUOTES)."<br>";
    }
  // Champs Additionnels
  // la ressource associée à la réservation :
  $room = $resa['room_name'];
  // Les champs add :
  $overload_data = grrGetOverloadDescArray($ofl, $resa['overload_desc']);
  foreach ($overload_data as $fieldname=>$field)
  {
    if (( (authGetUserLevel(getUserName(), $room) >= 4 && $field["confidentiel"] == 'n') || $field["affichage"] == 'y') && $field["valeur"] != "") {
      // ELM - Gestion des champs additionnels multivalués
      $valeur = str_replace("|", ", ", $field["valeur"]);
      $affichage .= "<i>".htmlspecialchars($fieldname,ENT_NOQUOTES).get_vocab("deux_points").htmlspecialchars($valeur,ENT_NOQUOTES|ENT_SUBSTITUTE)."</i><br />";
    }
  }
  // cas où aucune option n'est activée : afficher le numéro de la réservation
  if ($affichage == '')
    $affichage .= get_vocab("entryid").$resa['id']."<br>";
  // Emprunte
  if($resa['statut_entry'] != "-")
    $affichage .= "<img src=\"img_grr/buzy.png\" alt=\"".get_vocab("ressource_actuellement_empruntee")."\" title=\"".get_vocab("ressource_actuellement_empruntee")."\" width=\"20\" height=\"20\" class=\"image\" /> ";
  // Option réservation
  if($resa['delais_option_reservation'] > 0)
    $affichage .=  " <img src=\"img_grr/small_flag.png\" alt=\"".get_vocab("reservation_a_confirmer_au_plus_tard_le")."\" title=\"".get_vocab("reservation_a_confirmer_au_plus_tard_le").time_date_string_jma($resa['option_reservation'],$dformat)."\" width=\"20\" height=\"20\" class=\"image\" /> ";
  // Modération
  if($resa['moderate'] == 1)
    $affichage .= " <img src=\"img_grr/flag_moderation.png\" alt=\"".get_vocab("en_attente_moderation")."\" title=\"".get_vocab("en_attente_moderation")."\" width=\"20\" height=\"20\" class=\"image\" /> ";
  // Clef
  if($resa['clef'] == 1)
    $affichage .= " <img src=\"img_grr/skey.png\" width=\"20\" height=\"20\" class=\"image\" alt=\"Clef\"> ";
  // Courrier
  if (Settings::get('show_courrier') == 'y')
  {
    if($resa['courrier'] == 1)
      $affichage .= " <img src=\"img_grr/scourrier.png\" width=\"20\" height=\"20\" class=\"image\" alt=\"Courrier\"> ";
    else
      $affichage .= " <img src=\"img_grr/hourglass.png\" width=\"20\" height=\"20\" class=\"image\" alt=\"Buzy\"> ";
  }

  return $affichage;
}
/*
** Fonction qui affiche le header (= bandeau du haut de page)
*/
function print_header($day = '', $month = '', $year = '', $type_session = 'with_session')
{
  global $search_str, $grrSettings, $clock_file, $desactive_VerifNomPrenomUser, $grr_script_name;
  global $use_prototype, $use_admin, $use_tooltip_js, $desactive_bandeau_sup, $id_site, $use_select2;
  
  if($_SESSION['changepwd'] == 1 && $grr_script_name != 'changepwd.php'){
    header("Location: ./changepwd.php");
  }

  if (@file_exists('./admin_access_area.php')){
    $adm = 1;
    $racine = "../";
    $racineAd = "./";
  }else{
    $adm = 0;
    $racine = "./";
    $racineAd = "./admin/";
  }

  include $racine."/include/hook.class.php";

  if (!($desactive_VerifNomPrenomUser))
    $desactive_VerifNomPrenomUser = 'n';
  // On vérifie que les noms et prénoms ne sont pas vides
  VerifNomPrenomUser($type_session);
  if ($type_session == "with_session")
    echo begin_page(Settings::get("company"),"with_session");
  else
    echo begin_page(Settings::get("company"),"no_session");

  Hook::Appel("hookHeader2");
  // Si nous ne sommes pas dans un format imprimable
  if ((!isset($_GET['pview'])) || ($_GET['pview'] != 1))
  {
    // If we dont know the right date then make it up
    if (!isset($day) || !isset($month) || !isset($year) || ($day == '') || ($month == '') || ($year == ''))
    {
      $date_now = time();
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
    if (!(isset($search_str)))
      $search_str = get_vocab("search_for");
    if (empty($search_str))
      $search_str = "";
    if (!(isset($desactive_bandeau_sup) && ($desactive_bandeau_sup == 1) && ($type_session != 'with_session')))
    {

      // HOOK
      Hook::Appel("hookHeader1");

      // Génération XML
      $generationXML = 1;
      if ((Settings::get("export_xml_actif") == "Oui") && ($adm == 0)){
        include "{$racine}/include/generationxml.php";
      }
      if ((Settings::get("export_xml_plus_actif") == "Oui") && ($adm == 0)){
        include "{$racine}/include/generationxmlplus.php";
      }

      // On fabrique une date valide pour la réservation si ce n'est pas le cas
      $date_ = mktime(0, 0, 0, $month, $day, $year);
      if ($date_ < Settings::get("begin_bookings"))
        $date_ = Settings::get("begin_bookings");
      else if ($date_ > Settings::get("end_bookings"))
        $date_ = Settings::get("end_bookings");
      $day   = date("d",$date_);
      $month = date("m",$date_);
      $year  = date("Y",$date_);
      echo '<div id="toppanel">'.PHP_EOL;
      echo '<div id="panel">'.PHP_EOL;
      echo '<table id="header">'.PHP_EOL;
      echo '<tr>'.PHP_EOL;
      //Logo
      $nom_picture = $racine."images/".Settings::get("logo");
      if ((Settings::get("logo") != '') && (@file_exists($nom_picture)))
        echo '<td class="logo" height="100">'.PHP_EOL.'<a href="'.$racine.page_accueil('yes').'day='.$day.'&amp;year='.$year.'&amp;month='.$month.'"><img src="'.$nom_picture.'" alt="logo"/></a>'.PHP_EOL.'</td>'.PHP_EOL;
      //Accueil
      echo '<td class="accueil ">',PHP_EOL,'<h2>',PHP_EOL,'<a href="'.$racine.page_accueil('yes'),'day=',$day,'&amp;year=',$year,'&amp;month=',$month,'">',Settings::get("company"),'</a>',PHP_EOL,'</h2>',PHP_EOL, Settings::get('message_accueil'),'</td>',PHP_EOL;
      //Mail réservation
      $sql = "SELECT value FROM ".TABLE_PREFIX."_setting WHERE name='mail_etat_destinataire'";
      $res = grr_sql_query1($sql);
      grr_sql_free($res);

      if ( ( $res == 1 && $type_session == "no_session" ) || ( ( $res == 1 || $res == 2) && $type_session == "with_session" && (authGetUserLevel(getUserName(), -1, 'area')) == 1  ) )
      {
        echo '<td class="contactformulaire">',PHP_EOL,'<input class="btn btn-default" type="submit" rel="popup_name" value="'.get_vocab('Reserver').'" onClick="javascript:location.href=\'contactFormulaire.php?day=',$day,'&amp;month=',$month,'&amp;year=',$year,'\'" >',PHP_EOL,'</td>',PHP_EOL;
      }
      // Administration
      if ($type_session == "with_session")
      {
                $user_name = getUserName();
                $mess_resa = resaToModerate($user_name);
        if ((authGetUserLevel($user_name, -1, 'area') >= 4) || (authGetUserLevel($user_name, -1, 'user') == 1) || ($mess_resa != ''))
        {
          echo '<td class="administration">'.PHP_EOL;
          if ((authGetUserLevel($user_name, -1, 'area') >= 4) || (authGetUserLevel($user_name, -1, 'user') == 1))
                        echo "<br><a href='{$racineAd}admin_accueil.php?day={$day}&amp;month={$month}&amp;year={$year}'>".get_vocab('admin')."</a>".PHP_EOL;
          if (authGetUserLevel(getUserName(), -1, 'area') >= 6)
          {
            echo '<br />'.PHP_EOL;
            how_many_connected();
                        echo "<br />";
          }
                    echo "<p class='avertissement'>".$mess_resa."</p>";
          echo '</td>'.PHP_EOL;
        }
      }
      if ($type_session != "with_session")
        echo '<script>selection()</script>'.PHP_EOL;
      echo '<td class="configuration" >'.PHP_EOL;
      if (@file_exists('js/'.$clock_file))
      {
        echo '<div class="clock">'.PHP_EOL;
        echo '<div id="Date">'.PHP_EOL;
        echo '&nbsp;<span id="hours"></span>'.PHP_EOL;
        echo 'h'.PHP_EOL;
        echo '<span id="min"></span>'.PHP_EOL;
        echo '</div></div>'.PHP_EOL;
      }
      $_SESSION['chemin_retour'] = '';
      if (isset($_SERVER['QUERY_STRING']) && ($_SERVER['QUERY_STRING'] != ''))
      {
        $parametres_url = htmlspecialchars($_SERVER['QUERY_STRING'])."&amp;";
        $_SESSION['chemin_retour'] = traite_grr_url($grr_script_name)."?". $_SERVER['QUERY_STRING'];
        echo '<a onclick="" href="'.traite_grr_url($grr_script_name).'?'.$parametres_url.'default_language=fr"><img src="'.$racine.'img_grr/fr_dp.png" alt="France" title="france" width="20" height="13" class="image" /></a>'.PHP_EOL;
        echo '<a onclick="" href="'.traite_grr_url($grr_script_name).'?'.$parametres_url.'default_language=de"><img src="'.$racine.'img_grr/de_dp.png" alt="Deutch" title="deutch" width="20" height="13" class="image" /></a>'.PHP_EOL;
        echo '<a onclick="" href="'.traite_grr_url($grr_script_name).'?'.$parametres_url.'default_language=en"><img src="'.$racine.'img_grr/en_dp.png" alt="English" title="English" width="20" height="13" class="image" /></a>'.PHP_EOL;
        echo '<a onclick="" href="'.traite_grr_url($grr_script_name).'?'.$parametres_url.'default_language=it"><img src="'.$racine.'img_grr/it_dp.png" alt="Italiano" title="Italiano" width="20" height="13" class="image" /></a>'.PHP_EOL;
        echo '<a onclick="" href="'.traite_grr_url($grr_script_name).'?'.$parametres_url.'default_language=es"><img src="'.$racine.'img_grr/es_dp.png" alt="Spanish" title="Spanish" width="20" height="13" class="image" /></a>'.PHP_EOL;
      }
      if ($type_session == 'no_session')
      {
        if ((Settings::get('sso_statut') == 'cas_visiteur') || (Settings::get('sso_statut') == 'cas_utilisateur'))
        {
          echo '<br /> <a href="index.php?force_authentification=y">'.get_vocab("authentification").'</a>'.PHP_EOL;
          echo '<br /> <small><i><a href="login.php">'.get_vocab("connect_local").'</a></i></small>'.PHP_EOL;
        }
        else {
          echo '<br /> <a href="login.php">'.get_vocab("connect").'</a>'.PHP_EOL;
        }
      }
      else
      {
        if( strlen(htmlspecialchars($_SESSION['prenom']).' '.htmlspecialchars($_SESSION['nom'])) > 40 )
          $nomAffichage =  htmlspecialchars($_SESSION['nom']);
        else
          $nomAffichage =  htmlspecialchars($_SESSION['prenom']).' '.htmlspecialchars($_SESSION['nom']);
      
        echo '<br /><a href="'.$racine.'my_account.php?day='.$day.'&amp;year='.$year.'&amp;month='.$month.'">'. $nomAffichage .' - '.get_vocab("manage_my_account").'</a>'.PHP_EOL;
        if (verif_access_search(getUserName()))
          echo '<br/><a href="'.$racine.'recherche.php">'.get_vocab("report").'</a>'.PHP_EOL;
        $disconnect_link = false;
        if (!((Settings::get("cacher_lien_deconnecter") == 'y') && (isset($_SESSION['est_authentifie_sso']))))
        {
          $disconnect_link = true;
          if (Settings::get("authentification_obli") == 1)
            echo '<br /> <a href="'.$racine.'logout.php?auto=0" >'.get_vocab('disconnect').'</a>'.PHP_EOL;
          else
            echo '<br /> <a href="'.$racine.'logout.php?auto=0&amp;redirect_page_accueil=yes" >'.get_vocab('disconnect').'</a>'.PHP_EOL;
        }
        if ((Settings::get("Url_portail_sso") != '') && (isset($_SESSION['est_authentifie_sso'])))
        {
          if ($disconnect_link)
            echo ' - '.PHP_EOL;
          else
            echo '<br />'.PHP_EOL;
          echo '<a href="'.Settings::get("Url_portail_sso").'">'.get_vocab("Portail_accueil").'</a>'.PHP_EOL;
        }
        if ((Settings::get('sso_statut') == 'lasso_visiteur') || (Settings::get('sso_statut') == 'lasso_utilisateur'))
        {
          echo '<br />';
          if ($_SESSION['lasso_nameid'] == NULL)
            echo '<a href="lasso/federate.php">'.get_vocab('lasso_federate_this_account').'</a>'.PHP_EOL;
          else
            echo '<a href="lasso/defederate.php">'.get_vocab('lasso_defederate_this_account').'</a>'.PHP_EOL;
        }
      }
      echo '</td>'.PHP_EOL;
      echo '</tr>'.PHP_EOL;
      echo '</table>'.PHP_EOL;
      echo '</div>'.PHP_EOL;
      echo '<a id="open" class="open" href="#"><span class="glyphicon glyphicon-arrow-up"><span class="glyphicon glyphicon-arrow-down"></span></span></a>'.PHP_EOL;
      echo '</div>'.PHP_EOL;
    }
  }
}
/* fonction qui affiche un message approprié lorsque la date proposée n'est pas dans l'intervalle ouvert
 * mais ne fait pas la vérification préalable
*/
function showNoBookings($day, $month, $year, $back)
{
  $date = mktime(0, 0, 0, $month, $day,$year);
  echo '<h2>'.get_vocab("nobookings").' '.affiche_date($date).'</h2>';
  echo '<p>'.get_vocab("begin_bookings").'<b>'.affiche_date(Settings::get("begin_bookings")).'</b></p>';
  echo '<p>'.get_vocab("end_bookings").'<b>'.affiche_date(Settings::get("end_bookings")).'</b></p>';
  echo "<p>";
  if ($back !=''){
    echo "<a href=".$back.">".get_vocab('returnprev')."</a>";
  }
  echo "</p>";
  echo "</body>\n</html>";
}

function bouton_retour_haut()
{
  echo '<script type="text/javascript">',PHP_EOL,'$(function()',PHP_EOL,'{',PHP_EOL,'$(window).scroll(function()',PHP_EOL,'{',PHP_EOL,
    'if ($(window).scrollTop() != 0)',PHP_EOL,'$("#toTop").fadeIn();',PHP_EOL,'else',PHP_EOL,'$("#toTop").fadeOut();',PHP_EOL,
    '});',PHP_EOL,'$("#toTop").click(function()',PHP_EOL,'{',PHP_EOL,'$("body,html").animate({scrollTop:0},800);',PHP_EOL,
    '});',PHP_EOL,'});',PHP_EOL,'</script>',PHP_EOL;
}
/* function begin_simple_page()
 * page simplifiée pour les initialisations */
function begin_simple_page($title)
{
  if(@file_exists('admin_accueil.php') || @file_exists('install_mysql.php'))
    $root = "../";
  else
    $root = "./";
  header('Content-Type: text/html; charset=utf-8');
  $a = '<!DOCTYPE html>'.PHP_EOL;
  $a .= '<html lang="fr">'.PHP_EOL;
  $a .= '<head>'.PHP_EOL;
  $a .= '<meta charset="utf-8">'.PHP_EOL;
  $a .= '<meta http-equiv="X-UA-Compatible" content="IE=edge">'.PHP_EOL;
  $a .= '<meta name="viewport" content="width=device-width, initial-scale=1">'.PHP_EOL;
  $a .= '<meta name="Robots" content="noindex" />'.PHP_EOL;
  $a .= '<title>'.$title.'</title>'.PHP_EOL;
  $a .= '<link rel="shortcut icon" href="'.$root.'favicon.ico" />'.PHP_EOL;
  $a .= '<link rel="stylesheet" type="text/css" href="'.$root.'bootstrap/css/bootstrap.min.css" />'.PHP_EOL; // les outils css de bootstrap
  $a .= '<link rel="stylesheet" type="text/css" href="'.$root.'themes/default/css/style.css" />'.PHP_EOL; // le style par défaut
  $a .= '</head>'.PHP_EOL;
  $a .= '<body>'.PHP_EOL;
  if (substr(phpversion(), 0, 3) < "5.6")
    $a .= get_vocab('not_php3');
  return $a;
}
/**
 *function affiche_ressource_empruntee
 *- $id_room : identifiant de la ressource
 *- Si la ressource est empruntée, affiche une icône avec un lien vers la réservation pour laquelle la ressource est empruntée.
 * @param string $id_room
 * @return string
 */
function affiche_ressource_empruntee($id_room, $type = "logo"){
  echo html_ressource_empruntee($id_room,$type);
}
/*function affiche_ressource_empruntee($id_room, $type = "logo")
{
  $active_ressource_empruntee = grr_sql_query1("SELECT active_ressource_empruntee FROM ".TABLE_PREFIX."_room WHERE id = ?","i",[$id_room]);
  if ($active_ressource_empruntee == 'y')
  {
    $sql = "SELECT id,beneficiaire,beneficiaire_ext FROM ".TABLE_PREFIX."_entry WHERE room_id = ? AND statut_entry='y'";
    $res = grr_sql_query($sql,"i",[$id_room]);
    if($res){
      if(grr_sql_count($res) != 0){
        $row = grr_sql_row_keyed($res,0);
        $id_resa = $row['id'];
        if ($type == "logo")
          echo '<a href="view_entry.php?id='.$id_resa.'"><img src="img_grr/buzy_big.png" alt="'.get_vocab("ressource_actuellement_empruntee").'" title="'.get_vocab("reservation_en_cours").'" width="30" height="30" class="image" /></a>'.PHP_EOL;
        else if ($type == "texte")
        {
          $beneficiaire = $row["beneficiaire"];
          $beneficiaire_ext = $row["beneficiaire_ext"];
          echo '<br /><b><span class="avertissement">'.PHP_EOL;
          echo '<img src="img_grr/buzy_big.png" alt="'.get_vocab("ressource_actuellement_empruntee").'" title="'.get_vocab("ressource_actuellement_empruntee").'" width="30" height="30" class="image" />'.PHP_EOL;
          echo get_vocab("ressource_actuellement_empruntee").' '.get_vocab("nom_emprunteur").get_vocab("deux_points").affiche_nom_prenom_email($beneficiaire,$beneficiaire_ext,"withmail");
          echo '<a href="view_entry.php?id='.$id_resa.'&amp;mode=page">'.get_vocab("entryid").$id_resa.'</a>'.PHP_EOL.'</span></b>'.PHP_EOL;
        }
        else
          return "yes";
      }
    }
  }
}*/
/**
 * @param string $type
 * @param string $t
 * @return string
 */
function bbCode($t,$type)
{
  if ($type == "nobbcode")
  {
    $t = str_replace("[/]", "", $t);
    $t = str_replace("[hr]", "", $t);
    $t = str_replace("[center]", "", $t);
    $t = str_replace("[/center]", "", $t);
    $t = str_replace("[right]", "", $t);
    $t = str_replace("[/right]", "", $t);
    $t = str_replace("[justify]", "", $t);
    $t = str_replace("[/justify]", "", $t);
    $regLienSimple = "`\[url\] ?([^\[]*) ?\[/url\]`";
    $regLienEtendu = "`\[url ?=([^\[]*) ?] ?([^]]*) ?\[/url\]`";
    if (preg_match($regLienSimple, $t))
      $t = preg_replace($regLienSimple, "\\1", $t);
    else
      $t = preg_replace($regLienEtendu, "\\1", $t);
    $regMailSimple = "`\[email\] ?([^\[]*) ?\[/email\]`";
    $regMailEtendu = "`\[email ?=([^\[]*) ?] ?([^]]*) ?\[/email\]`";
    if (preg_match($regMailSimple, $t))
      $t = preg_replace($regMailSimple, "\\1", $t);
    else
      $t = preg_replace($regMailEtendu, "\\1", $t);
    $regImage = "`\[img\] ?([^\[]*) ?\[/img\]`";
    $regImageAlternatif = "`\[img ?= ?([^\[]*) ?\]`";
    if (preg_match($regImage, $t))
      $t = preg_replace($regImage, "", $t);
    else
      $t = preg_replace($regImageAlternatif, "", $t);
    $t = str_replace("[b]", "", $t);
    $t = str_replace("[/b]", "", $t);
    $t = str_replace("[i]", "", $t);
    $t = str_replace("[/i]", "", $t);
    $t = str_replace("[u]", "", $t);
    $t = str_replace("[/u]", "", $t);
    $t = str_replace("[/color]", "</span>", $t);
    $regCouleur = "`\[color= ?(([[:alpha:]]+)|(#[[:digit:][:alpha:]]{6})) ?\]`";
    $t = preg_replace($regCouleur, "", $t);
    $t = str_replace("[/size]", "</span>", $t);
    $regCouleur = "`\[size= ?([[:digit:]]+) ?\]`";
    $t = preg_replace($regCouleur, "", $t);
  }
  if ($type != "titre")
  {
    $t = str_replace("[/]", "<hr width=\"100%\" size=\"1\" />", $t);
    $t = str_replace("[hr]", "<hr width=\"100%\" size=\"1\" />", $t);
    $t = str_replace("[center]", "<div style=\"text-align: center\">", $t);
    $t = str_replace("[/center]", "</div>", $t);
    $t = str_replace("[right]", "<div style=\"text-align: right\">", $t);
    $t = str_replace("[/right]", "</div>", $t);
    $t = str_replace("[justify]", "<div style=\"text-align: justify\">", $t);
    $t = str_replace("[/justify]", "</div>", $t);
    $regLienSimple = "`\[url\] ?([^\[]*) ?\[/url\]`";
    $regLienEtendu = "`\[url ?=([^\[]*) ?] ?([^]]*) ?\[/url\]`";
    if (preg_match($regLienSimple, $t))
      $t = preg_replace($regLienSimple, "<a href=\"\\1\">\\1</a>", $t);
    else
      $t = preg_replace($regLienEtendu, "<a href=\"\\1\" target=\"_blank\" rel=\"noopener noreferer\" >\\2</a>", $t);
  }
  $regMailSimple = "`\[email\] ?([^\[]*) ?\[/email\]\`";
  $regMailEtendu = "`\[email ?=([^\[]*) ?] ?([^]]*) ?\[/email\]`";
  if (preg_match("'".$regMailSimple."'", $t))
    $t = preg_replace($regMailSimple, "<a href=\"mailto:\\1\">\\1</a>", $t);
  else
    $t = preg_replace($regMailEtendu, "<a href=\"mailto:\\1\">\\2</a>", $t);
  $regImage = "`\[img\] ?([^\[]*) ?\[/img\]`";
  $regImageAlternatif = "`\[img ?= ?([^\[]*) ?\]`";
  if (preg_match($regImage, $t))
    $t = preg_replace($regImage, "<img src=\"\\1\" alt=\"\" class=\"image\" />", $t);
  else
    $t = preg_replace($regImageAlternatif, "<img src=\"\\1\" alt=\"\" class=\"image\" />", $t);
  $t = str_replace("[b]", "<strong>", $t);
  $t = str_replace("[/b]", "</strong>", $t);
  $t = str_replace("[i]", "<em>", $t);
  $t = str_replace("[/i]", "</em>", $t);
  $t = str_replace("[u]", "<u>", $t);
  $t = str_replace("[/u]", "</u>", $t);
  $t = str_replace("[/color]", "</span>", $t);
  $regCouleur = "/\[color= ?(([[:alpha:]]+)|(#[[:digit:][:alpha:]]{6})) ?\]/";
  $t = preg_replace($regCouleur, "<span style=\"color: \\1\">", $t);
  $t = str_replace("[/size]", "</span>", $t);
  $regCouleur = "`\[size= ?([[:digit:]]+) ?\]`";
  $t = preg_replace($regCouleur, "<span style=\"font-size: \\1px\">", $t);
  return $t;
}
function begin_page($title, $page = "with_session")
{
  if ($page == "with_session")
  {
    if (isset($_SESSION['default_style']))
      $sheetcss = 'themes/'.$_SESSION['default_style'].'/css';
    else
      $sheetcss = 'themes/default/css'; // utilise le thème par défaut s'il n'a pas été défini... à voir YN le 11/04/2018
    if (isset($_GET['default_language']))
    {
      $_SESSION['default_language'] = clean_input($_GET['default_language']);
      if (isset($_SESSION['chemin_retour']) && ($_SESSION['chemin_retour'] != ''))
        header("Location: ".$_SESSION['chemin_retour']);
      else
        header("Location: ".traite_grr_url());
      die();
    }
  }
  else
  {
    if (Settings::get("default_css"))
      $sheetcss = 'themes/'.Settings::get("default_css").'/css';
    else
      $sheetcss = 'themes/default/css';
    if (isset($_GET['default_language']))
    {
      $_SESSION['default_language'] = clean_input($_GET['default_language']);
      if (isset($_SESSION['chemin_retour']) && ($_SESSION['chemin_retour'] != ''))
        header("Location: ".$_SESSION['chemin_retour']);
      else
        header("Location: ".traite_grr_url());
      die();
    }
  }
  global $clock_file, $use_select2, $use_admin;
  header('Content-Type: text/html; charset=utf-8');
  if (!isset($_COOKIE['open']))
  {
    header('Set-Cookie: open=true; SameSite=Strict');
  }
  $a = '<!DOCTYPE html>'.PHP_EOL;
  $a .= '<html lang="fr">'.PHP_EOL;
  $a .= '<head>'.PHP_EOL;
  $a .= '<meta charset="utf-8">'.PHP_EOL;
  $a .= '<meta http-equiv="X-UA-Compatible" content="IE=edge">'.PHP_EOL;
  $a .= '<meta name="viewport" content="width=device-width, initial-scale=1">'.PHP_EOL;
  $a .= '<meta name="Robots" content="noindex" />'.PHP_EOL;
  $a .= '<title>'.$title.'</title>'.PHP_EOL;

  if (@file_exists('admin_accueil.php') || @file_exists('install_mysql.php')){ // Si on est dans l'administration
    $a .= '<link rel="shortcut icon" href="../favicon.ico" />'.PHP_EOL;
    $a .= '<link rel="stylesheet" type="text/css" href="../bootstrap/css/bootstrap.min.css" />'.PHP_EOL;
    $a .= '<link rel="stylesheet" type="text/css" href="../include/admin_grr.css" />'.PHP_EOL;
    $a .= '<link rel="stylesheet" type="text/css" href="../bootstrap/css/select2.css" />'.PHP_EOL;
    $a .= '<link rel="stylesheet" type="text/css" href="../bootstrap/css/select2-bootstrap.css" />'.PHP_EOL;
    $a .= '<link rel="stylesheet" type="text/css" href="../bootstrap/css/jquery-ui.min.css" />'.PHP_EOL;
    $a .= '<link rel="stylesheet" type="text/css" href="../bootstrap/css/jquery-ui-timepicker-addon.css" >'.PHP_EOL;
    $a .= '<link rel="stylesheet" type="text/css" href="../bootstrap/css/bootstrap-multiselect.css">'.PHP_EOL;
    $a .= '<link rel="stylesheet" type="text/css" href="../bootstrap/css/bootstrap-clockpicker.min.css">'.PHP_EOL;
    $a .= '<link rel="stylesheet" type="text/css" href="../themes/default/css/style.css" />'.PHP_EOL; // le style par défaut
    $a .= '<link rel="stylesheet" type="text/css" href="../'.$sheetcss.'/style.css" />'.PHP_EOL; // le style personnalisé
    if ((isset($_GET['pview'])) && ($_GET['pview'] == 1))
      $a .= '<link rel="stylesheet" type="text/css" href="../themes/print/css/style.css" />'.PHP_EOL;
    $a .= '<script type="text/javascript" src="../js/jquery-3.7.1.min.js"></script>'.PHP_EOL;
    $a .= '<script type="text/javascript" src="../js/jquery-ui.min.js"></script>'.PHP_EOL;
    $a .= '<script type="text/javascript" src="../js/jquery.validate.js"></script>'.PHP_EOL;
    $a .= '<script type="text/javascript" src="../js/jquery-ui-timepicker-addon.js"></script>'.PHP_EOL;
    $a .= '<script type="text/javascript" src="../bootstrap/js/bootstrap.min.js"></script>'.PHP_EOL;
    $a .= '<script type="text/javascript" src="../js/bootstrap-clockpicker.js"></script>'.PHP_EOL;
    $a .= '<script type="text/javascript" src="../js/bootstrap-multiselect.js"></script>'.PHP_EOL;
    $a .= '<script type="text/javascript" src="../js/html2canvas.js"></script>'.PHP_EOL;
    $a .= '<script type="text/javascript" src="../js/menu.js"></script>'.PHP_EOL;
    $a .= '<script type="text/javascript" src="../js/jspdf.min.js"></script>'.PHP_EOL;
    // $a .= '<script type="text/javascript" src="../js/pdf.js" ></script>'.PHP_EOL;
    $a .= '<script type="text/javascript" src="../js/popup.js" charset="utf-8"></script>'.PHP_EOL;
    $a .= '<script type="text/javascript" src="../js/functions.js" ></script>'.PHP_EOL;
    $a .= '<script type="text/javascript" src="../js/select2.js"></script>'.PHP_EOL;
    $a .= '<script type="text/javascript" src="../js/select2_locale_fr.js"></script>'.PHP_EOL;
    $a .= '<script type="text/javascript" src="../js/jquery-ui-i18n.min.js"></script>'.PHP_EOL;
    //if (isset($use_tooltip_js))
      //echo '<script type="text/javascript" src="../js/tooltip.js"></script>'.PHP_EOL;
    if (!isset($_SESSION['selection']))
      $a .= '<script type="text/javascript" src="../js/selection.js" ></script>'.PHP_EOL;
    if (@file_exists('../js/'.$clock_file))
      $a .= '<script type="text/javascript" src="../js/'.$clock_file.'"></script>'.PHP_EOL;
    } 
  else
  { 
    $a .= '<link rel="shortcut icon" href="./favicon.ico" />'.PHP_EOL;
    $a .= '<link rel="stylesheet" type="text/css" href="bootstrap/css/bootstrap.min.css" />'.PHP_EOL;
    if (isset($use_select2))
    {
      $a .= '<link rel="stylesheet" type="text/css" href="bootstrap/css/select2.css" />'.PHP_EOL;
      $a .= '<link rel="stylesheet" type="text/css" href="bootstrap/css/select2-bootstrap.css" />'.PHP_EOL;
      $a .= '<link rel="stylesheet" type="text/css" href="bootstrap/css/bootstrap-multiselect.css">'.PHP_EOL;
      $a .= '<link rel="stylesheet" type="text/css" href="bootstrap/css/bootstrap-clockpicker.min.css">'.PHP_EOL;
    }
    $a .= '<link rel="stylesheet" type="text/css" href="bootstrap/css/jquery-ui.min.css" />'.PHP_EOL;
    $a .= '<link rel="stylesheet" type="text/css" href="bootstrap/css/jquery-ui-timepicker-addon.css" >'.PHP_EOL;
    $a .= '<link rel="stylesheet" type="text/css" href="themes/default/css/style.css" />'.PHP_EOL; // le style par défaut
    $a .= '<link rel="stylesheet" type="text/css" href="'.$sheetcss.'/style.css" />'.PHP_EOL; // le style personnalisé
    if (isset($use_admin))
      $a .= '<link rel="stylesheet" type="text/css" href="include/admin_grr.css" />'.PHP_EOL;
    if ((isset($_GET['pview'])) && ($_GET['pview'] == 1))
      $a .= '<link rel="stylesheet" type="text/css" href="themes/print/css/style.css" />'.PHP_EOL;
    $a .= '<script type="text/javascript" src="js/jquery-3.7.1.min.js"></script>'.PHP_EOL;
    $a .= '<script type="text/javascript" src="js/jquery-ui.min.js"></script>'.PHP_EOL;
    $a .= '<script type="text/javascript" src="js/jquery-ui-i18n.min.js"></script>'.PHP_EOL;
    $a .= '<script type="text/javascript" src="js/jquery.validate.js"></script>'.PHP_EOL;
    $a .= '<script type="text/javascript" src="js/jquery-ui-timepicker-addon.js"></script>'.PHP_EOL;
    $a .= '<script type="text/javascript" src="bootstrap/js/bootstrap.min.js"></script>'.PHP_EOL;
    $a .= '<script type="text/javascript" src="js/html2canvas.js"></script>'.PHP_EOL;
    $a .= '<script type="text/javascript" src="js/menu.js"></script>'.PHP_EOL;
    $a .= '<script type="text/javascript" src="js/jspdf.min.js"></script>'.PHP_EOL;
    // $a .= '<script type="text/javascript" src="js/pdf.js" ></script>'.PHP_EOL;
    $a .= '<script type="text/javascript" src="js/popup.js" charset="utf-8"></script>'.PHP_EOL;
    $a .= '<script type="text/javascript" src="js/functions.js" ></script>'.PHP_EOL;
    if (isset($use_select2))
    {
      $a .= '<script type="text/javascript" src="js/bootstrap-clockpicker.js"></script>'.PHP_EOL;
      $a .= '<script type="text/javascript" src="js/bootstrap-multiselect.js"></script>'.PHP_EOL;
      $a .= '<script type="text/javascript" src="js/select2.js"></script>'.PHP_EOL;
      $a .= '<script type="text/javascript" src="js/select2_locale_fr.js"></script>'.PHP_EOL;
    }
  //if (isset($use_tooltip_js))
    //echo '<script type="text/javascript" src="./js/tooltip.js"></script>'.PHP_EOL;
    if (!isset($_SESSION['selection']))
      $a .= '<script type="text/javascript" src="js/selection.js" ></script>'.PHP_EOL;
    if (@file_exists('js/'.$clock_file))
      $a .= '<script type="text/javascript" src="js/'.$clock_file.'"></script>'.PHP_EOL;
  }
  $a .= '</head>'.PHP_EOL;
  $a .= '<body>'.PHP_EOL;
  if (substr(phpversion(), 0, 3) < "5.6")
  $a .= get_vocab('not_php3');

  return $a;
}
/* function jQuery_DatePicker($typeDate)
 * fonction qui rend un sélecteur de date couplé à un calendrier jQuery-DatePicker
 * définit trois input : $typeDate.'day', $typeDate.'month', $typeDate.'year'
 * /!\ changement de spécification : le préfixe $typeDate doit comporter un éventuel '_'
*/
function jQuery_DatePicker($typeDate){
  global $locale;
  if (@file_exists('../include/connect.inc.php')){
    $racine = "../";
  } else{
    $racine = "./";
  }

  if ($typeDate == 'rep_end_' && isset($_GET['id'])){
    $res = grr_sql_query("SELECT repeat_id FROM ".TABLE_PREFIX."_entry WHERE id=?","i",[$_GET['id']]);
    if (!$res){
      fatal_error(0, grr_sql_error());
    }
    $repeat_id = implode('', grr_sql_row($res, 0));
    $res = grr_sql_query("SELECT rep_type, end_date, rep_opt, rep_num_weeks, start_time, end_time FROM ".TABLE_PREFIX."_repeat WHERE id=?","i",[$repeat_id]);
    if (!$res){
      fatal_error(0, grr_sql_error());
    }
    if (grr_sql_count($res) == 1){
      $row6 = grr_sql_row($res, 0);
      $date = date_parse(date("Y-m-d H:i:s",$row6[1]));
      $day = $date['day'];
      $month = $date['month'];
      $year = $date['year'];
    }
    else{
      $day = (isset ($_GET['day'])) ? clean_input($_GET['day']) : date("d");
      $month = (isset ($_GET['month']))? clean_input($_GET['month']) : date("m");
      $year = (isset ($_GET['year']))? clean_input($_GET['year']) : date("Y");
    }
  }
  else{
    global $start_day, $start_month, $start_year, $end_day, $end_month, $end_year;

    $day = (isset ($_GET['day'])) ? clean_input($_GET['day']) : date("d");
    if (isset($start_day) && $typeDate=='start_'){
      $day = $start_day;
    } 
    elseif (isset($end_day) && $typeDate=='end_'){
      $day = $end_day;
    }
    $month = (isset ($_GET['month']))? clean_input($_GET['month']) : date("m");
    if (isset($start_month) && $typeDate=='start_'){
      $month = $start_month;
    } 
    elseif (isset($end_month) && $typeDate=='end_'){
      $month = $end_month;
    }
    $year = (isset ($_GET['year']))? clean_input($_GET['year']) : date("Y");
    if (isset($start_year) && $typeDate=='start_'){
      $year = $start_year;
    } 
    elseif (isset($end_year) && $typeDate=='end_'){
      $year = $end_year;
    }
  }
  $mindate = utf8_strftime("%d/%m/%Y",Settings::get('begin_bookings'));
  $maxdate = utf8_strftime("%d/%m/%Y",Settings::get('end_bookings'));
  genDateSelector("".$typeDate, "$day", "$month", "$year","");
  echo '<input type="hidden" disabled="disabled" id="mydate_' .$typeDate. '">'.PHP_EOL;
  echo '<script type="text/javascript">'.PHP_EOL;
  echo '$(\'#mydate_' .$typeDate. '\').datepicker($.datepicker.regional["'.$locale.'"] );'.PHP_EOL;
  echo '  $(\'#mydate_' .$typeDate. '\').datepicker("option",{'.PHP_EOL;
  echo '    beforeShow: readSelected, onSelect: updateSelected,'.PHP_EOL;
  echo '    showOn: \'both\', buttonImageOnly: true, buttonImage: \'img_grr/calendar.png\',buttonText: "'.get_vocab('choose_date').'",'.PHP_EOL;
  //echo '      dayNamesMin: [ "Di","Lu","Ma","Me","Je","Ve","Sa" ],'.PHP_EOL;
  echo '      minDate:\''.$mindate.'\','.PHP_EOL;
  echo '      maxDate:\''.$maxdate.'\','.PHP_EOL;
  echo '      dateFormat:"dd/mm/yy",'.PHP_EOL;
  echo '});'.PHP_EOL;
  echo '    function readSelected()'.PHP_EOL;
  echo '    {'.PHP_EOL;
  echo '      $(\'#mydate_' .$typeDate. '\').val($(\'#' .$typeDate. 'day\').val() + \'/\' +'.PHP_EOL;
  echo '      $(\'#' .$typeDate. 'month\').val() + \'/\' + $(\'#' .$typeDate. 'year\').val());'.PHP_EOL;
  echo '      return {};'.PHP_EOL;
  echo '    }'.PHP_EOL;
  echo '    function updateSelected(date)'.PHP_EOL;
  echo '    {'.PHP_EOL;
  echo '      $(\'#' .$typeDate. 'day\').val(date.substring(0, 2));'.PHP_EOL;
  echo '      $(\'#' .$typeDate. 'month\').val(date.substring(3, 5));'.PHP_EOL;
  echo '      $(\'#' .$typeDate. 'year\').val(date.substring(6, 10));'.PHP_EOL;
  echo '    }'.PHP_EOL;
  echo '</script>'.PHP_EOL;
}
/* function jQuery_TimePicker2()
* paramètres :
* $typeTime : chaîne décrivant le nom donné au champ 
* start_hour, start_min : entiers donnant l'heure à afficher par défaut
* $resolution : pas de l'affichage horaire
* $morningstarts : heure de départ du sélecteur
* $eveningends, eveningends_minutes : heure et minutes de fin de journée
* $twentyfourhour_format : format de l'affichage
* rend :
* un sélecteur de temps avec une horloge et le code javascript d'activation
*/
function jQuery_TimePicker2($typeTime, $start_hour, $start_min,$resolution,$morningstarts,$eveningends,$eveningends_minutes,$twentyfourhour_format=0)
{
  $minTime = str_pad($morningstarts, 2, 0, STR_PAD_LEFT).":00";
  $end_time = mktime($eveningends,$eveningends_minutes,0,0,0,0);
  if($typeTime == "start_")
    $end_time -= $resolution;
  $maxTime = date("H:i",$end_time);
  if($maxTime == "00:00")
    $maxTime = "24:00";
  $hour = str_pad($start_hour, 2, 0, STR_PAD_LEFT);
  $minute = str_pad($start_min, 2, 0, STR_PAD_LEFT);
  if (($hour.":".$minute) < $minTime){
    $hour = $morningstarts;
    $minute = "00";
  }
  if (($hour.":".$minute) > $maxTime){
    $maxTime_split = explode(":",$maxTime);
    $hour = str_pad($maxTime_split[0], 2, 0, STR_PAD_LEFT);
    $minute = str_pad($maxTime_split[1], 2, 0, STR_PAD_LEFT);
  }
  $timeFormat = ($twentyfourhour_format)? "H:i" : "h:i a";
  echo '<label for="'.$typeTime.'">'.get_vocab('time').get_vocab('deux_points').'</label>
    <div class="input-group timepicker">';
  echo '<input id="'.$typeTime.'" name="'.$typeTime.'" type="text" class="form-control time" value="'.$hour.':'.$minute. '" >
    <span class="input-group-addon btn" id="'.$typeTime.'clock'.'">
      <span class="glyphicon glyphicon-time" ></span>
    </span>
  </div>';
  echo '<script type="text/javascript">
      $(\'#'.$typeTime.'\').timepicker({
          \'step\': '.($resolution/60).',
          \'scrollDefault\': \''.$hour.':'.$minute.'\',
          \'minTime\': \''.$minTime.'\',
          \'maxTime\': \''.$maxTime.'\',
          \'timeFormat\': \''.$timeFormat.'\',
          \'forceRoundTime\': true,
      });
      $(\'#'.$typeTime.'\').timepicker(\'setTime\', \''.$hour.':'.$minute.'\');
      $(\'#'.$typeTime.'clock'.'\').on(\'click\', function() {
          $(\'#'.$typeTime.'\').timepicker(\'show\');
      });
      </script>';
}

function returnmsg($type,$test, $status, $msg = '')
{
  echo encode_message_utf8('<div class="alert alert-'.$type.'" role="alert"><h3>'.$test);
  echo encode_message_utf8($status)."</h3>";
  if ($msg != '')
    echo encode_message_utf8("($msg)"),PHP_EOL;
  echo '</div>',PHP_EOL;
}
//Display the entry-type color key. This has up to 2 rows, up to 10 columns.
function show_colour_key($area_id)
{
  $sql = "SELECT DISTINCT t.id, t.type_name, t.type_letter, t.order_display FROM `".TABLE_PREFIX."_type_area` t
  LEFT JOIN `".TABLE_PREFIX."_j_type_area` j on j.id_type=t.id
  WHERE (j.id_area IS NULL or j.id_area != ?)
  AND NOT EXISTS (SELECT y.id_type FROM `".TABLE_PREFIX."_j_type_area` y WHERE y.id_type = j.id_type and id_area=?)
  ORDER BY t.order_display";
  $res = grr_sql_query($sql,"ii",[$area_id,$area_id]);
  echo '<table class="legende">';
  echo '<caption>'.get_vocab("show_color_key").'</caption>'.PHP_EOL;
  if ($res)
  {
    $nct = -1;
    $i = 0;
    foreach($res as $row)
    {
      $type_name = $row["type_name"];
      $type_letter = $row["type_letter"];
      if ($nct == -1)
        echo '<tr>'.PHP_EOL;
      if (++$nct == 2)
      {
        $nct = 0;
        echo '</tr>'.PHP_EOL, '<tr>'.PHP_EOL;
      }
      tdcell($type_letter);
      echo $type_name, '</td>'.PHP_EOL;
      $i++;
    }
    if ($i % 2 == 1)
      echo '<td></td>',PHP_EOL;
    echo '</tr>'.PHP_EOL;
  }
  echo '</table>'.PHP_EOL;
}
/*
Construit les informations à afficher sur les plannings
*/
function affichage_lien_resa_planning($breve_description, $id_resa)
{
  if ((Settings::get("display_short_description") == 1) && ($breve_description != ""))
    $affichage = $breve_description;
  else
    $affichage = get_vocab("entryid").$id_resa;
  return bbCode(htmlspecialchars($affichage,ENT_NOQUOTES),'titre');
}
/* Fonction qui affiche, si un mail n'a pas pu être envoyé, un pop-up avec le message d'erreur
*/
function display_mail_msg()
{
  if (!(Settings::get("javascript_info_disabled")))
  {
    if ((isset($_SESSION['session_message_error'])) && ($_SESSION['session_message_error'] != ''))
    {
      echo "<script type=\"text/javascript\">";
      echo "<!--\n";
      echo " alert(\"".get_vocab("title_automatic_mail")."\\n".$_SESSION['session_message_error']."\\n".get_vocab("technical_contact")."\")";
      echo "//-->";
      echo "</script>";
      $_SESSION['session_message_error'] = "";
    }
  }
}
/**
 * Menu affichage des sites via select
 *
 * @param string $link
 * @param string $current_site
 * @param string $year
 * @param string $month
 * @param string $day
 * @param string $user
 * @param string $pos
 * @return string
 */
function make_site_select_html($link, $current_site, $year, $month, $day, $user, $pos="G")
{
  if (Settings::get("module_multisite") == "Oui")
  {
    $sql = "SELECT id,sitename
    FROM ".TABLE_PREFIX."_site
    ORDER BY sitename";
    $sites = grr_sql_query($sql);
    if ($sites)
    {
      $Sites = array();
      foreach($sites as $site)
      {
        // Pour chaque site, on détermine s'il y a des domaines visibles par l'utilisateur
        $sql = "SELECT id_area
        FROM ".TABLE_PREFIX."_j_site_area
        WHERE ".TABLE_PREFIX."_j_site_area.id_site=?";
        $areas = grr_sql_query($sql,"i",[$site['id']]);
        if ($areas && grr_sql_count($areas) > 0)
        {
          foreach($areas as $area)
          {
            if (authUserAccesArea($user,$area['id_area']) == 1) // on a trouvé un domaine autorisé
            {
              $Sites[] = $site;
              break;  // On arrête la boucle
            }
          }
        }
        // On libère la réponse
        grr_sql_free($areas);
      } // $Sites contient les (id,sitename) des sites contenant au moins un domaine accessible par $user
      $nb_sites_a_afficher = count($Sites);
    }
    if ($nb_sites_a_afficher >0)
    {
      $out = array();
      foreach($Sites as $site){
        $selected = ($site['id'] == $current_site) ? 'selected="selected"' : '';
        $link2 = $link.'?year='.$year.'&amp;month='.$month.'&amp;day='.$day.'&amp;id_site='.$site['id'];
        $out[] = '<option '.$selected.' value="'.$link2.'">'.htmlspecialchars($site['sitename']).'</option>'.PHP_EOL;
      }
      $out_html = '<b><i>'.get_vocab('sites').get_vocab('deux_points').'</i></b><form id="site_'.$pos.'" action="'.$_SERVER['PHP_SELF'].'"><div>';
      $out_html .= '<select class="form-control" name="site" onchange="site_go_'.$pos.'()">';
      foreach($out as $row){
        $out_html .= $row;
      }
      $out_html .= "</select>".PHP_EOL;
      $out_html .= "</div>".PHP_EOL;
      $out_html .= "<script type=\"text/javascript\">".PHP_EOL;
      $out_html .= "function site_go_".$pos."(n)".PHP_EOL;
      $out_html .= "{".PHP_EOL;
      $out_html .= "box = document.getElementById(\"site_".$pos."\").site;".PHP_EOL;
      $out_html .= "destination = box.options[box.selectedIndex].value;".PHP_EOL;
      $out_html .= "if (destination) location.href = destination;".PHP_EOL;
      $out_html .= "}".PHP_EOL;
      $out_html .= "</script>".PHP_EOL;
      $out_html .= "<noscript>".PHP_EOL;
      $out_html .= "<div>".PHP_EOL;
      $out_html .= "<input type=\"submit\" value=\"Change\" />".PHP_EOL;
      $out_html .= "</div>".PHP_EOL;
      $out_html .= "</noscript>".PHP_EOL;
      $out_html .= "</form>".PHP_EOL;
      return $out_html;
    }
    else
      return '';
  }
  else 
    return '';
}
/**
 * Menu affichage des area via select
 *
 * @param string $link
 * @param string $current_site
 * @param string $current_area
 * @param string $year
 * @param string $month
 * @param string $day
 * @param string $user
 * @param string $pos
 * @return string
 */
function make_area_select_html( $link, $current_site, $current_area, $year, $month, $day, $user, $pos="G")
{
  $out_html = "";
  if (Settings::get("module_multisite") == "Oui")
  {
    // on a activé les sites
    if ($current_site != -1)
      $sql = "SELECT a.id, a.area_name,a.access FROM ".TABLE_PREFIX."_area a JOIN ".TABLE_PREFIX."_j_site_area j ON a.id=j.id_area WHERE j.id_site=$current_site ORDER BY a.order_display, a.area_name";
    else // $current_site = -1 correspond à un domaine (ou une ressource) inconnu
      return $out_html;
  }
  else
    $sql = "SELECT id, area_name,access FROM ".TABLE_PREFIX."_area ORDER BY order_display, area_name";
  $out_html .= '<b><i>'.get_vocab("areas").'</i></b>'.PHP_EOL;
  $out_html .= '<form id="area_'.$pos.'" action="'.$_SERVER['PHP_SELF'].'">'.PHP_EOL;
  $out_html .= '<div><select class="form-control" name="area" ';
  $out_html .= ' onchange="area_go_'.$pos.'()" ';
  $out_html .= '>'.PHP_EOL;
  $res = grr_sql_query($sql);
  if ($res)
  {
    foreach($res as $row)
    {
      $selected = ($row['id'] == $current_area) ? 'selected="selected"' : "";
      $link2 = $link.'?year='.$year.'&amp;month='.$month.'&amp;day='.$day.'&amp;area='.$row['id'];
      if (authUserAccesArea($user,$row['id']) == 1)
      {
        $out_html .= '<option '.$selected.' value="'.$link2.'">'.htmlspecialchars($row['area_name']).'</option>'.PHP_EOL;
      }
    }
  }
  $out_html .= '</select>'.PHP_EOL;
  $out_html .= '</div>'.PHP_EOL;
  $out_html .= '<script type="text/javascript">'.PHP_EOL;
  $out_html .= 'function area_go_'.$pos.'()'.PHP_EOL;
  $out_html .= '{'.PHP_EOL;
  $out_html .= 'box = document.getElementById("area_'.$pos.'").area;'.PHP_EOL;
  $out_html .= 'destination = box.options[box.selectedIndex].value;'.PHP_EOL;
  $out_html .= 'if (destination) location.href = destination;'.PHP_EOL;
  $out_html .= '}'.PHP_EOL;
  $out_html .= '</script>'.PHP_EOL;
  $out_html .= '<noscript>'.PHP_EOL;
  $out_html .= '<div>'.PHP_EOL;
  $out_html .= '<input type="submit" value="Change" />'.PHP_EOL;
  $out_html .= '</div>'.PHP_EOL;
  $out_html .= '</noscript>'.PHP_EOL;
  $out_html .= '</form>'.PHP_EOL;
  return $out_html;
}
/**
 * sélecteur de domaines, y compris tous les domaines d'un site
 * area selector, including any area in a site
 *
 * @param string $link
 * @param string $current_site
 * @param string $current_area
 * @param string $year
 * @param string $month
 * @param string $day
 * @param string $user
 * @return string
 */
function make_area_select_all_html( $link, $current_site, $current_area, $year, $month, $day, $user)
{
  if (Settings::get("module_multisite") == "Oui")
    $use_multi_site = 'y';
  else
    $use_multi_site = 'n';
  if ($use_multi_site == 'y')
  {
    // on a activé les sites
    if ($current_site != -1)
      $sql = "SELECT a.id, a.area_name,a.access FROM ".TABLE_PREFIX."_area a JOIN ".TABLE_PREFIX."_j_site_area j ON a.id=j.id_area WHERE j.id_site=$current_site ORDER BY a.order_display, a.area_name";
    else
      return "";
  }
  else
    $sql = "SELECT id, area_name,access FROM ".TABLE_PREFIX."_area ORDER BY order_display, area_name";
  $out_html = '<b><i>'.get_vocab("areas").'</i></b>'.PHP_EOL;
  $out_html .= '<form id="area_001" action="'.$_SERVER['PHP_SELF'].'">'.PHP_EOL;
  $out_html .= '<div><select class="form-control" name="area" ';
  $out_html .= ' onchange="area_go()" ';
  $out_html .= '>'.PHP_EOL;
  $out_html .= "<option value=\"".$link."_all.php?year=$year";
  if ($current_site != -1) 
      $out_html .= "&amp;site=$current_site";
  $out_html .= " \">".get_vocab("any_area")."</option>";
  $res = grr_sql_query($sql);
  if ($res)
  {
    foreach($res as $row)
    {
      $selected = ($row['id'] == $current_area) ? 'selected="selected"' : "";
      $link2 = $link.'.php?year='.$year.'&amp;month='.$month.'&amp;day='.$day.'&amp;area='.$row['id'];
      if (authUserAccesArea($user,$row['id']) == 1)
      {
        $out_html .= '<option '.$selected.' value="'.$link2.'">'.htmlspecialchars($row['area_name']).'</option>'.PHP_EOL;
      }
    }
  }
  $out_html .= '</select>'.PHP_EOL;
  $out_html .= '</div>'.PHP_EOL;
  $out_html .= '<script type="text/javascript">'.PHP_EOL;
  $out_html .= 'function area_go()'.PHP_EOL;
  $out_html .= '{'.PHP_EOL;
  $out_html .= 'box = document.getElementById("area_001").area;'.PHP_EOL;
  $out_html .= 'destination = box.options[box.selectedIndex].value;'.PHP_EOL;
  $out_html .= 'if (destination) location.href = destination;'.PHP_EOL;
  $out_html .= '}'.PHP_EOL;
  $out_html .= '</script>'.PHP_EOL;
  $out_html .= '<noscript>'.PHP_EOL;
  $out_html .= '<div>'.PHP_EOL;
  $out_html .= '<input type="submit" value="Change" />'.PHP_EOL;
  $out_html .= '</div>'.PHP_EOL;
  $out_html .= '</noscript>'.PHP_EOL;
  $out_html .= '</form>'.PHP_EOL;
  return $out_html;
}
/**
 * Menu gauche affichage des room via select
 *
 * @param string $link
 * @param string $current_area
 * @param string $current_room
 * @param string $year
 * @param string $month
 * @param string $day
 * @param string $pos
 * @return string
 */
function make_room_select_html($link, $current_area, $current_room, $year, $month, $day, $pos="G")
{
  $sql = "SELECT id, room_name, description FROM ".TABLE_PREFIX."_room WHERE area_id=? ORDER BY order_display,room_name";
  $res = grr_sql_query($sql,"i",[$current_area]);
  if ($res && (grr_sql_count($res)>0)) // il y a des ressources à afficher
  {
    $out_html = "<b><i>".get_vocab('rooms').get_vocab("deux_points")."</i></b><br /><form id=\"room_".$pos."\" action=\"".$_SERVER['PHP_SELF']."\"><div><select class=\"form-control\" name=\"room\" onchange=\"room_go_".$pos."()\">";
    $out_html .= "<option value=\"".$link;
    if ($link != "day"){$out_html .= "_all";}
    $out_html .= ".php?year=$year&amp;month=$month&amp;day=$day&amp;area=$current_area\">".get_vocab("all_rooms")."</option>";
    foreach($res as $row)
    {
      if (verif_acces_ressource(getUserName(),$row['id']))
      {
        if ($row["description"])
          $temp = " (".$row["description"].")";
        else
          $temp = "";
        $selected = ($row['id'] == $current_room) ? "selected=\"selected\"" : "";
        $link2 = $link.'.php?year='.$year.'&amp;month='.$month.'&amp;day='.$day.'&amp;room='.$row['id'];
        $out_html .= "<option $selected value=\"$link2\">" . htmlspecialchars($row["room_name"].$temp)."</option>".PHP_EOL;
      }
    }
    $out_html .= "</select>".PHP_EOL;
    $out_html .= "</div>".PHP_EOL;
    $out_html .= "<script type=\"text/javascript\">".PHP_EOL;
    $out_html .= "function room_go_".$pos."()".PHP_EOL;
    $out_html .= " {".PHP_EOL;
    $out_html .= "box = document.getElementById(\"room_".$pos."\").room;".PHP_EOL;
    $out_html .= "destination = box.options[box.selectedIndex].value;".PHP_EOL;
    $out_html .= "if (destination) location.href = destination;".PHP_EOL;
    $out_html .= "}".PHP_EOL;
    $out_html .= "</script>".PHP_EOL;
    $out_html .= "<noscript>".PHP_EOL;
    $out_html .= "<div>".PHP_EOL;
    $out_html .= "<input type=\"submit\" value=\"Change\" />".PHP_EOL;
    $out_html .= "</div>".PHP_EOL;
    $out_html .= "</noscript>".PHP_EOL;
    $out_html .= "</form>".PHP_EOL;
    return $out_html;
  }
}
/**
 * Affichage des sites sous la forme d'une liste
 *
 * @param string $link
 * @param string $current_site
 * @param string $year
 * @param string $month
 * @param string $day
 * @param string $user
 * @return string
 */
function make_site_list_html($link, $current_site, $year, $month, $day,$user)
{
  if (Settings::get("module_multisite") == "Oui")
  {
    $sql = "SELECT id,sitename
    FROM ".TABLE_PREFIX."_site
    ORDER BY sitename";
    $sites = grr_sql_query($sql);
    if ($sites)
    {
      $Sites = array();
      foreach($sites as $site)
      {
        // Pour chaque site, on détermine s'il y a des domaines visibles par l'utilisateur
        $sql = "SELECT id_area
        FROM ".TABLE_PREFIX."_j_site_area
        WHERE ".TABLE_PREFIX."_j_site_area.id_site=?";
        $areas = grr_sql_query($sql,"i",[$site['id']]);
        if ($areas && grr_sql_count($areas) > 0)
        {
          foreach($areas as $area)
          {
            if (authUserAccesArea($user,$area['id_area']) == 1) // on a trouvé un domaine autorisé
            {
              $Sites[] = $site;
              break;  // On arrête la boucle
            }
          }
        }
        // On libère la réponse
        grr_sql_free($areas);
      } // $Sites contient les (id,sitename) des sites contenant au moins un domaine accessible par $user
      $nb_sites_a_afficher = count($Sites);
      if ($nb_sites_a_afficher >0)
      {
        $out = array();
        foreach($Sites as $site){
          if ($site['id'] == $current_site)
          {
            $out[] = '
            <b><a id="liste_select"   href="'.$link.'?year='.$year.'&amp;month='.$month.'&amp;day='.$day.'&amp;id_site='.$site['id'].'" title="'.$site['sitename'].'">&gt; '.htmlspecialchars($site['sitename']).'</a></b>
            <br />'."\n";
          }
          else
          {
            $out[] = '
            <a id="liste"  href="'.$link.'?year='.$year.'&amp;month='.$month.'&amp;day='.$day.'&amp;id_site='.$site['id'].'" title="'.$site['sitename'].'">'.htmlspecialchars($site['sitename']).'</a>
            <br />'."\n";
          }
        }
        $out_html = '<b><i><span class="bground">'.get_vocab('sites').get_vocab('deux_points').'</span></i></b><br />';
        foreach($out as $row){
          $out_html .= $row;
        }
        return $out_html;
      }
    }
    else
      return '';
  }
  else 
    return '';
}
/**
 * Affichage des area sous la forme d'une liste
 *
 * @param string $link
 * @param string $current_site
 * @param string $current_area
 * @param string $year
 * @param string $month
 * @param string $day
 * @param string $user
 * @return string
 */
function make_area_list_html($link, $current_site, $current_area, $year, $month, $day, $user)
{
  if (Settings::get("module_multisite") == "Oui")
    $use_multi_site = 'y';
  else
    $use_multi_site = 'n';
  $out_html = "<b><i><span class=\"bground\">".get_vocab("areas")."</span></i></b><br />";
  if ($use_multi_site == 'y')
  {
    // on a activé les sites
    if ($current_site != -1)
      $sql = "SELECT a.id, a.area_name,a.access
        FROM ".TABLE_PREFIX."_area a JOIN ".TABLE_PREFIX."_j_site_area j
        ON a.id=j.id_area
        WHERE j.id_site=$current_site
        ORDER BY a.order_display, a.area_name";
    else
      $sql = "";
  }
  else
  {
    $sql = "SELECT id, area_name,access
    FROM ".TABLE_PREFIX."_area
    ORDER BY order_display, area_name";
  }
  $res = 0;
  if (($current_site != -1) || ($use_multi_site == 'n'))
    $res = grr_sql_query($sql);
  if ($res)
  {
    foreach($res as $row)
    {
      if (authUserAccesArea($user,$row['id']) == 1)
      {
        if ($row['id'] == $current_area)
        {
          $out_html .= "<a id=\"liste_select\" onclick=\"\" href=\"".$link."?year=$year&amp;month=$month&amp;day=$day&amp;area=".$row['id']."\">&gt; ".htmlspecialchars($row['area_name'])."</a></b><br />\n";
        } else {
          $out_html .= "<a id=\"liste\" onclick=\"\" href=\"".$link."?year=$year&amp;month=$month&amp;day=$day&amp;area=".$row['id']."\"> ".htmlspecialchars($row['area_name'])."</a><br />\n";
        }
      }
    }
  }
  grr_sql_free($res);
  return $out_html;
}
/**
 * Affichage des room sous la forme d'une liste
 *
 * @param string $link
 * @param string $current_area
 * @param string $current_room
 * @param string $year
 * @param string $month
 * @param string $day
 * @return string
 */
function make_room_list_html($link,$current_area, $current_room, $year, $month, $day)
{
  $out_html = "<b><i><span class=\"bground\">".get_vocab("rooms").get_vocab("deux_points")."</span></i></b><br />";
  $sql = "SELECT id, room_name, description FROM ".TABLE_PREFIX."_room WHERE area_id=? ORDER BY order_display,room_name";
  $res = grr_sql_query($sql,"i",[$current_area]);
  if ($res)
  {
    foreach($res as $row)
    {
      // On affiche uniquement les ressources autorisées
      if (verif_acces_ressource(getUserName(), $row['id']))
      {
        if ($row['id'] == $current_room)
          $out_html .= "<span id=\"liste_select\">&gt; ".htmlspecialchars($row["room_name"])."</span><br />\n";
        else
          $out_html .= "<a id=\"liste\" onclick=\"\" href=\"".$link.".php?year=$year&amp;month=$month&amp;day=$day&amp;&amp;room=".$row['id']."\">".htmlspecialchars($row["room_name"]). "</a><br />\n";
      }
    }
  }
  grr_sql_free($res);
  return $out_html;
}
/*
 * Affichage des sites sous la forme d'une liste de boutons
 *
 * @param string $link
 * @param string $current_site
 * @param string $year
 * @param string $month
 * @param string $day
 * @return string
 */
function make_site_item_html($link, $current_site, $year, $month, $day, $user)
{
  if (Settings::get("module_multisite") == "Oui")
  {
    $sql = "SELECT id,sitename
    FROM ".TABLE_PREFIX."_site
    ORDER BY sitename";
    $sites = grr_sql_query($sql);
    if ($sites)
    {
      $Sites = array();
      foreach($sites as $site)
      {
        // Pour chaque site, on détermine s'il y a des domaines visibles par l'utilisateur
        $sql = "SELECT id_area
        FROM ".TABLE_PREFIX."_j_site_area
        WHERE ".TABLE_PREFIX."_j_site_area.id_site=?";
        $areas = grr_sql_query($sql,"i",[$site['id']]);
        if ($areas && grr_sql_count($areas) > 0)
        {
          foreach($areas as $area)
          {
            if (authUserAccesArea($user,$area['id_area']) == 1) // on a trouvé un domaine autorisé
            {
              $Sites[] = $site;
              break;  // On arrête la boucle
            }
          }
        }
        // On libère la réponse
        grr_sql_free($areas);
      } // $Sites contient les (id,sitename) des sites contenant au moins un domaine accessible par $user
      $nb_sites_a_afficher = count($Sites);
    }
    if ($nb_sites_a_afficher >0)
    {
      $out = array();
      foreach($Sites as $site){
        $link2 = $link.'?year='.$year.'&amp;month='.$month.'&amp;day='.$day.'&amp;id_site='.$site['id'];
        if ($site['id'] == $current_site)
        {
          $out[] = "<input id=\"item_select\" type=\"button\" class=\"btn btn-primary btn-lg btn-block item_select\" name=\"".$site['id']."\" value=\"".htmlspecialchars($site['sitename'])."\" onclick=\"location.href='$link2';\" />".PHP_EOL;
        }
        else
        {
          $out[] = "<input type=\"button\" class=\"btn btn-default btn-lg btn-block item\" name=\"".$site['id']."\" value=\"".htmlspecialchars($site['sitename'])." \" onclick=\"location.href='$link2';\" />".PHP_EOL;
        }
      }
      $out_html = '<br />'.PHP_EOL.'<div class="panel panel-default">'.PHP_EOL.'<div class="panel-heading">'.get_vocab('sites').get_vocab('deux_points').'</div>'.PHP_EOL.'<div class="panel-body">'.PHP_EOL.'<form class="ressource" id="site_001" action="'.$_SERVER['PHP_SELF'].'">';
      foreach($out as $row){
        $out_html .= $row;
      }
      $out_html .= '</form>'.PHP_EOL;
      $out_html .= '</div></div>'.PHP_EOL;
      $out_html .= '<script type="text/javascript">'.PHP_EOL;
      $out_html .= 'function site_go()'.PHP_EOL;
      $out_html .= '{'.PHP_EOL;
      $out_html .= 'box = document.getElementById("site_001").site;'.PHP_EOL;
      $out_html .= 'destination = box.options[box.selectedIndex].value;'.PHP_EOL;
      $out_html .= 'if (destination) location.href = destination;'.PHP_EOL;
      $out_html .= '}'.PHP_EOL;
      $out_html .= '</script>'.PHP_EOL;
      $out_html .= '<noscript>'.PHP_EOL;
      $out_html .= '<div>'.PHP_EOL;
      $out_html .= '<input type="submit" value="change" />'.PHP_EOL;
      $out_html .= '</div>'.PHP_EOL;
      $out_html .= '</noscript>'.PHP_EOL;
      return $out_html;
    }
    else
      return '';
  }
  else 
    return '';
}
/**
 * Affichage des area sous la forme d'une liste de boutons
 *
 * @param string $link
 * @param string $current_site
 * @param string $current_area
 * @param string $year
 * @param string $month
 * @param string $day
 * @return string
 */
function make_area_item_html( $link, $current_site, $current_area, $year, $month, $day, $user)
{
  if (Settings::get("module_multisite") == "Oui")
  {// on a activé les sites
    if ($current_site != -1)
      $sql = "SELECT a.id, a.area_name,a.access FROM ".TABLE_PREFIX."_area a JOIN ".TABLE_PREFIX."_j_site_area j ON a.id=j.id_area
    WHERE j.id_site=".intval($current_site)."
    ORDER BY a.order_display, a.area_name";
    else
      return ""; // cas d'une ressource ou d'un domaine inconnu
  }
  else
  {
    $sql = "SELECT id, area_name,access
    FROM ".TABLE_PREFIX."_area
    ORDER BY order_display, area_name";
  }
  $out_html = '<br />'.PHP_EOL.'<div class="panel panel-default">'.PHP_EOL.'<div class="panel-heading">'.get_vocab("areas").'</div>'.PHP_EOL.'<div class="panel-body">'.PHP_EOL;
  $out_html .= '<form class="ressource" id="area_001" action="'.$_SERVER['PHP_SELF'].'">'.PHP_EOL;
  $res = grr_sql_query($sql);
  if ($res)
  {
    foreach($res as $row)
    {
      $link2 = $link.'?year='.$year.'&amp;month='.$month.'&amp;day='.$day.'&amp;area='.$row['id'];
      if (authUserAccesArea($user, $row['id']) == 1)
      {
        if ($current_area != null)
        {
          if ($current_area == $row['id']) /* Couleur du domaine selectionné*/
            $out_html .= '<input class="btn btn-primary btn-lg btn-block item_select" name="'.$row['id'].'" value="'.htmlspecialchars($row['area_name']).'" onclick="location.href=\''.$link2.'\' ;"/>'.PHP_EOL;
          else
            $out_html .= '<input class="btn btn-default btn-lg btn-block item " name="'.$row['id'].'" value="'.htmlspecialchars($row['area_name']).'" onclick="location.href=\''.$link2.'\' ;"/>'.PHP_EOL;
        }
        else
          $out_html .= '<input class="btn btn-default btn-lg btn-block item" name="'.$row['id'].'" value="'.htmlspecialchars($row['area_name']).'" onclick="location.href=\''.$link2.'\' ;"/>'.PHP_EOL;
      }
    }
  }
  $out_html .= '</form>'.PHP_EOL.'</div>'.PHP_EOL.'</div>'.PHP_EOL;
  return $out_html;
}
//end make_area_item_html
/**
 * Affichage des rooms sous la forme d'une liste de boutons
 *
 * @param string $link
 * @param string $current_area
 * @param string $current_room
 * @param string $year
 * @param string $month
 * @param string $day
 * @return string
 */
function make_room_item_html($link, $current_area, $current_room, $year, $month, $day)
{
  $sql = "SELECT id, room_name, description FROM ".TABLE_PREFIX."_room WHERE area_id=? ORDER BY order_display,room_name";
  $res = grr_sql_query($sql,"i",[$current_area]);
  if ($res && (grr_sql_count($res)>0)) // il y a des ressources à afficher
  {
    $out_html = '<br />'.PHP_EOL.'<div class="panel panel-default">'.PHP_EOL.'<div class="panel-heading">'.get_vocab("rooms").get_vocab("deux_points").'</div>'.PHP_EOL.'<div class="panel-body">'.PHP_EOL.'<form class="ressource" id="room_001" action="'.$_SERVER['PHP_SELF'].'">'.PHP_EOL;
    $all_ressource = 0; // permet l'affichage de toutes les ressources
    foreach($res as $row)
    {
      if (verif_acces_ressource(getUserName(),$row['id']))
      {
        $link2 = $link.'.php?year='.$year.'&amp;month='.$month.'&amp;day='.$day.'&amp;room='.$row['id'];
        $link_a = $link;
        if (($link != 'day')&&(!strpos($link,'all'))) {$link_a .= '_all';}
        $link_all_room = $link_a.'.php?year='.$year.'&amp;month='.$month.'&amp;day='.$day.'&amp;area='.$current_area;
        if (!isset($_GET['room']))
        {
          if (isset($all_ressource) && $all_ressource == 0)
            $out_html .= '<input id="item_select" class="btn btn-primary btn-lg btn-block item_select" name="all_room" value="'.get_vocab("all_rooms").'" onclick="location.href=\''.$link_all_room.'\' ;"/>'.PHP_EOL;
          $out_html .= '<input class="btn btn-default btn-lg btn-block item" type="button" name="'.$row['id'].'" value="'.htmlspecialchars($row['room_name']).'" onclick="location.href=\''.$link2.'\' ;"/>'.PHP_EOL;
          $all_ressource = 1;
        }
        else //changed (Ajout de type = " button pr gerer saut de ligne " 
        {
          if (isset($all_ressource) && $all_ressource == 0)
            $out_html .= '<input class="btn btn-default btn-lg btn-block item" type="button" name="all_room" value="'.get_vocab("all_rooms").'" onclick="location.href=\''.$link_all_room.'\' ;"/>'.PHP_EOL;
          $all_ressource = 1;
          if ($current_room == $row['id'])
            $out_html .= '<input class="btn btn-primary btn-lg btn-block item_select" type="button" name="'.$row['id'].'" value="'.htmlspecialchars($row['room_name']).'" onclick="location.href=\''.$link2.'\';"/>'.PHP_EOL;
          else
            $out_html .= '<input class="btn btn-default btn-lg btn-block item" type="button" name="'.$row['id'].'" value="'.htmlspecialchars($row['room_name']).'" onclick="location.href=\''.$link2.'\' ;"/>'.PHP_EOL;
        }
      }
    }
    $out_html .= '</form>'.PHP_EOL.'</div>'.PHP_EOL.'</div>'.PHP_EOL;
    return $out_html;
  }
}
// end make_room_item_html
function tdcell_rowspan($colclass, $step)
{
  global $tab_couleur;
  static $ecolors;
  if ($step < 1)
    $step = 1;
  if (($colclass >= "A") && ($colclass <= "Z"))
    echo '<td class="type'.$colclass.'" rowspan="'.$step.'">'.PHP_EOL;
  else
    echo '<td rowspan="'.$step.'" class="'.$colclass.'">'.PHP_EOL;
}

/**
 * FUNCTION: how_many_connected()
 * DESCRIPTION: Si c'est un admin qui est connecté, affiche le nombre de personnes actuellement connectées.
 */
function how_many_connected()
{
  if (authGetUserLevel(getUserName(), -1) >= 6)
  {
    $sql = "SELECT login FROM ".TABLE_PREFIX."_log WHERE end > now()";
    $res = grr_sql_query($sql);
    $nb_connect = grr_sql_count($res);
    grr_sql_free($res);

    if (@file_exists('./admin_access_area.php')){
      $racineAd = "./";
    }else{
      $racineAd = "./admin/";
    }

    if ($nb_connect == 1)
      echo "<a href='{$racineAd}admin_view_connexions.php'>".$nb_connect.get_vocab("one_connected")."</a>".PHP_EOL;
    else
      echo "<a href='{$racineAd}admin_view_connexions.php'>".$nb_connect.get_vocab("several_connected")."</a>".PHP_EOL;
    if (verif_version())
      affiche_pop_up(get_vocab("maj_bdd_not_update").get_vocab("please_go_to_admin_maj.php"),"force");
  }
}
function affiche_lien_contact($_cible, $_type_cible, $option_affichage)
{

  if ($_type_cible == "identifiant:non")
  {
    if ($_cible == "contact_administrateur")
    {
      $_email = Settings::get("webmaster_email");
      $_identite = get_vocab('administrator_contact');
    }
    else if ($_cible == "contact_support")
    {
      $_email = Settings::get("technical_support_email");
      $_identite = get_vocab('technical_contact');
    }
    else
    {
      $_email = "";
      $_identite = "";
    }
  }
  else
  {
    $sql_cible = "SELECT prenom, nom, email FROM ".TABLE_PREFIX."_utilisateurs WHERE login = ?";
    $res_cible = grr_sql_query($sql_cible,"s",[protect_data_sql($_cible)]);
    if ($res_cible)
    {
      $row_cible = grr_sql_row($res_cible, 0);
      $_email = $row_cible[2];
      $_identite = $row_cible[0]." ".$row_cible[1];
      grr_sql_free($res_cible);
    }
    else
    {
      $_email = "";
      $_identite = "";
    }
  }

  if (Settings::get("envoyer_email_avec_formulaire") == "yes")
  {
    if ($_email == "")
    {
      if ($option_affichage == "afficher_toujours")
        $affichage = $_identite;
      else
        $affichage = "";
    }
    else
      $affichage = '<a href="javascript:centrerpopup(\'contact.php?cible='.$_cible.'&amp;type_cible='.$_type_cible.'\',600,480,\'scrollbars=yes,statusbar=no,resizable=yes\')" title="'.$_identite.'\">'.$_identite.'</a>'.PHP_EOL;
  }
  else
  {   // Il s'agit de calculer une balise mailto
    $affichage = "";
    if ($_email == "")
    {
      if ($option_affichage == "afficher_toujours")
        $affichage = $_identite;
    }
    else
    {
      $email_seq = filter_multi_emails($_email);
      if ($email_seq != "")
      {
        $affichage = '<a href="mailto:'.$email_seq.'">'.$_identite.'</a>';
      }
      else 
        if ($option_affichage == "afficher_toujours")
          $affichage = $_identite;
    }
  }
  return $affichage;
}
// Affiche le numéro de version de GRR selon les cas sous la forme "GRR x.x.x_RCx" ou "GRR x.x.x
function affiche_version() {
  global $version_grr, $version_grr_RC, $sous_version_grr;
  if (Settings::get("versionRC")!="")
    return "GRR ".Settings::get("version")."_RC".Settings::get("versionRC").$sous_version_grr;
  else
    return "GRR ".Settings::get("version").$sous_version_grr;
}
/*
Construit les informations à ajouter dans les mails automatiques
*/
function affichage_champ_add_mails($id_resa)
{
  $affichage = "";
  // Les champs add :
  $overload_data = mrbsEntryGetOverloadDesc($id_resa);
  foreach ($overload_data as $fieldname=>$field)
  {
    if (($field["overload_mail"] == 'y') && ($field["valeur"] != ""))
      $affichage .= bbcode(htmlspecialchars($fieldname).get_vocab("deux_points").htmlspecialchars($field["valeur"]),'nobbcode')."\n";
  }
  return $affichage;
}

function html_ressource_empruntee($id_room, $type = "logo")
{
  $html = "";
  $active_ressource_empruntee = grr_sql_query1("SELECT active_ressource_empruntee FROM ".TABLE_PREFIX."_room WHERE id = ?","i",[$id_room]);
  if ($active_ressource_empruntee == 'y')
  {
    $sql = "SELECT id,beneficiaire,beneficiaire_ext FROM ".TABLE_PREFIX."_entry WHERE room_id = ? AND statut_entry='y'";
    $res = grr_sql_query($sql,"i",[$id_room]);
    if($res){
      if(grr_sql_count($res) != 0){
        $row = grr_sql_row_keyed($res,0);
        $id_resa = $row['id'];
        if ($type == "logo")
          $html.= '<a href="view_entry.php?id='.$id_resa.'"><img src="img_grr/buzy_big.png" alt="'.get_vocab("ressource_actuellement_empruntee").'" title="'.get_vocab("reservation_en_cours").'" width="30" height="30" class="image" /></a>'.PHP_EOL;
        else if ($type == "texte")
        {
          $beneficiaire = $row["beneficiaire"];
          $beneficiaire_ext = $row["beneficiaire_ext"];
          $html.= '<br /><b><span class="avertissement">'.PHP_EOL;
          $html.= '<img src="img_grr/buzy_big.png" alt="'.get_vocab("ressource_actuellement_empruntee").'" title="'.get_vocab("ressource_actuellement_empruntee").'" width="30" height="30" class="image" />'.PHP_EOL;
          $html.= get_vocab("ressource_actuellement_empruntee").' '.get_vocab("nom_emprunteur").get_vocab("deux_points").affiche_nom_prenom_email($beneficiaire,$beneficiaire_ext,"withmail");
          $html.= '<a href="view_entry.php?id='.$id_resa.'&amp;mode=page">'.get_vocab("entryid").$id_resa.'</a>'.PHP_EOL.'</span></b>'.PHP_EOL;
        }
        else
          $html.= "yes";
      }
    }
    else
      fatal_error(1,get_vocab('error_reading_database'));
  }
  return $html;
}
/* VerifyModeDemo()
 *
 * Affiche une page "opération non autorisée" pour certaines opérations dans le cas le mode demo est activé.
 *
 * Returns: Nothing
 */
function VerifyModeDemo() {
  if (Settings::get("ActiveModeDemo") == 'y')
  {
    print_header("", "", "", "");
    echo "<h1>Opération non autorisée</h1>
    <p>Vous êtes dans une <b>version de démonstration de GRR</b>.
      <br />Certaines fonctions ont été volontairement bridées. C'est le cas pour l'opération que vous avez tenté d'effectuer.</p>
    </body></html>";
    die();
  }
}
/* showNoReservation()
 * Displays an appropriate message when reservation does not exist
 * Returns: Nothing
 */
function showNoReservation($day, $month, $year, $back)
{
  if ((Settings::get("authentification_obli") == 0) && (getUserName() == ''))
    $type_session = "no_session";
  else
    $type_session = "with_session";
  start_page_w_header($day, $month, $year, $type_session);
  echo '<h1>'.get_vocab("accessdenied").'</h1>';
  echo '<p>'.get_vocab("noreservation").'</p>';
  echo '<p><a href="'.$back.'">'.get_vocab("returnprev").'</a></p>';
  end_page();
}
/* showAccessDeniedMaxBookings($day, $month, $year, $id_room, $back, $cas)
 * Displays an appropriate message when access has been denied because of overbooking
 * parameters :
 * $day, $month, $year : date
 * $id_room : room id
 * $back : link to the return page
 * $cas (integer): code returned by UserRoomMaxBookingRange, default -1 for the other cases
 * Returns: Nothing
 */
function showAccessDeniedMaxBookings($day, $month, $year, $id_room, $back, $cas = -1)
{
  start_page_w_header($day, $month, $year, $type="with_session");
  echo '<h1>'.get_vocab("accessdenied").'</h1>';
  echo '<p>';
  if ($cas > 0){ // l'appel vient de UserRoomMaxBookingRange
    switch($cas){
      case 1:     // Droits insuffisants
        echo get_vocab('norights');
        break;
      case 2:     // Limitation sur l'ensemble des ressources
        $max_booking_all = Settings::get("UserAllRoomsMaxBooking");
        echo get_vocab("msg_max_booking_all").get_vocab("deux_points").$max_booking_all."<br />";
        break;
      case 3:     // Limitation par domaine
        $id_area = mrbsGetRoomArea($id_room);
        $max_booking_per_area = grr_sql_query1("SELECT max_booking FROM ".TABLE_PREFIX."_area WHERE id =?","i",[$id_area]);
        echo get_vocab("msg_max_booking_area").get_vocab("deux_points").$max_booking_per_area."<br />";
        break;
      case 4:     // Limitation par ressource
        $max_booking_per_room = grr_sql_query1("SELECT max_booking FROM ".TABLE_PREFIX."_room WHERE id=?","i",[$id_room]);
        echo get_vocab("msg_max_booking").get_vocab("deux_points").$max_booking_per_room."<br />";
        break;
      case 5:     // Limitation sur un intervalle pour une ressource
        $sql = "SELECT booking_range,max_booking_on_range FROM ".TABLE_PREFIX."_room WHERE id = ?";
        $res = grr_sql_query($sql,"i",[$id_room]);
        if($res){
          $row = grr_sql_row_keyed($res,0);
          echo get_vocab('msg_max_booking_on_range').get_vocab('deux_points').$row['max_booking_on_range'].get_vocab('sur').$row['booking_range'].get_vocab('jour(s)').'.';
        }
        break;
    }
  }
  else {
    // Limitation par ressource
    $max_booking_per_room = grr_sql_query1("SELECT max_booking FROM ".TABLE_PREFIX."_room WHERE id=?","i",[$id_room]);
    if ($max_booking_per_room >= 0)
      echo get_vocab("msg_max_booking").get_vocab("deux_points").$max_booking_per_room."<br />";
    // Calcul de l'id de l'area de la ressource.
    $id_area = mrbsGetRoomArea($id_room);
    // Limitation par domaine
    $max_booking_per_area = grr_sql_query1("SELECT max_booking FROM ".TABLE_PREFIX."_area WHERE id =?","i",[$id_area]);
    if ($max_booking_per_area >= 0)
      echo get_vocab("msg_max_booking_area").get_vocab("deux_points").$max_booking_per_area."<br />";
    // Limitation sur l'ensemble des ressources
    $max_booking_all = Settings::get("UserAllRoomsMaxBooking");
    if ($max_booking_all >= 0)
      echo get_vocab("msg_max_booking_all").get_vocab("deux_points").$max_booking_all."<br />";
    }
    echo "<br />".get_vocab("accessdeniedtoomanybooking").
  '</p>
  <p>
    <a href="'.$back.'" >'.get_vocab("returnprev").'</a>
  </p>
</body>
</html>';
}

// gestion du temps
function hour_min_format()
{
  global $twentyfourhour_format;
  if ($twentyfourhour_format)
  {
    return "H:i";
  }
  else
  {
    return "h:ia";
  }
}
/* donne, pour un format français, un résultat de la forme lundi 30 sept. - 19:17
*/
function time_date_string($t, $dformat)
{
  global $twentyfourhour_format;
  // This bit's necessary, because it seems %p in strftime format
  // strings doesn't work
  if ($twentyfourhour_format)
    return utf8_strftime($dformat." - %H:%M",$t);
  else
    return utf8_strftime("%I:%M".date("a", $t)." - ".$dformat,$t);
}
/*L'heure d'été commence le dernier dimanche de mars * et se termine le dernier dimanche d'octobre
 Passage à l'heure d'hiver : -1h, le changement s'effectue à 3h
 Passage à l'heure d'été : +1h, le changement s'effectue à 2h
 Si type = hiver => La fonction retourne la date du jour de passage à l'heure d'hiver
 Si type = ete =>  La fonction retourne la date du jour de passage à l'heure d'été*/
function heure_ete_hiver($type, $annee, $heure)
{
  if ($type == "ete")
    $debut = mktime($heure, 0, 0, 03, 31, $annee);
  // 31-03-$annee
  else
    $debut = mktime($heure,0, 0, 10, 31, $annee);
  // 31-10-$annee
  while (date("D", $debut ) != 'Sun')
    $debut = mktime($heure, 0, 0, date("m", $debut), date("d", $debut) - 1, date("Y", $debut));
  //On retire 1 jour par rapport à la date examinée jusqu'à tomber sur dimanche
  return $debut;
}
/*
Fonction utilisée dans le cas où les créneaux de réservation sont basés sur des intitulés pré-définis :
Formatage des périodes de début ou de fin de réservation.
Dans le cas du début de réservation on a $mod_time=0
Dans le cas de la fin de réservation on a $mod_time=-1
*/
function period_time_string($t, $mod_time = 0)
{
  global $periods_name;
  $time = getdate($t);
  $p_num = $time["minutes"] + $mod_time;
  if ( $p_num < 0 )
    $p_num = 0;
  if ( $p_num >= count($periods_name) - 1 )
    $p_num = count($periods_name) - 1;
  return $periods_name[$p_num];
}
/*
Fonction utilisée dans le cas où les créneaux de réservation sont basés sur des intitulés pré-définis :
Formatage de la date de début ou de fin de réservation.
Dans le cas du début de réservation on a $mod_time=0
Dans le cas de la fin de réservation on a $mod_time=-1
*/
function period_date_string($t, $mod_time = 0)
{
  global $periods_name, $dformat;
  $time = getdate($t);
  $p_num = $time["minutes"] + $mod_time;
  if ( $p_num < 0 )
  {
    // fin de réservation : cas $time["minutes"] = 0. il faut afficher le dernier créneau de la journée précédente
    $t = $t - 60 * 60 * 24;
    $p_num = count($periods_name) - $p_num;
  }
  if ( $p_num >= count($periods_name) - 1 )
    $p_num = count($periods_name) - 1;
  return array($p_num, $periods_name[$p_num] . utf8_strftime(", ".$dformat, $t));
}
// la même, avec un résultat différent, pour les rapports csv
function period_date_string_rapport($t, $mod_time = 0)
{
  global $periods_name, $dformat;
  $time = getdate($t);
  $p_num = $time["minutes"] + $mod_time;
  if ( $p_num < 0 )
  {
    // fin de réservation : cas $time["minutes"] = 0. il faut afficher le dernier créneau de la journée précédente
    $t = $t - 60 * 60 * 24;
    $p_num = count($periods_name) - $p_num;
  }
  if ( $p_num >= count($periods_name) - 1 )
    $p_num = count($periods_name) - 1;
  return array($periods_name[$p_num],utf8_strftime($dformat, $t));
}
/** Get the local day name based on language. Note 2000-01-02 is a Sunday.
 * @param integer $daynumber
 */
function day_name($daynumber)
{
  return utf8_strftime("%A", mktime(0, 0, 0, 1, 2 + $daynumber, 2000));
}
// fonction de détermination des jours de vacances scolaires par lecture dans la base
function isSchoolHoliday($now)
{
  $test = grr_sql_query1("SELECT DAY FROM ".TABLE_PREFIX."_calendrier_vacances WHERE DAY = ?","i",[$now]);
  $val = ($test != -1);
  return $val;
}
// fonction de détermination si un jour est férié
function isHoliday($now){
  $test = grr_sql_query1("SELECT DAY FROM ".TABLE_PREFIX."_calendrier_feries WHERE DAY = ?","i",[$now]);
  $val = ($test != -1);
  return $val;
}
/* Transforme $dur en une durée exprimée en années, semaines, jours, heures, minutes et secondes
 OU en durée numérique exprimée dans l'une des unités de façon fixe, pour l'édition des
 réservations par durée.
 $dur : durée sous forme d'une chaine de caractère quand $edition=false, sinon, durée en valeur numérique.
 $units : variable conservée uniquement pour compatibilité avec la fonction toTimeString originale
          si $edition=false, sinon, contient l'unité utilisée pour $dur
 $edition : Valeur par défaut : false. Indique si le retour est pour affichage ou pour modifier la durée.
 Version écrite par David M - E-Concept Applications */
function toTimeString(&$dur, &$units, $edition = false)
{
  if ($edition)
  {
    if ($dur >= 60)
    {
      $dur = $dur / 60;
      if ($dur >= 60)
      {
        $dur /= 60;
        if (($dur >= 24) && ($dur % 24 == 0))
        {
          $dur /= 24;
          if (($dur >= 7) && ($dur % 7 == 0))
          {
            $dur /= 7;
            if (($dur >= 52) && ($dur % 52 == 0))
            {
              $dur  /= 52;
              $units = get_vocab("years");
            }
            else
              $units = get_vocab("weeks");
          }
          else
            $units = get_vocab("days");
        }
        else
          $units = get_vocab("hours");
      }
      else
        $units = get_vocab("minutes");
    }
    else
      $units = get_vocab("seconds");
  }
  else
  {
    $duree_formatee = "";
    $not_first_unit = false;
    // On définit la durée en secondes de chaque type d'unité
    $annee   = 60 * 60 * 24 * 365;
    $semaine = 60 * 60 * 24 * 7;
    $jour    = 60 * 60 * 24;
    $heure   = 60 * 60;
    $minute  = 60;
    // On calcule le nombre d'années.
    $nb_annees = floor($dur / $annee);
    if ($nb_annees > 0)
    {
      if ($not_first_unit)
        $duree_formatee .= ", ";
      else
        $not_first_unit = true;
      $duree_formatee .= $nb_annees . " " . get_vocab("years");
      // On soustrait le nombre d'années déjà déterminées à la durée initiale.
      $dur = $dur - $nb_annees * $annee;
    }
    // On calcule le nombre de semaines.
    $nb_semaines = floor($dur / $semaine);
    if ($nb_semaines > 0)
    {
      if ($not_first_unit)
        $duree_formatee .= ", ";
      else
        $not_first_unit = true;
      $duree_formatee .= $nb_semaines . " " . get_vocab("weeks");
      // On soustrait le nombre de semaines déjà déterminées à la durée initiale.
      $dur = $dur - $nb_semaines * $semaine;
    }
    // On calcule le nombre de jours.
    $nb_jours = floor($dur / $jour);
    if ($nb_jours > 0)
    {
      if ($not_first_unit)
        $duree_formatee .= ", ";
      else
        $not_first_unit = true;
      $duree_formatee .= $nb_jours . " " . get_vocab("days");
      // On soustrait le nombre de jours déjà déterminés à la durée initiale.
      $dur = $dur - $nb_jours * $jour;
    }
    // On calcule le nombre d'heures.
    $nb_heures = floor($dur / $heure);
    if ($nb_heures > 0)
    {
      if ($not_first_unit)
        $duree_formatee .= ", ";
      else
        $not_first_unit = true;
      $duree_formatee .= $nb_heures . " " . get_vocab("hours");
      // On soustrait le nombre d'heures déjà déterminées à la durée initiale.
      $dur = $dur - $nb_heures * $heure;
    }
    // On calcule le nombre de minutes.
    $nb_minutes = floor($dur / $minute);
    if ($nb_minutes > 0)
    {
      if ($not_first_unit)
        $duree_formatee .= ", ";
      else
        $not_first_unit = true;
      $duree_formatee .= $nb_minutes . " " . get_vocab("minutes");
            // On soustrait le nombre de minutes déjà déterminées à la durée initiale.
      $dur = $dur - $nb_minutes * $minute;
    }
        // On calcule le nombre de secondes.
    if ($dur > 0)
    {
      if ($not_first_unit)
        $duree_formatee .= ", ";
      $duree_formatee .= $dur . " " . get_vocab("seconds");
    }
    // On sépare les différentes unités de la chaine.
    $tmp = explode(", ", $duree_formatee);
    // Si on a plus d'une unitée...
    if (count($tmp) > 1)
    {
      // ... on dépile le tableau par la fin...
      $tmp_fin = array_pop($tmp);
      // ... on reconstiture la chaine avec les premiers éléments...
      $duree_formatee = implode(", ", $tmp);
      // ... et on ajoute le dernier élément
      $duree_formatee .= " et " . $tmp_fin;
    }
    // Sinon, on ne change rien.
    $dur = $duree_formatee;
    $units = "";
  }
}
function getDaysInMonth($month, $year)
{
  return date('t', mktime(0, 0, 0, $month, 1, $year));
}
/** Transforme $dur en un nombre entier
 $dur : durée
 $units : unité
 * @param integer $start_period
 */
function toPeriodString($start_period, &$dur, &$units)
{
  // la durée est donnée en secondes
  global $enable_periods, $periods_name;
  $max_periods = count($periods_name);
  $dur /= 60; // on transforme la durée en minutes
  // Chaque minute correspond à un créneau
  if ( $dur >= $max_periods || $start_period == 0 )
  {
    if ( $start_period == 0 && $dur == $max_periods )
    {
      $units = get_vocab("periods");
      $dur = $max_periods;
      return;
    }
    $dur /= 60;
    if (($dur >= 24) && is_int($dur))
    {
      $dur /= 24;
      $units = get_vocab("days");
      return;
    }
    else
    {
      $dur *= 60;
      $dur = ($dur % $max_periods) + floor( $dur/(24*60) ) * $max_periods;
      $units = get_vocab("periods");
      return;
    }
  }
  else
    $units = get_vocab("periods");
}
function time_date_string_jma($t,$dformat)
{
  return utf8_strftime($dformat, $t);
}

// $type = 1: Fonction Calendrier hors réservation ; 2; Fonction Calendrier feries ; 3 : calendrier vacances (scolaires par défaut)
function cal($month, $year, $type)
{
  global $weekstarts;

  if (!isset($weekstarts))
    $weekstarts = 0;
  $s = "";
  $daysInMonth = getDaysInMonth($month, $year);
  $date = mktime(12, 0, 0, $month, 1, $year);
  $first = (date("w",$date) + 7 - $weekstarts) % 7;
  $monthName = ucfirst(utf8_strftime("%B", $date));

  $s .= '<table class="table calendar2">'.PHP_EOL;
  $s .= '<tr>'.PHP_EOL;
  $s .= '<td class="calendarHeader2" colspan="8">'.$monthName.' '.$year.'</td>'.PHP_EOL;
  $s .= '</tr>'.PHP_EOL;
  $d = 1 - $first;
  $is_ligne1 = 'y';
  while ($d <= $daysInMonth)
  {
    $s .= '<tr>'.PHP_EOL;
    for ($i = 0; $i < 7; $i++)
    {
      $basetime = mktime(12, 0, 0, 6, 11 + $weekstarts, 2000);
      $show = $basetime + ($i * 24 * 60 * 60);
      $nameday = utf8_strftime('%A',$show);
      $temp = mktime(0, 0, 0, $month, $d, $year);
      if ($i == 0)
        $s .= '<td class="calendar2" style="vertical-align:bottom;"><b>S'.getWeekNumber($temp).'</b></td>'.PHP_EOL;
      $s .= '<td class="calendar2" valign="top">'.PHP_EOL;
      if ($is_ligne1 == 'y')
        $s .=  '<b>'.ucfirst(substr($nameday,0,1)).'</b><br />';
      if ($d > 0 && $d <= $daysInMonth)
      {
        $s .= $d;
        if($type == 1)
          $day = grr_sql_query1("SELECT day FROM ".TABLE_PREFIX."_calendar WHERE day=?","i",[$temp]);
        elseif($type == 2)
          $day = grr_sql_query1("SELECT day FROM ".TABLE_PREFIX."_calendrier_feries WHERE day=?","i",[$temp]);
        elseif($type == 3)
          $day = grr_sql_query1("SELECT day FROM ".TABLE_PREFIX."_calendrier_vacances WHERE day=?","i",[$temp]);
        else
          $day = -1;
        $s .= '<br><input type="checkbox" name="'.$temp.'" value="'.$nameday.'" ';
        if (!($day < 0))
          $s .= 'checked="checked" ';
        $s .= '/>';
      }
      else
        $s .= " ";
      $s .= '</td>'.PHP_EOL;
      $d++;
    }
    $s .= '</tr>'.PHP_EOL;
    $is_ligne1 = 'n';
  }
  $s .= '</table>'.PHP_EOL;
  return $s;
}
/* donne, pour un format français, un résultat de la forme lundi 30 sept.19:17:32
*/
function date_time_string($t, $dformat)
{
  global $twentyfourhour_format;
  if ($twentyfourhour_format)
    $timeformat = "%T";
  else
  {
    $ampm = date("a",$t);
    $timeformat = "%I:%M$ampm";
  }
  return utf8_strftime($dformat." ".$timeformat, $t);
}
// Convertit un créneau de début et de fin en un tableau donnant date, créneau de début et durée
function describe_period_span($starts, $ends)
{
  global $enable_periods, $periods_name, $duration;
  list($start_period, $start_date) =  period_date_string($starts);
  list($periodedebut, $datedebut) =  period_date_string_rapport($starts);
  list( , $end_date) =  period_date_string($ends, -1);
  $duration = $ends - $starts;
  toPeriodString($start_period, $duration, $dur_units);
  if ($duration > 1)
  {
    list( , $start_date) =  period_date_string($starts);
    list( , $end_date) =  period_date_string($ends, -1);
  }
  return array($datedebut, $periodedebut, $duration, $dur_units);
}
// Convertit l'heure de début et de fin en un tableau donnant date, heure de début et durée.
function describe_span($starts, $ends, $dformat)
{
  global $twentyfourhour_format;
  $start_date = utf8_strftime($dformat, $starts);
  if ($twentyfourhour_format)
    $timeformat = "%T";
  else
  {
    $ampm = date("a",$starts);
    $timeformat = "%I:%M$ampm";
  }
  $start_time = utf8_strftime($timeformat, $starts);
  $duration = $ends - $starts;
  toTimeString($duration, $dur_units);
  return array($start_date, $start_time ,$duration, $dur_units);
}
/** fonction plages_libre_semaine_ressource($id_room, $month_week, $day_week, $year_week)
 * Teste s'il reste ou non des plages libres sur une journée donnée pour une ressource donnée.
 * Arguments :
 *  integer $id_room : identifiant de la ressource
 *  integer $month_week : mois
 *  integer $day_week : jour
 *  integer $year_week : année
 * Renvoie un booléen :
 *  vrai s'il reste des plages non réservées sur la journée
 *  faux dans le cas contraire
*/
function plages_libre_semaine_ressource($id_room, $month_week, $day_week, $year_week)
{
  global $morningstarts, $eveningends, $eveningends_minutes, $resolution, $enable_periods;
  $date_end = mktime($eveningends, $eveningends_minutes, 0, $month_week, $day_week, $year_week);
  $date_start = mktime($morningstarts, 0, 0, $month_week, $day_week, $year_week);
  $t = $date_start ; 
  if ($enable_periods == "y") 
    $date_end += $resolution;
  $plage_libre = false;
  while ($t < $date_end)
  {
    $t_end = $t + $resolution;
    $query = "SELECT end_time FROM ".TABLE_PREFIX."_entry WHERE room_id=? AND start_time <= ? AND end_time >= ? ";
    $end_time = grr_sql_query1($query,"iii",[$id_room,$t,$t_end]);
    if ($end_time == -1){
      $plage_libre = true;
      break;
    } 
    else{
      $t = $end_time; // avance à la fin de la réservation trouvée
    }
  }
  return $plage_libre ;
}
//Round time up to the nearest resolution
function round_t_up($t, $resolution, $am7)
{
  if (($t - $am7) % $resolution != 0)
  {
    return $t + $resolution - abs(((int)$t - (int)$am7) % $resolution);
  }
  else
  {
    return $t;
  }
}
//Round time down to the nearest resolution
function round_t_down($t, $resolution, $am7)
{
  return (int)$t - (int)abs(((int)$t-(int)$am7) % $resolution);
}
/**
 * Affiche un lien email
 * @param string $_cible
 * @param string $_type_cible
 * @param string $option_affichage
 * @return string
 */
function affiche_heure_creneau($t,$resolution)
{
  global $twentyfourhour_format;
  if ($twentyfourhour_format)
    $hour_min_format = "H:i";
  else
    $hour_min_format = "h:ia";
    $heure_debut = date($hour_min_format,$t);
    $heure_fin = date($hour_min_format, $t + $resolution);
    if ($heure_fin == "00:00") $heure_fin = "24:00";
  return  $heure_debut." - ".$heure_fin;
}
function getWeekNumber($date)
{
  return date('W', $date);
}
// fonction de calcul des jours fériés (France, au sens de l'article L 3133-1 du code du travail)
function setHolidays($year = null)
{
  if ($year === null)
    $year = intval(date('Y'));
  $easterDate  = easter_date($year);
  $easterDay   = date('j', $easterDate);
  $easterMonth = date('n', $easterDate);
  $easterYear  = date('Y', $easterDate);
  $holidays = array(
  // Dates fixes
  mktime(0, 0, 0, 1,  1,  $year),  // 1er janvier
  mktime(0, 0, 0, 5,  1,  $year),  // Fête du travail
  mktime(0, 0, 0, 5,  8,  $year),  // Victoire des alliés
  mktime(0, 0, 0, 7,  14, $year),  // Fête nationale
  mktime(0, 0, 0, 8,  15, $year),  // Assomption
  mktime(0, 0, 0, 11, 1,  $year),  // Toussaint
  mktime(0, 0, 0, 11, 11, $year),  // Armistice
  mktime(0, 0, 0, 12, 25, $year),  // Noel
  // Dates variables
  mktime(0, 0, 0, $easterMonth, $easterDay + 1,  $easterYear),
  mktime(0, 0, 0, $easterMonth, $easterDay + 39, $easterYear),
  mktime(0, 0, 0, $easterMonth, $easterDay + 50, $easterYear),
  );
  sort($holidays);
  return $holidays;
}

// réservations
/* function verif_delais_max_resa_room($user, $id_room, $date_booking)
 *  $user : le login de l'utilisateur
 *  $id_room : l'id de la ressource. Si -1, il s'agit d'une nouvelle ressource
 *  $date_booking : la date de la réservation (n'est utile que si $id=-1)
 *  $date_now : la date actuelle
*/
function verif_delais_max_resa_room($user, $id_room, $date_booking)
{
  $day   = date("d");
  $month = date("m");
  $year  = date("Y");
  $datenow = mktime(0, 0, 0, $month, $day, $year);
  if (authGetUserLevel($user,$id_room) >= 3)
    return true;
  $delais_max_resa_room = grr_sql_query1("SELECT delais_max_resa_room FROM ".TABLE_PREFIX."_room WHERE id=?","i",[$id_room]);
  if ($delais_max_resa_room == -1)
    return true;
  else if ($datenow + $delais_max_resa_room * 24 * 3600 + 1 < $date_booking)
    return false;
  return true;
}
/*  function verif_delais_min_resa_room($user, $id_room, $date_booking, $enable_periods)
 *  $user : le login de l'utilisateur
 *  $id_room : l'id de la ressource. Si -1, il s'agit d'une nouvelle ressoure
 *  $date_booking : la date de la réservation (n'est utile que si $id=-1)
 *  $enable_periods : base temps (par défaut) ou créneaux
 *  renvoie vrai ou faux selon que le délai est respecté
*/
function verif_delais_min_resa_room($user, $id_room, $date_booking, $enable_periods = 'n')
{
  if (authGetUserLevel($user,$id_room) >= 3)
    return true;
  $delais_min_resa_room = grr_sql_query1("SELECT delais_min_resa_room FROM ".TABLE_PREFIX."_room WHERE id=?","i",[$id_room]);
  if ($delais_min_resa_room == 0)
    return true;
  else
  {
    $area = mrbsGetRoomArea($id_room);
    $hour = date("H");
    $minute  = date("i") + $delais_min_resa_room;
    $day   = date("d");
    $month = date("m");
    $year  = date("Y");
    $date_limite = mktime($hour, $minute, 0, $month, $day, $year);
    $limite = getdate($date_limite);
    if ($enable_periods == 'y'){
      $date_limite = mktime(0,0,0,$limite['mon'],$limite['mday'],$limite['year']);
      $limite['hours'] = 0;
      $limite['minutes'] = 0;
    }
    $day_limite = $limite['mday'];
    if (($limite['mday'] != $day)&&(Settings::get('delai_ouvert') == 1)){// jour différent et test pour jours ouvrés ?
      $cur_day = mktime(0,0,0,$month,$day,$year);
      while ($cur_day < $date_booking){
        if (est_hors_reservation($cur_day,$area)){// teste si le jour est hors réservation et dans ce cas allonge le délai
          $day_limite++;
          $date_limite = mktime($limite['hours'],$limite['minutes'],0,$limite['mon'],$day_limite,$limite['year']);
        }
        $day ++;
        $cur_day = mktime(0,0,0,$month,$day,$year);
      }
    }
    if ($date_limite > $date_booking)
      return false;
    return true;
  }
}
/* Cette fonction vérifie une fois par jour si les réservations devant être rendues ne sont pas
 * en retard
 * Si oui, les utilisateurs concernés reçoivent un mail automatique pour les avertir. */
function verify_retard_reservation()
{
  global $dformat;
  $day   = date("d");
  $month = date("m");
  $year  = date("Y");
  $date_now = mktime(0, 0, 0, $month, $day, $year);
  if (((Settings::get("date_verify_reservation2") == "") || (Settings::get("date_verify_reservation2") < $date_now )) && (Settings::get("automatic_mail") == 'yes'))
  {
    $res = grr_sql_query("SELECT id FROM ".TABLE_PREFIX."_room");
    if (!$res)
    {
      include "trailer.inc.php";
      exit;
    }
    else
    {
      foreach($res as $row)
      {
        $res2 = grr_sql_query("SELECT id FROM ".TABLE_PREFIX."_entry WHERE statut_entry='e' AND end_time < ? AND room_id=?","ii",[$date_now,$row['id']]);
        if (!$res2)
        {
          include "trailer.inc.php";
          exit;
        }
        else
        {
          foreach($res2 as $row2)
            $_SESSION['session_message_error'] = send_mail($row2['id'], 7, $dformat);
        }
      }
    }
    if (!Settings::set("date_verify_reservation2", $date_now))
    {
      echo get_vocab('save_err')." date_verify_reservation2 !<br />";
      die();
    }
  }
}
/**
 * Fonction : resaToModerate($user) 
 * Description : si c'est un admin ou un gestionnaire de ressource qui est connecté, retourne un tableau contenant, pour chaque réservation à modérer, [id,room_id,start_time,beneficiaire]
*/
function resaToModerate($user)
{
  $resas = array();
  $res = false;
  $sql = "SELECT e.id,r.room_name,e.start_time,e.beneficiaire,e.beneficiaire_ext FROM ".TABLE_PREFIX."_entry e JOIN ".TABLE_PREFIX."_room r ON e.room_id = r.id ";
  if (authGetUserLevel($user,-1) > 5){ // admin général
    $sql.= " WHERE e.moderate = 1 ORDER BY e.start_time ASC";
    $res = grr_sql_query($sql);
  }
  elseif (isset($_GET['id_site']) && (authGetUserLevel($user,intval($_GET['id_site']),'site') > 4)){ // admin du site
    $sql .= " JOIN ".TABLE_PREFIX."_j_site_area j ON r.area_id = j.id_area WHERE (j.id_site = ? AND e.moderate = 1) ORDER BY e.start_time ASC";
    $res = grr_sql_query($sql,"i",[$_GET['id_site']]);
  }
  elseif (isset($_GET['area']) && (authGetUserLevel($user,intval($_GET['area']),'area') > 3)){ // admin du domaine
    $sql .= " JOIN ".TABLE_PREFIX."_area a ON r.area_id = a.id WHERE (a.id = ? AND e.moderate = 1) ORDER BY e.start_time ASC";
    $res = grr_sql_query($sql,"i",[$_GET['area']]);
  }
  elseif (isset($_GET['room']) && (authGetUserLevel($user,intval($_GET['room']),'room') > 2)){ // gestionnaire de la ressource
    $sql .= " WHERE (e.moderate = 1 AND e.room_id = ?) ORDER BY e.start_time ASC";
    $res = grr_sql_query($sql,"i",[$_GET['room']]);
  }
  if ($res)
  {
    foreach($res as $row){
      if($row['beneficiaire']==""){
        $beneficiaire_ext = $row['beneficiaire_ext'];
        if(strstr($beneficiaire_ext,'|')){
          $s = explode('|',$beneficiaire_ext); // adaptation php8.1
          $beneficiaire = $s[0];
        }
      }
      else 
        $beneficiaire = $row['beneficiaire'];
      $resas[] = array('id' => $row['id'], 'room' => $row['room_name'], 'start_time' => $row['start_time'], 'beneficiaire' => $row['beneficiaire']);
    }
  }
  return $resas;
}
// Vérifie que la date de confirmation est inférieure à la date de début de réservation
function verif_date_option_reservation($option_reservation, $starttime)
{
  if ($option_reservation == -1)
    return true;
  else
  {
    $day   = date("d", $starttime);
    $month = date("m", $starttime);
    $year  = date("Y", $starttime);
    $date_starttime = mktime(0, 0, 0, $month, $day, $year);
    if ($option_reservation < $date_starttime)
      return true;
    return false;
  }
}
// Vérifie que $_create_by peut réserver la ressource $_room_id pour $_beneficiaire
function verif_qui_peut_reserver_pour($_room_id, $_create_by, $_beneficiaire)
{
  if ($_beneficiaire == "")
    return true;
  if (strtolower($_create_by) == strtolower($_beneficiaire))
    return true;
  $qui_peut_reserver_pour  = grr_sql_query1("SELECT qui_peut_reserver_pour FROM ".TABLE_PREFIX."_room WHERE id=?","i",[$_room_id]);
  if (authGetUserLevel($_create_by, $_room_id) >= $qui_peut_reserver_pour)
    return true;
  return false;
}
/* function verif_heure_debut_fin($start_time,$end_time,$area)
Vérifie si l'heure de début ou l'heure de fin de réservation est en dehors des créneaux autorisés.
*/
function verif_heure_debut_fin($start_time,$end_time,$area)
{
  global $enable_periods, $resolution, $morningstarts, $eveningends, $eveningends_minutes;
    // Récupération des données concernant l'affichage du planning du domaine
  get_planning_area_values($area);
    // On ne traite pas le cas des plannings basés sur les intitulés prédéfinis
  if ($enable_periods != "y")
  {
    $day = date("d",$start_time);
    $month = date("m",$start_time);
    $year = date("Y",$start_time);
    $startday = mktime($morningstarts, 0, 0, $month, $day  , $year);
    $day = date("d",$end_time);
    $month = date("m",$end_time);
    $year = date("Y",$end_time);
    $endday = mktime($eveningends, $eveningends_minutes , $resolution, $month, $day, $year);
    if ($start_time < $startday)
      return false;
    else if ($end_time > $endday)
      return false;
  }
  return true;
}
/* function verif_duree_max_resa_area($user, $id_room, $starttime, $endtime)
 *  $user : le login de l'utilisateur
 *  $id_room : l'id de la ressource. Si -1, il s'agit d'une nouvelle ressource.
 *  $starttime : début de la réservation
 *  $endtime : fin de la réservation
*/
function verif_duree_max_resa_area($user, $id_room, $starttime, $endtime)
{
  if (authGetUserLevel($user,$id_room) >= 3)
    return true;
  $id_area = grr_sql_query1("SELECT area_id FROM ".TABLE_PREFIX."_room WHERE id=?","i",[$id_room]);
  $duree_max_resa_area = grr_sql_query1("SELECT duree_max_resa_area FROM ".TABLE_PREFIX."_area WHERE id=?","i",[$id_area]);
  $enable_periods =  grr_sql_query1("SELECT enable_periods FROM ".TABLE_PREFIX."_area WHERE id=?","i",[$id_area]);
  if ($enable_periods == 'y')
    $duree_max_resa_area = $duree_max_resa_area * 24 * 60;
  if ($duree_max_resa_area < 0)
    return true;
  else if ($endtime - $starttime > $duree_max_resa_area * 60)
    return false;
  return true;
}
/* function verif_participation_date($user, $id, $id_room, $date_booking, $date_now, $enable_periods, $endtime = '')
 $user : le login de l'utilisateur
 $id : l'id de la résa. Si -1, il s'agit d'une nouvelle réservation
 $id_room : id de la ressource
 $date_booking (string): la date de la réservation (n'est utile que si $id=-1)
 $date_now (integer): la date actuelle
 $enable_periods : les plages sont définies par des créneaux
 $endtime :

 vérifie
 modifie le paramètre global $can_delete_or_create
 renvoie un booléen indiquant si $user peut s'inscrire à la réservation $id
*/
function verif_participation_date($user, $id, $id_room, $date_booking, $date_now, $enable_periods, $endtime = '')
{
  global $correct_diff_time_local_serveur, $can_delete_or_create;
  $can_delete_or_create = "y";
  // On teste si l'utilisateur est administrateur
  $sql = "SELECT statut FROM ".TABLE_PREFIX."_utilisateurs WHERE login = ?";
  $statut_user = grr_sql_query1($sql,"s",[protect_data_sql($user)]);
  if ($statut_user == 'administrateur')
    return true;
  // Correction de l'avance en nombre d'heure du serveur sur les postes clients
  if ((isset($correct_diff_time_local_serveur)) && ($correct_diff_time_local_serveur!=0))
    $date_now -= 3600 * $correct_diff_time_local_serveur;
  // Créneaux basés sur les intitulés
  // Dans ce cas, on prend comme temps présent le jour même à minuit (ou 00:00 ? YN le 23/05/23)
  // Cela signifie qu'il est possible de modifier/réserver/supprimer tout au long d'une journée
  // même si l'heure est passée. (à voir, YN le 23/05/23)
  // Cela pourrait être amélioré en introduisant pour chaque créneau une heure limite de réservation.
  if ($enable_periods == "y")
  {
    $month = date("m",$date_now);
    $day = date("d",$date_now);
    $year = date("Y",$date_now);
    $date_now = mktime(0, 0, 0, $month, $day, $year);
  }
  if ($id != -1)
  { // il s'agit de l'édition d'une réservation existante
    if (($endtime != '') && ($endtime < $date_now))
      return false;
    if ((Settings::get("allow_user_delete_after_begin") == 1) || (Settings::get("allow_user_delete_after_begin") == 2))
      $sql = "SELECT end_time FROM ".TABLE_PREFIX."_entry WHERE id =?";
    else
      $sql = "SELECT start_time FROM ".TABLE_PREFIX."_entry WHERE id = ?";
    $date_booking = grr_sql_query1($sql,"i",[$id]);
    if ($date_booking < $date_now)
      return false;
    else
    {
      // dans le cas où le créneau est entamé, on teste si l'utilisateur a le droit de modifier la réservation
      // Si oui, on modifie la variable $can_delete_or_create avant que la fonction ne retourne true.
      if (Settings::get("allow_user_delete_after_begin") == 2)
      {
        $date_debut = grr_sql_query1("SELECT start_time FROM ".TABLE_PREFIX."_entry WHERE id =?","i",[$id]);
        if ($date_debut < $date_now)
          $can_delete_or_create = "n";
        else
          $can_delete_or_create = "y";
      }
      return true;
    }
  }
  else
  {
    if (Settings::get("allow_user_delete_after_begin") == 1)
    {
      $id_area = mrbsGetRoomArea($id_room);
      $resolution_area = grr_sql_query1("SELECT resolution_area FROM ".TABLE_PREFIX."_area WHERE id = ?","i",[$id_area]);
      if ($date_booking > $date_now - $resolution_area)
        return true;
      return false;
    }
    else
    {
      if ($date_booking > $date_now)
        return true;
      return false;
    }
  }
}
/** supprimerReservationsUtilisateursEXT()
 * Supprime les réservations des membres qui proviennent d'une source "EXT"
 * Returns:
 *   0        - An error occured
 *   non-zero - The entries were deleted
 */
function supprimerReservationsUtilisateursEXT($avec_resa,$avec_privileges)
{
  // Récupération de tous les utilisateurs de la source EXT
  $requete_users_ext = "SELECT login FROM ".TABLE_PREFIX."_utilisateurs WHERE source='ext' and statut<>'administrateur'";
  $res = grr_sql_query($requete_users_ext);
  $logins = array();
  $logins_liaison  = array();
  if ($res)
  {
    foreach($res as $row)
    {
      $logins[]=$row['login'];
    }
  }
  // Construction des requêtes de suppression à partir des différents utilisateurs à supprimer
  if ($avec_resa == 'y')
  {
    // Pour chaque utilisateur, on supprime les réservations qu'il a créées et celles dont il est bénéficiaire
    // Table grr_entry
    $req_suppr_table_entry = "DELETE FROM ".TABLE_PREFIX."_entry WHERE create_by = ";
    $first = 1;
    foreach($logins as $log)
    {
      if ($first == 1)
      {
        $req_suppr_table_entry .= "'$log' OR beneficiaire='$log'";
        $first = 0;
      }
      else
        $req_suppr_table_entry .= " OR create_by = '$log' OR beneficiaire = '$log' ";
    }
    // Pour chaque utilisateur, on supprime les réservations périodiques qu'il a créées et celles dont il est bénéficiaire
    // Table grr_repeat
    $req_suppr_table_repeat = "DELETE FROM ".TABLE_PREFIX."_repeat WHERE create_by = ";
    $first = 1;
    foreach ($logins as $log)
    {
      if ($first == 1)
      {
        $req_suppr_table_repeat .= "'$log' OR beneficiaire='$log'";
        $first = 0;
      }
      else
        $req_suppr_table_repeat .= " OR create_by = '$log' OR beneficiaire = '$log' ";
    }
    // Pour chaque utilisateur, on supprime les réservations périodiques qu'il a créées et celles dont il est bénéficiaire
    // Table grr_entry_moderate
    $req_suppr_table_entry_moderate = "DELETE FROM ".TABLE_PREFIX."_entry_moderate WHERE create_by = ";
    $first = 1;
    foreach ($logins as $log)
    {
      if ($first == 1)
      {
        $req_suppr_table_entry_moderate .= "'$log' OR beneficiaire='$log'";
        $first = 0;
      }
      else
        $req_suppr_table_entry_moderate .= " OR create_by = '$log' OR beneficiaire = '$log' ";
    }
  }
  $req_j_mailuser_room = "";
  $req_j_user_area = "";
  $req_j_user_room = "";
  $req_j_useradmin_area = "";
  $req_j_useradmin_site = "";
  foreach ($logins as $log)
  {
    // Table grr_j_mailuser_room
    $test = grr_sql_query1("SELECT count(login) FROM ".TABLE_PREFIX."_j_mailuser_room WHERE login='".$log."'");
    if ($test >=1)
    {
      if ($avec_privileges == "y")
      {
        if ($req_j_mailuser_room == "")
          $req_j_mailuser_room = "DELETE FROM ".TABLE_PREFIX."_j_mailuser_room WHERE login='".$log."'";
        else
          $req_j_mailuser_room .= " OR login = '".$log."'";
      }
      else
        $logins_liaison[] = strtolower($log);
    }
    // Table grr_j_user_area
    $test = grr_sql_query1("SELECT count(login) FROM ".TABLE_PREFIX."_j_user_area WHERE login='".$log."'");
    if ($test >=1)
    {
      if ($avec_privileges == "y")
      {
        if ($req_j_user_area == "")
          $req_j_user_area = "DELETE FROM ".TABLE_PREFIX."_j_user_area WHERE login='".$log."'";
        else
          $req_j_user_area .= " OR login = '".$log."'";
      }
      else
        $logins_liaison[] = strtolower($log);
    }
    // Table grr_j_user_room
    $test = grr_sql_query1("SELECT count(login) FROM ".TABLE_PREFIX."_j_user_room WHERE login='".$log."'");
    if ($test >= 1)
    {
      if ($avec_privileges == "y")
      {
        if ($req_j_user_room == "")
          $req_j_user_room = "DELETE FROM ".TABLE_PREFIX."_j_user_room WHERE login='".$log."'";
        else
          $req_j_user_room .= " OR login = '".$log."'";
      }
      else
        $logins_liaison[] = strtolower($log);
    }
    // Table grr_j_useradmin_area
    $test = grr_sql_query1("SELECT count(login) FROM ".TABLE_PREFIX."_j_useradmin_area WHERE login='".$log."'");
    if ($test >= 1)
    {
      if ($avec_privileges == "y")
      {
        if ($req_j_useradmin_area == "")
          $req_j_useradmin_area = "DELETE FROM ".TABLE_PREFIX."_j_useradmin_area WHERE login='".$log."'";
        else
          $req_j_useradmin_area .= " OR login = '".$log."'";
      }
      else
        $logins_liaison[] = strtolower($log);
    }
    // Table grr_j_useradmin_site
    $test = grr_sql_query1("SELECT count(login) FROM ".TABLE_PREFIX."_j_useradmin_site WHERE login='".$log."'");
    if ($test >= 1)
    {
      if ($avec_privileges == "y")
      {
        if ($req_j_useradmin_site == "")
          $req_j_useradmin_site = "DELETE FROM ".TABLE_PREFIX."_j_useradmin_site WHERE login='".$log."'";
        else
          $req_j_useradmin_site .= " OR login = '".$log."'";
      }
      else
        $logins_liaison[] = strtolower($log);
    }
  }
    // Suppression effective
  echo "<hr />\n";
  if ($avec_resa == 'y')
  {
    $nb = 0;
    $s = grr_sql_command($req_suppr_table_entry);
    if ($s != -1)
      $nb += $s;
    $s = grr_sql_command($req_suppr_table_repeat);
    if ($s != -1)
      $nb += $s;
    $s = grr_sql_command($req_suppr_table_entry_moderate);
    if ($s != -1)
      $nb += $s;
    echo "<p class='avertissement'>".get_vocab("tables_reservations").get_vocab("deux_points").$nb.get_vocab("entres_supprimees")."</p>\n";
  }
  $nb = 0;
  if ($avec_privileges == "y")
  {
    if ($req_j_mailuser_room != "")
    {
      $s = grr_sql_command($req_j_mailuser_room);
      if ($s != -1)
        $nb += $s;
    }
    if ($req_j_user_area != "")
    {
      $s = grr_sql_command($req_j_user_area);
      if ($s != -1)
        $nb += $s;
    }
    if ($req_j_user_room != "")
    {
      $s = grr_sql_command($req_j_user_room);
      if ($s != -1)
        $nb += $s;
    }
    if ($req_j_useradmin_area != "")
    {
      $s = grr_sql_command($req_j_useradmin_area);
      if ($s != -1)
        $nb += $s;
    }
    if ($req_j_useradmin_site != "")
    {
      $s = grr_sql_command($req_j_useradmin_site);
      if ($s != -1)
        $nb += $s;
    }
  }
  echo "<p class='avertissement'>".get_vocab("tables_liaison").get_vocab("deux_points").$nb.get_vocab("entres_supprimees")."</p>\n";
  if ($avec_privileges == "y")
  {
    // Enfin, suppression des utilisateurs de la source EXT qui ne sont pas administrateur
    $requete_suppr_users_ext = "DELETE FROM ".TABLE_PREFIX."_utilisateurs WHERE source='ext' and statut<>'administrateur'";
    $s = grr_sql_command($requete_suppr_users_ext);
    if ($s == -1)
      $s = 0;
    echo "<p class='avertissement'>".get_vocab("table_utilisateurs").get_vocab("deux_points").$s.get_vocab("entres_supprimees")."</p>\n";
  }
  else
  {
    $n = 0;
    foreach ($logins as $log)
    {
      if (!in_array(strtolower($log), $logins_liaison))
      {
        grr_sql_command("DELETE FROM ".TABLE_PREFIX."_utilisateurs WHERE login='".$log."'");
        $n++;
      }
    }
    echo "<p class='avertissement'>".get_vocab("table_utilisateurs").get_vocab("deux_points").$n.get_vocab("entres_supprimees")."</p>\n";
  }
}
/** grrDelOverloadFromEntries()
 * Supprime les données du champ $id_field de toutes les réservations
 */
function grrDelOverloadFromEntries($id_field)
{
  $begin_string = "<".$id_field.">";
  $end_string = "</".$id_field.">";
  // On cherche à quel domaine est rattaché le champ additionnel
  $id_area = grr_sql_query1("SELECT id_area FROM ".TABLE_PREFIX."_overload WHERE id=?","i",[$id_field]);
  if ($id_area == -1)
    fatal_error(0, get_vocab('error_area') . $id_field . get_vocab('not_found'));
  // On cherche toutes les ressources du domaine
  $call_rooms = grr_sql_query("SELECT id FROM ".TABLE_PREFIX."_room WHERE area_id = ?","i",[$id_area]);
  if (!$call_rooms)
    fatal_error(0, get_vocab('error_room') . $id_area . get_vocab('not_found'));
  foreach($call_rooms as $row)
  {
    // On cherche toutes les resas de cette ressource
    $call_resa = grr_sql_query("SELECT id, overload_desc FROM ".TABLE_PREFIX."_entry WHERE room_id =?","i",[$row['id']]);
    if (! $call_resa)
      fatal_error(0, get_vocab('invalid_entry_id'));
    foreach($call_resa as $row2)
    {
      $overload_desc = $row2['overload_desc'];
      $begin_pos = strpos($overload_desc,$begin_string);
      $end_pos = strpos($overload_desc,$end_string);
      if ( $begin_pos !== false && $end_pos !== false )
      {
        $endpos = $end_pos + 1 + strlen($begin_string);
        $debut_new_chaine = substr($overload_desc,0,$begin_pos);
        $fin_new_chaine = substr($overload_desc,$endpos);
        $new_chaine = $debut_new_chaine.$fin_new_chaine;
        grr_sql_command("UPDATE ".TABLE_PREFIX."_entry SET overload_desc = ? WHERE id = ?","si",[$new_chaine,$row2['id']]);
      }
    }
    // On cherche toutes les séquences de réservations de cette ressource
    $call_resa = grr_sql_query("SELECT id, overload_desc FROM ".TABLE_PREFIX."_repeat WHERE room_id =?","i",[$row['id']]);
    if (!$call_resa)
      fatal_error(0, get_vocab('invalid_entry_id'));
    foreach($call_resa as $row2)
    {
      $overload_desc = $row2['overload_desc'];
      $begin_pos = strpos($overload_desc,$begin_string);
      $end_pos = strpos($overload_desc,$end_string);
      if ($begin_pos !== false && $end_pos !== false)
      {
        $endpos = $end_pos + 1 + strlen($begin_string);
        $debut_new_chaine = substr($overload_desc,0,$begin_pos);
        $fin_new_chaine = substr($overload_desc,$endpos);
        $new_chaine = $debut_new_chaine.$fin_new_chaine;
        grr_sql_command("UPDATE ".TABLE_PREFIX."_repeat SET overload_desc = ? WHERE id = ?","si",[$new_chaine,$row2['id']]);
      }
    }
  }
}
/** grrGetOverloadDescArray($ofl,$od)
 *
 * Return an array with all additionnal fields from grr_entry.overload_desc
 * $od - overload_desc of the entry
 * $ofl - overload fields list (depends on the area)
 *
 */
function grrGetOverloadDescArray($ofl,$od)
{
  $overload_array = array();
  foreach ($ofl as $field=>$fieldtype)
  {
    $begin_string = "@".$ofl[$field]["id"]."@";
    $end_string = "@/".$ofl[$field]["id"]."@";
    $l1 = strlen($begin_string);
    $l2 = strlen($end_string);
    $chaine = $od;
    $balise_fermante = 'n';
    $balise_ouvrante = 'n';
    $traitement1 = true;
    $traitement2 = true;
    while (($traitement1 !== false) || ($traitement2 !== false))
    {
      // le premier traitement cherche la prochaine occurrence de $begin_string et retourne la portion de chaine après cette occurrence
      if ($traitement1 != false)
      {
        $chaine1 = strstr ($chaine, $begin_string);
        // retourne la sous-chaîne de $chaine, allant de la première occurrence de $begin_string jusqu'à la fin de la chaîne.
        if ($chaine1 !== false)
        {
          // on a trouvé une occurrence de $begin_string
          $balise_ouvrante = 'y';
          // on sait qu'il y a au moins une balise ouvrante
          $chaine = substr($chaine1, $l1, strlen($chaine1)- $l1);
          // on retourne la chaine en ayant éliminé le début de chaine correspondant à $begin_string
          $result = $chaine;
          // On mémorise la valeur précédente
        }
        else
          $traitement1 = false;
      }
      //le 2ème traitement cherche la dernière occurrence de $end_string en partant de la fin et retourne la portion de chaine avant cette occurrence
      if ($traitement2 != false)
      {
        //La boucle suivante a pour effet de déterminer la dernière occurrence de $end_string
        $ind = 0;
        $end_pos = true;
        while ($end_pos !== false)
        {
          $end_pos = strpos($chaine,$end_string,$ind);
          if ($end_pos !== false)
          {
            $balise_fermante='y';
            $ind_old = $end_pos;
            $ind = $end_pos + $l2;
          }
          else
            break;
        }
        //a ce niveau, $ind_old est la dernière occurrence de $end_string trouvée dans $chaine
        if ($ind != 0 )
        {
          $chaine = substr($chaine,0,$ind_old);
          $result = $chaine;
        }
        else
          $traitement2=false;
      }
    }
  // while
    if (($balise_fermante == 'n' ) || ($balise_ouvrante == 'n'))
      $overload_array[$field]["valeur"]='';
    else
      $overload_array[$field]["valeur"]=urldecode($result);
    $overload_array[$field]["id"] = $ofl[$field]["id"];
    $sql = "SELECT affichage,overload_mail,obligatoire,confidentiel FROM ".TABLE_PREFIX."_overload WHERE id = ?";
    $res = grr_sql_query($sql,"i",[$ofl[$field]["id"]]);
    if($res){
      $row = grr_sql_row($res,0);
      $overload_array[$field]["affichage"] = $row[0];
      $overload_array[$field]["overload_mail"] = $row[1];
      $overload_array[$field]["obligatoire"] = $row[2];
      $overload_array[$field]["confidentiel"] = $row[3];
      grr_sql_free($res);
    }
    else
      fatal_error(1,get_vocab("error_reading_database"));
  }
  return $overload_array;
}

function resa_est_hors_reservation($start_time,$end_time)
{
  // On teste si la réservation est dans le calendrier "hors réservations"
  $test = grr_sql_query1("SELECT DAY FROM ".TABLE_PREFIX."_calendar WHERE DAY = ? or DAY = ?","ii",[$start_time,$end_time]);
  if ($test != -1)
    return true;
  else
    return false;
}
function resa_est_hors_reservation2($start_time,$end_time,$area)
{
  // S'agit-il d'une journée qui n'est pas affichée pour le domaine considéré ?
  $sql = "SELECT display_days FROM ".TABLE_PREFIX."_area WHERE id = ?";
  $result = grr_sql_query1($sql,"i",[$area]);
  $jour_semaine = date("w",$start_time);
  if (substr($result, $jour_semaine, 1) == 'n')
    return true;
  $jour_semaine = date("w",$end_time);
  if (substr($result, $jour_semaine, 1) == 'n')
    return true;
  return false;
}

// ressources
/* fonction get_planning_area_values($id_area)
 * modifie les variables globales $resolution, $morningstarts, $eveningends, $eveningends_minutes, $weekstarts, $twentyfourhour_format, $enable_periods, $periods_name, $display_day, $nb_display_day avec les valeurs associées au domaine d'id $id_area
*/
function get_planning_area_values($id_area)
{
  global $resolution, $morningstarts, $eveningends, $eveningends_minutes, $weekstarts, $twentyfourhour_format, $enable_periods, $periods_name, $display_day, $nb_display_day;
  $sql = "SELECT calendar_default_values, resolution_area, morningstarts_area, eveningends_area, eveningends_minutes_area, weekstarts_area, twentyfourhour_format_area, enable_periods, display_days
  FROM ".TABLE_PREFIX."_area
  WHERE id = ?";
  $res = grr_sql_query($sql,"i",[$id_area]);
  if (!$res)
  {
    // fatal_error(0, grr_sql_error());
    include "trailer.inc.php";
    exit;
  }
  $row_ = grr_sql_row($res, 0);
    if (!is_array($row_))
    {
      echo "Erreur de lecture en base de données";
      include "trailer.inc.php";
      exit;
    }
  $nb_display_day = 0;
  for ($i = 0; $i < 7; $i++)
  {
    if (substr($row_[8],$i,1) == 'y')
    {
      $display_day[$i] = 1;
      $nb_display_day++;
    }
    else
      $display_day[$i] = 0;
  }
  if ($row_[7] == 'y')  // Créneaux basés sur les intitulés
  {
    $resolution = 60;
    $morningstarts = 12;
    $eveningends = 12;
    $sql_periode = grr_sql_query("SELECT nom_periode FROM ".TABLE_PREFIX."_area_periodes WHERE id_area=?","i",[$id_area]);
    $eveningends_minutes = grr_sql_count($sql_periode) - 1;
    for($i=0;$i <= $eveningends_minutes;$i++){
      $periods_name[$i] = grr_sql_query1("SELECT nom_periode FROM ".TABLE_PREFIX."_area_periodes WHERE id_area=? and num_periode= ?","ii",[$id_area,$i]);
    }
    $enable_periods = "y";
    $weekstarts = intval($row_[5]);
    $twentyfourhour_format = intval($row_[6]);
  }
  else    // Créneaux basés sur le temps
  {
    if ($row_[0] != 'y')
    {
      $resolution = intval($row_[1]);
      $morningstarts = intval($row_[2]);
      $eveningends = intval($row_[3]);
      $eveningends_minutes = intval($row_[4]);
      $enable_periods = "n";
      $weekstarts = intval($row_[5]);
      $twentyfourhour_format = intval($row_[6]);
    }
  }
}

//Retourne le domaine par défaut; Utilisé si aucun domaine n'a été défini.
function get_default_area($id_site = -1)
{
  $id_site = intval($id_site);
  if (Settings::get("module_multisite") == "Oui")
    $use_multisite = true;
  else
    $use_multisite = false;
  if (OPTION_IP_ADR==1)
  {
    $sql = "SELECT ip_adr, id FROM ".TABLE_PREFIX."_area WHERE ip_adr!='' ORDER BY access, order_display, area_name";
    $res = grr_sql_query($sql);
    if ($res){
      foreach($res as $row){
        if (compare_ip_adr($_SERVER['REMOTE_ADDR'],$row['ip_adr'])){
          return intval($row['id']);
        }
      }
    }
  }
  if (authGetUserLevel(getUserName(),-1) >= 6)
  {
    if (($id_site != -1) and ($use_multisite))
      $res = grr_sql_query("SELECT a.id FROM ".TABLE_PREFIX."_area a JOIN ".TABLE_PREFIX."_j_site_area j ON a.id=j.id_area WHERE j.id_site= ? ORDER BY a.order_display, a.area_name","i",[$id_site]);
    else
      $res = grr_sql_query("SELECT id FROM ".TABLE_PREFIX."_area ORDER BY access, order_display, area_name");
  }
  else
  {
    if (($id_site != -1) and ($use_multisite))
      $res = grr_sql_query("SELECT a.id FROM ".TABLE_PREFIX."_area a JOIN ".TABLE_PREFIX."_j_site_area j ON a.id=j.id_area WHERE j.id_site= ? AND a.access!='r' ORDER BY a.order_display, a.area_name","i",[$id_site]);
    else
      $res = grr_sql_query("SELECT id FROM ".TABLE_PREFIX."_area WHERE access!='r' ORDER BY access, order_display, area_name");
  }
  if ($res && grr_sql_count($res)>0 )
  {
    $row = grr_sql_row($res, 0);
    grr_sql_free($res);
    return intval($row[0]);
  }
  else
  {
    if (($id_site != -1) and ($use_multisite))
      $res = grr_sql_query("SELECT a.id
        FROM (".TABLE_PREFIX."_area a JOIN ".TABLE_PREFIX."_j_site_area j ON a.id=j.id_area) JOIN ".TABLE_PREFIX."_j_user_area u ON a.id=u.id_area
        WHERE j.id_site=? AND u.login=?
        ORDER BY a.order_display, a.area_name","is",[$id_site,getUserName()]);
    else
      $res = grr_sql_query("SELECT a.id FROM ".TABLE_PREFIX."_area a JOIN ".TABLE_PREFIX."_j_user_area j ON a.id = j.id_area
        WHERE login=?
        ORDER BY order_display, area_name","s",[getUserName()]);
    if ($res && grr_sql_count($res)>0 )
    {
      $row = grr_sql_row($res, 0);
      grr_sql_free($res);
      return intval($row[0]);
    }
    else
      return -1;
  }
}
/**
 *Fonction qui calcule $room, $area et $id_site à partir de $_GET['room'], $_GET['area'], $_GET['id_site']
 */
function Definition_ressource_domaine_site()
{
  global $room, $area, $id_site;
  if (isset($_GET['room']))
  {
    $room = intval(clean_input($_GET['room']));
    $area = mrbsGetRoomArea($room);
    $id_site = mrbsGetAreaSite($area);
  }
  else
  {
    $room = NULL;
    if (isset($_GET['area']))
    {
      $area = intval(clean_input($_GET['area']));
      $id_site = mrbsGetAreaSite($area);
    }
    else
    {
      $area = NULL;
      if (isset($_GET["id_site"]))
      {
        $id_site = intval(clean_input($_GET["id_site"]));
        $area = get_default_area($id_site);
      }
      else
      {
        $id_site = get_default_site();
        $area = get_default_area($id_site);
      }
    }
  }
}
/* fonction get_default_site
    renvoie id_site du site par défaut de l'utilisateur, sinon celui de la table setting, sinon celui de plus petit id dans la table site
*/
function get_default_site()
{
  $user = getUserName();
  if ($user != ''){
    $id_site = grr_sql_query1("SELECT default_site FROM ".TABLE_PREFIX."_utilisateurs WHERE login =?","s",[$user]);
    if ($id_site > 0){return intval($id_site);}
  }
  // ici l'utilisateur n'est pas reconnu ou il n'a pas de site par défaut : on passe aux informations de la table settings
  $id_site = grr_sql_query1("SELECT VALUE FROM ".TABLE_PREFIX."_setting WHERE NAME ='default_site' ");
  $test = grr_sql_query1("SELECT id FROM ".TABLE_PREFIX."_site WHERE id = ?","i",[$id_site]);
  if ($test >0){return intval($id_site);}
  else { // il n'y a pas de site par défaut dans la table setting, on prend le premier site
    $id_site = grr_sql_query1("SELECT min(id) FROM ".TABLE_PREFIX."_site ");
    return intval($id_site);
  }
}


/* Fonction spéciale SE3
 $grp : le nom du groupe
 $uid : l'uid de l'utilisateur
 Cette fonction retourne "oui" ou "non" selon que $uid appartient au groupe $grp, ou bien "faux" si l'interrogation du LDAP échoue
 Seuls les groupes de type "posixGroup" sont supportés (les groupes de type "groupOfNames" ne sont pas supportés).
*/
 function se3_grp_members ($grp, $uid)
 {
  include "config_ldap.inc.php";
  $est_membre="non";
  // LDAP attributs
  $members_attr = array (
    $ldap_group_member_attr
    // Recherche des Membres du groupe
    );
    // Avec des GroupOfNames, ce ne serait pas ça.
  $ds = @ldap_connect($ldap_adresse, $ldap_port);
  if ($ds)
  {
    $r = @ldap_bind ($ds, $ldap_login, $ldap_pwd);
    // Bind anonyme
    if ($r)
    {
      // La requête est adaptée à un serveur SE3...
      //$result = @ldap_search($ds, "cn={$grp},{$ldap_group_base}",$ldap_group_filter, $members_attr);
            $result = @ldap_search($ds, "{$ldap_group_base}","(& (cn={$grp}) $ldap_group_filter )", $members_attr);
            // sur la proposition de marylenepaillassa (Forum #255)
      // Peut-être faudrait-il dans le $tab_grp_autorise mettre des chaines 'cn=$grp,ou=Groups'
      if ($result)
      {
        $info = @ldap_get_entries($ds, $result);
        if ($info["count"] == 1)
        {
          for ($loop = 0; $loop < $info[0][$ldap_group_member_attr]["count"]; $loop++)
          {
            if ($info[0][$ldap_group_member_attr][$loop] == $uid)
              $est_membre="oui";
          }
        }
        @ldap_free_result($result);
      }
    }
    else
      return false;
    @ldap_close($ds);
  }
  else
    return false;
  return $est_membre;
 }

// Les lignes suivantes permettent la compatibilité de GRR avec la variable register_global à off
unset($day);
if (isset($_GET["day"]))
{
  $day = $_GET["day"];
  settype($day,"integer");
  if ($day < 1)
    $day = 1;
  if ($day > 31)
    $day = 31;
}
unset($month);
if (isset($_GET["month"]))
{
  $month = $_GET["month"];
  settype($month,"integer");
  if ($month < 1)
    $month = 1;
  if ($month > 12)
    $month = 12;
}
unset($year);
if (isset($_GET["year"]))
{
  $year = $_GET["year"];
  settype($year,"integer");
  if ($year < 1900)
    $year = 1900;
  if ($year > 2100)
    $year = 2100;
}
?>