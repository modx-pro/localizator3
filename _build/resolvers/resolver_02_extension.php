<?php

/** @var \xPDO\Transport\xPDOTransport $transport */
/** @var array $options */
/** @var \MODX\Revolution\modX $modx */
if (!$transport->xpdo || !($transport->xpdo instanceof \MODX\Revolution\modX)) {
    return true;
}

$modx = $transport->xpdo;
$action = $options[\xPDO\Transport\xPDOTransport::PACKAGE_ACTION] ?? null;

switch ($action) {
    case \xPDO\Transport\xPDOTransport::ACTION_INSTALL:
    case \xPDO\Transport\xPDOTransport::ACTION_UPGRADE:
        $modx->log(\MODX\Revolution\modX::LOG_LEVEL_INFO, "\n[Localizator3] Installation starting...\n");
        $tStart = microtime(true);
        $modx->log(\MODX\Revolution\modX::LOG_LEVEL_INFO, '== RegisterExtension: migrating');
        $modx->addExtensionPackage('localizator3', '[[++core_path]]components/localizator3/model/', [
            'namespacePrefix' => 'localizator3\\',
        ]);
        $modx->log(\MODX\Revolution\modX::LOG_LEVEL_INFO, '✓ Registered namespace: localizator3');
        $modx->log(\MODX\Revolution\modX::LOG_LEVEL_INFO, '== RegisterExtension: migrated ' . number_format(microtime(true) - $tStart, 4) . 's');
        break;
    case \xPDO\Transport\xPDOTransport::ACTION_UNINSTALL:
        $modx->removeExtensionPackage('localizator3');
        break;
}

return true;
