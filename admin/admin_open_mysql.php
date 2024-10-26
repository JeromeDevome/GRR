<?php
/**
 * admin_open_mysql.php
 * script de restauration d'une sauvegarde
 * ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2024-10-26 11:16$
 * @author    JeromeB & Laurent Delineau & Sylvain Payeur & Yan Naessens
 * @copyright Copyright 2003-2024 Team DEVOME - JeromeB
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

$back = "./admin_accueil.php";
check_access(6, $back);

$msg = '';
$cas = 0;

if($restaureBBD == 1){
	$sql_file = isset($_FILES["sql_file"]) ? $_FILES["sql_file"] : NULL;
	$file_name = isset($_GET["file_name"]) ? clean_input($_GET["file_name"]) : NULL;
	VerifyModeDemo();
	if (!$file_name){
    if((is_null($sql_file)||(!$sql_file['name']))){
      $cas = 1; // pas de fichier choisi
      $msg = get_vocab("admin_import_users_csv11");
    }
    else{
      $file_name = str_replace("\\","/",dirname($sql_file['tmp_name'])."/".$sql_file['name']);
      $ok = @copy($sql_file['tmp_name'],$file_name);
      $file = fopen($file_name, "r") or exit("Unable to open file!");
      $line = fgets($file);
      // var_dump($line);
      if (!stristr($line,'#**************** BASE DE DONNEES'))
      {
        fclose($file);
        $cas = 2;
        $msg = "Il ne s'agit pas d'un fichier de sauvegarde GRR.";
      }
      else{
        $cas = 3;
        $out_html = "Version : ".substr($line,34,strpos(substr($line,34),"*"))."<br />";
        for ($i = 1; $i < 6; $i++)
        {
          $line = substr(fgets($file),2);
          $out_html.= $line."<br />";
        }
        fclose($file);
      }
    }
  }
  else
	{
		$file = fopen($file_name, "r") or exit("Erreur de lecture de fichier!");
    $cas = 4;
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
    $out_html = strlen($ok)." requêtes ont été exécutées " ;
    if (strrpos($ok, '0'))
		{
			$out_html.= "avec ".substr_count($ok,'0')." erreur(s) :";
			$out_html.= $error;
		}
		else
			$out_html.= "sans erreurs.";
  }
}
else{
  $msg = "La restauration depuis l'interface est désactivée";
}
  
// code html
if($cas == 0){
  start_page_w_header("","","","with_session");
  echo "<div class=\"container\">".PHP_EOL;
  echo "<div class='alert alert-warning center'>".$msg."</div>";
  echo "</div>".PHP_EOL;
}
elseif($cas == 1){
  start_page_w_header("", "", "", $type="with_session");
  echo "<div class=\"container\">".PHP_EOL;
  echo "<div class='alert alert-warning center'>".$msg."</div>";
  echo "<br /><a class='btn btn-default' type='button' href=\"admin_config4.php\">".get_vocab("back")."</a>";
  echo "</div>".PHP_EOL;
}
elseif($cas == 4){
  echo start_page_wo_header("", "no_session");
  echo "<div class='container center'>";
  echo "<div class='alert alert-info'>".$out_html."</div>";
  echo "<h3>La restauration est terminée !</h3>" ;
  echo "<br /><a class='btn btn-default' type='button' href='../login.php'>".get_vocab("msg_logout3")."</a>";
  echo "</div>".PHP_EOL;
  }
else{
  start_page_w_header("", "", "", $type="with_session");
  echo "<div class='center'><h3>Restauration à partir du ficher : ";
  echo $sql_file['name']."</h3></div>\n";
  echo "<div class=\"container\">".PHP_EOL;
  if($cas == 2){
    echo "<div class='alert alert-warning center'>".$msg."</div>";
    echo "<br /><a class=\"btn btn-default\" type=\"button\" href=\"admin_config4.php\">".get_vocab("back")."</a>";
  }
  elseif($cas = 3){
    echo "<div class='alert alert-info'>".$out_html."</div>";
    echo "<h1>".get_vocab("sure")."</h1>";
		echo "<h1><a class=\"btn btn-danger\" type=\"button\" href=\"admin_open_mysql.php?file_name=$file_name\">".get_vocab("YES")."!</a>&nbsp;&nbsp;<a class=\"btn btn-default\" type=\"button\" href=\"admin_config4.php\">".get_vocab("NO") . "!</a></h1>";
  }
  echo "</div></div>".PHP_EOL;
}
end_page();
?>