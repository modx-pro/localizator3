<?php

/**
 * Генерирует sitemap.xml с hreflang для мультиязычных страниц (SEO).
 *
 * @param string $parents ID родителей через запятую (по умолчанию 0)
 * @param int $depth Глубина обхода потомков. 0 оставляет только указанные parents: MODX getChildIds не спускается при depth < 1
 * @param string $excludeIds ID ресурсов для исключения
 * @param int $scheme 0: auto, 1: http, 2: https
 * @param bool $onlyWithLocalization Включать только ресурсы с локализацией
 */

$parents = $modx->getOption('parents', $scriptProperties, '0', true);
if ($parents === '' || $parents === null) {
    $parents = '0';
}
$depth = (int) $modx->getOption('depth', $scriptProperties, 10);
$excludeIds = $modx->getOption('excludeIds', $scriptProperties, '');
$scheme = (int) $modx->getOption('scheme', $scriptProperties, 0);
$onlyWithLocalization = filter_var($modx->getOption('onlyWithLocalization', $scriptProperties, false), FILTER_VALIDATE_BOOLEAN);

$parentIds = array_map('intval', array_filter(array_map('trim', explode(',', $parents)), 'strlen'));
if (empty($parentIds)) {
    return '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml"></urlset>';
}

$excludeIds = array_filter(array_map('intval', explode(',', $excludeIds)));

$q = $modx->newQuery(\MODX\Revolution\modResource::class);
$q->where([
    'published' => 1,
    'deleted' => 0,
    'searchable' => 1,
]);
if (!empty($excludeIds)) {
    $q->where(['id:NOT IN' => $excludeIds]);
}
$childIds = [];
foreach ($parentIds as $parentId) {
    $childIds = array_merge($childIds, $modx->getChildIds($parentId, $depth, ['context' => $modx->context->key]));
}
$childIds = array_unique($childIds);
$allIds = array_unique(array_merge($parentIds, $childIds));
$q->where(['id:IN' => $allIds]);
$q->sortby('id', 'ASC');
$resources = $modx->getCollection(\MODX\Revolution\modResource::class, $q);

$languages = [];
$langQ = $modx->newQuery(\localizator3\localizatorLanguage::class);
$langQ->where(['active' => 1]);
$langQ->sortby($modx->escape('rank'), 'ASC');
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
        $contentQ->where([
            'resource_id' => $resourceId,
            'active' => 1,
        ]);
        $contentQ->select($modx->escape('key'));
        if ($contentQ->prepare() && $contentQ->stmt->execute()) {
            while (($key = $contentQ->stmt->fetchColumn()) !== false) {
                if ($key !== null && $key !== '') {
                    $locales[(string) $key] = (string) $key;
                }
            }
            $locales = array_values($locales);
        }
        if (empty($locales)) {
            continue;
        }
        if ($defaultKey !== '' && isset($languages[$defaultKey]) && !in_array($defaultKey, $locales, true)) {
            array_unshift($locales, $defaultKey);
        }
    } else {
        $locales = array_keys($languages);
    }

    $alternates = [];
    $urlsByLanguageKey = [];
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
        $urlsByLanguageKey[$key] = $url;
    }

    if (empty($alternates)) {
        continue;
    }

    $defaultUrl = ($defaultKey !== '' && isset($urlsByLanguageKey[$defaultKey])) ? $urlsByLanguageKey[$defaultKey] : reset($alternates);
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
