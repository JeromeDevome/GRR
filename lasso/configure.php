<?php
require_once('../include/config.inc.php');
require_once(SPKITLASSO.'/lassospkit_configgen.inc.php');
require_once(SPKITLASSO.'/lassospkit_config.inc.php');
require_once(SPKITLASSO.'/lassospkit_debug.inc.php');
require_once(SPKITLASSO.'/lassospkit_metadata.inc.php');
require_once(SPKITLASSO.'/lassospkit_utils.inc.php');

$configgen = new LassoSPKitConfigUIGen();
$error = null;

if (isset($_POST)) {
    if (isset($_POST['clean'])) {
        unlink(SP_METADATA);
        unlink(PRIVATE_KEY);
        unlink(IDP_METADATA);
    }
    if (isset($_POST['organization']) 
        &&   (isset($_POST['idp_metadata']) 
           || isset($_POST['idp_metadata_url']))) 
    {
        $ret = $configgen->makeConfig(
                $_POST['organization'],
                $_POST['idp_metadata'],
                $_POST['idp_metadata_url'],
                isset($_POST['clear_pkey']),
                $error);

        if ($ret) {
            try {
                $configgen->importConfigFromPost($_POST);
            } catch (Exception $e) {
                lassospkit_showCode($e);
            }
        }
    }
}

print "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";
?>
<html>
<head>
<link type="text/css" rel="stylesheet" href="style.css">
</head>
<body>
<? 
    if ($error) {
?><span class="error"> <em>Error:</em> <? echo $error?></span><?
    }
    $configgen->render_form(); 

?>
</body>
</html>
