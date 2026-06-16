<?php

/**
 * OnEmptyTrash handler — removes localization data for trashed resources.
 *
 * @var \MODX\Revolution\modX $modx
 * @var localizator $localizator
 * @var array $ids
 */

if (!empty($ids)) {
    $modx->removeCollection(\localizator3\localizatorContent::class, array('resource_id:IN' => $ids));
    $modx->removeCollection(\localizator3\locTemplateVarResource::class, array('contentid:IN' => $ids));
}
