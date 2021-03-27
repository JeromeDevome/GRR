<?php
/**
 * my_account.php
 * Interface permettant à l'utilisateur de gérer son compte dans l'application GRR
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2021-02-23 09:15$
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
$grr_script_name = 'my_account.php';

include_once('include/connect.inc.php');
include_once('include/config.inc.php');
include_once('include/misc.inc.php');
include_once('include/functions.inc.php');
require_once('include/'.$dbsys.'.inc.php');
require_once('include/session.inc.php');
include_once('include/settings.class.php');

if (!Settings::load())
	die('Erreur chargement settings');
$desactive_VerifNomPrenomUser='y';
if (!grr_resumeSession())
{
	header('Location: logout.php?auto=1&url='.$grr_script_name);
	die();
};
include_once('include/language.inc.php');
$msg='';
if (Settings::get("module_multisite") == "Oui")
	$use_site = 'y';
else
	$use_site = 'n';
$menus = array('moi','param','pwd','conn','resa');
$pill = array();
foreach ($menus as $menu){
    $pill[$menu] = '<li><a data-toggle="pill" href="#menu_'.$menu.'">'.get_vocab($menu).'</a></li>';
}
$divs = array();
foreach ($menus as $menu){
    $divs[$menu] = '<div id="menu_'.$menu.'" class="tab-pane fade">';
}
// valeurs par défaut pour le reset
$reset_site = Settings::get('default_site');
$reset_area = Settings::get('default_area');
$reset_room = Settings::get('default_room');
// paramètres récupérés
// print_r($_POST);
$valid = isset($_POST['valid']) ? $_POST['valid'] : NULL;
// activation des pills et tabs
if (in_array($valid,$menus))
{   $pill[$valid] = '<li class="active"><a data-toggle="pill" href="#menu_'.$valid.'">'.get_vocab($valid).'</a></li>';
    $divs[$valid] = '<div id="menu_'.$valid.'" class="tab-pane fade in active">';
}
else{ // par défaut on affiche la page des coordonnées
    $pill['moi'] = '<li class="active"><a data-toggle="pill" href="#menu_'.'moi'.'">'.get_vocab('moi').'</a></li>';
    $divs['moi'] = '<div id="menu_'.'moi'.'" class="tab-pane fade in active">';
}

$span = (isset($_POST['span'])&&(($_POST['span']==0)||($_POST['span']==1)))? $_POST['span'] : 1; // par défaut résas à venir

if ($valid == 'moi')
{
	$sql = "SELECT nom,prenom,email
	FROM ".TABLE_PREFIX."_utilisateurs
	WHERE login='".getUserName()."'";
	$res = grr_sql_query($sql);
	if ($res)
	{
		$row = grr_sql_row($res, 0);
		$user_nom = $row[0];
		$user_prenom = $row[1];
		$user_email = $row[2];
	}
	$reg_email = isset($_POST['reg_email']) ? clean_input($_POST['reg_email']) : $user_email;
	$reg_nom = isset($_POST['reg_nom']) ? clean_input($_POST['reg_nom']) : $user_nom;
	$reg_prenom = isset($_POST['reg_prenom']) ? clean_input($_POST['reg_prenom']) : $user_prenom;
	$champ_manquant = 'n';
	if (trim($reg_nom) == '')
		$champ_manquant = 'y';
	if (trim($reg_prenom) == '')
		$champ_manquant = 'y';
	if (($user_email != $reg_email) || ($user_nom != $reg_nom) || ($user_prenom != $reg_prenom)) //MAJ
	{
		$sql = "UPDATE ".TABLE_PREFIX."_utilisateurs SET ";
		$flag_virgule = 'n';
		if (IsAllowedToModifyProfil())
		{
			if (trim($reg_nom) != '')
			{
				$sql.="nom = '" . protect_data_sql($reg_nom)."'";
				$flag_virgule = 'y';
				$_SESSION['nom'] = htmlspecialchars($reg_nom);
			}
			if (trim($reg_prenom) != '')
			{
				if ($flag_virgule == 'y') $sql .=",";
				$sql .= "prenom = '" . protect_data_sql($reg_prenom)."'";
				$flag_virgule = 'y';
				$_SESSION['prenom'] = htmlspecialchars($reg_prenom);
			}
		}
		if (IsAllowedToModifyEmail())
		{
			if ($flag_virgule == 'y')
				$sql .= ",";
			$sql .= "email = '" . protect_data_sql($reg_email)."'";
		}
		$sql .= "WHERE login='".getUserName()."'";
		if ((IsAllowedToModifyProfil()) || (IsAllowedToModifyEmail()))
		{
			if (grr_sql_command($sql) < 0)
				fatal_error(0, get_vocab('message_records_error') . grr_sql_error());
			else
				$msg .= "\\n".get_vocab('message_records');
		}
	}
	if (IsAllowedToModifyProfil() && ($champ_manquant=='y'))
		$msg .= "\\n".str_replace("\'","'",get_vocab('required'));
}
if (($valid == 'param')||($valid == 'reset'))
{
    $default_site = isset($_POST['id_site']) ? intval($_POST['id_site']) : NULL;
	$default_area = isset($_POST['id_area']) ? intval($_POST['id_area']) : NULL;
	$default_room = isset($_POST['id_room']) ? intval($_POST['id_room']) : NULL;
	$default_style = isset($_POST['default_css']) ? clean_input($_POST['default_css']) : NULL;
	$default_list_type = isset($_POST['area_item_format']) ? clean_input($_POST['area_item_format']) : NULL;
	$default_language = isset($_POST['default_language']) ? clean_input($_POST['default_language']) : NULL;
	$sql = "UPDATE ".TABLE_PREFIX."_utilisateurs
	SET default_site = '".protect_data_sql($default_site)."',
	default_area = '".protect_data_sql($default_area)."',
	default_room = '".protect_data_sql($default_room)."',
	default_style = '". protect_data_sql($default_style)."',
	default_list_type = '".protect_data_sql($default_list_type)."',
	default_language = '".protect_data_sql($default_language)."'
	WHERE login='".getUserName()."'";
	if (grr_sql_command($sql) < 0)
		fatal_error(0, get_vocab('message_records_error').grr_sql_error());
	else
	{
		if (($default_site != '') && ($default_site !='0'))
			$_SESSION['default_site'] = $default_site;
		else
			$_SESSION['default_site'] = Settings::get('default_site');
		if (($default_area != '') && ($default_area !='0'))
			$_SESSION['default_area'] = $default_area;
		else
			$_SESSION['default_area'] = Settings::get('default_area');
		if (($default_room != '') && ($default_room !='0'))
			$_SESSION['default_room'] = $default_room;
		else
			$_SESSION['default_room'] = Settings::get('default_room');
		if ($default_style != '')
			$_SESSION['default_style'] = $default_style;
		else
			$_SESSION['default_style'] = Settings::get('default_css');
		if ($default_list_type != '')
			$_SESSION['default_list_type'] = $default_list_type;
		else
			$_SESSION['default_list_type'] = Settings::get('area_list_format');
		if ($default_language != '')
			$_SESSION['default_language'] = $default_language;
		else
			$_SESSION['default_language'] = Settings::get('default_language');
	}
}
if (($valid == 'pwd')&& IsAllowedToModifyMdp())
{
    $reg_password_a = isset($_POST['reg_password_a']) ? $_POST['reg_password_a'] : NULL;
    $reg_password1 = isset($_POST['reg_password1']) ? $_POST['reg_password1'] : NULL;
    $reg_password2 = isset($_POST['reg_password2']) ? $_POST['reg_password2'] : NULL;
    if (($reg_password_a != '') && ($reg_password1 != ''))
    {
        $reg_password_a_c = md5($reg_password_a);
        if ($_SESSION['password'] == $reg_password_a_c)
        {
            if ($reg_password1 != $reg_password2)
                $msg = get_vocab('wrong_pwd2');
            else
            {
                VerifyModeDemo();
                $reg_password1 = md5($reg_password1);
                $sql = "UPDATE ".TABLE_PREFIX."_utilisateurs SET password='".protect_data_sql($reg_password1)."' WHERE login='".getUserName()."'";
                if (grr_sql_command($sql) < 0)
                    fatal_error(0, get_vocab('update_pwd_failed') . grr_sql_error());
                else
                {
                    $msg = get_vocab('update_pwd_succeed');
                    $_SESSION['password'] = $reg_password1;
                }
            }
        }
        else
            $msg = get_vocab('wrong_old_pwd');
    }
    else 
        $msg = get_vocab('wrong_old_pwd');
}

// données utilisateur
$user=array();
$sql = "SELECT nom,prenom,statut,email,default_site,default_area,default_room,default_style,default_list_type,default_language,source FROM ".TABLE_PREFIX."_utilisateurs WHERE login='".getUserName()."'";
$res = grr_sql_query($sql);
if ($res)
{
	$user = grr_sql_row_keyed($res, 0); // utilisateur.login est unique
    grr_sql_free($res);
}
$default_site = (isset($user['default_site']) && ($user['default_site'] != '0') && ($user['default_site'] != ''))? $user['default_site'] : Settings::get('default_site');
$default_area = (isset($user['default_area']) && ($user['default_area'] != '0') && ($user['default_area'] != ''))? $user['default_area'] : Settings::get('default_area');
$default_room = (isset($user['default_room']) && ($user['default_room'] != '0') && ($user['default_room'] != ''))? $user['default_room'] : Settings::get('default_room');
$default_css = (isset($user['default_style']) && ($user['default_style'] != ''))? $user['default_style'] : Settings::get('default_css');
$default_list_type = (isset($user['default_list_type']) && ($user['default_list_type'] != ''))? $user['default_list_type'] : Settings::get('area_list_format');
$default_language = (isset($user['default_language']) && ($user['default_language'] != ''))? $user['default_language'] : Settings::get('default_language');

function menu_moi($user){
    $mod_profil = IsAllowedToModifyProfil();
    $mod_email = IsAllowedToModifyEmail();
    $html = '<div class="container">';
	$html .= '<form class="form-horizontal" id="form_moi" action="my_account.php" method="post">';
    $html .= '<div class="form-group">';
    $html .= '<label class="control-label col-md-2 col-sm-3 col-xs-4" for="login">'.get_vocab('login').get_vocab('deux_points').'</label>';
    $html .= '<div class="col-md-4 col-sm-6 col-xs-8">';
    $html .= '<input class="form-control" id="login" type="text" name="login" value="'.getUserName().'" size="30" disabled/>';
    $html .= '</div></div>';
    $html .= '<div class="form-group">';
    $html .= '<label class="control-label col-md-2 col-sm-3 col-xs-4" for="nom">'.get_vocab('last_name').get_vocab('deux_points').'*</label>';
    $html .= '<div class="col-md-4 col-sm-6 col-xs-8">';
    $html .= '<input class="form-control" id="nom" type="text" name="reg_nom" value="';
    if ($user['nom'])
        $html .= htmlspecialchars($user['nom']);
    $html .= '"';
    if (!$mod_profil) 
        $html .= ' disabled';
    $html .= ' size="30" /></div>';
    $html .= '</div>';
    $html .= '<div class="form-group">';
    $html .= '<label class="control-label col-md-2 col-sm-3 col-xs-4" for="prenom">'.get_vocab('first_name').get_vocab('deux_points').'*</label>';
    $html .= '<div class="col-md-4 col-sm-6 col-xs-8">';
    $html .= '<input class="form-control" id="prenom" type="text" name="reg_prenom" value="';
    if ($user['prenom'])
        $html .= htmlspecialchars($user['prenom']);
    $html .= '"';
    if (!$mod_profil) 
        $html .= ' disabled';
    $html .= ' size="30" /></div>';
    $html .= '</div>';
    $html .= '<div class="form-group">';
    $html .= '<label class="control-label col-md-2 col-sm-3 col-xs-4" for="email">'.get_vocab('mail_user').get_vocab('deux_points').'</label>';
    $html .= '<div class="col-md-4 col-sm-6 col-xs-8">';
    $html .= '<input class="form-control" id="email" type="text" name="reg_email" value="';
    if ($user['email'])
        $html .= htmlspecialchars($user['email']);
    $html .= '"';
    if (!$mod_email) 
        $html .= ' disabled';
    $html .= ' size="30" /></div>';
    $html .= '</div>';
	if ($user['statut'] == "utilisateur")
		$text_user_statut = get_vocab("statut_user");
	else if ($user['statut'] == "visiteur")
		$text_user_statut = get_vocab("statut_visitor");
	else if ($user['statut'] == "gestionnaire_utilisateur")
		$text_user_statut = get_vocab("statut_user_administrator");
	else if ($user['statut'] == "administrateur")
		$text_user_statut = get_vocab("statut_administrator");
	else
		$text_user_statut = $user['statut'];
    $html .= '<div class="form-group">';
    $html .= '<label class="control-label col-md-2 col-sm-3 col-xs-4" for="statut">'.get_vocab('statut').get_vocab('deux_points').'</label>';
    $html .= '<div class="col-md-4 col-sm-6 col-xs-8">';
    $html .= '<input class="form-control" id="statut" type="text" name="reg_statut" value="'.$text_user_statut.'"';
    $html .= ' disabled size="30" /></div>';
    $html .= '</div>';
	$html .= '<div id="fixe">
            <input type="hidden" name="valid" value="moi" />
            <input class="btn btn-primary" type="submit" value="'.get_vocab('save').'" />
            </div>';
    $html .= "</form>";
	if (IsAllowedToModifyProfil())
	{
		$html .= '<p><em>('.str_replace("\'","'",get_vocab('required')).')</em></p>';
		if ((trim($user['nom']) == "") || (trim($user['prenom']) == ''))
			$html .= "\n".'      <h2 class="avertissement">'.get_vocab('nom_prenom_valides').'</h2>';
	}
    $html .= "</div>";
    return $html;
}
function menu_conn($login){
    // on commence par récupérer les données de connexion
    $sql = "SELECT START, SESSION_ID, REMOTE_ADDR, USER_AGENT, REFERER, AUTOCLOSE, END FROM ".TABLE_PREFIX."_log WHERE LOGIN = '".$login."' ORDER by START desc";
    $res = grr_sql_query($sql);
    if (!$res){
        grr_sql_error();
    }
    else {
        // affichage des résultats
        $html = '<p>'.get_vocab("see_connexions_explain").'</p>';
        $html .= '<table class="table table-bordered">
                <thead>
                  <tr>
                    <th class="col">
                        '.get_vocab("begining_of_session").'
                    </th>
                    <th class="col">
                        '.get_vocab("end_of_session").'
                    </th>   
                    <th class="col">
                        '.get_vocab("ip_adress").'
                    </th>
                    <th class="col">
                        '.get_vocab("navigator").'
                    </th>
                    <th class="col">
                        '.get_vocab("referer").'
                    </th>
                  </tr>
                </thead>
                ';
        $html .= "<tbody>";
        $now = time();
        for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
        {
            $annee = substr($row[6],0,4);
            $mois =  substr($row[6],5,2);
            $jour =  substr($row[6],8,2);
            $heures = substr($row[6],11,2);
            $minutes = substr($row[6],14,2);
            $secondes = substr($row[6],17,2);
            $end_time = mktime($heures, $minutes, $secondes, $mois, $jour, $annee);
            $temp1 = '';
            $temp2 = '';
            if ($end_time > $now)
            {
                $temp1 = "<span style=\"color:green;\">";
                $temp2 = "</span>";
            }
            else if ($row[5])
            {
                $temp1 = "<span style=\"color:red\">";
                $temp2 = "</span>";
            }
            $html .= "<tr>\n";
            $html .= "<td class=\"col\">".$temp1.$row[0].$temp2."</td>";
            $html .= "<td class=\"col\">".$temp1.$row[6].$temp2."</td>\n";
            $html .= "<td class=\"col\">".$temp1.$row[2].$temp2."</td>\n";
            $html .= "<td class=\"col\">".$temp1.$row[3].$temp2."</td>\n";
            $html .= "<td class=\"col\">".$temp1.$row[4].$temp2."</td>\n";
            $html .= "</tr>\n";
        }
        $html .= "</tbody></table>";
        return $html;
    }
}
/* met en forme une ligne du tableau des réservations à partir des données SQL et du paramètre $dformat
*/
// Report on one entry. See below for columns in $row[].
function reportone(&$row, $dformat)
{
	global $vocab, $enable_periods;
	echo "<tr>";
		//Affichage de l'heure et de la durée de réservation
	if ($enable_periods == 'y')
		list($start_date, $start_time ,$duration, $dur_units) =  describe_period_span($row[1], $row[2]);
	else
		list($start_date, $start_time ,$duration, $dur_units) = describe_span($row[1], $row[2], $dformat);

        // Date début réservation
	echo "<td>".$start_date . "</td>";
        // Heure début réservation
	echo "<td>".$start_time . "</td>";
        // Durée réservation
	echo "<td>".$duration ." ". $dur_units ."</td>";
        //Affiche "area"
	$area_nom = htmlspecialchars($row[8]);
	$areadescrip = htmlspecialchars($row[10]);
	if ($areadescrip != "")
		$titre_area_descript = "title=\"".$areadescrip."\"";
	else
		$titre_area_descript = "";
	echo "<td ".$titre_area_descript." >".$area_nom."</td>";
		//Affiche "room"
	$room = htmlspecialchars($row[9]);
	echo "<td>".$room."</td>";
		// Breve description (title), avec un lien
	$breve_description = affichage_lien_resa_planning($row[3],$row[0]);
	$breve_description = "<a href=\"view_entry.php?id=$row[0]&amp;mode=page\">". $breve_description . "</a>";
	echo "<td>".$breve_description."</td>\n";
		//Description complète
	if ($row[4] != "")
		$description = nl2br(htmlspecialchars($row[4]));
	else
		$description = " ";
	echo "<td>". $description . "</td>\n";
		//Type de réservation
	$et = grr_sql_query1("SELECT type_name FROM ".TABLE_PREFIX."_type_area WHERE type_letter='".$row[5]."'");
	if ($et == -1)
		$et = "?".$row[5]."?";
	echo "<td>".$et."</td>\n";
		//Affichage de la date de la dernière mise à jour
	echo "<td>". date_time_string($row[7],$dformat) . "</td>\n";
	echo "</tr>\n";
}
/* paramètres : $login = login de l'utilisateur, $span = 0 : toutes | 1 : à venir
*/
function mes_resas($login,$span,$dformat){
    $sql = "SELECT distinct e.id, e.start_time, e.end_time, e.name, e.description, "
    . "e.type, e.beneficiaire, "
    .  grr_sql_syntax_timestamp_to_unix("e.timestamp")
    . ", a.area_name, r.room_name, r.description, a.id, e.overload_desc, r.order_display, t.type_name"
	. ", e.beneficiaire_ext"
    . " FROM ".TABLE_PREFIX."_entry e, ".TABLE_PREFIX."_area a, ".TABLE_PREFIX."_room r, ".TABLE_PREFIX."_type_area t"
    . " WHERE e.room_id = r.id AND r.area_id = a.id"
    . " AND e.beneficiaire = '".$login."' ";
    if ($span)
        $sql .= "AND e.start_time >= ".time() ;
    $sql .= " AND  t.type_letter = e.type ";
    $sql .= "ORDER BY e.start_time ASC ";

    $res = grr_sql_query($sql);
    if (!$res)
        fatal_error(0, grr_sql_error());
    $nmatch = grr_sql_count($res);
    if ($nmatch == 0)
    {
        echo "<p><b>" . get_vocab("nothing_found") . "</b></p>\n";
        grr_sql_free($res);
    }
    else
    {
        echo "<p><b>" . $nmatch . " "
        . ($nmatch == 1 ? get_vocab("entry_found") : get_vocab("entries_found"))
        .  "</b></p>\n";
        echo "<table class='table table-bordered table-condensed'>";
        echo '<thead><tr><th>Date de début</th><th>Heure</th><th>Durée :</th><th>Domaine</th><th>Ressource</th><th>Brève description</th><th>Description complète </th><th>Type</th><th>Dernière mise à jour</th></tr></thead>';
        echo '<tbody>';
        for ($i = 0; ($row = grr_sql_row($res, $i)); $i++){
            get_planning_area_values($row[11]); // détermine le format d'affichage $dformat
            reportone($row, $dformat);
        }
        grr_sql_free($res);
        echo '</tbody></table>';
    }
}
/* paramètres : $login = login de l'utilisateur, $dformat = format des dates, issu de language.inc.php
*/
function menu_resa($login,$span,$dformat){
    echo get_vocab('resa_menu_explain');
    echo '<form class="form-inline" action="my_account.php" method="POST">'.PHP_EOL;
    echo '<input type="hidden" name="valid" value="resa" />'.PHP_EOL;
    echo '<input class="btn btn-default" type="submit" value="'.get_vocab('goto').'" />'.PHP_EOL;
    echo '<label class="radio-inline"><input type="radio" name="span" value=0';
    if ($span == 0)
        echo ' checked';
    echo ' />'.get_vocab('resas_toutes').'</label>';
    echo '<label class="radio-inline"><input type="radio" name="span" value=1';
    if ($span == 1)
        echo ' checked';
    echo ' />'.get_vocab('resas_a_venir').'</label>';
    echo '</form>'.PHP_EOL;
    echo mes_resas($login,$span,$dformat);
}

