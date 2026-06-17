<?php

/** @var xPDOTransport $transport */
/** @var array $options */
/** @var modX $modx */
if ($transport->xpdo) {
    $modx =& $transport->xpdo;
    switch ($options[xPDOTransport::PACKAGE_ACTION]) {
        case xPDOTransport::ACTION_INSTALL:
            break;
        case xPDOTransport::ACTION_UPGRADE:
            $corePath = $modx->getOption('localizator3_core_path', null,
                $modx->getOption('core_path') . 'components/localizator3/');
            $modx->addPackage('localizator3', $corePath . 'model/', null, 'localizator3\\');
            $classDir = $corePath . 'model/localizator3/';
            if (is_dir($classDir) && class_exists('xPDO\\xPDO')) {
                \xPDO\xPDO::getLoader()->addPsr4('localizator3\\', $classDir);
            }

            $tableName = $modx->getTableName(\localizator3\localizatorContent::class);
            if (!$tableName) {
                break;
            }
            $upd = $modx->prepare("UPDATE {$tableName} SET `content` = ? WHERE `resource_id` = ?");

            $q = $modx->newQuery(\MODX\Revolution\modResource::class);
            $q->innerJoin(\localizator3\localizatorContent::class, 'localizatorContent', 'localizatorContent.resource_id = modResource.id');
            $q->where([
                'modResource.class_key:IN' => [
                    \MODX\Revolution\modStaticResource::class,
                    \MODX\Revolution\modSymLink::class,
                    \MODX\Revolution\modWebLink::class,
                ],
            ]);
            $q->select('modResource.id, modResource.content');
            if ($q->prepare() && $q->stmt->execute()) {
                while ($row = $q->stmt->fetch(PDO::FETCH_ASSOC)) {
                    $upd->execute([$row['content'], $row['id']]);
                }
            }
            break;
        case xPDOTransport::ACTION_UNINSTALL:
            break;
    }
}
return true;
