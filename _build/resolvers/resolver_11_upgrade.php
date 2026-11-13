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

            $manager = $modx->getManager();

            $include = include($modx->getOption('core_path') . 'components/localizator3/model/localizator3/plugin.mysql.inc.php');
            if (is_array($include)) {
                $processed = array();
                foreach ($include as $class => $map) {
                    $mappedClass = $modx->loadClass($class);
                    if (!$mappedClass) {
                        $mappedClass = $class;
                    }
                    if (isset($processed[$mappedClass])) {
                        continue;
                    }
                    if (!isset($modx->map[$mappedClass])) {
                        continue;
                    }
                    $processed[$mappedClass] = true;

                    // MODX 3: xPDOMap — вложенное присваивание не сохраняется,
                    // merge на копии + запись обратно всей карты (см. OnMODXInit).
                    $currentMap = $modx->map[$mappedClass];
                    foreach ($map as $key => $values) {
                        $current = $currentMap[$key] ?? array();
                        $currentMap[$key] = array_merge($current, $values);
                    }
                    $modx->map[$mappedClass] = $currentMap;

                    $tableFields = array();
                    $table = $modx->getTableName($mappedClass);
                    $c = $modx->prepare("SHOW COLUMNS IN {$table}");
                    $c->execute();
                    while ($cl = $c->fetch(PDO::FETCH_ASSOC)) {
                        $tableFields[$cl['Field']] = $cl['Field'];
                    }
                    foreach ($map['fieldMeta'] as $field => $v) {
                        if (in_array($field, $tableFields)) {
                            unset($tableFields[$field]);
                            $manager->alterField($mappedClass, $field);
                        } else {
                            $manager->addField($mappedClass, $field);
                        }
                    }

                    $indexes = array();
                    $c = $modx->prepare("SHOW INDEX FROM {$table}");
                    $c->execute();
                    while ($cl = $c->fetch(PDO::FETCH_ASSOC)) {
                        $indexes[$cl['Key_name']] = $cl['Key_name'];
                    }
                    foreach ($map['indexes'] as $name => $meta) {
                        if (in_array($name, $indexes)) {
                            unset($indexes[$name]);
                        } else {
                            $manager->addIndex($mappedClass, $name);
                        }
                    }
                }
            }

            // Если на сайте ещё есть системная настройка localizator_tv_fields
            // со списком имён TV — включить им localizator3_enabled=1.
            // Иначе ничего не трогаем: новые TV по умолчанию выключены.
            $fields_in = $fields_out = $criteria = array();
            if ($fields = $modx->getOption('localizator_tv_fields', null, false, true)) {
                $fields = array_map('trim', explode(',', $fields));

                foreach ($fields as $v) {
                    if (is_numeric($v) || $v === '') {
                        continue;
                    }

                    if ($v[0] == '-') {
                        $fields_out[] = substr($v, 1);
                    } else {
                        $fields_in[] = $v;
                    }
                }

                if (!empty($fields_in)) {
                    $criteria['name:IN'] = $fields_in;
                } elseif (!empty($fields_out)) {
                    $criteria['name:NOT IN'] = $fields_out;
                }
            }

            if (!empty($criteria)) {
                $c = $modx->newQuery(\MODX\Revolution\modTemplateVar::class);
                $c->command('update');
                $c->set(array(
                    'localizator3_enabled' => 1
                ));
                $c->where($criteria);
                $c->prepare();
                if ($c->stmt) {
                    $c->stmt->execute();
                }
            }

            $modx->removeCollection(\MODX\Revolution\modSystemSetting::class, [
                'key' => 'localizator3_tv_fields',
            ]);

            $menu = $modx->getObject(\MODX\Revolution\modMenu::class, [
                'text' => 'localizator3',
                'namespace' => 'localizator3',
            ]);
            if ($menu) {
                $menu->set('text', 'Localizator3');
                $menu->save();
            }

            $settingKey = 'localizator3_default_from_resource';
            $exists = $modx->getObject(\MODX\Revolution\modSystemSetting::class, [
                'key' => $settingKey,
                'namespace' => 'localizator3',
            ]);
            if (!$exists) {
                $setting = $modx->newObject(\MODX\Revolution\modSystemSetting::class);
                $setting->fromArray([
                    'key' => $settingKey,
                    'namespace' => 'localizator3',
                    'value' => '0',
                    'xtype' => 'combo-boolean',
                    'area' => 'localizator3_main',
                ], '', true, true);
                $setting->save();
            }

            break;

        case xPDOTransport::ACTION_UNINSTALL:
            break;
    }
}
return true;