start_page_w_header();
affiche_pop_up($msg,'admin');
// print_r($_POST);
?>
<script type="text/javascript" >
	function modifier_liste_domaines(){
		$.ajax({
			url: "my_account_modif_listes.php",
			type: "get",
			dataType: "html",
			data: {
				id_site: $('#id_site').val(),
				// default_area : '<?php echo Settings::get("default_area"); ?>',
                default_area : '<?php echo $default_area; ?>',
				session_login:'<?php echo getUserName(); ?>',
				use_site:'<?php echo $use_site; ?>',
				type:'domaine',
			},
			success: function(returnData){
				$("#div_liste_domaines").html(returnData);
			},
			error: function(e){
				alert(e);
			}
		});
	}
	function modifier_liste_ressources(action){
		$.ajax({
			url: "my_account_modif_listes.php",
			type: "get",
			dataType: "html",
			data: {
				id_area:$('#id_area').val(),
				// default_room : '<?php echo Settings::get("default_room"); ?>',
                default_room : '<?php echo $default_room; ?>',
				type:'ressource',
				action:+action,
				},
			success: function(returnData){
				$("#div_liste_ressources").html(returnData);
			},
			error: function(e){
				alert(e);
			}
		});
	}
</script>
<?php
echo '<div class="container">';
echo '  <h2>'.get_vocab('my_data').'</h2>';
echo '  <ul class="nav nav-pills">';
foreach ($menus as $menu){
    echo $pill[$menu];
}
echo ' </ul>';
echo '   <div class="tab-content">';
echo $divs['moi'];
echo '      <h3>'.get_vocab('moi').'</h3>';
echo menu_moi($user);
echo '    </div>';
echo $divs['param'];
        echo '<h3>'.get_vocab('default_parameter_values_title').'</h3>';
        echo '<div class="container">';
        echo '<form class="form-horizontal" id="form_param" action="my_account.php" method="post">';
        echo "\n".'<h4>'.get_vocab('explain_area_list_format').'</h4>';
        echo '<div class="radio">
                <label><input type="radio" name="area_item_format" value="list" ';
				if ($user['default_list_type'] == 'list')
					echo 'checked="checked"';
				echo ' />'.get_vocab('liste_area_list_format').'</label>
              </div>';
        echo '<div class="radio">
                <label><input type="radio" name="area_item_format" value="select" ';
				if ($user['default_list_type'] == 'select')
					echo 'checked="checked" ';
				echo ' />'.get_vocab('select_area_list_format').'</label>
                </div>';
        echo '<div class="radio">
                <label><input type="radio" name="area_item_format" value="item" ';
				if ($user['default_list_type'] == 'item')
					echo 'checked="checked" ';
				echo ' />'.get_vocab('item_area_list_format').'</label>
                </div> ';
