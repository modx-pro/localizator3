<?php

$map = array(
    'fields' =>
    array(
        'localizator3_enabled' => 0,
    ),
    'fieldMeta' =>
    array(
        'localizator3_enabled' => array(
            'dbtype'        => 'tinyint',
            'precision'     => 1,
            'attributes'    => 'unsigned',
            'phptype'       => 'boolean',
            'null'          => false,
            'default'       => 0,
            'index'         => 'index',
        ),
    ),
    'indexes' =>
    array(
        'localizator3_enabled' =>
        array(
            'alias'     => 'localizator3_enabled',
            'primary'   => false,
            'unique'    => false,
            'type'      => 'BTREE',
            'columns'   =>
            array(
                'localizator3_enabled' =>
                array(
                    'length' => '',
                    'collation' => 'A',
                    'null' => false,
                ),
            ),
        )
    ),
);

return array(
    // MODX 3: xPDO map is keyed by FQCN; legacy key kept for MODX 2 compat.
    'MODX\\Revolution\\modTemplateVar' => $map,
    'modTemplateVar' => $map,
);
