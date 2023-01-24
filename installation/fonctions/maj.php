<?php
/**
 * installation/fonctions/maj.php
 * interface permettant la mise à jour de la base de données
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2023-01-24 09:13$
 * @author    JeromeB & Laurent Delineau & Yan Naessens
 * @author    Arnaud Fornerot pour l'intégation au portail Envole http://ent-envole.com/
 * @copyright Copyright 2003-2023 Team DEVOME - JeromeB
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
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('menu_gauche', '1')");
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
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_page (`nom`, `valeur`) VALUES ('CGU', 'Les CGU');");
		$result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_page ADD UNIQUE KEY `nom` (`nom`);");
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
    if ($version_old < "3.5.0.0")
    {
        $result .= formatResult("Mise à jour jusqu'à la version 3.5.0 RC0:","<b>","</b>");
        include "./fonctions/ISO_to_UTF8.inc.php";
        $result_inter = '';
    }
    if($version_old < "3.5.1.0")
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
	// On constuite un tableau des id des ".TABLE_PREFIX."_overload:
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
	$result = '';
	$result_inter = '';

	// On commence la mise à jour 
	if ($version_old_bdd < 4001) // Version GRR 4.0.0 Béta
	{
		$result .= formatresult("Mise à jour jusqu'à la version 4.0.0 :","<b>","</b>");
		$hash_pwd2 = bin2hex(random_bytes(12));

		$result_inter .= traiteRequete("DELETE FROM ".TABLE_PREFIX."_setting WHERE NAME='versionRC'");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting (`NAME`, `VALUE`) VALUES ('display_beneficiaire', '0')");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting (`NAME`, `VALUE`) VALUES ('display_type', '1')");
		$result_inter .= traiteRequete("CREATE TABLE IF NOT EXISTS ".TABLE_PREFIX."_log_mail (`idlogmail` int(11) NOT NULL AUTO_INCREMENT, `date` int(11) NOT NULL, `de` VARCHAR(184) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL, `a` VARCHAR(184) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL, `sujet` VARCHAR(184) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL, `message` text NOT NULL, PRIMARY KEY (`idlogmail`));");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting (`NAME`, `VALUE`) VALUES ('smtp_allow_self_signed', 'false')");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting (`NAME`, `VALUE`) VALUES ('smtp_verify_peer_name', 'true')");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting (`NAME`, `VALUE`) VALUES ('smtp_verify_peer', 'true')");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting (`NAME`, `VALUE`) VALUES ('smtp_verify_depth', '3')");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting (`NAME`, `VALUE`) VALUES ('backup_date', '')");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting (`NAME`, `VALUE`) VALUES ('hashpwd2', '".$hash_pwd2."')");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting (`NAME`, `VALUE`) VALUES ('mail_user_destinataire', 'y')");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting (`NAME`, `VALUE`) VALUES ('cas_version', 'CAS_VERSION_2_0')");
		$result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_utilisateurs CHANGE `password` `password` VARCHAR(184) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '';");
        $result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_participants ADD `id_participation` INT(11) NOT NULL AUTO_INCREMENT FIRST, ADD UNIQUE `UNIQUE` (`id_participation`)"); 
        $result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_participants CHANGE `participant` `beneficiaire` VARCHAR(184) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL"); 
        $result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_participants ADD `timestamp` TIMESTAMP on update CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `idresa`, ADD `cree_par` VARCHAR(184) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL AFTER `timestamp`");
        $result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_participants ADD `beneficiaire_ext` VARCHAR(184) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL AFTER `beneficiaire`, ADD `moderation` TINYINT(1) NOT NULL DEFAULT '0' AFTER `beneficiaire_ext`");
		$result_inter .= traiteRequete("CREATE TABLE IF NOT EXISTS ".TABLE_PREFIX."_log_resa (idlogresa bigint(20) NOT NULL AUTO_INCREMENT, date int(11) NOT NULL, idresa int(11) NOT NULL, identifiant varchar(100) NOT NULL, action tinyint(3) UNSIGNED NOT NULL, infoscomp text NOT NULL, PRIMARY KEY (`idlogresa`));");
		$result_inter .= traiteRequete("ALTER TABLE ".TABLE_PREFIX."_entry ADD `supprimer` TINYINT(1) NOT NULL DEFAULT '0' AFTER `nbparticipantmax`;");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting (`NAME`, `VALUE`) VALUES ('horaireconnexionde', '')");
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting (`NAME`, `VALUE`) VALUES ('horaireconnexiona', '')");
		$result_inter .= traiteRequete("CREATE TABLE ".TABLE_PREFIX."_groupes (`idgroupes` int(11) NOT NULL AUTO_INCREMENT, `nom` VARCHAR(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL, description text NOT NULL, `archive` tinyint(1) NOT NULL DEFAULT '0', PRIMARY KEY (`idgroupes`));");
		$result_inter .= traiteRequete("CREATE TABLE ".TABLE_PREFIX."_utilisateurs_groupes (`idutilisateursgroupes` bigint(20) NOT NULL AUTO_INCREMENT, `login` VARCHAR(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL, `idgroupes` int(11) NOT NULL, PRIMARY KEY (`idutilisateursgroupes`), UNIQUE KEY `idutilisateurs` (`login`,`idgroupes`));");
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

	// Mise à jour du numéro de version
	$req = grr_sql_query1("SELECT VALUE FROM ".TABLE_PREFIX."_setting WHERE NAME='version'");
	if ($req == -1)
		$result_inter .= traiteRequete("INSERT INTO ".TABLE_PREFIX."_setting VALUES ('version', '".$version_grr_bdd."');");
	else
		$result_inter .= traiteRequete("UPDATE ".TABLE_PREFIX."_setting SET VALUE='".$version_grr_bdd."' WHERE NAME='version';");



	$result .= $result_inter;

	return $result;
}