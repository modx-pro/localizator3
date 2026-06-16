# Configuration — Localizator3

Системные настройки компонента. Namespace: `localizator3`.

---

## Основные (area: localizator3_main)

| Ключ | Тип | Default | Описание |
|------|-----|---------|----------|
| `localizator3_default_language` | text | — | Ключ языка по умолчанию (например, `ru`). Если не задан, используется первый активный язык |
| `localizator3_check_permissions` | bool | false | Проверять права доступа (политика LocalizatorManagerPolicy) |
| `localizator3_disabled_templates` | text | — | ID шаблонов без вкладки Localizator (через запятую, например `3,7,12`) |
| `localizator3_404_if_no_localization` | bool | false | Показывать 404, если нет локализации для текущего языка. Событие: `OnHasLocalizatorError404` |
| `localizator3_auto_detect_language` | bool | false | Автоопределение языка по Accept-Language / cookie `localizator3_key` |
| `localizator3_debug_log` | bool | false | Отладочное логирование в `core/cache/logs/error.log` с префиксом `[localizator3]` |
| `localizator3_tv_fields` | text | — | Список TV через запятую для вкладки локализации. Пусто — все TV, доступные для локализации |

---

## Переводчик (area: localizator3_translator)

| Ключ | Тип | Default | Описание |
|------|-----|---------|----------|
| `localizator3_default_translator` | text | Yandex | Переводчик: `Yandex`, `Google`, `DeepL`, `LibreTranslate`, `MyMemory`, `SimpleCopy` |
| `localizator3_key_yandex` | text | — | API-ключ [Yandex Translate](https://translate.yandex.ru/developers/keys) |
| `localizator3_key_google` | text | — | API-ключ [Google Cloud Translation](https://cloud.google.com/translate/) |
| `localizator3_key_deepl` | text | — | API-ключ [DeepL](https://www.deepl.com/pro/) |
| `localizator3_libretranslate_url` | text | `http://localhost:5000` | URL LibreTranslate (self-hosted или [libretranslate.com](https://libretranslate.com)) |
| `localizator3_key_libretranslate` | text | — | API-ключ LibreTranslate (опционально для публичного API) |
| `localizator3_mymemory_email` | text | — | Email для MyMemory (увеличивает лимит до 50k символов/день) |
| `localizator3_translate_fields` | text | `pagetitle,longtitle,menutitle,seotitle,keywords,introtext,description,content` | Поля для автоперевода |
| `localizator3_translate_translated` | bool | false | Переводить пустые поля в уже существующих локализациях |
| `localizator3_translate_translated_fields` | bool | false | Перезаписывать все поля из `translate_fields` при повторном переводе |

---

## Переводчики — сравнение

| Переводчик | Тип | Бесплатный лимит | API-ключ |
|------------|-----|-------------------|----------|
| **Yandex** | Cloud API | Ограниченный | Обязателен |
| **Google** | Cloud Translation | $20/мес free tier | Обязателен |
| **DeepL** | REST API | 500k символов/мес (Free) | Обязателен |
| **LibreTranslate** | Self-hosted / SaaS | Без ограничений (self-hosted) | Опционально |
| **MyMemory** | REST API | 5k символов/день (50k с email) | Не нужен |
| **SimpleCopy** | Копирование | — | Не нужен |

---

## pdoTools

Для корректной работы `getLocalizedResources` и `getLanguageList`:

| Namespace | Ключ | Значение |
|-----------|------|----------|
| `pdotools` | `pdoFetch.class` | `pdotools.pdofetchlocalizator3` |

Устанавливается автоматически резолвером при установке пакета.
