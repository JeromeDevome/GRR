<?php
/* import.class.php
 * Permet de lire et d'écrire les paramètres dans la BDD (Table setting)
 * Dernière modification : $Date: 2020-05-03 21:00$
 * @author    JeromeB & Yan Naessens
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

class Import {

	public static function Image($dest, $nom)
	{
		global $gcDossierImg, $_FILES;

		$msg = "";

		$erreurDefautUpload = array(
			0=>"",
			1=>"La taille du fichier téléchargé excède la valeur de upload_max_filesize, configurée dans le php.ini",
			2=>"La taille du fichier téléchargé excède la valeur de MAX_FILE_SIZE, qui a été spécifiée dans le formulaire HTML.",
			3=>"Le fichier n'a été que partiellement téléchargé.",
			4=>"Aucun fichier n'a été téléchargé.",
			6=>"Un dossier temporaire est manquant.",
			7=>"Échec de l'écriture du fichier sur le disque.",
			8=>"Une extension PHP a arrêté l'envoi de fichier."
		);

		$doc_file = isset($_FILES["doc_file"]) ? $_FILES["doc_file"] : NULL;

		if($doc_file['error'] > 0)
		{
			$msg .= $erreurDefautUpload[$doc_file['error']];
		}
		/* Test pour bloquer les double extensions */
        elseif (count(explode('.', $doc_file['name'])) > 2)
		{
            $msg .= "L\'image n\'a pas pu être enregistrée : les seules extentions autorisées sont gif, png et jpg.\\n";
        }
        elseif  (preg_match("`\.([^.]+)$`", $doc_file['name'], $match))
		{
            $ext = strtolower($match[1]);
            if ($ext != 'jpg' && $ext != 'png'&& $ext != 'gif')
            {
                $msg .= "L\'image n\'a pas pu etre enregistree : les seules extentions autorisees sont gif, png et jpg.\\n";
            }
            else
			{
                /* deuxième test passé, l'extension est autorisée */
                /* 3ème test avec fileinfo */
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $fileType = finfo_file($finfo, $doc_file['tmp_name']);
                /* 4ème test avec gd pour valider que c'est bien une image malgré tout - nécessaire ou parano ? */
                switch($fileType) {
                    case "image/gif":
                        /* recreate l'image, supprime les data exif */
                        $logoRecreated = @imagecreatefromgif ( $doc_file['tmp_name'] );
                        /* fix pour la transparence */
                        imageAlphaBlending($logoRecreated, true);
                        imageSaveAlpha($logoRecreated, true);
                        $extSafe = "gif";
                        break;
                    case "image/jpeg":
                        $logoRecreated = @imagecreatefromjpeg ( $doc_file['tmp_name'] );
                        $extSafe = "jpg";
                        break;
                    case "image/png":
                        $logoRecreated = @imagecreatefrompng ( $doc_file['tmp_name'] );
                        /* fix pour la transparence */
                        imageAlphaBlending($logoRecreated, true);
                        imageSaveAlpha($logoRecreated, true);
                        $extSafe = "png";
                        break;
                    default:
                        $msg .= "L\'image n\'a pas pu être enregistrée : type mime incompatible.\\n";
                        $extSafe = false;
                        break;
                }
            }
            if (!$logoRecreated || $extSafe === false)
			{
                /* la fonction imagecreate a échoué, donc l'image est corrompue ou craftée */
                $msg .= "L\'image n\'a pas pu être enregistrée : fichier corrompu.\\n";
            }
            else
            {
				if (!is_dir($dest))
					mkdir($dest);
  
				/* je teste si la destination est writable */
                $ok1 = is_writable($dest);
                if (!$ok1)
                {
                    $msg .= "L\'image n\'a pas pu etre enregistree : probleme d\'ecriture sur le repertoire IMAGES. Veuillez signaler ce probleme e l\'administrateur du serveur.\\n";
                }
                else
                {
                    $ok1 = @copy($doc_file['tmp_name'], $dest.$doc_file['name']);
                    if (!$ok1)
                        $ok1 = @move_uploaded_file($doc_file['tmp_name'], $dest.$doc_file['name']);
                    if (!$ok1)
                    {
                        $msg .= "L\'image n\'a pas pu etre enregistree : probleme de transfert. Le fichier n\'a pas pu etre transfere sur le repertoire IMAGES. Veuillez signaler ce probleme à l\'administrateur du serveur.\\n";
                    }
                    else
                    {
                        $tab = explode(".", $doc_file['name']);
                        $ext = strtolower($tab[1]);
                        if (@file_exists($dest."img_".$nom.".".$extSafe))
                            @unlink($dest."img_".$nom.".".$extSafe);
                        rename($dest.$doc_file['name'],$dest."img_".$nom.".".$extSafe);
                        @chmod($dest."img_".$nom.".".$extSafe, 0666);
                        $picture_room = "img_".$nom.".".$extSafe;
                    }
                }
            }
        }
        else if ($doc_file['name'] != '')
        {
            $msg .= "L\'image n\'a pas pu être enregistrée : le fichier image sélectionné n'est pas valide !\\n";
        }

		return array($picture_room, $msg);
	}

}
?>
