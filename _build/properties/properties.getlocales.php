<?php

$properties = array();

$tmp = array(
    'outputMode' => array(
        'type' => 'textfield',
        'value' => 'list',
    ),
    'showActive' => array(
        'type' => 'combo-boolean',
        'value' => true,
    ),
    'activeClass' => array(
        'type' => 'textfield',
        'value' => 'active',
    ),
    'outerClass' => array(
        'type' => 'textfield',
        'value' => 'languages',
    ),
    'rowClass' => array(
        'type' => 'textfield',
        'value' => '',
    ),
    'tpl' => array(
        'type' => 'textfield',
        'value' => '',
    ),
    'pageId' => array(
        'type' => 'numberfield',
        'value' => '',
    ),
    'sortby' => array(
        'type' => 'textfield',
        'value' => 'rank',
    ),
    'sortdir' => array(
        'type' => 'textfield',
        'value' => 'ASC',
    ),
    'where' => array(
        'type' => 'textfield',
        'value' => '',
    ),
);

foreach ($tmp as $k => $v) {
    $properties[] = array_merge(
        array(
            'name' => $k,
            'desc' => 'localizator3_prop_' . $k,
            'lexicon' => 'localizator3:properties',
        ),
        $v
    );
}

return $properties;
