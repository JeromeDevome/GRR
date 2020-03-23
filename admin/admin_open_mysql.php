<?php
/**
 * admin_open_mysql.php
 * script de restauration d'une sauvegarde
 * ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2020-03-21 11:30$
 * @author    JeromeB & Laurent Delineau & Sylvain Payeur & Yan Naessens
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

include "../include/admin.inc.php";

if($restaureBBD == 1){
	$back = "";
	$day   = date("d");
	$month = date("m");
	$year  = date("Y");
	check_access(6, $back);
	$sql_file = isset($_FILES["sql_file"]) ? $_FILES["sql_file"] : NULL;
	$file_name = isset($_GET["file_name"]) ? clean_input($_GET["file_name"]) : NULL;
	VerifyModeDemo();
	if (!$file_name)
		start_page_w_header("", "", "", $type="with_session");
	else
		echo start_page_wo_header("", "no_session");
	echo "<div class=\"container\">";
	echo "<br />";
	if (!$file_name && !$sql_file['name'])
		exit (get_vocab("admin_import_users_csv11")."<br /><a href=\"admin_config4.php\">".get_vocab("back")."</a></div></section></body></html>");
	if (!$file_name)
	{
		echo "<h3>Restauration à partir du ficher : ";
		echo $sql_file['name']."</h3>\n";
		$file_name = str_replace("\\","/",dirname($sql_file['tmp_name'])."/".$sql_file['name']);
		$ok = @copy($sql_file['tmp_name'],$file_name);
		$file = fopen($file_name, "r") or exit("Unable to open file!");
		$line = fgets($file);
		var_dump($line);
		if (!stristr($line,'#**************** BASE DE DONNEES'))
		{
			fclose($file);
			echo "<span class=\"avertissement\">Il ne s'agit pas d'un fichier de sauvegarde GRR.</span><br />";
		}
		else
		{
			echo "<table class='table table-bordered'><tr><td>";
			echo "Version : ".substr($line,34,strpos(substr($line,34),"*"))."<br />";
			for ($i = 1; $i < 6; $i++)
			{
				$line = substr(fgets($file),2);
				echo $line."<br />";
			}
			fclose($file);
			echo "</td></tr></table>";
		}
		echo "<h1>" .  get_vocab("sure") . "</h1>";
		echo "<h1><a href=\"admin_open_mysql.php?file_name=$file_name\">" . get_vocab("YES") . "!</a>     <a href=\"admin_config4.php\">" . get_vocab("NO") . "!</a></h1>";
		echo "</div>";
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
		echo "<h3>La restauration est terminée !</h3>" ;
		echo strlen($ok)." requêtes ont été exécutées " ;
		if (strrpos($ok, '0'))
		{
			echo "avec ".substr_count($ok,'0')." erreur(s) :";
			echo $error."<hr /></div>";
		}
		else
			echo "sans erreurs.";
			echo "<br /><a href='../login.php'>".get_vocab("msg_logout3")."</a></div>";
	}
}
end_page();
?>
