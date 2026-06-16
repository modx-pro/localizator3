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
            'desc' => PKG_NAME_LOWER . '_prop_' . $k,
            'lexicon' => PKG_NAME_LOWER . ':properties',
        ),
        $v
    );
}

return $properties;
