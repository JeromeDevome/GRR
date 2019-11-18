<?php
/**
 * admin_edit_room.php
 * Interface de creation/modification des sites, domaines et des ressources de l'application GRR
 * Dernière modification : $Date: 2019-10-09 18:40$
 * @author    Laurent Delineau & JeromeB & Marc-Henri PAMISEU & Yan Naessens
 * @copyright Copyright 2003-2019 Team DEVOME - JeromeB
 * @link      http://www.gnu.org/licenses/licenses.html
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */

$grr_script_name = "admin_edit_room.php";

$ok = NULL;
if (Settings::get("module_multisite") == "Oui")
	$id_site = isset($_POST["id_site"]) ? $_POST["id_site"] : (isset($_GET["id_site"]) ? $_GET["id_site"] : -1);
$action = isset($_POST["action"]) ? $_POST["action"] : (isset($_GET["action"]) ? $_GET["action"] : NULL);
$area_id = isset($_POST["area_id"]) ? $_POST["area_id"] : (isset($_GET["area_id"]) ? $_GET["area_id"] : NULL);
$retour_page = isset($_POST["retour_page"]) ? $_POST["retour_page"] : (isset($_GET["retour_page"]) ? $_GET["retour_page"] : NULL);
$room = isset($_POST["room"]) ? $_POST["room"] : (isset($_GET["room"]) ? $_GET["room"] : NULL);
$area_name = isset($_POST["area_name"]) ? $_POST["area_name"] : NULL;
$room_name = isset($_POST["room_name"]) ? $_POST["room_name"] : NULL;
$description = isset($_POST["description"]) ? $_POST["description"] : NULL;
$capacity = isset($_POST["capacity"]) ? $_POST["capacity"] : NULL;
$delais_max_resa_room  = isset($_POST["delais_max_resa_room"]) ? $_POST["delais_max_resa_room"] : NULL;
$delais_min_resa_room  = isset($_POST["delais_min_resa_room"]) ? $_POST["delais_min_resa_room"] : NULL;
$delais_option_reservation  = isset($_POST["delais_option_reservation"]) ? $_POST["delais_option_reservation"] : NULL;
$allow_action_in_past  = isset($_POST["allow_action_in_past"]) ? $_POST["allow_action_in_past"] : NULL;
$dont_allow_modify  = isset($_POST["dont_allow_modify"]) ? $_POST["dont_allow_modify"] : NULL;
$qui_peut_reserver_pour  = isset($_POST["qui_peut_reserver_pour"]) ? $_POST["qui_peut_reserver_pour"] : NULL;
$who_can_see  = isset($_POST["who_can_see"]) ? $_POST["who_can_see"] : NULL;
$max_booking = isset($_POST["max_booking"]) ? $_POST["max_booking"] : NULL;
settype($max_booking, "integer");
if ($max_booking<-1)
	$max_booking = -1;
$statut_room = isset($_POST["statut_room"]) ? "0" : "1";
$show_fic_room = isset($_POST["show_fic_room"]) ? "y" : "n";
if (isset($_POST["active_ressource_empruntee"]))
	$active_ressource_empruntee = 'y';
else
{
	$active_ressource_empruntee = 'n';
	// toutes les reservations sont considerees comme restituee
	grr_sql_query("update ".TABLE_PREFIX."_entry set statut_entry = '-' where room_id = '".$room."'");
}
if (isset($_POST["active_cle"]))
	$active_cle = 'y';
else
{
	$active_cle = 'n';
	// toutes les reservations sont considerees comme restituee
	grr_sql_query("update ".TABLE_PREFIX."_entry set statut_entry = '-' where room_id = '".$room."'");
}
$picture_room = isset($_POST["picture_room"]) ? $_POST["picture_room"] : NULL;
$comment_room = isset($_POST["comment_room"]) ? $_POST["comment_room"] : NULL;
$show_comment = isset($_POST["show_comment"]) ? "y" : "n";
$change_done = isset($_POST["change_done"]) ? $_POST["change_done"] : NULL;
if(!isset($_POST["area_order"]) || empty($_POST["area_order"])){
	$area_order = 0;
} else{
	$area_order = $_POST["area_order"];
}
$change_room = isset($_POST["change_room"]) ? $_POST["change_room"] : NULL;
$type_affichage_reser = isset($_POST["type_affichage_reser"]) ? $_POST["type_affichage_reser"] : NULL;
$moderate = isset($_POST['moderate']) ? $_POST["moderate"] : NULL;
if ($moderate == 'on')
	$moderate = 1;
