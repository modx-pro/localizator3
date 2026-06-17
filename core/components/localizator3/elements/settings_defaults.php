<?php

return [
    'default_language' => [
        'xtype' => 'textfield',
        'area' => 'localizator3_main',
    ],
    'check_permissions' => [
        'xtype' => 'combo-boolean',
        'value' => false,
        'area' => 'localizator3_main',
    ],
    'disabled_templates' => [
        'xtype' => 'textfield',
        'value' => '',
        'area' => 'localizator3_main',
    ],
    '404_if_no_localization' => [
        'xtype' => 'combo-boolean',
        'value' => false,
        'area' => 'localizator3_main',
    ],
    'auto_detect_language' => [
        'xtype' => 'combo-boolean',
        'value' => false,
        'area' => 'localizator3_main',
    ],
    'debug_log' => [
        'xtype' => 'combo-boolean',
        'value' => false,
        'area' => 'localizator3_main',
    ],
    'default_translator' => [
        'xtype' => 'textfield',
        'value' => 'Yandex',
        'area' => 'localizator3_translator',
    ],
    'key_yandex' => [
        'xtype' => 'textfield',
        'area' => 'localizator3_translator',
    ],
    'key_google' => [
        'xtype' => 'textfield',
        'area' => 'localizator3_translator',
    ],
    'key_deepl' => [
        'xtype' => 'textfield',
        'area' => 'localizator3_translator',
    ],
    'libretranslate_url' => [
        'xtype' => 'textfield',
        'value' => 'http://localhost:5000',
        'area' => 'localizator3_translator',
    ],
    'key_libretranslate' => [
        'xtype' => 'textfield',
        'area' => 'localizator3_translator',
    ],
    'mymemory_email' => [
        'xtype' => 'textfield',
        'area' => 'localizator3_translator',
    ],
    'translate_translated' => [
        'xtype' => 'combo-boolean',
        'value' => false,
        'area' => 'localizator3_translator',
    ],
    'translate_translated_fields' => [
        'xtype' => 'combo-boolean',
        'value' => false,
        'area' => 'localizator3_translator',
    ],
    'translate_fields' => [
        'xtype' => 'textfield',
        'value' => 'pagetitle,longtitle,menutitle,seotitle,keywords,introtext,description,content',
        'area' => 'localizator3_translator',
    ],
    'tv_fields' => [
        'xtype' => 'textfield',
        'value' => '',
        'area' => 'localizator3_main',
    ],
];
