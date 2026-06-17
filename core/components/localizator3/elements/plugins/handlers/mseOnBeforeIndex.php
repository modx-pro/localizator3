<?php

/**
 * mseOnBeforeIndex — добавляет локализованные поля в индекс mSearch (MODX 3).
 *
 * Для каждого языка (localizator3_content) создаются поля вида {key}-pagetitle,
 * {key}-description и т.д. с весами как у базовых полей.
 *
 * API mSearch: params['resource'], params['fields'] (или params['workFields']).
 * См. docs/integration-msearch.md
 *
 * @see https://docs.modx.pro/components/msearch/
 * @see https://modx.pro/howto/16466 (идея для mSearch2)
 *
 * @var \MODX\Revolution\modX $modx
 */

$resource = $modx->event->params['resource'] ?? null;
$fields = $modx->event->params['fields'] ?? $modx->event->params['workFields'] ?? [];

if (empty($resource)) {
    return;
}
$isResource = $resource instanceof \MODX\Revolution\modResource
    || (class_exists(\MODX\Revolution\modResource::class) && $resource instanceof \MODX\Revolution\modResource);
if (!$isResource) {
    return;
}
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

// Исключаем служебные поля
$indexFields = $fields;
unset($indexFields['comment']);

$contentFields = array_diff(
    array_keys($modx->getFields(\localizator3\localizatorContent::class)),
    ['id', 'resource_id']
);

$contents = $modx->getCollection(\localizator3\localizatorContent::class, ['resource_id' => $resource->get('id')]);
if (empty($contents)) {
    return;
}

foreach ($contents as $content) {
    foreach ($indexFields as $field => $weight) {
        if (!in_array($field, $contentFields, true)) {
            continue;
        }
        $value = $content->get($field);
        if ($value === null || $value === '') {
            continue;
        }
        $fieldKey = $content->get('key') . '-' . $field;
        $fields[$fieldKey] = $weight;
        $resource->set($fieldKey, $value);
    }
}

$modx->event->params['fields'] = $fields;
if (isset($modx->event->params['workFields'])) {
    $modx->event->params['workFields'] = $fields;
}
$modx->event->params['resource'] = $resource;
