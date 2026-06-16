# Интеграция Localizator3 с miniShop3

Локализация товаров, опций и их значений miniShop3.

---

## Поддерживаемые возможности

### Локализация полей msProduct

Товары miniShop3 (`MiniShop3\Model\msProduct`) наследуют от `modResource`. Localizator3 сохраняет локализацию в `localizator3_content` по `resource_id` товара.

**Локализуемые поля:** pagetitle, longtitle, menutitle, seotitle, keywords, introtext, description, content, TV.

### Вкладка Локализация в форме товара

При редактировании товара в менеджере вкладка **Локализация** отображается автоматически. Поддерживаемые класcы:

- `\MODX\Revolution\modResource`
- `\MiniShop3\Model\msProduct`

### pdoResources с товарами

```fenom
{'!getLocalizedResources' | snippet : [
  'snippet' => 'msProducts',
  'parents' => 5,
  'tpl' => '@INLINE <div class="product">{$pagetitle} — {$price}</div>',
  'onlyWithLocalization' => 1
]}
```

Или через `ms3Products`:

```fenom
{'!getLocalizedResources' | snippet : [
  'snippet' => 'ms3Products',
  'parents' => 5,
  'tpl' => '@INLINE <div class="product">{$pagetitle}</div>'
]}
```

---

## Локализация опций (msOption)

Модели для локализации опций miniShop3:

| Модель | Таблица | Назначение |
|--------|---------|------------|
| `locOption` | `localizator3_option` | caption и description опции по `option_id` и `key` (язык) |
| `locProductOption` | `localizator3_product_option` | value опции товара по `product_option_id` и `key` |

### Fenom-модификаторы

```fenom
{* Локализованный caption опции (название: цвет, размер) *}
{$option_id | locoptioncaption}
{$option_id | locoptioncaption : 'По умолчанию'}

{* Локализованное value опции товара (значение: красный, XL) *}
{$product_option_id | locproductoptionvalue}
{$product_option_id | locproductoptionvalue : '—'}
```

### PHP

```php
$localizator = $modx->getService('localizator3', 'localizator', $corePath . 'model/localizator3/');

$caption = $localizator->getLocalizedOptionCaption($option_id, 'По умолчанию');
$description = $localizator->getLocalizedOptionDescription($option_id, '');
$value = $localizator->getLocalizedProductOptionValue($product_option_id, '—');
```

---

## Автоперевод

При создании локализации товара доступен автоперевод полей:

| Переводчик | Тип | API-ключ |
|------------|-----|----------|
| **Yandex** | Cloud API | Обязателен |
| **Google** | Cloud Translation | Обязателен |
| **DeepL** | REST API | Обязателен |
| **LibreTranslate** | Self-hosted / SaaS | Опционально |
| **MyMemory** | REST API | Не нужен |
| **SimpleCopy** | Копирование | Не нужен |

Настройка: Система → Настройки → Localizator3 → область «Переводчик».

---

## Настройка pdoTools

В настройках pdoTools (namespace `pdotools`):

```
pdoFetch.class = pdotools.pdofetchlocalizator3
```

Создаётся автоматически при установке.

---

## В планах

| Задача | Описание | Статус |
|--------|----------|--------|
| Способы оплаты/доставки | Локализация msPayment, msDelivery (name, description) | Ожидает |
| mFilter | Подстановка locOption / locProductOption в фасетные фильтры | Ожидает |

---

## Связанные документы

- [Установка](./INSTALL_LOCALIZATOR3.md)
- [API Reference](./api.md)
- [mSearch](./05_mSearch_integration.md)
