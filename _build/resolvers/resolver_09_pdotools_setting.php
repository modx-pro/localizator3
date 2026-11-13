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
$settingKey = 'pdotools_fetch_class';
$settingNamespace = 'pdotools';
$settingValue = 'ModxPro\\PdoTools\\FetchLocalizator';

$action = $options[\xPDO\Transport\xPDOTransport::PACKAGE_ACTION] ?? null;
$isInstallOrUpgrade = $action === \xPDO\Transport\xPDOTransport::ACTION_INSTALL
    || $action === \xPDO\Transport\xPDOTransport::ACTION_UPGRADE;
if (!$isInstallOrUpgrade) {
    return true;
}

$setting = $modx->getObject(\MODX\Revolution\modSystemSetting::class, [
    'key' => $settingKey,
    'namespace' => $settingNamespace,
]);

$tStart = microtime(true);
$modx->log(\MODX\Revolution\modX::LOG_LEVEL_INFO, '== SetupPdoTools: migrating');

if (!$setting) {
    $setting = $modx->newObject(\MODX\Revolution\modSystemSetting::class);
    $setting->set('key', $settingKey);
    $setting->set('namespace', $settingNamespace);
    $setting->set('value', $settingValue);
    $setting->set('xtype', 'textfield');
    $setting->set('area', 'pdotools_main');
    if ($setting->save()) {
        $modx->log(\MODX\Revolution\modX::LOG_LEVEL_INFO, '✓ Created ' . $settingKey . ' in namespace pdotools = ' . $settingValue);
    } else {
        $modx->log(\MODX\Revolution\modX::LOG_LEVEL_WARN, '✗ Failed to create ' . $settingKey . '. Set manually to "' . $settingValue . '"');
    }
} else {
    $oldValue = $setting->get('value');
    if ($oldValue !== $settingValue) {
        $setting->set('value', $settingValue);
        if ($setting->save()) {
            $modx->log(\MODX\Revolution\modX::LOG_LEVEL_INFO, '✓ Updated ' . $settingKey . ' from "' . $oldValue . '" to "' . $settingValue . '"');
        } else {
            $modx->log(\MODX\Revolution\modX::LOG_LEVEL_WARN, '✗ Failed to update ' . $settingKey);
        }
    } else {
        $modx->log(\MODX\Revolution\modX::LOG_LEVEL_INFO, '✓ ' . $settingKey . ' already set to "' . $settingValue . '"');
    }
}

$modx->log(\MODX\Revolution\modX::LOG_LEVEL_INFO, '== SetupPdoTools: migrated ' . number_format(microtime(true) - $tStart, 4) . 's');

return true;
