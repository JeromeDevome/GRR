<?php
/**
 * edit_entry_beneficiaire.php
 * Page "Ajax" utilisée dans edit_entry.php
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2021-02-28 18:53$
 * @author    Laurent Delineau & JeromeB & Yan Naessens
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
$id_area = isset($_GET["area"])? $_GET["area"]: -1;
$id_room = isset($_GET["room"])? $_GET["room"]: -1;
$id_user = isset($_GET["user"])? $_GET["user"]: getUserName();
$id_resa = isset($_GET["id"])? $_GET["id"]: 0;
$qui_peut_reserver_pour  = grr_sql_query1("SELECT qui_peut_reserver_pour FROM ".TABLE_PREFIX."_room WHERE id='".$id_room."'");
$flag_qui_peut_reserver_pour = (authGetUserLevel($id_user, $id_room, "room") >= $qui_peut_reserver_pour); // accès à la ressource
$flag_qui_peut_reserver_pour = $flag_qui_peut_reserver_pour || (authGetUserLevel($id_user, $id_area, "area") >= $qui_peut_reserver_pour); // accès au domaine
$flag_qui_peut_reserver_pour = $flag_qui_peut_reserver_pour && (($id_resa == 0) || (authGetUserLevel($id_user, $id_room) > 2) ); // création d'une nouvelle réservation ou usager 
if ($flag_qui_peut_reserver_pour ) // on crée les sélecteurs à afficher 
{
    $tab_benef = array();
    $tab_benef["nom"] = "";
    $tab_benef["email"] = "";
    $benef = "";
    if ($id_resa == 0 && isset($_COOKIE['beneficiaire_default']))
        $benef = $_COOKIE['beneficiaire_default'];
    elseif ($id_resa != 0) 
        $benef = grr_sql_query1("SELECT beneficiaire FROM ".TABLE_PREFIX."_entry WHERE id='".$id_resa."' ");
    $bnf = array(); // tableau des bénéficiaires autorisés (login,nom,prénom)
    $sql = "SELECT DISTINCT login, nom, prenom FROM ".TABLE_PREFIX."_utilisateurs WHERE (etat!='inactif' and statut!='visiteur' ) OR (login='".$id_user."') ORDER BY nom, prenom";
    $res = grr_sql_query($sql);
    if ($res){
        for ($i = 0; ($row = grr_sql_row($res, $i)); $i++) {$bnf[$i] = $row;}
    }
    $option = "";
    $option .= '<option value="" >'.get_vocab("personne exterieure").'</option>'.PHP_EOL;
    foreach ($bnf as $b){
        $option .= '<option value="'.$b[0].'" ';
        if ((!$benef && strtolower($id_user) == strtolower($b[0])) || ($benef && $benef == $b[0]))
            {
                $option .= ' selected="selected" ';
            }
        $option .= '>'.$b[1].' '.$b[2].'</option>'.PHP_EOL;
    }
    $test = grr_sql_query1("SELECT login FROM ".TABLE_PREFIX."_utilisateurs WHERE login='".$id_user."'");
    if (($test == -1) && ($id_user != ''))
    {
        $option .= '<option value="-1" selected="selected" >'.get_vocab("utilisateur_inconnu").$id_user.')</option>'.PHP_EOL;
    }
    echo '<div id="choix_beneficiaire" class="form-group">'.PHP_EOL;
    echo '<label for="beneficiaire" >'.ucfirst(trim(get_vocab("reservation_au_nom_de"))).get_vocab("deux_points").'</label>'.PHP_EOL;
    echo '<div class="col-sm-9">'.PHP_EOL;
    echo '<select class="select2" name="beneficiaire" id="beneficiaire" data-input onchange="check_4();">'.$option.'</select>'.PHP_EOL;
    echo '</div>';
    echo '<div class="col-sm-3">'.PHP_EOL;
    echo '<input type="button" id="bnfdef" class="btn btn-primary" value="'.get_vocab("definir par defaut").'" onclick="setdefault(\'beneficiaire_default\',document.getElementById(\'main\').beneficiaire.options[document.getElementById(\'main\').beneficiaire.options.selectedIndex].value)" />'.PHP_EOL;
    echo '</div></div>'.PHP_EOL;
    echo '<div id="menu4" class="form-inline">';
    echo '<div class="form-group col-sm-6">'.PHP_EOL;
    echo '    <div class="input-group">'.PHP_EOL;
    echo '      <div class="input-group-addon"><span class="glyphicon glyphicon-user"></span></div>'.PHP_EOL;
    echo '      <input class="form-control" type="text" name="benef_ext_nom" value="'.htmlspecialchars($tab_benef["nom"]).'" placeholder="'.get_vocab("nom beneficiaire").'" required>'.PHP_EOL;
    echo '    </div>'.PHP_EOL;
    echo '  </div>'.PHP_EOL;
    $affiche_mess_asterisque = true;
    if (Settings::get("automatic_mail") == 'yes')
    {
        echo '<div class="form-group col-sm-6">'.PHP_EOL;
        echo '    <div class="input-group">'.PHP_EOL;
        echo '      <div class="input-group-addon"><span class="glyphicon glyphicon-envelope" ></span></div>'.PHP_EOL;
        echo '      <input class="form-control" type="email" name="benef_ext_email" value="'.htmlspecialchars($tab_benef["email"]).'" placeholder="'.get_vocab("email beneficiaire").'">'.PHP_EOL;
        echo '    </div>'.PHP_EOL;
        echo '</div>'.PHP_EOL;
    }
    echo '</div>'.PHP_EOL; // fin menu4
}
else
{
    echo '<input type="hidden" name="beneficiaire" value="'.$id_user.'" />'.PHP_EOL;
}
?>