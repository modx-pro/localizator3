<?php

/** @var modX $modx */
/** @var array $sources */

$snippets = array();

$tmp = array(
    'getLocalizedResources' => array(
        'file' => 'localizator',
        'description' => 'Выборка ресурсов с учётом локализации (обёртка над pdoResources/pdoMenu)',
    ),
    'getLanguageList' => array(
        'file' => 'getlocales',
        'description' => 'Список языков для переключателя',
    ),
    'makeLocalizedUrl' => array(
        'file' => 'makelocalizedurl',
        'description' => 'Формирование URL ресурса с учётом языка',
    ),
    'getLocalizedCanonical' => array(
        'file' => 'getlocalizedcanonical',
        'description' => 'Canonical и hreflang-ссылки для SEO',
    ),
    'getLocalizedSitemap' => array(
        'file' => 'getlocalizedsitemap',
        'description' => 'Генерация sitemap.xml с hreflang для мультиязычных страниц',
    ),
    'getLocalizedField' => array(
        'file' => 'getlocalizedfield',
        'description' => 'Вывод поля ресурса на указанном языке',
    ),
);

foreach ($tmp as $k => $v) {
    /** @var modSnippet $snippet */
    $snippet = $modx->newObject('modSnippet');
    $snippet->fromArray(array(
        'id' => 0,
        'name' => $k,
        'description' => @$v['description'],
        'snippet' => getSnippetContent($sources['source_core'] . '/elements/snippets/snippet.' . $v['file'] . '.php'),
        'static' => BUILD_SNIPPET_STATIC,
        'source' => 1,
        'static_file' => 'core/components/' . PKG_NAME_LOWER . '/elements/snippets/snippet.' . $v['file'] . '.php',
    ), '', true, true);
    /** @noinspection PhpIncludeInspection */
    $properties = include $sources['build'] . 'properties/properties.' . $v['file'] . '.php';
    $snippet->setProperties($properties);

    $snippets[] = $snippet;
}
unset($tmp, $properties);

return $snippets;
