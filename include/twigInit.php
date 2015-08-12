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
global $loader, $twig;
$loader = new Twig_Loader_Filesystem($racine.'src/Main/Resources/views/');
$twig = new Twig_Environment($loader, array(
    'cache' => $racine.'app/cache/',
    'debug' => true
));