<?php
// Including the autoload (you need to composer install in the main directory)
require_once '../vendor/autoload.php';

use Gregwar\Captcha\CaptchaBuilder;

// Creating the captcha instance and setting the phrase in the session to store
// it for check when the form is submitted
$captcha = new CaptchaBuilder;
$_SESSION['captcha'] = $captcha->getPhrase();

// Setting the header to image jpeg because we here render an image
header('Content-Type: image/jpeg');

// Running the actual rendering of the captcha image
$captcha
    ->build()
    ->output()
;
?>
