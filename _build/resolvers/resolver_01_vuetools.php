<?php

/**
 * Resolver: проверка и установка зависимости VueTools ≥ 1.1.2-pl
 *
 * VueTools — обязательная зависимость для Vue-UI Localizator3.
 * Пакет предоставляет Vue 3, Pinia, PrimeVue 4 и Import Map через сервис vuetools.
 */

/** @var xPDOTransport $transport */
/** @var array $options */
/** @var modX $modx */

$success = true;
$packageName = 'localizator3';
$vueToolsPackageName = 'vuetools';
$minVueToolsVersion = '1.1.2-pl';

if ($transport->xpdo) {
    $modx = $transport->xpdo;
}

if (!$modx) {
    return true;
}

$packageClass = class_exists(\MODX\Revolution\Transport\modTransportPackage::class)
    ? \MODX\Revolution\Transport\modTransportPackage::class
    : 'transport.modTransportPackage';

$modx->log(modX::LOG_LEVEL_INFO, "[{$packageName}] Checking VueTools dependency...");

$vueToolsInstalled = false;
$vueToolsVersion = null;

$criteria = ['package_name' => $vueToolsPackageName];
$package = $modx->getObject($packageClass, $criteria);

if ($package) {
    $vueToolsInstalled = true;
    $vueToolsVersion = $package->get('version_major') . '.' . $package->get('version_minor') . '.' . $package->get('version_patch');
    $release = $package->get('release');
    if ($release) {
        $vueToolsVersion .= '-' . $release;
    }
    $modx->log(modX::LOG_LEVEL_INFO, "[{$packageName}] VueTools found: {$vueToolsVersion}");
}

if ($vueToolsInstalled) {
    $versionParts = explode('.', $vueToolsVersion);
    $major = (int)($versionParts[0] ?? 0);
    $minor = (int)($versionParts[1] ?? 0);
    $patch = (int)($versionParts[2] ?? 0);

    if ($major < 1 || ($major === 1 && $minor < 1) || ($major === 1 && $minor === 1 && $patch < 2)) {
        $modx->log(modX::LOG_LEVEL_WARN, "[{$packageName}] VueTools version {$vueToolsVersion} is older than required {$minVueToolsVersion}. Please upgrade VueTools.");
    } else {
        $modx->log(modX::LOG_LEVEL_INFO, "[{$packageName}] VueTools version OK (≥ {$minVueToolsVersion})");
    }
} else {
    $modx->log(modX::LOG_LEVEL_WARN, "[{$packageName}] VueTools not found. Attempting to install from modstore.pro...");

    try {
        $response = localizator3InstallTransportPackage($modx, $vueToolsPackageName);
        if (is_array($response)) {
            $level = !empty($response['success']) ? modX::LOG_LEVEL_INFO : modX::LOG_LEVEL_ERROR;
            $modx->log($level, "[{$packageName}] " . $response['message']);
            $vueToolsInstalled = !empty($response['success']);
        }
    } catch (\Throwable $e) {
        $modx->log(modX::LOG_LEVEL_ERROR, "[{$packageName}] Error installing VueTools: " . $e->getMessage());
    }
}

if ($modx->lexicon) {
    $modx->lexicon->load($packageName . ':default');
}

if (!$vueToolsInstalled) {
    $modx->log(modX::LOG_LEVEL_WARN, "[{$packageName}] VueTools is required but not installed. Vue UI will show warning message.");
    $modx->log(modX::LOG_LEVEL_WARN, "[{$packageName}] Install manually: https://modstore.pro/packages/utilities/vuetools");
}

return $success;
