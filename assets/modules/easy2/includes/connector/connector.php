<?php

// harden it
if (!@require_once('../../../../../manager/includes/protect.inc.php'))
    die('Go away!');

// initialize the variables prior to grabbing the config file
$database_type = "";
$database_server = "";
$database_user = "";
$database_password = "";
$dbase = "";
$table_prefix = "";
$base_url = "";
$base_path = "";

// MODx config
if (!@require_once '../../../../../manager/includes/config.inc.php')
    die('Unable to include the MODx\'s config file');

$conn =mysqli_connect($database_server, $database_user, $database_password) or die('mysqli connect error');
mysqli_select_db($conn,str_replace('`', '', $dbase));
@mysqli_query($conn,"{$database_connection_method} {$database_connection_charset}");

// e2g's configs
$q = mysqli_query($conn,'SELECT * FROM ' . $table_prefix . 'easy2_configs');
if (!$q)
    die(__FILE__ . ': mysqli query error for configs');
else {
    while ($row = mysqli_fetch_assoc($q)) {
        $e2g[$row['cfg_key']] = $row['cfg_val'];
    }
}

// initiate a new document parser
$docParserClassFile = realpath('../../../../../manager/includes/document.parser.class.inc.php');
if (empty($docParserClassFile) || !file_exists($docParserClassFile)) {
    die(__FILE__ . ': Missing doc parser class file.');
}
include ($docParserClassFile);
$modx = new DocumentParser;
$modx->getSettings();

// Easy 2 Gallery module path
define('E2G_MODULE_PATH', MODX_BASE_PATH . 'assets/modules/easy2/');
if (version_compare($modx->config['settings_version'], '1.0.12', '>=')) {
    // Easy 2 Gallery module URL
    define('E2G_MODULE_URL', MODX_SITE_URL . 'assets/modules/easy2/');
} else {
    // Easy 2 Gallery module URL
    define('E2G_MODULE_URL', MODX_SITE_URL . '../../');
}

$modClassFile = realpath('../models/e2g.module.class.php');
if (empty ($modClassFile) || !file_exists($modClassFile)) {
    die(__FILE__ . ': Missing module class file.');
}
include ($modClassFile);

$e2gMod = new E2gMod($modx);
$modx->e2gMod = $e2gMod;
// LANGUAGE
$lng = E2gPub::languageSwitch($modx->config['manager_language'],E2G_MODULE_PATH);
if (!is_array($lng)) {
    die($lng); // FALSE returned.
}

foreach ($lng as $k => $v) {
    $lng[$k] = $e2gMod->e2gEncode($v);
}
$modx->e2gMod->lng = $lng;
$getRequests = $e2gMod->sanitizedGets($_GET);
if (empty($getRequests)) {
    die('Request is empty');
}

$output = $e2gMod->handleRequest($getRequests);
echo $output;

exit;