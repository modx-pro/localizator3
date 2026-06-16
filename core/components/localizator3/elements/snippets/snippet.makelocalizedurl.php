<?php

/**
 * Формирует URL ресурса с учётом текущего или указанного языка.
 *
 * @param int $id ID ресурса
 * @param string $language Ключ языка (опционально, по умолчанию — текущий)
 * @param int $scheme -1: относительный, 0: полный, 1: http, 2: https
 * @param bool $fullUrl Вернуть полный URL с доменом
 */

$id = (int) $modx->getOption('id', $scriptProperties, 0);
$languageKey = $modx->getOption('language', $scriptProperties, $modx->getOption('localizator3_key', null, ''));
$scheme = (int) $modx->getOption('scheme', $scriptProperties, -1);
$fullUrl = filter_var($modx->getOption('fullUrl', $scriptProperties, false), FILTER_VALIDATE_BOOLEAN);

if (!$id || !$modx->getObject(\MODX\Revolution\modResource::class, $id)) {
    return '';
}

$language = $modx->getObject(\localizator3\localizatorLanguage::class, [
    'key' => $languageKey ?: $modx->getOption('localizator3_key', null, ''),
    'active' => 1,
]);
if (!$language) {
    return $modx->makeUrl($id, '', '', $scheme);
}

$httpHost = $language->get('http_host');
$protocol = $modx->getOption('server_protocol', null, 'http') . '://';

if (strpos($httpHost, '://') === false && strpos($httpHost, '[[') === false) {
    $baseUrl = $protocol . $httpHost;
} else {
    $baseUrl = $httpHost;
}
$baseUrl = rtrim($baseUrl, '/') . '/';

$resourcePath = $modx->makeUrl($id, '', '', -1);
if (strpos($resourcePath, '://') !== false) {
    $parsed = parse_url($resourcePath);
    $resourcePath = isset($parsed['path']) ? ltrim($parsed['path'], '/') : (string) $id;
} else {
    $resourcePath = ltrim($resourcePath, '/');
}

$url = $baseUrl . $resourcePath;

if ($fullUrl || $scheme >= 0) {
    if (strpos($url, '://') === false) {
        $url = $protocol . $modx->getOption('http_host', null, 'localhost') . '/' . ltrim($url, '/');
    }
    if ($scheme === 1) {
        $url = preg_replace('#^https://#', 'http://', $url);
    } elseif ($scheme === 2) {
        $url = preg_replace('#^http://#', 'https://', $url);
    }
} elseif ($scheme === -1) {
    $parsed = parse_url($url);
    $url = isset($parsed['path']) ? $parsed['path'] : $url;
}

return $url;
