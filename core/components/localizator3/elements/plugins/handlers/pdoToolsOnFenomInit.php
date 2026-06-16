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

$fenom->addModifier('locfield', function ($id, $field = null) use ($pdo, $modx) {
    if (empty($id)) {
        $resource = $modx->resource;
    } elseif (!is_numeric($id)) {
        $field = $id;
        $resource = $modx->resource;
    } elseif (!$resource = $pdo->getStore($id, 'resource')) {
        $resource = $modx->getObject(\MODX\Revolution\modResource::class, $id);
        $pdo->setStore($id, $resource, 'resource');
    }

    if (!$resource) {
        return '';
    }

    $id = $resource->get('id');
    $key = $modx->getOption('localizator3_key', null, '');
    $output = '';

    if (in_array($field, array_diff(array_keys($modx->getFields(\localizator3\localizatorContent::class)), array('id', 'resource_id')))) {
        $q = $modx->newQuery(\localizator3\localizatorContent::class)
            ->where(array(
                "resource_id" => $id,
                "key" => $key,
                "active" => 1,
            ))
            ->select($field);
        if ($q->prepare() && $q->stmt->execute()) {
            $output = $q->stmt->fetchColumn();
        }
    } elseif (in_array($field, array_keys($modx->getFields(\MODX\Revolution\modResource::class)))) {
        $output = $resource->get($field);
    } elseif ($tv = $modx->getObject(\MODX\Revolution\modTemplateVar::class, array('name' => $field))) {
        if ($tv->get('localizator3_enabled')) {
            $q = $modx->newQuery(\localizator3\locTemplateVarResource::class)
                ->where(array(
                    "contentid" => $id,
                    "key" => $key,
                    "tmplvarid" => $tv->get('id'),
                ))
                ->select('value');
            if ($q->prepare() && $q->stmt->execute()) {
                if ($output = $q->stmt->fetchColumn()) {
                    $output = \localizator3\localizatorContent::renderTVOutput($modx, $tv, $output, $id);
                }
            }
        } else {
            $output = $resource->getTVValue($field);
        }
    }
    return $output;
});

// B1: Локализация caption опции msOption. Использование: {$option_id|locoptioncaption:'По умолчанию'}
$fenom->addModifier('locoptioncaption', function ($option_id, $default = '') use ($loc) {
    return $loc ? $loc->getLocalizedOptionCaption((int)$option_id, $default) : (string)$default;
});

// B1: Локализация value msProductOption. Использование: {$product_option_id|locproductoptionvalue:'По умолчанию'}
$fenom->addModifier('locproductoptionvalue', function ($product_option_id, $default = '') use ($loc) {
    return $loc ? $loc->getLocalizedProductOptionValue((int)$product_option_id, $default) : (string)$default;
});
