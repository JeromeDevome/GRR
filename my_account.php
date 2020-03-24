<?php
/**
 * my_account.php
 * Interface permettant à l'utilisateur de gérer son compte dans l'application GRR
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2020-03-24 10:30$
 * @author    Laurent Delineau & JeromeB & Yan Naessens
 * @copyright Copyright 2003-2020 Team DEVOME - JeromeB
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
	header('Location: logout.php?auto=1&url=$url');
	die();
};
// Definition_ressource_domaine_site();
$day = isset($_POST['day']) ? $_POST['day'] : (isset($_GET['day']) ? $_GET['day'] : date('d'));
$month = isset($_POST['month']) ? $_POST['month'] : (isset($_GET['month']) ? $_GET['month'] : date('m'));
$year = isset($_POST['year']) ? $_POST['year'] : (isset($_GET['year']) ? $_GET['year'] : date('Y'));
include_once('include/language.inc.php');
//include "include/resume_session.php";
$back = '';
if (isset($_SERVER['HTTP_REFERER']))
	$back = htmlspecialchars($_SERVER['HTTP_REFERER']);
$user_login = isset($_POST['user_login']) ? $_POST['user_login'] : ($user_login = isset($_GET['user_login']) ? $_GET['user_login'] : NULL);
$valid = isset($_POST['valid']) ? $_POST['valid'] : NULL;
// valeurs par défaut pour le reset
$reset_site = Settings::get('default_site');
$reset_area = Settings::get('default_area');
$reset_room = Settings::get('default_room');
$msg = '';
if ($valid == 'yes')
{
	if (IsAllowedToModifyMdp())
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
	}
	$sql = "SELECT email,source,nom,prenom
	FROM ".TABLE_PREFIX."_utilisateurs
	WHERE login='".getUserName()."'";
	$res = grr_sql_query($sql);
	if ($res)
	{
		for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
		{
			$user_email = $row[0];
			$user_source = $row[1];
			$user_nom = $row[2];
			$user_prenom = $row[3];
		}
	}
	$reg_email = isset($_POST['reg_email']) ? clean_input($_POST['reg_email']) : $user_email;
	$reg_nom = isset($_POST['reg_nom']) ? clean_input($_POST['reg_nom']) : $user_nom;
	$reg_prenom = isset($_POST['reg_prenom']) ? clean_input($_POST['reg_prenom']) : $user_prenom;
	$champ_manquant = 'n';
	if (trim($reg_nom) == '')
		$champ_manquant = 'y';
	if (trim($reg_prenom) == '')
		$champ_manquant = 'y';
	if (($user_email != $reg_email) || ($user_nom != $reg_nom) || ($user_prenom != $reg_prenom))
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
if (($valid == 'yes') || ($valid=='reset'))
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
$use_prototype = 'y';
// début du code HTML
start_page_w_header($day, $month, $year, $type="with_session");
echo "\n    <!-- Repere ".$grr_script_name." -->\n";
if (Settings::get("module_multisite") == "Oui")
	$use_site = 'y';
else
	$use_site = 'n';
// données utilisateur
$sql = "SELECT nom,prenom,statut,email,default_site,default_area,default_room,default_style,default_list_type,default_language,source FROM ".TABLE_PREFIX."_utilisateurs WHERE login='".getUserName()."'";
$res = grr_sql_query($sql);
if ($res)
{
	for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
	{
		$user_nom = $row[0];
		$user_prenom = $row[1];
		$user_statut = $row[2];
		$user_email = $row[3];
		if (($row[4] != '') && ($row[4] !='0'))
			$default_site = $row[4];
		else
			$default_site = Settings::get('default_site');
		if (($row[5] != '') && ($row[5] !='0'))
			$default_area = $row[5];
		else
			$default_area = Settings::get('default_area');
		if (($row[6] != '') && ($row[6] !='0'))
			$default_room = $row[6];
		else
			$default_room = Settings::get('default_room');
		if ($row[7] != '')
			$default_css = $row[7];
		else
			$default_css = Settings::get('default_css');
		if ($row[8] != '')
			$default_list_type = $row[8];
		else
			$default_list_type = Settings::get('area_list_format');
		if ($row[9] != '')
			$default_language = $row[9];
		else
			$default_language = Settings::get('default_language');
		$user_source = $row[10];
	}
}
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
affiche_pop_up($msg,'admin');
echo ('
	<div class="container">
	<form id="param_account" action="my_account.php" method="post">
		<table>');
	if (!(IsAllowedToModifyProfil()))
	{
		echo '
		<tr>
			<td><b>'.get_vocab('login').get_vocab('deux_points').'</b></td>
			<td>'.getUserName().'</td>
		</tr>';
		echo '
		<tr>
			<td><b>'.get_vocab('last_name').get_vocab('deux_points').'</b></td>
			<td>'.$user_nom.'</td>
		</tr>';
		echo '
		<tr>
			<td><b>'.get_vocab('first_name').get_vocab('deux_points').'</b></td>
			<td>'.$user_prenom.'</td>
		</tr>';
	}
	else
	{
		echo '<tr><td><b>'.get_vocab('login').get_vocab('deux_points').'</b></td>';
		echo '<td>'.getUserName().'</td></tr>';
		echo '<tr><td><b>'.get_vocab('last_name').get_vocab('deux_points').'</b>*</td>';
		echo '<td><input class="form-control" type="text" name="reg_nom" value="';
		if ($user_nom)
			echo htmlspecialchars($user_nom);
		echo '" size="30" /></td></tr>';
		echo '<tr><td><b>'.get_vocab('first_name').get_vocab('deux_points').'</b>*</td><td><input class="form-control" type="text" name="reg_prenom" value="';
		if ($user_prenom)
			echo htmlspecialchars($user_prenom);
		echo '" size="30" /></td></tr>';
	}
	if (!(IsAllowedToModifyEmail()))
	{
		echo '
		<tr>
			<td><b>'.get_vocab('mail_user').get_vocab('deux_points').'</b></td>
			<td>'.$user_email.'</td>
		</tr>';
	}
	else
	{
		echo '<tr><td><b>'.get_vocab('mail_user').get_vocab('deux_points').'</b></td><td><input class="form-control" type="text" name="reg_email" value="';
		if ($user_email)
			echo htmlspecialchars($user_email);
		echo '" size="30" /></td></tr>';
	}
	if ($user_statut == "utilisateur")
		$text_user_statut = get_vocab("statut_user");
	else if ($user_statut == "visiteur")
		$text_user_statut = get_vocab("statut_visitor");
	else if ($user_statut == "gestionnaire_utilisateur")
		$text_user_statut = get_vocab("statut_user_administrator");
	else if ($user_statut == "administrateur")
		$text_user_statut = get_vocab("statut_administrator");
	else
		$text_user_statut = $user_statut;
	echo '<tr><td><b>'.get_vocab('statut').get_vocab('deux_points').'</b></td><td>'.$text_user_statut.'</td></tr></table>';
	if (IsAllowedToModifyProfil())
	{
		echo '<p>('.str_replace("\'","'",get_vocab('required')).')</p>';
		if ((trim($user_nom) == "") || (trim($user_prenom) == ''))
			echo "\n".'      <h2 class="avertissement">'.get_vocab('nom_prenom_valides').'</h2>';
	}
	if (IsAllowedToModifyMdp())
	{
		echo '
		<div>
			<table  class="table table-noborder">
				<tr>
					<td onclick="clicMenu(\'1\')" class="fontcolor4 center">
							<b><a href="#"><font color=black>'.get_vocab('click_here_to_modify_pwd').'</font></a></b>
					</td>
				</tr>
				<tr style="display:none" id="menu1">
					<td>
						<br />
						<p>'.get_vocab('pwd_msg_warning').'</p>'.get_vocab('old_pwd').get_vocab('deux_points').'
						<input type="password" name="reg_password_a" size="20" />
						<br />'.get_vocab('new_pwd1').get_vocab('deux_points').'
						<input type="password" name="reg_password1" size="20" />
						<br />'.get_vocab('new_pwd1').get_vocab('deux_points').'
						<input type="password" name="reg_password2" size="20" />
					</td>
				</tr>
			</table>
		</div>
		<hr />';
	}
    if (isset($_GET['see_conn']) && ($_GET['see_conn']==1))
    {
        // on commence par récupérer les données de connexion
        $sql = "SELECT START, SESSION_ID, REMOTE_ADDR, USER_AGENT, REFERER, AUTOCLOSE, END FROM ".TABLE_PREFIX."_log WHERE LOGIN = '".getUserName()."' ORDER by START desc";
        $res = grr_sql_query($sql);
        if (!$res){
            grr_sql_error();
        }
        else {
            echo '
                <div class="center">
                    <b><a href="my_account.php?see_conn=0">
                    <font color=black>'.get_vocab('click_here_to_hide_connexions').'</font></a></b>
                </div>';
            // affichage des résultats
            echo '<p>'.get_vocab("see_connexions_explain").'</p>';
            echo '<table class="table-bordered">
                    <thead>
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
                    </thead>
                    ';
            echo "<tbody>";
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
				echo "<tr>\n";
				echo "<td class=\"col\">".$temp1.$row[0].$temp2."</td>";
				echo "<td class=\"col\">".$temp1.$row[6].$temp2."</td>\n";
				echo "<td class=\"col\">".$temp1.$row[2].$temp2."</td>\n";
				echo "<td class=\"col\">".$temp1.$row[3].$temp2."</td>\n";
				echo "<td class=\"col\">".$temp1.$row[4].$temp2."</td>\n";
				echo "</tr>\n";
			}
            echo "</tbody></table>";
        }
        echo "<hr />";
    }
    else 
    {
        echo '
        <div class="center">
            <b><a href="my_account.php?see_conn=1">
            <font color=black>'.get_vocab('click_here_to_see_connexions').'</font></a></b>
        </div>
        <hr />';
    }
	echo "\n".'<h3>'.get_vocab('default_parameter_values_title').'</h3>';
	echo "\n".'<h4>'.get_vocab('explain_area_list_format').'</h4>';
	echo '
	<table>
		<tr>
			<td>'.get_vocab('liste_area_list_format').'</td>
			<td>
				<input type="radio" name="area_item_format" value="list" ';
				if ($default_list_type == 'list')
					echo 'checked="checked"';
				echo ' />';
				echo '
			</td>
		</tr>
		<tr>
			<td>'.get_vocab('select_area_list_format').'</td>
			<td>
				<input type="radio" name="area_item_format" value="select" ';
				if ($default_list_type == 'select')
					echo 'checked="checked" ';
				echo ' />';
				echo '
			</td>
		</tr>
		<tr>
			<td>'.get_vocab('item_area_list_format').'</td>
			<td>
				<input type="radio" name="area_item_format" value="item" ';
				if ($default_list_type == 'item')
					echo 'checked="checked" ';
				echo ' />';
				echo '
			</td>
		</tr>
	</table>';
/**
 * Liste des sites
 */
	if (Settings::get("module_multisite") == "Oui")
	{
		echo '<h4>'.get_vocab('explain_default_area_and_room_and_site').'</h4>';

		$sql = "SELECT id,sitecode,sitename
		FROM ".TABLE_PREFIX."_site
		ORDER BY id ASC";
		$resultat = grr_sql_query($sql);
		echo '
		<table>
			<tr>
				<td>'.get_vocab('default_site').get_vocab('deux_points').'</td>
				<td>
					<select class="form-control" id="id_site" name="id_site" onchange="modifier_liste_domaines();modifier_liste_ressources(2)">
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
					</td>
				</tr>';
	}
	else
	{
		echo '<h4>'.get_vocab('explain_default_area_and_room').'</h4>';
		echo '<input type="hidden" id="id_site" name="id_site" value="-1" />
		<table>';
	}
	/* Liste des domaines */
	echo '<tr><td colspan="2">';
	echo '<div id="div_liste_domaines">';
	echo '</div></td></tr>';
	/* Liste des ressources */
	echo '<tr><td colspan="2">';
	echo '<div id="div_liste_ressources">';
	echo '<input type="hidden" id="id_area" name="id_area" value="'.$default_area.'" />';
	echo '</div></td></tr></table>';
	/* Au chargement de la page, on initialise les select */
	echo '<script type="text/javascript">modifier_liste_domaines();</script>'."\n";
	echo '<script type="text/javascript">modifier_liste_ressources(1);</script>'."\n";
