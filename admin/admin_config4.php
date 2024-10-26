<?php
/**
 * admin_config4.php
 * Interface permettant à l'administrateur la configuration de certains paramètres généraux (sécurité, connexions)
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2024-10-22 11:46$
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

$grr_script_name = "admin_config4.php";

include "../include/admin.inc.php";

$back = (isset($_SERVER['HTTP_REFERER']))? htmlspecialchars_decode($_SERVER['HTTP_REFERER'], ENT_QUOTES) : "./admin_accueil.php" ;
$_SESSION['chemin_retour'] = "admin_accueil.php";
$day   = date("d");
$month = date("m");
$year  = date("Y");
check_access(6, $back);
$msg="";
// traitement du formulaire
if (isset($_GET['motdepasse_backup']))
{
	if (!Settings::set("motdepasse_backup", $_GET['motdepasse_backup']))
		$msg.= get_vocab('backup_pwd_save_err');
}
if (isset($_GET['disable_login']))
{
	if (!Settings::set("disable_login", $_GET['disable_login']))
		$msg.= get_vocab('disable_login_save_err');
}
if (isset($_GET['url_disconnect']))
{
	if (!Settings::set("url_disconnect", $_GET['url_disconnect']))
		$msg.= get_vocab('url_disconnect_save_err');
}
// Restriction iP
if (isset($_GET['ip_autorise']))
{
	if (!Settings::set("ip_autorise", $_GET['ip_autorise']))
		$msg.= get_vocab('ip_autorise_save_err');
}
// Max session length
if (isset($_GET['sessionMaxLength']))
{
	$session_max_length = intval($_GET['sessionMaxLength']);
	if ($session_max_length < 1)
		$session_max_length = 30;
	if (!Settings::set("sessionMaxLength", $session_max_length))
		$msg.= get_vocab('sessionMaxLength_save_err');
}
// pass_leng
if (isset($_GET['pass_leng']))
{
	$pass_length = intval($_GET['pass_leng']);
	if ($pass_length < 1)
		$pass_length = 1;
	if (!Settings::set("pass_leng", $pass_length))
		$msg.= get_vocab('pass_leng_save_err');
}
// début du code html
# print the page header
start_page_w_header("", "", "", $type="with_session");
if($msg != ""){
  affiche_pop_up($msg,"admin");
} 
elseif(isset($_GET['ok']))
{
	$msg = get_vocab("message_records");
	affiche_pop_up($msg,"admin");
}
// Affichage de la colonne de gauche
include "admin_col_gauche2.php";
echo '<div class="col col-md-9 col-sm-8 col-xs-12">';
echo "<h2>".get_vocab('admin_config4.php')."</h2>";
//
// dans le cas de mysql, on propose une sauvegarde et l'ouverture de la base
//
if ($dbsys == "mysql")
{
	//
	// Saving base
	//********************************
	//
	echo "<h3>".get_vocab('title_backup')."</h3>\n";
	echo "<p>".get_vocab("explain_backup")."</p>\n";
	echo "<p><i>".get_vocab("warning_message_backup")."</i></p>\n";
	echo '<form action="admin_save_mysql.php" method="get" >';
	echo '	<div class="center">
			<input type="hidden" name="flag_connect" value="yes" />
			<input class="btn btn-primary" type="submit" value="'.get_vocab("submit_backup").'" style="font-variant: small-caps;" />
		</div>';
	echo '</form>';
    //
    // Loading base
    //********************************
    //
	if($restaureBBD == 1){
		echo "\n<hr /><h3>".get_vocab('Restauration_de_la_base_GRR')."</h3>";
		echo "\n<p>".get_vocab('explain_restore')."</p>";
		echo "\n<p><span class=\"avertissement\"><i>".get_vocab('warning_restore')."</i></span></p>\n";
		echo '<form method="post" enctype="multipart/form-data" action="admin_open_mysql.php">';
		echo '<div class="center">';
		echo '<input type="file" name="sql_file" />';
		echo '<br /><br />';
		echo '<input class="btn btn-primary" type="submit" value="'.get_vocab('Restaurer_la_sauvegarde').'" style="font-variant: small-caps;" />';
		echo '</div>'.PHP_EOL;
		echo '</form>'.PHP_EOL;
	}
}
	echo "<form action=\"./admin_config4.php\" method=\"get\">";
	# Backup automatique
	echo "\n<hr /><h3>".get_vocab("execution automatique backup")."</h3>";
	echo "<p>".get_vocab("execution automatique backup explications")."</p>";
	echo "\n<p>".get_vocab("execution_automatique_backup_mdp").get_vocab("deux_points");
	echo "\n<input class=\"form-control\" type=\"password\" name=\"motdepasse_backup\" value=\"".Settings::get("motdepasse_backup")."\" size=\"20\" /></p>";
	//
	// Suspendre les connexions
	//*************************
	//
	echo "\n<hr /><h3>".get_vocab('title_disable_login')."</h3>";
	echo "\n<p>".get_vocab("explain_disable_login");
    echo "<br />";
	echo "<label><input type='radio' name='disable_login' value='yes' ";
    if (Settings::get("disable_login")=='yes') echo "checked=\"checked\""; 
    echo "/>";
	echo "&nbsp".get_vocab("disable_login_on")."</label>";
	echo "<br />";
	echo "<label><input type='radio' name='disable_login' value='no' ";
    if (Settings::get("disable_login")=='no') echo "checked=\"checked\"";
    echo " />";
	echo "&nbsp".get_vocab("disable_login_off")."</label>";
	echo "</p>";
	//
	// iP autorisé
	//*************************
	//
	echo "\n<hr /><h3>".get_vocab('title_ip_autorise')."</h3>";
	echo "\n<p>".get_vocab("explain_ip_autorise")."</p>";
	echo '<input class="form-control" type="text" name="ip_autorise" value="'.(Settings::get("ip_autorise")).'" />';
	//
	// Durée d'une session
	//********************
	//
echo "\n<hr /><h3>".get_vocab("title_session_max_length")."</h3>";
echo "\n<p><label>".get_vocab("session_max_length");
echo "&nbsp<input type=\"number\" name=\"sessionMaxLength\" size=\"5\" value=\"".Settings::get("sessionMaxLength")."\" /></label>";
echo "<p>".get_vocab("explain_session_max_length")."</p>";
//Longueur minimale du mot de passe exigé
echo "\n<hr /><h3>".get_vocab("pwd")."</h3>";
echo "\n<p><label>".get_vocab("pass_leng_explain").get_vocab("deux_points")."
<input type=\"number\" name=\"pass_leng\" value=\"".htmlentities(Settings::get("pass_leng"))."\" size=\"5\" /></label></p>";
//
// Url de déconnexion
//*******************
//
echo "<hr /><h3>".get_vocab("Url_de_deconnexion")."</h3>\n";
echo "<p>".get_vocab("Url_de_deconnexion_explain")."</p>\n";
echo "<p><i>".get_vocab("Url_de_deconnexion_explain2")."</i>";
$value_url = Settings::get("url_disconnect");
echo "<br /><label>".get_vocab("Url_de_deconnexion").get_vocab("deux_points")."\n";
echo "<input type=\"text\" name=\"url_disconnect\" size=\"40\" value =\"$value_url\"/></label></p>";
echo "\n<br /><br />";
echo "<div id=\"fixe\" ><input class=\"btn btn-primary\" type=\"submit\" name=\"ok\" value=\"".get_vocab("save")."\" style=\"font-variant: small-caps;\"/></div>";
echo "\n</form>";
// fin de l'affichage de la colonne de droite et de la page
echo "\n</div></section></body></html>";
?>