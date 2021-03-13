<?php
/**
 * admin_user_modify.php
 * Interface de modification/création d'un utilisateur de l'application GRR
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2021-03-13 11:35$
 * @author    Laurent Delineau & JeromeB & Yan Naessens
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
$grr_script_name = "admin_user_modify.php";

include "../include/admin.inc.php";

$back = (isset($_SERVER['HTTP_REFERER']))? htmlspecialchars_decode($_SERVER['HTTP_REFERER'], ENT_QUOTES) : "./admin_accueil.php" ;
if ((authGetUserLevel(getUserName(), -1) < 6) && (authGetUserLevel(getUserName(), -1, 'user') !=  1))
{
	showAccessDenied($back);
	exit();
}
// un gestionnaire d'utilisateurs ne peut pas modifier un administrateur général ou un gestionnaire d'utilisateurs
if (isset($_GET["user_login"]) && (authGetUserLevel(getUserName(),-1,'user') ==  1))
{
	$test_statut = grr_sql_query1("SELECT statut FROM ".TABLE_PREFIX."_utilisateurs WHERE login='".$_GET["user_login"]."'");
	if (($test_statut == "administrateur") or ($test_statut == "gestionnaire_utilisateur"))
	{
		showAccessDenied($back);
		exit();
	}
}
$msg = '';
unset($user_login);
$user_login = isset($_GET["user_login"]) ? $_GET["user_login"] : NULL;
$test_login = preg_replace("/([A-Za-z0-9_@. -])/","",$user_login);
if ($test_login != ""){
    $user_login = "";
    $msg = 'login incorrect';} // le login passé en paramètre est non valide, on le vide et on modifie le message
$valid = isset($_GET["valid"]) ? $_GET["valid"] : NULL;
$msg = '';
$utilisateur = array();
$utilisateur['nom'] = '';
$utilisateur['prenom'] = '';
$utilisateur['email'] = '';
$utilisateur['statut'] = '';
$utilisateur['source'] = 'local';
$utilisateur['etat'] = '';
$display = "";
$retry = '';
if ($valid == "yes")
{
    // Restriction dans le cas d'une démo
    VerifyModeDemo();
    $reg_nom = isset($_GET["reg_nom"]) ? $_GET["reg_nom"] : NULL;
    $reg_prenom = isset($_GET["reg_prenom"]) ? $_GET["reg_prenom"] : NULL;
    $new_login = isset($_GET["new_login"]) ? $_GET["new_login"] : NULL;
    $reg_password = isset($_GET["reg_password"]) ? unslashes($_GET["reg_password"]) : NULL;
    $reg_password2 = isset($_GET["reg_password2"]) ? unslashes($_GET["reg_password2"]) : NULL;
    $reg_changepwd = isset($_GET["reg_changepwd"]) ? $_GET["reg_changepwd"] : 0;
    $reg_statut = isset($_GET["reg_statut"]) ? $_GET["reg_statut"] : NULL;
    $reg_email = isset($_GET["reg_email"]) ? $_GET["reg_email"] : NULL;
    $reg_etat = isset($_GET["reg_etat"]) ? $_GET["reg_etat"] : NULL;
    $reg_source = isset($_GET["reg_source"]) ? $_GET["reg_source"] : NULL;
    $reg_type_authentification = isset($_GET["type_authentification"]) ? $_GET["type_authentification"] : "locale";
    if ($reg_type_authentification != "locale")
        $reg_password = "";
    if (($reg_nom == '') || ($reg_prenom == ''))
    {
        $msg = get_vocab("please_enter_name");
        $retry = 'yes';
    }
    else 
    {   // actions si un nouvel utilisateur a été défini
        $test_login = preg_replace("/([A-Za-z0-9_@. -])/","",$new_login);
        if ((isset($new_login)) && ($new_login != '') && ($test_login == ""))
        {
            // un gestionnaire d'utilisateurs ne peut pas créer un administrateur général ou un gestionnaire d'utilisateurs
            $test_statut = TRUE;
            if (authGetUserLevel(getUserName(),-1) < 6)
            {
                if (($reg_statut == "administrateur") || ($reg_statut == "gestionnaire_utilisateur"))
                    $test_statut = FALSE;
            }
            $new_login = strtoupper($new_login);
            if ($reg_password !='')
                $reg_password_c = md5($reg_password);
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
                $sql = "SELECT * FROM ".TABLE_PREFIX."_utilisateurs WHERE login = '".$new_login."'";
                $res = grr_sql_query($sql);
                $nombreligne = grr_sql_count ($res);
                if ($nombreligne != 0)
                {
                    $msg = get_vocab("error_exist_login");
                    $retry = 'yes';
                }
                else
                {
                    $sql = "INSERT INTO ".TABLE_PREFIX."_utilisateurs SET
                    nom='".protect_data_sql($reg_nom)."',
                    prenom='".protect_data_sql($reg_prenom)."',
                    login='".protect_data_sql($new_login)."',
                    password='".protect_data_sql($reg_password_c)."',
                    changepwd='".protect_data_sql($reg_changepwd)."',
                    statut='".protect_data_sql($reg_statut)."',
                    email='".protect_data_sql($reg_email)."',
                    etat='".protect_data_sql($reg_etat)."',
                    default_site = '0',
                    default_area = '0',
                    default_room = '0',
                    default_style = '',
                    default_list_type = '',
                    default_language = 'fr',";
                    if ($reg_type_authentification=="locale")
                        $sql .= "source='local'";
                    else
                        $sql .= "source='ext'";
                    if (grr_sql_command($sql) < 0)
                    {
                        fatal_error(0, get_vocab("msg_login_created_error") . grr_sql_error());
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
            if (authGetUserLevel(getUserName(),-1) < 6)
            {
                $old_statut = grr_sql_query1("SELECT statut FROM ".TABLE_PREFIX."_utilisateurs WHERE login='".protect_data_sql($user_login)."'");
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
                    $old_mdp = grr_sql_query1("SELECT password FROM ".TABLE_PREFIX."_utilisateurs WHERE login='".protect_data_sql($user_login)."'");
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
                    $reg_password_c = md5($reg_password);
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
    $res = grr_sql_query("SELECT nom, prenom, statut, etat, email, source, changepwd FROM ".TABLE_PREFIX."_utilisateurs WHERE login='$user_login'");
    if (!$res)
        fatal_error(0, get_vocab('message_records_error'));
    $utilisateur = grr_sql_row_keyed($res, 0);
    grr_sql_free($res);
    $flag_is_local = $utilisateur['source']=="local";
}
// Privilèges
$html_privileges ='';
if ((isset($user_login)) && ($user_login != ''))
{
    $html_privileges .= "<h2>".get_vocab('liste_privileges').$utilisateur['prenom']." ".$utilisateur['nom']." :</h2>";
    $a_privileges = 'n';
    if (Settings::get("module_multisite") == "Oui")
    {
        $req_site = "SELECT id, sitename FROM ".TABLE_PREFIX."_site ORDER BY sitename";
        $res_site = grr_sql_query($req_site);
        if ($res_site)
        {
            for ($i = 0; ($row_site = grr_sql_row($res_site, $i)); $i++)
            {
                $test_admin_site = grr_sql_query1("SELECT count(id_site) FROM ".TABLE_PREFIX."_j_useradmin_site j where j.login = '".$user_login."' and j.id_site='".$row_site[0]."'");
                if ($test_admin_site >= 1)
                {
                    $a_privileges = 'y';
                    $html_privileges .= "<h3>".get_vocab("site").get_vocab("deux_points").$row_site[1]."</h3>";
                    $html_privileges .= "<ul>";
                    $html_privileges .= "<li><b>".get_vocab("administrateur du site")."</b></li>";
                    $html_privileges .= "</ul>";
                }
            }
        }
    }
    $req_area = "SELECT id, area_name, access FROM ".TABLE_PREFIX."_area ORDER BY order_display";
    $res_area = grr_sql_query($req_area);
    if ($res_area)
    {
        for ($i = 0; ($row_area = grr_sql_row($res_area, $i)); $i++)
        {
            $test_admin = grr_sql_query1("SELECT count(id_area) FROM ".TABLE_PREFIX."_j_useradmin_area j where j.login = '".$user_login."' and j.id_area='".$row_area[0]."'");
            if ($test_admin >= 1)
                $is_admin = 'y';
            else
                $is_admin = 'n';
            $nb_room = grr_sql_query1("SELECT count(r.room_name) FROM ".TABLE_PREFIX."_room r
                left join ".TABLE_PREFIX."_area a on r.area_id=a.id
                where a.id='".$row_area[0]."'");
            $req_room = "SELECT r.room_name FROM ".TABLE_PREFIX."_room r
            left join ".TABLE_PREFIX."_j_user_room j on r.id=j.id_room
            left join ".TABLE_PREFIX."_area a on r.area_id=a.id
            where j.login = '".$user_login."' and a.id='".$row_area[0]."'";
            $res_room = grr_sql_query($req_room);
            $is_gestionnaire = '';
            if ($res_room)
            {
                if ((grr_sql_count($res_room) == $nb_room) && ($nb_room != 0))
                    $is_gestionnaire = $vocab["all_rooms"];
                else
                {
                    for ($j = 0; ($row_room = grr_sql_row($res_room, $j)); $j++)
                    {
                        $is_gestionnaire .= $row_room[0]."<br />";
                    }
                }
            }
            $req_mail = "SELECT r.room_name from ".TABLE_PREFIX."_room r
            left join ".TABLE_PREFIX."_j_mailuser_room j on r.id=j.id_room
            left join ".TABLE_PREFIX."_area a on r.area_id=a.id
            where j.login = '".$user_login."' and a.id='".$row_area[0]."'";
            $res_mail = grr_sql_query($req_mail);
            $is_mail = '';
            if ($res_mail)
            {
                for ($j = 0; ($row_mail = grr_sql_row($res_mail, $j)); $j++)
                {
                    $is_mail .= $row_mail[0]."<br />";
                }
            }
            if ($row_area[2] == 'r')
            {
                $test_restreint = grr_sql_query1("SELECT count(id_area) from ".TABLE_PREFIX."_j_user_area j where j.login = '".$user_login."' and j.id_area='".$row_area[0]."'");
                if ($test_restreint >= 1)
                    $is_restreint = 'y';
                else
                    $is_restreint = 'n';
            }
            else
                $is_restreint = 'n';
            if (($is_admin == 'y') || ($is_restreint == 'y') || ($is_gestionnaire != '') || ($is_mail != ''))
            {
                $a_privileges = 'y';
                $html_privileges .= "<h3>".get_vocab("match_area").get_vocab("deux_points").$row_area[1];
                if ($row_area[2] == 'r')
                    $html_privileges .= " (".$vocab["restricted"].")";
                $html_privileges .= "</h3>";
                $html_privileges .= "<ul>";
                if ($is_admin == 'y')
                    $html_privileges .= "<li><b>".get_vocab("administrateur du domaine")."</b></li>";
                if ($is_restreint == 'y')
                    $html_privileges .= "<li><b>".get_vocab("a acces au domaine")."</b></li>";
                if ($is_gestionnaire != '')
                {
                    $html_privileges .= "<li><b>".get_vocab("gestionnaire des resources suivantes")."</b><br />";
                    $html_privileges .= $is_gestionnaire;
                    $html_privileges .= "</li>";
                }
                if ($is_mail != '')
                {
                    $html_privileges .= "<li><b>".get_vocab("est prevenu par mail")."</b><br />";
                    $html_privileges .= $is_mail;
                    $html_privileges .= "</li>";
                }
                $html_privileges .= "</ul>";
            }
        }
    }
    // peut réserver une ressource restreinte ?
    $req_room = "SELECT r.id, r.room_name FROM ".TABLE_PREFIX."_room r JOIN ".TABLE_PREFIX."_j_userbook_room j ON j.id_room = r.id WHERE j.login = '".$user_login."'";
    $res_room = grr_sql_query($req_room);
    if ($res_room && grr_sql_count($res_room)>0){
        $html_privileges .= "<h3>".get_vocab('user_can_book')."</h3><ul>";
        while($room = mysqli_fetch_array($res_room)){
            $html_privileges .= "<li>".$room['room_name']." (".$room['id'].") </li>";
        }
        $html_privileges .= "</ul>";
        $a_privileges = 'y';
    }
    grr_sql_free($res_room);

    if ($a_privileges == 'n')
    {
        if ($utilisateur['statut'] == 'administrateur')
            $html_privileges .=  "<div>".get_vocab("administrateur general").".</div>";
        else
            $html_privileges .= "<div>".get_vocab("pas de privileges").".</div>";
    }
}
// code HTML
// Utilisation de la bibliothèque prototype dans ce script
$use_prototype = 'y';
// début de page avec entête
start_page_w_header("", "", "", $type="with_session");
// colonne gauche
include "admin_col_gauche2.php";
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
<?php
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
echo '<a href="admin_user.php?display='.$display.'" type="button" class="btn btn-primary">'.get_vocab("back").'</a>';
    if (isset($user_login) && ($user_login != ''))
    {
        echo "<a href=\"admin_user_modify.php?display=$display\" type='button' class='btn btn-warning'>".get_vocab("display_add_user")."</a>";
    }
echo '<br /><br />';
echo '<div class="avertissement"><b>'.get_vocab("required").'</b></div>';
echo '</p>';
echo '<form action="admin_user_modify.php?display='.$display.'" method="get"><div>';
    if ((Settings::get("sso_statut") != "") || (Settings::get("ldap_statut") != '') || (Settings::get("imap_statut") != ''))
    {
        echo get_vocab("authentification").get_vocab("deux_points");
        echo "<select id=\"select_auth_mode\" name='type_authentification' onchange=\"display_password_fields(this.id);\">\n";
        echo "<option value='locale'";
        if ($utilisateur['source'] == 'local')
            echo " selected=\"selected\" ";
        echo ">".get_vocab("authentification_base_locale")."</option>\n";
        echo "<option value='externe'";
        if ($utilisateur['source'] == 'ext')
            echo " selected=\"selected\" ";
        echo ">".get_vocab("authentification_base_externe")."</option>\n";
        echo "</select><br /><br />".PHP_EOL;
    }
    echo get_vocab("login")." *".get_vocab("deux_points");
    if (isset($user_login) && ($user_login!=''))
    {
        echo $user_login;
        echo "<input type=\"hidden\" name=\"reg_login\" value=\"$user_login\" />";
    }
    else
    {
        echo "<input type=\"text\" name=\"new_login\" size=\"40\" required />";
    }
    echo "<table class='table-noborder'><tr>".PHP_EOL;
    echo "<td>".get_vocab("last_name")." *".get_vocab("deux_points")."</td>\n<td><input type=\"text\" name=\"reg_nom\" size=\"40\" value=\"";
    if ($utilisateur['nom'])
        echo htmlspecialchars($utilisateur['nom']);
    echo "\" /></td>\n";
    echo "<td>".get_vocab("first_name")." *".get_vocab("deux_points")."</td>\n<td><input type=\"text\" name=\"reg_prenom\" size=\"20\" value=\"";
    if ($utilisateur['nom'])
        echo htmlspecialchars($utilisateur['prenom']);
    echo "\" /></td>\n";
    echo "<td></td><td></td>";
    echo "</tr>\n";
    echo "<tr><td>".get_vocab("mail_user").get_vocab("deux_points")."</td><td><input type=\"text\" name=\"reg_email\" size=\"30\" value=\"";
    if ($utilisateur['email'])
        echo htmlspecialchars($utilisateur['email']);
    echo "\" /></td>\n";
    echo "<td>".get_vocab("statut").get_vocab("deux_points")."</td>\n";
    echo "<td><select name=\"reg_statut\" size=\"1\">\n";
    echo "<option value=\"visiteur\" ";
    if ($utilisateur['statut'] == "visiteur")
    {
        echo "selected=\"selected\"";
    }
    echo ">".get_vocab("statut_visitor")."</option>\n";
    echo "<option value=\"utilisateur\" ";
    if ($utilisateur['statut'] == "utilisateur")
    {
        echo "selected=\"selected\"";
    }
    echo ">".get_vocab("statut_user")."</option>\n";
// un gestionnaire d'utilisateurs ne peut pas créer un administrateur général ou un gestionnaire d'utilisateurs
    if (authGetUserLevel(getUserName(),-1) >= 6)
    {
        echo "<option value=\"gestionnaire_utilisateur\" ";
        if ($utilisateur['statut'] == "gestionnaire_utilisateur")
        {
            echo "selected=\"selected\"";
        }
        echo ">".get_vocab("statut_user_administrator")."</option>\n";
        echo "<option value=\"administrateur\" ";
        if ($utilisateur['statut'] == "administrateur")
        {
            echo "selected=\"selected\"";
        }
        echo ">".get_vocab("statut_administrator")."</option>\n";
    }
    echo "</select></td>\n";
    if (strtolower(getUserName()) != strtolower($user_login))
    {
        echo "<td>".get_vocab("activ_no_activ").get_vocab("deux_points")."</td>";
        echo "<td><select name=\"reg_etat\" size=\"1\">\n";
        echo "<option value=\"actif\" ";
        if ($utilisateur['etat'] == "actif")
            echo "selected=\"selected\"";
        echo ">".get_vocab("activ_user")."</option>\n";
        echo "<option value=\"inactif\" ";
        if ($utilisateur['etat'] == "inactif")
            echo "selected=\"selected\"";
        echo ">".get_vocab("no_activ_user")."</option>\n";
        echo "</select></td>";
    }
    else
    {
        echo '<td></td><td><input type="hidden" name="reg_etat" value="'.$utilisateur['etat'].'" /></td>\n';
    }
    echo "</tr>\n";
    echo "</table>";
    
    echo "<div id='password_fields' >";
    if ((isset($user_login)) && ($user_login!='') && ($flag_is_local=="y"))
        echo "<b>".get_vocab("champ_vide_mot_de_passe_inchange")."</b>";
    echo "<br />".get_vocab("pwd_toot_short")." *".get_vocab("deux_points")."<input type=\"password\" name=\"reg_password\" size=\"20\" />\n";
    echo "<br />".get_vocab("confirm_pwd")." *".get_vocab("deux_points")."<input type=\"password\" name=\"reg_password2\" size=\"20\" />\n";
    echo '<br /><label for="reg_changepwd">'.get_vocab("user_change_pwd_connexion").'</label>
                                <input type="checkbox" name="reg_changepwd" value="1"';
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
    echo $html_privileges;
echo "</div></section></body></html>";
?>