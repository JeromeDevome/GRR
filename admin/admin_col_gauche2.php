<?php
/**
 * admin_col_gauche2.php
 * colonne de gauche des écrans d'administration des sites, des domaines et des ressources de l'application GRR
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2024-07-14 14:22$
 * @author    JeromeB & Laurent Delineau & Marc-Henri PAMISEUX & Yan Naessens
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
$grr_script_name = "admin_col_gauche2.php";

function sousMenu($liste,$titre='')
{
    global $chaine;
    if (count($liste)>0)
    {	
		$lien = mb_strtolower($titre,'UTF-8');
		$lien = preg_replace("/[^a-zA-Z0-9]/", "",$lien);
		echo '<div class="panel panel-default">';
        echo '      <div class="panel-heading">
                        <h4 class="panel-title">
                            <a data-toggle="collapse" data-parent="#accordeon" href="#'.$lien.'">'.$titre.'</a>
                        </h4>
                    </div>';
		echo '		<div id="'.$lien.'" class="panel-collapse collapse';
		if (in_array($chaine,$liste)) echo ' in';
		echo '		">
                        <div class="panel-body">
                            <table class="table table-condensed">';
						foreach ($liste as $key)
							{
								if ($chaine == $key)
									echo "<tr><td class='active'><a href='".$key.".php'>".get_vocab($key)."</a></td></tr>\n";
								else
									echo "<tr><td><a href='".$key.".php'>".get_vocab($key)."</a></td></tr>\n";
							}           
		echo '				</table>
						</div>
					</div>
			 </div>';
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
$authUserLevel = authGetUserLevel(getUserName(), -1, 'area'); // niveau d'accès de l'utilisateur connecté
if ($authUserLevel >= 6)
			$liste[1] = ['admin_config11','admin_config12','admin_couleurs','admin_config2','admin_config3','admin_config4','admin_config5','admin_config6'];
$liste[2] = array(); // sites, domaines et ressources
if (Settings::get("module_multisite") == "Oui")
		{
			if ($authUserLevel >= 6)
				$liste[2][] = 'admin_site';
		}
if ($authUserLevel >= 4) $liste[2][] = 'admin_room';
if ($authUserLevel >= 6) $liste[2][] = 'admin_type';
if ($authUserLevel >= 4) $liste[2][] = 'admin_overload';
$liste[3] = array(); // calendriers
if ($authUserLevel >= 6)
    $liste[3][] = 'admin_calend_ignore';
if (($authUserLevel >= 6)&&(Settings::get('show_holidays') == 'Oui'))
    $liste[3][] = 'admin_calend_vacances_feries';
if (Settings::get("jours_cycles_actif") == "Oui")
{
    if ($authUserLevel >= 6)
        $liste[3][] = 'admin_calend_jour_cycle';
}
$liste[4] = array(); // utilisateurs
if (($authUserLevel >= 6) || (authGetUserLevel(getUserName(), -1, 'user') == 1)) $liste[4][] = 'admin_user';
if (Settings::get("module_multisite") == "Oui")
    if ($authUserLevel >= 6) $liste[4][] = 'admin_admin_site';
if ($authUserLevel >= 5) $liste[4][] = 'admin_right_admin';
if ($authUserLevel >= 4) $liste[4][] = 'admin_access_area';
// ressources restreintes
$test = grr_sql_query1("SELECT COUNT(`who_can_book`) FROM ".TABLE_PREFIX."_room WHERE `who_can_book` = 0 ");
if (($test >0) && ($authUserLevel >= 4)) $liste[4][] = 'admin_book_room';
if ($authUserLevel >= 4) $liste[4][] = 'admin_right' ;
if ((Settings::get("ldap_statut") != "") || (Settings::get("sso_statut") != "") || (Settings::get("imap_statut") != ""))
{
    if ($authUserLevel >= 6) $liste[4][] = 'admin_purge_accounts';
}
$liste[5] = array(); // réservations en masse
if ($authUserLevel >= 4) $liste[5][] = 'admin_calend2';
if ($authUserLevel >= 5) $liste[5][] = 'admin_delete_entry_after';
if ($authUserLevel >= 5) $liste[5][] = 'admin_delete_entry_before';
if ($authUserLevel >= 5) $liste[5][] = 'admin_import_entries_csv_direct';
if ($authUserLevel >= 6) $liste[5][] = 'admin_import_entries_csv_udt';
if ($authUserLevel >= 6) $liste[5][] = 'admin_import_xml_edt';
$liste[6] = array(); // divers
if ($authUserLevel >= 4) $liste[6][] = 'admin_email_manager';
if ($authUserLevel >= 6) $liste[6][] = 'admin_view_connexions';
if ($authUserLevel >= 6) $liste[6][] = 'admin_cgu';
if ($authUserLevel >= 6) $liste[6][] = 'admin_maj';
$liste[7] = array(); // authentifications externes
if ( ($authUserLevel >= 6) && ((!isset($sso_restrictions)) || ($ldap_restrictions == false)) )
    $liste[7][] = 'admin_config_ldap';
if ( ($authUserLevel >= 6) && ((!isset($sso_restrictions)) || ($sso_restrictions == false)) )
    $liste[7][] = 'admin_config_sso';
if ( ($authUserLevel >= 6) && ((!isset($sso_restrictions)) || ($imap_restrictions == false)) )
    $liste[7][] = 'admin_config_imap';
if (Settings::get("sso_ac_corr_profil_statut") == 'y') {
    if ($authUserLevel >= 5) $liste[7][] = 'admin_corresp_statut';
}
// Affichage de la colonne de gauche
echo '<div class="col col-sm-3 col-xs-12">';
echo '<div class="panel-group" id="accordeon">';
// affichage des sous-menus calculés
    $k = 1;
    foreach ($titres as $titre)
    {
        sousMenu($liste[$k],$titre);
        $k++;
    }
echo '	</div>
	</div>';
?>