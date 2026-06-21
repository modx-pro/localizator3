<?php

/**
 * OnHandleRequest handler — finds localization from request.
 *
 * @var \MODX\Revolution\modX $modx
 * @var localizator $localizator
 */

if ($modx->context->key == 'mgr' || !$modx->getOption('friendly_urls')) {
    return;
}
$q_var = $modx->getOption('request_param_alias', null, 'q');
$request = $_REQUEST[$q_var] ?? '';
$localizator->findLocalization($_SERVER['HTTP_HOST'] ?? '', $request);
