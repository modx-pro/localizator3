# Сборка и установка Localizator3

**Localizator3** — компонент мультиязычности для **MODX 3 only**. Для MODX 2.x используйте оригинальный [Localizator](https://github.com/modx-pro/localizator).

**Версия:** 1.0.7-beta

---

## Требования

| Компонент | Версия | Обязательность |
|-----------|--------|----------------|
| MODX Revolution | 3.0+ | Да |
| PHP | 8.2+ (компонент: 8.1+) | Да |
| pdoTools | актуальная | Да (ставится с пакетом) |
| Node.js | 18+ | Только для сборки Vue UI |
| miniShop3 | — | Опционально |
| mSearch | — | Опционально |

---

## Установка готового пакета

1. Скачайте `.transport.zip` из [ModStore](https://modstore.pro/packages/utilities/localizator3) или соберите из исходников (ниже).
2. **Управление → Пакеты → Установить дополнение** — загрузите архив.
3. pdoTools установится автоматически (если отсутствует).
4. Проверьте настройку pdoTools: **pdoFetch.class** = `pdotools.pdofetchlocalizator3`.

---

## Сборка пакета из исходников

### 1. Подготовка окружения

Клонируйте репозиторий и разместите его так, чтобы MODX был доступен:

```
/путь/к/modx/
├── core/
├── manager/
├── connectors/
├── assets/
└── Extras/
    └── localizator3/   # репозиторий Localizator3
        ├── core/
        ├── assets/
        ├── vueManager/
        ├── _build/
        └── ...
```

Сборщик автоматически ищет `core/config/config.inc.php` в родительских каталогах. При необходимости задайте путь явно:

```bash
export MODX_BASE_PATH=/путь/к/modx/
```

### 2. Сборка Vue UI

```bash
cd vueManager
npm install
npm run build
```

Результат сборки (Vite):

| Файл | Назначение |
|------|------------|
| `assets/components/localizator3/js/mgr/vue-dist/content.min.js` | Вкладка «Локализация» в ресурсе |
| `assets/components/localizator3/js/mgr/vue-dist/languages.min.js` | Страница «Языки» |
| `assets/components/localizator3/css/mgr/vue-dist/*.min.css` | Стили Vue UI |

### 3. Сборка transport-пакета

Из **корня репозитория**:

```bash
php _build/build.php
```

Скрипт собирает transport, при необходимости запускает `composer install` в `core/components/localizator3/` и может установить пакет на локальный MODX (см. `install` в `_build/config.inc.php`).

Пакет: `core/packages/localizator3-1.0.7-beta.transport.zip`

> **Примечание:** `_build/build.transport.php` — устаревший скрипт сборки. Используйте `_build/build.php`.

### 4. Установка

1. **Управление → Пакеты** — загрузить `.transport.zip` вручную или установить через резолвер при `install => true`.
2. Очистите кэш MODX после установки/обновления.

---

## После установки

### 1. Добавление языков

**Localizator3 → Языки** — создать языки:

| Поле | Описание | Пример |
|------|----------|--------|
| Ключ | Уникальный код языка | `ru`, `en`, `de` |
| Имя | Название | Русский, English |
| HTTP host | Домен (мультидоменность) | `en.site.com` |
| cultureKey | Ключ культуры MODX | `en` |
| Активен | Включён ли язык | Да |
| Ранг | Порядок сортировки | 0, 1, 2 |

### 2. Локализация ресурса

1. Откройте ресурс в менеджере.
2. Вкладка **Локализация** → **Добавить перевод**.
3. Заполните поля (pagetitle, content, TV) или воспользуйтесь автопереводом.
4. Сохраните.

### 3. Вывод на фронте

**pdoResources с локализацией:**

```fenom
{'!getLocalizedResources' | snippet : [
  'snippet' => 'pdoResources',
  'parents' => 0,
  'tpl' => '@INLINE <li><a href="{$uri}">{$pagetitle}</a></li>'
]}
```

**Переключатель языков** (сниппет `getLanguageList`):

```fenom
{'!getLanguageList' | snippet}
{'!getLanguageList' | snippet : ['outputMode' => 'dropdown', 'activeClass' => 'current']}
```

**URL с учётом языка:**

```fenom
<a href="{'!makeLocalizedUrl' | snippet : ['id' => $id]}">{$pagetitle}</a>
```

**Canonical и hreflang (SEO):**

```fenom
{'!getLocalizedCanonical' | snippet}
```

**Sitemap с hreflang:**

```fenom
{'!getLocalizedSitemap' | snippet : ['parents' => '0', 'depth' => 5]}
```

---

## CLI: массовый перевод

```bash
cd core/components/localizator3/scripts
php translate_resources.php --parents=1 --depth=5
php translate_resources.php --ids=1,2,3 --dry-run
```

| Параметр | Описание |
|----------|----------|
| `--ids=1,2,3` | ID ресурсов через запятую |
| `--parents=1` | ID родителя |
| `--depth=5` | Глубина (по умолчанию 10) |
| `--dry-run` | План без выполнения |

Переменные окружения: `MODX_BASE_PATH`, `LOCALIZATOR_CONTEXT` (по умолчанию `web`).

---

## Миграции базы данных

Localizator3 использует **Phinx** для миграций. Резолвер `resolve.migrations.php` запускает миграции автоматически при установке/обновлении пакета.

Ручной запуск:

```bash
cd core/components/localizator3
composer install
vendor/bin/phinx migrate -c phinx.php
```

---

## Устранение неполадок

| Проблема | Решение |
|----------|---------|
| Ошибка «modX not found» при сборке | Проверьте расположение MODX или `MODX_BASE_PATH` |
| pdoResources не учитывает локализацию | `pdoFetch.class` = `pdotools.pdofetchlocalizator3` |
| 404 на страницах без локализации | Настройка `localizator3_404_if_no_localization` |
| Автоопределение не работает | `localizator3_auto_detect_language`, cookie `localizator3_key` |
| «Языки не настроены» | Добавьте языки в Localizator3 → Языки, `active = 1` |
| Пустой список переводов | Записи в `localizator3_content`, включите `localizator3_debug_log` |
| Старый JS/CSS после обновления | Очистите кэш MODX и браузера; assets с cache busting через `filemtime()` |
| `Could not load class: localizator3\...` | Пересоберите и переустановите пакет |
| Переключатель языков не работает | Используйте сниппет **`getLanguageList`**, не `getLocales` |

**Отладка:** включите `localizator3_debug_log`. Логи: `core/cache/logs/error.log` с префиксом `[localizator3]`.

---

## Связанные документы

- [Конфигурация](./configuration.md)
- [API Reference](./api.md)
- [Архитектура](./architecture.md)
- [Кастомизация](./CUSTOMIZATION.md)
- [miniShop3](./04_miniShop3_integration.md)
- [mSearch](./05_mSearch_integration.md)