/**
 * Liste des sites
 */
	if ($use_site == 'y')
	{
		echo '<h4>'.get_vocab('explain_default_area_and_room_and_site').'</h4>';
		$sql = "SELECT id,sitecode,sitename
		FROM ".TABLE_PREFIX."_site
		ORDER BY id ASC";
		$resultat = grr_sql_query($sql);
		echo '<div class="form-group">
                <label class="control-label col-md-3 col-sm-3 col-xs-4" for="id_site">'.get_vocab('default_site').get_vocab('deux_points').'</label>
				<div class="col-md-4 col-sm-6 col-xs-8">
                <select class="form-control" id="id_site" name="id_site" for="id_site" onchange="modifier_liste_domaines();modifier_liste_ressources(2)">
						<option value="-1">'.get_vocab('choose_a_site').'</option>'."\n";
						for ($enr = 0; ($row = grr_sql_row($resultat, $enr)); $enr++)
						{
							echo '<option value="'.$row[0].'"';
							if ($default_site == $row[0])
								echo ' selected="selected" ';
							echo '>'.htmlspecialchars($row[2]);
							echo '</option>'."\n";
						}
						echo '</select>
                </div>
			</div>';
	}
	else
	{
		echo '<h4>'.get_vocab('explain_default_area_and_room').'</h4>';
		echo '<input type="hidden" id="id_site" name="id_site" value="-1" />';
	}
