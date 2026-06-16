<?php

$properties = array();

$tmp = array(
    'parents' => array(
        'type' => 'textfield',
        'value' => '',
    ),
    'depth' => array(
        'type' => 'numberfield',
        'value' => '10',
    ),
    'excludeIds' => array(
        'type' => 'textfield',
        'value' => '',
    ),
    'scheme' => array(
        'type' => 'numberfield',
        'value' => '0',
    ),
    'onlyWithLocalization' => array(
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
