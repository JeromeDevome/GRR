<?php
/**
 * validation.php
 * Interface de validation d'une réservation modérée
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2024-04-02 19:24$
 * @author    Laurent Delineau & JeromeB & Yan Naessens
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

if (isset($_GET['id']))
	$id= intval($_GET['id']);
else
	$id = 0;

header("Location: app.php?p=vuereservation&id=".$id);

?>