# Архитектура — Localizator3

Архитектура компонента мультиязычности для MODX 3.

**Версия:** 1.1.0-beta · [Оглавление документации](./README.md)

---

## Ключевые концепции

- **Язык** — запись в `localizator3_languages` (ключ, имя, HTTP host, cultureKey)
- **Локализация** — запись в `localizator3_content` (resource_id, key, поля)
- **Текущий язык** — `$modx->localizator3_key`, опция `localizator3_key`
- **Native default** — при `localizator3_default_from_resource = true` базовый язык читается из полей `modResource` и TV, запись в `localizator3_content` для него не требуется

---

## Режим «базовый язык = поля ресурса»

При включённой настройке `localizator3_default_from_resource`:

1. Контент для ключа `localizator3_default_language` на фронте берётся из основной формы ресурса
2. `OnLoadWebDocument` не подменяет поля ресурса для базового языка
3. `locfield` / `getLocalizedField` / автоперевод используют поля ресурса как источник
4. Во вкладке «Локализация» в manager отображаются только переводы на другие языки

Существующие записи базового языка в `localizator3_content` игнорируются на фронте (можно удалить вручную).

---

## Слои

```
┌────────────────────────────────────────────────────────┐
│  Manager UI (Vue 3 + PrimeVue 4)                       │
│  ContentGrid.vue, LanguagesGrid.vue                    │
│  Entry: content.js, languages.js                       │
└────────────────────────────────────────────────────────┘
                            │ fetch → connector.php
┌────────────────────────────────────────────────────────┐
│  Processors (API)                                      │
│  mgr/language/*, mgr/content/*, mgr/content/getform*  │
└────────────────────────────────────────────────────────┘
                            │
┌────────────────────────────────────────────────────────┐
│  Frontend (Fenom)                                      │
│  locfield, locoptioncaption, locproductoptionvalue     │
└────────────────────────────────────────────────────────┘
                            │
┌────────────────────────────────────────────────────────┐
│  Snippets                                              │
│  getLocalizedResources, getLanguageList, makeLocalizedUrl│
│  getLocalizedField, getLocalizedCanonical,               │
│  getLocalizedSitemap                                     │
└────────────────────────────────────────────────────────┘
                            │
┌────────────────────────────────────────────────────────┐
│  Service (localizator.class.php)                       │
│  findLocalization, translate, versionedAsset           │
│  getLocalizedOptionCaption, getLocalizedProductOption*  │
└────────────────────────────────────────────────────────┘
                            │
┌────────────────────────────────────────────────────────┐
│  Model (xPDO 3, PSR-4, namespace localizator3)        │
│  localizatorLanguage, localizatorContent,              │
│  locTemplateVarResource, locOption, locProductOption   │
└────────────────────────────────────────────────────────┘
```

---

## Поток определения языка

1. **OnHandleRequest** — вызов `findLocalization(HTTP_HOST, request)` (полноценные страницы)
2. Поиск по HTTP host + первый сегмент URL
3. При `auto_detect_language` — cookie `localizator3_key`, Accept-Language (редирект на URL языка)
4. Установка `localizator3_key`, **cultureKey**, site_url, base_url
5. **OnToggleLocalizatorLanguage** — интеграции, кэш

### Connector / AJAX

`OnMODXInit` вызывает `resolveConnectorLanguage()` для `connector.php` и XHR. Порядок:

1. GET/POST `localizator3_request_language_param` (`getRequestLanguageKey()` → `applyLanguageFromKey`)
2. Referer path (если не mgr) → `findLocalization($host, $path)`
3. HTTP host → `findLocalization($host, '')`
4. cookie `localizator3_key` → `applyLanguageFromCookie()`

Так `cultureKey` совпадает с языком витрины даже без `X-Requested-With` и без параметра `q`.

---

## Модели (xPDO)

| Класс | Таблица | Назначение |
|-------|---------|------------|
| `localizatorLanguage` | `localizator3_languages` | Языки (key, name, http_host, cultureKey, active, rank) |
| `localizatorContent` | `localizator3_content` | Локализация полей ресурсов (resource_id, key, pagetitle, content, ...) |
| `locTemplateVarResource` | `localizator3_tmplvar_contentvalues` | Локализация TV (key, tmplvarid, contentid, value) |
| `locOption` | `localizator3_option` | Локализация caption/description msOption |
| `locProductOption` | `localizator3_product_option` | Локализация value msProductOption |

