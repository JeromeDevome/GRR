<?php
/**
 * ./admin/edit_room.php
 * Interface de creation/modification des ressources de l'application GRR
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2024-10-09 11:56$
 * @author    Laurent Delineau & JeromeB & Marc-Henri PAMISEU & Yan Naessens & Daniel Antelme
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
$grr_script_name = "edit_room.php";

include "../include/admin.inc.php";

$ok = NULL;
if (Settings::get("module_multisite") == "Oui")
  $id_site = (int)getFormVar("id_site","int",-1);
$action = clean_input(getFormVar("action","string"));
$room = (int)getFormVar("room","int",-1);
if (isset($_POST["active_cle"]))
  $active_cle = 'y';
else
{
  $active_cle = 'n';
  // toutes les clés sont considerees comme restituees
  grr_sql_command("update ".TABLE_PREFIX."_entry set clef = 0 where room_id =? ","i",[$room]);
}
$active_participant = (int)getFormVar("active_participant","int",0);
if (isset($_POST["active_ressource_empruntee"]))
  $active_ressource_empruntee = 'y';
else
{
  $active_ressource_empruntee = 'n';
  // toutes les reservations sont considerees comme restituees
  grr_sql_command("update ".TABLE_PREFIX."_entry set statut_entry = '-' where room_id =? ","i",[$room]);
}
$allow_action_in_past  = isset($_POST["allow_action_in_past"]) ? "y" : NULL;
$area_id = (int)getFormVar("area_id","int",-1);
$booking_range = (int)getFormVar("booking_range","int");
if ($booking_range<-1)
  $booking_range = -1;
$capacity = (int)getFormVar("capacity","int");
$change_done = isset($_POST["change_done"]) ? $_POST["change_done"] : NULL;
$change_room = isset($_POST["change_room"]) ? $_POST["change_room"] : NULL;
if (isset($_POST["change_room_and_back"]))
{
  $change_room = "yes";
  $change_done = "yes";
}
$comment_room = clean_input(getFormVar("comment_room","string"));
$delais_min_resa_room = (int)getFormVar("delais_min_resa_room","int");
$delais_max_resa_room = (int)getFormVar("delais_max_resa_room","int");
$delais_option_reservation = (int)getFormVar("delais_option_reservation","int");
$description = clean_input(getFormVar("description","string"));
$dont_allow_modify  = isset($_POST["dont_allow_modify"]) ? "y" : NULL;
$id_area = (int)getFormVar("id_area",'int');
$max_booking = (int)getFormVar("max_booking","int");
if ($max_booking<-1)
  $max_booking = -1;
$max_booking_on_range = (int)getFormVar("max_booking_on_range","int");
if ($max_booking_on_range<-1)
  $max_booking_on_range = -1;
$moderate = isset($_POST['moderate']) ? $_POST["moderate"] : NULL;
if ($moderate == 'on')
  $moderate = 1;
else
  $moderate = 0;
$picture_room = getFormVar("picture_room","string");
$qui_peut_reserver_pour  = (int)getFormVar("qui_peut_reserver_pour","int");
$retour_page = clean_input(getFormVar("retour_page","string"));
$room_name = clean_input(getFormVar("room_name","string"));
$room_order = (int)getFormVar("room_order","int",0);
$show_comment = isset($_POST["show_comment"]) ? "y" : "n";
$show_fic_room = isset($_POST["show_fic_room"]) ? "y" : "n";
$statut_room = isset($_POST["statut_room"]) ? "0" : "1";
$type_affichage_reser = (int)getFormVar("type_affichage_reser","int");
$who_can_book = (int)getFormVar("who_can_book","int",1);
$who_can_see = (int)getFormVar("who_can_see","int");

/*
$access = isset($_POST["access"]) ? $_POST["access"] : NULL;
$ip_adr = isset($_POST["ip_adr"]) ? clean_input($_POST["ip_adr"]) : NULL;


$number_periodes = isset($_POST["number_periodes"]) ? $_POST["number_periodes"] : NULL;
$retour_resa_obli = isset($_POST["retour_resa_obli"]) ? $_POST["retour_resa_obli"] : NULL;
*/
$back = (isset($_SERVER['HTTP_REFERER']))? htmlspecialchars_decode($_SERVER['HTTP_REFERER'], ENT_QUOTES): "./admin_accueil.php";


// memorisation du chemin de retour
if (!isset($retour_page))
{
  $retour_page = $back;
  if(!strstr($retour_page,"login.php")){
    // on nettoie la chaine :
    $long_chaine_a_supprimer = strlen(strstr($retour_page, "&amp;msg=")); // longueur de la chaine à partir de la premiere occurrence de &amp;msg=
    if ($long_chaine_a_supprimer == 0)
      $long_chaine_a_supprimer = strlen(strstr($retour_page, "?msg="));
    $long = strlen($retour_page) - $long_chaine_a_supprimer;
    $retour_page = substr($retour_page, 0, $long);
  }
  else
    $retour_page = "./admin_accueil.php";
}

