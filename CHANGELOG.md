# Changelog

All notable changes to Localizator3 are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

---

## [Unreleased]

### Changed
- (pending changes)

---

## [1.0.7-beta]

### Changed
- Vue UI: scoped styles с BEM-префиксами (.content-grid__*, .languages-grid__*)
- Vue UI: замена native confirm() на PrimeVue ConfirmDialog (useConfirm)
- Vue UI: appendTo="self" для Dialog-порталов (изоляция от ExtJS)
- CSS: откат глобального box-sizing от PrimeVue через !important в main.css
- Удалены legacy ExtJS-стили из main.css
- Удалены дублирующие import-ы PrimeVue компонентов в ContentGrid.vue
- Обновлена документация: README.md, docs/*, llms.txt, architecture.md, api.md, configuration.md, CUSTOMIZATION.md
- Исправлено имя сниппета переключателя языков: getLanguageList
- Cursor rules обновлены под Localizator3

---

## [1.0.6-beta]

### Added
- Лексиконы для языков СНГ: украинский (uk), казахский (kk), узбекский (uz), азербайджанский (az)
- Vue-вкладка локализации в форме ресурса (ContentGrid.vue)
- Процессор mgr/content/getformconfig — конфигурация формы с учётом кастомизации
- OnBuildLocalizationTabs расширен: resource, user, customization
- Поддержка visible/rank полей для кастомизации по пользователю
- mSearch integration — индексация локализованных полей (ru-pagetitle, en-description и т.д.)
- msProductOption — локализация опций miniShop3 (цвет, размер)
- Таблицы localizator3_option, localizator3_product_option
- Fenom-модификаторы: locoptioncaption, locproductoptionvalue
- Методы: getLocalizedOptionCaption, getLocalizedOptionDescription, getLocalizedProductOptionValue

---

## [1.0.5-beta]

### Removed
- mSearch2/mFilter2 support — использовать mFilter для фасетной фильтрации

---

## [1.0.4-beta]

### Added
- miniShop3 integration — вкладка Localizator для msProduct
- getLocalizedResources с msProducts/ms3Products

---

## [1.0.3-beta]

### Added
- Snippet getLocalizedField — вывод поля ресурса на указанном языке
- CLI script translate_resources.php — массовый перевод из консоли
- Автоопределение языка посетителя — localizator3_auto_detect_language, redirect по Accept-Language/cookie

---

## [1.0.2-beta]

### Added
- Событие OnToggleLocalizatorLanguage — при определении языка (валидация, кэш, интеграции)
- Snippet getLocalizedSitemap — sitemap.xml с hreflang для мультиязычных страниц (SEO)

---

## [1.0.1-beta]

### Added
- Параметр onlyWithLocalization в getLocalizedResources — исключает ресурсы без локализации (INNER JOIN)
- Snippet makeLocalizedUrl — формирование URL ресурса с языковым префиксом
- Расширен getLanguageList — outputMode (list/dropdown), showActive, activeClass; chunk languages.dropdown.tpl
- Настройка localizator3_404_if_no_localization — 404 для документов без локализации
- Событие OnHasLocalizatorError404
- Поле rank в localizator3_languages — сортировка языков (sortby, sortdir в getLanguageList)
- Snippet getLocalizedCanonical — canonical и hreflang для SEO

---

## [1.0.0-beta]

### Added
- Initial release for MODX 3 only
- Fork of Localizator adapted exclusively for MODX Revolution 3
- Отдельное имя пакета (localizator3) и таблицы (localizator3_*) — без конфликтов с Localizator
- Использование MODX 3 namespaces (\MODX\Revolution\*, \xPDO\Transport\*)
- Только нативная поддержка MODX 3 — без слоя совместимости
