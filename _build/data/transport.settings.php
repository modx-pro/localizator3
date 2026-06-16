<?php

/** @var modX $modx */
/** @var array $sources */

$settings = array();

$tmp = array(
    'default_language' => array(
        'xtype' => 'textfield',
        'area' => 'localizator3_main',
    ),
    'check_permissions' => array(
        'xtype' => 'combo-boolean',
        'value' => false,
        'area' => 'localizator3_main',
    ),
    'disabled_templates' => array(
        'xtype' => 'textfield',
        'value' => '',
        'area' => 'localizator3_main',
    ),
    '404_if_no_localization' => array(
        'xtype' => 'combo-boolean',
        'value' => false,
        'area' => 'localizator3_main',
    ),
    'auto_detect_language' => array(
        'xtype' => 'combo-boolean',
        'value' => false,
        'area' => 'localizator3_main',
    ),

    // translator
    'default_translator' => array(
        'xtype' => 'textfield',
        'value' => 'Yandex',
        'area' => 'localizator3_translator',
    ),
    'key_yandex' => array(
        'xtype' => 'textfield',
        'area' => 'localizator3_translator',
    ),
    'key_google' => array(
        'xtype' => 'textfield',
        'area' => 'localizator3_translator',
    ),
    'key_deepl' => array(
        'xtype' => 'textfield',
        'area' => 'localizator3_translator',
    ),
    'libretranslate_url' => array(
        'xtype' => 'textfield',
        'value' => 'http://localhost:5000',
        'area' => 'localizator3_translator',
    ),
    'key_libretranslate' => array(
        'xtype' => 'textfield',
        'area' => 'localizator3_translator',
    ),
    'mymemory_email' => array(
        'xtype' => 'textfield',
        'area' => 'localizator3_translator',
    ),
    'translate_translated' => array(
        'xtype' => 'combo-boolean',
        'value' => false,
        'area' => 'localizator3_translator',
    ),
    'translate_translated_fields' => array(
        'xtype' => 'combo-boolean',
        'value' => false,
        'area' => 'localizator3_translator',
    ),
    'translate_fields' => array(
        'xtype' => 'textfield',
        'value' => 'pagetitle,longtitle,menutitle,seotitle,keywords,introtext,description,content',
        'area' => 'localizator3_translator',
    ),

    'debug_log' => array(
        'xtype' => 'combo-boolean',
        'value' => false,
        'area' => 'localizator3_main',
    ),
);

foreach ($tmp as $k => $v) {
    /** @var modSystemSetting $setting */
    $setting = $modx->newObject('modSystemSetting');
    $setting->fromArray(array_merge(
        array(
            'key' => 'localizator3_' . $k,
            'namespace' => PKG_NAME_LOWER,
        ),
        $v
    ), '', true, true);

    $settings[] = $setting;
}
unset($tmp);

return $settings;
