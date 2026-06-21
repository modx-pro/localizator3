<?php

/**
 * OnMODXInit handler — loads xPDO map and handles AJAX referer localization.
 *
 * @var \MODX\Revolution\modX $modx
 * @var localizator $localizator
 */

$include = include_once($localizator->config['modelPath'] . 'localizator3/plugin.mysql.inc.php');
if (is_array($include)) {
    foreach ($include as $class => $map) {
        if (!isset($modx->map[$class])) {
            $modx->loadClass($class);
        }
        if (isset($modx->map[$class])) {
            foreach ($map as $key => $values) {
                $modx->map[$class][$key] = array_merge($modx->map[$class][$key], $values);
            }
        }
    }
}

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest';
if ($modx->getOption('friendly_urls') && $isAjax && isset($_SERVER['HTTP_REFERER'])) {
    $referer = parse_url($_SERVER['HTTP_REFERER']);
    if (stripos($referer['path'], MODX_MANAGER_URL) === 0) {
        return;
    }
    $request = ltrim($referer['path'] ?? '', '/');
    $localizator->findLocalization($referer['host'] ?? '', $request);
}
