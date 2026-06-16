<?php

if (!defined('MODX_BASE_PATH') || !defined('PKG_NAME_LOWER')) {
    $config = require __DIR__ . '/config.inc.php';
    if (!defined('MODX_CORE_PATH')) {
        define('MODX_CORE_PATH', dirname(__DIR__, 2) . '/core/');
    }
    if (!defined('MODX_BASE_PATH')) {
        define('MODX_BASE_PATH', dirname(MODX_CORE_PATH) . '/');
    }
    if (!defined('PKG_NAME_LOWER')) {
        define('PKG_NAME_LOWER', $config['name_lower']);
    }
}

$root = dirname(__DIR__) . '/';
$sources = [
    'root' => $root,
    'build' => $root . '_build/',
    'source_core' => $root . 'core/components/' . PKG_NAME_LOWER,
    'model' => $root . 'core/components/' . PKG_NAME_LOWER . '/model/',
    'schema' => $root . 'core/components/' . PKG_NAME_LOWER . '/model/schema/',
    'xml' => $root . 'core/components/' . PKG_NAME_LOWER . '/model/schema/' . PKG_NAME_LOWER . '.mysql.schema.xml',
];

require_once MODX_CORE_PATH . 'vendor/autoload.php';
require_once $sources['build'] . 'includes/functions.php';

$modx = \MODX\Revolution\modX::getInstance();
$modx->initialize('mgr');
$modx->getService('error', 'error.modError');
$modx->setLogLevel(modX::LOG_LEVEL_INFO);
$modx->setLogTarget('ECHO');
$modx->loadClass('transport.modPackageBuilder', '', false, true);
if (!XPDO_CLI_MODE) {
    echo '<pre>';
}

/** @var xPDOManager $manager */
$manager = $modx->getManager();
/** @var xPDOGenerator $generator */
$generator = $manager->getGenerator();

$baseClassFiles = [
    'localizatorContent.php',
    'localizatorLanguage.php',
    'locOption.php',
    'locProductOption.php',
    'locTemplateVarResource.php',
    'metadata.mysql.php',
];
$modelDir = $sources['model'] . PKG_NAME_LOWER . '/';

$backup = [];
foreach ($baseClassFiles as $file) {
    $path = $modelDir . $file;
    if (file_exists($path)) {
        $content = file_get_contents($path);
        if (strpos($content, '<?php') === 0 && strpos($content, '[+class-') === false) {
            $backup[$file] = $content;
        }
    }
}

rrmdir($modelDir . 'mysql');

$generator->parseSchema($sources['xml'], $sources['model']);

foreach ($backup as $file => $content) {
    file_put_contents($modelDir . $file, $content);
    $modx->log(modX::LOG_LEVEL_INFO, 'Preserved base class: ' . $file);
}

$modx->log(modX::LOG_LEVEL_INFO, 'Model generated.');
if (!XPDO_CLI_MODE) {
    echo '</pre>';
}
