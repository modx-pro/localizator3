# Localizator3

Мультиязычность для MODX Revolution 3. Локализация ресурсов, TV, товаров miniShop3 и опций.

**Только для MODX 3** — для MODX 2.x используйте [Localizator](https://github.com/modx-pro/localizator).

---

## Требования

- MODX Revolution 3.0+
- PHP 8.2+
- pdoTools (устанавливается автоматически)

---

## Quick Start

1. Установите пакет через Package Manager или соберите из `_build/`
2. **Localizator3 → Языки** — создайте языки (ru, en)
3. Укажите HTTP HOST для каждого языка
4. Включите «TV is available in localizations» для нужных TV
5. В pdoTools: **pdoFetch.class** = `pdotools.pdofetchlocalizator3`

```fenom
{'!getLocalizedResources' | snippet : [
  'snippet' => 'pdoResources',
  'parents' => 0,
  'tpl' => '@INLINE <li><a href="{$uri}">{$pagetitle}</a></li>'
]}
```

---

## Возможности

- Локализация полей ресурсов (pagetitle, content, TV)
- Vue 3 + PrimeVue 4 интерфейс в менеджере MODX
- Интеграция с pdoTools, pdoResources, pdoMenu
- Fenom-модификаторы: `locfield`, `locoptioncaption`, `locproductoptionvalue`
- Автоперевод: Yandex, Google, DeepL, LibreTranslate, MyMemory, SimpleCopy
- Автоопределение языка по Accept-Language / cookie
- SEO: canonical, hreflang, sitemap.xml
- miniShop3: локализация товаров и опций (цвет, размер)
- mSearch: индексация локализованных полей
- Кастомизация форм через событие OnBuildLocalizationTabs

---

## Настройки

| Настройка | Описание | Default |
|-----------|----------|---------|
| `localizator3_default_language` | Ключ языка по умолчанию | — |
| `localizator3_check_permissions` | Проверять права доступа | false |
| `localizator3_disabled_templates` | ID шаблонов без вкладки (через запятую) | — |
| `localizator3_404_if_no_localization` | 404 при отсутствии локализации | false |
| `localizator3_auto_detect_language` | Автоопределение языка посетителя | false |
| `localizator3_default_translator` | Переводчик | Yandex |
| `localizator3_key_yandex` | API-ключ Yandex | — |
| `localizator3_key_google` | API-ключ Google | — |
| `localizator3_key_deepl` | API-ключ DeepL | — |
| `localizator3_translate_fields` | Поля для перевода | pagetitle,longtitle,... |
| `localizator3_debug_log` | Отладочное логирование | false |

Полный список: [docs/configuration.md](docs/configuration.md)

---

## Документация

| Документ | Описание |
|----------|----------|
| [Установка и сборка](docs/INSTALL_LOCALIZATOR3.md) | Сборка из исходников, Vue, CLI |
| [API Reference](docs/api.md) | Сниппеты, параметры, события |
| [Конфигурация](docs/configuration.md) | Системные настройки |
| [Архитектура](docs/architecture.md) | Модели, плагины, интеграции |
| [Кастомизация](docs/CUSTOMIZATION.md) | Кастомизация форм через события |
| [miniShop3](docs/04_miniShop3_integration.md) | Локализация товаров и опций |
| [mSearch](docs/05_mSearch_integration.md) | Индексация локализованных полей |

---

## Contributing

1. Fork репозитория
2. Создайте ветку для фичи
3. Запустите `composer phpcs` и `composer test`
4. Отправьте Pull Request

---

## License

GPL-2.0-or-later
