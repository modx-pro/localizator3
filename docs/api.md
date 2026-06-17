# API — Localizator3

Сниппеты, Fenom-модификаторы, события, процессоры и CLI.

**Версия:** 1.0.8-beta · [Оглавление документации](./README.md)

---

## Сниппеты

### getLocalizedResources

Выборка ресурсов с учётом локализации. Обёртка над pdoResources / pdoMenu / msProducts.

**Параметры:**

| Имя | Тип | Required | Описание |
|-----|-----|----------|----------|
| snippet | string | No | Имя сниппета: `pdoResources`, `pdoMenu`, `msProducts`, `ms3Products` |
| class | string | No | Класс ресурса |
| localizator3_key | string | No | Ключ языка (по умолчанию — текущий) |
| onlyWithLocalization | bool | No | Только ресурсы с локализацией (INNER JOIN) |

Остальные параметры передаются во внутренний сниппет.

**Пример:**

```fenom
{'!getLocalizedResources' | snippet : [
  'snippet' => 'pdoResources',
  'parents' => 0,
  'tpl' => '@INLINE <li><a href="{$uri}">{$pagetitle}</a></li>',
  'onlyWithLocalization' => 1
]}
```

---

### getLanguageList

Список языков для переключателя. Требует pdoTools.

> Зарегистрированное имя сниппета — **`getLanguageList`**. В старых примерах может встречаться `getLocales` — это некорректное имя для MODX Package Manager.

**Параметры:**

| Имя | Тип | Required | Default | Описание |
|-----|-----|----------|---------|----------|
| outputMode | string | No | list | `list` — список, `dropdown` — выпадающий |
| showActive | bool | No | true | Подсветка текущего языка |
| activeClass | string | No | active | CSS-класс для активного |
| tpl | string | No | languages.tpl | Чанк шаблона |
| pageId | int | No | текущий | ID страницы для URL |
| sortby | string | No | rank | Поле сортировки |
| sortdir | string | No | ASC | Направление сортировки |
| where | array | No | — | Дополнительное условие выборки |

**Пример:**

```fenom
{'!getLanguageList' | snippet}
{'!getLanguageList' | snippet : ['outputMode' => 'dropdown', 'activeClass' => 'current']}
```

---

### makeLocalizedUrl

Формирует URL ресурса с учётом языка.

**Параметры:**

| Имя | Тип | Required | Описание |
|-----|-----|----------|----------|
| id | int | Yes | ID ресурса |
| language | string | No | Ключ языка (по умолчанию — текущий) |
| scheme | int | No | -1: относительный, 0: полный, 1: http, 2: https |
| fullUrl | bool | No | Полный URL с доменом |

**Пример:**

```fenom
<a href="{'!makeLocalizedUrl' | snippet : ['id' => $id]}">{$pagetitle}</a>
```

---

### getLocalizedField

Вывод поля ресурса на указанном языке.

**Параметры:**

| Имя | Тип | Required | Описание |
|-----|-----|----------|----------|
| id | int | No | ID ресурса (по умолчанию — текущий) |
| field | string | No | Имя поля (pagetitle, content, TV) |
| language | string | No | Ключ языка |
| default | string | No | Значение по умолчанию |

**Пример:**

```fenom
{'!getLocalizedField' | snippet : ['field' => 'pagetitle', 'language' => 'en']}
```

---

### getLocalizedCanonical

Canonical и hreflang-ссылки для SEO. Выводит `<link rel="canonical">` и `<link rel="alternate" hreflang="...">` для всех активных языков.

**Параметры:**

| Имя | Тип | Required | Default | Описание |
|-----|-----|----------|---------|----------|
| id | int | No | текущий | ID ресурса |
| tpl | string | No | — | Чанк для кастомного вывода |

**Пример:**

```fenom
{'!getLocalizedCanonical' | snippet}
```

Вывод в `<head>`:

```html
<link rel="canonical" href="https://site.com/page.html" />
<link rel="alternate" hreflang="ru" href="https://site.com/page.html" />
<link rel="alternate" hreflang="en" href="https://en.site.com/page.html" />
```

---

### getLocalizedSitemap

Генерация sitemap.xml с hreflang для мультиязычных страниц.

**Параметры:**

| Имя | Тип | Required | Default | Описание |
|-----|-----|----------|---------|----------|
| parents | string | No | 0 | Родительские ID через запятую |
| depth | int | No | 10 | Глубина обхода |
| excludeIds | string | No | — | ID для исключения |
| scheme | int | No | -1 | Схема URL |
| onlyWithLocalization | bool | No | false | Только страницы с локализацией |

**Пример:**

```fenom
{'!getLocalizedSitemap' | snippet : ['parents' => '0', 'depth' => 5]}
```

---

## Fenom-модификаторы

### locfield

Вывод локализованного поля ресурса.

