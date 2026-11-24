<?php

/**
 * OnHandleRequest handler — finds localization from request.
 *
 * Full pages resolve the language from the URL. connector.php (pdoPage ajax,
 * package connectors) already resolved it in OnMODXInit. A second
 * findLocalization() here sees an empty or assets `q` and would switch back
 * to the root language.
 *
 * @var \MODX\Revolution\modX $modx
 * @var localizator $localizator
 */

if ($modx->context->key == 'mgr' || !$modx->getOption('friendly_urls')) {
    return;
}

$script = (string) ($_SERVER['SCRIPT_NAME'] ?? $_SERVER['SCRIPT_FILENAME'] ?? '');
if (stripos($script, 'connector.php') !== false) {
    return;
}

$q_var = $modx->getOption('request_param_alias', null, 'q');
$request = $_REQUEST[$q_var] ?? '';
$localizator->findLocalization($_SERVER['HTTP_HOST'] ?? '', $request);