/**
 * Choix de la feuille de style par défaut
 */
	echo '<h4>'.get_vocab('explain_css').'</h4>';
	echo '
		<table>
			<tr>
				<td>'.get_vocab('choose_css').'</td>
				<td>
					<select class="form-control" name="default_css">'."\n";
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
					</td>
				</tr>
			</table>'."\n";
/**
 * Choix de la langue
 */
	echo '      <h4>'.get_vocab('choose_language').'</h4>';
	echo '
        <table>
            <tr>
                <td>'.get_vocab('choose_css').'</td>
                <td>
                    <select class="form-control" name="default_language">'."\n";
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
                    </td>
                </tr>
            </table>
      <div id="fixe">
            <div>
                <input type="hidden" name="valid" value="yes" />
                <input type="hidden" name="day" value="'.$day.'" />
                <input type="hidden" name="month" value="'.$month.'" />
                <input type="hidden" name="year" value="'.$year.'" />
                <br />
                <input class="btn btn-primary" type="submit" value="'.get_vocab('save').'" />
            </div>
        </form>
        <!-- Formulaire de Reset des données -->
        <form id="reset" action="my_account.php" method="post">
            <div>
                <input type="hidden" name="valid" value="reset" />
                <input type="hidden" name="day" value="'.$day.'" />
                <input type="hidden" name="month" value="'.$month.'" />
                <input type="hidden" name="year" value="'.$year.'" />
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
    </section>
</body>
</html>';
?>