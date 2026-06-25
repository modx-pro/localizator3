<?php

include_once 'setting.inc.php';

$_lang['localizator'] = 'localizator';
$_lang['localizator3'] = 'Localizator3';
$_lang['localizator_tab'] = 'Локализация';
$_lang['localizator_menu_desc'] = 'Локализация сайта';
$_lang['localizator_intro_msg'] = 'Вы можете выделять сразу несколько строк при помощи Shift или Ctrl.';
$_lang['localizator_languages'] = 'Локализации';
$_lang['localizator_languages_section_desc'] = 'Управление языками сайта, HTTP-хостами и ключами cultureKey для локализации.';
$_lang['localizator_content_section_desc'] = 'Управление переводами полей ресурса, TV и автопереводом.';
$_lang['localizator_content_section_desc_native'] = 'Переводы на другие языки. Базовый язык редактируется в основной форме ресурса (вкладки «Документ» и TV).';
$_lang['localizator_stats_total'] = 'Всего языков';
$_lang['localizator_stats_active'] = 'Активных';
$_lang['localizator_stats_inactive'] = 'Неактивных';
$_lang['localizator_stats_translations'] = 'Переводов';
$_lang['localizator_stats_available'] = 'Доступно языков';

$_lang['localizator_id'] = 'Id';
$_lang['localizator_key'] = 'Ключ';
$_lang['localizator_pagetitle'] = 'Заголовок';
$_lang['localizator_longtitle'] = 'Расширенный заголовок';
$_lang['localizator_menutitle'] = 'Заголовок меню';
$_lang['localizator_longtitle'] = 'Расширенный заголовок';
$_lang['localizator_seotitle'] = 'SEO Заголовок';
$_lang['localizator_description'] = 'SEO Описание';
$_lang['localizator_keywords'] = 'SEO Ключевые слова';
$_lang['localizator_introtext'] = 'Аннотация';
$_lang['localizator__key'] = 'Ключ (хост)';
$_lang['localizator_active'] = 'Активно';
$_lang['localizator_language'] = 'Язык';
$_lang['localizator_translate'] = 'Автоматический перевод';
$_lang['localizator_translate_confirm'] = 'Вы действительно хотите выполнить автоматический перевод? Эта операция может занять много времени.';
$_lang['localizator_translate_wait'] = 'Выполняется автоматический перевод';
$_lang['localizator_loading'] = 'Загрузка...';
$_lang['localizator_translate_wait_ext'] = 'Перевод...';
$_lang['localizator_translate_processed'] = 'Обработано строк: ';
$_lang['localizator_add'] = 'Добавить перевод';
$_lang['localizator_error'] = 'Ошибка';
$_lang['localizator_unknown_error'] = 'Неизвестная ошибка';
$_lang['localizator_cancel'] = 'Отмена';
$_lang['localizator_success'] = 'Успешно';
$_lang['localizator_save'] = 'Сохранить';
$_lang['localizator_language_updated'] = 'Язык обновлён';
$_lang['localizator_language_created'] = 'Язык создан';
$_lang['localizator_deleted'] = 'Удалено';
$_lang['localizator_enabled'] = 'Включено';
$_lang['localizator_disabled'] = 'Отключено';

$_lang['localizator_language_create'] = 'Добавить локализацию';
$_lang['localizator_language_update'] = 'Обновить локализацию';
$_lang['localizator_language_key'] = 'Ключ локализации';
$_lang['localizator_language_name'] = 'Название';
$_lang['localizator_language_cultureKey'] = 'Ключ языка';
$_lang['localizator_language_http_host'] = 'HTTP HOST';
$_lang['localizator_language_http_host_hint'] = 'Без http:// и https://, например: project.test/ru/';
$_lang['localizator_language_http_host_placeholder'] = 'project.test/ru/';
$_lang['localizator_language_description'] = 'Описание';
$_lang['localizator_language_err_no_key'] = 'Не указан ключ локализации';
$_lang['localizator_language_err_key_exist'] = 'Ключ локализации уже используется';
$_lang['localizator_language_err_no_http_host'] = 'Не указан HTTP HOST';
$_lang['localizator_language_err_http_host_exist'] = 'HTTP HOST уже используется';

