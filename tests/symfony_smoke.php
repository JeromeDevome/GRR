<?php

use Devome\Grr\Kernel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

$projectDir = dirname(__DIR__);
$_SERVER['APP_SECRET'] = 'test-secret';
require $projectDir.'/vendor/autoload.php';

$kernel = new Kernel('test', true);
$request = Request::create('/_symfony');
$response = $kernel->handle($request);
$translator = $kernel->getContainer()->get('translator');

if ($response->getStatusCode() !== Response::HTTP_OK) {
    throw new RuntimeException(sprintf('Expected HTTP %d, received %d.', Response::HTTP_OK, $response->getStatusCode()));
}

foreach ([
    'fr-fr' => 'Ressources',
    'en-gb' => 'Resources',
    'de-de' => 'Ressourcen',
    'es-es' => 'Recursos',
    'it-it' => 'Risorse',
] as $locale => $expectedTranslation) {
    if ($translator->trans('rooms', [], 'messages', $locale) !== $expectedTranslation) {
        throw new RuntimeException(sprintf('Symfony did not load the expected "rooms" translation for locale "%s".', $locale));
    }
}

if ($translator->trans('deux_points', [], 'messages', 'fr-fr') !== ' : '
    || $translator->trans('pwd_too_short', ['%pass_leng%' => '8'], 'messages', 'fr-fr') !== 'Mot de passe (8 caractères minimum)'
) {
    throw new RuntimeException('Symfony did not load the expected legacy-backed translation catalogues.');
}
foreach (['fr-fr', 'en-gb', 'de-de', 'es-es', 'it-it'] as $locale) {
    if ($translator->trans('pwa_install_title', [], 'messages', $locale) === 'pwa_install_title') {
        throw new RuntimeException(sprintf('Symfony did not load the installation prompt translation for "%s".', $locale));
    }
}

if (strpos($response->getContent(), 'Socle Symfony et Twig') === false) {
    throw new RuntimeException('The Symfony route did not render its Twig template.');
}

$routes = $kernel->getContainer()->get('router')->getRouteCollection();

foreach (['symfony_legacy_page', 'symfony_legacy_page_subdirectory'] as $routeName) {
    if ($routes->get($routeName) === null) {
        throw new RuntimeException(sprintf('The Symfony legacy page route "%s" is missing.', $routeName));
    }

    if ($routes->get($routeName)->getMethods() !== []) {
        throw new RuntimeException(sprintf('The Symfony legacy page route "%s" does not accept every HTTP method.', $routeName));
    }
}

$router = $kernel->getContainer()->get('router');
foreach ([
    '/app.php' => 'symfony_legacy_page',
    '/GitHub/GRR/app.php' => 'symfony_legacy_page_subdirectory',
    '/compte/compte.php' => 'symfony_legacy_account',
    '/GitHub/GRR/compte/compte.php' => 'symfony_legacy_account_subdirectory',
    '/reservation/controleurs/semaine.php' => 'symfony_legacy_reservation_controller',
    '/GitHub/GRR/reservation/controleurs/semaine.php' => 'symfony_legacy_reservation_controller_subdirectory',
    '/reservation/ajax/captcha.inc.php' => 'symfony_reservation_ajax',
    '/jour' => 'grr_page',
    '/report' => 'grr_page',
    '/GitHub/GRR/semaine_all' => 'grr_page_subdirectory',
    '/GitHub/GRR/reservation/ajax/captcha.inc.php' => 'symfony_reservation_ajax_subdirectory',
] as $path => $routeName) {
    if ($router->match($path)['_route'] !== $routeName) {
        throw new RuntimeException(sprintf('The Symfony legacy page route did not match "%s".', $path));
    }
}

$testContainer = $kernel->getContainer()->get('test.service_container');
$symfonyTwig = $testContainer->get('twig');
if ($symfonyTwig->isStrictVariables()) {
    throw new RuntimeException('Symfony Twig must preserve optional variables in GRR templates.');
}
$installPromptMarkup = $symfonyTwig->render('_pwa_install.twig');
if (strpos($installPromptMarkup, 'Installer GRR') === false
    || strpos($installPromptMarkup, 'Windows (Chrome ou Edge)') === false
    || strpos($installPromptMarkup, 'Android (Chrome)') === false
) {
    throw new RuntimeException('The GRR install prompt does not render its platform instructions.');
}

$legacyPageController = $testContainer->get(\Devome\Grr\Controller\LegacyPageController::class);
$unknownPageResponse = $legacyPageController->index(Request::create('/app.php?p=unknown'));
if ($unknownPageResponse->getStatusCode() !== Response::HTTP_FOUND) {
    throw new RuntimeException('Unknown GRR pages must redirect instead of dispatching a legacy controller script.');
}
$legacyAccountResponse = $legacyPageController->legacyAccount(
    Request::create('/GitHub/GRR/compte/compte.php?pc=moncompte'),
    'compte.php',
    'GitHub/GRR'
);
$legacyAccountTarget = Request::create('http://localhost'.$legacyAccountResponse->headers->get('Location'));
if ($legacyAccountResponse->getStatusCode() !== Response::HTTP_FOUND
    || $legacyAccountTarget->getPathInfo() !== '/GitHub/GRR/account'
    || $legacyAccountTarget->query->get('pc') !== 'moncompte'
) {
    throw new RuntimeException('The legacy account URL did not redirect to the Symfony account page.');
}

