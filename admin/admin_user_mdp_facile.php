<?php
/**
 * admin_user_mdp_facile.php
 * interface de gestion des utilisateurs de l'application GRR
 * Dernière modification : $Date: 2024-11-06 11:17$
 * @author    JeromeB & Yan Naessens
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
$grr_script_name = "admin_user_mdp_facile.php";

include "../include/admin.inc.php";

$back = (isset($_SERVER['HTTP_REFERER']))? htmlspecialchars_decode($_SERVER['HTTP_REFERER'], ENT_QUOTES) : "./admin_accueil.php" ;
$display = isset($_GET["display"]) ? $_GET["display"] : 'actifs';
$order_by = isset($_GET["order_by"]) ? $_GET["order_by"] : 'nom,prenom';
$msg = '';
if ((authGetUserLevel(getUserName(), -1) < 6) && (authGetUserLevel(getUserName(), -1,'user') != 1))
{
  showAccessDenied($back);
  exit();
}
// liste de base des mots de passe faciles
$liste_mdp = array("azerty", "", "123456", "1234567", "12345678", "0123456789", "000000", "00000000", "admin","azertyui","azertyuiop","grr","administrateur","administrator");
// parcours des utilisateurs
$data = array(); 
// les utilisateurs à identification externe ont un mot de passe vide dans la base GRR, il est inutile de les analyser
$sql = "SELECT nom, prenom, statut, login, email, etat, source, password FROM ".TABLE_PREFIX."_utilisateurs WHERE source = 'local' ORDER BY $order_by";
$res = grr_sql_query($sql);
if ($res)
{
  foreach($res as $row)
  {
    $user_etat = $row['etat'];
    if (($user_etat == 'actif') && (($display == 'tous') || ($display == 'actifs')))
      $affiche = TRUE;
    else if (($user_etat != 'actif') && (($display == 'tous') || ($display == 'inactifs')))
      $affiche = TRUE;
    else
      $affiche = FALSE;
    if ($affiche){// les calculs étant lourds, on ne les fait que pour les utilisateurs à afficher
      // on ajoute à $mdpFacile : login, login en majuscule, login en minuscule, adresse mail
      $mdpPerso = array();
      $mdpPerso[] = $row['login'];
      $mdpPerso[] = strtoupper($row['login']);
      $mdpPerso[] = strtolower($row['login']);
      $mdpPerso[] = $row['email'];
      $mdpFacile = $liste_mdp + $mdpPerso;
      $test = FALSE;
      foreach($mdpFacile as $mdp){
          if(!$test){
              $test = password_verify($mdp,$row['password']);
          }
          if(!$test){
              $test = (md5($mdp) == $row['password']);
          }
          if($test)
              break;
      }
      if($test){
        $user_nom = htmlspecialchars($row['nom']);
        $user_prenom = htmlspecialchars($row['prenom']);
        $user_statut = $row['statut'];
        $user_login = $row['login'];
        $user_source = $row['source'];
        // Affichage du statut
        if ($user_statut == "administrateur"){
          $color = 'style_admin';
          $user_statut_text = get_vocab("statut_administrator");
        }
        if ($user_statut == "visiteur"){
          $color = 'style_visiteur';
          $user_statut_text = get_vocab("statut_visitor");
        }
        if ($user_statut == "utilisateur"){
          $color = 'style_utilisateur';
          $user_statut_text = get_vocab("statut_user");
        }
        if ($user_statut == "gestionnaire_utilisateur"){
          $color = 'style_gestionnaire_utilisateur';
          $user_statut_text = get_vocab("statut_user_administrator");
        }
        if ($user_etat == 'actif'){
          $fond = 'fond1';
          $user_etat_text = get_vocab('activ_user');
        }
        else{
          $fond = 'fond2';
          $user_etat_text = get_vocab('no_activ_user');
        }
        // un gestionnaire d'utilisateurs ne peut pas modifier un administrateur général ou un gestionnaire d'utilisateurs
        if ((authGetUserLevel(getUserName(), -1, 'user') ==  1) && (($user_statut == "gestionnaire_utilisateur") || ($user_statut == "administrateur")))
          $lien_modifier = '';
        else
          $lien_modifier = "<a href=\"admin_user_modify.php?user_login=".urlencode($user_login)."&amp;display=$display\"><span class='glyphicon glyphicon-edit'></span></a>";
        $data[] = array($user_login,$user_nom." ".$user_prenom,$user_statut_text,$color,$user_etat_text,$fond,$lien_modifier);

      }
    }
  }
}

// code HTML
start_page_w_header("", "", "", $type="with_session");
// colonne de gauche
include "admin_col_gauche2.php";
// colonne de droite
echo "<div class='col-md-9 col-sm-8 col-xs-12'>";
echo "<h2>".get_vocab('admin_user_mdp_facile')."</h2>";
echo '<a href="admin_user.php" type="button" class="btn btn-primary">'.get_vocab("back").'</a>';
echo "<p>".get_vocab('admin_user_mdp_facile_description')."</p>";
// quels utilisateurs afficher ?
echo "<form action=\"admin_user_mdp_facile.php\" method=\"get\">\n";
echo "<div class='row'>\n";
echo "<div class='col col-xs-3'><input type=\"submit\" value=\"".get_vocab('goto').get_vocab('deux_points')."\" /></div>";
echo "<div class='col col-xs-3'><input type=\"radio\" name=\"display\" value=\"tous\"";
if ($display == 'tous')
  echo " checked=\"checked\"";
echo " />".get_vocab("display_all_user.php")."</div>";
echo '<div class="col col-xs-3"><input type="radio" name="display" value="actifs" ';
if ($display == 'actifs') {echo " checked=\"checked\"";}
echo './>'.get_vocab("display_user_on.php").'</div>';
echo '<div class="col col-xs-3"><input type="radio" name="display" value="inactifs" ';
if ($display == 'inactifs') {echo " checked=\"checked\"";}
echo ' />'.get_vocab("display_user_off.php").'</div>';
echo '</div>
<input type="hidden" name="order_by" value="'.$order_by.'" />
</form>';

if(count($data) != 0) // qqch à afficher ?
{
    echo "<table class='table table-striped table-bordered'>";
    echo "<thead>";
    echo "<tr>";
    echo "<th><b><a href='admin_user_mdp_facile.php?order_by=login&amp;display=$display'>".get_vocab("login_name")."</a></b></th>";
    echo "<th><b><a href='admin_user_mdp_facile.php?order_by=nom,prenom&amp;display=$display'>".get_vocab("names")."</a></b></th>";
    echo "<th><b><a href='admin_user_mdp_facile.php?order_by=statut,nom,prenom&amp;display=$display'>".get_vocab("statut")."</a></b></th>";
    echo "<th><b><a href='admin_user_mdp_facile.php?order_by=etat,nom,prenom&amp;display=$display'>".get_vocab('activ_no_activ')."</a></b></th>";
    echo "<th></th>";
    echo "</tr>";
    echo "</thead>";
    echo "<tbody>";
    foreach($data as list($l,$np,$s,$c,$e,$f,$m)){
        echo "<tr>";
        echo "<td>$l</td><td>$np</td><td class='$c'>$s</td><td class='$f'>$e</td><td>$m</td>";
        echo "</tr>";
    }
    echo "</tbody>";
    echo "</table>";
}
// fin de l'affichage de la colonne de droite
echo "</div>";
// Affichage d'un pop-up
affiche_pop_up($msg,"admin");
// fin de la page
end_page();
?>