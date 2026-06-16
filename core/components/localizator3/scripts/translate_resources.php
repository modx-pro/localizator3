#!/usr/bin/env php
<?php

/**
 * CLI: массовый перевод ресурсов (Localizator3).
 *
 * Использование:
 *   php core/components/localizator3/scripts/translate_resources.php --ids=1,2,3
 *   php core/components/localizator3/scripts/translate_resources.php --parents=1 --depth=2
 *
 * Параметры:
 *   --ids=1,2,3     ID ресурсов через запятую
 *   --parents=1     ID родителя (перевести все дочерние)
 *   --depth=10      Глубина при --parents (по умолчанию 10)
 *   --dry-run       Только показать, что будет переведено
 */

$basePath = getenv('MODX_BASE_PATH') ?: dirname(dirname(dirname(dirname(__DIR__))));
$configPath = rtrim($basePath, '/') . '/config.core.php';
if (!file_exists($configPath)) {
    fwrite(STDERR, "MODX config.core.php not found. Set MODX_BASE_PATH if running from repo.\n");
    exit(1);
}
require_once $configPath;
require_once MODX_CORE_PATH . 'config/' . MODX_CONFIG_KEY . '.inc.php';

$modx = \MODX\Revolution\modX::getInstance(null, [
    \xPDO\xPDO::OPT_CONN_INIT => [\xPDO\xPDO::OPT_CONN_MUTABLE => true],
]);
$modx->initialize('mgr');
$modx->setLogLevel(\MODX\Revolution\modX::LOG_LEVEL_WARN);
$modx->setLogTarget('ECHO');

$ids = [];
if (!empty($argv)) {
    foreach ($argv as $arg) {
        if (strpos($arg, '--ids=') === 0) {
            $ids = array_filter(array_map('intval', explode(',', substr($arg, 6))));
            break;
        }
        if (strpos($arg, '--parents=') === 0) {
            $parents = array_map('intval', explode(',', substr($arg, 10)));
            $depth = 10;
            foreach ($argv as $a) {
                if (strpos($a, '--depth=') === 0) {
                    $depth = (int) substr($a, 8);
                    break;
                }
            }
            $resourceContext = getenv('LOCALIZATOR_CONTEXT') ?: 'web';
            $ids = array_unique(array_merge(
                $parents,
                $modx->getChildIds($parents, $depth, ['context' => $resourceContext])
            ));
            break;
        }
    }
}

$dryRun = in_array('--dry-run', $argv);

if (empty($ids)) {
    fwrite(STDERR, "Usage: php translate_resources.php --ids=1,2,3\n");
    fwrite(STDERR, "   or: php translate_resources.php --parents=1 --depth=5\n");
    exit(1);
}

$localizator = $modx->getService('localizator3', 'localizator', $modx->getOption('localizator3_core_path', null, $modx->getOption('core_path') . 'components/localizator3/') . 'model/localizator3/');
if (!$localizator || !($localizator instanceof localizator)) {
    fwrite(STDERR, "Localizator3 service not found.\n");
    exit(1);
}

$defaultLang = $modx->getOption('localizator3_default_language', null, '');
if (empty($defaultLang)) {
    fwrite(STDERR, "Setting localizator3_default_language is required.\n");
    exit(1);
}

$translated = 0;
$errors = 0;

foreach ($ids as $resourceId) {
    $resource = $modx->getObject(\MODX\Revolution\modResource::class, $resourceId);
    if (!$resource || $resource->get('deleted')) {
        continue;
    }

    if ($dryRun) {
        echo "Would translate resource {$resourceId} ({$resource->get('pagetitle')})\n";
        $translated++;
        continue;
    }

    $response = $modx->runProcessor('mgr/content/translate', [
        'resource_id' => $resourceId,
    ], [
        'processors_path' => $localizator->config['processorsPath'],
    ]);

    if ($response->isError()) {
        fwrite(STDERR, "Error resource {$resourceId}: " . $response->getMessage() . "\n");
        $errors++;
    } else {
        $data = $response->getResponse();
        if (is_array($data) && isset($data['object']['processed'])) {
            echo "Translated resource {$resourceId}: {$data['object']['processed']} languages\n";
        } else {
            echo "Translated resource {$resourceId}\n";
        }
        $translated++;
    }
}

echo "\nDone: {$translated} resources, {$errors} errors.\n";
exit($errors > 0 ? 1 : 0);
