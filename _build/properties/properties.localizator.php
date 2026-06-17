<?php

$properties = array();

$tmp = array(
    'snippet' => array(
        'type' => 'textfield',
        'value' => 'pdoResources',
    ),
    'class' => array(
        'type' => 'textfield',
        'value' => 'modResource',
    ),
    'localizator3_key' => array(
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
