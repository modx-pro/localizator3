<?php

/** @var xPDOTransport $transport */
/** @var array $options */
/** @var modX $modx */

if (!$transport->xpdo || !($transport instanceof xPDOTransport)) {
    return false;
}

$modx = $transport->xpdo;

switch ($options[xPDOTransport::PACKAGE_ACTION]) {
    case xPDOTransport::ACTION_INSTALL:
    case xPDOTransport::ACTION_UPGRADE:
        @ini_set('max_execution_time', 300);
        @ini_set('memory_limit', '256M');

        $componentPath = MODX_CORE_PATH . 'components/localizator3/';
        $vendorAutoload = $componentPath . 'vendor/autoload.php';
        $phinxConfig = $componentPath . 'phinx.php';

        if (!file_exists($vendorAutoload)) {
            $modx->log(modX::LOG_LEVEL_ERROR, '[Localizator3] Phinx vendor/autoload.php not found at: ' . $vendorAutoload);
            $modx->log(modX::LOG_LEVEL_ERROR, '[Localizator3] Run "composer install" in: ' . $componentPath);
            break;
        }

        if (!file_exists($phinxConfig)) {
            $modx->log(modX::LOG_LEVEL_ERROR, '[Localizator3] Phinx config not found at: ' . $phinxConfig);
            break;
        }

        try {
            if (!class_exists('Phinx\Config\Config')) {
                require_once $vendorAutoload;
            }

            require_once $componentPath . 'phinx_repair.php';
            localizator3RepairPhinxTablePrefixes($modx);

            $configArray = require $phinxConfig;

            if (!isset($configArray['paths']['migrations'])) {
                $modx->log(modX::LOG_LEVEL_ERROR, '[Localizator3] Invalid Phinx config: missing migrations path');
                break;
            }

            $config = new \Phinx\Config\Config($configArray);
            $input = new \Symfony\Component\Console\Input\StringInput('');
            $output = new \Symfony\Component\Console\Output\BufferedOutput();
            $manager = new \Phinx\Migration\Manager($config, $input, $output);

            $modx->log(modX::LOG_LEVEL_INFO, '[Localizator3] Starting database migrations...');

            try {
                $manager->migrate('production');
            } catch (Exception $migrateEx) {
                $modx->log(modX::LOG_LEVEL_ERROR, '[Localizator3] Migration failed: ' . $migrateEx->getMessage());
                throw $migrateEx;
            }

            $outputText = $output->fetch();
            if (!empty($outputText)) {
                foreach (explode("\n", $outputText) as $line) {
                    if (!empty(trim($line))) {
                        $modx->log(modX::LOG_LEVEL_INFO, ' ' . $line);
                    }
                }
            }

            $modx->log(modX::LOG_LEVEL_INFO, '[Localizator3] Database migrations completed');
        } catch (Exception $e) {
            $modx->log(modX::LOG_LEVEL_ERROR, '[Localizator3] Migration error: ' . $e->getMessage());
            $modx->log(modX::LOG_LEVEL_ERROR, '[Localizator3] ' . $e->getTraceAsString());
            throw $e;
        }
        break;

    case xPDOTransport::ACTION_UNINSTALL:
        $modx->log(modX::LOG_LEVEL_INFO, '[Localizator3] Database tables preserved during uninstall');
        $modx->log(modX::LOG_LEVEL_INFO, '[Localizator3] To remove tables: DROP TABLE {table_prefix}localizator3_*');
        break;
}

return true;