else
	$moderate = 0;
settype($type_affichage_reser, "integer");

if (isset($_POST["change_room_and_back"]))
{
	$change_room = "yes";
	$change_done = "yes";
}

// memorisation du chemin de retour
if (!isset($retour_page))
{
	$retour_page = $back;
	// on nettoie la chaine :
	$long_chaine_a_supprimer = strlen(strstr($retour_page, "&amp;msg=")); // longueur de la chaine e partir de la premiere occurence de &amp;msg=
	if ($long_chaine_a_supprimer == 0)
		$long_chaine_a_supprimer = strlen(strstr($retour_page, "?msg="));
	$long = strlen($retour_page) - $long_chaine_a_supprimer;
	$retour_page = substr($retour_page, 0, $long);
}
// modification d'une resource : admin ou gestionnaire
if (authGetUserLevel(getUserName(),-1) < 6)
{
	// Il s'agit d'une modif de ressource
	if (((authGetUserLevel(getUserName(),$room) < 3)) || (!verif_acces_ressource(getUserName(), $room)))
	{
		showAccessDenied($back);
		exit();
	}
}
$msg ='';

	// Enregistrement d'une ressource
	if (isset($change_room))
	{
		if (isset($_POST['sup_img']))
		{
			$dest = '../images/';
			$ok1 = false;
			if ($f = @fopen("$dest/.test", "w"))
			{
				@fputs($f, '<'.'?php $ok1 = true; ?'.'>');
				@fclose($f);
				include("$dest/.test");
			}
			if (!$ok1)
			{
				$msg .= "L\'image n\'a pas pu être supprimee : probleme d\'écriture sur le repertoire. Veuillez signaler ce probleme e l\'administrateur du serveur.\\n";
				$ok = 'no';
			}
			else
			{
				if (@file_exists($dest."img_".TABLE_PREFIX."".$room.".jpg"))
					unlink($dest."img_".TABLE_PREFIX."".$room.".jpg");
				if (@file_exists($dest."img_".TABLE_PREFIX."".$room.".png"))
					unlink($dest."img_".TABLE_PREFIX."".$room.".png");
				if (@file_exists($dest."img_".TABLE_PREFIX."".$room.".gif"))
					unlink($dest."img_".TABLE_PREFIX."".$room.".gif");
				$picture_room = "";
			}
		}
		if (empty($capacity))
			$capacity = 0;
		if ($capacity < 0)
			$capacity = 0;
		settype($delais_max_resa_room,"integer");
		if ($delais_max_resa_room < 0)
			$delais_max_resa_room = -1;
		settype($delais_min_resa_room,"integer");
		if ($delais_min_resa_room < 0)
			$delais_min_resa_room = 0;
		settype($delais_option_reservation,"integer");
		if ($delais_option_reservation < 0)
			$delais_option_reservation = 0;
		if ($allow_action_in_past == '')
			$allow_action_in_past = 'n';
		if ($dont_allow_modify == '')
			$dont_allow_modify = 'n';
		if ((isset($room)) && !((isset($action) && ($action == "duplique_room"))))
		{
			$sql = "UPDATE ".TABLE_PREFIX."_room SET
			room_name='".protect_data_sql($room_name)."',
			description='".protect_data_sql($description)."', ";
			if ($picture_room != '')
				$sql .= "picture_room='".protect_data_sql($picture_room)."', ";
			$sql .= "comment_room='".protect_data_sql(corriger_caracteres($comment_room))."',
			show_comment='".$show_comment."',
			area_id='".$area_id."',
			show_fic_room='".$show_fic_room."',
			active_ressource_empruntee = '".$active_ressource_empruntee."',
			active_cle = '".$active_cle."',
			capacity='".$capacity."',
			delais_max_resa_room='".$delais_max_resa_room."',
			delais_min_resa_room='".$delais_min_resa_room."',
			delais_option_reservation='".$delais_option_reservation."',
			allow_action_in_past='".$allow_action_in_past."',
			dont_allow_modify='".$dont_allow_modify."',
			qui_peut_reserver_pour = '".$qui_peut_reserver_pour."',
			who_can_see = '".$who_can_see."',
			order_display='".protect_data_sql($area_order)."',
			type_affichage_reser='".$type_affichage_reser."',
			max_booking='".$max_booking."',
			moderate='".$moderate."',
			statut_room='".$statut_room."'
			WHERE id=$room";
			if (grr_sql_command($sql) < 0)
			{
				fatal_error(0, get_vocab('update_room_failed') . grr_sql_error());
				$ok = 'no';
			}
		}
		else
		{
			$sql = "insert into ".TABLE_PREFIX."_room
			SET room_name='".protect_data_sql($room_name)."',
			area_id='".$area_id."',
			description='".protect_data_sql($description)."',
			picture_room='".protect_data_sql($picture_room)."',
			comment_room='".protect_data_sql(corriger_caracteres($comment_room))."',
			show_fic_room='".$show_fic_room."',
			active_ressource_empruntee = '".$active_ressource_empruntee."',
			active_cle = '".$active_cle."',
			capacity='".$capacity."',
			delais_max_resa_room='".$delais_max_resa_room."',
			delais_min_resa_room='".$delais_min_resa_room."',
			delais_option_reservation='".$delais_option_reservation."',
			allow_action_in_past='".$allow_action_in_past."',
			dont_allow_modify='".$dont_allow_modify."',
			qui_peut_reserver_pour = '".$qui_peut_reserver_pour."',
			who_can_see = '".$who_can_see."',
			order_display='".protect_data_sql($area_order)."',
			type_affichage_reser='".$type_affichage_reser."',
			max_booking='".$max_booking."',
			moderate='".$moderate."',
			statut_room='".$statut_room."'";
			if (grr_sql_command($sql) < 0)
				fatal_error(1, "<p>" . grr_sql_error());
			$room = mysqli_insert_id($GLOBALS['db_c']);
		}
		#Si room_name est vide on le change maintenant que l'on a l'id room
		if ($room_name == '')
		{
			$room_name = get_vocab("room")." ".$room;
			grr_sql_command("UPDATE ".TABLE_PREFIX."_room SET room_name='".protect_data_sql($room_name)."' WHERE id=$room");
		}
		$doc_file = isset($_FILES["doc_file"]) ? $_FILES["doc_file"] : NULL;
		if (preg_match("`\.([^.]+)$`", $doc_file['name'], $match))
		{
			$ext = strtolower($match[1]);
			if ($ext != 'jpg' && $ext != 'png'&& $ext != 'gif')
			{
				$msg .= "L\'image n\'a pas pu être enregistrée : les seules extensions autorisees sont gif, png et jpg.\\n";
				$ok = 'no';
			}
			else
			{
				$dest = '../images/';
				$ok1 = false;
				if ($f = @fopen("$dest/.test", "w"))
				{
					@fputs($f, '<'.'?php $ok1 = true; ?'.'>');
					@fclose($f);
					include("$dest/.test");
				}
				if (!$ok1)
				{
					$msg .= "L\'image n\'a pas pu être enregistrée : probleme d\'écriture sur le repertoire IMAGES. Veuillez signaler ce probleme à l\'administrateur du serveur.\\n";
					$ok = 'no';
				}
				else
				{
					$ok1 = @copy($doc_file['tmp_name'], $dest.$doc_file['name']);
					if (!$ok1)
						$ok1 = @move_uploaded_file($doc_file['tmp_name'], $dest.$doc_file['name']);
					if (!$ok1)
					{
						$msg .= "L\'image n\'a pas pu être enregistrée : problème de transfert. Le fichier n\'a pas pu être transféré sur le répertoire IMAGES. Veuillez signaler ce probleme à l\'administrateur du serveur.\\n";
						$ok = 'no';
					}
					else
					{
						$tab = explode(".", $doc_file['name']);
						$ext = strtolower($tab[1]);
						if (@file_exists($dest."img_".TABLE_PREFIX."".$room.".".$ext))
							@unlink($dest."img_".TABLE_PREFIX."".$room.".".$ext);
						rename($dest.$doc_file['name'],$dest."img_".TABLE_PREFIX."".$room.".".$ext);
						@chmod($dest."img_".TABLE_PREFIX."".$room.".".$ext, 0666);
						$picture_room = "img_".TABLE_PREFIX."".$room.".".$ext;
						$sql_picture = "UPDATE ".TABLE_PREFIX."_room SET picture_room='".protect_data_sql($picture_room)."' WHERE id=".$room;
						if (grr_sql_command($sql_picture) < 0)
						{
							fatal_error(0, get_vocab('update_room_failed') . grr_sql_error());
							$ok = 'no';
						}
					}
				}
			}
		}
		else if ($doc_file['name'] != '')
		{
			$msg .= "L\'image n\'a pas pu être enregistrée : le fichier image sélectionné n'est pas valide !\\n";
			$ok = 'no';
		}
		$msg .= get_vocab("message_records");
	}
	// Si pas de probleme, retour à la page d'accueil après enregistrement
	if ((isset($change_done)) && (!isset($ok)))
	{
		if ($msg != '')
		{
			$_SESSION['displ_msg'] = 'yes';
			if (strpos($retour_page, ".php?") == "")
				$param = "?msg=".$msg;
			else
				$param = "&msg=".$msg;
		}
		else
			$param = '';
		Header("Location: ".$retour_page.$param);
		exit();
	}

	// affichage du formulaire
	if (isset($room))
	{
		// Il s'agit d'une modification d'une ressource
		$res = grr_sql_query("SELECT * FROM ".TABLE_PREFIX."_room WHERE id=$room");
		if (! $res)
			fatal_error(0, get_vocab('error_room') . $room . get_vocab('not_found'));
		$row = grr_sql_row_keyed($res, 0);
		grr_sql_free($res);
		$area_id = grr_sql_query1("select area_id from ".TABLE_PREFIX."_room where id='".$room."'");
		$area_name = grr_sql_query1("select area_name from ".TABLE_PREFIX."_area where id='".$area_id."'");
		
		if ($action == "duplique_room")
			$typeAction = get_vocab("duplique_ressource");
		else
			$typeAction = get_vocab("editroom");
	}
	else
	{
		// Il s'agit de l'enregistrement d'une nouvelle ressource
		$row['picture_room'] = '';
		$row["id"] = '';
		$row["room_name"]= '';
		$row["description"] = '';
		$row['comment_room'] = '';
		$row['show_comment'] = 'n';
		$row["capacity"]   = '';
		$row["delais_max_resa_room"] = -1;
		$row["delais_min_resa_room"] = 0;
		$row["delais_option_reservation"] = 0;
		$row["allow_action_in_past"] = 'n';
		$row["dont_allow_modify"] = 'n';
		$row["qui_peut_reserver_pour"] = 6;
		$row["who_can_see"] = 0;
		$row["order_display"]  = 0;
		$row["type_affichage_reser"]  = 0;
		$row["max_booking"] = -1;
		$row['statut_room'] = '1';
		$row['moderate'] = '';
		$row['show_fic_room'] = '';
		$row['active_ressource_empruntee'] = 'n';
		$row['active_cle'] = 'n';
		$area_name = grr_sql_query1("select area_name from ".TABLE_PREFIX."_area where id='".$area_id."'");

		$typeAction = get_vocab("addroom");
	}
