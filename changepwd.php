<?php
/**
 * changepwd.php
 * Interface permettant à l'utilisateur de gérer son compte dans l'application GRR
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2020-02-28 10:10$
 * @author    JeromeB & Yan Naessens
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
$grr_script_name = 'changepwd.php';

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

include_once('include/language.inc.php');
include "include/resume_session.php";

$back = '';
if (isset($_SERVER['HTTP_REFERER']))
	$back = htmlspecialchars($_SERVER['HTTP_REFERER']);

$user_login = isset($_POST['user_login']) ? $_POST['user_login'] : ($user_login = isset($_GET['user_login']) ? $_GET['user_login'] : NULL);
$valid = isset($_POST['valid']) ? $_POST['valid'] : NULL;
$msg = '';
if ($valid == 'yes')
{
	if (IsAllowedToModifyMdp() || $_SESSION['changepwd'] == 1)
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
				elseif($reg_password_a == $reg_password1)
					$msg = get_vocab('wrong_pwd3');
				else
				{
					VerifyModeDemo();
					$reg_password1 = md5($reg_password1);
					$sql = "UPDATE ".TABLE_PREFIX."_utilisateurs SET password='".protect_data_sql($reg_password1)."', changepwd ='0' WHERE login='".getUserName()."'";
					if (grr_sql_command($sql) < 0)
						fatal_error(0, get_vocab('update_pwd_failed') . grr_sql_error());
					else
					{
						$msg = get_vocab('update_pwd_succeed');
						$_SESSION['password'] = $reg_password1;
						$_SESSION['changepwd'] = 0;
						header("Location: ./my_account.php");
					}
				}
			}
			else
				$msg = get_vocab('wrong_old_pwd');
		}
	}
}

//print_header($type="no_session");
start_page_w_header('','','',$type_session="no_session");

affiche_pop_up($msg,'admin');
echo ('
	<div class="container">
	<form action="changepwd.php" method="post">
		<input type="hidden" name="valid" value="yes" />
		<table>');

		echo '<tr><td><b>'.get_vocab('login').get_vocab('deux_points').'</b>';
		echo getUserName().'</td></tr>';

	if (IsAllowedToModifyMdp() || $_SESSION['changepwd'] == 1)
	{
		if($_SESSION['changepwd'] == 1)
			echo "<h3><span class='avertissement'>".get_vocab('user_change_pwd_obligatoire')."</span></h3>";
		echo '
			<tr>
				<td>
				
					<br />
					<p>'.get_vocab('pwd_msg_warning').'</p>'.get_vocab('old_pwd').get_vocab('deux_points').'
					<input type="password" name="reg_password_a" size="20" required />
					<br />'.get_vocab('new_pwd1').get_vocab('deux_points').'
					<input type="password" name="reg_password1" size="20" required />
					<br />'.get_vocab('new_pwd1').get_vocab('deux_points').'
					<input type="password" name="reg_password2" size="20" required />
				</td>
			</tr>
		</table>
		<hr />';
		echo '<input class="btn btn-primary" type="submit" value="'.get_vocab('save').'" />';
	}

	echo '</form>
		</div> 
		</section>
	</body>
</html>';
?>