// modification d'une ressource : admin ou gestionnaire
$user_id = getUserName();
if (authGetUserLevel($user_id,-1) < 6)
{
  if($room != -1)
  {
    // Il s'agit d'une modif de ressource
    if (((authGetUserLevel($user_id,$room) < 3)) || (!verif_acces_ressource($user_id, $room)))
    {
      showAccessDenied($back);
      exit();
    }
  }
  else
  {
    if (isset($area_id))
    {
      // On verifie que le domaine $area_id existe
      $test = grr_sql_query1("SELECT id FROM ".TABLE_PREFIX."_area WHERE id= ? ","i",[$area_id]);
      if ($test == -1)
      {
        showAccessDenied($back);
        exit();
      }
      // Il s'agit de l'ajout d'une ressource
      // On verifie que l'utilisateur a le droit d'ajouter des ressources
      if ((authGetUserLevel($user_id, $area_id, 'area') < 4))
      {
        showAccessDenied($back);
        exit();
      }
    }
  }
}
$msg ='';
// traitement des données
$titre = "";
if ($room != -1){
  // Il s'agit d'une modification ou duplication d'une ressource
  $res = grr_sql_query("SELECT * FROM ".TABLE_PREFIX."_room WHERE id=? ","i",[$room]);
  if (!$res)
    fatal_error(0, get_vocab('error_room') . $room . get_vocab('not_found'));
  $Room = grr_sql_row_keyed($res, 0);
  grr_sql_free($res);
  $area_id = $Room['area_id'];
  $area_name = grr_sql_query1("select area_name from ".TABLE_PREFIX."_area where id=? ","i",[$area_id]);
  if ($action == "dupliquer")
    $titre_action = get_vocab("duplique_ressource");
  else
    $titre_action = get_vocab("editroom");
}
else{ // ajout
  $area_name = grr_sql_query1("select area_name from ".TABLE_PREFIX."_area where id=? ","i",[$area_id]);
  $titre_action = get_vocab("addroom");
  $Room = array('picture_room' => '',
                "id" => '',
                "room_name" => '',
                "description" => '',
                'comment_room' => '',
                'show_comment' => 'n',
                "capacity" => '',
                "delais_max_resa_room" => -1,
                "delais_min_resa_room" => 0,
                "delais_option_reservation" => 0,
                "allow_action_in_past" => 'n',
                "dont_allow_modify" => 'n',
                "qui_peut_reserver_pour" => 6,
                "who_can_see" => 0,
                "who_can_book" => 1,
                "order_display" => 0,
                "type_affichage_reser" => 0,
                "max_booking" => -1,
                "booking_range" => -1,
                'max_booking_on_range' => -1,
                'statut_room' => '',
                'moderate' => '',
                'show_fic_room' => '',
                'active_ressource_empruntee' => 'n',
                'active_cle' => 'n',
                'active_participant' => 0);
}
// Domaine
$enable_periods = grr_sql_query1("select enable_periods from ".TABLE_PREFIX."_area where id=? ","i",[$area_id]);
if (((authGetUserLevel($user_id,$area_id,"area") >=4 ) || (authGetUserLevel($user_id,$room) >= 4)) && ($enable_periods == 'n'))
{    // les creneaux sont bases sur le temps : on ne peut pas changer une ressource pour un domaine dont les créneaux sont basés sur des intitulés
  if(authGetUserLevel($user_id,-1,'area') >= 6)
    $sql = "SELECT id,area_name FROM ".TABLE_PREFIX."_area where enable_periods='n' ORDER BY area_name ASC";
  else if (authGetUserLevel($user_id,$area_id,'area') == 5)
    $sql = "SELECT distinct a.id, a.area_name 
      FROM ".TABLE_PREFIX."_area a, ".TABLE_PREFIX."_j_site_area j, ".TABLE_PREFIX."_site s,  ".TABLE_PREFIX."_j_useradmin_site u
      WHERE a.id=j.id_area and u.id_site=j.id_site  and s.id=u.id_site and u.login='".$user_id."'  and  enable_periods='n'
      ORDER BY a.area_name ASC";
  else
    $sql = "SELECT id,area_name
      FROM ".TABLE_PREFIX."_area a JOIN ".TABLE_PREFIX."_j_useradmin_area u ON a.id=u.id_area
      WHERE u.login='".$user_id."' and a.enable_periods='n'
      ORDER BY a.area_name ASC";
  $res = grr_sql_query($sql);
  if(!$res)
    fatal_error(0,grr_sql_error());
  else{
    $Areas = array();
    foreach($res as $row){
      $Areas[] = $row;
    }
    $nb_area = count($Areas);
  }
}
// Gestion des ressources
if (($room != -1) || (isset($area_id)))
{
  // Enregistrement d'une ressource
  if (isset($change_room))
  {
    if (isset($_POST['sup_img']))
    {
      $dest = '../images/';
      $ok1 = false;
      if ($f = @fopen("$dest/.test", "w"))
      {
          @fputs($f, '<'.'?php $ok1 = true; ?'.'>');
          @fclose($f);
          include("$dest/.test");
      }
      if (!$ok1)
      {
          $msg .= "L\'image n\'a pas pu etre supprimee : probleme d\'écriture sur le repertoire. Veuillez signaler ce problème à l\'administrateur du serveur.\\n";
          $ok = 'no';
      }
      else
      {
          if (@file_exists($dest."img_".TABLE_PREFIX."".$room.".jpg"))
              unlink($dest."img_".TABLE_PREFIX."".$room.".jpg");
          if (@file_exists($dest."img_".TABLE_PREFIX."".$room.".png"))
              unlink($dest."img_".TABLE_PREFIX."".$room.".png");
          if (@file_exists($dest."img_".TABLE_PREFIX."".$room.".gif"))
              unlink($dest."img_".TABLE_PREFIX."".$room.".gif");
          $sql_picture = "UPDATE ".TABLE_PREFIX."_room SET picture_room='' WHERE id=? ";
          if (grr_sql_command($sql_picture,"i",[$room]) < 0)
          {
              fatal_error(0, get_vocab('update_room_failed') . grr_sql_error());
              $ok = 'no';
          }
      }
    }
    if (empty($capacity))
      $capacity = 0;
    if ($capacity < 0)
      $capacity = 0;
    if ($delais_max_resa_room < 0)
      $delais_max_resa_room = -1;
    if ($delais_min_resa_room < 0)
      $delais_min_resa_room = 0;
    if ($delais_option_reservation < 0)
      $delais_option_reservation = 0;
    if ($allow_action_in_past == '')
      $allow_action_in_past = 'n';
    if ($dont_allow_modify == '')
      $dont_allow_modify = 'n';
    if (($room != -1) && !((isset($action) && ($action == "dupliquer"))))
    {
      $sql = "UPDATE ".TABLE_PREFIX."_room SET
      room_name='".protect_data_sql($room_name)."',
      description='".protect_data_sql($description)."', ";
      if ($picture_room != '')
        $sql .= "picture_room='".protect_data_sql($picture_room)."', ";
      $sql .= "comment_room='".protect_data_sql(corriger_caracteres($comment_room))."',
      show_comment='".$show_comment."',
      area_id='".protect_data_sql($area_id)."',
      show_fic_room='".$show_fic_room."',
      active_ressource_empruntee = '".$active_ressource_empruntee."',
      active_cle = '".$active_cle."',
      active_participant = '".$active_participant."',
      capacity='".protect_data_sql($capacity)."',
      delais_max_resa_room='".protect_data_sql($delais_max_resa_room)."',
      delais_min_resa_room='".protect_data_sql($delais_min_resa_room)."',
      delais_option_reservation='".protect_data_sql($delais_option_reservation)."',
      allow_action_in_past='".protect_data_sql($allow_action_in_past)."',
      dont_allow_modify='".$dont_allow_modify."',
      qui_peut_reserver_pour = '".$qui_peut_reserver_pour."',
      who_can_see = '".$who_can_see."',
      who_can_book = '".$who_can_book."',
      order_display='".protect_data_sql($room_order)."',
      type_affichage_reser='".protect_data_sql($type_affichage_reser)."',
      max_booking='".protect_data_sql($max_booking)."',
      booking_range='".protect_data_sql($booking_range)."',
      max_booking_on_range='".protect_data_sql($max_booking_on_range)."',
      moderate='".$moderate."',
      statut_room='".$statut_room."'
      WHERE id=? ";
      if (grr_sql_command($sql,"i",[$room]) < 0)
      {
        fatal_error(0, get_vocab('update_room_failed') . grr_sql_error());
        $ok = 'no';
      }
    }
    else
    {
      $sql = "insert into ".TABLE_PREFIX."_room
      SET room_name='".protect_data_sql($room_name)."',
      area_id='".protect_data_sql($area_id)."',
      description='".protect_data_sql($description)."',
      picture_room='".protect_data_sql($picture_room)."',
      comment_room='".protect_data_sql(corriger_caracteres($comment_room))."',
      show_fic_room='".$show_fic_room."',
      active_ressource_empruntee = '".$active_ressource_empruntee."',
      active_cle = '".$active_cle."',
      active_participant = '".$active_participant."',
      capacity='".protect_data_sql($capacity)."',
      delais_max_resa_room='".protect_data_sql($delais_max_resa_room)."',
      delais_min_resa_room='".protect_data_sql($delais_min_resa_room)."',
      delais_option_reservation='".protect_data_sql($delais_option_reservation)."',
      allow_action_in_past='".protect_data_sql($allow_action_in_past)."',
      dont_allow_modify='".$dont_allow_modify."',
      qui_peut_reserver_pour = '".$qui_peut_reserver_pour."',
      who_can_see = '".$who_can_see."',
      who_can_book = '".$who_can_book."',
      order_display='".protect_data_sql($room_order)."',
      type_affichage_reser='".protect_data_sql($type_affichage_reser)."',
      max_booking='".protect_data_sql($max_booking)."',
      booking_range='".protect_data_sql($booking_range)."',
      max_booking_on_range='".protect_data_sql($max_booking_on_range)."',
      moderate='".$moderate."',
      statut_room='".$statut_room."'";
      if (grr_sql_command($sql) < 0)
        fatal_error(1, "<p>" . grr_sql_error());
      $room = grr_sql_insert_id();
    }
    #Si room_name est vide on le change maintenant que l'on a l'id room
    if ($room_name == '')
    {
      $room_name = get_vocab("room")." ".$room;
      $sql = "UPDATE ".TABLE_PREFIX."_room SET room_name=? WHERE id=?";
      grr_sql_command($sql,"si",[$room_name,$room]);
    }
    // image d'illustration
    $doc_file = isset($_FILES["doc_file"]) ? $_FILES["doc_file"] : NULL;
    /* Test premier, juste pour bloquer les double extensions */
    if (count(explode('.', $doc_file['name'])) > 2) {
      $msg .= "L\'image n\'a pas pu être enregistrée : les seules extentions autorisées sont gif, png et jpg.\\n";
      $ok = 'no';
    } 
    elseif(preg_match("`\.([^.]+)$`", $doc_file['name'], $match)) {
      $ext = strtolower($match[1]);
      if ($ext != 'jpg' && $ext != 'png'&& $ext != 'gif') {
        $msg .= "L\'image n\'a pas pu etre enregistree : les seules extentions autorisees sont gif, png et jpg.\\n";
        $ok = 'no';
      }
      else {
        /* deuxième test passé, l'extension est autorisée */
        /* 3ème test avec fileinfo */
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $fileType = finfo_file($finfo, $doc_file['tmp_name']);
        /* 4ème test avec gd pour valider que c'est bien une image malgré tout - nécessaire ou parano ? */
        switch($fileType) {
          case "image/gif":
            /* recreate l'image, supprime les data exif */
            $logoRecreated = @imagecreatefromgif ( $doc_file['tmp_name'] );
            /* fix pour la transparence */
            imageAlphaBlending($logoRecreated, true);
            imageSaveAlpha($logoRecreated, true);
            $extSafe = "gif";
            break;
          case "image/jpeg":
            $logoRecreated = @imagecreatefromjpeg ( $doc_file['tmp_name'] );
            $extSafe = "jpg";
            break;
          case "image/png":
            $logoRecreated = @imagecreatefrompng ( $doc_file['tmp_name'] );
            /* fix pour la transparence */
            imageAlphaBlending($logoRecreated, true);
            imageSaveAlpha($logoRecreated, true);
            $extSafe = "png";
            break;
          default:
            $msg .= "L\'image n\'a pas pu être enregistrée : type mime incompatible.\\n";
            $ok = 'no';
            $extSafe = false;
            break;
        }
      }
      if (!$logoRecreated || $extSafe === false) {
        /* la fonction imagecreate a échoué, donc l'image est corrompue ou craftée */
        $msg .= "L\'image n\'a pas pu être enregistrée : fichier corrompu.\\n";
        $ok = 'no';
      }
      else {   /* je teste si la destination est writable */
        $dest = '../images/';
        $ok1 = is_writable($dest);
        if (!$ok1)
        {
          $msg .= "L\'image n\'a pas pu etre enregistree : probleme d\'ecriture sur le repertoire IMAGES. Veuillez signaler ce probleme e l\'administrateur du serveur.\\n";
          $ok = 'no';
        }
        else
        {
          $ok1 = @copy($doc_file['tmp_name'], $dest.$doc_file['name']);
          if (!$ok1)
            $ok1 = @move_uploaded_file($doc_file['tmp_name'], $dest.$doc_file['name']);
          if (!$ok1)
          {
            $msg .= "L\'image n\'a pas pu etre enregistree : probleme de transfert. Le fichier n\'a pas pu etre transfere sur le repertoire IMAGES. Veuillez signaler ce probleme à l\'administrateur du serveur.\\n";
            $ok = 'no';
          }
          else
          {
            $tab = explode(".", $doc_file['name']);
            $ext = strtolower($tab[1]);
            if (@file_exists($dest."img_".TABLE_PREFIX."".$room.".".$extSafe))
              @unlink($dest."img_".TABLE_PREFIX."".$room.".".$extSafe);
            rename($dest.$doc_file['name'],$dest."img_".TABLE_PREFIX."".$room.".".$extSafe);
            @chmod($dest."img_".TABLE_PREFIX."".$room.".".$extSafe, 0666);
            $picture_room = "img_".TABLE_PREFIX."".$room.".".$extSafe;
            $sql_picture = "UPDATE ".TABLE_PREFIX."_room SET picture_room=? WHERE id=?";
            if (grr_sql_command($sql_picture,"ss",[protect_data_sql($picture_room),protect_data_sql($room)]) < 0){
                fatal_error(0, get_vocab('update_room_failed') . grr_sql_error());
                $ok = 'no';
            }
          }
        }
      }
    }
    else if ($doc_file['name'] != '') {
        $msg .= "L\'image n\'a pas pu être enregistrée : le fichier image sélectionné n'est pas valide !\\n";
        $ok = 'no';
    }
    $msg .= get_vocab("message_records");
  }
}
// Si pas de probleme, retour à la page d'accueil apres enregistrement
if ((isset($change_done)) && (!isset($ok)))
{
  if ($msg != '')
  {
    $_SESSION['displ_msg'] = 'yes';
    if (strpos($retour_page, ".php?") == "")
      $param = "?msg=".$msg;
    else
      $param = "&msg=".$msg;
  }
  else
    $param = '';
  Header("Location: ".$retour_page.$param);
  exit();
}
// page formulaire
# print the page header
start_page_w_header("", "", "", $type="with_session");
/*print_r($_POST);
echo "<br/>";
print_r($_GET);*/
affiche_pop_up($msg,"admin");
include "admin_col_gauche2.php";
echo "<div class=\"col-md-9 col-sm-8 col-xs-12\">";
echo "<h2>".get_vocab("match_area").get_vocab('deux_points')." ".$area_name."<br />".$titre_action."</h2>\n";
//if($action == "ajout"){
  // formulaire
  echo '<form enctype="multipart/form-data" action="edit_room.php" method="post">';
  echo "<div>";
  if (isset($action))
  echo "<input type=\"hidden\" name=\"action\" value=\"$action\" />\n";
  if ($Room["id"] != '')
    echo "<input type=\"hidden\" name=\"room\" value=\"".clean_input($Room["id"])."\" />\n";
  if (isset($retour_page))
    echo "<input type=\"hidden\" name=\"retour_page\" value=\"".$retour_page."\" />\n";
  echo "</div>";
  $nom_picture = '';
  if ($Room['picture_room'] != '') $nom_picture = "../images/".clean_input($Room['picture_room']);
  if (Settings::get("use_fckeditor") == 1)
    echo "<script type=\"text/javascript\" src=\"../js/ckeditor/ckeditor.js\"></script>\n";
  echo "<table class='table table-bordered'>\n";
  echo "<tr><td>".get_vocab("name").get_vocab("deux_points")."</td><td>\n";
  // seul l'administrateur peut modifier le nom de la ressource
  if ((authGetUserLevel($user_id,$area_id,"area") >= 4) || (authGetUserLevel($user_id,$room) >= 4))
    echo "<input class=\"form-control\" type=\"text\" name=\"room_name\" maxlength=\"60\" size=\"40\" value=\"".clean_input($Room["room_name"])."\" />\n";
  else {
    echo "<input type=\"hidden\" name=\"room_name\" value=\"".clean_input($Room["room_name"])."\" />\n";
    echo "<b>".clean_input($Room["room_name"])."</b>\n";
  }
  echo "</td></tr>\n";
  // Description
  echo "<tr><td>".get_vocab("description")."</td><td><input class=\"form-control\" type=\"text\" name=\"description\"  maxlength=\"60\" size=\"40\" value=\"".clean_input($Room["description"])."\" /></td></tr>\n";
  // Domaine
  if($nb_area >1){
    echo "<tr><td>".get_vocab('match_area').get_vocab('deux_points')."</td>\n";
    echo "<td><select class=\"form-control\" name=\"area_id\" >\n";
    echo "<option value=\"-1\">".get_vocab('choose_an_area')."</option>\n";
    foreach($Areas as $area)
      {
        echo "<option value=\"".$area['id']."\"";
        if ($area_id == $area['id'])
            echo ' selected ';
        echo '>'.htmlspecialchars($area['area_name'], ENT_QUOTES);
        echo '</option>'."\n";
      }
      echo "</select></td></tr>";
  }
  else
  {   // les creneaux sont bases sur les intitules ou un seul domaine : on ne peut pas changer une ressource de domaine
      if (isset($area_id))
          echo "<input type=\"hidden\" name=\"area_id\" value=\"".$area_id."\" />\n";
  }
  // Ordre d'affichage du domaine
  echo "<tr><td>".get_vocab("order_display").get_vocab("deux_points")."</td>\n";
  echo "<td><input class=\"form-control\" type=\"text\" name=\"room_order\" size=\"1\" value=\"".clean_input($Room["order_display"])."\" /></td>\n";
  echo "</tr>\n";
  // Qui peut voir cette ressource
  echo "<tr><td colspan=\"2\">".get_vocab("qui_peut_voir_ressource")."<br />\n";
  echo "<select class=\"form-control\" name=\"who_can_see\" size=\"1\">\n
  <option value=\"0\" ";
  if ($Room["who_can_see"] == 0)
      echo " selected ";
  echo ">".get_vocab("visu_fiche_description0")."</option>\n<option value=\"1\" ";
  if ($Room["who_can_see"] == 1)
      echo " selected ";
  echo ">".get_vocab("visu_fiche_description1")."</option>\n<option value=\"2\" ";
  if ($Room["who_can_see"] == 2)
      echo " selected ";
  echo ">".get_vocab("visu_fiche_description2")."</option>\n<option value=\"3\" ";
  if ($Room["who_can_see"] == 3)
      echo " selected ";
  echo ">".get_vocab("visu_fiche_description3")."</option>\n<option value=\"4\" ";
  if ($Room["who_can_see"] == 4)
      echo " selected ";
  echo ">".get_vocab("visu_fiche_description4")."</option>\n";
  if (Settings::get("module_multisite") == "Oui")
  {
      echo "<option value=\"5\" ";
      if ($Room["who_can_see"] == 5)
          echo " selected ";
      echo ">".get_vocab("visu_fiche_description5")."</option>\n";
  };
  echo "<option value=\"6\" ";
  if ($Room["who_can_see"] == 6)
      echo " selected ";
  echo ">".get_vocab("visu_fiche_description6")."</option>\n</select></td></tr>\n";
  // Accès restreint
  echo "<tr><td>".get_vocab("access").get_vocab("deux_points")."<br /><em>".get_vocab("who_can_book_explain")."</em></td>\n";
  echo "<td><input type=\"checkbox\" name=\"who_can_book\"";
  if ($Room["who_can_book"] == 0)
    echo " checked ";
  echo " /></td>\n";
  echo "</tr>";
  // Declarer ressource indisponible
  echo "<tr><td>".get_vocab("declarer_ressource_indisponible")."<br /><i>".get_vocab("explain_max_booking")."</i></td><td><input type=\"checkbox\" name=\"statut_room\" ";
  if ($Room['statut_room'] == "0")
    echo " checked ";
  echo "/></td></tr>\n";
  // Afficher la fiche de presentation de la ressource
  echo "<tr><td>".get_vocab("montrer_fiche_presentation_ressource")."</td><td><input type=\"checkbox\" name=\"show_fic_room\" ";
  if ($Room['show_fic_room'] == "y")
    echo " checked ";
  echo "/><a href='javascript:centrerpopup(\"../view_room.php?id_room=$room\",600,480,\"scrollbars=yes,statusbar=no,resizable=yes\")' title=\"".get_vocab("fiche_ressource")."\"><span class=\"glyphicon glyphicon-search\"></span></a></td></tr>\n";
  // Choix de l'image de la ressource
  echo "<tr><td>".get_vocab("choisir_image_ressource")."</td><td><input type=\"file\" name=\"doc_file\" accept='.jpg,.png,.gif' size=\"30\" /></td></tr>\n";
  echo "<tr><td>".get_vocab("supprimer_image_ressource").get_vocab("deux_points");
  if (@file_exists($nom_picture))
  {
    echo "<b>$nom_picture</b></td><td><input type=\"checkbox\" name=\"sup_img\" /></td></tr>";}
  else
    echo "<b>".get_vocab("nobody")."</b></td><td><input type=\"checkbox\" disabled=\"disabled\" name=\"sup_img\" /></td></tr>";
  // affichage description complète
  echo "<tr><td>".get_vocab("Afficher_description_complete_dans_titre_plannings")."</td>\n<td><input type=\"checkbox\" name=\"show_comment\" ";
  if ($Room['show_comment'] == "y")
    echo " checked ";
  echo "/></td></tr>\n";
  // Description complète
  echo "<tr><td colspan=\"2\">".get_vocab("description_complete");
  if (Settings::get("use_fckeditor") != 1)
    echo " ".get_vocab("description_complete2");
  echo get_vocab("deux_points")."<br />";
  if (Settings::get("use_fckeditor") == 1)
  {
    echo "<textarea id=\"editor1\" name=\"comment_room\" rows=\"8\" cols=\"120\">\n".PHP_EOL;
    echo clean_input($Room['comment_room']);
    echo "</textarea>\n";
        ?>
    <script type="text/javascript">
        //<![CDATA[
        CKEDITOR.replace( 'editor1',
        {
            extraPlugins: 'colorbutton,colordialog',
            toolbar :
            [
            ['Source'],
            ['Cut','Copy','Paste','PasteText','PasteFromWord', 'SpellChecker', 'Scayt'],
            ['Undo','Redo','-','Find','Replace','-','SelectAll','RemoveFormat'],
            ['Bold','Italic','Underline','Strike','-','Subscript','Superscript','-','TextColor','BGColor'],
            ['NumberedList','BulletedList','-','Outdent','Indent'],
            ['JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock'],
            ['Link','Unlink','Anchor'],
            ['Image','Table','HorizontalRule','SpecialChar','PageBreak'],
            ]
        });
        //]]>
    </script>
<?php
  }
  else
    echo "<textarea class=\"form-control\" name=\"comment_room\" rows=\"8\" cols=\"120\" >".clean_input($Room['comment_room'])."</textarea>";
  echo "</td></tr></table>\n";
  echo "<h3>".get_vocab("configuration_ressource")."</h3>\n";
