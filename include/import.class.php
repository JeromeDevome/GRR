<?php
/* import.class.php
 * Permet d'importer des éléments dans GRR
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2025-12-21 12:00$
 * @author    JeromeB & Yan Naessens
 * @copyright Copyright 2003-2025 Team DEVOME - JeromeB
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
            if ($ext != 'jpeg' && $ext != 'jpg' && $ext != 'png'&& $ext != 'gif')
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
					mkdir($dest, 0777, true);
  
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


    public static function DocumentResa($idResa)
	{
        global $gcDossierDoc, $gcTailleMaxDocResa, $_FILES;

        $uploadDir = realpath(".")."/personnalisation/".$gcDossierDoc."/";
        $msg = "";
        // vérifie qu'une réservation est associée
        if ($idResa == -1) {
            $msg .= '<p>'.'Erreur, aucune réservation associée'."</p>";
            return array($msg);
        }

        // vérifie la présence de fichiers
        if (!isset($_FILES['myFiles']) || empty($_FILES['myFiles']['name'])) {
            $msg .= "<br><span style='color:red'>Erreur, aucun fichier transmis</span></p>";
            return array($msg);
        }

        // normalise en tableau si un seul fichier envoyé
        if (!is_array($_FILES['myFiles']['name'])) {
            foreach (['name','type','tmp_name','error','size'] as $k) {
                if (!is_array($_FILES['myFiles'][$k])) {
                    $_FILES['myFiles'][$k] = array($_FILES['myFiles'][$k]);
                }
            }
        }

        $nb = count($_FILES['myFiles']['name']);
        if ($nb <= 0) {
            $msg .= "<br><span style='color:red'>Erreur, aucun fichier envoyé</span></p>";
            return array($msg);
        }

        // traite chaque fichier
        for ($i = 0; $i < $nb; $i++) {
            $origName = $_FILES['myFiles']['name'][$i];
            $size = $_FILES['myFiles']['size'][$i];
            $tmpName = $_FILES['myFiles']['tmp_name'][$i];

            $msg .= "<p> Fichier : ".$origName;
            $msg .= "<br>Taille : ".$size;

            // rejette les fichiers à double extension
            if (count(explode('.', $origName)) > 2) {
                $msg .= "<br>type de fichier inconnu";
                continue;
            }

            $fileExt = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
            if (!in_array($fileExt, ["jpg","jpeg","png","gif","pdf"])) {
                $msg .= "<br>type de fichier inconnu";
                continue;
            }

            // contrôle de la taille du fichier par rapport à $gcTailleMaxDocResa
            if ($size > $gcTailleMaxDocResa) {
                $msg .= "<br><span style='color:red'>Erreur, fichier trop volumineux (max : ".$gcTailleMaxDocResa." octets)</span>";
                continue;
            }

            if (!is_dir($uploadDir)) @mkdir($uploadDir, 0777, true);

            $copie = @move_uploaded_file($tmpName, $uploadDir.$origName);
            if (!$copie) {
                $err = isset($_FILES['myFiles']['error'][$i]) ? $_FILES['myFiles']['error'][$i] : 'unknown';
                $msg .= "<br><span style='color:red'>move_uploaded_file a échoué (error={$err}). tmp_name={$tmpName}</span>";
                if (!is_uploaded_file($tmpName)) {
                    $msg .= "<br><span style='color:red'>(is_uploaded_file=false)</span>";
                }
            }

            // prepare le rename du fichier
            $strf = ""; $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789_";
            srand(time()*$idResa);
            for ($c = 0; $c < 12; $c++) {
                $strf .= $chars[rand(0, strlen($chars)-1)];
            }
            $fileName = $idResa.$strf.".".$fileExt;

            if (rename($uploadDir.$origName, $uploadDir.$fileName)) {
                // ajout dans la base de donnée.
                $req = "INSERT INTO ".TABLE_PREFIX."_files (id_entry, file_name, public_name) VALUES ($idResa,'".protect_data_sql($fileName)."','".protect_data_sql($origName)."')";
                if (grr_sql_command($req) < 0) {
                    $msg .= "<br>erreur d'enregistrement sur base de donnée";
                } else {
                    if ($copie) {
                        $msg .= "<br> <span style='color:green'>Fichier enregistré</span></p>";
                        header('Location: app.php?p=semaine_all');
                    } else {
                        $msg .= "<br><span style='color:red'>Erreur d'enregistrement</span></p>";
                    }
                }
            } else {
                $exists = file_exists($uploadDir.$origName) ? 'yes' : 'no';
                $writable = is_writable($uploadDir) ? 'yes' : 'no';
                $msg .= "<br><span style='color:red'>Erreur, le fichier n'a pu être renommé</span>";
                $msg .= "<br>src_exists={$exists} uploadDir_writable={$writable}";
            }
        }

        return array($msg);
    }

}
?>
