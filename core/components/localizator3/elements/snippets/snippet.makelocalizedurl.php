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

$resource = $id ? $modx->getObject(\MODX\Revolution\modResource::class, $id) : null;
if (!$resource) {
    return '';
}
$contextKey = $modx->context ? (string)$modx->context->get('key') : '';
if ($contextKey !== 'mgr' && (!(int)$resource->get('published') || (int)$resource->get('deleted'))) {
    return '';
}

$localizator = $modx->getService(
    'localizator3',
    'localizator',
    $modx->getOption('localizator3_core_path', null, $modx->getOption('core_path') . 'components/localizator3/') . 'model/localizator3/'
);
if ($localizator instanceof localizator) {
    $languageKey = $localizator->resolveLanguageKey((string)($languageKey ?: $modx->getOption('localizator3_key', null, '')));
    $protocol = $localizator->resolveUrlScheme();
} else {
    $languageKey = (string)($languageKey ?: $modx->getOption('localizator3_key', null, ''));
    $protocol = rtrim((string) $modx->getOption('server_protocol', null, 'http'), ':/') . '://';
}

$language = $modx->getObject(\localizator3\localizatorLanguage::class, [
    'key' => $languageKey,
    'active' => 1,
]);
if (!$language) {
    return $modx->makeUrl($id, '', '', $scheme);
}

$httpHost = $language->get('http_host');
if (strpos($httpHost, '://') === false && strpos($httpHost, '[[') === false) {
    $baseUrl = $protocol . $httpHost;
} else {
    $baseUrl = $httpHost;
}
$baseUrl = rtrim($baseUrl, '/') . '/';

// site_start with scheme -1 is the current base_url (/ or /en/), not the document path.
$resourcePath = '';
if ((int) $id !== (int) $modx->getOption('site_start')) {
    $resourcePath = $modx->makeUrl($id, '', '', -1);
    if (strpos($resourcePath, '://') !== false) {
        $parsed = parse_url($resourcePath);
        $resourcePath = isset($parsed['path']) ? ltrim((string) ($parsed['path'] ?? ''), '/') : '';
    } else {
        $resourcePath = ltrim($resourcePath, '/');
    }
}

$url = $baseUrl . $resourcePath;
if ($localizator instanceof localizator) {
    $publicUrl = $localizator->isPublicHttpUrl($url);
} else {
    $urlScheme = parse_url($url, PHP_URL_SCHEME);
    $publicUrl = $urlScheme === null
        || (is_string($urlScheme) && in_array(strtolower($urlScheme), array('http', 'https'), true));
}
if (!$publicUrl) {
    return '';
}

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
