<?php
/**
 * report.php
 * interface affichant un rapport des réservations
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2021-03-18 19:00$
 * @author    Laurent Delineau & JeromeB & Yan Naessens
 * @copyright Copyright 2003-2019 Team DEVOME - JeromeB
 * @link      http://www.gnu.org/licenses/licenses.htmlselectType
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */
$grr_script_name = "report.php";

include "./reservation/modeles/report.php";


//Récupération des informations relatives au serveur.
$back = '';
if (isset($_SERVER['HTTP_REFERER']))
	$back = htmlspecialchars($_SERVER['HTTP_REFERER']);
//Renseigne les droits de l'utilisateur, si les droits sont insuffisants, l'utilisateur est averti.
if (!verif_access_search(getUserName()))
{
	showAccessDenied($back);
	exit();
}

// Construction des identifiants de la ressource $room, du domaine $area, du site $id_site
Definition_ressource_domaine_site();
if ((Settings::get("authentification_obli") == 0) && (getUserName() == ''))
	$type_session = "no_session";
else
	$type_session = "with_session";

//Champs de création du rapport.
$From_day = isset($_GET["From_day"]) ? $_GET["From_day"] : NULL;
$From_month = isset($_GET["From_month"]) ? $_GET["From_month"] : NULL;
$From_year = isset($_GET["From_year"]) ? $_GET["From_year"] : NULL;
$To_day = isset($_GET["To_day"]) ? $_GET["To_day"] : NULL;
$To_month = isset($_GET["To_month"]) ? $_GET["To_month"] : NULL;
$To_year = isset($_GET["To_year"]) ? $_GET["To_year"] : NULL;
$champ = array();
$texte = array();
$type_recherche = array();
$gListeReservations = array();
$gListeResume = array();
$k = 0;
if (isset($_GET['champ'][0]))
{
	while ($k < count($_GET['champ']))
	{
		if ((isset($_GET['champ'][$k])) && ($_GET['champ'][$k] != "") && (isset($_GET['texte'][$k])) && ($_GET['texte'][$k] != ""))
		{
			$champ[] = $_GET['champ'][$k];
			$texte[] = $_GET['texte'][$k];
			$type_recherche[] = $_GET['type_recherche'][$k];
		}
		$k++;
	}
}
$summarize = isset($_GET["summarize"]) ? $_GET["summarize"] : 1;
if (!isset($_GET["sumby"]))
	$_GET["sumby"] = "6";
else
	settype($_GET["sumby"],"integer");

$d['sumby'] = $_GET["sumby"];
//$sortby = isset($_GET["sortby"])? $_GET["sortby"] : "d";

// Si la table j_user_area est vide, il faut modifier la requête
$test_grr_j_user_area = grr_sql_count(grr_sql_query("SELECT * FROM ".TABLE_PREFIX."_j_user_area"));

if (isset($champ[0]))
{
	//Applique les paramètres par defaut.
	//S'assurer que ces paramètres ne sont pas cités.
	$k = 0;
	while ($k < count($texte))
	{
		$texte[$k] = unslashes($texte[$k]);
		//Mettre les valeurs par défaut quand le formulaire est réutilisé.
		$texte_default[$k] = htmlspecialchars($texte[$k]);
		$k++;
	}
}
else
{
	$to_time = mktime(0, 0, 0, $month, $day + Settings::get("default_report_days"), $year);
	if (!isset($From_day))
		$From_day = $day;
	if (!isset($From_month))
		$From_month = $month;
	if (!isset($From_year))
		$From_year = $year;
	if (!isset($To_day))
		$To_day   = date("d", $to_time);
	if (!isset($To_month))
		$To_month = date("m", $to_time);
	if (!isset($To_year))
		$To_year  = date("Y", $to_time);
}
//$summarize:
// 1=Rapport seulement,
// 2=Résumé seulement,
// 3=Les deux

$d['summarize'] = $summarize;

get_vocab_admin("search_report_stats");
get_vocab_admin("deux_points");
get_vocab_admin("report_start");
get_vocab_admin("report_end");
get_vocab_admin("valide_toutes_les_conditions_suivantes");
get_vocab_admin("valide_au_moins_une_des_conditions_suivantes");
get_vocab_admin("choose");
get_vocab_admin("match_area");
get_vocab_admin("room");
get_vocab_admin("type");
get_vocab_admin("namebooker");
get_vocab_admin("match_descr");
get_vocab_admin("match_login");
get_vocab_admin("contient");
get_vocab_admin("ne_contient_pas");
get_vocab_admin("include");
get_vocab_admin("report_only");
get_vocab_admin("summary_only");
get_vocab_admin("report_and_summary");
get_vocab_admin("dlrapportcsv");
get_vocab_admin("dlresumecsv");
get_vocab_admin("summarize_by");
get_vocab_admin("summarize_by_precisions");
get_vocab_admin("sum_by_creator");
get_vocab_admin("sum_by_descrip");
get_vocab_admin("moderation");
get_vocab_admin("en_attente_moderation");
get_vocab_admin("moderation_acceptee");
get_vocab_admin("moderation_refusee");
get_vocab_admin("type");
get_vocab_admin("submit");

