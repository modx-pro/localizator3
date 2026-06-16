<?php

$pdoFetch = $modx->getService('pdoFetch');
$outputMode = $modx->getOption('outputMode', $scriptProperties, 'list');
$showActive = filter_var($modx->getOption('showActive', $scriptProperties, true), FILTER_VALIDATE_BOOLEAN);
$activeClass = $modx->getOption('activeClass', $scriptProperties, 'active');
$outerClass = $modx->getOption('outerClass', $scriptProperties, 'languages');
$rowClass = $modx->getOption('rowClass', $scriptProperties, '');
$tpl = $modx->getOption('tpl', $scriptProperties, $outputMode === 'dropdown' ? 'languages.dropdown.tpl' : 'languages.tpl');
$start = $modx->getOption('site_start');
$pageId = $modx->getOption('pageId', $scriptProperties, $modx->resource ? $modx->resource->get('id') : $start);
$currentLocale = $modx->config['cultureKey'];
$where = $modx->getOption('where', $scriptProperties, ['active' => 1]);
$sortby = $modx->getOption('sortby', $scriptProperties, 'rank');
$sortdir = $modx->getOption('sortdir', $scriptProperties, 'ASC');
$protocol = $modx->getOption('server_protocol', null, 'http') . '://';

$scriptProperties['sortby'] = $sortby;
$scriptProperties['sortdir'] = $sortdir;
$locales = $pdoFetch->getCollection(\localizator3\localizatorLanguage::class, $where, $scriptProperties);

$output = '';
$data = [];
$languages = [];

if ($locales) {
    $data = $scriptProperties;

    foreach ($locales as $locale) {
        $httpHost = $locale['http_host'];
        if (strpos($httpHost, '[[') === false && strpos($httpHost, '://') === false) {
            $httpHost = $protocol . $httpHost;
        }

        $baseUrl = rtrim($httpHost, '/') . '/';
        if ($pageId != $start) {
            $path = $modx->makeUrl($pageId, '', '', -1);
            $path = (strpos($path, '://') !== false) ? ltrim(parse_url($path, PHP_URL_PATH), '/') : ltrim($path, '/');
            $url = $baseUrl . $path;
        } else {
            $url = $baseUrl;
        }

        $isCurrent = ($locale['key'] == $currentLocale || $locale['cultureKey'] == $currentLocale);
        $rowCssClass = ($showActive && $isCurrent) ? trim($rowClass . ' ' . $activeClass) : $rowClass;

        $languages[] = array_merge(
            $locale,
            [
                'rowClass' => $rowCssClass,
                'url' => $url,
                'is_current' => $isCurrent,
            ]
        );
    }

    $data['languages'] = $languages;
    $output = $pdoFetch->getChunk($tpl, $data);
}

return $output;
