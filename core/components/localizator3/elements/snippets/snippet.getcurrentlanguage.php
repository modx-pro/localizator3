<?php

/**
 * Возвращает поле текущего языка: key, cultureKey, name или http_host.
 *
 * @param string $field Поле языка: key (по умолчанию), cultureKey, name, http_host
 * @param string $format Формат результата: json — вернуть JSON-строку
 */

$field = $modx->getOption('field', $scriptProperties, 'key', true);
$format = $modx->getOption('format', $scriptProperties, '', true);

$key = $modx->getOption('localizator3_key', null, '', true);
$cultureKey = $modx->getOption('cultureKey', null, '', true);
$value = '';

if ($key !== '') {
    if ($field === 'key') {
        $value = $key;
    } elseif ($field === 'cultureKey') {
        $value = $cultureKey ?: $key;
    } elseif ($field === 'name' || $field === 'http_host') {
        $language = $modx->getObject(\localizator3\localizatorLanguage::class, ['key' => $key]);
        if ($language) {
            $value = $language->get($field);
        }
    }
}

if ($format === 'json') {
    return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
}

return $value;
