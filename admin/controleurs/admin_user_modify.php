<?php
/**
 * admin_user_modify.php
 * Interface de modification/création d'un utilisateur de l'application GRR
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2026-09-20 14:15$
 * @author    Laurent Delineau & JeromeB
 * @copyright Since 2003 Team DEVOME - JeromeB
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


if ((SecuAccess::UserLevel(getUserName(), -1) < 6) && (SecuAccess::UserLevel(getUserName(), -1, 'user') !=  1))
{
	showAccessDenied($back);
	exit();
}

// les variables attendues et leur type
$form_vars = array(
    'p_type' => array('int', 0), // 1 : Création, 2 : Modification
	'p_user_login' => array('string', ''), 
	'p_nom' => array('string', ''),
	'p_prenom' => array('string', ''),
	'p_password' => array('string', ''),
	'p_password2' => array('string', ''),
	'p_changepwd' => array('int', 0),
	'p_statut' => array('alphanumeric', 'visiteur'),
	'p_email' => array('string', ''),
	'p_etat' => array('alphanumeric', 'actif'),
	'p_source' => array('alphanumeric', 'local'),
	'p_desactive_mail' => array('int', 0),
	'p_commentaire' => array('string', null),
	'p_id_site' => array('int', -1),
	'p_id_area' => array('int', -1),
	'p_id_room' => array('int', -1),
	'p_default_css' => array('string', 'default'),
	'p_area_list_format' => array('string', 'item'),
	'p_default_language' => array('string', 'fr-fr'),
	'p_groupes' => array('array', array()),
);
// récupération des valeurs des variables passées en paramètres
foreach($form_vars as $var => $params)
    $$var = SecuChaine::GetFormVarSecure($var, $params[0], $params[1]);



// Un gestionnaire d'utilisateurs ne peut pas modifier un administrateur général ou un gestionnaire d'utilisateurs
if (isset($p_user_login) && (SecuAccess::UserLevel(getUserName(),-1,'user') ==  1))
{
	$test_statut = grr_sql_query1("SELECT statut FROM ".TABLE_PREFIX."_utilisateurs WHERE login='".$p_user_login."'");
	if (($test_statut == "administrateur") or ($test_statut == "gestionnaire_utilisateur"))
	{
		showAccessDenied($back);
		exit();
	}
}

$msg = '';
$user_nom = '';
$user_prenom = '';
$user_mail = '';
$user_statut = 'visiteur';
$user_source = 'local';
$user_etat = '';

/** Actions **/
	if ($p_type > 0)
	{
		// Restriction dans le cas d'une démo
		VerifyModeDemo();
		$retry = false;

		if ($p_source != "local")
			$p_password = "";

		if (($p_nom == '') || ($p_prenom == ''))
		{
			$msg = get_vocab("please_enter_name");
			$retry = true;
		}
		else
		{
			/** Création d'un nouvel utilisateur **/
			if ($p_type == 1) 
			{
				$test_login = preg_replace("/([A-Za-z0-9_@.-])/","",$p_user_login);
				if($test_login == ""){
					// un gestionnaire d'utilisateurs ne peut pas créer un administrateur général ou un gestionnaire d'utilisateurs
					$test_statut = TRUE;
					if (SecuAccess::UserLevel(getUserName(),-1) < 6)
					{
						if (($p_statut == "administrateur") || ($p_statut == "gestionnaire_utilisateur"))
							$test_statut = FALSE;
					}
					$p_user_login = strtoupper($p_user_login);
					if ($p_password !='')
						$p_password_c = password_hash($p_password,PASSWORD_DEFAULT);
					else
					{
						if ($p_source != "local")
							$p_password_c = '';
						else
						{
							$msg = get_vocab("passwd_error");
							$retry = true;
						}
					}

					if(Settings::get("mail_user_unique") == 1){
						$nbEmail = "SELECT COUNT(*) FROM ".TABLE_PREFIX."_utilisateurs WHERE email = '".SecuChaine::ProtectDataSql($p_email)."'";
						if(grr_sql_query1($nbEmail) > 0){
							$msg = get_vocab("mail_user_unique_error");
							$retry = true;
						}
					}

					if (!($test_statut))
					{
						$msg = get_vocab("erreur_choix_statut");
						$retry = true;
					}
					else if ((($p_password != $p_password2) || (check_password_difficult($p_password) == false)) && ($p_source == "local"))
					{
						$msg = get_vocab("passwd_error");
						$retry = true;
					}
					else
					{
						$sql = "SELECT * FROM ".TABLE_PREFIX."_utilisateurs WHERE login = '".$p_user_login."'";
						$res = grr_sql_query($sql);
						$nombreligne = grr_sql_count ($res);
						if ($nombreligne != 0)
						{
							$msg = get_vocab("error_exist_login");
							$retry = true;
						}
						if($retry == false)
						{
							$sql = "INSERT INTO ".TABLE_PREFIX."_utilisateurs SET
							nom='".SecuChaine::ProtectDataSql($p_nom)."',
							prenom='".SecuChaine::ProtectDataSql($p_prenom)."',
							login='".SecuChaine::ProtectDataSql($p_user_login)."',
							password='".SecuChaine::ProtectDataSql($p_password_c)."',
							changepwd='".SecuChaine::ProtectDataSql($p_changepwd)."',
							statut='".SecuChaine::ProtectDataSql($p_statut)."',
							email='".SecuChaine::ProtectDataSql($p_email)."',
							etat='".SecuChaine::ProtectDataSql($p_etat)."',
							source='".SecuChaine::ProtectDataSql($p_source)."',
							commentaire='".SecuChaine::ProtectDataSql($p_commentaire)."',
							desactive_mail='".SecuChaine::ProtectDataSql($p_desactive_mail)."',
							default_site = '".$p_id_site."',
							default_area = '".$p_id_area."',
							default_room = '".$p_id_room."',
							default_style = '".$p_default_css."',
							default_list_type = '".$p_area_list_format."',
							default_language = '".$p_default_language."'";

							if (grr_sql_command($sql) < 0)
							{
								fatal_error(0, get_vocab("msg_login_created_error") . grr_sql_error());
							}
							else
							{
								$msg = get_vocab("msg_login_created");
							}

							// Groupes
							// Normalement la suppression ne sert pas mais en cas de résidut d'un ancien compte comportant ce login...
							$sql = "DELETE FROM ".TABLE_PREFIX."_utilisateurs_groupes WHERE login='$p_user_login'";
							if (grr_sql_command($sql) < 0)
								fatal_error(0, get_vocab('message_records_error') . grr_sql_error());

							if(isset($p_groupes) && !empty($p_groupes)){
								foreach ($p_groupes as $valeur)
								{
									if ($valeur != '')
									{
										$sql = "INSERT INTO ".TABLE_PREFIX."_utilisateurs_groupes SET login= '$p_user_login', idgroupes = '$valeur'";
										if (grr_sql_command($sql) < 0)
											fatal_error(1, "<p>" . grr_sql_error());
									}
								}
							}
							//Fin des groupes
						}
					}
				}
				else
				{
					$msg = get_vocab("erreur_caract_login");
					$retry = true;
				}
			}
			/* Modification d'un utilisateur */
			else if ($p_type == 2)
			{
				// un gestionnaire d'utilisateurs ne peut pas modifier un administrateur général ou un gestionnaire d'utilisateurs
				$test_statut = TRUE;
				if (SecuAccess::UserLevel(getUserName(),-1) < 6)
				{
					$old_statut = grr_sql_query1("SELECT statut FROM ".TABLE_PREFIX."_utilisateurs WHERE login='".SecuChaine::ProtectDataSql($p_user_login)."'");
					if (((($old_statut == "administrateur") || ($old_statut == "gestionnaire_utilisateur")) && ($old_statut != $p_statut))
						|| ((($old_statut == "utilisateur") || ($old_statut == "visiteur")) && (($p_statut == "administrateur") || ($p_statut == "gestionnaire_utilisateur"))))
						$test_statut = FALSE;
				}

				if(Settings::get("mail_user_unique") == 1){
					$nbEmail = "SELECT COUNT(*) FROM ".TABLE_PREFIX."_utilisateurs WHERE email = '".SecuChaine::ProtectDataSql($p_email)."' AND login <> '".SecuChaine::ProtectDataSql($p_user_login)."'";
					if(grr_sql_query1($nbEmail) > 0){
						$msg = get_vocab("mail_user_unique_error");
						$retry = true;
					}
				}

				if (!($test_statut))
				{
					$msg = get_vocab("erreur_choix_statut");
					$retry = true;
				}
				else if ($p_source == "local")
				{
					// On demande un changement de la source ext->local
					if (($p_password == '') && ($p_password2 == ''))
					{
						$old_mdp = grr_sql_query1("SELECT password FROM ".TABLE_PREFIX."_utilisateurs WHERE login='".SecuChaine::ProtectDataSql($p_user_login)."'");
						if (($old_mdp == '') || ($old_mdp == -1))
						{
							$msg = get_vocab("passwd_error");
							$retry = true;
						}
						else
							$p_password_c = '';
					}
					else
					{
						$p_password_c =  password_hash($p_password,PASSWORD_DEFAULT);
						if (($p_password != $p_password2) || (check_password_difficult($p_password) == false))
						{
							$msg = get_vocab("passwd_error");
							$retry = true;
						}
					}
				}
				if ($retry == false)
				{
					$sql = "UPDATE ".TABLE_PREFIX."_utilisateurs SET nom='".SecuChaine::ProtectDataSql($p_nom)."',
					prenom='".SecuChaine::ProtectDataSql($p_prenom)."',
					statut='".SecuChaine::ProtectDataSql($p_statut)."',
					changepwd='".SecuChaine::ProtectDataSql($p_changepwd)."',
					email='".SecuChaine::ProtectDataSql($p_email)."',";
					if ($p_source=="local")
					{
						$sql .= "source='local',";
						if ($p_password_c!='')
							$sql .= "password='".SecuChaine::ProtectDataSql($p_password_c)."',";
					}
					else
						$sql .= "source='ext',password='',";
					$sql .= "etat='".SecuChaine::ProtectDataSql($p_etat)."',
					commentaire='".SecuChaine::ProtectDataSql($p_commentaire)."',
					desactive_mail='".SecuChaine::ProtectDataSql($p_desactive_mail)."',
					default_site='".SecuChaine::ProtectDataSql($p_id_site)."',
					default_area='".SecuChaine::ProtectDataSql($p_id_area)."',
					default_room='".SecuChaine::ProtectDataSql($p_id_room)."',
					default_style='".SecuChaine::ProtectDataSql($p_default_css)."',
					default_list_type='".SecuChaine::ProtectDataSql($p_area_list_format)."',
					default_language='".SecuChaine::ProtectDataSql($p_default_language)."' 
					WHERE login='".SecuChaine::ProtectDataSql($p_user_login)."'";

					if (grr_sql_command($sql) < 0)
					{
						fatal_error(0, get_vocab("message_records_error") . grr_sql_error());
					}

					// Cas où on a déclaré un utilisateur inactif, on le supprime dans les tables ".TABLE_PREFIX."_j_user_area,  ".TABLE_PREFIX."_j_mailuser_room
					if ($p_etat != 'actif')
					{
						$sql = "DELETE FROM ".TABLE_PREFIX."_j_user_area WHERE login='$p_user_login'";
						if (grr_sql_command($sql) < 0)
							fatal_error(0, get_vocab('message_records_error') . grr_sql_error());
						$sql = "DELETE FROM ".TABLE_PREFIX."_j_user_site WHERE login='$p_user_login'";
						if (grr_sql_command($sql) < 0)
							fatal_error(0, get_vocab('message_records_error') . grr_sql_error());
						$sql = "DELETE FROM ".TABLE_PREFIX."_j_mailuser_room WHERE login='$p_user_login'";
						if (grr_sql_command($sql) < 0)
							fatal_error(0, get_vocab('message_records_error') . grr_sql_error());
						$sql = "DELETE FROM ".TABLE_PREFIX."_j_useradmin_area WHERE login='$p_user_login'";
						if (grr_sql_command($sql) < 0)
							fatal_error(0, get_vocab('message_records_error') . grr_sql_error());
						$sql = "DELETE FROM ".TABLE_PREFIX."_j_useradmin_site WHERE login='$p_user_login'";
						if (grr_sql_command($sql) < 0)
							fatal_error(0, get_vocab('message_records_error') . grr_sql_error());
					}
					// Cas où on a déclaré un utilisateur visiteur, on le supprime dans les tables ".TABLE_PREFIX."_j_user_area, ".TABLE_PREFIX."_j_mailuser_room et ".TABLE_PREFIX."_j_user_room
					if ($p_statut == 'visiteur')
					{
						$sql = "DELETE FROM ".TABLE_PREFIX."_j_user_room WHERE login='$p_user_login'";
						if (grr_sql_command($sql) < 0)
							fatal_error(0, get_vocab('message_records_error') . grr_sql_error());
						$sql = "DELETE FROM ".TABLE_PREFIX."_j_mailuser_room WHERE login='$p_user_login'";
						if (grr_sql_command($sql) < 0)
							fatal_error(0, get_vocab('message_records_error') . grr_sql_error());
						$sql = "DELETE FROM ".TABLE_PREFIX."_j_user_area WHERE login='$p_user_login'";
						if (grr_sql_command($sql) < 0)
							fatal_error(0, get_vocab('message_records_error') . grr_sql_error());
						$sql = "DELETE FROM ".TABLE_PREFIX."_j_user_site WHERE login='$p_user_login'";
						if (grr_sql_command($sql) < 0)
							fatal_error(0, get_vocab('message_records_error') . grr_sql_error());
						$sql = "DELETE FROM ".TABLE_PREFIX."_j_useradmin_area WHERE login='$p_user_login'";
						if (grr_sql_command($sql) < 0)
							fatal_error(0, get_vocab('message_records_error') . grr_sql_error());
						$sql = "DELETE FROM ".TABLE_PREFIX."_j_useradmin_site WHERE login='$p_user_login'";
						if (grr_sql_command($sql) < 0)
							fatal_error(0, get_vocab('message_records_error') . grr_sql_error());
					}
					if ($p_statut == 'administrateur')
					{
						$sql = "DELETE FROM ".TABLE_PREFIX."_j_user_room WHERE login='$p_user_login'";
						if (grr_sql_command($sql) < 0)
							fatal_error(0, get_vocab('message_records_error') . grr_sql_error());
						$sql = "DELETE FROM ".TABLE_PREFIX."_j_user_area WHERE login='$p_user_login'";
						if (grr_sql_command($sql) < 0)
							fatal_error(0, get_vocab('message_records_error') . grr_sql_error());
						$sql = "DELETE FROM ".TABLE_PREFIX."_j_user_site WHERE login='$p_user_login'";
						if (grr_sql_command($sql) < 0)
							fatal_error(0, get_vocab('message_records_error') . grr_sql_error());
						$sql = "DELETE FROM ".TABLE_PREFIX."_j_useradmin_area WHERE login='$p_user_login'";
						if (grr_sql_command($sql) < 0)
							fatal_error(0, get_vocab('message_records_error') . grr_sql_error());
						$sql = "DELETE FROM ".TABLE_PREFIX."_j_useradmin_site WHERE login='$p_user_login'";
						if (grr_sql_command($sql) < 0)
							fatal_error(0, get_vocab('message_records_error') . grr_sql_error());
					}
					/* Groupes */
					//Supression
					$sql = "SELECT idgroupes FROM ".TABLE_PREFIX."_utilisateurs_groupes WHERE login='$p_user_login'";
					$res = grr_sql_query($sql);
					if ($res)
					{
						for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
						{
							$sql = "DELETE FROM ".TABLE_PREFIX."_utilisateurs_groupes WHERE login='$p_user_login'";
							if (grr_sql_command($sql) < 0)
								fatal_error(0, get_vocab('message_records_error') . grr_sql_error());

							synchro_groupe($row[0], 0);
						}
					}
					// Insertion
					if(isset($p_groupes) && !empty($p_groupes)){
						foreach ($p_groupes as $valeur)
						{
							if ($valeur != '')
							{
								$sql = "INSERT INTO ".TABLE_PREFIX."_utilisateurs_groupes SET login= '$p_user_login', idgroupes = '$valeur'";
								if (grr_sql_command($sql) < 0)
									fatal_error(1, "<p>" . grr_sql_error());
								synchro_groupe($valeur, 0);
							}
						}
					}
					//Fin des groupes

				}
			}
		}
		if ($retry == true) // Formulaire invalide, on le recharge avec les valeurs nouvellement saisie
		{
			$d['enregistrement'] = 2;
			$d['message_erreur'] = $msg;
			$user_nom = $p_nom;
			$user_prenom = $p_prenom;
			$user_statut = $p_statut;
			$user_mail = $p_email;
			$user_etat = $p_etat;
		} else
		{
 			$d['enregistrement'] = 1;
		}
	}



