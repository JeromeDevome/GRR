<?php
/**
 * Shared GRR request initialization for Symfony controllers and the legacy entry point.
 *
 * This file is part of the application GRR and is distributed under the
 * GNU General Public License, version 2 or (at your option) any later version.
 */

$projectDir = dirname(__DIR__);
if (!chdir($projectDir)) {
    throw new \RuntimeException(sprintf('Unable to change directory to "%s".', $projectDir));
}

$niveauDossier = 1;
$GLOBALS['niveauDossier'] = $niveauDossier;
$grrVariablesBeforeConfig = array_keys(get_defined_vars());
include $projectDir.'/include/config.inc.php';
// Ce fichier peut être inclus depuis une méthode (GrrApplicationContext::boot()) : on publie en global
// les variables de configuration (dont personnalisation/configperso.inc.php : $gcDossierImg, $gcDossierCss…),
// lues par les fonctions legacy via « global ».
foreach (array_diff_key(get_defined_vars(), array_flip($grrVariablesBeforeConfig)) as $grrConfigName => $grrConfigValue) {
    if ($grrConfigName !== 'grrVariablesBeforeConfig') {
        $GLOBALS[$grrConfigName] = $grrConfigValue;
    }
}
unset($grrVariablesBeforeConfig, $grrConfigName, $grrConfigValue);

ini_set('display_errors', $gDebugPHPresa);
error_reporting(E_ALL);

require_once $projectDir.'/vendor/autoload.php';
require_once $projectDir.'/include/twiggrr.class.php';
require_once $projectDir.'/include/securite.class.php';
require_once $projectDir.'/include/functions.inc.php';

$page = 'login';
if (isset($_GET['p'])) {
    $page = SecuChaine::Alphanumeric($_GET['p']);
}

include $projectDir.'/personnalisation/connect.inc.php';
include $projectDir.'/include/misc.inc.php';
include $projectDir.'/include/'.$dbsys.'.inc.php';
include $projectDir.'/include/mincals.inc.php';
include $projectDir.'/include/mrbs_sql.inc.php';

require_once $projectDir.'/include/settings.class.php';
if (!Settings::load()) {
    throw new \RuntimeException('GRR : chargement des réglages impossible (table _setting).');
}
require_once $projectDir.'/include/session.inc.php';

if ($page !== 'deconnexion') {
    grr_resumeSession();
}

include $projectDir.'/include/language.inc.php';
include $projectDir.'/include/hook.class.php';

// Les interruptions décidées pendant ce démarrage (redirections) sont renvoyées par « return » à
// GrrApplicationContext::boot(), qui les lève depuis sa propre méthode : une exception levée ici, au
// milieu des inclusions legacy, fait planter PHP quand Xdebug est en mode « develop ».
if (verif_version()) {
    return \Devome\Grr\Http\PageInterruption::redirect('./installation/maj.php');
}

include $projectDir.'/verif_auto_grr.php';

$back = '';
if (isset($_SERVER['HTTP_REFERER'])) {
    $back = htmlspecialchars($_SERVER['HTTP_REFERER']);
}

if (getUserName() !== '') {
    $userConnecte = 'with_session';
} else {
    $userConnecte = 'no_session';
}

$day = isset($_POST['day']) ? $_POST['day'] : (isset($_GET['day']) ? intval($_GET['day']) : date('d'));
$month = isset($_POST['month']) ? $_POST['month'] : (isset($_GET['month']) ? intval($_GET['month']) : date('m'));
$year = isset($_POST['year']) ? $_POST['year'] : (isset($_GET['year']) ? intval($_GET['year']) : date('Y'));

$grrRedirection = print_header_twig($day, $month, $year, $userConnecte);
if (is_string($grrRedirection)) {
    // changement de langue, ou profil à compléter
    return \Devome\Grr\Http\PageInterruption::redirect($grrRedirection);
}
unset($grrRedirection);

if (Settings::get('langues_dispo') != '') {
    $d['listeLangues'] = explode(';', Settings::get('langues_dispo'));
} else {
    $d['listeLangues'] = ['fr-fr'];
}
$d['listeLangues'] = array_values(array_filter(array_map('trim', $d['listeLangues'])));
// Nom de chaque langue dans sa propre langue (« Français », « Deutsch »…) pour le sélecteur du bandeau
$d['nomsLangues'] = [];
foreach ($d['listeLangues'] as $codeLangue) {
    $nomLangue = class_exists('Locale') ? (string) \Locale::getDisplayLanguage($codeLangue, $codeLangue) : '';
    if ($nomLangue === '' || $nomLangue === $codeLangue) {
        $nomLangue = get_vocab('langue_'.$codeLangue);
    }
    $d['nomsLangues'][$codeLangue] = mb_strtoupper(mb_substr($nomLangue, 0, 1)).mb_substr($nomLangue, 1);
}

$d['gDay'] = $day;
$d['gMonth'] = $month;
$d['gYear'] = $year;
$d['levelUser'] = SecuAccess::UserLevel(getUserName(), -1);
$d['accesStats'] = SecuAccess::UserSearch(getUserName());
$AllSettings = Settings::getAll();

$d['gNomUser'] = getUserName();
$d['versionGRR'] = $version_grr;
$d['versionCache'] = hash('sha256', $version_grr.Settings::get('tokenpublic'));
$d['liensPerso1'] = liensPerso(1, $d['levelUser']);


if (($GLOBALS['grr_symfony_twig'] ?? null) instanceof \Twig\Environment) {
    // Sous Symfony : même environnement Twig que les contrôleurs (path(), trans, @account…)
    $twig = $GLOBALS['grr_symfony_twig'];
} else {
    $loader = new \Twig\Loader\FilesystemLoader($projectDir.'/templates/reservation');
    $twig = new \Twig\Environment($loader, ['charset']);
    $twig->addExtension(new TwigGRR());
}

if (strpos($back, 'login') !== false && $userConnecte === 'with_session') {
    $valeurPopup = grr_sql_query1("SELECT valeur FROM ".TABLE_PREFIX."_page WHERE nom = 'popup'");
    if ($valeurPopup != '' && $valeurPopup != -1) {
        $d['gPopup'] = grr_sql_query1("SELECT popup FROM ".TABLE_PREFIX."_utilisateurs WHERE login='".getUserName()."'");
    }
}
