<?php
/**
 * edit_entry_beneficiaires.php
 * Page "Ajax" utilisée dans edit_entry.php, calcule les data pour le sélecteur #beneficiaire
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2021-04-11 19:20$
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
//print_r($_GET);die();
$id_area = isset($_GET["area"])? $_GET["area"]: -1;
$id_room = isset($_GET["room"])? $_GET["room"]: -1;
$id_user = isset($_GET["user"])? $_GET["user"]: getUserName();
$id_resa = isset($_GET["id"])? $_GET["id"]: 0;
// le test suivant est redondant, peut-on s'en passer sans vérifier que l'appel vient par AJAX ?
$qui_peut_reserver_pour  = grr_sql_query1("SELECT qui_peut_reserver_pour FROM ".TABLE_PREFIX."_room WHERE id='".$id_room."'");
$flag_qui_peut_reserver_pour = (authGetUserLevel($id_user, $id_room, "room") >= $qui_peut_reserver_pour); // accès à la ressource
$flag_qui_peut_reserver_pour = $flag_qui_peut_reserver_pour || (authGetUserLevel($id_user, $id_area, "area") >= $qui_peut_reserver_pour); // accès au domaine
$flag_qui_peut_reserver_pour = $flag_qui_peut_reserver_pour && (($id_resa == 0) || (authGetUserLevel($id_user, $id_room) > 2) ); // création d'une nouvelle réservation ou usager 
$bnf = array(); // tableau des bénéficiaires autorisés (id -> login, text -> nom prénom)
if ($flag_qui_peut_reserver_pour ) // on crée la liste des options pour le sélecteur #beneficiaire 
{
    $benef = "";
    if ($id_resa == 0 && isset($_COOKIE['beneficiaire_default']))
        $benef = $_COOKIE['beneficiaire_default'];
    elseif ($id_resa != 0) 
        $benef = grr_sql_query1("SELECT beneficiaire FROM ".TABLE_PREFIX."_entry WHERE id='".$id_resa."' ");
    if ($benef == -1){
        $benef_ext = grr_sql_query1("SELECT beneficiaire_ext FROM ".TABLE_PREFIX."_entry WHERE id='".$id_resa."' ");
        $tab_benef = explode('|',$benef_ext);
        $benef_ext_nom = $tab_benef[0];
        $benef_ext_email = (isset($tab_benef[1]))? $tab_benef[1]:"";
    }
    if (!isset($benef_ext_nom))
        $bnf[] = array('id'=>"",'text'=>get_vocab("personne exterieure"));
    else
        $bnf[] = array('id'=>"",'text'=>get_vocab("personne exterieure"),'selected'=>TRUE);
    $sql = "SELECT DISTINCT login, nom, prenom FROM ".TABLE_PREFIX."_utilisateurs WHERE (etat!='inactif' and statut!='visiteur' ) OR (login='".$id_user."') ORDER BY nom, prenom";
    $res = grr_sql_query($sql);
    if ($res){
        foreach ($res as $row){
            if ((!$benef && strtolower($id_user) == strtolower($row['login'])) || ($benef && $benef == $row['login'])){
                $bnf[] = array('id'=>$row['login'],'text'=>$row['nom'].' '.$row['prenom'],"selected"=>TRUE);
            }
            else $bnf[] = array('id'=>$row['login'],'text'=>$row['nom'].' '.$row['prenom']);
        }
    }
}
else
{
    //echo '<input type="text" name="beneficiaire" value="'.$id_user.'" disabled />'.PHP_EOL;
    $sql = "SELECT nom, prenom FROM ".TABLE_PREFIX."_utilisateurs WHERE login='".$id_user."' ";
    $res = grr_sql_query($sql);
    if ($res){
        $row = grr_sql_row($res,0);
        $benef = $row[0]." ".$row[1];
    }
    $benef = ($benef != " ")? $benef : get_vocab('utilisateur_inconnu').$id_user.')';
    $bnf[] = array('id'=>$id_user,'text'=>$benef,'disabled'=>TRUE,'selected'=>TRUE);
}
$json = json_encode($bnf);
echo $json;
?>