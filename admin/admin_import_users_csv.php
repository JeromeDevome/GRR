<?php
/**
 * admin_import_user_csv.php
 * script d'importation d'utilisateurs à partir d'un fichier CSV
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2021-03-16 11:22$
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
$grr_script_name = "admin_import_users_csv.php";

include "../include/admin.inc.php";

$back = (isset($_SERVER['HTTP_REFERER']))? htmlspecialchars_decode($_SERVER['HTTP_REFERER'], ENT_QUOTES) : "./admin_accueil.php" ;
if ((authGetUserLevel(getUserName(), -1) < 6) && (authGetUserLevel(getUserName(), -1, 'user') !=  1))
{
	showAccessDenied($back);
	exit();
}
// début de page avec menu haut
start_page_w_header("", "", "", $type="with_session");
// Affichage de la colonne de gauche
include "admin_col_gauche2.php";
// affichage de la colonne de droite
echo "<div class='col-md-9 col-sm-8 col-xs-12'>";
echo "<h2>".get_vocab("display_add_user_list_csv")."</h2>";
echo '<a href="admin_user.php" type="button" class="btn btn-primary">'.get_vocab("back").'</a>';
$reg_data = isset($_POST["reg_data"]) ? $_POST["reg_data"] : NULL;
$is_posted = isset($_POST["is_posted"]) ? $_POST["is_posted"] : NULL;
$test_login_existant = '';
$test_nom_prenom_existant = '';
$test_login = '';
if ($reg_data != 'yes')
{
// $long_max : doit être plus grand que la plus grande ligne trouvée dans le fichier CSV
    $long_max = 8000;
    if ($is_posted != '1') //page initiale, demande le fichier csv
    {
        echo '<form enctype="multipart/form-data" action="admin_import_users_csv.php" method="post" >';
		$csvfile="";
		echo '<p>';
		echo get_vocab("admin_import_users_csv0");
		echo '<input type="file" accept=".csv" name="csvfile" />';
		echo '</p>';
		echo '<div>';
		echo '<input type="hidden" name="is_posted" value="1" />';
		echo '<p>';
		echo get_vocab("admin_import_users_csv1");
		echo '<input type="checkbox" name="en_tete" value="yes" checked="checked" />';
		echo '</p>';
		echo '<input type="submit" value="'.get_vocab("submit").'" />';
		echo '<br />';
		echo '</div></form>';
        echo '<div>';
        echo get_vocab("admin_import_users_csv2");
        echo get_vocab("admin_import_users_csv3")."</div>";
    }
    if ($is_posted == '1') // deuxième et troisième phases
    {
		$valid = isset($_POST["valid"]) ? $_POST["valid"] : NULL;
		$en_tete = isset($_POST["en_tete"]) ? $_POST["en_tete"] : NULL;
		$csv_file = isset($_FILES["csvfile"]) ? $_FILES["csvfile"] : NULL;
		echo "<form enctype=\"multipart/form-data\" action=\"admin_import_users_csv.php\" method=\"post\" >";
		if ($csv_file['tmp_name'] != "")
		{
            $split = explode('.', $csv_file['name']);
            /* Test pour bloquer les doubles extensions et vérifier qu'on a bien un csv */
            if ((count($split) > 2)||(strtolower(end($split)) != 'csv')) {
                echo get_vocab("admin_import_users_csv4")."</form>";
            }
            else 
            {
                $fp = @fopen($csv_file['tmp_name'], "r");
                if (!$fp)
                    echo get_vocab("admin_import_users_csv4")."</form>";
                else // lecture des données
                {
                    $row = 0;
                    echo "<table class='table table-bordered table-condensed'>
                    <tr>
                        <td>".get_vocab("login")."</td>
                        <td>".get_vocab("name")."</td>
                        <td>".get_vocab("first_name")."</td>
                        <td>".get_vocab("pwd")."</td>
                        <td>".get_vocab("email")."</td>
                        <td>".get_vocab("type")."</td>
                        <td>".get_vocab("statut")."</td>
                        <td>".get_vocab("authentification")."</td>
                        <td>".get_vocab("pwd_change")."</td>
                    </tr>";
                    $valid = 1;
                    while (!feof($fp))
                    {
                        if ($en_tete == 'yes')
                        {
                            $data = fgetcsv($fp, $long_max, ";");
                            $en_tete = 'no';
                        }
                        $data = fgetcsv($fp, $long_max, ";");
                        if($data != "")
                            $num = count($data);
                        else
                            $num = 0;
                        if ($num == 9)
                        {
                            $row++;
                            echo "<tr>";
                            for ($c = 0; $c < $num; $c++)
                            {
                                switch ($c)
                                {
                                    case 0:
                                    //login
                                    $test_login = preg_replace("/([A-Za-z0-9_@. -])/","",$data[$c]);
                                    if ($test_login=="")
                                    {
                                        $data[$c] = strtoupper($data[$c]);
                                        $test = grr_sql_count(grr_sql_query("SELECT login FROM ".TABLE_PREFIX."_utilisateurs WHERE login='$data[$c]'"));
                                        if ($test!='0')
                                        {
                                            echo "<td><font color=\"red\">$data[$c]</font>\n";
                                            echo "<input type=\"hidden\" name=\"reg_stat[$row]\" value=\"existant\" />\n";
                                            $test_login_existant = "oui";
                                            $login_exist = "oui";
                                            $login_valeur = $data[$c];
                                        }
                                        else
                                        {
                                            echo "<td>$data[$c]\n";
                                            echo "<input type=\"hidden\" name=\"reg_stat[$row]\" value=\"nouveau\" />\n";
                                            $login_exist = "non";
                                        }
                                        $data_login = htmlentities($data[$c]);
                                        echo "<input type=\"hidden\" name=\"reg_login[$row]\" value=\"$data_login\" /></td>\n";
                                    }
                                    else
                                    {
                                        echo "<td><font color=\"red\">???</font></td>\n";
                                        $valid = 0;
                                    }
                                    break;
                                    case 1:
                                    //Nom
                                    $test_nom_prenom_existant = 'no';
                                    if (preg_match ("`^.{1,30}$`", $data[$c]))
                                    {
                                        $test_nom = protect_data_sql($data[$c]);
                                        $test_prenom = protect_data_sql($data[$c+1]);
                                        $test_nom_prenom = grr_sql_count(grr_sql_query("SELECT nom FROM ".TABLE_PREFIX."_utilisateurs WHERE (nom='$test_nom' and prenom = '$test_prenom')"));
                                        if ($test_nom_prenom != '0')
                                        {
                                            $test_nom_prenom_existant = 'yes';
                                            echo "<td><font color=\"blue\">$data[$c]</font>";
                                        }
                                        else
                                            echo "<td>$data[$c]";
                                        $data_nom = htmlentities($data[$c]);
                                        echo "<input type=\"hidden\" name=\"reg_nom[$row]\" value=\"$data_nom\" /></td>";
                                    }
                                    else
                                    {
                                        echo "<td><font color=\"red\">???</font></td>";
                                        $valid = 0;
                                    }
                                    break;
                                    case 2:
                                    //Prenom
                                    if (preg_match ("`^.{1,30}$`", $data[$c]))
                                    {
                                        if ($test_nom_prenom_existant == 'yes')
                                            echo "<td><font color=\"blue\">$data[$c]</font>";
                                        else 
                                            echo "<td>$data[$c]";
                                        $data_prenom = htmlentities($data[$c]);
                                        echo "<input type=\"hidden\" name=\"reg_prenom[$row]\" value=\"$data_prenom\" /></td>";
                                    }
                                    else
                                    {
                                        echo "<td><font color=\"red\">???</font></td>";
                                        $valid = 0;
                                    }
                                    break;
                                    case 3:
                                    // Mot de passe
                                    if ((preg_match ("`^.{".$pass_leng.",30}$`", $data[$c])) || ($data[$c] == ''))
                                    {
                                        $data_mdp = htmlentities($data[$c]);
                                        echo "<td>$data[$c]";
                                        echo "<input type=\"hidden\" name=\"reg_mdp[$row]\" value=\"$data_mdp\" /></td>";
                                    }
                                    else
                                    {
                                        echo "<td><font color=\"red\">???</font></td>";
                                        $valid = 0;
                                    }
                                    break;
                                    case 4:
                                    // Adresse E-mail
                                    $masque = "/^[_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-]+)*@([a-zA-Z0-9-]+\.)+[a-zA-Z]{2,4}$/";
                                    if ((preg_match ($masque, $data[$c])) || ($data[$c] ==''))
                                    {
                                        $data_email = htmlentities($data[$c]);
                                        echo "<td>$data[$c]";
                                        echo "<input type=\"hidden\" name=\"reg_email[$row]\" value=\"$data_email\" /></td>";
                                    }
                                    else if ($data[$c]=='-')
                                    {
                                        echo "<td><font color=\"red\">???</font>";
                                        echo "<input type=\"hidden\" name=\"reg_email[$row]\" value=\"\" /></td>";
                                    }
                                    else
                                    {
                                        echo "<td><font color=\"red\">???</font></td>";
                                        $valid = 0;
                                    }
                                    break;
                                    case 5:
                                    // Type d'utilisateur : quatre valeurs autorisées : visiteur, utilisateur, administrateur, gestionnaire_utilisateur
                                    // Si c'est un gestionnaire d'utilisateurs qui importe, seuls les types visiteur et utilisateur sont autorisés
                                    if (authGetUserLevel(getUserName(), -1) >= 6)
                                        $filtre = "(\bvisiteur\b|\butilisateur\b|\badministrateur\b|\bgestionnaire_utilisateur\b)";
                                    else
                                        $filtre = "`(\bvisiteur\b|\butilisateur\b)`";

                                    if (preg_match ($filtre, $data[$c]))
                                    {
                                        $data_type_user = htmlentities($data[$c]);
                                        echo "<td>$data[$c]";
                                        echo "<input type=\"hidden\" name=\"reg_type_user[$row]\" value=\"$data_type_user\" /></td>";
                                    }
                                    else
                                    {
                                        echo "<td><font color=\"red\">???</font></td>";
                                        $valid = 0;
                                    }
                                    break;
                                    case 6:
                                    // statut: deux valeurs autorisées : actif ou inactif
                                    if (preg_match ("`(\bactif\b|\binactif\b)`", $data[$c]))
                                    {
                                        $data_statut = htmlentities($data[$c]);
                                        echo "<td>$data[$c]";
                                        echo "<input type=\"hidden\" name=\"reg_statut[$row]\" value=\"$data_statut\" /></td>";
                                    } 
                                    else {
                                        echo "<td><font color=\"red\">???</font></td>";
                                        $valid = 0;
                                    }
                                    break;
                                    case 7:
                                    // Type d'authentification : deux valeurs autorisées : local ou ext
                                    if (preg_match ("`(\blocal\b|\bext\b)`", $data[$c])) {
                                        $data_type_auth = htmlentities($data[$c]);
                                        if (($data_mdp == "") && ($data_type_auth == "local"))
                                        {
                                            echo "<td><font color=\"red\">local -> mot de passe incorrect</font></td>";
                                            $valid = 0;
                                        }
                                        else if (($data_mdp != "") && ($data_type_auth == "ext"))
                                        {
                                            echo "<td><font color=\"red\">ext -> mot de passe incorrect</font></td>";
                                            $valid = 0;
                                        }
                                        else
                                        {
                                            echo "<td>$data[$c]";
                                            echo "<input type=\"hidden\" name=\"reg_type_auth[$row]\" value=\"$data_type_auth\" /></td>";
                                        }
                                    }
                                    else {
                                        echo "<td><font color=\"red\">???</font></td>";
                                        $valid = 0;
                                    }
                                    break;
                                    case 8:
                                    // Doit changer mot de passe à la connexion: deux valeurs autorisées : 1 (oui) ou 0 (non)changerpwd
                                    if (preg_match("(\b0\b|\b1\b)", $data[$c])){
                                        echo "<td>$data[$c]";
                                        echo "<input type=\"hidden\" name=\"reg_changer_pwd[$row]\" value=\"$data[$c]\" /></td>";
                                    }
                                    else {
                                        echo "<td><font color=\"red\">???</font></td>";
                                        $valid = 0;
                                    }
                                    break;
                                }
                            }
                            echo "</tr>";
                        }
                    // pas de message si le nombre de données n'est pas correct, i.e. 9 ?
                    }
                    echo "</table>";
                    fclose($fp);
                    echo "<p>".get_vocab("admin_import_users_csv5")."$row ".get_vocab("admin_import_users_csv6")."</p>\n";
                    if ($row > 0)
                    {
                        if ($test_login_existant == "oui")
                            echo get_vocab("admin_import_users_csv7");
                        if ($test_nom_prenom_existant == 'yes')
                            echo get_vocab("admin_import_users_csv8");
                        if ($valid == '1')
                        {
                            echo "<div><input type=\"submit\" value=\"".get_vocab("submit")."\" />\n";
                            echo "<input type=\"hidden\" name=\"nb_row\" value=\"$row\" />\n";
                            echo "<input type=\"hidden\" name=\"reg_data\" value=\"yes\" />\n";
                            echo "</div></form>";
                        }
                        else
                        {
                            echo get_vocab("admin_import_users_csv9");
                            echo "</form>";
                        }
                    }
                    else
                        echo "<p>".get_vocab("admin_import_users_csv10")."</p></form>";
                }
            }
        }
        else
            echo "<p>".get_vocab("admin_import_users_csv11")."</p></form>";
    }
}
else
{
    // Restriction dans le cas d'une démo
    VerifyModeDemo();
    // Phase d'enregistrement des données
    $nb_row = isset($_POST["nb_row"]) ? $_POST["nb_row"] : NULL;
    $reg_stat = isset($_POST["reg_stat"]) ? $_POST["reg_stat"] : NULL;
    $reg_login = isset($_POST["reg_login"]) ? $_POST["reg_login"] : NULL;
    $reg_nom = isset($_POST["reg_nom"]) ? $_POST["reg_nom"] : NULL;
    $reg_prenom = isset($_POST["reg_prenom"]) ? $_POST["reg_prenom"] : NULL;
    $reg_email = isset($_POST["reg_email"]) ? $_POST["reg_email"] : NULL;
    $reg_mdp = isset($_POST["reg_mdp"]) ? $_POST["reg_mdp"] : NULL;
    $reg_type_user = isset($_POST["reg_type_user"]) ? $_POST["reg_type_user"] : NULL;
    $reg_statut = isset($_POST["reg_statut"]) ? $_POST["reg_statut"] : NULL;
    $reg_type_auth = isset($_POST["reg_type_auth"]) ? $_POST["reg_type_auth"] : NULL;
    $reg_changer_pwd = isset($_POST["reg_changer_pwd"]) ? $_POST["reg_changer_pwd"] : NULL;
    $nb_row++;
    for ($row = 1; $row < $nb_row; $row++)
    {
        if ($reg_type_auth[$row] != "ext")
            $reg_mdp[$row] = md5(unslashes($reg_mdp[$row]));
        // On nettoie les windozeries
        $reg_nom[$row] = protect_data_sql(corriger_caracteres($reg_nom[$row]));
        $reg_prenom[$row] = protect_data_sql(corriger_caracteres($reg_prenom[$row]));
        $reg_email[$row] = protect_data_sql(corriger_caracteres($reg_email[$row]));
        $test_login = grr_sql_count(grr_sql_query("SELECT login FROM ".TABLE_PREFIX."_utilisateurs WHERE login='$reg_login[$row]'"));
        if ($test_login == 0)
            $regdata = grr_sql_query("INSERT INTO ".TABLE_PREFIX."_utilisateurs SET nom='".$reg_nom[$row]."',prenom='".$reg_prenom[$row]."',login='".$reg_login[$row]."',email='".$reg_email[$row]."',password='".protect_data_sql($reg_mdp[$row])."',statut='".$reg_type_user[$row]."',etat='".$reg_statut[$row]."',source='".$reg_type_auth[$row]."',changepwd='".$reg_changer_pwd[$row]."'");
        else
            $regdata = grr_sql_query("UPDATE ".TABLE_PREFIX."_utilisateurs SET nom='".$reg_nom[$row]."',prenom='".$reg_prenom[$row]."',email='".$reg_email[$row]."',password='".protect_data_sql($reg_mdp[$row])."',statut='".$reg_type_user[$row]."',etat='".$reg_statut[$row]."',source='".$reg_type_auth[$row]."',changepwd='".$reg_changer_pwd[$row]."' WHERE login='".$reg_login[$row]."'");
        if (!$regdata)
            echo "<p><font color=\"red\">".$reg_login[$row].get_vocab("deux_points").get_vocab("message_records_error")."</font></p>";
        else
        {
            if ($reg_stat[$row] == "nouveau")
                echo "<p>".$reg_login[$row].get_vocab("deux_points").get_vocab("admin_import_users_csv12")."</p>";
            else
                echo "<p>".$reg_login[$row].get_vocab("deux_points").get_vocab("message_records")."</p>";
        }
    }
}
// fin de la colonne droite
echo "</div>";
// et de la page
end_page();
?>