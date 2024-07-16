<?php
/**
 * include/functions.inc.php
 * fichier Bibliothèque de fonctions de GRR
 * Dernière modification : $Date: 2024-07-16 11:31$
 * @author    JeromeB & Laurent Delineau & Marc-Henri PAMISEUX & Yan Naessens
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

function returnmsg($type,$test, $status, $msg = '')
{
	echo encode_message_utf8('<div class="alert alert-'.$type.'" role="alert"><h3>'.$test);
	echo encode_message_utf8($status)."</h3>";
	if ($msg != '')
		echo encode_message_utf8("($msg)"),PHP_EOL;
	echo '</div>',PHP_EOL;
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

// $type = 1: Fonction Calendrier hors réservation ; 2; Fonction Calendrier feries ; 3 : calendrier vacances (scolaires par défaut)
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
				if($type == 1)
					$day = grr_sql_query1("SELECT day FROM ".TABLE_PREFIX."_calendar WHERE day='$temp'");
				elseif ($type == 2)
                    $day = grr_sql_query1("SELECT day FROM ".TABLE_PREFIX."_calendrier_feries WHERE day='$temp'");
                else
					$day = grr_sql_query1("SELECT day FROM ".TABLE_PREFIX."_calendrier_vacances WHERE day='$temp'");
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
 * Fonction de verification d'access
 * @param int $level
 */
