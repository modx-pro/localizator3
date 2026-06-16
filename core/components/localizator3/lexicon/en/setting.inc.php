<?php

$_lang['area_localizator_main'] = 'Basic';
$_lang['area_localizator_translator'] = 'Translated';
$_lang['area_localizator3_main'] = 'Basic';
$_lang['area_localizator3_translator'] = 'Translated';

$_lang['setting_localizator_default_language'] = 'The default localization key';
$_lang['setting_localizator_default_language_desc'] = 'Key localization for primary language version';
$_lang['setting_localizator3_default_language'] = 'The default localization key';
$_lang['setting_localizator3_default_language_desc'] = 'Key localization for primary language version';

$_lang['setting_localizator_key_yandex'] = 'API key for Yandex';
$_lang['setting_localizator_key_yandex_desc'] = 'API key for Yandex translator, <a href="https://translate.yandex.ru/developers/keys" target="_blank">get API key</a>';
$_lang['setting_localizator3_key_yandex'] = 'API key for Yandex';
$_lang['setting_localizator3_key_yandex_desc'] = 'API key for Yandex translator, <a href="https://translate.yandex.ru/developers/keys" target="_blank">get API key</a>';

$_lang['setting_localizator_key_google'] = 'API key for Google';
$_lang['setting_localizator_key_google_desc'] = 'API key for Google translator, <a href="https://cloud.google.com/translate/" target="_blank">Google cloud</a>';
$_lang['setting_localizator3_key_google'] = 'API key for Google';
$_lang['setting_localizator3_key_google_desc'] = 'API key for Google translator, <a href="https://cloud.google.com/translate/" target="_blank">Google cloud</a>';

$_lang['setting_localizator_key_deepl'] = 'API key for Deepl';
$_lang['setting_localizator_key_deepl_desc'] = 'API key for Deepl , <a href="https://www.deepl.com/ru/pro/select-country/" target="_blank">Get key</a>';
$_lang['setting_localizator3_key_deepl'] = 'API key for Deepl';
$_lang['setting_localizator3_key_deepl_desc'] = 'API key for Deepl , <a href="https://www.deepl.com/ru/pro/select-country/" target="_blank">Get key</a>';

$_lang['setting_localizator3_key_libretranslate'] = 'LibreTranslate API key';
$_lang['setting_localizator3_key_libretranslate_desc'] = 'Optional. For public API <a href="https://libretranslate.com" target="_blank">libretranslate.com</a>. Self-hosted does not require a key.';

$_lang['setting_localizator3_libretranslate_url'] = 'LibreTranslate URL';
$_lang['setting_localizator3_libretranslate_url_desc'] = 'Self-hosted instance URL (default: http://localhost:5000). Or https://libretranslate.com for public API.';

$_lang['setting_localizator3_mymemory_email'] = 'MyMemory email';
$_lang['setting_localizator3_mymemory_email_desc'] = 'Optional. Valid email increases free limit from 5,000 to 50,000 characters/day. <a href="https://mymemory.translated.net" target="_blank">MyMemory</a>';

$_lang['setting_localizator_default_translator'] = 'Translator automatic translation';
$_lang['setting_localizator_default_translator_desc'] = 'Possible values: Yandex, Google, DeepL, LibreTranslate, MyMemory, or leave blank to copy values';
$_lang['setting_localizator3_default_translator'] = 'Translator automatic translation';
$_lang['setting_localizator3_default_translator_desc'] = 'Possible values: Yandex, Google, DeepL, LibreTranslate, MyMemory, or leave blank to copy values';

$_lang['setting_localizator_translate_translated'] = 'Complement translated localization?';
$_lang['setting_localizator_translate_translated_desc'] = 'When you use automatic translation translate blank fields from existing localisations';
$_lang['setting_localizator3_translate_translated'] = 'Complement translated localization?';
$_lang['setting_localizator3_translate_translated_desc'] = 'When you use automatic translation translate blank fields from existing localisations';

$_lang['setting_localizator_translate_translated_fields'] = 'Overwrite the translated localization?';
$_lang['setting_localizator_translate_translated_fields_desc'] = 'When you use automatic translation overwrites all fields localizations';
$_lang['setting_localizator3_translate_translated_fields'] = 'Overwrite the translated localization?';
$_lang['setting_localizator3_translate_translated_fields_desc'] = 'When you use automatic translation overwrites all fields localizations';

$_lang['setting_localizator_translate_fields'] = 'List of fields for the translation';
$_lang['setting_localizator_translate_fields_desc'] = 'These fields will be translated when using automatic translation';
$_lang['setting_localizator3_translate_fields'] = 'List of fields for the translation';
$_lang['setting_localizator3_translate_fields_desc'] = 'These fields will be translated when using automatic translation';

$_lang['setting_localizator_tv_fields'] = 'List of additional fields (TV)';
$_lang['setting_localizator_tv_fields_desc'] = 'These additional fields will be available for editing in the localization. Leave the setting blank, if you need all the extra fields';

$_lang['setting_localizator_check_permissions'] = 'Check permissions';
$_lang['setting_localizator_check_permissions_desc'] = 'Check permissions to edit localization';
$_lang['setting_localizator3_check_permissions'] = 'Check permissions';
$_lang['setting_localizator3_check_permissions_desc'] = 'Check permissions to edit localization';

$_lang['setting_localizator_disabled_templates'] = 'Disable localization tab for templates';
$_lang['setting_localizator_disabled_templates_desc'] = 'Comma-separated list of template IDs. The Localizator tab will not be shown for resources using these templates.';
$_lang['setting_localizator3_disabled_templates'] = 'Disable localization tab for templates';
$_lang['setting_localizator3_disabled_templates_desc'] = 'Comma-separated list of template IDs. The Localizator tab will not be shown for resources using these templates.';

$_lang['setting_localizator3_404_if_no_localization'] = '404 for documents without localization';
$_lang['setting_localizator3_404_if_no_localization_desc'] = 'When enabled, show 404 instead of original content when a document has no localization for the current language (SEO).';

$_lang['setting_localizator3_auto_detect_language'] = 'Auto-detect visitor language';
$_lang['setting_localizator3_auto_detect_language_desc'] = 'When enabled, redirect first-time visitors to the language version based on Accept-Language header or cookie.';

$_lang['setting_localizator3_debug_log'] = 'Debug logging';
$_lang['setting_localizator3_debug_log_desc'] = 'Log getformconfig/getlist calls to MODX error log for troubleshooting empty languages or translation list.';
