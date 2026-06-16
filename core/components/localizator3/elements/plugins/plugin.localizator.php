<?php

/**
 * Localizator3 plugin — delegates to per-event handlers.
 *
 * @var \MODX\Revolution\modX $modx
 * @var localizator $localizator
 */
$localizator = $modx->getService('localizator3', 'localizator', $modx->getOption('localizator3_core_path', null, $modx->getOption('core_path') . 'components/localizator3/') . 'model/localizator3/');
if (!($localizator instanceof localizator)) {
    return;
}

$eventName = $modx->event->name;
$handlersPath = $modx->getOption('localizator3_core_path', null, $modx->getOption('core_path') . 'components/localizator3/') . 'elements/plugins/handlers/';

$handlerFile = $handlersPath . $eventName . '.php';
if (file_exists($handlerFile)) {
    include $handlerFile;
}
