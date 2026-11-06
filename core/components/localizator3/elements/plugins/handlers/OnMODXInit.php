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

$include = include($localizator->config['modelPath'] . 'localizator3/plugin.mysql.inc.php');
if (is_array($include)) {
    $processed = array();
    foreach ($include as $class => $map) {
        $mappedClass = $modx->loadClass($class);
        if (!$mappedClass) {
            $mappedClass = $class;
        }
        if (isset($processed[$mappedClass])) {
            continue;
        }
        if (!isset($modx->map[$mappedClass])) {
            continue;
        }
        $processed[$mappedClass] = true;
        // MODX 3: $modx->map is xPDOMap (ArrayAccess); nested
        // $modx->map[$class][$key] = ... does NOT persist (offsetGet
        // returns by value). Merge on a copy, then write back whole map.
        $currentMap = $modx->map[$mappedClass];
        foreach ($map as $key => $values) {
            $current = $currentMap[$key] ?? array();
            $currentMap[$key] = array_merge($current, $values);
        }
        $modx->map[$mappedClass] = $currentMap;
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
