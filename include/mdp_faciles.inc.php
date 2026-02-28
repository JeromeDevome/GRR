<?php
/**
 * mdp_faciles.inc.php
 * Fonctions Général de l'administration
 * Dernière modification : $Date: 2023-02-01 10:00$
 * @author    JeromeB & Yan Naessens
 * @copyright Since 2003 Team DEVOME - JeromeB
 * @link      http://www.gnu.org/licenses/licenses.html
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */

/*$liste_mdp = array("azerty", "", "123456", "1234567", "12345678", "0123456789", "000000", "00000000", "admin","azertyui","azertyuiop","grr","administrateur","administrator");
$mdpFacile = array();

foreach ($liste_mdp as &$value) {
    $mdpFacile[] = password_hash($value, PASSWORD_DEFAULT);
	$mdpFacile[] = md5($value);
}

unset($value);*/
// le hachage d'un mot de passe donné n'est pas constant, il faut utiliser password_verify() pour "décoder", donc laisser le tableau des mots de passe faciles en clair
$mdpFacile = array("azerty", "", "123456", "1234567", "12345678", "0123456789", "000000", "00000000", "admin","azertyui","azertyuiop","grr","administrateur","administrator");
?>