/** Affichage de la page **/
	$trad['TitrePage']	= $trad['admin_user'];
	if ($p_user_login != '')
		$trad['SousTitrePage'] = get_vocab('change');
	else
		$trad['SousTitrePage'] = get_vocab('add');

	if (Settings::get('module_multisite') == 1) {
		$d['use_site'] = 'y';
	} else {
		$d['use_site'] = 'n';
	}

	// On appelle les informations de l'utilisateur dans le cas d'une modification
	if ($p_user_login != '')
	{
		$res = grr_sql_query("SELECT nom, prenom, statut, etat, email, source, changepwd, default_site, default_area, default_room, default_style, default_list_type, default_language, commentaire, desactive_mail FROM ".TABLE_PREFIX."_utilisateurs WHERE login='$p_user_login'");
		if (!$res)
			fatal_error(0, get_vocab('message_records_error'));
		$utilisateur = grr_sql_row_keyed($res, 0);
		grr_sql_free($res);
	}

	// Liste des sites
	if (Settings::get('module_multisite') == 1) {
		$sql = 'SELECT id,sitecode,sitename
		FROM '.TABLE_PREFIX.'_site
		ORDER BY id ASC';
		$resultat = grr_sql_query($sql);

		$d['optionSite'] = "";
		for ($enr = 0; ($row = grr_sql_row($resultat, $enr)); ++$enr) {
			$d['optionSite'] .= '<option value="'.$row[0].'"';
			if ($p_user_login != '' && $utilisateur['default_site'] == $row[0]) {
				$d['optionSite'] .= ' selected="selected" ';
			}
			$d['optionSite'] .= '>'.htmlspecialchars($row[2]);
			$d['optionSite'] .= '</option>'."\n";
		}
	}

	// Choix de la feuille de style
	$i = 0;
	$d['optionTheme'] = "";
	while ($i < count($liste_themes)) {
		$d['optionTheme'] .= "<option value='".$liste_themes[$i]."'";
		if ($p_user_login != '' && $utilisateur['default_style'] == $liste_themes[$i]) {
			$d['optionTheme'] .= ' selected="selected"';
		}
		$d['optionTheme'] .= ' >'.encode_message_utf8($liste_name_themes[$i]).'</option>';
		++$i;
	}

	// Choix de la langue
	$i = 0;
	$d['optionLangue'] = "";
	while ($i < count($liste_language)) {
		$d['optionLangue'] .= "<option value='".$liste_language[$i]."'";
		if ($p_user_login != '' && $utilisateur['default_language'] == $liste_language[$i]) {
			$d['optionLangue'] .= ' selected="selected"';
		}
		$d['optionLangue'] .= ' >'.encode_message_utf8($trad['langue_' . $liste_language[$i]]).'</option>'.PHP_EOL;
		++$i;
	}

	if (SecuAccess::UserLevel(getUserName(),-1) >= 6)
		$d['estAdministrateur'] = 1;

	if (isset($p_user_login) && strtolower(getUserName()) != strtolower($p_user_login))
		$d['estPasLuiMeme'] = 1;


	$utilisateur['reg_login'] = $p_user_login;


