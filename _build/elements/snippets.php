<?php

return [
    'getLocalizedResources' => [
        'file' => 'snippet.localizator',
        'description' => 'localizator_snippet_getlocalizedresources_desc',
        'properties' => include __DIR__ . '/../properties/properties.localizator.php',
    ],
    'getLanguageList' => [
        'file' => 'snippet.getlocales',
        'description' => 'localizator_snippet_getlanguagelist_desc',
        'properties' => include __DIR__ . '/../properties/properties.getlocales.php',
    ],
    'makeLocalizedUrl' => [
        'file' => 'snippet.makelocalizedurl',
        'description' => 'localizator_snippet_makelocalizedurl_desc',
        'properties' => include __DIR__ . '/../properties/properties.makelocalizedurl.php',
    ],
    'getLocalizedCanonical' => [
        'file' => 'snippet.getlocalizedcanonical',
        'description' => 'localizator_snippet_getlocalizedcanonical_desc',
        'properties' => include __DIR__ . '/../properties/properties.getlocalizedcanonical.php',
    ],
    'getLocalizedSitemap' => [
        'file' => 'snippet.getlocalizedsitemap',
        'description' => 'localizator_snippet_getlocalizedsitemap_desc',
        'properties' => include __DIR__ . '/../properties/properties.getlocalizedsitemap.php',
    ],
    'getLocalizedField' => [
        'file' => 'snippet.getlocalizedfield',
        'description' => 'localizator_snippet_getlocalizedfield_desc',
        'properties' => include __DIR__ . '/../properties/properties.getlocalizedfield.php',
    ],
];
