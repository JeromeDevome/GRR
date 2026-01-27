<?php
/**
 * admin_config5.php
 * Interface permettant à l'administrateur la configuration des des modules (Jours Cycles, multisite, modules externes)
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2026-01-27 10:55$
 * @author    Laurent Delineau & JeromeB
 * @copyright Copyright 2003-2026 Team DEVOME - JeromeB
 * @link      http://www.gnu.org/licenses/licenses.html
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */

get_vocab_admin("admin_config1");
get_vocab_admin("admin_config2");
get_vocab_admin("admin_config3");
get_vocab_admin("admin_config4");
get_vocab_admin("admin_config5");
get_vocab_admin("admin_config6");
get_vocab_admin("admin_config7");
$d['msgToast'] = "";

if (!Settings::load())
	die("Erreur chargement settings");

// Met à jour dans la BD le Jours/Cycles
if (isset($_GET['jours_cycles']))
{
	if (Settings::get("jours_cycles_actif") == "Oui")
		$activeModuleInt = "Non";
	else
		$activeModuleInt = "Oui";

	if (!Settings::set("jours_cycles_actif", $activeModuleInt))
		echo "Erreur lors de l'enregistrement de jours_cycles_actif ! <br />";
}

// Met à jour dans la BD "multisite"
if (isset($_GET['module_multisite']))
{
	if (Settings::get("module_multisite") == "Oui")
		$activeModuleInt = "Non";
	else
		$activeModuleInt = "Oui";

	if (!Settings::set("module_multisite", $activeModuleInt))
		echo "Erreur lors de l'enregistrement de module_multisite ! <br />";
	else
	{
		if ($activeModuleInt == 'Oui')
		{
			// On crée un site par défaut s'il n'en existe pas
			$id_site = grr_sql_query1("SELECT min(id) FROM ".TABLE_PREFIX."_site");
			if ($id_site == -1)
			{
				$sql="INSERT INTO ".TABLE_PREFIX."_site SET sitecode='1', sitename='site par defaut'";
				if (grr_sql_command($sql) < 0)
					fatal_error(0,'<p>'.grr_sql_error().'</p>');
				$id_site = mysqli_insert_id($GLOBALS['db_c']);
			}
			// On affecte tous les domaines à un site.
			$sql = "SELECT id FROM ".TABLE_PREFIX."_area";
			$res = grr_sql_query($sql);
			if ($res)
			{
				for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
				{
					// l'area est-elle déjà affectée à un site ?
					$test_site = grr_sql_query1("SELECT count(id_area) FROM ".TABLE_PREFIX."_j_site_area WHERE id_area='".$row[0]."'");
					if ($test_site == 0)
					{
						$sql="INSERT INTO ".TABLE_PREFIX."_j_site_area SET id_site='".$id_site."', id_area='".$row[0]."'";
						if (grr_sql_command($sql) < 0)
							fatal_error(0,'<p>'.grr_sql_error().'</p>');
					}
				}
			}
		}
	}
}


// Modules Externes

// Installation, Activation, Désactivation
if (isset($_GET['activation'])) {
	
	$iter = $_GET['activation'];

	$sql = "SELECT `nom`, `actif` FROM ".TABLE_PREFIX."_modulesext WHERE `nom` = '".$iter."';";
	$res = grr_sql_query($sql);

	if ($res)
	{
		$nb = grr_sql_count($res);
		if($nb > 0){
			$row = grr_sql_row($res, 0);
			if($row[1] == 0){
				grr_sql_command("UPDATE ".TABLE_PREFIX."_modulesext SET actif = '1' WHERE `nom` = '".$iter."'");
			} else{
				grr_sql_command("UPDATE ".TABLE_PREFIX."_modulesext SET actif = '0' WHERE `nom` = '".$iter."'");
			}
		} else{
			if(is_file('../personnalisation/modules/'.$iter.'/installation.php') && is_file('../personnalisation/modules/'.$iter.'/infos.php')){
				include '../personnalisation/modules/'.$iter.'/installation.php';
				include '../personnalisation/modules/'.$iter.'/infos.php';
				Module::Installation($iter, $module_versionBDD);
				
			} else{
				$d['enregistrement'] = 3;
				$d['msgToast'] = get_vocab('adm_cfg5_msg0');
			}
		}
	}

}

