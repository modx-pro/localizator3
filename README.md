# Localizator3

Мультиязычность для MODX Revolution 3: поля ресурсов, TV, товары и опции miniShop3.

Работает только с MODX 3. Для MODX 2.x используйте [Localizator](https://github.com/modx-pro/localizator).

**Версия:** 1.0.10-beta

---

## Требования

| Компонент | Версия | Обязательность |
|-----------|--------|----------------|
| MODX Revolution | 3.0+ | Да |
| PHP | 8.2+ | Да |
| **VueTools** | **≥1.1.2-pl** | **Да** (Vue-стек через Import Map) |
| pdoTools | актуальная | Да (ставится с пакетом) |
| Node.js | 18+ | Только для сборки Vue UI |

VueTools подтягивается при установке через ModStore или вручную с [modstore.pro](https://modstore.pro/packages/utilities/vuetools).

---

## Быстрый старт

1. Установите пакет через [ModStore](https://modstore.pro/packages/utilities/localizator3) или соберите из `_build/` ([инструкция](docs/installation.md)).
2. Откройте **Localizator3 → Языки** и создайте языки (`ru`, `en` и т.д.).
3. Задайте **HTTP host** для каждого языка (мультидомен или поддомены).
4. В форме TV включите «TV is available in localizations» для нужных полей.
5. В pdoTools укажите **pdoFetch.class** = `pdotools.pdofetchlocalizator3` (класс создаётся при установке).

```fenom
{'!getLocalizedResources' | snippet : [
  'snippet' => 'pdoResources',
  'parents' => 0,
  'tpl' => '@INLINE <li><a href="{$uri}">{$pagetitle}</a></li>'
]}
```

Переключатель языков, сниппет **`getLanguageList`**:

```fenom
{'!getLanguageList' | snippet}
{'!getLanguageList' | snippet : ['outputMode' => 'dropdown', 'activeClass' => 'current']}
```

---

## Возможности

- Локализация полей ресурсов (pagetitle, content, TV)
- Vue 3 + PrimeVue 4 в менеджере MODX (вкладка «Локализация», страница «Языки»)
- pdoTools, pdoResources, pdoMenu
- Fenom: `locfield`, `locoptioncaption`, `locproductoptionvalue`
- Автоперевод: Yandex, Google, DeepL, LibreTranslate, MyMemory, SimpleCopy
- Язык по Accept-Language и cookie `localizator3_key`
- SEO: canonical, hreflang, sitemap.xml
- miniShop3: товары и опции (цвет, размер)
- mSearch: индексация локализованных полей
- Кастомные поля формы через `OnBuildLocalizationTabs`
- CLI: массовый перевод (`translate_resources.php`)

---

## Интерфейс менеджера

| Раздел | Компонент | Назначение |
|--------|-----------|------------|
| Localizator3 → Языки | `LanguagesGrid.vue` | CRUD языков, активация, ранг |
| Ресурс → Локализация | `ContentGrid.vue` | Выбор языка, поля и TV, автоперевод |

PrimeVue 4 (Aura). Стили изолированы (`.vueApp`, BEM-префиксы). Подтверждения через `ConfirmDialog`, не native `confirm()`.

---

## Настройки

| Настройка | Описание | Default |
|-----------|----------|---------|
| `localizator3_default_language` | Ключ языка по умолчанию | — |
| `localizator3_default_from_resource` | Базовый язык из полей ресурса, без дубля в локализации | false |
| `localizator3_check_permissions` | Проверка прав доступа | false |
| `localizator3_disabled_templates` | ID шаблонов без вкладки (через запятую) | — |
| `localizator3_404_if_no_localization` | 404 без локализации | false |
| `localizator3_auto_detect_language` | Автоопределение языка посетителя | false |
| `localizator3_debug_log` | Отладочное логирование | false |
| `localizator3_default_translator` | Переводчик | Yandex |
| `localizator3_key_yandex` | API-ключ Yandex | — |
| `localizator3_key_google` | API-ключ Google | — |
| `localizator3_key_deepl` | API-ключ DeepL | — |
| `localizator3_libretranslate_url` | URL LibreTranslate | `http://localhost:5000` |
| `localizator3_key_libretranslate` | API-ключ LibreTranslate | — |
| `localizator3_mymemory_email` | Email для MyMemory | — |
| `localizator3_translate_fields` | Поля для автоперевода | pagetitle,longtitle,... |
| `localizator3_translate_translated` | Дополнять пустые поля в существующих локализациях | false |
| `localizator3_translate_translated_fields` | Перезаписывать все поля при повторном переводе | false |
| `localizator3_tv_fields` | Список TV во вкладке (пусто = все) | — |

Полный список: [docs/configuration.md](docs/configuration.md)

---

## Сборка

Из корня репозитория:

```bash
cd vueManager && npm install && npm run build
cd .. && php _build/build.php
```

Transport-пакет: `core/packages/localizator3-1.0.10-beta.transport.zip`

Подробнее: [docs/installation.md](docs/installation.md)

---

## Документация

Оглавление: **[docs/README.md](docs/README.md)**

| Документ | Описание |
|----------|----------|
| [Установка и сборка](docs/installation.md) | Сборка из исходников, Vue, CLI |
| [API](docs/api.md) | Сниппеты, параметры, события, процессоры |
| [Конфигурация](docs/configuration.md) | Системные настройки |
| [Архитектура](docs/architecture.md) | Модели, плагины, интеграции |
| [Кастомизация](docs/customization.md) | Формы через события |
| [miniShop3](docs/integration-minishop3.md) | Товары и опции |
| [mSearch](docs/integration-msearch.md) | Индексация локализованных полей |
| [Roadmap](docs/roadmap.md) | План развития, каталог фич F1–F10 |
| [llms.txt](docs/llms.txt) | Краткий контекст для LLM |

---

## Contributing

Репозиторий: [github.com/modx-pro/localizator3](https://github.com/modx-pro/localizator3)

1. Fork
2. Ветка под задачу
3. После правок в `vueManager/` или `core/`: `npm run build` в `vueManager/`, затем `php _build/build.php`
4. `composer phpcs` и `composer test`
5. Pull Request

---

## License

GPL-2.0-or-later
