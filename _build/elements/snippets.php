<?php

return [
    'getLocalizedResources' => [
        'file' => 'localizator',
        'description' => 'localizator_snippet_getlocalizedresources_desc',
        'properties' => include __DIR__ . '/../properties/properties.localizator.php',
    ],
    'getLanguageList' => [
        'file' => 'getlocales',
        'description' => 'localizator_snippet_getlanguagelist_desc',
        'properties' => include __DIR__ . '/../properties/properties.getlocales.php',
    ],
    'makeLocalizedUrl' => [
        'file' => 'makelocalizedurl',
        'description' => 'localizator_snippet_makelocalizedurl_desc',
        'properties' => include __DIR__ . '/../properties/properties.makelocalizedurl.php',
    ],
    'getLocalizedCanonical' => [
        'file' => 'getlocalizedcanonical',
        'description' => 'localizator_snippet_getlocalizedcanonical_desc',
        'properties' => include __DIR__ . '/../properties/properties.getlocalizedcanonical.php',
    ],
    'getLocalizedSitemap' => [
        'file' => 'getlocalizedsitemap',
        'description' => 'localizator_snippet_getlocalizedsitemap_desc',
        'properties' => include __DIR__ . '/../properties/properties.getlocalizedsitemap.php',
    ],
    'getLocalizedField' => [
        'file' => 'getlocalizedfield',
        'description' => 'localizator_snippet_getlocalizedfield_desc',
        'properties' => include __DIR__ . '/../properties/properties.getlocalizedfield.php',
    ],
];