```fenom
{$pagetitle | locfield}
{$id | locfield : 'pagetitle'}
{$resource_id | locfield : 'introtext'}
{$tv_name | locfield}
```

### locoptioncaption

Локализованный caption опции msOption (miniShop3).

```fenom
{$option_id | locoptioncaption}
{$option_id | locoptioncaption : 'По умолчанию'}
```

### locproductoptionvalue

Локализованное value msProductOption (miniShop3).

```fenom
{$product_option_id | locproductoptionvalue}
{$product_option_id | locproductoptionvalue : '—'}
```

---

## События

### События Localizator3

| Событие | Параметры | Описание |
|---------|-----------|----------|
| `OnBuildLocalizationTabs` | tabs, resource, user, customization | Построение вкладок формы локализации |
| `OnBeforeFindLocalization` | — | Перед поиском языка из URL/host |
| `OnFindLocalization` | — | После определения языка |
| `OnFindLocalizatorResource` | — | Поиск ресурса |
| `OnToggleLocalizatorLanguage` | key, language | При установке текущего языка |
| `OnBeforeSaveLocalization` | — | Перед сохранением локализации |
| `OnSaveLocalization` | — | После сохранения локализации |
| `OnBeforeSaveLocalizatorLanguage` | — | Перед сохранением языка |
| `OnSaveLocalizatorLanguage` | — | После сохранения языка |
| `OnHasLocalizatorError404` | resource, language_key | Перед отдачей 404 при отсутствии локализации |

### Системные события MODX (обработчики)

| Событие | Назначение |
|---------|------------|
| `OnMODXInit` | Загрузка xPDO map |
| `OnHandleRequest` | Определение языка |
| `OnPageNotFound` | Роутинг URL |
| `OnDocFormPrerender` | Вкладка Localizator |
| `OnDocFormSave` | Синхронизация контента |
| `OnLoadWebDocument` | Подстановка переводов |
| `OnEmptyTrash` | Очистка данных |
| `OnTVFormPrerender` | Чекбокс доступности TV |
| `pdoToolsOnFenomInit` | Fenom-модификаторы |
| `mseOnBeforeIndex` | Индексация для mSearch |
| `mseOnGetWorkFields` | Расширение полей mSearch |

---

## Процессоры

### Языки (`mgr/language/`)

| Action | HTTP | Описание |
|--------|------|----------|
| `mgr/language/create` | POST | Создать язык |
| `mgr/language/update` | POST | Обновить язык |
| `mgr/language/get` | GET | Получить язык по id |
| `mgr/language/getlist` | GET | Список языков |
| `mgr/language/remove` | POST | Удалить язык |
| `mgr/language/enable` | POST | Включить язык |
| `mgr/language/disable` | POST | Отключить язык |

### Контент (`mgr/content/`)

| Action | HTTP | Описание |
|--------|------|----------|
| `mgr/content/create` | POST | Создать локализацию (resource_id, key, поля) |
| `mgr/content/update` | POST | Обновить локализацию |
| `mgr/content/get` | GET | Получить запись по id |
| `mgr/content/getlist` | GET | Список локализаций ресурса |
| `mgr/content/remove` | POST | Удалить локализацию |
| `mgr/content/enable` | POST | Включить |
| `mgr/content/disable` | POST | Отключить |
| `mgr/content/getformconfig` | GET | Конфигурация inline-формы Vue UI (`formtabs`, `activeLanguages`, `record`) |
| `mgr/content/translate` | POST | Автоперевод полей |
| `mgr/content/multiple` | POST | Групповые операции |

### Вспомогательные

| Action | Описание |
|--------|----------|
| `mgr/fields` | Конфигурация полей (legacy ExtJS) |
| `mgr/lexicon/getlanguages` | Языки для переводчика лексиконов |
| `mgr/lexicon/translate` | Перевод лексиконов |

---

## CLI

Массовый перевод ресурсов из командной строки:

```bash
cd core/components/localizator3/scripts
php translate_resources.php --parents=1 --depth=5
php translate_resources.php --ids=1,2,3 --dry-run
```

| Параметр | Описание |
|----------|----------|
| `--ids=1,2,3` | ID ресурсов через запятую |
| `--parents=1` | ID родителя (перевести все дочерние) |
| `--depth=5` | Глубина при `--parents` (по умолчанию 10) |
| `--dry-run` | Показать план без выполнения |

Переменные окружения:

| Переменная | Описание |
|------------|----------|
| `MODX_BASE_PATH` | Путь к корню MODX |
| `LOCALIZATOR_CONTEXT` | Контекст ресурсов (по умолчанию `web`) |

---

## См. также

- [Оглавление](./README.md)
- [architecture.md](./architecture.md) — модели и плагины
- [customization.md](./customization.md) — события формы
- [installation.md](./installation.md) — CLI и сборка
