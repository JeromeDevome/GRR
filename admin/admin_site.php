<?php
/**
 * admin_site.php
 * Interface d'accueil de Gestion des sites de l'application GRR
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2021-03-13 11:38$
 * @author    JeromeB & Laurent Delineau & Marc-Henri PAMISEUX & Yan Naessens
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

$grr_script_name = "admin_site.php";
/**
 * Compte le nombre de sites définis
 *
 * @return integer number of rows
 */
function count_sites()
{
	$sql = "SELECT COUNT(*)
	FROM ".TABLE_PREFIX."_site";
	$res = grr_sql_query($sql);
	if ($res)
	{
		$sites = grr_sql_row($res,0);
		if (is_array($sites))
			return $sites[0];
		else
		{
			echo '      <p>'.get_vocab['admin_site_err1'].'</p>';
			// fin de l'affichage de la colonne de droite
			end_page();
			die();
		}
	}
	else
	{
		echo '      <p>'.get_vocab['admin_site_err2'].'</p>';
		// fin de l'affichage de la colonne de droite
		end_page();
		die();
	}
}
function create_site($id_site)
{
	if ((isset($_POST['back']) || isset($_GET['back'])))
	{
		// On affiche le tableau des sites
		read_sites();
		exit();
	}
	// Initialisation des variables du formulaire
	if (!isset($id_site))
		$id_site = isset($_POST['id']) ? $_POST['id'] :  NULL;
	if (!isset($sitecode))
		$sitecode = isset($_POST['sitecode']) ? $_POST['sitecode'] : NULL;
	if (!isset($sitename))
		$sitename = isset($_POST['sitename']) ? $_POST['sitename'] :  NULL;
	if (!isset($adresse_ligne1))
		$adresse_ligne1 = isset($_POST['adresse_ligne1']) ? $_POST['adresse_ligne1'] :  NULL;
	if (!isset($adresse_ligne2))
		$adresse_ligne2 = isset($_POST['adresse_ligne2']) ? $_POST['adresse_ligne2'] :  NULL;
	if (!isset($adresse_ligne3))
		$adresse_ligne3 = isset($_POST['adresse_ligne3']) ? $_POST['adresse_ligne3'] :  NULL;
	if (!isset($cp))
		$cp = isset($_POST['cp']) ? $_POST['cp'] :  NULL;
	if (!isset($ville))
		$ville = isset($_POST['ville']) ? $_POST['ville'] :  NULL;
	if (!isset($pays))
		$pays = isset($_POST['pays']) ? $_POST['pays'] :  NULL;
	if (!isset($tel))
		$tel = isset($_POST['tel']) ? $_POST['tel'] :  NULL;
	if (!isset($fax))
		$fax = isset($_POST['fax']) ? $_POST['fax'] :  NULL;
	// On affiche le formulaire de saisie quand l'appel de la fonction ne provient pas de la validation de ce même formulaire
	if ((! (isset($_POST['save']) || isset($_GET['save']))) && ($id_site==0))
	{
		// Affichage des titres de la page
		echo '      <h2>'.get_vocab('addsite').'</h2>';
		echo '
		<form action="admin_site.php?action=create" method="post">
			<table>
				<tr><td>'.get_vocab('site_code').' *</td><td><input type="text" name="sitecode" value="'.$sitecode.'" size="10" title="'.get_vocab('site_code').'" /></td></tr>
				<tr><td>'.get_vocab('site_name').' *</td><td><input type="text" name="sitename" value="'.$sitename.'" size="50" title="'.get_vocab('site_name').'" /></td></tr>
				<tr><td>'.get_vocab('site_adresse_ligne1').'</td><td><input type="text" name="adresse_ligne1" value="'.$adresse_ligne1.'" size="38" title="'.get_vocab('site_adresse_ligne1').'" /></td></tr>
				<tr><td>'.get_vocab('site_adresse_ligne2').'</td><td><input type="text" name="adresse_ligne2" value="'.$adresse_ligne2.'" size="38" title="'.get_vocab('site_adresse_ligne2').'" /></td></tr>
				<tr><td>'.get_vocab('site_adresse_ligne3').'</td><td><input type="text" name="adresse_ligne3" value="'.$adresse_ligne3.'" size="38" title="'.get_vocab('site_adresse_ligne3').'" /></td></tr>
				<tr><td>'.get_vocab('site_cp').'</td><td><input type="text" name="cp" value="'.$cp.'" size="5" title="'.get_vocab('site_cp').'" /></td></tr>
				<tr><td>'.get_vocab('site_ville').'</td><td><input type="text" name="ville" value="'.$ville.'" size="50" title="'.get_vocab('site_ville').'" /></td></tr>
				<tr><td>'.get_vocab('site_pays').'</td><td><input type="text" name="pays" value="'.$pays.'" size="50" title="'.get_vocab('site_pays').'" /></td></tr>
				<tr><td>'.get_vocab('site_tel').'</td><td><input type="text" name="tel" value="'.$tel.'" size="25" title="'.get_vocab('site_tel').'" /></td></tr>
				<tr><td>'.get_vocab('site_fax').'</td><td><input type="text" name="fax" value="'.$fax.'" size="25" title="'.get_vocab('site_fax').'" /></td></tr>
			</table>
			<div>
				<input type="hidden" name="valid" value="yes" />
				<input type="submit" name="save" value="'.get_vocab('save').'" />
				<input type="submit" name="back" value="'.get_vocab('back').'" />
			</div>
		</form>';
		echo get_vocab("required");
		// Sinon, il faut valider le formulaire
	}
	else
	{
		// On vérifie que le code et le nom du site ont été renseignés
		if ($sitecode == '' || $sitecode == NULL || $sitename == '' || $sitename == NULL)
		{
			$_POST['save'] = 'no';
			$_GET['save'] = 'no';
			echo '<div class="avertissement">'.get_vocab('required').'</div>';
		}
		// Sauvegarde du record
		if ((isset($_POST['save']) && ($_POST['save'] != 'no')) || ((isset($_GET['save'])) && ($_GET['save'] != 'no')))
		{
			$sql="INSERT INTO ".TABLE_PREFIX."_site
			SET sitecode='".strtoupper(protect_data_sql($sitecode))."',
			sitename='".protect_data_sql($sitename)."',
			adresse_ligne1='".protect_data_sql($adresse_ligne1)."',
			adresse_ligne2='".protect_data_sql($adresse_ligne2)."',
			adresse_ligne3='".protect_data_sql($adresse_ligne3)."',
			cp='".protect_data_sql($cp)."',
			ville='".strtoupper(protect_data_sql($ville))."',
			pays='".strtoupper(protect_data_sql($pays))."',
			tel='".protect_data_sql($tel)."',
			fax='".protect_data_sql($fax)."'";
            if (grr_sql_command($sql) < 0){ // on traite les doublons dans sitecode, sitename
                $err_sql = grr_sql_error();
                if (!strstr($err_sql,"Duplicate entry")){
                    fatal_error(0,'<p>'.grr_sql_error().'</p>');
                }
                else {
                    $_POST['save'] = 'no';
                    $_GET['save'] = 'no';
                    echo '<div class="avertissement">'.get_vocab('duplicate_sitecode_name').'</div>';
                }
            }
            else 
                mysqli_insert_id($GLOBALS['db_c']);
		}
		// On affiche le tableau des sites
		read_sites();
	}
}
function read_sites()
{
	// Affichage des titres de la page
	echo '      <h2>'.get_vocab('admin_site.php').'</h2>';
	echo '      <p>'.get_vocab('admin_site_explications').'</p>
	| <a href="admin_site.php?action=create&amp;id=0">'.get_vocab('display_add_site').'</a> |';
	if (count_sites() > 0)
	{
		$sql = "SELECT id,sitecode,sitename,cp,ville
		FROM ".TABLE_PREFIX."_site
		ORDER BY sitename,ville,id";
		$res = grr_sql_query($sql);
		if ($res)
		{
			// Affichage de l'entête du tableau
			echo '      <table class="table table-bordered table-condensed">
			<tr>
				<th>'.get_vocab('action').'</th>
				<th>'.get_vocab('site_code').'</th>
				<th>'.get_vocab('site_name').'</th>
				<th>'.get_vocab('site_cp').'</th>
				<th>'.get_vocab('site_ville').'</th>
			</tr>';
			for ($i = 0; ($row=grr_sql_row($res,$i));$i++)
			{
				echo '        <tr>
				<td>
					<a href="admin_site.php?action=update&amp;id='.$row[0].'" title='.get_vocab('edit').' ><span class=\'glyphicon glyphicon-edit\'></span></a>
					<a href="admin_site.php?action=delete&amp;id='.$row[0].'" title='.get_vocab('delete').' ><span class=\'glyphicon glyphicon-trash\'></span></a>
					<a href="javascript:centrerpopup(\'../view_rights_site.php?site_id='.$row[0].'\',600,480,\'scrollbars=yes,statusbar=no,resizable=yes\')"><img class="image" title="'.get_vocab('privileges').'" alt="'.get_vocab('privileges').'" src="../img_grr/rights.png" /></a>';
                    echo '         </td>
					<td>'.$row[1].'</td>
					<td>'.$row[2].'</td>
					<td>'.$row[3].'</td>
					<td>'.$row[4].'</td>
				</tr>';
			}
			echo '      </table>';
		}
		else
		{
			echo '      <p>'.get_vocab['admin_site_err3'].'</p>';
		}
		// fin de l'affichage de la colonne de droite et de la page
		echo "</div></section></body></html>";
		die();
	}
}
function update_site($id)
{
	if ((isset($_POST['back']) || isset($_GET['back'])))
	{
		 // On affiche le tableau des sites
		read_sites();
		exit();
	}
	 // On affiche le formulaire de saisie quand l'appel de la fonction ne provient pas de la validation de ce même formulaire
	if (!(isset($_POST['save']) || isset($_GET['save'])))
	{
		 // Initialisation
		$res = grr_sql_query("SELECT * FROM ".TABLE_PREFIX."_site WHERE id='".$id."'");
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
		// Affichage des titres de la page
		echo '      <h2>'.get_vocab('modifier site').'</h2>';
		echo '
		<form action="admin_site.php?action=update" method="post">
			<table>
				<tr><td>'.get_vocab('site_code').' *</td><td><input type="text" name="sitecode" value="'.$sitecode.'" size="10" title="'.get_vocab('site_code').'" /></td></tr>
				<tr><td>'.get_vocab('site_name').' *</td><td><input type="text" name="sitename" value="'.$sitename.'" size="50" title="'.get_vocab('site_name').'" /></td></tr>
				<tr><td>'.get_vocab('site_adresse_ligne1').'</td><td><input type="text" name="adresse_ligne1" value="'.$adresse_ligne1.'" size="38" title="'.get_vocab('site_adresse_ligne1').'" /></td></tr>
				<tr><td>'.get_vocab('site_adresse_ligne2').'</td><td><input type="text" name="adresse_ligne2" value="'.$adresse_ligne2.'" size="38" title="'.get_vocab('site_adresse_ligne2').'" /></td></tr>
				<tr><td>'.get_vocab('site_adresse_ligne3').'</td><td><input type="text" name="adresse_ligne3" value="'.$adresse_ligne3.'" size="38" title="'.get_vocab('site_adresse_ligne3').'" /></td></tr>
				<tr><td>'.get_vocab('site_cp').'</td><td><input type="text" name="cp" value="'.$cp.'" size="5" title="'.get_vocab('site_cp').'" /></td></tr>
				<tr><td>'.get_vocab('site_ville').'</td><td><input type="text" name="ville" value="'.$ville.'" size="50" title="'.get_vocab('site_ville').'" /></td></tr>
				<tr><td>'.get_vocab('site_pays').'</td><td><input type="text" name="pays" value="'.$pays.'" size="50" title="'.get_vocab('site_pays').'" /></td></tr>
				<tr><td>'.get_vocab('site_tel').'</td><td><input type="text" name="tel" value="'.$tel.'" size="25" title="'.get_vocab('site_tel').'" /></td></tr>
				<tr><td>'.get_vocab('site_fax').'</td><td><input type="text" name="fax" value="'.$fax.'" size="25" title="'.get_vocab('site_fax').'" /></td></tr>
			</table>
			<div>
				<input type="hidden" name="valid" value="yes" />
				<input type="hidden" name="id" value="'.$id.'" />
				<input type="submit" name="save" value="'.get_vocab('save').'" />
				<input type="submit" name="back" value="'.get_vocab('back').'" /></div>
			</form>';
			echo get_vocab("required");
		}
    else // Sinon, il faut valider le formulaire
    {
        if (!isset($id))
            $id = isset($_POST['id']) ? $_POST['id'] :  NULL;
        if (!isset($sitecode))
            $sitecode = isset($_POST['sitecode']) ? $_POST['sitecode'] : NULL;
        if (!isset($sitename))
            $sitename = isset($_POST['sitename']) ? $_POST['sitename'] :  NULL;
        if (!isset($adresse_ligne1))
            $adresse_ligne1 = isset($_POST['adresse_ligne1']) ? $_POST['adresse_ligne1'] :  NULL;
        if (!isset($adresse_ligne2))
            $adresse_ligne2 = isset($_POST['adresse_ligne2']) ? $_POST['adresse_ligne2'] :  NULL;
        if (!isset($adresse_ligne3))
            $adresse_ligne3 = isset($_POST['adresse_ligne3']) ? $_POST['adresse_ligne3'] :  NULL;
        if (!isset($cp))
            $cp = isset($_POST['cp']) ? $_POST['cp'] :  NULL;
        if (!isset($ville))
            $ville = isset($_POST['ville']) ? $_POST['ville'] :  NULL;
        if (!isset($pays))
            $pays = isset($_POST['pays']) ? $_POST['pays'] :  NULL;
        if (!isset($tel))
            $tel = isset($_POST['tel']) ? $_POST['tel'] :  NULL;
        if (!isset($fax))
            $fax = isset($_POST['fax']) ? $_POST['fax'] :  NULL;
        // On vérifie que le code et le nom du site ont été renseignés
        if ($sitecode == '' || $sitecode == NULL || $sitename == '' || $sitename==NULL)
        {
            $_POST['save'] = 'no';
            $_GET['save'] = 'no';
            echo '<div class="avertissement">'.get_vocab('required').'</div>';
        }
        // Sauvegarde du record
        if ((isset($_POST['save']) && ($_POST['save']!='no')) || ((isset($_GET['save'])) && ($_GET['save']!='no')))
        {
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
            WHERE id='".$id."'";
            if (grr_sql_command($sql) < 0){ // on traite les doublons dans sitecode, sitename
                $err_sql = grr_sql_error();
                if (!strstr($err_sql,"Duplicate entry")){
                    fatal_error(0,'<p>'.grr_sql_error().'</p>');
                }
                else {
                    $_POST['save'] = 'no';
                    $_GET['save'] = 'no';
                    echo '<div class="avertissement">'.get_vocab('duplicate_sitecode_name').'</div>';
                }
            }
            else 
                mysqli_insert_id($GLOBALS['db_c']);
        }
        // On affiche le tableau des sites
        read_sites();
    }
}
function delete_site($id)
{
    if (!(isset($_GET['confirm'])))
    {
        echo '<h2>'.get_vocab('supprimer site').'</h2>';
        echo '<h2 style="text-align:center;">' .  get_vocab('sure') . '</h2>';
        echo '<h2 style="text-align:center;"><a href="admin_site.php?action=delete&amp;id='.$id.'&amp;confirm=yes">' . get_vocab('YES') . '!</a>     <a href="admin_site.php?action=delete&amp;id='.$id.'&amp;confirm=no">' . get_vocab('NO') . '!</a></h2>';
    }
    else
    {
        if ($_GET['confirm'] == 'yes')
        {
            grr_sql_command("delete from ".TABLE_PREFIX."_site where id='".$_GET['id']."'");
            grr_sql_command("delete from ".TABLE_PREFIX."_j_site_area where id_site='".$_GET['id']."'");
            grr_sql_command("delete from ".TABLE_PREFIX."_j_useradmin_site where id_site='".$_GET['id']."'");
            grr_sql_command("update ".TABLE_PREFIX."_utilisateurs set default_site = '-1' where default_site='".$_GET['id']."'");
            $test = grr_sql_query1("select VALUE from ".TABLE_PREFIX."_setting where NAME='default_site'");
            if ($test == $_GET['id'])
                grr_sql_command("delete from ".TABLE_PREFIX."_setting where NAME='default_site'");
            // On affiche le tableau des sites
            read_sites();
        }
        else
        {
            // On affiche le tableau des sites
            read_sites();
        }
    }
}
function check_right($id)
{
    echo get_vocab['check_right'].$id;
}

