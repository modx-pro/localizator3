<?php

if (!defined('MODX_CORE_PATH')) {
    if (getenv('MODX_CORE_PATH')) {
        define('MODX_CORE_PATH', rtrim(getenv('MODX_CORE_PATH'), '/') . '/');
    } else {
        $path = dirname(__FILE__);
        while (!file_exists($path . '/core/config/config.inc.php') && strlen($path) > 1) {
            $path = dirname($path);
        }
        if (file_exists($path . '/core/config/config.inc.php')) {
            define('MODX_CORE_PATH', $path . '/core/');
        } else {
            $dir = __DIR__;
            while ($dir && !file_exists($dir . '/config.core.php')) {
                $parent = dirname($dir);
                if ($parent === $dir) {
                    break;
                }
                $dir = $parent;
            }
            if (file_exists($dir . '/config.core.php')) {
                require_once $dir . '/config.core.php';
            } else {
                die('Could not find MODX. Set MODX_CORE_PATH or place the component in the MODX tree.');
            }
        }
    }
}

return [
    'name' => 'Localizator3',
    'name_lower' => 'localizator3',
    'version' => '1.0.10',
    'release' => 'beta',
    'install' => true,
    'update' => [
        'chunks' => false,
        'menus' => true,
        'plugins' => true,
        'settings' => false,
        'snippets' => true,
        'policies' => false,
        'policy_templates' => true,
    ],
    'static' => [
        'plugins' => false,
        'snippets' => false,
        'chunks' => false,
    ],
    'properties_lexicon' => 'localizator3:properties',
    'log_level' => !empty($_REQUEST['download']) ? 0 : 3,
    'log_target' => getenv('BUILD_LOG') ? getenv('BUILD_LOG') : (php_sapi_name() === 'cli' ? 'ECHO' : 'HTML'),
    'download' => !empty($_REQUEST['download']),
];
