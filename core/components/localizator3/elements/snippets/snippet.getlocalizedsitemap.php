<?php

/**
 * Генерирует sitemap.xml с hreflang для мультиязычных страниц (SEO).
 *
 * @param string $parents ID родителей через запятую (по умолчанию — site_start)
 * @param int $depth Глубина выборки (0 = без ограничения)
 * @param string $excludeIds ID ресурсов для исключения
 * @param int $scheme 0: auto, 1: http, 2: https
 * @param bool $onlyWithLocalization Включать только ресурсы с локализацией
 */

$parents = $modx->getOption('parents', $scriptProperties, $modx->getOption('site_start', null, 1));
$depth = (int) $modx->getOption('depth', $scriptProperties, 10);
$excludeIds = $modx->getOption('excludeIds', $scriptProperties, '');
$scheme = (int) $modx->getOption('scheme', $scriptProperties, 0);
$onlyWithLocalization = filter_var($modx->getOption('onlyWithLocalization', $scriptProperties, false), FILTER_VALIDATE_BOOLEAN);

$parentIds = array_map('intval', array_filter(array_map('trim', explode(',', $parents))));
if (empty($parentIds)) {
    return '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml"></urlset>';
}

$excludeIds = array_filter(array_map('intval', explode(',', $excludeIds)));

$q = $modx->newQuery(\MODX\Revolution\modResource::class);
$q->where([
    'published' => 1,
    'deleted' => 0,
]);
if (!empty($excludeIds)) {
    $q->where(['id:NOT IN' => $excludeIds]);
}
$childIds = $modx->getChildIds($parentIds, $depth, ['context' => $modx->context->key]);
$allIds = array_unique(array_merge($parentIds, $childIds));
$q->where(['id:IN' => $allIds]);
$q->sortby('id', 'ASC');
$resources = $modx->getCollection(\MODX\Revolution\modResource::class, $q);

$languages = [];
$langQ = $modx->newQuery(\localizator3\localizatorLanguage::class);
$langQ->where(['active' => 1]);
$langQ->sortby('rank', 'ASC');
$langQ->sortby('id', 'ASC');
foreach ($modx->getCollection(\localizator3\localizatorLanguage::class, $langQ) as $lang) {
    $languages[$lang->get('key')] = $lang;
}

$defaultKey = $modx->getOption('localizator3_default_language', null, '', true);

$urls = [];
foreach ($resources as $resource) {
    $resourceId = $resource->get('id');
    $locales = [];

    if ($onlyWithLocalization) {
        $contentQ = $modx->newQuery(\localizator3\localizatorContent::class);
        $contentQ->where(['resource_id' => $resourceId]);
        $contentQ->select('key');
        $contentQ->groupby('key');
        $contentObjs = $modx->getCollection(\localizator3\localizatorContent::class, $contentQ);
        foreach ($contentObjs as $c) {
            $locales[] = $c->get('key');
        }
        if (empty($locales)) {
            continue;
        }
    } else {
        $locales = array_keys($languages);
    }

    $alternates = [];
    foreach ($locales as $key) {
        if (!isset($languages[$key])) {
            continue;
        }
        $url = $modx->runSnippet('makeLocalizedUrl', [
            'id' => $resourceId,
            'language' => $key,
            'scheme' => $scheme,
            'fullUrl' => true,
        ]);
        if (empty($url)) {
            continue;
        }
        $hreflang = $languages[$key]->get('cultureKey') ?: $key;
        $alternates[$hreflang] = $url;
    }

    if (empty($alternates)) {
        continue;
    }

    $defaultUrl = ($defaultKey && isset($alternates[$defaultKey])) ? $alternates[$defaultKey] : reset($alternates);
    $urls[] = [
        'loc' => $defaultUrl,
        'alternates' => $alternates,
        'lastmod' => $resource->get('editedon') ? date('c', strtotime($resource->get('editedon'))) : '',
    ];
}

$output = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
$output .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";

foreach ($urls as $item) {
    $output .= '  <url>' . "\n";
    $output .= '    <loc>' . htmlspecialchars($item['loc']) . '</loc>' . "\n";
    if (!empty($item['lastmod'])) {
        $output .= '    <lastmod>' . htmlspecialchars($item['lastmod']) . '</lastmod>' . "\n";
    }
    foreach ($item['alternates'] as $hreflang => $href) {
        $output .= '    <xhtml:link rel="alternate" hreflang="' . htmlspecialchars($hreflang) . '" href="' . htmlspecialchars($href) . '"/>' . "\n";
    }
    $output .= '    <xhtml:link rel="alternate" hreflang="x-default" href="' . htmlspecialchars($item['loc']) . '"/>' . "\n";
    $output .= '  </url>' . "\n";
}

$output .= '</urlset>';

return $output;
