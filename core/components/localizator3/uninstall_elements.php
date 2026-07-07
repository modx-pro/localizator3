<?php

declare(strict_types=1);

/**
 * Removes Localizator3 plugins on package uninstall (#10).
 *
 * xPDO transport removes related objects only when UPDATE_OBJECT was true
 * in the built package. This helper guarantees cleanup even for older builds.
 */
function localizator3RemovePlugins(modX $modx): void
{
    $pluginClass = class_exists(\MODX\Revolution\modPlugin::class)
        ? \MODX\Revolution\modPlugin::class
        : 'modPlugin';
    $eventClass = class_exists(\MODX\Revolution\modPluginEvent::class)
        ? \MODX\Revolution\modPluginEvent::class
        : 'modPluginEvent';

    foreach (['localizator3'] as $pluginName) {
        $plugin = $modx->getObject($pluginClass, ['name' => $pluginName]);
        if (!$plugin) {
            continue;
        }

        $pluginId = (int)$plugin->get('id');
        $events = $modx->getCollection($eventClass, ['pluginid' => $pluginId]);
        foreach ($events as $event) {
            $event->remove();
        }

        if ($plugin->remove()) {
            $modx->log(modX::LOG_LEVEL_INFO, "[Localizator3] Removed plugin: {$pluginName}");
            continue;
        }

        $modx->log(modX::LOG_LEVEL_ERROR, "[Localizator3] Failed to remove plugin: {$pluginName}");
    }
}
