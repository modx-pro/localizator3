<?php

/**
 * OnMODXInit handler — loads xPDO map and syncs language for AJAX / connectors.
 *
 * Full pages: OnHandleRequest → findLocalization(URL).
 * Connectors: often no `q`, no X-Requested-With — resolve via Referer / cookie
 * so cultureKey matches the storefront language (not only system cultureKey).
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

$ctx = isset($modx->context) ? (string) $modx->context->key : '';
if ($ctx === 'mgr' || !$modx->getOption('friendly_urls')) {
    return;
}

$script = (string) ($_SERVER['SCRIPT_NAME'] ?? $_SERVER['SCRIPT_FILENAME'] ?? '');
$isConnector = stripos($script, 'connector.php') !== false;
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
    && strcasecmp((string) $_SERVER['HTTP_X_REQUESTED_WITH'], 'XMLHttpRequest') === 0;

if ($isAjax || $isConnector) {
    $localizator->resolveConnectorLanguage();
}
