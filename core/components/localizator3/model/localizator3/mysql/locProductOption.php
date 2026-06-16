<?php
namespace localizator3\mysql;

use xPDO\xPDO;

class locProductOption extends \localizator3\locProductOption
{

    public static $metaMap = array (
        'package' => 'localizator3',
        'version' => '3.0',
        'table' => 'localizator3_product_option',
        'extends' => 'xPDOSimpleObject',
        'tableMeta' => 
        array (
            'engine' => 'InnoDB',
        ),
        'fields' => 
        array (
            'product_option_id' => 0,
            'key' => '',
            'value' => NULL,
        ),
        'fieldMeta' => 
        array (
            'product_option_id' => 
            array (
                'dbtype' => 'int',
                'precision' => '10',
                'attributes' => 'unsigned',
                'phptype' => 'integer',
                'null' => false,
                'default' => 0,
                'index' => 'index',
            ),
            'key' => 
            array (
                'dbtype' => 'varchar',
                'precision' => '100',
                'phptype' => 'string',
                'null' => false,
                'default' => '',
                'index' => 'index',
            ),
            'value' => 
            array (
                'dbtype' => 'text',
                'phptype' => 'string',
                'null' => true,
            ),
        ),
        'indexes' => 
        array (
            'product_option_lang' => 
            array (
                'alias' => 'product_option_lang',
                'primary' => false,
                'unique' => true,
                'type' => 'BTREE',
                'columns' => 
                array (
                    'product_option_id' => 
                    array (
                        'length' => '',
                        'collation' => 'A',
                        'null' => false,
                    ),
                    'key' => 
                    array (
                        'length' => '',
                        'collation' => 'A',
                        'null' => false,
                    ),
                ),
            ),
        ),
    );

}