/* Liste des domaines */
	echo '<div id="div_liste_domaines">';
	echo '</div>';
	/* Liste des ressources */
	echo '<div id="div_liste_ressources">';
	echo '<input type="hidden" id="id_area" name="id_area" value="'.$default_area.'" />';
	echo '</div>';
	/* Au chargement de la page, on initialise les select */
	echo '<script type="text/javascript">modifier_liste_domaines();</script>'."\n";
	echo '<script type="text/javascript">modifier_liste_ressources(1);</script>'."\n";
/**
 * Choix de la feuille de style par défaut
 */
	echo '<h4>'.get_vocab('explain_css').'</h4>';
	echo '<div class="form-group">
                <label class="control-label col-md-3 col-sm-3 col-xs-4" for="css">'.get_vocab('choose_css').'</label>
				<div class="col-md-4 col-sm-6 col-xs-8">
					<select class="form-control" name="default_css" id="css">'."\n";
						$i = 0;
						while ($i < count($liste_themes))
						{
							echo '              <option value="'.$liste_themes[$i].'"';
							if ($default_css == $liste_themes[$i])
								echo ' selected="selected"';
							echo ' >'.encode_message_utf8($liste_name_themes[$i]).'</option>'."\n";
							$i++;
						}
						echo '</select>
				</div>
		  </div>'."\n";
