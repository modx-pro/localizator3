<?php

/** @var modX $modx */
/** @var array $sources */

$events = array();

$tmp = array(
    'OnBuildLocalizationTabs',  //построение табов

    'OnBeforeFindLocalization', //поиск ключа локализации
    'OnFindLocalization',

    //'OnBeforeFindLocalizatorResource',  //поиск ресурса локализации
    'OnFindLocalizatorResource',

    'OnBeforeSaveLocalization', //сохранение локализаций
    'OnSaveLocalization',

    'OnBeforeRemoveLocalization',   //удаление локализаций
    'OnRemoveLocalization',

    'OnBeforeSaveLocalizatorLanguage',  //сохранение ключей локализаций
    'OnSaveLocalizatorLanguage',

    'OnHasLocalizatorError404',  //перед 404 при отсутствии локализации (кастомизация)

    'OnToggleLocalizatorLanguage',  // при переключении языка (валидация, кэш, интеграции)
);

foreach ($tmp as $k => $v) {
    /** @var modEvent $event */
    $event = $modx->newObject('modEvent');
    $event->fromArray(
        array(
            'name' => $v,
            'groupname' =>  PKG_NAME,
            'service' =>  6,
        ),
        '',
        true,
        true
    );

    $events[] = $event;
}
unset($tmp);

return $events;
