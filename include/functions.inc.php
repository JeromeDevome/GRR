<?php
/**
 * include/functions.inc.php
 * fichier Bibliothèque de fonctions de GRR
 * Dernière modification : $Date: 2026-06-15 18:10$
 * @author    JeromeB & Laurent Delineau & Marc-Henri PAMISEUX & Yan Naessens
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

/**
 * Valide que l'URL de redirection appartient au même domaine/protocole/port
 * Protège contre les attaques de phishing via redirection ouvert
 * @param string $redirect_url L'URL de redirection à valider
 * @return bool true si la redirection est sûre, false sinon
 */
function is_safe_redirect($redirect_url)
{
	// Refuser les URLs vides ou mal formées
	if (empty($redirect_url) || !is_string($redirect_url))
		return false;
	
	// Parser l'URL cible
	$parsed_redirect = parse_url($redirect_url);
	if ($parsed_redirect === false)
		return false;
	
	// Accepter les chemins relatifs internes (commençant par ./ ou /)
	if (!isset($parsed_redirect['scheme']))
	{
		if (strpos($redirect_url, './') === 0 || strpos($redirect_url, '/') === 0)
			return true;
		return false;
	}
	
	// Parser l'URL actuelle du serveur
	$current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
	$parsed_current = parse_url($current_url);
	
	// Vérifier protocole, hôte et port
	if (isset($parsed_redirect['scheme']) && $parsed_redirect['scheme'] !== $parsed_current['scheme'])
		return false;
	
	if (isset($parsed_redirect['host']) && $parsed_redirect['host'] !== $parsed_current['host'])
		return false;
	
	if (isset($parsed_redirect['port']) && $parsed_redirect['port'] !== ($parsed_current['port'] ?? null))
		return false;
	
	return true;
}

/**
 * Valide et redirige vers une URL interne sûre
 * @param string $redirect_url L'URL de redirection
 * @param string $default_url L'URL par défaut si la redirection n'est pas sûre
 */
function safe_redirect($redirect_url, $default_url = './')
{
	if (is_safe_redirect($redirect_url))
		header("Location: " . $redirect_url);
	else
		header("Location: " . $default_url);
	exit();
}

function getDaysInMonth($month, $year)
{
	return date('t', mktime(0, 0, 0, $month, 1, $year));
}

function getWeekNumber($date)
{
	return date('W', $date);
}
// fonction de détermination des jours de vacances scolaires par lecture dans la base
function isSchoolHoliday($now)
{
	$test = grr_sql_query1("SELECT DAY FROM ".TABLE_PREFIX."_calendrier_vacances where DAY = '".$now."'");
	$val = ($test != -1);
	return $val;
}
// fonction de calcul des jours fériés (France)
function setHolidays($year = null)
{
	if ($year === null)
		$year = intval(date('Y'));
	$easterDate  = easter_date($year);
	$easterDay   = date('j', $easterDate);
	$easterMonth = date('n', $easterDate);
	$easterYear  = date('Y', $easterDate);
	$holidays = array(
	// Dates fixes
	mktime(0, 0, 0, 1,  1,  $year),  // 1er janvier
	mktime(0, 0, 0, 5,  1,  $year),  // Fête du travail
	mktime(0, 0, 0, 5,  8,  $year),  // Victoire des alliés
	mktime(0, 0, 0, 7,  14, $year),  // Fête nationale
	mktime(0, 0, 0, 8,  15, $year),  // Assomption
	mktime(0, 0, 0, 11, 1,  $year),  // Toussaint
	mktime(0, 0, 0, 11, 11, $year),  // Armistice
	mktime(0, 0, 0, 12, 25, $year),  // Noel
	// Dates variables
	mktime(0, 0, 0, $easterMonth, $easterDay + 1,  $easterYear),
	mktime(0, 0, 0, $easterMonth, $easterDay + 39, $easterYear),
	mktime(0, 0, 0, $easterMonth, $easterDay + 50, $easterYear),
	);
	sort($holidays);
	return $holidays;
}
// fonction de détermination si un jour est férié
function isHoliday($now){
	$test = grr_sql_query1("SELECT DAY FROM ".TABLE_PREFIX."_calendrier_feries where DAY = '".$now."'");
	$val = ($test != -1);
	return $val;
}

// $type : 0 => jours cycle; 1 => Fonction Calendrier hors réservation; 2 => Fonction Calendrier feries; 3 => calendrier vacances (scolaires par défaut)
function cal($month, $year, $type)
{
	global $weekstarts;

	if (!isset($weekstarts))
		$weekstarts = 0;
	$s = "";
	$daysInMonth = getDaysInMonth($month, $year);
	$date = mktime(12, 0, 0, $month, 1, $year);
	$first = (date('w',$date) + 7 - $weekstarts) % 7;
	$monthName = ucfirst(utf8_strftime("%B", $date));

	$s .= '<table class="table calendar2">'.PHP_EOL;
	$s .= '<tr>'.PHP_EOL;
	$s .= '<td class="calendarHeader2" colspan="8">'.$monthName.' '.$year.'</td>'.PHP_EOL;
	$s .= '</tr>'.PHP_EOL;
	$d = 1 - $first;
	$is_ligne1 = 'y';
	while ($d <= $daysInMonth)
	{
		$s .= '<tr>'.PHP_EOL;
		for ($i = 0; $i < 7; $i++)
		{
			$basetime = mktime(12, 0, 0, 6, 11 + $weekstarts, 2000);
			$show = $basetime + ($i * 24 * 60 * 60);
			$nameday = utf8_strftime('%A',$show);
			$temp = mktime(0, 0, 0, $month, $d, $year);
			if ($i == 0)
				$s .= '<td class="calendar2" style="vertical-align:bottom;"><b>S'.getWeekNumber($temp).'</b></td>'.PHP_EOL;
			$s .= '<td class="calendar2" align="center" valign="top">'.PHP_EOL;
			if ($is_ligne1 == 'y')
				$s .=  '<b>'.ucfirst(substr($nameday,0,1)).'</b><br />';
      if ($d > 0 && $d <= $daysInMonth)
      {
        $s .= $d;
        if($type == 0)
          $day = grr_sql_query1("SELECT day FROM ".TABLE_PREFIX."_calendrier_jours_cycle WHERE day=?","i",[$temp]);
        elseif($type == 1)
          $day = grr_sql_query1("SELECT day FROM ".TABLE_PREFIX."_calendar WHERE day=?","i",[$temp]);
        elseif($type == 2)
          $day = grr_sql_query1("SELECT day FROM ".TABLE_PREFIX."_calendrier_feries WHERE day=?","i",[$temp]);
        elseif($type == 3)
          $day = grr_sql_query1("SELECT day FROM ".TABLE_PREFIX."_calendrier_vacances WHERE day=?","i",[$temp]);
        else
          $day = -1;
        $s .= '<br><input type="checkbox" name="'.$temp.'" value="'.$nameday.'" ';
        if (!($day < 0))
          $s .= 'checked="checked" ';
        $s .= '/>';
      }
			else
				$s .= " ";
			$s .= '</td>'.PHP_EOL;
			$d++;
		}
		$s .= '</tr>'.PHP_EOL;
		$is_ligne1 = 'n';
	}
	$s .= '</table>'.PHP_EOL;
	return $s;
}

/** function checkPassword($pwd, $pwd_hash, $login, $test_rehash)
* vérifie que le mot de passe fourni $pwd correspond au $pwd_hash issu de la BDD pour l'utilisateur associé au $login
* $test_rehash indique s'il faut tester que le mot de passe haché doit être mis à jour
* si le mot de passe n'a pas été enregistré par la fonction password_hash, mais est valide pour md5 ou l'algo de v4.0.0, alors, si la base est en version 3.5.1+, la fonction le convertit au passage et l'enregistre au nouveau format
* renvoie TRUE si le mot de passe est valable, FALSE sinon ; déclenche une erreur si l'enregistrement du nouveau mot de passe échoue
*/
function checkPassword($pwd, $pwd_hash, $login, $test_rehash = TRUE){
	global $algoPwd;

    $result = false;
    $do_rehash = false;

    /* si $pwd_hash commence par '$' il est censé être issu de password_hash */
    if (password_verify($pwd, $pwd_hash))
    { // c'est un mot de passe codé par password_hash, voyons s'il faut le mettre à jour
        $result = true;
        if ($test_rehash &&(password_needs_rehash($pwd_hash, PASSWORD_DEFAULT)))
    	{
            $do_rehash = true;
        }
    }
    /* sinon $pwd_hash est censé être issu de MD5 ou de l'algorithme de la v4.0.0 */
    elseif (md5($pwd) == $pwd_hash)
    {
        $result = true;
        // si la base est 3.5.1+, on mettra à jour le mot de passe
        $ver = grr_sql_query1("SELECT VALUE FROM ".TABLE_PREFIX."_setting WHERE NAME='version';");
        if($ver >= "0400000")
        	$do_rehash = $test_rehash;
    }
	elseif(hash($algoPwd, Settings::get("hashpwd2").$pwd) == $pwd_hash) 	// Controle de l'algo V4.0.0
	{
			$result = true;
            $do_rehash = $test_rehash;
	}

    if ($do_rehash)
    {
        $pwd_hash = password_hash($pwd, PASSWORD_DEFAULT);
        $sql = "UPDATE ".TABLE_PREFIX."_utilisateurs SET password = '$pwd_hash' WHERE login = '".strtoupper($login)."';";
        if (grr_sql_command($sql) < 0)
			fatal_error(0, "<p>".$sql."<br>" . grr_sql_error());
    }

    return $result;
}

/**
 * Fonction de verification que le mot de passe comporte bien les différents prérequis
 * @param string $pwd
 */
