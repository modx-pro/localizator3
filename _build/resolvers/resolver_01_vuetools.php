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
    return true; // не критично на этапе сборки
}

$modx->log(modX::LOG_LEVEL_INFO, "[{$packageName}] Checking VueTools dependency...");

// Проверяем, установлен ли VueTools
$vueToolsInstalled = false;
$vueToolsVersion = null;

$criteria = array('package_name' => $vueToolsPackageName);
$package = $modx->getObject(\MODX\Revolution\Transport\modTransportPackage::class, $criteria);

if ($package) {
    $vueToolsInstalled = true;
    $vueToolsVersion = $package->get('version_major') . '.' . $package->get('version_minor') . '.' . $package->get('version_patch');
    $release = $package->get('release');
    if ($release) {
        $vueToolsVersion .= '-' . $release;
    }
    $modx->log(modX::LOG_LEVEL_INFO, "[{$packageName}] VueTools found: {$vueToolsVersion}");
}

// Проверяем минимальную версию (упрощённая проверка)
if ($vueToolsInstalled) {
    // Сравниваем версии: требуется ≥ 1.1.2
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

    // Пытаемся скачать и установить VueTools с modstore.pro
    $provider = $modx->getObject(\MODX\Revolution\Transport\modTransportProvider::class, array('service_url:LIKE' => '%modstore.pro%'));

    if (!$provider) {
        $provider = $modx->getObject(\MODX\Revolution\Transport\modTransportProvider::class, array('name' => 'modstore.pro'));
    }

    if (!$provider) {
        $modx->log(modX::LOG_LEVEL_ERROR, "[{$packageName}] ModStore provider not found. Please install VueTools manually from https://modstore.pro/packages/utilities/vuetools");
        $modx->log(modX::LOG_LEVEL_ERROR, "[{$packageName}] Localizator3 Vue UI requires VueTools >= {$minVueToolsVersion}");
    } else {
        try {
            $modx->log(modX::LOG_LEVEL_INFO, "[{$packageName}] Downloading VueTools from modstore.pro...");
            $response = $provider->request('package', array('signature' => $vueToolsPackageName));

            if ($response && $response->response && $response->response->url) {
                $url = $response->response->url;
                $modx->log(modX::LOG_LEVEL_INFO, "[{$packageName}] VueTools download URL: {$url}");

                // Скачиваем и устанавливаем
                $packageManager = $modx->getVersionData();
                $result = $package->downloadAndInstall($url);

                if ($result) {
                    $modx->log(modX::LOG_LEVEL_INFO, "[{$packageName}] VueTools installed successfully");
                    $vueToolsInstalled = true;
                } else {
                    $modx->log(modX::LOG_LEVEL_ERROR, "[{$packageName}] Failed to install VueTools automatically");
                }
            } else {
                $modx->log(modX::LOG_LEVEL_ERROR, "[{$packageName}] VueTools not found on modstore.pro or no download URL");
            }
        } catch (Exception $e) {
            $modx->log(modX::LOG_LEVEL_ERROR, "[{$packageName}] Error installing VueTools: " . $e->getMessage());
        }
    }
}

// Добавляем lexicon entry для сообщения о необходимости VueTools
if ($modx->lexicon) {
    $modx->lexicon->load($packageName . ':default');
}

if (!$vueToolsInstalled) {
    $modx->log(modX::LOG_LEVEL_WARN, "[{$packageName}] VueTools is required but not installed. Vue UI will show warning message.");
    $modx->log(modX::LOG_LEVEL_WARN, "[{$packageName}] Install manually: https://modstore.pro/packages/utilities/vuetools");
}

return $success;
