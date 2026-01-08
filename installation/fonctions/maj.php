<?php
/**
 * installation/fonctions/maj.php
 * interface permettant la mise à jour de la base de données
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2025-09-23 18:45$
 * @author    JeromeB & Laurent Delineau & Yan Naessens
 * @author    Arnaud Fornerot pour l'intégation au portail Envole http://ent-envole.com/
 * @copyright Copyright 2003-2025 Team DEVOME - JeromeB
 * @link      http://www.gnu.org/licenses/licenses.html
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */

function formatresult($echo,$dbt,$fin) {
	global $majscript;

	if($majscript)
		$formatresultat =  $echo."\n";
	else
		$formatresultat = $dbt.$echo.$fin."</br>";

	return $formatresultat;
}

function traiteRequete($requete = "")
{
	mysqli_query($GLOBALS['db_c'], $requete);
	$erreur_no = mysqli_errno($GLOBALS['db_c']);
	if (!$erreur_no)
		$retour = "";
	else
	{
		switch ($erreur_no)
		{
			case "1060":
			// le champ existe déjà : pas de problème
			$retour = "";
			break;
			case "1061":
			// La clef existe déjà : pas de problème
			$retour = "";
			break;
			case "1062":
			// Présence d'un doublon : création de la cléf impossible
			$retour = "<span style=\"color:#FF0000;\">Erreur (<b>non critique</b>) sur la requête : <i>".$requete."</i> (".mysqli_errno($GLOBALS['db_c'])." : ".mysqli_error($GLOBALS['db_c']).")</span><br />\n";
			break;
			case "1068":
			// Des clefs existent déjà : pas de problème
			$retour = "";
			break;
			case "1091":
			// Déjà supprimé : pas de problème
			$retour = "";
			break;
			default:
			$retour = "<span style=\"color:#FF0000;\">Erreur sur la requête : <i>".$requete."</i> (".mysqli_errno($GLOBALS['db_c'])." : ".mysqli_error($GLOBALS['db_c']).")</span><br />\n";
			break;
		}
	}
	return $retour;
}

