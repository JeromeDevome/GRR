<?php
  /**
 * captcha.php
 * Création d'une image captcha pour compléter le formulaire d'envoi de mail demandant une réservation
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2022-08-19 10:50$
 * @author    JeromeB & Yan Naessens
 * @copyright Copyright 2003-2022 Team DEVOME - JeromeB
 * @link      http://www.gnu.org/licenses/licenses.html
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */
$grr_script_name = "captcha.php";

$code_length = 5; // Longueur de la chaîne générée en image
$alphabet = 'abcdefghjklmnpqrstuvwxyz023456789'; // Liste des caractères possibles
$nb_characters = strlen($alphabet)-1; // Nombre de caractères possibles -1
// La variable $code contient la chaîne qui sera insérée dans l'image
$code = '';
for($i = 0; $i < $code_length; ++$i) {
    $code .= $alphabet[rand(0, $nb_characters)];
}
// Construction de l'image, utilise les outils GD
// initialise l'image 
$font_size = 5;
$width=imagefontwidth($font_size)*(2*$code_length+1);
$height=imagefontheight($font_size)*2;
$image = @imagecreatetruecolor($width, $height) or die("Cannot Initialize new GD image stream");
// Création du fond de l'image
for($x = 0; $x < imagesx($image); ++$x) {
    for($y = 0; $y < imagesy($image); ++$y) {
        $vred = rand(100, 150);
        $vgreen = rand(100, 150);
        $vblue = rand(100, 150);
        $color = imagecolorallocate($image, $vred, $vgreen, $vblue);
        imagesetpixel($image, $x, $y, $color);
        imagecolordeallocate($image, $color);
    }
}
// Quelques traits aléatoires
for($i=0; $i < 8; $i++) {
    imagesetthickness($image, rand(1,3));
    $linecolor = imagecolorallocate($image, rand(0, 255), rand(0, 255), rand(0, 255));
    imageline($image, rand(0,$width), 0, rand(0,$width), 30 , $linecolor);
    imagecolordeallocate($image, $linecolor);
}
// Création de la bordure
$color = imagecolorallocate($image, 0, 0, 0);
imagerectangle($image, 0, 0, imagesx($image)-1 , imagesy($image)-1, $color);
imagecolordeallocate($image, $color);
// Insertion du texte
$textcolor1 = imagecolorallocate($image, 0, 0, rand(0,255));
$textcolor2 = imagecolorallocate($image, rand(0,255), 255, 255);
$pas_x = imagefontwidth($font_size);
$pas_y = imagefontheight($font_size);
$x = $pas_x;
for($i = 0; $i < $code_length; ++$i) {
    $y =rand(0,$pas_y);
    $textcolor = (rand() % 2) ? $textcolor1 : $textcolor2;
    imagechar($image, 5, $x, $y, $code[$i], $textcolor);
    $x += 2*$pas_x;
}
// Enregistrement du code en variable de session
session_start();
$_SESSION['captcha'] = $code;

// display image and clean up
header('Content-type: image/png');
imagepng($image);
imagedestroy($image);
?>

