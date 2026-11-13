<?php
/**
 * Register FetchLocalizator before modX::initialize().
 * pdoTools / MiniShop3 resolve pdotools_fetch_class during OnMODXInit.
 */
if (!defined('MODX_CORE_PATH')) {
    return;
}

$pdoAutoload = MODX_CORE_PATH . 'components/pdotools/vendor/autoload.php';
if (is_file($pdoAutoload)) {
    require_once $pdoAutoload;
}

$localizatorAutoload = MODX_CORE_PATH . 'components/localizator3/vendor/autoload.php';
if (is_file($localizatorAutoload)) {
    require_once $localizatorAutoload;
}