function execute_maj3($version_old, $version_grr)
{
	
	$result = '';
	$result_inter = '';

	// On commence la mise à jour
	if (version_compare($version_old, "1.4.0", '<'))
	{
		$result .= "<b>Mise à jour jusqu'à la version 1.4 :</b><br />";
		$result_inter .= traiteRequete("ALTER TABLE mrbs_area ADD order_display TINYINT NOT NULL;");
		$result_inter .= traiteRequete("ALTER TABLE mrbs_room ADD max_booking SMALLINT DEFAULT '-1' NOT NULL ;");
		$req = grr_sql_query1("SELECT VALUE FROM setting WHERE NAME='sessionMaxLength'");
		if ($req == -1)
			$result_inter .= traiteRequete("INSERT INTO setting VALUES ('sessionMaxLength', '30');");
		$req = grr_sql_query1("SELECT VALUE FROM setting WHERE NAME='automatic_mail'");
		if ($req == -1)
			$result_inter .= traiteRequete("INSERT INTO setting VALUES ('automatic_mail', 'yes');");
		$req = grr_sql_query1("SELECT VALUE FROM setting WHERE NAME='begin_bookings'");
		if ($req == -1)
			$result_inter .= traiteRequete("INSERT INTO setting VALUES ('begin_bookings', '1062367200');");
		$req = grr_sql_query1("SELECT VALUE FROM setting WHERE NAME='end_bookings'");
		if ($req == -1)
			$result_inter .= traiteRequete("INSERT INTO setting VALUES ('end_bookings', '1088546400');");
		$req = grr_sql_query1("SELECT VALUE FROM setting WHERE NAME='company'");
		if ($req == -1)
			$result_inter .= traiteRequete("INSERT INTO setting VALUES ('company', 'Nom de l\'établissement');");
		$req = grr_sql_query1("SELECT VALUE FROM setting WHERE NAME='webmaster_name'");
		if ($req == -1)
			$result_inter .= traiteRequete("INSERT INTO setting VALUES ('webmaster_name', 'Webmestre de GRR');");
		$req = grr_sql_query1("SELECT VALUE FROM setting WHERE NAME='webmaster_email'");
		if ($req == -1)
			$result_inter .= traiteRequete("INSERT INTO setting VALUES ('webmaster_email', 'admin@mon.site.fr');");
		$req = grr_sql_query1("SELECT VALUE FROM setting WHERE NAME='technical_support_email'");
		if ($req == -1)
			$result_inter .= traiteRequete("INSERT INTO setting VALUES ('technical_support_email', 'support.technique@mon.site.fr');");
		$req = grr_sql_query1("SELECT VALUE FROM setting WHERE NAME='grr_url'");
		if ($req == -1)
			$result_inter .= traiteRequete("INSERT INTO setting VALUES ('grr_url', 'http://mon.site.fr/grr/');");
		$req = grr_sql_query1("SELECT VALUE FROM setting WHERE NAME='disable_login'");
		if ($req == -1)
			$result_inter .= traiteRequete("INSERT INTO setting VALUES ('disable_login', 'no');");
		if ($result_inter == '')
			$result .= "<span style=\"color:green;\">Ok !</span><br />";
		else
			$result .= $result_inter;
		$result_inter = '';
	}

	if (version_compare($version_old, "1.5.0", '<'))
	{
		$result .= "<b>Mise à jour jusqu'à la version 1.5 :</b><br />";
		// GRR1.5
		$result_inter .= traiteRequete("ALTER TABLE utilisateurs ADD default_area SMALLINT NOT NULL ;");
		$result_inter .= traiteRequete("ALTER TABLE utilisateurs ADD default_room SMALLINT NOT NULL ;");
		$result_inter .= traiteRequete("ALTER TABLE utilisateurs ADD default_style VARCHAR( 50 ) NOT NULL ;");
		$result_inter .= traiteRequete("ALTER TABLE utilisateurs ADD default_list_type VARCHAR( 50 ) NOT NULL ;");
		$result_inter .= traiteRequete("ALTER TABLE utilisateurs ADD default_language VARCHAR( 3 ) NOT NULL ;");
		$req = grr_sql_query1("SELECT VALUE FROM setting WHERE NAME='title_home_page'");
		if ($req == -1)
			$result_inter .= traiteRequete("INSERT INTO setting VALUES ('title_home_page', 'Gestion et Réservation de Ressources');");
		if ($req == -1)
			$result_inter .= traiteRequete("INSERT INTO setting VALUES ('message_home_page', 'En raison du caractère personnel du contenu, ce site est soumis à des restrictions utilisateurs. Pour accéder aux outils de réservation, identifiez-vous :');");
		if ($result_inter == '')
			$result .= "<span style=\"color:green;\">Ok !</span><br />";
		else
			$result .= $result_inter;
		$result_inter = '';
	}

	if (version_compare($version_old, "1.6.0", '<'))
	{
		$result .= "<b>Mise à jour jusqu'à la version 1.6 :</b><br />";
		// GRR1.6
		$req = grr_sql_query1("SELECT VALUE FROM setting WHERE NAME='default_language'");
		if ($req == -1)
			$result_inter .= traiteRequete("INSERT INTO setting VALUES ('default_language', 'fr');");
		$result_inter .= traiteRequete("ALTER TABLE mrbs_entry ADD statut_entry CHAR( 1 ) DEFAULT '-' NOT NULL ;");
		$result_inter .= traiteRequete("ALTER TABLE mrbs_room ADD statut_room CHAR( 1 ) DEFAULT '1' NOT NULL ;");
		$result_inter .= traiteRequete("ALTER TABLE mrbs_room ADD show_fic_room CHAR( 1 ) DEFAULT 'n' NOT NULL ;");
		$result_inter .= traiteRequete("ALTER TABLE mrbs_room ADD picture_room VARCHAR( 50 ) NOT NULL;");
		$result_inter .= traiteRequete("ALTER TABLE mrbs_room ADD comment_room TEXT NOT NULL;");
		if ($result_inter == '')
			$result .= "<span style=\"color:green;\">Ok !</span><br />";
		else
			$result .= $result_inter;
		$result_inter = '';
	}

	if (version_compare($version_old, "1.7.0", '<'))
	{
		$result .= "<b>Mise à jour jusqu'à la version 1.7 :</b><br />";
		// GRR1.7
		$result_inter .= traiteRequete("ALTER TABLE utilisateurs ADD source VARCHAR( 10 ) NOT NULL ;");
		$result_inter .= traiteRequete("ALTER TABLE j_mailuser_room CHANGE login login VARCHAR( 20 ) NOT NULL;");
		$result_inter .= traiteRequete("ALTER TABLE j_user_area CHANGE login login VARCHAR( 20 ) NOT NULL;");
		$result_inter .= traiteRequete("ALTER TABLE j_user_room CHANGE login login VARCHAR( 20 ) NOT NULL;");
		$result_inter .= traiteRequete("ALTER TABLE j_mailuser_room ADD PRIMARY KEY ( login , id_room ) ;");
		$result_inter .= traiteRequete("ALTER TABLE j_user_area ADD PRIMARY KEY ( login , id_area ) ;");
		$result_inter .= traiteRequete("ALTER TABLE j_user_room ADD PRIMARY KEY ( login , id_room ) ;");
		$result_inter .= traiteRequete("ALTER TABLE log CHANGE LOGIN LOGIN VARCHAR( 20 ) NOT NULL;");
		$req = grr_sql_query1("SELECT VALUE FROM setting WHERE NAME='url_disconnect'");
		if ($req == -1)
			$result_inter .= traiteRequete("INSERT INTO setting VALUES ('url_disconnect', '');");

		if ($result_inter == '')
			$result .= "<span style=\"color:green;\">Ok !</span><br />";
		else
			$result .= $result_inter;
		$result_inter = '';
	}

	if (version_compare($version_old, "1.8.0", '<'))
	{
		$result .= "<b>Mise à jour jusqu'à la version 1.8 :</b><br />";
		// GRR1.8
		$result_inter .= traiteRequete("ALTER TABLE utilisateurs CHANGE login login VARCHAR( 20 ) NOT NULL;");
		$result_inter .= traiteRequete("ALTER TABLE utilisateurs CHANGE nom nom VARCHAR( 30 ) NOT NULL;");
		$result_inter .= traiteRequete("ALTER TABLE utilisateurs CHANGE prenom prenom VARCHAR( 30 ) NOT NULL;");
		$result_inter .= traiteRequete("ALTER TABLE utilisateurs CHANGE password password VARCHAR( 32 ) NOT NULL;");
		$result_inter .= traiteRequete("ALTER TABLE utilisateurs CHANGE email email VARCHAR( 100 ) NOT NULL;");
		$result_inter .= traiteRequete("ALTER TABLE utilisateurs CHANGE statut statut VARCHAR( 30 ) NOT NULL;");
		$result_inter .= traiteRequete("ALTER TABLE utilisateurs ADD PRIMARY KEY ( login );");
		$result_inter .= traiteRequete("CREATE TABLE IF NOT EXISTS ".TABLE_PREFIX."_j_useradmin_area (login varchar(20) NOT NULL default '', id_area int(11) NOT NULL default '0', PRIMARY KEY  (login,id_area) );");
		$result_inter .= traiteRequete("ALTER TABLE j_mailuser_room RENAME ".TABLE_PREFIX."_j_mailuser_room;");
		$result_inter .= traiteRequete("ALTER TABLE j_user_area RENAME ".TABLE_PREFIX."_j_user_area;");
		$result_inter .= traiteRequete("ALTER TABLE j_user_room RENAME ".TABLE_PREFIX."_j_user_room;");
		$result_inter .= traiteRequete("ALTER TABLE log RENAME ".TABLE_PREFIX."_log;");
		$result_inter .= traiteRequete("ALTER TABLE mrbs_area RENAME ".TABLE_PREFIX."_area;");
		$result_inter .= traiteRequete("ALTER TABLE mrbs_entry RENAME ".TABLE_PREFIX."_entry;");
		$result_inter .= traiteRequete("ALTER TABLE mrbs_repeat RENAME ".TABLE_PREFIX."_repeat;");
		$result_inter .= traiteRequete("ALTER TABLE mrbs_room RENAME ".TABLE_PREFIX."_room;");
		$result_inter .= traiteRequete("ALTER TABLE setting RENAME ".TABLE_PREFIX."_setting;");
		$result_inter .= traiteRequete("ALTER TABLE utilisateurs RENAME ".TABLE_PREFIX."_utilisateurs;");
		$result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_area ADD ip_adr VARCHAR(15) NOT NULL;");
		$result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_area CHANGE area_name area_name VARCHAR( 30 ) NOT NULL;");
		$result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_room CHANGE description description VARCHAR( 60 ) NOT NULL;");
		if ($result_inter == '')
			$result .= "<span style=\"color:green;\">Ok !</span><br />";
		else
			$result .= $result_inter;
		$result_inter = '';
	}

	if (version_compare($version_old, "1.9.0", '<'))
	{
		$result .= "<b>Mise à jour jusqu'à la version 1.9 :</b><br />";
		// GRR1.9
		$result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_area ADD morningstarts_area SMALLINT NOT NULL ,ADD eveningends_area SMALLINT NOT NULL , ADD resolution_area SMALLINT NOT NULL ,ADD eveningends_minutes_area SMALLINT NOT NULL ,ADD weekstarts_area SMALLINT NOT NULL ,ADD twentyfourhour_format_area SMALLINT NOT NULL ;");
		$result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_area ADD calendar_default_values VARCHAR( 1 ) DEFAULT 'y' NOT NULL ;");
		$result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_room ADD delais_max_resa_room SMALLINT DEFAULT '-1' NOT NULL ;");
		$result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_room ADD delais_min_resa_room SMALLINT DEFAULT '0' NOT NULL ;");
		$result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_room ADD order_display SMALLINT DEFAULT '0' NOT NULL ;");
		$result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_room ADD allow_action_in_past VARCHAR( 1 ) DEFAULT 'n' NOT NULL ;");
		$result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_j_mailuser_room CHANGE login login VARCHAR( 40 ) NOT NULL;");
		$result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_j_user_area CHANGE login login VARCHAR( 40 ) NOT NULL;");
		$result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_j_user_room CHANGE login login VARCHAR( 40 ) NOT NULL;");
		$result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_j_useradmin_area CHANGE login login VARCHAR( 40 ) NOT NULL;");
		$result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_log CHANGE LOGIN LOGIN VARCHAR( 40 ) NOT NULL;");
		$result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_utilisateurs CHANGE login login VARCHAR( 40 ) NOT NULL;");
		if ($result_inter == '')
			$result .= "<span style=\"color:green;\">Ok !</span><br />";
		else
			$result .= $result_inter;
		$result_inter = '';
	}

	if (version_compare($version_old, "1.9.1", '<'))
	{
		$result .= "<b>Mise à jour jusqu'à la version 1.9.1 :</b><br />";
		// GRR1.9.1
		$result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_log CHANGE USER_AGENT USER_AGENT VARCHAR( 100 ) NOT NULL;");
		if ($result_inter == '')
			$result .= "<span style=\"color:green;\">Ok !</span><br />";
		else
			$result .= $result_inter;
		$result_inter = '';
	}

	if (version_compare($version_old, "1.9.2", '<'))
	{
		$result .= "<b>Mise à jour jusqu'à la version 1.9.2 :</b><br />";
		// GRR1.9.2
		$result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_area ADD enable_periods VARCHAR( 1 ) DEFAULT 'n' NOT NULL ;");
		$result_inter .= traiteRequete("CREATE TABLE IF NOT EXISTS ".TABLE_PREFIX."_area_periodes (id_area INT NOT NULL , num_periode SMALLINT NOT NULL , nom_periode VARCHAR( 100 ) NOT NULL );");
		$result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_room ADD delais_option_reservation SMALLINT DEFAULT '0' NOT NULL;");
		$result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_entry ADD option_reservation INT DEFAULT '0' NOT NULL;");
		$result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_room ADD dont_allow_modify VARCHAR( 1 ) DEFAULT 'n' NOT NULL ;");
		$result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_room ADD type_affichage_reser SMALLINT DEFAULT '0' NOT NULL;");
		$result_inter .= traiteRequete("CREATE TABLE IF NOT EXISTS ".TABLE_PREFIX."_type_area (id int(11) NOT NULL auto_increment, type_name varchar(30) NOT NULL default '',order_display smallint(6) NOT NULL default '0',couleur smallint(6) NOT NULL default '0',type_letter char(2) NOT NULL default '',  PRIMARY KEY  (id));");
		$result_inter .= traiteRequete("CREATE TABLE IF NOT EXISTS ".TABLE_PREFIX."_j_type_area (id_type int(11) NOT NULL default '0', id_area int(11) NOT NULL default '0');");
		$result_inter .= traiteRequete("CREATE TABLE IF NOT EXISTS ".TABLE_PREFIX."_calendar (DAY int(11) NOT NULL default '0');");
		if ($result_inter == '')
			$result .= "<span style=\"color:green;\">Ok !</span><br />";
		else
			$result .= $result_inter;
		$result_inter = '';
	}

	if (version_compare($version_old, "1.9.3", '<'))
	{
		$result .= "<b>Mise à jour jusqu'à la version 1.9.3 :</b><br />";
		// GRR1.9.3
		$result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_entry ADD overload_desc text;");
		$result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_repeat ADD overload_desc text;");
		$result_inter .= traiteRequete("CREATE TABLE IF NOT EXISTS ".TABLE_PREFIX."_overload (id int(11) NOT NULL auto_increment, id_area INT NOT NULL, fieldname VARCHAR(25) NOT NULL default '', fieldtype VARCHAR(25) NOT NULL default '', obligatoire CHAR( 1 ) DEFAULT 'n' NOT NULL, PRIMARY KEY  (id));");
		$result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_area ADD display_days VARCHAR( 7 ) DEFAULT 'yyyyyyy' NOT NULL;");
		$result_inter .= traiteRequete("UPDATE ".TABLE_PREFIX."_utilisateurs SET default_style='';");
		// Suppression du paramètre url_disconnect_lemon
		$req = grr_sql_query1("SELECT VALUE FROM ".TABLE_PREFIX."_setting WHERE NAME='url_disconnect_lemon'");
		if (($req != -1) && (($req != "")))
		{
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('url_disconnect', '".$req."');");
			$del = traiteRequete("DELETE from ".TABLE_PREFIX."_setting where NAME='url_disconnect_lemon'");
		}
		// Mise à jour de cas_statut
		$req = grr_sql_query1("SELECT VALUE FROM ".TABLE_PREFIX."_setting WHERE NAME='cas_statut'");
		if ($req == "visiteur")
		{
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('sso_statut', 'cas_visiteur');");
			$del = traiteRequete("DELETE from ".TABLE_PREFIX."_setting where NAME='cas_statut'");
		}
		if ($req == "utilisateur")
		{
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('sso_statut', 'cas_utilisateur');");
			$del = traiteRequete("DELETE from ".TABLE_PREFIX."_setting where NAME='cas_statut'");
		}
		// Mise à jour de lemon_statut
		$req = grr_sql_query1("SELECT VALUE FROM ".TABLE_PREFIX."_setting WHERE NAME='lemon_statut'");
		if ($req == "visiteur")
		{
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('sso_statut', 'lemon_visiteur');");
			$del = traiteRequete("DELETE from ".TABLE_PREFIX."_setting where NAME='lemon_statut'");
		}
		if ($req == "utilisateur")
		{
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('sso_statut', 'lemon_utilisateur');");
			$del = traiteRequete("DELETE from ".TABLE_PREFIX."_setting where NAME='lemon_statut'");
		}
		if ($result_inter == '')
			$result .= "<span style=\"color:green;\">Ok !</span><br />";
		else
			$result .= $result_inter;
		$result_inter = '';
	}

	if (version_compare($version_old, "1.9.4", '<'))
	{
		$result .= "<b>Mise à jour jusqu'à la version 1.9.4 :</b><br />";
		// GRR1.9.4
		$result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_overload ADD fieldlist TEXT NOT NULL AFTER fieldtype;");
		$result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_entry CHANGE type type CHAR(2);");
		$result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_repeat CHANGE type type CHAR(2);");
		$result_inter .= traiteRequete("ALTER TABLE  ".TABLE_PREFIX."_room ADD  moderate TINYINT( 1 ) NULL DEFAULT  '0';");
		$result_inter .= traiteRequete("ALTER TABLE  ".TABLE_PREFIX."_entry ADD  moderate TINYINT( 1 ) NULL DEFAULT  '0';");
		$result_inter .= traiteRequete("CREATE TABLE IF NOT EXISTS ".TABLE_PREFIX."_entry_moderate (id int(11) NOT NULL auto_increment, login_moderateur varchar(40) NOT NULL default '',motivation_moderation text NOT NULL,start_time int(11) NOT NULL default '0',end_time int(11) NOT NULL default '0',entry_type int(11) NOT NULL default '0', repeat_id int(11) NOT NULL default '0',room_id int(11) NOT NULL default '1',timestamp timestamp(14) NOT NULL,create_by varchar(25) NOT NULL default '',name varchar(80) NOT NULL default '',type char(2) default NULL,description text,statut_entry char(1) NOT NULL default '-',option_reservation int(11) NOT NULL default '0',overload_desc text,moderate tinyint(1) default '0', PRIMARY KEY  (id), KEY idxStartTime (start_time), KEY idxEndTime (end_time) );");
		$result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_area ADD id_type_par_defaut INT(11) DEFAULT '-1' NOT NULL;");
		$result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_overload ADD obligatoire CHAR( 1 ) DEFAULT 'n' NOT NULL;");
		$req = grr_sql_query1("SELECT VALUE FROM ".TABLE_PREFIX."_setting WHERE NAME='allow_users_modify_profil'");
		if ($req == -1)
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('allow_users_modify_profil', '2');");
		$req = grr_sql_query1("SELECT VALUE FROM ".TABLE_PREFIX."_setting WHERE NAME='allow_users_modify_mdp'");
		if ($req == -1)
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('allow_users_modify_mdp', '2');");
		$req = grr_sql_query1("SELECT VALUE FROM ".TABLE_PREFIX."_setting WHERE NAME='display_info_bulle'");
		if ($req == -1)
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('display_info_bulle', '1');");
		$req = grr_sql_query1("SELECT VALUE FROM ".TABLE_PREFIX."_setting WHERE NAME='display_full_description'");
		if ($req == -1)
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('display_full_description', '1');");
		$req = grr_sql_query1("SELECT VALUE FROM ".TABLE_PREFIX."_setting WHERE NAME='pview_new_windows'");
		if ($req == -1)
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('pview_new_windows', '1');");
		$req = grr_sql_query1("SELECT VALUE FROM ".TABLE_PREFIX."_setting WHERE NAME='default_report_days'");
		if ($req == -1)
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('default_report_days', '30');");
		$req = grr_sql_query1("SELECT VALUE FROM ".TABLE_PREFIX."_setting WHERE NAME='use_fckeditor'");
		if ($req == -1)
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('use_fckeditor', '1');");
		$req = grr_sql_query1("SELECT VALUE FROM ".TABLE_PREFIX."_setting WHERE NAME='authentification_obli'");
		if ($req == -1)
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('authentification_obli', '0');");
		$req = grr_sql_query1("SELECT VALUE FROM ".TABLE_PREFIX."_setting WHERE NAME='visu_fiche_description'");
		if ($req == -1)
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('visu_fiche_description', '0');");
		$req = grr_sql_query1("SELECT VALUE FROM ".TABLE_PREFIX."_setting WHERE NAME='allow_search_level'");
		if ($req == -1)
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('allow_search_level', '1');");
		$req = grr_sql_query1("SELECT VALUE FROM ".TABLE_PREFIX."_setting WHERE NAME='allow_user_delete_after_begin'");
		if ($req == -1)
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('allow_user_delete_after_begin', '0');");
		$req = grr_sql_query1("SELECT VALUE FROM ".TABLE_PREFIX."_setting WHERE NAME='allow_gestionnaire_modify_del'");
		if ($req == -1)
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('allow_gestionnaire_modify_del', '1');");
		$req = grr_sql_query1("SELECT VALUE FROM ".TABLE_PREFIX."_setting WHERE NAME='javascript_info_disabled'");
		if ($req == -1)
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('javascript_info_disabled', '1');");
		$req = grr_sql_query1("SELECT VALUE FROM ".TABLE_PREFIX."_setting WHERE NAME='javascript_info_admin_disabled'");
		if ($req == -1)
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('javascript_info_admin_disabled', '1');");
		$req = grr_sql_query1("SELECT VALUE FROM ".TABLE_PREFIX."_setting WHERE NAME='pass_leng'");
		if ($req == -1)
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('pass_leng', '6');");
		if ($result_inter == '')
		{
			$result .= "<span style=\"color:green;\">Ok !</span><br />";
		}
		else
		{
			$result .= $result_inter;
		}
		$result_inter = '';

	}

	if (version_compare($version_old, "1.9.5", '<'))
	{
		$result .= "<b>Mise à jour jusqu'à la version 1.9.5 RC1 :</b><br />";
		// GRR1.9.5
		$result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_area ADD duree_max_resa_area INT DEFAULT '-1' NOT NULL ;");
		$result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_area ADD duree_par_defaut_reservation_area SMALLINT DEFAULT '0' NOT NULL ;");
		$result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_log CHANGE USER_AGENT USER_AGENT VARCHAR( 255 );");
		$result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_log CHANGE REFERER REFERER VARCHAR( 255 );");
		$result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_entry ADD jours TINYINT( 2 ) NOT NULL DEFAULT '0';");
		$result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_repeat ADD jours TINYINT( 2 ) NOT NULL DEFAULT '0';");
		$result_inter .= traiteRequete("CREATE TABLE IF NOT EXISTS ".TABLE_PREFIX."_calendrier_jours_cycle (DAY int(11) NOT NULL default '0', Jours tinyint(2) NOT NULL default '0');");
		$result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_overload ADD affichage CHAR( 1 ) DEFAULT 'n' NOT NULL;");
		$result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_overload ADD overload_mail CHAR( 1 ) DEFAULT 'n' NOT NULL;");
		$result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_area CHANGE resolution_area resolution_area INT DEFAULT '0' NOT NULL");
		$result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_area CHANGE duree_par_defaut_reservation_area duree_par_defaut_reservation_area INT DEFAULT '0' NOT NULL");
		$req = grr_sql_query1("SELECT VALUE FROM ".TABLE_PREFIX."_setting WHERE NAME='allow_users_modify_email'");
		if ($req == -1)
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('allow_users_modify_email', '2');");
		$req = grr_sql_query1("SELECT VALUE FROM ".TABLE_PREFIX."_setting WHERE NAME='jour_debut_Jours_Cycles'");
		if ($req == -1)
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('jour_debut_Jours_Cycles', '1');");
		$req = grr_sql_query1("SELECT VALUE FROM ".TABLE_PREFIX."_setting WHERE NAME='nombre_jours_Jours_Cycles'");
		if ($req == -1)
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('nombre_jours_Jours_Cycles', '1');");
		$req = grr_sql_query1("SELECT NAME FROM ".TABLE_PREFIX."_setting WHERE NAME='UserAllRoomsMaxBooking'");
		if ($req == -1)
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('UserAllRoomsMaxBooking', '-1');");
		$req = grr_sql_query1("SELECT VALUE FROM ".TABLE_PREFIX."_setting WHERE NAME='jours_cycles_actif'");
		if ($req == -1)
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('jours_cycles_actif', 'Non');");
		$req = grr_sql_query1("SELECT count(VALUE) FROM ".TABLE_PREFIX."_setting WHERE NAME='grr_mail_Password'");
		if ($req == 0)
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('grr_mail_Password', '');");
		$req = grr_sql_query1("SELECT VALUE FROM ".TABLE_PREFIX."_setting WHERE NAME='grr_mail_method'");
		if ($req == -1)
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('grr_mail_method', 'mail');");
		$req = grr_sql_query1("SELECT count(VALUE) FROM ".TABLE_PREFIX."_setting WHERE NAME='grr_mail_smtp'");
		if ($req == 0)
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('grr_mail_smtp', '');");
		$req = grr_sql_query1("SELECT VALUE FROM ".TABLE_PREFIX."_setting WHERE NAME='grr_mail_Bcc'");
		if ($req == -1)
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('grr_mail_Bcc', 'n');");
		$req = grr_sql_query1("SELECT count(VALUE) FROM ".TABLE_PREFIX."_setting WHERE NAME='grr_mail_Username'");
		if ($req == 0)
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('grr_mail_Username', '');");
		$req = grr_sql_query1("SELECT VALUE FROM ".TABLE_PREFIX."_setting WHERE NAME='verif_reservation_auto'");
		if ($req == -1)
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('verif_reservation_auto', '0');");
		$req = grr_sql_query1("SELECT VALUE FROM ".TABLE_PREFIX."_setting WHERE NAME='ConvertLdapUtf8toIso'");
		if ($req == -1)
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('ConvertLdapUtf8toIso', 'y');");
		$req = grr_sql_query1("SELECT VALUE FROM ".TABLE_PREFIX."_setting WHERE NAME='ActiveModeDiagnostic'");
		if ($req == -1)
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('ActiveModeDiagnostic', 'n');");
		$req = grr_sql_query1("SELECT VALUE FROM ".TABLE_PREFIX."_setting WHERE NAME='ldap_champ_nom'");
		if ($req == -1)
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('ldap_champ_nom', 'sn');");
		$req = grr_sql_query1("SELECT VALUE FROM ".TABLE_PREFIX."_setting WHERE NAME='ldap_champ_prenom'");
		if ($req == -1)
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('ldap_champ_prenom', 'givenname');");
		$req = grr_sql_query1("SELECT VALUE FROM ".TABLE_PREFIX."_setting WHERE NAME='ldap_champ_email'");
		if ($req == -1)
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('ldap_champ_email', 'mail');");
		$req = grr_sql_query1("SELECT VALUE FROM ".TABLE_PREFIX."_setting WHERE NAME='gestion_lien_aide'");
		if ($req == -1)
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('gestion_lien_aide', 'ext');");
		$req = grr_sql_query1("SELECT count(VALUE) FROM ".TABLE_PREFIX."_setting WHERE NAME='lien_aide'");
		if ($req == 0)
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('lien_aide', '');");
		$req = grr_sql_query1("SELECT VALUE FROM ".TABLE_PREFIX."_setting WHERE NAME='display_short_description'");
		if ($req == -1)
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('display_short_description', '1');");
		$req = grr_sql_query1("SELECT VALUE FROM ".TABLE_PREFIX."_setting WHERE NAME='remplissage_description_breve'");
		if ($req == -1)
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('remplissage_description_breve', '1');");
		$req1 = grr_sql_query1("SELECT VALUE FROM ".TABLE_PREFIX."_setting WHERE NAME='ldap_statut'");
		$req2 = grr_sql_query1("SELECT count(VALUE) FROM ".TABLE_PREFIX."_setting WHERE NAME='ldap_champ_recherche'");
		if ((($req1=="utilisateur") || ($req1=="visiteur")) and ($req2 == 0))
			$result_inter .= "<br /><span style=\"color:red;\"><b>AVERTISSEMENT</b> : suite à cette mise à jour, vous devez configurer l'<b>attribut utilisé pour la recherche dans l'annuaire ldap</b>. Pour cela, rendez-vous dans la page de configuration LDAP.</span><br />";
		if ($req2 == 0)
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('ldap_champ_recherche', 'uid');");
		$req = grr_sql_count(grr_sql_query("SHOW COLUMNS FROM ".TABLE_PREFIX."_entry LIKE 'beneficiaire'"));
		if ($req == 0)
		{
			$result_inter .= traiteRequete("ALTER TABLE `".TABLE_PREFIX."_entry` ADD beneficiaire VARCHAR( 100 ) NOT NULL AFTER `create_by`");
			$result_inter .= traiteRequete("UPDATE `".TABLE_PREFIX."_entry` SET `beneficiaire` = `create_by`");
			$result_inter .= traiteRequete("ALTER TABLE `".TABLE_PREFIX."_entry_moderate` ADD beneficiaire VARCHAR( 100 ) NOT NULL AFTER `create_by`");
			$result_inter .= traiteRequete("UPDATE `".TABLE_PREFIX."_entry_moderate` SET `beneficiaire` = `create_by`");
			$result_inter .= traiteRequete("ALTER TABLE `".TABLE_PREFIX."_repeat` ADD beneficiaire VARCHAR( 100 ) NOT NULL AFTER `create_by`");
			$result_inter .= traiteRequete("UPDATE `".TABLE_PREFIX."_repeat` SET `beneficiaire` = `create_by`");
			$result_inter .= traiteRequete("ALTER TABLE `".TABLE_PREFIX."_entry` ADD beneficiaire_ext VARCHAR( 200 ) NOT NULL AFTER `create_by`");
			$result_inter .= traiteRequete("ALTER TABLE `".TABLE_PREFIX."_entry_moderate` ADD beneficiaire_ext VARCHAR( 200 ) NOT NULL AFTER `create_by`");
			$result_inter .= traiteRequete("ALTER TABLE `".TABLE_PREFIX."_repeat` ADD beneficiaire_ext VARCHAR( 200 ) NOT NULL AFTER `create_by`");
		};
		$result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_room ADD qui_peut_reserver_pour VARCHAR( 1 ) DEFAULT '5' NOT NULL");
		if ($result_inter == '')
			$result .= "<span style=\"color:green;\">Ok !</span><br />";
		else
			$result .= $result_inter;
		$result_inter = '';
	}

	if (version_compare($version_old, "1.9.5", '<'))
	{
		$result .= "<b>Mise à jour jusqu'à la version 1.9.5 :</b><br />";
		// GRR1.9.5
		$result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_calendrier_jours_cycle CHANGE Jours Jours VARCHAR(20);");
		if (Settings::get("maj195_champ_rep_type_grr_repeat") != 1)
		{
			// Avant la version 195, la valeur 6 était utilisée pour le type "une semaine sur n"
				// et la valeur 7 pour la périodicité jour cycle
			$result_inter .= traiteRequete("UPDATE ".TABLE_PREFIX."_repeat SET rep_type = 2 WHERE rep_type = 6");
			$result_inter .= traiteRequete("UPDATE ".TABLE_PREFIX."_repeat SET rep_type = 6 WHERE rep_type = 7");
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('maj195_champ_rep_type_grr_repeat', '1')");
		}
		$result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_room ADD active_ressource_empruntee CHAR( 1 ) NOT NULL DEFAULT 'y'");
		$result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_overload ADD confidentiel CHAR( 1 ) NOT NULL DEFAULT 'n'");
		if ($result_inter == '')
			$result .= "<span style=\"color:green;\">Ok !</span><br />";
		else
			$result .= $result_inter;
		$result_inter = '';
	}

	if (version_compare($version_old, "1.9.6", '<'))
	{
		$result .= "<b>Mise à jour jusqu'à la version 1.9.6 :</b><br />";
		// GRR1.9.6
		$req = grr_sql_query1("SELECT VALUE FROM ".TABLE_PREFIX."_setting WHERE NAME='longueur_liste_ressources_max'");
		if ($req == -1)
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('longueur_liste_ressources_max', '20')");
		$req = grr_sql_query1("SELECT VALUE FROM ".TABLE_PREFIX."_setting WHERE NAME='acces_fiche_reservation'");
		if ($req == -1)
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('acces_fiche_reservation', '0')");
		$req = grr_sql_query1("SELECT VALUE FROM ".TABLE_PREFIX."_setting WHERE NAME='display_level_email'");
		if ($req == -1)
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('display_level_email', '0')");
		$req = grr_sql_query1("SELECT VALUE FROM ".TABLE_PREFIX."_setting WHERE NAME='nb_calendar'");
		if ($req == -1)
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('nb_calendar', '1')");
		$req = grr_sql_query1("SELECT count(VALUE) FROM ".TABLE_PREFIX."_setting WHERE NAME='default_site'");
		if ($req == 0)
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('default_site', '-1')");
		$req = grr_sql_query1("SELECT VALUE FROM ".TABLE_PREFIX."_setting WHERE NAME='envoyer_email_avec_formulaire'");
		if ($req == -1)
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('envoyer_email_avec_formulaire', 'no');");
		$result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_area ADD max_booking SMALLINT DEFAULT '-1' NOT NULL ;");
		$result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_area_periodes ADD primary key (`id_area`,`num_periode`) ;");
		$result_inter .= traiteRequete("CREATE TABLE IF NOT EXISTS ".TABLE_PREFIX."_site (id int(11) NOT NULL auto_increment, sitecode varchar(10) default NULL, sitename varchar(50) NOT NULL default '', adresse_ligne1 varchar(38) default NULL, adresse_ligne2 varchar(38) default NULL, adresse_ligne3 varchar(38) default NULL, cp varchar(5) default NULL, ville varchar(50) default NULL, pays varchar(50) default NULL, tel varchar(25) default NULL, fax varchar(25) default NULL, PRIMARY KEY (`id`));");
		$result_inter .= traiteRequete("CREATE TABLE IF NOT EXISTS ".TABLE_PREFIX."_j_site_area ( id_site int(11) NOT NULL default '0', id_area int(11) NOT NULL default '0', PRIMARY KEY  (`id_site`,`id_area`));");
		$result_inter .= traiteRequete("CREATE TABLE IF NOT EXISTS ".TABLE_PREFIX."_j_useradmin_site (login varchar(40) NOT NULL default '', id_site int(11) NOT NULL default '0', PRIMARY KEY  (login,id_site) );");
		$result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_utilisateurs ADD default_site SMALLINT(6) NOT NULL  default '0' AFTER etat;");
		$result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_room ADD who_can_see SMALLINT DEFAULT '0' NOT NULL;");
		if ($result_inter == '')
			$result .= "<span style=\"color:green;\">Ok !</span><br />";
		else
			$result .= $result_inter;
		$result_inter = '';
	}

	if (version_compare($version_old, "1.9.7", '<'))
	{
		$result .= "<b>Mise à jour jusqu'à la version 1.9.7 :</b><br />";
		// GRR1.9.7
		$result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_entry CHANGE create_by create_by VARCHAR( 100 ) NOT NULL  default '';");
		$result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_entry CHANGE beneficiaire beneficiaire VARCHAR( 100 ) NOT NULL  default '';");
		$result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_repeat CHANGE create_by create_by VARCHAR( 100 ) NOT NULL  default '';");
		$result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_repeat CHANGE beneficiaire beneficiaire VARCHAR( 100 ) NOT NULL  default '';");
		$result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_entry_moderate CHANGE create_by create_by VARCHAR( 100 ) NOT NULL  default '';");
		$result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_entry_moderate CHANGE beneficiaire beneficiaire VARCHAR( 100 ) NOT NULL  default '';");
		$result_inter .= traiteRequete("CREATE TABLE IF NOT EXISTS ".TABLE_PREFIX."_correspondance_statut (id int(11) NOT NULL auto_increment, code_fonction varchar(30) NOT NULL, libelle_fonction varchar(200) NOT NULL, statut_grr varchar(30) NOT NULL,  PRIMARY KEY (id));");
		$result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_type_area ADD disponible VARCHAR(1) NOT NULL DEFAULT '2'");
		$result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_room ADD show_comment CHAR(1) NOT NULL DEFAULT 'n' AFTER comment_room");
		if ($result_inter == '')
			$result .= "<span style=\"color:green;\">Ok !</span><br />";
		else
			$result .= $result_inter;
		$result_inter = '';
	}

	if (version_compare($version_old, "2.0.0", '<'))
	{
		$result .= "<b>Mise à jour jusqu'à la version 2.0.0 :</b><br />";
		$result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_entry ADD `clef` INT(2) NOT NULL DEFAULT '0' AFTER `jours`;");
		$result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_entry ADD `courrier` INT(2) NOT NULL DEFAULT '0' AFTER `clef`;");
		$result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_repeat ADD `courrier` INT(2) NOT NULL DEFAULT '0' AFTER `jours`;");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('menu_gauche', '1')");
		$result_inter .= traiteRequete("UPDATE ".TABLE_PREFIX."_utilisateurs SET `default_style` = 'default';");
		if ($result_inter == '')
			$result .= "<span style=\"color:green;\">Ok !</span><br />";
		else
			$result .= $result_inter;
		$result_inter = '';
	}

	if (version_compare($version_old, "3.0.0", '<'))
	{
		$result .= "<b>Mise à jour jusqu'à la version 3.0.0 :</b><br />";
	
		$result_inter .= traiteRequete("UPDATE ".TABLE_PREFIX."_setting SET VALUE = 1 WHERE NAME = 'nb_calendar';");
		$result_inter .= traiteRequete("UPDATE ".TABLE_PREFIX."_setting SET VALUE = 'item' WHERE NAME = 'area_list_format';");
		
		$req = grr_sql_query1("SELECT VALUE FROM ".TABLE_PREFIX."_setting WHERE NAME='default_css'");
		if ($req == -1){
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('default_css', 'bleu')");
		}else{
			$result_inter .= traiteRequete("UPDATE ".TABLE_PREFIX."_setting SET VALUE = 'bleu' WHERE NAME = 'default_css';");
		}
		
		$req2 = grr_sql_query1("SELECT VALUE FROM ".TABLE_PREFIX."_setting WHERE NAME='mail_destinataire'");
		if ($req2 == -1){
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('mail_destinataire', 'votreemail@adresse.xx')");
		}

		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('export_xml_actif', 'Non')");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('allow_pdf', 'y')");

		if ($result_inter == '')
			$result .= "<span style=\"color:green;\">Ok !</span><br />";
		else
			$result .= $result_inter;
		$result_inter = '';
	}

	if (version_compare($version_old, "3.1.0", '<'))
	{
		$result .= "<b>Mise à jour jusqu'à la version 3.1.0 :</b><br />";
	
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('export_xml_plus_actif', 'Non')");

		if ($result_inter == '')
			$result .= "<span style=\"color:green;\">Ok !</span><br />";
		else
			$result .= $result_inter;
		$result_inter = '';
	}
	
	if (version_compare($version_old, "3.2.0", '<'))
	{
		$result .= formatresult("Mise à jour jusqu'à la version 3.2.0 :","<b>","</b>");

		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('smtp_secure', '')");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('smtp_port', '25')");
		$result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_room ADD active_cle CHAR( 1 ) NOT NULL DEFAULT 'y' AFTER active_ressource_empruntee");

		if ($result_inter == '')
			$result .= formatresult("Ok !","<span style='color:green;'>","</span>");
		else
			$result .= $result_inter;
		$result_inter = '';
	}

	if (version_compare($version_old, "3.3.0", '<'))
	{
		$result .= formatresult("Mise à jour jusqu'à la version 3.3.0 :","<b>","</b>");

		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting (`NAME`, `VALUE`) VALUES ('periodicite', 'y')");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting (`NAME`, `VALUE`) VALUES ('remplissage_description_complete', '0')");
		$result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_overload CHANGE `fieldname` `fieldname` VARCHAR(55) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT '';");

		if ($result_inter == '')
			$result .= formatresult("Ok !","<span style='color:green;'>","</span>");
		else
			$result .= $result_inter;
		$result_inter = '';
	}

	if (version_compare($version_old, "3.3.1", '<'))
	{
		$result .= formatresult("Mise à jour jusqu'à la version 3.3.1 :","<b>","</b>");

		$result_inter .= traiteRequete("CREATE TABLE IF NOT EXISTS ".TABLE_PREFIX."_calendrier_vacances (`DAY` int(11) NOT NULL DEFAULT '0');");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting (`NAME`, `VALUE`) VALUES ('cas_port', '')");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting (`NAME`, `VALUE`) VALUES ('cas_racine', '')");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting (`NAME`, `VALUE`) VALUES ('cas_serveur', '')");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting (`NAME`, `VALUE`) VALUES ('ip_autorise', '')");
		$result_inter .= traiteRequete("CREATE TABLE IF NOT EXISTS ".TABLE_PREFIX."_calendrier_feries (`DAY` int(11) NOT NULL DEFAULT '0');");
		
		if ($result_inter == '')
			$result .= formatresult("Ok !","<span style='color:green;'>","</span>");
		else
			$result .= $result_inter;
		$result_inter = '';
	}

	if (version_compare($version_old, "3.4.0", '<'))
	{
		$result .= formatresult("Mise à jour jusqu'à la version 3.4.0 :","<b>","</b>");

		$result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_type_area ADD `couleurhexa` VARCHAR(10) NOT NULL AFTER `couleur`;");
		$result_inter .= traiteRequete("UPDATE ".TABLE_PREFIX."_type_area SET `couleurhexa` = '#F49AC2' WHERE couleur = '1';");
		$result_inter .= traiteRequete("UPDATE ".TABLE_PREFIX."_type_area SET `couleurhexa` = '#99CCCC' WHERE couleur = '2';");
		$result_inter .= traiteRequete("UPDATE ".TABLE_PREFIX."_type_area SET `couleurhexa` = '#FF9999' WHERE couleur = '3';");
		$result_inter .= traiteRequete("UPDATE ".TABLE_PREFIX."_type_area SET `couleurhexa` = '#95a5a6' WHERE couleur = '4';");
		$result_inter .= traiteRequete("UPDATE ".TABLE_PREFIX."_type_area SET `couleurhexa` = '#C0E0FF' WHERE couleur = '5';");
		$result_inter .= traiteRequete("UPDATE ".TABLE_PREFIX."_type_area SET `couleurhexa` = '#FFCC99' WHERE couleur = '6';");
		$result_inter .= traiteRequete("UPDATE ".TABLE_PREFIX."_type_area SET `couleurhexa` = '#e74c3c' WHERE couleur = '7';");
		$result_inter .= traiteRequete("UPDATE ".TABLE_PREFIX."_type_area SET `couleurhexa` = '#3498db' WHERE couleur = '8';");
		$result_inter .= traiteRequete("UPDATE ".TABLE_PREFIX."_type_area SET `couleurhexa` = '#DDFFDD' WHERE couleur = '9';");
		$result_inter .= traiteRequete("UPDATE ".TABLE_PREFIX."_type_area SET `couleurhexa` = '#34495e' WHERE couleur = '10';");
		$result_inter .= traiteRequete("UPDATE ".TABLE_PREFIX."_type_area SET `couleurhexa` = '#2ecc71' WHERE couleur = '11';");
		$result_inter .= traiteRequete("UPDATE ".TABLE_PREFIX."_type_area SET `couleurhexa` = '#9b59b6' WHERE couleur = '12';");
		$result_inter .= traiteRequete("UPDATE ".TABLE_PREFIX."_type_area SET `couleurhexa` = '#f1c40f' WHERE couleur = '13';");
		$result_inter .= traiteRequete("UPDATE ".TABLE_PREFIX."_type_area SET `couleurhexa` = '#FF00DE' WHERE couleur = '14';");
		$result_inter .= traiteRequete("UPDATE ".TABLE_PREFIX."_type_area SET `couleurhexa` = '#009900' WHERE couleur = '15';");
		$result_inter .= traiteRequete("UPDATE ".TABLE_PREFIX."_type_area SET `couleurhexa` = '#e67e22' WHERE couleur = '16';");
		$result_inter .= traiteRequete("UPDATE ".TABLE_PREFIX."_type_area SET `couleurhexa` = '#bdc3c7' WHERE couleur = '17';");
		$result_inter .= traiteRequete("UPDATE ".TABLE_PREFIX."_type_area SET `couleurhexa` = '#C000FF' WHERE couleur = '18';");
		$result_inter .= traiteRequete("UPDATE ".TABLE_PREFIX."_type_area SET `couleurhexa` = '#FF0000' WHERE couleur = '19';");
		$result_inter .= traiteRequete("UPDATE ".TABLE_PREFIX."_type_area SET `couleurhexa` = '#FFFFFF' WHERE couleur = '20';");
		$result_inter .= traiteRequete("UPDATE ".TABLE_PREFIX."_type_area SET `couleurhexa` = '#A0A000' WHERE couleur = '21';");
		$result_inter .= traiteRequete("UPDATE ".TABLE_PREFIX."_type_area SET `couleurhexa` = '#f39c12' WHERE couleur = '22';");
		$result_inter .= traiteRequete("UPDATE ".TABLE_PREFIX."_type_area SET `couleurhexa` = '#1abc9c' WHERE couleur = '23';");
		$result_inter .= traiteRequete("UPDATE ".TABLE_PREFIX."_type_area SET `couleurhexa` = '#884DA7' WHERE couleur = '24';");
		$result_inter .= traiteRequete("UPDATE ".TABLE_PREFIX."_type_area SET `couleurhexa` = '#4169E1' WHERE couleur = '25';");
		$result_inter .= traiteRequete("UPDATE ".TABLE_PREFIX."_type_area SET `couleurhexa` = '#6A5ACD' WHERE couleur = '26';");
		$result_inter .= traiteRequete("UPDATE ".TABLE_PREFIX."_type_area SET `couleurhexa` = '#AA5050' WHERE couleur = '27';");
		$result_inter .= traiteRequete("UPDATE ".TABLE_PREFIX."_type_area SET `couleurhexa` = '#FFBB20' WHERE couleur = '28';");
		$result_inter .= traiteRequete("UPDATE ".TABLE_PREFIX."_type_area SET `couleurhexa` = '#CFCFCF' WHERE couleur > '28';");
		$result_inter .= traiteRequete("CREATE TABLE IF NOT EXISTS ".TABLE_PREFIX."_page (`nom` varchar(30) NOT NULL, `valeur` longtext NOT NULL);");
    $result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_page ADD UNIQUE KEY `nom` (`nom`);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page (`nom`, `valeur`) VALUES ('CGU', 'Les CGU') ON DUPLICATE KEY UPDATE `valeur`='Les CGU';");
		$result_inter .= traiteRequete("CREATE TABLE IF NOT EXISTS ".TABLE_PREFIX."_modulesext (`nom` varchar(50) NOT NULL, `actif` tinyint(1) NOT NULL DEFAULT '0', `version` INT(11) NOT NULL);");
		$result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_modulesext ADD UNIQUE KEY `nom` (`nom`);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting (`NAME`, `VALUE`) VALUES ('imprimante', '0')");

		if ($result_inter == '')
			$result .= formatresult("Ok !","<span style='color:green;'>","</span>");
		else
			$result .= $result_inter;
		$result_inter = '';
	}
    if (version_compare($version_old, "3.4.1", '<'))
    {
        $result .= formatresult("Mise à jour jusqu'à la version 3.4.1 :","<b>","</b>");

        $result_inter .= traiteRequete('ALTER TABLE '.TABLE_PREFIX.'_type_area  ADD `couleur_texte` VARCHAR(10) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT \'#000000\'  AFTER `disponible`');

        if ($result_inter == '')
            $result .= formatresult("Ok !","<span style='color:green;'>","</span>");
        else
            $result .= $result_inter;
        $result_inter = '';
    }
    if (version_compare($version_old, "3.4.2", '<'))
    {
        $result .= formatresult("Mise à jour jusqu'à la version 3.4.2 :","<b>","</b>");
        
        $result_inter .= traiteRequete("UPDATE ".TABLE_PREFIX."_setting SET `NAME` = 'nombre_jours_Jours_Cycles' WHERE `NAME` = 'nombre_jours_Jours/Cycles';");
        $result_inter .= traiteRequete("UPDATE ".TABLE_PREFIX."_setting SET `NAME` = 'jour_debut_Jours_Cycles' WHERE `NAME` = 'jour_debut_Jours/Cycles';");
        $result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_utilisateurs ADD `changepwd` TINYINT(1) NOT NULL DEFAULT '0' AFTER `password`;");
        $result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_type_area CHANGE `couleur_texte` `couleurtexte` VARCHAR(10) NOT NULL DEFAULT '#000000' AFTER `couleurhexa`;");
        $result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_overload ADD `mail_spec` TEXT NOT NULL AFTER `overload_mail`;");
        $result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_overload DROP PRIMARY KEY, ADD PRIMARY KEY (`id_area`,`fieldname`), ADD UNIQUE KEY `id` (`id`);");
        $result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_site ADD UNIQUE KEY `name` (`sitename`), ADD UNIQUE KEY `code` (`sitecode`);");
        $result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_room ADD `booking_range` SMALLINT(6) NOT NULL DEFAULT '-1' AFTER `who_can_see`;");
        $result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_room ADD `who_can_book` TINYINT(1) NOT NULL DEFAULT '1' AFTER `booking_range`;");
        $result_inter .= traiteRequete("CREATE TABLE IF NOT EXISTS ".TABLE_PREFIX."_j_userbook_room (`login` varchar(40) NOT NULL, `id_room` int(11) NOT NULL) DEFAULT CHARSET=latin1;");

        if ($result_inter == '')
            $result .= formatresult("Ok !","<span style='color:green;'>","</span>");
        else
            $result .= $result_inter;
        $result_inter = '';
    }
    
    if (version_compare($version_old, "3.4.3", '<'))
    {
        // conversion de la valeur par défaut des champs START et END de la table grr_log (ne devrait être utile que pour des bases converties depuis d'anciennes versions)
        $result .= formatResult("Mise à jour de la table grr_log:","<b>","</b>");
        $result_inter .= traiteRequete("ALTER TABLE `".TABLE_PREFIX."_log` CHANGE `START` `START` DATETIME NOT NULL DEFAULT '1970-01-01 00:00:00', CHANGE `END` `END` DATETIME NOT NULL DEFAULT '1970-01-01 00:00:00';");
        if ($result_inter == '')
            $result .= formatresult("Ok !","<span style='color:green;'>","</span>");
        else
            $result .= $result_inter;
        // valeur par défaut du TIMESTAMP dans grr_entry_moderate (ne devrait être utile que pour des bases converties depuis d'anciennes versions)
        $result .= formatResult("Mise à jour de la table grr_entry_moderate:","<b>","</b>");
        $result_inter .= traiteRequete("ALTER TABLE `".TABLE_PREFIX."_entry_moderate` CHANGE `TIMESTAMP` `TIMESTAMP` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;");
        if ($result_inter == '')
            $result .= formatresult("Ok !","<span style='color:green;'>","</span>");
        else
            $result .= $result_inter;
        
        $result .= formatresult("Mise à jour jusqu'à la version 3.4.3 RC0:","<b>","</b>");
        $result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_log CHANGE `REMOTE_ADDR` `REMOTE_ADDR` VARCHAR(40) NOT NULL DEFAULT ''");
        $result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_room ADD `active_participant` TINYINT(1) NOT NULL DEFAULT '0' AFTER `active_cle`;");
		$result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_entry ADD `nbparticipantmax` int(11) NOT NULL DEFAULT '0' AFTER `courrier`;");
		$result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_repeat ADD `nbparticipantmax` int(11) NOT NULL DEFAULT '0' AFTER `courrier`;");
        $result_inter .= traiteRequete("CREATE TABLE IF NOT EXISTS ".TABLE_PREFIX."_participants (idresa int(11) NOT NULL, participant varchar(200) NOT NULL, PRIMARY KEY  (idresa,participant));");
        
        if ($result_inter == '')
            $result .= formatresult("Ok !","<span style='color:green;'>","</span>");
        else
            $result .= $result_inter;
        $result_inter = '';
    }
    if ($version_old < "3.5.0.9")
    {
        $result .= formatResult("Mise à jour jusqu'à la version 3.5.0 RC0:","<b>","</b>");
        include "./fonctions/ISO_to_UTF8.inc.php";
        $result_inter = '';
    }
    if($version_old < "3.5.1.9")
    {   
        $result .= formatResult("Mise à jour jusqu'à la version 3.5.1:","<b>","</b>");

        $result_inter .= traiteRequete("ALTER TABLE `".TABLE_PREFIX."_utilisateurs` CHANGE `password` `password` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' ;");
        // modification des champs login, create_by et beneficiaire en varchar(190) pour compatibilité avec certains SSO
        $result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_j_mailuser_room CHANGE login login varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL default '' ;");
        $result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_j_user_area CHANGE login login varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL default '' ;");
        $result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_j_user_room CHANGE login login varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL default '' ;");
        $result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_j_userbook_room CHANGE login login varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL default '' ;");
        $result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_j_useradmin_area CHANGE login login varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL default '' ;");
        $result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_log CHANGE LOGIN LOGIN varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL default '' ");
        $result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_entry CHANGE create_by create_by varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL default '' ;");
        $result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_entry CHANGE beneficiaire beneficiaire varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL default '' ;");
        $result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_repeat CHANGE create_by create_by varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL default '' ;");
        $result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_repeat CHANGE beneficiaire beneficiaire varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL default '' ;");
        $result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_utilisateurs CHANGE login login varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL default '' ;");
        $result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_entry_moderate CHANGE login_moderateur login_moderateur varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL default '' ;");
        $result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_entry_moderate CHANGE create_by create_by varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL default '' ;");
        $result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_entry_moderate CHANGE beneficiaire beneficiaire varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL default '' ;");
        $result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_j_useradmin_site CHANGE login login varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL default '' ;");

        if ($result_inter == '')
            $result .= formatResult("Ok !","<span style='color:green;'>","</span>");
        else
            $result .= $result_inter;
        $result_inter = '';
    }
 

	// Vérification du format des champs additionnels
	// Avant version 1.9.4, les champs add étaient stockés sous la forme <id_champ>champ_encode_en_base_64</id_champ>
	// A partir de la version 1.9.4, les champs add. sont stockés sous la forme @id_champ@url_encode(champ)@/id_champ@
	if (($version_old < "1.9.4") && (Settings::get("maj194_champs_additionnels") != 1) && isset($_POST['maj']))
	{
	// On construit un tableau des id des ".TABLE_PREFIX."_overload:
		$sql_overload = grr_sql_query("SELECT id FROM ".TABLE_PREFIX."_overload");
		for ($i = 0; ($row = grr_sql_row($sql_overload, $i)); $i++)
			$tab_id_overload[] = $row[0];
		// On selectionne les entrées
		$sql_entry = grr_sql_query("SELECT overload_desc, id FROM ".TABLE_PREFIX."_entry WHERE overload_desc != ''");
		for ($i = 0; ($row = grr_sql_row($sql_entry, $i)); $i++)
		{
			$nouvelle_chaine = "";
			foreach ($tab_id_overload as $value)
			{
				$begin_string = "<".$value.">";
				$end_string = "</".$value.">";
				$begin_pos = strpos($row[0],$begin_string);
				$end_pos = strpos($row[0],$end_string);
				if ( $begin_pos !== false && $end_pos !== false )
				{
					$first = $begin_pos + strlen($begin_string);
					$data = substr($row[0],$first,$end_pos-$first);
					$data  = urlencode(base64_decode($data));
					$nouvelle_chaine .= "@".$value."@".$data."@/".$value."@";
				}
			}
			// On met à jour le champ
			if ($nouvelle_chaine != '')
				$up = grr_sql_query("UPDATE ".TABLE_PREFIX."_entry set overload_desc = '".$nouvelle_chaine."' where id='".$row[1]."'");
		}
		// on inscrit le résultat dans la table ".TABLE_PREFIX."_settings
		grr_sql_query("DELETE from ".TABLE_PREFIX."_setting where NAME = 'maj194_champs_additionnels'");
		grr_sql_query("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('maj194_champs_additionnels', '1');");
		$result .= "<b>Mise à jour des champs additionnels : </b><span style=\"color:green;\">Ok !</span><br /><br />";
	}

	// Mise à jour du champ "qui_peut_reserver_pour
	// La version 1.9.6 a introduit un niveau supplémentaire pour le champ qui_peut_reserver_pour, ce qui oblige à un décalage : les niveaux 5 deviennent des niveaux 6
	if (($version_old < "1.9.6") && (Settings::get("maj196_qui_peut_reserver_pour") != 1) && (isset($_POST['maj']) ))
	{
		// On met à jour le champ
		$up = grr_sql_query("UPDATE ".TABLE_PREFIX."_room set qui_peut_reserver_pour='6' where qui_peut_reserver_pour='5'");
		grr_sql_query("DELETE from ".TABLE_PREFIX."_setting where NAME = 'maj196_qui_peut_reserver_pour'");
		grr_sql_query("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('maj196_qui_peut_reserver_pour', '1');");
		$result .= "<b>Mise à jour du champs qui_peut_reserver_pour : </b><span style=\"color:green;\">Ok !</span><br /><br />";
	}

	// Mise à jour du numéro de version
	$req = grr_sql_query1("SELECT VALUE FROM ".TABLE_PREFIX."_setting WHERE NAME='version'");
	if ($req == -1)
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('version', '".$version_grr."');");
	else
		$result_inter .= traiteRequete("UPDATE ".TABLE_PREFIX."_setting SET VALUE='".$version_grr."' WHERE NAME='version';");

	$result .= $result_inter;

	return $result;
}

