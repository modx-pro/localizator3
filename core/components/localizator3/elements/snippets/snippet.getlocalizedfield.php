<?php

/**
 * Выводит поле ресурса на указанном языке.
 *
 * @param int $id ID ресурса (по умолчанию — текущий)
 * @param string $field Имя поля (pagetitle, content, или TV)
 * @param string $language Ключ языка (по умолчанию — текущий)
 * @param string $default Значение по умолчанию, если локализация отсутствует
 */

$id = (int) $modx->getOption('id', $scriptProperties, $modx->resource ? $modx->resource->get('id') : 0);
$field = $modx->getOption('field', $scriptProperties, '');
$language = $modx->getOption('language', $scriptProperties, $modx->getOption('localizator3_key', null, ''));
$default = $modx->getOption('default', $scriptProperties, '');

if (!$id || !$field) {
    return $default;
}

$resource = $modx->getObject(\MODX\Revolution\modResource::class, $id);
if (!$resource) {
    return $default;
}

$contentFields = array_diff(array_keys($modx->getFields(\localizator3\localizatorContent::class)), array('id', 'resource_id'));
$resourceFields = array_keys($modx->getFields(\MODX\Revolution\modResource::class));

if (in_array($field, $contentFields)) {
    $q = $modx->newQuery(\localizator3\localizatorContent::class);
    $q->where([
        'resource_id' => $id,
        'key' => $language,
        'active' => 1,
    ]);
    $q->select($field);
    if ($q->prepare() && $q->stmt->execute()) {
        $value = $q->stmt->fetchColumn();
        if ($value !== false && $value !== null && $value !== '') {
            return $value;
        }
    }
    return $default ?: $resource->get($field);
}

if (in_array($field, $resourceFields)) {
    $q = $modx->newQuery(\localizator3\localizatorContent::class);
    $q->where([
        'resource_id' => $id,
        'key' => $language,
        'active' => 1,
    ]);
    $content = $modx->getObject(\localizator3\localizatorContent::class, $q);
    if ($content) {
        $value = $content->get($field);
        if ($value !== null && $value !== '') {
            return $value;
        }
    }
    return $default ?: $resource->get($field);
}

$tv = $modx->getObject(\MODX\Revolution\modTemplateVar::class, array('name' => $field));
if ($tv) {
    if ($tv->get('localizator3_enabled')) {
        $q = $modx->newQuery(\localizator3\locTemplateVarResource::class);
        $q->where([
            'contentid' => $id,
            'key' => $language,
            'tmplvarid' => $tv->get('id'),
        ]);
        $q->select('value');
        if ($q->prepare() && $q->stmt->execute()) {
            $value = $q->stmt->fetchColumn();
            if ($value !== false && $value !== null && $value !== '') {
                return \localizator3\localizatorContent::renderTVOutput($modx, $tv, $value, $id);
            }
        }
    }
    return $default ?: $resource->getTVValue($field);
}

return $default;
