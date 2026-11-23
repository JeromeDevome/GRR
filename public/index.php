<?php

use Devome\Grr\AppSecret;
use Devome\Grr\Kernel;
use Symfony\Component\HttpFoundation\Request;

require dirname(__DIR__).'/vendor/autoload.php';

AppSecret::load(dirname(__DIR__));

$environment = $_SERVER['APP_ENV'] ?? $_ENV['APP_ENV'] ?? 'prod';
$debug = filter_var($_SERVER['APP_DEBUG'] ?? $_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOL);

$kernel = new Kernel($environment, $debug);
$request = Request::createFromGlobals();
$projectPath = realpath(dirname(__DIR__));
$documentRoot = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : false;

if ($projectPath !== false && $documentRoot !== false) {
    $projectPath = str_replace('\\', '/', $projectPath);
    $documentRoot = rtrim(str_replace('\\', '/', $documentRoot), '/');
    $documentRootPrefix = $documentRoot.'/';

    if (stripos($projectPath, $documentRootPrefix) === 0) {
        $basePath = substr($projectPath, strlen($documentRoot));
        $requestPath = parse_url($request->getRequestUri(), PHP_URL_PATH);

        if ($basePath !== '' && is_string($requestPath) && ($requestPath === $basePath || strncmp($requestPath, $basePath.'/', strlen($basePath) + 1) === 0)) {
            $request->server->set('SCRIPT_NAME', $basePath.'/index.php');
            $request->server->set('PHP_SELF', $basePath.'/index.php');
        }
    }
}

// Pour le code legacy (traite_grr_url(), liens de langue, chemin de retour…), le script visible est
// app.php à la racine et non ce contrôleur frontal atteint par réécriture. On ne modifie que les
// superglobales, après la création de la Request : le routage Symfony n'est pas affecté.
$legacyScript = preg_replace('#/public/index\.php$#', '/app.php', (string) ($_SERVER['SCRIPT_NAME'] ?? ''), 1, $replaced);
if ($replaced === 1) {
    $_SERVER['SCRIPT_NAME'] = $_SERVER['PHP_SELF'] = $legacyScript;
}

$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