function check_password_difficult($pwd)
{
	global $niveauDossier, $gMdpFacile;

	$nb_min_caractere = Settings::get("pass_leng");
	$nb_min_minuscules = Settings::get("pass_nb_min");
	$nb_min_majuscules = Settings::get("pass_nb_maj");
	$nb_min_chiffres = Settings::get("pass_nb_ch");
	$nb_min_speciaux = Settings::get("pass_nb_sp");

    // Compter les caractères correspondant à chaque critère
    $minuscules = preg_match_all('/[a-z]/', $pwd);
    $majuscules = preg_match_all('/[A-Z]/', $pwd);
    $chiffres = preg_match_all('/[0-9]/', $pwd);
    $speciaux = preg_match_all('/[\W_]/', $pwd); // \W capture tout sauf lettres et chiffres, _ est ajouté explicitement

    // Vérifier si les critères sont remplis
    if (strlen($pwd) >= $nb_min_caractere &&
		$minuscules >= $nb_min_minuscules &&
        $majuscules >= $nb_min_majuscules &&
        $chiffres >= $nb_min_chiffres &&
        $speciaux >= $nb_min_speciaux)
	{
		if(Settings::get("pass_simple") == "1")
		{
			$pwdMin = strtolower($pwd);
			$prefixeChemin = cheminDetermination($niveauDossier);
			$fichier = $prefixeChemin."include/most_used_passwords.txt";

			if (file_exists($fichier))
				$listeMdp = file($fichier, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

			// Fusion avec la globale $gMdpFacile
			if (is_array($gMdpFacile))
				$listeMdp = array_merge($listeMdp, $gMdpFacile);

			// Vérification
			foreach ($listeMdp as $ligne)
			{
				if ($pwdMin === strtolower(trim($ligne)))
					return false;
			}
		}
		
    } else {
        return false;
    }

	return true;
}

/**
 * Fonction qui compare 2 valeurs
 * @param string $a
 * @param integer $b
 * @return string
 */
function cmp3($a, $b)
{
	if ($a < $b)
		return "< ";
	if ($a == $b)
		return "= ";
	return "> ";
}

function get_request_uri()
{
	global $grr_script_name;
	$RequestUri = "";
	if (isset($_SERVER['REQUEST_URI']))
		$RequestUri = $_SERVER['REQUEST_URI'];
	else if (isset($_ENV['REQUEST_URI']))
		$RequestUri = $_ENV['REQUEST_URI'];
	else if (isset($_SERVER['HTTP_X_REWRITE_URL']))
		$RequestUri = $_SERVER['HTTP_X_REWRITE_URL'];
	else
	{
		if (!isset($_SERVER['QUERY_STRING']))
			$_SERVER['QUERY_STRING'] = "";
		if ((Settings::get("use_grr_url") == 1) && (Settings::get("grr_url") != ""))
		{
			if (substr(Settings::get("grr_url"), -1) != "/")
				$ad_signe = "/";
			else
				$ad_signe = "";
			$RequestUri = Settings::get("grr_url").$ad_signe.$grr_script_name.$_SERVER['QUERY_STRING'];
		}
		else
		{
			if (isset($_SERVER['PHP_SELF']))
				$RequestUri = $_SERVER['PHP_SELF'].$_SERVER['QUERY_STRING'];
		}
	}
	return $RequestUri;
}

/**
 * Affiche un lien email
 * @param string $_cible
 * @param string $_type_cible
 * @param string $option_affichage
 * @return string
 */
function affiche_lien_contact($_cible, $_type_cible, $option_affichage)
{

	if ($_type_cible == "identifiant:non")
	{
		if ($_cible == "contact_administrateur")
		{
			$_email = Settings::get("webmaster_email");
			$_identite = get_vocab('administrator_contact');
		}
		else if ($_cible == "contact_support")
		{
			$_email = Settings::get("technical_support_email");
			$_identite = get_vocab('technical_contact');
		}
		else
		{
			$_email = "";
			$_identite = "";
		}
	}
	else
	{
		$sql_cible = "SELECT prenom, nom, email FROM ".TABLE_PREFIX."_utilisateurs WHERE login = '".$_cible."'";
		$res_cible = grr_sql_query($sql_cible);
		if ($res_cible)
		{
			$row_cible = grr_sql_row($res_cible, 0);
			$_email = $row_cible[2];
			$_identite = $row_cible[0]." ".$row_cible[1];
			grr_sql_free($res_cible);
		}
		else
		{
			$_email = "";
			$_identite = "";
		}
	}

	if (Settings::get("envoyer_email_avec_formulaire") == 1)
	{
		if ($_email == "")
		{
			if ($option_affichage == "afficher_toujours")
				$affichage = $_identite;
			else
				$affichage = "";
		}
		else
            $affichage = '<a href="javascript:centrerpopup(\'app.php?p=contact&cible='.$_cible.'&amp;type_cible='.$_type_cible.'\',600,480,\'scrollbars=yes,statusbar=no,resizable=yes\')" title="'.$_identite.'\">'.$_identite.'</a>'.PHP_EOL;
	}
	else
	{   // Il s'agit de calculer une balise mailto
		$affichage = "";
		if ($_email == "")
		{
			if ($option_affichage == "afficher_toujours")
				$affichage = $_identite;
		}
		else
		{
            if (SecuChaine::ValideMail($_email))
            {
                $affichage = '<a href="mailto:'.$_email.'">'.$_identite.'</a>';
            }
            else 
                if ($option_affichage == "afficher_toujours")
                    $affichage = $_identite;
		}
	}
	return $affichage;
}

/**
 *Fonction qui calcule $room, $area et $id_site à partir de $_GET['room'], $_GET['area'], $_GET['id_site']
 */
function Definition_ressource_domaine_site()
{
	global $room, $area, $id_site;

	$id_site = 0;
	$area = 0;
	$room = 0;

	if (isset($_GET['room']) && $_GET['room'] != 'all' && $_GET['room'] != 0)
	{
		$room = intval(SecuChaine::CleanInput($_GET['room']));
		$area = mrbsGetRoomArea($room);
		$id_site = mrbsGetAreaSite($area);
	}
	else
	{
		if (isset($_GET['area']))
		{
			$area = intval(SecuChaine::CleanInput($_GET['area']));
			$id_site = mrbsGetAreaSite($area);
		}
		else
		{
			if (isset($_GET["id_site"]))
			{
				$id_site = intval(SecuChaine::CleanInput($_GET["id_site"]));
				$area = get_default_area($id_site);
			}
			else
			{
				$id_site = get_default_site();
				$area = get_default_area($id_site);
			}
		}
	}
}
/**
 *function affiche_ressource_empruntee_twig
 *- $id_room : identifiant de la ressource
 *- Si la ressource est empruntée, affiche une icône avec un lien vers la réservation pour laquelle la ressource est empruntée.
 * @param string $id_room
 * @return string
 */
function affiche_ressource_empruntee_twig($id_room, $type = "logo")
{
	$active_ressource_empruntee = grr_sql_query1("SELECT active_ressource_empruntee FROM ".TABLE_PREFIX."_room WHERE id = '".$id_room."'");
	$valeur = "";
	if ($active_ressource_empruntee == 'y')
	{
		$id_resa = grr_sql_query1("SELECT id FROM ".TABLE_PREFIX."_entry WHERE room_id = '".$id_room."' AND statut_entry='y'");
		if ($id_resa != -1)
		{
			if ($type == "logo")
				$valeur = '<a href="app.php?p=vuereservation&id='.$id_resa.'" style="color: red;" title="'.get_vocab("reservation_en_cours").'"><i class="icone fa-solid fa-hand" title="'.get_vocab("ressource_actuellement_empruntee").'"></i></a>'.PHP_EOL;
			else if ($type == "texte")
			{
				$beneficiaire = grr_sql_query1("SELECT beneficiaire FROM ".TABLE_PREFIX."_entry WHERE room_id = '".$id_room."' AND statut_entry='y'");
				$beneficiaire_ext = grr_sql_query1("SELECT beneficiaire_ext FROM ".TABLE_PREFIX."_entry WHERE room_id = '".$id_room."' AND statut_entry='y'");
				$valeur = '<br /><b><span class="avertissement">'.PHP_EOL;
				$valeur .= '<i class="icone fa-solid fa-hand" title="'.get_vocab("ressource_actuellement_empruntee").'"></i>'.PHP_EOL;
				$valeur .= get_vocab("ressource_actuellement_empruntee").' '.get_vocab("nom_emprunteur").get_vocab("deux_points").affiche_nom_prenom_email($beneficiaire,$beneficiaire_ext,"withmail");
				$valeur .= ' <a href="app.php?p=vuereservation&id='.$id_resa.'&amp;mode=page">'.get_vocab("entryid").$id_resa.'</a>'.PHP_EOL.'</span></b>'.PHP_EOL;
			}
			else
				$valeur = "yes";
		}
	}
	return $valeur;
}

/**
 * @param string $type
 * @param string $t
 * @return string
 */
function bbCode($t,$type)
{
	if ($type == "nobbcode")
	{
		$t = str_replace("[/]", "", $t);
		$t = str_replace("[hr]", "", $t);
		$t = str_replace("[center]", "", $t);
		$t = str_replace("[/center]", "", $t);
		$t = str_replace("[right]", "", $t);
		$t = str_replace("[/right]", "", $t);
		$t = str_replace("[justify]", "", $t);
		$t = str_replace("[/justify]", "", $t);
		$regLienSimple = "`\[url\] ?([^\[]*) ?\[/url\]`";
		$regLienEtendu = "`\[url ?=([^\[]*) ?] ?([^]]*) ?\[/url\]`";
		if (preg_match($regLienSimple, $t))
			$t = preg_replace($regLienSimple, "\\1", $t);
		else
			$t = preg_replace($regLienEtendu, "\\1", $t);
		$regMailSimple = "`\[email\] ?([^\[]*) ?\[/email\]`";
		$regMailEtendu = "`\[email ?=([^\[]*) ?] ?([^]]*) ?\[/email\]`";
		if (preg_match($regMailSimple, $t))
			$t = preg_replace($regMailSimple, "\\1", $t);
		else
			$t = preg_replace($regMailEtendu, "\\1", $t);
		$regImage = "`\[img\] ?([^\[]*) ?\[/img\]`";
		$regImageAlternatif = "`\[img ?= ?([^\[]*) ?\]`";
		if (preg_match($regImage, $t))
			$t = preg_replace($regImage, "", $t);
		else
			$t = preg_replace($regImageAlternatif, "", $t);
		$t = str_replace("[b]", "", $t);
		$t = str_replace("[/b]", "", $t);
		$t = str_replace("[i]", "", $t);
		$t = str_replace("[/i]", "", $t);
		$t = str_replace("[u]", "", $t);
		$t = str_replace("[/u]", "", $t);
		$t = str_replace("[/color]", "</span>", $t);
		$regCouleur = "`\[color= ?(([[:alpha:]]+)|(#[[:digit:][:alpha:]]{6})) ?\]`";
		$t = preg_replace($regCouleur, "", $t);
		$t = str_replace("[/size]", "</span>", $t);
		$regCouleur = "`\[size= ?([[:digit:]]+) ?\]`";
		$t = preg_replace($regCouleur, "", $t);
	}
	if ($type != "titre")
	{
		$t = str_replace("[/]", "<hr width=\"100%\" size=\"1\" />", $t);
		$t = str_replace("[hr]", "<hr width=\"100%\" size=\"1\" />", $t);
		$t = str_replace("[center]", "<div style=\"text-align: center\">", $t);
		$t = str_replace("[/center]", "</div>", $t);
		$t = str_replace("[right]", "<div style=\"text-align: right\">", $t);
		$t = str_replace("[/right]", "</div>", $t);
		$t = str_replace("[justify]", "<div style=\"text-align: justify\">", $t);
		$t = str_replace("[/justify]", "</div>", $t);
		$regLienSimple = "`\[url\] ?([^\[]*) ?\[/url\]`";
		$regLienEtendu = "`\[url ?=([^\[]*) ?] ?([^]]*) ?\[/url\]`";
		if (preg_match($regLienSimple, $t))
			$t = preg_replace($regLienSimple, "<a href=\"\\1\">\\1</a>", $t);
		else
			$t = preg_replace($regLienEtendu, "<a href=\"\\1\" target=\"_blank\" rel=\"noopener noreferer\" >\\2</a>", $t);
	}
	$regMailSimple = "`\[email\] ?([^\[]*) ?\[/email\]\`";
	$regMailEtendu = "`\[email ?=([^\[]*) ?] ?([^]]*) ?\[/email\]`";
	if (preg_match("'".$regMailSimple."'", $t))
		$t = preg_replace($regMailSimple, "<a href=\"mailto:\\1\">\\1</a>", $t);
	else
		$t = preg_replace($regMailEtendu, "<a href=\"mailto:\\1\">\\2</a>", $t);
	$regImage = "`\[img\] ?([^\[]*) ?\[/img\]`";
	$regImageAlternatif = "`\[img ?= ?([^\[]*) ?\]`";
	if (preg_match($regImage, $t))
		$t = preg_replace($regImage, "<img src=\"\\1\" alt=\"\" class=\"image\" />", $t);
	else
		$t = preg_replace($regImageAlternatif, "<img src=\"\\1\" alt=\"\" class=\"image\" />", $t);
	$t = str_replace("[b]", "<strong>", $t);
	$t = str_replace("[/b]", "</strong>", $t);
	$t = str_replace("[i]", "<em>", $t);
	$t = str_replace("[/i]", "</em>", $t);
	$t = str_replace("[u]", "<u>", $t);
	$t = str_replace("[/u]", "</u>", $t);
	$t = str_replace("[/color]", "</span>", $t);
	$regCouleur = "/\[color= ?(([[:alpha:]]+)|(#[[:digit:][:alpha:]]{6})) ?\]/";
	$t = preg_replace($regCouleur, "<span style=\"color: \\1\">", $t);
	$t = str_replace("[/size]", "</span>", $t);
	$regCouleur = "`\[size= ?([[:digit:]]+) ?\]`";
	$t = preg_replace($regCouleur, "<span style=\"font-size: \\1px\">", $t);
	return $t;
}
                       
/**                                     
 * FUNCTION: nb_connecte()
 * DESCRIPTION: Si c'est un admin qui est connecté, affiche le nombre de personnes actuellement connectées.
 */                     
function nb_connecte() {
        $lien = "";     
        if (SecuAccess::UserLevel(getUserName(), -1) >= 6) {
                $sql = "SELECT count(login) as cnt FROM ".TABLE_PREFIX."_log WHERE end > now()";
                $res = grr_sql_query($sql);
                $tmpsql = mysqli_fetch_assoc($res);
                $nb_connect = $tmpsql["cnt"];
                grr_sql_free($res);
                if ($nb_connect == 1) {
                  $lien = $nb_connect.get_vocab("one_connected");
                } else {
                  $lien = $nb_connect.get_vocab("several_connected");
                }               
        }               
        return $lien;   
}
/**
 * Fonction : resaToModerate($user) 
 * Description : si c'est un admin ou un gestionnaire de ressource qui est connecté, retourne un tableau contenant, pour chaque réservation à modérer, [id,room_id,start_time]
*/
function resaToModerate($user)
{
    $resas = array();

    if (SecuAccess::UserLevel($user,-1) > 5) // admin général
    {
        if (Settings::get("module_multisite") == 1)
        {
            $sql = "SELECT e.id,r.room_name,e.start_time,e.create_by,e.beneficiaire,a.area_name,s.sitename FROM ".TABLE_PREFIX."_entry e JOIN ".TABLE_PREFIX."_room r ON e.room_id = r.id JOIN ".TABLE_PREFIX."_area a ON r.area_id = a.id JOIN ".TABLE_PREFIX."_j_site_area jsa ON a.id = jsa.id_area JOIN ".TABLE_PREFIX."_site s ON jsa.id_site = s.id WHERE e.moderate = 1 AND e.supprimer = 0 ";
            $res = grr_sql_query($sql);
            if ($res)
            {
                $i = 0;
                while (($a = grr_sql_row($res, $i++)))
                {
                    $resas[$i] = array('id' => $a[0],'room' => $a[1],'start_time' => $a[2],'create_by' => $a[3],'beneficiaire' => $a[4],'area' => $a[5],'site' => $a[6]);
                }
                grr_sql_free($res);
            }
        }
        else
        {
            $sql = "SELECT e.id,r.room_name,e.start_time,e.create_by,e.beneficiaire,a.area_name FROM ".TABLE_PREFIX."_entry e JOIN ".TABLE_PREFIX."_room r ON e.room_id = r.id JOIN ".TABLE_PREFIX."_area a ON r.area_id = a.id WHERE e.moderate = 1 AND e.supprimer = 0 ";
            $res = grr_sql_query($sql);
            if ($res)
            {
                $i = 0;
                while (($a = grr_sql_row($res, $i++)))
                {
                    $resas[$i] = array('id' => $a[0],'room' => $a[1],'start_time' => $a[2],'create_by' => $a[3],'beneficiaire' => $a[4],'area' => $a[5]);
                }
                grr_sql_free($res);
            }
        }
        return $resas;
    }

    // Requête optimisée qui récupère directement les réservations à modérer
    // sans charger toutes les ressources en mémoire
    if (Settings::get("module_multisite") == 1)
    {
        $sql = "
            SELECT DISTINCT e.id, r.room_name, e.start_time, e.create_by, e.beneficiaire, a.area_name, s.sitename
            FROM ".TABLE_PREFIX."_entry e
            JOIN ".TABLE_PREFIX."_room r ON e.room_id = r.id
            JOIN ".TABLE_PREFIX."_area a ON r.area_id = a.id
            JOIN ".TABLE_PREFIX."_j_site_area jsa ON a.id = jsa.id_area
            JOIN ".TABLE_PREFIX."_site s ON jsa.id_site = s.id
            WHERE e.moderate = 1 AND e.supprimer = 0
            AND (
                e.room_id IN (SELECT id_room FROM ".TABLE_PREFIX."_j_user_room WHERE login = '".SecuChaine::ProtectDataSql($user)."')
                OR
                r.area_id IN (SELECT id_area FROM ".TABLE_PREFIX."_j_user_area WHERE login = '".SecuChaine::ProtectDataSql($user)."')
                OR
                r.area_id IN (
                    SELECT jsa2.id_area
                    FROM ".TABLE_PREFIX."_j_site_area jsa2
                    WHERE jsa2.id_site IN (SELECT id_site FROM ".TABLE_PREFIX."_j_useradmin_site WHERE login = '".SecuChaine::ProtectDataSql($user)."')
                )
            )
        ";
    }
    else
    {
        $sql = "
            SELECT DISTINCT e.id, r.room_name, e.start_time, e.create_by, e.beneficiaire, a.area_name
            FROM ".TABLE_PREFIX."_entry e
            JOIN ".TABLE_PREFIX."_room r ON e.room_id = r.id
            JOIN ".TABLE_PREFIX."_area a ON r.area_id = a.id
            WHERE e.moderate = 1 AND e.supprimer = 0
            AND (
                e.room_id IN (SELECT id_room FROM ".TABLE_PREFIX."_j_user_room WHERE login = '".SecuChaine::ProtectDataSql($user)."')
                OR
                r.area_id IN (SELECT id_area FROM ".TABLE_PREFIX."_j_user_area WHERE login = '".SecuChaine::ProtectDataSql($user)."')
                OR
                r.area_id IN (
                    SELECT jsa.id_area
                    FROM ".TABLE_PREFIX."_j_site_area jsa
                    WHERE jsa.id_site IN (SELECT id_site FROM ".TABLE_PREFIX."_j_useradmin_site WHERE login = '".SecuChaine::ProtectDataSql($user)."')
                )
            )
        ";
    }

    $res = grr_sql_query($sql);
    if ($res)
    {
        $i = 0;
        while (($a = grr_sql_row($res, $i++)))
        {
            if (Settings::get("module_multisite") == 1)
            {
                $resas[$i] = array('id' => $a[0],'room' => $a[1],'start_time' => $a[2],'create_by' => $a[3],'beneficiaire' => $a[4],'area' => $a[5],'site' => $a[6]);
            }
            else
            {
                $resas[$i] = array('id' => $a[0],'room' => $a[1],'start_time' => $a[2],'create_by' => $a[3],'beneficiaire' => $a[4],'area' => $a[5]);
            }
        }
        grr_sql_free($res);
    }

    return $resas;
}
/** fonction plages_libre_semaine_ressource($id_room, $month_week, $day_week, $year_week)
 * Teste s'il reste ou non des plages libres sur une journée donnée pour une ressource donnée.
 * Arguments :
 *  integer $id_room : identifiant de la ressource
 *  integer $month_week : mois
 *  integer $day_week : jour
 *  integer $year_week : année
 * Renvoie un booléen :
 *  vrai s'il reste des plages non réservées sur la journée
 *  faux dans le cas contraire
*/
function plages_libre_semaine_ressource($id_room, $month_week, $day_week, $year_week)
{
	global $morningstarts, $eveningends, $eveningends_minutes, $resolution, $enable_periods;
	$date_end = mktime($eveningends, $eveningends_minutes, 0, $month_week, $day_week, $year_week);
	$date_start = mktime($morningstarts, 0, 0, $month_week, $day_week, $year_week);
	$t = $date_start ; 
	if ($enable_periods == "y") 
		$date_end += $resolution;
	$plage_libre = false;
	while ($t < $date_end)
	{
		$t_end = $t + $resolution;
		$query = "SELECT end_time FROM ".TABLE_PREFIX."_entry WHERE room_id='".$id_room."' AND start_time <= ".$t." AND end_time >= ".$t_end." AND supprimer = 0 ";
		$end_time = grr_sql_query1($query);
		if ($end_time == -1){
			$plage_libre = true;
			break;
		} 
        else{
			$t = $end_time; // avance à la fin de la réservation trouvée
		}
	}
	return $plage_libre ;
}

/* Fonction spéciale SE3
 $grp : le nom du groupe
 $uid : l'uid de l'utilisateur
 Cette fonction retourne "oui" ou "non" selon que $uid appartient au groupe $grp, ou bien "faux" si l'interrogation du LDAP échoue
 Seuls les groupes de type "posixGroup" sont supportés (les groupes de type "groupOfNames" ne sont pas supportés).
*/
 function se3_grp_members ($grp, $uid)
 {
 	include "../personnalisation/config_ldap.inc.php";
 	$est_membre="non";
	// LDAP attributs
 	$members_attr = array (
		$ldap_group_member_attr
		// Recherche des Membres du groupe
 		);
		// Avec des GroupOfNames, ce ne serait pas ça.
 	$ds = @ldap_connect($ldap_adresse, $ldap_port);
 	if ($ds)
 	{
 		$r = @ldap_bind ($ds, $ldap_login, $ldap_pwd);
		// Bind anonyme
 		if ($r)
 		{
			// La requête est adaptée à un serveur SE3...
			//$result = @ldap_search($ds, "cn={$grp},{$ldap_group_base}",$ldap_group_filter, $members_attr);
            
            // Échapper le nom du groupe pour éviter les injections
            $grp_escaped = ldap_escape($grp, "", LDAP_ESCAPE_FILTER);
            // ldap_group_filter est déjà validé et stocké brut (non échappé)
            $result = @ldap_search($ds, "{$ldap_group_base}","(& (cn={$grp_escaped}) $ldap_group_filter )", $members_attr);
            // sur la proposition de marylenepaillassa (Forum #255)
			// Peut-être faudrait-il dans le $tab_grp_autorise mettre des chaines 'cn=$grp,ou=Groups'
 			if ($result)
 			{
 				$info = @ldap_get_entries($ds, $result);
 				if ($info["count"] == 1)
 				{
 					for ($loop = 0; $loop < $info[0][$ldap_group_member_attr]["count"]; $loop++)
 					{
 						if ($info[0][$ldap_group_member_attr][$loop] == $uid)
 							$est_membre="oui";
 					}
 				}
 				@ldap_free_result($result);
 			}
 		}
 		else
 			return false;
 		@ldap_close($ds);
 	}
 	else
 		return false;
 	return $est_membre;
 }

/*
Arguments :
$id_entry : identifiant de la réservation
$login_moderateur : identifiant du modérateur
$motivation_moderation : texte facultatif
Insère dans la table ".TABLE_PREFIX."_entry_moderate les valeurs de ".TABLE_PREFIX."_entry dont l'identifiant est $id_entry
*/
/**
 * @param string $motivation_moderation
 */
function  grr_add_ligne_moderation($id_entry, $login_moderateur, $motivation_moderation)
{
	$sql = "SELECT id FROM ".TABLE_PREFIX."_entry_moderate WHERE id=$id_entry";
	$resCompteur = grr_sql_query($sql);
	if (!$resCompteur)
		fatal_error(1, grr_sql_error());
	
	$sql = "SELECT * FROM ".TABLE_PREFIX."_entry WHERE id='".$id_entry."'";
	$res = grr_sql_query($sql);
	if (!$res)
		return false;
	$row = grr_sql_row_keyed($res, 0);
	grr_sql_free($res);

	if (grr_sql_count($resCompteur) == 1)
	{
		$req = "UPDATE ".TABLE_PREFIX."_entry_moderate SET
		start_time = '".$row['start_time']."',
		end_time  = '".$row['end_time']."',
		entry_type  = '".$row['entry_type']."',
		repeat_id  = '".$row['repeat_id']."',
		room_id = '".$row['room_id']."',
		timestamp = '".$row['timestamp']."',
		create_by = '".$row['create_by']."',
		beneficiaire = '".$row['beneficiaire']."',
		name = '".SecuChaine::ProtectDataSql($row['name'])."',
		type = '".$row['type']."',
		description = '".SecuChaine::ProtectDataSql($row['description'])."',
		statut_entry = '".$row['statut_entry']."',
		option_reservation = '".$row['option_reservation']."',
		overload_desc  = '".SecuChaine::ProtectDataSql($row['overload_desc'])."',
		moderate = '".$row['moderate']."',
		motivation_moderation = '".SecuChaine::ProtectDataSql(strip_tags($motivation_moderation))."',
		login_moderateur = '".SecuChaine::ProtectDataSql($login_moderateur)."'
		WHERE id= '".$id_entry."'";
	}
	else
	{
		$req = "INSERT INTO ".TABLE_PREFIX."_entry_moderate SET
		id = '".$row['id']."',
		start_time = '".$row['start_time']."',
		end_time  = '".$row['end_time']."',
		entry_type  = '".$row['entry_type']."',
		repeat_id  = '".$row['repeat_id']."',
		room_id = '".$row['room_id']."',
		timestamp = '".$row['timestamp']."',
		create_by = '".$row['create_by']."',
		beneficiaire = '".$row['beneficiaire']."',
		name = '".SecuChaine::ProtectDataSql($row['name'])."',
		type = '".$row['type']."',
		description = '".SecuChaine::ProtectDataSql($row['description'])."',
		statut_entry = '".$row['statut_entry']."',
		option_reservation = '".$row['option_reservation']."',
		overload_desc  = '".SecuChaine::ProtectDataSql($row['overload_desc'])."',
		moderate = '".$row['moderate']."',
		motivation_moderation = '".SecuChaine::ProtectDataSql(strip_tags($motivation_moderation))."',
		login_moderateur = '".SecuChaine::ProtectDataSql($login_moderateur)."'";
	}
	
	grr_sql_free($resCompteur);

	$res = grr_sql_query($req);
	if (!$res)
		return false;
	else
	{
		grr_sql_free($res);
		return true;
	}
}

function verif_version()
{
	global $version_bdd;
	$version_bdd_fichier = $version_bdd;
	$version_bdd_base = Settings::get("version");
	if (strpos($version_bdd_base,".") || $version_bdd_base == '' || $version_bdd_fichier > $version_bdd_base) // strpos . pour ancienne numérotation
		return true;
	else
		return false;
}

function affiche_version()
{
	return "GRR ".Settings::get("version");
}

function affiche_date($x)
{
	$j = date("d", $x);
	$m = date("m", $x);
	$a = date("Y", $x);
	$result = $j."/".$m."/".$a;
	return $result;
}

//L'heure d'été commence le dernier dimanche de mars * et se termine le dernier dimanche d'octobre
//Passage à l'heure d'hiver : -1h, le changement s'effectue à 3h
//Passage à l'heure d'été : +1h, le changement s'effectue à 2h
//Si type = hiver => La fonction retourne la date du jour de passage à l'heure d'hiver
//Si type = ete =>  La fonction retourne la date du jour de passage à l'heure d'été
function heure_ete_hiver($type, $annee, $heure)
{
	if ($type == "ete")
		$debut = mktime($heure, 0, 0, 03, 31, $annee);
	// 31-03-$annee
	else
		$debut = mktime($heure,0, 0, 10, 31, $annee);
	// 31-10-$annee
	while (date("D", $debut ) != 'Sun')
		$debut = mktime($heure, 0, 0, date("m", $debut), date("d", $debut) - 1, date("Y", $debut));
	//On retire 1 jour par rapport à la date examinée
	return $debut;
}



// Corrige les caracteres degoutants utilises par les Windozeries
function corriger_caracteres($texte)
{
	// 145,146,180 = simple quote ; 147,148 = double quote ; 150,151 = tiret long
	$texte = strtr($texte, chr(145).chr(146).chr(180).chr(147).chr(148).chr(150).chr(151), "'''".'""--');
	return $texte;
}

// Fonction supprimer accents d'une chaine
function remplacer_accents($texte)
{
	$search  = array('À', 'Á', 'Â', 'Ã', 'Ä', 'Å', 'Ç', 'È', 'É', 'Ê', 'Ë', 'Ì', 'Í', 'Î', 'Ï', 'Ò', 'Ó', 'Ô', 'Õ', 'Ö', 'Ù', 'Ú', 'Û', 'Ü', 'Ý', 'à', 'á', 'â', 'ã', 'ä', 'å', 'ç', 'è', 'é', 'ê', 'ë', 'ì', 'í', 'î', 'ï', 'ð', 'ò', 'ó', 'ô', 'õ', 'ö', 'ù', 'ú', 'û', 'ü', 'ý', 'ÿ');
	//Préférez str_replace à strtr car strtr travaille directement sur les octets, ce qui pose problème en UTF-8
	$replace = array('A', 'A', 'A', 'A', 'A', 'A', 'C', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I', 'O', 'O', 'O', 'O', 'O', 'U', 'U', 'U', 'U', 'Y', 'a', 'a', 'a', 'a', 'a', 'a', 'c', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'o', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'y', 'y');

	$chaineSansAccent = str_replace($search, $replace, $texte);
	
	return $chaineSansAccent;
}

// Traite les données envoyées par la methode GET|POST de la variable $_GET|POST["page"], renvoie "jour" si la page n'est pas définie
function verif_page()
{
  $pages = array("jour", "semaine", "mois", "semaine_all", "mois_all", "mois2_all", "annee", "annee_all");
  $page = (isset($_GET["page"]))? $_GET["page"]:((isset($_POST["page"]))? $_POST["page"]:NULL);
  if (isset($page))
  {
    if (in_array($page, $pages))
      return $page;
    else
      return "jour";
  }
  else
    return "jour";
}

function page_accueil($param = 'no')
{
	// Definition de $defaultroom
	if (isset($_SESSION['default_room']))// && ($_SESSION['default_room'] > -5))
		$defaultroom = $_SESSION['default_room'];
	else
		$defaultroom = Settings::get("default_room");
	// Definition de $defaultsite
	if (isset($_SESSION['default_site']) && ($_SESSION['default_site'] > 0))
		$defaultsite = $_SESSION['default_site'];
	else if (Settings::get("default_site") > 0)
		$defaultsite = Settings::get("default_site");
	else
		$defaultsite = get_default_site();
	// Definition de $defaultarea
	if (isset($_SESSION['default_area']) && ($_SESSION['default_area'] > 0))
		$defaultarea = $_SESSION['default_area'];
	else if (Settings::get("default_area") > 0)
		$defaultarea = Settings::get("default_area");
	else
		$defaultarea = get_default_area($defaultsite);
	// Calcul de $page_accueil
	if ($defaultarea == - 1)
		$page_accueil = 'app.php?p=jour&noarea=';
	// le paramètre noarea ne sert à rien, il est juste là pour éviter un cas particulier à traiter avec &amp;id_site= et $param
	else if ($defaultroom == - 1)
		$page_accueil = 'app.php?p=jour&area='.$defaultarea;
	else if ($defaultroom == - 2)
		$page_accueil = 'app.php?p=semaine_all&area='.$defaultarea;
	else if ($defaultroom == - 3)
		$page_accueil = 'app.php?p=mois_all&area='.$defaultarea;
	else if ($defaultroom == -4)
		$page_accueil = 'app.php?p=mois2_all&area='.$defaultarea;
	else
		$page_accueil = 'app.php?p=semaine&area='.$defaultarea.'&amp;room='.$defaultroom;
	if ((Settings::get("module_multisite") == 1) && ($defaultsite > 0))
		$page_accueil .= '&amp;id_site='.$defaultsite;
	if ($param == 'yes')
		$page_accueil .= '&amp;';
	return $page_accueil ;
}

function begin_page($title, $page = "with_session")
{
	if ($page == "with_session")
	{
		if (isset($_SESSION['default_style']))
			$sheetcss = $_SESSION['default_style'];

		else
			$sheetcss = "default"; // utilise le thème par défaut s'il n'a pas été défini... à voir YN le 11/04/2018
	}
	else
	{
		if (Settings::get("default_css"))
			$sheetcss = Settings::get("default_css");
		else
			$sheetcss = "default";
	}

	if (isset($_GET['default_language']))
	{
		$_SESSION['default_language'] = SecuChaine::Alphanumeric(SecuChaine::CleanInput($_GET['default_language']));
		if (isset($_SESSION['chemin_retour']) && ($_SESSION['chemin_retour'] != ''))
			header("Location: ".$_SESSION['chemin_retour']);
		else
			header("Location: ".traite_grr_url());
		die();
	}

	global $vocab, $charset_html, $clock_file, $gcDossierCss, $version_grr;
	header('Content-Type: text/html; charset=utf-8');

    if (!isset($_COOKIE['open']))
    {
        header('Set-Cookie: open=true; SameSite=Strict;');
    }
	$a = '<!DOCTYPE html>'.PHP_EOL;
	$a .= '<html lang="fr">'.PHP_EOL;
	$a .= '<head>'.PHP_EOL;
	$a .= '<meta charset="utf-8">'.PHP_EOL;
	$a .= '<meta http-equiv="X-UA-Compatible" content="IE=edge">'.PHP_EOL;
	$a .= '<meta name="viewport" content="width=device-width, initial-scale=1">'.PHP_EOL;
	$a .= '<meta name="Robots" content="noindex" />'.PHP_EOL;
	$a .= '<title>'.$title.'</title>'.PHP_EOL;
	$a .= '<link rel="shortcut icon" href="./favicon.ico" />'.PHP_EOL;

	$a .= '<link rel="stylesheet" type="text/css" href="bootstrap/css/bootstrap.min.css?v='.$version_grr.'" />'.PHP_EOL;
	$a .= '<link rel="stylesheet" type="text/css" href="bootstrap/css/jquery-ui.css?v='.$version_grr.'" />'.PHP_EOL;
	$a .= '<link rel="stylesheet" type="text/css" href="bootstrap/css/jquery-ui-timepicker-addon.css?v='.$version_grr.'" >'.PHP_EOL;
	$a .= '<link rel="stylesheet" type="text/css" href="themes/default/css/style.css?v='.$version_grr.'" />'.PHP_EOL; // le style par défaut
	$a .= '<link rel="stylesheet" type="text/css" href="./jslib/fontawesome-free/css/all.min.css">';
	if ((isset($_GET['pview'])) && ($_GET['pview'] == 1))
		$a .= '<link rel="stylesheet" type="text/css" href="themes/print/css/style.css?v='.$version_grr.'" />'.PHP_EOL;
		$a .= '<link rel="stylesheet" type="text/css" href="themes/'.$sheetcss.'/css/style.css?v='.$version_grr.'" />'.PHP_EOL; // le style couleurs prédéfinis
	if($sheetcss == "perso" && file_exists("personnalisation/".$gcDossierCss."/perso.css"))
		$a .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"personnalisation/".$gcDossierCss."/perso.css?".Settings::get("sp_time")."\" />".PHP_EOL; // style perso via admin
	if(file_exists("personnalisation/".$gcDossierCss."/perso-css.css"))
		$a .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"personnalisation/".$gcDossierCss."/perso-css.css?".Settings::get("sp_time")."\" />".PHP_EOL; // style perso via admin
	
	$a .= '<script type="text/javascript" src="jslib/jquery/jquery.min.js?v='.$version_grr.'"></script>'.PHP_EOL;
	$a .= '<script type="text/javascript" src="jslib/jquery-ui/jquery-ui.min.js?v='.$version_grr.'"></script>'.PHP_EOL;
	$a .= '<script type="text/javascript" src="bootstrap/js/bootstrap.min.js?v='.$version_grr.'"></script>'.PHP_EOL;
	$a .= '<script type="text/javascript" src="js/popup.js?v='.$version_grr.'" charset="utf-8"></script>'.PHP_EOL;
	$a .= '<script type="text/javascript" src="js/functions.min.js?v='.$version_grr.'" ></script>'.PHP_EOL;
	if (@file_exists('js/'.$clock_file))
		$a .= '<script type="text/javascript" src="js/'.$clock_file.'?v='.$version_grr.'"></script>'.PHP_EOL;
	if (substr(phpversion(), 0, 1) < 7)
		$a .= get_vocab('not_php');

	$a .= '</head>'.PHP_EOL;
	$a .= '<body>'.PHP_EOL;
	return $a;
}

function print_header_twig($day = '', $month = '', $year = '', $type_session = 'with_session')
{
	global $niveauDossier, $vocab, $search_str, $grrSettings, $clock_file, $desactive_VerifNomPrenomUser,$grr_script_name, $page;
	global $use_prototype, $use_admin, $desactive_bandeau_sup, $id_site, $d, $gcDossierImg, $gcDossierCss, $version_grr;

	$area = SecuChaine::GetFormVar("area","int",0);
	$room = SecuChaine::GetFormVar("room","int",0);
	$id_site = SecuChaine::GetFormVar("id_site","int",0);
	
	if( isset($_SESSION['changepwd']) && $_SESSION['changepwd'] == 1 && $page != 'changemdp'){
		header("Location: ./compte/compte.php?pc=changemdp");
	}

	if ($niveauDossier == 1)
        $racine = "./";
	else
		$racine = "../";


	if (!($desactive_VerifNomPrenomUser))
		$desactive_VerifNomPrenomUser = 'n';
	// On vérifie que les noms et prénoms ne sont pas vides
	VerifNomPrenomUser($type_session);

	if(file_exists($racine."personnalisation/".$gcDossierCss."/perso-css.css"))
		$d['sheetcsspersocss'] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"".$racine."personnalisation/".$gcDossierCss."/perso-css.css?".Settings::get("sp_time")."\" />";


	if ($type_session == "with_session")
	{
		if (isset($_SESSION['default_style']))
		{
			if($_SESSION['default_style'] == "perso" && file_exists($racine."personnalisation/".$gcDossierCss."/perso.css"))
				$d['sheetcssperso'] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"".$racine."personnalisation/".$gcDossierCss."/perso.css?".Settings::get("sp_time")."\" />";

			$d['sheetcss'] = 'themes/'.$_SESSION['default_style'].'/css/style.css';
		}
		else
			$d['sheetcss'] = 'themes/default/css/style.css'; // utilise le thème par défaut s'il n'a pas été défini... à voir YN le 11/04/2018
	}
	else
	{
		if (Settings::get("default_css"))
		{
			if (Settings::get("default_css") == "perso" && file_exists($racine."personnalisation/".$gcDossierCss."/perso.css"))
				$d['sheetcssperso'] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"".$racine."personnalisation/".$gcDossierCss."/perso.css?".Settings::get("sp_time")."\" />";

			$d['sheetcss'] = 'themes/'.Settings::get("default_css").'/css/style.css?'.Settings::get("sp_time");
		}
		else
			$d['sheetcss'] = 'themes/default/css/style.css';
	}

	if (isset($_GET['default_language']))
	{
		$_SESSION['default_language'] = SecuChaine::Alphanumeric($_GET['default_language']);
		if (isset($_SESSION['chemin_retour']) && ($_SESSION['chemin_retour'] != ''))
			header("Location: ".$_SESSION['chemin_retour']);
		else
			header("Location: ".traite_grr_url());
		die();
	}

	if (!isset($_COOKIE['open']))
	{
		setcookie("open", "true", time()+3600, "", "", true, false);
	}

	$resulHook = Hook::Appel("hookHeader2");
	$d['hookHeader2'] = $resulHook['hookHeader2'];

	// Si nous ne sommes pas dans un format imprimable
	if ((!isset($_GET['pview'])) || ($_GET['pview'] != 1))
	{

		// If we dont know the right date then make it up
		if (!isset($day) || !isset($month) || !isset($year) || ($day == '') || ($month == '') || ($year == ''))
		{
			$date_now = time();

			if ($date_now < Settings::get("begin_bookings"))
				$date_ = Settings::get("begin_bookings");
			else if ($date_now > Settings::get("end_bookings"))
				$date_ = Settings::get("end_bookings");
			else
				$date_ = $date_now;
			$day   = date("d",$date_);
			$month = date("m",$date_);
			$year  = date("Y",$date_);
		}

		//Parametre url fixe compte / admin
		$paramUrl = 'p='.$page.'&day='.$day.'&year='.$year.'&month='.$month;
    if($room)
      $paramUrl .= "&room=".$room;
    if($area)
      $paramUrl .= "&area=".$area;
    if($id_site)
      $paramUrl .= "&id_site=".$id_site;
		$paramUrlAccueil = 'day='.$day.'&amp;year='.$year.'&amp;month='.$month;
		$d['paramUrl'] = $paramUrl;
		
		if (!(isset($search_str)))
			$search_str = get_vocab("search_for");
		if (empty($search_str))
			$search_str = "";
		if (!(isset($desactive_bandeau_sup) && ($desactive_bandeau_sup == 1) && ($type_session != 'with_session')))
		{

			// HOOK
			$resulHook = Hook::Appel("hookHeader1");
			$d['hookHeader1'] = $resulHook['hookHeader1'];

			//Accueil
			$d['pageAccueil'] = $racine.page_accueil('yes').$paramUrlAccueil;

			//Logo
			$nom_picture = $racine."personnalisation/".$gcDossierImg."/logos/".Settings::get("logo");
			if ((Settings::get("logo") != '') && (@file_exists($nom_picture)))
				$d['logo'] = $nom_picture;
			
			//Mail réservation
			if ( ( Settings::get("mail_etat_destinataire") == 1 && $type_session == "no_session" ) || ( ( Settings::get("mail_etat_destinataire") == 1 || Settings::get("mail_etat_destinataire") == 2) && $type_session == "with_session" && (SecuAccess::UserLevel(getUserName(), -1, 'area')) == 1  ) )
			{
				$d['lienFormaulaireResa'] = 1;
			}
			// Administration
			if ($type_session == "with_session")
			{

                $user_name = getUserName();
				$resaAModerer = resaToModerate($user_name);
                $nbResaAModerer = count($resaAModerer);
                $d['mess_resa'] = '';
                if ($nbResaAModerer > 1){$d['mess_resa'] = $nbResaAModerer.get_vocab('resasToModerate');}
                if ($nbResaAModerer == 1){$d['mess_resa'] = $nbResaAModerer.get_vocab('resaToModerate');}
				if ((SecuAccess::UserLevel($user_name, -1, 'area') >= 4) || (SecuAccess::UserLevel($user_name, -1, 'user') == 1) || ($d['mess_resa'] != ''))
				{
					if ((SecuAccess::UserLevel($user_name, -1, 'area') >= 4) || (SecuAccess::UserLevel($user_name, -1, 'user') == 1))
                       $d['lienAdmin'] = 'admin/admin.php?p=admin_accueil&'.$paramUrlAccueil;
					if (SecuAccess::UserLevel(getUserName(), -1, 'area') >= 6)
						$d['nbConnecte'] = nb_connecte();
				}
			}

			// Heure selon la langue
			if (@file_exists($racine.'js/'.$clock_file))
				$d['jsHeure'] = $clock_file;

			$_SESSION['chemin_retour'] = '';
			if (isset($_SERVER['QUERY_STRING']) && ($_SERVER['QUERY_STRING'] != ''))
			{
				$parametres_url = htmlspecialchars($_SERVER['QUERY_STRING'])."&amp;";
				$_SESSION['chemin_retour'] = traite_grr_url($grr_script_name)."?". $_SERVER['QUERY_STRING'];
				$d['urlLangue'] = traite_grr_url($grr_script_name);
			}
			if ($type_session == 'no_session')
			{
				$resulHook = Hook::Appel("hookLienConnexion2");
				if ((Settings::get('sso_statut') == 'cas_visiteur') || (Settings::get('sso_statut') == 'cas_utilisateur'))
				{
					$d['lienConnexion'] =  '<br /> <a href="index.php?force_authentification=y">'.get_vocab("authentification").'</a>';
					$d['lienConnexion'] .=  '<br /> <small><i><a href="app.php?p=login">'.get_vocab("connect_local").'</a></i></small>';
				} elseif($resulHook['hookLienConnexion2'] != "")
				{
					$d['lienConnexion'] = $resulHook['hookLienConnexion2'];
				}
				else {
					$d['lienConnexion'] = '<br /> <a href="app.php?p=login">'.get_vocab("connect").'</a>';
				}
			}
			else
			{
				$resulHook = Hook::Appel("hookLienConnexion3");
				if( strlen(htmlspecialchars($_SESSION['prenom']).' '.htmlspecialchars($_SESSION['nom'])) > 40 )
					$d['nomUtilisateur'] =  htmlspecialchars($_SESSION['nom']);
				else
					$d['nomUtilisateur'] =  htmlspecialchars($_SESSION['prenom']).' '.htmlspecialchars($_SESSION['nom']);
				if($resulHook['hookLienConnexion3'] != "")
					$d['lienConnexion'] = $resulHook['hookLienConnexion3'];
				// Déconnexion
				$disconnect_link = false;
				if (!((Settings::get("cacher_lien_deconnecter") == 1) && (isset($_SESSION['est_authentifie_sso']))))
				{
					$disconnect_link = true;
					if (Settings::get("authentification_obli") == 1)
						$d['lienDeconnexion'] = '<br /> <a href="'.$racine.'app.php?p=deconnexion&auto=0" >'.get_vocab('disconnect').'</a>'.PHP_EOL;
					else
						$d['lienDeconnexion'] = '<br /> <a href="'.$racine.'app.php?p=deconnexion&auto=0&amp;redirect_page_accueil=yes" >'.get_vocab('disconnect').'</a>'.PHP_EOL;
				}
				if ((Settings::get("Url_portail_sso") != '') && (isset($_SESSION['est_authentifie_sso'])))
				{
					$d['lienDeconnexion'] = '<br><a href="'.Settings::get("Url_portail_sso").'">'.get_vocab("Portail_accueil").'</a>'.PHP_EOL;
				}
				if ((Settings::get('sso_statut') == 'lasso_visiteur') || (Settings::get('sso_statut') == 'lasso_utilisateur'))
				{
					if ($_SESSION['lasso_nameid'] == NULL)
						$d['lienDeconnexion'] = '<br><a href="lasso/federate.php">'.get_vocab('lasso_federate_this_account').'</a>'.PHP_EOL;
					else
						$d['lienDeconnexion'] = '<br><a href="lasso/defederate.php">'.get_vocab('lasso_defederate_this_account').'</a>'.PHP_EOL;
				}
			}

		}
	}
}

/**
 * @param string $type
 */
function VerifNomPrenomUser($type)
{
	global $page;

	// ne pas prendre en compte la page my_account.php
	global $desactive_VerifNomPrenomUser, $page;
	if (($type == "with_session") && ($desactive_VerifNomPrenomUser != 'y') && (SecuAccess::IsAllowedToModifyProfil()))
	{
		$test = grr_sql_query1("SELECT login FROM ".TABLE_PREFIX."_utilisateurs WHERE (login = '".getUserName()."' AND (nom='' or prenom = ''))");
		if (($test != -1) && ($page != 'moncompte'))
		{
			header("Location: ./compte/compte.php");
			die();
		}
	}
}

// Transforme $dur en une durée exprimée en années, semaines, jours, heures, minutes et secondes
// OU en durée numérique exprimée dans l'une des unités de façon fixe, pour l'édition des
// réservations par durée.
// $dur : durée sous forme d'une chaine de caractère quand $edition=false, sinon, durée en valeur numérique.
// $units : variable conservée uniquement pour compatibilité avec la fonction toTimeString originale
//          si $edition=false, sinon, contient l'unité utilisée pour $dur
// $edition : Valeur par défaut : false. Indique si le retour est pour affichage ou pour modifier la durée.
// Version écrite par David M - E-Concept Applications
function toTimeString(&$dur, &$units, $edition = false)
{
	global $vocab;
	if ($edition)
	{
		if ($dur >= 60)
		{
			$dur = $dur / 60;
			if ($dur >= 60)
			{
				$dur /= 60;
				if (($dur >= 24) && (fmod($dur, 24) == 0))
				{
					$dur /= 24;
					if (($dur >= 7) && (fmod($dur, 7) == 0))
					{
						$dur /= 7;
						if (($dur >= 52) && (fmod($dur, 52) == 0))
						{
							$dur  /= 52;
							$units = get_vocab("years");
						}
						else
							$units = get_vocab("weeks");
					}
					else
						$units = get_vocab("days");
				}
				else
					$units = get_vocab("hours");
			}
			else
				$units = get_vocab("minutes");
		}
		else
			$units = get_vocab("seconds");
	}
	else
	{
		$duree_formatee = "";
		$not_first_unit = false;
		// On définit la durée en secondes de chaque type d'unité
		$annee   = 60 * 60 * 24 * 365;
		$semaine = 60 * 60 * 24 * 7;
		$jour    = 60 * 60 * 24;
		$heure   = 60 * 60;
		$minute  = 60;
		// On calcule le nombre d'années.
		$nb_annees = floor($dur / $annee);
		if ($nb_annees > 0)
		{
			if ($not_first_unit)
				$duree_formatee .= ", ";
			else
				$not_first_unit = true;
			$duree_formatee .= $nb_annees . " " . get_vocab("years");
			// On soustrait le nombre d'années déjà déterminées à la durée initiale.
			$dur = $dur - $nb_annees * $annee;
		}
		// On calcule le nombre de semaines.
		$nb_semaines = floor($dur / $semaine);
		if ($nb_semaines > 0)
		{
			if ($not_first_unit)
				$duree_formatee .= ", ";
			else
				$not_first_unit = true;
			$duree_formatee .= $nb_semaines . " " . get_vocab("weeks");
			// On soustrait le nombre de semaines déjà déterminées à la durée initiale.
			$dur = $dur - $nb_semaines * $semaine;
		}
		// On calcule le nombre de jours.
		$nb_jours = floor($dur / $jour);
		if ($nb_jours > 0)
		{
			if ($not_first_unit)
				$duree_formatee .= ", ";
			else
				$not_first_unit = true;
			$duree_formatee .= $nb_jours . " " . get_vocab("days");
			// On soustrait le nombre de jours déjà déterminés à la durée initiale.
			$dur = $dur - $nb_jours * $jour;
		}
		// On calcule le nombre d'heures.
		$nb_heures = floor($dur / $heure);
		if ($nb_heures > 0)
		{
			if ($not_first_unit)
				$duree_formatee .= ", ";
			else
				$not_first_unit = true;
			$duree_formatee .= $nb_heures . " " . get_vocab("hours");
			// On soustrait le nombre d'heures déjà déterminées à la durée initiale.
			$dur = $dur - $nb_heures * $heure;
		}
		// On calcule le nombre de minutes.
		$nb_minutes = floor($dur / $minute);
		if ($nb_minutes > 0)
		{
			if ($not_first_unit)
				$duree_formatee .= ", ";
			else
				$not_first_unit = true;
			$duree_formatee .= $nb_minutes . " " . get_vocab("minutes");
						// On soustrait le nombre de minutes déjà déterminées à la durée initiale.
			$dur = $dur - $nb_minutes * $minute;
		}
				// On calcule le nombre de secondes.
		if ($dur > 0)
		{
			if ($not_first_unit)
				$duree_formatee .= ", ";
			$duree_formatee .= $dur . " " . get_vocab("seconds");
		}
		// On sépare les différentes unités de la chaine.
		$tmp = explode(", ", $duree_formatee);
		// Si on a plus d'une unitée...
		if (count($tmp) > 1)
		{
			// ... on dépile le tableau par la fin...
			$tmp_fin = array_pop($tmp);
			// ... on reconstiture la chaine avec les premiers éléments...
			$duree_formatee = implode(", ", $tmp);
			// ... et on ajoute le dernier élément
			$duree_formatee .= " et " . $tmp_fin;
		}
		// Sinon, on ne change rien.
		$dur = $duree_formatee;
		$units = "";
	}
}
// Transforme $dur en un nombre entier
// $dur : durée
// $units : unité
/**
 * @param integer $start_period
 */
function toPeriodString($start_period, &$dur, &$units)
{
	// la durée est donnée en secondes
	global $enable_periods, $periods_name, $vocab;
	$max_periods = count($periods_name);
	$dur /= 60; // on transforme la durée en minutes
	// Chaque minute correspond à un créneau
	if ( $dur >= $max_periods || $start_period == 0 )
	{
		if ( $start_period == 0 && $dur == $max_periods )
		{
			$units = get_vocab("periods");
			$dur = $max_periods;
			return;
		}
		$dur /= 60;
		if (($dur >= 24) && is_int($dur))
		{
			$dur /= 24;
			$units = get_vocab("days");
			return;
		}
		else
		{
			$dur *= 60;
			$dur = ($dur % $max_periods) + floor( $dur/(24*60) ) * $max_periods;
			$units = get_vocab("periods");
			return;
		}
	}
	else
		$units = get_vocab("periods");
}

/**
* @param string $prefix
* @param string $option
*/
function genDateSelectorForm($prefix, $day, $month, $year, $option)
{
	global $nb_year_calendar;
	$selector_data = "";
	if (!isset($nb_year_calendar))
		$nb_year_calendar = 5;
	if (($day == 0) && ( $day != ""))
		$day = date("d");
	if ($month == 0)
		$month = date("m");
	if ($year == 0)
		$year = date("Y");
	if ($day != "")
	{
		$selector_data .= "<select class='btn btn-default btn-sm' name=\"{$prefix}day\" id=\"{$prefix}day\">\n";
		for ($i = 1; $i <= 31; $i++)
		{
			if ($i < 10)
				$selector_data .= "<option" . ($i == $day ? " selected=\"selected\"" : "") . ">0$i</option>\n";
			else
				$selector_data .= "<option" . ($i == $day ? " selected=\"selected\"" : "") . ">$i</option>\n";
		}
		$selector_data .= "</select>";
	}
	if ($month != "")
	{
		$selector_data .= "<select class='btn btn-default btn-sm' name=\"{$prefix}month\" id=\"{$prefix}month\">\n";
		for ($i = 1; $i <= 12; $i++)
		{
			$m = utf8_strftime("%b", mktime(0, 0, 0, $i, 1, $year));
			if ($i < 10)
			{
				$selector_data .=  "<option value=\"0$i\"" . ($i == $month ? " selected=\"selected\"" : "") . ">$m</option>\n";
			}
			else
			{
				$selector_data .=  "<option value=\"$i\"" . ($i == $month ? " selected=\"selected\"" : "") . ">$m</option>\n";
			}
		}
		$selector_data .=  "</select>";
	}
	$selector_data .=  "<select class='btn btn-default btn-sm' name=\"{$prefix}year\" id=\"{$prefix}year\">\n";
	$min = date('Y', Settings::get("begin_bookings"));
	if ($option == "more_years")
		$min = date("Y") - $nb_year_calendar;
	$max = date('Y', Settings::get("end_bookings"));
	if ($option == "more_years")
		$max = date("Y") + $nb_year_calendar;
	for($i = $min; $i <= $max; $i++)
		$selector_data .= "<option value=\"$i\" " . ($i == $year ? " selected=\"selected\"" : "") . ">$i</option>\n";
	$selector_data .= "</select> \n\n";
	return $selector_data;
}

/**
 * @param string $prefix
 * @param string $option
 */
function genDateSelector($prefix, $day, $month, $year, $option)
{
	echo genDateSelectorForm($prefix, $day, $month, $year, $option);
}

/**
 * @param integer $need_header
 */
function fatal_error($need_header, $message, $show_form_data = true)
{
	global $vocab, $twig, $page, $AllSettings, $d, $trad;


	if ($need_header)
		print_header_twig();
	error_log("GRR: ".$message);
	if ($show_form_data)
	{
		if (!empty($_GET))
		{
			error_log("GRR GET: ".print_r($_GET, true));
		}
		if (!empty($_POST))
		{
			error_log("GRR POST: ".print_r($_POST, true));
		}
	}
	if (!empty($_SESSION))
	{
		error_log("GRR SESSION: ".print_r($_SESSION, true));
	}
	

	if ($need_header)
	{
		$d['messageErreur'] = $message;
		echo $twig->render('erreur.twig', array('trad' => $trad, 'd' => $d, 'settings' => $AllSettings));
	}
	else
		echo '<p>',$message,'</p>'.PHP_EOL;

	exit;
}

/**
 * Compare une ip à d'autres iP - CIDR
 */
function compare_ip_adr($ip1, $ips2)
{

	$ipCorrespondante = false;
    $ip2 = explode(';', $ips2);
	
    $resultIP = in_array($ip1,$ip2,true);
	if($resultIP == false){ // cherche si l'adresse est dans une plage CIDR p.ex. 192.168.1.0/24 --> 192.168.1.0 à 192.168.1.255
        foreach ($ip2 as $ip){
            $slash = strpos($ip,'/');
            if ($slash !== false){
                list($net,$mask) = preg_split("~/~",$ip);
                $lnet=ip2long($net);
                $lip=ip2long($ip1);
                $binnet=str_pad( decbin($lnet),32,"0",STR_PAD_LEFT );
				$firstpart=substr($binnet,0,(int)$mask);
                $binip=str_pad( decbin($lip),32,"0",STR_PAD_LEFT );
				$firstip=substr($binip,0,(int)$mask);
                $resultIP = (strcmp($firstpart,$firstip)==0);
            }
            if ($resultIP){
				$ipCorrespondante = true;
				break;
			}
        }
	} else {
		$ipCorrespondante = true;
	}

	return $ipCorrespondante;

}

//Retourne le domaine par défaut; Utilisé si aucun domaine n'a été défini.
function get_default_area($id_site = -1)
{
	global $gOptionIp;
	if (Settings::get("module_multisite") == 1)
		$use_multisite = true;
	else
		$use_multisite = false;
	if ($gOptionIp==1) {
		$sql = "SELECT id FROM ".TABLE_PREFIX."_area WHERE ip_adr='".$_SERVER['REMOTE_ADDR']."' ORDER BY access, order_display, area_name LIMIT 1";
          	$res = grr_sql_query($sql);
          	if ($row = mysqli_fetch_assoc($res)) {
            		return $row["id"];
          	}
	}
	if (SecuAccess::UserLevel(getUserName(),-1) >= 6)
	{
		if (($id_site != -1) and ($use_multisite))
			$res = grr_sql_query("SELECT a.id
				FROM ".TABLE_PREFIX."_area a, ".TABLE_PREFIX."_j_site_area j
				WHERE a.id=j.id_area and j.id_site=$id_site
				ORDER BY a.order_display, a.area_name");
		else
			$res = grr_sql_query("SELECT id FROM ".TABLE_PREFIX."_area ORDER BY access, order_display, area_name");
	}
	else
	{
		if (($id_site != -1) and ($use_multisite))
			$res = grr_sql_query("SELECT a.id
				FROM ".TABLE_PREFIX."_area a, ".TABLE_PREFIX."_j_site_area j
				WHERE a.id=j.id_area and j.id_site=$id_site and a.access!='r'
				ORDER BY a.order_display, a.area_name");
		else
			$res = grr_sql_query("SELECT id FROM ".TABLE_PREFIX."_area WHERE access!='r' ORDER BY access, order_display, area_name");
	}
	if ($res && grr_sql_count($res)>0 )
	{
		$row = grr_sql_row($res, 0);
		grr_sql_free($res);
		return $row[0];
	}
	else
	{
		if (($id_site != -1) and ($use_multisite))
			$res = grr_sql_query("SELECT a.id
				FROM ".TABLE_PREFIX."_area a, ".TABLE_PREFIX."_j_site_area j, ".TABLE_PREFIX."_j_user_area u
				WHERE a.id=j.id_area and j.id_site=$id_site and a.id=u.id_area and u.login='" . getUserName() . "'
				ORDER BY a.order_display, a.area_name");
		else
			$res = grr_sql_query("select id from ".TABLE_PREFIX."_area, ".TABLE_PREFIX."_j_user_area WHERE
				".TABLE_PREFIX."_area.id=".TABLE_PREFIX."_j_user_area.id_area and
				login='" . getUserName() . "'
				ORDER BY order_display, area_name");
		if ($res && grr_sql_count($res)>0 )
		{
			$row = grr_sql_row($res, 0);
			grr_sql_free($res);
			return $row[0];
		}
		else
			return -1;
	}
}
/* fonction get_default_site
    renvoie id_site du site par défaut de l'utilisateur, sinon celui de la table setting, sinon celui de plus petit id dans la table site
*/

function get_default_site()
{
    $user = getUserName();
    if ($user != ''){
        $id_site = grr_sql_query1("SELECT default_site FROM ".TABLE_PREFIX."_utilisateurs WHERE login ='".$user."'");
        if ($id_site > 0){return $id_site;}
    }
    // ici l'utilisateur n'est pas reconnu ou il n'a pas de site par défaut : on passe aux informations de la table settings
    $id_site = grr_sql_query1("SELECT VALUE FROM ".TABLE_PREFIX."_setting WHERE NAME ='default_site' ");
    $test = grr_sql_query1("SELECT id FROM ".TABLE_PREFIX."_site WHERE id = ".$id_site." AND access != 'r' ");
    if ($test >0){return $id_site;}
    else { // il n'y a pas de site par défaut dans la table setting, on prend le premier site
        $id_site = grr_sql_query1("SELECT min(id) FROM ".TABLE_PREFIX."_site where access != 'r' ");
        return($id_site);
    }
}
// fonction get_default_room
/*  renvoie id_room de la ressource par défaut de l'utilisateur, sinon celle de la table setting, sinon celle de plus petit indice dans la table room 
*/
function get_default_room(){
    $user = getUserName();
    if ($user != ''){
        $id_room = grr_sql_query1("SELECT default_room FROM ".TABLE_PREFIX."_utilisateurs WHERE login =".$user);
        if ($id_room > 0){return $id_room;}
    }
    // ici l'utilisateur n'est pas reconnu ou il n'a pas de ressource par défaut : on passe aux informations de la table settings
    $id_room = grr_sql_query1("SELECT VALUE FROM ".TABLE_PREFIX."_setting WHERE NAME ='default_room' ");
    $test = grr_sql_query1("SELECT id FROM ".TABLE_PREFIX."_room WHERE id = ".$id_room);
    if ($test >0){return $id;}
    else { // il n'y a pas de ressource par défaut dans la table setting, on prend la première ressource
        $id_room = grr_sql_query1("SELECT min(id) FROM ".TABLE_PREFIX."_room ");
        return($id_room);
    }
}
# Get the local day name based on language. Note 2000-01-02 is a Sunday.
/**
 * @param integer $daynumber
 */
function day_name($daynumber)
{
	return utf8_strftime("%A", mktime(0, 0, 0, 1, 2 + $daynumber, 2000));
}

function affiche_heure_creneau($t,$resolution)
{
	global $twentyfourhour_format;
	if ($twentyfourhour_format)
		$hour_min_format = "H:i";
	else
		$hour_min_format = "h:ia";
	return date($hour_min_format,$t) ." - ".date($hour_min_format, $t + $resolution);
}

function hour_min_format()
{
	global $twentyfourhour_format;
	if ($twentyfourhour_format)
	{
		return "H:i";
	}
	else
	{
		return "h:ia";
	}
}

/*
Fonction utilisée dans le cas où les créneaux de réservation sont basés sur des intitulés pré-définis :
Formatage de la date de début ou de fin de réservation.
Dans le cas du début de réservation on a $mod_time=0
Dans le cas de la fin de réservation on a $mod_time=-1
*/
function period_date_string($t, $mod_time = 0)
{
	global $periods_name, $dformat;
	$time = getdate($t);
	$p_num = $time["minutes"] + $mod_time;
	if ( $p_num < 0 )
	{
		// fin de réservation : cas $time["minutes"] = 0. il faut afficher le dernier créneau de la journée précédente
		$t = $t - 60 * 60 * 24;
		$p_num = count($periods_name) - $p_num;
	}
	if ( $p_num >= count($periods_name) - 1 )
		$p_num = count($periods_name) - 1;
	return array($p_num, $periods_name[$p_num] . utf8_strftime(", ".$dformat, $t));
}
// la même, avec un résultat différent, pour les rapports csv
function period_date_string_rapport($t, $mod_time = 0)
{
	global $periods_name, $dformat;
	$time = getdate($t);
	$p_num = $time["minutes"] + $mod_time;
	if ( $p_num < 0 )
	{
		// fin de réservation : cas $time["minutes"] = 0. il faut afficher le dernier créneau de la journée précédente
		$t = $t - 60 * 60 * 24;
		$p_num = count($periods_name) - $p_num;
	}
	if ( $p_num >= count($periods_name) - 1 )
		$p_num = count($periods_name) - 1;
	return array($periods_name[$p_num],utf8_strftime($dformat, $t));
}
/*
Fonction utilisée dans le cas où les créneaux de réservation sont basés sur des intitulés pré-définis :
Formatage des périodes de début ou de fin de réservation.
Dans le cas du début de réservation on a $mod_time=0
Dans le cas de la fin de réservation on a $mod_time=-1
*/
function period_time_string($t, $mod_time = 0)
{
	global $periods_name;
	$time = getdate($t);
	$p_num = $time["minutes"] + $mod_time;
	if ( $p_num < 0 )
		$p_num = 0;
	if ( $p_num >= count($periods_name) - 1 )
		$p_num = count($periods_name) - 1;
	return $periods_name[$p_num];
}
/* donne, pour un format français, un résultat de la forme lundi 30 sept. - 19:17
*/
function time_date_string($t, $dformat)
{
	global $twentyfourhour_format;
	// This bit's necessary, because it seems %p in strftime format
	// strings doesn't work
	if ($twentyfourhour_format)
		return utf8_strftime($dformat." - %H:%M",$t);
	else
		return utf8_strftime("%I:%M".date("a", $t)." - ".$dformat,$t);
}

function time_date_string_jma($t,$dformat)
{
	return utf8_strftime($dformat, $t);
}

//Output a start table cell tag <td> with color class and fallback color.
function tdcell($colclass, $width = '')
{
	if ($width != "")
		$temp = ' style="width:'.$width.'%;" ';
	else
		$temp = "";
	global $tab_couleur;
	static $ecolors;
	if (($colclass >= "A") && ($colclass <= "ZZ"))
	{
		/*$couleurhexa = grr_sql_query1("SELECT couleurhexa FROM ".TABLE_PREFIX."_type_area WHERE type_letter='".$colclass."'");
        $couleur_texte = grr_sql_query1("SELECT couleur_texte FROM".TABLE_PREFIX."_type_area WHERE type_letter='".$colclass."'");
		echo '<td style="background-color:'.$couleurhexa.'; color:'.$couleur_texte.';" '.$temp.'>'.PHP_EOL;*/
        echo '<td class="type'.$colclass.'"'.$temp.'>';
	}
	else
		echo '<td class="'.$colclass.'" '.$temp.'>'.PHP_EOL;
}

//Output a start table cell tag <td> with color class and fallback color. : Twig
function tdcellT($colclass, $width = '')
{
	if ($width != "")
		$temp = ' style="width:'.$width.'%;" ';
	else
		$temp = "";

	if (($colclass >= "A") && ($colclass <= "ZZ"))
	{
		/*$couleurhexa = grr_sql_query1("SELECT couleurhexa FROM ".TABLE_PREFIX."_type_area WHERE type_letter='".$colclass."'");
        $couleur_texte = grr_sql_query1("SELECT couleur_texte FROM".TABLE_PREFIX."_type_area WHERE type_letter='".$colclass."'");
		echo '<td style="background-color:'.$couleurhexa.'; color:'.$couleur_texte.';" '.$temp.'>'.PHP_EOL;*/
        return '<td class="type'.$colclass.'"'.$temp.'>';
	}
	else
		return '<td class="'.$colclass.'" '.$temp.'>'.PHP_EOL;
}

//Display the entry-type color key. This has up to 2 rows, up to 10 columns.
function show_colour_keyTwig($area_id)
{
    $sql = "SELECT DISTINCT t.id, t.type_name, t.type_letter, t.order_display FROM ".TABLE_PREFIX."_type_area t
    LEFT JOIN ".TABLE_PREFIX."_j_type_area j on j.id_type=t.id
    WHERE (j.id_area IS NULL or j.id_area != '".$area_id."')
    AND NOT EXISTS (SELECT y.id_type FROM ".TABLE_PREFIX."_j_type_area y WHERE y.id_type = j.id_type and id_area='".$area_id."')
    ORDER BY t.order_display";
    $res = grr_sql_query($sql);

	$affichage = "";

    if ($res)
    {
        $nct = -1;
        for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
        {
            $type_name = $row[1];
            $type_letter = $row[2];
            if ($nct == -1)
				$affichage .= '<tr>';
            if (++$nct == 2)
            {
                $nct = 0;
                $affichage .= '</tr><tr>';
            }
            $affichage .= tdcellT($type_letter);
            $affichage .= $type_name.'</td>';
        }
        if ($i % 2 == 1)
			$affichage .= '<td></td>';
		$affichage .= '</tr>';
    }

	return $affichage;
}

function synchro_groupe($idGroupe, $action)
{
	if($action == 0 || $action == 1)
	{
		// Sites restreints
		$sql1 = "DELETE FROM ".TABLE_PREFIX."_j_user_site WHERE idgroupes= $idGroupe";
		if (grr_sql_command($sql1) < 0)
			fatal_error(0, get_vocab('message_records_error') . grr_sql_error());

		$sql2 = "SELECT id_site FROM ".TABLE_PREFIX."_j_group_site WHERE idgroupes = $idGroupe";

		$res2 = grr_sql_query($sql2);
		if ($res2)
		{
			for ($i = 0; ($row2 = grr_sql_row($res2, $i)); $i++)
			{
				$sql3 = "SELECT DISTINCT ug.login FROM ".TABLE_PREFIX."_j_group_site jg JOIN ".TABLE_PREFIX."_utilisateurs_groupes ug ON jg.idgroupes = ug.idgroupes WHERE jg.id_site = $row2[0] AND jg.idgroupes = $idGroupe";
				$res3 = grr_sql_query($sql3);

				for ($i = 0; ($row3 = grr_sql_row($res3, $i)); $i++)
				{
					$sql = "INSERT INTO ".TABLE_PREFIX."_j_user_site (login, id_site, idgroupes) values ('$row3[0]',$row2[0],$idGroupe)";
					if (grr_sql_command($sql) < 0)
						fatal_error(1, "<p>" . grr_sql_error());
				}
			}
		}

		// Domaines restreints
		$sql1 = "DELETE FROM ".TABLE_PREFIX."_j_user_area WHERE idgroupes= $idGroupe";
		if (grr_sql_command($sql1) < 0)
			fatal_error(0, get_vocab('message_records_error') . grr_sql_error());

		$sql2 = "SELECT id_area FROM ".TABLE_PREFIX."_j_group_area WHERE idgroupes = $idGroupe";

		$res2 = grr_sql_query($sql2);
		if ($res2)
		{
			for ($i = 0; ($row2 = grr_sql_row($res2, $i)); $i++)
			{
				$sql3 = "SELECT DISTINCT ug.login FROM ".TABLE_PREFIX."_j_group_area jg JOIN ".TABLE_PREFIX."_utilisateurs_groupes ug ON jg.idgroupes = ug.idgroupes WHERE jg.id_area = $row2[0] AND jg.idgroupes = $idGroupe";
				$res3 = grr_sql_query($sql3);

				for ($i = 0; ($row3 = grr_sql_row($res3, $i)); $i++)
				{
					$sql = "INSERT INTO ".TABLE_PREFIX."_j_user_area (login, id_area, idgroupes) values ('$row3[0]',$row2[0],$idGroupe)";
					if (grr_sql_command($sql) < 0)
						fatal_error(1, "<p>" . grr_sql_error());
				}
			}
		}

		// Ressources restreintes
		$sql1 = "DELETE FROM ".TABLE_PREFIX."_j_userbook_room WHERE idgroupes= $idGroupe";
		if (grr_sql_command($sql1) < 0)
			fatal_error(0, get_vocab('message_records_error') . grr_sql_error());

		$sql2 = "SELECT id_room FROM ".TABLE_PREFIX."_j_group_room WHERE idgroupes = $idGroupe";

		$res2 = grr_sql_query($sql2);
		if ($res2)
		{
			for ($i = 0; ($row2 = grr_sql_row($res2, $i)); $i++)
			{
				$sql3 = "SELECT DISTINCT ug.login FROM ".TABLE_PREFIX."_j_group_room jg JOIN ".TABLE_PREFIX."_utilisateurs_groupes ug ON jg.idgroupes = ug.idgroupes WHERE jg.id_room = $row2[0] AND jg.idgroupes = $idGroupe";
				$res3 = grr_sql_query($sql3);

				for ($i = 0; ($row3 = grr_sql_row($res3, $i)); $i++)
				{
					$sql = "INSERT INTO ".TABLE_PREFIX."_j_userbook_room (login, id_room, idgroupes) values ('$row3[0]',$row2[0],$idGroupe)";
					if (grr_sql_command($sql) < 0)
						fatal_error(1, "<p>" . grr_sql_error());
				}
			}
		}
	}
}

//Round time down to the nearest resolution
function round_t_down($t, $resolution, $am7)
{
	return (int)$t - (int)abs(((int)$t-(int)$am7) % $resolution);
}

//Round time up to the nearest resolution
function round_t_up($t, $resolution, $am7)
{
	if (($t - $am7) % $resolution != 0)
	{
		return $t + $resolution - abs(((int)$t - (int)$am7) % $resolution);
	}
	else
	{
		return $t;
	}
}

/**
 * Menu gauche affichage des sites via select
 *
 * @param string $link
 * @param string $current_site
 * @param string $year
 * @param string $month
 * @param string $day
 * @param string $user
 * @param string $pos
 * @return string
 */
function make_site_select_html($link, $current_site, $year, $month, $day, $user, $pos="G")
{
	global $vocab;
	$nb_sites_a_afficher = 0;
	if (strncmp("4.1", grr_sql_version(), 3) < 0)
	{
		$sql = "SELECT id,sitename
		FROM ".TABLE_PREFIX."_site
		WHERE ".TABLE_PREFIX."_site.id IN (SELECT id_site FROM ".TABLE_PREFIX."_j_site_area GROUP BY id_site)
		ORDER BY id ASC";
	}
	else
	{
		$sql = "SELECT id, sitename
		FROM ".TABLE_PREFIX."_site
		left join ".TABLE_PREFIX."_j_site_area on ".TABLE_PREFIX."_site.id = ".TABLE_PREFIX."_j_site_area.id_site
		WHERE ".TABLE_PREFIX."_j_site_area.id_site is not null
		GROUP BY id_site
		ORDER BY id ASC
		";
	}
	$res = grr_sql_query($sql); // devrait donner la liste des sites non vides
	if ($res)
	{
        $out = array();
		for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
		{
			// Pour chaque site, on détermine le premier domaine disponible
			$sql = "SELECT id_area
			FROM ".TABLE_PREFIX."_j_site_area
			WHERE ".TABLE_PREFIX."_j_site_area.id_site='".$row[0]."'";
			$res2 = grr_sql_query($sql);
			$default_area = -1;
			if ($res2 && grr_sql_count($res2) > 0)
			{
				for ($j = 0; ($row2 = grr_sql_row($res2, $j)); $j++)
				{
					if (SecuAccess::UserArea($user,$row2[0]) == 1)
					{
						// on a trouvé un domaine autorisé
						$default_area = $row2[0];
						break; // On arrête la boucle
					}
				}
			}
			// On libère le résultat $res2
			grr_sql_free($res2);
			if ($default_area != -1)
			{
				// on affiche le site uniquement si au moins un domaine est visible par l'utilisateur
				$nb_sites_a_afficher++;
				$selected = ($row[0] == $current_site) ? 'selected="selected"' : '';

				$queryUrl = [
					'p'		=> $link,
					'month'	=> $month,
					'day'	=> $day,
					'area'	=> $default_area
				];

				$link2 ='app.php?'.http_build_query($queryUrl);
				if (SecuAccess::UserSite($user,$row[0]) == 1)		// DDE: on ne prend que les sites autorisés
				{
					$out[] = '<option '.$selected.' value="'.$link2.'">'.htmlspecialchars($row[1]).'</option>'.PHP_EOL;
				}
			}
		}
	}
	if ($nb_sites_a_afficher > 1)
	{
        $out_html = '<b><i>'.get_vocab('sites').get_vocab('deux_points').'</i></b><form id="site_'.$pos.'" action="'.$_SERVER['PHP_SELF'].'"><div>';
        $out_html .= '<select class="form-control" name="site" onchange="site_go_'.$pos.'()">';
        foreach($out as $row){
            $out_html .= $row;
        }
		$out_html .= "</select>".PHP_EOL;
		$out_html .= "</div>".PHP_EOL;
		$out_html .= "<script type=\"text/javascript\">".PHP_EOL;
		$out_html .= "function site_go_".$pos."(n)".PHP_EOL;
		$out_html .= "{".PHP_EOL;
		$out_html .= "box = document.getElementById(\"site_".$pos."\").site;".PHP_EOL;
		$out_html .= "destination = box.options[box.selectedIndex].value;".PHP_EOL;
		$out_html .= "if (destination) location.href = destination;".PHP_EOL;
		$out_html .= "}".PHP_EOL;
		$out_html .= "</script>".PHP_EOL;
		$out_html .= "<noscript>".PHP_EOL;
		$out_html .= "<div>".PHP_EOL;
		$out_html .= "<input type=\"submit\" value=\"Change\" />".PHP_EOL;
		$out_html .= "</div>".PHP_EOL;
		$out_html .= "</noscript>".PHP_EOL;
		$out_html .= "</form>".PHP_EOL;
		return $out_html;
	}
}

/**
 * Menu gauche affichage des area via select
 *
 * @param string $link
 * @param string $current_site
 * @param string $current_area
 * @param string $year
 * @param string $month
 * @param string $day
 * @param string $user
 * @param string $pos
 * @return string
 */
function make_area_select_html( $link, $current_site, $current_area, $year, $month, $day, $user, $pos="G")
{
	global $vocab;
    $out_html = "";//$link. $current_site. $current_area. $year. $month. $day. $user.'<br />'.PHP_EOL;
	if (Settings::get("module_multisite") == 1)
	{
		// on a activé les sites
		if ($current_site != -1)
			$sql = "SELECT a.id, a.area_name,a.access FROM ".TABLE_PREFIX."_area a, ".TABLE_PREFIX."_j_site_area j WHERE a.id=j.id_area and j.id_site=$current_site ORDER BY a.order_display, a.area_name";
		else // $current_site = -1 correspond à un domaine (ou une ressource) inconnu
            return $out_html;
			//$sql = ""; une requête vide déclenche une erreur non rattrapée
	}
	else
		$sql = "SELECT id, area_name,access FROM ".TABLE_PREFIX."_area ORDER BY order_display, area_name";
	$out_html .= '<b><i>'.get_vocab("areas").'</i></b>'.PHP_EOL;
	$out_html .= '<form id="area_'.$pos.'" action="'.$_SERVER['PHP_SELF'].'">'.PHP_EOL;
	$out_html .= '<div><select class="form-control" name="area" ';
	$out_html .= ' onchange="area_go_'.$pos.'()" ';
	$out_html .= '>'.PHP_EOL;
	$res = grr_sql_query($sql);
	if ($res)
	{
		for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
		{
			$selected = ($row[0] == $current_area) ? 'selected="selected"' : "";
			$link2 = 'app.php?p='.$link.'&amp;year='.$year.'&amp;month='.$month.'&amp;day='.$day.'&amp;area='.$row[0];
			if (SecuAccess::UserArea($user,$row[0]) == 1)
			{
				$out_html .= '<option '.$selected.' value="'.$link2.'">'.htmlspecialchars($row[1]).'</option>'.PHP_EOL;
			}
		}
	}
	$out_html .= '</select>'.PHP_EOL;
	$out_html .= '</div>'.PHP_EOL;
	$out_html .= '<script type="text/javascript">'.PHP_EOL;
	$out_html .= 'function area_go_'.$pos.'()'.PHP_EOL;
	$out_html .= '{'.PHP_EOL;
	$out_html .= 'box = document.getElementById("area_'.$pos.'").area;'.PHP_EOL;
	$out_html .= 'destination = box.options[box.selectedIndex].value;'.PHP_EOL;
	$out_html .= 'if (destination) location.href = destination;'.PHP_EOL;
	$out_html .= '}'.PHP_EOL;
	$out_html .= '</script>'.PHP_EOL;
	$out_html .= '<noscript>'.PHP_EOL;
	$out_html .= '<div>'.PHP_EOL;
	$out_html .= '<input type="submit" value="Change" />'.PHP_EOL;
	$out_html .= '</div>'.PHP_EOL;
	$out_html .= '</noscript>'.PHP_EOL;
	$out_html .= '</form>'.PHP_EOL;
	return $out_html;
}
/**
 * sélecteur de domaines, y compris tous les domaines d'un site
 * area selector, including any area in a site
 *
 * @param string $link
 * @param string $current_site
 * @param string $current_area
 * @param string $year
 * @param string $month
 * @param string $day
 * @param string $user
 * @return string
 */
function make_area_select_all_html( $link, $current_site, $current_area, $year, $month, $day, $user)
{
	global $vocab;
	if (Settings::get("module_multisite") == 1)
		$use_multi_site = 'y';
	else
		$use_multi_site = 'n';
	if ($use_multi_site == 'y')
	{
		// on a activé les sites
		if ($current_site != -1)
			$sql = "SELECT a.id, a.area_name,a.access FROM ".TABLE_PREFIX."_area a, ".TABLE_PREFIX."_j_site_area j WHERE a.id=j.id_area and j.id_site=$current_site ORDER BY a.order_display, a.area_name";
		else
            return "";
			//$sql = "";
	}
	else
		$sql = "SELECT id, area_name,access FROM ".TABLE_PREFIX."_area ORDER BY order_display, area_name";
	$out_html = '<b><i>'.get_vocab("areas").'</i></b>'.PHP_EOL;
	$out_html .= '<form id="area_001" action="'.$_SERVER['PHP_SELF'].'">'.PHP_EOL;
	$out_html .= '<div><select class="form-control" name="area" ';
	$out_html .= ' onchange="area_go()" ';
	$out_html .= '>'.PHP_EOL;
    $out_html .= "<option value=\"app.php?p=".$link."_all&amp;year=$year";
    if ($current_site != -1) 
        $out_html .= "&amp;site=$current_site";
    $out_html .= " \">".get_vocab("any_area")."</option>";
	$res = grr_sql_query($sql);
	if ($res)
	{
		for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
		{
			$selected = ($row[0] == $current_area) ? 'selected="selected"' : "";
			$link2 = 'app.php?p='.$link.'&amp;year='.$year.'&amp;month='.$month.'&amp;day='.$day.'&amp;area='.$row[0];
			if (SecuAccess::UserArea($user,$row[0]) == 1)
			{
				$out_html .= '<option '.$selected.' value="'.$link2.'">'.htmlspecialchars($row[1]).'</option>'.PHP_EOL;
			}
		}
	}
	$out_html .= '</select>'.PHP_EOL;
	$out_html .= '</div>'.PHP_EOL;
	$out_html .= '<script type="text/javascript">'.PHP_EOL;
	$out_html .= 'function area_go()'.PHP_EOL;
	$out_html .= '{'.PHP_EOL;
	$out_html .= 'box = document.getElementById("area_001").area;'.PHP_EOL;
	$out_html .= 'destination = box.options[box.selectedIndex].value;'.PHP_EOL;
	$out_html .= 'if (destination) location.href = destination;'.PHP_EOL;
	$out_html .= '}'.PHP_EOL;
	$out_html .= '</script>'.PHP_EOL;
	$out_html .= '<noscript>'.PHP_EOL;
	$out_html .= '<div>'.PHP_EOL;
	$out_html .= '<input type="submit" value="Change" />'.PHP_EOL;
	$out_html .= '</div>'.PHP_EOL;
	$out_html .= '</noscript>'.PHP_EOL;
	$out_html .= '</form>'.PHP_EOL;
	return $out_html;
}
/**
 * Menu gauche affichage des room via select
 *
 * @param string $link
 * @param string $current_area
 * @param string $current_room
 * @param string $year
 * @param string $month
 * @param string $day
 * @param string $pos
 * @return string
 */
function make_room_select_html($link, $current_area, $current_room, $year, $month, $day, $pos="G")
{
	global $vocab;
	$sql = "select id, room_name, description from ".TABLE_PREFIX."_room WHERE area_id='".SecuChaine::ProtectDataSql($current_area)."' order by order_display,room_name";
	$res = grr_sql_query($sql);
	if ($res && (grr_sql_count($res)>0)) // il y a des ressources à afficher
	{
		if ($link != "jour")
			$linkTout = $link."_all";
		else 
			$linkTout = $link;

        $out_html = "<b><i>".get_vocab('rooms').get_vocab("deux_points")."</i></b><br /><form id=\"room_".$pos."\" action=\"".$_SERVER['PHP_SELF']."\"><div><select class=\"form-control\" name=\"room\" onchange=\"room_go_".$pos."()\">";
        $out_html .= "<option value=\"app.php?p=$linkTout&amp;year=$year&amp;month=$month&amp;day=$day&amp;area=$current_area\">".get_vocab("all_rooms")."</option>";
		for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
		{
			if (SecuAccess::UserResource(getUserName(),$row[0]))
			{
				if ($row[2])
					$temp = " (".htmlspecialchars($row[2]).")";
				else
					$temp = "";
				$selected = ($row[0] == $current_room) ? "selected=\"selected\"" : "";
				$link2 = 'app.php?p='.$link.'&amp;year='.$year.'&amp;month='.$month.'&amp;day='.$day.'&amp;room='.$row[0];
				$out_html .= "<option $selected value=\"$link2\">" . htmlspecialchars($row[1].$temp)."</option>".PHP_EOL;
			}
		}
        $out_html .= "</select>".PHP_EOL;
        $out_html .= "</div>".PHP_EOL;
        $out_html .= "<script type=\"text/javascript\">".PHP_EOL;
        $out_html .= "function room_go_".$pos."()".PHP_EOL;
        $out_html .= " {".PHP_EOL;
        $out_html .= "box = document.getElementById(\"room_".$pos."\").room;".PHP_EOL;
        $out_html .= "destination = box.options[box.selectedIndex].value;".PHP_EOL;
        $out_html .= "if (destination) location.href = destination;".PHP_EOL;
        $out_html .= "}".PHP_EOL;
        $out_html .= "</script>".PHP_EOL;
        $out_html .= "<noscript>".PHP_EOL;
        $out_html .= "<div>".PHP_EOL;
        $out_html .= "<input type=\"submit\" value=\"Change\" />".PHP_EOL;
        $out_html .= "</div>".PHP_EOL;
        $out_html .= "</noscript>".PHP_EOL;
        $out_html .= "</form>".PHP_EOL;
        return $out_html;
	}
}
/**
 * Affichage des sites sous la forme d'une liste
 *
 * @param string $link
 * @param string $current_site
 * @param string $year
 * @param string $month
 * @param string $day
 * @param string $user
 * @return string
 */
function make_site_list_html($link, $current_site, $year, $month, $day,$user)
{
	global $vocab;
	// On affiche le site
	if (Settings::get("module_multisite") == 1)
	{
		$sql = "SELECT id,sitename
		FROM ".TABLE_PREFIX."_site
		ORDER BY sitename";
		$nb_sites_a_afficher = 0;
		$res = grr_sql_query($sql);
		if ($res)
		{
            $out = array();
			for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
			{
				if (SecuAccess::UserSite($user,$row[0]) == 1)		// DDE: on ne prend que les sites autorisés
				{
					// Pour chaque site, on détermine s'il y a des domaines visibles par l'utilisateur
					$sql = "SELECT id_area
					FROM ".TABLE_PREFIX."_j_site_area
					WHERE ".TABLE_PREFIX."_j_site_area.id_site='".$row[0]."'";
					$res2 = grr_sql_query($sql);
					$au_moins_un_domaine = false;
					if ($res2 && grr_sql_count($res2) > 0)
					{
						for ($j = 0; ($row2 = grr_sql_row($res2, $j)); $j++)
						{
							if (SecuAccess::UserArea($user,$row2[0]) == 1)
							{
								// on a trouvé un domaine autorisé
								$au_moins_un_domaine = true;
								break;	// On arrête la boucle
							}
						}
					}
					// On libère la ressource2
					grr_sql_free($res2);
					if ($au_moins_un_domaine)
					{
						$queryUrl = [
							'p'			=> $link,
							'year'		=> $year,
							'month'		=> $month,
							'day'		=> $day,
							'id_site'	=> $row[0]
						];

						$link2 ='app.php?'.http_build_query($queryUrl);
						// on affiche le site uniquement si au moins un domaine est visible par l'utilisateur
						$nb_sites_a_afficher++;
						if ($row[0] == $current_site)
						{
							$out[] = '
							<b><a id="liste_select" href="'.$link2.'" title="'.$row[1].'">&gt; '.htmlspecialchars($row[1]).'</a></b>
							<br />'."\n";
						}
						else
						{
							$out[] = '
							<a id="liste" href="'.$link2.'" title="'.$row[1].'">'.htmlspecialchars($row[1]).'</a>
							<br />'."\n";
						}
					}
				}
			}
		}
		if ($nb_sites_a_afficher > 1){
            $out_html = '<b><i><span class="bground">'.get_vocab('sites').get_vocab('deux_points').'</span></i></b><br />';
            foreach($out as $row){
                $out_html .= $row;
            }
            return $out_html;
        }
		else
			return '';
	}
}
/**
 * Affichage des area sous la forme d'une liste
 *
 * @param string $link
 * @param string $current_site
 * @param string $current_area
 * @param string $year
 * @param string $month
 * @param string $day
 * @param string $user
 * @return string
 */
function make_area_list_html($link, $current_site, $current_area, $year, $month, $day, $user)
{
	global $vocab;
	if (Settings::get("module_multisite") == 1)
		$use_multi_site = 'y';
	else
		$use_multi_site = 'n';
	$out_html = "<b><i><span class=\"bground\">".get_vocab("areas")."</span></i></b><br />";
	if ($use_multi_site == 'y')
	{
		// on a activé les sites
		if ($current_site != -1)
			$sql = "SELECT a.id, a.area_name,a.access
		FROM ".TABLE_PREFIX."_area a, ".TABLE_PREFIX."_j_site_area j
		WHERE a.id=j.id_area and j.id_site=$current_site
		ORDER BY a.order_display, a.area_name";
		else
			$sql = "";
	}
	else
	{
		$sql = "SELECT id, area_name,access
		FROM ".TABLE_PREFIX."_area
		ORDER BY order_display, area_name";
	}
	$res = 0;
	if (($current_site != -1) || ($use_multi_site == 'n'))
		$res = grr_sql_query($sql);
	if ($res)
	{
		for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
		{
			if (SecuAccess::UserArea($user,$row[0]) == 1)
			{
				if ($row[0] == $current_area)
				{
					$out_html .= "<a id=\"liste_select\" onclick=\"charger();\"  href=\"app.php?p=".$link."&amp;year=$year&amp;month=$month&amp;day=$day&amp;area=$row[0]\">&gt; ".htmlspecialchars($row[1])."</a></b><br />\n";
				} else {
					$out_html .= "<a id=\"liste\" onclick=\"charger();\"  href=\"app.php?p=".$link."&amp;year=$year&amp;month=$month&amp;day=$day&amp;area=$row[0]\"> ".htmlspecialchars($row[1])."</a><br />\n";
				}
			}
		}
	}
	grr_sql_free($res);
    return $out_html;
}
/**
 * Affichage des room sous la forme d'une liste
 *
 * @param string $link
 * @param string $current_area
 * @param string $current_room
 * @param string $year
 * @param string $month
 * @param string $day
 * @return string
 */
function make_room_list_html($link,$current_area, $current_room, $year, $month, $day)
{
	global $vocab;
	$out_html = "<b><i><span class=\"bground\">".get_vocab("rooms").get_vocab("deux_points")."</span></i></b><br />";
	$sql = "select id, room_name, description from ".TABLE_PREFIX."_room WHERE area_id='".SecuChaine::ProtectDataSql($current_area)."' order by order_display,room_name";
	$res = grr_sql_query($sql);
	if ($res)
	{
		for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
		{
			// On affiche uniquement les ressources autorisées
			if (SecuAccess::UserResource(getUserName(), $row[0]))
			{
				if ($row[0] == $current_room)
					$out_html .= "<span id=\"liste_select\">&gt; ".htmlspecialchars($row[1])."</span><br />\n";
				else
					$out_html .= "<a id=\"liste\" onclick=\"charger();\"  href=\"app.php?p=".$link."&amp;year=$year&amp;month=$month&amp;day=$day&amp;&amp;room=$row[0]\">".htmlspecialchars($row[1]). "</a><br />\n";
			}
		}
	}
    grr_sql_free($res);
    return $out_html;
}

/*
 * Affichage des sites sous la forme d'une liste de boutons
 *
 * @param string $link
 * @param string $current_site
 * @param string $year
 * @param string $month
 * @param string $day
 * @return string
 */
function make_site_item_html($link, $current_site, $year, $month, $day, $user)
{
	global $vocab;
	$nb_sites_a_afficher = 0;
	$sql = "SELECT id, sitename
	FROM ".TABLE_PREFIX."_site
	left join ".TABLE_PREFIX."_j_site_area on ".TABLE_PREFIX."_site.id = ".TABLE_PREFIX."_j_site_area.id_site
	WHERE ".TABLE_PREFIX."_j_site_area.id_site is not null
	GROUP BY id_site
	ORDER BY id ASC
	";
	$res = grr_sql_query($sql);// devrait donner la liste des sites non vides
	if ($res)
	{
        $out = array();
		for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
		{
			$sql = "SELECT id_area FROM ".TABLE_PREFIX."_j_site_area WHERE ".TABLE_PREFIX."_j_site_area.id_site='".$row[0]."'";
			$res2 = grr_sql_query($sql);
			$default_area = -1;
			if ($res2 && grr_sql_count($res2) > 0)
			{
				for ($j = 0; ($row2 = grr_sql_row($res2, $j)); $j++)
				{
					if (SecuAccess::UserArea($user,$row2[0]) == 1)
					{
						$default_area = $row2[0];
						break; // un domaine est accessible, on sort de la boucle
					}
				}
			}
			grr_sql_free($res2);

			$queryUrl = [
				'p'			=> $link,
				'year'		=> $year,
				'month'		=> $month,
				'day'		=> $day,
			];

			if ($default_area != -1)
			{
				$nb_sites_a_afficher++;
				$queryUrl['area'] = $default_area;
			}
			$link2 ='app.php?'.http_build_query( $queryUrl );

			if ($current_site != null)
			{
				if (SecuAccess::UserSite($user,$row[0]) == 1)		// DDE: on ne prend que les sites autorisés
				{
					if ($current_site == $row[0])
						$out[] = "<input id=\"item_select\" type=\"button\" class=\"btn btn-primary btn-lg btn-block item_select\" name=\"$row[0]\" value=\"".htmlspecialchars($row[1])."\" onclick=\"location.href='$link2';charger();\" />".PHP_EOL;
					else
						$out[] = "<input type=\"button\" class=\"btn btn-default btn-lg btn-block item\" name=\"$row[0]\" value=\"".htmlspecialchars($row[1])." \" onclick=\"location.href='$link2';charger();\" />".PHP_EOL;
				}
			}
			else
				$out[] = "<input type=\"button\" class=\"btn btn-default btn-lg btn-block item\" name=\"$row[0]\" value=\"".htmlspecialchars($row[1])." \" onclick=\"location.href='$link2';charger();\" /><br />".PHP_EOL;
		}
	}
	if ($nb_sites_a_afficher > 1)// s'il y a au moins deux sites à afficher, on affiche une liste de boutons, sinon rien.
	{
        $out_html = '<br />'.PHP_EOL.'<div class="panel panel-default">'.PHP_EOL.'<div class="panel-heading">'.get_vocab('sites').get_vocab('deux_points').'</div>'.PHP_EOL.'<div class="panel-body">'.PHP_EOL.'<form class="ressource" id="site_001" action="'.$_SERVER['PHP_SELF'].'">';
        foreach($out as $row){
            $out_html .= $row;
        }
		$out_html .= '</form>'.PHP_EOL;
		$out_html .= '</div></div>'.PHP_EOL;
		$out_html .= '<script type="text/javascript">'.PHP_EOL;
		$out_html .= 'function site_go()'.PHP_EOL;
		$out_html .= '{'.PHP_EOL;
		$out_html .= 'box = document.getElementById("site_001").site;'.PHP_EOL;
		$out_html .= 'destination = box.options[box.selectedIndex].value;'.PHP_EOL;
		$out_html .= 'if (destination) location.href = destination;'.PHP_EOL;
		$out_html .= '}'.PHP_EOL;
		$out_html .= '</script>'.PHP_EOL;
		$out_html .= '<noscript>'.PHP_EOL;
		$out_html .= '<div>'.PHP_EOL;
		$out_html .= '<input type="submit" value="change" />'.PHP_EOL;
		$out_html .= '</div>'.PHP_EOL;
		$out_html .= '</noscript>'.PHP_EOL;
		return $out_html;
	}
    else return "";
}
/**
 * Affichage des area sous la forme d'une liste de boutons
 *
 * @param string $link
 * @param string $current_site
 * @param string $current_area
 * @param string $year
 * @param string $month
 * @param string $day
 * @return string
 */
function make_area_item_html( $link, $current_site, $current_area, $year, $month, $day, $user)
{
	global $vocab;
	if (Settings::get("module_multisite") == 1)
	{// on a activé les sites
		if ($current_site != -1)
			$sql = "SELECT a.id, a.area_name,a.access
		FROM ".TABLE_PREFIX."_area a, ".TABLE_PREFIX."_j_site_area j
		WHERE a.id=j.id_area and j.id_site=$current_site
		ORDER BY a.order_display, a.area_name";
		else
			return ""; // cas d'une ressource ou d'un domaine inconnu
	}
	else
	{
		$sql = "SELECT id, area_name,access
		FROM ".TABLE_PREFIX."_area
		ORDER BY order_display, area_name";
	}
	$out_html = '<br />'.PHP_EOL.'<div class="panel panel-default">'.PHP_EOL.'<div class="panel-heading">'.get_vocab("areas").'</div>'.PHP_EOL.'<div class="panel-body">'.PHP_EOL;
	$out_html .= '<form class="ressource" id="area_001" action="'.$_SERVER['PHP_SELF'].'">'.PHP_EOL;
	$res = grr_sql_query($sql);
	if ($res)
	{
		for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
		{
			$link2 = 'app.php?p='.$link.'&amp;year='.$year.'&amp;month='.$month.'&amp;day='.$day.'&amp;area='.$row[0];
			if (SecuAccess::UserArea($user, $row[0]) == 1)
			{
				if ($current_area != null)
				{
					if ($current_area == $row[0]) /* Couleur du domaine selectionné*/
						$out_html .= '<input class="btn btn-primary btn-lg btn-block item_select" name="'.$row[0].'" value="'.htmlspecialchars($row[1]).'" onclick="location.href=\''.$link2.'\' ;charger();"/>'.PHP_EOL;
					else
						$out_html .= '<input class="btn btn-default btn-lg btn-block item " name="'.$row[0].'" value="'.htmlspecialchars($row[1]).'" onclick="location.href=\''.$link2.'\' ;charger();"/>'.PHP_EOL;
				}
				else
					$out_html .= '<input class="btn btn-default btn-lg btn-block item" name="'.$row[0].'" value="'.htmlspecialchars($row[1]).'" onclick="location.href=\''.$link2.'\' ;charger();"/>'.PHP_EOL;
			}
		}
	}
	$out_html .= '</form>'.PHP_EOL.'</div>'.PHP_EOL.'</div>'.PHP_EOL;
	return $out_html;
}
//end make_area_item_html
/**
 * Affichage des rooms sous la forme d'une liste de boutons
 *
 * @param string $link
 * @param string $current_area
 * @param string $current_room
 * @param string $year
 * @param string $month
 * @param string $day
 * @return string
 */
function make_room_item_html($link, $current_area, $current_room, $year, $month, $day)
{
	global $vocab;
	$sql = "SELECT id, room_name, description FROM ".TABLE_PREFIX."_room WHERE area_id='".SecuChaine::ProtectDataSql($current_area)."' ORDER BY order_display,room_name";
	$res = grr_sql_query($sql);
	if ($res && (grr_sql_count($res)>0)) // il y a des ressources à afficher
	{
        $out_html = '<br />'.PHP_EOL.'<div class="panel panel-default">'.PHP_EOL.'<div class="panel-heading">'.get_vocab("rooms").get_vocab("deux_points").'</div>'.PHP_EOL.'<div class="panel-body">'.PHP_EOL.'<form class="ressource" id="room_001" action="'.$_SERVER['PHP_SELF'].'">'.PHP_EOL;
        $all_ressource = 0; // permet l'affichage de toutes les ressources
		for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
		{
			if (SecuAccess::UserResource(getUserName(),$row[0]))
			{
				$link2 = 'app.php?p='.$link.'&amp;year='.$year.'&amp;month='.$month.'&amp;day='.$day.'&amp;room='.$row[0];
                $link_a = $link;
                if (($link != 'jour')&&(!strpos($link,'all'))) {$link_a .= '_all';}
				$link_all_room = 'app.php?p='.$link_a.'&amp;year='.$year.'&amp;month='.$month.'&amp;day='.$day.'&amp;area='.$current_area;
				if (!isset($_GET['room']))
				{
					if (isset($all_ressource) && $all_ressource == 0)
						$out_html .= /*'<div class="panel-body">'.PHP_EOL.*/'<input id="item_select" class="btn btn-primary btn-lg btn-block item_select" name="all_room" value="'.get_vocab("all_rooms").'" onclick="location.href=\''.$link_all_room.'\' ;charger();"/>'.PHP_EOL;
					$out_html .= '<input class="btn btn-default btn-lg btn-block item" type="button" name="'.$row[0].'" value="'.htmlspecialchars($row[1]).'" onclick="location.href=\''.$link2.'\' ;charger();"/>'.PHP_EOL;
					$all_ressource = 1;
				}
				else //changed (Ajout de type = " button pr gerer saut de ligne " 
				{
					if (isset($all_ressource) && $all_ressource == 0)
						$out_html .= '<input class="btn btn-default btn-lg btn-block item" type="button" name="all_room" value="'.get_vocab("all_rooms").'" onclick="location.href=\''.$link_all_room.'\' ;charger();"/>'.PHP_EOL;
					$all_ressource = 1;
					if ($current_room == $row[0])
						$out_html .= '<input class="btn btn-primary btn-lg btn-block item_select" type="button" name="'.$row[0].'" value="'.htmlspecialchars($row[1]).'" onclick="location.href=\''.$link2.'\';charger();"/>'.PHP_EOL;
					else
						$out_html .= '<input class="btn btn-default btn-lg btn-block item" type="button" name="'.$row[0].'" value="'.htmlspecialchars($row[1]).'" onclick="location.href=\''.$link2.'\' ;charger();"/>'.PHP_EOL;
				}
			}
		}
		$out_html .= '</form>'.PHP_EOL.'</div>'.PHP_EOL.'</div>'.PHP_EOL;
        return $out_html;
    }
}
// end make_room_item_html
/**
 * @param integer $action
 * $action = 1 -> Création
 * $action = 2 -> Modification
 * $action = 3 -> Suppression
 * $action = 4 -> Suppression automatique
 * $action = 5 -> Réservation en attente de modération
 * $action = 6 -> Résultat d'une décision de modération
 * $action = 7 -> Notification d'un retard dans la restitution d'une ressource.
*/
function send_mail($id_entry, $action, $dformat, $tab_id_moderes = array(), $oldRessource = '', $rep_info = array(), $mail_invite = 0)
{
	global $vocab, $niveauDossier, $locale, $weekstarts, $enable_periods, $periods_name;

	$message_erreur = '';
	$prefixeChemin = cheminDetermination($niveauDossier);

	require_once $prefixeChemin.'include/mail.class.php';
	require_once $prefixeChemin.'include/pages.class.php';

	if (!Pages::load())
		die('Erreur chargement pages');

	$sql = "SELECT ".TABLE_PREFIX."_entry.name,
	".TABLE_PREFIX."_entry.description,
	".TABLE_PREFIX."_entry.beneficiaire,
	".TABLE_PREFIX."_room.room_name,
	".TABLE_PREFIX."_area.area_name,
	".TABLE_PREFIX."_entry.type,
	".TABLE_PREFIX."_entry.room_id,
	".TABLE_PREFIX."_entry.repeat_id,
	" . grr_sql_syntax_timestamp_to_unix("".TABLE_PREFIX."_entry.timestamp") . ",
	(".TABLE_PREFIX."_entry.end_time - ".TABLE_PREFIX."_entry.start_time),
	".TABLE_PREFIX."_entry.start_time,
	".TABLE_PREFIX."_entry.end_time,
	".TABLE_PREFIX."_room.area_id,
	".TABLE_PREFIX."_room.delais_option_reservation,
	".TABLE_PREFIX."_entry.option_reservation,
	".TABLE_PREFIX."_entry.moderate,
	".TABLE_PREFIX."_entry.beneficiaire_ext,
	".TABLE_PREFIX."_entry.jours,
	".TABLE_PREFIX."_entry.clef,
	".TABLE_PREFIX."_entry.courrier,
	".TABLE_PREFIX."_room.description,
	".TABLE_PREFIX."_room.comment_room,
	".TABLE_PREFIX."_room.capacity
	FROM ".TABLE_PREFIX."_entry, ".TABLE_PREFIX."_room, ".TABLE_PREFIX."_area
	WHERE ".TABLE_PREFIX."_entry.room_id = ".TABLE_PREFIX."_room.id
	AND ".TABLE_PREFIX."_room.area_id = ".TABLE_PREFIX."_area.id
	AND ".TABLE_PREFIX."_entry.id='".SecuChaine::ProtectDataSql($id_entry)."'
	";
	$res = grr_sql_query($sql);
	if (!$res)
		fatal_error(0, grr_sql_error());
	if (grr_sql_count($res) < 1)
		fatal_error(0, get_vocab('invalid_entry_id'));
	$row = grr_sql_row($res, 0);
	grr_sql_free($res);

	get_planning_area_values($row[12]);
	$webmaster_email  			= Settings::get("webmaster_email");
	$rep_type					= 0;
	$breve_description 			= bbcode(removeMailUnicode(htmlspecialchars($row[0])), 'nobbcode');
	$description  				= bbcode(removeMailUnicode(htmlspecialchars($row[1])), 'nobbcode');
	$beneficiaire 				= htmlspecialchars($row[2]);
	$room_name    				= removeMailUnicode(htmlspecialchars($row[3]));
	$area_name    				= removeMailUnicode(htmlspecialchars($row[4]));
	$room_id      				= $row[6];
	$repeat_id    				= $row[7];
	$delais_option_reservation 	= $row[13];
	$option_reservation 		= $row[14];
	$moderate 					= $row[15];
	$beneficiaire_ext			= htmlspecialchars($row[16]);
	$jours_cycle 				= htmlspecialchars($row[17]);
	$duration     				= $row[9];
	$raw_start_date 			= $row[10];
	$raw_end_date 				= $row[11];

	// Date début et fin de la réservation
	if ($enable_periods == 'y')
	{
		list($start_period, $start_date) = period_date_string($row[10]);
		list($start_period, $end_date) = period_date_string($row[11]);
	}
	else
	{
		$start_date = time_date_string($row[10],$dformat);
		$end_date = time_date_string($row[11],$dformat);
	}

	// Recherche du nom de l'ancienne ressource si besoin
	if($oldRessource != '' && $oldRessource != $room_id)
	{
		$oldRess = grr_sql_query1("SELECT room_name FROM ".TABLE_PREFIX."_room WHERE id='".SecuChaine::ProtectDataSql($oldRessource)."'");
		$room_name = $oldRess." => ".$room_name;
	}

	// Si la réservation est périodique, on récupère les informations de périodicité
	if ($repeat_id != 0)
	{
		// Utiliser les informations de périodicité fournies si disponibles, sinon les récupérer depuis la base
		if (!empty($rep_info) && isset($rep_info['rep_type']))
		{
			$rep_type = $rep_info['rep_type'];
			$rep_end_date = isset($rep_info['rep_end_date']) ? time_date_string($rep_info['rep_end_date'], $dformat) : '';
			$rep_end_date_raw = $rep_info['rep_end_date'];
			$rep_id = $rep_info['rep_id'];
			$rep_opt = isset($rep_info['rep_opt']) ? $rep_info['rep_opt'] : '';
			$rep_num_weeks = isset($rep_info['rep_num_weeks']) ? $rep_info['rep_num_weeks'] : 1;
		}
		else
		{
			// Récupération depuis la base (comportement par défaut)
			$res = grr_sql_query("SELECT rep_type, end_date, rep_opt, rep_num_weeks, id FROM ".TABLE_PREFIX."_repeat WHERE id='".SecuChaine::ProtectDataSql($repeat_id)."'");
			if (!$res)
				fatal_error(0, grr_sql_error());
			$test = grr_sql_count($res);
			if ($test > 1)
				fatal_error(0, "Deux reservations ont le même ID.");
			else
			{
				$row2 = grr_sql_row($res, 0);
				$rep_type     = $row2[0];
				$rep_end_date = time_date_string($row2[1],$dformat);
				$rep_end_date_raw = $row2[1];
				$rep_opt      = $row2[2];
				$rep_num_weeks = $row2[3];
				$rep_id = $row2[4];
			}
			grr_sql_free($res);
		}
	}
	if ($enable_periods == 'y')
		toPeriodString($start_period, $duration, $dur_units);
	else
		toTimeString($duration, $dur_units);

	// Le bénéficiaire
	$beneficiaire_email = affiche_nom_prenom_email($beneficiaire,$beneficiaire_ext,"onlymail");
	if ($beneficiaire != "")
	{
		$beneficiaireInfos = grr_sql_query("SELECT etat, desactive_mail FROM ".TABLE_PREFIX."_utilisateurs WHERE login='$beneficiaire'");
		$rowBeneficiaire = grr_sql_row($beneficiaireInfos, 0);

		if($rowBeneficiaire[0] != 'inactif' && $rowBeneficiaire[1] == 0)
			$beneficiaire_actif = "actif";
		else
			$beneficiaire_actif = "inactif";
	}
	else if (($beneficiaire_ext != "") && ($beneficiaire_email != ""))
		$beneficiaire_actif = "actif";
	else
		$beneficiaire_actif = "inactif";

	// Utilisateur ayant agi sur la réservation
	$user_login = getUserName();
	$user_email = grr_sql_query1("SELECT email FROM ".TABLE_PREFIX."_utilisateurs WHERE login='$user_login'");
	//
	// Elaboration du message destiné aux utilisateurs désignés par l'admin dans la partie "Mails automatiques"
	//Infos générales
	$codes = [
		'%nomdusite%' => Settings::get('title_home_page'),
		'%nometablissement%' => Settings::get('company'),
		'%urlgrr%' =>  traite_grr_url("","y"),
		'%webmasteremail%' => $webmaster_email,
		'%identifiantuser%' => $user_login,
		'%logincompletuser%' => affiche_nom_prenom_email($user_login,"","formail"),
		'%logincompletbeneficiaire%' => affiche_nom_prenom_email($beneficiaire,$beneficiaire_ext,"formail"),
		'%identifiantbeneficiaire%' => $beneficiaire,
		'%domaine%' => $area_name,
		'%ressource%' => $room_name,
		'%ressourcedescription%' => $row[20],
		'%ressourcecomment%' => $row[21],
		'%ressourcecapacity%' => $row[22],
		'%resanom%' => $vocab["entryid"].$room_id,
		'%resadatedebut%' => $start_date,
		'%resadatefin%' => $end_date,
		'%resaduree%' => $duration." ".$dur_units,
		'%resatype%' => "?".$row[5]."?",
		'%resachampsadditionnels%' =>  affichage_champ_add_mails($id_entry),
		'%urldetail%' => "\n".traite_grr_url("","y")."app.php?p=vuereservation&id=".$id_entry,
		'%resaperiodique%' => "",
		'%resaconfirmation%' => "",
		'%resadescription%' => "",
		'%enattentemoderation%' => "",
		'%decisionmotif%' => "",
		'%maildestinataire%' => ""
	];

	// Infos sur la réservation	
	if (trim($breve_description) != "")
		$codes['%resanom%'] = $vocab["namebooker"]." ".$vocab["deux_points"]." ".$breve_description;

	if ($description !='')
		$codes['%resadescription%'] = $vocab["description"]." ".$description."\n";

	if ($moderate == 1)
		$codes['%enattentemoderation%'] = " (".$vocab['en_attente_moderation'].")";

	// Type de réservation
	$temp = grr_sql_query1("SELECT type_name FROM ".TABLE_PREFIX."_type_area WHERE type_letter='".$row[5]."'");
	if ($temp != -1)
		$codes['%resatype%'] = removeMailUnicode($temp);

	// Infos périodicités
	if ($rep_type != 0)
	{
		$affiche_period = $vocab['rep_type_'.$rep_type];
		$resaPeriodique = $vocab["rep_type"]." ".$affiche_period."\n";

		if ($rep_type == 2) // Chaque semaine ou semaine 1 de 2, 3, 4 ou 5
		{
			$weeklist = array("unused", "every_week", "week_1_of_2", "week_1_of_3", "week_1_of_4", "week_1_of_5");
			$affiche_period = $vocab[$weeklist[$rep_num_weeks]];

			$opt = "";
			for ($i = 0; $i < 7; $i++)
			{
				$daynum = ($i + $weekstarts) % 7;
				if ($rep_opt[$daynum])
					$opt .= day_name($daynum) . " ";
			}
			if ($opt)
				$resaPeriodique .= $vocab["rep_rep_day"]." ".$opt."\n";
		}
		elseif ($rep_type == 6) // Cycles de jours
		{
			if (Settings::get("jours_cycles_actif") == 1)
				$resaPeriodique .= $vocab["rep_type_6"].preg_replace("/ /", " ",$vocab["deux_points"]).ucfirst(substr($vocab["rep_type_6"],0,1)).$jours_cycle."\n";
		}
		$resaPeriodique .= $vocab["rep_end_date"]." ".$rep_end_date."\n";
		$codes['%resaperiodique%'] = $resaPeriodique;
	}

	if (($delais_option_reservation > 0) && ($option_reservation != -1))
		$codes['%resaconfirmation%'] = "*** ".$vocab["reservation_a_confirmer_au_plus_tard_le"]." ".time_date_string_jma($option_reservation,$dformat)." ***\n";

	// Par défaut l'expéditeur est l'utilisateur ayant agi sur la réservation
	$expediteur1 = $user_email;
	$expediteur2 = $user_email;
	$expediteur3 = $user_email;
	$repondre1 = '';
	$repondre2 = '';
	$repondre3 = '';

	if($action == 4){ // Suppression automatique
		// L'expéditeur est le webmaster
		$expediteur1 = $webmaster_email;
		$expediteur2 = $webmaster_email;
		$expediteur3 = $webmaster_email;
	} 
	elseif ($action == 6){ // Résultat d'une décision de modération
		$resmoderate = grr_sql_query("SELECT moderate, motivation_moderation FROM ".TABLE_PREFIX."_entry_moderate WHERE id ='".SecuChaine::ProtectDataSql($id_entry)."'");
		if (!$resmoderate)
			fatal_error(0, grr_sql_error());
		if (grr_sql_count($resmoderate) < 1)
			fatal_error(0, get_vocab('invalid_entry_id'));
		$rowModerate = grr_sql_row($resmoderate, 0);
		grr_sql_free($resmoderate);
		$moderate_decision = $rowModerate[0];
		$moderate_description = $rowModerate[1];

		if ($moderate_decision == 2)
			$codes['%decisionmoderation%'] = $vocab["moderation_acceptee"];
		else if ($moderate_decision == 3)
			$codes['%decisionmoderation%'] = $vocab["moderation_refusee"];

		if ($moderate_description != "")
			$codes['%decisionmotif%'] = $vocab["motif"].$vocab["deux_points"]."<br>".$moderate_description;

		if (count($tab_id_moderes) != 0 )
		{
			foreach ($tab_id_moderes as $id_moderes)
				$codes['%urldetail%'] =  "\n".traite_grr_url("","y")."app.php?p=vuereservation&id=".$id_moderes;
		}
	}
	elseif ($action == 7){ // Notification d'un retard dans la restitution d'une ressource
		// L'expéditeur est le webmaster
		$expediteur1 = $webmaster_email;
		$expediteur2 = $webmaster_email;
		$expediteur3 = $webmaster_email;

		// Variable indiquant à qui le rappel est effectué
		if ($beneficiaire_email != "")
			$codes['%maildestinataire%'] .= $vocab["un_email_envoye"].$beneficiaire_email;
	}

	/*
	(1) Pour les utilisateurs : Qui sont renseigné manuellement dans l'administration ET les utilisateurs notifié via les champs additionnels
	*/
	$sql = "SELECT u.email FROM ".TABLE_PREFIX."_utilisateurs u, ".TABLE_PREFIX."_j_mailuser_room j WHERE (j.id_room='".SecuChaine::ProtectDataSql($room_id)."' AND u.login=j.login and u.etat='actif' AND j.mail_resa=1 AND u.desactive_mail=0) ORDER BY u.nom, u.prenom";
	$res = grr_sql_query($sql);
	$nombre = grr_sql_count($res);
	$destinataire1 = "";
	if ($nombre > 0)
	{
		$tab_destinataire = array();
		for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
		{
			if ($row[0] != "")
				$tab_destinataire[] = $row[0];
		}
		foreach ($tab_destinataire as $value){
			$destinataire1 .= $value.";";
		}
	} else {
		$destinataire1 = $user_email . ';';
	}
	$destinataire_spec = envois_spec_champ_add_mails($id_entry);
	$destinataire1 = $destinataire1 . $destinataire_spec;

	if($destinataire1 != "")
	{
		if ($repondre1 == '')
			$repondre1 = $expediteur1;

		$codes['%raisonmail%'] = $vocab['mail_raison_5'];
		$templateMail1 = Pages::get('mails_resa_'.$action.'_1_'.$locale);
		$sujetEncode1 = str_replace(array_keys($codes), $codes, $templateMail1[0]);
		$msgEncode1 = str_replace(array_keys($codes), $codes, $templateMail1[1]);
		Email::Envois($destinataire1, $sujetEncode1, $msgEncode1, $expediteur1, '', '', $repondre1,'mails_resa_'.$action.'_1_'.$locale, $id_entry, 1, $mail_invite, array(
			'id_entry' => $id_entry,
			'start_time' => $raw_start_date,
			'end_time' => $raw_end_date,
			'nom_resa' => $breve_description,
			'location' => $room_name,
			'action' => $action,
			'rep_id' => isset($rep_id) ? $rep_id : -1,
			'rep_opt' => isset($rep_opt) ? $rep_opt : -1,
			'rep_type' => isset($rep_type) ? $rep_type : -1,
			'rep_num_weeks' => isset($rep_num_weeks) ? $rep_num_weeks : -1,
			'rep_end_date' => isset($rep_end_date_raw) ? $rep_end_date_raw : -1,
			'rep_month_abs1' => isset($rep_info['rep_month_abs1']) ? $rep_info['rep_month_abs1'] : -1,
			'rep_month_abs2' => isset($rep_info['rep_month_abs2']) ? $rep_info['rep_month_abs2'] : -1,
		));
	}

	/*
	(2) Pour le benificiare de la ressource
	*/
	$envoi2 = false;
	$destinataire2 = $beneficiaire_email;
	$codes['%raisonmail%'] = $vocab['mail_raison_6'];

	// Cas d'une création, modification ou suppression d'un message par un utilisateur différent du bénéficiaire :
	// On envoie un message au bénéficiaire de la réservation pour l'avertir d'une modif ou d'une suppression
	if (($action == 1 || $action == 2 || $action == 3 || $action == 6) && ((strtolower($user_login) != strtolower($beneficiaire)) || (Settings::get('send_always_mail_to_creator') == '1')))
		$envoi2 = true;
	elseif ($action == 4 || $action == 5 || $action == 7) // Supression automatique OU réservation en attente de modération, mail pour le beneficiaire OU Résultat d'une décision de modération
	{
		// L'expéditeur est le webmaster
		$expediteur2 = $webmaster_email;
		$expediteur3 = $webmaster_email;

		$envoi2 = true;
	}

	if($envoi2 == true && $beneficiaire_email != '' && $beneficiaire_actif == 'actif')
	{
		if ($repondre2 == '')
			$repondre2 = $expediteur2;

		$templateMail2 = Pages::get('mails_resa_'.$action.'_2_'.$locale);
		$sujetEncode2 = str_replace(array_keys($codes), $codes, $templateMail2[0]);
		$msgEncode2 = str_replace(array_keys($codes), $codes, $templateMail2[1]);
		Email::Envois($destinataire2, $sujetEncode2, $msgEncode2, $expediteur2, '', '', $repondre2,'mails_resa_'.$action.'_2_'.$locale, $id_entry, 2, $mail_invite, array(
			'id_entry' => $id_entry,
			'start_time' => $raw_start_date,
			'end_time' => $raw_end_date,
			'nom_resa' => $breve_description,
			'location' => $room_name,
			'action' => $action,
			'rep_id' => isset($rep_id) ? $rep_id : -1,
			'rep_opt' => isset($rep_opt) ? $rep_opt : -1,
			'rep_type' => isset($rep_type) ? $rep_type : -1,
			'rep_num_weeks' => isset($rep_num_weeks) ? $rep_num_weeks : -1,
			'rep_end_date' => isset($rep_end_date_raw) ? $rep_end_date_raw : -1,
			'rep_month_abs1' => isset($rep_info['rep_month_abs1']) ? $rep_info['rep_month_abs1'] : -1,
			'rep_month_abs2' => isset($rep_info['rep_month_abs2']) ? $rep_info['rep_month_abs2'] : -1,
		));
	}

	/*
	(3) Mail pour le gestionnaire, ou l'admin
	*/
	$envoi3 = false;

    // Cas d'une réservation modérée : le bénéficiaire peut éventuellement la modifier ou supprimer, mais on prévient le modérateur
	// Ou réservation en attente de modération, mail pour le modérateur OU  Notification d'un retard dans la restitution d'une ressource
    if ( (($action == 2 || $action == 3) && ($moderate > 0)) || ($action == 5 || $action == 7))
		$envoi3 = true;

	if($envoi3 == true)
	{
		// Liste des destinataire
		// Raison admin : 1:Gestionnaire de la ressource; 2:Admin du domaine; 3:Admin site; 4:Admins
		list($mail_admin, $raison_admin) = find_active_user_room($room_id);
		if($raison_admin != 0)
			$codes['%raisonmail%'] = $vocab["mail_raison_".$raison_admin];
		else
			$codes['%raisonmail%'] = "";

		$destinataire3 = "";
		if (count($mail_admin) > 0)
		{
			foreach ($mail_admin as $value){
				$destinataire3 .= $value.";";
			}
		}

		if($destinataire3 != "")
		{
			if ($repondre3 == '')
				$repondre3 = $expediteur3;

			$templateMail3 = Pages::get('mails_resa_'.$action.'_3_'.$locale);
			$sujetEncode3 = str_replace(array_keys($codes), $codes, $templateMail3[0]);
			$msgEncode3 = str_replace(array_keys($codes), $codes, $templateMail3[1]);
			Email::Envois($destinataire3, $sujetEncode3, $msgEncode3, $expediteur3, '', '', $repondre3,'mails_resa_'.$action.'_3_'.$locale, $id_entry, 3, $mail_invite, array(
			'id_entry' => $id_entry,
			'start_time' => $raw_start_date,
			'end_time' => $raw_end_date,
			'nom_resa' => $breve_description,
			'location' => $room_name,
			'action' => $action,
			'rep_id' => isset($rep_id) ? $rep_id : -1,
			'rep_opt' => isset($rep_opt) ? $rep_opt : -1,
			'rep_type' => isset($rep_type) ? $rep_type : -1,
			'rep_num_weeks' => isset($rep_num_weeks) ? $rep_num_weeks : -1,
			'rep_end_date' => isset($rep_end_date_raw) ? $rep_end_date_raw : -1,
			'rep_month_abs1' => isset($rep_info['rep_month_abs1']) ? $rep_info['rep_month_abs1'] : -1,
			'rep_month_abs2' => isset($rep_info['rep_month_abs2']) ? $rep_info['rep_month_abs2'] : -1,
			));
		}
	}

	return $message_erreur;
} // Fin fonction send_mail

/** function getUserName()
 * retourne le login de l'utilisateur connecté (et pas son nom), une chaîne vide sinon
*/
function getUserName()
{
	if (isset($_SESSION['login']))
		return $_SESSION['login'];
	else
		return '';
}

// LiensPerso
function liensPerso($emplacement, $statutUser)
{
	$lienPerso = "";

	$res = grr_sql_query("SELECT nom, titre, valeur, systeme, statutmini, lien, nouveauonglet, ordre, emplacement FROM ".TABLE_PREFIX."_page WHERE emplacement = '".$emplacement."' ORDER BY ordre ASC;");
  
	if ($res){
    if (grr_sql_count($res) != 0)
    {
      for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
      {	
        if(
          ($row[4] =='nc') || // NC on affiche à tous
          ($row[4] =='visiteur' && $statutUser >= 1 ) ||
          ($row[4] =='utilisateur' && $statutUser >= 2 ) ||
          ($row[4] =='gestionnaire_utilisateur' && $statutUser >= 3 ) ||
          ($row[4] =='administrateur' && $statutUser >= 6 ) 
        )
        {
          if($lienPerso != "")
            $lienPerso .= " - ";

          $lienPerso .= "<a href='".$row[5]."' ";
            if($row[6] == 1)
              $lienPerso .= "target='_blank'";
          $lienPerso .= ">".$row[1]."</a>";
        }
      }
    }
  }
  else{
    if($debug_flag)
      fatal_error(0, grr_sql_error());
  }
	if($lienPerso != "")
		$lienPerso = "<br>".$lienPerso;
	return $lienPerso;
}

// function UserRoomMaxBooking
// Cette fonction teste si l'utilisateur a la possibilité d'effectuer une réservation, compte tenu
// des limitations éventuelles de la ressource et du nombre de réservations déjà effectuées.
//
function UserRoomMaxBooking($user, $id_room, $number)
{
	global $enable_periods,$id_room_autorise;
	$level = SecuAccess::UserLevel($user,$id_room);
	if ($id_room == '')
		return 0;
	if ($level >= 3)
		return 1;
	else if (($level == 1 ) &&  !((in_array($id_room,$id_room_autorise)) && ($id_room_autorise != "")))
		return 0;
	else if ($level  < 1 )
		return 0;
	// A ce niveau, l'utilisateur est simple utilisateur ou bien simple visiteur sur un domaine autorisé
	// On regarde si le nombre de réservation de la ressource est limité
	$max_booking_per_room = grr_sql_query1("SELECT max_booking FROM ".TABLE_PREFIX."_room WHERE id = '".SecuChaine::ProtectDataSql($id_room)."'");
	// Calcul de l'id de l'area de la ressource.
	$id_area = mrbsGetRoomArea($id_room);
	// On regarde si le nombre de réservation du domaine est limité
	$max_booking_per_area = grr_sql_query1("SELECT max_booking FROM ".TABLE_PREFIX."_area WHERE id = '".SecuChaine::ProtectDataSql($id_area)."'");
	// On regarde si le nombre de réservation pour l'ensemble des ressources est limité
	$max_booking = Settings::get("UserAllRoomsMaxBooking");
	// Si aucune limitation
	if (($max_booking_per_room < 0) && ($max_booking_per_area < 0) && ($max_booking < 0))
		return 1;
	// A ce niveau, il s'agit d'un utilisateur et il y a au moins une limitation
	$day   = date("d");
	$month = date("m");
	$year  = date("Y");
	$hour  = date("H");
	$minute = date("i");
	if ($enable_periods == 'y')
		$now = mktime(0, 0, 0, $month, $day, $year);
	else
		$now = mktime($hour, $minute, 0, $month, $day, $year);
	// y-a-t-il dépassement pour l'ensemble des ressources ?
	if ($max_booking > 0)
	{
		$nb_bookings = grr_sql_query1("SELECT count(id) FROM ".TABLE_PREFIX."_entry r WHERE (beneficiaire = '".SecuChaine::ProtectDataSql($user)."' and end_time > '$now') AND supprimer=0");
		$nb_bookings += $number;
		if ($nb_bookings > $max_booking)
			return 0;
	}
	else if ($max_booking == 0)
		return 0;
	// y-a-t-il dépassement pour l'ensemble des ressources du domaine ?
	if ($max_booking_per_area > 0)
	{
		$nb_bookings = grr_sql_query1("SELECT count(e.id) FROM ".TABLE_PREFIX."_entry e, ".TABLE_PREFIX."_room r WHERE (e.room_id=r.id and r.area_id='".$id_area."' and e.beneficiaire = '".SecuChaine::ProtectDataSql($user)."' and e.end_time > '$now') AND e.supprimer=0");
		$nb_bookings += $number;
		if ($nb_bookings > $max_booking_per_area)
			return 0;
	}
	else if ($max_booking_per_area == 0)
		return 0;
	// y-a-t-il dépassement pour la ressource
	if ($max_booking_per_room > 0)
	{
		$nb_bookings = grr_sql_query1("SELECT count(id) FROM ".TABLE_PREFIX."_entry WHERE (room_id = '".SecuChaine::ProtectDataSql($id_room)."' and beneficiaire = '".SecuChaine::ProtectDataSql($user)."' and end_time > '$now') AND supprimer=0");
		$nb_bookings += $number;
		if ($nb_bookings > $max_booking_per_room)
			return 0;
	}
	else if ($max_booking_per_room == 0)
		return 0;
	// A ce stade, il s'agit d'un utilisateur et il n'y a pas eu de dépassement, ni pour l'ensemble des domaines, ni pour le domaine, ni pour la ressource
	return 1;
}
// function UserRoomMaxBookingRange
// Cette fonction teste si l'utilisateur $user a la possibilité d'effectuer une réservation, compte tenu
// des limitations éventuelles de la ressource $id_room et du nombre $number de réservations à effectuer, sans que le quota défini sur l'intervalle [$start_time - $range, $start_time] dépasse la limite.
//
/*
function UserRoomMaxBookingRange($user, $id_room, $number, $start_time)
{
	global $enable_periods,$id_room_autorise;
	$level = SecuAccess::UserLevel($user,$id_room);
	if ($id_room == '')
		return 0;
	if ($level >= 3)
		return 1;
	else if (($level == 1 ) &&  !((in_array($id_room,$id_room_autorise)) && ($id_room_autorise != "")))
		return 0;
	else if ($level  < 1 )
		return 0;
	// A ce niveau, l'utilisateur est simple utilisateur ou bien simple visiteur sur un domaine autorisé
	// On regarde si le nombre de réservation de la ressource est limité
	$max_booking_per_room = grr_sql_query1("SELECT max_booking FROM ".TABLE_PREFIX."_room WHERE id = '".SecuChaine::ProtectDataSql($id_room)."'");
    // limitation dans le temps
    $booking_range = grr_sql_query1("SELECT booking_range FROM ".TABLE_PREFIX."_room WHERE id = '".SecuChaine::ProtectDataSql($id_room)."'"); // jours
    $min_int = $start_time - $booking_range * 86400 ;// approximatif, mais devrait être convenable
	// Calcul de l'id de l'area de la ressource.
	$id_area = mrbsGetRoomArea($id_room);
	// On regarde si le nombre de réservation du domaine est limité
	$max_booking_per_area = grr_sql_query1("SELECT max_booking FROM ".TABLE_PREFIX."_area WHERE id = '".SecuChaine::ProtectDataSql($id_area)."'");
	// On regarde si le nombre de réservation pour l'ensemble des ressources est limité
	$max_booking = Settings::get("UserAllRoomsMaxBooking");
	// Si aucune limitation
	if (($max_booking_per_room < 0) && ($max_booking_per_area < 0) && ($max_booking < 0))
		return 1;
	// A ce niveau, il s'agit d'un utilisateur et il y a au moins une limitation
	$day   = date("d");
	$month = date("m");
	$year  = date("Y");
	$hour  = date("H");
	$minute = date("i");
	if ($enable_periods == 'y')
		$now = mktime(0, 0, 0, $month, $day, $year);
	else
		$now = mktime($hour, $minute, 0, $month, $day, $year);
	// y-a-t-il dépassement pour l'ensemble des ressources ?
	if ($max_booking > 0)
	{
		$nb_bookings = grr_sql_query1("SELECT count(id) FROM ".TABLE_PREFIX."_entry r WHERE (beneficiaire = '".SecuChaine::ProtectDataSql($user)."' and end_time > '$now') AND supprimer=0");
		$nb_bookings += $number;
		if ($nb_bookings > $max_booking)
			return 0;
	}
	else if ($max_booking == 0)
		return 0;
	// y-a-t-il dépassement pour l'ensemble des ressources du domaine ?
	if ($max_booking_per_area > 0)
	{
		$nb_bookings = grr_sql_query1("SELECT count(e.id) FROM ".TABLE_PREFIX."_entry e, ".TABLE_PREFIX."_room r WHERE (e.room_id=r.id and r.area_id='".$id_area."' and e.beneficiaire = '".SecuChaine::ProtectDataSql($user)."' and e.end_time > '$now') AND e.supprimer=0");
		$nb_bookings += $number;
		if ($nb_bookings > $max_booking_per_area)
			return 0;
	}
	else if ($max_booking_per_area == 0)
		return 0;
	// y-a-t-il dépassement pour la ressource
	if ($max_booking_per_room > 0)
	{
		$nb_bookings = grr_sql_query1("SELECT count(id) FROM ".TABLE_PREFIX."_entry WHERE (room_id = '".SecuChaine::ProtectDataSql($id_room)."' and beneficiaire = '".SecuChaine::ProtectDataSql($user)."' and end_time > '$now') AND supprimer=0");
		$nb_bookings += $number;
		if ($nb_bookings > $max_booking_per_room)
			return 0;
	}
	else if ($max_booking_per_room == 0)
		return 0;
    // limitation sur l'intervalle
    if ($booking_range > 0 ){
        $nb_bookings = grr_sql_query1("SELECT count(id) FROM ".TABLE_PREFIX."_entry WHERE (
        room_id = '".SecuChaine::ProtectDataSql($id_room)."'
        AND beneficiaire = '".SecuChaine::ProtectDataSql($user)."'
        AND end_time > '".$now."'
        AND start_time > '".min_int."') AND supprimer=0");
        $nb_bookings += $number;
        if ($nb_bookings > $max_booking_per_room)
            return 0;
    }
	// A ce stade, il s'agit d'un utilisateur et il n'y a pas eu de dépassement, ni pour l'ensemble des domaines, ni pour le domaine, ni pour la ressource, ni sur l'intervalle de temps
	return 1;
}*/

/* function verif_booking_date($user, $id, $id_room, $date_booking, $date_now, $enable_periods, $endtime = '')
 $user : le login de l'utilisateur
 $id : l'id de la résa. Si -1, il s'agit d'une nouvelle réservation
 $id_room : id de la ressource
 $date_booking : la date de la réservation (n'est utile que si $id=-1)
 $date_now : la date actuelle
*/
 /**
  * @param string $date_booking
  * @param integer $date_now
  */
 function verif_booking_date($user, $id, $id_room, $date_booking, $date_now, $enable_periods, $endtime = '')
 {
 	global $correct_diff_time_local_serveur, $can_delete_or_create;
 	$can_delete_or_create = "y";
	// On teste si l'utilisateur est administrateur
 	$sql = "SELECT statut FROM ".TABLE_PREFIX."_utilisateurs WHERE login = '".SecuChaine::ProtectDataSql($user)."'";
 	$statut_user = grr_sql_query1($sql);
 	if ($statut_user == 'administrateur')
 		return true;
	// A-t-on le droit d'agir dans le passé ?
 	$allow_action_in_past = grr_sql_query1("SELECT allow_action_in_past FROM ".TABLE_PREFIX."_room WHERE id = '".SecuChaine::ProtectDataSql($id_room)."'");
 	if ($allow_action_in_past == 'y')
 		return true;
	// Correction de l'avance en nombre d'heure du serveur sur les postes clients
 	if ((isset($correct_diff_time_local_serveur)) && ($correct_diff_time_local_serveur!=0))
 		$date_now -= 3600 * $correct_diff_time_local_serveur;
	// Créneaux basés sur les intitulés
	// Dans ce cas, on prend comme temps présent le jour même à minuit.
	// Cela signifie qu'il est possible de modifier/réserver/supprimer tout au long d'une journée
	// même si l'heure est passée.
	// Cela demande donc à être améliorer en introduisant pour chaque créneau une heure limite de réservation.
 	if ($enable_periods == "y")
 	{
 		$month = date("m",$date_now);
 		$day = date("d",$date_now);
 		$year = date("Y",$date_now);
 		$date_now = mktime(0, 0, 0, $month, $day, $year);
 	}
 	if ($id != -1)
 	{
		// il s'agit de l'edition d'une réservation existante
 		if (($endtime != '') && ($endtime < $date_now))
 			return false;
 		if ((Settings::get("allow_user_delete_after_begin") == 1) || (Settings::get("allow_user_delete_after_begin") == 2))
 			$sql = "SELECT end_time FROM ".TABLE_PREFIX."_entry WHERE id = '".SecuChaine::ProtectDataSql($id)."'";
 		else
 			$sql = "SELECT start_time FROM ".TABLE_PREFIX."_entry WHERE id = '".SecuChaine::ProtectDataSql($id)."'";
 		$date_booking = grr_sql_query1($sql);
 		if ($date_booking < $date_now)
 			return false;
 		else
 		{
			// dans le cas où le créneau est entamé, on teste si l'utilisateur a le droit de supprimer la réservation
			// Si oui, on transmet la variable $only_modify = true avant que la fonction de retourne true.
 			if (Settings::get("allow_user_delete_after_begin") == 2)
 			{
 				$date_debut = grr_sql_query1("SELECT start_time FROM ".TABLE_PREFIX."_entry WHERE id = '".SecuChaine::ProtectDataSql($id)."'");
 				if ($date_debut < $date_now)
 					$can_delete_or_create = "n";
 				else
 					$can_delete_or_create = "y";
 			}
 			return true;
 		}
 	}
 	else
 	{
 		if (Settings::get("allow_user_delete_after_begin") == 1)
 		{
 			$id_area = grr_sql_query1("select area_id from ".TABLE_PREFIX."_room WHERE id = '".SecuChaine::ProtectDataSql($id_room)."'");
 			$resolution_area = grr_sql_query1("select resolution_area from ".TABLE_PREFIX."_area WHERE id = '".$id_area."'");
 			if ($date_booking > $date_now - $resolution_area)
 				return true;
 			return false;
 		}
 		else
 		{
 			if ($date_booking > $date_now)
 				return true;
 			return false;
 		}
 	}
 }
// function verif_duree_max_resa_area($user, $id_room, $starttime, $endtime)
// $user : le login de l'utilisateur
// $id_room : l'id de la ressource. Si -1, il s'agit d'une nouvelle ressource.
// $starttime : début de la réservation
// $endtime : fin de la réservation
 function verif_duree_max_resa_area($user, $id_room, $starttime, $endtime)
 {
 	if (SecuAccess::UserLevel($user,$id_room) >= 3)
 		return true;
 	$id_area = grr_sql_query1("SELECT area_id from ".TABLE_PREFIX."_room WHERE id='".SecuChaine::ProtectDataSql($id_room)."'");
 	$duree_max_resa_area = grr_sql_query1("SELECT duree_max_resa_area from ".TABLE_PREFIX."_area WHERE id='".$id_area."'");
 	$enable_periods =  grr_sql_query1("SELECT enable_periods from ".TABLE_PREFIX."_area WHERE id='".$id_area."'");
 	if ($enable_periods == 'y')
 		$duree_max_resa_area = $duree_max_resa_area * 24 * 60;
 	if ($duree_max_resa_area < 0)
 		return true;
 	else if ($endtime - $starttime > $duree_max_resa_area * 60)
 		return false;
 	return true;
 }
// function verif_delais_max_resa_room($user, $id_room, $date_booking)
// $user : le login de l'utilisateur
// $id_room : l'id de la ressource. Si -1, il s'agit d'une nouvelle ressoure
// $date_booking : la date de la réservation (n'est utile que si $id=-1)
// $date_now : la date actuelle
 function verif_delais_max_resa_room($user, $id_room, $date_booking)
 {
 	$day   = date("d");
 	$month = date("m");
 	$year  = date("Y");
 	$datenow = mktime(0, 0, 0, $month, $day, $year);
 	if (SecuAccess::UserLevel($user,$id_room) >= 3)
 		return true;
 	$delais_max_resa_room = grr_sql_query1("SELECT delais_max_resa_room FROM ".TABLE_PREFIX."_room WHERE id='".SecuChaine::ProtectDataSql($id_room)."'");
	if ($delais_max_resa_room == -1)
 		return true;
 	else if ($datenow + $delais_max_resa_room * 24 * 3600 + 1 < $date_booking)
 		return false;
 	return true;
 }



/* function verif_participation_date($user, $id, $date_booking, $date_now)
 $user : le login de l'utilisateur
 $id : l'id de la résa. Si -1, il s'agit d'une nouvelle réservation
 $id_room : id de la ressource
 $date_booking : la date de la réservation (n'est utile que si $id=-1)
 $date_now : la date actuelle
*/
 /**
  * @param string $date_booking
  * @param integer $date_now
  */
 function verif_participation_date($user, $id, $id_room, $date_booking, $date_now, $enable_periods, $endtime = '')
 {
 	global $correct_diff_time_local_serveur, $can_delete_or_create;
 	$can_delete_or_create = "y";
	// On teste si l'utilisateur est administrateur
 	$sql = "SELECT statut FROM ".TABLE_PREFIX."_utilisateurs WHERE login = '".SecuChaine::ProtectDataSql($user)."'";
 	$statut_user = grr_sql_query1($sql);
 	if ($statut_user == 'administrateur')
 		return true;
	// Correction de l'avance en nombre d'heure du serveur sur les postes clients
 	if ((isset($correct_diff_time_local_serveur)) && ($correct_diff_time_local_serveur!=0))
 		$date_now -= 3600 * $correct_diff_time_local_serveur;
	// Créneaux basés sur les intitulés
	// Dans ce cas, on prend comme temps présent le jour même à minuit.
	// Cela signifie qu'il est possible de modifier/réserver/supprimer tout au long d'une journée
	// même si l'heure est passée.
	// Cela demande donc à être améliorer en introduisant pour chaque créneau une heure limite de réservation.
 	if ($enable_periods == "y")
 	{
 		$month = date("m",$date_now);
 		$day = date("d",$date_now);
 		$year = date("Y",$date_now);
 		$date_now = mktime(0, 0, 0, $month, $day, $year);
 	}
 	if ($id != -1)
 	{
		// il s'agit de l'edition d'une réservation existante
 		if (($endtime != '') && ($endtime < $date_now))
 			return false;
 		if ((Settings::get("allow_user_delete_after_begin") == 1) || (Settings::get("allow_user_delete_after_begin") == 2))
 			$sql = "SELECT end_time FROM ".TABLE_PREFIX."_entry WHERE id = '".SecuChaine::ProtectDataSql($id)."'";
 		else
 			$sql = "SELECT start_time FROM ".TABLE_PREFIX."_entry WHERE id = '".SecuChaine::ProtectDataSql($id)."'";
 		$date_booking = grr_sql_query1($sql);
 		if ($date_booking < $date_now)
 			return false;
 		else
 		{
			// dans le cas où le créneau est entamé, on teste si l'utilisateur a le droit de supprimer la réservation
			// Si oui, on transmet la variable $only_modify = true avant que la fonction de retourne true.
 			if (Settings::get("allow_user_delete_after_begin") == 2)
 			{
 				$date_debut = grr_sql_query1("SELECT start_time FROM ".TABLE_PREFIX."_entry WHERE id = '".SecuChaine::ProtectDataSql($id)."'");
 				if ($date_debut < $date_now)
 					$can_delete_or_create = "n";
 				else
 					$can_delete_or_create = "y";
 			}
 			return true;
 		}
 	}
 	else
 	{
 		if (Settings::get("allow_user_delete_after_begin") == 1)
 		{
 			$id_area = grr_sql_query1("select area_id from ".TABLE_PREFIX."_room WHERE id = '".SecuChaine::ProtectDataSql($id_room)."'");
 			$resolution_area = grr_sql_query1("select resolution_area from ".TABLE_PREFIX."_area WHERE id = '".$id_area."'");
 			if ($date_booking > $date_now - $resolution_area)
 				return true;
 			return false;
 		}
 		else
 		{
 			if ($date_booking > $date_now)
 				return true;
 			return false;
 		}
 	}
 }

 

// function verif_delais_min_resa_room($user, $id_room, $date_booking)
// $user : le login de l'utilisateur
// $id_room : l'id de la ressource. Si -1, il s'agit d'une nouvelle ressoure
// $date_booking : la date de la réservation (n'est utile que si $id=-1)
// $enable_periods : base temps (par défaut) ou créneaux
// renvoie vrai ou faux selon que le délai est respecté
//
function verif_delais_min_resa_room($user, $id_room, $date_booking, $enable_periods = 'n')
{
	if (SecuAccess::UserLevel($user,$id_room) >= 3)
		return true;
	$delais_min_resa_room = grr_sql_query1("SELECT delais_min_resa_room FROM ".TABLE_PREFIX."_room WHERE id='".SecuChaine::ProtectDataSql($id_room)."'");
	if ($delais_min_resa_room == 0)
		return true;
	else
	{
        $area = mrbsGetRoomArea($id_room);
		$hour = date("H");
		$minute  = date("i") + $delais_min_resa_room;
		$day   = date("d");
		$month = date("m");
		$year  = date("Y");
        $date_limite = mktime($hour, $minute, 0, $month, $day, $year);
        $limite = getdate($date_limite);
        if ($enable_periods == 'y'){
            $date_limite = mktime(0,0,0,$limite['mon'],$limite['mday'],$limite['year']);
            $limite['hours'] = 0;
            $limite['minutes'] = 0;
        }
        //echo "day_limite".$limite['mday']."<br/>";
        //echo "date_booking".strftime("%c",$date_booking)."<br/>";
        $day_limite = $limite['mday'];
        if (($limite['mday'] != $day)&&(Settings::get('delai_ouvert') == 1)){// jour différent et test pour jours ouvrés ?
            $cur_day = mktime(0,0,0,$month,$day,$year);
            while ($cur_day < $date_booking){
                //echo "jour".strftime("%c",$cur_day)."<br/>";
                //echo "limit".strftime('%c',$date_limite)."<br/>";
                if (est_hors_reservation($cur_day,$area)){// teste si le jour est hors réservation et dans ce cas allonge le délai
                    $day_limite++;
                    $date_limite = mktime($limite['hours'],$limite['minutes'],0,$limite['mon'],$day_limite,$limite['year']);
                    //echo "testé<br/>";
                }
                $day ++;
                $cur_day = mktime(0,0,0,$month,$day,$year);
            }
        }
		if ($date_limite > $date_booking)
			return false;
		return true;
	}
}
// Vérifie que la date de confirmation est inférieure à la date de début de réservation
function verif_date_option_reservation($option_reservation, $starttime)
{
	if ($option_reservation == -1)
		return true;
	else
	{
		$day   = date("d", $starttime);
		$month = date("m", $starttime);
		$year  = date("Y", $starttime);
		$date_starttime = mktime(0, 0, 0, $month, $day, $year);
		if ($option_reservation < $date_starttime)
			return true;
		return false;
	}
}
// Vérifie que $_create_by peut réserver la ressource $_room_id pour $_beneficiaire
function verif_qui_peut_reserver_pour($_room_id, $_create_by, $_beneficiaire)
{
	if ($_beneficiaire == "")
		return true;
	if (strtolower($_create_by) == strtolower($_beneficiaire))
		return true;
	$qui_peut_reserver_pour  = grr_sql_query1("SELECT qui_peut_reserver_pour FROM ".TABLE_PREFIX."_room WHERE id='".$_room_id."'");
	if (SecuAccess::UserLevel($_create_by, $_room_id) >= $qui_peut_reserver_pour)
		return true;
	return false;
}
/*
function verif_heure_debut_fin($start_time,$end_time,$area)
Vérifie si l'heure de début ou l'heure de fin de réservation est en dehors des créneaux autorisés.
*/
function verif_heure_debut_fin($start_time,$end_time,$area)
{
	global $enable_periods, $resolution, $morningstarts, $eveningends, $eveningends_minutes;
		// Récupération des données concernant l'affichage du planning du domaine
	get_planning_area_values($area);
		// On ne traite pas le cas des plannings basés sur les intitulés prédéfinis
	if ($enable_periods != "y")
	{
		$day = date("d",$start_time);
		$month = date("m",$start_time);
		$year = date("Y",$start_time);
		$startday = mktime($morningstarts, 0, 0, $month, $day  , $year);
		$day = date("d",$end_time);
		$month = date("m",$end_time);
		$year = date("Y",$end_time);
		$endday = mktime($eveningends, $eveningends_minutes , $resolution, $month, $day, $year);
		if ($start_time < $startday)
			return false;
		else if ($end_time > $endday)
			return false;
	}
	return true;
}
/* VerifyModeDemo()
 *
 * Affiche une page "opération non autorisée" pour certaines opérations dans le cas le mode demo est activé.
 *
 * Returns: Nothing
 */
function VerifyModeDemo($force = true) {
	if (Settings::get("ActiveModeDemo") == 1)
	{
		if($force == true)
		{
			//print_header_twig();
			echo "<h1>Opération non autorisée</h1>
			<p>Vous êtes dans une <b>version de démonstration de GRR</b>.
				<br />Certaines fonctions ont été volontairement bridées. C'est le cas pour l'opération que vous avez tenté d'effectuer.</p>
			</body></html>";
			die();
		}
		else // Cas dans certaines fonctions (Paramètres settings) sont bridées mais pas toutes, on retourne juste false pour que la fonction appelante puisse gérer le cas.
		{
			return true;
		}
	}
}
/* MajMysqlModeDemo()
 * dans le cas le mode demo est activé :
 * Met à jour la base mysql une fois par jour, lors de la première connexion
 *
 */
function MajMysqlModeDemo() {
	global $vocab;
		// Nom du fichier sql à exécuter
	$fic_sql = "grr_maj_quotidienne.sql";
	if ((Settings::get("ActiveModeDemo") == 1) && (file_exists($fic_sql)))
	{
		$date_now = mktime(0,0,0,date("m"),date("d"),date("Y"));
		if ((Settings::get("date_verify_demo") == "") || (Settings::get("date_verify_demo") < $date_now))
		{
			$fd = fopen($fic_sql, "r");
			while (!feof($fd))
			{
				$query = fgets($fd, 5000);
				$query = trim($query);
				if ($query != '')
					@mysqli_query($GLOBALS['db_c'], $query);
			}
			fclose($fd);
			if (!Settings::set("date_verify_demo", $date_now))
			{
				echo $vocab['save_err']." date_verify_demo !<br />";
				die();
			}
		}
	}
}
/* showAccessDenied()
 *
 * Displays an appropriate message when access has been denied
 *
 * Returns: Nothing
 */
function showAccessDenied($back, $infodebug = '')
{
	global $vocab, $debug_flag;
	echo '<h1>'.get_vocab("accessdenied").'</h1>';
	echo '<p>'.get_vocab("norights").'</p>';
	if($debug_flag)
		echo '<p>'.$infodebug.'</p>';
	echo '<p><a href="'.SecuChaine::UrlInt($back).'">'.get_vocab("returnprev").'</a></p>';
	echo '</section></body></html>';
}
function showAccessDenied_twig($back, $infodebug = '')
{
	global $vocab, $debug_flag;
	$html = '<h1>'.get_vocab("accessdenied").'</h1>';
	$html .= '<p>'.get_vocab("norights").'</p>';
	if($debug_flag)
		$html .= '<p>'.$infodebug.'</p>';
	$html .= '<p><a href="'.SecuChaine::UrlInt($back).'">'.get_vocab("returnprev").'</a></p>';

	return $html;
}
/* showNoReservation()
 *
 * Displays an appropriate message when reservation does not exist
 *
 * Returns: Nothing
 */
function showNoReservation($day, $month, $year, $back)
{

	$html = '<h1>'.get_vocab("accessdenied").'</h1>';
	$html .= '<p>'.get_vocab("noreservation").'</p>';
	$html .= '<p><a href="'.$back.'">'.get_vocab("returnprev").'</a></p>';
    
	return $html;
}
/* showAccessDeniedMaxBookings()
 *
 * Displays an appropriate message when access has been denied because of overbooking
 *
 * Returns: Nothing
 */
function showAccessDeniedMaxBookings($day, $month, $year, $id_room, $back)
{
	global $vocab;
	print_header($day, $month, $year, $type="with_session");
	echo '<h1>'.get_vocab("accessdenied").'</h1>';
	echo '<p>';
		// Limitation par ressource
		$max_booking_per_room = grr_sql_query1("SELECT max_booking FROM ".TABLE_PREFIX."_room WHERE id='".SecuChaine::ProtectDataSql($id_room)."'");
		if ($max_booking_per_room >= 0)
			echo get_vocab("msg_max_booking").get_vocab("deux_points").$max_booking_per_room."<br />";
		// Calcul de l'id de l'area de la ressource.
		$id_area = mrbsGetRoomArea($id_room);
		// Limitation par domaine
		$max_booking_per_area = grr_sql_query1("SELECT max_booking FROM ".TABLE_PREFIX."_area WHERE id = '".SecuChaine::ProtectDataSql($id_area)."'");
		if ($max_booking_per_area >= 0)
			echo get_vocab("msg_max_booking_area").get_vocab("deux_points").$max_booking_per_area."<br />";
		// Limitation sur l'ensemble des ressources
		$max_booking_all = Settings::get("UserAllRoomsMaxBooking");
		if ($max_booking_all >= 0)
			echo get_vocab("msg_max_booking_all").get_vocab("deux_points").$max_booking_all."<br />";
		echo "<br />".get_vocab("accessdeniedtoomanybooking").
	'</p>
	<p>
		<a href="'.$back.'" >'.get_vocab("returnprev").'</a>
	</p>
</body>
</html>';
}
function showAccessDeniedMaxBookings_twig($day, $month, $year, $id_room, $back)
{
	global $vocab;
	$html = '<h1>'.get_vocab("accessdenied").'</h1>';
	$html .= '<p>';
		// Limitation par ressource
		$max_booking_per_room = grr_sql_query1("SELECT max_booking FROM ".TABLE_PREFIX."_room WHERE id='".SecuChaine::ProtectDataSql($id_room)."'");
		if ($max_booking_per_room >= 0)
			$html .= get_vocab("msg_max_booking").get_vocab("deux_points").$max_booking_per_room."<br />";
		// Calcul de l'id de l'area de la ressource.
		$id_area = mrbsGetRoomArea($id_room);
		// Limitation par domaine
		$max_booking_per_area = grr_sql_query1("SELECT max_booking FROM ".TABLE_PREFIX."_area WHERE id = '".SecuChaine::ProtectDataSql($id_area)."'");
		if ($max_booking_per_area >= 0)
			$html .= get_vocab("msg_max_booking_area").get_vocab("deux_points").$max_booking_per_area."<br />";
		// Limitation sur l'ensemble des ressources
		$max_booking_all = Settings::get("UserAllRoomsMaxBooking");
		if ($max_booking_all >= 0)
			$html .= get_vocab("msg_max_booking_all").get_vocab("deux_points").$max_booking_all."<br />";
		$html .= "<br />".get_vocab("accessdeniedtoomanybooking");
	$html .='</p>
	<p>
		<a href="'.$back.'" >'.get_vocab("returnprev").'</a>
	</p>';
	return $html;
}
/* fonction qui rend TRUE lorsque la date proposée est en dehors de la période réservable
*/
function check_begin_end_bookings($day, $month, $year)
{
	$date = mktime(0,0,0,$month,$day,$year);
	if (($date < Settings::get("begin_bookings")) || ($date > Settings::get("end_bookings")))
		return -1;
}
function showNoBookings_twig($day, $month, $year, $back)
{
	global $vocab;
	$date = mktime(0, 0, 0, $month, $day,$year);
	$html = '<h2>'.get_vocab("nobookings").' '.affiche_date($date).'</h2>';
	$html .= '<p>'.get_vocab("begin_bookings").'<b>'.affiche_date(Settings::get("begin_bookings")).'</b></p>';
	$html .= '<p>'.get_vocab("end_bookings").'<b>'.affiche_date(Settings::get("end_bookings")).'</b></p>';
    $html .= "<p>";
        if ($back !=''){
            $html .= "<a href=".$back.">".get_vocab('returnprev')."</a>";
        }
		$html .= "</p>";

	return $html;
}
/* donne, pour un format français, un résultat de la forme lundi 30 sept.19:17:32
*/
function date_time_string($t, $dformat)
{
	global $twentyfourhour_format;
	if ($twentyfourhour_format)
		$timeformat = "%T";
	else
	{
		$ampm = date("a",$t);
		$timeformat = "%I:%M$ampm";
	}
	return utf8_strftime($dformat." ".$timeformat, $t);
}
# Convertit un créneau de début et de fin en un tableau donnant date, créneau de début et durée
function describe_period_span($starts, $ends)
{
	global $enable_periods, $periods_name, $vocab, $duration;
	list($start_period, $start_date) =  period_date_string($starts);
	list($periodedebut, $datedebut) =  period_date_string_rapport($starts);
	list($periodefin, $datefin) =  period_date_string_rapport($ends);
	list( , $end_date) =  period_date_string($ends, -1);
	$duration = $ends - $starts;
	toPeriodString($start_period, $duration, $dur_units);
	if ($duration > 1)
	{
		list( , $start_date) =  period_date_string($starts);
		list( , $end_date) =  period_date_string($ends, -1);
	}
	return array($datedebut, $periodedebut, $duration, $dur_units, $datefin, $periodefin);
}
#Convertit l'heure de début et de fin en un tableau donnant date, heure de début et durée.
function describe_span($starts, $ends, $dformat)
{
	global $vocab, $twentyfourhour_format;
	$start_date = utf8_strftime($dformat, $starts);
	$end_date = utf8_strftime($dformat, $ends);
	if ($twentyfourhour_format)
	{
		$timeformatStart = "%T";
		$timeformatEnds = "%T";
	}
	else
	{
		$ampmStart = date("a",$starts);
		$timeformatStart = "%I:%M$ampmStart";
		$ampmEnds= date("a",$ends);
		$timeformatEnds = "%I:%M$ampmEnds";
	}
	$start_time = utf8_strftime($timeformatStart, $starts);
	$end_time = utf8_strftime($timeformatEnds, $ends);
	$duration = $ends - $starts;
	toTimeString($duration, $dur_units);
	return array($start_date, $start_time ,$duration, $dur_units, $end_date, $end_time);
}

// Récupère les données concernant l'affichage du planning du domaine
function get_planning_area_values($id_area)
{
	global $resolution, $morningstarts, $eveningends, $eveningends_minutes, $weekstarts, $twentyfourhour_format, $enable_periods, $periods_name, $display_day, $nb_display_day, $description_breve, $description_complete;
	$sql = "SELECT calendar_default_values, resolution_area, morningstarts_area, eveningends_area, eveningends_minutes_area, weekstarts_area, twentyfourhour_format_area, enable_periods, display_days, description_breve, description_complete 
	FROM ".TABLE_PREFIX."_area
	WHERE id = '".SecuChaine::ProtectDataSql($id_area)."'";
	$res = grr_sql_query($sql);
	if (!$res)
	{
		// fatal_error(0, grr_sql_error());
		include "trailer.inc.php";
		exit;
	}
	$row_ = grr_sql_row($res, 0);
	$nb_display_day = 0;
	$description_breve = $row_[9];
	$description_complete = $row_[10];
	for ($i = 0; $i < 7; $i++)
	{
		if (substr($row_[8],$i,1) == 'y')
		{
			$display_day[$i] = 1;
			$nb_display_day++;
		}
		else
			$display_day[$i] = 0;
	}
	if ($row_[7] == 'y')	// Créneaux basés sur les intitulés
	{
		$resolution = 60;
		$morningstarts = 12;
		$eveningends = 12;
		$sql_periode = grr_sql_query("SELECT nom_periode FROM ".TABLE_PREFIX."_area_periodes where id_area='".$id_area."'");
		$eveningends_minutes = grr_sql_count($sql_periode) - 1;
		$i = 0;
		while ($i <= $eveningends_minutes)
		{
			$periods_name[$i] = grr_sql_query1("SELECT nom_periode FROM ".TABLE_PREFIX."_area_periodes where id_area='".$id_area."' and num_periode= '".$i."'");
			$i++;
		}
		$enable_periods = "y";
		$weekstarts = $row_[5];
		$twentyfourhour_format = $row_[6];
	}
	else		// Créneaux basés sur le temps
	{
		if ($row_[0] != 'y')
		{
			$resolution = $row_[1];
			$morningstarts = $row_[2];
			$eveningends = $row_[3];
			$eveningends_minutes = $row_[4];
			$enable_periods = "n";
			$weekstarts = $row_[5];
			$twentyfourhour_format = $row_[6];
		}
	}
}
// Dans le cas ou $unicode_encoding = 1 (UTF-8) cette fonction encode les chaînes présentes dans
// le code "en dur", en UTF-8 avant affichage
function encode_message_utf8($tag)
{
	global $charset_html, $unicode_encoding;
	if ($unicode_encoding)
		return iconv($charset_html,"utf-8",$tag);
	else
		return $tag;
}
/**
 * @param string $string
 * @return string
 */
function removeMailUnicode($string)
{
	global $unicode_encoding, $charset_html;
	if ($unicode_encoding)
		return @iconv("utf-8", $charset_html, $string);
	else
		return $string;
}
// Cette fonction vérifie une fois par jour si le délai de confirmation des réservations est dépassé
// Si oui, les réservations concernées sont supprimées et un mail automatique est envoyé.
function verify_confirm_reservation()
{
	global $dformat;
	$day   = date("d");
	$month = date("m");
	$year  = date("Y");
	$date_now = mktime(0,0,0,$month,$day,$year);
	if ((Settings::get("date_verify_reservation") == "") || (Settings::get("date_verify_reservation") < $date_now ))
	{
		$res = grr_sql_query("select id from ".TABLE_PREFIX."_room where delais_option_reservation > 0");
		if (!$res)
		{
			//fatal_error(0, grr_sql_error());
			include "trailer.inc.php";
			exit;
		}
		else
		{
			for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
			{
				$res2 = grr_sql_query("select id from ".TABLE_PREFIX."_entry where option_reservation < '".$date_now."' and option_reservation != '-1' and room_id='".$row[0]."' and supprimer != 1");
				if (!$res2)
				{
					//fatal_error(0, grr_sql_error());
					include "trailer.inc.php";
					exit;
				}
				else
				{
					for ($j = 0; ($row2 = grr_sql_row($res2, $j)); $j++)
					{
						if (Settings::get("automatic_mail") == 1)
							$_SESSION['session_message_error'] = send_mail($row2[0],4,$dformat);
						// On efface la réservation
						grr_sql_command("UPDATE ".TABLE_PREFIX."_entry SET supprimer = 1 WHERE id=" . $row2[0]);
						insertLogResa($row2[0], 5, "Délai de confirmation est dépassé");
						// On efface le cas écheant également  dans ".TABLE_PREFIX."_entry_moderate
						grr_sql_command("DELETE FROM ".TABLE_PREFIX."_entry_moderate WHERE id=" . $row2[0]);
						grr_sql_command("DELETE FROM ".TABLE_PREFIX."_participants WHERE idresa=" . $row2[0]);
					}
				}
			}
		}
		if (!Settings::set("date_verify_reservation", $date_now))
		{
			echo $vocab['save_err']." date_verify_reservation !<br />";
			die();
		}
	}
}
// Cette fonction vérifie une fois par jour si les réservations devant être rendus ne sont pas
// en retard
// Si oui, les utilisateurs concernées recoivent un mail automatique pour leur notifier.
function verify_retard_reservation()
{
	global $dformat;
	$day   = date("d");
	$month = date("m");
	$year  = date("Y");
	$date_now = mktime(0, 0, 0, $month, $day, $year);
	if (((Settings::get("date_verify_reservation2") == "") || (Settings::get("date_verify_reservation2") < $date_now )) && (Settings::get("automatic_mail") == 1))
	{
		$res = grr_sql_query("SELECT id FROM ".TABLE_PREFIX."_room");
		if (!$res)
		{
			include "trailer.inc.php";
			exit;
		}
		else
		{
			for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
			{
				$res2 = grr_sql_query("SELECT id from ".TABLE_PREFIX."_entry WHERE statut_entry='e' AND end_time < '".$date_now."' AND room_id='".$row[0]."'");
				if (!$res2)
				{
					include "trailer.inc.php";
					exit;
				}
				else
				{
					for ($j = 0; ($row2 = grr_sql_row($res2, $j)); $j++)
						$_SESSION['session_message_error'] = send_mail($row2[0], 7, $dformat);
				}
			}
		}
		if (!Settings::set("date_verify_reservation2", $date_now))
		{
			echo $vocab['save_err']." date_verify_reservation2 !<br />";
			die();
		}
	}
}

function mail_hebdo()
{
	$envoisOk = true;

	if(time() < Settings::get("nextalertemailhebdo")) // Déjà envoyé
		$envoisOk = false;

	if (Settings::get('grr_mail_method') == 'bloque') // Mail désactivé
		$envoisOk = false;
	
	if (Settings::get("automatic_mail") != 1) // Mail automatique désactivé
		$envoisOk = false;

	if($envoisOk){
		$sql = "SELECT DISTINCT jmr.login, jmr.id_room, u.email 
				FROM ".TABLE_PREFIX."_j_mailuser_room AS jmr
				LEFT JOIN ".TABLE_PREFIX."_utilisateurs AS u 
				ON u.login = jmr.login
				WHERE jmr.mail_hebdo = '1' AND u.email IS NOT NULL AND u.desactive_mail = 0";
		$dest = grr_sql_query($sql);
    if($dest){
      $logins_rooms = [];
      foreach($dest as $rowD) 
      {
        $logins_rooms[$rowD['login']]['email'] = $rowD['email'];
        $logins_rooms[$rowD['login']]['rooms'][] = $rowD['id_room'];
      }

      //print_r($logins_rooms);

      // On récupère les infos sur les champs additionnels
      $overload_fields = mrbsOverloadGetFieldslist("");
      $tablOverload = array();
      $champsAddTitre = "";
      $l = 1;
      foreach ($overload_fields as $fieldname=>$fieldtype)
      {
        if ($overload_fields[$fieldname]['overload_mail'] == 'y'){
          $champsAddTitre .= "<th>".$overload_fields[$fieldname]['name']."</th>";
          $tablOverload[$l] = $overload_fields[$fieldname]["name"];
          $l++;
        }
      }

      // Pour chaque destinataire
      foreach ($logins_rooms as $login => $data)
      {
        $to = $data['email'];
        $rooms = implode(",", array_map('intval', $data['rooms'])); // Éviter les injections SQL

        // ! Reste  à faire :
        // ! Internationaliser
        // ! Optimiser l'affichage
        // ! Personnalisation administration

        $sql = "SELECT DISTINCT 
            e.id, e.start_time, e.end_time, e.name, e.description, e.type, 
            t.type_name, e.beneficiaire, e.room_id, a.area_name, 
            r.room_name, r.description, a.id, e.overload_desc
          FROM ".TABLE_PREFIX."_entry e
          JOIN ".TABLE_PREFIX."_room r ON e.room_id = r.id
          JOIN ".TABLE_PREFIX."_area a ON r.area_id = a.id
          JOIN ".TABLE_PREFIX."_type_area t ON t.type_letter = e.type
          WHERE e.room_id IN ($rooms) 
          AND e.supprimer = 0
          AND e.start_time BETWEEN UNIX_TIMESTAMP(NOW()) 
                    AND UNIX_TIMESTAMP(NOW() + INTERVAL 1 MONTH)
          ORDER BY e.start_time";

        // echo $sql."<br>";
        $res = grr_sql_query($sql);

        //Nbre de jour déclenchant le mail de rappel
        $delai_jour="7";
        $activation_rappel = 0;
        $message="Cas pas de message";
        //Tableaux de stockage des valeurs
        $tab_rappel_actif = array();
        $tab_rappel_futur =  array();
        $j = 0;
        $k = 0;

        //$nb_ligne = mysqli_num_rows($res);
        //echo "Nbre lignes = ".$nb_ligne." ";

        for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
        {
          //print_r($row)."<br>";

          //On vérifie si la 1ère date de la requête correspond à demain
          $date_grr = Date('Y/m/d',$row[1]);
          //echo $date_grr;
          $date_jour = Date('Y/m/d');
          //echo $date_jour;
          $ecart_date = round(abs(strtotime($date_grr)-strtotime($date_jour))/86400);
          //echo "<br>"." écart jour = ".$ecart_date."  ";

          //$overload_fields = mrbsOverloadGetFieldslist("");
          $champsAddValeur = "";

          $overload_data = mrbsEntryGetOverloadDesc($row[0]);

          $nbValeur = count($tablOverload);
          $AddReservation = array();
          $champAddValeur = array();

          foreach ($overload_data as $fieldname=>$fielddata) // Pour chaque champ additionnel de la réservation
          {
            // if ($fielddata["confidentiel"] == 'n') filtrage trop strict
            if ($fielddata['overload_mail'] == 'y')
            {
              $keyTab = array_search($fieldname, $tablOverload);
              $AddReservation[$keyTab] = $fielddata["valeur"];
            }
          }

          //echo "<br>";
          $m = 1;
          while($m <= $nbValeur){
            if(isset($AddReservation[$m]))
              $champsAddValeur .= "<td>".$AddReservation[$m]."</td>";
            else
              $champsAddValeur .= "<td>-</td>";
            $m++;
          }
        
          //echo $champsAddValeur;

          //Si la prochaine réservation correspond au délai de rappel
          if($ecart_date <= $delai_jour)
          {
            //flag pour provoquer l'envoi du mail de rappel
            $activation_rappel = 1;
          
            //on stocke les infos pour les afficher plus tard
            $tab_rappel_actif[$j] = array(Date('d/m/Y',$row[1]),$row[4],$row[10],$row[6],$row[0],$champsAddValeur);
            $j = $j + 1;
          
          } 
          else
          {
            //on stocke les infos pour les afficher plus tard
            $tab_rappel_futur[$k] = array(Date('d/m/Y',$row[1]),$row[4],$row[10],$row[6],$row[0],$champsAddValeur);
            $k = $k + 1;
          }

        }

        //Construction du message html
        //Affichage résultat
        $message="PROCHAINE(S) RESERVATION(S) / rappel transmis ".$delai_jour." jours avant :"."<br><br>";
        $message.="<table border='1'>";
        //Affichage entête
        $message.="<tr bgcolor='orange'>";
        $message.="<th>Date</th>";
        $message.="<th>Description</th>";
        $message.="<th>Ressource</th>";
        $message.="<th>Type</th>";
        $message.="<th>ID Résa</th>";
        $message.=$champsAddTitre;
        $message.="</tr>";
        for ($i=0 ; $i < $j ; $i++)
        {
          $message.="<tr>";
          $message.="<td><font color='red'>".$tab_rappel_actif[$i][0]."</font></td>";
          $message.="<td><font color='red'>".$tab_rappel_actif[$i][1]."</font></td>";
          $message.="<td><font color='red'>".$tab_rappel_actif[$i][2]."</font></td>";
          $message.="<td><font color='red'>".$tab_rappel_actif[$i][3]."</font></td>";
          $message.="<td><font color='red'>".$tab_rappel_actif[$i][4]."</font></td>";
          $message.=$tab_rappel_actif[$i][5];
          $message.="</tr>";
        }
        //Fermeture tableau de la liste des rappels
        $message.="</table>";
        $message.="<br>";  
            
        //Affichage résultat
        $message.="VISIBILITE DES RESERVATIONS SUR 1 MOIS :"."<br><br>";
        $message.="<table border='1'>";
        //Affichage entête
        $message.="<tr bgcolor='lightgrey'>";
        $message.="<th>Date</th>";
        $message.="<th>Description</th>";
        $message.="<th>Ressource</th>";
        $message.="<th>Type</th>";
        $message.="<th>ID Résa</th>";
        $message.=$champsAddTitre;
        $message.="</tr>";
        for ($i=0 ; $i < $k ; $i++)
        {
          $message.="<tr>";
          $message.="<td>".$tab_rappel_futur[$i][0]."</td>";
          $message.="<td>".$tab_rappel_futur[$i][1]."</td>";
          $message.="<td>".$tab_rappel_futur[$i][2]."</td>";
          $message.="<td>".$tab_rappel_futur[$i][3]."</td>";
          $message.="<td>".$tab_rappel_futur[$i][4]."</td>";
          $message.= $tab_rappel_futur[$i][5];
          $message.="</tr>";
        }
        //Fermeture tableau de la liste des rappels futurs
        $message.="</table>";
        $message.="<br><br>";  


        // Sujet
        $subject = 'RAPPEL RESERVATION SALLE';

        // Envoi
        //Variable d'activation du rappel
        if($activation_rappel == "1")
        {
          //Envoi du mail  
          //echo "Mail envoyé<br>";
          //echo $to."<br>.$subject."<br>.$message."<br>";

          Email::Envois($to, $subject, $message, Settings::get('grr_mail_from'), '', '');
        }

      }

    }
		
		// Calculer le prochain lundi
		$today = new DateTime();
		$nextMonday = (clone $today)->modify('next monday');
		$timestampNextMonday = $nextMonday->getTimestamp();

		Settings::set("nextalertemailhebdo", $timestampNextMonday);

		$message ="";
	}
}
/**
 * @param integer $time
 */
function est_hors_reservation($time,$area="-1")
{
	// Premier test : s'agit-il d'un jour du calendrier "hors réservation" ?
	$test = grr_sql_query1("SELECT DAY FROM ".TABLE_PREFIX."_calendar where DAY = '".$time."'");
	if ($test != -1)
		return true;
	// 2ème test : s'agit-il d'une journée qui n'est pas affichée pour le domaine considéré ?
	if ($area!=-1)
	{
		$sql = "SELECT display_days FROM ".TABLE_PREFIX."_area WHERE id = '".SecuChaine::ProtectDataSql($area)."'";
		$result = grr_sql_query1($sql);
		$jour_semaine = date("w",$time);
		if (substr($result,$jour_semaine,1) == 'n')
			return true;
	}
	return false;
}
function resa_est_hors_reservation($start_time,$end_time)
{
	// On teste si la réservation est dans le calendrier "hors réservations"
	$test = grr_sql_query1("select DAY from ".TABLE_PREFIX."_calendar where DAY = '".$start_time."' or DAY = '".$end_time."'");
	if ($test != -1)
		return true;
	else
		return false;
}
function resa_est_hors_reservation2($start_time,$end_time,$area)
{
	// S'agit-il d'une journée qui n'est pas affichée pour le domaine considéré ?
	$sql = "SELECT display_days FROM ".TABLE_PREFIX."_area WHERE id = '".SecuChaine::ProtectDataSql($area)."'";
	$result = grr_sql_query1($sql);
	$jour_semaine = date("w",$start_time);
	if (substr($result, $jour_semaine, 1) == 'n')
		return true;
	$jour_semaine = date("w",$end_time);
	if (substr($result, $jour_semaine, 1) == 'n')
		return true;
	return false;
}
// trouve les utilisateurs actifs gestionnaires de ressource
function find_active_user_room ($id_room)
{
	$raison = 0;

	// Raison : 1:Gestionnaire de la ressource; 2:Admin du domaine; 3:Admin site; 4:Admins
	$emails = array ();
	$sql = "select email from ".TABLE_PREFIX."_utilisateurs, ".TABLE_PREFIX."_j_user_room
	where ".TABLE_PREFIX."_utilisateurs.etat = 'actif' AND ".TABLE_PREFIX."_utilisateurs.desactive_mail = 0 AND 
	".TABLE_PREFIX."_utilisateurs.login = ".TABLE_PREFIX."_j_user_room.login and 
	id_room='".$id_room."'";
	$res = grr_sql_query($sql);
	if ($res)
	{
		for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
		{
			if (SecuChaine::ValideMail($row[0]))
			{
				$emails[] = $row[0];
				$raison = 1;
			}
		}
	}
	// Si la table des emails des gestionnaires de la ressource est vide, on avertit les administrateurs du domaine
	if (count($emails) == 0)
	{
		$id_area = mrbsGetAreaIdFromRoomId($id_room);
		$sql_admin = grr_sql_query("select email from ".TABLE_PREFIX."_utilisateurs, ".TABLE_PREFIX."_j_useradmin_area
			where ".TABLE_PREFIX."_utilisateurs.etat = 'actif' AND ".TABLE_PREFIX."_utilisateurs.desactive_mail = 0 AND 
			".TABLE_PREFIX."_utilisateurs.login = ".TABLE_PREFIX."_j_useradmin_area.login and ".TABLE_PREFIX."_j_useradmin_area.id_area='".$id_area."'");
		if ($sql_admin)
		{
			for ($i = 0; ($row = grr_sql_row($sql_admin, $i)); $i++)
			{
				if (SecuChaine::ValideMail($row[0]))
				{
					$emails[] = $row[0];
					$raison = 2;
				}
			}
		}
	}
	// Si la table des emails des administrateurs du domaines est vide, on avertit les administrateurs des sites
	if (Settings::get("module_multisite") == 1)
	{
		if (count($emails) == 0)
		{
			$id_area = mrbsGetAreaIdFromRoomId($id_room);
			$id_site = mrbsGetAreaSite($id_area);
			$sql_admin = grr_sql_query("select email from ".TABLE_PREFIX."_utilisateurs, ".TABLE_PREFIX."_j_useradmin_site
				where ".TABLE_PREFIX."_utilisateurs.etat = 'actif' AND ".TABLE_PREFIX."_utilisateurs.desactive_mail = 0 AND 
				".TABLE_PREFIX."_utilisateurs.login = ".TABLE_PREFIX."_j_useradmin_site.login and ".TABLE_PREFIX."_j_useradmin_site.id_site='".$id_site."'");
			if ($sql_admin)
			{
				for ($i = 0; ($row = grr_sql_row($sql_admin, $i)); $i++)
				{
					if (SecuChaine::ValideMail($row[0]))
					{
						$emails[] = $row[0];
						$raison = 3;
					}
				}
			}
		}
	}
	// Si la table des emails des administrateurs des sites est vide, on avertit les administrateurs généraux
	if (count($emails) == 0)
	{
		$sql_admin = grr_sql_query("select email from ".TABLE_PREFIX."_utilisateurs where etat = 'actif' AND statut = 'administrateur' AND desactive_mail = 0");
		if ($sql_admin)
		{
			for ($i = 0; ($row = grr_sql_row($sql_admin, $i)); $i++)
			{
				if (SecuChaine::ValideMail($row[0]))
				{
					$emails[] = $row[0];
					$raison = 4;
				}
			}
		}
	}
	return array($emails,$raison);
}

/** grrDelOverloadFromEntries()
 * Supprime les données du champ $id_field de toutes les réservations
 */
function grrDelOverloadFromEntries($id_field)
{
	$begin_string = "<".$id_field.">";
	$end_string = "</".$id_field.">";
	// On cherche à quel domaine est rattaché le champ additionnel
	$id_area = grr_sql_query1("SELECT id_area FROM ".TABLE_PREFIX."_overload WHERE id='".$id_field."'");
	if ($id_area == -1)
		fatal_error(0, get_vocab('error_area') . $id_field . get_vocab('not_found'));
	// On cherche toutes les ressources du domaine
	$call_rooms = grr_sql_query("SELECT id FROM ".TABLE_PREFIX."_room WHERE area_id = '".$id_area."'");
	if (!$call_rooms)
		fatal_error(0, get_vocab('error_room') . $id_area . get_vocab('not_found'));
	for ($i = 0; ($row = grr_sql_row($call_rooms, $i)); $i++)
	{
		// On cherche toutes les resas de cette resources
		$call_resa = grr_sql_query("SELECT id, overload_desc FROM ".TABLE_PREFIX."_entry WHERE room_id ='".$row[0]."'");
		if (! $call_resa)
			fatal_error(0, get_vocab('invalid_entry_id'));
		for ($j = 0; ($row2 = grr_sql_row($call_resa, $j)); $j++)
		{
			$overload_desc = $row2[1];
			$begin_pos = strpos($overload_desc,$begin_string);
			$end_pos = strpos($overload_desc,$end_string);
			if ( $begin_pos !== false && $end_pos !== false )
			{
				$endpos = $end_pos + 1 + strlen($begin_string);
				$debut_new_chaine = substr($overload_desc,0,$begin_pos);
				$fin_new_chaine = substr($overload_desc,$endpos);
				$new_chaine = $debut_new_chaine.$fin_new_chaine;
				grr_sql_command("UPDATE ".TABLE_PREFIX."_entry SET overload_desc = '".$new_chaine."' WHERE id = '".$row2[0]."'");
			}
		}
		// On cherche toutes les resas de cette ressource
		$call_resa = grr_sql_query("SELECT id, overload_desc FROM ".TABLE_PREFIX."_repeat WHERE room_id ='".$row[0]."'");
		if (!$call_resa)
			fatal_error(0, get_vocab('invalid_entry_id'));
		for ($j = 0; ($row2 = grr_sql_row($call_resa, $j)); $j++)
		{
			$overload_desc = $row2[1];
			$begin_pos = strpos($overload_desc,$begin_string);
			$end_pos = strpos($overload_desc,$end_string);
			if ($begin_pos !== false && $end_pos !== false)
			{
				$endpos = $end_pos + 1 + strlen($begin_string);
				$debut_new_chaine = substr($overload_desc,0,$begin_pos);
				$fin_new_chaine = substr($overload_desc,$endpos);
				$new_chaine = $debut_new_chaine.$fin_new_chaine;
				grr_sql_command("UPDATE ".TABLE_PREFIX."_repeat SET overload_desc = '".$new_chaine."' WHERE id = '".$row2[0]."'");
			}
		}
	}
}
// Traite l'url à utiliser pour les liens dans les pages, en fonction de la configuration et des éventuels reverse proxy'
function traite_grr_url($grr_script_name = "", $force_use_grr_url = "n")
{
	// Dans certaines configuration (reverse proxy, ...) les variables $_SERVER["SCRIPT_NAME"] ou $_SERVER['PHP_SELF']
	// sont mal interprétées entraînant des liens erronés sur certaines pages.
	if (((Settings::get("use_grr_url") == 1) && (Settings::get("grr_url") != "")) || ($force_use_grr_url == "y"))
	{
		if (substr(Settings::get("grr_url"), -1) != "/")
			$ad_signe = "/";
		else
			$ad_signe = "";
		return Settings::get("grr_url").$ad_signe.$grr_script_name;
	}
	else
		return filter_var($_SERVER['PHP_SELF'], FILTER_SANITIZE_URL);
}
// Pour les Jours/Cycles
//Crée le calendrier Jours/Cycles
function cree_calendrier_date_valide($n, $i)
{
	if ($i <= Settings::get("nombre_jours_Jours_Cycles"))
	{
		$sql = "INSERT INTO ".TABLE_PREFIX."_calendrier_jours_cycle SET DAY='".$n."', Jours = $i";
		if (grr_sql_command($sql) < 0)
			fatal_error(1, "<p>" . grr_sql_error());
		$i++;
	}
	else
	{
		$i = 1;
		$sql = "INSERT INTO ".TABLE_PREFIX."_calendrier_jours_cycle set DAY='".$n."', Jours = $i";
		if (grr_sql_command($sql) < 0)
			fatal_error(1, "<p>" . grr_sql_error());
		$i++;
	}
	return $i;
}
/*
Construit les informations à afficher sur les plannings
*/
function affichage_lien_resa_planning($breve_description, $id_resa)
{
	$room = grr_sql_query1("SELECT room_id FROM ".TABLE_PREFIX."_entry WHERE id ='".$id_resa."'");

	$UserLevel = SecuAccess::UserLevel(getUserName(), $room);

	// Brève description ou le numéro de la réservation
	if( ($UserLevel == 0 && Settings::get("display_short_description_nc") == 1) || 
		($UserLevel == 1 && Settings::get("display_short_description_vi") == 1) ||
		($UserLevel == 2 && Settings::get("display_short_description_us") == 1) || 
		($UserLevel == 3 && Settings::get("display_short_description_gr") == 1) || 
		($UserLevel >= 4)
	  )
		$affichage = $breve_description;
	else
		$affichage = get_vocab("entryid").$id_resa;
	return bbCode(htmlspecialchars($affichage,ENT_NOQUOTES),'titre');
}
/*
Construit les informations à afficher sur les plannings
*/
function affichage_resa_planning($_description, $id_resa)
{
	$affichage = "";
	if (Settings::get("display_full_description") == 1)
		$affichage = htmlspecialchars($_description,ENT_NOQUOTES);
    // la ressource associée à la réservation :
    $res = mrbsGetEntryInfo($id_resa);
    $room = (!$res) ? -1 : $res["room_id"]; 
	$UserLevel = SecuAccess::UserLevel(getUserName(), $room);
	// Les champs add :
	$overload_data = mrbsEntryGetOverloadDesc($id_resa);
	foreach ($overload_data as $fieldname=>$field)
	{
		if ((($UserLevel >= 4) ||($field["affichage"] == 'y')) and ($field["valeur"]!=""))
		{
			if ($affichage != "")
				$affichage .= "<br />";
			$affichage .= htmlspecialchars($fieldname,ENT_NOQUOTES).get_vocab("deux_points").htmlspecialchars($field["valeur"],ENT_NOQUOTES|ENT_SUBSTITUTE);
		}
	}
	return $affichage;
}
/*
Construit les informations à ajouter dans les mails automatiques
*/
function affichage_champ_add_mails($id_resa)
{
	$affichage = "";
	// Les champs add :
	$overload_data = mrbsEntryGetOverloadDesc($id_resa);
	foreach ($overload_data as $fieldname=>$field)
	{
		if (($field["overload_mail"] == 'y') && ($field["valeur"] != ""))
			$affichage .= bbcode(htmlspecialchars($fieldname).get_vocab("deux_points").htmlspecialchars($field["valeur"]),'nobbcode')."\n";
	}
	return $affichage;
}
/*
Construit les informations à afficher sur les plannings week_all, month ou month_all
$vue = 1 pour une ressource / 2 vue multiple ressource
$ofl = overload fields list, ne dépend que du domaine, donc est constant dans la boucle d'affichage
*/
function affichage_resa_planning_complet($ofl, $vue, $resa, $heures)
{
	global $dformat;

	$affichage = "";
	$room = $resa[17];

	$UserLevel = SecuAccess::UserLevel(getUserName(), $room);

	// Heures ou créneaux + symboles <== ==>
	if( (($UserLevel == 0 && Settings::get("display_horaires_nc") == 1) || 
		($UserLevel == 1 && Settings::get("display_horaires_vi") == 1) ||
		($UserLevel == 2 && Settings::get("display_horaires_us") == 1) || 
		($UserLevel == 3 && Settings::get("display_horaires_gr") == 1) || 
		($UserLevel >= 4 && Settings::get("display_horaires_ad") == 1)) &&
		$heures != ""
 	)
        $affichage .= $heures."<br>";

	// Ressource seulement dans les vues globales
	if($vue == 2)
		$affichage .= $resa[5]."<br>";

	// Bénéficiaire
	if( ($UserLevel == 0 && Settings::get("display_beneficiaire_nc") == 1) || 
		($UserLevel == 1 && Settings::get("display_beneficiaire_vi") == 1) ||
		($UserLevel == 2 && Settings::get("display_beneficiaire_us") == 1) || 
		($UserLevel == 3 && Settings::get("display_beneficiaire_gr") == 1) || 
		($UserLevel >= 4 && Settings::get("display_beneficiaire_ad") == 1)  
	  )
	{
		if($resa[19] == 0 || getUserName() == $resa[4] || $UserLevel >= 3) // Si résa confidentielle, on n'affiche le bénéficiaire qu'à l'auteur de la résa ou aux gestionnaires
			$affichage .= affiche_nom_prenom_email($resa[4], $resa[12], "nomail")."<br>";
	}

	// Type
	if( ($UserLevel == 0 && Settings::get("display_type_nc") == 1) || 
		($UserLevel == 1 && Settings::get("display_type_vi") == 1) ||
		($UserLevel == 2 && Settings::get("display_type_us") == 1) || 
		($UserLevel == 3 && Settings::get("display_type_gr") == 1) || 
		($UserLevel >= 4 && Settings::get("display_type_ad") == 1)  
	  )
	{
        $typeResa = grr_sql_query1("SELECT ".TABLE_PREFIX."_type_area.type_name FROM ".TABLE_PREFIX."_type_area JOIN ".TABLE_PREFIX."_entry ON ".TABLE_PREFIX."_entry.type=".TABLE_PREFIX."_type_area.type_letter WHERE ".TABLE_PREFIX."_entry.id = '".$resa[2]."';");
		if ($typeResa != -1)
			$affichage .= $typeResa."<br>";
	}

	// Brève description ou le numéro de la réservation
	if( (($UserLevel == 0 && Settings::get("display_short_description_nc") == 1) || 
		($UserLevel == 1 && Settings::get("display_short_description_vi") == 1) ||
		($UserLevel == 2 && Settings::get("display_short_description_us") == 1) || 
		($UserLevel == 3 && Settings::get("display_short_description_gr") == 1) || 
		($UserLevel >= 4 && Settings::get("display_short_description_ad") == 1)) &&
		$resa[3] != ""
	  )
	{
		if($resa[19] == 0 || getUserName() == $resa[4] || $UserLevel >= 3) // Si résa confidentielle, on n'affiche la description qu'à l'auteur de la résa ou aux gestionnaires
			$affichage .= htmlspecialchars($resa[3],ENT_NOQUOTES)."<br>";
	}
	else
		$affichage .= get_vocab("entryid").$resa[2]."<br>";

	// Description Complète
	if( (($UserLevel == 0 && Settings::get("display_full_description_nc") == 1) || 
		($UserLevel == 1 && Settings::get("display_full_description_vi") == 1) ||
		($UserLevel == 2 && Settings::get("display_full_description_us") == 1) || 
		($UserLevel == 3 && Settings::get("display_full_description_gr") == 1) || 
		($UserLevel >= 4 && Settings::get("display_full_description_ad") == 1)) &&
		$resa[8] != ""
	  )
	{
		if($resa[19] == 0 || getUserName() == $resa[4] || $UserLevel >= 3) // Si résa confidentielle, on n'affiche la description qu'à l'auteur de la résa ou aux gestionnaires
			$affichage .= htmlspecialchars($resa[8],ENT_NOQUOTES)."<br>";
	}

	// Participant
	if( (($UserLevel == 0 && Settings::get("display_participants_nc") == 1) || 
		($UserLevel == 1 && Settings::get("display_participants_vi") == 1) ||
		($UserLevel == 2 && Settings::get("display_participants_us") == 1) || 
		($UserLevel == 3 && Settings::get("display_participants_gr") == 1) || 
		($UserLevel >= 4 && Settings::get("display_participants_ad") == 1)) &&
		$resa[18] != 0
	)
	{
		$sql = "SELECT count(id_participation) FROM ".TABLE_PREFIX."_participants WHERE idresa=".$resa[2];
		$res = grr_sql_query($sql);
        	$tmpsql = mysqli_fetch_array($res);
		$nb_inscrit = $tmpsql[0];
		grr_sql_free($res);

		$affichage .= $nb_inscrit."/".$resa[18]."<br>";
	}

	// Champs Additionnels
    // la ressource associée à la réservation :
	//$res = mrbsGetEntryInfo($resa[2]);
	//$room = (!$res) ? -1 : $res["room_id"]; 
   
	// Les champs add :
	if($resa[19] == 0 || getUserName() == $resa[4] || $UserLevel >= 3) // Si résa confidentielle, on n'affiche les champs add qu'à l'auteur de la résa ou aux gestionnaires
	{
		$overload_data = grrGetOverloadDescArray($ofl, $resa[16]);//mrbsEntryGetOverloadDesc($resa[2]);
		foreach ($overload_data as $fieldname=>$field)
		{
			if ( ($UserLevel >= 4 && $field["confidentiel"] == 'n') && $field["affichage"] == 'y' && $field["valeur"] != "") {
				// ELM - Gestion des champs aditionnels multivalués (lignes 384 - 392)
				$valeur = str_replace("|", ",", $field["valeur"]);
				$affichage .= "<i>".htmlspecialchars($fieldname,ENT_NOQUOTES).get_vocab("deux_points").htmlspecialchars($valeur,ENT_NOQUOTES|ENT_SUBSTITUTE)."</i><br />";
			}
		}
	}

	// Emprunte
	if($resa[7] != "-")
		$affichage .= "<i class=\"icone fa-solid fa-hand\" title=\"".get_vocab("ressource_actuellement_empruntee")."\"></i> ";

	// Option réservation
	if($resa[9] > 0 && $resa[10] > 0)
		$affichage .=  " <i class=\"fa-solid fa-flag\" title=\"".get_vocab("reservation_a_confirmer_au_plus_tard_le")." ".time_date_string_jma($resa[9],$dformat)."\" style=\"color:yellow;text-shadow: -2px 0 black, 0 2px black, 2px 0 black, 0 -2px black;\"></i>";

	// Modération
	if($resa[11] == 1)
		$affichage .= "<i class=\"icone fa-solid fa-clock\" title=\"".get_vocab("en_attente_moderation")."\"></i> ";

	// Clef
	if($resa[13] == 1)
		$affichage .= "<i class=\"icone fa-solid fa-key\"></i> ";
	
	// Courrier
	if (Settings::get('show_courrier') == 1)
	{
		if($resa[14] == 1)
			$affichage .= "<i class=\"icone fa-solid fa-envelope\"></i> ";
		else
			$affichage .= "<i class=\"icone fa-solid fa-hourglass-half\"></i> ";
	}

	// Je participe
	if($resa[18] != 0)
	{
		$sql = "SELECT count(id_participation) FROM ".TABLE_PREFIX."_participants WHERE beneficiaire='".getUserName()."' AND idresa = ".$resa[2];
		$res = grr_sql_query($sql);
        	$tmpsql = mysqli_fetch_array($res);
		$present = $tmpsql[0];
		grr_sql_free($res);

		if($present > 0)
		$affichage .= "<i class=\"fa-solid fa-person\"></i> ";
	}

	// Pièce jointe dans la réservation
	$sql = "SELECT count(id) FROM ".TABLE_PREFIX."_files WHERE id_entry= ".$resa[2];
	$res = grr_sql_query($sql);
		$tmpsql = mysqli_fetch_array($res);
	$present = $tmpsql[0];
	grr_sql_free($res);

	if($present > 0)
	$affichage .= "<i class=\"fa-solid fa-paperclip\"></i> ";


	return $affichage;
}
/*
Construit les informations à afficher sur les plannings week_all, month ou month_all
$vue = 1 pour une ressource / 2 vue multiple ressource
$ofl = overload fields list, ne dépend que du domaine, donc est constant dans la boucle d'affichage
*/
function affichage_resa_info_bulle($ofl, $vue, $resa, $heures)
{

	$affichage = "";
	$room = $resa[17];
	$UserLevel = SecuAccess::UserLevel(getUserName(), $room);

	// Heures ou créneaux + symboles <== ==>
	if( (($UserLevel == 0 && Settings::get("display_horaires_nc") == 2) || 
		($UserLevel == 1 && Settings::get("display_horaires_vi") == 2) ||
		($UserLevel == 2 && Settings::get("display_horaires_us") == 2) || 
		($UserLevel == 3 && Settings::get("display_horaires_gr") == 2) || 
		($UserLevel >= 4 && Settings::get("display_horaires_ad") == 2)) &&
		$heures != ""
 	)
        $affichage .= $heures."\n";

	// Ressource seulement dans les vues globales
	if($vue == 2)
		$affichage .= $resa[5]."\n";

	// Bénéficiaire
	if( ($UserLevel == 0 && Settings::get("display_beneficiaire_nc") == 2) || 
		($UserLevel == 1 && Settings::get("display_beneficiaire_vi") == 2) ||
		($UserLevel == 2 && Settings::get("display_beneficiaire_us") == 2) || 
		($UserLevel == 3 && Settings::get("display_beneficiaire_gr") == 2) || 
		($UserLevel >= 4 && Settings::get("display_beneficiaire_ad") == 2)  
	  )
	{
		$affichage .= affiche_nom_prenom_email($resa[4], $resa[12], "nomail")."\n";
	}

	// Type
	if( ($UserLevel == 0 && Settings::get("display_type_nc") == 2) || 
		($UserLevel == 1 && Settings::get("display_type_vi") == 2) ||
		($UserLevel == 2 && Settings::get("display_type_us") == 2) || 
		($UserLevel == 3 && Settings::get("display_type_gr") == 2) || 
		($UserLevel >= 4 && Settings::get("display_type_ad") == 2)  
	  )
	{
        $typeResa = grr_sql_query1("SELECT ".TABLE_PREFIX."_type_area.type_name FROM ".TABLE_PREFIX."_type_area JOIN ".TABLE_PREFIX."_entry ON ".TABLE_PREFIX."_entry.type=".TABLE_PREFIX."_type_area.type_letter WHERE ".TABLE_PREFIX."_entry.id = '".$resa[2]."';");
		if ($typeResa != -1)
			$affichage .= $typeResa."\n";
	}

	// Brève description ou le numéro de la réservation
	if( (($UserLevel == 0 && Settings::get("display_short_description_nc") == 2) || 
		($UserLevel == 1 && Settings::get("display_short_description_vi") == 2) ||
		($UserLevel == 2 && Settings::get("display_short_description_us") == 2) || 
		($UserLevel == 3 && Settings::get("display_short_description_gr") == 2) || 
		($UserLevel >= 4 && Settings::get("display_short_description_ad") == 2)) &&
		$resa[3] != ""
	  )
		$affichage .= htmlspecialchars($resa[3],ENT_NOQUOTES)."\n";
	else
		$affichage .= get_vocab("entryid").$resa[2]."\n";

	// Description Complète
	if( (($UserLevel == 0 && Settings::get("display_full_description_nc") == 2) || 
		($UserLevel == 1 && Settings::get("display_full_description_vi") == 2) ||
		($UserLevel == 2 && Settings::get("display_full_description_us") == 2) || 
		($UserLevel == 3 && Settings::get("display_full_description_gr") == 2) || 
		($UserLevel >= 4 && Settings::get("display_full_description_ad") == 2)) &&
		$resa[8] != ""
	  )
		$affichage .= htmlspecialchars($resa[8],ENT_NOQUOTES)."\n";

	// Champs Additionnels
    // la ressource associée à la réservation :
	//$res = mrbsGetEntryInfo($resa[2]);
	//$room = (!$res) ? -1 : $res["room_id"]; 
   
	// Les champs add :
	$overload_data = grrGetOverloadDescArray($ofl, $resa[16]);//mrbsEntryGetOverloadDesc($resa[2]);
	foreach ($overload_data as $fieldname=>$field)
	{
		if (( ($UserLevel >= 4 && $field["confidentiel"] == 'n') || $field["affichage"] == 'y') && $field["valeur"] != "") {
			// ELM - Gestion des champs aditionnels multivalués (lignes 384 - 392)
			$valeur = str_replace("|", ",", $field["valeur"]);
			$affichage .= htmlspecialchars($fieldname,ENT_NOQUOTES).get_vocab("deux_points").htmlspecialchars($valeur,ENT_NOQUOTES|ENT_SUBSTITUTE)."\n";
		}
	}

	if($affichage == "")
		$affichage = get_vocab('voir_details');

	return $affichage;
}
/*
Construit les informations à afficher en pop-up sur les plannings month_all2, year ou year_all
$ofl = overload fields list, ne dépend que du domaine, donc est constant dans la boucle d'affichage
*/
function titre_compact($ofl, $resa, $heures)
{
	global $dformat;

	$affichage = "";

	// la ressource associée à la réservation :
	$room = $resa[17];

	// Heures ou créneaux + symboles <== ==>
	$affichage .= $heures;
	$UserLevel = SecuAccess::UserLevel(getUserName(), $room);

	// Bénéficiaire
	if( ($UserLevel == 0 && Settings::get("display_beneficiaire_nc") >= 1) || 
		($UserLevel == 1 && Settings::get("display_beneficiaire_vi") >= 1) ||
		($UserLevel == 2 && Settings::get("display_beneficiaire_us") >= 1) || 
		($UserLevel == 3 && Settings::get("display_beneficiaire_gr") >= 1) || 
		($UserLevel >= 4 && Settings::get("display_beneficiaire_ad") >= 1)  
	  )
	{
		$affichage .= "\n".affiche_nom_prenom_email($resa[4], $resa[12], "nomail");
	}

	// Type
	if( ($UserLevel == 0 && Settings::get("display_type_nc") >= 1) || 
		($UserLevel == 1 && Settings::get("display_type_vi") >= 1) ||
		($UserLevel == 2 && Settings::get("display_type_us") >= 1) || 
		($UserLevel == 3 && Settings::get("display_type_gr") >= 1) || 
		($UserLevel >= 4 && Settings::get("display_type_ad") >= 1)  
	  )
	{
        $typeResa = grr_sql_query1("SELECT ".TABLE_PREFIX."_type_area.type_name FROM ".TABLE_PREFIX."_type_area JOIN ".TABLE_PREFIX."_entry ON ".TABLE_PREFIX."_entry.type=".TABLE_PREFIX."_type_area.type_letter WHERE ".TABLE_PREFIX."_entry.id = '".$resa[2]."';");
		if ($typeResa != -1)
			$affichage .= "\n".$typeResa;
	}

	// Brève description ou le numéro de la réservation
	if( (($UserLevel == 0 && Settings::get("display_short_description_nc") >= 1) || 
		($UserLevel == 1 && Settings::get("display_short_description_vi") >= 1) ||
		($UserLevel == 2 && Settings::get("display_short_description_us") >= 1) || 
		($UserLevel == 3 && Settings::get("display_short_description_gr") >= 1) || 
		($UserLevel >= 4 && Settings::get("display_short_description_ad") >= 1)) &&
		$resa[3] != ""
	  )
		$affichage .= "\n".htmlspecialchars($resa[3],ENT_NOQUOTES);
	else
		$affichage .= "\n".get_vocab("entryid").$resa[2];

	// Description Complète
	if( (($UserLevel == 0 && Settings::get("display_full_description_nc") >= 1) || 
		($UserLevel == 1 && Settings::get("display_full_description_vi") >= 1) ||
		($UserLevel == 2 && Settings::get("display_full_description_us") >= 1) || 
		($UserLevel == 3 && Settings::get("display_full_description_gr") >= 1) || 
		($UserLevel >= 4 && Settings::get("display_full_description_ad") >= 1)) &&
		$resa[8] != ""
	  )
		$affichage .= "\n".htmlspecialchars($resa[8],ENT_NOQUOTES);

	// Champs Additionnels
	$UserLevel = SecuAccess::UserLevel(getUserName(), $room);
	$overload_data = grrGetOverloadDescArray($ofl, $resa[16]);//mrbsEntryGetOverloadDesc($resa[2]);
	foreach ($overload_data as $fieldname=>$field)
	{
		if (( ($UserLevel >= 4 && $field["confidentiel"] == 'n') || $field["affichage"] == 'y') && $field["valeur"] != "")
			$affichage .= "\n".htmlspecialchars($fieldname,ENT_NOQUOTES).get_vocab("deux_points").htmlspecialchars($field["valeur"],ENT_NOQUOTES|ENT_SUBSTITUTE);
	}

	return $affichage;
}
/*
Construit les informations à afficher dans la cellule du planning month_all2, year ou year_all :
description abrégée, statut, option, modération
*/
function lien_compact($resa)
{
    global $dformat;
	$affichage = "";
	$room = $resa[17];

	$UserLevel = SecuAccess::UserLevel(getUserName(), $room);

	// Bénéficiaire
	if( ($UserLevel == 0 && Settings::get("display_beneficiaire_nc") == 1) || 
		($UserLevel == 1 && Settings::get("display_beneficiaire_vi") == 1) ||
		($UserLevel == 2 && Settings::get("display_beneficiaire_us") == 1) || 
		($UserLevel == 3 && Settings::get("display_beneficiaire_gr") == 1) || 
		($UserLevel >= 4 && Settings::get("display_beneficiaire_ad") == 1)  
	  )
		$affichage .= affiche_nom_prenom_email($resa[4], $resa[12], "nomail");

	// Brève description ou le numéro de la réservation
	if( (($UserLevel == 0 && Settings::get("display_short_description_nc") == 1) || 
		($UserLevel == 1 && Settings::get("display_short_description_vi") == 1) ||
		($UserLevel == 2 && Settings::get("display_short_description_us") == 1) || 
		($UserLevel == 3 && Settings::get("display_short_description_gr") == 1) || 
		($UserLevel >= 4 && Settings::get("display_short_description_ad") == 1)) &&
		$resa[3] != ""
	  )
		$affichage .= htmlspecialchars($resa[3],ENT_NOQUOTES);
	else
		$affichage .= get_vocab("entryid").$resa[2];

	// Description Complète
	if( (($UserLevel == 0 && Settings::get("display_full_description_nc") == 1) || 
		($UserLevel == 1 && Settings::get("display_full_description_vi") == 1) ||
		($UserLevel == 2 && Settings::get("display_full_description_us") == 1) || 
		($UserLevel == 3 && Settings::get("display_full_description_gr") == 1) || 
		($UserLevel >= 4 && Settings::get("display_full_description_ad") == 1)) &&
		$resa[8] != ""
	  )
		$affichage .= htmlspecialchars($resa[8],ENT_NOQUOTES);
    // on coupe aux quatre premiers caractères
    $affichage = substr($affichage,0,4)."<br />";

	// Emprunte
	if($resa[7] != "-")
		$affichage .= "<i class=\"icone fa-solid fa-hand\" title=\"".get_vocab("ressource_actuellement_empruntee")."\"></i> ";
	// Option réservation
	if($resa[9] > 0 && $resa[10] > 0)
		$affichage .= "<i class=\"icone fa-solid fa-flag\" title=\"".get_vocab("en_attente_moderation")."\" title=\"".get_vocab("reservation_a_confirmer_au_plus_tard_le").time_date_string_jma($resa[9],$dformat)."\"></i> ";
	// Modération
	if($resa[11] == 1)
		$affichage .= "<i class=\"icone fa-solid fa-clock\" title=\"".get_vocab("en_attente_moderation")."\"></i> ";
	// Clef
	if($resa[13] == 1)
		$affichage .= "<i class=\"icone fa-solid fa-key\"></i> ";
	// Courrier
	if (Settings::get('show_courrier') == 1)
	{
		if($resa[14] == 1)
			$affichage .= " <i class=\"icone fa-solid fa-envelope\"></i> ";
		else
			$affichage .= " <i class=\"icone fa-solid fa-hourglass-half\"></i> ";
	}
	return $affichage;
}
/*
Destinaire specifique champ additionnel
*/
function envois_spec_champ_add_mails($id_resa)
{
	$destinataire = "";
	// Les champs add :
	$overload_data = mrbsEntryGetOverloadDesc($id_resa);
	foreach ($overload_data as $field)
	{
		if (isset($field["mail_spec"]) && ($field["mail_spec"] != '') && ($field["valeur"] != "") && ($field["valeur"] != 0))
			$destinataire .= htmlspecialchars($field["mail_spec"]).";";
	}
	return $destinataire;
}
/*
Affiche un message pop-up
$type_affichage = "user" -> Affichage des "pop-up" de confirmation après la création/modification/suppression d'une réservation
Dans ce cas, l'affichage n'a lieu que si $_SESSION['displ_msg']='yes'
$type_affichage = "admin" -> Affichage des "pop-up" de confirmation dans les menus d'administration (V4.6+ On force la popup plus le choix de l'affichage dans les paramètres d'administration)
$type_affichage = "force" -> On force l'affichage du pop-up même si javascript_info_admin_disabled est true
*/
function affiche_pop_up($msg = "",$type_affichage = "user")
{
	// Si $_SESSION["msg_a_afficher"] est défini, on l'affiche, sinon, on affiche $msg passé en variable
	if ((isset($_SESSION["msg_a_afficher"])) and ($_SESSION["msg_a_afficher"] != ""))
		$msg = $_SESSION["msg_a_afficher"];
	if ($msg != "")
	{
		if ($type_affichage == "user")
		{
			if (!(Settings::get("javascript_info_disabled")))
			{
				echo "<script type=\"text/javascript\">";
				if ((isset($_SESSION['displ_msg'])) && ($_SESSION['displ_msg'] == 'yes'))
					echo " alert(\"".$msg."\")";
				echo "</script>";
			}
		}
		else
		{
			echo "<script type=\"text/javascript\">";
			echo "<!--\n";
			echo " alert(\"".$msg."\")";
			echo "//-->";
			echo "</script>";
		}
	}
	$_SESSION['displ_msg'] = "";
	$_SESSION["msg_a_afficher"] = "";
}
/*
Retourne un tableau contenant les nom et prénom et l'email de $_beneficiaire
*/
function donne_nom_email($_beneficiaire)
{
	$tab_benef = array();
	$tab_benef["nom"] = "";
	$tab_benef["email"] = "";
	if ($_beneficiaire == "")
		return $tab_benef;
	$temp = explode("|",$_beneficiaire);
	if (isset($temp[0]))
		$tab_benef["nom"] = $temp[0];
	if (isset($temp[1]))
		$tab_benef["email"] = $temp[1];
	return $tab_benef;
}
/*
Retourne une chaine concaténée des nom et prénom et l'email
*/
function concat_nom_email($_nom, $_email)
{
	// On supprime les caractères | de $_nom
	$_nom = trim(str_replace("|","",$_nom));
	if ($_nom == "")
		return "-1";
	$_email = trim($_email);
	if ($_email != "")
	{
		if (strstr($_email,"|"))
			return "-2";
	}
	$chaine = $_nom."|".$_email;
	return $chaine;
}
/*
Formate les noms, prénom et email du bénéficiaire ou du bénéficiaire extérieur
$type = nomail -> on affiche les prénom et nom sans le mail.
$type = withmail -> on affiche un lien avec le mail sur les prénom et nom.
$type = formail -> on formate en utf8 pour l'envoi par mail (utilisé dans l'envoi de mails automatiques)
$type = onlymail -> on affiche uniquement le mail (utilisé dans l'envoi de mails automatiques)
*/
/**
 * @return string
 */
function affiche_nom_prenom_email($_beneficiaire, $_beneficiaire_ext, $type = "nomail")
{
	if ($_beneficiaire != "")
	{
		$sql_beneficiaire = "SELECT prenom, nom, email FROM ".TABLE_PREFIX."_utilisateurs WHERE login = '".$_beneficiaire."'";
		$res_beneficiaire = grr_sql_query($sql_beneficiaire);
		if ($res_beneficiaire)
		{
			$nb_result = grr_sql_count($res_beneficiaire);
			if ($nb_result == 0){
				$chaine = get_vocab("utilisateur_inconnu").$_beneficiaire;
			} else
			{
				$row_user = grr_sql_row($res_beneficiaire, 0);
				if ($type == "formail")
				{
					$chaine = removeMailUnicode($row_user[0])." ".removeMailUnicode($row_user[1]);
					if ($row_user[2] != "")
						$chaine .= " (".$row_user[2].")";
				}
				else if ($type == "onlymail")
				{
					// Cas où en envoie uniquement le mail
					$chaine = grr_sql_query1("select email from ".TABLE_PREFIX."_utilisateurs where login='$_beneficiaire'");
				}
				else if (($type == "withmail") and ($row_user[2] != ""))
				{
					// Cas où en envoie les noms, prénoms et mail
					$chaine = affiche_lien_contact($_beneficiaire,"identifiant:oui","afficher_toujours");
				}
				else
				{
					// Cas où en envoie les noms, prénoms sans le mail
					$chaine = $row_user[0]." ".$row_user[1];
				}
			}
			return $chaine;
		}
		else
			return "";
	}
	else
	{
		// cas d'un bénéficiaire extérieur
		// On récupère le tableau des nom et emails
		$tab_benef = donne_nom_email($_beneficiaire_ext);
		// Cas où en envoie uniquement le mail
		if ($type == "onlymail")
		{
			$chaine = $tab_benef["email"];
			// Cas où en envoie les noms, prénoms et mail
		}
		else if (($type == "withmail") && ($tab_benef["email"] != ""))
		{
			$email = explode('@',$tab_benef["email"]);
			$person = $email[0];
			if (isset($email[1]))
			{
				$domain = $email[1];
				$chaine = "<script type=\"text/javascript\">encode_adresse('".$person."','".$domain."','".AddSlashes($tab_benef["nom"])."',1);</script>";
			}
			else
				$chaine = $tab_benef["nom"];
		}
		else
		{
		// Cas où en envoie les noms, prénoms sans le mail
			$chaine = $tab_benef["nom"];
		}
		return $chaine;
	}
}
/*
 Fonction permettant d'effectuer une correspondance entre
 le profil lu sous LDAP et les statuts existants dans GRR
*/
 function effectuer_correspondance_profil_statut($codefonction, $libellefonction) {
		# On récupère le statut par défaut des utilisateurs CAS
 	$sso = Settings::get("sso_statut");
 	if ($sso == "cas_visiteur")
 		$_statut = "visiteur";
 	else if ($sso == "cas_utilisateur")
 		$_statut = "utilisateur";
		# Le code fonction est défini
 	if ($codefonction != "")
 	{
 		$sql = grr_sql_query1("SELECT statut_grr from ".TABLE_PREFIX."_correspondance_statut where code_fonction='".$codefonction."'");
 		if ($sql != -1)
 		{
			// Si la fonction existe dans la table de correspondance, on retourne le statut_grr associé
 			return $sql;
 		}
 		else
 		{
			// Le code n'existe pas dans la base, alors on l'insère en lui attribuant le statut par défaut.
 			$libellefonction = SecuChaine::ProtectDataSql($libellefonction);
 			grr_sql_command("INSERT INTO grr_correspondance_statut(code_fonction,libelle_fonction,statut_grr) VALUES ('$codefonction', '$libellefonction', '$_statut')");
 			return $_statut;
 		}
		//Le code fonction n'est pas défini, alors on retourne le statut par défaut.
 	}
 	else
 		return $_statut;
 }
/* function jQuery_DatePickerTwig($typeDate)
 * fonction qui rend un sélecteur de date couplé à un calendrier jQuery-DatePicker
 * définit trois input : $typeDate.'day', $typeDate.'month', $typeDate.'year'
 * /!\ changement de spécification : le préfixe $typeDate doit comporter un éventuel '_'
*/
function jQuery_DatePickerTwig($typeDate){

	$getID		= isset($_GET['id']) ? SecuChaine::Alphanumeric($_GET['id']) : '';
	$getDay		= isset($_GET['day']) ? SecuChaine::Alphanumeric($_GET['day']) : '';
	$getMonth	= isset($_GET['month']) ? SecuChaine::Alphanumeric($_GET['month']) : '';
	$getYear	= isset($_GET['year']) ? SecuChaine::Alphanumeric($_GET['year']) : '';

	if ($typeDate == 'rep_end_' && isset($_GET['id'])){
		$res = grr_sql_query("SELECT repeat_id FROM ".TABLE_PREFIX."_entry WHERE id=".$getID.";");
		if (!$res){
			fatal_error(0, grr_sql_error());
		}
		$repeat_id = implode('', grr_sql_row($res, 0));
		$res = grr_sql_query("SELECT rep_type, end_date, rep_opt, rep_num_weeks, start_time, end_time FROM ".TABLE_PREFIX."_repeat WHERE id=$repeat_id");
		if (!$res){
			fatal_error(0, grr_sql_error());
		}
		if (grr_sql_count($res) == 1){
			$row6 = grr_sql_row($res, 0);
			$date = date_parse(date("Y-m-d H:i:s",$row6[1]));
			$day = $date['day'];
			$month = $date['month'];
			$year = $date['year'];
		} else{
			if (isset ($_GET['day']))
				$day = $getDay;
			else
				$day = date("d");
			if (isset ($_GET['month']))
				$month = $getMonth;
			else
				$month = date("m");
			if (isset ($_GET['year']))
				$year = $getYear;
			else
				$year = date("Y");
		}
	} else{
		global $start_day, $start_month, $start_year, $end_day, $end_month, $end_year;

		if (isset ($_GET['day'])){
			$day = $getDay;
		} else{
			$day = date("d");
		}

		if (isset($start_day) && $typeDate=='start_'){
			$day = $start_day;
		} elseif (isset($end_day) && $typeDate=='end_'){
			$day = $end_day;
		}

		if (isset ($_GET['month'])){
			$month = $getMonth;
		} else{
			$month = date("m");
		}

		if (isset($start_month) && $typeDate=='start_'){
			$month = $start_month;
		} elseif (isset($end_month) && $typeDate=='end_'){
			$month = $end_month;
		}

		if(isset ($_GET['year'])){
			$year = $getYear;
		} else{
			$year = date("Y");
		}

		if (isset($start_year) && $typeDate=='start_'){
			$year = $start_year;
		} elseif (isset($end_year) && $typeDate=='end_'){
			$year = $end_year;
		}
 	}

	if($typeDate != '')
		$typeDate = $typeDate;

 	$retour = genDateSelectorForm($typeDate, "$day", "$month", "$year","");

	return $retour;
}

function jQuery_TimePickerTwig($typeTime, $hour, $minute)
{
  $hour = str_pad($hour, 2, 0, STR_PAD_LEFT); // Affichage heure sur 2 digits
  $minute = str_pad($minute, 2, 0, STR_PAD_LEFT); // Affichage minutes sur 2 digits
	$html = '<div class="input-group clockpicker" style="width:auto;">
		<input name="' .$typeTime. '" type="text" id="clockpicker" class="form-control" value="' .$hour. ':' .$minute. '">
		<span class="input-group-addon">
			<i class="fa-regular fa-clock"></i>
		</span>
	</div>';

	return $html;
}

/** supprimerReservationsUtilisateursEXT()
 *
 * Supprime les réservations des membres qui proviennent d'une source "EXT"
 *
 *
 * Returns:
 *   0        - An error occured
 *   non-zero - The entries were deleted
 */
function supprimerReservationsUtilisateursEXT($avec_resa,$avec_privileges)
{
	// Récupération de tous les utilisateurs de la source EXT
	$requete_users_ext = "SELECT login FROM ".TABLE_PREFIX."_utilisateurs WHERE source='ext' and statut<>'administrateur'";
	$res = grr_sql_query($requete_users_ext);
	$logins = array();
	$logins_liaison  = array();
	if ($res)
	{
		for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
		{
			$logins[]=$row[0];
		}
	}
	// Construction des requêtes de suppression à partir des différents utilisateurs à supprimer
	if ($avec_resa == 'y')
	{
		// Pour chaque utilisateur, on supprime les réservations qu'il a créées et celles dont il est bénéficiaire
		// Table grr_entry
		$req_suppr_table_entry = "DELETE FROM ".TABLE_PREFIX."_entry WHERE create_by = ";
		$first = 1;
		foreach ($logins as $log)
		{
			if ($first == 1)
			{
				$req_suppr_table_entry .= "'$log' OR beneficiaire='$log'";
				$first = 0;
			}
			else
				$req_suppr_table_entry .= " OR create_by = '$log' OR beneficiaire = '$log' ";
		}
		// Pour chaque utilisateur, on supprime les réservations périodiques qu'il a créées et celles dont il est bénéficiaire
		// Table grr_repeat
		$req_suppr_table_repeat = "DELETE FROM ".TABLE_PREFIX."_repeat WHERE create_by = ";
		$first = 1;
		foreach ($logins as $log)
		{
			if ($first == 1)
			{
				$req_suppr_table_repeat .= "'$log' OR beneficiaire='$log'";
				$first = 0;
			}
			else
				$req_suppr_table_repeat .= " OR create_by = '$log' OR beneficiaire = '$log' ";
		}
		// Pour chaque utilisateur, on supprime les réservations périodiques qu'il a créées et celles dont il est bénéficiaire
		// Table grr_entry_moderate
		$req_suppr_table_entry_moderate = "DELETE FROM ".TABLE_PREFIX."_entry_moderate WHERE create_by = ";
		$first = 1;
		foreach ($logins as $log)
		{
			if ($first == 1)
			{
				$req_suppr_table_entry_moderate .= "'$log' OR beneficiaire='$log'";
				$first = 0;
			}
			else
				$req_suppr_table_entry_moderate .= " OR create_by = '$log' OR beneficiaire = '$log' ";
		}
	}
	$req_j_mailuser_room = "";
	$req_j_user_area = "";
	$req_j_user_room = "";
	$req_j_useradmin_area = "";
	$req_j_useradmin_site = "";
	foreach ($logins as $log)
	{
		// Table grr_j_mailuser_room
		$test = grr_sql_query1("SELECT count(login) FROM ".TABLE_PREFIX."_j_mailuser_room WHERE login='".$log."'");
		if ($test >=1)
		{
			if ($avec_privileges == "y")
			{
				if ($req_j_mailuser_room == "")
					$req_j_mailuser_room = "DELETE FROM ".TABLE_PREFIX."_j_mailuser_room WHERE login='".$log."'";
				else
					$req_j_mailuser_room .= " OR login = '".$log."'";
			}
			else
				$logins_liaison[] = strtolower($log);
		}
		// Table grr_j_user_area
		$test = grr_sql_query1("SELECT count(login) FROM ".TABLE_PREFIX."_j_user_area WHERE login='".$log."'");
		if ($test >=1)
		{
			if ($avec_privileges == "y")
			{
				if ($req_j_user_area == "")
					$req_j_user_area = "DELETE FROM ".TABLE_PREFIX."_j_user_area WHERE login='".$log."'";
				else
					$req_j_user_area .= " OR login = '".$log."'";
			}
			else
				$logins_liaison[] = strtolower($log);
		}
		// Table grr_j_user_room
		$test = grr_sql_query1("SELECT count(login) FROM ".TABLE_PREFIX."_j_user_room WHERE login='".$log."'");
		if ($test >= 1)
		{
			if ($avec_privileges == "y")
			{
				if ($req_j_user_room == "")
					$req_j_user_room = "DELETE FROM ".TABLE_PREFIX."_j_user_room WHERE login='".$log."'";
				else
					$req_j_user_room .= " OR login = '".$log."'";
			}
			else
				$logins_liaison[] = strtolower($log);
		}
		// Table grr_j_useradmin_area
		$test = grr_sql_query1("SELECT count(login) FROM ".TABLE_PREFIX."_j_useradmin_area WHERE login='".$log."'");
		if ($test >= 1)
		{
			if ($avec_privileges == "y")
			{
				if ($req_j_useradmin_area == "")
					$req_j_useradmin_area = "DELETE FROM ".TABLE_PREFIX."_j_useradmin_area WHERE login='".$log."'";
				else
					$req_j_useradmin_area .= " OR login = '".$log."'";
			}
			else
				$logins_liaison[] = strtolower($log);
		}
		// Table grr_j_useradmin_site
		$test = grr_sql_query1("SELECT count(login) FROM ".TABLE_PREFIX."_j_useradmin_site WHERE login='".$log."'");
		if ($test >= 1)
		{
			if ($avec_privileges == "y")
			{
				if ($req_j_useradmin_site == "")
					$req_j_useradmin_site = "DELETE FROM ".TABLE_PREFIX."_j_useradmin_site WHERE login='".$log."'";
				else
					$req_j_useradmin_site .= " OR login = '".$log."'";
			}
			else
				$logins_liaison[] = strtolower($log);
		}
	}
		// Suppression effective
	echo "<hr />\n";
	if ($avec_resa == 'y')
	{
		$nb = 0;
		$s = grr_sql_command($req_suppr_table_entry);
		if ($s != -1)
			$nb += $s;
		$s = grr_sql_command($req_suppr_table_repeat);
		if ($s != -1)
			$nb += $s;
		$s = grr_sql_command($req_suppr_table_entry_moderate);
		if ($s != -1)
			$nb += $s;
		echo "<p class='avertissement'>".get_vocab("tables_reservations").get_vocab("deux_points").$nb.get_vocab("entres_supprimees")."</p>\n";
	}
	$nb = 0;
	if ($avec_privileges == "y")
	{
		if ($req_j_mailuser_room != "")
		{
			$s = grr_sql_command($req_j_mailuser_room);
			if ($s != -1)
				$nb += $s;
		}
		if ($req_j_user_area != "")
		{
			$s = grr_sql_command($req_j_user_area);
			if ($s != -1)
				$nb += $s;
		}
		if ($req_j_user_room != "")
		{
			$s = grr_sql_command($req_j_user_room);
			if ($s != -1)
				$nb += $s;
		}
		if ($req_j_useradmin_area != "")
		{
			$s = grr_sql_command($req_j_useradmin_area);
			if ($s != -1)
				$nb += $s;
		}
		if ($req_j_useradmin_site != "")
		{
			$s = grr_sql_command($req_j_useradmin_site);
			if ($s != -1)
				$nb += $s;
		}
	}
	echo "<p class='avertissement'>".get_vocab("tables_liaison").get_vocab("deux_points").$nb.get_vocab("entres_supprimees")."</p>\n";
	if ($avec_privileges == "y")
	{
		// Enfin, suppression des utilisateurs de la source EXT qui ne sont pas administrateur
		$requete_suppr_users_ext = "DELETE FROM ".TABLE_PREFIX."_utilisateurs WHERE source='ext' and statut<>'administrateur'";
		$s = grr_sql_command($requete_suppr_users_ext);
		if ($s == -1)
			$s = 0;
		echo "<p class='avertissement'>".get_vocab("table_utilisateurs").get_vocab("deux_points").$s.get_vocab("entres_supprimees")."</p>\n";
	}
	else
	{
		$n = 0;
		foreach ($logins as $log)
		{
			if (!in_array(strtolower($log), $logins_liaison))
			{
				grr_sql_command("DELETE FROM ".TABLE_PREFIX."_utilisateurs WHERE login='".$log."'");
				$n++;
			}
		}
		echo "<p class='avertissement'>".get_vocab("table_utilisateurs").get_vocab("deux_points").$n.get_vocab("entres_supprimees")."</p>\n";
	}
}

if (!function_exists('htmlspecialchars_decode'))
{
	function htmlspecialchars_decode($text)
	{
		return strtr($text, array_flip(get_html_translation_table(HTML_SPECIALCHARS)));
	}
}

/*
* @param integer $delai : nombre de jours de rétention des logs de connexion
* nettoieLogConnexion efface les entrées de la table _log antérieures au jour courant moins le délai
*/
function nettoieLogConnexion($delai){
    // est-ce un administrateur ?
    if (SecuAccess::UserLevel(getUserName(), -1) >= 6){
        $dateMax = new DateTime('NOW');
        $dateMax->sub(new DateInterval('P'.$delai.'D'));
        $dateMax = $dateMax->format('Y-m-d H:i:s');
        $sql = "DELETE FROM ".TABLE_PREFIX."_log WHERE START < '" . $dateMax . "';";
        grr_sql_query($sql);
    }
}
/*
* @param integer $delai : nombre de jours de rétention des logs de connexion
* nettoieLogConnexion efface les entrées de la table _log antérieures au jour courant moins le délai
*/
function nettoieLogEmail($delai){
    // est-ce un administrateur ?
    if (SecuAccess::UserLevel(getUserName(), -1) >= 6){
        $dateMax = new DateTime('NOW');
        $dateMax->sub(new DateInterval('P'.$delai.'D'));
        $dateMax = $dateMax->format('Y-m-d H:i:s');
        $sql = "DELETE FROM ".TABLE_PREFIX."_log_mail WHERE date < '" . strtotime($dateMax) . "';";
        grr_sql_query($sql);
    }
}

function insertLogResa($idresa, $idAction, $infos){

	$sql = "INSERT INTO ".TABLE_PREFIX."_log_resa (date, idresa, identifiant, action, infoscomp) values (
		'" . time() . "',
		'" . $idresa . "',
		'" . getUserName() . "',
		'" . $idAction . "',
		'" . $infos . "'
		)
	;";
	grr_sql_query($sql);

}

/*
Génère le style CSS pour les différents types de réservations
*/
function cssTypeResa()
{
    // récupération des couleurs des types
    $types = '';
    $sql = "SELECT type_letter,couleurhexa,couleurtexte,couleuricone FROM ".TABLE_PREFIX."_type_area WHERE 1";
    $res = grr_sql_query($sql);
    if ($res->num_rows > 0) {
        $types = "<style>".PHP_EOL;
        while($row = $res->fetch_assoc()) {
            $types .= "td.type".$row["type_letter"]."{background:".$row["couleurhexa"]." !important;color:".$row["couleurtexte"]." !important;}".PHP_EOL;
            $types .= "td.type".$row["type_letter"]." a.lienCellule{color:".$row["couleurtexte"]." !important;}".PHP_EOL;
			$types .= "td.type".$row["type_letter"]." i.icone{color:".$row["couleuricone"]." !important;}".PHP_EOL;
        }
        $types .= "</style>".PHP_EOL;
    }

	return $types;
}

/* Fonction qui affiche, si un mail n'a pas pu être envoyé, un pop-up avec le message d'erreur
*/
function display_mail_msg()
{
    if (!(Settings::get("javascript_info_disabled")))
    {
        if ((isset($_SESSION['session_message_error'])) && ($_SESSION['session_message_error'] != ''))
        {
            echo "<script type=\"text/javascript\">";
            echo "<!--\n";
            echo " alert(\"".get_vocab("title_automatic_mail")."\\n".$_SESSION['session_message_error']."\\n".get_vocab("technical_contact")."\")";
            echo "//-->";
            echo "</script>";
            $_SESSION['session_message_error'] = "";
        }
    }
}

// Génération d'un Token aléatoire
function generationToken()
{
	return $token = base_convert(hash('sha256', time() . mt_rand()), 16, 36);
}

/* fonction acces_formulaire_reservation
* détermine si le quota de réservations par formulaire non modérées est atteint
* rend TRUE si le formulaire est accessible ou FALSE (y compris si l'accès à la base est impossible)
*/
function acces_formulaire_reservation(){
    if (null == Settings::get('nb_max_resa_form'))
        return FALSE;
    elseif (Settings::get('nb_max_resa_form') == '-1')
        return TRUE;
    else {
        $quota = grr_sql_query1("SELECT COUNT(*) FROM ".TABLE_PREFIX."_entry WHERE (entry_type = -1 AND moderate = 1)");
        // echo $quota;
        if ($quota == -1)
            return FALSE;
        else 
            return ((Settings::get('nb_max_resa_form') - $quota) > 0);
    }
}
/** cheminDetermination($ofl,$od)
 * permet d'obternir les liens depuis la racine GRR selon différents scripts
 *
 */
function cheminDetermination($niv)
{
	if($niv == 1)
		$prefixeChemin = "./";
	elseif($niv == 2)
		$prefixeChemin = "../";
	elseif($niv == 3)
		$prefixeChemin = "../../";

	return $prefixeChemin;
}
/** grrGetOverloadDescArray($ofl,$od)
 *
 * Return an array with all additionnal fields from grr_entry.overload_desc
 * $od - overload_desc of the entry
 * $ofl - overload fields list (depends on the area)
 *
 */
function grrGetOverloadDescArray($ofl,$od)
{
	$overload_array = array();
    foreach ($ofl as $field=>$fieldtype)
    {
        $begin_string = "@".$ofl[$field]["id"]."@";
        $end_string = "@/".$ofl[$field]["id"]."@";
        $l1 = strlen($begin_string);
        $l2 = strlen($end_string);
        $chaine = $od;
        $balise_fermante = 'n';
        $balise_ouvrante = 'n';
        $traitement1 = true;
        $traitement2 = true;
        while (($traitement1 !== false) || ($traitement2 !== false))
        {
            // le premier traitement cherche la prochaine occurrence de $begin_string et retourne la portion de chaine après cette occurrence
            if ($traitement1 != false)
            {
                $chaine1 = strstr ($chaine, $begin_string);
                // retourne la sous-chaîne de $chaine, allant de la première occurrence de $begin_string jusqu'à la fin de la chaîne.
                if ($chaine1 !== false)
                {
                    // on a trouvé une occurrence de $begin_string
                    $balise_ouvrante = 'y';
                    // on sait qu'il y a au moins une balise ouvrante
                    $chaine = substr($chaine1, $l1, strlen($chaine1)- $l1);
                    // on retourne la chaine en ayant éliminé le début de chaine correspondant à $begin_string
                    $result = $chaine;
                    // On mémorise la valeur précédente
                }
                else
                    $traitement1 = false;
            }
            //le 2ème traitement cherche la dernière occurrence de $end_string en partant de la fin et retourne la portion de chaine avant cette occurrence
            if ($traitement2 != false)
            {
                //La boucle suivante a pour effet de déterminer la dernière occurrence de $end_string
                $ind = 0;
                $end_pos = true;
                while ($end_pos !== false)
                {
                    $end_pos = strpos($chaine,$end_string,$ind);
                    if ($end_pos !== false)
                    {
                        $balise_fermante='y';
                        $ind_old = $end_pos;
                        $ind = $end_pos + $l2;
                    }
                    else
                        break;
                }
                //a ce niveau, $ind_old est la dernière occurrence de $end_string trouvée dans $chaine
                if ($ind != 0 )
                {
                    $chaine = substr($chaine,0,$ind_old);
                    $result = $chaine;
                }
                else
                    $traitement2=false;
            }
        }
        // while
        $sql = "SELECT affichage, overload_mail, obligatoire, confidentiel FROM ".TABLE_PREFIX."_overload WHERE id = '".$ofl[$field]["id"]."'";
        $res = grr_sql_query($sql);
        if ($row = mysqli_fetch_assoc($res)) {
          $overload_array[$field]["id"] = $ofl[$field]["id"];
          if (($balise_fermante == 'n' ) || ($balise_ouvrante == 'n')) {
            $overload_array[$field]["valeur"]='';
           } else {
             $overload_array[$field]["valeur"]=urldecode($result);
           }
          $overload_array[$field]["affichage"] = $row["affichage"];
          $overload_array[$field]["overload_mail"] = $row["overload_mail"];
          $overload_array[$field]["obligatoire"] = $row["obligatoire"];
          $overload_array[$field]["confidentiel"] = $row ["confidentiel"];
        }
    }
    return $overload_array;
}

// Les lignes suivantes permettent la compatibilité de GRR avec la variable register_global à off
unset($day);
if (isset($_GET["day"]))
{
	$day = $_GET["day"];
	settype($day,"integer");
	if ($day < 1)
		$day = 1;
	if ($day > 31)
		$day = 31;
}
unset($month);
if (isset($_GET["month"]))
{
	$month = $_GET["month"];
	settype($month,"integer");
	if ($month < 1)
		$month = 1;
	if ($month > 12)
		$month = 12;
}
unset($year);
if (isset($_GET["year"]))
{
	$year = $_GET["year"];
	settype($year,"integer");
	if ($year < 1900)
		$year = 1900;
	if ($year > 2100)
		$year = 2100;
}

function rep_opt_to_iCal_args(string $rep_opt) { // Convertis 'rep_opt' en jours pour la répétition dans les iCal
	// ex: 0100100 -> BYDAY=MO,THU
	$days = array('SU','MO','TU','WE','TH','FR','SA');
	$rep_opt_array = str_split($rep_opt);
	$output = array();
	$i = 0;
	foreach ($rep_opt_array as $value) {
		if ($value == "0") {
			$i++;
			continue;
		}
		$output[] = $days[$i];
		$i++;
	}
	return $output;
}

function restoreEntries(array $entries) {
	$query = "UPDATE ".TABLE_PREFIX."_entry SET supprimer = 0 WHERE id IN (".implode(',', $entries).")";
	$res = grr_sql_query($query);
	if (!$res) {
		fatal_error(0, grr_sql_error());
	}
	return;
}

function get_xth_day_of_month($timestamp) {
    $joursiCal = [
        'Monday'    => 'MO',
        'Tuesday'   => 'TU',
        'Wednesday' => 'WE',
        'Thursday'  => 'TH',
        'Friday'    => 'FR',
        'Saturday'  => 'SA',
        'Sunday'    => 'SU'
    ];

    $jour = date('l', $timestamp); // "Monday", "Tuesday", etc.
    $numjour = (int)date('j', $timestamp);     // 1 à 31

    $iCalJour = $joursiCal[$jour];

    // Xème jour - ex: 1 pour 1er Lundi du mois
    $position = ceil($numjour / 7);

	return [$iCalJour, (int)$position];
}

?>