/**
 * Choix de la langue
 */
	echo '      <h4>'.get_vocab('choose_language').'</h4>';
	echo '<div class="form-group">
            <label class="control-label col-md-3 col-sm-3 col-xs-4" for="lang">'.get_vocab('choose_css').'</label>
            <div class="col-md-4 col-sm-6 col-xs-8">
                    <select class="form-control" name="default_language" id="lang">'."\n";
                        $i = 0;
                        while ($i < count($liste_language))
                        {
                            echo '              <option value="'.$liste_language[$i].'"';
                            if ($default_language == $liste_language[$i])
                                echo ' selected="selected"';
                            echo ' >'.encode_message_utf8($liste_name_language[$i]).'</option>'."\n";
                            $i++;
                        }
                        echo '</select>
            </div>
         </div>';
    echo '<div id="fixe">
            <div>
                <input type="hidden" name="valid" value="param" />
                <br />
                <input class="btn btn-primary" type="submit" value="'.get_vocab('save').'" />
            </div>
        </form>
        <!-- Formulaire de Reset des données -->
        <form id="reset" action="my_account.php" method="post">
            <div>
                <input type="hidden" name="valid" value="reset" />
                <input type="hidden" name="id_site" value="'.$reset_site.'" />
                <input type="hidden" name="id_area" value="'.$reset_area.'" />
                <input type="hidden" name="id_room" value="'.$reset_room.'" />
                <input type="hidden" name="default_css" value="" />
                <input type="hidden" name="area_item_format" value="" />
                <input type="hidden" name="default_language" value="" />
                <input class="btn btn-primary" type="submit" value="'.get_vocab('reset').'" />
            </div>
        </form>
        </div>
      </div>
    </div>';
