<?php
/**
 * @author: Bouteillier Nicolas <http://www.kaizendo.fr>
 * Date: 12/08/15
 * Time: 14:10
 */
/**
 * Load des infos pour twig
 */
if (@file_exists('../include/connect.inc.php')) {
    $racine = '../';
} else {
    $racine = '';
}
require_once $racine.'vendor/autoload.php';
global $twig;
$loader = new Twig_Loader_Filesystem($racine.'src/Main/Resources/Templates/'.$template.'/views/');
/**
 * debug true, and profiler, only for dev env, todo : manage env dev or prod
 */
/*$twig = new Twig_Environment($loader, array(
    'cache' => $racine.'app/cache/',
    'debug' => false,
));*/
$twig = new Twig_Environment($loader, array(
    'cache' => false,
    'debug' => true,
));
$twig->addExtension(new Twig_Extension_Debug());
