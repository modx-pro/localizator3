<?php

/**
 * Smoke: connector language sync (cultureKey from cookie).
 *
 * Run from site root or: php Extras/localizator3/_build/test_connector_language.php
 */

define('MODX_API_MODE', true);
define('MODX_REQP', false);

$_REQUEST['ctx'] = 'web';
$_SERVER['HTTP_HOST'] = 'project.test';
$_SERVER['SCRIPT_NAME'] = '/assets/components/msquote/connector.php';
$_COOKIE['localizator3_key'] = 'en';
unset($_SERVER['HTTP_REFERER'], $_SERVER['HTTP_X_REQUESTED_WITH']);

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
$ok = ($culture === 'en' && $loc === 'en');

echo $ok ? "OK cultureKey=en localizator3_key=en\n" : "FAIL cultureKey={$culture} localizator3_key={$loc}\n";
exit($ok ? 0 : 1);
