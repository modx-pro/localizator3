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

            $include = include_once($modx->getOption('core_path') . 'components/localizator3/model/localizator3/plugin.mysql.inc.php');
            if (is_array($include)) {
                foreach ($include as $class => $map) {
                    if (!isset($modx->map[$class])) {
                        $modx->loadClass($class);
                    }
                    if (isset($modx->map[$class])) {
                        foreach ($map as $key => $values) {
                            $modx->map[$class][$key] = array_merge($modx->map[$class][$key], $values);
                        }

                        $tableFields = array();
                        $c = $modx->prepare("SHOW COLUMNS IN {$modx->getTableName($class)}");
                        $c->execute();
                        while ($cl = $c->fetch(PDO::FETCH_ASSOC)) {
                            $tableFields[$cl['Field']] = $cl['Field'];
                        }
                        foreach ($map['fieldMeta'] as $field => $v) {
                            if (in_array($field, $tableFields)) {
                                unset($tableFields[$field]);
                                $manager->alterField($class, $field);
                            } else {
                                $manager->addField($class, $field);
                            }
                        }

                        $indexes = array();
                        $c = $modx->prepare("SHOW INDEX FROM {$modx->getTableName($class)}");
                        $c->execute();
                        while ($cl = $c->fetch(PDO::FETCH_ASSOC)) {
                            $indexes[$cl['Key_name']] = $cl['Key_name'];
                        }
                        foreach ($map['indexes'] as $name => $meta) {
                            if (in_array($name, $indexes)) {
                                unset($indexes[$name]);
                            } else {
                                $manager->addIndex($class, $name);
                            }
                        }
                    }
                }
            }

            $fields_in = $fields_out = $criteria = array();
            if ($fields = $modx->getOption('localizator_tv_fields', null, false, true)) {
                $fields = array_map('trim', explode(',', $fields));

                foreach ($fields as $v) {
                    if (is_numeric($v)) {
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

            $c = $modx->newQuery(\MODX\Revolution\modTemplateVar::class);
            $c->command('update');
            $c->set(array(
                'localizator3_enabled' => 1
            ));
            if ($criteria) {
                $c->where($criteria);
            }
            $c->prepare();
            $c->stmt->execute();

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

            break;

        case xPDOTransport::ACTION_UNINSTALL:
            break;
    }
}
return true;