// Type d'affichage : duree ou heure/date de fin de reservation
  echo "<table class='table table-bordered'>\n";
  echo "<tr><td>".get_vocab("type_affichage_reservation").get_vocab("deux_points")."</td>\n";
  echo "<td>";
  echo "<label><input type=\"radio\" name=\"type_affichage_reser\" value=\"0\" ";
  if (($Room["type_affichage_reser"]) == 0)
    echo " checked ";
  echo "/>";
  echo get_vocab("affichage_reservation_duree");
  echo "</label><br /><label><input type=\"radio\" name=\"type_affichage_reser\" value=\"1\" ";
  if (($Room["type_affichage_reser"]) == 1)
    echo " checked ";
  echo "/>";
  echo get_vocab("affichage_reservation_date_heure");
  echo "</label></td>\n";
  echo "</tr>\n";

// Capacite
  echo "<tr><td>".get_vocab("capacity").": </td><td><input class=\"form-control\" type=\"text\" name=\"capacity\" size=\"1\" value=\"".clean_input($Room["capacity"])."\" /></td></tr>\n";
// seuls les administrateurs de la ressource peuvent modifier le nombre max de reservation par utilisateur
  if ((authGetUserLevel($user_id,$area_id,"area") >= 4) || (authGetUserLevel($user_id,$room) >= 4))
  {
    echo "<tr><td>".get_vocab("max_booking")." ";
    echo "</td><td><input class=\"form-control\" type=\"text\" name=\"max_booking\" size=\"1\" value=\"".clean_input($Room["max_booking"])."\" /></td></tr>";
  }
  else 
  {
    if ($Room["max_booking"] != "-1")
      echo "<tr><td>".get_vocab("msg_max_booking").get_vocab("deux_points")."</td><td><b>".clean_input($Room["max_booking"])."</b></td></tr>";
    echo "<input type=\"hidden\" name=\"max_booking\" value=\"".clean_input($Room["max_booking"])."\" />";
  }
