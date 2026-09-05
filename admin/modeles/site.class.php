<?php
/**
 * admin_site.php
 * Interface d'accueil de Gestion des sites de l'application GRR
 * Dernière modification : $Date: 2026-09-05 11:30$
 * @author    JeromeB
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


Class Adm_Site
{
	/**
	 * Compte le nombre de sites définis
	 * @return integer number of rows
	 */
	private static function count_sites()
	{
		$sql = "SELECT COUNT(*)
		FROM ".TABLE_PREFIX."_site";
		$res = grr_sql_query($sql);
		if ($res)
		{
			$sites = grr_sql_row($res,0);
			if (is_array($sites))
				return $sites[0];
			else
				$d['mesgSysteme'] = "Une erreur est survenue pendant le comptage des sites.";
		}
		else
			$d['mesgSysteme'] = "Une erreur est survenue pendant la préparation de la requète de comptage des sites.";
	}


	public static function create_site()
	{
		global $d, $p_submit;

		if($p_submit == 1) // Modification du site
		{
			$enregistrement = true;

			// les variables attendues et leur type
			$form_vars = array(
				'p_sitecode'		=> array('string', ''),
				'p_access'			=> array('alphanumeric', 'a'),
				'p_sitename' 		=> array('string', ''),
				'p_adresse_ligne1'	=> array('string', ''),
				'p_adresse_ligne2'	=> array('string', ''),
				'p_adresse_ligne3'	=> array('string', ''),
				'p_cp'				=> array('string', ''),
				'p_ville'			=> array('string', ''),
				'p_pays'			=> array('string', ''),
				'p_tel'				=> array('string', ''),
				'p_fax'				=> array('string', '')	
			);
			// récupération des valeurs des variables passées en paramètres
			foreach($form_vars as $var => $params)
				$$var = SecuChaine::GetFormVarSecure($var, $params[0], $params[1]);

			if ($p_sitecode == '' || $p_sitename == '')
			{
				$enregistrement = false;
				$d['mesgSysteme'] = get_vocab('required');
			}

			// Enregistrement du nouveau site
			if ($enregistrement == true)
			{
				$sql="INSERT INTO ".TABLE_PREFIX."_site
				SET sitecode='".strtoupper($p_sitecode)."',
				sitename='".SecuChaine::ProtectDataSql($p_sitename)."',
				access='".SecuChaine::ProtectDataSql($p_access)."',
				adresse_ligne1='".SecuChaine::ProtectDataSql($p_adresse_ligne1)."',
				adresse_ligne2='".SecuChaine::ProtectDataSql($p_adresse_ligne2)."',
				adresse_ligne3='".SecuChaine::ProtectDataSql($p_adresse_ligne3)."',
				cp='".SecuChaine::ProtectDataSql($p_cp)."',
				ville='".strtoupper(SecuChaine::ProtectDataSql($p_ville))."',
				pays='".strtoupper(SecuChaine::ProtectDataSql($p_pays))."',
				tel='".SecuChaine::ProtectDataSql($p_tel)."',
				fax='".SecuChaine::ProtectDataSql($p_fax)."'";
				if (grr_sql_command($sql) < 0)
					fatal_error(0,'<p>'.grr_sql_error().'</p>');
				mysqli_insert_id($GLOBALS['db_c']);
			}
			// On affiche le tableau des sites
			Adm_Site::read_sites();
		}

	}


	public static function read_sites()
	{
		global $d;

		$sites = array();

		if (Adm_Site::count_sites() > 0)
		{
			$sql = "SELECT id,sitecode,sitename,cp,ville,access FROM ".TABLE_PREFIX."_site ORDER BY sitename,ville,id";
			$res = grr_sql_query($sql);
			if ($res)
			{
				for ($i = 0; ($row=grr_sql_row($res,$i));$i++){
					$sites[] = array('idsite' => $row[0], 'code' => $row[1], 'nomsite' => $row[2], 'cp' => $row[3], 'ville' => $row[4], 'access' => $row[5]);
				}
			}
			else
				$d['mesgSysteme'] = 'Une erreur est survenue pendant la préparation de la requète de lecture des sites.';
		}

		return $sites;
	}


	public static function update_site($id_site)
	{
		global $d, $p_submit;

		if($p_submit == 1) // Modification du site
		{
			$enregistrement = true;

			// les variables attendues et leur type
			$form_vars = array(
				'p_sitecode'		=> array('string', ''),
				'p_access'			=> array('alphanumeric', 'a'),
				'p_sitename' 		=> array('string', ''),
				'p_adresse_ligne1'	=> array('string', ''),
				'p_adresse_ligne2'	=> array('string', ''),
				'p_adresse_ligne3'	=> array('string', ''),
				'p_cp'				=> array('string', ''),
				'p_ville'			=> array('string', ''),
				'p_pays'			=> array('string', ''),
				'p_tel'				=> array('string', ''),
				'p_fax'				=> array('string', '')	
			);
			// récupération des valeurs des variables passées en paramètres
			foreach($form_vars as $var => $params)
				$$var = SecuChaine::GetFormVarSecure($var, $params[0], $params[1]);

			if ($p_sitecode == '' || $p_sitename == '')
			{
				$enregistrement = false;
				$d['mesgSysteme'] = get_vocab('required');
			}

			// Sauvegarde des modifications
			if ($enregistrement == true)
			{
				$sql = "UPDATE ".TABLE_PREFIX."_site
				SET sitecode='".strtoupper(SecuChaine::ProtectDataSql($p_sitecode))."',
				access='".SecuChaine::ProtectDataSql($p_access)."',
				sitename='".SecuChaine::ProtectDataSql($p_sitename)."',
				adresse_ligne1='".SecuChaine::ProtectDataSql($p_adresse_ligne1)."',
				adresse_ligne2='".SecuChaine::ProtectDataSql($p_adresse_ligne2)."',
				adresse_ligne3='".SecuChaine::ProtectDataSql($p_adresse_ligne3)."',
				cp='".SecuChaine::ProtectDataSql($p_cp)."',
				ville='".strtoupper(SecuChaine::ProtectDataSql($p_ville))."',
				pays='".strtoupper(SecuChaine::ProtectDataSql($p_pays))."',
				tel='".SecuChaine::ProtectDataSql($p_tel)."',
				fax='".SecuChaine::ProtectDataSql($p_fax)."'
				WHERE id='".$id_site."'";
				if (grr_sql_command($sql) < 0)
					fatal_error(0,'<p>'.grr_sql_error().'</p>');
				mysqli_insert_id($GLOBALS['db_c']);
			}
			// On affiche le tableau des sites
			Adm_Site::read_sites();
		} else // Chargement des données du site à modifier
		{
			$res = grr_sql_query("SELECT * FROM ".TABLE_PREFIX."_site WHERE id='".$id_site."'");
			if (!$res)
				fatal_error(0,'<p>'.grr_sql_error().'</p>');
			$row = grr_sql_row_keyed($res, 0);
			grr_sql_free($res);
			$site['code'] = $row['sitecode'];
			$site['access'] = $row['access'];
			$site['nom'] = $row['sitename'];
			$site['adresse_ligne1'] = $row['adresse_ligne1'];
			$site['adresse_ligne2'] = $row['adresse_ligne2'];
			$site['adresse_ligne3'] = $row['adresse_ligne3'];
			$site['cp'] = $row['cp'];
			$site['ville'] = $row['ville'];
			$site['pays'] = $row['pays'];
			$site['tel'] = $row['tel'];
			$site['fax'] = $row['fax'];

			return $site;
		}

	}


	public static function delete_site($id)
	{
		grr_sql_command("DELETE FROM ".TABLE_PREFIX."_site where id='".$id."'");
		grr_sql_command("DELETE FROM ".TABLE_PREFIX."_j_site_area where id_site='".$id."'");
		grr_sql_command("DELETE FROM ".TABLE_PREFIX."_j_group_site where id_site='".$id."'");
		grr_sql_command("DELETE FROM ".TABLE_PREFIX."_j_useradmin_site where id_site='".$id."'");
		grr_sql_command("UPDATE ".TABLE_PREFIX."_utilisateurs SET default_site = '-1' WHERE default_site='".$id."'");
		$test = grr_sql_query1("SELECT VALUE FROM ".TABLE_PREFIX."_setting WHERE NAME='default_site'");
		if ($test == $id)
			grr_sql_command("DELETE FROM ".TABLE_PREFIX."_setting WHERE NAME='default_site'");
		// On affiche le tableau des sites
		Adm_Site::read_sites();
	}

}

?>