<?php

/**
 * Resolver для отправки анонимной статистики установки
 *
 * Собирает обезличенные технические данные о среде для улучшения совместимости.
 * Не собирает персональные данные, домены, IP-адреса или конфиденциальную информацию.
 */

use MODX\Revolution\modContext;
use MODX\Revolution\Transport\modTransportPackage;
use xPDO\Transport\xPDOTransport;

/** @var xPDOTransport $transport */
/** @var array $options */
/** @var \MODX\Revolution\modX $modx */
if (!$transport->xpdo || !($transport instanceof xPDOTransport)) {
    return true;
}

$modx = $transport->xpdo;

$packageAction = $options[xPDOTransport::PACKAGE_ACTION] ?? null;
if (!in_array($packageAction, [xPDOTransport::ACTION_INSTALL, xPDOTransport::ACTION_UPGRADE], true)) {
    return true;
}

$collectMetrics = function () use ($modx, $options, $transport): array {
    $installType = 'fresh';
    $previousVersion = null;

    /** @var modTransportPackage|null $existingPackage */
    $existingPackage = $modx->getObject(modTransportPackage::class, [
        'package_name' => 'Localizator3',
        'installed:IS NOT' => null,
    ]);

    if ($existingPackage) {
        $installType = 'upgrade';
        $previousVersion = $existingPackage->get('version_major') . '.'
            . $existingPackage->get('version_minor') . '.'
            . $existingPackage->get('version_patch');
        $release = $existingPackage->get('release');
        if ($release) {
            $previousVersion .= '-' . $release;
            $releaseIndex = $existingPackage->get('release_index');
            if ($releaseIndex) {
                $previousVersion .= $releaseIndex;
            }
        }
    }

    $modx->getVersionData();
    $modxVersion = $modx->version['full_version'] ?? 'unknown';

    $pdoToolsVersion = null;
    /** @var modTransportPackage|null $pdoPackage */
    $pdoPackage = $modx->getObject(modTransportPackage::class, [
        'package_name' => 'pdoTools',
        'installed:IS NOT' => null,
    ]);
    if ($pdoPackage) {
        $pdoToolsVersion = $pdoPackage->get('version_major') . '.'
            . $pdoPackage->get('version_minor') . '.'
            . $pdoPackage->get('version_patch');
        $release = $pdoPackage->get('release');
        if ($release) {
            $pdoToolsVersion .= '-' . $release;
        }
    }

    $contextCount = $modx->getCount(modContext::class);
    $modxLocale = $modx->getOption('cultureKey', null, 'en');

    $dbVersion = null;
    $dbType = null;
    try {
        $pdo = $modx->getConnection()->pdo ?? null;
        if ($pdo instanceof PDO) {
            $dbType = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            $dbVersion = $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
        }
    } catch (Throwable $e) {
        // ignore
    }

    $webServer = null;
    $serverSoftware = $_SERVER['SERVER_SOFTWARE'] ?? '';
    if (stripos($serverSoftware, 'apache') !== false) {
        $webServer = 'Apache';
    } elseif (stripos($serverSoftware, 'nginx') !== false) {
        $webServer = 'Nginx';
    } elseif (stripos($serverSoftware, 'litespeed') !== false) {
        $webServer = 'LiteSpeed';
    } elseif (stripos($serverSoftware, 'iis') !== false) {
        $webServer = 'IIS';
    } elseif (!empty($serverSoftware)) {
        $webServer = explode('/', $serverSoftware)[0];
    }

    $packageVersion = 'unknown';
    if (!empty($transport->signature)) {
        $parts = explode('-', $transport->signature, 2);
        if (isset($parts[1])) {
            $packageVersion = $parts[1];
        }
    }

    return [
        'package_name' => 'Localizator3',
        'package_version' => $packageVersion,
        'l3_install_type' => $installType,
        'l3_previous_version' => $previousVersion,
        'php_version' => PHP_VERSION,
        'php_sapi' => PHP_SAPI,
        'modx_version' => $modxVersion,
        'modx_locale' => $modxLocale,
        'modx_context_count' => $contextCount,
        'pdo_tools_version' => $pdoToolsVersion,
        'db_type' => $dbType,
        'db_version' => $dbVersion,
        'os_type' => PHP_OS_FAMILY,
        'web_server' => $webServer,
    ];
};

$sendMetrics = function (array $metrics): bool {
    $url = 'https://metrics.modx.pro/';
    $payload = json_encode($metrics, JSON_UNESCAPED_UNICODE);
    $timeout = 2;

    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($payload),
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => $timeout,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        curl_exec($ch);
        $success = curl_errno($ch) === 0;
        curl_close($ch);
        return $success;
    }

    if (ini_get('allow_url_fopen')) {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\n",
                'content' => $payload,
                'timeout' => $timeout,
                'ignore_errors' => true,
            ],
            'ssl' => ['verify_peer' => true],
        ]);
        @file_get_contents($url, false, $context);
        return true;
    }

    return false;
};

try {
    $metrics = $collectMetrics();
    $sendMetrics($metrics);
} catch (Throwable $e) {
    // Молча игнорируем — установка не должна падать
}

return true;
