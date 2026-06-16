<?php

$properties = array();

$tmp = array(
    'id' => array(
        'type' => 'numberfield',
        'value' => '',
    ),
    'language' => array(
        'type' => 'textfield',
        'value' => '',
    ),
    'scheme' => array(
        'type' => 'numberfield',
        'value' => -1,
    ),
    'fullUrl' => array(
        'type' => 'combo-boolean',
        'value' => false,
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