// quota de réservation sur un intervalle de temps
// seuls les administrateurs de la ressource peuvent modifier le quota de réservation sur un intervalle de temps
  if ((authGetUserLevel($user_id,$area_id,"area") >= 4) || (authGetUserLevel($user_id,$room) >= 4))
  {
    echo "<tr><td>".get_vocab("max_booking_on_range")." ";
    echo "</td><td><input class=\"form-control\" type=\"text\" name=\"max_booking_on_range\" size=\"1\" value=\"".clean_input($Room["max_booking_on_range"])."\" /></td></tr>";
    echo "<tr><td>".get_vocab("booking_range")." ";
    echo "</td><td><input class=\"form-control\" type=\"text\" name=\"booking_range\" size=\"1\" value=\"".clean_input($Room["booking_range"])."\" /></td></tr>";
  }
  else 
  {
    if ($Room["booking_range"] != "-1")
      echo "<tr><td>".get_vocab("msg_booking_range").get_vocab("deux_points")."</td><td><b>".clean_input($Room["max_booking_on_range"]).get_vocab('of').clean_input($Room["booking_range"]).get_vocab('days')."</b></td></tr>";
    echo "<input type=\"hidden\" name=\"max_booking_on_range\" value=\"".clean_input($Room["max_booking_on_range"])."\" />";
    echo "<input type=\"hidden\" name=\"booking_range\" value=\"".clean_input($Room["booking_range"])."\" />";
  }
