<?php
/**
 * admin_import_users_elycee.php
 * script d'importation d'utilisateurs à partir d'un fichier CSV issu de l'ENT elycée Rhône-Alpes, une variante de Kd'Ecole
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2021-03-13 11:47$
 * @author    JeromeB & Yan Naessens & Laurent Delineau
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
$grr_script_name = "admin_import_users_elycee.php";

include "../include/admin.inc.php";

$back = (isset($_SERVER['HTTP_REFERER']))? htmlspecialchars_decode($_SERVER['HTTP_REFERER'], ENT_QUOTES) : "./admin_accueil.php" ;
if ((authGetUserLevel(getUserName(), -1) < 6) && (authGetUserLevel(getUserName(), -1, 'user') !=  1))
{
	showAccessDenied($back);
	exit();
}
# print the page header
start_page_w_header("", "", "", $type="with_session");
// Affichage de la colonne de gauche
include "admin_col_gauche2.php";
// affichage de la colonne de droite
echo "<div class='col-md-9 col-sm-8 col-xs-12'>";
echo "<h2>Import d'utilisateurs elycée</h2>";
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
	if ($is_posted != '1')
	{
		echo '<form enctype="multipart/form-data" action="admin_import_users_elycee.php" method="post" >';
		$csvfile="";
		echo '<p>';
		echo get_vocab("admin_import_users_csv0");
		echo '<input type="file" accept=".csv" name="csvfile" />';
		echo '</p>';
		echo '<div>';
		echo '<input type="hidden" name="is_posted" value="1" />';
		echo '<p>';
		echo get_vocab("admin_import_users_csv1")."&nbsp;";
		echo '<input type="checkbox" name="en_tete" value="yes" checked="checked" />';
		echo '</p>';
		echo '<input type="submit" value="'.get_vocab("submit").'" />';
		echo '<br /></div></form>';
		echo '<div>';
		echo " le fichier à importer est obtenu par export depuis l'annuaire elycée <br>";
        echo " les champs présents devraient être les suivants :<br>";
        echo "<code>";
        echo "rne;uid;classe;profil;prenom;nom;login;mot de passe;cle de jointure;uid pere;uid mere;uid tuteur1;uid tuteur2;prenom enfant;nom enfant;adresse;code postal;ville;pays";
        echo "</code>";
        echo '</div>';
	}
    if ($is_posted == '1')
    {
        $valid = isset($_POST["valid"]) ? $_POST["valid"] : NULL;
        $en_tete = isset($_POST["en_tete"]) ? $_POST["en_tete"] : NULL;
        $csv_file = isset($_FILES["csvfile"]) ? $_FILES["csvfile"] : NULL;

        echo "<form enctype=\"multipart/form-data\" action=\"admin_import_users_elycee.php\" method=\"post\" >";
        if ($csv_file['tmp_name'] != "")
		{
            $split = explode('.', $csv_file['name']);
            /* Test pour bloquer les doubles extensions et vérifier qu'on a bien un csv */
            if ((count($split) > 2)||(strtolower(end($split)) != 'csv')) {
                echo get_vocab("admin_import_users_csv4")."</form>";
            }
            else {
                $fp = @fopen($csv_file['tmp_name'], "r");
                if (!$fp)
                    echo get_vocab("admin_import_users_csv4")."</form>";
                else
                {
                    $row = 0;
                    echo "<table class='table table-bordered table-condensed'><tr>
                    <td><p>".get_vocab("login")."</p></td>
                    <td><p>".get_vocab("statut")."</p></td>
                    <td><p>".get_vocab("first_name")."</p></td>
                    <td><p>".get_vocab("name")."</p></td>
                    </tr>";
                    $valid = 1;
                    while (!feof($fp))
                    {
                        if ($en_tete == 'yes')
                        {
                            $data = fgetcsv ($fp, $long_max, ";");
                            $en_tete = 'no';
                        }
                        $data = fgetcsv ($fp, $long_max, ";");
                        $num = count ($data);
                        if ($num == 19)
                        {
                            $row++;
                            echo "<tr>";
                            for ($c = 0; $c < $num; $c++)
                            {
                                switch ($c)
                                {
                                    case 1:
                                    //login
                                    $test_login = preg_replace("/([A-Za-z0-9_@. -])/","",$data[$c]);
                                    if ($test_login=="")
                                    {
                                        $data[$c] =    strtoupper($data[$c]);
                                        $test = grr_sql_count(grr_sql_query("SELECT login FROM ".TABLE_PREFIX."_utilisateurs WHERE login='$data[$c]'"));
                                        if ($test!='0')
                                        {
                                            echo "<td><p><font color=\"red\">$data[$c]</font></p>\n";
                                            echo "<input type=\"hidden\" name=\"reg_stat[$row]\" value=\"existant\" />\n";
                                            $test_login_existant = "oui";
                                            $login_exist = "oui";
                                            $login_valeur = $data[$c];
                                        }
                                        else
                                        {
                                            echo "<td><p>$data[$c]</p>\n";
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
                                    case 3:
                                    // profil : élève ou parent => visiteur, enseignant ou administratif => utilisateur
                                    // Type d'utilisateur : quatre valeurs autorisées : visiteur, utilisateur, administrateur, gestionnaire_utilisateur
                                    // Si c'est un gestionnaire d'utilisateurs qui importe, seuls les types visiteur et utilisateur sont autorisés
                                    /* if (authGetUserLevel(getUserName(), -1) >= 6)
                                        $filtre = "(visiteur|utilisateur|administrateur|gestionnaire_utilisateur)";
                                    else
                                        $filtre = "`(visiteur|utilisateur)`";

                                    if (preg_match ($filtre, $data[$c]))
                                    {
                                        $data_type_user = htmlentities($data[$c]);
                                        echo "<td><p>$data[$c] </p>";
                                        echo "<input type=\"hidden\" name=\"reg_type_user[$row]\" value=\"$data_type_user\" /></td>";
                                    }
                                    else
                                    {
                                        echo "<td><font color=\"red\">???</font></td>";
                                        $valid = 0;
                                    } */
                                    // en attendant de filtrer, statut visiteur par défaut
                                        echo "<td><p>visiteur </p>";
                                        echo "<input type=\"hidden\" name=\"reg_type_user[$row]\" value=\"visiteur\" /></td>";
                                    break;
                                    case 4:
                                    //Prenom
                                    if (preg_match ("`^.{1,30}$`", $data[$c]))
                                    {
                                        if ($test_nom_prenom_existant == 'yes')
                                            echo "<td><p><font color=\"blue\">$data[$c]</font></p>";
                                        else 
                                            echo "<td><p>$data[$c]</p>";
                                        $data_prenom = htmlentities($data[$c]);
                                        echo "<input type=\"hidden\" name=\"reg_prenom[$row]\" value=\"$data_prenom\" /></td>";
                                    }
                                    else
                                    {
                                        echo "<td><font color=\"red\">???</font></td>";
                                        $valid = 0;
                                    }
                                    break;
                                    case 5:
                                    //Nom
                                    $test_nom_prenom_existant = 'no';
                                    if (preg_match ("`^.{1,30}$`", $data[$c]))
                                    {
                                        $test_nom = protect_data_sql($data[$c]);
                                        $test_prenom = protect_data_sql($data[$c-1]);
                                        $test_nom_prenom = grr_sql_count(grr_sql_query("SELECT nom FROM ".TABLE_PREFIX."_utilisateurs WHERE (nom='$test_nom' and prenom = '$test_prenom')"));
                                        if ($test_nom_prenom != '0')
                                        {
                                            $test_nom_prenom_existant = 'yes';
                                            echo "<td><p><font color=\"blue\">$data[$c]</font></p>";
                                        }
                                        else
                                            echo "<td><p>$data[$c]</p>";
                                        $data_nom = htmlentities($data[$c]);
                                        echo "<input type=\"hidden\" name=\"reg_nom[$row]\" value=\"$data_nom\" /></td>";
                                    }
                                    else
                                        echo "<td><font color=\"red\">???</font></td>";
                                    break;
                                    default:
                                    // tous les autres champs ne sont pas traités
                                    break;
                                }
                            }
                            echo "</tr>";
                        } // peut-être filtrer si le nombre de données par ligne n'est pas correct
                    }
                    fclose($fp);
                    echo "</table>";
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
    $reg_type_user = isset($_POST["reg_type_user"]) ? $_POST["reg_type_user"] : NULL;
    $reg_statut = isset($_POST["reg_statut"]) ? $_POST["reg_statut"] : NULL;
    $reg_type_auth = isset($_POST["reg_type_auth"]) ? $_POST["reg_type_auth"] : NULL;
    $nb_row++;
    for ($row = 1; $row < $nb_row; $row++)
    {
        // On nettoie les windozeries
        $reg_nom[$row] = protect_data_sql(corriger_caracteres($reg_nom[$row]));
        $reg_prenom[$row] = protect_data_sql(corriger_caracteres($reg_prenom[$row]));
        $test_login = grr_sql_count(grr_sql_query("SELECT login FROM ".TABLE_PREFIX."_utilisateurs WHERE login='$reg_login[$row]'"));
        if ($test_login == 0) {
            $sqlQuery = "INSERT INTO ".TABLE_PREFIX."_utilisateurs SET nom='".$reg_nom[$row]."',prenom='".$reg_prenom[$row]."',login='".$reg_login[$row]."',statut='".$reg_type_user[$row]."',etat='actif',source='ext'";
            echo $sqlQuery;
            $regdata = grr_sql_query($sqlQuery); 
        }
        else {
            $sqlQuery = "UPDATE ".TABLE_PREFIX."_utilisateurs SET nom='".$reg_nom[$row]."',prenom='".$reg_prenom[$row]."',login='".$reg_login[$row]."',statut='".$reg_type_user[$row]."',etat='actif',source='ext'"."WHERE login='".$reg_login[$row]."'";
            echo $sqlQuery;
            $regdata = grr_sql_query($sqlQuery);
        }
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
 echo "</div>"; // fin de la colonne droite
 end_page();
?>