/* Form*/


		if (isset($action))
			$trad['dHidden1'] = "<input type=\"hidden\" name=\"action\" value=\"duplique_room\" />";
		if (isset($retour_page))
			$trad['dHidden2'] = "<input type=\"hidden\" name=\"retour_page\" value=\"".$retour_page."\" />";
		if ($row["id"] != '')
			$trad['dHidden3'] = "<input type=\"hidden\" name=\"room\" value=\"".$row["id"]."\" />\n";

		get_vocab_admin("miscellaneous");
		get_vocab_admin("name");
		get_vocab_admin("description");
		get_vocab_admin("match_area");
		get_vocab_admin("choose_an_area");
		get_vocab_admin("order_display");
		get_vocab_admin("qui_peut_voir_ressource");
		get_vocab_admin("visu_fiche_description0");
		get_vocab_admin("visu_fiche_description1");
		get_vocab_admin("visu_fiche_description2");
		get_vocab_admin("visu_fiche_description3");
		get_vocab_admin("visu_fiche_description4");
		get_vocab_admin("visu_fiche_description5");
		get_vocab_admin("visu_fiche_description6");
		get_vocab_admin("declarer_ressource_indisponible");
		get_vocab_admin("explain_max_booking");
		get_vocab_admin("montrer_fiche_presentation_ressource");
		get_vocab_admin("choisir_image_ressource");
		get_vocab_admin("supprimer_image_ressource");
		get_vocab_admin("Pas_image_disponible");
		get_vocab_admin("Afficher_description_complete_dans_titre_plannings");
		get_vocab_admin("description_complete");

		get_vocab_admin("configuration_ressource");
		get_vocab_admin("type_affichage_reservation");
		get_vocab_admin("affichage_reservation_duree");
		get_vocab_admin("affichage_reservation_date_heure");
		get_vocab_admin("capacity");
		get_vocab_admin("msg_max_booking");
		get_vocab_admin("delais_max_resa_room");
		get_vocab_admin("delais_min_resa_room");
		get_vocab_admin("msg_option_de_reservation");
		get_vocab_admin("msg_moderation_reservation");
		get_vocab_admin("allow_action_in_past");
		get_vocab_admin("allow_action_in_past_explain");
		get_vocab_admin("dont_allow_modify");
		get_vocab_admin("qui_peut_reserver_pour_autre_utilisateur");
		get_vocab_admin("personne");
		get_vocab_admin("les_administrateurs_restreints");
		get_vocab_admin("les_gestionnaires_de_la_ressource");
		get_vocab_admin("tous_les_utilisateurs");
		get_vocab_admin("activer_fonctionalite_ressource_empruntee_restituee");
		get_vocab_admin("activer_fonctionalite_gestion_cle");

		get_vocab_admin("back");
		get_vocab_admin("save");
		get_vocab_admin("save_and_back");

		$trad['dTitrePage'] = get_vocab("match_area").get_vocab('deux_points')." ".$area_name." <span class=\"fa fa-arrow-right\"></span> ".$typeAction;
		$trad['dDroitsDomaine'] = authGetUserLevel(getUserName(),$area_id,"area");
		$trad['dDroitsRessource'] = authGetUserLevel(getUserName(),$room);
		$trad['dIdDomaine'] = $area_id;
		
		// Domaine
		$trad['dEnablePeriods'] = grr_sql_query1("select enable_periods from ".TABLE_PREFIX."_area where id='".$area_id."'");

		//$nom_picture = '';
		//if ($row['picture_room'] != '') $nom_picture = "../images/".$row['picture_room'];
		
		if (((authGetUserLevel(getUserName(),$area_id,"area") >=4 ) || (authGetUserLevel(getUserName(),$room) >= 4)) && ($trad['dEnablePeriods'] == 'n'))
		{
			// les creneaux sont bases sur le temps : on ne peut pas changer une ressource de domaine
			if(authGetUserLevel(getUserName(),-1,'area') >= 6)
				$sql = "SELECT id,area_name
			FROM ".TABLE_PREFIX."_area where enable_periods='n'
			ORDER BY area_name ASC";
			else if (authGetUserLevel(getUserName(),$area_id,'area') == 5)
				$sql = "SELECT distinct a.id, a.area_name
			FROM ".TABLE_PREFIX."_area a, ".TABLE_PREFIX."_j_site_area j, ".TABLE_PREFIX."_site s,  ".TABLE_PREFIX."_j_useradmin_site u
			WHERE a.id=j.id_area and u.id_site=j.id_site  and s.id=u.id_site and u.login='".getUserName()."'  and  enable_periods='n'
			ORDER BY a.area_name ASC";
			else
				$sql = "SELECT id,area_name
			FROM ".TABLE_PREFIX."_area a,  ".TABLE_PREFIX."_j_useradmin_area u
			WHERE a.id=u.id_area and u.login='".getUserName()."' and  a.enable_periods='n'
			ORDER BY a.area_name ASC";
			$res = grr_sql_query($sql);
			$nb_area = grr_sql_count($res);

			for ($enr = 0; ($row1 = grr_sql_row($res, $enr)); $enr++)
				$domaines[] = array('id' => $row1[0], 'nom' => $row1[1]);

			grr_sql_free($res);
		}

		if (@file_exists("../images/".$row['picture_room']))
			$trad['dLienImg'] = "../images/".$row['picture_room'];


		//Hook::Appel("hookEditRoom1");

	echo $twig->render('admin_edit_ressource.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'trad' => $trad, 'settings' => $AllSettings, 'ressource' => $row, 'domaines' => $domaines));
?>