// L'utilisateur ne peut pas reserver au-delà d'un certain temps
  echo "<tr><td>".get_vocab("delais_max_resa_room")." </td><td><input class=\"form-control\" type=\"text\" name=\"delais_max_resa_room\" size=\"1\" value=\"".clean_input($Room["delais_max_resa_room"])."\" /></td></tr>\n";
// L'utilisateur ne peut pas reserver en-dessous d'un certain temps
  echo "<tr><td>".get_vocab("delais_min_resa_room");
  echo "</td><td><input class=\"form-control\" type=\"text\" name=\"delais_min_resa_room\" size=\"5\" value=\"".clean_input($Room["delais_min_resa_room"])."\" /></td></tr>\n";
// L'utilisateur peut poser poser une option de reservation
  echo "<tr><td>".get_vocab("msg_option_de_reservation")."</td><td><input class=\"form-control\" type=\"text\" name=\"delais_option_reservation\" size=\"5\" value=\"".clean_input($Room["delais_option_reservation"])."\" /></td></tr>\n";
// Les demandes de reservations sont moderées
  echo "<tr><td>".get_vocab("msg_moderation_reservation").get_vocab("deux_points");
  echo "</td>" ."<td><input type='checkbox' name='moderate' ";
  if ($Room['moderate'])
    echo 'checked ';
  echo " /></td></tr>\n";
