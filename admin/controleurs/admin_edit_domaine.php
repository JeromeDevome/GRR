<?php
/**
 * admin_edit_domaine.php
 * Interface de creation/modification des sites, domaines et des ressources de l'application GRR
 * Dernière modification : $Date: 2018-08-14 11:30$
 * @author    JeromeB
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

$grr_script_name = "admin_edit_domaine.php";

$ok = NULL;
if (Settings::get("module_multisite") == "Oui")
	$id_site = isset($_POST["id_site"]) ? $_POST["id_site"] : (isset($_GET["id_site"]) ? $_GET["id_site"] : -1);
$action = isset($_POST["action"]) ? $_POST["action"] : (isset($_GET["action"]) ? $_GET["action"] : NULL);
$add_area = isset($_POST["add_area"]) ? $_POST["add_area"] : (isset($_GET["add_area"]) ? $_GET["add_area"] : NULL);
$area_id = isset($_POST["area_id"]) ? $_POST["area_id"] : (isset($_GET["area_id"]) ? $_GET["area_id"] : NULL);
$retour_page = isset($_POST["retour_page"]) ? $_POST["retour_page"] : (isset($_GET["retour_page"]) ? $_GET["retour_page"] : NULL);
$id_area = isset($_POST["id_area"]) ? $_POST["id_area"] : (isset($_GET["id_area"]) ? $_GET["id_area"] : NULL);
$change_area = isset($_POST["change_area"]) ? $_POST["change_area"] : NULL;
$area_name = isset($_POST["area_name"]) ? $_POST["area_name"] : NULL;
$access = isset($_POST["access"]) ? $_POST["access"] : NULL;
$ip_adr = isset($_POST["ip_adr"]) ? $_POST["ip_adr"] : NULL;
$duree_max_resa_area1  = isset($_POST["duree_max_resa_area1"]) ? $_POST["duree_max_resa_area1"] : NULL;
$duree_max_resa_area2  = isset($_POST["duree_max_resa_area2"]) ? $_POST["duree_max_resa_area2"] : NULL;
$max_booking = isset($_POST["max_booking"]) ? $_POST["max_booking"] : NULL;
settype($max_booking, "integer");
if ($max_booking<-1)
	$max_booking = -1;

$change_done = isset($_POST["change_done"]) ? $_POST["change_done"] : NULL;
if(!isset($_POST["area_order"]) || empty($_POST["area_order"])){
	$area_order = 0;
} else{
	$area_order = $_POST["area_order"];
}

$number_periodes = isset($_POST["number_periodes"]) ? $_POST["number_periodes"] : NULL;
$type_affichage_reser = isset($_POST["type_affichage_reser"]) ? $_POST["type_affichage_reser"] : NULL;

settype($type_affichage_reser, "integer");

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

	if (isset($id_area))
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
$msg ='';

// Ajout ou modification d'un domaine
if ((!empty($id_area)) || (isset($add_area)))
{
	if (isset($change_area))
	{
	// Affectation e un site : si aucun site n'a ete affecte
		if ((Settings::get("module_multisite") == "Oui") && ($id_site == -1))
		{
	  		// On affiche un message d'avertissement
			/*
			<script type="text/javascript">
				alert("<?php echo get_vocab('choose_a_site'); ?>");
			</script>
			*/
	  		// On empeche le retour e la page admin_room
			unset($change_done);
		}
		else
		{
	  		// Un site a ete affecte, on peut continuer
	 		// la valeur par defaut ne peut etre inferiure au plus petit bloc reservable
			if ($_POST['duree_par_defaut_reservation_area'] < $_POST['resolution_area'])
				$_POST['duree_par_defaut_reservation_area'] = $_POST['resolution_area'];
			// la valeur par defaut doit etre un multiple du plus petit bloc reservable
			$_POST['duree_par_defaut_reservation_area'] = intval($_POST['duree_par_defaut_reservation_area'] / $_POST['resolution_area']) * $_POST['resolution_area'];
	  		// Duree maximale de reservation
			if (isset($_POST['enable_periods']))
			{
				if ($_POST['enable_periods'] == 'y')
					$duree_max_resa_area = $duree_max_resa_area2 * 1440;
				else
				{
					$duree_max_resa_area = $duree_max_resa_area1;
					if ($duree_max_resa_area >= 0)
						$duree_max_resa_area = max ($duree_max_resa_area, $_POST['resolution_area'] / 60, $_POST['duree_par_defaut_reservation_area'] / 60);
				}
				settype($duree_max_resa_area, "integer");
				if ($duree_max_resa_area < 0)
					$duree_max_resa_area = -1;
			}

			$display_days = "";
			for ($i = 0; $i < 7; $i++)
			{
				if (isset($_POST['display_day'][$i]))
					$display_days .= "y";
				else
					$display_days .= "n";
			}
			if ($display_days != "nnnnnnn")
			{
				while (!isset($_POST['display_day'][$_POST['weekstarts_area']]))
					$_POST['weekstarts_area']++;
			}
			if ($_POST['morningstarts_area'] > $_POST['eveningends_area'])
				$_POST['eveningends_area'] = $_POST['morningstarts_area'];
			if ($access)
				$access='r';
			else
				$access='a';
			if ((isset($id_area)) && $id_area > 0 && !((isset($action) && ($action == "duplique_area"))))
			{
				// s'il y a changement de type de creneaux, on efface les reservations du domaines
				$old_enable_periods = grr_sql_query1("select enable_periods from ".TABLE_PREFIX."_area WHERE id='".$id_area."'");
				if ($old_enable_periods != $_POST['enable_periods'])
				{
					$del = grr_sql_query("DELETE ".TABLE_PREFIX."_entry FROM ".TABLE_PREFIX."_entry, ".TABLE_PREFIX."_room, ".TABLE_PREFIX."_area WHERE
						".TABLE_PREFIX."_entry.room_id = ".TABLE_PREFIX."_room.id and
						".TABLE_PREFIX."_room.area_id = ".TABLE_PREFIX."_area.id and
						".TABLE_PREFIX."_area.id = '".$id_area."'");
					$del = grr_sql_query("DELETE ".TABLE_PREFIX."_repeat FROM ".TABLE_PREFIX."_repeat, ".TABLE_PREFIX."_room, ".TABLE_PREFIX."_area WHERE
						".TABLE_PREFIX."_repeat.room_id = ".TABLE_PREFIX."_room.id and
						".TABLE_PREFIX."_room.area_id = ".TABLE_PREFIX."_area.id and
						".TABLE_PREFIX."_area.id = '".$id_area."'");
				}
				$sql = "UPDATE ".TABLE_PREFIX."_area SET
				area_name='".protect_data_sql($area_name)."',
				access='".protect_data_sql($access)."',
				order_display='".protect_data_sql($area_order)."',
				ip_adr='".protect_data_sql($ip_adr)."',
				calendar_default_values = 'n',
				duree_max_resa_area = '".protect_data_sql($duree_max_resa_area)."',
				morningstarts_area = '".protect_data_sql($_POST['morningstarts_area'])."',
				eveningends_area = '".protect_data_sql($_POST['eveningends_area'])."',
				resolution_area = '".protect_data_sql($_POST['resolution_area'])."',
				duree_par_defaut_reservation_area = '".protect_data_sql($_POST['duree_par_defaut_reservation_area'])."',
				eveningends_minutes_area = '".protect_data_sql($_POST['eveningends_minutes_area'])."',
				weekstarts_area = '".protect_data_sql($_POST['weekstarts_area'])."',
				enable_periods = '".protect_data_sql($_POST['enable_periods'])."',
				twentyfourhour_format_area = '".protect_data_sql($_POST['twentyfourhour_format_area'])."',
				max_booking='".$max_booking."',
				display_days = '".$display_days."'
				WHERE id=$id_area";
				if (grr_sql_command($sql) < 0)
				{
					fatal_error(0, get_vocab('update_area_failed') . grr_sql_error());
					$ok = 'no';
				}
			}
			else
			{
				$sql = "INSERT INTO ".TABLE_PREFIX."_area SET
				area_name='".protect_data_sql($area_name)."',
				access='".protect_data_sql($access)."',
				order_display='".protect_data_sql($area_order)."',
				ip_adr='".protect_data_sql($ip_adr)."',
				calendar_default_values = 'n',
				duree_max_resa_area = '".protect_data_sql($duree_max_resa_area)."',
				morningstarts_area = '".protect_data_sql($_POST['morningstarts_area'])."',
				eveningends_area = '".protect_data_sql($_POST['eveningends_area'])."',
				resolution_area = '".protect_data_sql($_POST['resolution_area'])."',
				duree_par_defaut_reservation_area = '".protect_data_sql($_POST['duree_par_defaut_reservation_area'])."',
				eveningends_minutes_area = '".protect_data_sql($_POST['eveningends_minutes_area'])."',
				weekstarts_area = '".protect_data_sql($_POST['weekstarts_area'])."',
				enable_periods = '".protect_data_sql($_POST['enable_periods'])."',
				twentyfourhour_format_area = '".protect_data_sql($_POST['twentyfourhour_format_area'])."',
				display_days = '".$display_days."',
				max_booking='".$max_booking."',
				id_type_par_defaut = '-1'
				";
				if (grr_sql_command($sql) < 0)
					fatal_error(1, "<p>" . grr_sql_error());
				$id_area = grr_sql_insert_id();
			}
	  		// Affectation e un site
			if (Settings::get("module_multisite") == "Oui")
			{
				$sql = "delete from ".TABLE_PREFIX."_j_site_area where id_area='".$id_area."'";
				if (grr_sql_command($sql) < 0)
					fatal_error(0, "<p>".grr_sql_error()."</p>");
				$sql = "INSERT INTO ".TABLE_PREFIX."_j_site_area SET id_site='".$id_site."', id_area='".$id_area."'";
				if (grr_sql_command($sql) < 0)
					fatal_error(0, "<p>".grr_sql_error()."</p>");
			}
			#Si area_name est vide on le change maintenant que l'on a l'id area
			if ($area_name == '')
			{
				$area_name = get_vocab("match_area")." ".$id_area;
				grr_sql_command("UPDATE ".TABLE_PREFIX."_area SET area_name='".protect_data_sql($area_name)."' WHERE id=$id_area");
			}
		  	#on cree ou recree ".TABLE_PREFIX."_area_periodes pour le domaine
			if (protect_data_sql($_POST['enable_periods']) == 'y')
			{
				if (isset($number_periodes))
				{
					settype($number_periodes, "integer");
					if ($number_periodes < 1)
						$number_periodes = 1;
					$del_periode = grr_sql_query("delete from ".TABLE_PREFIX."_area_periodes where id_area='".$id_area."'");
		  			#on efface le modele par defaut avec area=0
					$del_periode = grr_sql_query("delete from ".TABLE_PREFIX."_area_periodes where id_area='0'");
					$i = 0;
					$num = 0;
					while ($i < $number_periodes)
					{
						$temp = "periode_".$i;
						if (isset($_POST[$temp]))
						{
							$nom_periode = corriger_caracteres($_POST[$temp]);
							$reg_periode = grr_sql_query("insert into ".TABLE_PREFIX."_area_periodes set
								id_area='".$id_area."',
								num_periode='".$num."',
								nom_periode='".protect_data_sql($nom_periode)."'
								");
			  				#on cree un modele par defaut avec area=0
							$reg_periode = grr_sql_query("insert into ".TABLE_PREFIX."_area_periodes set
								id_area='0',
								num_periode='".$num."',
								nom_periode='".protect_data_sql($nom_periode)."'");
							$num++;
						}
						$i++;
					}
				}
			}
			//$msg = get_vocab("message_records");
		}
	}
	if ($access=='a')
	{
		$sql = "DELETE FROM ".TABLE_PREFIX."_j_user_area WHERE id_area='$id_area'";
		if (grr_sql_command($sql) < 0)
			fatal_error(0, get_vocab('update_area_failed') . grr_sql_error());
	}
	if ((isset($change_done)) && (!isset($ok)))
	{
		if ($msg != '') {
			$_SESSION['displ_msg'] = 'yes';
			if (strpos($retour_page, ".php?") == "")
				$param = "?msg=".$msg;
			else
				$param = "&msg=".$msg;
		} else
		$param = '';
		Header("Location: ".$retour_page.$param);
		exit();
	}

	// Si pas de problème, message de confirmation
	if (isset($_POST['area_name'])) {
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

	//affiche_pop_up($msg,"admin");

	if (isset($id_area))
	{
		$res = grr_sql_query("SELECT * FROM ".TABLE_PREFIX."_area WHERE id=$id_area");
		if (! $res)
			fatal_error(0, get_vocab('error_area') . $id_area . get_vocab('not_found'));
		$domaine = grr_sql_row_keyed($res, 0);
		grr_sql_free($res);
		if ($action=="duplique_area")
			$trad['editarea'] = get_vocab("duplique_domaine");
		else
			$trad['editarea'] = get_vocab("editarea");
		if ($domaine["calendar_default_values"] == 'y')
		{
			$domaine["morningstarts_area"] = $morningstarts;
			$domaine["eveningends_area"] = $eveningends;
			$domaine["resolution_area"] = $resolution;
			$domaine["duree_par_defaut_reservation_area"] = $duree_par_defaut_reservation_area;
			$domaine["duree_max_resa_area"] = $duree_max_resa;
			$domaine["eveningends_minutes_area"] = $eveningends_minutes;
			$domaine["weekstarts_area"] = $weekstarts;
			$domaine["twentyfourhour_format_area"] = $twentyfourhour_format;
			$domaine["display_days"] = $display_days;
		}
		if ($domaine["enable_periods"] != 'y')
			$domaine["enable_periods"] = 'n';
		if (Settings::get("module_multisite") == "Oui")
			$id_site=grr_sql_query1("select id_site from ".TABLE_PREFIX."_j_site_area where id_area='".$id_area."'");
	}
	else
	{
		$domaine["id"] = '';
		$domaine["area_name"] = '';
		$domaine["order_display"]  = '';
		$domaine["access"] = '';
		$domaine["ip_adr"] = '';
		$domaine["morningstarts_area"] = $morningstarts;
		$domaine["eveningends_area"] = $eveningends;
		$domaine["resolution_area"] = $resolution;
		$domaine["duree_par_defaut_reservation_area"] = $resolution;
		$domaine["duree_max_resa_area"] = $duree_max_resa;
		$domaine["eveningends_minutes_area"] = $eveningends_minutes;
		$domaine["weekstarts_area"] = $weekstarts;
		$domaine["twentyfourhour_format_area"] = $twentyfourhour_format;
		$domaine["enable_periods"] = 'n';
		$domaine["display_days"] = "yyyyyyy";
		$domaine['max_booking'] = '-1';
		$trad['editarea'] = get_vocab('addarea');
	}

	get_vocab_admin('miscellaneous');
	get_vocab_admin('name');
	get_vocab_admin('order_display');
	get_vocab_admin('access');
	get_vocab_admin('site');
	get_vocab_admin('choose_a_site');
	get_vocab_admin('ip_adr');
	get_vocab_admin('ip_adr_explain');

	get_vocab_admin('configuration_plages_horaires');
	get_vocab_admin('weekstarts_area');
	get_vocab_admin('cocher_jours_a_afficher');

	get_vocab_admin("avertissement_change_type");

	get_vocab_admin('type_de_creneaux');
	get_vocab_admin('creneaux_de_reservation_temps');
	get_vocab_admin('creneaux_de_reservation_pre_definis');
	get_vocab_admin('nombre_de_creneaux');
	get_vocab_admin('goto');
	get_vocab_admin('intitule_creneau');
	get_vocab_admin('duree_max_resa_area2');

	get_vocab_admin('morningstarts_area');
	get_vocab_admin('eveningends_area');
	get_vocab_admin('eveningends_minutes_area');
	get_vocab_admin('resolution_area');
	get_vocab_admin('duree_par_defaut_reservation_area');
	get_vocab_admin('twentyfourhour_format_area');
	get_vocab_admin('twentyfourhour_format_12');
	get_vocab_admin('twentyfourhour_format_24');
	get_vocab_admin('duree_max_resa_area');
	get_vocab_admin('max_booking');

	get_vocab_admin('back');
	get_vocab_admin('save');
	get_vocab_admin('save_and_back');
	get_vocab_admin('message_records');

	$trad['dIdSite'] = $id_site;
	$trad['dIpClient'] = OPTION_IP_ADR;

		if (isset($action))
			$trad['dHidden1'] = "<input type=\"hidden\" name=\"action\" value=\"duplique_area\" />";
		if (isset($retour_page))
			$trad['dHidden2'] = "<input type=\"hidden\" name=\"retour_page\" value=\"".$retour_page."\" />";
		if (isset($add_area))
			$trad['dHidden3'] = "<input type=\"hidden\" name=\"add_area\" value=\"".$add_area."\" />\n";

		// Sites
        $sites = array();
		if (Settings::get("module_multisite") == "Oui")
		{

			if (authGetUserLevel(getUserName(), -1, 'area') >= 6)
				$sql = "SELECT id,sitecode,sitename
						FROM ".TABLE_PREFIX."_site
						ORDER BY sitename ASC";
			else
				$sql = "SELECT id,sitecode,sitename
						FROM ".TABLE_PREFIX."_site s,  ".TABLE_PREFIX."_j_useradmin_site u
						WHERE s.id=u.id_site and u.login='".getUserName()."'
						ORDER BY s.sitename ASC";
			$res = grr_sql_query($sql);
			$nb_site = grr_sql_count($res);

			for ($enr = 0; ($domaine1 = grr_sql_row($res, $enr)); $enr++)
				$sites[] = array('id' => $domaine1[0], 'nom' => $domaine1[2]);

			grr_sql_free($res);
		}

		// jours de la semaine: 0 pour dimanche, 1 pour lundi, etc.
		for ($i = 0; $i < 7; $i++)
			$JoursSemaine[] = array('num' => $i, 'nom' => day_name($i));

		//Les creneaux de reservation sont bases sur des intitules pre-definis.
		$sql_periode = grr_sql_query("SELECT num_periode, nom_periode FROM ".TABLE_PREFIX."_area_periodes where id_area='".$id_area."' order by num_periode");
		$num_periodes = grr_sql_count($sql_periode);
		if (!isset($number_periodes))
			if ($num_periodes == 0)
				$number_periodes = 10;
			else
				$number_periodes = $num_periodes;
			
			$trad['dNumberPeriodes'] = $number_periodes;

			$i = 0;
			$trad['dNomCrenaux'] = "";
			while ($i < 50)
			{
				$nom_periode = grr_sql_query1("select nom_periode FROM ".TABLE_PREFIX."_area_periodes where id_area='".$id_area."' and num_periode= '".$i."'");
				if ($nom_periode == -1)
					$nom_periode = "";
				$trad['dNomCrenaux'] .= "<table style=\"display:none\" id=\"c".($i+1)."\"><tr><td>".get_vocab("intitule_creneau").($i+1).get_vocab("deux_points")."</td>";
				$trad['dNomCrenaux'] .= "<td style=\"width:30%;\"><input type=\"text\" name=\"periode_".$i."\" value=\"".htmlentities($nom_periode)."\" size=\"20\" /></td></tr></table>\n";
				$i++;
			}

			// L'utilisateur ne peut reserver qu'une duree limitee (-1 desactivee), exprimee en jours
			if ($domaine["duree_max_resa_area"] > 0)
				$trad['dNombreJourMax'] = max(round($domaine["duree_max_resa_area"]/1440,0),1);
			else
				$trad['dNombreJourMax'] = -1;

/**/
			//Hook::Appel("hookEditArea1");
		}

	echo $twig->render('admin_edit_domaine.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings, 'domaine' => $domaine, 'sites' => $sites, 'JoursSemaine' => $JoursSemaine));
?>