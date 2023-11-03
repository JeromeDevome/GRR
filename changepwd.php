<?php
/**
 * changepwd.php
 * Interface permettant à l'utilisateur de gérer son mot de passe dans l'application GRR
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2023-11-03 10:39$
 * @author    JeromeB & Yan Naessens
 * @copyright Copyright 2003-2023 Team DEVOME - JeromeB
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
require_once('include/'.$dbsys.'.inc.php');
include_once('include/mrbs_sql.inc.php');
include_once('include/functions.inc.php');
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

$user_login = getFormVar('user_login','string');
$valid = isset($_POST['valid']) ? $_POST['valid'] : NULL;
$msg = '';
if ($valid == 'pwd')
{
	if (IsAllowedToModifyMdp() || $_SESSION['changepwd'] == 1)
	{
		$reg_password_a = getFormVar('reg_password_a','string');
		$reg_password1 = getFormVar('reg_password1','string');
		$reg_password2 = getFormVar('reg_password2','string');
		if (($reg_password_a != '') && ($reg_password1 != ''))
		{
            $test_md5 = $_SESSION['password'] == md5($reg_password_a);
            $test_hash = password_verify($reg_password_a, $_SESSION['password']);
			if ($test_md5 || $test_hash)
			{
				if ($reg_password1 != $reg_password2)
					$msg = get_vocab('wrong_pwd2');
				elseif($reg_password_a == $reg_password1)
					$msg = get_vocab('wrong_pwd3');
				else
				{
					VerifyModeDemo();
					$reg_password1 = password_hash($reg_password1, PASSWORD_DEFAULT);
					$sql = "UPDATE ".TABLE_PREFIX."_utilisateurs SET password= ? , changepwd ='0' WHERE login= ? ";
                    $types = 'ss';
                    $params = array(protect_data_sql($reg_password1),getUserName());
					if (grr_sql_command($sql, $types, $params) < 0)
						fatal_error(0, get_vocab('update_pwd_failed') . grr_sql_error());
					else
					{
						$msg = get_vocab('update_pwd_succeed');
						$_SESSION['password'] = $reg_password1;
						$_SESSION['changepwd'] = 0;
						header("Location: ./my_account.php?message=$msg");
					}
				}
			}
			else
				$msg = get_vocab('wrong_old_pwd');
		}
	}
}

start_page_w_header('','','',$type_session="no_session");

affiche_pop_up($msg,'admin');
echo '<div class="container">';
echo "<h3><span class='avertissement'>".get_vocab('user_change_pwd_obligatoire')."</span></h3>";
echo '<script type="text/javascript" src="./js/pwd_strength.js"></script>';
echo '  <p>'.get_vocab('pwd_msg_warning').'</p>
        <form class="form-horizontal" id="form_pwd" action="changepwd.php" method="post">
          <div class="form-group">
            <label class="control-label col-md-4 col-sm-6 col-xs-8" for="opwd">'.get_vocab('old_pwd').get_vocab('deux_points').'</label>
            <div class="col col-md-3 col-sm-4 col-xs-6">
            <input class="form-control" id="opwd" type="password" name="reg_password_a" size="20" required /></div>
          </div>
          <div class="form-group">
            <label class="control-label col-md-4 col-sm-6 col-xs-8" for="pwd1">'.get_vocab('new_pwd1').get_vocab('deux_points').'</label>
            <div class="col col-md-3 col-sm-4 col-xs-6">
            <input id="pwd1" class="form-control" type="password" name="reg_password1" size="20" 
            onkeyup="runPassword(this.value, \'pwd1\');" required /></div>
          </div>
          <div class="form-group">
            <div class="col col-md-4 col-sm-6 col-xs-8"><p class="text-right">'.get_vocab('pwd_strength').get_vocab('deux_points').'</p></div>
            <div class="col col-md-3 col-sm-4 col-xs-6">
              <div id="pwd1_text" style="font-size: 11px;"></div>
              <div id="pwd1_bar" style="font-size: 1px; height: 3px; width: 0px; border: 1px solid white;"></div>
            </div>
          </div>
          <div class="form-group">
            <label class="control-label col-md-4 col-sm-6 col-xs-8" for="pwd2">'.get_vocab('new_pwd2').get_vocab('deux_points').'</label>
            <div class="col col-md-3 col-sm-4 col-xs-6">
            <input class="form-control" id="pwd2" type="password" name="reg_password2" size="20" required /></div>
          </div>';
echo '<div id="fixe">
        <input type="hidden" name="valid" value="pwd" />
        <input class="btn btn-primary" type="submit" value="'.get_vocab('save').'" />
      </div>';
echo "  </form>
     </div>";
echo '	</section>
	</body>
</html>';
?>