<?php

/**
 * OnLoadWebDocument handler — applies localized content to resource.
 *
 * @var \MODX\Revolution\modX $modx
 * @var localizator $localizator
 */

require_once $localizator->config['modelPath'] . 'localizator3/localizatorresourcetvwrapper.class.php';
$localizatorKey = $modx->getOption('localizator3_key', null, '');
if ($localizatorKey === '') {
    return;
}
if ($localizator->shouldUseResourceFields($localizatorKey)) {
    $modx->resource->set('localizator3_content', $modx->resource->getContent());
    return;
}
$q = $modx->newQuery(\localizator3\localizatorContent::class);
$q->leftJoin(\localizator3\localizatorLanguage::class, 'localizatorLanguage', 'localizatorLanguage.key = localizatorContent.key');
$q->where(array(
    'localizatorContent.resource_id' => $modx->resource->id,
    'localizatorContent.active' => 1,
));
$q->where(array(
    'localizatorLanguage.key' => $localizatorKey,
    'OR:localizatorLanguage.cultureKey:=' => $localizatorKey,
));
$content = $modx->getObject(\localizator3\localizatorContent::class, $q);
if (!$content && $localizatorKey !== '' && $modx->getOption('localizator3_404_if_no_localization', null, false, true)) {
    $prevent404 = $modx->invokeEvent('OnHasLocalizatorError404', array(
        'resource' => $modx->resource,
        'language_key' => $localizatorKey,
    ));
    if (!is_array($prevent404) || !in_array(false, $prevent404, true)) {
        $modx->sendErrorPage(404);
    }
}
$defaultFromResource = $localizator->isDefaultFromResource();
if (!$content) {
    $placeholders = array(
        'localizator3_content' => $defaultFromResource ? '' : $modx->resource->getContent(),
    );
    $modx->resource->set('localizator3_content', $placeholders['localizator3_content']);
    if ($defaultFromResource) {
        $fields = explode(',', $modx->getOption('localizator3_translate_fields', null, 'pagetitle,longtitle,menutitle,seotitle,keywords,introtext,description,content'));
        foreach ($fields as $field) {
            $field = trim($field);
            if ($field === '' || $field === 'content' || $field === 'menutitle') {
                continue;
            }
            $placeholders[$field] = '';
            $modx->resource->set($field, '');
        }
    }
    $modx->setPlaceholders($placeholders, '*');
}
if ($content) {
    $placeholders = array();
    $fields = explode(',', $modx->getOption('localizator3_translate_fields', null, 'pagetitle,longtitle,menutitle,seotitle,keywords,introtext,description,content'));
    foreach ($fields as $field) {
        $field = trim($field);
        if (empty($field)) {
            continue;
        }
        $value = $content->get($field);
        if ($field == 'content') {
            if (($value === null || $value === '') && !$defaultFromResource) {
                $value = $modx->resource->getContent();
            }
            $placeholders['localizator3_content'] = $value;
            $modx->resource->set('localizator3_content', $value);
            continue;
        }
        if ($field === 'menutitle' && ($value === null || $value === '')) {
            $localizedTitle = $content->get('pagetitle');
            if ($localizedTitle !== null && $localizedTitle !== '') {
                $placeholders['menutitle'] = $localizedTitle;
                $modx->resource->set('menutitle', $localizedTitle);
            }
            continue;
        }
        if ($value === null || $value === '') {
            if ($defaultFromResource) {
                $placeholders[$field] = '';
                $modx->resource->set($field, '');
            }
            continue;
        }
        $placeholders[$field] = $value;
        $modx->resource->set($field, $value);
    }
    if (!array_key_exists('localizator3_content', $placeholders)) {
        $placeholders['localizator3_content'] = $defaultFromResource ? '' : $modx->resource->getContent();
        $modx->resource->set('localizator3_content', $placeholders['localizator3_content']);
    }
    $localizatorTVOutput = array();
    foreach ($content->getTVKeys() as $field) {
        $value = $content->get($field);
        if (!empty($value)) {
            $value = \localizator3\localizatorContent::renderTVOutput($modx, $field, $value, $modx->resource->id);
            $modx->resource->set($field, $value);
            $localizatorTVOutput[$field] = $value;
            $placeholders[$field] = $value;
        }
    }
    if (!empty($localizatorTVOutput)) {
        $modx->resource = new LocalizatorResourceTVWrapper($modx->resource, $localizatorTVOutput);
    }
    $modx->setPlaceholders($placeholders, '*');
}
