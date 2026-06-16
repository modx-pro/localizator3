# Кастомизация вкладки локализации

Вкладка локализации в форме ресурса поддерживает кастомизацию через событие **OnBuildLocalizationTabs** и конфигурацию формы.

---

## Событие OnBuildLocalizationTabs

Событие вызывается при построении формы. Плагины могут изменять вкладки и поля.

### Параметры

| Параметр | Тип | Описание |
|----------|-----|----------|
| `tabs` | array | Массив вкладок и полей (по ссылке) |
| `resource` | modResource | Объект ресурса |
| `user` | modUser | Текущий пользователь |
| `customization` | array | Контекст: `user_id`, `resource_id`, `context_key`, `class_key` |
| `localizatorContent` | object\|null | Объект `localizatorContent` (при legacy mgr/fields) или null (при getformconfig) |

### Возврат

В `$modx->event->returnedValues`:

- `tabs` — изменённые вкладки
- `customization` — дополнительные данные (опционально)

---

## Структура вкладок

```php
$tabs = [
    'document' => [
        'title' => 'Документ',
        'fields' => [
            [
                'field' => 'pagetitle',
                'label' => 'Заголовок',
                'type' => 'text',      // text, textarea, richtext
                'visible' => true,
                'rank' => 0,
            ],
            // ...
        ],
    ],
    'tvs' => [
        'title' => 'TV',
        'fields' => [
            [
                'field' => 'tv_123',
                'label' => 'Мой TV',
                'type' => 'text',
                'visible' => true,
            ],
        ],
    ],
];
```

---

## Пример: скрытие полей по роли

```php
case 'OnBuildLocalizationTabs':
    $tabs = $modx->event->params['tabs'];
    $user = $modx->event->params['user'];

    if ($user && $user->get('primary_group') == 2) {
        if (isset($tabs['document']['fields'])) {
            foreach ($tabs['document']['fields'] as &$field) {
                if (in_array($field['field'], ['seotitle', 'keywords'])) {
                    $field['visible'] = false;
                }
            }
        }
    }
    $modx->event->returnedValues['tabs'] = $tabs;
    break;
```

---

## Пример: изменение порядка полей

```php
case 'OnBuildLocalizationTabs':
    $tabs = $modx->event->params['tabs'];
    if (isset($tabs['document']['fields'])) {
        $fields = $tabs['document']['fields'];
        usort($fields, function ($a, $b) {
            $order = ['pagetitle' => 0, 'description' => 1, 'content' => 2];
            return ($order[$a['field']] ?? 99) - ($order[$b['field']] ?? 99);
        });
        $tabs['document']['fields'] = $fields;
    }
    $modx->event->returnedValues['tabs'] = $tabs;
    break;
```

---

## Пример: добавление кастомного поля

```php
case 'OnBuildLocalizationTabs':
    $tabs = $modx->event->params['tabs'];
    $tabs['document']['fields'][] = [
        'field' => 'custom_seo_title',
        'label' => 'SEO Title (кастомный)',
        'type' => 'text',
        'visible' => true,
        'rank' => 100,
    ];
    $modx->event->returnedValues['tabs'] = $tabs;
    break;
```

---

## API getformconfig (Vue)

Процессор `mgr/content/getformconfig` возвращает JSON для Vue-вкладки:

| Поле | Тип | Описание |
|------|-----|----------|
| `formtabs` | array | Вкладки и поля (с учётом visible) |
| `customization` | object | user_id, resource_id, context_key, class_key |
| `languages` | array | Список активных языков |
| `record` | object\|null | Данные записи (при параметре `loc_id`) |

Плагины могут менять `tabs` и `customization` через событие `OnBuildLocalizationTabs`.

---

## Другие события для кастомизации

| Событие | Описание |
|---------|----------|
| `OnBeforeSaveLocalization` | Модификация данных перед сохранением |
| `OnSaveLocalization` | Пост-обработка после сохранения |
| `OnBeforeSaveLocalizatorLanguage` | Валидация перед сохранением языка |
| `OnSaveLocalizatorLanguage` | Пост-обработка после сохранения языка |
| `OnHasLocalizatorError404` | Кастомная обработка 404 (можно предотвратить) |

---

## Связанные документы

- [API Reference](./api.md) — полный список событий
- [Архитектура](./architecture.md) — модели и процессоры