get_vocab_admin("ppreview");
get_vocab_admin("nothing_found");
get_vocab_admin("entry_found");
get_vocab_admin("entries_found");
get_vocab_admin("time");
get_vocab_admin("duration");
get_vocab_admin("lastupdate");
get_vocab_admin("summary_header_per");
get_vocab_admin("summary_header");

// Gestion du formulaire de recherche
$d['jQuery_DatePickerDebut'] = genDateSelectorForm("From_", $From_day, $From_month, $From_year,"");
$d['jQuery_DatePickerFin'] = genDateSelectorForm("To_", $To_day, $To_month, $To_year,"");

if (!isset($_GET["condition_et_ou"]) || ($_GET["condition_et_ou"] != "OR"))
	$d['checkedAND'] = "checked";
else
	$d['checkedOR'] = "checked";

if (isset($texte))
	$nb_ligne = max((count($texte) +2),5);
else
	$nb_ligne = 5;
$k = 0;
$conditions = array();
// On récupère les infos sur les champs additionnels
$overload_fields = mrbsOverloadGetFieldslist("");

while ($k < $nb_ligne)
{
	$selectCritere = "";
	if (isset($champ[$k]) && ($champ[$k] == "area"))
		$selectCritere = "area";
	if (isset($champ[$k]) && ($champ[$k] == "room"))
		$selectCritere = "room";
	if (isset($champ[$k]) && ($champ[$k] == "type"))
		$selectCritere = "type";
	if (isset($champ[$k]) && ($champ[$k] == "name"))
		$selectCritere = "name";
	if (isset($champ[$k]) && ($champ[$k] == "descr"))
		$selectCritere = "descr";
	if (isset($champ[$k]) && ($champ[$k] == "login"))
		$selectCritere = "login";

	// On récupère les infos sur les champs additionnels
	//$overload_fields = mrbsOverloadGetFieldslist("");
	// Boucle sur tous les champs additionnels
	$champsAdd = array();
	foreach ($overload_fields as $fieldname=>$fieldtype)
	{
		$selectAdd = 0;
		// if ($overload_fields[$fieldname]["confidentiel"] != 'y') filtrage trop strict
		if ((authGetUserLevel(getUserName(),-1) > 5) || ($overload_fields[$fieldname]['affichage'] == 'y'))
		{
			if (isset($champ[$k]) && ($champ[$k] == "addon_".$overload_fields[$fieldname]["id"]))
				$selectAdd = 1;
			$champsAdd[] = array('id' => $overload_fields[$fieldname]["id"], 'nom' => $fieldname, 'select' => $selectAdd);
		}
		
	}

	$selectType = "1";
	if (isset($type_recherche[$k]) && ($type_recherche[$k] == "0"))
		$selectType = "0";

	if (!isset($texte_default[$k]))
		$texte_default[$k] ="";
	
	$motRecherche = $texte_default[$k];

	$k++;
	
	$conditions[] = array('selectCritere' => $selectCritere, 'selectType' => $selectType, 'motRecherche' => $motRecherche, 'champAdd' => $champsAdd);
}


// [3]   Descrition brêve,(HTML) -> e.name
// [4]   Descrition,(HTML) -> e.description
// [5]   Type -> e.type
// [6]   réservé par (nom ou IP), (HTML) -> e.beneficiaire
// [12]  les champs additionnele -> e.overload_desc

// On récupère les infos sur le champ add pour le résumé par...
$champsAddResume = array();
$overload_fields = mrbsOverloadGetFieldslist("");
// Boucle sur tous les champs additionnels de l'area
foreach ($overload_fields as $fieldname=>$fieldtype)
{
	// if ($overload_fields[$fieldname]["confidentiel"] != 'y') filtrage trop strict
	if ((authGetUserLevel(getUserName(),-1) > 5) || ($overload_fields[$fieldname]['affichage'] == 'y'))
	{
		if ($_GET["sumby"] == $overload_fields[$fieldname]["id"])
			$selectAdd = 1;
		$champsAddResume[] = array('id' => $overload_fields[$fieldname]["id"], 'nom' => $fieldname, 'select' => $selectAdd);
	}
}

