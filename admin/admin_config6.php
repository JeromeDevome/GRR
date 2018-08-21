<?php
/**
 * admin_config6.php
 * Interface permettant à l'administrateur la configuration des paramètres pour les modules externes
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2018-08-23 10:30$
 * @author    JeromeB & Yan Naessens
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
// cette page reste à internationaliser
$grr_script_name = "admin_config6.php";

include "../include/admin.inc.php";

$back = '';
if (isset($_SERVER['HTTP_REFERER']))
	$back = htmlspecialchars($_SERVER['HTTP_REFERER']);
$_SESSION['chemin_retour'] = "admin_accueil.php";
check_access(6, $back);

$msg = '';

// Installation, Activation, Désactivation
if (isset($_GET['activation'])) 
{
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
		} 
        else{
			if(is_file('../modules/'.$iter.'/installation.php') && is_file('../modules/'.$iter.'/infos.php')){
				include '../modules/'.$iter.'/installation.php';
				include '../modules/'.$iter.'/infos.php';
				Module::Installation($iter, $module_versionBDD);
				
			} else{
				$msg .= "Impossible de trouver le fichier d'installation et ou d'infos !\\n";
			}
		}
	}
}

// Import de module
if (isset($_POST['ok']) && $upload_Module == 1) 
{
    // Enregistrement du logo
    //$_FILES['doc_file'] = isset($_FILES['doc_file']) ? $_FILES['doc_file'] : null;
    /* Test premier, juste pour bloquer les double extensions */
	if($_FILES['file']['error'] > 0) {
        exit('Erreur n°'.$_FILES['file']['error']);
    }
    if (count(explode('.', $_FILES['doc_file']['name'])) > 2) {

        $msg .= "Erreur 1 - Le module n\'a pas pu être importé : la seule extension autorisée est zip.\\n";
        $ok = 'no';

    } elseif (preg_match("`\.([^.]+)$`", $_FILES['doc_file']['name'], $match)) {
        /* normalement, si on arrive ici l'image n'a qu'une extension */

        $ext = strtolower($match[1]);
        if ($ext != 'zip') {
            $msg .= "Erreur 2 - Le module n\'a pas pu être importé : la seule extension autorisée est zip.\\n";
            $ok = 'no';
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
                        $msg .= "Erreur 3 - Le module n\'a pas pu être importé : problème de transfert. Le fichier ".$_FILES['doc_file']['name']." n\'a pas pu être transféré sur le répertoire \"temp\". Veuillez signaler ce problème à l\'administrateur du serveur.\\n";
                        $ok = 'no';
                    } else {
						$zip = new ZipArchive;
						if ($zip->open($picturePath) === TRUE) {
							$zip->extractTo('../modules/');
							$zip->close();
						} else {
							$msg .= "Erreur 8 - Le module n\'a pas pu être installé\\n";
							$ok = 'no';
						}
						
                        $unlinkReturn = unlink($picturePath);
                        if (!$unlinkReturn) {
                            $msg .= "Erreur 9 - Installation réussie, cependant archive non supprimée.  Cette erreur peut être ignorée.\\n";
                            $ok = 'no';
                        }
                    }

                } else {
                    $msg .= "Erreur 5 - Le module n\'a pas pu être enregistré : problème d\'écriture sur le répertoire \"temp\". Veuillez signaler ce problème à l\'administrateur du serveur.\\n";
                    $ok = 'no';
                }
			} else{
			    $msg .= "Erreur 7 - Le module n\'a pas pu être enregistré !\\n";
				$ok = 'no';	
			}
			
        }
    } elseif ($_FILES['doc_file']['name'] != '') {
        $msg .= "Erreur 6 - Le module n\'a pas pu être enregistré : le fichier sélectionné n'est pas valide !\\n";
        $ok = 'no';
    }
}



// Si pas de problème, message de confirmation
if (isset($_POST['ok'])) {
    $_SESSION['displ_msg'] = 'yes';
    if ($msg == '') {
        $msg = get_vocab('message_records');
    }
    Header('Location: '.'admin_config6.php?msg='.$msg);
    exit();
}
if ((isset($_GET['msg'])) && isset($_SESSION['displ_msg']) && ($_SESSION['displ_msg'] == 'yes')) {
    $msg = $_GET['msg'];
} else {
    $msg = '';
}


// Page

start_page_w_header("", "", "", $type="with_session");
if (isset($_GET['ok']))
{
	$msg = get_vocab("message_records");
	affiche_pop_up($msg, "admin");
}
include "admin_col_gauche2.php";
//include "../include/admin_config_tableau.inc.php";
echo "<div class='col-md-9 col-sm-8 col-xs-12'>";

// Formulaire import module
if($upload_Module == 1){
	echo "<h3>".get_vocab("Module_Ext_Import")."</h3>\n";
	echo '<form enctype="multipart/form-data" action="./admin_config6.php" id="nom_formulaire" method="post" >';
	echo get_vocab("Module_Ext_Import_Description").get_vocab("deux_points");
	echo "<input type='file' name='doc_file' /><br>\n";
	echo "<input class=\"btn btn-primary\" type=\"submit\" name=\"ok\" value=Import style=\"font-variant: small-caps;\"/>\n";
	echo "<hr />\n";
}

///////////////////////
//****
///*******
///////////////////


	$ligne = "";
	echo "<h3>".get_vocab("Module_Ext_Gestion")."</h3>\n";
	echo "<table class='table-bordered'>";
	echo "<tr><th>Nom</th><th>Description</th><th>Version</th><th>Auteur</th><th>Licence</th><th>Activation</th></tr>";

	$path = "../modules/"; // chemin vers le dossier
	$iter = new DirectoryIterator($path);
	$lienActivation = "";
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
					$activation = "<font color='red'>Impossible</font>";
					$lienActivation = "#";
				} else{
					$sql = "SELECT `nom`, `actif` FROM ".TABLE_PREFIX."_modulesext WHERE `nom` = '".$iter."';";
					$res = grr_sql_query($sql);
					if ($res)
					{
						$lienActivation = "admin_config6.php?activation=".$iter;
						$nb = grr_sql_count($res);
						if($nb > 0){
							$row = grr_sql_row($res, 0);
							
							if($row[1] == 0)
								$activation = "Activer";
							else
								$activation = "Désactiver";
						} else{
							$activation = "Installer";
						}
					}
				}
				echo "<tr><td>".$infosModule[0]."<br>(".$iter.")</td><td>".$infosModule[1]."</td><td>".$infosModule[2]."</td><td>".$infosModule[3]."</td><td>".$infosModule[4]."</td><td><a href='".$lienActivation."'>".$activation."</a></td></tr>";
				unset($infosModule);
			}
		}
	}
    echo "</table>";
    echo "</form>";
// fin de la colonne de droite et de la page
echo "</div></section></body></html>";
?>
