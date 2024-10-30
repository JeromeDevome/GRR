<?php
/**
 * admin_site.php
 * Interface d'accueil de Gestion des sites de l'application GRR
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2024-10-30 11:42$
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

$grr_script_name = "admin_site.php";

include_once('../include/admin.inc.php');

$back = (isset($_SERVER['HTTP_REFERER']))? htmlspecialchars_decode($_SERVER['HTTP_REFERER'], ENT_QUOTES) : "./admin_accueil.php" ;
if (authGetUserLevel(getUserName(), -1, 'site') < 4){
    showAccessDenied($back);
    exit();
}
// Lecture des paramètres passés à la page
$id_site = isset($_POST['id']) ? $_POST['id'] : (isset($_GET['id']) ? $_GET['id'] : NULL);
if (isset($id_site))
    $id_site = intval($id_site);
$action = clean_input(getFormVar('action','string','read'));

$sitecode = getFormVar('sitecode','string');
$sitename = getFormVar('sitename','string');
$adresse_ligne1 = getFormVar('adresse_ligne1','string');
$adresse_ligne2 = getFormVar('adresse_ligne2','string');
$adresse_ligne3 = getFormVar('adresse_ligne3','string');
$cp = getFormVar('cp','string');
$ville = getFormVar('ville','string');
$pays = getFormVar('pays','string');
$tel = getFormVar('tel','string');
$fax = getFormVar('fax','string');
$confirm = getFormVar('confirm','int');
$save = getFormVar('save','string','0');

if ((isset($_GET['msg'])) && isset($_SESSION['displ_msg']) && ($_SESSION['displ_msg'] == 'yes') )
{
    $msg = $_GET['msg'];
}
else
    $msg = '';

// traitement des données
if($action == "create"){
  $_SESSION['displ_msg'] = 'yes';
  // On vérifie que le code et le nom du site ont été renseignés
  if ($sitecode == '' || $sitecode == NULL || $sitename == '' || $sitename == NULL){
    $msg = get_vocab('required');
    $save = '0';
  }
  elseif($save != '0'){ // enregistrement des données
    /* $sql="INSERT INTO ".TABLE_PREFIX."_site (sitecode,sitename,adresse_ligne1,adresse_ligne2,adresse_ligne3,cp,ville,pays,tel,fax) VALUES (?,?,?,?,?,?,?,?,?,?)";
    $types = "ssssssssss";
    $params = [strtoupper(protect_data_sql($sitecode)),protect_data_sql($sitename),protect_data_sql($adresse_ligne1),protect_data_sql($adresse_ligne2),
    protect_data_sql($adresse_ligne3),protect_data_sql($cp),strtoupper(protect_data_sql($ville)),strtoupper(protect_data_sql($pays)),
    protect_data_sql($tel),protect_data_sql($fax)]; */
    $sql="INSERT INTO ".TABLE_PREFIX."_site (sitecode,sitename,adresse_ligne1,adresse_ligne2,adresse_ligne3,cp,ville,pays,tel,fax) 
    VALUES ('".strtoupper(protect_data_sql($sitecode))."','".protect_data_sql($sitename)."','".protect_data_sql($adresse_ligne1)."','".protect_data_sql($adresse_ligne2)."','".protect_data_sql($adresse_ligne3)."','".protect_data_sql($cp)."','".strtoupper(protect_data_sql($ville))."','".strtoupper(protect_data_sql($pays))."','".protect_data_sql($tel)."','".protect_data_sql($fax)."')";
    //if (grr_sql_command($sql,$types,$params) < 0){ // on traite les doublons dans sitecode, sitename
    if (grr_sql_command($sql) < 0){
      $err_sql = grr_sql_error();
      if (!strstr($err_sql,"Duplicate entry")){
          fatal_error(0,$sql.'<p>'.grr_sql_error().'</p>');
      }
      else {
        $save = '0';
        $msg = get_vocab('duplicate_sitecode_name');
        $action = 'read';
      }
    }
    else{ // succès : affiche le tableau des sites
      $msg = get_vocab("message_records");
      $action = 'read';
    }
  }
}
elseif($action == 'update'){
  if($save != '0'){ // valider le formulaire
  $_SESSION['displ_msg'] = 'yes';
  // On vérifie que le code et le nom du site ont été renseignés
  if ($sitecode == '' || $sitecode == NULL || $sitename == '' || $sitename == NULL){
    $msg = get_vocab('required');
    $save = '0';
  }
  elseif($save != '0'){ // enregistrement des données
    $sql = "UPDATE ".TABLE_PREFIX."_site
            SET sitecode='".strtoupper(protect_data_sql($sitecode))."',
            sitename='".protect_data_sql($sitename)."',
            adresse_ligne1='".protect_data_sql($adresse_ligne1)."',
            adresse_ligne2='".protect_data_sql($adresse_ligne2)."',
            adresse_ligne3='".protect_data_sql($adresse_ligne3)."',
            cp='".protect_data_sql($cp)."',
            ville='".strtoupper(protect_data_sql($ville))."',
            pays='".strtoupper(protect_data_sql($pays))."',
            tel='".protect_data_sql($tel)."',
            fax='".protect_data_sql($fax)."'
            WHERE id='".$id_site."'";
    if (grr_sql_command($sql) < 0){
      $err_sql = grr_sql_error();
      if (!strstr($err_sql,"Duplicate entry")){
          fatal_error(0,$sql.'<p>'.grr_sql_error().'</p>');
      }
      else {
        $save = '0';
        $msg = get_vocab('duplicate_sitecode_name');
        $action = 'read';
      }
    }
    else{ // succès : affiche le tableau des sites
      $msg = get_vocab("message_records");
      $action = 'read';
    }
  }
  }
  else{ // récupérer les valeurs
    $res = grr_sql_query("SELECT * FROM ".TABLE_PREFIX."_site WHERE id=?","i",[$id_site]);
		if (!$res)
			fatal_error(0,'<p>'.grr_sql_error().'</p>');
		$row = grr_sql_row_keyed($res, 0);
		grr_sql_free($res);
		$sitecode = $row['sitecode'];
		$sitename = $row['sitename'];
		$adresse_ligne1 = $row['adresse_ligne1'];
		$adresse_ligne2 = $row['adresse_ligne2'];
		$adresse_ligne3 = $row['adresse_ligne3'];
		$cp = $row['cp'];
		$ville = $row['ville'];
		$pays = $row['pays'];
		$tel = $row['tel'];
		$fax = $row['fax'];
  }
}
elseif($action == "delete"){
  if((isset($_GET['confirm']))&&($confirm == 1)){// on peut effacer
    grr_sql_command("delete from ".TABLE_PREFIX."_site where id=?","i",[$id_site]);
    grr_sql_command("delete from ".TABLE_PREFIX."_j_site_area where id_site=?","i",[$id_site]);
    grr_sql_command("delete from ".TABLE_PREFIX."_j_useradmin_site where id_site=?","i",[$id_site]);
    grr_sql_command("update ".TABLE_PREFIX."_utilisateurs set default_site = '-1' where default_site=?","i",[$id_site]);
    $test = grr_sql_query1("select VALUE from ".TABLE_PREFIX."_setting where NAME='default_site'");
    if ($test == $id_site)
      grr_sql_command("delete from ".TABLE_PREFIX."_setting where NAME='default_site'");
    $action = 'read';
  }
  elseif((isset($_GET['confirm']))&&($confirm == 0)){ // renvoie à la liste des sites
    header("Location: ./admin_site.php");
  }
}

