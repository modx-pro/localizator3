<?php

/**
 * mseOnGetWorkFields — добавляет локализованные поля в список полей для индексации.
 *
 * Расширяет mse_index_fields полями вида {key}-pagetitle для каждого языка.
 * API mSearch (MODX 3): params['fields'] или params['workFields'].
 *
 * @see https://docs.modx.pro/components/msearch/
 *
 * @var \MODX\Revolution\modX $modx
 */

$fields = $modx->event->params['fields'] ?? $modx->event->params['workFields'] ?? [];
if (empty($fields)) {
    return;
}
if (is_string($fields)) {
    $parsed = [];
    foreach (array_map('trim', explode(',', $fields)) as $part) {
        if (strpos($part, ':') !== false) {
            list($f, $w) = explode(':', $part, 2);
            $parsed[trim($f)] = (int)$w;
        }
    }
    $fields = $parsed;
}
if (!is_array($fields)) {
    return;
}

$contentFields = array_diff(
    array_keys($modx->getFields(\localizator3\localizatorContent::class)),
    ['id', 'resource_id']
);

$langs = $modx->getCollection(\localizator3\localizatorLanguage::class, ['active' => 1]);
if (empty($langs)) {
    return;
}

$baseFields = array_intersect(array_keys($fields), $contentFields);
foreach ($langs as $lang) {
    $key = $lang->get('key');
    foreach ($baseFields as $field) {
        $fieldKey = $key . '-' . $field;
        $fields[$fieldKey] = $fields[$field];
    }
}

$modx->event->params['fields'] = $fields;
if (isset($modx->event->params['workFields'])) {
    $modx->event->params['workFields'] = $fields;
}
