<?php

/**
 * Resolver: create or update pdoTools setting pdoFetch.class for Localizator integration.
 * Ensures pdoResources/pdoMenu return localized data when used via Localizator snippet.
 * MODX 3 only. Runs on install and upgrade.
 *
 * @var \xPDO\Transport\xPDOTransport $transport
 * @var array $options
 */

if (!$transport->xpdo) {
    return true;
}

$modx = $transport->xpdo;
$settingKey = 'pdoFetch.class';
$settingNamespace = 'pdotools';
$settingValue = 'pdotools.pdofetchlocalizator3';

$action = $options[\xPDO\Transport\xPDOTransport::PACKAGE_ACTION] ?? null;
$isInstallOrUpgrade = $action === \xPDO\Transport\xPDOTransport::ACTION_INSTALL
    || $action === \xPDO\Transport\xPDOTransport::ACTION_UPGRADE;
if (!$isInstallOrUpgrade) {
    return true;
}

$exists = $modx->getObject(\MODX\Revolution\modSystemSetting::class, [
    'key' => $settingKey,
    'namespace' => $settingNamespace,
]);

$tStart = microtime(true);
$modx->log(\MODX\Revolution\modX::LOG_LEVEL_INFO, '== SetupPdoTools: migrating');

if (!$exists) {
    $setting = $modx->newObject(\MODX\Revolution\modSystemSetting::class);
    $setting->set('key', $settingKey);
    $setting->set('namespace', $settingNamespace);
    $setting->set('value', $settingValue);
    $setting->set('xtype', 'textfield');
    $setting->set('area', 'pdotools_main');
    if ($setting->save()) {
        $modx->log(\MODX\Revolution\modX::LOG_LEVEL_INFO, '✓ Created pdoFetch.class in namespace pdotools = ' . $settingValue);
    } else {
        $modx->log(\MODX\Revolution\modX::LOG_LEVEL_WARN, '✗ Failed to create pdoFetch.class. Set manually to "' . $settingValue . '"');
    }
} else {
    $modx->log(\MODX\Revolution\modX::LOG_LEVEL_INFO, '✓ pdoFetch.class already exists in pdotools namespace');
}

$modx->log(\MODX\Revolution\modX::LOG_LEVEL_INFO, '== SetupPdoTools: migrated ' . number_format(microtime(true) - $tStart, 4) . 's');

return true;