function check_access($level, $back)
{
	if (authGetUserLevel(getUserName(), -1, 'area') < $level)
	{
		showAccessDenied($back);
		exit();
	}
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
		if ((Settings::get("use_grr_url") == "y") && (Settings::get("grr_url") != ""))
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

	if (Settings::get("envoyer_email_avec_formulaire") == "yes")
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
            if (validate_email($_email))
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

function decode_options($a,$modele){
    // suppose que l'on a une chaîne $a de {V,F} de longueur égale à celle du $modele
    // renvoie un tableau de booléens True, False indexé par les valeurs du modèle
    $choix = array();
    $l = count($modele);
    for($i=0; $i<$l; $i++){
        $choix[$modele[$i]] = ((isset($a))&&('V' == $a[$i]))? TRUE: FALSE;
    }
    return $choix;
}

/**
 *Fonction qui calcule $room, $area et $id_site à partir de $_GET['room'], $_GET['area'], $_GET['id_site']
 */
function Definition_ressource_domaine_site()
{
	global $room, $area, $id_site;
	if (isset($_GET['room']) && $_GET['room'] != 'all')
	{
		$room = intval(clean_input($_GET['room']));
		$area = mrbsGetRoomArea($room);
		$id_site = mrbsGetAreaSite($area);
	}
	else
	{
		$room = 0;
		if (isset($_GET['area']))
		{
			$area = intval(clean_input($_GET['area']));
			$id_site = mrbsGetAreaSite($area);
		}
		else
		{
			$area = NULL;
			if (isset($_GET["id_site"]))
			{
				$id_site = intval(clean_input($_GET["id_site"]));
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

function bouton_retour_haut()
{
	echo '<script type="text/javascript">',PHP_EOL,'$(function()',PHP_EOL,'{',PHP_EOL,'$(window).scroll(function()',PHP_EOL,'{',PHP_EOL,
		'if ($(window).scrollTop() != 0)',PHP_EOL,'$("#toTop").fadeIn();',PHP_EOL,'else',PHP_EOL,'$("#toTop").fadeOut();',PHP_EOL,
		'});',PHP_EOL,'$("#toTop").click(function()',PHP_EOL,'{',PHP_EOL,'$("body,html").animate({scrollTop:0},800);',PHP_EOL,
		'});',PHP_EOL,'});',PHP_EOL,'</script>',PHP_EOL;
}

function bouton_aller_bas()
{
	echo '<script type="text/javascript">',PHP_EOL,'$(function()',PHP_EOL,'{',PHP_EOL,'$(window).scroll(function()',PHP_EOL,'{',PHP_EOL,
		'if ($(this).scrollTop() != 800)',PHP_EOL,'$("#toBot").fadeIn();',PHP_EOL,'else',PHP_EOL,'$("#toBot").fadeOut();',PHP_EOL,
		'});',PHP_EOL,'$("#toBot").click(function()',PHP_EOL,'{',PHP_EOL,'$("body,html").animate({scrollTop:800},0);',PHP_EOL,
		'});',PHP_EOL,'});',PHP_EOL,'</script>',PHP_EOL;
}
/**
 *function affiche_ressource_empruntee
 *- $id_room : identifiant de la ressource
 *- Si la ressource est empruntée, affiche une icône avec un lien vers la réservation pour laquelle la ressource est empruntée.
 * @param string $id_room
 * @return string
 */
function affiche_ressource_empruntee($id_room, $type = "logo")
{
	$active_ressource_empruntee = grr_sql_query1("SELECT active_ressource_empruntee FROM ".TABLE_PREFIX."_room WHERE id = '".$id_room."'");
	if ($active_ressource_empruntee == 'y')
	{
		$id_resa = grr_sql_query1("SELECT id FROM ".TABLE_PREFIX."_entry WHERE room_id = '".$id_room."' AND statut_entry='y'");
		if ($id_resa != -1)
		{
			if ($type == "logo")
				echo '<a href="app.php?p=vuereservation&id='.$id_resa.'"><img src="img_grr/buzy_big.png" alt="'.get_vocab("ressource_actuellement_empruntee").'" title="'.get_vocab("reservation_en_cours").'" width="30" height="30" class="image" /></a>'.PHP_EOL;
			else if ($type == "texte")
			{
				$beneficiaire = grr_sql_query1("SELECT beneficiaire FROM ".TABLE_PREFIX."_entry WHERE room_id = '".$id_room."' AND statut_entry='y'");
				$beneficiaire_ext = grr_sql_query1("SELECT beneficiaire_ext FROM ".TABLE_PREFIX."_entry WHERE room_id = '".$id_room."' AND statut_entry='y'");
				echo '<br /><b><span class="avertissement">'.PHP_EOL;
				echo '<img src="img_grr/buzy_big.png" alt="'.get_vocab("ressource_actuellement_empruntee").'" title="'.get_vocab("ressource_actuellement_empruntee").'" width="30" height="30" class="image" />'.PHP_EOL;
				echo get_vocab("ressource_actuellement_empruntee").' '.get_vocab("nom_emprunteur").get_vocab("deux_points").affiche_nom_prenom_email($beneficiaire,$beneficiaire_ext,"withmail");
				echo ' <a href="app.php?p=vuereservation&id='.$id_resa.'&amp;mode=page">'.get_vocab("entryid").$id_resa.'</a>'.PHP_EOL.'</span></b>'.PHP_EOL;
			}
			else
				return "yes";
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
	$valeur = "";
	$active_ressource_empruntee = grr_sql_query1("SELECT active_ressource_empruntee FROM ".TABLE_PREFIX."_room WHERE id = '".$id_room."'");
	if ($active_ressource_empruntee == 'y')
	{
		$id_resa = grr_sql_query1("SELECT id FROM ".TABLE_PREFIX."_entry WHERE room_id = '".$id_room."' AND statut_entry='y'");
		if ($id_resa != -1)
		{
			if ($type == "logo")
				$valeur = '<a href="app.php?p=vuereservation&id='.$id_resa.'"><img src="img_grr/buzy_big.png" alt="'.get_vocab("ressource_actuellement_empruntee").'" title="'.get_vocab("reservation_en_cours").'" width="30" height="30" class="image" /></a>'.PHP_EOL;
			else if ($type == "texte")
			{
				$beneficiaire = grr_sql_query1("SELECT beneficiaire FROM ".TABLE_PREFIX."_entry WHERE room_id = '".$id_room."' AND statut_entry='y'");
				$beneficiaire_ext = grr_sql_query1("SELECT beneficiaire_ext FROM ".TABLE_PREFIX."_entry WHERE room_id = '".$id_room."' AND statut_entry='y'");
				$valeur = '<br /><b><span class="avertissement">'.PHP_EOL;
				$valeur .= '<img src="img_grr/buzy_big.png" alt="'.get_vocab("ressource_actuellement_empruntee").'" title="'.get_vocab("ressource_actuellement_empruntee").'" width="30" height="30" class="image" />'.PHP_EOL;
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
 * FUNCTION: how_many_connected()
 * DESCRIPTION: Si c'est un admin qui est connecté, affiche le nombre de personnes actuellement connectées.
 */
function how_many_connected()
{
	if (authGetUserLevel(getUserName(), -1) >= 6)
	{
		$sql = "SELECT login FROM ".TABLE_PREFIX."_log WHERE end > now()";
		$res = grr_sql_query($sql);
		$nb_connect = grr_sql_count($res);
		grr_sql_free($res);

		if (@file_exists('./admin_access_area.php')){
			$racineAd = "./";
		}else{
			$racineAd = "./admin/";
		}

		if ($nb_connect == 1)
			echo "<a href='{$racineAd}admin.php?p=admin_view_connexions'>".$nb_connect.get_vocab("one_connected")."</a>".PHP_EOL;
		else
			echo "<a href='{$racineAd}admin.php?p=admin_view_connexions'>".$nb_connect.get_vocab("several_connected")."</a>".PHP_EOL;
		if (verif_version())
			affiche_pop_up(get_vocab("maj_bdd_not_update").get_vocab("please_go_to_admin_maj.php"),"force");
	}
}

/**
 * FUNCTION: nb_connecte()
 * DESCRIPTION: Si c'est un admin qui est connecté, affiche le nombre de personnes actuellement connectées.
 */
function nb_connecte()
{
	$lien = "";

	if (authGetUserLevel(getUserName(), -1) >= 6)
	{
		$sql = "SELECT login FROM ".TABLE_PREFIX."_log WHERE end > now()";
		$res = grr_sql_query($sql);
		$nb_connect = grr_sql_count($res);
		grr_sql_free($res);

		if ($nb_connect == 1)
			$lien = $nb_connect.get_vocab("one_connected");
		else
			$lien = $nb_connect.get_vocab("several_connected");
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
    $res = false;
    if (authGetUserLevel($user,-1) > 5) // admin général
    {
        $sql = "SELECT e.id,r.room_name,e.start_time FROM ".TABLE_PREFIX."_entry e JOIN ".TABLE_PREFIX."_room r ON e.room_id = r.id WHERE e.moderate = 1 AND e.supprimer = 0 ";
        $res = grr_sql_query($sql);
    }
    elseif (isset($_GET['id_site']) && (authGetUserLevel($user,intval($_GET['id_site']),'site') > 4)) // admin du site
    {
        $sql = "SELECT e.id,r.room_name,e.start_time FROM ".TABLE_PREFIX."_entry e JOIN ".TABLE_PREFIX."_room r ON e.room_id = r.id JOIN ".TABLE_PREFIX."_j_site_area j ON r.area_id = j.id_area WHERE (j.id_site = ".intval($_GET['id_site'])." AND e.moderate = 1  AND e.supprimer = 0)";
        $res = grr_sql_query($sql);
    }
    elseif (isset($_GET['area']) && (authGetUserLevel($user,intval($_GET['area']),'area') > 3)) // admin du domaine
    {
        $sql = "SELECT e.id,r.room_name,e.start_time FROM ".TABLE_PREFIX."_entry e JOIN ".TABLE_PREFIX."_room r ON e.room_id = r.id JOIN ".TABLE_PREFIX."_area a ON r.area_id = a.id WHERE (a.id = ".intval($_GET['area'])." AND e.moderate = 1 AND e.supprimer = 0)";
        $res = grr_sql_query($sql);
    }
    elseif (isset($_GET['room']) && (authGetUserLevel($user,intval($_GET['room']),'room') > 2)) // gestionnaire de la ressource
    {
        $sql = "SELECT e.id,r.room_name,e.start_time FROM ".TABLE_PREFIX."_entry e JOIN ".TABLE_PREFIX."_room r ON e.room_id = r.id WHERE (e.moderate = 1 AND e.supprimer = 0 AND e.room_id = ".intval($_GET['room']).") ";
        $res = grr_sql_query($sql);
    }
    if ($res)
    {
        $i = 0; 
        while (($a = grr_sql_row($res, $i++))) 
        {
            $resas[$i] = array('id' => $a[0],'room' => $a[1],'start_time' => $a[2]);
        }
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
            $result = @ldap_search($ds, "{$ldap_group_base}","(& (cn={$grp}) $ldap_group_filter )", $members_attr);
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
		name = '".protect_data_sql($row['name'])."',
		type = '".$row['type']."',
		description = '".protect_data_sql($row['description'])."',
		statut_entry = '".$row['statut_entry']."',
		option_reservation = '".$row['option_reservation']."',
		overload_desc  = '".protect_data_sql($row['overload_desc'])."',
		moderate = '".$row['moderate']."',
		motivation_moderation = '".protect_data_sql(strip_tags($motivation_moderation))."',
		login_moderateur = '".protect_data_sql($login_moderateur)."'
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
		name = '".protect_data_sql($row['name'])."',
		type = '".$row['type']."',
		description = '".protect_data_sql($row['description'])."',
		statut_entry = '".$row['statut_entry']."',
		option_reservation = '".$row['option_reservation']."',
		overload_desc  = '".protect_data_sql($row['overload_desc'])."',
		moderate = '".$row['moderate']."',
		motivation_moderation = '".protect_data_sql(strip_tags($motivation_moderation))."',
		login_moderateur = '".protect_data_sql($login_moderateur)."'";
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

# Remove backslash-escape quoting if PHP is configured to do it with
# magic_quotes_gpc. Use this whenever you need the actual value of a GET/POST
# form parameter (which might have special characters) regardless of PHP's
# magic_quotes_gpc setting.
function unslashes($s)
{
	if (function_exists('get_magic_quotes_gpc') && @get_magic_quotes_gpc())
		return stripslashes($s);
	else
		return $s;
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

// Traite les données avant insertion dans une requête SQL
function protect_data_sql($_value)
{
	global $use_function_mysql_real_escape_string;
	//if (get_magic_quotes_gpc())
	if (function_exists('get_magic_quotes_gpc') && @get_magic_quotes_gpc())
		$_value = stripslashes($_value);
	if (!is_numeric($_value) && $_value != null)
		$_value = mysqli_real_escape_string($GLOBALS['db_c'], $_value);

	return $_value;
}

// Traite les données envoyées par la methode GET|POST de la variable $_GET|POST["page"], renvoie "day" si la page n'est pas définie
function verif_page()
{
	$pages = array("day", "week", "month", "week_all", "month_all", "month_all2", "year", "year_all");
    $page = (isset($_GET["page"]))? $_GET["page"]:((isset($_POST["page"]))? $_POST["page"]:NULL);
    if (isset($page))
	{
		if (in_array($page, $pages))
			return $page;
		else
			return "day";
	}
	else
		return "day";
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
		$page_accueil = 'day.php?noarea=';
	// le paramètre noarea ne sert à rien, il est juste là pour éviter un cas particulier à traiter avec &amp;id_site= et $param
	else if ($defaultroom == - 1)
		$page_accueil = 'day.php?area='.$defaultarea;
	else if ($defaultroom == - 2)
		$page_accueil = 'week_all.php?area='.$defaultarea;
	else if ($defaultroom == - 3)
		$page_accueil = 'month_all.php?area='.$defaultarea;
	else if ($defaultroom == -4)
		$page_accueil = 'month_all2.php?area='.$defaultarea;
	else
		$page_accueil = 'week.php?area='.$defaultarea.'&amp;room='.$defaultroom;
	if ((Settings::get("module_multisite") == "Oui") && ($defaultsite > 0))
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
		if (isset($_GET['default_language']))
		{
			$_SESSION['default_language'] = alphanum(clean_input($_GET['default_language']));
			if (isset($_SESSION['chemin_retour']) && ($_SESSION['chemin_retour'] != ''))
				header("Location: ".$_SESSION['chemin_retour']);
			else
				header("Location: ".traite_grr_url());
			die();
		}
	}
	else
	{
		if (Settings::get("default_css"))
			$sheetcss = Settings::get("default_css");
		else
			$sheetcss = "default";
		if (isset($_GET['default_language']))
		{
			$_SESSION['default_language'] = alphanum(clean_input($_GET['default_language']));
			if (isset($_SESSION['chemin_retour']) && ($_SESSION['chemin_retour'] != ''))
				header("Location: ".$_SESSION['chemin_retour']);
			else
				header("Location: ".traite_grr_url());
			die();
		}
	}
	global $vocab, $charset_html, $unicode_encoding, $clock_file, $use_select2, $gcDossierCss, $version_grr;
	header('Content-Type: text/html; charset=utf-8');
	/*if (!isset($_COOKIE['open']))
	{
		setcookie("open", "true", time()+3600, "", "", false, false);
	}*/
    if (!isset($_COOKIE['open']))
    {
        header('Set-Cookie: open=true; SameSite=Lax;');
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
	//$a .= '<link rel="stylesheet" type="text/css" href="bootstrap/css/mod_bootstrap.css" />'.PHP_EOL;

	if (isset($use_select2))
	{
		$a .= '<link rel="stylesheet" type="text/css" href="bootstrap/css/select2.css?v='.$version_grr.'" />'.PHP_EOL;
		$a .= '<link rel="stylesheet" type="text/css" href="bootstrap/css/select2-bootstrap.css?v='.$version_grr.'" />'.PHP_EOL;
		$a .= '<link rel="stylesheet" type="text/css" href="bootstrap/css/bootstrap-multiselect.css?v='.$version_grr.'">'.PHP_EOL;
		$a .= '<link rel="stylesheet" type="text/css" href="bootstrap/css/bootstrap-clockpicker.min.css?v='.$version_grr.'">'.PHP_EOL;
	}
	$a .= '<link rel="stylesheet" type="text/css" href="themes/default/css/style.css?v='.$version_grr.'" />'.PHP_EOL; // le style par défaut
	
	if ((isset($_GET['pview'])) && ($_GET['pview'] == 1))
		$a .= '<link rel="stylesheet" type="text/css" href="themes/print/css/style.css?v='.$version_grr.'" />'.PHP_EOL;
		$a .= '<link rel="stylesheet" type="text/css" href="themes/'.$sheetcss.'/css/style.css?v='.$version_grr.'" />'.PHP_EOL; // le style couleurs prédéfinis
		if($sheetcss == "perso" && file_exists("personnalisation/".$gcDossierCss."/perso.css"))
			$a .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"personnalisation/".$gcDossierCss."/perso.css?".Settings::get("sp_time")."\" />".PHP_EOL; // style perso via admin
	
	$a .= '<script type="text/javascript" src="js/jquery.min.js?v='.$version_grr.'"></script>'.PHP_EOL;
	$a .= '<script type="text/javascript" src="js/jquery-ui.min.js?v='.$version_grr.'"></script>'.PHP_EOL;
	$a .= '<script type="text/javascript" src="js/jquery.validate.js?v='.$version_grr.'"></script>'.PHP_EOL;
	$a .= '<script type="text/javascript" src="js/jquery-ui-timepicker-addon.js?v='.$version_grr.'"></script>'.PHP_EOL;
	$a .= '<script type="text/javascript" src="bootstrap/js/bootstrap.min.js?v='.$version_grr.'"></script>'.PHP_EOL;
	$a .= '<script type="text/javascript" src="js/menu.js?v='.$version_grr.'"></script>'.PHP_EOL;
	$a .= '<script type="text/javascript" src="js/popup.js?v='.$version_grr.'" charset="utf-8"></script>'.PHP_EOL;
	$a .= '<script type="text/javascript" src="js/functions.js?v='.$version_grr.'" ></script>'.PHP_EOL;
	if (isset($use_select2))
	{
		$a .= '<script type="text/javascript" src="js/bootstrap-clockpicker.js"></script>'.PHP_EOL;
		$a .= '<script type="text/javascript" src="js/bootstrap-multiselect.js"></script>'.PHP_EOL;
		$a .= '<script type="text/javascript" src="js/select2.min.js"></script>'.PHP_EOL;
		$a .= '<script type="text/javascript" src="js/select2_locale_fr.js"></script>'.PHP_EOL;
	}
	if (isset($use_tooltip_js))
		echo '<script type="text/javascript" src="js/tooltip.min.js?v='.$version_grr.'"></script>'.PHP_EOL;
	if (!isset($_SESSION['selection']))
		$a .= '<script type="text/javascript" src="js/selection.js?v='.$version_grr.'" ></script>'.PHP_EOL;
	if (@file_exists('js/'.$clock_file))
		$a .= '<script type="text/javascript" src="js/'.$clock_file.'?v='.$version_grr.'"></script>'.PHP_EOL;
	if (substr(phpversion(), 0, 1) < 7)
		$a .= get_vocab('not_php');

	$a .= '</head>'.PHP_EOL;
	$a .= '<body>'.PHP_EOL;
	return $a;
}


/*
** Fonction qui affiche le header
*/
function print_header($day = '', $month = '', $year = '', $type_session = 'with_session')
{
	global $niveauDossier, $vocab, $search_str, $grrSettings, $clock_file, $desactive_VerifNomPrenomUser, $grr_script_name;
	global $use_prototype, $use_admin, $use_tooltip_js, $desactive_bandeau_sup, $id_site, $use_select2, $gcDossierImg;
	
	if($_SESSION['changepwd'] == 1 && $grr_script_name != 'changepwd.php'){
		header("Location: ./changepwd.php");
	}

	if ($niveauDossier == 2){
		$adm = 1;
		$racine = "../";
		$racineAd = "./";
	}else{
		$adm = 0;
		$racine = "./";
		$racineAd = "./admin/";
	}

	include $racine."/include/hook.class.php";

	if (!($desactive_VerifNomPrenomUser))
		$desactive_VerifNomPrenomUser = 'n';
	// On vérifie que les noms et prénoms ne sont pas vides
	VerifNomPrenomUser($type_session);
	if ($type_session == "with_session")
		echo begin_page(Settings::get("company"),"with_session");
	else
		echo begin_page(Settings::get("company"),"no_session");

	$resulHook = Hook::Appel("hookHeader2");
	echo $resulHook['hookHeader2'];

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
		if (!(isset($search_str)))
			$search_str = get_vocab("search_for");
		if (empty($search_str))
			$search_str = "";
		if (!(isset($desactive_bandeau_sup) && ($desactive_bandeau_sup == 1) && ($type_session != 'with_session')))
		{

			// HOOK
			$resulHook = Hook::Appel("hookHeader1");
			echo $resulHook['hookHeader1'];

			// On fabrique une date valide pour la réservation si ce n'est pas le cas
			$date_ = mktime(0, 0, 0, $month, $day, $year);
			if ($date_ < Settings::get("begin_bookings"))
				$date_ = Settings::get("begin_bookings");
			else if ($date_ > Settings::get("end_bookings"))
				$date_ = Settings::get("end_bookings");
			$day   = date("d",$date_);
			$month = date("m",$date_);
			$year  = date("Y",$date_);
			echo '<div id="toppanel">'.PHP_EOL;
			echo '<div id="panel">'.PHP_EOL;
			echo '<table id="header">'.PHP_EOL;
			echo '<tr>'.PHP_EOL;
			//Logo
			$nom_picture = $racine."personnalisation/".$gcDossierImg."/logos/".Settings::get("logo");
			if ((Settings::get("logo") != '') && (@file_exists($nom_picture)))
				echo '<td class="logo" height="100">'.PHP_EOL.'<a href="'.$racine.page_accueil('yes').'day='.$day.'&amp;year='.$year.'&amp;month='.$month.'"><img src="'.$nom_picture.'" alt="logo"/></a>'.PHP_EOL.'</td>'.PHP_EOL;
			//Accueil
			echo '<td class="accueil ">',PHP_EOL,'<h2>',PHP_EOL,'<a href="'.$racine.page_accueil('yes'),'day=',$day,'&amp;year=',$year,'&amp;month=',$month,'">',Settings::get("company"),'</a>',PHP_EOL,'</h2>',PHP_EOL, Settings::get('message_accueil'),'</td>',PHP_EOL;
			//Mail réservation
			$sql = "SELECT value FROM ".TABLE_PREFIX."_setting WHERE name='mail_etat_destinataire'";
			$res = grr_sql_query1($sql);
			grr_sql_free($res);

			if ( ( $res == 1 && $type_session == "no_session" ) || ( ( $res == 1 || $res == 2) && $type_session == "with_session" && (authGetUserLevel(getUserName(), -1, 'area')) == 1  ) )
			{
				echo '<td class="contactformulaire">',PHP_EOL,'<input class="btn btn-default" type="submit" rel="popup_name" value="'.get_vocab('reserver').'" onClick="javascript:location.href=\'app.php?p=contactresa&day=',$day,'&amp;month=',$month,'&amp;year=',$year,'\'" >',PHP_EOL,'</td>',PHP_EOL;
			}
			// Administration
			if ($type_session == "with_session")
			{
                $user_name = getUserName();
                $mess_resa = resaToModerate($user_name);
				if ((authGetUserLevel($user_name, -1, 'area') >= 4) || (authGetUserLevel($user_name, -1, 'user') == 1) || ($mess_resa != ''))
				{
					echo '<td class="administration">'.PHP_EOL;
					if ((authGetUserLevel($user_name, -1, 'area') >= 4) || (authGetUserLevel($user_name, -1, 'user') == 1))
                        echo "<br><a href='{$racineAd}admin.php?p=admin_accueil&day={$day}&amp;month={$month}&amp;year={$year}'>".get_vocab('admin')."</a>".PHP_EOL;
					if (authGetUserLevel(getUserName(), -1, 'area') >= 6)
					{
						echo '<br />'.PHP_EOL;
						how_many_connected();
                        echo "<br />";
					}
                    echo "<p class='avertissement'>".$mess_resa."</p>";
					echo '</td>'.PHP_EOL;
				}
			}
			if ($type_session != "with_session")
				echo '<script>selection()</script>'.PHP_EOL;
			echo '<td class="configuration" >'.PHP_EOL;
			if (@file_exists('js/'.$clock_file))
			{
				echo '<div class="clock">'.PHP_EOL;
				echo '<div id="Date">'.PHP_EOL;
				echo '&nbsp;<span id="hours"></span>'.PHP_EOL;
				echo 'h'.PHP_EOL;
				echo '<span id="min"></span>'.PHP_EOL;
				echo '</div></div>'.PHP_EOL;
			}
			$_SESSION['chemin_retour'] = '';
			if (isset($_SERVER['QUERY_STRING']) && ($_SERVER['QUERY_STRING'] != ''))
			{
				$parametres_url = htmlspecialchars($_SERVER['QUERY_STRING'])."&amp;";
				$_SESSION['chemin_retour'] = traite_grr_url($grr_script_name)."?". $_SERVER['QUERY_STRING'];
				echo '<a onclick="charger();" href="'.traite_grr_url($grr_script_name).'?'.$parametres_url.'default_language=fr-fr"><img src="'.$racine.'img_grr/fr_dp.png" alt="France" title="france" width="20" height="13" class="image" /></a>'.PHP_EOL;
				echo '<a onclick="charger();" href="'.traite_grr_url($grr_script_name).'?'.$parametres_url.'default_language=de-de"><img src="'.$racine.'img_grr/de_dp.png" alt="Deutch" title="deutch" width="20" height="13" class="image" /></a>'.PHP_EOL;
				echo '<a onclick="charger();" href="'.traite_grr_url($grr_script_name).'?'.$parametres_url.'default_language=en-gb"><img src="'.$racine.'img_grr/en_dp.png" alt="English" title="English" width="20" height="13" class="image" /></a>'.PHP_EOL;
				echo '<a onclick="charger();" href="'.traite_grr_url($grr_script_name).'?'.$parametres_url.'default_language=it-it"><img src="'.$racine.'img_grr/it_dp.png" alt="Italiano" title="Italiano" width="20" height="13" class="image" /></a>'.PHP_EOL;
				echo '<a onclick="charger();" href="'.traite_grr_url($grr_script_name).'?'.$parametres_url.'default_language=es-es"><img src="'.$racine.'img_grr/es_dp.png" alt="Spanish" title="Spanish" width="20" height="13" class="image" /></a>'.PHP_EOL;
			}
			if ($type_session == 'no_session')
			{
				$resulHook = Hook::Appel("hookLienConnexion2");
				if ((Settings::get('sso_statut') == 'cas_visiteur') || (Settings::get('sso_statut') == 'cas_utilisateur'))
				{
					echo '<br /> <a href="index.php?force_authentification=y">'.get_vocab("authentification").'</a>'.PHP_EOL;
					echo '<br /> <small><i><a href="login.php">'.get_vocab("connect_local").'</a></i></small>'.PHP_EOL;
				} elseif($resulHook['hookLienConnexion2'] != ""){
					echo $resulHook['hookLienConnexion2'];	
				}
				else {
					echo '<br /> <a href="login.php">'.get_vocab("connect").'</a>'.PHP_EOL;
				}
			}
			else
			{
				$resulHook = Hook::Appel("hookLienConnexion3");
				if( strlen(htmlspecialchars($_SESSION['prenom']).' '.htmlspecialchars($_SESSION['nom'])) > 40 )
					$nomAffichage =  htmlspecialchars($_SESSION['nom']);
				else
					$nomAffichage =  htmlspecialchars($_SESSION['prenom']).' '.htmlspecialchars($_SESSION['nom']);
			
				echo '<br /><a href="'.$racine.'/compte/compte.php?day='.$day.'&amp;year='.$year.'&amp;month='.$month.'">'. $nomAffichage .' - '.get_vocab("manage_my_account").'</a>'.PHP_EOL;
				if (verif_access_search(getUserName()))
					echo '<br/><a href="'.$racine.'app.php?p=report">'.get_vocab("report").'</a>'.PHP_EOL;
				if($resulHook['hookLienConnexion3'] != "")
					echo $resulHook['hookLienConnexion3'];
				$disconnect_link = false;
				if (!((Settings::get("cacher_lien_deconnecter") == 'y') && (isset($_SESSION['est_authentifie_sso']))))
				{
					$disconnect_link = true;
					if (Settings::get("authentification_obli") == 1)
						echo '<br /> <a href="'.$racine.'app.php?p=deconnexion&auto=0" >'.get_vocab('disconnect').'</a>'.PHP_EOL;
					else
						echo '<br /> <a href="'.$racine.'app.php?p=deconnexion&auto=0&amp;redirect_page_accueil=yes" >'.get_vocab('disconnect').'</a>'.PHP_EOL;
				}
				if ((Settings::get("Url_portail_sso") != '') && (isset($_SESSION['est_authentifie_sso'])))
				{
					if ($disconnect_link)
						echo ' - '.PHP_EOL;
					else
						echo '<br />'.PHP_EOL;
					echo '<a href="'.Settings::get("Url_portail_sso").'">'.get_vocab("Portail_accueil").'</a>'.PHP_EOL;
				}
				if ((Settings::get('sso_statut') == 'lasso_visiteur') || (Settings::get('sso_statut') == 'lasso_utilisateur'))
				{
					echo '<br />';
					if ($_SESSION['lasso_nameid'] == NULL)
						echo '<a href="lasso/federate.php">'.get_vocab('lasso_federate_this_account').'</a>'.PHP_EOL;
					else
						echo '<a href="lasso/defederate.php">'.get_vocab('lasso_defederate_this_account').'</a>'.PHP_EOL;
				}
			}
			echo '</td>'.PHP_EOL;
			echo '</tr>'.PHP_EOL;
			echo '</table>'.PHP_EOL;
			echo '</div>'.PHP_EOL;
			echo '<a id="open" class="open" href="#"><span class="glyphicon glyphicon-arrow-up"><span class="glyphicon glyphicon-arrow-down"></span></span></a>'.PHP_EOL;
			echo '</div>'.PHP_EOL;
		}
	}
}

function print_header_twig($day = '', $month = '', $year = '', $type_session = 'with_session')
{
	global $niveauDossier, $vocab, $search_str, $grrSettings, $clock_file, $desactive_VerifNomPrenomUser, $grr_script_name;
	global $use_prototype, $use_admin, $use_tooltip_js, $desactive_bandeau_sup, $id_site, $use_select2, $d, $gcDossierImg, $gcDossierCss, $version_grr;
	
	if(isset($_SESSION['changepwd']) && $_SESSION['changepwd'] == 1 && $grr_script_name != 'changepwd.php'){
		header("Location: ./changepwd.php");
	}

	if ($niveauDossier == 1)
        $racine = "./";
	else
		$racine = "../";


	if (!($desactive_VerifNomPrenomUser))
		$desactive_VerifNomPrenomUser = 'n';
	// On vérifie que les noms et prénoms ne sont pas vides
	VerifNomPrenomUser($type_session);

	if ($type_session == "with_session")
	{
		if (isset($_SESSION['default_style']))
		{
			if($_SESSION['default_style'] == "perso" && file_exists($racine."personnalisation/".$gcDossierCss."/perso.css"))
				$d['sheetcssperso'] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"".$racine."personnalisation/".$gcDossierCss."/perso.css?".Settings::get("sp_time")."\" />";

			$d['sheetcss'] = 'themes/'.$_SESSION['default_style'].'/css/style.css?v='.$version_grr;
		}
		else
			$d['sheetcss'] = 'themes/default/css/style.css?v='.$version_grr; // utilise le thème par défaut s'il n'a pas été défini... à voir YN le 11/04/2018
		if (isset($_GET['default_language']))
		{
			$_SESSION['default_language'] = alphanum($_GET['default_language']);
			if (isset($_SESSION['chemin_retour']) && ($_SESSION['chemin_retour'] != ''))
				header("Location: ".$_SESSION['chemin_retour']);
			else
				header("Location: ".traite_grr_url());
			die();
		}
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
			$d['sheetcss'] = 'themes/default/css/style.css?v='.$version_grr.'';
		if (isset($_GET['default_language']))
		{
			$_SESSION['default_language'] = alphanum($_GET['default_language']);
			if (isset($_SESSION['chemin_retour']) && ($_SESSION['chemin_retour'] != ''))
				header("Location: ".$_SESSION['chemin_retour']);
			else
				header("Location: ".traite_grr_url());
			die();
		}
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
		if (!(isset($search_str)))
			$search_str = get_vocab("search_for");
		if (empty($search_str))
			$search_str = "";
		if (!(isset($desactive_bandeau_sup) && ($desactive_bandeau_sup == 1) && ($type_session != 'with_session')))
		{

			// HOOK
			$resulHook = Hook::Appel("hookHeader1");
			$d['hookHeader1'] = $resulHook['hookHeader1'];

			// On fabrique une date valide pour la réservation si ce n'est pas le cas
			$date_ = mktime(0, 0, 0, $month, $day, $year);
			if ($date_ < Settings::get("begin_bookings"))
				$date_ = Settings::get("begin_bookings");
			else if ($date_ > Settings::get("end_bookings"))
				$date_ = Settings::get("end_bookings");
			$day   = date("d",$date_);
			$month = date("m",$date_);
			$year  = date("Y",$date_);


			//Parmetre url fixe compte / admin
			$paramUrl = 'day='.$day.'&amp;year='.$year.'&amp;month='.$month;
			$d['paramUrl'] = $paramUrl;

			//Accueil
			$d['pageAccueil'] = $racine.page_accueil('yes').$paramUrl;

			//Logo
			$nom_picture = $racine."personnalisation/".$gcDossierImg."/logos/".Settings::get("logo");
			if ((Settings::get("logo") != '') && (@file_exists($nom_picture)))
				$d['logo'] = $nom_picture;
			
			//Mail réservation
			$sql = "SELECT value FROM ".TABLE_PREFIX."_setting WHERE name='mail_etat_destinataire'";
			$res = grr_sql_query1($sql);
			grr_sql_free($res);

			if ( ( $res == 1 && $type_session == "no_session" ) || ( ( $res == 1 || $res == 2) && $type_session == "with_session" && (authGetUserLevel(getUserName(), -1, 'area')) == 1  ) )
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
				if ((authGetUserLevel($user_name, -1, 'area') >= 4) || (authGetUserLevel($user_name, -1, 'user') == 1) || ($d['mess_resa'] != ''))
				{
					if ((authGetUserLevel($user_name, -1, 'area') >= 4) || (authGetUserLevel($user_name, -1, 'user') == 1))
                       $d['lienAdmin'] = 'admin/admin.php?p=admin_accueil&'.$paramUrl;
					if (authGetUserLevel(getUserName(), -1, 'area') >= 6)
						$d['nbConnecte'] = nb_connecte();
				}
			}

			// ???
			//if ($type_session != "with_session")
			//	echo '<script>selection()</script>'.PHP_EOL;

			// Heure selon la langue
			if (@file_exists($racine.'js/'.$clock_file))
				$d['jsHeure'] = $clock_file;

			$_SESSION['chemin_retour'] = '';
			if (isset($_SERVER['QUERY_STRING']) && ($_SERVER['QUERY_STRING'] != ''))
			{
				$parametres_url = htmlspecialchars($_SERVER['QUERY_STRING'])."&amp;";
				$_SESSION['chemin_retour'] = traite_grr_url($grr_script_name)."?". $_SERVER['QUERY_STRING'];
				$d['urlLangue'] = traite_grr_url($grr_script_name);			}
			if ($type_session == 'no_session')
			{
				$resulHook = Hook::Appel("hookLienConnexion2");
				if ((Settings::get('sso_statut') == 'cas_visiteur') || (Settings::get('sso_statut') == 'cas_utilisateur'))
				{
					$d['lienConnexion'] =  '<br /> <a href="index.php?force_authentification=y">'.get_vocab("authentification").'</a>';
					$d['lienConnexion'] .=  '<br /> <small><i><a href="login.php">'.get_vocab("connect_local").'</a></i></small>';
				} elseif($resulHook['hookLienConnexion2'] != "")
				{
					$d['lienConnexion'] = $resulHook['hookLienConnexion2'];
				}
				else {
					$d['lienConnexion'] = '<br /> <a href="login.php">'.get_vocab("connect").'</a>';
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
				if (!((Settings::get("cacher_lien_deconnecter") == 'y') && (isset($_SESSION['est_authentifie_sso']))))
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

function print_header_admin($day = '', $month = '', $year = '', $type_session = 'with_session')
{
	global $vocab, $search_str, $grrSettings, $desactive_VerifNomPrenomUser, $grr_script_name;
	global $use_admin, $id_site, $use_select2, $lienRetour, $lienCompte, $nomAffichage;

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
		if (!(isset($search_str)))
			$search_str = get_vocab("search_for");
		if (empty($search_str))
			$search_str = "";
		if (!(isset($desactive_bandeau_sup) && ($desactive_bandeau_sup == 1) && ($type_session != 'with_session')))
		{

			$nomAffichage =  htmlspecialchars($_SESSION['prenom']).' '.htmlspecialchars($_SESSION['nom']);

			// On fabrique une date valide pour la réservation si ce n'est pas le cas
			$date_ = mktime(0, 0, 0, $month, $day, $year);
			if ($date_ < Settings::get("begin_bookings"))
				$date_ = Settings::get("begin_bookings");
			else if ($date_ > Settings::get("end_bookings"))
				$date_ = Settings::get("end_bookings");
			$day   = date("d",$date_);
			$month = date("m",$date_);
			$year  = date("Y",$date_);

			// Liens
			$lienRetour = '../'.page_accueil('yes').'day='.$day.'&year='.$year.'&month='.$month;
			$lienCompte = '../compte/compte.php?day='.$day.'&amp;year='.$year.'&amp;month='.$month;

			//Mail réservation
			$sql = "SELECT value FROM ".TABLE_PREFIX."_setting WHERE name='mail_etat_destinataire'";
			$res = grr_sql_query1($sql);
			grr_sql_free($res);

		}
	}
}

/**
 * @param string $type
 */
function VerifNomPrenomUser($type)
{
	// ne pas prendre en compte la page my_account.php
	global $desactive_VerifNomPrenomUser;
	if (($type == "with_session") && ($desactive_VerifNomPrenomUser != 'y') && (IsAllowedToModifyProfil()))
	{
		$test = grr_sql_query1("SELECT login FROM ".TABLE_PREFIX."_utilisateurs WHERE (login = '".getUserName()."' AND (nom='' or prenom = ''))");
		if (($test != -1) && ($page != 'moncompte'))
		{
			header("Location: ./compte/compte.php");
			die();
		}
	}
}
//Vérifie si utilisateur autorisé à changer ses noms et prénoms et mail
//Renvoie true (peut changer ses noms et prénoms et email) ou false (ne peut pas)
function sso_IsAllowedModify()
{
	if (Settings::get("sso_IsNotAllowedModify")=="y")
	{
		$source = grr_sql_query1("SELECT source FROM ".TABLE_PREFIX."_utilisateurs WHERE login = '".getUserName()."'");
		if ($source == "ext")
			return false;
		else
			return true;
	}
	else
		return true;
}
//Vérifie que l'utilisateur est autorisé à changer ses noms et prénoms
//Renvoie true (peut changer ses noms et prénoms) ou false (ne peut pas)
function IsAllowedToModifyProfil()
{
	if (!(sso_IsAllowedModify()))
		return false;
		// l'utilisateur connecté n'a pas le niveau suffisant pour modifier son compte
	if (authGetUserLevel(getUserName(),-1) < Settings::get("allow_users_modify_profil"))
		return false;
	else
		return true;
}
//Vérifie que l'utilisateur est autorisé à changer son emai
//Renvoie true (peut changer son email) ou false (ne peut pas)
function IsAllowedToModifyEmail()
{
	if (!(sso_IsAllowedModify()))
		return false;
		// l'utilisateur connecté n'a pas le niveau suffisant pour modifier son compte
	if (authGetUserLevel(getUserName(),-1) < Settings::get("allow_users_modify_email"))
		return false;
	else
		return true;
}
//Vérifie que l'utilisateur est autorisé à changer son mot de passe
//Renvoie true (peut changer) ou false (ne peut pas)
function IsAllowedToModifyMdp() {
		// l'utilisateur connecté n'a pas le niveau suffisant pour modifier son compte
	if (authGetUserLevel(getUserName(), -1) < Settings::get("allow_users_modify_mdp"))
		return false;
	else if ((Settings::get("sso_statut") != "") or (Settings::get("ldap_statut") != '') or (Settings::get("imap_statut") != ''))
	{
			// ou bien on est dans un environnement SSO ou ldap et l'utilisateur n'est pas un utilisateur local
		$source = grr_sql_query1("SELECT source FROM ".TABLE_PREFIX."_utilisateurs WHERE login = '".getUserName()."'");
		if ($source == "ext")
			return false;
		else
			return true;
	}
	else
		return true;
}
// Transforme $dur en une durée exprimée en années, semaines, jours, heures, minutes et secondes
// OU en durée numérique exprimée dans l'une des unités de façon fixe, pour l'édition des
// réservations par durée.
// $dur : durée sous forme d'une chaine de caractère quandd $edition=false, sinon, durée en valeur numérique.
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
				if (($dur >= 24) && ($dur % 24 == 0))
				{
					$dur /= 24;
					if (($dur >= 7) && ($dur % 7 == 0))
					{
						$dur /= 7;
						if (($dur >= 52) && ($dur % 52 == 0))
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
	global $vocab;
	if ($need_header)
		print_header(0, 0, 0, 0);
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
	echo '<p>',$message,'</p>'.PHP_EOL;
	include "trailer.inc.php";
	exit;
}

/** function iptobin($ip)
 * paramètre : une adresse iP v4 ou v6 supposée valable
 * rend : une chaîne de 0 ou 1 codant l'adresse sur 32 ou 128 digits 
 */
function iptobin($ip){
    $hex = unpack("H*", inet_pton($ip));
    $out = "";
    foreach(str_split($hex[1]) as $char)
        $out = $out.str_pad( base_convert($char,16,2),4,"0",STR_PAD_LEFT );
    return $out;
}
/** function compare_ip_adr($ip1, $ips2)
 * paramètres : 
 *   $ip1 : une adresse iP
 *   $ips2 : une liste d'adresses iP ou de plages au format CIDR séparées par des points-virgules
 * rend :
 *   TRUE ou FALSE 
 * teste si l'adresse $ip1 est dans la liste $ips2 ou est dans l'une des plages de $ips2
 */
function compare_ip_adr($ip1, $ips2)
{
	$ipCorrespondante = false;
    $ip2 = explode(';', $ips2);
	
    $resultIP = in_array($ip1,$ip2,true); // teste si l'adresse est dans la liste
	if($resultIP == false){ // cherche si l'adresse est dans une plage CIDR p.ex. 192.168.1.0/24 --> 192.168.1.0 à 192.168.1.255
        foreach ($ip2 as $ip){
            $slash = strpos($ip,'/');
            if ($slash !== false){ // $ip2 est une plage CIDR
                list($net,$mask) = preg_split("~/~",$ip);
                $binnet=iptobin($net);
                $firstpart=substr($binnet,0,$mask);
                $binip=iptobin($ip1);
                $firstip=substr($binip,0,$mask);
                $resultIP = (strcmp($firstpart,$firstip)==0);
            }
            if ($resultIP){
				$ipCorrespondante = true;
				break;
			}
        }
	} 
    else {
		$ipCorrespondante = true;
	}
	return $ipCorrespondante;
}

//Retourne le domaine par défaut; Utilisé si aucun domaine n'a été défini.
function get_default_area($id_site = -1)
{
	if (Settings::get("module_multisite") == "Oui")
		$use_multisite = true;
	else
		$use_multisite = false;
	if (OPTION_IP_ADR==1)
	{
		$sql = "SELECT ip_adr, id FROM ".TABLE_PREFIX."_area WHERE ip_adr!='' ORDER BY access, order_display, area_name";
		$res = grr_sql_query($sql);
		if ($res)
		{
			for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
			{
				if (compare_ip_adr($_SERVER['REMOTE_ADDR'],$row[0]))
				{
					return $row[1];
				}
			}
		}
	}
	if (authGetUserLevel(getUserName(),-1) >= 6)
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
    $test = grr_sql_query1("SELECT id FROM ".TABLE_PREFIX."_site WHERE id = ".$id_site);
    if ($test >0){return $id_site;}
    else { // il n'y a pas de site par défaut dans la table setting, on prend le premier site
        $id_site = grr_sql_query1("SELECT min(id) FROM ".TABLE_PREFIX."_site ");
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
/*
ancien code. Je ne vois pas l'intérêt du test YN le 25/09/19
{
	global $twentyfourhour_format;
	//his bit's necessary, because it seems %p in strftime format
	//strings doesn't work
	if ($twentyfourhour_format)
		return utf8_strftime($dformat, $t);
	else
		return utf8_strftime($dformat, $t);
}*/

// Renvoie une balise span avec un style background-color correspondant au type de  la réservation
function span_bgground($colclass)
{
	global $tab_couleur;
	static $ecolors;
	$res = grr_sql_query("SELECT couleurhexa, couleurtexte FROM ".TABLE_PREFIX."_type_area WHERE type_letter='".$colclass."'");
	$row = grr_sql_row($res, 0);
	echo '<span style="background-color: '.$row[0].'; background-image: none; background-repeat: repeat; background-attachment: scroll;color: '.$row[1].';">'.PHP_EOL;
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
	if (($colclass >= "A") && ($colclass <= "Z"))
	{
		/*$couleurhexa = grr_sql_query1("SELECT couleurhexa FROM ".TABLE_PREFIX."_type_area WHERE type_letter='".$colclass."'");
        $couleur_texte = grr_sql_query1("SELECT couleur_texte FROM".TABLE_PREFIX."_type_area WHERE type_letter='".$colclass."'");
		echo '<td style="background-color:'.$couleurhexa.'; color:'.$couleur_texte.';" '.$temp.'>'.PHP_EOL;*/
        echo '<td class="type'.$colclass.'"'.$temp.'>';
	}
	else
		echo '<td class="'.$colclass.'" '.$temp.'>'.PHP_EOL;
}

function tdcell_rowspan($colclass, $step)
{
	global $tab_couleur;
	static $ecolors;
	if ($step < 1)
		$step = 1;
	if (($colclass >= "A") && ($colclass <= "Z"))
	{
		/*$couleurhexa = grr_sql_query1("SELECT couleurhexa FROM ".TABLE_PREFIX."_type_area WHERE type_letter='".$colclass."'");
		echo '<td rowspan="'.$step.'" style="background-color:'.$couleurhexa.';">'.PHP_EOL;*/
        echo '<td class="type'.$colclass.'" rowspan="'.$step.'">';
	}
	else
		echo '<td rowspan="'.$step.'" class="'.$colclass.'">'.PHP_EOL;
}

//Display the entry-type color key. This has up to 2 rows, up to 10 columns.
function show_colour_key($area_id)
{
    $sql = "SELECT DISTINCT t.id, t.type_name, t.type_letter, t.order_display FROM `".TABLE_PREFIX."_type_area` t
    LEFT JOIN `".TABLE_PREFIX."_j_type_area` j on j.id_type=t.id
    WHERE (j.id_area IS NULL or j.id_area != '".$area_id."')
    AND NOT EXISTS (SELECT y.id_type FROM `".TABLE_PREFIX."_j_type_area` y WHERE y.id_type = j.id_type and id_area='".$area_id."')
    ORDER BY t.order_display";
    $res = grr_sql_query($sql);
    echo '<table class="legende">';
    echo '<caption>'.get_vocab("show_color_key").'</caption>'.PHP_EOL;
    if ($res)
    {
        $nct = -1;
        for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
        {
            $type_name = $row[1];
            $type_letter = $row[2];
            if ($nct == -1)
                echo '<tr>'.PHP_EOL;
            if (++$nct == 2)
            {
                $nct = 0;
                echo '</tr>'.PHP_EOL, '<tr>'.PHP_EOL;
            }
            tdcell($type_letter);
            echo $type_name, '</td>'.PHP_EOL;
        }
        if ($i % 2 == 1)
			echo '<td></td>',PHP_EOL;
		echo '</tr>'.PHP_EOL;
    }
    echo '</table>'.PHP_EOL;
}

//Display the entry-type color keys. This has up to 2 rows, up to 10 columns.
function show_colour_keys()
{
	echo '<table class="legende">';
    echo '<caption>'.get_vocab("show_color_key").'</caption>'.PHP_EOL;
	$sql = "SELECT DISTINCT id, type_name, type_letter, order_display FROM `".TABLE_PREFIX."_type_area` ";
	$res = grr_sql_query($sql);
	if ($res)
	{
		$nct = -1;
		for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
		{
            $type_name   = $row[1];
            $type_letter = $row[2];
            if ($nct == -1)
                echo '<tr>'.PHP_EOL;
            if (++$nct == 2)
            {
                $nct = 0;
                echo '</tr>'.PHP_EOL, '<tr>'.PHP_EOL;
            }
            tdcell($type_letter);
            echo $type_name, '</td>'.PHP_EOL;
		}
		if ($i % 2 == 1)
			echo '<td></td>',PHP_EOL;
		echo '</tr>'.PHP_EOL;
	}
	echo '</table>'.PHP_EOL;
}
// transforme une chaine de caractères en couleur hexadécimale valide
function valid_color($entry)
{
	$out = preg_replace('/[^a-fA-F0-9]/','',$entry);
	if (strlen($out)<4)
	{
		$out = '#'.substr($out.'000',0,3);
	}
	else //if (strlen($out)<7)
	{
		$out = '#'.substr($out.'000',0,6);
	}
	return($out);
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
					if (authUserAccesArea($user,$row2[0]) == 1)
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
				$link2 = $link.'?year='.$year.'&amp;month='.$month.'&amp;day='.$day.'&amp;area='.$default_area;
				$out[] = '<option '.$selected.' value="'.$link2.'">'.htmlspecialchars($row[1]).'</option>'.PHP_EOL;
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
	if (Settings::get("module_multisite") == "Oui")
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
			$link2 = $link.'?year='.$year.'&amp;month='.$month.'&amp;day='.$day.'&amp;area='.$row[0];
			if (authUserAccesArea($user,$row[0]) == 1)
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
	if (Settings::get("module_multisite") == "Oui")
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
    $out_html .= "<option value=\"".$link."_all.php?year=$year";
    if ($current_site != -1) 
        $out_html .= "&amp;site=$current_site";
    $out_html .= " \">".get_vocab("any_area")."</option>";
	$res = grr_sql_query($sql);
	if ($res)
	{
		for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
		{
			$selected = ($row[0] == $current_area) ? 'selected="selected"' : "";
			$link2 = $link.'.php?year='.$year.'&amp;month='.$month.'&amp;day='.$day.'&amp;area='.$row[0];
			if (authUserAccesArea($user,$row[0]) == 1)
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
	$sql = "select id, room_name, description from ".TABLE_PREFIX."_room WHERE area_id='".protect_data_sql($current_area)."' order by order_display,room_name";
	$res = grr_sql_query($sql);
	if ($res && (grr_sql_count($res)>0)) // il y a des ressources à afficher
	{
        $out_html = "<b><i>".get_vocab('rooms').get_vocab("deux_points")."</i></b><br /><form id=\"room_".$pos."\" action=\"".$_SERVER['PHP_SELF']."\"><div><select class=\"form-control\" name=\"room\" onchange=\"room_go_".$pos."()\">";
        $out_html .= "<option value=\"".$link;
        if ($link != "day"){$out_html .= "_all";}
        $out_html .= ".php?year=$year&amp;month=$month&amp;day=$day&amp;area=$current_area\">".get_vocab("all_rooms")."</option>";
		for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
		{
			if (verif_acces_ressource(getUserName(),$row[0]))
			{
				if ($row[2])
					$temp = " (".htmlspecialchars($row[2]).")";
				else
					$temp = "";
				$selected = ($row[0] == $current_room) ? "selected=\"selected\"" : "";
				$link2 = $link.'.php?year='.$year.'&amp;month='.$month.'&amp;day='.$day.'&amp;room='.$row[0];
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
	if (Settings::get("module_multisite") == "Oui")
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
						if (authUserAccesArea($user,$row2[0]) == 1)
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
					// on affiche le site uniquement si au moins un domaine est visible par l'utilisateur
					$nb_sites_a_afficher++;
					if ($row[0] == $current_site)
					{
						$out[] = '
						<b><a id="liste_select"   href="'.$link.'?year='.$year.'&amp;month='.$month.'&amp;day='.$day.'&amp;id_site='.$row[0].'" title="'.$row[1].'">&gt; '.htmlspecialchars($row[1]).'</a></b>
						<br />'."\n";
					}
					else
					{
						$out[] = '
						<a id="liste"  href="'.$link.'?year='.$year.'&amp;month='.$month.'&amp;day='.$day.'&amp;id_site='.$row[0].'" title="'.$row[1].'">'.htmlspecialchars($row[1]).'</a>
						<br />'."\n";
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
	if (Settings::get("module_multisite") == "Oui")
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
			if (authUserAccesArea($user,$row[0]) == 1)
			{
				if ($row[0] == $current_area)
				{
					$out_html .= "<a id=\"liste_select\" onclick=\"charger();\" href=\"".$link."?year=$year&amp;month=$month&amp;day=$day&amp;area=$row[0]\">&gt; ".htmlspecialchars($row[1])."</a></b><br />\n";
				} else {
					$out_html .= "<a id=\"liste\" onclick=\"charger();\" href=\"".$link."?year=$year&amp;month=$month&amp;day=$day&amp;area=$row[0]\"> ".htmlspecialchars($row[1])."</a><br />\n";
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
	$sql = "select id, room_name, description from ".TABLE_PREFIX."_room WHERE area_id='".protect_data_sql($current_area)."' order by order_display,room_name";
	$res = grr_sql_query($sql);
	if ($res)
	{
		for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
		{
			// On affiche uniquement les ressources autorisées
			if (verif_acces_ressource(getUserName(), $row[0]))
			{
				if ($row[0] == $current_room)
					$out_html .= "<span id=\"liste_select\">&gt; ".htmlspecialchars($row[1])."</span><br />\n";
				else
					$out_html .= "<a id=\"liste\" onclick=\"charger();\" href=\"".$link.".php?year=$year&amp;month=$month&amp;day=$day&amp;&amp;room=$row[0]\">".htmlspecialchars($row[1]). "</a><br />\n";
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
					if (authUserAccesArea($user,$row2[0]) == 1)
					{
						$default_area = $row2[0];
						break; // un domaine est accessible, on sort de la boucle
					}
				}
			}
			grr_sql_free($res2);
			if ($default_area != -1)
			{
				$nb_sites_a_afficher++;
				$link2 = $link.'?year='.$year.'&amp;month='.$month.'&amp;day='.$day.'&amp;area='.$default_area;
				//$out_html .="\n";
			}
			else
				$link2 = $link.'?year='.$year.'&amp;month='.$month.'&amp;day='.$day;
			if ($current_site != null)
			{
				if ($current_site == $row[0])
					$out[] = "<input id=\"item_select\" type=\"button\" class=\"btn btn-primary btn-lg btn-block item_select\" name=\"$row[0]\" value=\"".htmlspecialchars($row[1])."\" onclick=\"location.href='$link2';charger();\" />".PHP_EOL;
				else
					$out[] = "<input type=\"button\" class=\"btn btn-default btn-lg btn-block item\" name=\"$row[0]\" value=\"".htmlspecialchars($row[1])." \" onclick=\"location.href='$link2';charger();\" />".PHP_EOL;
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
	if (Settings::get("module_multisite") == "Oui")
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
			$link2 = $link.'?year='.$year.'&amp;month='.$month.'&amp;day='.$day.'&amp;area='.$row[0];
			if (authUserAccesArea($user, $row[0]) == 1)
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
	$sql = "SELECT id, room_name, description FROM ".TABLE_PREFIX."_room WHERE area_id='".protect_data_sql($current_area)."' ORDER BY order_display,room_name";
	$res = grr_sql_query($sql);
	if ($res && (grr_sql_count($res)>0)) // il y a des ressources à afficher
	{
        $out_html = '<br />'.PHP_EOL.'<div class="panel panel-default">'.PHP_EOL.'<div class="panel-heading">'.get_vocab("rooms").get_vocab("deux_points").'</div>'.PHP_EOL.'<div class="panel-body">'.PHP_EOL.'<form class="ressource" id="room_001" action="'.$_SERVER['PHP_SELF'].'">'.PHP_EOL;
        $all_ressource = 0; // permet l'affichage de toutes les ressources
		for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
		{
			if (verif_acces_ressource(getUserName(),$row[0]))
			{
				$link2 = $link.'.php?year='.$year.'&amp;month='.$month.'&amp;day='.$day.'&amp;room='.$row[0];
                $link_a = $link;
                if (($link != 'day')&&(!strpos($link,'all'))) {$link_a .= '_all';}
				$link_all_room = $link_a.'.php?year='.$year.'&amp;month='.$month.'&amp;day='.$day.'&amp;area='.$current_area;
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
function send_mail($id_entry, $action, $dformat, $tab_id_moderes = array(), $oldRessource = '')
{
	global $vocab, $grrSettings, $locale, $weekstarts, $enable_periods, $periods_name;

	$message_erreur = '';

	if (@file_exists('include/mail.class.php')){
		require_once 'vendor/phpmailer/phpmailer/src/PHPMailer.php';
		require_once 'vendor/phpmailer/phpmailer/src/SMTP.php';
		require_once 'vendor/phpmailer/phpmailer/src/Exception.php';
		require_once 'include/mail.class.php';
		require_once 'include/pages.class.php';
	}else{
		require_once '../vendor/phpmailer/phpmailer/src/PHPMailer.php';
		require_once '../vendor/phpmailer/phpmailer/src/SMTP.php';
		require_once '../vendor/phpmailer/phpmailer/src/Exception.php';
		require_once '../include/mail.class.php';
		require_once '../include/pages.class.php';
	}
	

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
	".TABLE_PREFIX."_entry.courrier
	FROM ".TABLE_PREFIX."_entry, ".TABLE_PREFIX."_room, ".TABLE_PREFIX."_area
	WHERE ".TABLE_PREFIX."_entry.room_id = ".TABLE_PREFIX."_room.id
	AND ".TABLE_PREFIX."_room.area_id = ".TABLE_PREFIX."_area.id
	AND ".TABLE_PREFIX."_entry.id='".protect_data_sql($id_entry)."'
	";
	$res = grr_sql_query($sql);
	if (!$res)
		fatal_error(0, grr_sql_error());
	if (grr_sql_count($res) < 1)
		fatal_error(0, get_vocab('invalid_entry_id'));
	$row = grr_sql_row($res, 0);
	grr_sql_free($res);

	get_planning_area_values($row[12]);
	$breve_description 			= bbcode(removeMailUnicode(htmlspecialchars($row[0])), 'nobbcode');
	$description  				= bbcode(removeMailUnicode(htmlspecialchars($row[1])), 'nobbcode');
	$beneficiaire 				= htmlspecialchars($row[2]);
	$room_name    				= removeMailUnicode(htmlspecialchars($row[3]));
	$area_name    				= removeMailUnicode(htmlspecialchars($row[4]));
	$room_id      				= $row[6];
	$repeat_id    				= $row[7];
	$date_avis    				= date('Y/m/d', $row[10]); //strftime("%Y/%m/%d", $row[10]);
	$delais_option_reservation 	= $row[13];
	$option_reservation 		= $row[14];
	$moderate 					= $row[15];
	$beneficiaire_ext			= htmlspecialchars($row[16]);
	$jours_cycle 				= htmlspecialchars($row[17]);
	$duration     				= $row[9];
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
	$rep_type = 0;

	// Recherche du nom de l'ancienne ressource si besoin
	if($oldRessource != '' && $oldRessource != $room_id)
	{
		$sql = "SELECT room_name FROM ".TABLE_PREFIX."_room WHERE id='".protect_data_sql($oldRessource)."'";
		$oldRess = grr_sql_query($sql);
		if (!$oldRess)
			fatal_error(0, grr_sql_error());
		$rowOld = grr_sql_row($oldRess, 0);
		grr_sql_free($oldRess);
		$nomAncienneSalle = $rowOld[0];
	}
	else
		$nomAncienneSalle = "";
	//

	if ($repeat_id != 0)
	{
		$res = grr_sql_query("SELECT rep_type, end_date, rep_opt, rep_num_weeks FROM ".TABLE_PREFIX."_repeat WHERE id='".protect_data_sql($repeat_id)."'");
		if (!$res)
			fatal_error(0, grr_sql_error());
		$test = grr_sql_count($res);
		if ($test != 1)
			fatal_error(0, "Deux reservations ont le même ID.");
		else
		{
			$row2 = grr_sql_row($res, 0);
			$rep_type     = $row2[0];
			$rep_end_date = date($dformat, $row2[1]); //strftime($dformat,$row2[1]);
			$rep_opt      = $row2[2];
			$rep_num_weeks = $row2[3];
		}
		grr_sql_free($res);
	}
	if ($enable_periods == 'y')
		toPeriodString($start_period, $duration, $dur_units);
	else
		toTimeString($duration, $dur_units);
	$weeklist = array("unused", "every_week", "week_1_of_2", "week_1_of_3", "week_1_of_4", "week_1_of_5");
	if ($rep_type == 2)
		$affiche_period = $vocab[$weeklist[$rep_num_weeks]];
	else
		$affiche_period = $vocab['rep_type_'.$rep_type];

	// Le bénéficiaire
	$beneficiaire_email = affiche_nom_prenom_email($beneficiaire,$beneficiaire_ext,"onlymail");
	if ($beneficiaire != "")
	{
		$beneficiaire_actif = grr_sql_query1("SELECT etat FROM ".TABLE_PREFIX."_utilisateurs WHERE login='$beneficiaire'");
		if ($beneficiaire_actif == -1)
			$beneficiaire_actif = 'actif'; // Cas des admins
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
	//
    // Nom d'expéditeur (si != adresse de réponse, cas des serveurs SMTP refusant le relai)
    $expediteur = '';
    if (Settings::get('grr_mail_sender'))
        $expediteur = Settings::get('grr_mail_from');

	//Infos générales
	$codes = [
		'%nomdusite%' => Settings::get('title_home_page'),
		'%nometablissement%' => Settings::get('company'),
		'%urlgrr%' =>  traite_grr_url("","y"),
		'%webmasteremail%' => Settings::get("webmaster_email"),
		'%logincompletuser%' => affiche_nom_prenom_email($user_login,"","formail"),
		'%logincompletbeneficiaire%' => affiche_nom_prenom_email($beneficiaire,$beneficiaire_ext,"formail"),
		'%domaine%' => $area_name,
		'%ressource%' => $room_name,
	];

	// Infos sur la réservation	
	$codes['%resadatedebut%'] = $start_date;
	$codes['%resadatefin%'] = $end_date;
	$codes['%resaduree%'] = $duration." ".$dur_units;
	if (trim($breve_description) != "")
		$codes['%resanom%'] = $vocab["namebooker"]." ".$vocab["deux_points"]." ".$breve_description;
	else
		$codes['%resanom%'] = $vocab["entryid"].$room_id."\n";
	if ($description !='')
		$codes['%resadescription%'] = $vocab["description"]." ".$description."\n";
	else
		$codes['%resadescription%'] = "";
	if ($moderate == 1)
		$codes['%enattentemoderation%'] = " (".$vocab['en_attente_moderation'].")";
	else
		$codes['%enattentemoderation%'] = "";

	$codes['%urldetail%'] = traite_grr_url("","y")."app.php?p=vuereservation&id=".$id_entry;

	// Champs additionnels
	$codes['%resachampsadditionnels%'] =  affichage_champ_add_mails($id_entry);
	$destinataire_spec = envois_spec_champ_add_mails($id_entry);

	// Type de réservation
	$temp = grr_sql_query1("SELECT type_name FROM ".TABLE_PREFIX."_type_area WHERE type_letter='".$row[5]."'");
	if ($temp == -1)
		$codes['%resatype%'] = "?".$row[5]."?";
	else
		$codes['%resatype%'] = removeMailUnicode($temp);

	// Infos périodicités
	if ($rep_type != 0)
	{
		$resaPeriodique = $vocab["rep_type"]." ".$affiche_period."\n";
		if ($rep_type == 2)
		{
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
		if ($rep_type == 6)
		{
			if (Settings::get("jours_cycles_actif") == "Oui")
				$resaPeriodique .= $vocab["rep_type_6"].preg_replace("/ /", " ",$vocab["deux_points"]).ucfirst(substr($vocab["rep_type_6"],0,1)).$jours_cycle."\n";
		}
		$resaPeriodique .= $vocab["rep_end_date"]." ".$rep_end_date."\n";
		$codes['%resaperiodique%'] = $resaPeriodique;
	} else
		$codes['%resaperiodique%'] = "";

	if (($delais_option_reservation > 0) && ($option_reservation != -1))
		$codes['%resaconfirmation%'] = "*** ".$vocab["reservation_a_confirmer_au_plus_tard_le"]." ".time_date_string_jma($option_reservation,$dformat)." ***\n";
	else
		$codes['%resaconfirmation%'] = "";

/*
(1)
Pour les utilisateurs :
	- qui sont renseigné manuellement dans l'administration
	- les utilisateurs notifié via les champs additionnels
*/
	$sujet1 = $vocab["subject_mail1"].$room_name." - ".$date_avis;

	if ($action == 1){ // Création
		//$sujet1 .= $vocab["subject_mail_creation"];
		$repondre = $user_email;
		$templateSujet1 = Pages::get('mails_resacreation_'.$locale);
		$templateMail1 = Pages::get('mails_resacreation_'.$locale);
	}
	elseif ($action == 2){ // Modification
		//$sujet1 .= $vocab["subject_mail_modify"];
		//if ($moderate == 1)
		//	$sujet1 .= " (".$vocab["en_attente_moderation"].")";
		$repondre = $user_email;
		if ($room_name != $oldRessource)
			$codes['%ressource%'] .= $oldRessource." => ".$room_name;
		$templateSujet1 = Pages::get('mails_resamodification_'.$locale);
		$templateMail1 = Pages::get('mails_resamodification_'.$locale);
	}
	elseif ($action == 3){ // Suppression
		//$sujet1 .= $vocab["subject_mail_delete"];
		//if ($moderate == 1)
		//	$sujet1 .= " (".$vocab["en_attente_moderation"].")";
		$repondre = $user_email;
		$templateSujet1 = Pages::get('mails_resasuppression_'.$locale);
		$templateMail1 = Pages::get('mails_resasuppression_'.$locale);
	}
	elseif ($action == 4){ // Suppression automatique
		//$sujet1 .= $vocab["subject_mail_delete"];
		$repondre = $user_email;
		$templateMail1 = Pages::get('mails_resasuppression3_'.$locale);
	}
	elseif ($action == 5){ // Réservation en attente de modération
		//$sujet1 .= $vocab["subject_mail_moderation"];
		$repondre = Settings::get("webmaster_email");
		$templateMail1 = Pages::get('mails_resamoderation_'.$locale);
	}
	elseif ($action == 6){ // Résultat d'une décision de modération
		//$sujet1 .= $vocab["subject_mail_decision_moderation"];
		$repondre = $user_email;
		$resmoderate = grr_sql_query("SELECT moderate, motivation_moderation FROM ".TABLE_PREFIX."_entry_moderate WHERE id ='".protect_data_sql($id_entry)."'");
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
		else
			$codes['%decisionmotif%'] = "";

		if (count($tab_id_moderes) == 0 )
			$codes['%urldetail%'] = traite_grr_url("","y")."app.php?p=vuereservation&id=".$id_entry;
		else
		{
			$codes['%urldetail%'] = "";
			foreach ($tab_id_moderes as $id_moderes)
				$codes['%urldetail%'] .=  traite_grr_url("","y")."app.php?p=vuereservation&id=".$id_moderes;
		}
		$templateMail1 = Pages::get('mails_resamoderation2_'.$locale);
	}
	elseif ($action == 7){ // Notification d'un retard dans la restitution d'une ressource
		//$sujet1 .= $vocab["subject_mail_retard"];

		if ($beneficiaire_email != "")
			$codes['%maildestinataire%'] .= $vocab["un_email_envoye"].$beneficiaire_email;
		else
			$codes['%maildestinataire%'] .= "";

		$repondre = Settings::get("webmaster_email");
		$templateMail1 = Pages::get('mails_retardrestitution_'.$locale);
	}

	$sql = "SELECT u.email FROM ".TABLE_PREFIX."_utilisateurs u, ".TABLE_PREFIX."_j_mailuser_room j WHERE (j.id_room='".protect_data_sql($room_id)."' AND u.login=j.login and u.etat='actif') ORDER BY u.nom, u.prenom";
	$res = grr_sql_query($sql);
	$nombre = grr_sql_count($res);
	$destinataire = "";
	if ($nombre > 0)
	{
		$tab_destinataire = array();
		for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
		{
			if ($row[0] != "")
				$tab_destinataire[] = $row[0];
		}
		foreach ($tab_destinataire as $value){
			$destinataire .= $value.";";
		}
	}
	$destinataire = $destinataire . $destinataire_spec;
    
	if ($expediteur =='')
		$expediteur = $repondre;

	if($destinataire != "")
	{
		$codes['%raisonmail%'] = $vocab['mail_raison_5'];
		$sujetEncode1 = str_replace(array_keys($codes), $codes, $templateMail1[0]);
		$msgEncode1 = str_replace(array_keys($codes), $codes, $templateMail1[1]);
		Email::Envois($destinataire, $sujetEncode1, $msgEncode1, $expediteur, '', '', $repondre);
	}


/*
(2)
Pour le benificiare de la ressource
*/

	$envoi2 = false;
	$destinataire2 = $beneficiaire_email;
	$codes['%raisonmail%'] = $vocab['mail_raison_6'];

	// Cas d'une création, modification ou suppression d'un message par un utilisateur différent du bénéficiaire :
	// On envoie un message au bénéficiaire de la réservation pour l'avertir d'une modif ou d'une suppression
	if ((($action == 1) || ($action == 2) || ($action == 3)) && ((strtolower($user_login) != strtolower($beneficiaire)) || (Settings::get('send_always_mail_to_creator') == '1')) && ($beneficiaire_email != '') && ($beneficiaire_actif == 'actif'))
	{
		$repondre2 = $user_email;
        if ($expediteur =='')
			$expediteur = $repondre2;

		if ($action == 1){ // Création
			$templateMail2 = Pages::get('mails_resacreation2_'.$locale);
		}
		elseif ($action == 2){ // Modification
			if ($room_id != $oldRessource)
				$codes['%ressource%'] =  $nomAncienneSalle." => ".$room_name;

			$templateMail2 = Pages::get('mails_resamodification2_'.$locale);

		}
		else{ // Suppression
			$templateMail2 = Pages::get('mails_resasuppression2_'.$locale);
		}
		$envoi2 = true;
	}
	elseif (($action == 4) && ($beneficiaire_email != '') && ($beneficiaire_actif == 'actif')) // Supression automatique
	{
		// Sujet
		$repondre2 = Settings::get("webmaster_email");
		if ($expediteur =='')
			$expediteur = $repondre2;

		$templateMail2 = Pages::get('mails_resasuppression3_'.$locale);
		$envoi2 = true;
	}
	elseif (($action == 5) && ($beneficiaire_email != '') && ($beneficiaire_actif == 'actif')) // Réservation en attente de modération, mail pour le beneficiaire
	{
		$repondre2 = Settings::get("webmaster_email");
        if ($expediteur =='')
			$expediteur = $repondre2;
		$templateMail2 = Pages::get('mails_resamoderation3_'.$locale);
		$envoi2 = true;
	}
	elseif (($action == 6) && ($beneficiaire_email != '') && ($beneficiaire_actif=='actif')) // Résultat d'une décision de modération
	{
		$repondre2 = $user_email;
        if ($expediteur =='')
			$expediteur = $repondre2;

		$resmoderate = grr_sql_query("SELECT moderate, motivation_moderation FROM ".TABLE_PREFIX."_entry_moderate WHERE id ='".protect_data_sql($id_entry)."'");
		if (!$resmoderate)
			fatal_error(0, grr_sql_error());
		if (grr_sql_count($resmoderate) < 1)
			fatal_error(0, get_vocab('invalid_entry_id'));
		$rowModerate = grr_sql_row($resmoderate, 0);
		grr_sql_free($resmoderate);
		$moderate_decision = $rowModerate[0];
		$moderate_description = $rowModerate[1];

		if ($moderate_decision == 2)
			$codes['%decisionmoderation%'] .= $vocab["moderation_acceptee"];
		else if ($moderate_decision == 3)
			$codes['%decisionmoderation%'] .= $vocab["moderation_refusee"];

		if ($moderate_description != "")
			$codes['%decisionmotif%'] .= $vocab["motif"].$vocab["deux_points"]."<br>".$moderate_description;
		else
			$codes['%decisionmotif%'] .= "";

		if (count($tab_id_moderes) == 0 )
			$codes['%urldetail%'] = traite_grr_url("","y")."app.php?p=vuereservation&id=".$id_entry;
		else
		{
			$codes['%urldetail%'] = "";
			foreach ($tab_id_moderes as $id_moderes)
				$codes['%urldetail%'] .= traite_grr_url("","y")."app.php?p=vuereservation&id=".$id_moderes;
		}

		$templateMail2 = Pages::get('mails_resamoderation4_'.$locale);
		$envoi2 = true;
	}
	elseif ( ($action == 7) && ($beneficiaire_email != '') )// Notification d'un retard dans la restitution d'une ressource
	{
		$repondre2 = Settings::get("webmaster_email");
        if ($expediteur =='')
			$expediteur = $repondre2;

		$templateMail2 = Pages::get('mails_retardrestitution2_'.$locale);
		$envoi2 = true;
	}

	if($envoi2 == true)
	{
		$sujetEncode2 = str_replace(array_keys($codes), $codes, $templateMail2[0]);
		$msgEncode2 = str_replace(array_keys($codes), $codes, $templateMail2[1]);
		Email::Envois($destinataire2, $sujetEncode2, $msgEncode2, $expediteur, '', '', $repondre2);
	}

/*
(3)
Mail pour le gestionnaire, ou l'admin
*/
	$envoi3 = false;

	// Liste des destinataire
	// Raison admin : 1:Gestionnaire de la ressource; 2:Admin du domaine; 3:Admin site; 4:Admins
	list($mail_admin, $raison_admin) = find_active_user_room($room_id);
	if($raison_admin == 0)
		$codes['%raisonmail%'] = "";
	else
		$codes['%raisonmail%'] = $vocab["mail_raison_".$raison_admin];
	$destinataire3 = "";
	if (count($mail_admin) > 0)
	{
		foreach ($mail_admin as $value){
			$destinataire3 .= $value.";";
		}
	}

    // Cas d'une réservation modérée : le bénéficiaire peut éventuellement la supprimer, mais on prévient le modérateur
    if ( ($action == 3) && ($moderate >0) && (count($mail_admin) > 0) ){
		//$sujet3 = $vocab["subject_mail1"].$room_name." - ".$date_avis.$vocab["subject_mail_delete"];
		$repondre3 = $user_email;
		if (!isset($expediteur)||($expediteur ==''))
			$expediteur = $repondre3;
		
		$templateMail3 = Pages::get('mails_resamoderation5_'.$locale);
		$envoi3 = true;
    }
	elseif ( ($action == 5) && (count($mail_admin) > 0) )// Réservation en attente de modération, mail pour le modérateur
	{
		//$sujet3 = $vocab["subject_mail1"].$room_name." - ".$date_avis.$vocab["subject_mail_moderation"];
		$repondre3 = Settings::get("webmaster_email");
		if ($expediteur =='')
			$expediteur = $repondre3;
		
		$templateMail3 = Pages::get('mails_resamoderation6_'.$locale);
		$envoi3 = true;
	}
	elseif ( ($action == 7) && (count($mail_admin) > 0) ) // Notification d'un retard dans la restitution d'une ressource
	{
		//! Attention champ identique aux 1er cas
		//$sujet3 = $vocab["subject_mail1"].$room_name." - ".$date_avis.$vocab["subject_mail_retard"];
		$repondre3 = Settings::get("webmaster_email");
        if ($expediteur =='')
			$expediteur = $repondre3;

		if ($beneficiaire_email != "")
			$codes['%maildestinataire%'] .= $vocab["un_email_envoye"].$beneficiaire_email;
		else
			$codes['%maildestinataire%'] .= "";

		$templateMail3 = Pages::get('mails_retardrestitution_'.$locale);

		$envoi3 = true;
	}

	if($envoi3 == true)
	{
		$sujetEncode3 = str_replace(array_keys($codes), $codes, $templateMail3[0]);
		$msgEncode3 = str_replace(array_keys($codes), $codes, $templateMail3[1]);
		Email::Envois($destinataire3, $sujetEncode3, $msgEncode3, $expediteur, '', '', $repondre3);
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
function getWritable($user, $id)
{
    if (Settings::get("allow_gestionnaire_modify_del") == 0)
		$temp = 3;
	else
		$temp = 2;
    $sql = "SELECT room_id, create_by, beneficiaire, dont_allow_modify, who_can_book, qui_peut_reserver_pour 
            FROM ".TABLE_PREFIX."_entry JOIN ".TABLE_PREFIX."_room ON room_id = ".TABLE_PREFIX."_room.id
            WHERE ".TABLE_PREFIX."_entry.id ='".protect_data_sql($id)."'";
    $res = grr_sql_query($sql);
    if (!$res)
        fatal_error(0, grr_sql_error());
    elseif (grr_sql_count($res) == 0) // réservation inconnue
        fatal_error(1, get_vocab('invalid_entry_id'));
    else {
        $data = grr_sql_row_keyed($res,0);
        grr_sql_free($res);
        if (authGetUserLevel($user,$data['room_id']) > $temp)
            return 1; // Modifications permises si l'utilisateur a les droits suffisants
        else {
            $user_can_book = $data['who_can_book'] || authBooking($user,$data['room_id']);
            $createur = strtolower($data['create_by']);
            $beneficiaire = strtolower($data['beneficiaire']);
            $utilisateur = strtolower($user);
            /* Dans l'étude du cas d'un utilisateur sans droits particuliers, quatre possibilités :
            Cas 1 : l'utilisateur (U) n'est ni le créateur (C) ni le bénéficiaire (B)
            	R1 -> on retourne 0
            Cas 2 : U=B et U<>C  ou ...
            Cas 3 : U=B et U=C
            	R2 -> on retourne 0 si personne hormis les gestionnaires et les administrateurs ne peut modifier ou supprimer ses propres réservations.
            	R3 -> on retourne $user_can_book selon les droits de l'utilisateur sur la ressource
            Cas 4 : U=C et U<>B
            	R4 -> on retourne 0 si personne hormis les gestionnaires et les administrateurs ne peut modifier ou supprimer ses propres réservations.
            	-> sinon
            		R5 -> on retourne $user_can_book selon les droits de l'utilisateur U sur la ressource et s'il peut réserver la ressource pour B
            		R6 -> on retourne 0 sinon (si on permettait à U d'éditer la résa, il ne pourrait de toute façon pas la modifier)*/
            if (($utilisateur != $beneficiaire) && ($utilisateur != $createur)) // cas 1
                return 0;
            elseif ($utilisateur == $beneficiaire) // cas 2 et 3
            {
                if (authGetUserLevel($user, $data['room_id']) > 2) 
                    return 1; // un gestionnaire de ressource peut toujours modifier ses propres réservations
                elseif ($data['dont_allow_modify'] == 'y')
                    return 0; // un simple utilisateur ne peut pas modifier ses propres réservations
                else 
                    return $user_can_book;
            }
            elseif ($utilisateur == $createur) // cas 4
            {
                if (authGetUserLevel($user, $data['room_id']) > 2) 
                    return 1; // un gestionnaire de ressource peut toujours modifier ses propres réservations
                elseif ($data['dont_allow_modify'] == 'y')
                    return 0; // un simple utilisateur ne peut pas modifier ses propres réservations
                else
                {
                    if (authGetUserLevel($user, $data['room_id']) >= $data['qui_peut_reserver_pour'])
                        return $user_can_book;
                    else
                        return 0;
                }
            }
        }
    }
}
//auth_visiteur($user,$id_room)
//Determine si un visiteur peut réserver une ressource
//$user - l'identifiant de l'utilisateur
//$id_room -   l'identifiant de la ressource
//Retourne le niveau d'accès de l'utilisateur//
function auth_visiteur($user,$id_room)
{
	global $id_room_autorise;
	if ((!isset($user)) || (!isset($id_room)))
		return 0;
	$res = grr_sql_query("SELECT statut FROM ".TABLE_PREFIX."_utilisateurs WHERE login ='".protect_data_sql($user)."'");
	if (!$res || grr_sql_count($res) == 0)
		return 0;
	$status = mysqli_fetch_row($res);
	if (strtolower($status[0]) == 'visiteur')
	{
		if ((in_array($id_room,$id_room_autorise)) && ($id_room_autorise != ""))
			return 1;
		else
			return 0;
	}
	return 0;
}
//authGetUserLevel($user,$id,$type)
//Determine le niveau d'accès de l'utilisateur
//$user - l'identifiant de l'utilisateur
//$id -   l'identifiant de la ressource ou du domaine
// $type - argument optionnel : 'room' (par défaut) si $id désigne une ressource et 'area' si $id désigne un domaine.
////Retourne le niveau d'accès de l'utilisateur
// 0 NC / 1 Visiteur / 2 Utilisateur / 3 gestionnaire de ressource / 4 administrateur de domaine / 5 administrateur de site / 6 Admin général
function authGetUserLevel($user, $id, $type = 'room')
{
	//user level '0': User not logged in, or User value is NULL (getUserName()='')
	if (!isset($user) || ($user == ''))
		return 0;
	// On vient lire le statut de l'utilisateur courant dans la database
	$sql = "SELECT statut FROM ".TABLE_PREFIX."_utilisateurs WHERE login='".protect_data_sql($user)."' "." AND etat='actif'";
	$res = grr_sql_query($sql);
	$nbraw = grr_sql_count($res);
	//user level '0': User not defined in database
	if (!$res || $nbraw == 0)
		return 0;
	// On vient lire le résultat de la requète
	$status = grr_sql_row($res,$nbraw-1);
	//user level '0': Same User defined multiple time in database !!!
	if ($status === 0)
		return 0;
	// Teste si le type concerne la gestion des utilisateurs
	if ($type === 'user')
	{
		if (strtolower($status[0]) == 'gestionnaire_utilisateur')
			return 1;
		else
			return 0;
	}
	switch (strtolower($status[0]))
	{
		case 'visiteur':
		return 1;
		case 'administrateur':
		return 6;
		default:
		break;
	}
	if ((strtolower($status[0]) == 'utilisateur') || (strtolower($status[0]) == 'gestionnaire_utilisateur'))
	{
		if ($type == 'room')
		{
			// On regarde si l'utilisateur est administrateur du site auquel la ressource $id appartient
			// calcul de l'id du domaine
			$id_area = grr_sql_query1("SELECT area_id FROM ".TABLE_PREFIX."_room WHERE id='".protect_data_sql($id)."'");
			// calcul de l'id du site
			$id_site = grr_sql_query1("SELECT id_site FROM ".TABLE_PREFIX."_j_site_area  WHERE id_area='".protect_data_sql($id_area)."'");
			if (Settings::get("module_multisite") == "Oui")
			{
				$res3 = grr_sql_query("SELECT login FROM ".TABLE_PREFIX."_j_useradmin_site j WHERE j.id_site='".protect_data_sql($id_site)."' AND j.login='".protect_data_sql($user)."'");
				if (grr_sql_count($res3) > 0)
				{
					grr_sql_free($res3);
					return 5;
				}
			}
			// On regarde si l'utilisateur est administrateur du domaine auquel la ressource $id appartient
			$res3 = grr_sql_query("SELECT u.login
				FROM ".TABLE_PREFIX."_utilisateurs u, ".TABLE_PREFIX."_j_useradmin_area j
				WHERE (u.login=j.login AND j.id_area='".protect_data_sql($id_area)."' AND u.login='".protect_data_sql($user)."')");
			if (grr_sql_count($res3) > 0)
				return 4;
			// On regarde si l'utilisateur est gestionnaire des réservations pour une ressource
			$str_res2 = "SELECT *
			FROM ".TABLE_PREFIX."_utilisateurs u, ".TABLE_PREFIX."_j_user_room j
			WHERE u.login=j.login and u.login='".protect_data_sql($user)."' ";
			if ($id!=-1)
				$str_res2.="AND j.id_room='".protect_data_sql($id)."'";
			$res2 = grr_sql_query($str_res2);
			if (grr_sql_count($res2) > 0)
				return 3;
			// Sinon il s'agit d'un simple utilisateur
			return 2;
		}
		// On regarde si l'utilisateur est administrateur d'un domaine
		if ($type == 'area')
		{
			if ($id == '-1')
			{
				if (Settings::get("module_multisite") == "Oui")
				{
				//On regarde si l'utilisateur est administrateur d'un site quelconque
					$res2 = grr_sql_query("SELECT u.login
						FROM ".TABLE_PREFIX."_utilisateurs u, ".TABLE_PREFIX."_j_useradmin_site j
						WHERE (u.login=j.login and u.login='".protect_data_sql($user)."')");
					if (grr_sql_count($res2) > 0)
						return 5;
				}
				//On regarde si l'utilisateur est administrateur d'un domaine quelconque
				$res2 = grr_sql_query("SELECT u.login
					FROM ".TABLE_PREFIX."_utilisateurs u, ".TABLE_PREFIX."_j_useradmin_area j
					WHERE (u.login=j.login and u.login='".protect_data_sql($user)."')");
				if (grr_sql_count($res2) > 0)
					return 4;
			}
			else
			{
				if (Settings::get("module_multisite") == "Oui")
				{
				// On regarde si l'utilisateur est administrateur du site auquel le domaine $id appartient
					$id_site = grr_sql_query1("SELECT id_site FROM ".TABLE_PREFIX."_j_site_area  WHERE id_area='".protect_data_sql($id)."'");
					$res3 = grr_sql_query("SELECT login FROM ".TABLE_PREFIX."_j_useradmin_site j WHERE j.id_site='".protect_data_sql($id_site)."' AND j.login='".protect_data_sql($user)."'");
					if (grr_sql_count($res3) > 0)
						return 5;
				}
				//On regarde si l'utilisateur est administrateur du domaine dont l'id est $id
				$res3 = grr_sql_query("SELECT u.login
					FROM ".TABLE_PREFIX."_utilisateurs u, ".TABLE_PREFIX."_j_useradmin_area j
					WHERE (u.login=j.login and j.id_area='".protect_data_sql($id)."' and u.login='".protect_data_sql($user)."')");
				if (grr_sql_count($res3) > 0)
					return 4;
			}
			// Sinon il s'agit d'un simple utilisateur
			return 2;
		}
		// On regarde si l'utilisateur est administrateur d'un site
		if (($type == 'site') and (Settings::get("module_multisite") == "Oui"))
		{
			if ($id == '-1')
			{
				//On regarde si l'utilisateur est administrateur d'un site quelconque
				$res2 = grr_sql_query("SELECT u.login
					FROM ".TABLE_PREFIX."_utilisateurs u, ".TABLE_PREFIX."_j_useradmin_site j
					WHERE (u.login=j.login and u.login='".protect_data_sql($user)."')");
				if (grr_sql_count($res2) > 0)
					return 5;
			}
			else
			{
				//On regarde si l'utilisateur est administrateur du site dont l'id est $id
				$res3 = grr_sql_query("SELECT u.login
					FROM ".TABLE_PREFIX."_utilisateurs u, ".TABLE_PREFIX."_j_useradmin_site j
					WHERE (u.login=j.login and j.id_site='".protect_data_sql($id)."' and u.login='".protect_data_sql($user)."')");
				if (grr_sql_count($res3) > 0)
					return 5;
			}
			// Sinon il s'agit d'un simple utilisateur
			return 2;
		}
	}
}
/* authUserAccesArea($user,$id)
 *
 * Determines if the user access area
 *
 * $user - The user name
 * $id -   Which area are we checking
 *
 */
function authUserAccesArea($user,$id)
{
	if ($id == '')
		return 0;
	$sql = "SELECT login FROM ".TABLE_PREFIX."_utilisateurs WHERE (login = '".protect_data_sql($user)."' and statut='administrateur')";
	$res = grr_sql_query($sql);
	if (grr_sql_count($res) != "0")
		return 1;
	if (Settings::get("module_multisite") == "Oui")
	{
		$id_site = mrbsGetAreaSite($id);
		$sql = "SELECT login FROM ".TABLE_PREFIX."_j_useradmin_site j WHERE j.id_site='".$id_site."' AND j.login='".protect_data_sql($user)."'";
		$res = grr_sql_query($sql);
		if (grr_sql_count($res) != "0")
			return 1;
	}
	$sql = "SELECT id FROM ".TABLE_PREFIX."_area WHERE (id = '".protect_data_sql($id)."' and access='r')";
	$res = grr_sql_query($sql);
	$test = grr_sql_count($res);
	if ($test == "0")
		return 1;
	else
	{
		$sql2 = "SELECT login FROM ".TABLE_PREFIX."_j_user_area WHERE (login = '".protect_data_sql($user)."' and id_area = '".protect_data_sql($id)."')";
		$res2 = grr_sql_query($sql2);
		$test2 = grr_sql_count($res2);
		if ($test2 != "0")
			return 1;
		else
			return 0;
	}
}
// function UserRoomMaxBooking
// Cette fonction teste si l'utilisateur a la possibilité d'effectuer une réservation, compte tenu
// des limitations éventuelles de la ressource et du nombre de réservations déjà effectuées.
//
function UserRoomMaxBooking($user, $id_room, $number)
{
	global $enable_periods,$id_room_autorise;
	$level = authGetUserLevel($user,$id_room);
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
	$max_booking_per_room = grr_sql_query1("SELECT max_booking FROM ".TABLE_PREFIX."_room WHERE id = '".protect_data_sql($id_room)."'");
	// Calcul de l'id de l'area de la ressource.
	$id_area = mrbsGetRoomArea($id_room);
	// On regarde si le nombre de réservation du domaine est limité
	$max_booking_per_area = grr_sql_query1("SELECT max_booking FROM ".TABLE_PREFIX."_area WHERE id = '".protect_data_sql($id_area)."'");
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
		$nb_bookings = grr_sql_query1("SELECT count(id) FROM ".TABLE_PREFIX."_entry r WHERE (beneficiaire = '".protect_data_sql($user)."' and end_time > '$now') AND supprimer=0");
		$nb_bookings += $number;
		if ($nb_bookings > $max_booking)
			return 0;
	}
	else if ($max_booking == 0)
		return 0;
	// y-a-t-il dépassement pour l'ensemble des ressources du domaine ?
	if ($max_booking_per_area > 0)
	{
		$nb_bookings = grr_sql_query1("SELECT count(e.id) FROM ".TABLE_PREFIX."_entry e, ".TABLE_PREFIX."_room r WHERE (e.room_id=r.id and r.area_id='".$id_area."' and e.beneficiaire = '".protect_data_sql($user)."' and e.end_time > '$now') AND e.supprimer=0");
		$nb_bookings += $number;
		if ($nb_bookings > $max_booking_per_area)
			return 0;
	}
	else if ($max_booking_per_area == 0)
		return 0;
	// y-a-t-il dépassement pour la ressource
	if ($max_booking_per_room > 0)
	{
		$nb_bookings = grr_sql_query1("SELECT count(id) FROM ".TABLE_PREFIX."_entry WHERE (room_id = '".protect_data_sql($id_room)."' and beneficiaire = '".protect_data_sql($user)."' and end_time > '$now') AND supprimer=0");
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
function UserRoomMaxBookingRange($user, $id_room, $number, $start_time)
{
	global $enable_periods,$id_room_autorise;
	$level = authGetUserLevel($user,$id_room);
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
	$max_booking_per_room = grr_sql_query1("SELECT max_booking FROM ".TABLE_PREFIX."_room WHERE id = '".protect_data_sql($id_room)."'");
    // limitation dans le temps
    $booking_range = grr_sql_query1("SELECT booking_range FROM ".TABLE_PREFIX."_room WHERE id = '".protect_data_sql($id_room)."'"); // jours
    $min_int = $start_time - $booking_range * 86400 ;// approximatif, mais devrait être convenable
	// Calcul de l'id de l'area de la ressource.
	$id_area = mrbsGetRoomArea($id_room);
	// On regarde si le nombre de réservation du domaine est limité
	$max_booking_per_area = grr_sql_query1("SELECT max_booking FROM ".TABLE_PREFIX."_area WHERE id = '".protect_data_sql($id_area)."'");
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
		$nb_bookings = grr_sql_query1("SELECT count(id) FROM ".TABLE_PREFIX."_entry r WHERE (beneficiaire = '".protect_data_sql($user)."' and end_time > '$now') AND supprimer=0");
		$nb_bookings += $number;
		if ($nb_bookings > $max_booking)
			return 0;
	}
	else if ($max_booking == 0)
		return 0;
	// y-a-t-il dépassement pour l'ensemble des ressources du domaine ?
	if ($max_booking_per_area > 0)
	{
		$nb_bookings = grr_sql_query1("SELECT count(e.id) FROM ".TABLE_PREFIX."_entry e, ".TABLE_PREFIX."_room r WHERE (e.room_id=r.id and r.area_id='".$id_area."' and e.beneficiaire = '".protect_data_sql($user)."' and e.end_time > '$now') AND e.supprimer=0");
		$nb_bookings += $number;
		if ($nb_bookings > $max_booking_per_area)
			return 0;
	}
	else if ($max_booking_per_area == 0)
		return 0;
	// y-a-t-il dépassement pour la ressource
	if ($max_booking_per_room > 0)
	{
		$nb_bookings = grr_sql_query1("SELECT count(id) FROM ".TABLE_PREFIX."_entry WHERE (room_id = '".protect_data_sql($id_room)."' and beneficiaire = '".protect_data_sql($user)."' and end_time > '$now') AND supprimer=0");
		$nb_bookings += $number;
		if ($nb_bookings > $max_booking_per_room)
			return 0;
	}
	else if ($max_booking_per_room == 0)
		return 0;
    // limitation sur l'intervalle
    if ($booking_range > 0 ){
        $nb_bookings = grr_sql_query1("SELECT count(id) FROM ".TABLE_PREFIX."_entry WHERE (
        room_id = '".protect_data_sql($id_room)."'
        AND beneficiaire = '".protect_data_sql($user)."'
        AND end_time > '".$now."'
        AND start_time > '".min_int."') AND supprimer=0");
        $nb_bookings += $number;
        if ($nb_bookings > $max_booking_per_room)
            return 0;
    }
	// A ce stade, il s'agit d'un utilisateur et il n'y a pas eu de dépassement, ni pour l'ensemble des domaines, ni pour le domaine, ni pour la ressource, ni sur l'intervalle de temps
	return 1;
}
/* function authBooking($user,$room)
à utiliser avec une ressource restreinte : détermine si $user est autorisé à réserver dans $room
utilise la table grr_j_userbook_room
*/
function authBooking($user,$room){
    $sql = "SELECT COUNT(*) FROM ".TABLE_PREFIX."_j_userbook_room WHERE (login = '".protect_data_sql($user)."' AND id_room = '".protect_data_sql($room)."')";
	$test = grr_sql_query1($sql);
    return ($test > 0);
}
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
 	$sql = "SELECT statut FROM ".TABLE_PREFIX."_utilisateurs WHERE login = '".protect_data_sql($user)."'";
 	$statut_user = grr_sql_query1($sql);
 	if ($statut_user == 'administrateur')
 		return true;
	// A-t-on le droit d'agir dans le passé ?
 	$allow_action_in_past = grr_sql_query1("SELECT allow_action_in_past FROM ".TABLE_PREFIX."_room WHERE id = '".protect_data_sql($id_room)."'");
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
 			$sql = "SELECT end_time FROM ".TABLE_PREFIX."_entry WHERE id = '".protect_data_sql($id)."'";
 		else
 			$sql = "SELECT start_time FROM ".TABLE_PREFIX."_entry WHERE id = '".protect_data_sql($id)."'";
 		$date_booking = grr_sql_query1($sql);
 		if ($date_booking < $date_now)
 			return false;
 		else
 		{
			// dans le cas où le créneau est entamé, on teste si l'utilisateur a le droit de supprimer la réservation
			// Si oui, on transmet la variable $only_modify = true avant que la fonction de retourne true.
 			if (Settings::get("allow_user_delete_after_begin") == 2)
 			{
 				$date_debut = grr_sql_query1("SELECT start_time FROM ".TABLE_PREFIX."_entry WHERE id = '".protect_data_sql($id)."'");
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
 			$id_area = grr_sql_query1("select area_id from ".TABLE_PREFIX."_room WHERE id = '".protect_data_sql($id_room)."'");
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
 	if (authGetUserLevel($user,$id_room) >= 3)
 		return true;
 	$id_area = grr_sql_query1("SELECT area_id from ".TABLE_PREFIX."_room WHERE id='".protect_data_sql($id_room)."'");
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
 	if (authGetUserLevel($user,$id_room) >= 3)
 		return true;
 	$delais_max_resa_room = grr_sql_query1("SELECT delais_max_resa_room FROM ".TABLE_PREFIX."_room WHERE id='".protect_data_sql($id_room)."'");
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
 	$sql = "SELECT statut FROM ".TABLE_PREFIX."_utilisateurs WHERE login = '".protect_data_sql($user)."'";
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
 			$sql = "SELECT end_time FROM ".TABLE_PREFIX."_entry WHERE id = '".protect_data_sql($id)."'";
 		else
 			$sql = "SELECT start_time FROM ".TABLE_PREFIX."_entry WHERE id = '".protect_data_sql($id)."'";
 		$date_booking = grr_sql_query1($sql);
 		if ($date_booking < $date_now)
 			return false;
 		else
 		{
			// dans le cas où le créneau est entamé, on teste si l'utilisateur a le droit de supprimer la réservation
			// Si oui, on transmet la variable $only_modify = true avant que la fonction de retourne true.
 			if (Settings::get("allow_user_delete_after_begin") == 2)
 			{
 				$date_debut = grr_sql_query1("SELECT start_time FROM ".TABLE_PREFIX."_entry WHERE id = '".protect_data_sql($id)."'");
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
 			$id_area = grr_sql_query1("select area_id from ".TABLE_PREFIX."_room WHERE id = '".protect_data_sql($id_room)."'");
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

 

// function verif_access_search : vérifier l'accès à l'outil de recherche
// $user : le login de l'utilisateur
// $id_room : l'id de la ressource.
 function verif_access_search($user)
 {
 	if (authGetUserLevel($user,-1) >= Settings::get("allow_search_level"))
 		return true;
 	return false;
 }
// function verif_display_fiche_ressource : vérifier l'accès à la visualisation de la fiche d'une ressource
// $user : le login de l'utilisateur
// $id_room : l'id de la ressource.
 function verif_display_fiche_ressource($user, $id_room)
 {
 	$show_fic_room = grr_sql_query1("SELECT show_fic_room FROM ".TABLE_PREFIX."_room WHERE id='".$id_room."'");
 	if ($show_fic_room == "y")
 	{
 		if (authGetUserLevel($user,$id_room) >= Settings::get("visu_fiche_description"))
 			return true;
 		return false;
 	}
 	return false;
 }
// function verif_acces_fiche_reservation : vérifier l'accès à la fiche de réservation d'une ressource
// $user : le login de l'utilisateur
// $id_room : l'id de la ressource.
 function verif_acces_fiche_reservation($user, $id_room)
 {
 	if (authGetUserLevel($user,$id_room) >= Settings::get("acces_fiche_reservation"))
 		return true;
 	return false;
 }
/* function verif_display_email : vérifier l'accès à l'adresse email
 *$user : le login de l'utilisateur
 * $id_room : l'id de la ressource.
 */
function verif_display_email($user, $id_room)
{
	if (authGetUserLevel($user,$id_room) >= Settings::get("display_level_email"))
		return true;
	else
		return false;
}
/* function verif_acces_ressource : vérifier l'accès à la ressource
 *$user : le login de l'utilisateur
 * $id_room : l'id de la ressource.
 */
function verif_acces_ressource($user, $id_room)
{
	if ($id_room != 'all')
	{
		$who_can_see = grr_sql_query1("SELECT who_can_see FROM ".TABLE_PREFIX."_room WHERE id='".$id_room."'");
		if (authGetUserLevel($user,$id_room) >= $who_can_see)
			return true;
		else
			return false;
	}
	else
	{
		$tab_rooms_noaccess = array();
		$sql = "SELECT id, who_can_see FROM ".TABLE_PREFIX."_room";
		$res = grr_sql_query($sql);
		if (!$res)
			fatal_error(0, grr_sql_error());
		for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
		{
			if (authGetUserLevel($user,$row[0]) < $row[1])
				$tab_rooms_noaccess[] = $row[0];
		}
		return $tab_rooms_noaccess;
	}
}
/* function no_book_rooms($user)
détermine les ressources (rooms) dans lesquelles $user ne peut pas réserver (droits insuffisants ou ressource restreinte)
*/
function no_book_rooms($user){
    $rooms_no_book = array();
    $sql = "SELECT id,who_can_see,who_can_book FROM ".TABLE_PREFIX."_room";
    $rooms = grr_sql_query($sql);
    if (!$rooms)
        fatal_error(0,grr_sql_error());
    while($room = mysqli_fetch_array($rooms)){
        $auth_level = authGetUserLevel($user,$room['id']);
        if ($auth_level < $room['who_can_see'])
            $rooms_no_book[] = $room['id'];
        elseif (!$room['who_can_book']){ // ressource restreinte
            $sql = "SELECT login FROM ".TABLE_PREFIX."_j_userbook_room j WHERE j.login = '".$user."' AND j.id_room = '".$room['id']."'";
            $login = grr_sql_query1($sql);
            if ((strtoupper($login) != strtoupper($user)) && ($auth_level < 3)){ // un gestionnaire de ressource peut toujours accéder !
                $rooms_no_book[] = $room['id'];
            }
        }
    }
    return $rooms_no_book;
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
	if (authGetUserLevel($user,$id_room) >= 3)
		return true;
	$delais_min_resa_room = grr_sql_query1("SELECT delais_min_resa_room FROM ".TABLE_PREFIX."_room WHERE id='".protect_data_sql($id_room)."'");
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
	if (authGetUserLevel($_create_by, $_room_id) >= $qui_peut_reserver_pour)
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
function VerifyModeDemo() {
	if (Settings::get("ActiveModeDemo") == 'y')
	{
		print_header("", "", "", "");
		echo "<h1>Opération non autorisée</h1>
		<p>Vous êtes dans une <b>version de démonstration de GRR</b>.
			<br />Certaines fonctions ont été volontairement bridées. C'est le cas pour l'opération que vous avez tenté d'effectuer.</p>
		</body></html>";
		die();
	}
}
/* MajMysqlModeDemo()
 * dans le cas le mode demo est activé :
 * Met à jour la base mysql une fois par jour, lors de la première connexion
 *
 */
function MajMysqlModeDemo() {
		// Nom du fichier sql à exécuter
	$fic_sql = "grr_maj_quotidienne.sql";
	if ((Settings::get("ActiveModeDemo") == 'y') && (file_exists($fic_sql)))
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
	echo '<p><a href="'.$back.'">'.get_vocab("returnprev").'</a></p>';
	end_page();
}
/* showNoReservation()
 *
 * Displays an appropriate message when reservation does not exist
 *
 * Returns: Nothing
 */
function showNoReservation($day, $month, $year, $back)
{
	global $vocab;
	if ((Settings::get("authentification_obli") == 0) && (getUserName() == ''))
		$type_session = "no_session";
	else
		$type_session = "with_session";
	start_page_w_header($day, $month, $year, $type_session);
	echo '<h1>'.get_vocab("accessdenied").'</h1>';
	echo '<p>'.get_vocab("noreservation").'</p>';
	echo '<p><a href="'.$back.'">'.get_vocab("returnprev").'</a></p>';
    end_page();
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
		$max_booking_per_room = grr_sql_query1("SELECT max_booking FROM ".TABLE_PREFIX."_room WHERE id='".protect_data_sql($id_room)."'");
		if ($max_booking_per_room >= 0)
			echo get_vocab("msg_max_booking").get_vocab("deux_points").$max_booking_per_room."<br />";
		// Calcul de l'id de l'area de la ressource.
		$id_area = mrbsGetRoomArea($id_room);
		// Limitation par domaine
		$max_booking_per_area = grr_sql_query1("SELECT max_booking FROM ".TABLE_PREFIX."_area WHERE id = '".protect_data_sql($id_area)."'");
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
/* fonction qui rend TRUE lorsque la date proposée est en dehors de la période réservable
*/
function check_begin_end_bookings($day, $month, $year)
{
	$date = mktime(0,0,0,$month,$day,$year);
	if (($date < Settings::get("begin_bookings")) || ($date > Settings::get("end_bookings")))
		return -1;
}
function showNoBookings($day, $month, $year, $back)
{
	global $vocab;
	$date = mktime(0, 0, 0, $month, $day,$year);
	echo '<h2>'.get_vocab("nobookings").' '.affiche_date($date).'</h2>';
	echo '<p>'.get_vocab("begin_bookings").'<b>'.affiche_date(Settings::get("begin_bookings")).'</b></p>';
	echo '<p>'.get_vocab("end_bookings").'<b>'.affiche_date(Settings::get("end_bookings")).'</b></p>';
    echo "<p>";
        if ($back !=''){
            echo "<a href=".$back.">".get_vocab('returnprev')."</a>";
        }
    echo "</p>";
    echo "</body>\n</html>";
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
	list( , $end_date) =  period_date_string($ends, -1);
	$duration = $ends - $starts;
	toPeriodString($start_period, $duration, $dur_units);
	if ($duration > 1)
	{
		list( , $start_date) =  period_date_string($starts);
		list( , $end_date) =  period_date_string($ends, -1);
	}
	return array($datedebut, $periodedebut, $duration, $dur_units);
}
#Convertit l'heure de début et de fin en un tableau donnant date, heure de début et durée.
function describe_span($starts, $ends, $dformat)
{
	global $vocab, $twentyfourhour_format;
	$start_date = utf8_strftime($dformat, $starts);
	if ($twentyfourhour_format)
		$timeformat = "%T";
	else
	{
		$ampm = date("a",$starts);
		$timeformat = "%I:%M$ampm";
	}
	$start_time = utf8_strftime($timeformat, $starts);
	$duration = $ends - $starts;
	toTimeString($duration, $dur_units);
	return array($start_date, $start_time ,$duration, $dur_units);
}

function get_planning_area_values($id_area)
{
	global $resolution, $morningstarts, $eveningends, $eveningends_minutes, $weekstarts, $twentyfourhour_format, $enable_periods, $periods_name, $display_day, $nb_display_day;
	$sql = "SELECT calendar_default_values, resolution_area, morningstarts_area, eveningends_area, eveningends_minutes_area, weekstarts_area, twentyfourhour_format_area, enable_periods, display_days
	FROM ".TABLE_PREFIX."_area
	WHERE id = '".protect_data_sql($id_area)."'";
	$res = grr_sql_query($sql);
	if (!$res)
	{
		// fatal_error(0, grr_sql_error());
		include "trailer.inc.php";
		exit;
	}
	$row_ = grr_sql_row($res, 0);
	$nb_display_day = 0;
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
						if (Settings::get("automatic_mail") == 'yes')
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
	if (((Settings::get("date_verify_reservation2") == "") || (Settings::get("date_verify_reservation2") < $date_now )) && (Settings::get("automatic_mail") == 'yes'))
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
		$sql = "SELECT display_days FROM ".TABLE_PREFIX."_area WHERE id = '".protect_data_sql($area)."'";
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
	$sql = "SELECT display_days FROM ".TABLE_PREFIX."_area WHERE id = '".protect_data_sql($area)."'";
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
	where ".TABLE_PREFIX."_utilisateurs.etat = 'actif' AND 
	".TABLE_PREFIX."_utilisateurs.login = ".TABLE_PREFIX."_j_user_room.login and 
	id_room='".$id_room."'";
	$res = grr_sql_query($sql);
	if ($res)
	{
		for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
		{
			if (validate_email($row[0]))
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
			where ".TABLE_PREFIX."_utilisateurs.etat = 'actif' AND
			".TABLE_PREFIX."_utilisateurs.login = ".TABLE_PREFIX."_j_useradmin_area.login and ".TABLE_PREFIX."_j_useradmin_area.id_area='".$id_area."'");
		if ($sql_admin)
		{
			for ($i = 0; ($row = grr_sql_row($sql_admin, $i)); $i++)
			{
				if (validate_email($row[0]))
				{
					$emails[] = $row[0];
					$raison = 2;
				}
			}
		}
	}
	// Si la table des emails des administrateurs du domaines est vide, on avertit les administrateurs des sites
	if (Settings::get("module_multisite") == "Oui")
	{
		if (count($emails) == 0)
		{
			$id_area = mrbsGetAreaIdFromRoomId($id_room);
			$id_site = mrbsGetAreaSite($id_area);
			$sql_admin = grr_sql_query("select email from ".TABLE_PREFIX."_utilisateurs, ".TABLE_PREFIX."_j_useradmin_site
				where ".TABLE_PREFIX."_utilisateurs.etat = 'actif' AND
				".TABLE_PREFIX."_utilisateurs.login = ".TABLE_PREFIX."_j_useradmin_site.login and ".TABLE_PREFIX."_j_useradmin_site.id_site='".$id_site."'");
			if ($sql_admin)
			{
				for ($i = 0; ($row = grr_sql_row($sql_admin, $i)); $i++)
				{
					if (validate_email($row[0]))
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
		$sql_admin = grr_sql_query("select email from ".TABLE_PREFIX."_utilisateurs where etat = 'actif' AND statut = 'administrateur'");
		if ($sql_admin)
		{
			for ($i = 0; ($row = grr_sql_row($sql_admin, $i)); $i++)
			{
				if (validate_email($row[0]))
				{
					$emails[] = $row[0];
					$raison = 4;
				}
			}
		}
	}
	return array($emails,$raison);
}
/** validate_email ($email)
 * Détermine si l'adresse mail en paramètre est syntaxiquement valable
 * Rend un booléen
*/
function validate_email ($email)
{
	$atom   = '[-a-z0-9!#$%&\'*+\\/=?^_`{|}~]';
		// caractères autorisés avant l'arobase
	$domain = '([a-z0-9]([-a-z0-9]*[a-z0-9]+)?)';
		// caractères autorisés après l'arobase (nom de domaine)
	$regex = '/^' . $atom . '+' . '(\.' . $atom . '+)*' . '@' . '(' . $domain . '{1,63}\.)+' . $domain . '{2,63}$/i';
	if (preg_match($regex, $email))
		return true;
	else {
        $regex2 = '/^' . $atom . '+' . '(\.' . $atom . '+)*' . '@' . 'localhost/i';
        return preg_match($regex2, $email);
    }
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
function traite_grr_url($grr_script_name = "", $force_use_grr_url = "n")
{
	// Dans certaines configuration (reverse proxy, ...) les variables $_SERVER["SCRIPT_NAME"] ou $_SERVER['PHP_SELF']
	// sont mal interprétées entraînant des liens erronés sur certaines pages.
	if (((Settings::get("use_grr_url") == "y") && (Settings::get("grr_url") != "")) || ($force_use_grr_url == "y"))
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

	$authGetUserLevel = authGetUserLevel(getUserName(), $room);

	// Brève description ou le numéro de la réservation
	if( ($authGetUserLevel == 0 && Settings::get("display_short_description_nc") == 1) || 
		($authGetUserLevel == 1 && Settings::get("display_short_description_vi") == 1) ||
		($authGetUserLevel == 2 && Settings::get("display_short_description_us") == 1) || 
		($authGetUserLevel == 3 && Settings::get("display_short_description_gr") == 1) || 
		($authGetUserLevel >= 4)
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
	$authGetUserLevel = authGetUserLevel(getUserName(), $room);
	// Les champs add :
	$overload_data = mrbsEntryGetOverloadDesc($id_resa);
	foreach ($overload_data as $fieldname=>$field)
	{
		if ((($authGetUserLevel >= 4) ||($field["affichage"] == 'y')) and ($field["valeur"]!=""))
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
	$room = $resa[5];

	$authGetUserLevel = authGetUserLevel(getUserName(), $room);

	// Heures ou créneaux + symboles <== ==>
	if( ($authGetUserLevel == 0 && Settings::get("display_horaires_nc") == 1) || 
		($authGetUserLevel == 1 && Settings::get("display_horaires_vi") == 1) ||
		($authGetUserLevel == 2 && Settings::get("display_horaires_us") == 1) || 
		($authGetUserLevel == 3 && Settings::get("display_horaires_gr") == 1) || 
		($authGetUserLevel >= 4 && Settings::get("display_horaires_ad") == 1) &&
		$heures != ""
 	)
        $affichage .= $heures."<br>";

	// Ressource seulement dans les vues globales
	if($vue == 2)
		$affichage .= $resa[5]."<br>";

	// Bénéficiaire
	if( ($authGetUserLevel == 0 && Settings::get("display_beneficiaire_nc") == 1) || 
		($authGetUserLevel == 1 && Settings::get("display_beneficiaire_vi") == 1) ||
		($authGetUserLevel == 2 && Settings::get("display_beneficiaire_us") == 1) || 
		($authGetUserLevel == 3 && Settings::get("display_beneficiaire_gr") == 1) || 
		($authGetUserLevel >= 4 && Settings::get("display_beneficiaire_ad") == 1)  
	  )
	{
		$affichage .= affiche_nom_prenom_email($resa[4], $resa[12], "nomail")."<br>";
	}

	// Type
	if( ($authGetUserLevel == 0 && Settings::get("display_type_nc") == 1) || 
		($authGetUserLevel == 1 && Settings::get("display_type_vi") == 1) ||
		($authGetUserLevel == 2 && Settings::get("display_type_us") == 1) || 
		($authGetUserLevel == 3 && Settings::get("display_type_gr") == 1) || 
		($authGetUserLevel >= 4 && Settings::get("display_type_ad") == 1)  
	  )
	{
        $typeResa = grr_sql_query1("SELECT ".TABLE_PREFIX."_type_area.type_name FROM ".TABLE_PREFIX."_type_area JOIN ".TABLE_PREFIX."_entry ON ".TABLE_PREFIX."_entry.type=".TABLE_PREFIX."_type_area.type_letter WHERE ".TABLE_PREFIX."_entry.id = '".$resa[2]."';");
		if ($typeResa != -1)
			$affichage .= $typeResa."<br>";
	}

	// Brève description ou le numéro de la réservation
	if( ($authGetUserLevel == 0 && Settings::get("display_short_description_nc") == 1) || 
		($authGetUserLevel == 1 && Settings::get("display_short_description_vi") == 1) ||
		($authGetUserLevel == 2 && Settings::get("display_short_description_us") == 1) || 
		($authGetUserLevel == 3 && Settings::get("display_short_description_gr") == 1) || 
		($authGetUserLevel >= 4 && Settings::get("display_short_description_ad") == 1) &&
		$resa[3] != ""
	  )
		$affichage .= htmlspecialchars($resa[3],ENT_NOQUOTES)."<br>";
	else
		$affichage .= get_vocab("entryid").$resa[2]."<br>";

	// Description Complète
	if( ($authGetUserLevel == 0 && Settings::get("display_full_description_nc") == 1) || 
		($authGetUserLevel == 1 && Settings::get("display_full_description_vi") == 1) ||
		($authGetUserLevel == 2 && Settings::get("display_full_description_us") == 1) || 
		($authGetUserLevel == 3 && Settings::get("display_full_description_gr") == 1) || 
		($authGetUserLevel >= 4 && Settings::get("display_full_description_ad") == 1) &&
		$resa[8] != ""
	  )
		$affichage .= htmlspecialchars($resa[8],ENT_NOQUOTES)."<br>";

	// Champs Additionnels
    // la ressource associée à la réservation :
	//$res = mrbsGetEntryInfo($resa[2]);
	//$room = (!$res) ? -1 : $res["room_id"]; 
   
	// Les champs add :
	$overload_data = grrGetOverloadDescArray($ofl, $resa[16]);//mrbsEntryGetOverloadDesc($resa[2]);
	foreach ($overload_data as $fieldname=>$field)
	{
		if (( ($authGetUserLevel >= 4 && $field["confidentiel"] == 'n') || $field["affichage"] == 'y') && $field["valeur"] != "") {
			// ELM - Gestion des champs aditionnels multivalués (lignes 384 - 392)
			$valeur = str_replace("|", ",", $field["valeur"]);
			$affichage .= "<i>".htmlspecialchars($fieldname,ENT_NOQUOTES).get_vocab("deux_points").htmlspecialchars($valeur,ENT_NOQUOTES|ENT_SUBSTITUTE)."</i><br />";
		}
	}

	// Emprunte
	if($resa[7] != "-")
		$affichage .= "<img src=\"img_grr/buzy.png\" alt=\"".get_vocab("ressource_actuellement_empruntee")."\" title=\"".get_vocab("ressource_actuellement_empruntee")."\" width=\"20\" height=\"20\" class=\"image\" /> ";

	// Option réservation
	if($resa[9] > 0 && $resa[10] > 0)
		$affichage .=  " <img src=\"img_grr/small_flag.png\" alt=\"".get_vocab("reservation_a_confirmer_au_plus_tard_le")."\" title=\"".get_vocab("reservation_a_confirmer_au_plus_tard_le").time_date_string_jma($resa[9],$dformat)."\" width=\"20\" height=\"20\" class=\"image\" /> ";

	// Modération
	if($resa[11] == 1)
		$affichage .= " <img src=\"img_grr/flag_moderation.png\" alt=\"".get_vocab("en_attente_moderation")."\" title=\"".get_vocab("en_attente_moderation")."\" width=\"20\" height=\"20\" class=\"image\" /> ";

	// Clef
	if($resa[13] == 1)
		$affichage .= " <img src=\"img_grr/skey.png\" width=\"20\" height=\"20\" class=\"image\" alt=\"Clef\"> ";
	
	// Courrier
	if (Settings::get('show_courrier') == 'y')
	{
		if($resa[14] == 1)
			$affichage .= " <img src=\"img_grr/scourrier.png\" width=\"20\" height=\"20\" class=\"image\" alt=\"Courrier\"> ";
		else
			$affichage .= " <img src=\"img_grr/hourglass.png\" width=\"20\" height=\"20\" class=\"image\" alt=\"Buzy\"> ";
	}

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
	$room = $resa[5];
	$authGetUserLevel = authGetUserLevel(getUserName(), $room);

	// Heures ou créneaux + symboles <== ==>
	if( ($authGetUserLevel == 0 && Settings::get("display_horaires_nc") == 2) || 
		($authGetUserLevel == 1 && Settings::get("display_horaires_vi") == 2) ||
		($authGetUserLevel == 2 && Settings::get("display_horaires_us") == 2) || 
		($authGetUserLevel == 3 && Settings::get("display_horaires_gr") == 2) || 
		($authGetUserLevel >= 4 && Settings::get("display_horaires_ad") == 2) &&
		$heures != ""
 	)
        $affichage .= $heures."\n";

	// Ressource seulement dans les vues globales
	if($vue == 2)
		$affichage .= $resa[5]."\n";

	// Bénéficiaire
	if( ($authGetUserLevel == 0 && Settings::get("display_beneficiaire_nc") == 2) || 
		($authGetUserLevel == 1 && Settings::get("display_beneficiaire_vi") == 2) ||
		($authGetUserLevel == 2 && Settings::get("display_beneficiaire_us") == 2) || 
		($authGetUserLevel == 3 && Settings::get("display_beneficiaire_gr") == 2) || 
		($authGetUserLevel >= 4 && Settings::get("display_beneficiaire_ad") == 2)  
	  )
	{
		$affichage .= affiche_nom_prenom_email($resa[4], $resa[12], "nomail")."\n";
	}

	// Type
	if( ($authGetUserLevel == 0 && Settings::get("display_type_nc") == 2) || 
		($authGetUserLevel == 1 && Settings::get("display_type_vi") == 2) ||
		($authGetUserLevel == 2 && Settings::get("display_type_us") == 2) || 
		($authGetUserLevel == 3 && Settings::get("display_type_gr") == 2) || 
		($authGetUserLevel >= 4 && Settings::get("display_type_ad") == 2)  
	  )
	{
        $typeResa = grr_sql_query1("SELECT ".TABLE_PREFIX."_type_area.type_name FROM ".TABLE_PREFIX."_type_area JOIN ".TABLE_PREFIX."_entry ON ".TABLE_PREFIX."_entry.type=".TABLE_PREFIX."_type_area.type_letter WHERE ".TABLE_PREFIX."_entry.id = '".$resa[2]."';");
		if ($typeResa != -1)
			$affichage .= $typeResa."\n";
	}

	// Brève description ou le numéro de la réservation
	if( ($authGetUserLevel == 0 && Settings::get("display_short_description_nc") == 2) || 
		($authGetUserLevel == 1 && Settings::get("display_short_description_vi") == 2) ||
		($authGetUserLevel == 2 && Settings::get("display_short_description_us") == 2) || 
		($authGetUserLevel == 3 && Settings::get("display_short_description_gr") == 2) || 
		($authGetUserLevel >= 4 && Settings::get("display_short_description_ad") == 2) &&
		$resa[3] != ""
	  )
		$affichage .= htmlspecialchars($resa[3],ENT_NOQUOTES)."\n";
	else
		$affichage .= get_vocab("entryid").$resa[2]."\n";

	// Description Complète
	if( ($authGetUserLevel == 0 && Settings::get("display_full_description_nc") == 2) || 
		($authGetUserLevel == 1 && Settings::get("display_full_description_vi") == 2) ||
		($authGetUserLevel == 2 && Settings::get("display_full_description_us") == 2) || 
		($authGetUserLevel == 3 && Settings::get("display_full_description_gr") == 2) || 
		($authGetUserLevel >= 4 && Settings::get("display_full_description_ad") == 2) &&
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
		if (( ($authGetUserLevel >= 4 && $field["confidentiel"] == 'n') || $field["affichage"] == 'y') && $field["valeur"] != "") {
			// ELM - Gestion des champs aditionnels multivalués (lignes 384 - 392)
			$valeur = str_replace("|", ",", $field["valeur"]);
			$affichage .= "<i>".htmlspecialchars($fieldname,ENT_NOQUOTES).get_vocab("deux_points").htmlspecialchars($valeur,ENT_NOQUOTES|ENT_SUBSTITUTE)."</i>\n";
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
	$room = $resa[5];

	// Heures ou créneaux + symboles <== ==>
	$affichage .= $heures;
	$authGetUserLevel = authGetUserLevel(getUserName(), $room);

	// Bénéficiaire
	if( ($authGetUserLevel == 0 && Settings::get("display_beneficiaire_nc") >= 1) || 
		($authGetUserLevel == 1 && Settings::get("display_beneficiaire_vi") >= 1) ||
		($authGetUserLevel == 2 && Settings::get("display_beneficiaire_us") >= 1) || 
		($authGetUserLevel == 3 && Settings::get("display_beneficiaire_gr") >= 1) || 
		($authGetUserLevel >= 4 && Settings::get("display_beneficiaire_ad") >= 1)  
	  )
	{
		$affichage .= "\n".affiche_nom_prenom_email($resa[4], $resa[12], "nomail");
	}

	// Type
	if( ($authGetUserLevel == 0 && Settings::get("display_type_nc") >= 1) || 
		($authGetUserLevel == 1 && Settings::get("display_type_vi") >= 1) ||
		($authGetUserLevel == 2 && Settings::get("display_type_us") >= 1) || 
		($authGetUserLevel == 3 && Settings::get("display_type_gr") >= 1) || 
		($authGetUserLevel >= 4 && Settings::get("display_type_ad") >= 1)  
	  )
	{
        $typeResa = grr_sql_query1("SELECT ".TABLE_PREFIX."_type_area.type_name FROM ".TABLE_PREFIX."_type_area JOIN ".TABLE_PREFIX."_entry ON ".TABLE_PREFIX."_entry.type=".TABLE_PREFIX."_type_area.type_letter WHERE ".TABLE_PREFIX."_entry.id = '".$resa[2]."';");
		if ($typeResa != -1)
			$affichage .= "\n".$typeResa;
	}

	// Brève description ou le numéro de la réservation
	if( ($authGetUserLevel == 0 && Settings::get("display_short_description_nc") >= 1) || 
		($authGetUserLevel == 1 && Settings::get("display_short_description_vi") >= 1) ||
		($authGetUserLevel == 2 && Settings::get("display_short_description_us") >= 1) || 
		($authGetUserLevel == 3 && Settings::get("display_short_description_gr") >= 1) || 
		($authGetUserLevel >= 4 && Settings::get("display_short_description_ad") >= 1) &&
		$resa[3] != ""
	  )
		$affichage .= "\n".htmlspecialchars($resa[3],ENT_NOQUOTES);
	else
		$affichage .= "\n".get_vocab("entryid").$resa[2];

	// Description Complète
	if( ($authGetUserLevel == 0 && Settings::get("display_full_description_nc") >= 1) || 
		($authGetUserLevel == 1 && Settings::get("display_full_description_vi") >= 1) ||
		($authGetUserLevel == 2 && Settings::get("display_full_description_us") >= 1) || 
		($authGetUserLevel == 3 && Settings::get("display_full_description_gr") >= 1) || 
		($authGetUserLevel >= 4 && Settings::get("display_full_description_ad") >= 1) &&
		$resa[8] != ""
	  )
		$affichage .= "\n".htmlspecialchars($resa[8],ENT_NOQUOTES);

	// Champs Additionnels
	$authGetUserLevel = authGetUserLevel(getUserName(), $room);
	$overload_data = grrGetOverloadDescArray($ofl, $resa[16]);//mrbsEntryGetOverloadDesc($resa[2]);
	foreach ($overload_data as $fieldname=>$field)
	{
		if (( ($authGetUserLevel >= 4 && $field["confidentiel"] == 'n') || $field["affichage"] == 'y') && $field["valeur"] != "")
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
	$room = $resa[5];

	$authGetUserLevel = authGetUserLevel(getUserName(), $room);

	// Bénéficiaire
	if( ($authGetUserLevel == 0 && Settings::get("display_beneficiaire_nc") == 1) || 
		($authGetUserLevel == 1 && Settings::get("display_beneficiaire_vi") == 1) ||
		($authGetUserLevel == 2 && Settings::get("display_beneficiaire_us") == 1) || 
		($authGetUserLevel == 3 && Settings::get("display_beneficiaire_gr") == 1) || 
		($authGetUserLevel >= 4 && Settings::get("display_beneficiaire_ad") == 1)  
	  )
		$affichage .= affiche_nom_prenom_email($resa[4], $resa[12], "nomail");

	// Brève description ou le numéro de la réservation
	if( ($authGetUserLevel == 0 && Settings::get("display_short_description_nc") == 1) || 
		($authGetUserLevel == 1 && Settings::get("display_short_description_vi") == 1) ||
		($authGetUserLevel == 2 && Settings::get("display_short_description_us") == 1) || 
		($authGetUserLevel == 3 && Settings::get("display_short_description_gr") == 1) || 
		($authGetUserLevel >= 4 && Settings::get("display_short_description_ad") == 1) &&
		$resa[3] != ""
	  )
		$affichage .= htmlspecialchars($resa[3],ENT_NOQUOTES);
	else
		$affichage .= get_vocab("entryid").$resa[2];

	// Description Complète
	if( ($authGetUserLevel == 0 && Settings::get("display_full_description_nc") == 1) || 
		($authGetUserLevel == 1 && Settings::get("display_full_description_vi") == 1) ||
		($authGetUserLevel == 2 && Settings::get("display_full_description_us") == 1) || 
		($authGetUserLevel == 3 && Settings::get("display_full_description_gr") == 1) || 
		($authGetUserLevel >= 4 && Settings::get("display_full_description_ad") == 1) &&
		$resa[8] != ""
	  )
		$affichage .= htmlspecialchars($resa[8],ENT_NOQUOTES);
    // on coupe aux quatre premiers caractères
    $affichage = substr($affichage,0,4)."<br />";

	// Emprunte
	if($resa[7] != "-")
		$affichage .= "<img src=\"img_grr/buzy.png\" alt=\"".get_vocab("ressource_actuellement_empruntee")."\" title=\"".get_vocab("ressource_actuellement_empruntee")."\" width=\"20\" height=\"20\" class=\"image\" /> ";
	// Option réservation
	if($resa[9] > 0 && $resa[10] > 0)
		$affichage .=  " <img src=\"img_grr/small_flag.png\" alt=\"".get_vocab("reservation_a_confirmer_au_plus_tard_le")."\" title=\"".get_vocab("reservation_a_confirmer_au_plus_tard_le").time_date_string_jma($resa[9],$dformat)."\" width=\"20\" height=\"20\" class=\"image\" /> ";
	// Modération
	if($resa[11] == 1)
		$affichage .= " <img src=\"img_grr/flag_moderation.png\" alt=\"".get_vocab("en_attente_moderation")."\" title=\"".get_vocab("en_attente_moderation")."\" width=\"20\" height=\"20\" class=\"image\" /> ";
	// Clef
	if($resa[13] == 1)
		$affichage .= " <img src=\"img_grr/skey.png\" width=\"20\" height=\"20\" class=\"image\" alt=\"Clef\"> ";
	// Courrier
	if (Settings::get('show_courrier') == 'y')
	{
		if($resa[14] == 1)
			$affichage .= " <img src=\"img_grr/scourrier.png\" width=\"20\" height=\"20\" class=\"image\" alt=\"Courrier\"> ";
		else
			$affichage .= " <img src=\"img_grr/hourglass.png\" width=\"20\" height=\"20\" class=\"image\" alt=\"Buzy\"> ";
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
$type_affichage = "admin" -> Affichage des "pop-up" de confirmation dans les menus d'administration
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
		else if ($type_affichage == "admin")
		{
			if (!(Settings::get("javascript_info_admin_disabled")))
			{
				echo "<script type=\"text/javascript\">";
				echo "<!--\n";
				echo " alert(\"".$msg."\")";
				echo "//-->";
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
 			$libellefonction = protect_data_sql($libellefonction);
 			grr_sql_command("INSERT INTO grr_correspondance_statut(code_fonction,libelle_fonction,statut_grr) VALUES ('$codefonction', '$libellefonction', '$_statut')");
 			return $_statut;
 		}
		//Le code fonction n'est pas défini, alors on retourne le statut par défaut.
 	}
 	else
 		return $_statut;
 }
/* function jQuery_DatePicker($typeDate)
 * fonction qui rend un sélecteur de date couplé à un calendrier jQuery-DatePicker
 * définit trois input : $typeDate.'day', $typeDate.'month', $typeDate.'year'
 * /!\ changement de spécification : le préfixe $typeDate doit comporter un éventuel '_'
*/
function jQuery_DatePicker($typeDate){

    if (@file_exists('../personnalisation/connect.inc.php')){
        $racine = "../";
    } else{
        $racine = "./";
    }

    if ($typeDate == 'rep_end_' && isset($_GET['id'])){
        $res = grr_sql_query("SELECT repeat_id FROM ".TABLE_PREFIX."_entry WHERE id=".$_GET['id'].";");
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
        }
        else{
            $day = (isset ($_GET['day'])) ? clean_input($_GET['day']) : date("d");
            $month = (isset ($_GET['month']))? clean_input($_GET['month']) : date("m");
            $year = (isset ($_GET['year']))? clean_input($_GET['year']) : date("Y");
        }
    }
    else{
        global $start_day, $start_month, $start_year, $end_day, $end_month, $end_year;

        $day = (isset ($_GET['day'])) ? clean_input($_GET['day']) : date("d");
        if (isset($start_day) && $typeDate=='start_'){
            $day = $start_day;
        } 
        elseif (isset($end_day) && $typeDate=='end_'){
            $day = $end_day;
        }
        $month = (isset ($_GET['month']))? clean_input($_GET['month']) : date("m");
        if (isset($start_month) && $typeDate=='start_'){
            $month = $start_month;
        } 
        elseif (isset($end_month) && $typeDate=='end_'){
            $month = $end_month;
        }
        $year = (isset ($_GET['year']))? clean_input($_GET['year']) : date("Y");
        if (isset($start_year) && $typeDate=='start_'){
            $year = $start_year;
        } 
        elseif (isset($end_year) && $typeDate=='end_'){
            $year = $end_year;
        }
    }
 	$mindate = utf8_strftime("%d/%m/%Y",Settings::get('begin_bookings'));
    $maxdate = utf8_strftime("%d/%m/%Y",Settings::get('end_bookings'));
    genDateSelector("".$typeDate, "$day", "$month", "$year","");
 	echo '<input type="hidden" disabled="disabled" id="mydate_' .$typeDate. '">'.PHP_EOL;
 	echo '<script>'.PHP_EOL;
 	echo '	$(function() {'.PHP_EOL;
 		echo '$.datepicker.setDefaults( $.datepicker.regional[\'fr\'] );'.PHP_EOL;
 		echo '	$(\'#mydate_' .$typeDate. '\').datepicker({'.PHP_EOL;
 			echo '		beforeShow: readSelected, onSelect: updateSelected,'.PHP_EOL;
 			echo '		showOn: \'both\', buttonImageOnly: true, buttonImage: \'img_grr/calendar.png\',buttonText: "Choisir la date"});'.PHP_EOL;
    echo '		function readSelected()'.PHP_EOL;
    echo '		{'.PHP_EOL;
    echo '			$(\'#mydate_' .$typeDate. '\').val($(\'#' .$typeDate. 'day\').val() + \'/\' +'.PHP_EOL;
	echo '			$(\'#' .$typeDate. 'month\').val() + \'/\' + $(\'#' .$typeDate. 'year\').val());'.PHP_EOL;
    echo '			return {};'.PHP_EOL;
    echo '		}'.PHP_EOL;
    echo '		function updateSelected(date)'.PHP_EOL;
    echo '		{'.PHP_EOL;
    echo '			$(\'#' .$typeDate. 'day\').val(date.substring(0, 2));'.PHP_EOL;
    echo '			$(\'#' .$typeDate. 'month\').val(date.substring(3, 5));'.PHP_EOL;
    echo '			$(\'#' .$typeDate. 'year\').val(date.substring(6, 10));'.PHP_EOL;
    echo '		}'.PHP_EOL;
    echo '	});'.PHP_EOL;
    echo '</script>'.PHP_EOL;
}

function jQuery_DatePickerTwig($typeDate){

	if (@file_exists('../personnalisation/connect.inc.php')){
		$racine = "../";
	} else{
		$racine = "./";
	}

	if ($typeDate == 'rep_end' && isset($_GET['id'])){
		$res = grr_sql_query("SELECT repeat_id FROM ".TABLE_PREFIX."_entry WHERE id=".$_GET['id'].";");
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
				$day = $_GET['day'];
			else
				$day = date("d");
			if (isset ($_GET['month']))
				$month = $_GET['month'];
			else
				$month = date("m");
			if (isset ($_GET['year']))
				$year = $_GET['year'];
			else
				$year = date("Y");
		}
	} else{
		global $start_day, $start_month, $start_year, $end_day, $end_month, $end_year;

		if (isset ($_GET['day'])){
			$day = $_GET['day'];
		} else{
			$day = date("d");
		}

		if (isset($start_day) && $typeDate=='start'){
			$day = $start_day;
		} elseif (isset($end_day) && $typeDate=='end'){
			$day = $end_day;
		}

		if (isset ($_GET['month'])){
			$month = $_GET['month'];
		} else{
			$month = date("m");
		}

		if (isset($start_month) && $typeDate=='start'){
			$month = $start_month;
		} elseif (isset($end_month) && $typeDate=='end'){
			$month = $end_month;
		}

		if(isset ($_GET['year'])){
			$year = $_GET['year'];
		} else{
			$year = date("Y");
		}

		if (isset($start_year) && $typeDate=='start'){
			$year = $start_year;
		} elseif (isset($end_year) && $typeDate=='end'){
			$year = $end_year;
		}
 	}

	if($typeDate != '')
		$typeDate = $typeDate."_";

 	$retour = genDateSelectorForm($typeDate, "$day", "$month", "$year","");

	return $retour;
}

function jQuery_TimePicker($typeTime, $start_hour, $start_min,$dureepardefaultsec)
{
	if (isset ($_GET['id']))
	{
		if (isset($start_hour) && isset($start_min))
		{
			$hour = $start_hour;
			$minute = $start_min;
		}
		else
		{
			$hour = date("h");
			$minute = date("m");
		}
	}
	else
	{
		$hour = (isset ($_GET['hour']))? clean_input($_GET['hour']) : date("h");
		$minute = (isset ($_GET['minute']))? clean_input($_GET['minute']) : date("m");
			
		if ($typeTime == 'end_'){
            $dureepardefautmin = $dureepardefaultsec/60;
            if ($dureepardefautmin == 60){
                $ajout = 1;
                $hour = $_GET['hour'] + $ajout;
                $minute ="00";
            }
            elseif ($dureepardefautmin < 60){
                $hour = $_GET['hour'];
                $minute =$dureepardefautmin;
            }
            elseif ($dureepardefautmin > 60){
                $dureepardefautheure = $dureepardefautmin/60;
            //	if (($dureepardefautheure % 60)!=0){
        //		$hour = $_GET['hour']+ $dureepardefautheure;
                $hour = ($_GET['hour']+ $dureepardefautheure)%24; // Modulo 24
                $hour = str_pad($hour, 2, 0, STR_PAD_LEFT); // Affichage heure sur 2 digits 
                $minute = ($_GET['minute'] == 30)? 30 : "00";
        //		}
            }
        }
	}
	if ($minute == 0)
		$minute = '00';
	// MAJ
	echo '<div class="input-group clockpicker">
	<input name="' .$typeTime. '" type="text" class="form-control" value="' .$hour. ':' .$minute. '">
	<span class="input-group-addon">
		<span class="glyphicon glyphicon-time"></span>
	</span>
</div>';
echo '<script type="text/javascript">
$(\'.clockpicker\').clockpicker({
	align: \'left\',
	placement: \'top\',
	donetext: \'Valider\'
});
</script>';
}
function jQuery_TimePicker2($typeTime, $start_hour, $start_min,$dureepardefaultsec,$resolution,$morningstarts,$eveningends,$eveningends_minutes,$twentyfourhour_format=0)
{
    $minTime = $morningstarts.":00";
    $eveningends_minutes = str_pad($eveningends_minutes, 2, 0, STR_PAD_LEFT);
    $maxTime = $eveningends.":".$eveningends_minutes;
	if (isset ($_GET['id']))
	{
		if (isset($start_hour) && isset($start_min))
		{
			$hour = $start_hour;
			$minute = $start_min;
		}
		else
		{
			$hour = date("H");
			$minute = date("i");
		}
	}
	else
	{
		$hour = (isset ($_GET['hour']))? clean_input($_GET['hour']) : date("H");
		$minute = (isset ($_GET['minute']))? clean_input($_GET['minute']) : date("i");

		if ($typeTime == 'end_')
        {
            $dureepardefautmin = $dureepardefaultsec/60;
            if ($dureepardefautmin == 60){
                $ajout = 1;
                $hour += $ajout;
                $minute ="00";
            }
            if ($dureepardefautmin < 60){
                $minute += $dureepardefautmin;
                if ($minute >= 60){
                    $hour++;
                    $minute = $minute%60;
                }
            }
            if ($dureepardefautmin > 60)
            {
                $dureepardefautheure = $dureepardefautmin/60;
                $hour = ($hour + $dureepardefautheure)%24;
                $hour = str_pad($hour, 2, 0, STR_PAD_LEFT);
                $minute += $dureepardefautmin % 60;
            }
        }
        $minute = str_pad($minute, 2, 0, STR_PAD_LEFT);
	}
    $timeFormat = ($twentyfourhour_format)? "H:i" : "h:i a";
	echo '<label for="'.$typeTime.'">'.get_vocab('time').get_vocab('deux_points').'</label>
    <div class="input-group timepicker">
	<input id="'.$typeTime.'" name="'.$typeTime.'" type="text" class="form-control time" value="'.$hour.':'.$minute. '" >
    <span class="input-group-addon btn" id="'.$typeTime.'clock'.'">
        <span class="glyphicon glyphicon-time" ></span>
    </span>
	</div>';
    echo '<script type="text/javascript">
        $(\'#'.$typeTime.'\').timepicker({
            \'step\': '.($resolution/60).',
            \'scrollDefault\': \''.$hour.':'.$minute.'\',
            \'minTime\': \''.$minTime.'\',
            \'maxTime\': \''.$maxTime.'\',
            \'timeFormat\': \''.$timeFormat.'\',
            \'forceRoundTime\': true,
        });
        $(\'#'.$typeTime.'\').timepicker(\'setTime\', \''.$hour.':'.$minute.'\');
		$(\'#'.$typeTime.'clock'.'\').on(\'click\', function() {
            $(\'#'.$typeTime.'\').timepicker(\'show\');
        });
        </script>';
}

function spinner ($duration)
{
	echo "<input class=\"form-control\" name=\"duration\" value=\"" .$duration. "\" id=\"spinner\" />";
	echo "<script type=\"text/javascript\" src=\"js/jquery.mousewheel.js\"></script>";
	echo "<script>$(function() {
		$('#spinner').spinner({
			min: 1,
			page: 10,
		});});
</script>";
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
/** NettoyerTablesJointure()
 *
 * Supprime les lignes inutiles dans les tables de liaison
 *
 */
function NettoyerTablesJointure()
{
	$nb = 0;
	// Table grr_j_mailuser_room
	$req = "SELECT j.login FROM ".TABLE_PREFIX."_j_mailuser_room j
	LEFT JOIN ".TABLE_PREFIX."_utilisateurs u on u.login=j.login
	WHERE (u.login  IS NULL)";
	$res = grr_sql_query($req);
	if ($res)
	{
		for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
		{
			$nb++;
			grr_sql_command("delete from ".TABLE_PREFIX."_j_mailuser_room where login='".$row[0]."'");
		}
	}
	// Table grr_j_user_area
	$req = "SELECT j.login FROM ".TABLE_PREFIX."_j_user_area j
	LEFT JOIN ".TABLE_PREFIX."_utilisateurs u on u.login=j.login
	WHERE (u.login  IS NULL)";
	$res = grr_sql_query($req);
	if ($res)
	{
		for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
		{
			$nb++;
			grr_sql_command("delete from ".TABLE_PREFIX."_j_user_area where login='".$row[0]."'");
		}
	}
	// Table grr_j_user_room
	$req = "SELECT j.login FROM ".TABLE_PREFIX."_j_user_room j
	LEFT JOIN ".TABLE_PREFIX."_utilisateurs u on u.login=j.login
	WHERE (u.login  IS NULL)";
	$res = grr_sql_query($req);
	if ($res)
	{
		for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
		{
			$nb++;
			grr_sql_command("DELETE FROM ".TABLE_PREFIX."_j_user_room WHERE login='".$row[0]."'");
		}
	}
	// Table grr_j_useradmin_area
	$req = "SELECT j.login FROM ".TABLE_PREFIX."_j_useradmin_area j
	LEFT JOIN ".TABLE_PREFIX."_utilisateurs u on u.login=j.login
	WHERE (u.login  IS NULL)";
	$res = grr_sql_query($req);
	if ($res)
	{
		for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
		{
			$nb++;
			grr_sql_command("DELETE FROM ".TABLE_PREFIX."_j_useradmin_area WHERE login='".$row[0]."'");
		}
	}
	// Table grr_j_useradmin_site
	$req = "SELECT j.login FROM ".TABLE_PREFIX."_j_useradmin_site j
	LEFT JOIN ".TABLE_PREFIX."_utilisateurs u on u.login=j.login
	WHERE (u.login  IS NULL)";
	$res = grr_sql_query($req);
	if ($res)
	{
		for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
		{
			$nb++;
			grr_sql_command("DELETE FROM ".TABLE_PREFIX."_j_useradmin_site WHERE login='".$row[0]."'");
		}
	}
	// Suppression effective
	echo "<hr />\n";
	echo "<p class='avertissement'>".get_vocab("tables_liaison").get_vocab("deux_points").$nb.get_vocab("entres_supprimees")."</p>\n";
}
if (!function_exists('htmlspecialchars_decode'))
{
	function htmlspecialchars_decode($text)
	{
		return strtr($text, array_flip(get_html_translation_table(HTML_SPECIALCHARS)));
	}
}

/*
* valideDate : Retourne true ou false selon si le format de la date est bon ou non
*/
function valideDate($date, $format = 'Y-m-d H:i:s')
{
	$d = DateTime::createFromFormat($format, $date);
	return $d && $d->format($format) == $date;
}

/*
* @param integer $delai : nombre de jours de rétention des logs de connexion
* nettoieLogConnexion efface les entrées de la table _log antérieures au jour courant moins le délai
*/
function nettoieLogConnexion($delai){
    // est-ce un administrateur ?
    if (authGetUserLevel(getUserName(), -1) >= 6){
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
    if (authGetUserLevel(getUserName(), -1) >= 6){
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
    $sql = "SELECT type_letter,couleurhexa,couleurtexte FROM ".TABLE_PREFIX."_type_area WHERE 1";
    $res = grr_sql_query($sql);
    if ($res->num_rows > 0) {
        $types = "<style>".PHP_EOL;
        while($row = $res->fetch_assoc()) {
            $types .= "td.type".$row["type_letter"]."{background:".$row["couleurhexa"]." !important;color:".$row["couleurtexte"]." !important;}".PHP_EOL;
            $types .= "td.type".$row["type_letter"]." a.lienCellule{color:".$row["couleurtexte"]." !important;}".PHP_EOL;
        }
        $types .= "</style>".PHP_EOL;
    }

	return $types;
}

// suggestions pour reformuler les pages plannings
function pageHead2($title, $page = "with_session") 
{
	if ($page == "with_session")
	{
		if($_SESSION['changepwd'] == 1 && $grr_script_name != 'changepwd.php'){
			header("Location: ./changepwd.php");
		} // est-ce bien placé ? YN le 27/02/2020

		if (isset($_SESSION['default_style']))
			$sheetcss = $_SESSION['default_style'];
		else {
            if (Settings::get("default_css")) 
				$sheetcss = Settings::get("default_css"); // thème global par défaut
			else
				$sheetcss = 'default'; // utilise le thème par défaut s'il n'a pas été défini
        }
		if (isset($_GET['default_language']))
		{
			$_SESSION['default_language'] = alphanum(clean_input($_GET['default_language']));
			if (isset($_SESSION['chemin_retour']) && ($_SESSION['chemin_retour'] != ''))
				header("Location: ".$_SESSION['chemin_retour']);
			else
				header("Location: ".traite_grr_url());
			die();
		}
	}
	else
	{
		if (Settings::get("default_css"))
			$sheetcss = Settings::get("default_css");
		else
			$sheetcss = 'default';
		if (isset($_GET['default_language']))
		{
			$_SESSION['default_language'] = alphanum(clean_input($_GET['default_language']));
			if (isset($_SESSION['chemin_retour']) && ($_SESSION['chemin_retour'] != ''))
				header("Location: ".$_SESSION['chemin_retour']);
			else
				header("Location: ".traite_grr_url());
			die();
		}
	}
	global $vocab, $charset_html, $unicode_encoding, $clock_file, $use_select2, $use_admin, $gcDossierCss, $version_grr;
    // récupération des couleurs des types
    $types = cssTypeResa();
    // code de la partie <head> 
	$a  = '<head>'.PHP_EOL;
	$a .= '<meta charset="utf-8">'.PHP_EOL;
	$a .= '<meta http-equiv="X-UA-Compatible" content="IE=edge">'.PHP_EOL;
	$a .= '<meta name="viewport" content="width=device-width, initial-scale=1">'.PHP_EOL;
	$a .= '<meta name="Robots" content="noindex" />'.PHP_EOL;
	$a .= '<title>'.$title.'</title>'.PHP_EOL;
	$a .= '<link rel="shortcut icon" href="./favicon.ico" />'.PHP_EOL;
	$a .= '<link rel="stylesheet" type="text/css" href="bootstrap/css/bootstrap.min.css?v='.$version_grr.'" />'.PHP_EOL;
	if (isset($use_select2))
	{
		$a .= '<link rel="stylesheet" type="text/css" href="bootstrap/css/select2.css?v='.$version_grr.'" />'.PHP_EOL;
		$a .= '<link rel="stylesheet" type="text/css" href="bootstrap/css/select2-bootstrap.css?v='.$version_grr.'" />'.PHP_EOL;
		$a .= '<link rel="stylesheet" type="text/css" href="bootstrap/css/bootstrap-multiselect.css?v='.$version_grr.'">'.PHP_EOL;
		$a .= '<link rel="stylesheet" type="text/css" href="bootstrap/css/bootstrap-clockpicker.min.css?v='.$version_grr.'">'.PHP_EOL;
	}
	$a .= '<link rel="stylesheet" type="text/css" href="bootstrap/css/jquery-ui.css?v='.$version_grr.'" />'.PHP_EOL;
	$a .= '<link rel="stylesheet" type="text/css" href="bootstrap/css/jquery-ui-timepicker-addon.css?v='.$version_grr.'" >'.PHP_EOL;
	$a .= '<link rel="stylesheet" type="text/css" href="themes/default/css/style.css?v='.$version_grr.'" />'.PHP_EOL; // le style par défaut
	
	if ((isset($_GET['pview'])) && ($_GET['pview'] == 1))
		$a .= '<link rel="stylesheet" type="text/css" href="themes/print/css/style.css?v='.$version_grr.'" />'.PHP_EOL;
	
	$a .= '<link rel="stylesheet" type="text/css" href="themes/'.$sheetcss.'/css/style.css?v='.$version_grr.'" />'.PHP_EOL; // le style couleurs prédéfinis
	if($sheetcss == "perso" && file_exists("personnalisation/".$gcDossierCss."/perso.css?v='.$version_grr.'"))
		$a .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"personnalisation/".$gcDossierCss."/perso.css?".Settings::get("sp_time")."\" />".PHP_EOL; // style perso via admin
		
	$a .= $types;
    $a .= '<script type="text/javascript" src="js/jquery.min.js?v='.$version_grr.'"></script>'.PHP_EOL;
	$a .= '<script type="text/javascript" src="js/jquery-ui.min.js?v='.$version_grr.'"></script>'.PHP_EOL;
	$a .= '<script type="text/javascript" src="js/jquery.validate.js?v='.$version_grr.'"></script>'.PHP_EOL;
	$a .= '<script type="text/javascript" src="js/jquery-ui-timepicker-addon.js?v='.$version_grr.'"></script>'.PHP_EOL;
	$a .= '<script type="text/javascript" src="bootstrap/js/bootstrap.min.js?v='.$version_grr.'"></script>'.PHP_EOL;
	$a .= '<script type="text/javascript" src="js/menu.js?v='.$version_grr.'"></script>'.PHP_EOL;
    $a .= '<script type="text/javascript" src="js/jquery.floatThead.min.js?v='.$version_grr.'"></script>'.PHP_EOL;
    //$a .= '<script type="text/javascript" src="js/planning2Thead.js"></script>'.PHP_EOL;
	$a .= '<script type="text/javascript" src="js/popup.js?v='.$version_grr.'" charset="utf-8"></script>'.PHP_EOL;
	$a .= '<script type="text/javascript" src="js/functions.js?v='.$version_grr.'" ></script>'.PHP_EOL;
	if (isset($use_select2))
	{
		$a .= '<script type="text/javascript" src="js/bootstrap-clockpicker.js?v='.$version_grr.'"></script>'.PHP_EOL;
		$a .= '<script type="text/javascript" src="js/bootstrap-multiselect.js?v='.$version_grr.'"></script>'.PHP_EOL;
		$a .= '<script type="text/javascript" src="js/select2.js?v='.$version_grr.'"></script>'.PHP_EOL;
		$a .= '<script type="text/javascript" src="js/select2_locale_fr.js?v='.$version_grr.'"></script>'.PHP_EOL;
	}
	if (isset($use_tooltip_js))
		echo '<script type="text/javascript" src="./include/tooltip.js?v='.$version_grr.'"></script>'.PHP_EOL;
	if (!isset($_SESSION['selection']))
		$a .= '<script type="text/javascript" src="js/selection.js?v='.$version_grr.'" ></script>'.PHP_EOL;
	if (@file_exists('js/'.$clock_file))
		$a .= '<script type="text/javascript" src="js/'.$clock_file.'?v='.$version_grr.'"></script>'.PHP_EOL;
	if (substr(phpversion(), 0, 1) < 7)
		$a .= get_vocab('not_php');

	$a .= '</head>'.PHP_EOL;
	return $a;
}

/*
** Fonction qui affiche le header
*/
function pageHeader2($day = '', $month = '', $year = '', $type_session = 'with_session', $adm=0)
{
	global $niveauDossier, $vocab, $search_str, $grrSettings, $clock_file, $desactive_VerifNomPrenomUser, $grr_script_name, $racine, $racineAd;
	global $use_prototype, $use_admin, $use_tooltip_js, $desactive_bandeau_sup, $id_site, $use_select2, $gcDossierImg;

    if (isset($_SERVER['QUERY_STRING']) && ($_SERVER['QUERY_STRING'] != ''))
        $parametres_url = htmlspecialchars($_SERVER['QUERY_STRING']);
        
	$resulHook = Hook::Appel("hookHeader2");
	echo $resulHook['hookHeader2'];
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
		if (!(isset($search_str)))
			$search_str = get_vocab("search_for");
		if (empty($search_str))
			$search_str = "";
		if (!(isset($desactive_bandeau_sup) && ($desactive_bandeau_sup == 1) && ($type_session != 'with_session')))
		{

			// HOOK
			$resulHook = Hook::Appel("hookHeader1");
			echo $resulHook['hookHeader1'];

			// On fabrique une date valide pour la réservation si ce n'est pas le cas
			$date_ = mktime(0, 0, 0, $month, $day, $year);
			if ($date_ < Settings::get("begin_bookings"))
				$date_ = Settings::get("begin_bookings");
			else if ($date_ > Settings::get("end_bookings"))
				$date_ = Settings::get("end_bookings");
			$day   = date("d",$date_);
			$month = date("m",$date_);
			$year  = date("Y",$date_);
			echo '<div id="panel">'.PHP_EOL;
			//Logo
			$nom_picture = $racine."personnalisation/".$gcDossierImg."/logos/".Settings::get("logo");
			if ((Settings::get("logo") != '') && (@file_exists($nom_picture)))
				echo '<div class="logo" height="100">'.PHP_EOL.'<a href="'.$racine.page_accueil('yes').'day='.$day.'&amp;year='.$year.'&amp;month='.$month.'"><img src="'.$nom_picture.'" alt="logo"/></a>'.PHP_EOL.'</div>'.PHP_EOL;
			//Accueil
			echo '<div class="accueil ">',PHP_EOL,'<h2>',PHP_EOL,'<a href="'.$racine.page_accueil('yes'),'day=',$day,'&amp;year=',$year,'&amp;month=',$month,'">',Settings::get("company"),'</a>',PHP_EOL,'</h2>',PHP_EOL, Settings::get('message_accueil'),'</div>',PHP_EOL;
			//Mail réservation
			$sql = "SELECT value FROM ".TABLE_PREFIX."_setting WHERE name='mail_etat_destinataire'";
			$res = grr_sql_query1($sql);
			if ((( $res == 1 && $type_session == "no_session" ) || ( ( $res == 1 || $res == 2) && $type_session == "with_session" && (authGetUserLevel(getUserName(), -1, 'area')) == 1  ) )/*&& acces_formulaire_reservation()*/)
			{
				echo '<div class="contactformulaire">',PHP_EOL,'<input class="btn btn-default" type="submit" rel="popup_name" value="'.get_vocab('reserver').'" onClick="javascript:location.href=\'app.php?p=contactresa&day=',$day,'&amp;month=',$month,'&amp;year=',$year,'\'" >',PHP_EOL,'</div>',PHP_EOL;
			}
			// Administration
			if ($type_session == "with_session")
			{
                $user_name = getUserName();
                $resaAModerer = resaToModerate($user_name);
                $nbResaAModerer = count($resaAModerer);
                $mess_resa = '';
                if ($nbResaAModerer > 1){$mess_resa = $nbResaAModerer.get_vocab('resasToModerate');}
                if ($nbResaAModerer == 1){$mess_resa = $nbResaAModerer.get_vocab('resaToModerate');}
				if ((authGetUserLevel($user_name, -1, 'area') >= 4) || (authGetUserLevel($user_name, -1, 'user') == 1) || ($mess_resa != '')) // trop large ? YN le 06/01/19
				{
					echo '<div class="administration">'.PHP_EOL;
					if ((authGetUserLevel($user_name, -1, 'area') >= 4) || (authGetUserLevel($user_name, -1, 'user') == 1))
                        echo "<br><a href='{$racineAd}admin.php?p=admin_accueil&amp;day={$day}&amp;month={$month}&amp;year={$year}'>".get_vocab('admin')."</a>".PHP_EOL;
					if (authGetUserLevel(getUserName(), -1, 'area') >= 6)
					{
						echo '<br />'.PHP_EOL;
						how_many_connected();
                        echo "<br />";
					}
                    echo "<p class='avertissement'><a href='{$racineAd}admin.php?p=admin_accueil&amp;".$parametres_url."' class='avertissement'>".$mess_resa."</a></p>".PHP_EOL;
					echo '</div>'.PHP_EOL;
				}
			}
			//if ($type_session != "with_session")
			//	echo '<script>selection()</script>'.PHP_EOL;
			echo '<div class="configuration" >'.PHP_EOL;
			if (@file_exists('js/'.$clock_file))
			{
				echo '<div class="clock">'.PHP_EOL;
				echo '<div id="Date">'.PHP_EOL;
				echo '&nbsp;<span id="hours"></span>'.PHP_EOL;
				echo 'h'.PHP_EOL;
				echo '<span id="min"></span>'.PHP_EOL;
				echo '</div></div>'.PHP_EOL;
			}
			$_SESSION['chemin_retour'] = '';
			if (isset($_SERVER['QUERY_STRING']) && ($_SERVER['QUERY_STRING'] != ''))
			{
				$_SESSION['chemin_retour'] = traite_grr_url($grr_script_name)."?". $_SERVER['QUERY_STRING'];
				echo '<a onclick="charger();" href="'.traite_grr_url($grr_script_name).'?'.$parametres_url.'&amp;default_language=fr-fr"><img src="'.$racine.'img_grr/fr_dp.png" alt="France" title="france" width="20" height="13" class="image" /></a>'.PHP_EOL;
				echo '<a onclick="charger();" href="'.traite_grr_url($grr_script_name).'?'.$parametres_url.'&amp;default_language=de-de"><img src="'.$racine.'img_grr/de_dp.png" alt="Deutch" title="deutch" width="20" height="13" class="image" /></a>'.PHP_EOL;
				echo '<a onclick="charger();" href="'.traite_grr_url($grr_script_name).'?'.$parametres_url.'&amp;default_language=en-gb"><img src="'.$racine.'img_grr/en_dp.png" alt="English" title="English" width="20" height="13" class="image" /></a>'.PHP_EOL;
				echo '<a onclick="charger();" href="'.traite_grr_url($grr_script_name).'?'.$parametres_url.'&amp;default_language=it-it"><img src="'.$racine.'img_grr/it_dp.png" alt="Italiano" title="Italiano" width="20" height="13" class="image" /></a>'.PHP_EOL;
				echo '<a onclick="charger();" href="'.traite_grr_url($grr_script_name).'?'.$parametres_url.'&amp;default_language=es-es"><img src="'.$racine.'img_grr/es_dp.png" alt="Spanish" title="Spanish" width="20" height="13" class="image" /></a>'.PHP_EOL;
			}
			if ($type_session == 'no_session')
			{
				$resulHook = Hook::Appel("hookLienConnexion2");
				if ((Settings::get('sso_statut') == 'cas_visiteur') || (Settings::get('sso_statut') == 'cas_utilisateur'))
				{
					echo '<br /> <a href="index.php?force_authentification=y">'.get_vocab("authentification").'</a>'.PHP_EOL;
					echo '<br /> <small><i><a href="login.php">'.get_vocab("connect_local").'</a></i></small>'.PHP_EOL;
				} elseif($resulHook['hookLienConnexion2'] != ""){
					echo $resulHook['hookLienConnexion2'];	
				}else{
					echo '<br /> <a href="login.php">'.get_vocab("connect").'</a>'.PHP_EOL;
				}
			}
			else
			{
				$resulHook = Hook::Appel("hookLienConnexion3");
				if( strlen(htmlspecialchars($_SESSION['prenom']).' '.htmlspecialchars($_SESSION['nom'])) > 40 )
					$nomAffichage =  htmlspecialchars($_SESSION['nom']);
				else
					$nomAffichage =  htmlspecialchars($_SESSION['prenom']).' '.htmlspecialchars($_SESSION['nom']);
			
				echo '<br /><a href="'.$racine.'compte/compte.php?day='.$day.'&amp;year='.$year.'&amp;month='.$month.'">'. $nomAffichage .' - '.get_vocab("manage_my_account").'</a>'.PHP_EOL;
				if (verif_access_search(getUserName()))
					echo '<br/><a href="'.$racine.'app.php?p=report">'.get_vocab("report").'</a>'.PHP_EOL;
				if($resulHook['hookLienConnexion3'] != "")
					echo $resulHook['hookLienConnexion3'];	
				$disconnect_link = false;
				if (!((Settings::get("cacher_lien_deconnecter") == 'y') && (isset($_SESSION['est_authentifie_sso']))))
				{
					$disconnect_link = true;
					if (Settings::get("authentification_obli") == 1)
						echo '<br /> <a href="'.$racine.'app.php?p=deconnexion&auto=0" >'.get_vocab('disconnect').'</a>'.PHP_EOL;
					else
						echo '<br /> <a href="'.$racine.'app.php?p=deconnexion&auto=0&amp;redirect_page_accueil=yes" >'.get_vocab('disconnect').'</a>'.PHP_EOL;
				}
				if ((Settings::get("Url_portail_sso") != '') && (isset($_SESSION['est_authentifie_sso'])))
				{
					if ($disconnect_link)
						echo ' - '.PHP_EOL;
					else
						echo '<br />'.PHP_EOL;
					echo '<a href="'.Settings::get("Url_portail_sso").'">'.get_vocab("Portail_accueil").'</a>'.PHP_EOL;
				}
				if ((Settings::get('sso_statut') == 'lasso_visiteur') || (Settings::get('sso_statut') == 'lasso_utilisateur'))
				{
					echo '<br />';
					if ($_SESSION['lasso_nameid'] == NULL)
						echo '<a href="lasso/federate.php">'.get_vocab('lasso_federate_this_account').'</a>'.PHP_EOL;
					else
						echo '<a href="lasso/defederate.php">'.get_vocab('lasso_defederate_this_account').'</a>'.PHP_EOL;
				}
			}
			echo '</div>'.PHP_EOL;
			echo '</div>'.PHP_EOL;
			echo '<a id="open" class="open" href="#"><span class="glyphicon glyphicon-arrow-up"><span class="glyphicon glyphicon-arrow-down"></span></span></a>'.PHP_EOL;
		}
	}
}
/*
** Fonction qui affiche le début d'une page avec entête et balise <section>
*/
function start_page_w_header($day = '', $month = '', $year = '', $type_session = 'with_session')
{
    global $racine,$racineAd;
    // pour le traitement des modules
    if (@file_exists('./admin_access_area.php')){
        $adm = 1;
        $racine = "../";
        $racineAd = "./";
    }
	elseif (@file_exists('./compte.php')){
        $adm = 1;
        $racine = "../";
        $racineAd = "./";
    }
    else{
        $adm = 0;
        $racine = "./";
        $racineAd = "./admin/";
    }
    include $racine."/include/hook.class.php";
    // code HTML
    header('Content-Type: text/html; charset=utf-8'); // en liaison avec la modification de pageHead2
    /*if (!isset($_COOKIE['open']))
	{
		setcookie("open", "true", time()+3600, "", "", false, false);
	}*/
    if (!isset($_COOKIE['open']))
    {
        header('Set-Cookie: open=true; SameSite=Lax; Secure=true;');
    }
    echo '<!DOCTYPE html>'.PHP_EOL;
    echo '<html lang="fr">'.PHP_EOL;
    // section <head>
    if ($type_session == "with_session")
        echo pageHead2(Settings::get("company"),"with_session");
    else
        echo pageHead2(Settings::get("company"),"no_session");
    // section <body>
    echo "<body>";
    // Menu du haut = section <header>
    echo "<header>";
    pageHeader2($day, $month, $year, $type_session, $adm);
    echo "</header>";
    // Debut de la page
    echo '<section>'.PHP_EOL;
    // doit être fermé par la fonction end_page
}   
/*
** Fonction qui affiche le début d'une page sans entête et avec une balise <section>
*/
function start_page_wo_header($titre, $type_session = 'with_session')
{
    // pour le traitement des modules
    if (@file_exists('./admin_access_area.php')){
        $adm = 1;
        $racine = "../";
        $racineAd = "./";
    }
    else{
        $adm = 0;
        $racine = "./";
        $racineAd = "./admin/";
    }
    include $racine."/include/hook.class.php";
    // code HTML
    header('Content-Type: text/html; charset=utf-8');
    /*if (!isset($_COOKIE['open']))
	{
		setcookie("open", "true", time()+3600, "", "", false, false);
	}*/
    if (!isset($_COOKIE['open']))
    {
        header('Set-Cookie: open=true; SameSite=Lax; Secure=true;');
    }
    echo '<!DOCTYPE html>'.PHP_EOL;
    echo '<html lang="fr">'.PHP_EOL;
    // section <head>
    if ($type_session == "with_session")
        echo pageHead2(Settings::get("company"),"with_session");
    else
        echo pageHead2(Settings::get("company"),"no_session");
    // section <body>
    echo "<body>";
    // Debut de la page
    echo '<section>'.PHP_EOL;
    // doit être fermé par la fonction end_page
} 
/* Fonction qui ferme les balises restées ouvertes dans les précédentes 
*/
function end_page()
{
    echo '</section></body></html>';
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

/* fonction clean_input
* pour réduire le risque XSS
*/
function clean_input($data){
    $data = trim($data);
    $data = stripslashes($data);
	$data = htmlspecialchars($data);
    return $data;
}

/* fonction alphanum
* pour réduire les vulnérabilités
*/
function alphanum($data){
	$data = trim($data);
	$data = stripslashes($data);
	$data = htmlentities($data);
	$data = preg_replace('/[^A-Za-z0-9\-_]/', '', $data);

	return $data;
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
        if (($balise_fermante == 'n' ) || ($balise_ouvrante == 'n'))
            $overload_array[$field]["valeur"]='';
        else
            $overload_array[$field]["valeur"]=urldecode($result);
        $overload_array[$field]["id"] = $ofl[$field]["id"];
        $overload_array[$field]["affichage"] = grr_sql_query1("SELECT affichage FROM ".TABLE_PREFIX."_overload WHERE id = '".$ofl[$field]["id"]."'");
        $overload_array[$field]["overload_mail"] = grr_sql_query1("SELECT overload_mail FROM ".TABLE_PREFIX."_overload WHERE id = '".$ofl[$field]["id"]."'");
        $overload_array[$field]["obligatoire"] = grr_sql_query1("SELECT obligatoire FROM ".TABLE_PREFIX."_overload WHERE id = '".$ofl[$field]["id"]."'");
        $overload_array[$field]["confidentiel"] = grr_sql_query1("SELECT confidentiel FROM ".TABLE_PREFIX."_overload WHERE id = '".$ofl[$field]["id"]."'");
    }
    return $overload_array;
}
/* récupère les variables passées par GET ou POST ou bien par COOKIE, et leur affecte le type indiqué (int ou string)
 * rend $default si la valeur recherchée n'est pas référencée
*/
function getFormVar($nom,$type='',$default=NULL){
    $valeur = isset($_GET[$nom])? $_GET[$nom] : (isset($_POST[$nom])? $_POST[$nom] : (isset($_COOKIE['nom'])? $_COOKIE['nom'] : $default));
    if ((isset($valeur)) && ($type !=''))
        settype($valeur,$type);
    return $valeur;
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
?>