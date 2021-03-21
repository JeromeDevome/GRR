<?php
/* fichier.class.php
 * Permet de lire et d'écrire les paramètres dans la BDD (Table setting)
 * Dernière modification : $Date: 2021-03-21 17:30$
 * @author    JeromeB
 * @copyright Copyright 2003-2021 Team DEVOME - JeromeB
 * @link      http://www.gnu.org/licenses/licenses.html
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */

class Fichier {
	
	public static function TestDroitsDossier($dossier){

		$ok1 = 0;

		if ($f = @fopen($dossier.".test", "w"))
		{
			@fputs($f, '<'.'?php $ok1 = true; ?'.'>');
			@fclose($f);
			include($dossier.".test");
		}
		if (!$ok1)
			$statut = 0;
		else
		{
			$statut = 1;
			unlink($dossier.".test");
		}

		return $statut;
	}

	// Type 1 = dossier ressources
	public static function SupprimeDossier($dossierAsup, $type)
	{
		global $gcDossierImg;

		$controleSecurite = false;

		// Sécurité on ne peut supprimer qu'un dossier image dans personnalisation
		if($type == 1){
			$dossier = "../personnalisation/".$gcDossierImg."/ressources/".$dossierAsup; 
			$controleSecurite = true;
		}

		if($controleSecurite == true){

			if (is_dir($dossier)) { // si le paramètre est un dossier
				$objects = scandir($dossier); // on scan le dossier pour récupérer ses objets
				foreach ($objects as $object) { // pour chaque objet
					if ($object != "." && $object != "..") { // si l'objet n'est pas . ou ..
						if (filetype($dossier."/".$object) == "dir")
							rmdir($dossier."/".$object);else unlink($dossier."/".$object); // on supprime l'objet
					}
				}
				reset($objects); // on remet à 0 les objets
				rmdir($dossier); // on supprime le dossier
			}
		}

	}

}
?>
