# Интеграция mSearch — Localizator3

Полнотекстовый поиск [mSearch](https://modstore.pro/packages/ecommerce/msearch) (MODX 3) с учётом локализованного контента.

**Версия:** 1.0.8-beta · [Оглавление документации](./README.md)

---

## Как работает

При индексации ресурса плагин Localizator3 добавляет в индекс поля для каждого активного языка:

- `ru-pagetitle`, `ru-description`, `ru-content` — для русского
- `en-pagetitle`, `en-description`, `en-content` — для английского
- и т.п.

Веса полей соответствуют настройке `mse_index_fields`.

---

## Настройка

1. Установите **mSearch** и **Localizator3**.
2. Создайте языки в Localizator3 → Языки.
3. Добавьте локализации к ресурсам.
4. Запустите индексацию: **mSearch → Индексация**.
5. При обновлении локализации **сохраните ресурс** для переиндексации.

---

## Вывод на фронте

```fenom
{var $lang_key = $_modx->getOption('localizator3_key', null, '')}
{var $index_fields = ($_modx->getOption('mse_index_fields', null, 'pagetitle:3,longtitle:2,description:2,introtext:2,content:1') | split : ',')}
{if $lang_key && $lang_key != $_modx->getOption('localizator3_default_language', null, '', true)}
    {var $search_fields = []}
    {foreach $index_fields as $f}
        {var $parts = ($f | trim | split : ':')}
        {var $search_fields[] = ($lang_key ~ '-' ~ $parts[0] ~ ':' ~ ($parts[1] ?? '1'))}
    {/foreach}
    {var $fields_param = ($search_fields | join : ',')}
{else}
    {var $fields_param = ''}
{/if}

{'!mSearchForm' | snippet : ['pageId' => $_modx->resource.id, 'autocomplete' => 1]}

{'!mSearch' | snippet : [
    'tpl' => 'mSearch.row',
    'limit' => 10,
    'fields' => $fields_param,
]}
```

Для языка по умолчанию `fields` можно не передавать — поиск идёт по стандартным полям. Для остальных языков указываются поля с префиксом `{key}-`.

---

## События

| Событие | Описание |
|---------|----------|
| `mseOnBeforeIndex` | Добавляет локализованный контент в индекс |
| `mseOnGetWorkFields` | Расширяет поля индекса: `{key}-{field}` для каждого языка |

---

## Совместимость

Обработчики поддерживают оба варианта API mSearch:

- `resource` — объект ресурса
- `fields` или `workFields` — массив `[field => weight]` или строка `pagetitle:3,content:1`

При несовпадении API обработчики завершатся без изменений.

---

## См. также

- [Оглавление](./README.md)
- [mSearch — документация](https://docs.modx.pro/components/msearch/)
- [integration-minishop3.md](./integration-minishop3.md)
- [api.md](./api.md)
