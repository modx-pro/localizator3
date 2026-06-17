<?php

/**
 * Синхронизация plugin-кода из файлов в БД MODX
 *
 * Использование: php sync_plugins.php
 */

if (!defined('MODX_CORE_PATH')) {
    $path = dirname(__FILE__);
    while (!file_exists($path . '/core/config/config.inc.php') && strlen($path) > 1) {
        $path = dirname($path);
    }
    if (file_exists($path . '/core/config/config.inc.php')) {
        define('MODX_CORE_PATH', $path . '/core/');
    } else {
        die('Could not find MODX');
    }
}

require_once MODX_CORE_PATH . 'model/modx/modx.class.php';

$modx = new modX();
$modx->initialize('mgr');
$modx->getService('error', 'error.modError');

$corePath = MODX_CORE_PATH . 'components/localizator3/';
$pluginsDir = $corePath . 'elements/plugins/';

$plugins = [
    'localizator3' => 'plugin.localizator.php',
];

foreach ($plugins as $name => $file) {
    $filePath = $pluginsDir . $file;
    if (!file_exists($filePath)) {
        echo "[SKIP] File not found: {$filePath}\n";
        continue;
    }

    $content = file_get_contents($filePath);
    // Remove PHP tags
    $content = preg_replace('#^\s*<\?php\s*#i', '', $content);
    $content = preg_replace('#\?>\s*$#', '', $content);

    $plugin = $modx->getObject('modPlugin', ['name' => $name]);
    if (!$plugin) {
        echo "[CREATE] Plugin {$name}\n";
        $plugin = $modx->newObject('modPlugin');
        $plugin->set('name', $name);
        $plugin->set('category', 0);
    } else {
        echo "[UPDATE] Plugin {$name}\n";
    }

    $plugin->set('plugincode', $content);
    $plugin->set('static', false);
    $plugin->save();
}

echo "[DONE] Plugins synchronized\n";