// L'utilisateur peut reserver dans le passe
  echo "<tr><td>".get_vocab("allow_action_in_past")."<br /><i>".get_vocab("allow_action_in_past_explain")."</i></td><td><input type=\"checkbox\" name=\"allow_action_in_past\" value=\"y\" ";
  if ($Room["allow_action_in_past"] == 'y')
    echo " checked ";
  echo " /></td></tr>\n";
// L'utilisateur ne peut pas modifier ou supprimer ses propres reservations
  echo "<tr><td>".get_vocab("dont_allow_modify")."</td><td><input type=\"checkbox\" name=\"dont_allow_modify\" value=\"y\" ";
  if ($Room["dont_allow_modify"] == 'y')
    echo " checked ";
  echo " /></td></tr>\n";
// Quels utilisateurs ont le droit de reserver cette ressource au nom d'un autre utilisateur ?
  echo "<tr><td>".get_vocab("qui_peut_reserver_pour_autre_utilisateur")."</td><td><select class=\"form-control\" name=\"qui_peut_reserver_pour\" size=\"1\">\n<option value=\"5\" ";
  if ($Room["qui_peut_reserver_pour"]==6)
    echo " selected ";
  echo ">".get_vocab("personne")."</option>\n
  <option value=\"4\" ";
  if ($Room["qui_peut_reserver_pour"]==4)
    echo " selected ";
  echo ">".get_vocab("les_administrateurs_restreints")."</option>\n
  <option value=\"3\" ";
  if ($Room["qui_peut_reserver_pour"]==3)
    echo " selected ";
  echo ">".get_vocab("les_gestionnaires_de_la_ressource")."</option>\n
  <option value=\"2\" ";
  if ($Room["qui_peut_reserver_pour"]==2)
    echo " selected ";
  echo ">".get_vocab("tous_les_utilisateurs")."</option>\n
