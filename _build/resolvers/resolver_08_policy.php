<?php

/** @var xPDOTransport $transport */
/** @var array $options */
/** @var modX $modx */
if ($transport->xpdo) {
    $modx =& $transport->xpdo;
    switch ($options[xPDOTransport::PACKAGE_ACTION]) {
        case xPDOTransport::ACTION_INSTALL:
        case xPDOTransport::ACTION_UPGRADE:
            $modelPath = $modx->getOption(
                'localizator3_core_path',
                null,
                $modx->getOption('core_path') . 'components/localizator3/'
            ) . 'model/';
            $modx->addPackage('localizator3', $modelPath, null, 'localizator3\\');
            $classDir = $modelPath . 'localizator3/';
            if (is_dir($classDir) && class_exists('xPDO\\xPDO')) {
                \xPDO\xPDO::getLoader()->addPsr4('localizator3\\', $classDir);
            }

            $keys = [];
            $c = $modx->newQuery(\localizator3\localizatorLanguage::class)->select('key');
            if ($c->prepare() && $c->stmt->execute()) {
                $keys = $c->stmt->fetchAll(PDO::FETCH_COLUMN);
            }

            $tStart = microtime(true);
            $modx->log(\modX::LOG_LEVEL_INFO, '== SetupPolicy: migrating');

            /** @var modAccessPolicy $policy */
            if ($policy = $modx->getObject(\MODX\Revolution\modAccessPolicy::class, ['name' => 'LocalizatorManagerPolicy'])) {
                $modx->log(\modX::LOG_LEVEL_INFO, '✓ Found LocalizatorManagerPolicy Access Policy');

                if (
                    $template = $modx->getObject(
                        \MODX\Revolution\modAccessPolicyTemplate::class,
                        ['name' => 'LocalizatorManagerPolicyTemplate']
                    )
                ) {
                    $modx->log(\modX::LOG_LEVEL_INFO, '✓ Found LocalizatorManagerPolicyTemplate');
                    $data = $policy->get('data');
                    foreach ($keys as $key) {
                        foreach (\localizator3\localizatorLanguage::$permissions as $tmp) {
                            if (!$permission = $modx->getObject(\MODX\Revolution\modAccessPermission::class, ['name' => "localizatorcontent_{$tmp}_{$key}"])) {
                                $permission = $modx->newObject(\MODX\Revolution\modAccessPermission::class);
                                $permission->fromArray([
                                    'template' => $template->get('id'),
                                    'name' => "localizatorcontent_{$tmp}_{$key}",
                                    'description' => "localizatorcontent_{$tmp}",
                                    'value' => 1,
                                ]);
                                $permission->save();
                            }

                            if (!isset($data["localizatorcontent_{$tmp}_{$key}"])) {
                                $data["localizatorcontent_{$tmp}_{$key}"] = true;
                            }
                        }
                    }
                    $policy->set('data', $data);

                    $policy->set('template', $template->get('id'));
                    $policy->save();
                } else {
                    $modx->log(
                        \modX::LOG_LEVEL_ERROR,
                        '✗ Could not find LocalizatorManagerPolicyTemplate Access Policy Template'
                    );
                }
                if ($adminGroup = $modx->getObject(\MODX\Revolution\modUserGroup::class, ['name' => 'Administrator'])) {
                    $properties = [
                        'target' => 'mgr',
                        'principal_class' => \MODX\Revolution\modUserGroup::class,
                        'principal' => $adminGroup->get('id'),
                        'authority' => 9999,
                        'policy' => $policy->get('id'),
                    ];
                    if (!$modx->getObject(\MODX\Revolution\modAccessContext::class, $properties)) {
                        $access = $modx->newObject(\MODX\Revolution\modAccessContext::class);
                        $access->fromArray($properties);
                        $access->save();
                    }
                }
                $elapsed = number_format(microtime(true) - $tStart, 4);
                $modx->log(\modX::LOG_LEVEL_INFO, '== SetupPolicy: migrated ' . $elapsed . 's');
                break;
            } else {
                $modx->log(\modX::LOG_LEVEL_ERROR, '✗ Could not find LocalizatorManagerPolicy Access Policy');
            }
            break;
    }
}
return true;