---

## Плагины (handlers)

| Событие | Handler | Назначение |
|---------|---------|------------|
| `OnMODXInit` | OnMODXInit.php | xPDO map; connector/AJAX → `resolveConnectorLanguage()` (param → Referer → host → cookie) |
| `OnHandleRequest` | OnHandleRequest.php | Определение языка из URL/host |
| `OnPageNotFound` | OnPageNotFound.php | Роутинг URL локализации |
| `OnDocFormPrerender` | OnDocFormPrerender.php | Вкладка Localizator в форме ресурса |
| `OnDocFormSave` | OnDocFormSave.php | Синхронизация контента по умолчанию |
| `OnLoadWebDocument` | OnLoadWebDocument.php | Подстановка локализованных полей |
| `OnEmptyTrash` | OnEmptyTrash.php | Удаление данных при очистке корзины |
| `OnTVFormPrerender` | OnTVFormPrerender.php | Чекбокс доступности TV для локализации |
| `OnTVFormSave` | OnTVFormSave.php | Сохранение `localizator3_enabled` (в т.ч. `0` при снятии галочки) |
| `pdoToolsOnFenomInit` | pdoToolsOnFenomInit.php | Fenom-модификаторы |
| `mseOnBeforeIndex` | mseOnBeforeIndex.php | Индексация для mSearch |
| `mseOnGetWorkFields` | mseOnGetWorkFields.php | Расширение полей mSearch |

---

## Процессоры

| Action | Назначение |
|--------|------------|
| `mgr/language/create` | Создание языка |
| `mgr/language/update` | Обновление языка |
| `mgr/language/get` | Получение языка |
| `mgr/language/getlist` | Список языков |
| `mgr/language/remove` | Удаление языка |
| `mgr/language/enable` | Включение языка |
| `mgr/language/disable` | Отключение языка |
| `mgr/content/create` | Создание локализации |
| `mgr/content/update` | Обновление локализации |
| `mgr/content/updatefromgrid` | Обновление из DataTable (JSON `data`) |
| `mgr/content/get` | Получение локализации |
| `mgr/content/getlist` | Список локализаций ресурса |
| `mgr/content/remove` | Удаление локализации |
| `mgr/content/enable` | Включение локализации |
| `mgr/content/disable` | Отключение локализации |
| `mgr/content/multiple` | Групповые операции |
| `mgr/content/getformconfig` | Конфигурация формы для Vue UI |
| `mgr/content/translate` | Автоперевод |
| `mgr/fields` | Поля формы (legacy) |
| `mgr/lexicon/translate` | Перевод лексиконов |
| `mgr/lexicon/getlanguages` | Список языков для лексикона |

---

## События компонента

| Событие | Параметры | Описание |
|---------|-----------|----------|
| `OnBuildLocalizationTabs` | tabs, resource, user, customization | Построение вкладок формы |
| `OnBeforeFindLocalization` | — | Перед поиском языка |
| `OnFindLocalization` | — | После определения языка |
| `OnFindLocalizatorResource` | — | Поиск ресурса |
| `OnToggleLocalizatorLanguage` | key, language | При установке языка |
| `OnBeforeSaveLocalization` | — | Перед сохранением локализации |
| `OnSaveLocalization` | — | После сохранения |
| `OnBeforeRemoveLocalization` | — | Перед удалением локализации |
| `OnRemoveLocalization` | — | После удаления локализации |
| `OnBeforeSaveLocalizatorLanguage` | — | Перед сохранением языка |
| `OnSaveLocalizatorLanguage` | — | После сохранения языка |
| `OnHasLocalizatorError404` | resource, language_key | Перед 404 при отсутствии локализации |

---

## Vue UI

Интерфейс менеджера: **Vue 3 + PrimeVue 4**, встроен в ExtJS MODX 3. Стек Vue/Pinia/PrimeVue загружается через **VueTools ≥1.1.2-pl** (Import Map); entry-бандлы Localizator3 — lean (~14 KB).

