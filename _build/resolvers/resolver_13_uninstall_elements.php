<?php

/** @var xPDOTransport $transport */
/** @var array $options */
/** @var modX $modx */

if (!$transport->xpdo || !($transport instanceof xPDOTransport)) {
    return false;
}

$modx = $transport->xpdo;

switch ($options[xPDOTransport::PACKAGE_ACTION]) {
    case xPDOTransport::ACTION_UNINSTALL:
        $componentPath = MODX_CORE_PATH . 'components/localizator3/';
        $uninstallFile = $componentPath . 'uninstall_elements.php';

        if (!file_exists($uninstallFile)) {
            $modx->log(modX::LOG_LEVEL_ERROR, '[Localizator3] uninstall_elements.php not found at: ' . $uninstallFile);
            break;
        }

        require_once $uninstallFile;
        localizator3RemovePlugins($modx);
        break;
}

return true;
