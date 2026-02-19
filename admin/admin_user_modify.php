<?php
/**
 * admin_user_modify.php
 * Interface de modification/création d'un utilisateur de l'application GRR
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2026-01-28 11:54$
 * @author    Laurent Delineau & JeromeB & Yan Naessens
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
$grr_script_name = "admin_user_modify.php";

include "../include/admin.inc.php";

$back = (isset($_SERVER['HTTP_REFERER']))? htmlspecialchars_decode($_SERVER['HTTP_REFERER'], ENT_QUOTES) : "./admin_accueil.php" ;

$user_id = getUserName();
if ((authGetUserLevel($user_id, -1) < 6) && (authGetUserLevel($user_id, -1, 'user') !=  1))
{
	showAccessDenied($back);
	exit();
}
unset($user_login);
$user_login = getFormVar("user_login","string");
// un gestionnaire d'utilisateurs ne peut pas modifier un administrateur général ou un gestionnaire d'utilisateurs
if (isset($user_login) && (authGetUserLevel($user_id,-1,'user') ==  1))
{
	$test_statut = grr_sql_query1("SELECT statut FROM ".TABLE_PREFIX."_utilisateurs WHERE login=?","s",[$user_login]);
	if (($test_statut == "administrateur") or ($test_statut == "gestionnaire_utilisateur"))
	{
		showAccessDenied($back);
		exit();
	}
}
$msg = '';

$test_login = ($user_login != NULL)? preg_replace("/([A-Za-z0-9_@. -])/","",$user_login):$user_login;
if ($test_login != ""){
    $user_login = "";
    $msg = 'login incorrect';} // le login passé en paramètre est non valide, on le vide et on modifie le message
$valid = getFormVar("valid");
$msg = '';
$utilisateur = array();
$utilisateur['nom'] = '';
$utilisateur['prenom'] = '';
$utilisateur['email'] = '';
$utilisateur['statut'] = '';
$utilisateur['source'] = 'local';
$utilisateur['etat'] = '';
$display = getFormVar("display","string","");
$retry = '';
if ($valid == "yes")
{
    // Restriction dans le cas d'une démo
    VerifyModeDemo();
    $reg_nom = getFormVar("reg_nom","string");
    $reg_prenom = getFormVar("reg_prenom","string");
    $new_login = getFormVar("new_login","string");
    $reg_password = getFormVar("reg_password");
    if (isset($reg_password)) 
        $reg_password = unslashes($reg_password);
    $reg_password2 = getFormVar("reg_password2","string");
    if (isset($reg_password2)) 
        $reg_password = unslashes($reg_password2);
    $reg_changepwd = getFormVar("reg_changepwd","string",0);
    $reg_statut = getFormVar("reg_statut","string");
    $reg_email = getFormVar("reg_email","string");
    $reg_etat = getFormVar("reg_etat","string");
    $reg_source = getFormVar("reg_source","string");
    $reg_type_authentification = getFormVar("type_authentification","string","locale");
    if ($reg_type_authentification != "locale")
        $reg_password = "";
    if (($reg_nom == '') || ($reg_prenom == ''))
    {
        $msg = get_vocab("please_enter_name");
        $retry = 'yes';
    }
    else 
    {   // actions si un nouvel utilisateur a été défini
        $test_login = ($new_login != NULL)? preg_replace("/([A-Za-z0-9_@. -])/","",$new_login):$new_login;
        if ((isset($new_login)) && ($new_login != '') && ($test_login == ""))
        {
            // un gestionnaire d'utilisateurs ne peut pas créer un administrateur général ou un gestionnaire d'utilisateurs
            $test_statut = TRUE;
            if (authGetUserLevel($user_id,-1) < 6)
            {
                if (($reg_statut == "administrateur") || ($reg_statut == "gestionnaire_utilisateur"))
                    $test_statut = FALSE;
            }
            $new_login = strtoupper($new_login);
            if ($reg_password !='')
                $reg_password_c = password_hash($reg_password,PASSWORD_DEFAULT);
            else
            {
                if ($reg_type_authentification != "locale")
                    $reg_password_c = '';
                else
                {
                    $msg = get_vocab("passwd_error");
                    $retry = 'yes';
                }
            }
            if (!($test_statut))
            {
                $msg = get_vocab("erreur_choix_statut");
                $retry = 'yes';
            }
            else if ((($reg_password != $reg_password2) || (strlen($reg_password) < $pass_leng)) && ($reg_type_authentification == "locale"))
            {
                $msg = get_vocab("passwd_error");
                $retry = 'yes';
            }
            else
            {
                $sql = "SELECT COUNT(*) FROM ".TABLE_PREFIX."_utilisateurs WHERE login =?";
                $nombreligne = grr_sql_query1($sql,"s",[protect_data_sql($new_login)]);
                if ($nombreligne != 0)
                {
                    $msg = get_vocab("error_exist_login");
                    $retry = 'yes';
                }
                else
                {
                    $sql = "INSERT INTO ".TABLE_PREFIX."_utilisateurs SET nom=?,prenom=?,login=?,password=?,changepwd=?,statut=?,email=?,etat=?,
                    default_site = '0',default_area = '0',default_room = '0',default_style = '',default_list_type = '',default_language = 'fr',";
                    $types = "ssssisss";
                    $params = [protect_data_sql($reg_nom),protect_data_sql($reg_prenom),protect_data_sql($new_login),protect_data_sql($reg_password_c),$reg_changepwd,protect_data_sql($reg_statut),protect_data_sql($reg_email),protect_data_sql($reg_etat)];
                    if ($reg_type_authentification=="locale")
                        $sql .= "source='local'";
                    else
                        $sql .= "source='ext'";
                    if (grr_sql_command($sql,$types,$params) < 0)
                    {
                        fatal_error(0, get_vocab("msg_login_created_error") . $_SESSION["msg_a_afficher"]);
                    }
                    else
                    {
                        $msg = get_vocab("msg_login_created");
                    }
                    $user_login = $new_login;
                }
            }
        }
        // actions s'il s'agit d'une modification
        else if ((isset($user_login)) && ($user_login != ''))
        {
            // un gestionnaire d'utilisateurs ne peut pas modifier un administrateur général ou un gestionnaire d'utilisateurs
            $test_statut = TRUE;
            if (authGetUserLevel($user_id,-1) < 6)
            {
                $old_statut = grr_sql_query1("SELECT statut FROM ".TABLE_PREFIX."_utilisateurs WHERE login=?","s",[protect_data_sql($user_login)]);
                if (((($old_statut == "administrateur") || ($old_statut == "gestionnaire_utilisateur")) && ($old_statut != $reg_statut))
                    || ((($old_statut == "utilisateur") || ($old_statut == "visiteur")) && (($reg_statut == "administrateur") || ($reg_statut == "gestionnaire_utilisateur"))))
                    $test_statut = FALSE;
            }
            if (!($test_statut))
            {
                $msg = get_vocab("erreur_choix_statut");
                $retry = 'yes';
            }
            else if ($reg_type_authentification == "locale")
            {
                // On demande un changement de la source ext->local
                if (($reg_password == '') && ($reg_password2 == ''))
                {
                    $old_mdp = grr_sql_query1("SELECT password FROM ".TABLE_PREFIX."_utilisateurs WHERE login=?","s",[protect_data_sql($user_login)]);
                    if (($old_mdp == '') || ($old_mdp == -1))
                    {
                        $msg = get_vocab("passwd_error");
                        $retry = 'yes';
                    }
                    else
                        $reg_password_c = '';
                }
                else
                {
                    $reg_password_c = password_hash($reg_password,PASSWORD_DEFAULT);
                    if (($reg_password != $reg_password2) || (strlen($reg_password) < $pass_leng))
                    {
                        $msg = get_vocab("passwd_error");
                        $retry = 'yes';
                    }
                }
            }
            if ($retry != 'yes')
            {
                $sql = "UPDATE ".TABLE_PREFIX."_utilisateurs SET nom='".protect_data_sql($reg_nom)."',
                prenom='".protect_data_sql($reg_prenom)."',
                statut='".protect_data_sql($reg_statut)."',
                changepwd='".protect_data_sql($reg_changepwd)."',
                email='".protect_data_sql($reg_email)."',";
                if ($reg_type_authentification=="locale")
                {
                    $sql .= "source='local',";
                    if ($reg_password_c!='')
                        $sql .= "password='".protect_data_sql($reg_password_c)."',";
                }
                else
                    $sql .= "source='ext',password='',";
                $sql .= "etat='".protect_data_sql($reg_etat)."'
                WHERE login='".protect_data_sql($user_login)."'";
                if (grr_sql_command($sql) < 0)
                {
                    fatal_error(0, get_vocab("message_records_error") . grr_sql_error());
                }
                else
                {
                    $msg = get_vocab("message_records");
                }
            // Cas où on a déclaré un utilisateur inactif, on le supprime dans les tables ".TABLE_PREFIX."_j_user_area,  ".TABLE_PREFIX."_j_mailuser_room
                if ($reg_etat != 'actif')
                {
                    $sql = "DELETE FROM ".TABLE_PREFIX."_j_user_area WHERE login='$user_login'";
                    if (grr_sql_command($sql) < 0)
                        fatal_error(0, get_vocab('message_records_error') . grr_sql_error());
                    $sql = "DELETE FROM ".TABLE_PREFIX."_j_mailuser_room WHERE login='$user_login'";
                    if (grr_sql_command($sql) < 0)
                        fatal_error(0, get_vocab('message_records_error') . grr_sql_error());
                    $sql = "DELETE FROM ".TABLE_PREFIX."_j_useradmin_area WHERE login='$user_login'";
                    if (grr_sql_command($sql) < 0)
                        fatal_error(0, get_vocab('message_records_error') . grr_sql_error());
                    $sql = "DELETE FROM ".TABLE_PREFIX."_j_useradmin_site WHERE login='$user_login'";
                    if (grr_sql_command($sql) < 0)
                        fatal_error(0, get_vocab('message_records_error') . grr_sql_error());
                }
            // Cas où on a déclaré un utilisateur visiteur, on le supprime dans les tables ".TABLE_PREFIX."_j_user_area, ".TABLE_PREFIX."_j_mailuser_room et ".TABLE_PREFIX."_j_user_room
                if ($reg_statut == 'visiteur')
                {
                    $sql = "DELETE FROM ".TABLE_PREFIX."_j_user_room WHERE login='$user_login'";
                    if (grr_sql_command($sql) < 0)
                        fatal_error(0, get_vocab('message_records_error') . grr_sql_error());
                    $sql = "DELETE FROM ".TABLE_PREFIX."_j_mailuser_room WHERE login='$user_login'";
                    if (grr_sql_command($sql) < 0)
                        fatal_error(0, get_vocab('message_records_error') . grr_sql_error());
                    $sql = "DELETE FROM ".TABLE_PREFIX."_j_user_area WHERE login='$user_login'";
                    if (grr_sql_command($sql) < 0)
                        fatal_error(0, get_vocab('message_records_error') . grr_sql_error());
                    $sql = "DELETE FROM ".TABLE_PREFIX."_j_useradmin_area WHERE login='$user_login'";
                    if (grr_sql_command($sql) < 0)
                        fatal_error(0, get_vocab('message_records_error') . grr_sql_error());
                    $sql = "DELETE FROM ".TABLE_PREFIX."_j_useradmin_site WHERE login='$user_login'";
                    if (grr_sql_command($sql) < 0)
                        fatal_error(0, get_vocab('message_records_error') . grr_sql_error());
                }
                if ($reg_statut == 'administrateur')
                {
                    $sql = "DELETE FROM ".TABLE_PREFIX."_j_user_room WHERE login='$user_login'";
                    if (grr_sql_command($sql) < 0)
                        fatal_error(0, get_vocab('message_records_error') . grr_sql_error());
                    $sql = "DELETE FROM ".TABLE_PREFIX."_j_user_area WHERE login='$user_login'";
                    if (grr_sql_command($sql) < 0)
                        fatal_error(0, get_vocab('message_records_error') . grr_sql_error());
                    $sql = "DELETE FROM ".TABLE_PREFIX."_j_useradmin_area WHERE login='$user_login'";
                    if (grr_sql_command($sql) < 0)
                        fatal_error(0, get_vocab('message_records_error') . grr_sql_error());
                    $sql = "DELETE FROM ".TABLE_PREFIX."_j_useradmin_site WHERE login='$user_login'";
                    if (grr_sql_command($sql) < 0)
                        fatal_error(0, get_vocab('message_records_error') . grr_sql_error());
                }
            }
        }
        else
        {
            $msg = get_vocab("only_letters_and_numbers");
            $retry = 'yes';
        }
    }
    if ($retry == 'yes')
    {
        $utilisateur['nom'] = $reg_nom;
        $utilisateur['prenom'] = $reg_prenom;
        $utilisateur['statut'] = $reg_statut;
        $utilisateur['email'] = $reg_email;
        $utilisateur['etat'] = $reg_etat;
    }
}
// On appelle les informations de l'utilisateur pour les afficher :
if (isset($user_login) && ($user_login != ''))
{
    $res = grr_sql_query("SELECT nom, prenom, statut, etat, email, source, changepwd FROM ".TABLE_PREFIX."_utilisateurs WHERE login=?","s",[protect_data_sql($user_login)]);
    if (!$res)
        fatal_error(0, get_vocab('message_records_error'));
    $utilisateur = grr_sql_row_keyed($res, 0);
    grr_sql_free($res);
    $flag_is_local = $utilisateur['source']=="local";
}
// Privilèges
$html_privileges = '';

$privileges = array('site'=>array(),'area'=>array());
if ((isset($user_login)) && ($user_login != ''))
{
  if($utilisateur["statut"] != "administrateur"){
    if (Settings::get("module_multisite") == "Oui")
    {
        $req_site = "SELECT id, sitename FROM ".TABLE_PREFIX."_site s JOIN ".TABLE_PREFIX."_j_useradmin_site j ON s.id = j.id_site WHERE j.login = ? ORDER BY sitename";
        $res_site = grr_sql_query($req_site,"s",[protect_data_sql($user_login)]);
        if ($res_site){
            foreach($res_site as $row){
              $privileges['site'][] = $row;
            }
        }
        else 
          fatal_error(0,grr_sql_error());
    }
    // les autres privilèges sont regroupés par domaine
    $sql = "SELECT id, area_name, access FROM ".TABLE_PREFIX."_area ORDER BY order_display";
    $res_area = grr_sql_query($sql);
    if($res_area){
      foreach($res_area as $area){
        // administrateur du domaine ?
        $req_adm = "SELECT COUNT(login) FROM ".TABLE_PREFIX."_j_useradmin_area WHERE login = ? AND id_area = ?";
        $res = grr_sql_query1($req_adm,"si",[$user_login,$area["id"]]);
        if($res == -1)
          fatal_error(0,grr_sql_error());
        elseif($res > 0)
          $privileges['area'][$area["id"]]["adm"] = TRUE;
        // si le domaine est restreint, accès au domaine ?
        if($area['access'] == 'r'){
          $restreint = grr_sql_query1("SELECT COUNT(login) FROM ".TABLE_PREFIX."_j_user_area j WHERE j.login = ? AND j.id_area= ?","si",[$user_login,$area["id"]]);
          if($restreint == -1)
            fatal_error(0,grr_sql_error());
          elseif($restreint > 0)
            $privileges['area'][$area["id"]]["acc"] = TRUE;
        }
        // les ressources du domaine
        $rooms = array();
        $sql = "SELECT id, room_name FROM ".TABLE_PREFIX."_room WHERE area_id = ?";
        $res_room = grr_sql_query($sql,"i",[$area["id"]]);
        if($res_room){
          foreach($res_room as $room){
            $rooms[] = $room;
            // gestionnaire de cette ressource ?
            $gere = grr_sql_query1("SELECT count(login) FROM ".TABLE_PREFIX."_j_user_room WHERE login = ? AND id_room = ?","si",[$user_login,$room["id"]]);
            if($gere == -1)
              fatal_error(0,grr_sql_error());
            elseif($gere >0)
              $privileges['area'][$area["id"]]["ress"][] = $room['room_name'];
            // reçoit un mail ?
            $mail = grr_sql_query1("SELECT count(login) FROM ".TABLE_PREFIX."_j_mailuser_room WHERE login = ? AND id_room = ?","si",[$user_login,$room["id"]]);
            if($mail == -1)
              fatal_error(0,grr_sql_error());
            elseif($mail >0)
              $privileges['area'][$area["id"]]["mail"][] = $room['room_name'];
            // si cette ressource est restreinte, peut-il réserver ?
            $rest = grr_sql_query1("SELECT count(login) FROM ".TABLE_PREFIX."_j_userbook_room WHERE login = ? AND id_room = ?","si",[$user_login,$room["id"]]);
            if($rest == -1)
              fatal_error(0,grr_sql_error());
            elseif($rest >0)
              $privileges['area'][$area["id"]]["rest"][] = $room['room_name'];
          }
          $nb_room = count($rooms);
          $nb_gere = isset($privileges['area'][$area["id"]]["ress"])? count($privileges['area'][$area["id"]]["ress"]):0;
          if(($nb_gere == $nb_room)&&($nb_room >0))
            $privileges['area'][$area["id"]]["ress"] = array('all');
        }
        else
          fatal_error(0,grr_sql_error());
        if((isset($privileges["area"][$area["id"]])) && (count($privileges["area"][$area["id"]])>0))
          $privileges["area"][$area["id"]]['name'] = $area["area_name"];
      }
      grr_sql_free($res);
    }
    else 
      fatal_error(0,grr_sql_error());
  }
}

$a_privileges = (count($privileges["site"])+count($privileges["area"]))>0;

// code HTML
// Utilisation de la bibliothèque prototype dans ce script
$use_prototype = 'y';
// début de page avec entête
start_page_w_header("", "", "", $type="with_session");
// colonne gauche
include "admin_col_gauche2.php";
// Affichage d'un pop-up
affiche_pop_up($msg,"admin");
// colonne de droite
echo "<div class='col-md-9 col-sm-8 col-xs-12'>";
if (isset($user_login) && ($user_login != ''))
{
    echo "<h2>".get_vocab('admin_user_modify_modify.php')."</h2>";
}
else
{
    echo "<h2>".get_vocab('admin_user_modify_create.php')."</h2>";
}
echo '<p>';
echo "<a href=\"admin_user.php?display=$display\" type='button' class='btn btn-primary'>".get_vocab("back").'</a>';
if (isset($user_login) && ($user_login != ''))
{
    echo "<a href=\"admin_user_modify.php?display=$display\" type='button' class='btn btn-warning'>".get_vocab("display_add_user")."</a>";
}
echo '<br /><br />';
echo '<div class="avertissement"><b>'.get_vocab("required").'</b></div>';
echo '</p>';

echo '<form class="form-horizontal" action="admin_user_modify.php" method="POST">';
echo '<input type="hidden" name="display" value="'.$display.'" />';
echo '<div>';
    if ((Settings::get("sso_statut") != "") || (Settings::get("ldap_statut") != '') || (Settings::get("imap_statut") != ''))
    {
      echo "<div class=\"form-group\">";
        echo "<label class=\"control-label col-md-2 col-sm-3 col-xs-4\" for=\"select_auth_mode\">".get_vocab("authentification").get_vocab("deux_points")."</label>";
        echo "<div class=\"col col-md-4 col-sm-6 col-xs-8\">";
        echo "<select class=\"form-control\" id=\"select_auth_mode\" name='type_authentification' onchange=\"display_password_fields(this.id);\">\n";
        echo "<option value='locale'";
        if ($utilisateur['source'] == 'local')
            echo " selected=\"selected\" ";
        echo ">".get_vocab("authentification_base_locale")."</option>\n";
        echo "<option value='externe'";
        if ($utilisateur['source'] == 'ext')
            echo " selected=\"selected\" ";
        echo ">".get_vocab("authentification_base_externe")."</option>\n";
        echo "</select></div></div>".PHP_EOL;
    }
    echo "<div class='form-group'>";
    echo "<label class='control-label col-md-2 col-sm-3 col-xs-4' for='login'>";
    echo get_vocab("login")." *".get_vocab("deux_points")."</label>";
    echo "<div class='col col-md-4 col-sm-6 col-xs-8'>";
    if (isset($user_login) && ($user_login!='')){
        echo "<input class='form-control' type=\"text\" name=\"reg_login\" id='login' value=\"$user_login\" disabled />";
    }
    else{
        echo "<input class='form-control' type=\"text\" name=\"new_login\" id='login' size=\"40\" required />";
    }
    echo "</div></div>";
    echo "<div class='form-group'>";
    echo "<label class='control-label col-md-2 col-sm-3 col-xs-4' for='nom'>";
    echo get_vocab("last_name")." *".get_vocab("deux_points")."</label>";
    echo "<div class='col col-md-4 col-sm-6 col-xs-8'>";
    echo "<input class='form-control' type=\"text\" name=\"reg_nom\" id='nom' size=\"40\" value='";
    if ($utilisateur['nom'])
        echo htmlspecialchars($utilisateur['nom']);
    echo "' required />";
    echo "</div></div>";
    echo "<div class='form-group'>";
    echo "<label class='control-label col-md-2 col-sm-3 col-xs-4' for='prenom'>";
    echo get_vocab("first_name")." *".get_vocab("deux_points")."</label>";
    echo "<div class='col col-md-4 col-sm-6 col-xs-8'>";
    echo "<input class='form-control' type=\"text\" name=\"reg_prenom\" id='prenom' size=\"40\" value='";
    if ($utilisateur['prenom'])
        echo htmlspecialchars($utilisateur['prenom']);
    echo "' required />";
    echo "</div></div>";
    echo "<div class='form-group'>";
    echo "<label class='control-label col-md-2 col-sm-3 col-xs-4' for='mail'>";
    echo get_vocab("mail_user").get_vocab("deux_points")."</label>";
    echo "<div class='col col-md-4 col-sm-6 col-xs-8'>";
    echo "<input class='form-control' type=\"email\" name=\"reg_email\" id='mail' size=\"40\" value='";
    if ($utilisateur['email'])
      echo htmlspecialchars($utilisateur['email']);
    echo "' autocomplete='email' />";
    echo "</div></div>";
    echo "<div class='form-group'>";
    echo "<label class='control-label col-md-2 col-sm-3 col-xs-4' for='statut'>";
    echo get_vocab("statut").get_vocab("deux_points")."</label>";
    echo "<div class='col col-md-4 col-sm-6 col-xs-8'>";
    echo "<select class='form-control' name=\"reg_statut\" id='statut' size=\"1\">\n";
    echo "<option value=\"visiteur\" ";
    if ($utilisateur['statut'] == "visiteur"){
        echo "selected ";
    }
    echo ">".get_vocab("statut_visitor")."</option>\n";
    echo "<option value=\"utilisateur\" ";
    if ($utilisateur['statut'] == "utilisateur")
    {
        echo "selected ";
    }
    echo ">".get_vocab("statut_user")."</option>\n";
// un gestionnaire d'utilisateurs ne peut pas créer un administrateur général ou un gestionnaire d'utilisateurs
    if (authGetUserLevel($user_id,-1) >= 6)
    {
        echo "<option value=\"gestionnaire_utilisateur\" ";
        if ($utilisateur['statut'] == "gestionnaire_utilisateur")
        {
            echo "selected ";
        }
        echo ">".get_vocab("statut_user_administrator")."</option>\n";
        echo "<option value=\"administrateur\" ";
        if ($utilisateur['statut'] == "administrateur")
        {
            echo "selected ";
        }
        echo ">".get_vocab("statut_administrator")."</option>\n";
    }
    echo "</select>\n";
    echo "</div></div>";
    echo "<div class='form-group'>";
    echo "<label class='control-label col-md-2 col-sm-3 col-xs-4' for='etat'>";
    echo get_vocab("activ_no_activ").get_vocab("deux_points")."</label>";
    echo "<div class='col col-md-4 col-sm-6 col-xs-8'>";
    echo "<select class='form-control' name=\"reg_etat\" id='etat' size=\"1\" ";
    if(($user_login != '')&&(strtolower($user_id) == strtolower($user_login)))
      echo "disabled ";
    echo ">";
    echo "<option value=\"actif\" ";
    if ($utilisateur['etat'] == "actif")
      echo "selected ";
    echo ">".get_vocab("activ_user")."</option>\n";
    echo "<option value=\"inactif\" ";
    if ($utilisateur['etat'] == "inactif")
      echo "selected ";
    echo ">".get_vocab("no_activ_user")."</option>\n";
    echo "</select>";
    echo "</div></div>";    
    echo "<div id='password_fields' >";
    if ((isset($user_login)) && ($user_login!='') && ($flag_is_local=="y"))
        echo "<b>".get_vocab("champ_vide_mot_de_passe_inchange")."</b>";
    echo "<br />".get_vocab("pwd_too_short")." *".get_vocab("deux_points")."<input type=\"password\" name=\"reg_password\" size=\"20\" autocomplete='off' />\n";
    echo "<br />".get_vocab("confirm_pwd")." *".get_vocab("deux_points")."<input type=\"password\" name=\"reg_password2\" size=\"20\" autocomplete='off' />\n";
    echo '<br /><label for="reg_changepwd">'.get_vocab("user_change_pwd_connexion").'</label>
                                <input type="checkbox" id="reg_changepwd" name="reg_changepwd" value="1"';
                                if ($_SESSION['changepwd'] == 1) echo 'checked = "checked"';
                            echo '/>
          </div>';
    echo "<br />";
    echo "<input type=\"hidden\" name=\"valid\" value=\"yes\" />\n";
    if (isset($user_login))
        echo "<input type=\"hidden\" name=\"user_login\" value=\"".$user_login."\" />\n";
    echo "<br /><div class=\"center\"><input type=\"submit\" value=\"".get_vocab("save")."\" /></div>\n";
    echo "</div></form>\n";
    if ($utilisateur['source']!="local")
    {
        echo "<script  type='text/javascript'> $('#password_fields').hide(); </script>";
    }
    // affichage des privilèges
    echo "<h2>".get_vocab('liste_privileges').$utilisateur['prenom']." ".$utilisateur['nom']." :</h2>";
    if($a_privileges){
      if(count($privileges["site"])>0)
        foreach($privileges["site"] as $row){
          echo "<h3>".get_vocab("site").get_vocab("deux_points").$row['sitename']."</h3>";
          echo "<ul><li><b>".get_vocab("administrateur du site")."</b></li></ul>";
        }
      if(count($privileges["area"])>0)
        foreach($privileges["area"] as $row){
          echo "<h3>".get_vocab("match_area").get_vocab("deux_points").$row['name']."</h3>";
          echo "<ul>";
          if(isset($row["adm"]) && $row['adm'])
            echo "<li>".get_vocab("administrateur du domaine")."</li>";
          if(isset($row["acc"]) && $row["acc"])
            echo "<li>".get_vocab("a_acces_au_domaine")."</li>";
          if(isset($row["ress"])){
            echo "<li>".get_vocab("gestionnaire des resources suivantes")."</li>";
            echo "<ul>";
            foreach($row["ress"] as $ressource){
              echo "<li>".$ressource."</li>";
            }
            echo "</ul>";
          }
          if(isset($row["mail"])){
            echo "<li>".get_vocab("est prevenu par mail")."</li>";
            echo "<ul>";
            foreach($row["mail"] as $ressource){
              echo "<li>".$ressource."</li>";
            }
            echo "</ul>";
          }
          if(isset($row["rest"])){
            echo "<li>".get_vocab('user_can_book')."</li>";
            echo "<ul>";
            foreach($row["rest"] as $ressource){
              echo "<li>".$ressource."</li>";
            }
            echo "</li>";
          }
          echo "</ul>";
        }
    }
    if(!$a_privileges)
    {
        if ($utilisateur['statut'] == 'administrateur')
          echo "<div>".get_vocab("administrateur general").".</div>";
        else
          echo "<div>".get_vocab("pas de privileges").".</div>";
    }
echo "</div></section></body>";
?>
<script type='text/javascript'>
    function display_password_fields(id){
        if ($('#'+id).val()=='locale')
        {
            $('#password_fields').show();
        }
        else
        {
            $('#password_fields').hide();
        }
    }
</script>
</html>