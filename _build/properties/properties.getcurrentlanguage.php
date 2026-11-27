<?php

$properties = array();

$tmp = array(
    'field' => array(
        'type' => 'textfield',
        'value' => 'key',
    ),
    'format' => array(
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
