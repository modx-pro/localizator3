<?php

/**
 * Smoke: connector language survives the second OnHandleRequest.
 *
 * pdoPage ajax posts to /assets/components/pdotools/connector.php, which
 * initializes the web context and then invokes OnHandleRequest again.
 * That second call must not replace the language resolved from the Referer.
 *
 * Run from site root or: php Extras/localizator3/_build/test_connector_language.php
 */

define('MODX_API_MODE', true);
define('MODX_REQP', false);

$_REQUEST['ctx'] = 'web';
$_REQUEST['q'] = 'assets/components/pdotools/connector.php';
$_SERVER['HTTP_HOST'] = 'project.test';
$_SERVER['HTTPS'] = 'on';
$_SERVER['SERVER_PORT'] = '443';
$_SERVER['SCRIPT_NAME'] = '/assets/components/pdotools/connector.php';
$_SERVER['HTTP_REFERER'] = 'https://project.test/en/';
$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
$_COOKIE['localizator3_key'] = 'en';

$candidates = [
    dirname(__DIR__, 3) . '/config.core.php', // Sites/project/config.core.php when Extra is in project/Extras/
    dirname(__DIR__, 2) . '/config.core.php',
];
$config = null;
foreach ($candidates as $path) {
    if (is_file($path)) {
        $config = $path;
        break;
    }
}
if ($config === null) {
    fwrite(STDERR, "config.core.php not found\n");
    exit(1);
}

require $config;
require MODX_CORE_PATH . 'vendor/autoload.php';

$modx = new \MODX\Revolution\modX();
$modx->initialize('web');

$culture = (string) $modx->getOption('cultureKey');
$loc = (string) $modx->getOption('localizator3_key');
$base = (string) $modx->getOption('base_url');
$ok = ($culture === 'en' && $loc === 'en' && $base === '/en/');

echo $ok
    ? "OK before OnHandleRequest cultureKey=en localizator3_key=en base_url=/en/\n"
    : "FAIL before OnHandleRequest cultureKey={$culture} localizator3_key={$loc} base_url={$base}\n";

$modx->invokeEvent('OnHandleRequest');

$culture = (string) $modx->getOption('cultureKey');
$loc = (string) $modx->getOption('localizator3_key');
$base = (string) $modx->getOption('base_url');
$kept = ($culture === 'en' && $loc === 'en' && $base === '/en/');
$ok = $ok && $kept;

echo $kept
    ? "OK after OnHandleRequest cultureKey=en localizator3_key=en base_url=/en/\n"
    : "FAIL after OnHandleRequest cultureKey={$culture} localizator3_key={$loc} base_url={$base}\n";

exit($ok ? 0 : 1);