include_once('../include/admin.inc.php');

$back = (isset($_SERVER['HTTP_REFERER']))? htmlspecialchars_decode($_SERVER['HTTP_REFERER'], ENT_QUOTES) : "./admin_accueil.php" ;
if (authGetUserLevel(getUserName(), -1, 'site') < 4){
    showAccessDenied($back);
    exit();
}
// print the page header
start_page_w_header("", "", "", $type="with_session");
// Affichage de la colonne de gauche
include_once('admin_col_gauche2.php');
// colonne de droite
echo "<div class='col-md-9 col-sm-8 col-xs-12'>";
if ((isset($_GET['msg'])) && isset($_SESSION['displ_msg']) && ($_SESSION['displ_msg'] == 'yes') )
{
    $msg = $_GET['msg'];
    affiche_pop_up($msg,'admin');
}
else
    $msg = '';
// Lecture des paramètres passés à la page
$id_site = isset($_POST['id']) ? $_POST['id'] : (isset($_GET['id']) ? $_GET['id'] : NULL);
if (isset($id_site))
    $id_site = intval($id_site);
$action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : NULL);
if ($action == NULL)
    $action = 'read';
// SWITCH sur l'action (CRUD)
switch($action)
{
    case 'create':
    create_site($id_site);
    break;
    case 'read':
    read_sites();
    break;
    case 'update':
    update_site($id_site);
    break;
    case 'delete':
    delete_site($id_site);
    break;
    case 'right':
    check_right($id_site);
    break;
    default:
    read_sites();
    break;
}
// fin de l'affichage de la colonne de droite
echo "</div>\n";
end_page();
?>