$legacyListsResponse = $legacyPageController->legacyAccount(
    Request::create('/GitHub/GRR/compte/my_account_modif_listes.php?type=ressource&action=2&default_room=-1&id_area=1'),
    'my_account_modif_listes.php',
    'GitHub/GRR'
);
$legacyListsTarget = Request::create('http://localhost'.$legacyListsResponse->headers->get('Location'));
if ($legacyListsTarget->getPathInfo() !== '/GitHub/GRR/account'
    || $legacyListsTarget->query->get('legacy_lists') !== '1'
    || $legacyListsTarget->query->get('action') !== '2'
) {
    throw new RuntimeException('The legacy account list URL did not preserve its list action.');
}

$legacyReservationController = $testContainer->get(\Devome\Grr\Controller\LegacyReservationController::class);
$legacyReservationResponse = $legacyReservationController->controller(
    Request::create('/GitHub/GRR/reservation/controleurs/semaine.php?room=11', 'POST'),
    'semaine',
    'GitHub/GRR',
);
$legacyReservationTarget = Request::create('http://localhost'.$legacyReservationResponse->headers->get('Location'));
if ($legacyReservationResponse->getStatusCode() !== Response::HTTP_TEMPORARY_REDIRECT
    || $legacyReservationTarget->getPathInfo() !== '/GitHub/GRR/semaine'
    || $legacyReservationTarget->query->get('room') !== '11'
) {
    throw new RuntimeException('The legacy reservation URL did not preserve its POST request and query.');
}

// Routes propres : app.php?p=<page> redirige en GET, est servi directement en POST
$cleanRedirect = $legacyPageController->index(Request::create('/GitHub/GRR/app.php?p=jour&area=1&day=12'), 'GitHub/GRR');
if ($cleanRedirect->getStatusCode() !== Response::HTTP_FOUND
    || $cleanRedirect->headers->get('Location') !== '/GitHub/GRR/jour?area=1&day=12'
) {
    throw new RuntimeException('app.php?p=<page> must redirect GET requests to the clean page URL.');
}
try {
    $router->match('/inconnue');
    throw new RuntimeException('Unknown clean page URLs must not match the page route.');
} catch (\Symfony\Component\Routing\Exception\ResourceNotFoundException) {
}
// Le .htaccess doit réécrire exactement les pages déclarées
$htaccess = (string) file_get_contents($projectDir.'/.htaccess');
if (!str_contains($htaccess, 'RewriteRule ^(?:'.\Devome\Grr\Routing\Pages::ROUTE_REQUIREMENT.')$ public/index.php [QSA,L]')) {
    throw new RuntimeException('The .htaccess clean page rewrite rule is out of sync with Devome\Grr\Routing\Pages.');
}
$ajaxUrl = $router->generate('symfony_reservation_ajax', ['action' => 'captcha.inc.php']);
if ($ajaxUrl !== '/reservation/ajax/captcha.inc.php') {
    throw new RuntimeException('Symfony did not generate the expected reservation AJAX endpoint URL.');
}

$legacyTemplates = glob($projectDir.'/templates/reservation/*.twig');
if ($legacyTemplates === false) {
    throw new RuntimeException('Unable to list the Symfony reservation Twig templates.');
}

foreach ($legacyTemplates as $templatePath) {
    $templateName = basename($templatePath);
    if (!$symfonyTwig->getLoader()->exists($templateName)) {
        throw new RuntimeException(sprintf('Symfony Twig cannot find the reservation template "%s".', $templateName));
    }
}

$manifestPath = $projectDir.'/manifest.webmanifest';
$manifest = json_decode((string) file_get_contents($manifestPath), true, 512, JSON_THROW_ON_ERROR);
if (($manifest['start_url'] ?? null) !== './app.php' || ($manifest['display'] ?? null) !== 'standalone') {
    throw new RuntimeException('The GRR web app manifest is missing its standalone entry point.');
}
foreach ($manifest['icons'] ?? [] as $icon) {
    $iconPath = $projectDir.'/'.($icon['src'] ?? '');
    $dimensions = is_file($iconPath) ? getimagesize($iconPath) : false;
    if ($dimensions === false || $dimensions[0] !== $dimensions[1] || $dimensions[0] !== (int) strtok((string) ($icon['sizes'] ?? ''), 'x')) {
        throw new RuntimeException(sprintf('The GRR web app icon "%s" is missing or has the wrong dimensions.', $iconPath));
    }
}
if (!is_file($projectDir.'/service-worker.js') || !is_file($projectDir.'/jslib/_grr/pwa-install.js')) {
    throw new RuntimeException('The GRR install prompt or service worker asset is missing.');
}