/* Groupes */
	$groupesajoutable = array();
	$groupespresent = array();

	$sql = "SELECT idgroupes, nom, archive FROM ".TABLE_PREFIX."_groupes ORDER BY nom ASC";
	$res = grr_sql_query($sql);
	if ($res)
	{
		for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
		{
			$req_ug = "SELECT idutilisateursgroupes FROM ".TABLE_PREFIX."_utilisateurs_groupes WHERE login = '".$p_user_login."' AND idgroupes = '".$row[0]."'";
			$req_ug = grr_sql_query($req_ug);
			if (grr_sql_count($req_ug) > 0) // Il est dans le groupe
			{
				$groupespresent[] = array('idgroupes' => $row[0], 'nom' => $row[1] );
			}
			else
			{
				if( $row[2] == 0)
					$groupesajoutable[] = array('idgroupes' => $row[0], 'nom' => $row[1] );
			}

		}
	}


/* Test des privilèges*/
	$dAdministrateurDomaine = "";
	$dAdministrateurSite = "";
		
	if ((isset($p_user_login)) && ($p_user_login != ''))
	{
		$a_privileges = 'n';

		if (Settings::get("module_multisite") == 1){
			$req_site = "SELECT id, sitename, access FROM ".TABLE_PREFIX."_site ORDER BY sitename";
			$res_site = grr_sql_query($req_site);
			if ($res_site)
			{
				for ($i = 0; ($row_site = grr_sql_row($res_site, $i)); $i++)
				{
					$test_admin = grr_sql_query1("SELECT count(id_site) FROM ".TABLE_PREFIX."_j_useradmin_site j where j.login = '".$p_user_login."' and j.id_site='".$row_site[0]."'");
					if ($test_admin >= 1)
						$is_admin = 'y';
					else
						$is_admin = 'n';
					$nb_room = grr_sql_query1("SELECT count(r.room_name) FROM ".TABLE_PREFIX."_room r
						left join ".TABLE_PREFIX."_area a on r.area_id=a.id
						left join ".TABLE_PREFIX."_j_site_area sa on a.id=sa.id_area
						left join ".TABLE_PREFIX."_site s on sa.id_site=s.id
						where s.id='".$row_site[0]."'");
					$req_room = "SELECT r.room_name FROM ".TABLE_PREFIX."_room r
					left join ".TABLE_PREFIX."_j_user_room j on r.id=j.id_room
					left join ".TABLE_PREFIX."_area a on r.area_id=a.id
					left join ".TABLE_PREFIX."_j_site_area sa on a.id=sa.id_area
					left join ".TABLE_PREFIX."_site s on sa.id_site=s.id
					where j.login = '".$p_user_login."' and s.id='".$row_site[0]."'";
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
					left join ".TABLE_PREFIX."_j_site_area sa on a.id=sa.id_area
					left join ".TABLE_PREFIX."_site s on sa.id_site=s.id
					where j.login = '".$p_user_login."' and s.id='".$row_site[0]."'";
					$res_mail = grr_sql_query($req_mail);
					$is_mail = '';
					if ($res_mail)
					{
						for ($j = 0; ($row_mail = grr_sql_row($res_mail, $j)); $j++)
						{
							$is_mail .= $row_mail[0]."<br />";
						}
					}
					if ($row_site[2] == 'r')
					{
						$test_restreint = grr_sql_query1("SELECT count(id_site) from ".TABLE_PREFIX."_j_user_site j where j.login = '".$p_user_login."' and j.id_site='".$row_site[0]."'");
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
						$dAdministrateurSite .= "<li>".get_vocab("match_site")." ".$row_site[1];
						if ($row_site[2] == 'r')
							$dAdministrateurSite .= " (".$vocab["restricted"].")";
	
						$dAdministrateurSite .= get_vocab("deux_points");
						$dAdministrateurSite .= "<ul>";
						
						if ($is_admin == 'y')
							$dAdministrateurSite .= "<li>".get_vocab("administrateur_du_site")."</li>";
						if ($is_restreint == 'y')
							$dAdministrateurSite .= "<li>".get_vocab("a_acces_au_site")."</li>";
						if ($is_gestionnaire != '')
						{
							$dAdministrateurSite .= "<li>".get_vocab("gestionnaire_des_ressources_suivantes")."<br />";
							$dAdministrateurSite .= $is_gestionnaire;
							$dAdministrateurSite .= "</li>";
						}
						if ($is_mail != '')
						{
							$dAdministrateurSite .= "<li>".get_vocab("est_prevenu_par_mail")."<br />";
							$dAdministrateurSite .= $is_mail;
							$dAdministrateurSite .= "</li>";
						}
						$dAdministrateurSite .= "</ul>";
						
						$d['AdministrateurSite'] = $dAdministrateurSite;
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
				$test_admin = grr_sql_query1("SELECT count(id_area) FROM ".TABLE_PREFIX."_j_useradmin_area j where j.login = '".$p_user_login."' and j.id_area='".$row_area[0]."'");
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
				where j.login = '".$p_user_login."' and a.id='".$row_area[0]."'";
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
				where j.login = '".$p_user_login."' and a.id='".$row_area[0]."'";
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
					$test_restreint = grr_sql_query1("SELECT count(id_area) from ".TABLE_PREFIX."_j_user_area j where j.login = '".$p_user_login."' and j.id_area='".$row_area[0]."'");
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
					$dAdministrateurDomaine .= "<li>".get_vocab("match_area")." ".$row_area[1];
					if ($row_area[2] == 'r')
						$dAdministrateurDomaine .= " (".$vocab["restricted"].")";

					$dAdministrateurDomaine .= get_vocab("deux_points");
					$dAdministrateurDomaine .= "<ul>";
					
					if ($is_admin == 'y')
						$dAdministrateurDomaine .= "<li>".get_vocab("administrateur_du_domaine")."</li>";
					if ($is_restreint == 'y')
						$dAdministrateurDomaine .= "<li>".get_vocab("a_acces_au_domaine")."</li>";
					if ($is_gestionnaire != '')
					{
						$dAdministrateurDomaine .= "<li>".get_vocab("gestionnaire_des_ressources_suivantes")."<br />";
						$dAdministrateurDomaine .= $is_gestionnaire;
						$dAdministrateurDomaine .= "</li>";
					}
					if ($is_mail != '')
					{
						$dAdministrateurDomaine .= "<li>".get_vocab("est_prevenu_par_mail")."<br />";
						$dAdministrateurDomaine .= $is_mail;
						$dAdministrateurDomaine .= "</li>";
					}
					$dAdministrateurDomaine .= "</ul>";
					
					$d['AdministrateurDomaine'] = $dAdministrateurDomaine;
				}
			}
		}

		// peut réserver une ressource restreinte ?
		$req_room = "SELECT r.id, r.room_name FROM ".TABLE_PREFIX."_room r JOIN ".TABLE_PREFIX."_j_userbook_room j ON j.id_room = r.id WHERE j.login = '".$p_user_login."'";
		$res_room = grr_sql_query($req_room);
		if ($res_room && grr_sql_count($res_room)>0){
			$ressoureceRestreinte = "<h3>".get_vocab('user_can_book')."</h3><ul>";
			while($room = mysqli_fetch_array($res_room)){
				$ressoureceRestreinte .= "<li>".$room['room_name']." (".$room['id'].") </li>";
			}
			$ressoureceRestreinte .= "</ul>";
			$a_privileges = 'y';
			$d['ressoureceRestreinte'] = $ressoureceRestreinte;
		}
		grr_sql_free($res_room);

		if ($a_privileges == 'n')
		{
			if ($utilisateur['statut'] == 'administrateur')
				$d['AdministrateurOuRien'] = "<li>".get_vocab("administrateur_general")."</li>";
			else
				$d['AdministrateurOuRien'] = "<li>".get_vocab("pas_de_privileges")."</li>";
		}
	}

echo $twig->render($page.'.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings, 'utilisateur' => $utilisateur, 'groupesajoutable' => $groupesajoutable, 'groupespresent' => $groupespresent));
?>