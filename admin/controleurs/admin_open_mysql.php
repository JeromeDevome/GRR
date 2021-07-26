<?php
/**
 * admin_open_mysql.php
 *
 * Dernière modification : $Date: 2017-12-16 14:00$
 * @author    JeromeB & Laurent Delineau & Sylvain Payeur
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

$grr_script_name = "admin_open_mysql.php";

if($restaureBBD == 1){
	$back = "";
	$day   = date("d");
	$month = date("m");
	$year  = date("Y");
	check_access(6, $back);
	$sql_file = isset($_FILES["sql_file"]) ? $_FILES["sql_file"] : NULL;
	$file_name = isset($_GET["file_name"]) ? $_GET["file_name"] : NULL;
	VerifyModeDemo();
	$detailBackup = "";

	if (!$file_name && !$sql_file['name'])
		exit (get_vocab("admin_import_users_csv11")."<br /><a href=\"?p=admin_config4\">".get_vocab("back")."</a></div></body></html>");
	if (!$file_name)
	{
		$trad['dNomFichier'] = $sql_file['name'];
		get_vocab_admin("cancel");
		
		$file_name = str_replace("\\","/",dirname($sql_file['tmp_name'])."/".$sql_file['name']);
		$ok = @copy($sql_file['tmp_name'],$file_name);
		$file = fopen($file_name, "r") or exit("Unable to open file!");
		$line = fgets($file);
		//var_dump($line);
		if (!stristr($line,'#**************** BASE DE DONNEES'))
		{
			fclose($file);
			$detailBackup .= "<span class=\"avertissement\">Il ne s'agit pas d'un fichier de sauvegarde GRR.</span><br />";
		}
		else
		{
			$detailBackup .= "BDD : ".substr($line,34,strpos(substr($line,34),"*"))." <br>";
			for ($i = 1; $i < 6; $i++)
			{
				$detailBackup .= substr(fgets($file),2)." <br>";
			}
			get_vocab_admin("confirmer");
			fclose($file);
		}
		
		$trad['dDetailBackup'] = $detailBackup;
		$trad['dFileName'] = $file_name;
		$trad['dEtat'] = "alert-warning";
		$trad['dFa'] = "fa fa-warning";
	}
	else
	{
		$file = fopen($file_name, "r") or exit("Erreur de lecture de fichier!");
		$ok = "";
		$error = "";
		while (!feof($file))
		{
			$line = fgets($file);
			while ($line[0] != '#' && !stristr($line, ';') && !feof($file))
			$line .= fgets($file);
			if (grr_sql_query($line))
				$ok .= "1";
			else
			{
				$ok .= "0";
				$error .= "<hr />".htmlspecialchars($line);
			}
		}
		fclose($file);
		unlink($file_name);
		$detailBackup = "La restauration est terminée ! ". strlen($ok)." requêtes ont été exécutées " ;
		if (strrpos($ok, '0'))
		{
			$detailBackup .= "avec ".substr_count($ok,'0')." erreur(s) :";
			$detailBackup .= $error."<hr />";
			$trad['dEtat'] = "alert-warning";
			$trad['dFa'] = "fa fa-warning";
		}
		else {
			$detailBackup .= "sans erreurs.";
			$trad['dEtat'] = "alert-success";
			$trad['dFa'] = "fa fa-check";
		}
		$trad['dDetailBackup'] = $detailBackup;
		get_vocab_admin("msg_logout3");
	}
}
?>