$_lang['localizator_lexicon'] = 'Словарь';
$_lang['localizator_lexicon_create'] = 'Добавить перевод';
$_lang['localizator_lexicon_name'] = 'Имя (ключ)';
$_lang['localizator_lexicon_language'] = 'Язык';
$_lang['localizator_lexicon_value'] = 'Значение';

$_lang['localizator_item_create'] = 'Создать';
$_lang['localizator_item_update'] = 'Изменить';
$_lang['localizator_item_enable'] = 'Включить';
$_lang['localizator_items_enable'] = 'Включить';
$_lang['localizator_item_disable'] = 'Отключить ';
$_lang['localizator_items_disable'] = 'Отключить';
$_lang['localizator_item_remove'] = 'Удалить';
$_lang['localizator_items_remove'] = 'Удалить';
$_lang['localizator_item_remove_confirm'] = 'Вы уверены, что хотите удалить эту запись?';
$_lang['localizator_items_remove_confirm'] = 'Вы уверены, что хотите удалить эти записи?';
$_lang['localizator_item_active'] = 'Включено';

$_lang['localizator_item_err_nf'] = 'Запись не найдена';
$_lang['localizator_item_err_ns'] = 'Не указана запись';
$_lang['localizator_item_err_remove'] = 'Ошибка при удалении';
$_lang['localizator_item_err_save'] = 'Ошибка при сохранении';
$_lang['localizator_item_err_no_line'] = 'Для автоматического перевода необходимо добавить хотя бы одну запись в таблицу';
$_lang['localizator_item_err_default_language'] = 'Не указана опция localizator_default_language, невозможно определить исходный языка для перевода';
$_lang['localizator_item_err_yandex_key'] = 'Ошибка при переводе - не указан Yandex api key в системных настройках';
$_lang['localizator_item_err_google_key'] = 'Ошибка при переводе - не указан Google api key в системных настройках';
$_lang['localizator_item_err_deepl_key'] = 'Ошибка при переводе - не указан Deepl api key в системных настройках';

$_lang['localizator_grid_search'] = 'Поиск';
$_lang['localizator_grid_actions'] = 'Действия';

$_lang['localizator_content_created'] = 'Перевод создан';
$_lang['localizator_content_updated'] = 'Перевод обновлён';
$_lang['localizator_content_err_ae'] = 'Локализация ресурса уже заполнена';
$_lang['localizator_content_err_default_from_resource'] = 'Базовый язык редактируется в основной форме ресурса. Создайте перевод на другой язык.';
$_lang['localizator_no_available_languages'] = 'Нет доступных языков. Все локализации для этого ресурса уже созданы.';
$_lang['localizator_no_languages_configured'] = 'Языки не настроены. Добавьте языки в разделе Localizator3 → Языки.';
$_lang['localizator_add_languages_hint'] = 'Добавьте языки в разделе Localizator3 → Языки';

$_lang['tv_localizator_enabled'] = 'TV доступно в локализациях';
$_lang['tv_localizator_enabled_msg'] = 'Дополнительное поле доступно для редактирования в локализациях';
$_lang['tv_localizator3_enabled'] = 'TV доступно в локализациях';
$_lang['tv_localizator3_enabled_msg'] = 'Дополнительное поле доступно для редактирования в локализациях';
$_lang['localizator3_tab'] = 'Локализация';

// Ключи из core (для попапа «Добавить перевод»)
$_lang['resource_pagetitle'] = 'Заголовок';
$_lang['resource_longtitle'] = 'Расширенный заголовок';
$_lang['resource_menutitle'] = 'Заголовок меню';
$_lang['resource_description'] = 'Описание';
$_lang['resource_content'] = 'Содержимое';
$_lang['introtext'] = 'Аннотация';
$_lang['document'] = 'Документ';
$_lang['tvs'] = 'Дополнительные поля';
$_lang['uncategorized'] = 'Без категории';

// VueTools dependency
$_lang['localizator3_vuetools_required'] = 'Для работы Vue-интерфейса требуется пакет <strong>VueTools ≥ 1.1.2-pl</strong>. Установите его через <a href="https://modstore.pro/packages/utilities/vuetools" target="_blank">modstore.pro</a>.';
