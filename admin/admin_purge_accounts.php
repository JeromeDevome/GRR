<?php
/**
 * admin_purge_accounts.php
 * interface de purge des comptes et réservations
 * Dernière modification : $Date: 2009-12-16 14:52:31 $
 * @author    Christian Daviau (GIP RECIA - Esco-Portail)
 * @author    Laurent Delineau <laurent.delineau@ac-poitiers.fr>
 * @copyright Copyright 2003-2008 Laurent Delineau
 * @link      http://www.gnu.org/licenses/licenses.html
 * @package   admin
 * @version   $Id: admin_purge_accounts.php,v 1.2 2009-12-16 14:52:31 grr Exp $
 * @filesource
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GRR is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GRR; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */
include "../include/admin.inc.php";
$grr_script_name = "admin_user.php";
$back = '';
if (isset($_SERVER['HTTP_REFERER']))
	$back = htmlspecialchars($_SERVER['HTTP_REFERER']);
$display = isset($_GET["display"]) ? $_GET["display"] : NULL;
$order_by = isset($_GET["order_by"]) ? $_GET["order_by"] : NULL;
$msg = '';
if (((Settings::get("ldap_statut") == "") && (Settings::get("sso_statut") == "") && (Settings::get("imap_statut") == "")) || (authGetUserLevel(getUserName(), -1) < 6) && (authGetUserLevel(getUserName(), -1,'user') !=  1))
{
	showAccessDenied($back);
	exit();
}
print_header("", "", "", $type="with_session");
// Affichage de la colonne de gauche
include "admin_col_gauche.php";
$themessage = str_replace ( "'"  , "\\'"  , get_vocab("admin_purge_accounts_confirm"));
$themessage2 = str_replace ( "'"  , "\\'"  , get_vocab("admin_purge_accounts_confirm2"));
$themessage3 = str_replace ( "'"  , "\\'"  , get_vocab("admin_purge_tables_confirm"));
$themessage4 = str_replace ( "'"  , "\\'"  , get_vocab("admin_purge_accounts_confirm4"));
echo "<h2>".get_vocab('admin_purge_accounts.php')."</h2>";
echo get_vocab('admin_clean_accounts_desc');
echo "<div style=\"text-align:center;\">\n
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
	echo "<div style=\"text-align:center;\">\n
	<form id=\"purge_sauf_privileges\" action=\"admin_purge_accounts.php\" method=\"post\">\n
		<div>
			<input type=\"hidden\" name=\"do_purge_sauf_privileges\" value=\"1\" />\n
			<input
			type=\"button\"
			value=\"".get_vocab('admin_purge_accounts_sauf_privileges')."\"
			onclick=\"return confirmButton('purge_sauf_privileges', '$themessage4')\" />\n
		</div></form></div>";
		echo "<div style=\"text-align:center;\">\n
		<form id=\"purge\" action=\"admin_purge_accounts.php\" method=\"post\">\n
			<div>
				<input type=\"hidden\" name=\"do_purge\" value=\"1\" />\n
				<input
				type=\"button\"
				value=\"".get_vocab('admin_purge_accounts')."\"
				onclick=\"return confirmButton('purge', '$themessage')\" />\n
			</div></form></div>";
			echo "<div style=\"text-align:center;\">\n
			<form id=\"purge_avec_resa\" action=\"admin_purge_accounts.php\" method=\"post\">\n
				<div>
					<input type=\"hidden\" name=\"do_purge_avec_resa\" value=\"1\" />\n
					<input
					type=\"button\"
					value=\"".get_vocab('admin_purge_accounts_with_bookings')."\"
					onclick=\"return confirmButton('purge_avec_resa', '$themessage2')\" />\n
				</div></form></div>";
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
 // fin de l'affichage de la colonne de droite
				echo "</td></tr></table>\n";
// Affichage d'un pop-up
				affiche_pop_up($msg,"admin");
				?>
			</body>
			</html>
