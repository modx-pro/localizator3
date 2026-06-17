<?php

/**
 * Синхронизация snippet-кода из файлов в БД MODX
 *
 * Использование: php sync_snippets.php
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
$snippetsDir = $corePath . 'elements/snippets/';

$snippets = [
    'getLocalizedResources' => 'snippet.localizator.php',
    'getLanguageList' => 'snippet.getlocales.php',
    'makeLocalizedUrl' => 'snippet.makelocalizedurl.php',
    'getLocalizedCanonical' => 'snippet.getlocalizedcanonical.php',
    'getLocalizedSitemap' => 'snippet.getlocalizedsitemap.php',
    'getLocalizedField' => 'snippet.getlocalizedfield.php',
];

foreach ($snippets as $name => $file) {
    $filePath = $snippetsDir . $file;
    if (!file_exists($filePath)) {
        echo "[SKIP] File not found: {$filePath}\n";
        continue;
    }

    $content = file_get_contents($filePath);
    // Remove PHP tags
    $content = preg_replace('#^\s*<\?php\s*#i', '', $content);
    $content = preg_replace('#\?>\s*$#', '', $content);

    $snippet = $modx->getObject('modSnippet', ['name' => $name]);
    if (!$snippet) {
        echo "[CREATE] Snippet {$name}\n";
        $snippet = $modx->newObject('modSnippet');
        $snippet->set('name', $name);
        $snippet->set('category', 0);
    } else {
        echo "[UPDATE] Snippet {$name}\n";
    }

    $snippet->set('snippet', $content);
    $snippet->set('static', false);
    $snippet->save();
}

echo "[DONE] Snippets synchronized\n";
