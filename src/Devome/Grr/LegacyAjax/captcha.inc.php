<?php
use Gregwar\Captcha\CaptchaBuilder;

// Creating the captcha instance and setting the phrase in the session to store
// it for check when the form is submitted
$captcha = new CaptchaBuilder;
$_SESSION['phrase'] = $captcha->getPhrase();

// Setting the header to image jpeg because we here render an image
header('Content-Type: image/jpeg');

// Running the actual rendering of the captcha image
$captcha
    ->build()
    ->output()
;
?>
