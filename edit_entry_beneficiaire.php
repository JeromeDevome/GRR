<?php
/**
 * edit_entry_beneficiaire.php
 * Page "Ajax" utilisée dans editentree
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2024-01-30 18:22$
 * @author    Laurent Delineau & JeromeB & Yan Naessens
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
include_once "include/admin.inc.php";
// vérifications de sécurité : page accessible si utilisateur connecté et usager
if ((authGetUserLevel(getUserName(),-1) < 2))
{
	showAccessDenied("");
	exit();
}
/* Initialisation
paramètres attendus, passés par la méthode GET :
 area : le domaine
 room : la ressource
 user : l'utilisateur connecté
 id : l'identifiant de la réservation (pour modification)
*/
$tab_benefext = array();
$tab_benef = array();
$tab_benef["nom"] = "";
$tab_benef["email"] = "";
$area = isset($_GET["area"])? intval($_GET["area"]): -1;
$room = isset($_GET["room"])? intval($_GET["room"]): -1;
$user = isset($_GET["user"])? protect_data_sql($_GET["user"]): getUserName();
$id = isset($_GET["id"])? intval($_GET["id"]): 0;
$qui_peut_reserver_pour  = grr_sql_query1("SELECT qui_peut_reserver_pour FROM ".TABLE_PREFIX."_room WHERE id='".$room."'");
$flag_qui_peut_reserver_pour = (authGetUserLevel($user, $room, "room") >= $qui_peut_reserver_pour); // accès à la ressource
$flag_qui_peut_reserver_pour = $flag_qui_peut_reserver_pour || (authGetUserLevel($user, $area, "area") >= $qui_peut_reserver_pour); // accès au domaine
$flag_qui_peut_reserver_pour = $flag_qui_peut_reserver_pour && (($id == 0) || (authGetUserLevel($user, $room) > 2) ); // création d'une nouvelle réservation ou usager 
if ($flag_qui_peut_reserver_pour ) // on crée les sélecteurs à afficher 
{
	$benef = "";
    if ($id == 0 && isset($_COOKIE['beneficiaire_default']))
	{
		$benef = $_COOKIE['beneficiaire_default'];
	}
	elseif ( $id != 0 )
	{  
		$benefs = grr_sql_query( "SELECT beneficiaire, beneficiaire_ext FROM ".TABLE_PREFIX."_entry WHERE id=$id" );  
		$benefs = grr_sql_row( $benefs, 0 );  
		grr_sql_free( $benefs );  
		if ( $benefs[0] != '' )  
			$benef = $benefs[0];  
		if ( $benefs[1] != '' )
		{
			$tab_benefext = explode('|',$benefs[1]);
			$tab_benef['nom'] = $tab_benefext[0];
			$tab_benef['email'] = $tab_benefext[1];

			$benef = $tab_benef['nom'];
		}
	}
	echo '<tr>'.PHP_EOL;
	echo '<td class="E">'.PHP_EOL;
	echo '<b>'.ucfirst(trim(get_vocab("reservation_au_nom_de"))).get_vocab("deux_points").'</b>'.PHP_EOL;
	echo '</td>'.PHP_EOL;
	echo '</tr>'.PHP_EOL;
	echo '<tr>'.PHP_EOL;
	echo '<td class="CL">'.PHP_EOL;
    //echo "domaine".$area." ressource".$room." utilisateur".$user." droits requis".$qui_peut_reserver_pour;
    //echo "Choix du bénéficiaire";
	echo '<select size="1" class="form-control" name="beneficiaire" id="beneficiaire" onchange="setdefault(\'beneficiaire_default\',\'\');check_4();">'.PHP_EOL;
	echo '<option value="" >'.get_vocab("personne_exterieure").'</option>'.PHP_EOL;
	$sql = "SELECT DISTINCT login, nom, prenom FROM ".TABLE_PREFIX."_utilisateurs WHERE (etat!='inactif') OR (login='".$user."') ORDER BY nom, prenom";
	$res = grr_sql_query($sql);
	if ($res)
	{
		for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
		{
			echo '<option value="'.$row[0].'" ';
			if ((!$benef && strtolower($user) == strtolower($row[0])) || ($benef && $benef == $row[0]))
			{
				echo ' selected="selected" ';
			}
			echo '>'.$row[1].' '.$row[2].'</option>'.PHP_EOL;
		}
	}
	$test = grr_sql_query1("SELECT login FROM ".TABLE_PREFIX."_utilisateurs WHERE login='".$user."'");
	if (($test == -1) && ($user != ''))
	{
		echo '<option value="-1" selected="selected" >'.get_vocab("utilisateur_inconnu").$user.')</option>'.PHP_EOL;
}
echo '</select>'.PHP_EOL;

echo '<input type="button" class="btn btn-primary" value="'.get_vocab("definir_par_defaut").'" onclick="setdefault(\'beneficiaire_default\',document.getElementById(\'main\').beneficiaire.options[document.getElementById(\'main\').beneficiaire.options.selectedIndex].value)" />'.PHP_EOL;
//echo '<div id="div_profilBeneficiaire">'.PHP_EOL;
//echo '</div>'.PHP_EOL;
/*if (isset($statut_beneficiaire))
	echo $statut_beneficiaire; */
// partie non reprise : utile ?
echo '</td></tr>'.PHP_EOL;
if ($tab_benef["nom"] != "")
	echo '<tr id="menu4"><td>'.PHP_EOL;
else
	echo '<tr style="display:none" id="menu4"><td>'.PHP_EOL;
echo '<div class="form-group">'.PHP_EOL;
echo '    <div class="input-group">'.PHP_EOL;
echo '      <div class="input-group-addon"><i class="fa-regular fa-user"></i></div>'.PHP_EOL;
echo '      <input class="form-control" type="text" name="benef_ext_nom" value="'.htmlspecialchars($tab_benef["nom"]).'" placeholder="'.get_vocab("nom_beneficiaire").'">'.PHP_EOL;
echo '    </div>'.PHP_EOL;
echo '  </div>'.PHP_EOL;
$affiche_mess_asterisque = true;
if (Settings::get("automatic_mail") == 'yes')
{
	echo '<div class="form-group">'.PHP_EOL;
	echo '    <div class="input-group">'.PHP_EOL;
	echo '      <div class="input-group-addon"><i class="fa-solid fa-envelope"></i></div>'.PHP_EOL;
	echo '      <input class="form-control" type="email" name="benef_ext_email" value="'.htmlspecialchars($tab_benef["email"]).'" placeholder="'.get_vocab("email_beneficiaire").'">'.PHP_EOL;
	echo '    </div>'.PHP_EOL;
	echo '  </div>'.PHP_EOL;
}
echo "</td></tr>\n";
}
else
{
    echo '<input type="hidden" name="beneficiaire" value="'.$user.'" />'.PHP_EOL;
}
?>
