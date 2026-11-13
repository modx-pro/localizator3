<?php

return [
    'localizator3' => [
        'file' => 'plugin.localizator',
        'description' => 'localizator3_plugin_desc',
        'events' => [
            'OnTVFormPrerender' => [],
            'OnTVFormSave' => [],
            'OnDocFormPrerender' => [],
            // Load FetchLocalizator autoload before pdoTools/MS3 factories resolve pdotools_fetch_class
            'OnMODXInit' => ['priority' => -1000],
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
