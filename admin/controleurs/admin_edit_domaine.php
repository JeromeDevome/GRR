<?php
/**
 * admin_edit_domaine.php
 * Interface de creation/modification des domaines de l'application GRR
 * Dernière modification : $Date: 2026-10-10 16:00$
 * @author    JeromeB, Yan Naessens
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

$grr_script_name = "admin_edit_domaine.php";

// Accès à la page


// les variables attendues et leur type
$form_vars = array(
    'p_action' => array('int', 0), // 1 : Création, 2 : Duplication, 3 : Modification
    'p_submit' => array('int', 0), // 1 : soumission du formulaire
    'p_id_site' => array('int', -1),
    'p_id_area' => array('int', -1),
    'p_area_name' => array('string', ''),
    'p_area_order' => array('int', 0),
    'p_access' => array('alphanumeric', 'a'),
	'p_weekstarts_area' => array('int', 1), // 1er jour de la semaine (0=dimanche, 1=lundi, 2=mardi, 3=mercredi, 4=jeudi, 5=vendredi, 6=samedi)
	'p_display_day' => array('array', array()), // tableau des jours sélectionnés
	'p_enable_periods' => array('alphanumeric', 'n'),
	'p_morningstarts_area' => array('int', 8),
	'p_eveningends_area' => array('int', 18),
	'p_eveningends_minutes_area' => array('int', 0),
    'p_resolution_area' => array('int', 1800),
	'p_duree_par_defaut_reservation_area' => array('int', 1800),
	'p_twentyfourhour_format_area' => array('int', 1),
	'p_number_periodes' => array('int', 10),
	'p_description_breve' => array('int', 1),
	'p_description_complete' => array('int', 0),
	'p_access_file' => array('int', 0),
	'p_user_right' => array('int', 1),
	'p_upload_file' => array('int', 1),
	'p_ip_adr' => array('string', ''),
	'p_max_booking' => array('int', -1),
	'p_duree_max_resa_area1' => array('int', -1),
	'p_duree_max_resa_area2' => array('int', -1),
);
// récupération des valeurs des variables passées en paramètres
foreach($form_vars as $var => $params)
    $$var = SecuChaine::GetFormVarSecure($var, $params[0], $params[1]);

$i = 0;
while ($i < $p_number_periodes)
{
	$varPeriode = 'p_periode_'.$i;
	$$varPeriode = SecuChaine::GetFormVarSecure($varPeriode, 'string', '');
	$i++;	
}

// modification d'une resource : administrateur de domaine ou plus
if (SecuAccess::UserLevel(getUserName(),-1) < 6)
{
  if ($p_id_area > 0)
	{
		// On verifie que le domaine $area existe
		$test = grr_sql_query1("SELECT id FROM ".TABLE_PREFIX."_area WHERE id='".$p_id_area."'");
		if ($test == -1)
		{
			showAccessDenied($back);
			exit();
		}
		// Il s'agit de la modif d'un domaine
		if ((SecuAccess::UserLevel(getUserName(), $p_id_area, 'area') < 4))
		{
			showAccessDenied($back);
			exit();
		}
	}
  	elseif ($p_id_site > 0){
        $test = grr_sql_query1("SELECT id FROM ".TABLE_PREFIX."_site WHERE id='".$p_id_site."'");
        if ($test == -1){
            showAccessDenied($back);
            exit();
        }
        elseif(SecuAccess::UserLevel(getUserName(),$p_id_site,'site')<5){
            showAccessDenied($back);
            exit();
        }
    }
}



/** Actions **/
	if($p_submit > 0)
	{
		// Affectation un site : si aucun site n'a ete affecte
		if ((Settings::get("module_multisite") == 1) && ($p_id_site == -1))
		{
			// On empeche le retour de la page admin_room
			unset($change_done);
		}
		else
		{
			// Un site a ete affecte, on peut continuer

			if($p_resolution_area <= 0)
				$p_resolution_area = 1800;

			// la valeur par defaut ne peut etre inferieure au plus petit bloc reservable
			if($p_duree_par_defaut_reservation_area < $p_resolution_area)
				$p_duree_par_defaut_reservation_area = $p_resolution_area;

			// la valeur par defaut doit etre un multiple du plus petit bloc reservable
			$duree_par_defaut_reservation_area = intval($p_duree_par_defaut_reservation_area / $p_resolution_area) * $p_resolution_area;
			
			// Duree maximale de reservation
			$p_duree_max_resa_area1 = is_numeric($p_duree_max_resa_area1) ? (int) $p_duree_max_resa_area1 : -1;
			$p_duree_max_resa_area2 = is_numeric($p_duree_max_resa_area2) ? (int) $p_duree_max_resa_area2 : -1;
			if ($p_enable_periods == 'y')
				$duree_max_resa_area = $p_duree_max_resa_area2 * 1440;
			else
			{
				$duree_max_resa_area = $p_duree_max_resa_area1;
				if ($duree_max_resa_area >= 0)
					$duree_max_resa_area = max($duree_max_resa_area, $p_resolution_area / 60, $duree_par_defaut_reservation_area / 60);
			}
			settype($duree_max_resa_area, "integer");
			if ($duree_max_resa_area < 0)
				$duree_max_resa_area = -1;


			// Affichage des jours de la semaine
			$display_days = "";
			for ($i = 0; $i < 7; $i++)
			{
				if (isset($p_display_day[$i]))
					$display_days .= "y";
				else
					$display_days .= "n";
			}
			if ($display_days != "nnnnnnn")
			{
				while (!isset($p_display_day[$p_weekstarts_area]))
					$p_weekstarts_area++;
			}
			if ($p_morningstarts_area > $p_eveningends_area)
				$p_eveningends_area = $p_morningstarts_area;

			// On verifie que l'utilisateur a le droit de modifier le domaine
			if ($p_access == 'r')
				$p_access='r';
			else
				$p_access='a';
			if ($p_action == 3 && $p_id_area != -1) // Modification d'un domaine
			{
				// S'il y a changement de type de creneaux, on efface les reservations du domaines
				$old_enable_periods = grr_sql_query1("select enable_periods from ".TABLE_PREFIX."_area WHERE id='".$p_id_area."'");
				if ($old_enable_periods != $p_enable_periods)
				{
					$del = grr_sql_query("DELETE ".TABLE_PREFIX."_entry FROM ".TABLE_PREFIX."_entry, ".TABLE_PREFIX."_room, ".TABLE_PREFIX."_area WHERE
						".TABLE_PREFIX."_entry.room_id = ".TABLE_PREFIX."_room.id and
						".TABLE_PREFIX."_room.area_id = ".TABLE_PREFIX."_area.id and
						".TABLE_PREFIX."_area.id = '".$p_id_area."'");
					$del = grr_sql_query("DELETE ".TABLE_PREFIX."_repeat FROM ".TABLE_PREFIX."_repeat, ".TABLE_PREFIX."_room, ".TABLE_PREFIX."_area WHERE
						".TABLE_PREFIX."_repeat.room_id = ".TABLE_PREFIX."_room.id and
						".TABLE_PREFIX."_room.area_id = ".TABLE_PREFIX."_area.id and
						".TABLE_PREFIX."_area.id = '".$p_id_area."'");
				}

				$sql = "UPDATE ".TABLE_PREFIX."_area SET
				area_name='".SecuChaine::ProtectDataSql($p_area_name)."',
				access='".SecuChaine::ProtectDataSql($p_access)."',
				order_display='".SecuChaine::ProtectDataSql($p_area_order)."',
				ip_adr='".SecuChaine::ProtectDataSql($p_ip_adr)."',
				calendar_default_values = 'n',
				duree_max_resa_area = '".SecuChaine::ProtectDataSql($duree_max_resa_area)."',
				morningstarts_area = '".SecuChaine::ProtectDataSql($p_morningstarts_area)."',
				eveningends_area = '".SecuChaine::ProtectDataSql($p_eveningends_area)."',
				resolution_area = '".SecuChaine::ProtectDataSql($p_resolution_area)."',
				duree_par_defaut_reservation_area = '".SecuChaine::ProtectDataSql($duree_par_defaut_reservation_area)."',
				eveningends_minutes_area = '".SecuChaine::ProtectDataSql($p_eveningends_minutes_area)."',
				weekstarts_area = '".SecuChaine::ProtectDataSql($p_weekstarts_area)."',
				enable_periods = '".SecuChaine::ProtectDataSql($p_enable_periods)."',
				twentyfourhour_format_area = '".SecuChaine::ProtectDataSql($p_twentyfourhour_format_area)."',
				max_booking='".$p_max_booking."',
				display_days = '".$display_days."',
				user_right = '".$p_user_right."',
				access_file = ".$p_access_file.",
				upload_file = ".$p_upload_file.",
				description_breve = '".$p_description_breve."',
				description_complete = '".$p_description_complete."'
				WHERE id=$p_id_area";
				if (grr_sql_command($sql) < 0)
				{
					fatal_error(0, get_vocab('update_area_failed') . grr_sql_error());
					$ok = 'no';
				}
			}
			else
			{
				$sql = "INSERT INTO ".TABLE_PREFIX."_area SET
				area_name='".SecuChaine::ProtectDataSql($p_area_name)."',
				access='".SecuChaine::ProtectDataSql($p_access)."',
				order_display='".SecuChaine::ProtectDataSql($p_area_order)."',
				ip_adr='".SecuChaine::ProtectDataSql($p_ip_adr)."',
				calendar_default_values = 'n',
				duree_max_resa_area = '".SecuChaine::ProtectDataSql($duree_max_resa_area)."',
				morningstarts_area = '".SecuChaine::ProtectDataSql($p_morningstarts_area)."',
				eveningends_area = '".SecuChaine::ProtectDataSql($p_eveningends_area)."',
				resolution_area = '".SecuChaine::ProtectDataSql($p_resolution_area)."',
				duree_par_defaut_reservation_area = '".SecuChaine::ProtectDataSql($duree_par_defaut_reservation_area)."',
				eveningends_minutes_area = '".SecuChaine::ProtectDataSql($p_eveningends_minutes_area)."',
				weekstarts_area = '".SecuChaine::ProtectDataSql($p_weekstarts_area)."',
				enable_periods = '".SecuChaine::ProtectDataSql($p_enable_periods)."',
				twentyfourhour_format_area = '".SecuChaine::ProtectDataSql($p_twentyfourhour_format_area)."',
				display_days = '".$display_days."',
				max_booking='".$p_max_booking."',
				id_type_par_defaut = '-1',
				user_right = '".$p_user_right."',
				access_file = ".$p_access_file.",
				upload_file = ".$p_upload_file.",
				description_breve = '".$p_description_breve."',
				description_complete = '".$p_description_complete."'
				";
				if (grr_sql_command($sql) < 0)
					fatal_error(1, "<p>" . grr_sql_error());
				$p_id_area = grr_sql_insert_id();
			}
			// Affectation d'un site
			if (Settings::get("module_multisite") == 1)
			{
				$sql = "DELETE FROM ".TABLE_PREFIX."_j_site_area WHERE id_area='".$p_id_area."'";
				if (grr_sql_command($sql) < 0)
					fatal_error(0, "<p>".grr_sql_error()."</p>");
				$sql = "INSERT INTO ".TABLE_PREFIX."_j_site_area SET id_site='".$p_id_site."', id_area='".$p_id_area."'";
				if (grr_sql_command($sql) < 0)
					fatal_error(0, "<p>".grr_sql_error()."</p>");
			}
			// Si area_name est vide on le change maintenant que l'on a l'id area
			if ($p_area_name == '')
			{
				$area_name = get_vocab("match_area")." ".$p_id_area;
				grr_sql_command("UPDATE ".TABLE_PREFIX."_area SET area_name='".SecuChaine::ProtectDataSql($area_name)."' WHERE id=$p_id_area");
			}
			// On crée ou recréé _area_periodes pour le domaine
			if ($p_enable_periods == 'y')
			{
				if ($p_number_periodes < 1)
					$p_number_periodes = 1;
				$del_periode = grr_sql_query("DELETE FROM ".TABLE_PREFIX."_area_periodes WHERE id_area='".$p_id_area."'");
				// On efface le modele par defaut avec area=0
				$del_periode = grr_sql_query("DELETE FROM ".TABLE_PREFIX."_area_periodes WHERE id_area='0'");
				$i = 0;
				$num = 0;
				while ($i < $p_number_periodes)
				{
					$temp = "p_periode_".$i;
					if (isset($$temp))
					{
						$nom_periode = corriger_caracteres($$temp);
						$reg_periode = grr_sql_query("INSERT INTO ".TABLE_PREFIX."_area_periodes SET
							id_area='".$p_id_area."',
							num_periode='".$num."',
							nom_periode='".SecuChaine::ProtectDataSql($nom_periode)."'
							");
						// On crée un modele par defaut avec area=0
						$reg_periode = grr_sql_query("INSERT INTO ".TABLE_PREFIX."_area_periodes SET
							id_area='0',
							num_periode='".$num."',
							nom_periode='".SecuChaine::ProtectDataSql($nom_periode)."'");
						$num++;
					}
					$i++;
				}
			}
			// Le domaine n'est pas ou plus restreint, on supprime les droits des utilisateurs et des groupes
			if ($p_access=='a')
			{
				$sql = "DELETE FROM ".TABLE_PREFIX."_j_user_area WHERE id_area='$p_id_area'";
				if (grr_sql_command($sql) < 0)
					fatal_error(0, get_vocab('update_area_failed') . grr_sql_error());

				$sql = "DELETE FROM ".TABLE_PREFIX."_j_group_area WHERE id_area='$p_id_area'";
				if (grr_sql_command($sql) < 0)
					fatal_error(0, get_vocab('update_area_failed') . grr_sql_error());
			}

			$d['enregistrement'] = 1;
			$p_action = 3; // On passe en mode modification une fois ajouté ou dupliqué
		}
	}

/** Affichage de la page **/
	$trad['TitrePage'] = $trad['admin_room'];

	$d['idSite']	= $p_id_site;
	$d['action']	= $p_action;
	$d['gOptionIp'] = $gOptionIp;

	if ($p_action == 1)
		$trad['SousTitrePage'] = get_vocab("addarea");
	elseif ($p_action == 2)
		$trad['SousTitrePage'] = get_vocab("duplique_domaine");
	else
		$trad['SousTitrePage'] = get_vocab("editarea");

	// Sites
	$sites = array();
	if (Settings::get("module_multisite") == 1)
	{
		if (SecuAccess::UserLevel(getUserName(), -1, 'area') >= 6)
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

	// Jours de la semaine: 0 pour dimanche, 1 pour lundi, etc.
	for ($i = 0; $i < 7; $i++)
		$JoursSemaine[] = array('num' => $i, 'nom' => day_name($i));

	// Infos du domaine
	if ($p_id_area > 0) // Modification ou duplication d'un domaine
	{
		$res = grr_sql_query("SELECT * FROM ".TABLE_PREFIX."_area WHERE id=$p_id_area");
		if (! $res)
			fatal_error(0, get_vocab('error_area') . $p_id_area . get_vocab('not_found'));
		$domaine = grr_sql_row_keyed($res, 0);
		grr_sql_free($res);

		$domaine["area_name"] = html_entity_decode($domaine["area_name"], ENT_QUOTES, 'UTF-8');

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
		if (Settings::get("module_multisite") == 1)
			$d['idSite'] = grr_sql_query1("SELECT id_site FROM ".TABLE_PREFIX."_j_site_area WHERE id_area='".$p_id_area."'");
	}
	else // Création d'un domaine
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
	}

	//Les creneaux de reservation sont bases sur des intitules pre-definis.
	$sql_periode = grr_sql_query("SELECT num_periode, nom_periode FROM ".TABLE_PREFIX."_area_periodes WHERE id_area='".$p_id_area."' ORDER BY num_periode");
	$num_periodes = grr_sql_count($sql_periode);

	if ($p_number_periodes == 0)
		$number_periodes = 10;
	else
		$number_periodes = $p_number_periodes;


	$d['numberPeriodes'] = $number_periodes;

	$i = 0;
	$d['nomCrenaux'] = "";
	while ($i < 50)
	{
		$nom_periode = grr_sql_query1("SELECT nom_periode FROM ".TABLE_PREFIX."_area_periodes WHERE id_area='".$p_id_area."' AND num_periode= '".$i."'");
		if ($nom_periode == -1)
			$nom_periode = "";
		$d['nomCrenaux'] .= "<div class=\"row mb-12\" style=\"display:none\" id=\"c".($i+1)."\">";
		$d['nomCrenaux'] .= "<label for=\"p_periode_".$i."\" class=\"col-md-6 col-form-label\">".get_vocab("intitule_creneau").($i+1).get_vocab("deux_points")."</label>";
		$d['nomCrenaux'] .= "<div class=\"col-md-6\">";
		$d['nomCrenaux'] .= "<input type=\"text\" class=\"form-control\" id=\"p_periode_".$i."\" name=\"p_periode_".$i."\" value=\"".htmlentities($nom_periode, ENT_QUOTES, 'UTF-8')."\" />";
		$d['nomCrenaux'] .= "</div></div>\n";
		$i++;
	}

	// L'utilisateur ne peut reserver qu'une duree limitee (-1 desactivee), exprimee en jours
	if ($domaine["duree_max_resa_area"] > 0)
		$d['nombreJourMax'] = max(round($domaine["duree_max_resa_area"]/1440,0),1);
	else
		$d['nombreJourMax'] = -1;

	echo $twig->render('admin_edit_domaine.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings, 'domaine' => $domaine, 'sites' => $sites, 'JoursSemaine' => $JoursSemaine));
?>