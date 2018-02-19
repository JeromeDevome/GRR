<?php
/**
 * admin_config6.php
 * Interface permettant à l'administrateur la configuration des paramètres pour le module Jours Cycles
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2018-02-18 18:00$
 * @author    JeromeB
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




		$ligne = "";

		echo "<table border='1'>";
		echo "<tr><th>Nom</th><th width='200px'>Description</th><th>Version</th><th>Autheur</th><th>Licence</th><th>Activation</th></tr>";

		$path = "../modules/"; // chemin vers le dossier
		$iter = new DirectoryIterator($path);
		$files = [];
		foreach ($iter as $fileinfo) {
			if($fileinfo->isFile()) {

			} else {
				if($iter != "." && $iter != ".."){
					if(is_file('../modules/'.$iter.'/infos.php')){
						include '../modules/'.$iter.'/infos.php';
						$infosModule = array($module_nom,$module_description,$module_version,$module_autheur,$module_copyright);
					}
					if(!isset($infosModule)){
						$infosModule = array();
						$infosModule[0] = "<font color='red'>Erreur lecture</font>";
						$infosModule[1] = "<font color='red'>Erreur lecture</font>";
						$infosModule[2] = "<font color='red'>Erreur lecture</font>";
						$infosModule[3] = "<font color='red'>Erreur lecture</font>";
						$infosModule[4] = "<font color='red'>Erreur lecture</font>";
					}
					echo "<tr><td>".$infosModule[0]."<br>(".$iter.")</td><td>".$infosModule[1]."</td><td>".$infosModule[2]."</td><td>".$infosModule[3]."</td><td>".$infosModule[4]."</td><td></td></tr>";
					unset($infosModule);
				}
			}
		}

		echo "<table>";



?>
