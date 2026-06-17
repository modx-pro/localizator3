# Localizator3

Мультиязычность для MODX Revolution 3. Локализация ресурсов, TV, товаров miniShop3 и опций.

**Только для MODX 3** — для MODX 2.x используйте [Localizator](https://github.com/modx-pro/localizator).

**Текущая версия:** 1.0.8-beta

---

## Требования

| Компонент | Версия | Обязательность |
|-----------|--------|----------------|
| MODX Revolution | 3.0+ | Да |
| PHP | 8.2+ (компонент: 8.1+) | Да |
| **VueTools** | **≥1.1.2-pl** | **Да** (предоставляет Vue-стек через Import Map) |
| pdoTools | актуальная | Да (ставится с пакетом) |
| Node.js | 18+ | Только для сборки Vue UI |

VueTools устанавливается автоматически при наличии ModStore провайдера или вручную с [modstore.pro](https://modstore.pro/packages/utilities/vuetools).

---

## Quick Start

1. Установите пакет через [ModStore](https://modstore.pro/packages/utilities/localizator3) или соберите из `_build/` (см. [Установка](docs/installation.md)).
2. **Localizator3 → Языки** — создайте языки (`ru`, `en` и т.д.).
3. Укажите **HTTP host** для каждого языка (мультидоменность или поддомены).
4. Включите «TV is available in localizations» для нужных TV в форме TV.
5. В pdoTools: **pdoFetch.class** = `pdotools.pdofetchlocalizator3` (создаётся при установке).

```fenom
{'!getLocalizedResources' | snippet : [
  'snippet' => 'pdoResources',
  'parents' => 0,
  'tpl' => '@INLINE <li><a href="{$uri}">{$pagetitle}</a></li>'
]}
```

Переключатель языков — сниппет **`getLanguageList`**:

```fenom
{'!getLanguageList' | snippet}
{'!getLanguageList' | snippet : ['outputMode' => 'dropdown', 'activeClass' => 'current']}
```

---

## Возможности

- Локализация полей ресурсов (pagetitle, content, TV)
- Vue 3 + PrimeVue 4 интерфейс в менеджере MODX (вкладка «Локализация», страница «Языки»)
- Интеграция с pdoTools, pdoResources, pdoMenu
- Fenom-модификаторы: `locfield`, `locoptioncaption`, `locproductoptionvalue`
- Автоперевод: Yandex, Google, DeepL, LibreTranslate, MyMemory, SimpleCopy
- Автоопределение языка по Accept-Language / cookie `localizator3_key`
- SEO: canonical, hreflang, sitemap.xml
- miniShop3: локализация товаров и опций (цвет, размер)
- mSearch: индексация локализованных полей
- Кастомизация форм через событие `OnBuildLocalizationTabs`
- CLI: массовый перевод ресурсов (`translate_resources.php`)

---

## Интерфейс менеджера

| Раздел | Компонент | Назначение |
|--------|-----------|------------|
| Localizator3 → Языки | `LanguagesGrid.vue` | CRUD языков, активация, ранг |
| Ресурс → вкладка Локализация | `ContentGrid.vue` | Dropdown языка, inline-форма полей и TV, автоперевод |

UI: PrimeVue 4 (Aura), изолированные стили (`.vueApp`, BEM-префиксы), `ConfirmDialog` вместо native `confirm()`.

---

## Настройки

| Настройка | Описание | Default |
|-----------|----------|---------|
| `localizator3_default_language` | Ключ языка по умолчанию | — |
| `localizator3_check_permissions` | Проверять права доступа | false |
| `localizator3_disabled_templates` | ID шаблонов без вкладки (через запятую) | — |
| `localizator3_404_if_no_localization` | 404 при отсутствии локализации | false |
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
| `localizator3_tv_fields` | Ограничить список TV во вкладке (пусто = все) | — |

Полный список: [docs/configuration.md](docs/configuration.md)

---

## Сборка для разработчиков

Из корня репозитория:

```bash
cd vueManager && npm install && npm run build
cd .. && php _build/build.php
```

Transport-пакет: `core/packages/localizator3-1.0.8-beta.transport.zip`

Подробнее: [docs/installation.md](docs/installation.md)

---

## Документация

Полное оглавление: **[docs/README.md](docs/README.md)**

| Документ | Описание |
|----------|----------|
| [Установка и сборка](docs/installation.md) | Сборка из исходников, Vue, CLI |
| [API](docs/api.md) | Сниппеты, параметры, события, процессоры |
| [Конфигурация](docs/configuration.md) | Системные настройки |
| [Архитектура](docs/architecture.md) | Модели, плагины, интеграции |
| [Кастомизация](docs/customization.md) | Кастомизация форм через события |
| [miniShop3](docs/integration-minishop3.md) | Локализация товаров и опций |
| [mSearch](docs/integration-msearch.md) | Индексация локализованных полей |
| [Roadmap](docs/roadmap.md) | План развития, каталог фич F1–F10 |
| [llms.txt](docs/llms.txt) | Краткий контекст для LLM |

---

## Contributing

Репозиторий: [github.com/modx-pro/localizator3](https://github.com/modx-pro/localizator3)

1. Fork репозитория
2. Создайте ветку для фичи
3. После изменений в `vueManager/` или `core/`: `npm run build` в `vueManager/`, затем `php _build/build.php`
4. Запустите `composer phpcs` и `composer test`
5. Отправьте Pull Request

---

## License

GPL-2.0-or-later
