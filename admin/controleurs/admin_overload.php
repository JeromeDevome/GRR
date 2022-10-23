<?php
/**
 * admin_overload.php
 * Interface de création/modification des champs additionnels.
 * Dernière modification : $Date: 2017-12-16 14:00$
 * @author    JeromeB & Laurent Delineau & Marc-Henri PAMISEUX
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

$grr_script_name = "admin_overload.php";

check_access(4, $back);

if (isset($_POST["action"]))
	$action = $_POST["action"];
else
	$action = "default";
$res = grr_sql_query("SELECT id, area_name, access FROM ".TABLE_PREFIX."_area ORDER BY order_display");
if (!$res)
	fatal_error(0, grr_sql_error());
$userdomain = array();
if (grr_sql_count($res) != 0)
{
	for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
	{
		if (authGetUserLevel(getUserName(), $row[0], 'area') >= 4)
			$userdomain[$row[0]] = $row[1];
	}
}


if ($action == "add")
{
	$arearight = false;
	if (isset($_POST["id_area"]))
		$id_area = $_POST["id_area"];
	else
		$id_area = 0;
	settype($id_area,"integer");
	if (isset($_POST["fieldname"]))
		$fieldname = $_POST["fieldname"];
	else
		$fieldname = "";
	if (isset($_POST["fieldtype"]))
		$fieldtype = $_POST["fieldtype"];
	else
		$fieldtype = "";
	$fieldlist = "";
	if (isset($_POST["obligatoire"]))
		$obligatoire = "y";
	else
		$obligatoire = "n";
	if (isset($_POST["affichage"]))
		$affichage = "y";
	else
		$affichage = "n";
	if (isset($_POST["overload_mail"]))
		$overload_mail = "y";
	else
		$overload_mail = "n";
	if (isset($_POST["confidentiel"])){
		$confidentiel = "y";
		$affichage = "n";
		$overload_mail = "n";
	}
	else
		$confidentiel = "n";

    if (isset($_POST["mail_spec"]))
        $mail_spec = $_POST["mail_spec"];
    else
        $mail_spec = "";

	foreach ($userdomain as $key=>$value)
	{
		if ($key == $id_area)
			$arearight = true;
	}
	if ($arearight == true)
	{
		$sql = "INSERT INTO ".TABLE_PREFIX."_overload (id_area, fieldname, fieldtype, obligatoire, confidentiel, fieldlist, affichage, overload_mail, mail_spec) VALUES ($id_area, '".protect_data_sql($fieldname)."', '".protect_data_sql($fieldtype)."', '".$obligatoire."', '".$confidentiel."', '".protect_data_sql($fieldlist)."', '".$affichage."', '".$overload_mail."', '".$mail_spec."');";
		if (grr_sql_command($sql) < 0)
			fatal_error(0, "$sql \n\n" . grr_sql_error());
	}

}


if ($action == "delete")
{
	$arearight = false ;
	if (isset($_POST["id_overload"]))
		$id_overload = $_POST["id_overload"];
	else
		$id_overload = "";
	$sql = "SELECT id_area FROM ".TABLE_PREFIX."_overload WHERE id=$id_overload;";
	$resquery = grr_sql_query($sql);
	if (!$resquery)
		fatal_error(0, grr_sql_error());
	if (grr_sql_count($resquery) > 0)
		for ($i = 0; ($row = grr_sql_row($resquery, $i)); $i++)
		{
			foreach ($userdomain as $key=>$value)
			{
				if ($key == $row[0])
					$arearight = true;
			}
		}
	if ($arearight == true)
	{
		grrDelOverloadFromEntries($id_overload);
		$sql = "DELETE FROM ".TABLE_PREFIX."_overload WHERE id=$id_overload;";
		if (grr_sql_command($sql) < 0)
			fatal_error(0, "$sql \n\n" . grr_sql_error());
	}
}


if ($action == "change")
{
	$arearight = false ;
	if (isset($_POST["id_overload"]))
		$id_overload = $_POST["id_overload"];
	else
		$id_overload = "";
	settype($id_overload,"integer");
	if (isset($_POST["fieldname"]))
		$fieldname = $_POST["fieldname"];
	else
		$fieldname = "";
	if (isset($_POST["fieldtype"]))
		$fieldtype = $_POST["fieldtype"];
	else
		$fieldtype = "";
	if (isset($_POST["fieldlist"]))
		$fieldlist = $_POST["fieldlist"];
	else
		$fieldlist = "";
	if ($fieldtype != "list")
		$fieldlist = "";
	if (isset($_POST["obligatoire"]))
		$obligatoire = "y";
	else
		$obligatoire = "n";
	if (isset($_POST["affichage"]))
		$affichage = "y";
	else
		$affichage = "n";
	if (isset($_POST["overload_mail"]))
		$overload_mail = "y";
	else
		$overload_mail = "n";
	if (isset($_POST["confidentiel"]))
	{
		$confidentiel = "y";
		$affichage = "n";
		$overload_mail = "n";
	}
	else
		$confidentiel = "n";
    if (isset($_POST["mail_spec"]))
        $mail_spec = $_POST["mail_spec"];
    else
        $mail_spec = "";
	$sql = "SELECT id_area FROM ".TABLE_PREFIX."_overload WHERE id=$id_overload;";
	$resquery = grr_sql_query($sql);
	if (!$resquery)
		fatal_error(0, grr_sql_error());
	if (grr_sql_count($resquery) > 0)
		for ($i = 0; ($row = grr_sql_row($resquery, $i)); $i++)
		{
			foreach ($userdomain as $key=>$value)
			{
				if ($key == $row[0] )
					$arearight = true;
			}
		}
		if ($arearight == true)
		{
			$sql = "UPDATE ".TABLE_PREFIX."_overload SET
			fieldname='".protect_data_sql($fieldname)."',
			fieldtype='".protect_data_sql($fieldtype)."',
			obligatoire='".$obligatoire."',
			confidentiel='".$confidentiel."',
			affichage='".$affichage."',
			overload_mail='".$overload_mail."',
			fieldlist='".protect_data_sql($fieldlist)."',
			mail_spec='".protect_data_sql($mail_spec)."'
			WHERE id=$id_overload;";
			if (grr_sql_command($sql) < 0)
				fatal_error(0, "$sql \n\n" . grr_sql_error());
		}
	}


get_vocab_admin("admin_overload");
get_vocab_admin("explication_champs_additionnels");

get_vocab_admin("match_area");
get_vocab_admin("fieldname");
get_vocab_admin("fieldtype");
get_vocab_admin("champ_obligatoire");
get_vocab_admin("affiche_dans_les_vues");
get_vocab_admin("affiche_dans_les_mails");
get_vocab_admin("champ_confidentiel");
get_vocab_admin("envoy_mail_specifique");
get_vocab_admin("action");

get_vocab_admin("type_text");
get_vocab_admin("type_numeric");
get_vocab_admin("type_area");
get_vocab_admin("type_list");
get_vocab_admin("Liste_des_champs");

get_vocab_admin('add');
get_vocab_admin('confirm_del');
get_vocab_admin('delete');
get_vocab_admin('cancel');

get_vocab_admin("YES");
get_vocab_admin("NO");

get_vocab_admin("envois_mail_spec_exp");
get_vocab_admin("cas_fonctionnalite_mail_actif");


	foreach ($userdomain as $key=>$value)
		$domaines[] = array('id' => $key, 'nom' => $userdomain[$key]);

	$ind_div = 0;
	$champsAdd = array();

	foreach ($userdomain as $key=>$value)
	{
		$res = grr_sql_query("SELECT id, fieldname, fieldtype, obligatoire, fieldlist, affichage, overload_mail, confidentiel, mail_spec FROM ".TABLE_PREFIX."_overload WHERE id_area=$key ORDER BY fieldname;");
		if (!$res)
			fatal_error(0, grr_sql_error());

		if (grr_sql_count($res) != 0)
			for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
			{	
				$champsAdd[] = array('id' => $row[0], 'nom' => $row[1], 'domaine' => $userdomain[$key], 'type' => $row[2], 'obligatoire' => $row[3], 'affichagevue' => $row[5], 'affichagemail' => $row[6], 'confidentiel' => $row[7], 'liste' => $row[4], 'mail_spec' => $row[8]);
			}
		}

	echo $twig->render('admin_overload.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings, 'domaines' => $domaines, 'champsAdd' => $champsAdd));
?>