<?php

$templates = array();
$tmp = array(
    'LocalizatorManagerPolicyTemplate' => array(
        'description' => 'A policy for Localizator managers.',
        'template_group' => 1,
        'permissions' => array(
            'localizatorcontent_list' => array(),
            //'localizatorcontent_view_ru' => array(),
            //'localizatorcontent_save_ru' => array(),
        ),
    ),
);
foreach ($tmp as $k => $v) {
    $permissions = array();
    if (isset($v['permissions']) && is_array($v['permissions'])) {
        foreach ($v['permissions'] as $k2 => $v2) {
            /** @var modAccessPermission $event */
            $permission = $modx->newObject('modAccessPermission');
            $permission->fromArray(
                array_merge(array(
                    'name' => $k2,
                    'description' => $k2,
                    'value' => true,
                ), $v2),
                '',
                true,
                true
            );
            $permissions[] = $permission;
        }
    }
    /** @var $template modAccessPolicyTemplate */
    $template = $modx->newObject('modAccessPolicyTemplate');
    $template->fromArray(
        array_merge(array(
            'name' => $k,
            'lexicon' => PKG_NAME_LOWER . ':permissions',
        ), $v),
        '',
        true,
        true
    );
    if (!empty($permissions)) {
        $template->addMany($permissions);
    }
    $templates[] = $template;
}
return $templates;
