# Сборка и установка Localizator3

**Localizator3** — компонент мультиязычности для **MODX 3 only**. Для MODX 2.x используйте оригинальный [Localizator](https://github.com/modx-pro/localizator).

---

## Требования

- **MODX Revolution 3.0+**
- **PHP 8.2+**
- **pdoTools** (устанавливается автоматически при установке пакета)
- **Node.js 18+** (для сборки Vue UI)
- **miniShop3** (опционально, для локализации товаров и опций)
- **mSearch** (опционально, для полнотекстового поиска)

---

## Сборка пакета из исходников

### 1. Подготовка окружения

Клонируйте репозиторий и разместите его так, чтобы MODX был доступен:

```
/путь/к/проекту/
├── core/           # MODX core
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

Либо задайте `MODX_BASE_PATH`:

```bash
export MODX_BASE_PATH=/путь/к/modx/
```

### 2. Сборка Vue UI

```bash
cd localizator3/vueManager
npm install
npm run build
```

Результат: `assets/components/localizator3/js/mgr/content.min.js` и `languages.min.js`.

### 3. Сборка transport-пакета

```bash
cd localizator3/_build
php build.transport.php
```

Пакет: `core/packages/localizator3-1.0.0-beta.transport.zip`

### 4. Установка

1. **Управление → Пакеты** — загрузить `.transport.zip` вручную.
2. pdoTools установится автоматически.
3. В настройках pdoTools появится **pdoFetch.class** = `pdotools.pdofetchlocalizator3`.

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

**Переключатель языков:**

```fenom
{'!getLocales' | snippet}
```

**URL с учётом языка:**

```fenom
<a href="{'!makeLocalizedUrl' | snippet : ['id' => $id]}">{$pagetitle}</a>
```

**Canonical и hreflang (SEO):**

```fenom
{'!getLocalizedCanonical' | snippet}
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

---

## Миграции базы данных

Localizator3 использует **Phinx** для миграций. Резолвер `resolve.migrations.php` запускает миграции автоматически при установке/обновлении пакета.

Ручной запуск:

```bash
cd core/components/localizator3
vendor/bin/phinx migrate -c phinx.php
```

---

## Устранение неполадок

| Проблема | Решение |
|----------|---------|
| Ошибка «modX not found» при сборке | Проверьте `MODX_BASE_PATH` |
| pdoResources не учитывает локализацию | `pdoFetch.class` = `pdotools.pdofetchlocalizator3` |
| 404 на страницах без локализации | Настройка `localizator3_404_if_no_localization` |
| Автоопределение не работает | Настройка `localizator3_auto_detect_language`, cookie `localizator3_key` |
| «Языки не настроены» | Добавьте языки в Localizator3 → Языки, поле `active = 1` |
| Пустой список переводов | Проверьте записи в `localizator3_content`, включите `localizator3_debug_log` |
| Старый JS/CSS после обновления | Очистите кэш браузера; файлы используют `?v=` cache busting через `filemtime()` |
| `Could not load class: localizator3\...` | Пересоберите и переустановите пакет |

**Отладка:** включите `localizator3_debug_log` в системных настройках. Логи: `core/cache/logs/error.log` с префиксом `[localizator3]`.

---

## Связанные документы

- [Конфигурация](./configuration.md)
- [API Reference](./api.md)
- [Архитектура](./architecture.md)
- [Кастомизация](./CUSTOMIZATION.md)
- [miniShop3](./04_miniShop3_integration.md)
- [mSearch](./05_mSearch_integration.md)
