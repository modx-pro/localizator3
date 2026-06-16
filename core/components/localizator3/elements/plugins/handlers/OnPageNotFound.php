<?php

/**
 * OnPageNotFound handler — handles localization URL routing.
 *
 * @var \MODX\Revolution\modX $modx
 * @var localizator $localizator
 */

$localizator_key = $modx->getOption('localizator3_key', null, '');
if ($localizator_key === '') {
    return;
}

$q_var = $modx->getOption('request_param_alias', null, 'q');
$request = &$_REQUEST[$q_var];
if ($request == $localizator_key) {
    $modx->sendRedirect($request . '/', array('responseCode' => 'HTTP/1.1 301 Moved Permanently'));
} elseif (preg_match('/^(' . preg_quote($localizator_key, '/') . ')\//i', $request)) {
    $request = preg_replace('/^' . preg_quote($localizator_key, '/') . '\//', '', $request);
}
$resource_id = (!$request) ? $modx->getOption('site_start', null, 1) : $localizator->findResource($request);
if ($modx->getObject(\MODX\Revolution\modResource::class, ['id' => $resource_id, 'deleted' => 0, 'published' => 1])) {
    $modx->sendForward($resource_id);
}