Тема всегда **ModxManagerTheme**: `getActiveTheme('modx')` в [`vueManager/src/app/primevueTheme.js`](../vueManager/src/app/primevueTheme.js). Системная настройка `vuetools.theme` (Aura) на Localizator3 не влияет. Опции: `darkModeSelector: '.modx-dark-mode'`, `cssLayer: false`. Кнопки Save/Add — `severity="success"` (`getPrimarySaveSeverity()`).

| Экран | Компонент | Entry | Контейнер | Сборка |
|-------|-----------|-------|-----------|--------|
| Ресурс → вкладка «Локализация» | `ContentGrid.vue` | `content.js` | `#localizator3-content-app` | `vue-dist/content.min.js` |
| Localizator3 → Языки | `LanguagesGrid.vue` | `languages.js` | `#localizator3-languages-app` | `vue-dist/languages.min.js` |

### Поведение вкладки «Локализация»

- Dropdown **всех активных языков** (`activeLanguages` из `mgr/content/getformconfig`).
- Inline-форма с табами **Документ** / **TV** (не grid-список переводов).
- TV рендерятся по типу: number/listbox/checkbox/radio и др. — контролами PrimeVue; `richtext` — MODX RTE; MIGX/TVMedia3x и прочие кастомные типы — штатным рендером MODX (`renderMode`).
- Действия: Save, Translate, enable/disable, delete.
- ExtJS: вкладка с `layout: anchor`, mount через `OnDocFormPrerender` + retry в `content.js`.

### Структура `vueManager/src/`

```
vueManager/src/
├── base.css                         # Базовые стили entry
├── app/
│   ├── createLocalizatorApp.js      # Bootstrap (без Pinia)
│   └── primevueTheme.js             # ModxManagerTheme (getActiveTheme('modx'))
├── styles/
│   └── mgr-ui.css                   # Общие паттерны UI под .vueApp
├── composables/                      # Подключены в ContentGrid / LanguagesGrid
│   ├── index.js
│   ├── useConnector.js
│   ├── useLexicon.js
│   ├── useDataTable.js
│   ├── useGridCrud.js                # actionPrefix: mgr/content | mgr/language
│   ├── useConfirmAction.js
│   └── useContentForm.js             # Форма вкладки «Локализация»
├── components/
│   ├── ContentGrid.vue               # Inline-форма Document/TV
│   ├── LanguagesGrid.vue             # DataTable + Dialog
│   └── shared/
│       ├── index.js
│       ├── FormFieldRenderer.vue
│       ├── GridActionsColumn.vue
│       ├── TvFieldControl.vue        # PrimeVue-контролы TV по типу
│       └── TvRenderedInput.vue       # MODX-рендер / richtext (MODx.loadRTE)
└── entries/
    ├── content.js
    └── languages.js
```

### Изоляция стилей

- `assets/.../css/mgr/main.css` — `box-sizing` для mount-контейнеров в ExtJS
- `vueManager/src/styles/mgr-ui.css` — общие паттерны UI под `.vueApp`
- PostCSS prefix `.vueApp` в Vite; mount id `#localizator3-*` без prefix
- BEM: `.content-grid__*`, `.languages-grid__*`
- `appendTo="self"` на Dialog / ConfirmDialog

---

## Зависимости

| Пакет | Обязательность | Назначение |
|-------|----------------|------------|
| **VueTools** ≥1.1.2-pl | Да | Vue 3, Pinia, PrimeVue через Import Map |
| **pdoTools** | Да | `getLocalizedResources`, `getLanguageList` |
| **miniShop3** | Нет | Локализация товаров и опций → [integration-minishop3.md](./integration-minishop3.md) |
| **mSearch** | Нет | Индексация локализованных полей → [integration-msearch.md](./integration-msearch.md) |

---

## См. также

- [Оглавление](./README.md)
- [installation.md](./installation.md) — сборка и установка
- [api.md](./api.md) — процессоры и события
- [customization.md](./customization.md) — кастомизация формы
- [roadmap.md](./roadmap.md) — план развития Vue UI
