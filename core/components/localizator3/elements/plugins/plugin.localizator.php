<?php

/**
 * Localizator3 plugin — delegates to per-event handlers.
 *
 * @var \MODX\Revolution\modX $modx
 * @var localizator $localizator
 */
$localizator3Core = $modx->getOption(
    'localizator3_core_path',
    null,
    $modx->getOption('core_path') . 'components/localizator3/'
);
$localizator3Autoload = $localizator3Core . 'vendor/autoload.php';
if (is_file($localizator3Autoload)) {
    require_once $localizator3Autoload;
}

$localizator = $modx->getService(
    'localizator3',
    'localizator',
    $localizator3Core . 'model/localizator3/'
);
if (!($localizator instanceof localizator)) {
    return;
}

$eventName = $modx->event->name;
$handlersPath = $modx->getOption('localizator3_core_path', null, $modx->getOption('core_path') . 'components/localizator3/') . 'elements/plugins/handlers/';

$handlerFile = $handlersPath . $eventName . '.php';
if (file_exists($handlerFile)) {
    include $handlerFile;
}