//------------------------------------------------------------
// Résultats:
if (isset($_GET["is_posted"]))
{
	$d['resultat'] = 1;
  
	//S'assurer que ces paramètres ne sont pas cités.
    $k = 0;
    while ($k < count($texte))
    {
        $texte[$k] = unslashes($texte[$k]);
        $k++;
    }
	//Les heures de début et de fin sont aussi utilisés pour mettre l'heure dans le rapport.
    $report_start = mktime(0, 0, 0, $From_month, $From_day, $From_year);
    $report_end = mktime(0, 0, 0, $To_month, $To_day+1, $To_year);
    //   La requête SQL va contenir les colonnes suivantes:
    // Col Index  Description:
    //   1  [0]   Entry ID, Non affiché -> e.id
    //   2  [1]   Date de début (Unix) -> e.start_time
    //   3  [2]   Date de fin (Unix) -> e.end_time
    //   4  [3]   Descrition brêve,(HTML) -> e.name
    //   5  [4]   Descrition,(HTML) -> e.description
    //   6  [5]   Type -> e.type
    //   7  [6]   réservé par (nom ou IP), (HTML) -> e.beneficiaire
    //   8  [7]   Timestamp (création), (Unix) -> e.timestamp
    //   9  [8]   Area (HTML) -> a.area_name
    //  10  [9]   Room (HTML) -> r.room_name
    //  11  [10]  Room description -> r.description
    //  12  [11]  id de l'area -> a.id
    //  13  [12]  les champs additionnels -> e.overload_desc
    //  14  [13]  rang d'affichage de la ressource -> r.order_display
    //  15  [14]  type de réservation -> t.type_name
	//  16  [15]  bénéficiaire extérieur -> e.beneficiaire_ext
	//  17  [16]  résa supprimer -> e.supprimer
	//  18  [17]  moderation -> e.moderate
    // Tableau des ressources invisibles pour l'utilisateur
    $sql = "SELECT distinct e.id, e.start_time, e.end_time, e.name, e.description, "
    . "e.type, e.beneficiaire, "
    .  grr_sql_syntax_timestamp_to_unix("e.timestamp")
    . ", a.area_name, r.room_name, r.description, a.id, e.overload_desc, r.order_display, t.type_name"
	. ", e.beneficiaire_ext, e.supprimer, e.moderate"
    . " FROM ".TABLE_PREFIX."_entry e, ".TABLE_PREFIX."_area a, ".TABLE_PREFIX."_room r, ".TABLE_PREFIX."_type_area t";
	// Si l'utilisateur n'est pas administrateur, seuls les domaines auxquels il a accès sont pris en compte
    if (authGetUserLevel(getUserName(),-1) < 6)
        if ($test_grr_j_user_area != 0)
            $sql .= ", ".TABLE_PREFIX."_j_user_area j ";
        $sql .= " WHERE e.room_id = r.id AND r.area_id = a.id AND supprimer =0";
	// on ne cherche pas parmi les ressources invisibles pour l'utilisateur
    $tab_rooms_noaccess = verif_acces_ressource(getUserName(), 'all');
    foreach ($tab_rooms_noaccess as $key)
    {
        $sql .= " and r.id != $key ";
    }
	// Si l'utilisateur n'est pas administrateur, seuls les domaines auxquels il a accès sont pris en compte
    if (authGetUserLevel(getUserName(),-1) < 6)
        if ($test_grr_j_user_area == 0)
            $sql .= " and a.access='a' ";
        else
            $sql .= " and ((j.login='".getUserName()."' and j.id_area=a.id and a.access='r') or (a.access='a')) ";

	$sql .= " AND e.start_time < $report_end AND e.end_time > $report_start";
	$k = 0;
	if (isset($champ[0]))
	{
		$sql .= " AND (";
			while ($k < count($texte))
			{
				if ($champ[$k] == "area")
					$sql .=  grr_sql_syntax_caseless_contains("a.area_name", $texte[$k], $type_recherche[$k]);
				if ($champ[$k] == "room")
					$sql .=  grr_sql_syntax_caseless_contains("r.room_name", $texte[$k], $type_recherche[$k]);
				if ($champ[$k] == "type")
					$sql .=  grr_sql_syntax_caseless_contains("t.type_name", $texte[$k], $type_recherche[$k]);
				if ($champ[$k] == "name")
					$sql .=  grr_sql_syntax_caseless_contains("e.name", $texte[$k], $type_recherche[$k]);
				if ($champ[$k] == "descr")
					$sql .=  grr_sql_syntax_caseless_contains("e.description", $texte[$k], $type_recherche[$k]);
				if ($champ[$k] == "login")
					$sql .=  grr_sql_syntax_caseless_contains("e.beneficiaire", $texte[$k], $type_recherche[$k]);
				$overload_fields = mrbsOverloadGetFieldslist("");
				foreach ($overload_fields as $fieldname=>$fieldtype)
				{
					// if ($overload_fields[$fieldname]["confidentiel"] != 'y') filtrage trop strict
					if ((authGetUserLevel(getUserName(),-1) > 5) || ($overload_fields[$fieldname]['affichage'] == 'y'))
					{
						if ($champ[$k] == "addon_".$overload_fields[$fieldname]["id"])
							$sql .=  grr_sql_syntax_caseless_contains_overload("e.overload_desc", $texte[$k], $overload_fields[$fieldname]["id"], $type_recherche[$k]);
					}
				}
				if ($k < (count($texte) - 1))
					$sql .= " ".$_GET["condition_et_ou"]." ";
				$k++;
			}
	$sql .= ")";
	}
	$sql .= " AND  t.type_letter = e.type ";
//	if ( $sortby == "a" )
			//Trié par: Area, room, debut, date/heure.
		$sql .= " ORDER BY 9,r.order_display,10,t.type_name,2";
/*	else if ( $sortby == "r" )
			//Trié par: room, area, debut, date/heure.
		$sql .= " ORDER BY r.order_display,10,9,t.type_name,2";
	else if ( $sortby == "d" )
			// Order by Start date/time, Area, Room
		$sql .= " ORDER BY 2,9,r.order_display,10,t.type_name";
	else if ( $sortby == "t" )
			//Trié par: type, Area, room, debut, date/heure.
		$sql .= " ORDER BY t.type_name,9,r.order_display,10,2";
	else if ( $sortby == "c" )
			//Trié par: réservant, Area, room, debut, date/heure.
		$sql .= " ORDER BY e.beneficiaire,9,r.order_display,10,2";
	else if ( $sortby == "b" )
			//Trié par: brève description, Area, room, debut, date/heure.
		$sql .= " ORDER BY e.name,9,r.order_display,10,2";*/
		// echo $sql." <br /><br />"; // en test
	$res = grr_sql_query($sql);
	if (!$res)
		fatal_error(0, grr_sql_error());
	$nmatch = grr_sql_count($res);

	$d['nbResultat'] = $nmatch;

	if ($nmatch == 0)
	{
		grr_sql_free($res);
	}
	else
	{
		if (($summarize == 1) || ($summarize == 3)) // tableau des détails des réservations
		{
			// X Colonnes champs additionnels
			$overload_fields_c = mrbsOverloadGetFieldslist("");
			// Boucle sur tous les champs additionnels de l'area
			$i = 1;
			$tablOverload = array();
			foreach ($overload_fields_c as $fieldname=>$fieldtype)
			{
				//if ($overload_fields_c[$fieldname]["confidentiel"] != 'y') // filtrage trop strict
				if ((authGetUserLevel(getUserName(),-1) > 5) || ($overload_fields_c[$fieldname]['affichage'] == 'y'))
				{
					//echo "<td>".$fieldname."</td>";
					$tablOverload[$i] = $overload_fields_c[$fieldname]["name"];
					$i++;
				}
			}
		}
		for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
		{
			// Récupération des données concernant l'affichage du planning du domaine
			get_planning_area_values($row[11]);
			if (($summarize == 1) || ($summarize == 3))
				reporton($row, $dformat);
			if (($summarize == 2) || ($summarize == 3))
			{
				if ($enable_periods=='y')
				{
					accumulate_periods($row, $count, $hours, $report_start, $report_end, $room_hash, $breve_description_hash, "n");
					$do_sum1 = 'y';
				}
				else
				{
					accumulate($row, $count2, $hours2, $report_start, $report_end, $room_hash2, $breve_description_hash2, "n");
					$do_sum2 = 'y';
				}
			}
		}

		if (($summarize == 2) || ($summarize == 3))
		{
			$d['enablePeriods'] = $enable_periods;
			// Décompte des créneaux réservées
			if (isset($do_sum1))
				do_summary($count, $hours, $room_hash, $breve_description_hash, 'y', '', "n");
			// Décompte des heures réservées
			if (isset($do_sum2))
				do_summary($count2, $hours2, $room_hash2, $breve_description_hash2, 'n', '', "n");
		}
	}
}

echo $twig->render('report.twig', array('trad' => $trad, 'd' => $d, 'settings' => $AllSettings, 'conditions' => $conditions, 'champsaddresume' => $champsAddResume, 'listeresa' => $gListeReservations, 'listeresume' => $gListeResume));

?>