// Import de module
if (isset($_POST['ok']) && $upload_Module == 1) {
    // Enregistrement du logo
    //$_FILES['doc_file'] = isset($_FILES['doc_file']) ? $_FILES['doc_file'] : null;
    /* Test premier, juste pour bloquer les double extensions */
	if($_FILES['doc_file']['error'] > 0) {
        exit('Erreur n°'.$_FILES['file']['error']);
    }
    if (count(explode('.', $_FILES['doc_file']['name'])) > 2) {
		$d['enregistrement'] = 3;
        $d['msgToast'] .= get_vocab('adm_cfg5_err1');
    } elseif (preg_match("`\.([^.]+)$`", $_FILES['doc_file']['name'], $match)) {
        /* normalement, si on arrive ici l'image n'a qu'une extension */

        $ext = strtolower($match[1]);
        if ($ext != 'zip') {
			$d['enregistrement'] = 3;
            $d['msgToast'] .= get_vocab('adm_cfg5_err2');
        } else {
            /* deuxième test passé, l'extension est autorisée */

			if(is_uploaded_file($_FILES['doc_file']['tmp_name'])){
                /* je test si la destination est writable */
                $dest = '../temp/';
                $picturePath = $dest.$_FILES['doc_file']['name'];

                if (is_writable($dest)) {
                    /* je copie le logo pour valider avec la fonction move_uploaded_file */
                    $moveUploadReturn = move_uploaded_file($_FILES['doc_file']['tmp_name'], $picturePath);
                    if (!$moveUploadReturn) {
						$d['enregistrement'] = 3;
                        $d['msgToast'] .= get_vocab('adm_cfg5_err3d').$_FILES['doc_file']['name'].get_vocab('adm_cfg5_err3f');

                    } else {
						$zip = new ZipArchive;
						if ($zip->open($picturePath) === TRUE) {
							$zip->extractTo('../personnalisation/modules/');
							$zip->close();
						} else {
							$d['enregistrement'] = 3;
							$d['msgToast'] .= get_vocab('adm_cfg5_err8');
						}
						
                        $unlinkReturn = unlink($picturePath);
                        if (!$unlinkReturn) {
							$d['enregistrement'] = 3;
                            $d['msgToast'] = get_vocab('adm_cfg5_err9');
                        }
                    }

                } else {
					$d['enregistrement'] = 3;
                    $d['msgToast'] .= get_vocab('adm_cfg5_err5');
                }
			} else{
				$d['enregistrement'] = 3;
			    $d['msgToast'] .= get_vocab('adm_cfg5_err7');
			}
			
        }
    } elseif ($_FILES['doc_file']['name'] != '') {
		$d['enregistrement'] = 3;
        $d['msgToast'] .= get_vocab('adm_cfg5_err6');
    }
}



get_vocab_admin("Module_Int_Gestion");
get_vocab_admin("Module_Ext_Gestion");
get_vocab_admin("Module_Ext_Import");

get_vocab_admin("name");
get_vocab_admin("sum_by_descrip");
get_vocab_admin("action");

get_vocab_admin("Activer_module_jours_cycles");
get_vocab_admin("explication_debut_Jours_Cycles");
get_vocab_admin("explain_multisite");
get_vocab_admin("Activer_module_multisite");

get_vocab_admin("Module_Ext_Import_Description");
get_vocab_admin("version");


// Jour de Cycle
if (Settings::get("jours_cycles_actif") == "Oui")
	$d['dActiveJourCycle'] = 1;
else
	$d['dActiveJourCycle'] = 0;


// Multisite
if (Settings::get("module_multisite") == "Oui")
	$d['dActiveMultiSite'] = 1;
else
	$d['dActiveMultiSite'] = 0;

// Listes des modules Ext
$ligne = "";

$path = "../personnalisation/modules/"; // chemin vers le dossier
$iter = new DirectoryIterator($path);
$lienActivation = "";
$files = [];
$modulesext = array ();
foreach ($iter as $fileinfo) {
	if($fileinfo->isFile()) {

	} else {
		if($iter != "." && $iter != ".." && $iter != ""){
			$dossier = "".$iter;
			if(is_file('../personnalisation/modules/'.$dossier.'/infos.php')){
				$module_nom = "";
				include '../personnalisation/modules/'.$dossier.'/infos.php';
				if($module_nom != "") {

					if(isset($module_administrable) && $module_administrable == false)
					{
						$lienActivation = "";
						$activation = 0; // Non administrable

					}
					else
					{
						$sql = "SELECT `nom`, `actif` FROM ".TABLE_PREFIX."_modulesext WHERE `nom` = '".$dossier."';";
						$res = grr_sql_query($sql);
						if ($res)
						{
							$lienActivation = "admin_config.php?page_config=6&activation=".$dossier;
							$nb = grr_sql_count($res);
							if($nb > 0){
								$row = grr_sql_row($res, 0);
								
								if($row[1] == 0)
									$activation = 2; // Activer
								else
									$activation = 3; // Désactiver
							} else{
								$activation = 1; // Installer
							}
						}
					}

					$modulesext[] = array('nom' => $module_nom, 'dossier' => $dossier, 'description' => $module_description, 'version' => $module_version, 'auteur' => $module_autheur, 'copyright' => $module_copyright, 'activation' => $activation, 'lienActivation' => $lienActivation);

				} else{
					$modulesext[] = array('nom' => '', 'dossier' => $dossier, 'description' => "Impossible de lire le fichier", 'version' => "", 'auteur' => "", 'copyright' => "", 'activation' => "");
					$activation = 4; // Impossible
					$lienActivation = "#";
				}
			}
			unset($infosModule);
		}
	}
}
unset($iter);

// Formulaire import module
$trad['dUpload_Module'] = $upload_Module;

echo $twig->render($page.'.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings, 'modulesext' => $modulesext));

?>