</select></td></tr>\n";
// Activer la fonctionnalite "ressource empruntee/restituee"
echo "<tr><td>".get_vocab("activer_fonctionalite_ressource_empruntee_restituee")."</td><td><input type=\"checkbox\" name=\"active_ressource_empruntee\" ";
if ($Room['active_ressource_empruntee'] == "y")
  echo " checked ";
echo "/></td></tr>\n";
// Activer la gestion des clés
  echo "<tr><td>".get_vocab("activer_fonctionalite_gestion_cle")."</td><td><input type=\"checkbox\" name=\"active_cle\" ";
  if ($Room['active_cle'] == "y")
    echo " checked ";
  echo "/></td></tr>\n";
// Activer la fonctionnalite "participant"
  echo "<tr><td>".get_vocab("activer_fonctionnalite_participant")."</td><td>";
  echo '<select class="form-control" name="active_participant">';
  echo '<option value="0" ';
  if ($Room['active_participant'] == 0)
    echo 'selected';
  echo '>'.get_vocab('personne').'</option>';
  echo '<option value="1" ';
  if ($Room['active_participant'] == 1)
    echo 'selected';
  echo '>'.get_vocab('visu_fiche_description1').'</option>';
  echo '<option value="2" ';
  if ($Room['active_participant'] == 2)
    echo 'selected';
  echo '>'.get_vocab('visu_fiche_description2').'</option>';
  echo '</select>';
  echo "</td></tr>\n";
  echo "</table>\n";
  Hook::Appel("hookEditRoom1");
  echo "<div style=\"text-align:center;\"><br />\n";
  echo "<input class=\"btn btn-primary\" type=\"submit\" name=\"change_room\"  value=\"".get_vocab("save")."\" />\n";
  echo "<input class=\"btn btn-primary\" type=\"submit\" name=\"change_done\" value=\"".get_vocab("back")."\" />\n";
  echo "<input class=\"btn btn-primary\" type=\"submit\" name=\"change_room_and_back\" value=\"".get_vocab("save_and_back")."\" />";
  if (@file_exists($nom_picture) && $nom_picture)
    echo "<br /><br /><b>".get_vocab("Image_de_la_ressource").get_vocab("deux_points")."</b><br /><img src=\"".$nom_picture."\" alt=\"logo\" />";
  else
    echo "<br /><br /><b>".get_vocab("Pas_image_disponible")."</b>";
  echo "</div>";
  echo "</form>";
  echo "</div>";
// }
echo "</div>";
end_page();
exit;
?>