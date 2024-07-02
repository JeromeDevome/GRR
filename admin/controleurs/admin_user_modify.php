<?php
/**
 * admin_user_modify.php
 * Interface de modification/création d'un utilisateur de l'application GRR
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2017-12-16 14:00$
 * @author    Laurent Delineau & JeromeB
 * @copyright Copyright 2003-2020 Team DEVOME - JeromeB
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

$trad = $vocab;

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
#If we dont know the right date then make it up
unset($user_login);
$user_login = isset($_GET["user_login"]) ? $_GET["user_login"] : NULL;
$valid = isset($_GET["valid"]) ? $_GET["valid"] : NULL;
$msg = '';
$user_nom = '';
$user_prenom = '';
$user_mail = '';
$user_statut = '';
$user_source = 'local';
$user_etat = '';
$display = "";
$retry = '';

if (Settings::get('module_multisite') == 'Oui') {
	$d['use_site'] = 'y';
} else {
	$d['use_site'] = 'n';
}

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
	$reg_etat = isset($_GET["reg_etat"]) ? $_GET["reg_etat"] : "actif";
	$reg_source = isset($_GET["reg_source"]) ? $_GET["reg_source"] : "local";
	$reg_site = isset($_GET["id_site"]) ? $_GET["id_site"] : -1;
	$reg_area = isset($_GET["id_area"]) ? $_GET["id_area"] : -1;
	$reg_room = isset($_GET["id_room"]) ? $_GET["id_room"] : -1;
	$reg_style = isset($_GET["default_css"]) ? $_GET["default_css"] : "default";
	$reg_list = isset($_GET["area_list_format"]) ? $_GET["area_list_format"] : "item";
	$reg_langue = isset($_GET["default_language"]) ? $_GET["default_language"] : "fr-fr";
	$groupes_select = isset($_GET["groupes"]) ? $_GET["groupes"] : NULL;
	if ($reg_source != "local")
		$reg_password = "";
	if ($reg_etat == "")
		$reg_etat = "actif";
	if (($reg_nom == '') || ($reg_prenom == ''))
	{
		$msg = get_vocab("please_enter_name");
		$retry = 'yes';
	}
	else
	{
		//
		// actions si un nouvel utilisateur a été défini
		//
		
		if ((isset($new_login)) && ($new_login != '') )
		{
			$new_login = trim($new_login);
			$test_login = preg_replace("/([A-Za-z0-9_@.-])/","",$new_login);
			if($test_login == ""){
				// un gestionnaire d'utilisateurs ne peut pas créer un administrateur général ou un gestionnaire d'utilisateurs
				$test_statut = TRUE;
				if (authGetUserLevel(getUserName(),-1) < 6)
				{
					if (($reg_statut == "administrateur") || ($reg_statut == "gestionnaire_utilisateur"))
						$test_statut = FALSE;
				}
				$new_login = strtoupper($new_login);
				if ($reg_password !='')
					$reg_password_c = password_hash($reg_password,PASSWORD_DEFAULT);
				else
				{
					if ($reg_source != "local")
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
				else if ((($reg_password != $reg_password2) || (strlen($reg_password) < $pass_leng)) && ($reg_source == "local"))
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
						default_site = '-1',
						default_area = '-1',
						default_room = '-1',
						default_style = '',
						default_list_type = 'item',
						default_language = 'fr-fr',";
						if ($reg_source=="local")
							$sql .= "source='local'";
						else
							$sql .= "source='ext'";
						if (grr_sql_command($sql) < 0)
						{
							fatal_error(0, get_vocab("msg_login_created_error") . grr_sql_error());
						}
						$user_login = $new_login;

						// Groupes
						$sql = "DELETE FROM ".TABLE_PREFIX."_utilisateurs_groupes WHERE login='$new_login'";
						if (grr_sql_command($sql) < 0)
							fatal_error(0, get_vocab('message_records_error') . grr_sql_error());

						if(isset($groupes_select) && !empty($groupes_select)){
							foreach ($groupes_select as $valeur)
							{
								if ($valeur != '')
								{
									$sql = "INSERT INTO ".TABLE_PREFIX."_utilisateurs_groupes SET login= '$new_login', idgroupes = '$valeur'";
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
				$msg = get_vocab("erreur_caract_login");
//
//action s'il s'agit d'une modification
//
		}
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
			else if ($reg_source == "local")
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
					$reg_password_c =  password_hash($reg_password,PASSWORD_DEFAULT);
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
				if ($reg_source=="local")
				{
					$sql .= "source='local',";
					if ($reg_password_c!='')
						$sql .= "password='".protect_data_sql($reg_password_c)."',";
				}
				else
					$sql .= "source='ext',password='',";
				$sql .= "etat='".protect_data_sql($reg_etat)."',
				default_site='".protect_data_sql($reg_site)."',
				default_area='".protect_data_sql($reg_area)."',
				default_room='".protect_data_sql($reg_room)."',
				default_style='".protect_data_sql($reg_style)."',
				default_list_type='".protect_data_sql($reg_list)."',
				default_language='".protect_data_sql($reg_langue)."' 
				WHERE login='".protect_data_sql($user_login)."'";

				if (grr_sql_command($sql) < 0)
				{
					fatal_error(0, get_vocab("message_records_error") . grr_sql_error());
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
				// Groupes
				$sql = "DELETE FROM ".TABLE_PREFIX."_utilisateurs_groupes WHERE login='$user_login'";
				if (grr_sql_command($sql) < 0)
					fatal_error(0, get_vocab('message_records_error') . grr_sql_error());

				if(isset($groupes_select) && !empty($groupes_select)){
					foreach ($groupes_select as $valeur)
					{
						if ($valeur != '')
						{
							$sql = "INSERT INTO ".TABLE_PREFIX."_utilisateurs_groupes SET login= '$user_login', idgroupes = '$valeur'";
							if (grr_sql_command($sql) < 0)
								fatal_error(1, "<p>" . grr_sql_error());
						}
					}
				}
				//Fin des groupes

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
		$user_nom = $reg_nom;
		$user_prenom = $reg_prenom;
		$user_statut = $reg_statut;
		$user_mail = $reg_email;
		$user_etat = $reg_etat;
	}
}


// Si pas de problème, message de confirmation
if (isset($_GET['valid'])) {
    $_SESSION['displ_msg'] = 'yes';
    if ($msg == '') {
        $d['enregistrement'] = 1;
    } else{
        $d['enregistrement'] = $msg;
    }
}
if ((isset($_GET['msg'])) && isset($_SESSION['displ_msg']) && ($_SESSION['displ_msg'] == 'yes')) {
    $msg = $_GET['msg'];
} else {
    $msg = '';
}


// On appelle les informations de l'utilisateur pour les afficher :
if (isset($user_login) && ($user_login != ''))
{
	$res = grr_sql_query("SELECT nom, prenom, statut, etat, email, source, changepwd, default_site, default_area, default_room, default_style, default_list_type, default_language FROM ".TABLE_PREFIX."_utilisateurs WHERE login='$user_login'");
	if (!$res)
		fatal_error(0, get_vocab('message_records_error'));
	$utilisateur = grr_sql_row_keyed($res, 0);
	grr_sql_free($res);

	// Liste des sites
	if (Settings::get('module_multisite') == 'Oui') {
		$sql = 'SELECT id,sitecode,sitename
		FROM '.TABLE_PREFIX.'_site
		ORDER BY id ASC';
		$resultat = grr_sql_query($sql);

		$d['optionSite'] = "";
		for ($enr = 0; ($row = grr_sql_row($resultat, $enr)); ++$enr) {
			$d['optionSite'] .= '<option value="'.$row[0].'"';
			if ($utilisateur['default_site'] == $row[0]) {
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
		if ($utilisateur['default_style'] == $liste_themes[$i]) {
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
		if ($utilisateur['default_language'] == $liste_language[$i]) {
			$d['optionLangue'] .= ' selected="selected"';
		}
		$d['optionLangue'] .= ' >'.encode_message_utf8($liste_name_language[$i]).'</option>'.PHP_EOL;
		++$i;
	}

} else
{
	$d['estPasLuiMeme'] = 1;
}
if ((authGetUserLevel(getUserName(), -1) < 1) && (Settings::get("authentification_obli") == 1))
{
	showAccessDenied($back);
	exit();
}


// Affichage d'un pop-up
affiche_pop_up($msg,"admin");

if (isset($user_login) && ($user_login != ''))
	$trad['admin_user_modify_modify'] = get_vocab('admin_user_modify_modify');
else
	$trad['admin_user_modify_modify'] = get_vocab('admin_user_modify_create');

if ((Settings::get("sso_statut") != "") || (Settings::get("ldap_statut") != '') || (Settings::get("imap_statut") != ''))
	$d['connexionExterne'] = 1;

if (authGetUserLevel(getUserName(),-1) >= 6)
	$d['estAdministrateur'] = 1;

if (isset($user_login) && strtolower(getUserName()) != strtolower($user_login))
	$d['estPasLuiMeme'] = 1;

$d['display'] = $display;


$utilisateur['reg_login'] = $user_login;


/* Groupes */
$groupesajoutable = array();
$groupespresent = array();

$sql = "SELECT idgroupes, nom, archive FROM ".TABLE_PREFIX."_groupes ORDER BY nom ASC";
$res = grr_sql_query($sql);
if ($res)
{
	for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
	{
		$req_ug = "SELECT idutilisateursgroupes FROM ".TABLE_PREFIX."_utilisateurs_groupes WHERE login = '".$user_login."' AND idgroupes = '".$row[0]."'";
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
	
	if ((isset($user_login)) && ($user_login != ''))
	{
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
						$d['AdministrateurSite'] = "<li>".get_vocab("site")." ".$row_site[1].get_vocab("deux_points")." ".get_vocab("administrateur_du_site")."</li>";
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
						$dAdministrateurDomaine .= "<li>".get_vocab("gestionnaire_des_resources_suivantes")."<br />";
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
		$req_room = "SELECT r.id, r.room_name FROM ".TABLE_PREFIX."_room r JOIN ".TABLE_PREFIX."_j_userbook_room j ON j.id_room = r.id WHERE j.login = '".$user_login."'";
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