<?php

$properties = array();

$tmp = array(
    'id' => array(
        'type' => 'numberfield',
        'value' => '',
    ),
    'field' => array(
        'type' => 'textfield',
        'value' => 'pagetitle',
    ),
    'language' => array(
        'type' => 'textfield',
        'value' => '',
    ),
    'default' => array(
        'type' => 'textfield',
        'value' => '',
    ),
);

foreach ($tmp as $k => $v) {
    $properties[] = array_merge(
        array(
            'name' => $k,
            'desc' => 'localizator3' . '_prop_' . $k,
            'lexicon' => 'localizator3' . ':properties',
        ),
        $v
    );
}

return $properties;
