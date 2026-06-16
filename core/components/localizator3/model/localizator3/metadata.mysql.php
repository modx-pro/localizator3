<?php

/**
 * xPDO registers PSR-4 as namespacePrefix\ → $path (model/).
 * Our class files reside in model/localizator3/, so we add the correct path.
 */
if (class_exists('xPDO\\xPDO')) {
    \xPDO\xPDO::getLoader()->addPsr4('localizator3\\', __DIR__ . '/');
}

$xpdo_meta_map = [
    'version' => '3.0',
    'namespace' => 'localizator3',
    'namespacePrefix' => 'localizator3',
    'class_map' => [
        'xPDO\\Om\\xPDOSimpleObject' => [
            'localizator3\\localizatorLanguage',
            'localizator3\\localizatorContent',
            'localizator3\\locTemplateVarResource',
            'localizator3\\locOption',
            'localizator3\\locProductOption',
        ],
    ],
];
