<?php

/**
 * OnDocFormSave handler — syncs default language content on resource save.
 *
 * @var \MODX\Revolution\modX $modx
 * @var localizator $localizator
 * @var \MODX\Revolution\modResource $resource
 * @var string $mode
 */

if ($mode == 'new') {
    if ($key = $modx->getOption('localizator3_default_language', null, false, true)) {
        if ($fields = $modx->getOption('localizator3_translate_fields', null, false, true)) {
            $content = $modx->newObject(\localizator3\localizatorContent::class);
            $content->set('resource_id', $resource->get('id'));
            $content->set('key', $key);
            $fields = array_map('trim', explode(',', $fields));
            foreach ($fields as $field) {
                if (isset($resource->_fieldMeta[$field])) {
                    $v = $resource->get($field);
                    if ($v) {
                        $content->set($field, $v);
                    }
                }
            }
            foreach ($content->getTVKeys() as $field) {
                $v = $resource->getTVValue($field);
                if ($v) {
                    $content->set($field, $v);
                }
            }
            $content->save();
        }
    }
} elseif ($resource->get('class_key') === \MODX\Revolution\modStaticResource::class) {
    $upd = $modx->prepare("UPDATE " . $modx->getTableName(\localizator3\localizatorContent::class) . " SET `content` = ? WHERE `resource_id` = ?");
    $upd->execute(array($resource->get('content'), $resource->get('id')));
} elseif (in_array($resource->get('class_key'), array(\MODX\Revolution\modSymLink::class, \MODX\Revolution\modWebLink::class))) {
    $defaultKey = $modx->getOption('localizator3_default_language', null, '', true);
    if ($defaultKey !== '') {
        $upd = $modx->prepare("UPDATE " . $modx->getTableName(\localizator3\localizatorContent::class) . " SET `content` = ? WHERE `resource_id` = ? AND `key` = ?");
        $upd->execute(array($resource->get('content'), $resource->get('id'), $defaultKey));
    }
}
