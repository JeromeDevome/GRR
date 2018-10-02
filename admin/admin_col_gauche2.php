<?php
/**
 * admin_col_gauche2.php
 * colonne de gauche des écrans d'administration des sites, des domaines et des ressources de l'application GRR
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2018-08-31 15:00$
 * @author    JeromeB & Laurent Delineau & Marc-Henri PAMISEUX & Yan Naessens
 * @copyright Copyright 2003-2018 Team DEVOME - JeromeB
 * @link      http://www.gnu.org/licenses/licenses.html
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */
$grr_script_name = "admin_col_gauche2.php";

function sousMenu($liste,$titre='')
{
    global $chaine;
    if (count($liste)>0)
    {
        echo '<div class="dropdown">';
        echo '<button class="btn btn-block btn-primary dropdown-toggle" type="button" data-toggle="dropdown">'.$titre.'&nbsp; <span class="caret"></span></button>';
        echo '<ul class="dropdown-menu">';
        foreach ($liste as $key)
        {
            if ($chaine == $key)
                echo "<li><a href='".$key."' class='active'>".get_vocab($key)."</a></li>\n";
            else
				echo "<li><a href='".$key."'>".get_vocab($key)."</a></li>\n";
        }
        echo'</ul>';
        echo "</div>";
    }
}
// calculs préliminaires
if (get_request_uri() != '')
{
    $url_ = parse_url(get_request_uri());
    $pos = strrpos($url_['path'], "/") + 1;
    $chaine = substr($url_['path'], $pos);
}
else
    $chaine = '';
$titres = [get_vocab("admin_menu_general"),(Settings::get("module_multisite") == "Oui") ? get_vocab("admin_menu_site_area_room"):get_vocab("admin_menu_arearoom"),get_vocab("calendriers"),get_vocab("admin_menu_user"),get_vocab("admin_menu_resa"),get_vocab("admin_menu_various"),get_vocab("admin_menu_auth")];
// calcul des éléments à afficher
$liste[1] = array(); // configuration
if (authGetUserLevel(getUserName(), -1, 'area') >= 6)
			$liste[1] = ['admin_config1.php','admin_config2.php','admin_config3.php','admin_config4.php','admin_config5.php','admin_config6.php'];
$liste[2] = array(); // sites, domaines et ressources
if (Settings::get("module_multisite") == "Oui")
		{
			if (authGetUserLevel(getUserName(), -1, 'area') >= 6)
				$liste[2][] = 'admin_site.php';
		}
if (authGetUserLevel(getUserName(), -1, 'area') >= 4) $liste[2][] = 'admin_room.php';
if (authGetUserLevel(getUserName(), -1, 'area') >= 6) $liste[2][] = 'admin_type.php';
if (authGetUserLevel(getUserName(), -1, 'area') >= 4) $liste[2][] = 'admin_overload.php';
$liste[3] = array(); // calendriers
if (authGetUserLevel(getUserName(), -1, 'area') >= 6)
    $liste[3][] = 'admin_calend_ignore.php';
if ((authGetUserLevel(getUserName(), -1, 'area') >= 6)&&(Settings::get('show_holidays') == 'Oui'))
    $liste[3][] = 'admin_calend_vacances_feries.php';
if (Settings::get("jours_cycles_actif") == "Oui")
{
    if (authGetUserLevel(getUserName(), -1, 'area') >= 6)
        $liste[3][] = 'admin_calend_jour_cycle.php';
}
$liste[4] = array(); // utilisateurs
if ((authGetUserLevel(getUserName(), -1, 'area') >= 6) || (authGetUserLevel(getUserName(), -1, 'user') == 1)) $liste[4][] = 'admin_user.php';
if (Settings::get("module_multisite") == "Oui")
    if (authGetUserLevel(getUserName(), -1, 'area') >= 6) $liste[4][] = 'admin_admin_site.php';
if (authGetUserLevel(getUserName(), -1, 'area') >= 6) $liste[4][] = 'admin_right_admin.php';
if (authGetUserLevel(getUserName(), -1, 'area') >= 4) $liste[4][] = 'admin_access_area.php';
if (authGetUserLevel(getUserName(), -1, 'area') >= 4) $liste[4][] = 'admin_right.php' ;
if ((Settings::get("ldap_statut") != "") || (Settings::get("sso_statut") != "") || (Settings::get("imap_statut") != ""))
{
    if (authGetUserLevel(getUserName(), -1, 'area') >= 6) $liste[4][] = 'admin_purge_accounts.php';
}
$liste[5] = array(); // réservations en masse
if (authGetUserLevel(getUserName(), -1, 'area') >= 4) $liste[5][] = 'admin_calend2.php';
if (authGetUserLevel(getUserName(), -1, 'area') >= 5) $liste[5][] = 'admin_delete_entry_after.php';
if (authGetUserLevel(getUserName(), -1, 'area') >= 5) $liste[5][] = 'admin_delete_entry_before.php';
if (authGetUserLevel(getUserName(), -1, 'area') >= 5) $liste[5][] = 'admin_import_entries_csv_direct.php';
if (authGetUserLevel(getUserName(), -1, 'area') >= 6) $liste[5][] = 'admin_import_entries_csv_udt.php';
if (authGetUserLevel(getUserName(), -1, 'area') >= 6) $liste[5][] = 'admin_import_xml_edt.php';
$liste[6] = array(); // divers
if (authGetUserLevel(getUserName(), -1, 'area') >= 4) $liste[6][] = 'admin_email_manager.php';
if (authGetUserLevel(getUserName(), -1, 'area') >= 6) $liste[6][] = 'admin_view_connexions.php';
if (authGetUserLevel(getUserName(), -1, 'area') >= 6) $liste[6][] = 'admin_cgu.php';
if (authGetUserLevel(getUserName(), -1, 'area') >= 6) $liste[6][] = 'admin_maj.php';
$liste[7] = array(); // authentifications externes
if ( (authGetUserLevel(getUserName(), -1, 'area') >= 6) && ((!isset($sso_restrictions)) || ($ldap_restrictions == false)) )
    $liste[7][] = 'admin_config_ldap.php';
if ( (authGetUserLevel(getUserName(), -1, 'area') >= 6) && ((!isset($sso_restrictions)) || ($sso_restrictions == false)) )
    $liste[7][] = 'admin_config_sso.php';
if ( (authGetUserLevel(getUserName(), -1, 'area') >= 6) && ((!isset($sso_restrictions)) || ($imap_restrictions == false)) )
    $liste[7][] = 'admin_config_imap.php';
if (Settings::get("sso_ac_corr_profil_statut") == 'y') {
    if (authGetUserLevel(getUserName(), -1, 'area') >= 5) $liste[7][] = 'admin_corresp_statut.php';
}
// Affichage de la colonne de gauche
echo '<div class="col-md-3 col-sm-4 col-xs-12">';
// affichage des sous-menus calculés
    $k = 1;
    foreach ($titres as $titre)
    {
        sousMenu($liste[$k],$titre);
        $k++;
    }
echo '</div>';
?>