echo $divs['pwd'];
echo '  <h3>'.get_vocab('pwd').'</h3>';
//echo menu_pwd(getUserName());
if (!IsAllowedToModifyMdp()){
    echo '<p class="">'.get_vocab('user_change_pwd_interdit').'</p>';
}
else {
    echo '<div class="container">';
    echo '<script type="text/javascript" src="./js/pwd_strength.js"></script>';
    echo '  <p>'.get_vocab('pwd_msg_warning').'</p>
            <form class="form-horizontal" id="form_pwd" action="my_account.php" method="post">
              <div class="form-group">
                <label class="control-label col-md-4 col-sm-6 col-xs-8" for="opwd">'.get_vocab('old_pwd').get_vocab('deux_points').'</label>
                <div class="col-md-3 col-sm-4 col-xs-6">
                <input class="form-control" id="opwd" type="password" name="reg_password_a" size="20" required /></div>
              </div>
              <div class="form-group">
                <label class="control-label col-md-4 col-sm-6 col-xs-8" for="pwd1">'.get_vocab('new_pwd1').get_vocab('deux_points').'</label>
                <div class="col-md-3 col-sm-4 col-xs-6">
                <input id="pwd1" class="form-control" id="pwd1" type="password" name="reg_password1" size="20" 
                onkeyup="runPassword(this.value, \'pwd1\');" required /></div>
              </div>
              <div class="form-group">
                <div class="col-md-4 col-sm-6 col-xs-8"><p class="text-right">'.get_vocab('pwd_strength').get_vocab('deux_points').'</p></div>
                <div class="col-md-3 col-sm-4 col-xs-6">
                  <div id="pwd1_text" style="font-size: 11px;"></div>
                  <div id="pwd1_bar" style="font-size: 1px; height: 3px; width: 0px; border: 1px solid white;"></div>
                </div>
              </div>
              <div class="form-group">
                <label class="control-label col-md-4 col-sm-6 col-xs-8" for="pwd2">'.get_vocab('new_pwd2').get_vocab('deux_points').'</label>
                <div class="col-md-3 col-sm-4 col-xs-6">
                <input class="form-control" id="pwd2" type="password" name="reg_password2" size="20" required /></div>
              </div>';
	echo '<div id="fixe">
            <input type="hidden" name="valid" value="pwd" />
            <input class="btn btn-primary" type="submit" value="'.get_vocab('save').'" />
          </div>';
    echo "  </form>
         </div>";
}
echo '</div>';
echo $divs['conn'];
echo '      <h3>'.get_vocab('conn').'</h3>';
echo menu_conn(getUserName());
echo '</div>';
echo $divs['resa'];
echo '      <h3>'.get_vocab('resa').'</h3>';
echo menu_resa(getUserName(),$span,$dformat);
echo '</div>';
echo '</div>'; // tab content
echo '</div>'; // container
end_page();
?>