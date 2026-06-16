<?php

$policies = array();
/** @var modAccessPolicy $policy */
$policy = $modx->newObject('modAccessPolicy');
$policy->fromArray(array(
    'name' => 'LocalizatorManagerPolicy',
    'description' => 'A policy for create and update LocalizatorContent.',
    'parent' => 0,
    'class' => '',
    'lexicon' => 'localizator3:permissions',
    'data' => json_encode(array(
        'localizatorcontent_list' => true,
    )),
), '', true, true);
$policies[] = $policy;
return $policies;
