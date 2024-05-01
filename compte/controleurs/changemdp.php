<?php
/**
 * changemdp.php
 * Interface permettant à l'utilisateur de gérer son compte dans l'application GRR
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2024-04-28 17:30$
 * @author    JeromeB
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
$grr_script_name = 'changemdp.php';

$trad = $vocab;

if (!Settings::load())
	die('Erreur chargement settings');
$desactive_VerifNomPrenomUser='y';

$d['masquerMenu'] = 1;

$back = '';
if (isset($_SERVER['HTTP_REFERER']))
	$back = htmlspecialchars($_SERVER['HTTP_REFERER']);

$user_login = isset($_POST['user_login']) ? $_POST['user_login'] : (isset($_GET['user_login']) ? $_GET['user_login'] : NULL);
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
			$reg_password_a_c = password_verify($reg_password_a, $_SESSION['password']);
			if ($_SESSION['password'] == $reg_password_a_c)
			{
				if ($reg_password1 != $reg_password2)
					$msg = get_vocab('wrong_pwd2');
				elseif($reg_password_a == $reg_password1)
					$msg = get_vocab('wrong_pwd3');
				elseif(strlen($reg_password1) < $pass_leng)
					$msg .= get_vocab('mdp_taille').$pass_leng;
				else
				{
					VerifyModeDemo();
					$reg_password1 = password_hash($reg_password1, PASSWORD_DEFAULT);
					$sql = "UPDATE ".TABLE_PREFIX."_utilisateurs SET password='".protect_data_sql($reg_password1)."', changepwd ='0' WHERE login='".getUserName()."'";
					if (grr_sql_command($sql) < 0)
						fatal_error(0, get_vocab('update_pwd_failed') . grr_sql_error());
					else
					{
						$msg = get_vocab('update_pwd_succeed');
						$_SESSION['password'] = $reg_password1;
						$_SESSION['changepwd'] = 0;
						if($page != 'moncompte')
							header("Location: ./compte.php");
					}
				}
			}
			else
				$msg = get_vocab('wrong_old_pwd');
		}
	}
}

affiche_pop_up($msg,'admin');

if (IsAllowedToModifyMdp() || $_SESSION['changepwd'] == 1)
    $d['droitChanger'] = 1;

if($_SESSION['changepwd'] == 1)
    $d['obligatoirement'] = 1;

echo $twig->render('changemdp.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings));
?>