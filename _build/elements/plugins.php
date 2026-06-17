<?php

return [
    'localizator3' => [
        'file' => 'localizator',
        'description' => 'localizator3_plugin_desc',
        'events' => [
            'OnTVFormPrerender' => [],
            'OnDocFormPrerender' => [],
            'OnMODXInit' => [],
            'OnHandleRequest' => [],
            'OnPageNotFound' => [],
            'OnLoadWebDocument' => ['priority' => 10],
            'OnDocFormSave' => [],
            'OnEmptyTrash' => [],
            'pdoToolsOnFenomInit' => [],
            'mseOnBeforeIndex' => [],
            'mseOnGetWorkFields' => [],
        ],
    ],
];
