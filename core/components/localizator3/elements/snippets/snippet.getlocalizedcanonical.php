<?php

/**
 * Выводит canonical и hreflang-ссылки для мультиязычных страниц (SEO).
 *
 * @param int $id ID ресурса (по умолчанию — текущий)
 * @param string $tpl Чанк для вывода (опционально)
 */

$idProp = $modx->getOption('id', $scriptProperties, null);
$id = ($idProp !== null && $idProp !== '') ? (int)$idProp : ($modx->resource ? (int)$modx->resource->get('id') : 0);
$tpl = $modx->getOption('tpl', $scriptProperties, '');

if (!$id) {
    return '';
}

$currentKey = $modx->getOption('localizator3_key', null, '');
if ($currentKey === '') {
    return '';
}

$q = $modx->newQuery(\localizator3\localizatorLanguage::class);
$q->where(['active' => 1]);
$q->sortby($modx->escape('rank'), 'ASC');
$q->sortby('id', 'ASC');
$languages = $modx->getCollection(\localizator3\localizatorLanguage::class, $q);

$links = [];

foreach ($languages as $language) {
    $url = $modx->runSnippet('makeLocalizedUrl', [
        'id' => $id,
        'language' => $language->get('key'),
        'scheme' => 0,
        'fullUrl' => true,
    ]);
    if (empty($url)) {
        continue;
    }
    $hreflang = $language->get('cultureKey') ?: $language->get('key');
    $links[] = [
        'url' => $url,
        'hreflang' => $hreflang,
        'language_key' => $language->get('key'),
        'is_current' => ($language->get('key') == $currentKey),
    ];
}

if (empty($links)) {
    return '';
}

$output = '';
$defaultKey = $modx->getOption('localizator3_default_language', null, '', true);
$defaultUrl = '';
foreach ($links as $link) {
    if ($link['is_current']) {
        $output .= '<link rel="canonical" href="' . htmlspecialchars($link['url']) . '">' . "\n";
    }
    $output .= '<link rel="alternate" hreflang="' . htmlspecialchars($link['hreflang']) . '" href="' . htmlspecialchars($link['url']) . '">' . "\n";
    if (($defaultKey !== '' && $link['language_key'] === $defaultKey) || ($defaultKey === '' && $link['is_current'])) {
        $defaultUrl = $link['url'];
    }
}
if ($defaultUrl) {
    $output .= '<link rel="alternate" hreflang="x-default" href="' . htmlspecialchars($defaultUrl) . '">' . "\n";
}

if ($tpl !== '') {
    $rendered = '';
    if (isset($tpl[0]) && $tpl[0] === '@') {
        $pdoFetch = $modx->getService('pdoFetch');
        if ($pdoFetch) {
            $rendered = $pdoFetch->getChunk($tpl, [
                'links' => $links,
                'output' => $output,
            ]);
        }
    } elseif ($modx->getCount(\MODX\Revolution\modChunk::class, ['name' => $tpl])) {
        $rendered = $modx->getChunk($tpl, [
            'links' => $links,
            'output' => $output,
        ]);
    }
    if (is_string($rendered) && $rendered !== '') {
        $output = $rendered;
    }
}

return $output;
