<?php
/**
 * Namespace bootstrap — loaded by modX::_initNamespaces() before OnMODXInit.
 *
 * Registers Composer autoload so ModxPro\PdoTools\FetchLocalizator is available
 * when pdoTools resolves pdotools_fetch_class (pdoTools vendor only knows its own src/).
 *
 * @see https://github.com/modx-pro/localizator3/issues/38
 */

$autoload = __DIR__ . '/vendor/autoload.php';
if (is_file($autoload)) {
    require_once $autoload;
}
