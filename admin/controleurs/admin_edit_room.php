<?php
/**
 * admin_edit_room.php
 * Interface de creation/modification des sites, domaines et des ressources de l'application GRR
 * Dernière modification : $Date: 2017-12-16 14:00$
 * @author    Laurent Delineau & JeromeB & Marc-Henri PAMISEU
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

$grr_script_name = "admin_edit_room.php";

$ok = NULL;
if (Settings::get("module_multisite") == "Oui")
	$id_site = isset($_POST["id_site"]) ? $_POST["id_site"] : (isset($_GET["id_site"]) ? $_GET["id_site"] : -1);
$action = isset($_POST["action"]) ? $_POST["action"] : (isset($_GET["action"]) ? $_GET["action"] : NULL);
//$add_area = isset($_POST["add_area"]) ? $_POST["add_area"] : (isset($_GET["add_area"]) ? $_GET["add_area"] : NULL);
$area_id = isset($_POST["area_id"]) ? $_POST["area_id"] : (isset($_GET["area_id"]) ? $_GET["area_id"] : NULL);
$retour_page = isset($_POST["retour_page"]) ? $_POST["retour_page"] : (isset($_GET["retour_page"]) ? $_GET["retour_page"] : NULL);
$room = isset($_POST["room"]) ? $_POST["room"] : (isset($_GET["room"]) ? $_GET["room"] : NULL);
$id_area = isset($_POST["id_area"]) ? $_POST["id_area"] : (isset($_GET["id_area"]) ? $_GET["id_area"] : NULL);
$change_area = isset($_POST["change_area"]) ? $_POST["change_area"] : NULL;
$area_name = isset($_POST["area_name"]) ? $_POST["area_name"] : NULL;
//$access = isset($_POST["access"]) ? $_POST["access"] : NULL;
//$ip_adr = isset($_POST["ip_adr"]) ? $_POST["ip_adr"] : NULL;
$room_name = isset($_POST["room_name"]) ? $_POST["room_name"] : NULL;
$description = isset($_POST["description"]) ? $_POST["description"] : NULL;
$capacity = isset($_POST["capacity"]) ? $_POST["capacity"] : NULL;
//$duree_max_resa_area1  = isset($_POST["duree_max_resa_area1"]) ? $_POST["duree_max_resa_area1"] : NULL;
//$duree_max_resa_area2  = isset($_POST["duree_max_resa_area2"]) ? $_POST["duree_max_resa_area2"] : NULL;
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

//$room_order = isset($_POST["room_order"]) ? $_POST["room_order"] : NULL;
$change_room = isset($_POST["change_room"]) ? $_POST["change_room"] : NULL;
//$number_periodes = isset($_POST["number_periodes"]) ? $_POST["number_periodes"] : NULL;
$type_affichage_reser = isset($_POST["type_affichage_reser"]) ? $_POST["type_affichage_reser"] : NULL;
//$retour_resa_obli = isset($_POST["retour_resa_obli"]) ? $_POST["retour_resa_obli"] : NULL;
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

if (isset($_POST["change_area_and_back"]))
{
	$change_area = "yes";
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
	if (isset($room))
	{
		// Il s'agit d'une modif de ressource
		if (((authGetUserLevel(getUserName(),$room) < 3)) || (!verif_acces_ressource(getUserName(), $room)))
		{
			showAccessDenied($back);
			exit();
		}
	}
	else
	{
		if (isset($area_id))
		{
			// On verifie que le domaine $area_id existe
			$test = grr_sql_query1("SELECT id FROM ".TABLE_PREFIX."_area WHERE id='".$area_id."'");
			if ($test == -1)
			{
				showAccessDenied($back);
				exit();
			}
			// Il s'agit de l'ajout d'une ressource
			// On verifie que l'utilisateur a le droit d'ajouter des ressources
			if ((authGetUserLevel(getUserName(), $area_id, 'area') < 4))
			{
				showAccessDenied($back);
				exit();
			}
		}
		else if (isset($id_area))
		{
			// On verifie que le domaine $area existe
			$test = grr_sql_query1("SELECT id FROM ".TABLE_PREFIX."_area WHERE id='".$id_area."'");
			if ($test == -1)
			{
				showAccessDenied($back);
				exit();
			}
			// Il s'agit de la modif d'un domaine
			if ((authGetUserLevel(getUserName(), $id_area, 'area') < 4))
			{
				showAccessDenied($back);
				exit();
			}
		}
	}
}
$msg ='';
// Gestion des ressources
if ((!empty($room)) || (isset($area_id)))
{
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
				$msg .= "L\'image n\'a pas pu etre supprimee : probleme d\'ecriture sur le repertoire. Veuillez signaler ce probleme e l\'administrateur du serveur.\\n";
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
				$msg .= "L\'image n\'a pas pu etre enregistree : les seules extentions autorisees sont gif, png et jpg.\\n";
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
					$msg .= "L\'image n\'a pas pu etre enregistree : probleme d\'ecriture sur le repertoire IMAGES. Veuillez signaler ce probleme e l\'administrateur du serveur.\\n";
					$ok = 'no';
				}
				else
				{
					$ok1 = @copy($doc_file['tmp_name'], $dest.$doc_file['name']);
					if (!$ok1)
						$ok1 = @move_uploaded_file($doc_file['tmp_name'], $dest.$doc_file['name']);
					if (!$ok1)
					{
						$msg .= "L\'image n\'a pas pu etre enregistree : probleme de transfert. Le fichier n\'a pas pu etre transfere sur le repertoire IMAGES. Veuillez signaler ce probleme e l\'administrateur du serveur.\\n";
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
			$msg .= "L\'image n\'a pas pu etre enregistree : le fichier image selectionne n'est pas valide !\\n";
			$ok = 'no';
		}
		$msg .= get_vocab("message_records");
	}
	// Si pas de probleme, retour e la page d'accueil apres enregistrement
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
			echo "<h2>".get_vocab("match_area").get_vocab('deux_points')." ".$area_name."<br />".get_vocab("duplique_ressource")."</h2>\n";
		else
			echo "<h2>".get_vocab("match_area").get_vocab('deux_points')." ".$area_name."<br />".get_vocab("editroom")."</h2>\n";
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
		$row['statut_room'] = '';
		$row['moderate'] = '';
		$row['show_fic_room'] = '';
		$row['active_ressource_empruntee'] = 'n';
		$row['active_cle'] = 'n';
		$area_name = grr_sql_query1("select area_name from ".TABLE_PREFIX."_area where id='".$area_id."'");
		echo "<h2>".get_vocab("match_area").get_vocab('deux_points')." ".$area_name."<br />".get_vocab("addroom")."</h2>\n";

	}
	?>
	<form enctype="multipart/form-data" action="admin_edit_room.php" method="post">

		<?php
		echo "<div>";
		if (isset($action))
			echo "<input type=\"hidden\" name=\"action\" value=\"duplique_room\" />\n";
		if ($row["id"] != '')
			echo "<input type=\"hidden\" name=\"room\" value=\"".$row["id"]."\" />\n";
		if (isset($retour_page))
			echo "<input type=\"hidden\" name=\"retour_page\" value=\"".$retour_page."\" />\n";
		echo "</div>";
		$nom_picture = '';
		if ($row['picture_room'] != '') $nom_picture = "../images/".$row['picture_room'];
		if (Settings::get("use_fckeditor") == 1)
			echo "<script type=\"text/javascript\" src=\"../js/ckeditor/ckeditor.js\"></script>\n";
		echo "<table border=\"1\" cellspacing=\"1\" cellpadding=\"6\">\n";
		echo "<tr><td>".get_vocab("name").get_vocab("deux_points")."</td><td style=\"width:30%;\">\n";
		// seul l'administrateur peut modifier le nom de la ressource
		if ((authGetUserLevel(getUserName(),$area_id,"area") >= 4) || (authGetUserLevel(getUserName(),$room) >= 4))
			echo "<input type=\"text\" name=\"room_name\" size=\"40\" value=\"".htmlspecialchars($row["room_name"])."\" />\n";
		else
		{
			echo "<input type=\"hidden\" name=\"room_name\" value=\"".htmlspecialchars($row["room_name"])."\" />\n";
			echo "<b>".htmlspecialchars($row["room_name"])."</b>\n";
		}
		echo "</td></tr>\n";
		// Description
		echo "<tr><td>".get_vocab("description")."</td><td><input type=\"text\" name=\"description\"  size=\"40\" value=\"".htmlspecialchars($row["description"])."\" /></td></tr>\n";
		// Domaine
		$enable_periods = grr_sql_query1("select enable_periods from ".TABLE_PREFIX."_area where id='".$area_id."'");
		if (((authGetUserLevel(getUserName(),$area_id,"area") >=4 ) || (authGetUserLevel(getUserName(),$room) >= 4)) && ($enable_periods == 'n'))
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
			if ($nb_area > 1)
			{
				echo "<tr><td>".get_vocab('match_area').get_vocab('deux_points')."</td>\n";
				echo "<td><select class=\"form-control\" name=\"area_id\" >\n
				<option value=\"-1\">".get_vocab('choose_an_area')."</option>\n";
				for ($enr = 0; ($row1 = grr_sql_row($res, $enr)); $enr++)
				{
					echo "<option value=\"".$row1[0]."\"";
					if ($area_id == $row1[0])
						echo ' selected="selected"';
					echo '>'.htmlspecialchars($row1[1]);
					echo '</option>'."\n";
				}
				grr_sql_free($res);
				echo "</select></td></tr>";
			}
			else
			{
				if (isset($area_id))
					echo "<input type=\"hidden\" name=\"area_id\" value=\"".$area_id."\" />\n";
			}
		}
		else
		{
	  	// les creneaux sont bases sur les intitules : on ne peut pas changer une ressource de domaine
			if (isset($area_id))
				echo "<input type=\"hidden\" name=\"area_id\" value=\"".$area_id."\" />\n";
		}
		// Ordre d'affichage du domaine
		echo "<tr><td>".get_vocab("order_display").get_vocab("deux_points")."</td>\n";
		echo "<td><input class=\"form-control\" type=\"text\" name=\"area_order\" size=\"1\" value=\"".htmlspecialchars($row["order_display"])."\" /></td>\n";
		echo "</tr>\n";
		// Qui peut voir cette ressource
		echo "<tr><td colspan=\"2\">".get_vocab("qui_peut_voir_ressource")."<br />\n";
		echo "<select class=\"form-control\" name=\"who_can_see\" size=\"1\">\n
		<option value=\"0\" ";
		if ($row["who_can_see"] == 0)
			echo " selected=\"selected\" ";
		echo ">".get_vocab("visu_fiche_description0")."</option>\n<option value=\"1\" ";
		if ($row["who_can_see"] == 1)
			echo " selected=\"selected\" ";
		echo ">".get_vocab("visu_fiche_description1")."</option>\n<option value=\"2\" ";
		if ($row["who_can_see"] == 2)
			echo " selected=\"selected\" ";
		echo ">".get_vocab("visu_fiche_description2")."</option>\n<option value=\"3\" ";
		if ($row["who_can_see"] == 3)
			echo " selected=\"selected\" ";
		echo ">".get_vocab("visu_fiche_description3")."</option>\n<option value=\"4\" ";
		if ($row["who_can_see"] == 4)
			echo " selected=\"selected\" ";
		echo ">".get_vocab("visu_fiche_description4")."</option>\n";
		if (Settings::get("module_multisite") != "Oui")
		{
			echo "<option value=\"5\" ";
			if ($row["who_can_see"] == 5)
				echo " selected=\"selected\" ";
			echo ">".get_vocab("visu_fiche_description5")."</option>\n";
		};
		echo "<option value=\"6\" ";
		if ($row["who_can_see"] == 6)
			echo " selected=\"selected\" ";
		echo ">".get_vocab("visu_fiche_description6")."</option>\n</select></td></tr>\n";
		// Declarer ressource indisponible
		echo "<tr><td>".get_vocab("declarer_ressource_indisponible")."<br /><i>".get_vocab("explain_max_booking")."</i></td><td><input type=\"checkbox\" name=\"statut_room\" ";
		if ($row['statut_room'] == "0")
			echo " checked=\"checked\" ";
		echo "/></td></tr>\n";
		// Afficher la fiche de presentation de la ressource
		echo "<tr><td>".get_vocab("montrer_fiche_presentation_ressource")."</td><td><input type=\"checkbox\" name=\"show_fic_room\" ";
		if ($row['show_fic_room'] == "y")
			echo " checked=\"checked\" ";
		echo "/><a href='javascript:centrerpopup(\"../view_room.php?id_room=$room\",600,480,\"scrollbars=yes,statusbar=no,resizable=yes\")' title=\"".get_vocab("fiche_ressource")."\"><span class=\"glyphicon glyphicon-search\"></span></a></td></tr>\n";
		// Choix de l'image de la ressource
		echo "<tr><td>".get_vocab("choisir_image_ressource")."</td><td><input type=\"file\" name=\"doc_file\" size=\"30\" /></td></tr>\n";
		echo "<tr><td>".get_vocab("supprimer_image_ressource").get_vocab("deux_points");
		if (@file_exists($nom_picture))
		{
			echo "<b>$nom_picture</b></td><td><input type=\"checkbox\" name=\"sup_img\" /></td></tr>";}
			else
				echo "<b>".get_vocab("nobody")."</b></td><td><input type=\"checkbox\" disabled=\"disabled\" name=\"sup_img\" /></td></tr>";
			echo "<tr><td>".get_vocab("Afficher_description_complete_dans_titre_plannings")."</td>\n<td><input type=\"checkbox\" name=\"show_comment\" ";
			if ($row['show_comment'] == "y")
				echo " checked ";
			echo "/></td></tr>\n";
			// Description complete
			echo "<tr><td colspan=\"2\">".get_vocab("description complete");
			if (Settings::get("use_fckeditor") != 1)
				echo " ".get_vocab("description complete2");
			echo get_vocab("deux_points")."<br />";
			if (Settings::get("use_fckeditor") == 1)
			{
				echo "<textarea class=\"ckeditor\" id=\"editor1\" name=\"comment_room\" rows=\"8\" cols=\"120\">\n";
				echo htmlspecialchars($row['comment_room']);
				echo "</textarea>\n";
				?>
				<script type="text/javascript">
					CKEDITOR.replace( 'editor1',
					{
						toolbar :
						[
						['Source'],
						['Cut','Copy','Paste','PasteText','PasteFromWord', 'SpellChecker', 'Scayt'],
						['Undo','Redo','-','Find','Replace','-','SelectAll','RemoveFormat'],
						['Bold','Italic','Underline','Strike','-','Subscript','Superscript'],
						['NumberedList','BulletedList','-','Outdent','Indent','Blockquote'],
						['JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock'],
						['Link','Unlink','Anchor'],
						['Image','Table','HorizontalRule','SpecialChar','PageBreak'],
						]
					});
				</script>
				<?php
			}
			else
				echo "<textarea class=\"form-control\" name=\"comment_room\" rows=\"8\" cols=\"120\" >".$row['comment_room']."</textarea>";
			echo "</td></tr></table>\n";
			echo "<h3>".get_vocab("configuration_ressource")."</h3>\n";
// Type d'affichage : duree ou heure/date de fin de reservation
			echo "<table border=\"1\" cellspacing=\"1\" cellpadding=\"6\">\n";
			echo "<tr><td>".get_vocab("type_affichage_reservation").get_vocab("deux_points")."</td>\n";
			echo "<td>";
			echo "<label><input type=\"radio\" name=\"type_affichage_reser\" value=\"0\" ";
			if (($row["type_affichage_reser"]) == 0)
				echo " checked=\"checked\" ";
			echo "/>";
			echo get_vocab("affichage_reservation_duree");
			echo "</label><br /><label><input type=\"radio\" name=\"type_affichage_reser\" value=\"1\" ";
			if (($row["type_affichage_reser"]) == 1)
				echo " checked=\"checked\" ";
			echo "/>";
			echo get_vocab("affichage_reservation_date_heure");
			echo "</label></td>\n";
			echo "</tr>\n";

	// Capacite
			echo "<tr><td>".get_vocab("capacity").": </td><td><input class=\"form-control\" type=\"text\" name=\"capacity\" size=\"1\" value=\"".$row["capacity"]."\" /></td></tr>\n";
	// seul les administrateurs de la ressource peuvent modifier le nombre max de reservation par utilisateur
			if ((authGetUserLevel(getUserName(),$area_id,"area") >= 4) || (authGetUserLevel(getUserName(),$room) >= 4))
			{
				echo "<tr><td>".get_vocab("max_booking")." ";
				echo "</td><td><input class=\"form-control\" type=\"text\" name=\"max_booking\" size=\"1\" value=\"".$row["max_booking"]."\" /></td></tr>";

			}
			else if ($row["max_booking"] != "-1")
				echo "<tr><td>".get_vocab("msg_max_booking").get_vocab("deux_points")."</td><td><input type=\"hidden\" name=\"max_booking\" value=\"".$row["max_booking"]."\" /><b>".htmlspecialchars($row["max_booking"])."</b></td></tr>";
// L'utilisateur ne peut pas reserver au-dele d'un certain temps
			echo "<tr><td>".get_vocab("delais_max_resa_room").": </td><td><input class=\"form-control\" type=\"text\" name=\"delais_max_resa_room\" size=\"1\" value=\"".$row["delais_max_resa_room"]."\" /></td></tr>\n";
// L'utilisateur ne peut pas reserver en-dessous d'un certain temps
			echo "<tr><td>".get_vocab("delais_min_resa_room").": ";
			echo "</td><td><input class=\"form-control\" type=\"text\" name=\"delais_min_resa_room\" size=\"5\" value=\"".$row["delais_min_resa_room"]."\" /></td></tr>\n";
// L'utilisateur peut poser poser une option de reservation
			echo "<tr><td>".get_vocab("msg_option_de_reservation")."</td><td><input class=\"form-control\" type=\"text\" name=\"delais_option_reservation\" size=\"5\" value=\"".$row["delais_option_reservation"]."\" /></td></tr>\n";
// Les demandes de reservations sont moderes
			echo "<tr><td>".get_vocab("msg_moderation_reservation").get_vocab("deux_points");
			echo "</td>" ."<td><input type='checkbox' name='moderate' ";
			if ($row['moderate'])
				echo 'checked="checked"';
			echo " /></td></tr>\n";
// L'utilisateur peut reserver dans le passe
			echo "<tr><td>".get_vocab("allow_action_in_past")."<br /><i>".get_vocab("allow_action_in_past_explain")."</i></td><td><input type=\"checkbox\" name=\"allow_action_in_past\" value=\"y\" ";
			if ($row["allow_action_in_past"] == 'y')
				echo " checked=\"checked\"";
			echo " /></td></tr>\n";
	// L'utilisateur ne peut pas modifier ou supprimer ses propres reservations
			echo "<tr><td>".get_vocab("dont_allow_modify")."</td><td><input type=\"checkbox\" name=\"dont_allow_modify\" value=\"y\" ";
			if ($row["dont_allow_modify"] == 'y')
				echo " checked=\"checked\"";
			echo " /></td></tr>\n";
	// Quels utilisateurs ont le droit de reserver cette ressource au nom d'un autre utilisateur ?
			echo "<tr><td>".get_vocab("qui peut reserver pour autre utilisateur")."</td><td><select class=\"form-control\" name=\"qui_peut_reserver_pour\" size=\"1\">\n<option value=\"5\" ";
			if ($row["qui_peut_reserver_pour"]==6)
				echo " selected=\"selected\" ";
			echo ">".get_vocab("personne")."</option>\n
			<option value=\"4\" ";
			if ($row["qui_peut_reserver_pour"]==4)
				echo " selected=\"selected\" ";
			echo ">".get_vocab("les administrateurs restreints")."</option>\n
			<option value=\"3\" ";
			if ($row["qui_peut_reserver_pour"]==3)
				echo " selected=\"selected\" ";
			echo ">".get_vocab("les gestionnaires de la ressource")."</option>\n
			<option value=\"2\" ";
			if ($row["qui_peut_reserver_pour"]==2)
				echo " selected=\"selected\" ";
			echo ">".get_vocab("tous les utilisateurs")."</option>\n
		</select></td></tr>\n";
// Activer la fonctionalite "ressource empruntee/restituee"
		echo "<tr><td>".get_vocab("activer_fonctionalite_ressource_empruntee_restituee")."</td><td><input type=\"checkbox\" name=\"active_ressource_empruntee\" ";
		if ($row['active_ressource_empruntee'] == "y")
			echo " checked=\"checked\" ";
		echo "/></td></tr>\n";
// Activer la gestion des clés
		echo "<tr><td>".get_vocab("activer_fonctionalite_gestion_cle")."</td><td><input type=\"checkbox\" name=\"active_cle\" ";
		if ($row['active_cle'] == "y")
			echo " checked=\"checked\" ";
		echo "/></td></tr>\n";
	//
		echo "</table>\n";
		Hook::Appel("hookEditRoom1");
		echo "<div style=\"text-align:center;\"><br />\n";
		echo "<input class=\"btn btn-primary\" type=\"submit\" name=\"change_room\"  value=\"".get_vocab("save")."\" />\n";
		echo "<input class=\"btn btn-primary\" type=\"submit\" name=\"change_done\" value=\"".get_vocab("back")."\" />";
		echo "<input class=\"btn btn-primary\" type=\"submit\" name=\"change_room_and_back\" value=\"".get_vocab("save_and_back")."\" />";
		if (@file_exists($nom_picture) && $nom_picture)
			echo "<br /><br /><b>".get_vocab("Image de la ressource").get_vocab("deux_points")."</b><br /><img src=\"".$nom_picture."\" alt=\"logo\" />";
		else
			echo "<br /><br /><b>".get_vocab("Pas image disponible")."</b>";
		?>
	</div>
</form>
</div>
<?php
}
?>