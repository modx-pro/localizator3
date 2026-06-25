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

$localizator = $modx->getService(
    'localizator3',
    'localizator',
    $modx->getOption('localizator3_core_path', null, $modx->getOption('core_path') . 'components/localizator3/') . 'model/localizator3/'
);

if (!($localizator instanceof localizator)) {
    return $default;
}

$value = $localizator->getLocalizedFieldValue($resource, $field, $language);
if ($value !== '') {
    return $value;
}

return $default;