function execute_maj4($version_old_bdd, $version_grr_bdd)
{
	global $liste_tables, $table_prefix; // Pour X_to_UTF8

	$result = '';
	$result_inter = '';

	include "./fonctions/X_to_UTF8.inc.php";

	// On commence la mise à jour 
	if (intval($version_old_bdd) < 400001) // Version GRR 4.0.0 Béta
	{
		$result .= formatresult("Mise à jour jusqu'à la version 4.0.0 :","<b>","</b>");

		$result_inter .= traiteRequete("DELETE FROM ".TABLE_PREFIX."_setting WHERE NAME='versionRC'");
		$result_inter .= traiteRequete("INSERT IGNORE INTO ".TABLE_PREFIX."_setting (`NAME`, `VALUE`) VALUES ('display_beneficiaire', '0')");
		$result_inter .= traiteRequete("INSERT IGNORE INTO ".TABLE_PREFIX."_setting (`NAME`, `VALUE`) VALUES ('display_type', '1')");
		$result_inter .= traiteRequete("CREATE TABLE IF NOT EXISTS ".TABLE_PREFIX."_log_mail (`idlogmail` int(11) NOT NULL AUTO_INCREMENT, `date` int(11) NOT NULL, `de` VARCHAR(184) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL, `a` VARCHAR(184) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL, `sujet` VARCHAR(184) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL, `message` text NOT NULL, PRIMARY KEY (`idlogmail`));");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting (`NAME`, `VALUE`) VALUES ('smtp_allow_self_signed', 'false')");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting (`NAME`, `VALUE`) VALUES ('smtp_verify_peer_name', 'true')");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting (`NAME`, `VALUE`) VALUES ('smtp_verify_peer', 'true')");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting (`NAME`, `VALUE`) VALUES ('smtp_verify_depth', '3')");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting (`NAME`, `VALUE`) VALUES ('backup_date', '')");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting (`NAME`, `VALUE`) VALUES ('mail_user_destinataire', 'y')");
		$result_inter .= traiteRequete("INSERT IGNORE INTO ".TABLE_PREFIX."_setting (`NAME`, `VALUE`) VALUES ('cas_version', 'CAS_VERSION_2_0')");
		$result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_utilisateurs CHANGE `password` `password` VARCHAR(184) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '';");
        $result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_participants ADD `id_participation` INT(11) NOT NULL AUTO_INCREMENT FIRST, ADD UNIQUE `UNIQUE` (`id_participation`)"); 
        $result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_participants CHANGE IF EXISTS `participant` `beneficiaire` VARCHAR(184) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL"); 
        $result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_participants ADD `timestamp` TIMESTAMP on update CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `idresa`, ADD `cree_par` VARCHAR(184) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL AFTER `timestamp`");
        $result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_participants ADD `beneficiaire_ext` VARCHAR(184) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL AFTER `beneficiaire`, ADD `moderation` TINYINT(1) NOT NULL DEFAULT '0' AFTER `beneficiaire_ext`");
		$result_inter .= traiteRequete("CREATE TABLE IF NOT EXISTS ".TABLE_PREFIX."_log_resa (idlogresa bigint(20) NOT NULL AUTO_INCREMENT, date int(11) NOT NULL, idresa int(11) NOT NULL, identifiant varchar(100) NOT NULL, action tinyint(3) UNSIGNED NOT NULL, infoscomp text NOT NULL, PRIMARY KEY (`idlogresa`));");
		$result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_entry ADD `supprimer` TINYINT(1) NOT NULL DEFAULT '0' AFTER `nbparticipantmax`;");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting (`NAME`, `VALUE`) VALUES ('horaireconnexionde', '')");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting (`NAME`, `VALUE`) VALUES ('horaireconnexiona', '')");
		$result_inter .= traiteRequete("CREATE TABLE IF NOT EXISTS ".TABLE_PREFIX."_groupes (`idgroupes` int(11) NOT NULL AUTO_INCREMENT, `nom` VARCHAR(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL, description text NOT NULL, `archive` tinyint(1) NOT NULL DEFAULT '0', PRIMARY KEY (`idgroupes`));");
		$result_inter .= traiteRequete("CREATE TABLE IF NOT EXISTS ".TABLE_PREFIX."_utilisateurs_groupes (`idutilisateursgroupes` bigint(20) NOT NULL AUTO_INCREMENT, `login` VARCHAR(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL, `idgroupes` int(11) NOT NULL, PRIMARY KEY (`idutilisateursgroupes`), UNIQUE KEY `idutilisateurs` (`login`,`idgroupes`));");
		$result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_utilisateurs CHANGE `default_language` `default_language` CHAR(8);");
		$result_inter .= traiteRequete("UPDATE ".TABLE_PREFIX."_setting SET VALUE = 'fr-fr' WHERE NAME = 'default_language' AND VALUE = 'fr';");
		$result_inter .= traiteRequete("UPDATE ".TABLE_PREFIX."_setting SET VALUE = 'en-gb' WHERE NAME = 'default_language' AND VALUE = 'en';");
		$result_inter .= traiteRequete("UPDATE ".TABLE_PREFIX."_setting SET VALUE = 'es-es' WHERE NAME = 'default_language' AND VALUE = 'es';");
		$result_inter .= traiteRequete("UPDATE ".TABLE_PREFIX."_setting SET VALUE = 'it-it' WHERE NAME = 'default_language' AND VALUE = 'it';");
		$result_inter .= traiteRequete("UPDATE ".TABLE_PREFIX."_setting SET VALUE = 'de-de' WHERE NAME = 'default_language' AND VALUE = 'de';");
		$result_inter .= traiteRequete("UPDATE ".TABLE_PREFIX."_utilisateurs SET default_language = 'fr-fr' WHERE default_language = 'fr';");
		$result_inter .= traiteRequete("UPDATE ".TABLE_PREFIX."_utilisateurs SET default_language = 'en-gb' WHERE default_language = 'en';");
		$result_inter .= traiteRequete("UPDATE ".TABLE_PREFIX."_utilisateurs SET default_language = 'es-es' WHERE default_language = 'es';");
		$result_inter .= traiteRequete("UPDATE ".TABLE_PREFIX."_utilisateurs SET default_language = 'it-it' WHERE default_language = 'it';");
		$result_inter .= traiteRequete("UPDATE ".TABLE_PREFIX."_utilisateurs SET default_language = 'de-de' WHERE default_language = 'de';");
		

		//include "./ISO_to_UTF8.inc.php";

		if ($result_inter == '')
			$result .= formatresult("Ok !","<span style='color:green;'>","</span>");
		else
			$result .= $result_inter;
		$result_inter = '';
	}

	// On commence la mise à jour 
	if (intval($version_old_bdd) < 400002) // Version GRR 4.1.0 >= RC1
	{
		
		$result .= formatresult("Mise à jour jusqu'à la version 4.1.0 :","<b>","</b>");

		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting (`NAME`, `VALUE`) VALUES ('select_date_directe', 'y')");

		if ($result_inter == '')
			$result .= formatresult("Ok !","<span style='color:green;'>","</span>");
		else
			$result .= $result_inter;
		$result_inter = '';
	}

	if (intval($version_old_bdd) < 400003) // Version GRR 4.2.0 >= RC1
	{
		
		$result .= formatresult("Mise à jour jusqu'à la version 4.2.0 :","<b>","</b>");

		$result_inter .= traiteRequete("CREATE TABLE IF NOT EXISTS ".TABLE_PREFIX."_utilisateurs_demandes (`idutilisateursdemandes` bigint(20) NOT NULL AUTO_INCREMENT, `nom` varchar(30) NOT NULL, `prenom` varchar(30) NOT NULL, `email` varchar(100) NOT NULL, `telephone` varchar(20) NOT NULL, `mdp` varchar(184) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL, `commentaire` text NOT NULL, `datedemande` date NOT NULL, `etat` tinyint(1) NOT NULL DEFAULT 0, `gestionnaire` varchar(40) NOT NULL DEFAULT '', `datechoix` date DEFAULT NULL, PRIMARY KEY (`idutilisateursdemandes`))  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
		

		$ctncgu = grr_sql_query1("SELECT valeur FROM ".TABLE_PREFIX."_page WHERE nom='CGU'");
		
		$result_inter .= 

		$result_inter .= traiteRequete("DROP TABLE IF EXISTS ".TABLE_PREFIX."_page;");
		$result_inter .= traiteRequete("CREATE TABLE ".TABLE_PREFIX."_page ( nom varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL, titre varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '', valeur longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL, PRIMARY KEY  (`nom`), systeme tinyint(1) NOT NULL DEFAULT '0');");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES ('cgu', 'CGU', 'Les CGU', 0);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_resacreation2_de-de', 'GRR: Meldung %ressource% - %resadatedebut% - Neue Reservierung %enattentemoderation%', '<p>%nometablissement% - Automatische Nachricht von der GRR Webseite : %urlgrr%</p><p>Benutzer %logincompletuser% für Sie reserviert hat %ressource% (%domaine%)</p><br><p>Beginn der Reservierung : %resadatedebut%<br></p><p>Dauer : %resaduree%</p><p>%resanom%<br></p><p>%resadescription%<br></p><p>%resachampsadditionnels%</p><p>Art : %resatype%</p><p>%resaperiodique%</p>%resaconfirmation%<p></p><p></p><p><br></p><p>------</p><p>%raisonmail%<br></p>Wenn\r\n sie diese automatische Nachricht nicht mehr erhalten wollen, wenden Sie\r\n sich bitte an Ihren GRR-Systemverwalter : %webmasteremail%<p></p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_resacreation2_en-gb', 'GRR : notice %ressource% - %resadatedebut% - New reservation %enattentemoderation%', '<p>%nometablissement% - Automatic message emitted by GRR site : %urlgrr%</p><p>The user %logincompletuser% has reserved for you %ressource% (%domaine%)</p><br><p>Reservation will start : %resadatedebut%<br></p><p>Duration : %resaduree%</p><p>%resanom%<br></p><p>%resadescription%<br></p><p>%resachampsadditionnels%</p><p>Type : %resatype%</p><p>%resaperiodique%</p>%resaconfirmation%<p></p><p></p><p><br></p><p>------</p><p>%raisonmail%<br></p>If you no longer wish to get these automatic mails, write to GRR manager : %webmasteremail%<p></p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_resacreation2_es-es', 'GRR : opinión %ressource% - %resadatedebut% - Nueva reserva %enattentemoderation%', '<p>%nometablissement% - Mensaje automático emitido por el GRR : %urlgrr%</p><p>El usuario %logincompletuser% ha reservado para ti %ressource% (%domaine%)</p><br><p>Principio de la reserva : %resadatedebut%<br></p><p>Duración : %resaduree%</p><p>%resanom%<br></p><p>%resadescription%<br></p><p>%resachampsadditionnels%</p><p>Tipo : %resatype%</p><p>%resaperiodique%</p>%resaconfirmation%<p></p><p></p><p><br></p><p>------</p><p>%raisonmail%<br></p>Si usted no desea recibir estos mensajes automáticos, escriba al gestor de GRR : %webmasteremail%<p></p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_resacreation2_fr-fr', 'GRR : avis %ressource% - %resadatedebut% - Nouvelle réservation %enattentemoderation%', '<p><p>%nometablissement% - Message automatique émis par le site GRR : %urlgrr%</p>L\'utilisateur %logincompletuser%&nbsp; a réservé pour vous %ressource% (%domaine%) </p><p><p><br></p><p>Début de la réservation : %resadatedebut%<br></p><p>Durée : %resaduree%</p><p>%resanom%<br></p><p>%resadescription%<br></p><p>%resachampsadditionnels%</p><p>Type : %resatype%</p><p>%resaperiodique%</p>%resaconfirmation%</p><p><p><br></p><p>------<br></p>Si vous ne souhaitez plus recevoir ces messages automatiques, écrivez en ce sens au gestionnaire de GRR : %webmasteremail%<p></p><p></p><p></p><p></p></p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_resacreation2_it-it', 'GRR : opinione %ressource% - %resadatedebut% - Nuova prenotazione %enattentemoderation%', '<p>%nometablissement% - Messaggio automatico emesso dal sito GRR : %urlgrr%</p><p>L\'utente %logincompletuser% ti ha riservato %ressource% (%domaine%)</p><br><p>Inizio della prenotazione : %resadatedebut%<br></p><p>Durata : %resaduree%</p><p>%resanom%<br></p><p>%resadescription%<br></p><p>%resachampsadditionnels%</p><p>Tipo : %resatype%</p><p>%resaperiodique%</p>%resaconfirmation%<p></p><p></p><p><br></p><p>------</p><p>%raisonmail%<br></p>Se non desiderate ricevere più questi messaggi automatici, scrivete in questo senso all\'amministratore di GRR : %webmasteremail%<p></p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_resacreation_de-de', 'GRR: Meldung %ressource% - %resadatedebut% - Neue Reservierung %enattentemoderation%', '<p><p>%nometablissement% - Automatische Nachricht von der GRR Webseite : %urlgrr%</p><p>Benutzer %logincompletuser% hat reserviert %ressource% (%domaine%)</p><p><br></p><p>Reservierung im Namen von&nbsp; %logincompletbeneficiaire%</p><p>Beginn der Reservierung : %resadatedebut%<br></p><p>Dauer : %resaduree%</p><p>%resanom%<br></p><p>%resadescription%<br></p><p>%resachampsadditionnels%</p><p>Art : %resatype%</p><p>%resaperiodique%</p>%resaconfirmation%</p><p><p><br></p><p>------</p><p>%raisonmail%<br></p>Wenn sie diese automatische Nachricht nicht mehr erhalten wollen, wenden Sie sich bitte an Ihren GRR-Systemverwalter : %webmasteremail%<p></p></p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_resacreation_en-gb', 'GRR : notice %ressource% - %resadatedebut% - New reservation %enattentemoderation%', '<p><p>%nometablissement% - Automatic message emitted by GRR site : %urlgrr%</p><p>The user %logincompletuser% has reserved %ressource% (%domaine%)</p><p><br></p><p>Reservation in the name of %logincompletbeneficiaire%</p><p>Reservation will start : %resadatedebut%<br></p><p>Duration : %resaduree%</p><p>%resanom%<br></p><p>%resadescription%<br></p><p>%resachampsadditionnels%</p><p>Type : %resatype%</p><p>%resaperiodique%</p>%resaconfirmation%</p><p><p><br></p><p>------</p><p>%raisonmail%<br></p>If you no longer wish to get these automatic mails, write to GRR manager : %webmasteremail%<p></p></p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_resacreation_es-es', 'GRR : opinión %ressource% - %resadatedebut% - Nueva reserva %enattentemoderation%', '<p><p>%nometablissement% - Mensaje automático emitido por el GRR : %urlgrr%</p><p>El usuario %logincompletuser% ha reservado %ressource% (%domaine%)</p><p><br></p><p>Reserva por cuenta del usuario : %logincompletbeneficiaire%</p><p>Principio de la reserva : %resadatedebut%<br></p><p>Duración : %resaduree%</p><p>%resanom%<br></p><p>%resadescription%<br></p><p>%resachampsadditionnels%</p><p>Tipo : %resatype%</p><p>%resaperiodique%</p>%resaconfirmation%</p><p><p><br></p><p>------</p><p>%raisonmail%<br></p>Si usted no desea recibir estos mensajes automáticos, escriba al gestor de GRR : %webmasteremail%<p></p></p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_resacreation_fr-fr', 'GRR : avis %ressource% - %resadatedebut% - Nouvelle réservation %enattentemoderation%', '<p><p>%nometablissement% - Message automatique émis par le site GRR : %urlgrr%</p><p>L\'utilisateur %logincompletuser% a réservé %ressource% (%domaine%)</p><p><br></p><p>Réservation au nom de l\'utilisateur %logincompletbeneficiaire%</p><p>Début de la réservation : %resadatedebut%<br></p><p>Durée : %resaduree%</p><p>%resanom%<br></p><p>%resadescription%<br></p><p>%resachampsadditionnels%</p><p>Type : %resatype%</p><p>%resaperiodique%</p>%resaconfirmation%</p><p><p><br></p><p>------</p><p>%raisonmail%<br></p>Si vous ne souhaitez plus recevoir ces messages automatiques, écrivez en ce sens au gestionnaire de GRR : %webmasteremail%<p></p><p></p><p></p></p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_resacreation_it-it', 'GRR : opinione %ressource% - %resadatedebut% - Nuova prenotazione %enattentemoderation%', '<p>%nometablissement% - Messaggio automatico emesso dal sito GRR : %urlgrr%</p><p>L\'utente %logincompletuser% ha riservato %ressource% (%domaine%)</p><p><br></p><p>Prenotazione per conto dell\'utente : %logincompletbeneficiaire%</p><p>Inizio della prenotazione : %resadatedebut%<br></p><p>Durata : %resaduree%</p><p>%resanom%<br></p><p>%resadescription%<br></p><p>%resachampsadditionnels%</p><p>Tipo : %resatype%</p><p>%resaperiodique%</p>%resaconfirmation%<p></p><p></p><p><br></p><p>------</p><p>%raisonmail%<br></p>Se non desiderate ricevere più questi messaggi automatici, scrivete in questo senso all\'amministratore di GRR : %webmasteremail%<p></p><p></p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_resamoderation2_de-de', 'GRR: Meldung %ressource% - %resadatedebut% - Verarbeitung einer Reservierung im Warten von Moderation %enattentemoderation%', '<p>%nometablissement% - Automatische Nachricht von der GRR Webseite : %urlgrr%</p><p>Benutzer %logincompletuser% hat die Reservierungsanfrage für Ressource %ressource% (%domaine%) im Namen von Benutzer %logincompletbeneficiaire% verarbeitet</p><p><font style=\"background-color: rgb(255, 255, 0);\" color=\"#000000\">%decisionmoderation%</font></p><p>%decisionmotif%</p><p>Sehen Sie sich die Details an :</p><p>%urldetail%</p><p><br></p><p><u>Erinnerung an die Anfrage:</u><br></p><p></p><p>Reservierung im Namen von&nbsp; %logincompletbeneficiaire%</p><p>Beginn der Reservierung : %resadatedebut%<br></p><p>Dauer : %resaduree%</p><p>%resanom%<br></p><p>%resadescription%<br></p><p>%resachampsadditionnels%</p><p>Art : %resatype%</p><p><br></p><p></p><p>------</p><p>%raisonmail%<br></p>Wenn\r\n sie diese automatische Nachricht nicht mehr erhalten wollen, wenden Sie\r\n sich bitte an Ihren GRR-Systemverwalter : %webmasteremail%<p></p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_resamoderation2_en-gb', 'GRR : notice %ressource% - %resadatedebut% - Processing of a reservation waiting for a moderation', '<p>%nometablissement% - Automatic message emitted by GRR site : %urlgrr%</p><p>The user %logincompletuser% processed the resource booking request %ressource% (%domaine%) on behalf of the user %logincompletbeneficiaire%</p><p><font style=\"background-color: rgb(255, 255, 0);\" color=\"#000000\">%decisionmoderation%</font></p><p>%decisionmotif%</p><p>See the details :</p><p>%urldetail%</p><p><br><u>Reminder of the request :</u></p><p>Reservation on behalf of the user : %logincompletbeneficiaire%</p><p>Reservation will start : %resadatedebut%<br></p><p>Duration : %resaduree%</p><p>%resanom%<br></p><p>%resadescription%<br></p><p>%resachampsadditionnels%</p><p>Type : %resatype%</p><p><br></p><p></p><p>------</p><p>%raisonmail%<br></p>If you no longer wish to get these automatic mails, write to GRR manager : %webmasteremail%', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_resamoderation2_es-es', 'GRR : opinión %ressource% - %resadatedebut% - Tratamiento de una reserva en espera de moderación', '<p>%nometablissement% - Mensaje automático emitido por el GRR : %urlgrr%</p><p>El usuario %logincompletuser% procesó la solicitud de reserva del recurso %ressource% (%domaine%) en nombre del usuario %logincompletbeneficiaire%</p><p><font style=\"background-color: rgb(255, 255, 0);\" color=\"#000000\">%decisionmoderation%</font></p><p>%decisionmotif%</p><p>Ver los detalles :</p><p>%urldetail%</p><p></p><p><br></p><p><u>Recordatorio de la solicitud:</u><br></p><p>Reserva por cuenta del usuario : %logincompletbeneficiaire%</p><p>Principio de la reserva : %resadatedebut%<br></p><p>Duración : %resaduree%</p><p>%resanom%<br></p><p>%resadescription%<br></p><p>%resachampsadditionnels%</p><p>Tipo : %resatype%</p><br><p>------</p><p>%raisonmail%<br></p>Si usted no desea recibir estos mensajes automáticos, escriba al gestor de GRR : %webmasteremail%<p></p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_resamoderation2_fr-fr', 'GRR : avis %ressource% - %resadatedebut% - Traitement d\'une réservation en attente de modération', '<p>%nometablissement% - Message automatique émis par le site GRR : %urlgrr%</p><p>L\'utilisateur %logincompletuser%&nbsp; a traité la demande de réservation de  la ressource %ressource% (%domaine%) au nom de l\'utilisateur %logincompletbeneficiaire%</p><p><font style=\"background-color: rgb(255, 255, 0);\" color=\"#000000\">%decisionmoderation%</font></p><p>%decisionmotif%</p><p>Voir les détails :</p><p>%urldetail%</p><p><br></p><p><u>Rappel de la demande:</u><br></p><p>Réservation au nom de l\'utilisateur %logincompletbeneficiaire%</p><p>Début de la réservation : %resadatedebut%<br></p><p>Durée : %resaduree%</p><p>%resanom%<br></p><p>%resadescription%<br></p><p>%resachampsadditionnels%</p><p>Type : %resatype%</p><br><p>------</p><p>%raisonmail%</p>Si vous ne souhaitez plus recevoir ces messages automatiques, écrivez en ce sens au gestionnaire de GRR : %webmasteremail%<p></p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_resamoderation2_it-it', 'GRR : opinione %ressource% - %resadatedebut% - Trattamento di una prenotazione in attesa di moderazione', '<p>%nometablissement% - Messaggio automatico emesso dal sito GRR : %urlgrr%</p><p>L\'utente %logincompletuser% ha elaborato la richiesta di prenotazione per la risorsa %ressource% (%domaine%) per conto dell\'utente %logincompletbeneficiaire%</p><p><font style=\"background-color: rgb(255, 255, 0);\" color=\"#000000\">%decisionmoderation%</font></p><p>%decisionmotif%</p><p>Guarda i dettagli :</p><p>%urldetail%</p><p></p><p><br></p><p><u>Promemoria della richiesta :</u><br></p><p>Prenotato da %logincompletuser%</p><p>Inizio della prenotazione : %resadatedebut%<br></p><p>Durata : %resaduree%</p><p>%resanom%<br></p><p>%resadescription%<br></p><p>%resachampsadditionnels%</p><p>Tipo : %resatype%</p><p><br></p><p>------</p><p>%raisonmail%<br></p>Se non desiderate ricevere più questi messaggi automatici, scrivete in questo senso all\'amministratore di GRR : %webmasteremail%<p></p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_resamoderation3_de-de', 'GRR: Meldung %ressource% - %resadatedebut% - Reservierung ist im Warten von Moderation', '<p>%nometablissement% - Automatische Nachricht von der GRR Webseite : %urlgrr%</p>Ihre Anfrage steht zur Moderation aus.<br>Sie werden benachrichtigt, sobald der Ressourcenmanager eine Entscheidung trifft.<p><u>Erinnerung an die Anfrage:</u><br></p><p></p><p>Reservierung im Namen von&nbsp; %logincompletbeneficiaire%</p><p>Beginn der Reservierung : %resadatedebut%<br></p><p>Dauer : %resaduree%</p><p>%resanom%<br></p><p>%resadescription%<br></p><p>%resachampsadditionnels%</p><p>Art : %resatype%</p><p><br></p><p></p><p>------</p><p>%raisonmail%<br></p>Wenn\r\n sie diese automatische Nachricht nicht mehr erhalten wollen, wenden Sie\r\n sich bitte an Ihren GRR-Systemverwalter : %webmasteremail%<p></p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_resamoderation3_en-gb', 'GRR : notice %ressource% - %resadatedebut% - Reservation waiting for a moderation', '<p>%nometablissement% - Automatic message emitted by GRR site : %urlgrr%</p><p>Your request is pending moderation.</p><p>You will be notified as soon as the resource manager decides.</p><p><u>Reminder of the request:</u><br></p><p>Booked by %logincompletuser%</p><p>Reservation in the name of %logincompletbeneficiaire%</p><p>Reservation will start : %resadatedebut%<br></p><p>Duration : %resaduree%</p><p>%resanom%<br></p><p>%resadescription%<br></p><p>%resachampsadditionnels%</p><p>Type : %resatype%</p><p><br></p><p></p><p>------</p><p>%raisonmail%<br></p>If you no longer wish to get these automatic mails, write to GRR manager : %webmasteremail%<p></p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_resamoderation3_es-es', 'GRR : opinión %ressource% - %resadatedebut% - Reserva en espera de moderación', '<p>%nometablissement% - Mensaje automático emitido por el GRR : %urlgrr%</p>Su solicitud está pendiente de moderación.<br>Se le notificará tan pronto como el administrador de recursos decida.<p></p><p><br></p><p><u>Recordatorio de la solicitud:</u><br></p><p>Reserva por cuenta del usuario : %logincompletbeneficiaire%</p><p>Principio de la reserva : %resadatedebut%<br></p><p>Duración : %resaduree%</p><p>%resanom%<br></p><p>%resadescription%<br></p><p>%resachampsadditionnels%</p><p>Tipo : %resatype%</p><br><p>------</p><p>%raisonmail%<br></p>Si usted no desea recibir estos mensajes automáticos, escriba al gestor de GRR : %webmasteremail%<p></p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_resamoderation3_fr-fr', 'GRR : avis %ressource% - %resadatedebut% - Réservation en attente de modération', '<p>%nometablissement% - Message automatique émis par le site GRR : %urlgrr%</p><p>Votre demande est en attente de modération.</p><p>Vous serez notifié dès la décision du gestionnaire de la ressource.</p><p><br></p><p><u>Rappel de la demande:</u></p><p> Ressource : %ressource% (%domaine%)<br></p><p>Réservation au nom de l\'utilisateur %logincompletbeneficiaire%</p><p>Début de la réservation : %resadatedebut%<br></p><p>Durée : %resaduree%</p><p>%resanom%<br></p><p>%resadescription%<br></p><p>%resachampsadditionnels%</p><p>Type : %resatype%</p><p><br></p><p>------</p><p>%raisonmail%<br></p>Si vous ne souhaitez plus recevoir ces messages automatiques, écrivez en ce sens au gestionnaire de GRR : %webmasteremail%<p></p><p></p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_resamoderation3_it-it', 'GRR : opinione %ressource% - %resadatedebut% - Prenotazione in attesa di moderazione', '<p>%nometablissement% - Messaggio automatico emesso dal sito GRR : %urlgrr%</p><p>La tua richiesta è in attesa di moderazione.<br>Riceverai una notifica non appena il responsabile delle risorse deciderà.<br></p><br><p><u>Promemoria della richiesta :<br></u>Prenotazione per conto dell\'utente %logincompletbeneficiaire%</p><p>Inizio della prenotazione : %resadatedebut%<br></p><p>Durata : %resaduree%</p><p>%resanom%<br></p><p>%resadescription%<br></p><p>%resachampsadditionnels%</p><p>Tipo : %resatype%</p><p><br></p><p>------</p><p>%raisonmail%<br></p>Se non desiderate ricevere più questi messaggi automatici, scrivete in questo senso all\'amministratore di GRR : %webmasteremail%<p></p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_resamoderation4_de-de', 'GRR: Meldung %ressource% - %resadatedebut% - Verarbeitung einer Reservierung im Warten von Moderation %enattentemoderation%', '<p>%nometablissement% - Automatische Nachricht von der GRR Webseite : %urlgrr%</p><p>Benutzer\r\n %logincompletuser% Ihre Reservierungsanfrage für die Ressource wurde bearbeitet %ressource% (%domaine%)</p><p><font style=\"background-color: rgb(255, 255, 0);\" color=\"#000000\">%decisionmoderation%</font></p><p>%decisionmotif%</p><p>Sehen Sie sich die Details an :</p><p>%urldetail%</p><p><br></p><p><u>Erinnerung an die Anfrage:</u><br></p><p></p><p>Reservierung im Namen von&nbsp; %logincompletbeneficiaire%</p><p>Beginn der Reservierung : %resadatedebut%<br></p><p>Dauer : %resaduree%</p><p>%resanom%<br></p><p>%resadescription%<br></p><p>%resachampsadditionnels%</p><p>Art : %resatype%</p><p><br></p><p></p><p>------</p><p>%raisonmail%<br></p>Wenn\r\n sie diese automatische Nachricht nicht mehr erhalten wollen, wenden Sie\r\n sich bitte an Ihren GRR-Systemverwalter : %webmasteremail%<p></p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_resamoderation4_en-gb', 'GRR : notice %ressource% - %resadatedebut% - Processing of a reservation waiting for a moderation', '<p>%nometablissement% - Automatic message emitted by GRR site : %urlgrr%</p><p>The\r\n user %logincompletuser% processed your reservation request for the resource \r\n%ressource% (%domaine%)<br></p><p><font style=\"background-color: rgb(255, 255, 0);\" color=\"#000000\">%decisionmoderation%</font></p><p>%decisionmotif%</p><p>See the details :</p><p>%urldetail%</p><p><br><u>Reminder of the request :</u></p><p>Reservation on behalf of the user : %logincompletbeneficiaire%</p><p>Reservation will start : %resadatedebut%<br></p><p>Duration : %resaduree%</p><p>%resanom%<br></p><p>%resadescription%<br></p><p>%resachampsadditionnels%</p><p>Type : %resatype%</p><p><br></p><p></p><p>------</p><p>%raisonmail%<br></p>If you no longer wish to get these automatic mails, write to GRR manager : %webmasteremail%<p></p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_resamoderation4_es-es', 'GRR : opinión %ressource% - %resadatedebut% - Tratamiento de una reserva en espera de moderación', '<p>%nometablissement% - Mensaje automático emitido por el GRR : %urlgrr%</p><p>El\r\n usuario %logincompletuser% procesó su solicitud de reserva para el recurso\r\n%ressource% (%domaine%)<br></p><p><font style=\"background-color: rgb(255, 255, 0);\" color=\"#000000\">%decisionmoderation%</font></p><p>%decisionmotif%</p><p>Ver los detalles :</p><p>%urldetail%</p><p></p><p><br></p><p><u>Recordatorio de la solicitud:</u><br></p><p>Reserva por cuenta del usuario : %logincompletbeneficiaire%</p><p>Principio de la reserva : %resadatedebut%<br></p><p>Duración : %resaduree%</p><p>%resanom%<br></p><p>%resadescription%<br></p><p>%resachampsadditionnels%</p><p>Tipo : %resatype%</p><br><p>------</p><p>%raisonmail%<br></p>Si usted no desea recibir estos mensajes automáticos, escriba al gestor de GRR : %webmasteremail%<p></p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_resamoderation4_fr-fr', 'GRR : avis %ressource% - %resadatedebut% - Traitement d\'une réservation en attente de modération', '<p>%nometablissement% - Message automatique émis par le site GRR : %urlgrr%</p><p>L\'utilisateur %logincompletuser%&nbsp; a traité la demande de votre réservation \r\npour la ressource %ressource% (%domaine%)</p><p><font style=\"background-color: rgb(255, 255, 0);\" color=\"#000000\">%decisionmoderation%</font></p><p>%decisionmotif%</p><p>Voir les détails :</p><p>%urldetail%</p><p><br></p><p><u>Rappel de la demande:</u><br></p><p>Réservation au nom de l\'utilisateur %logincompletbeneficiaire%</p><p>Début de la réservation : %resadatedebut%<br></p><p>Durée : %resaduree%</p><p>%resanom%<br></p><p>%resadescription%<br></p><p>%resachampsadditionnels%</p><p>Type : %resatype%</p><p><br></p><p></p><p>------</p><p>%raisonmail%<br></p>Si vous ne souhaitez plus recevoir ces messages automatiques, écrivez en ce sens au gestionnaire de GRR : %webmasteremail%<p></p><p></p><p></p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_resamoderation4_it-it', 'GRR : opinione %ressource% - %resadatedebut% - Trattamento di una prenotazione in attesa di moderazione', '<p>%nometablissement% - Messaggio automatico emesso dal sito GRR : %urlgrr%</p><p>L\'utente\r\n %logincompletuser% ha elaborato la tua richiesta di prenotazione per la risorsa %ressource% (%domaine%)<br></p><p><font style=\"background-color: rgb(255, 255, 0);\" color=\"#000000\">%decisionmoderation%</font></p><p>%decisionmotif%</p><p>Guarda i dettagli :</p><p>%urldetail%</p><p></p><p><br></p><p><u>Promemoria della richiesta :</u><br></p><p>Prenotazione per conto dell\'utente %logincompletbeneficiaire%</p><p>Inizio della prenotazione : %resadatedebut%<br></p><p>Durata : %resaduree%</p><p>%resanom%<br></p><p>%resadescription%<br></p><p>%resachampsadditionnels%</p><p>Tipo : %resatype%</p><p><br></p><p>------</p><p>%raisonmail%<br></p>Se non desiderate ricevere più questi messaggi automatici, scrivete in questo senso all\'amministratore di GRR : %webmasteremail%<p></p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_resamoderation5_de-de', 'GRR: Meldung %ressource% - %resadatedebut% - Eine Reservierung löschen', '<p><p>%nometablissement% - Automatische Nachricht von der GRR Webseite : %urlgrr%</p>Benutzer %logincompletuser% hat Buchungsanfrage für Ressource %ressource% (%domaine%) gelöscht</p><p><p><u>Erinnerung an die Anfrage:</u><br></p><p></p><p>Reservierung im Namen von&nbsp; %logincompletbeneficiaire%</p><p>Beginn der Reservierung : %resadatedebut%<br></p><p>Dauer : %resaduree%</p><p>%resanom%<br></p><p>%resadescription%<br></p><p>%resachampsadditionnels%</p><p>Art : %resatype%</p><p><br></p><p></p><p>------</p><p>%raisonmail%<br></p>Wenn\r\n sie diese automatische Nachricht nicht mehr erhalten wollen, wenden Sie\r\n sich bitte an Ihren GRR-Systemverwalter : %webmasteremail%<p><br></p><p></p></p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_resamoderation5_en-gb', 'GRR : notice %ressource% - %resadatedebut% - Deleting a reservation', '<p>%nometablissement% - Automatic message emitted by GRR site : %urlgrr%</p><p>The\r\n user %logincompletuser% deleted the booking request for the resource \r\n%ressource% (%domaine%)<br></p><p><br><u>Reminder of the request :</u></p><p>Reservation on behalf of the user : %logincompletbeneficiaire%</p><p>Reservation will start : %resadatedebut%<br></p><p>Duration : %resaduree%</p><p>%resanom%<br></p><p>%resadescription%<br></p><p>%resachampsadditionnels%</p><p>Type : %resatype%</p><p><br></p><p></p><p>------</p><p>%raisonmail%<br></p>If you no longer wish to get these automatic mails, write to GRR manager : %webmasteremail%<p></p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_resamoderation5_es-es', 'GRR : opinión %ressource% - %resadatedebut% - Supresión de una reserva', '<p>%nometablissement% - Mensaje automático emitido por el GRR : %urlgrr%</p>El usuario %logincompletuser% eliminó la solicitud de reserva para el recurso %ressource% (%domaine%)<p></p><p><br></p><p><u>Recordatorio de la solicitud:</u><br></p><p>Reserva por cuenta del usuario : %logincompletbeneficiaire%</p><p>Principio de la reserva : %resadatedebut%<br></p><p>Duración : %resaduree%</p><p>%resanom%<br></p><p>%resadescription%<br></p><p>%resachampsadditionnels%</p><p>Tipo : %resatype%</p><br><p>------</p><p>%raisonmail%<br></p>Si usted no desea recibir estos mensajes automáticos, escriba al gestor de GRR : %webmasteremail%<p></p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_resamoderation5_fr-fr', 'GRR : avis %ressource% - %resadatedebut% - Suppression d\'une réservation', '<p>%nometablissement% - Message automatique émis par le site GRR : %urlgrr%</p><p>L\'utilisateur %logincompletuser%&nbsp; a supprimé la demande de réservation \r\npour la ressource %ressource% (%domaine%)</p><p><br></p><p><u>Rappel de la demande:</u><br></p><p>Réservation au nom de l\'utilisateur %logincompletbeneficiaire%</p><p>Début de la réservation : %resadatedebut%<br></p><p>Durée : %resaduree%</p><p>%resanom%<br></p><p>%resadescription%<br></p><p>%resachampsadditionnels%</p><p>Type : %resatype%</p><p><br></p><p></p><p>------</p><p>%raisonmail%<br></p>Si vous ne souhaitez plus recevoir ces messages automatiques, écrivez en ce sens au gestionnaire de GRR : %webmasteremail%<p></p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_resamoderation5_it-it', 'GRR : opinione %ressource% - %resadatedebut% - Soppressione di una prenotazione', '<p>%nometablissement% - Messaggio automatico emesso dal sito GRR : %urlgrr%</p><p>L\'utente %logincompletuser% ha eliminato la richiesta di prenotazione per la risorsa %ressource% (%domaine%)<br></p><br><p><u>Promemoria della richiesta :<br></u>Prenotazione per conto dell\'utente %logincompletbeneficiaire%</p><p>Inizio della prenotazione : %resadatedebut%<br></p><p>Durata : %resaduree%</p><p>%resanom%<br></p><p>%resadescription%<br></p><p>%resachampsadditionnels%</p><p>Tipo : %resatype%</p><p><br></p><p>------</p><p>%raisonmail%<br></p>Se non desiderate ricevere più questi messaggi automatici, scrivete in questo senso all\'amministratore di GRR : %webmasteremail%<p></p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_resamoderation6_de-de', 'GRR: Meldung %ressource% - %resadatedebut% - Verarbeitung einer Reservierung im Warten von Moderation', '<p>%nometablissement% - Automatische Nachricht von der GRR Webseite : %urlgrr%</p><p>Eine Reservierungsanfrage lautet nun:</p><p>%urldetail%</p><p>Der folgende vorbehalt wartet auf die moderation %ressource%  (%domaine%)</p><p>Gebucht von %logincompletuser%</p><p>Reservierung im Namen von&nbsp; %logincompletbeneficiaire%</p><p>Beginn der Reservierung : %resadatedebut%<br></p><p>Dauer : %resaduree%</p><p>%resanom%<br></p><p>%resadescription%<br></p><p>%resachampsadditionnels%</p><p>Art : %resatype%</p><p><br></p><p></p><p>------</p><p>%raisonmail%<br></p>Wenn\r\n sie diese automatische Nachricht nicht mehr erhalten wollen, wenden Sie\r\n sich bitte an Ihren GRR-Systemverwalter : %webmasteremail%<p><br></p><p></p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_resamoderation6_en-gb', 'GRR : notice %ressource% - %resadatedebut% - Processing of a reservation waiting for a moderation', '<p>%nometablissement% - Automatic message emitted by GRR site : %urlgrr%</p><p>A reservation request is to be moderated :</p><p>%urldetail%</p><p>The following reservation is awaiting moderation for %ressource% (%domaine%)<br></p><p>Booked by %logincompletuser%</p><p>Reservation on behalf of the user : %logincompletbeneficiaire%</p><p>Reservation will start : %resadatedebut%<br></p><p>Duration : %resaduree%</p><p>%resanom%<br></p><p>%resadescription%<br></p><p>%resachampsadditionnels%</p><p>Type : %resatype%</p><p><br></p><p></p><p>------</p><p>%raisonmail%<br></p>If you no longer wish to get these automatic mails, write to GRR manager : %webmasteremail%<p></p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_resamoderation6_es-es', 'GRR : opinión %ressource% - %resadatedebut% - Tratamiento de una reserva en espera de moderación', '<p>%nometablissement% - Mensaje automático emitido por el GRR : %urlgrr%</p><p>Una solicitud de reserva debe ser moderada :</p><p>%urldetail%</p>La siguiente reserva está pendiente de moderación para %ressource% (%domaine%)<p></p><p>Reservado por %logincompletuser%</p><p>Reserva por cuenta del usuario : %logincompletbeneficiaire%</p><p>Principio de la reserva : %resadatedebut%<br></p><p>Duración : %resaduree%</p><p>%resanom%<br></p><p>%resadescription%<br></p><p>%resachampsadditionnels%</p><p>Tipo : %resatype%</p><br><p>------</p><p>%raisonmail%<br></p>Si usted no desea recibir estos mensajes automáticos, escriba al gestor de GRR : %webmasteremail%<p></p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_resamoderation6_fr-fr', 'GRR : avis %ressource% - %resadatedebut% - Traitement d\'une réservation en attente de modération', '<p>%nometablissement% - Message automatique émis par le site GRR : %urlgrr%</p><p>Une demande de réservation est à modérer :</p><p>%urldetail%<br></p><p>La réservation suivante est en attente de modération pour %ressource% (%domaine%)</p><p>Réservé par %logincompletuser%</p><p>Réservation au nom de l\'utilisateur %logincompletbeneficiaire%</p><p>Début de la réservation : %resadatedebut%<br></p><p>Durée : %resaduree%</p><p>%resanom%<br></p><p>%resadescription%<br></p><p>%resachampsadditionnels%</p><p>Type : %resatype%</p><br><p>------</p><p>%raisonmail%</p>Si vous ne souhaitez plus recevoir ces messages automatiques, écrivez en ce sens au gestionnaire de GRR : %webmasteremail%', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_resamoderation6_it-it', 'GRR : opinione %ressource% - %resadatedebut% - Trattamento di una prenotazione in attesa di moderazione', '<p>%nometablissement% - Messaggio automatico emesso dal sito GRR : %urlgrr%</p><p>Una richiesta di prenotazione deve essere moderata :</p><p>%urldetail%</p><p>La seguente prenotazione è in attesa di moderazione per %ressource% (%domaine%)<br></p>Prenotato da %logincompletuser%<p>Prenotazione per conto dell\'utente %logincompletbeneficiaire%</p><p>Inizio della prenotazione : %resadatedebut%<br></p><p>Durata : %resaduree%</p><p>%resanom%<br></p><p>%resadescription%<br></p><p>%resachampsadditionnels%</p><p>Tipo : %resatype%</p><p><br></p><p>------</p><p>%raisonmail%<br></p>Se non desiderate ricevere più questi messaggi automatici, scrivete in questo senso all\'amministratore di GRR : %webmasteremail%<p></p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_resamoderation_de-de', 'GRR: Meldung %ressource% - %resadatedebut% - Reservierung ist im Warten von Moderation', '<p>%nometablissement% - Automatische Nachricht von der GRR Webseite : %urlgrr%</p><p>Der folgende vorbehalt wartet auf die moderation %ressource%  (%domaine%)</p><p>Gebucht von %logincompletuser%</p><p>Reservierung im Namen von&nbsp; %logincompletbeneficiaire%</p><p>Beginn der Reservierung : %resadatedebut%<br></p><p>Dauer : %resaduree%</p><p>%resanom%<br></p><p>%resadescription%<br></p><p>%resachampsadditionnels%</p><p>Art : %resatype%</p><p><br></p><p></p><p>------</p><p>%raisonmail%<br></p>Wenn\r\n sie diese automatische Nachricht nicht mehr erhalten wollen, wenden Sie\r\n sich bitte an Ihren GRR-Systemverwalter : %webmasteremail%<p></p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_resamoderation_en-gb', 'GRR : notice %ressource% - %resadatedebut% - Reservation waiting for a moderation', '<p>%nometablissement% - Automatic message emitted by GRR site : %urlgrr%</p><p>The following reservation is awaiting moderation for %ressource% (%domaine%)</p><p>Booked by %logincompletuser%</p><p>Reservation in the name of %logincompletbeneficiaire%</p><p>Reservation will start : %resadatedebut%<br></p><p>Duration : %resaduree%</p><p>%resanom%<br></p><p>%resadescription%<br></p><p>%resachampsadditionnels%</p><p>Type : %resatype%</p><p><br></p><p></p><p>------</p><p>%raisonmail%<br></p>If you no longer wish to get these automatic mails, write to GRR manager : %webmasteremail%<p><br></p><p></p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_resamoderation_es-es', 'GRR : opinión %ressource% - %resadatedebut% - Reserva en espera de moderación', '<p>%nometablissement% - Mensaje automático emitido por el GRR : %urlgrr%</p><p>La siguiente reserva está pendiente de moderación para %ressource% (%domaine%)</p><p>Reservado por %logincompletuser%<br></p><p>Reserva por cuenta del usuario : %logincompletbeneficiaire%</p><p>Principio de la reserva : %resadatedebut%<br></p><p>Duración : %resaduree%</p><p>%resanom%<br></p><p>%resadescription%<br></p><p>%resachampsadditionnels%</p><p>Tipo : %resatype%</p><br><p>------</p><p>%raisonmail%<br></p>Si usted no desea recibir estos mensajes automáticos, escriba al gestor de GRR : %webmasteremail%<p></p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_resamoderation_fr-fr', 'GRR : avis %ressource% - %resadatedebut% - Réservation en attente de modération', '<p>%nometablissement% - Message automatique émis par le site GRR : %urlgrr%</p><p>La réservation suivante est en attente de modération pour %ressource% (%domaine%)</p><p>Réservé par %logincompletuser%</p><p>Réservation au nom de l\'utilisateur %logincompletbeneficiaire%</p><p>Début de la réservation : %resadatedebut%<br></p><p>Durée : %resaduree%</p><p>%resanom%<br></p><p>%resadescription%<br></p><p>%resachampsadditionnels%</p><p>Type : %resatype%</p><br><p>------</p><p>%raisonmail%</p>Si vous ne souhaitez plus recevoir ces messages automatiques, écrivez en ce sens au gestionnaire de GRR : %webmasteremail%<p></p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_resamoderation_it-it', 'GRR : opinione %ressource% - %resadatedebut% - Prenotazione in attesa di moderazione', '<p>%nometablissement% - Messaggio automatico emesso dal sito GRR : %urlgrr%</p><p>La seguente prenotazione è in attesa di moderazione per %ressource%  (%domaine%) <br></p><p><br></p><p>Prenotato da %logincompletuser%</p><p>Inizio della prenotazione : %resadatedebut%<br></p><p>Durata : %resaduree%</p><p>%resanom%<br></p><p>%resadescription%<br></p><p>%resachampsadditionnels%</p><p>Tipo : %resatype%</p><p><br></p><p>------</p><p>%raisonmail%<br></p>Se non desiderate ricevere più questi messaggi automatici, scrivete in questo senso all\'amministratore di GRR : %webmasteremail%<p></p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_resamodification2_de-de', 'GRR: Meldung %ressource% - %resadatedebut% - Eine Reservierung ändern %enattentemoderation%', '<p><p>%nometablissement% - Automatische Nachricht von der GRR Webseite : %urlgrr%</p><p>Benutzer %logincompletuser% hat folgende Reservierung geändert %ressource% (%domaine%) das du getan hast.</p><p><br></p><p>Reservierung im Namen von&nbsp; %logincompletbeneficiaire%</p><p>Beginn der Reservierung : %resadatedebut%<br></p><p>Dauer : %resaduree%</p><p>%resanom%<br></p><p>%resadescription%<br></p><p>%resachampsadditionnels%</p><p>Art : %resatype%</p><p>%resaperiodique%</p>%resaconfirmation%</p><p><p><br></p><p>------</p><p>%raisonmail%<br></p>Wenn\r\n sie diese automatische Nachricht nicht mehr erhalten wollen, wenden Sie\r\n sich bitte an Ihren GRR-Systemverwalter : %webmasteremail%</p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_resamodification2_en-gb', 'GRR : notice %ressource% - %resadatedebut% - Modifying a reservation %enattentemoderation%', '<p><p>%nometablissement% - Automatic message emitted by GRR site : %urlgrr%</p><p>The user %logincompletuser% has modified the reservation of %ressource% (%domaine%) that you had done.</p><p><br></p><p>Reservation in the name of %logincompletbeneficiaire%</p><p>Reservation will start : %resadatedebut%<br></p><p>Duration : %resaduree%</p><p>%resanom%<br></p><p>%resadescription%<br></p><p>%resachampsadditionnels%</p><p>Type : %resatype%</p><p>%resaperiodique%</p>%resaconfirmation%</p><p><p><br></p><p>------</p><p>%raisonmail%<br></p>If you no longer wish to get these automatic mails, write to GRR manager : %webmasteremail%<p></p></p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_resamodification2_es-es', 'GRR : opinión %ressource% - %resadatedebut% - Modificación de una reserva %enattentemoderation%', '<p><p>%nometablissement% - Mensaje automático emitido por el GRR : %urlgrr%</p><p>El usuario %logincompletuser% cambió la reserva del recurso %ressource%  (%domaine%) que habías hecho.</p><br><p>Principio de la reserva : %resadatedebut%<br></p><p>Duración : %resaduree%</p><p>%resanom%<br></p><p>%resadescription%<br></p><p>%resachampsadditionnels%</p><p>Tipo : %resatype%</p><p>%resaperiodique%</p>%resaconfirmation%</p><p><p><br></p><p>------</p><p>%raisonmail%<br></p>Si usted no desea recibir estos mensajes automáticos, escriba al gestor de GRR : %webmasteremail%<p></p></p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_resamodification2_fr-fr', 'GRR : avis %ressource% - %resadatedebut% - Modification d\'une réservation %enattentemoderation%', '<p><p>%nometablissement% - Message automatique émis par le site GRR : %urlgrr%</p><p>L\'utilisateur %logincompletuser% a modifié la réservation de la ressource %ressource% (%domaine%)&nbsp; que vous aviez effectuée.</p><p><br></p><p>Début de la réservation : %resadatedebut%<br></p><p>Durée : %resaduree%</p><p>%resanom%<br></p><p>%resadescription%<br></p><p>%resachampsadditionnels%</p><p>Type : %resatype%</p><p>%resaperiodique%</p>%resaconfirmation%</p><p><p><br></p><p>------<br></p>Si vous ne souhaitez plus recevoir ces messages automatiques, écrivez en ce sens au gestionnaire de GRR : %webmasteremail%<p></p></p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_resamodification2_it-it', 'GRR : opinione %ressource% - %resadatedebut% - Modifica di una prenotazione %enattentemoderation%', '<p><p>%nometablissement% - Messaggio automatico emesso dal sito GRR : %urlgrr%</p><p>L\'utente %logincompletuser% ha cambiato la prenotazione della risorsa %ressource%  (%domaine%) che avevi fatto.<br></p><p><br></p><p>Inizio della prenotazione : %resadatedebut%<br></p><p>Durata : %resaduree%</p><p>%resanom%<br></p><p>%resadescription%<br></p><p>%resachampsadditionnels%</p><p>Tipo : %resatype%</p><p>%resaperiodique%</p>%resaconfirmation%</p><p><p><br></p><p>------</p><p>%raisonmail%<br></p>Se non desiderate ricevere più questi messaggi automatici, scrivete in questo senso all\'amministratore di GRR : %webmasteremail%<p></p></p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_resamodification_de-de', 'GRR: Meldung %ressource% - %resadatedebut% - Eine Reservierung ändern %enattentemoderation%', '<p><p>%nometablissement% - Automatische Nachricht von der GRR Webseite : %urlgrr%</p><p>Benutzer %logincompletuser% hat folgende Reservierung geändert %ressource% (%domaine%)</p><p><br></p><p>Reservierung im Namen von&nbsp; %logincompletbeneficiaire%</p><p>Beginn der Reservierung : %resadatedebut%<br></p><p>Dauer : %resaduree%</p><p>%resanom%<br></p><p>%resadescription%<br></p><p>%resachampsadditionnels%</p><p>Art : %resatype%</p><p>%resaperiodique%</p>%resaconfirmation%</p><p><p><br></p><p>------</p><p>%raisonmail%<br></p>Wenn\r\n sie diese automatische Nachricht nicht mehr erhalten wollen, wenden Sie\r\n sich bitte an Ihren GRR-Systemverwalter : %webmasteremail%</p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_resamodification_en-gb', 'GRR : notice %ressource% - %resadatedebut% - Modifying a reservation %enattentemoderation%', '<p><p>%nometablissement% - Automatic message emitted by GRR site : %urlgrr%</p><p>The user %logincompletuser% has modified the reservation of %ressource% (%domaine%)</p><p><br></p><p>Reservation in the name of %logincompletbeneficiaire%</p><p>Reservation will start : %resadatedebut%<br></p><p>Duration : %resaduree%</p><p>%resanom%<br></p><p>%resadescription%<br></p><p>%resachampsadditionnels%</p><p>Type : %resatype%</p><p>%resaperiodique%</p>%resaconfirmation%</p><p><p><br></p><p>------</p><p>%raisonmail%<br></p>If you no longer wish to get these automatic mails, write to GRR manager : %webmasteremail%<p></p></p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_resamodification_es-es', 'GRR : opinión %ressource% - %resadatedebut% - Modificación de una reserva %enattentemoderation%', '<p><p>%nometablissement% - Mensaje automático emitido por el GRR : %urlgrr%</p><p>El usuario %logincompletuser% cambió la reserva del recurso %ressource%  (%domaine%)</p><p><br></p><p>Reserva por cuenta del usuario : %logincompletbeneficiaire%</p><p>Principio de la reserva : %resadatedebut%<br></p><p>Duración : %resaduree%</p><p>%resanom%<br></p><p>%resadescription%<br></p><p>%resachampsadditionnels%</p><p>Tipo : %resatype%</p><p>%resaperiodique%</p>%resaconfirmation%</p><p><p><br></p><p>------</p><p>%raisonmail%<br></p>Si usted no desea recibir estos mensajes automáticos, escriba al gestor de GRR : %webmasteremail%<p></p></p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_resamodification_fr-fr', 'GRR : avis %ressource% - %resadatedebut% - Modification d\'une réservation %enattentemoderation%', '<p>%nometablissement% - Message automatique émis par le site GRR : %urlgrr%</p><p>L\'utilisateur %logincompletuser% a modifié la réservation de la ressource %ressource% (%domaine%)</p><p><br></p><p>Réservation au nom de l\'utilisateur %logincompletbeneficiaire%</p><p>Début de la réservation : %resadatedebut%<br></p><p>Durée : %resaduree%</p><p>%resanom%<br></p><p>%resadescription%<br></p><p>%resachampsadditionnels%</p><p>Type : %resatype%</p><p>%resaperiodique%</p><p>%resaconfirmation%</p><p><br></p><p>------</p><p>%raisonmail%<br></p><p>Si vous ne souhaitez plus recevoir ces messages automatiques, écrivez en ce sens au gestionnaire de GRR : %webmasteremail%<br></p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_resamodification_it-it', 'GRR : opinione %ressource% - %resadatedebut% - Modifica di una prenotazione %enattentemoderation%', '<p><p>%nometablissement% - Messaggio automatico emesso dal sito GRR : %urlgrr%</p><p>L\'utente %logincompletuser% ha cambiato la prenotazione della risorsa %ressource%  (%domaine%)</p><p><br></p><p>Prenotazione per conto dell\'utente : %logincompletbeneficiaire%</p><p>Inizio della prenotazione : %resadatedebut%<br></p><p>Durata : %resaduree%</p><p>%resanom%<br></p><p>%resadescription%<br></p><p>%resachampsadditionnels%</p><p>Tipo : %resatype%</p><p>%resaperiodique%</p>%resaconfirmation%</p><p><p><br></p><p>------</p><p>%raisonmail%<br></p>Se non desiderate ricevere più questi messaggi automatici, scrivete in questo senso all\'amministratore di GRR : %webmasteremail%</p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_resasuppression2_de-de', 'GRR: Meldung %ressource% - %resadatedebut% - Eine Reservierung löschen %enattentemoderation%', '<p>%nometablissement% - Automatische Nachricht von der GRR Webseite : %urlgrr%</p><p>Der Benutzer %logincompletuser% hat die von Ihnen vorgenommene Reservierung der Ressource %ressource% (%domaine%) geändert.<br></p><p><br></p><p>Beginn der Reservierung : %resadatedebut%<br></p><p>Dauer : %resaduree%</p><p>%resanom%<br></p><p>%resadescription%<br></p><p>%resachampsadditionnels%</p><p>Art : %resatype%</p><p><br></p><p></p><p>------</p><p>%raisonmail%<br></p>Wenn\r\n sie diese automatische Nachricht nicht mehr erhalten wollen, wenden Sie\r\n sich bitte an Ihren GRR-Systemverwalter : %webmasteremail%<p></p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_resasuppression2_en-gb', 'GRR : notice %ressource% - %resadatedebut% - Deleting a reservation %enattentemoderation%', '<p>%nometablissement% - Automatic message emitted by GRR site : %urlgrr%</p><p>The user %logincompletuser% has deleted the reservation of %ressource% that you had done.</p><br><p>Reservation will start : %resadatedebut%<br></p><p>Duration : %resaduree%</p><p>%resanom%<br></p><p>%resadescription%<br></p><p>%resachampsadditionnels%</p><p>Type : %resatype%</p><p>%resaperiodique%</p>%resaconfirmation%<p></p><p></p><p><br></p><p>------</p><p>%raisonmail%<br></p>If you no longer wish to get these automatic mails, write to GRR manager : %webmasteremail%<p></p><p></p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_resasuppression2_es-es', 'GRR : opinión %ressource% - %resadatedebut% - Supresión de una reserva %enattentemoderation%', '<p>%nometablissement% - Mensaje automático emitido por el GRR : %urlgrr%</p><p>El usuario %logincompletuser% eliminó la reserva de %ressource%  (%domaine%) que habías hecho.</p><p><br></p><p>Principio de la reserva : %resadatedebut%<br></p><p>Duración : %resaduree%</p><p>%resanom%<br></p><p>%resadescription%<br></p><p>%resachampsadditionnels%</p><p>Tipo : %resatype%</p><p>%resaperiodique%</p>%resaconfirmation%<p></p><p></p><p><br></p><p>------</p><p>%raisonmail%<br></p>Si usted no desea recibir estos mensajes automáticos, escriba al gestor de GRR : %webmasteremail%<p></p><p></p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_resasuppression2_fr-fr', 'GRR : avis %ressource% - %resadatedebut% - Suppression d\'une réservation  %enattentemoderation%', '<p><p>%nometablissement% - Message automatique émis par le site GRR : %urlgrr%</p>L\'utilisateur %logincompletuser% a supprimé la réservation de la ressource %ressource% (%domaine%)&nbsp; que vous aviez effectuée.</p><p><p><br></p><p>Début de la réservation : %resadatedebut%<br></p><p>Durée : %resaduree%</p><p>%resanom%<br></p><p>%resadescription%<br></p><p>%resachampsadditionnels%</p><p>Type : %resatype%</p><p>%resaperiodique%</p>%resaconfirmation%</p><p><p><br></p><p>------<br></p>Si vous ne souhaitez plus recevoir ces messages automatiques, écrivez en ce sens au gestionnaire de GRR : %webmasteremail%<p></p></p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_resasuppression2_it-it', 'GRR : opinione %ressource% - %resadatedebut% -  Soppressione di una prenotazione %enattentemoderation%', '<p>%nometablissement% - Messaggio automatico emesso dal sito GRR : %urlgrr%</p><p>L\'utente %logincompletuser% cancellata la prenotazione di %ressource%  (%domaine%) che avevi fatto.</p><br><p>Inizio della prenotazione : %resadatedebut%<br></p><p>Durata : %resaduree%</p><p>%resanom%<br></p><p>%resadescription%<br></p><p>%resachampsadditionnels%</p><p>Tipo : %resatype%</p><p>%resaperiodique%</p>%resaconfirmation%<p></p><p></p><p><br></p><p>------</p><p>%raisonmail%<br></p>Se non desiderate ricevere più questi messaggi automatici, scrivete in questo senso all\'amministratore di GRR : %webmasteremail%<p></p><p></p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_resasuppression3_de-de', 'GRR: Meldung %ressource% - %resadatedebut% - Eine Reservierung löschen %enattentemoderation%', '<p>%nometablissement% - Automatische Nachricht von der GRR Webseite : %urlgrr%</p><p>Die Frist für die Reservierungsbestätigung ist abgelaufen.</p>Automatisches Löschen der Ressourcenreservierung %ressource% (%domaine%) <p></p><p><br></p><p>Beginn der Reservierung : %resadatedebut%<br></p><p>Dauer : %resaduree%</p><p>%resanom%<br></p><p>%resadescription%<br></p><p>%resachampsadditionnels%</p><p>Art : %resatype%</p><p><br></p><p></p><p>------</p><p>%raisonmail%<br></p>Wenn\r\n sie diese automatische Nachricht nicht mehr erhalten wollen, wenden Sie\r\n sich bitte an Ihren GRR-Systemverwalter : %webmasteremail%<p></p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_resasuppression3_en-gb', 'GRR : notice %ressource% - %resadatedebut% - Deleting a reservation %enattentemoderation%', '<p>%nometablissement% - Automatic message emitted by GRR site : %urlgrr%</p><p>The delay of confirmation of the reservation is exceeded.</p><p>Automatic suppression of the reservation of %ressource% (%domaine%) </p><p><br></p><p>Reservation will start : %resadatedebut%<br></p><p>Duration : %resaduree%</p><p>%resanom%<br></p><p>%resadescription%<br></p><p>%resachampsadditionnels%</p><p>Type : %resatype%</p><p>%resaperiodique%</p>%resaconfirmation%<p></p><p></p><p><br></p><p>------</p><p>%raisonmail%<br></p>If you no longer wish to get these automatic mails, write to GRR manager : %webmasteremail%<p></p><p></p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_resasuppression3_es-es', 'GRR : opinión %ressource% - %resadatedebut% - Supresión de una reserva %enattentemoderation%', '<p>%nometablissement% - Mensaje automático emitido por el GRR : %urlgrr%</p><p>El plazo de confirmación de reserva sobrepasó.</p><p>Supresión automático de la reserva de %ressource%  (%domaine%)</p><p><br></p><p>Principio de la reserva : %resadatedebut%<br></p><p>Duración : %resaduree%</p><p>%resanom%<br></p><p>%resadescription%<br></p><p>%resachampsadditionnels%</p><p>Tipo : %resatype%</p><p>%resaperiodique%</p>%resaconfirmation%<p></p><p></p><p><br></p><p>------</p><p>%raisonmail%<br></p>Si usted no desea recibir estos mensajes automáticos, escriba al gestor de GRR : %webmasteremail%<p></p><p></p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_resasuppression3_fr-fr', 'GRR : avis %ressource% - %resadatedebut% - Suppression d\'une réservation  %enattentemoderation%', '<p><p>%nometablissement% - Message automatique émis par le site GRR : %urlgrr%</p><p>Le délai de confirmation de réservation a été dépassé.</p><p>Suppression automatique de la réservation de la ressource %ressource% (%domaine%) </p><p><br></p><p>Début de la réservation : %resadatedebut%<br></p><p>Durée : %resaduree%</p><p>%resanom%<br></p><p>%resadescription%<br></p><p>%resachampsadditionnels%</p><p>Type : %resatype%</p><p>%resaperiodique%</p>%resaconfirmation%</p><p><p><br></p><p>------<br></p>Si vous ne souhaitez plus recevoir ces messages automatiques, écrivez en ce sens au gestionnaire de GRR : %webmasteremail%<p></p></p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_resasuppression3_it-it', 'GRR : opinione %ressource% - %resadatedebut% - Soppressione di una prenotazione %enattentemoderation%', '<p>%nometablissement% - Messaggio automatico emesso dal sito GRR : %urlgrr%</p><p>Il termine per la conferma della prenotazione è scaduto.<br>Cancellazione automatica della prenotazione di %ressource%  (%domaine%)</p><p><br></p><p>Inizio della prenotazione : %resadatedebut%<br></p><p>Durata : %resaduree%</p><p>%resanom%<br></p><p>%resadescription%<br></p><p>%resachampsadditionnels%</p><p>Tipo : %resatype%</p><p>%resaperiodique%</p>%resaconfirmation%<p></p><p></p><p><br></p><p>------</p><p>%raisonmail%<br></p>Se non desiderate ricevere più questi messaggi automatici, scrivete in questo senso all\'amministratore di GRR : %webmasteremail%<p></p><p></p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_resasuppression_de-de', 'GRR: Meldung %ressource% - %resadatedebut% - Eine Reservierung löschen %enattentemoderation%', '<p>%nometablissement% - Automatische Nachricht von der GRR Webseite : %urlgrr%</p><p>Benutzer %logincompletuser% hat folgende Reservierung gelöscht %ressource%</p><p><br></p><p>Reservierung im Namen von&nbsp; %logincompletbeneficiaire%</p><p>Beginn der Reservierung : %resadatedebut%<br></p><p>Dauer : %resaduree%</p><p>%resanom%<br></p><p>%resadescription%<br></p><p>%resachampsadditionnels%</p><p>Art : %resatype%</p><p><br></p><p></p><p>------</p><p>%raisonmail%<br></p>Wenn\r\n sie diese automatische Nachricht nicht mehr erhalten wollen, wenden Sie\r\n sich bitte an Ihren GRR-Systemverwalter : %webmasteremail%<p></p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_resasuppression_en-gb', 'GRR : notice %ressource% - %resadatedebut% - Deleting a reservation %enattentemoderation%', '<p><p>%nometablissement% - Automatic message emitted by GRR site : %urlgrr%</p><p>The user %logincompletuser% has deleted the reservation of %ressource%</p><p><br></p><p>Reservation in the name of %logincompletbeneficiaire%</p><p>Reservation will start : %resadatedebut%<br></p><p>Duration : %resaduree%</p><p>%resanom%<br></p><p>%resadescription%<br></p><p>%resachampsadditionnels%</p><p>Type : %resatype%</p><p>%resaperiodique%</p>%resaconfirmation%</p><p><p><br></p><p>------</p><p>%raisonmail%<br></p>If you no longer wish to get these automatic mails, write to GRR manager : %webmasteremail%<p></p></p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_resasuppression_es-es', 'GRR : opinión %ressource% - %resadatedebut% - Supresión de una reserva %enattentemoderation%', '<p><p>%nometablissement% - Mensaje automático emitido por el GRR : %urlgrr%</p><p>El usuario %logincompletuser% eliminó la reserva de %ressource%  (%domaine%)</p><p><br></p><p>Reserva por cuenta del usuario : %logincompletbeneficiaire%</p><p>Principio de la reserva : %resadatedebut%<br></p><p>Duración : %resaduree%</p><p>%resanom%<br></p><p>%resadescription%<br></p><p>%resachampsadditionnels%</p><p>Tipo : %resatype%</p><p>%resaperiodique%</p>%resaconfirmation%</p><p><p><br></p><p>------</p><p>%raisonmail%<br></p>Si usted no desea recibir estos mensajes automáticos, escriba al gestor de GRR : %webmasteremail%<p></p></p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_resasuppression_fr-fr', 'GRR : avis %ressource% - %resadatedebut% - Suppression d\'une réservation  %enattentemoderation%', '<p>%nometablissement% - Message automatique émis par le site GRR : %urlgrr%</p><p>L\'utilisateur %logincompletuser% a supprimé la réservation de %ressource% (%domaine%)</p><p><br></p><p>Réservation au nom de l\'utilisateur %logincompletbeneficiaire%</p><p>Début de la réservation : %resadatedebut%<br></p><p>Durée : %resaduree%</p><p>%resanom%<br></p><p>%resadescription%<br></p><p>%resachampsadditionnels%</p><p>Type : %resatype%</p><br><p>------</p><p>%raisonmail%</p><p>Si vous ne souhaitez plus recevoir ces messages automatiques, écrivez en ce sens au gestionnaire de GRR : %webmasteremail%<br></p><p></p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_resasuppression_it-it', 'GRR : opinione %ressource% - %resadatedebut% - Soppressione di una prenotazione %enattentemoderation%', '<p><p>%nometablissement% - Messaggio automatico emesso dal sito GRR : %urlgrr%</p><p>L\'utente %logincompletuser% cancellata la prenotazione di %ressource%  (%domaine%)</p><p><br></p><p>Prenotazione per conto dell\'utente : %logincompletbeneficiaire%</p><p>Inizio della prenotazione : %resadatedebut%<br></p><p>Durata : %resaduree%</p><p>%resanom%<br></p><p>%resadescription%<br></p><p>%resachampsadditionnels%</p><p>Tipo : %resatype%</p><p>%resaperiodique%</p>%resaconfirmation%</p><p><p><br></p><p>------</p><p>%raisonmail%<br></p>Se non desiderate ricevere più questi messaggi automatici, scrivete in questo senso all\'amministratore di GRR : %webmasteremail%<p></p></p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_retardrestitution2_de-de', 'GRR: Meldung %ressource% - %resadatedebut% - Verzögerungsbenachrichtigung', '<p>%nometablissement% -&nbsp;Automatische Nachricht von der GRR Webseite&nbsp; : %urlgrr%</p><br><p>Sofern ich mich nicht irre, wurde die von Ihnen geliehene ressource&nbsp;<b>%ressource%</b> (%domaine%) nicht zurückgegeben. Wenn es sich hierbei um einen Fehler handelt, ignorieren Sie diese E-Mail bitte.</p><p><br></p><p>Reservierung im Namen von : %logincompletbeneficiaire%</p><p>Beginn der Reservierung : %resadatedebut%<br></p><p>Dauer : %resaduree%</p><p>%resanom%<br></p><p>%resadescription%<br></p><p>%resachampsadditionnels%</p><p>Art : %resatype%</p><p>%resaperiodique%</p>%resaconfirmation%<p><br></p><p></p>------<p></p><p>%raisonmail%</p><p>Wenn\r\n sie diese automatische Nachricht nicht mehr erhalten wollen, wenden Sie\r\n sich bitte an Ihren GRR-Systemverwalter : %webmasteremail%</p><p></p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_retardrestitution2_en-gb', 'GRR : notice %ressource% - %resadatedebut% - Notifying a delay', '<p>%nometablissement% -&nbsp;Automatic message emitted by GRR site : %urlgrr%</p><br><p>Errors excepted, the resource&nbsp;<b>%ressource% </b>(%domaine%) you borrowed was not restored. If it is an error, please not take this mail into account.</p><p><br></p><p>Reservation in the name of : %logincompletbeneficiaire%</p><p>Reservation will start : %resadatedebut%<br></p><p>Duration : %resaduree%</p><p>%resanom%<br></p><p>%resadescription%<br></p><p>%resachampsadditionnels%</p><p>Type : %resatype%</p><p>%resaperiodique%</p>%resaconfirmation%<p><br></p><p></p>------<p></p><p>%raisonmail%<br>If you no longer wish to get these automatic mails, write to GRR manager : %webmasteremail%</p><p></p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_retardrestitution2_es-es', 'GRR : opinión %ressource% - %resadatedebut% - Notificación de retraso', '<p>%nometablissement% -&nbsp;Mensaje automático emitido por el GRR : %urlgrr%</p><br><p>A menos que me equivoque, el recurso <b>%ressource%</b> (%domaine%)que tomó prestado no se ha devuelto. Si se trata de un error, ignore este correo.</p><p><br></p><p>Reserva por cuenta del usuario : %logincompletbeneficiaire%</p><p>Principio de la reserva : %resadatedebut%<br></p><p>Duración : %resaduree%</p><p>%resanom%<br></p><p>%resadescription%<br></p><p>%resachampsadditionnels%</p><p>Tipo : %resatype%</p><p>%resaperiodique%</p>%resaconfirmation%<p><br></p><p></p>------<p></p><p>%raisonmail%</p><p>Si usted no desea recibir estos mensajes automáticos, escriba al gestor de GRR : %webmasteremail%</p><p></p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_retardrestitution2_fr-fr', 'GRR : avis %ressource% - %resadatedebut% - Notification de retard', '<p>%nometablissement% - Message automatique émis par le site GRR : %urlgrr%</p><br><p>Sauf erreur, la ressource <b>%ressource%</b> (%domaine%) que vous avez empruntée n\'a pas été restituée. S\'il s\'agit d\'une erreur, veuillez ne pas tenir compte de ce courrier.</p><p><br></p><p>Réservation au nom de l\'utilisateur : %logincompletbeneficiaire%</p><p>Début de la réservation : %resadatedebut%<br></p><p>Durée : %resaduree%</p><p>%resanom%<br></p><p>%resadescription%<br></p><p>%resachampsadditionnels%</p><p>Type : %resatype%</p><p>%resaperiodique%</p>%resaconfirmation%<p><br></p><p></p>------<p></p><p>%raisonmail%<br>Si vous ne souhaitez plus recevoir ces messages automatiques, écrivez en ce sens au gestionnaire de GRR : %webmasteremail%</p><p></p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_retardrestitution2_it-it', 'GRR : opinione %ressource% - %resadatedebut% - Notifica di ritardo', '<p>%nometablissement% -&nbsp;Messaggio automatico emesso dal sito GRR : %urlgrr%</p><br><p>Se non sbaglio, la risorsa <b>%ressource%</b> (%domaine%) che hai preso in prestito non è stata restituita. Se si tratta di un errore, si prega di ignorare questa mail.</p><p><br></p><p>Prenotazione per conto dell\'utente : %logincompletbeneficiaire%</p>Inizio della prenotazione : %resadatedebut%<p></p><p>Durata : %resaduree%</p><p></p><p>%resanom%<br></p><p>%resadescription%<br></p><p>%resachampsadditionnels%</p><p>Tipo : %resatype%</p><p>%resaperiodique%</p>%resaconfirmation%<p><br></p><p></p>------<p></p><p>%raisonmail%</p><p>Se non desiderate ricevere più questi messaggi automatici, scrivete in questo senso all\'amministratore di GRR : %webmasteremail%</p><p></p><p></p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_retardrestitution_de-de', 'GRR: Meldung %ressource% - %resadatedebut% - Verzögerungsbenachrichtigung', '<p>%nometablissement% -&nbsp;Automatische Nachricht von der GRR Webseite :  %urlgrr%</p><p>Ressource<b> %ressource%</b> (%domaine%) <u>wurde nicht zurückgegeben</u>.<br></p>Name des Kreditnehmers : %logincompletbeneficiaire%<p></p><p></p><p>%maildestinataire%<br></p>Wenn die Ressource zurückgegeben wurde, gehen Sie zur folgenden Adresse, um den Status der Reservierung zu ändern:<p></p>%urldetail%<p></p><p></p><p><br></p>------<p></p><p>%raisonmail%<br>Wenn\r\n sie diese automatische Nachricht nicht mehr erhalten wollen, wenden Sie\r\n sich bitte an Ihren GRR-Systemverwalter : %webmasteremail%</p><p></p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_retardrestitution_en-gb', 'GRR : notice %ressource% - %resadatedebut% - Notifying a delay', '<p>%nometablissement% -&nbsp;Automatic message emitted by GRR site : %urlgrr%</p><p>The resource<b> %ressource%</b> (%domaine%) <u>has not been returned.</u><br></p>Name of the borrower : %logincompletbeneficiaire%<p></p><p></p><p>%maildestinataire%<br></p>Lorsque la ressource aura été restituée, rendez-vous à l\'adresse suivante pour changer le statut de la réservation :<p></p>%urldetail%<p></p><p></p><p><br></p>------<p></p><p>%raisonmail%<br>If you no longer wish to get these automatic mails, write to GRR manager : %webmasteremail%</p><p></p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_retardrestitution_es-es', 'GRR : opinión %ressource% - %resadatedebut% - Notificación de retraso', '<p>%nometablissement% -&nbsp;Mensaje automático emitido por el GRR : %urlgrr%</p><p>El recurso<b> %ressource%</b> (%domaine%) <u>no ha sido devuelto</u>.<br></p>Nombre del prestatario : %logincompletbeneficiaire%<p></p><p></p><p>%maildestinataire%<br></p>Cuando el recurso habrá sido restituido, vayase a la dirección siguiente para cambiar el estatuto de la reserva :<p></p>%urldetail%<p></p><p></p><p><br></p>------<p></p><p>%raisonmail%</p><p>Si usted no desea recibir estos mensajes automáticos, escriba al gestor de GRR : %webmasteremail%</p><p></p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_retardrestitution_fr-fr', 'GRR : avis %ressource% - %resadatedebut% - Notification de retard', '<p>%nometablissement% - Message automatique émis par le site GRR : %urlgrr%</p><p>La ressource<b> %ressource%</b> (%domaine%) <u>n\'a pas été restituée</u>.<br></p>Nom de l\'emprunteur : %logincompletbeneficiaire%<p></p><p></p><p>%maildestinataire%<br></p>Lorsque la ressource aura été restituée, rendez-vous à l\'adresse suivante pour changer le statut de la réservation :<p></p>%urldetail%<p></p><p></p><p><br></p>------<p></p><p>%raisonmail%<br>Si vous ne souhaitez plus recevoir ces messages automatiques, écrivez en ce sens au gestionnaire de GRR : %webmasteremail%</p><p></p><p></p><p></p><p></p><p></p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_retardrestitution_it-it', 'GRR : opinione %ressource% - %resadatedebut% - Notifica di ritardo', '<p>%nometablissement% -&nbsp;Messaggio automatico emesso dal sito GRR : %urlgrr%</p><p>La risorsa<b> %ressource%</b> (%domaine%) <u>non è stato restituito</u>.<br></p>Nome del mutuatario : %logincompletbeneficiaire%<p></p><p></p><p>%maildestinataire%<br></p>Quando la risorsa è stata restituita, vai al seguente indirizzo per modificare lo stato della prenotazione :<p></p>%urldetail%<p></p><p></p><p><br></p>------<p></p><p>%raisonmail%</p><p>Se non desiderate ricevere più questi messaggi automatici, scrivete in questo senso all\'amministratore di GRR : %webmasteremail%</p><p></p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_test_de-de', 'Test seit %urlgrr%', '<p>Willkommen bei <span style=\"background-color: rgb(255, 255, 0);\">%nomdusite%</span> von %nometablissement%<br></p>Diese E-Mail <b>validiert </b>die Konfiguration Ihres Mailservers.<p></p><p></p><p><br></p>E-Mail von der Verwaltung Ihres GRR.<p></p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_test_en-gb', 'Test since %urlgrr%', '<p>Welcome to <span style=\"background-color: rgb(255, 255, 0);\">%nomdusite%</span>&nbsp; of %nometablissement%<br></p>This email <b>validates</b> the configuration of your mail server.<p></p><p><br></p><p></p><p></p><p></p>Mail sent from the administration of your GRR.<p></p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_test_es-es', 'Después de la prueba %urlgrr%', '<p>Bienvenido a <span style=\"background-color: rgb(255, 255, 0);\"><font style=\"background-color: rgb(255, 255, 0);\" color=\"#000000\"></font>%nomdusite%</span> de %nometablissement%<br></p>Este correo electrónico <b>valida </b>la configuración de su servidor de correo.\r\n\r\n\r\n<p></p><p></p><p><br></p>Correo enviado desde la administración de su GRR.<p></p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_test_fr-fr', 'Test depuis %urlgrr%', '<p>Bienvenue sur <span style=\"background-color: rgb(255, 255, 0);\"><font style=\"background-color: rgb(255, 255, 0);\" color=\"#000000\"></font>%nomdusite%</span> de %nometablissement%<br></p>Ce mail <b>valide </b>la configuration de votre serveur de mail.<p></p><p></p><p><br></p><p>Mail envoyé depuis l\'administration de votre GRR.<br><br></p><p></p><p></p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_test_it-it', 'Prueba desde %urlgrr%', '<p>Benvenuto in <span style=\"background-color: rgb(255, 255, 0);\">%nomdusite%</span>&nbsp; di %nometablissement%<br></p>Questa e-mail <b>convalida</b> la configurazione del tuo server di posta.<p></p><p><br></p><p></p><p></p><p></p>Mail inviata dall\'amministrazione del tuo GRR.<p></p><p></p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_demandecompte2_de-de', '[Ergebnis] Anfrage zur Kontoerstellung', '<p>Guten Morgen<br>Ihre Anfrage zur Erstellung eines Kontos auf %urlgrr% wurde angenommen.</p><p><br>Sie können sich jetzt unten mit Ihrem Benutzernamen anmelden.<br>Bezeichner: %identifiant%<br>Passwort: Das Passwort, das Sie bei Ihrer Anfrage angegeben haben.</p><p><br></p><p>---</p><p><span class=\"HwtZe\" lang=\"de\"><span class=\"jCAhz ChMk0b\"><span class=\"ryNqvb\">Bitte antworten Sie nicht auf diese Email.</span></span></span></p><p></p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_demandecompte2_en-gb', '[Result] Account creation request %formnom% %formprenom%', '<p><span class=\"HwtZe\" lang=\"en\"><span class=\"jCAhz ChMk0b\"><span class=\"ryNqvb\">Good morning,<br></span></span><span class=\"jCAhz ChMk0b\"><span class=\"ryNqvb\">Your request to create an account on %urlgrr% has been accepted.<br></span></span><span class=\"jCAhz ChMk0b\"><span class=\"ryNqvb\">You can now log in with your username below.<br></span></span><span class=\"jCAhz ChMk0b\"><span class=\"ryNqvb\">Identifier: %identifiant%<br></span></span><span class=\"jCAhz ChMk0b\"><span class=\"ryNqvb\">Password: The one you provided during your request.</span></span></span></p><p><span class=\"HwtZe\" lang=\"en\"><span class=\"jCAhz ChMk0b\"><span class=\"ryNqvb\"><br></span></span></span></p><p><span class=\"HwtZe\" lang=\"en\"><span class=\"jCAhz ChMk0b\"><span class=\"ryNqvb\">---</span></span></span></p><p><span class=\"HwtZe\" lang=\"en\"><span class=\"jCAhz ChMk0b\"><span class=\"ryNqvb\">Please do not reply to this email.</span></span></span></p><p></p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_demandecompte2_es-es', '[Resultado] Solicitud de creación de cuenta', '<p>Buenos dias<br>Su solicitud para crear una cuenta en %urlgrr% ha sido aceptada.<br>Ahora puede iniciar sesión con su nombre de usuario a continuación.<br>Identificador: %identifiant%<br>Contraseña: La que usted proporcionó durante su solicitud.</p><p><br></p><p>---</p><p><span class=\"HwtZe\" lang=\"es\"><span class=\"jCAhz\"><span class=\"ryNqvb\">\r\n</span></span><span class=\"jCAhz ChMk0b\"><span class=\"ryNqvb\">Por favor no responder a este email.</span></span></span></p><p></p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_demandecompte2_fr-fr', '[Résultat] Demande de création de compte %formnom% %formprenom%', '<p>Bonjour,</p><p>Votre demande création de compte sur&nbsp;%urlgrr% a été acceptée.<br>Vous pouvez désormais vous connecter avec votre identifiant ci-dessous.<br>Identifiant : %identifiant%<br>Mot de passe : Celui que vous avez renseignez lors de votre demande.</p><p><br></p><p>---</p>Veuillez ne pas répondre à ce mail.<p></p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_demandecompte2_it-it', '[Risultato]  Richiesta di creazione dell\'account', '<p>Buongiorno<br>La tua richiesta di creare un account su %urlgrr% è stata accettata.<br>Ora puoi accedere con il tuo nome utente qui sotto.<br>Identificatore: %identifiant%<br>Password: quella che hai fornito durante la richiesta.</p><p><br></p><p>---</p><p><span class=\"HwtZe\" lang=\"it\"><span class=\"jCAhz ChMk0b\"><span class=\"ryNqvb\">Per favore non rispondere a questa email.</span></span></span></p><p></p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_demandecompte3_de-de', '[Ergebnis] Anfrage zur Kontoerstellung', '<p>Guten Morgen,<br>Ihre Anfrage, ein Konto bei %urlgrr% zu erstellen, wurde abgelehnt.</p><p><br></p><p>---</p><p><span class=\"HwtZe\" lang=\"de\"><span class=\"jCAhz ChMk0b\"><span class=\"ryNqvb\">Bitte antworten Sie nicht auf diese Email.</span></span></span></p><p></p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_demandecompte3_en-gb', '[Result] Account creation request %formnom% %formprenom%', '<p><span class=\"HwtZe\" lang=\"en\"><span class=\"jCAhz ChMk0b\"><span class=\"ryNqvb\">Good morning,<br></span></span><span class=\"jCAhz ChMk0b\"><span class=\"ryNqvb\">Your request to create an account on %urlgrr% has been refused.</span></span></span></p><p><span class=\"HwtZe\" lang=\"en\"><span class=\"jCAhz ChMk0b\"><span class=\"ryNqvb\"><br></span></span></span></p><p><span class=\"HwtZe\" lang=\"en\"><span class=\"jCAhz ChMk0b\"><span class=\"ryNqvb\">---</span></span></span></p><p><span class=\"HwtZe\" lang=\"en\"><span class=\"jCAhz ChMk0b\"><span class=\"ryNqvb\">Please do not reply to this email.</span></span></span></p><p></p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_demandecompte3_es-es', '[Resultado] Solicitud de creación de cuenta', '<p>Buenos dias,<br>Su solicitud para crear una cuenta en %urlgrr% ha sido rechazada.</p><p><br></p><p>---</p><p><span class=\"HwtZe\" lang=\"es\"><span class=\"jCAhz\"><span class=\"ryNqvb\">\r\n</span></span><span class=\"jCAhz ChMk0b\"><span class=\"ryNqvb\">Por favor no responder a este email.</span></span></span></p><p></p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_demandecompte3_fr-fr', '[Résultat] Demande de création de compte %formnom% %formprenom%', '<p>Bonjour,</p><p>Votre demande création de compte sur&nbsp;%urlgrr% a été refusée.</p><p>---</p>Veuillez ne pas répondre à ce mail.<p></p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_demandecompte3_it-it', '[Risultato]  Richiesta di creazione dell\'account', '<p>Buongiorno,<br>La tua richiesta di creare un account su %urlgrr% è stata rifiutata.</p><p><br></p><p>---</p><p><span class=\"HwtZe\" lang=\"it\"><span class=\"jCAhz ChMk0b\"><span class=\"ryNqvb\">Per favore non rispondere a questa email.</span></span></span></p><p></p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_demandecompte_de-de', 'Anfrage zur Kontoerstellung', '<p>%nometablissement% - Automatische Nachricht von der GRR Webseite : %urlgrr%</p><p><br></p><p>Anfrage zur Kontoerstellung für: %formnom% %formprenom%<br>E-Mail: %formemail%<br>Telefon: %formtelephone%<br>Kommentar: %formcommentaire%</p><p><br></p><p>---</p><p><span class=\"HwtZe\" lang=\"de\"><span class=\"jCAhz ChMk0b\"><span class=\"ryNqvb\">Bitte antworten Sie nicht auf diese Email.</span></span></span></p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_demandecompte_en-gb', 'Account creation request %formnom% %formprenom%', '<p>%nometablissement% - Automatic message emitted by GRR site : %urlgrr%</p><p><span class=\"HwtZe\" lang=\"en\"><span class=\"jCAhz ChMk0b\"><span class=\"ryNqvb\">Account creation request for: %formnom% %formprenom%<br></span></span><span class=\"jCAhz ChMk0b\"><span class=\"ryNqvb\">Email: %formemail%<br></span></span><span class=\"jCAhz\"><span class=\"ryNqvb\">\r\n</span></span><span class=\"jCAhz ChMk0b\"><span class=\"ryNqvb\">Telephone: %formtelephone%<br></span></span><span class=\"jCAhz ChMk0b\"><span class=\"ryNqvb\">Comment: %formcommentaire%</span></span></span></p><p><span class=\"HwtZe\" lang=\"en\"><span class=\"jCAhz ChMk0b\"><span class=\"ryNqvb\"><br></span></span></span></p><p><span class=\"HwtZe\" lang=\"en\"><span class=\"jCAhz ChMk0b\"><span class=\"ryNqvb\">---</span></span></span></p><p><span class=\"HwtZe\" lang=\"en\"><span class=\"jCAhz ChMk0b\"><span class=\"ryNqvb\">Please do not reply to this email.</span></span></span></p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_demandecompte_es-es', 'Solicitud de creación de cuenta', '<p>%nometablissement% - Mensaje automático emitido por el GRR : %urlgrr%</p><p><br></p><p>Solicitud de creación de cuenta para: %formnom% %formprenom%<br>Correo electrónico: %formemail%<br>Teléfono: %formtelephone%<br>Comentario: %formcommentaire%</p><p><br></p><p>---</p><p><span class=\"HwtZe\" lang=\"es\"><span class=\"jCAhz\"><span class=\"ryNqvb\">\r\n</span></span><span class=\"jCAhz ChMk0b\"><span class=\"ryNqvb\">Por favor no responder a este email.</span></span></span></p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_demandecompte_fr-fr', 'Demande de création de compte %formnom% %formprenom%', '<p>%nometablissement% - Message automatique émis par le site GRR : %urlgrr%</p><p>Demande de création de compte pour : %formnom% %formprenom%<br>Email : %formemail%<br>Téléphone : %formtelephone%<br>Commentaire : %formcommentaire%</p><p><br></p><p>---</p><p>Veuillez ne pas répondre à ce mail.<br></p>', 1);");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES('mails_demandecompte_it-it', 'Richiesta di creazione dell\'account', '<p>%nometablissement% - Messaggio automatico emesso dal sito GRR : %urlgrr%</p><p><br></p><p>Richiesta di creazione account per: %formnom% %formprenom%<br>E-mail: %formemail%<br>Telefono: %formtelephone%<br>Commento: %formcommentaire%</p><br><p>---</p><p><span class=\"HwtZe\" lang=\"it\"><span class=\"jCAhz ChMk0b\"><span class=\"ryNqvb\">Per favore non rispondere a questa email.</span></span></span></p>', 1);");
		



		if ($result_inter == '')
			$result .= formatresult("Ok !","<span style='color:green;'>","</span>");
		else
			$result .= $result_inter;
		$result_inter = '';
	}


	if (intval($version_old_bdd) < 400004) // Version GRR 4.3.0
	{
		
		$result .= formatresult("Mise à jour jusqu'à la version 4.3.0 :","<b>","</b>");

		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page VALUES ('contactresa', 'contactresa', '', 0);");
		$result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_room ADD `who_can_book` TINYINT(1) NOT NULL DEFAULT '1' AFTER `booking_range`;");
		$result_inter .= traiteRequete("CREATE TABLE IF NOT EXISTS ".TABLE_PREFIX."_j_userbook_room (login varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL default '', id_room int(11) NOT NULL default '0', PRIMARY KEY  (login,id_room) )");

		$req = grr_sql_query1("SELECT VALUE FROM ".TABLE_PREFIX."_setting WHERE NAME='display_beneficiaire'");
		if (($req != -1) && (($req != "")))
		{
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('display_beneficiaire_nc', '".$req."');");
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('display_beneficiaire_vi', '".$req."');");
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('display_beneficiaire_us', '".$req."');");
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('display_beneficiaire_gr', '".$req."');");
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('display_beneficiaire_ad', '".$req."');");
			$del = traiteRequete("DELETE FROM ".TABLE_PREFIX."_setting where NAME='display_beneficiaire'");
		}
		else{
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('display_beneficiaire_nc', '1');");
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('display_beneficiaire_vi', '1');");
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('display_beneficiaire_us', '1');");
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('display_beneficiaire_gr', '1');");
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('display_beneficiaire_ad', '1');");
		}

		$req = grr_sql_query1("SELECT VALUE FROM ".TABLE_PREFIX."_setting WHERE NAME='display_horaires'");
		if (($req != -1) && (($req != "")))
		{
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('display_horaires_nc', '".$req."');");
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('display_horaires_vi', '".$req."');");
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('display_horaires_us', '".$req."');");
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('display_horaires_gr', '".$req."');");
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('display_horaires_ad', '".$req."');");
			$del = traiteRequete("DELETE FROM ".TABLE_PREFIX."_setting where NAME='display_horaires'");
		}
		else
		{
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('display_horaires_nc', '1');");
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('display_horaires_vi', '1');");
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('display_horaires_us', '1');");
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('display_horaires_gr', '1');");
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('display_horaires_ad', '1');");
		}

		$req = grr_sql_query1("SELECT VALUE FROM ".TABLE_PREFIX."_setting WHERE NAME='display_short_description'");
		if (($req != -1) && (($req != "")))
		{
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('display_short_description_nc', '".$req."');");
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('display_short_description_vi', '".$req."');");
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('display_short_description_us', '".$req."');");
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('display_short_description_gr', '".$req."');");
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('display_short_description_ad', '".$req."');");
			$del = traiteRequete("DELETE FROM ".TABLE_PREFIX."_setting where NAME='display_short_description'");
		}
		else
		{
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('display_short_description_nc', '1');");
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('display_short_description_vi', '1');");
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('display_short_description_us', '1');");
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('display_short_description_gr', '1');");
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('display_short_description_ad', '1');");
		}

		$req = grr_sql_query1("SELECT VALUE FROM ".TABLE_PREFIX."_setting WHERE NAME='display_full_description'");
		if (($req != -1) && (($req != "")))
		{
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('display_full_description_nc', '".$req."');");
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('display_full_description_vi', '".$req."');");
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('display_full_description_us', '".$req."');");
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('display_full_description_gr', '".$req."');");
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('display_full_description_ad', '".$req."');");
			$del = traiteRequete("DELETE FROM ".TABLE_PREFIX."_setting where NAME='display_full_description'");
		}
		else
		{
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('display_full_description_nc', '1');");
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('display_full_description_vi', '1');");
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('display_full_description_us', '1');");
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('display_full_description_gr', '1');");
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('display_full_description_ad', '1');");
		}

		$req = grr_sql_query1("SELECT VALUE FROM ".TABLE_PREFIX."_setting WHERE NAME='display_type'");
		if (($req != -1) && (($req != "")))
		{
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('display_type_nc', '".$req."');");
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('display_type_vi', '".$req."');");
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('display_type_us', '".$req."');");
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('display_type_gr', '".$req."');");
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('display_type_ad', '".$req."');");
			$del = traiteRequete("DELETE FROM ".TABLE_PREFIX."_setting where NAME='display_type'");
		}
		else
		{
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('display_type_nc', '1');");
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('display_type_vi', '1');");
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('display_type_us', '1');");
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('display_type_gr', '1');");
			$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('display_type_ad', '1');");
		}

		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('allow_users_modify_affichage', '2');");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('allow_users_modify_domaine', '2');");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('allow_users_modify_theme', '2');");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('allow_users_modify_langue', '2');");

		if ($result_inter == '')
			$result .= formatresult("Ok !","<span style='color:green;'>","</span>");
		else
			$result .= $result_inter;
		$result_inter = '';
	}

	
	if (intval($version_old_bdd) < 400005) // Version GRR 4.3.7
	{
		
		$result .= formatresult("Mise à jour jusqu'à la version 4.3.7 :","<b>","</b>");

		$result_inter .= traiteRequete("ALTER TABLE  ".TABLE_PREFIX."_utilisateurs_groupes CHANGE login login VARCHAR(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL;");

		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('pass_nb_ch', '0');");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('pass_nb_maj', '0');");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('pass_nb_min', '0');");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('pass_nb_sp', '0');");

		if ($result_inter == '')
			$result .= formatresult("Ok !","<span style='color:green;'>","</span>");
		else
			$result .= $result_inter;
		$result_inter = '';
	}

	if (intval($version_old_bdd) < 400006) // Version GRR 4.4.0 alpha
	{
		
		$result .= formatresult("Mise à jour jusqu'à la version 4.4.0 :","<b>","</b>");

		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('calcul_plus_mois', 'y');");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('calcul_plus_mois2_all', 'y');");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('calcul_plus_semaine_all', 'y');");

		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('login_template', '1');");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('login_logo', '1');");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('login_nom', '1');");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('nextalertemailhebdo', '1735686000') ON DUPLICATE KEY UPDATE `value` = '1735686000';");

		$result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_page ADD `statutmini` VARCHAR(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;");
		$result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_page ADD `lien` VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL;");
		$result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_page ADD `nouveauonglet` TINYINT(1) NOT NULL DEFAULT '1';");
		$result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_page ADD `ordre` SMALLINT(6) NOT NULL DEFAULT '0';");
		$result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_page ADD `emplacement` SMALLINT(6) NOT NULL DEFAULT '1';");

		$result_inter .= traiteRequete("UPDATE ".TABLE_PREFIX."_page SET emplacement = 0 WHERE 1;");

		$result_inter .= traiteRequete("CREATE TABLE IF NOT EXISTS ".TABLE_PREFIX."_j_group_area (`idgroupes` int NOT NULL, `id_area` int NOT NULL DEFAULT '0', PRIMARY KEY (`idgroupes`,`id_area`));");
		$result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_j_user_area ADD `idgroupes` int(11) NOT NULL DEFAULT '0';");

		$result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_j_mailuser_room ADD `mail_resa` tinyint(1) NOT NULL DEFAULT '1';");
		$result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_j_mailuser_room ADD `mail_hebdo` tinyint(1) NOT NULL DEFAULT '0';");

		$result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_type_area ADD `couleuricone` VARCHAR(10) NOT NULL DEFAULT '#000000' AFTER `couleurtexte`;");

		$result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_utilisateurs ADD `commentaire` MEDIUMTEXT NOT NULL AFTER `source`, ADD `desactive_mail` TINYINT NOT NULL DEFAULT '0' AFTER `commentaire`, ADD `nb_tentative` TINYINT NOT NULL DEFAULT '0' AFTER `desactive_mail`, ADD `date_blocage` INT NOT NULL DEFAULT '0' AFTER `nb_tentative`, ADD `popup` TINYINT NOT NULL DEFAULT '0' AFTER `date_blocage`;");

		if ($result_inter == '')
			$result .= formatresult("Ok !","<span style='color:green;'>","</span>");
		else
			$result .= $result_inter;
		$result_inter = '';
	}

	if (intval($version_old_bdd) < 400007) // Version GRR 4.4.0 béta
	{
		$result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_room ADD `inscription_participant` tinyint(1) NOT NULL DEFAULT '1' AFTER `active_participant`;");
		$result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_room ADD `nb_participant_defaut` smallint NOT NULL DEFAULT '0' AFTER `inscription_participant`;");

		$result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_site ADD `access` CHAR(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'a' AFTER `sitename`;");
		$result_inter .= traiteRequete("CREATE TABLE IF NOT EXISTS ".TABLE_PREFIX."_j_user_site (`login` varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,`id_site` int NOT NULL DEFAULT '0',`idgroupes` int NOT NULL DEFAULT '0',PRIMARY KEY (`login`,`id_site`));");
		$result_inter .= traiteRequete("CREATE TABLE IF NOT EXISTS ".TABLE_PREFIX."_j_group_site (`idgroupes` int NOT NULL, `id_site` int NOT NULL DEFAULT '0', PRIMARY KEY (`idgroupes`,`id_site`));");

		if ($result_inter == '')
			$result .= formatresult("Ok !","<span style='color:green;'>","</span>");
		else
			$result .= $result_inter;
		$result_inter = '';
	}

	if (intval($version_old_bdd) < 400008) // Version GRR 4.4.2
	{
		$result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_j_user_site DROP PRIMARY KEY;");
		$result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_j_user_site ADD CONSTRAINT grr_j_user_site_pk PRIMARY KEY (login,id_site,idgroupes);");
		$result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_utilisateurs CHANGE commentaire commentaire MEDIUMTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL;");

		if ($result_inter == '')
			$result .= formatresult("Ok !","<span style='color:green;'>","</span>");
		else
			$result .= $result_inter;
		$result_inter = '';
	}

	if (intval($version_old_bdd) < 400009) // Version GRR 4.5.0
	{
		$result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_room ADD confidentiel_resa TINYINT(1) NOT NULL DEFAULT '0' AFTER who_can_book;");
		$result_inter .= traiteRequete("CREATE TABLE IF NOT EXISTS ".TABLE_PREFIX."_files(id int not null auto_increment, id_entry int, file_name varchar(50), public_name varchar(50),Primary key (id)) CHARACTER SET utf8mb4;");
		$exists = grr_sql_query1("SHOW COLUMNS FROM ".TABLE_PREFIX."_area LIKE 'user_right'");
		if ($exists == -1) {
			$result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_area ADD user_right INT(11) AFTER max_booking;");
			$result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_area ADD access_file INT(11) AFTER user_right;");
			$result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_area ADD upload_file INT(11) AFTER access_file;");
		}

		if ($result_inter == '')
			$result .= formatresult("Ok !","<span style='color:green;'>","</span>");
		else
			$result .= $result_inter;
		$result_inter = '';
	}

	// Mise à jour du numéro de version BDD précédent
	$req = grr_sql_query1("SELECT VALUE FROM ".TABLE_PREFIX."_setting WHERE NAME='previousversion'");
	if ($req == -1)
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('previousversion', '".$version_old_bdd."');");
	else
		$result_inter .= traiteRequete("UPDATE ".TABLE_PREFIX."_setting SET VALUE='".$version_old_bdd."' WHERE NAME='previousversion';");

	// Mise à jour du numéro de version
	$req = grr_sql_query1("SELECT VALUE FROM ".TABLE_PREFIX."_setting WHERE NAME='version'");
	if ($req == -1)
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('version', '".$version_grr_bdd."');");
	else
		$result_inter .= traiteRequete("UPDATE ".TABLE_PREFIX."_setting SET VALUE='".$version_grr_bdd."' WHERE NAME='version';");



	$result .= $result_inter;

	return $result;
}