// lecture de la table des sites
$Sites = array();
$sql = "SELECT * FROM ".TABLE_PREFIX."_site ORDER BY sitename,ville,id";
$res = grr_sql_query($sql);
if($res){
  foreach($res as $row){
    $Sites[] = $row;
  }
}
else{
  $_SESSION['displ_msg'] = 'yes';
  $msg = get_vocab('admin_site_err3');
}
$nb_sites = count($Sites);

// print the page header
start_page_w_header("", "", "", $type="with_session");
affiche_pop_up($msg,'admin');
// Affichage de la colonne de gauche
include_once('admin_col_gauche2.php');
// colonne de droite
echo "<div class='col-md-9 col-sm-8 col-xs-12'>";
if($action == 'read'){ // afficher la table des sites 
  echo '<h2>'.get_vocab('admin_site.php').'</h2>';
	echo '<p>'.get_vocab('admin_site_explications').'</p>';
	echo '<a class="btn btn-success" href="admin_site.php?action=create&amp;id=0">'.get_vocab('display_add_site').'</a>';
  if($nb_sites > 0){ // table des sites
    echo '<table class="table table-bordered table-condensed">';
		echo '<tr>
				<th>'.get_vocab('action').'</th>
				<th>'.get_vocab('site_code').'</th>
				<th>'.get_vocab('site_name').'</th>
				<th>'.get_vocab('site_cp').'</th>
				<th>'.get_vocab('site_ville').'</th>
			</tr>';
    foreach($Sites as $s)
			{
				echo '<tr>
				<td>
					<a href="admin_site.php?action=update&amp;id='.$s['id'].'" title='.get_vocab('edit').' ><span class=\'glyphicon glyphicon-edit\'></span></a>
					<a href="admin_site.php?action=delete&amp;id='.$s['id'].'" title='.get_vocab('delete').' ><span class=\'glyphicon glyphicon-trash\'></span></a>
					<a href="javascript:centrerpopup(\'../view_rights_site.php?site_id='.$s['id'].'\',600,480,\'scrollbars=yes,statusbar=no,resizable=yes\')"><img class="image" title="'.get_vocab('privileges').'" alt="'.get_vocab('privileges').'" src="../img_grr/rights.png" /></a>
        </td>
        <td>'.$s['sitecode'].'</td>
        <td>'.$s['sitename'].'</td>
        <td>'.$s['cp'].'</td>
        <td>'.$s['ville'].'</td>
				</tr>';
			}
    echo '</table>';
  }
}
elseif(($action == 'create')||($action == 'update')){ // afficher le formulaire
  // Affichage des titres de la page
  if($action == 'create')
		echo '<h2>'.get_vocab('addsite').'</h2>';
  elseif($action == 'update')
    echo '<h2>'.get_vocab('modifier site').'</h2>';
  else
    showAccessDenied($back);
  // le formulaire
  echo '<form action="admin_site.php" method="post">';
  echo '<table>';
  echo '<tr><td>'.get_vocab('site_code').' *</td><td><input type="text" name="sitecode" value="'.$sitecode.'" size="10" title="'.get_vocab('site_code').'" required /></td></tr>';
  echo '<tr><td>'.get_vocab('site_name').' *</td><td><input type="text" name="sitename" value="'.$sitename.'" size="50" title="'.get_vocab('site_name').'" required /></td></tr>';
  echo '<tr><td>'.get_vocab('site_adresse_ligne1').'</td><td><input type="text" name="adresse_ligne1" value="'.$adresse_ligne1.'" size="38" title="'.get_vocab('site_adresse_ligne1').'" /></td></tr>';
  echo '<tr><td>'.get_vocab('site_adresse_ligne2').'</td><td><input type="text" name="adresse_ligne2" value="'.$adresse_ligne2.'" size="38" title="'.get_vocab('site_adresse_ligne2').'" /></td></tr>';
  echo '<tr><td>'.get_vocab('site_adresse_ligne3').'</td><td><input type="text" name="adresse_ligne3" value="'.$adresse_ligne3.'" size="38" title="'.get_vocab('site_adresse_ligne3').'" /></td></tr>';
  echo '<tr><td>'.get_vocab('site_cp').'</td><td><input type="text" name="cp" value="'.$cp.'" size="5" title="'.get_vocab('site_cp').'" /></td></tr>';
  echo '<tr><td>'.get_vocab('site_ville').'</td><td><input type="text" name="ville" value="'.$ville.'" size="50" title="'.get_vocab('site_ville').'" /></td></tr>';
  echo '<tr><td>'.get_vocab('site_pays').'</td><td><input type="text" name="pays" value="'.$pays.'" size="50" title="'.get_vocab('site_pays').'" /></td></tr>';
  echo '<tr><td>'.get_vocab('site_tel').'</td><td><input type="text" name="tel" value="'.$tel.'" size="25" title="'.get_vocab('site_tel').'" /></td></tr>';
  echo '<tr><td>'.get_vocab('site_fax').'</td><td><input type="text" name="fax" value="'.$fax.'" size="25" title="'.get_vocab('site_fax').'" /></td></tr>';
  echo '</table>';
  echo '<div>';
  echo '<input type="hidden" name="action" value="'.$action.'" />';
	if($action == 'update')
    echo '<input type="hidden" name="id" value="'.$id_site.'" />';
  echo '<input class="btn btn-success" type="submit" name="save" value="'.get_vocab('save').'" />';
  echo '<a class="btn btn-default" href="./admin_site.php" >'.get_vocab('back').'</a>';
  echo '</div></form>';
  echo get_vocab("required");
}
elseif(($action == 'delete')&& !$confirm){ // confirmer la suppression
  echo '<div class="center">';
  echo '<h2>'.get_vocab('supprimer_site').'</h2>';
  echo '<h2>'.get_vocab('sure').'</h2>';
  echo '<p><a class="btn btn-danger" href="admin_site.php?action=delete&amp;id='.$id_site.'&amp;confirm=1">'.get_vocab('YES').'!</a>';
  echo '<a class="btn btn-default" href="admin_site.php?action=delete&amp;id='.$id_site.'&amp;confirm=0">' . get_vocab('NO').'!</a></p>';
  echo '</div>';
}
// fin de l'affichage de la colonne de droite
echo "</div>\n";
end_page();
?>