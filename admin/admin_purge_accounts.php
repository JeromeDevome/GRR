<?php
/**
 * admin_purge_accounts.php
 * interface de purge des comptes et réservations
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2021-03-13 11:44$
 * @author    JeromeB & Laurent Delineau & Christian Daviau & Yan Naessens
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
$grr_script_name = "admin_user.php";

include "../include/admin.inc.php";

$back = (isset($_SERVER['HTTP_REFERER']))? htmlspecialchars_decode($_SERVER['HTTP_REFERER'], ENT_QUOTES) : "./admin_accueil.php" ;
$display = isset($_GET["display"]) ? $_GET["display"] : NULL;
$order_by = isset($_GET["order_by"]) ? $_GET["order_by"] : NULL;
$msg = '';
if (((Settings::get("ldap_statut") == "") && (Settings::get("sso_statut") == "") && (Settings::get("imap_statut") == "")) || (authGetUserLevel(getUserName(), -1) < 6) && (authGetUserLevel(getUserName(), -1,'user') !=  1))
{
	showAccessDenied($back);
	exit();
}
if (isset($_POST['do_purge_table_liaison']))
    {
        if ($_POST['do_purge_table_liaison'] == 1)
        {
            NettoyerTablesJointure();
        }
    }
    if (isset($_POST['do_purge_sauf_privileges']))
    {
        if ($_POST['do_purge_sauf_privileges'] == 1)
        {
            supprimerReservationsUtilisateursEXT("n","n");
        }
    }
    if (isset($_POST['do_purge']))
    {
        if ($_POST['do_purge'] == 1)
        {
            supprimerReservationsUtilisateursEXT("n","y");
        }
    }
    if (isset($_POST['do_purge_avec_resa']))
    {
        if ($_POST['do_purge_avec_resa'] == 1)
        {
            supprimerReservationsUtilisateursEXT("y","y");
        }
    }
// code HTML    
start_page_w_header("", "", "", $type="with_session");
// Affichage de la colonne de gauche
include "admin_col_gauche2.php";
// colonne de droite
echo "<div class='col-md-9 col-sm-8 col-xs-12'>";
$themessage = str_replace ( "'"  , "\\'"  , get_vocab("admin_purge_accounts_confirm"));
$themessage2 = str_replace ( "'"  , "\\'"  , get_vocab("admin_purge_accounts_confirm2"));
$themessage3 = str_replace ( "'"  , "\\'"  , get_vocab("admin_purge_tables_confirm"));
$themessage4 = str_replace ( "'"  , "\\'"  , get_vocab("admin_purge_accounts_confirm4"));
echo "<h2>".get_vocab('admin_purge_accounts.php')."</h2>";
echo get_vocab('admin_clean_accounts_desc');
echo "<div class='center'>\n
<form id=\"purge_liaison\" action=\"admin_purge_accounts.php\" method=\"post\">\n
	<div>
		<input type=\"hidden\" name=\"do_purge_table_liaison\" value=\"1\" />\n
		<input
		type=\"button\"
		value=\"".get_vocab('admin_purge_tables_liaison')."\"
		onclick=\"return confirmButton('purge_liaison', '$themessage3')\" />\n
	</div></form></div>";
echo "<hr />";
echo get_vocab('admin_purge_accounts_desc');
echo "<div class=\"center\">\n
<form id=\"purge_sauf_privileges\" action=\"admin_purge_accounts.php\" method=\"post\">\n
        <input type=\"hidden\" name=\"do_purge_sauf_privileges\" value=\"1\" />\n
        <input
        type=\"button\"
        value=\"".get_vocab('admin_purge_accounts_sauf_privileges')."\"
        onclick=\"return confirmButton('purge_sauf_privileges', '$themessage4')\" />\n
    </form></div>";
echo "<div class=\"center\">\n
    <form id=\"purge\" action=\"admin_purge_accounts.php\" method=\"post\">\n
        <input type=\"hidden\" name=\"do_purge\" value=\"1\" />\n
        <input
        type=\"button\"
        value=\"".get_vocab('admin_purge_accounts')."\"
        onclick=\"return confirmButton('purge', '$themessage')\" />\n
    </form></div>";
echo "<div class=\"center\">\n
    <form id=\"purge_avec_resa\" action=\"admin_purge_accounts.php\" method=\"post\">\n
        <input type=\"hidden\" name=\"do_purge_avec_resa\" value=\"1\" />\n
        <input
        type=\"button\"
        value=\"".get_vocab('admin_purge_accounts_with_bookings')."\"
        onclick=\"return confirmButton('purge_avec_resa', '$themessage2')\" />\n
    </form></div>";
echo "<hr />";
echo "<div class='center'>";
echo '<form action="admin_save_mysql.php" method="get">';
echo '	<input type="hidden" name="flag_connect" value="yes" />';
echo '	<input class="btn btn-primary" type="submit" value="'.get_vocab("submit_backup").'" style="font-variant: small-caps;" />';
echo '</form>';
echo "</div>";
// fin de l'affichage de la colonne de droite
echo "</div>\n";
// Affichage d'un pop-up
    affiche_pop_up($msg,"admin");
    
end_page();
?>
