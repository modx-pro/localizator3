# Документация Localizator3

Мультиязычность для **MODX Revolution 3**: локализация ресурсов, TV, товаров miniShop3 и опций.

**Версия:** 1.0.8-beta · **Репозиторий:** [github.com/modx-pro/localizator3](https://github.com/modx-pro/localizator3)

---

## Быстрый старт

| Документ | Описание |
|----------|----------|
| [installation.md](./installation.md) | Требования, установка пакета, сборка Vue UI, CLI, troubleshooting |
| [configuration.md](./configuration.md) | Системные настройки (`localizator3_*`) |
| [../README.md](../README.md) | Обзор возможностей и quick start |

---

## Справочник

| Документ | Описание |
|----------|----------|
| [api.md](./api.md) | Сниппеты, Fenom, события, процессоры, CLI |
| [architecture.md](./architecture.md) | Модели, плагины, Vue UI, потоки данных |
| [customization.md](./customization.md) | Событие `OnBuildLocalizationTabs`, кастомизация формы |

---

## Интеграции

| Документ | Описание |
|----------|----------|
| [integration-minishop3.md](./integration-minishop3.md) | Товары, опции, Fenom-модификаторы MS3 |
| [integration-msearch.md](./integration-msearch.md) | Индексация локализованных полей |

---

## Разработка

| Документ | Описание |
|----------|----------|
| [roadmap.md](./roadmap.md) | План развития, анализ CMS (Babel/WPML/Drupal), каталог фич F1–F11 |
| [llms.txt](./llms.txt) | Краткий контекст для LLM |
| [../core/components/localizator3/docs/changelog.txt](../core/components/localizator3/docs/changelog.txt) | История изменений (в transport-пакете) |

---

## Соглашения

### Именование файлов

| Правило | Пример |
|---------|--------|
| `kebab-case`, латиница | `installation.md`, `integration-minishop3.md` |
| Без префиксов с номерами | ~~`04_miniShop3_integration.md`~~ → `integration-minishop3.md` |
| Без UPPERCASE | ~~`INSTALL_LOCALIZATOR3.md`~~ → `installation.md` |
| Исключение | `llms.txt` — машиночитаемый индекс для LLM |

### Структура документа

1. Заголовок `# Тема — Localizator3`
2. Краткое описание (1–2 предложения)
3. Строка **Версия:** при необходимости
4. Основные разделы (`##`)
5. Блок **См. также** со ссылками на связанные документы и [оглавление](./README.md)

### Язык

- Пользовательская и техническая документация — **русский**
- Идентификаторы кода, настройки, action процессоров — **как в коде** (англ.)
