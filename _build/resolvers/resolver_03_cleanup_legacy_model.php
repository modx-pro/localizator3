<?php

/**
 * Removes legacy MODX 2 model files (*.class.php, *.map.inc.php) from mysql/ so xPDO loads only MODX 3 driver classes.
 * MODX 3 only. Runs before tables resolver on install/upgrade.
 *
 * @var \xPDO\Transport\xPDOTransport $transport
 * @var array $options
 */

if (!$transport->xpdo) {
    return true;
}

$action = $options[\xPDO\Transport\xPDOTransport::PACKAGE_ACTION] ?? null;
if ($action !== \xPDO\Transport\xPDOTransport::ACTION_INSTALL && $action !== \xPDO\Transport\xPDOTransport::ACTION_UPGRADE) {
    return true;
}

/** @var \MODX\Revolution\modX $modx */
$modx = $transport->xpdo;
$modelPath = $modx->getOption('core_path') . 'components/localizator3/model/localizator3/';
$mysqlPath = $modelPath . 'mysql/';
$removed = 0;

foreach (glob($mysqlPath . '*.class.php') ?: [] as $file) {
    if (unlink($file)) {
        $removed++;
    }
}
foreach (glob($mysqlPath . '*.map.inc.php') ?: [] as $file) {
    if (unlink($file)) {
        $removed++;
    }
}

$legacyClassFiles = [
    'localizatorcontent.class.php',
    'localizatorlanguage.class.php',
    'locoption.class.php',
    'locproductoption.class.php',
    'loctemplatevarresource.class.php',
];
foreach ($legacyClassFiles as $legacyFile) {
    $fullPath = $modelPath . $legacyFile;
    if (file_exists($fullPath) && unlink($fullPath)) {
        $removed++;
    }
}

if ($removed > 0) {
    $modx->log(\MODX\Revolution\modX::LOG_LEVEL_INFO, '[Localizator3] Removed ' . $removed . ' legacy model files');
}

return true;
