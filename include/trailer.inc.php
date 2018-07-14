<?php
/**
 * trailer.inc.php
 * script de bas de page html
 * Dernière modification : $Date: 2018-07-14 17:00$
 * @author    JeromeB & Laurent Delineau & Yan Naessens
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

// Affichage d'un lien pour format imprimable
//Appel d'une methode en fonction du paramétrage pour le lien imprimable
if ((!isset($_GET['pview']) || ($_GET['pview'] != 1)) && (isset($affiche_pview) && Settings::get("imprimante") == '0'))
{
	if (Settings::get("pview_new_windows") == 1)
	{
		$s = "<button type=\"button\" class=\"btn btn-default btn-xs\" onclick=\"";
		$s .= "javascript:window.open(";
		$s .= "'".traite_grr_url($grr_script_name)."?";
		if (isset($_SERVER['QUERY_STRING']) && ($_SERVER['QUERY_STRING'] != ''))
			$s .= htmlspecialchars($_SERVER['QUERY_STRING']) . "&amp;";
		$s .= "pview=1')\"";
	}
	else
	{
		$s = "<button type=\"button\" class=\"btn btn-default btn-xs\" onclick=\"charger();";
		$s .= "   javascript:location.href=";
		$s .= "'".traite_grr_url($grr_script_name)."?";
		if (isset($_SERVER['QUERY_STRING']) && ($_SERVER['QUERY_STRING'] != ''))
			$s .= htmlspecialchars($_SERVER['QUERY_STRING']) . "&amp;";
		$s .= "pview=1&amp;precedent=1'\"";
	}
	$s.= "><span class=\"glyphicon glyphicon-print\"></span></button>";
	echo $s;
}
// Affichage du message d'erreur en cas d'échec de l'envoi de mails automatiques
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
?>
