<?php
/**
 * admin_change_pwd.php
 * Interface de changement du mot de passe pour les administrateurs
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2021-03-13 12:03$
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
$grr_script_name = "admin_change_pwd.php";

include "../include/admin.inc.php";

$back = (isset($_SERVER['HTTP_REFERER']))? htmlspecialchars_decode($_SERVER['HTTP_REFERER'], ENT_QUOTES) : "./admin_accueil.php" ;
check_access(6, $back);
// Restriction dans le cas d'une démo
VerifyModeDemo();
unset($user_login);
$user_login = isset($_POST["user_login"]) ? $_POST["user_login"] : ($user_login = isset($_GET["user_login"]) ? $_GET["user_login"] : NULL);
$user_login = clean_input($user_login);
$valid = isset($_POST["valid"]) ? $_POST["valid"] : NULL;
$valid = clean_input($valid);
$msg = '';
if ($valid == "yes")
{
    unset($reg_password1);
    $reg_password1 = unslashes($_POST["reg_password1"]);
    unset($reg_password2);
    $reg_password2 = unslashes($_POST["reg_password2"]);
    $reg_password_c = md5($reg_password1);
    if (($reg_password1 != $reg_password2) || (strlen($reg_password1) < $pass_leng))
        $msg = get_vocab("passwd_error");
    else
    {
        $sql = "UPDATE ".TABLE_PREFIX."_utilisateurs SET password='" . protect_data_sql($reg_password_c)."' WHERE login='$user_login'";
        if (grr_sql_command($sql) < 0)
            fatal_error(0, get_vocab('update_pwd_failed') . grr_sql_error());
        else
            $msg = get_vocab('update_pwd_succeed');
    }
}
$user_nom = '';
$user_prenom = '';
$user_source = '';
// On appelle les informations de l'utilisateur
if (isset($user_login) && ($user_login!=''))
{
    $sql = "SELECT nom,prenom, source FROM ".TABLE_PREFIX."_utilisateurs WHERE login='$user_login'";
    $res = grr_sql_query($sql);
    if ($res)
    {
        for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
        {
            $user_nom = $row[0];
            $user_prenom = $row[1];
            $user_source = $row[2];
        }
    }
}
if (($user_source != 'local') && ($user_source != ''))
{
    showAccessDenied($back);
    exit();
}
# print the page header
start_page_w_header("", "", "", $type="with_session");
affiche_pop_up($msg,"admin");
echo "<div class='container'>";
echo "<h2>".get_vocab("pwd_change")."</h2>\n";

echo '<a href="admin_user_modify.php?user_login='.$user_login.'" type="button" class="btn btn-primary">'.get_vocab("back").'</a>';

if ($user_login != getUserName())
{
    echo "<form action=\"admin_change_pwd.php\" method='post'>";
    echo get_vocab("login")." : $user_login";
    echo "\n<br />".get_vocab("last_name").get_vocab("deux_points").$user_nom."   ".get_vocab("first_name").get_vocab("deux_points").$user_prenom;
    echo "\n<br />".get_vocab("pwd_msg_warning");
    echo "\n<br /><br />".get_vocab("new_pwd1").get_vocab("deux_points")."<input type=\"password\" name=\"reg_password1\" value=\"\" size=\"20\" />";
    echo "\n<br />".get_vocab("new_pwd2").get_vocab("deux_points")."<input type=\"password\" name=\"reg_password2\" value=\"\" size=\"20\" />";
    echo "\n<input type=\"hidden\" name=\"valid\" value=\"yes\" />";
    echo "\n<input type=\"hidden\" name=\"user_login\" value=\"$user_login\" />";
    echo "\n<br /><input type=\"submit\" value=\"".get_vocab("save")."\" /></form>";
}
else
    echo "<div><br />".get_vocab("pwd_msg_warning2")."</div>";
echo "</div>";
end_page();
?>