$accountTemplates = glob($projectDir.'/templates/account/*.twig');
if ($accountTemplates === false) {
    throw new RuntimeException('Unable to list the account Twig templates.');
}
foreach ($accountTemplates as $templatePath) {
    $templateName = '@account/'.basename($templatePath);
    if (!$symfonyTwig->getLoader()->exists($templateName)) {
        throw new RuntimeException(sprintf('Symfony Twig cannot find the account template "%s".', $templateName));
    }
}

if (is_dir($projectDir.'/compte')) {
    throw new RuntimeException('The legacy account directory still exists.');
}

if (is_dir($projectDir.'/reservation')) {
    throw new RuntimeException('The legacy reservation directory still exists.');
}

if (!class_exists(\Devome\Grr\Service\AccountReservations::class)) {
    throw new RuntimeException('The Symfony account reservation service is not autoloadable.');
}
if (!class_exists(\Devome\Grr\Service\Annee::class)) {
    throw new RuntimeException('The Symfony year-planning service is not autoloadable.');
}

$testContainer->get(\Devome\Grr\Controller\AccountController::class);
$testContainer->get(\Devome\Grr\Controller\ReservationAjaxController::class);

$prodKernel = new Kernel('prod', false);
$prodKernel->boot();
$prodRouter = $prodKernel->getContainer()->get('router');
if ($prodRouter->match('/GitHub/GRR/app.php')['_route'] !== 'symfony_legacy_page_subdirectory') {
    throw new RuntimeException('The production Symfony kernel did not match the GRR subdirectory URL.');
}
$prodKernel->shutdown();

$devKernel = new Kernel('dev', true);
$devRequest = Request::create('/_symfony');
$devResponse = $devKernel->handle($devRequest);

if ($devResponse->getStatusCode() !== Response::HTTP_OK || strpos($devResponse->getContent(), 'sfwdt') === false) {
    throw new RuntimeException('The Symfony debug toolbar was not enabled in the dev environment.');
}

$devKernel->terminate($devRequest, $devResponse);

foreach (['index.php', 'app.php', 'installation/install_mysql.php'] as $legacyEntryPoint) {
    if (!is_file($projectDir.'/'.$legacyEntryPoint)) {
        throw new RuntimeException(sprintf('Legacy GRR entry point "%s" is missing.', $legacyEntryPoint));
    }
}

// Traducteur GRR : décore le traducteur Symfony (langue de l'utilisateur, textes personnalisés)
if (!$translator instanceof \Devome\Grr\Service\Translation\GrrTranslator) {
    throw new RuntimeException('The translator service must be decorated by Devome\Grr\Service\Translation\GrrTranslator.');
}
if ($translator->trans('rooms', [], 'messages', 'en-gb') !== 'Resources') {
    throw new RuntimeException('GrrTranslator must keep the requested locale.');
}

// Templates du front : textes par |trans uniquement (plus de variable « trad »), et chaque clé
// traduite doit exister dans le catalogue de référence — sinon la clé brute s'afficherait.
$referenceCatalogue = \Symfony\Component\Yaml\Yaml::parseFile($projectDir.'/translations/messages.fr-fr.yaml');
foreach (array_merge(glob($projectDir.'/templates/reservation/*.twig'), glob($projectDir.'/templates/account/*.twig')) as $template) {
    $source = (string) file_get_contents($template);
    if (preg_match('/\btrad\b/', $source)) {
        throw new RuntimeException(sprintf('Template "%s" still uses the legacy "trad" variable; use |trans.', basename($template)));
    }
    preg_match_all("/'([A-Za-z0-9_]+)'\s*\|\s*trans\b/", $source, $translatedKeys);
    foreach (array_unique($translatedKeys[1]) as $translatedKey) {
        if (!array_key_exists($translatedKey, $referenceCatalogue)) {
            throw new RuntimeException(sprintf('Template "%s" translates "%s", which is missing from messages.fr-fr.yaml.', basename($template), $translatedKey));
        }
    }
}

// Interruption de page legacy (ex-« header(Location); exit; ») transformée en réponse par le noyau
$interruptionEvent = new \Symfony\Component\HttpKernel\Event\ExceptionEvent(
    $kernel,
    Request::create('/jour'),
    \Symfony\Component\HttpKernel\HttpKernelInterface::MAIN_REQUEST,
    \Devome\Grr\Http\PageInterruption::redirect('/GitHub/GRR/login'),
);
(new \Devome\Grr\EventListener\PageInterruptionListener())($interruptionEvent);
$interruptionResponse = $interruptionEvent->getResponse();
if ($interruptionResponse === null
    || $interruptionResponse->getStatusCode() !== Response::HTTP_FOUND
    || $interruptionResponse->headers->get('Location') !== '/GitHub/GRR/login'
) {
    throw new RuntimeException('A PageInterruption must become the redirect response it carries.');
}

$kernel->terminate($request, $response);
