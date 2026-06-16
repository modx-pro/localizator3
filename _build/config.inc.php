<?php

if (!defined('MODX_CORE_PATH')) {
    $path = dirname(__FILE__);
    while (!file_exists($path . '/core/config/config.inc.php') && (strlen($path) > 1)) {
        $path = dirname($path);
    }
    define('MODX_CORE_PATH', $path . '/core/');
}
if (!defined('MODX_BASE_PATH')) {
    define('MODX_BASE_PATH', dirname(MODX_CORE_PATH) . '/');
}
if (!defined('MODX_BASE_URL')) {
    define('MODX_BASE_URL', '/');
}
if (!defined('MODX_ASSETS_PATH')) {
    define('MODX_ASSETS_PATH', MODX_BASE_PATH . 'assets/');
}
if (!defined('MODX_ASSETS_URL')) {
    define('MODX_ASSETS_URL', MODX_BASE_URL . 'assets/');
}

return [
    'name' => 'Localizator3',
    'name_lower' => 'localizator3',
    'version' => '1.0.7',
    'release' => 'beta',
    // Install package to site right after build
    'install' => true,
    // Which elements should be updated on package upgrade
    'update' => [
        'chunks' => false,
        'menus' => true,
        'permission' => false,
        'plugins' => true,
        'policies' => false,
        'policy_templates' => true,
        'settings' => false,
        'snippets' => true,
        'events' => true,
    ],
    // Which elements should be static by default
    'static' => [
        'plugins' => false,
        'snippets' => false,
        'chunks' => false,
    ],
    // Resolvers to add (in order)
    'resolvers' => [
        'extension',
        'setup.modx.com',
        'setup',
        'cleanup_legacy_model',
        'migrations',
        'weblink',
        'policy',
        'pdotools_setting',
        'chunks',
        'upgrade',
        'metrics',
    ],
    // Chunk names for setup options
    'chunks' => ['languages.tpl', 'languages.dropdown.tpl'],
    // Log settings
    'log_level' => !empty($_REQUEST['download']) ? 0 : 3,
    'log_target' => php_sapi_name() === 'cli' ? 'ECHO' : 'HTML',
    // Download transport.zip after build
    'download' => !empty($_REQUEST['download']),
];
