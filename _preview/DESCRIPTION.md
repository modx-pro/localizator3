# Localizator3 — мультиязычность для MODX Revolution 3

Localizator3 локализует поля ресурсов, TV, товары miniShop3 и опции на MODX 3. В менеджере работает Vue 3 + PrimeVue 4; на витрине — сниппеты, Fenom, canonical, hreflang, sitemap, автоперевод и CLI.

MODX 2.x: [Localizator](https://github.com/modx-pro/localizator).

Версия: **1.0.8-beta**.

---

## Модель локализации

Overlay: один ресурс MODX, несколько строк в `localizator3_content` по ключу языка. ID ресурса один для Fenom, pdoTools, miniShop3 и ссылок.

| Подход | Localizator3 | Babel | Lingua |
|--------|:------------:|:-----:|:------:|
| Один ID ресурса | ✅ | ❌ (ресурс на язык) | ✅ |
| Разная структура сайта по языкам | ❌ | ✅ | ❌ |
| Локализация TV | ✅ | ✅ | ✅ |
| pdoTools / Fenom | ✅ | ⚙️ | ✅ |

---

## Сценарии

- Мультидоменные и мультиязычные сайты
- Каталоги и лендинги с переводами полей без второго дерева ресурсов
- miniShop3: переводы товаров и атрибутов
- mSearch: поиск по локализованным полям
- Массовая локализация через CLI и автоперевод из менеджера

---

## Возможности

### Менеджер MODX

- **Localizator3 → Языки**: key, name, HTTP host, cultureKey, rank, active
- **Ресурс → Локализация**: dropdown языков, inline-форма Document / TV, Save, Translate, enable/disable, delete
- Vue 3 + PrimeVue 4 через **VueTools ≥1.1.2-pl** (Import Map, entry ~14 KB)
- Форма: событие `OnBuildLocalizationTabs` (visible, rank, свои табы)
- Права: `LocalizatorManagerPolicy`, `localizator3_check_permissions`

### Витрина

**Сниппеты:**

| Сниппет | Назначение |
|---------|------------|
| `getLocalizedResources` | pdoResources, pdoMenu, msProducts / ms3Products + JOIN локализации |
| `getLanguageList` | Переключатель (list / dropdown) |
| `makeLocalizedUrl` | URL с учётом языка и http_host |
| `getLocalizedField` | Поле или TV на указанном языке |
| `getLocalizedCanonical` | Canonical + hreflang |
| `getLocalizedSitemap` | sitemap.xml с alternate |

**Fenom:** `locfield`, `locoptioncaption`, `locproductoptionvalue`.

**pdoTools:** `pdoFetchLocalizator`, настройка `pdoFetch.class` = `pdotools.pdofetchlocalizator3`.

### Маршрутизация и SEO

- Язык: HTTP host, cookie `localizator3_key`, Accept-Language (`localizator3_auto_detect_language`)
- Переводы на странице: `OnLoadWebDocument`
- 404 без локализации: `localizator3_404_if_no_localization`, событие `OnHasLocalizatorError404`
- hreflang, canonical, multilingual sitemap

### Автоперевод

Yandex, Google, DeepL, LibreTranslate, MyMemory, SimpleCopy. Перевод из менеджера; режимы «только пустые» и «перезаписать». CLI: `core/components/localizator3/scripts/translate_resources.php`.

### miniShop3

Вкладка на `msProduct`, модели `locOption` / `locProductOption`, Fenom для опций, `getLocalizedResources` с `msProducts` / `ms3Products`.

### mSearch

Индексация `{key}-{field}` (`mseOnBeforeIndex`), динамический `fields` в mSearchForm.

---

## Быстрый старт

1. Установите **VueTools ≥1.1.2-pl** и **Localizator3** (ModStore или `_build/`).
2. **Localizator3 → Языки**: создайте языки, укажите **HTTP host**.
3. В TV включите «TV is available in localizations».
4. `pdoFetch.class` = `pdotools.pdofetchlocalizator3`.
5. На ресурсе добавьте перевод на вкладке «Локализация».
6. На шаблон:

```fenom
{'!getLanguageList' | snippet : ['outputMode' => 'dropdown', 'activeClass' => 'current']}
{'!getLocalizedCanonical' | snippet}

{'!getLocalizedResources' | snippet : [
  'snippet' => 'pdoResources',
  'parents' => 0,
  'tpl' => '@INLINE <li><a href="{$uri}">{$pagetitle}</a></li>',
  'onlyWithLocalization' => 1
]}
```

---

## Требования

| Компонент | Версия | Обязательность |
|-----------|--------|----------------|
| MODX Revolution | 3.0+ | Да |
| PHP | 8.2+ | Да |
| VueTools | ≥1.1.2-pl | Да |
| pdoTools | актуальная | Да |
| Node.js | 18+ | Сборка Vue UI |
| miniShop3 | актуальная | Нет |
| mSearch | актуальная | Нет |

---

## События

| Событие | Назначение |
|---------|------------|
| `OnBuildLocalizationTabs` | Вкладки и поля формы |
| `OnBeforeSaveLocalization` / `OnSaveLocalization` | Сохранение |
| `OnToggleLocalizatorLanguage` | Смена языка |
| `OnBeforeFindLocalization` / `OnFindLocalization` | Язык из URL |
| `OnHasLocalizatorError404` | Перед 404 |

Полный список: [docs/api.md](../docs/api.md)

---

## Документация

| Раздел | Файл |
|--------|------|
| Оглавление | [docs/README.md](../docs/README.md) |
| Установка | [docs/installation.md](../docs/installation.md) |
| API | [docs/api.md](../docs/api.md) |
| Настройки | [docs/configuration.md](../docs/configuration.md) |
| Архитектура | [docs/architecture.md](../docs/architecture.md) |
| Кастомизация | [docs/customization.md](../docs/customization.md) |
| miniShop3 | [docs/integration-minishop3.md](../docs/integration-minishop3.md) |
| mSearch | [docs/integration-msearch.md](../docs/integration-msearch.md) |
| Roadmap | [docs/roadmap.md](../docs/roadmap.md) |

---

## Ограничения

- Overlay: одно дерево ресурсов, переводы полей. Разная структура по языкам: **Babel**.
- Локализованный alias на язык (F4-04) в roadmap; в 1.0.8 URL = общий alias + http_host.
- Vue UI требует VueTools ≥1.1.2; ExtJS fallback убран в 1.0.8.
- `hasLocalization` и список переводов ресурса (F11) запланированы на 1.1.

---

## Ссылки

- [github.com/modx-pro/localizator3](https://github.com/modx-pro/localizator3)
- [VueTools](https://modstore.pro/packages/utilities/vuetools)
- [changelog.txt](../core/components/localizator3/docs/changelog.txt)
