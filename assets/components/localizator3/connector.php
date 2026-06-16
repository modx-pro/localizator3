<?php
/** Suppress PHP errors in output — connector must return valid JSON */
if (php_sapi_name() !== 'cli') {
    @ini_set('display_errors', 0);
}
set_error_handler(function ($severity, $message, $file, $line) {
    if (error_reporting() & $severity) {
        throw new ErrorException($message, 0, $severity, $file, $line);
    }
});
try {
if (file_exists(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.core.php')) {
    /** @noinspection PhpIncludeInspection */
    require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/config.core.php';
}
else {
    require_once dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/config.core.php';
}
/** @noinspection PhpIncludeInspection */
require_once MODX_CORE_PATH . 'config/' . MODX_CONFIG_KEY . '.inc.php';
/** @noinspection PhpIncludeInspection */
require_once MODX_CONNECTORS_PATH . 'index.php';
$corePath = $modx->getOption('localizator3_core_path', null, $modx->getOption('core_path') . 'components/localizator3/');
$modx->addPackage('localizator3', $corePath . 'model/', null, 'localizator3\\');
/** @var localizator $localizator */
$localizator = $modx->getService('localizator3', 'localizator', $corePath . 'model/localizator3/');
$modx->lexicon->load('localizator3:default');

// handle request
$path = $modx->getOption('processorsPath', $localizator->config, $corePath . 'processors/');
$modx->getRequest();

/** @var modConnectorRequest $request */
$request = $modx->request;
$request->handleRequest(array(
    'processors_path' => $path,
    'location' => '',
));
} catch (Throwable $e) {
    header('Content-Type: application/json; charset=UTF-8');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
    ]);
} finally {
    restore_error_handler();
}