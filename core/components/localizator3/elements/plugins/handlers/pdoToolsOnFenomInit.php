<?php

/**
 * pdoToolsOnFenomInit handler — adds locfield, locoptioncaption, locproductoptionvalue modifiers for Fenom.
 *
 * @var \MODX\Revolution\modX $modx
 * @var localizator $localizator
 * @var Fenom $fenom
 */

$pdo = $modx->getService('pdoTools');
$loc = isset($localizator) ? $localizator : $modx->getService('localizator3', 'localizator', $modx->getOption('localizator3_core_path', null, $modx->getOption('core_path') . 'components/localizator3/') . 'model/localizator3/');

$fenom->addModifier('locfield', function ($id, $field = null) use ($pdo, $modx, $loc) {
    if (empty($id)) {
        $resource = $modx->resource;
    } elseif (!is_numeric($id)) {
        $field = $id;
        $resource = $modx->resource;
    } elseif (!$resource = $pdo->getStore($id, 'resource')) {
        $resource = $modx->getObject(\MODX\Revolution\modResource::class, $id);
        $pdo->setStore($id, $resource, 'resource');
    }

    if (!$resource || !($loc instanceof localizator)) {
        return '';
    }

    if ($field === null || $field === '') {
        return '';
    }

    return $loc->getLocalizedFieldValue($resource, $field);
});

// B1: Локализация caption опции msOption. Использование: {$option_id|locoptioncaption:'По умолчанию'}
$fenom->addModifier('locoptioncaption', function ($option_id, $default = '') use ($loc) {
    return $loc ? $loc->getLocalizedOptionCaption((int)$option_id, $default) : (string)$default;
});

// B1: Локализация value msProductOption. Использование: {$product_option_id|locproductoptionvalue:'По умолчанию'}
$fenom->addModifier('locproductoptionvalue', function ($product_option_id, $default = '') use ($loc) {
    return $loc ? $loc->getLocalizedProductOptionValue((int)$product_option_id, $default) : (string)$default;
});
