# Architecture — Localizator3

Архитектура компонента мультиязычности для MODX 3.

---

## Ключевые концепции

- **Язык** — запись в `localizator3_languages` (ключ, имя, HTTP host, cultureKey)
- **Локализация** — запись в `localizator3_content` (resource_id, key, поля)
- **Текущий язык** — `$modx->localizator3_key`, опция `localizator3_key`

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
│  getLocalizedResources, getLocales, makeLocalizedUrl   │
│  getLocalizedField, getLocalizedCanonical,             │
│  getLocalizedSitemap                                   │
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

1. **OnHandleRequest** — вызов `findLocalization(HTTP_HOST, request)`
2. Поиск по HTTP host + первый сегмент URL
3. При `auto_detect_language` — cookie `localizator3_key`, Accept-Language
4. Установка `localizator3_key`, cultureKey, site_url, base_url
5. **OnToggleLocalizatorLanguage** — интеграции, кэш

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
| `OnMODXInit` | OnMODXInit.php | Загрузка xPDO map, обработка AJAX referer |
| `OnHandleRequest` | OnHandleRequest.php | Определение языка из URL/host |
| `OnPageNotFound` | OnPageNotFound.php | Роутинг URL локализации |
| `OnDocFormPrerender` | OnDocFormPrerender.php | Вкладка Localizator в форме ресурса |
| `OnDocFormSave` | OnDocFormSave.php | Синхронизация контента по умолчанию |
| `OnLoadWebDocument` | OnLoadWebDocument.php | Подстановка локализованных полей |
| `OnEmptyTrash` | OnEmptyTrash.php | Удаление данных при очистке корзины |
| `OnTVFormPrerender` | OnTVFormPrerender.php | Чекбокс доступности TV для локализации |
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
| `mgr/content/get` | Получение локализации |
| `mgr/content/getlist` | Список локализаций ресурса |
| `mgr/content/remove` | Удаление локализации |
| `mgr/content/enable` | Включение локализации |
| `mgr/content/disable` | Отключение локализации |
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
| `OnBeforeSaveLocalizatorLanguage` | — | Перед сохранением языка |
| `OnSaveLocalizatorLanguage` | — | После сохранения языка |
| `OnHasLocalizatorError404` | resource, language_key | Перед 404 при отсутствии локализации |

---

## Vue UI

Интерфейс менеджера построен на Vue 3 + PrimeVue 4 (тема Aura) и встроен в ExtJS-менеджер MODX 3.

| Компонент | Entry point | Контейнер |
|-----------|-------------|-----------|
| `ContentGrid.vue` | `content.js` | `#localizator3-content-app` |
| `LanguagesGrid.vue` | `languages.js` | `#localizator3-languages-app` |

Изоляция стилей:
- `main.css` — откат глобального `box-sizing` от PrimeVue через `!important`
- Scoped styles в компонентах с BEM-префиксами `.content-grid__*`, `.languages-grid__*`
- `appendTo="self"` на Dialog для изоляции порталов

---

## Зависимости

- **pdoTools** — обязательно (getLocalizedResources, getLocales)
- **miniShop3** — опционально (локализация товаров и опций)
- **mSearch** — опционально (индексация локализованных полей)
