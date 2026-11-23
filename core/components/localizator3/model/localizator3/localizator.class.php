<?php

class localizator
{
    /** @var modX $modx */
    public $modx;


    /**
     * @param modX $modx
     * @param array $config
     */
    public function __construct($modx, array $config = array())
    {
        $this->modx = $modx;

        $corePath = $this->modx->getOption(
            'localizator3_core_path',
            $config,
            $this->modx->getOption('core_path') . 'components/localizator3/'
        );
        $assetsUrl = $this->modx->getOption(
            'localizator3_assets_url',
            $config,
            $this->modx->getOption('assets_url') . 'components/localizator3/'
        );
        $connectorUrl = $assetsUrl . 'connector.php';
        if (strpos($connectorUrl, 'http') !== 0 && strpos($connectorUrl, '//') !== 0 && (strlen($connectorUrl) === 0 || $connectorUrl[0] !== '/')) {
            $connectorUrl = rtrim($this->modx->getOption('site_url'), '/') . '/' . ltrim($connectorUrl, '/');
        }

        $this->config = array_merge(array(
            'assetsUrl' => $assetsUrl,
            'cssUrl' => $assetsUrl . 'css/',
            'jsUrl' => $assetsUrl . 'js/',
            'imagesUrl' => $assetsUrl . 'images/',
            'connectorUrl' => $connectorUrl,

            'corePath' => $corePath,
            'modelPath' => $corePath . 'model/',
            'chunksPath' => $corePath . 'elements/chunks/',
            'templatesPath' => $corePath . 'elements/templates/',
            'chunkSuffix' => '.chunk.tpl',
            'snippetsPath' => $corePath . 'elements/snippets/',
            'processorsPath' => $corePath . 'processors/',
            'translator' => $this->modx->getOption('localizator3_default_translator', null, 'SimpleCopy', true),
        ), $config);

        $allowedTranslators = array('SimpleCopy', 'Yandex', 'Google', 'DeepL', 'LibreTranslate', 'MyMemory');
        $translator = (string)$this->config['translator'];
        if (!in_array($translator, $allowedTranslators, true)) {
            $this->modx->log(\modX::LOG_LEVEL_ERROR, '[localizator3] Unknown translator "' . $translator . '", using SimpleCopy');
            $translator = 'SimpleCopy';
            $this->config['translator'] = $translator;
        }

        require_once dirname(__FILE__, 3) . '/translators/' . strtolower($translator) . '.class.php';

        $this->translator = new $translator($this->modx, $this->config);

        $this->modx->addPackage('localizator3', $this->config['modelPath'], null, 'localizator3\\');
        $this->modx->lexicon->load('localizator3:default');
        require_once __DIR__ . '/acceptlanguage.php';
    }


    /**
     * @param string $relativePath Path relative to assets dir (e.g. 'js/mgr/vue-dist/content.min.js')
     * @return string URL with cache-busting query string
     */
    public function versionedAsset($relativePath)
    {
        $assetsPath = $this->modx->getOption(
            'localizator3_assets_path',
            null,
            $this->modx->getOption('assets_path') . 'components/localizator3/'
        );
        $file = $assetsPath . $relativePath;
        $v = is_file($file) ? filemtime($file) : time();
        return $this->config['assetsUrl'] . $relativePath . '?v=' . $v;
    }

    /**
     * @param string $text
     * @param string $from
     * @param string $to
     *
     * @return string
     */
    public function translate($text, $from, $to)
    {
        return $this->translator->translate($text, $from, $to);
    }


    public function createForm(&$formtabs, &$record, &$allfields, &$categories, $scriptProperties)
    {

        $input_prefix = $this->modx->getOption('input_prefix', $scriptProperties, '');
        $input_prefix = !empty($input_prefix) ? $input_prefix . '_' : '';
        $rte = isset($scriptProperties['which_editor']) ? $scriptProperties['which_editor'] : $this->modx->getOption('which_editor', '', $this->modx->_userConfig);


        foreach ($formtabs as $tabid => $subtab) {
            $tabs = array();
            foreach ($subtab['tabs'] as $subtabid => $tab) {
                $tvs = array();
                $fields = $this->modx->getOption('fields', $tab, array());
                $fields = is_array($fields) ? $fields : $this->modx->fromJson($fields);
                if (is_array($fields) && count($fields) > 0) {
                    foreach ($fields as &$field) {
                        $fieldname = $this->modx->getOption('field', $field, '');
                        $useDefaultIfEmpty = $this->modx->getOption('useDefaultIfEmpty', $field, 0);

                        /*generate unique tvid, must be numeric*/
                        /*todo: find a better solution*/
                        $field['tv_id'] = 'localizator3_' . $fieldname;
                        $params = array();
                        $tv = false;


                        if (isset($field['inputTV']) && $tv = $this->modx->getObject(\MODX\Revolution\modTemplateVar::class, array('name' => $field['inputTV']))) {
                            $params = $tv->get('input_properties');
                            $params['inputTVid'] = $tv->get('id');
                        }

                        if (!empty($field['inputTVtype'])) {
                            $tv = $this->modx->newObject(\MODX\Revolution\modTemplateVar::class);
                            $tv->set('type', $field['inputTVtype']);
                        }

                        if (!$tv) {
                            $tv = $this->modx->newObject(\MODX\Revolution\modTemplateVar::class);
                            $tv->set('type', 'text');
                        }

                        $tv->set('name', ($fieldname == 'content' ? 'localizator3_content' : $fieldname));

                        $o_type = $tv->get('type');

                        if ($tv->get('type') == 'richtext') {
                            $tv->set('type', 'migx' . str_replace(' ', '_', strtolower($rte)));
                        }

                        //we change the phptype, that way we can use any id, not only integers (issues on windows-systems with big integers!)
                        $tv->_fieldMeta['id']['phptype'] = 'string';

                        if (!empty($field['inputOptionValues'])) {
                            $tv->set('elements', $field['inputOptionValues']);
                        }
                        if (!empty($field['default'])) {
                            $tv->set('default_text', $tv->processBindings($field['default']));
                        }
                        if (isset($field['display'])) {
                            $tv->set('display', $field['display']);
                        }
                        if (!empty($field['configs'])) {
                            $cfg = $this->modx->fromJson($field['configs']);
                            if (is_array($cfg)) {
                                $params = array_merge($params, $cfg);
                            } else {
                                $params['configs'] = $field['configs'];
                            }
                        }

                        /*insert actual value from requested record, convert arrays to ||-delimeted string */
                        $fieldvalue = '';
                        if (isset($record[$fieldname])) {
                            $fieldvalue = $record[$fieldname];
                            if (is_array($fieldvalue)) {
                                $fieldvalue = is_array($fieldvalue[0]) ? $this->modx->toJson($fieldvalue) : implode('||', $fieldvalue);
                            }
                        }

                        $tv->set('value', $fieldvalue);

                        if (!empty($field['caption'])) {
                            $field['caption'] = htmlentities($field['caption'], ENT_QUOTES, $this->modx->getOption('modx_charset'));
                            $tv->set('caption', $field['caption']);
                        }



                        $desc = '';
                        if (!empty($field['description'])) {
                            $desc = $field['description'];
                            $field['description'] = htmlentities($field['description'], ENT_QUOTES, $this->modx->getOption('modx_charset'));
                            $tv->set('description', $field['description']);
                        }


                        $allfield = array();
                        $allfield['field'] = $fieldname;
                        $allfield['tv_id'] = $field['tv_id'];
                        $allfield['array_tv_id'] = $field['tv_id'] . '[]';
                        $allfields[] = $allfield;

                        $field['array_tv_id'] = $field['tv_id'] . '[]';
                        $mediasource = $this->getFieldSource($field, $tv);

                        $tv->setSource($mediasource);
                        $tv->set('id', $field['tv_id']);

                        $isnew = $this->modx->getOption('isnew', $scriptProperties, 0);
                        $isduplicate = $this->modx->getOption('isduplicate', $scriptProperties, 0);


                        if (!empty($useDefaultIfEmpty)) {
                            //old behaviour minus use now default values for checkboxes, if new record
                            if ($tv->get('value') == null) {
                                $v = $tv->get('default_text');
                                if ($tv->get('type') == 'checkbox' && $tv->get('value') == '') {
                                    if (!empty($isnew) && empty($isduplicate)) {
                                        $v = $tv->get('default_text');
                                    } else {
                                        $v = '';
                                    }
                                }
                                $tv->set('value', $v);
                            }
                        } else {
                            //set default value, only on new records
                            if (!empty($isnew) && empty($isduplicate)) {
                                $v = $tv->get('default_text');
                                $tv->set('value', $v);
                            }
                        }


                        $this->modx->smarty->assign('tv', $tv);

                        if (!isset($params['allowBlank'])) {
                            $params['allowBlank'] = 1;
                        }

                        $value = $tv->get('value');
                        if ($value === null) {
                            $value = $tv->get('default_text');
                        }

                        $this->modx->smarty->assign('params', $params);
                        /* find the correct renderer for the TV, if not one, render a textbox */
                        $inputRenderPaths = $tv->getRenderDirectories('OnTVInputRenderList', 'input');

                        if ($o_type == 'richtext') {
                            $fallback = true;
                            foreach ($inputRenderPaths as $path) {
                                $renderFile = $path . $tv->get('type') . '.class.php';
                                if (file_exists($renderFile)) {
                                    $fallback = false;
                                    break;
                                }
                            }
                            if ($fallback) {
                                $tv->set('type', 'textarea');
                            }
                        }

                        $inputForm = $tv->getRender($params, $value, $inputRenderPaths, 'input', null, $tv->get('type'));
                        $tv->set('formElement', $inputForm);
                        $tvs[] = $tv;
                    }
                }
                $tabs[] = array(
                    'category' => $this->modx->getOption('caption', $tab, 'undefined'),
                    'print_before_tabs' => (isset($tab['print_before_tabs']) && !empty($tab['print_before_tabs']) ? true : false),
                    'id' => $subtabid,
                    'tvs' => $tvs,
                );
            }

            $categories[] = array(
                'category' => $this->modx->getOption('caption', $subtab, 'undefined'),
                'print_before_tabs' => (isset($subtab['print_before_tabs']) && !empty($subtab['print_before_tabs']) ? true : false),
                'id' => $tabid,
                'tabs' => $tabs,
            );
        }
    }



    public function getFieldSource($field, &$tv)
    {
        $sources = [];
        $sourcefrom = isset($field['sourceFrom']) && !empty($field['sourceFrom']) ? $field['sourceFrom'] : 'config';

        if ($sourcefrom == 'config' && isset($field['sources'])) {
            if (is_array($field['sources'])) {
                foreach ($field['sources'] as $context => $sourceid) {
                    $sources[$context] = $sourceid;
                }
            } else {
                $fsources = $this->modx->fromJson($field['sources']);
                if (is_array($fsources)) {
                    foreach ($fsources as $source) {
                        if (isset($source['context']) && isset($source['sourceid'])) {
                            $sources[$source['context']] = $source['sourceid'];
                        }
                    }
                }
            }
        }

        if (isset($sources[$this->working_context]) && !empty($sources[$this->working_context])) {
            //try using field-specific mediasource from config
            if ($mediasource = $this->modx->getObject('sources.modMediaSource', $sources[$this->working_context])) {
                return $mediasource;
            }
        }

        $mediasource = $tv->getSource($this->working_context, false);

        //try to get the context-default-media-source
        if (!$mediasource) {
            $defaultSourceId = null;
            if ($contextSetting = $this->modx->getObject(\MODX\Revolution\modContextSetting::class, array('key' => 'default_media_source', 'context_key' => $this->working_context))) {
                $defaultSourceId = $contextSetting->get('value');
            }
            $mediasource = modMediaSource::getDefaultSource($this->modx, $defaultSourceId);
        }

        return $mediasource;
    }


    public function findLocalization($http_host, $request)
    {
        /* @var localizatorLanguage $language */
        $language = null;

        $response = $this->invokeEvent('OnBeforeFindLocalization', array(
            'language' => &$language,
            'http_host' => $http_host,
            'request' => $request,
        ));
        if (!$response['success']) {
            return $response['message'];
        }

        if (!$language) {
            $host = $find = $http_host;
            $firstSegment = '';
            if ($request) {
                if (strpos($request, '/') !== false) {
                    $tmp = explode('/', $request);
                    $firstSegment = $tmp[0];
                    $find = $host . '/' . $firstSegment . '/';
                } else {
                    $firstSegment = $request;
                    $find = $host . '/' . $request;
                }
            }

            if ($request !== '' && strpos($request, '/') === false) {
                $segmentHost = $host . '/' . $request;
                $bareQuery = $this->modx->newQuery(\localizator3\localizatorLanguage::class);
                $bareQuery->where(array(
                    'active' => 1,
                    'http_host:IN' => array($segmentHost, $segmentHost . '/'),
                ));
                if ($this->modx->getCount(\localizator3\localizatorLanguage::class, $bareQuery)) {
                    $target = $this->resolveUrlScheme() . $http_host . '/' . $request . '/';
                    $params = $_GET;
                    unset($params[$this->modx->getOption('request_param_alias', null, 'q')]);
                    if ($params) {
                        $target .= '?' . http_build_query($params);
                    }
                    $this->modx->sendRedirect($target, array('responseCode' => 'HTTP/1.1 301 Moved Permanently'));
                    return false;
                }
            }

            if ($this->modx->getOption('localizator3_auto_detect_language', null, false, true)) {
                $langKeys = [];
                $langQ = $this->modx->newQuery(\localizator3\localizatorLanguage::class);
                $langQ->where(['active' => 1]);
                $langQ->select('key');
                foreach ($this->modx->getCollection(\localizator3\localizatorLanguage::class, $langQ) as $l) {
                    $langKeys[] = $l->get('key');
                }
                $hasLangInPath = in_array($firstSegment, $langKeys, true);
                if (!$hasLangInPath) {
                    // Explicit `?language=xx` beats the HttpOnly cookie and Accept-Language.
                    $preferredKey = $this->getRequestLanguageKey() ?: ($_COOKIE['localizator3_key'] ?? null);
                    if (!$preferredKey && !empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
                        $preferredKey = localizator_detect_language_from_accept($_SERVER['HTTP_ACCEPT_LANGUAGE'], $langKeys);
                    }
                    if ($preferredKey && in_array($preferredKey, $langKeys, true)) {
                        $targetLang = $this->modx->getObject(\localizator3\localizatorLanguage::class, [
                            'key' => $preferredKey,
                            'active' => 1,
                        ]);
                        if ($targetLang) {
                            $siteUrl = preg_match('#^https?://#i', $targetLang->http_host)
                                ? $targetLang->http_host
                                : $this->resolveUrlScheme() . $targetLang->http_host;
                            $siteUrl = rtrim($siteUrl, '/') . '/';
                            $path = $request ? ltrim($request, '/') : '';
                            $redirectUrl = $siteUrl . $path;
                            $this->modx->sendRedirect($redirectUrl, ['responseCode' => 'HTTP/1.1 302 Found']);
                            return false;
                        }
                    }
                }
            }

            $specific = rtrim($find, '/');
            $root = rtrim($host, '/');
            $rankedHosts = array_values(array_unique(array(
                $find,
                $specific,
                $specific . '/',
                $root . '/',
                $root,
            )));
            $q = $this->modx->newQuery(\localizator3\localizatorLanguage::class);
            $q->where(array(
                'http_host:IN' => $rankedHosts,
            ));
            $byHost = array();
            foreach ($this->modx->getCollection(\localizator3\localizatorLanguage::class, $q) as $candidate) {
                $byHost[(string)$candidate->get('http_host')] = $candidate;
            }
            foreach ($rankedHosts as $candidateHost) {
                if (isset($byHost[$candidateHost])) {
                    $language = $byHost[$candidateHost];
                    break;
                }
            }
        }

        if ($language) {
            $this->applyLanguage($language, $http_host, $request, true);
        }

        $this->invokeEvent('OnFindLocalization', array(
            'language' => $language,
            'http_host' => $http_host,
            'request' => $request,
        ));

        return false;
    }

    /**
     * Apply language options: localizator3_key, cultureKey, site_url, base_url.
     *
     * @param \localizator3\localizatorLanguage $language
     * @param string $http_host
     * @param string $request
     * @param bool $setCookie
     * @return bool
     */
    public function applyLanguage($language, $http_host = '', $request = '', $setCookie = true)
    {
        if (!$language) {
            return false;
        }

        if (preg_match("/^(http(s):\/\/)/i", $language->http_host)) {
            $site_url = $language->http_host;
        } else {
            $site_url = $this->resolveUrlScheme() . $language->http_host;
        }

        if (substr($site_url, -1) != '/') {
            $site_url .= '/';
        }

        $base_url = '/';
        $parse_url = parse_url($site_url);
        if (isset($parse_url['path'])) {
            $base_url = $parse_url['path'];
            if (substr($base_url, -1) != '/') {
                $base_url .= '/';
            }
        }

        $this->modx->localizator3_key = $language->key;
        $this->modx->setOption('localizator3_key', $this->modx->localizator3_key);
        $this->modx->setOption('cache_resource_key', 'resource/' . $this->modx->localizator3_key);

        $this->modx->cultureKey = $cultureKey = ($language->cultureKey ?: $language->key);
        $this->modx->setOption('cultureKey', $cultureKey);
        $this->modx->setOption('site_url', $site_url);
        $this->modx->setOption('base_url', $base_url);

        $this->modx->setPlaceholders(array(
            'localizator3_key' => $language->key,
            'cultureKey' => $cultureKey,
            'site_url' => $site_url,
            'base_url' => $base_url,
        ), '+');

        $this->modx->lexicon->load($cultureKey . ':localizator3:site');

        if ($setCookie) {
            setcookie('localizator3_key', $language->key, time() + 31536000, '/', '', $this->isSecureRequest(), true);
        }

        $this->modx->invokeEvent('OnToggleLocalizatorLanguage', array(
            'language' => $language,
            'language_key' => $language->key,
            'http_host' => $http_host,
            'request' => $request,
        ));

        return true;
    }

    /**
     * Scheme for a language HTTP HOST that has no protocol.
     * MODX_URL_SCHEME ignores X-Forwarded-Proto, so TLS terminated at a proxy stays http.
     *
     * @return string
     */
    public function resolveUrlScheme()
    {
        if ($this->isSecureRequest()) {
            return 'https://';
        }

        if (defined('MODX_URL_SCHEME') && MODX_URL_SCHEME !== '') {
            return MODX_URL_SCHEME;
        }

        return 'http://';
    }

    /**
     * Публичная ссылка может быть только http или https.
     * Адрес без схемы (относительный путь или тег MODX) остаётся.
     *
     * @param string $url
     * @return bool
     */
    public function isPublicHttpUrl($url)
    {
        $scheme = parse_url((string)$url, PHP_URL_SCHEME);
        if ($scheme === null) {
            return true;
        }

        return is_string($scheme) && in_array(strtolower($scheme), array('http', 'https'), true);
    }

    /**
     * Request reached PHP over HTTPS, including a proxy that sets X-Forwarded-Proto or X-Forwarded-Ssl.
     *
     * @return bool
     */
    public function isSecureRequest()
    {
        $https = strtolower((string) ($_SERVER['HTTPS'] ?? ''));
        if ($https !== '' && $https !== 'off') {
            return true;
        }

        if ((int) ($_SERVER['SERVER_PORT'] ?? 0) === 443) {
            return true;
        }

        $forwarded = strtolower(trim(explode(',', (string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''))[0]));
        if ($forwarded === 'https') {
            return true;
        }

        $ssl = strtolower(trim((string) ($_SERVER['HTTP_X_FORWARDED_SSL'] ?? '')));

        return $ssl === 'on';
    }

    /**
     * Sync cultureKey / localizator3_key from a language key (cookie, request param, …).
     *
     * @param string $key
     * @param bool $setCookie
     * @return bool
     */
    public function applyLanguageFromKey($key, $setCookie = false)
    {
        $key = trim((string) $key);
        if ($key === '') {
            return false;
        }

        $language = $this->modx->getObject(\localizator3\localizatorLanguage::class, [
            'key' => $key,
            'active' => 1,
        ]);
        if (!$language) {
            return false;
        }

        $cultureKey = (string) ($language->cultureKey ?: $language->key);
        $currentKey = (string) $this->modx->getOption('localizator3_key', null, '', true);
        $currentCulture = (string) $this->modx->getOption('cultureKey', null, '', true);
        if ($currentKey === $key && $currentCulture === $cultureKey) {
            return true;
        }

        return $this->applyLanguage($language, (string) ($_SERVER['HTTP_HOST'] ?? ''), '', $setCookie);
    }

    /**
     * Sync cultureKey from cookie localizator3_key (connectors / AJAX without language path).
     *
     * @param string|null $cookieKey
     * @return bool
     */
    public function applyLanguageFromCookie($cookieKey = null)
    {
        if ($cookieKey === null) {
            $cookieKey = isset($_COOKIE['localizator3_key']) ? trim((string) $_COOKIE['localizator3_key']) : '';
        }

        return $this->applyLanguageFromKey($cookieKey, false);
    }

    /**
     * Explicit `language` request param (GET/POST). Highest priority: a switcher
     * or a demo link `?language=ru` must beat the HttpOnly cookie localizator3_key.
     *
     * @return string '' when the param is missing / not an active language key
     */
    public function getRequestLanguageKey()
    {
        $param = trim((string) $this->modx->getOption('localizator3_request_language_param', null, 'language', true));
        if ($param === '') {
            return '';
        }

        $key = isset($_REQUEST[$param]) && is_string($_REQUEST[$param]) ? trim($_REQUEST[$param]) : '';
        if ($key === '' || !preg_match('/^[a-z]{2}(-[a-z0-9]{2,8})?$/i', $key)) {
            return '';
        }

        $exists = (bool) $this->modx->getCount(\localizator3\localizatorLanguage::class, [
            'key' => $key,
            'active' => 1,
        ]);

        return $exists ? $key : '';
    }

    /**
     * Resolve language for connector / AJAX: request param → Referer path → HTTP host → cookie.
     *
     * Full page requests still use OnHandleRequest → findLocalization(URL).
     * Package connectors often never send X-Requested-With and have empty `q`,
     * so cultureKey stayed at the system default (e.g. ru) while the UI was en.
     *
     * @return bool
     */
    public function resolveConnectorLanguage()
    {
        // Explicit request param beats Referer / cookie (HttpOnly cookie must not
        // override an intentional `language=xx` in the connector payload).
        $requestKey = $this->getRequestLanguageKey();
        if ($requestKey !== '' && $this->applyLanguageFromKey($requestKey, false)) {
            return true;
        }

        $host = (string) ($_SERVER['HTTP_HOST'] ?? '');
        $request = '';

        if (!empty($_SERVER['HTTP_REFERER'])) {
            $referer = parse_url((string) $_SERVER['HTTP_REFERER']);
            $refPath = (string) ($referer['path'] ?? '');
            if ($refPath === '' || stripos($refPath, MODX_MANAGER_URL) !== 0) {
                $host = (string) ($referer['host'] ?? $host);
                $request = ltrim($refPath, '/');
            }
        }

        if ($request !== '') {
            $this->findLocalization($host, $request);
            return (string) $this->modx->getOption('localizator3_key', null, '') !== '';
        }

        if ($host !== '') {
            $this->findLocalization($host, '');
        }

        // Prefer cookie when URL has no language segment (typical for /assets/.../connector.php).
        return $this->applyLanguageFromCookie();
    }


    public function findResource($request)
    {
        $resourceId = false;

        $this->invokeEvent('OnFindLocalizatorResource', array(
            'resource' => &$resourceId,
            'request' => $request,
        ));

        if (!$resourceId) {
            $resourceId = $this->modx->findResource($request);
        }

        return $resourceId;
    }


    /**
     * @return string
     */
    /**
     * Cached value for default language key fallback.
     * @var string|null
     */
    protected $defaultLanguageKeyCache = null;

    /**
     * Нижний регистр ключа или cultureKey => сохранённый key.
     * @var array<string, string>|null
     */
    protected $languageKeyMap = null;

    public function getDefaultLanguageKey()
    {
        // Return cached value if we already computed it.
        if ($this->defaultLanguageKeyCache !== null) {
            return $this->defaultLanguageKeyCache;
        }

        $defaultKey = (string)$this->modx->getOption('localizator3_default_language', null, '', true);
        if ($defaultKey !== '') {
            $this->defaultLanguageKeyCache = $defaultKey;
            return $defaultKey;
        }

        // Fallback: первый активный язык по rank/id
        $q = $this->modx->newQuery(\localizator3\localizatorLanguage::class);
        $q->where(['active' => 1]);
        $q->sortby($this->modx->escape('rank'), 'ASC');
        $q->sortby('id', 'ASC');
        $q->select('key');
        if ($q->prepare() && $q->stmt->execute()) {
            $key = $q->stmt->fetchColumn();
            if ($key !== false && $key !== '') {
                $this->defaultLanguageKeyCache = (string)$key;
                return $this->defaultLanguageKeyCache;
            }
        }

        $this->defaultLanguageKeyCache = '';
        return '';
    }

    /**
     * @return bool
     */
    public function isDefaultFromResource()
    {
        return (bool)$this->modx->getOption('localizator3_default_from_resource', null, false, true);
    }

    /**
     * @param string|null $key
     * @return bool
     */
    public function shouldUseResourceFields($key = null)
    {
        if (!$this->isDefaultFromResource()) {
            return false;
        }
        $defaultKey = $this->getDefaultLanguageKey();
        if ($defaultKey === '') {
            return false;
        }
        if ($key === null || $key === '') {
            $key = (string)$this->modx->getOption('localizator3_key', null, '', true);
        }
        return $this->resolveLanguageKey($key) === $this->resolveLanguageKey($defaultKey);
    }

    /**
     * @param \MODX\Revolution\modResource $resource
     * @param string $field
     * @return string
     */
    public function getResourceFieldValue($resource, $field)
    {
        if (!$resource || $field === '') {
            return '';
        }

        $contentFields = array_diff(
            array_keys($this->modx->getFields(\localizator3\localizatorContent::class)),
            array('id', 'resource_id')
        );
        $resourceFields = array_keys($this->modx->getFields(\MODX\Revolution\modResource::class));

        if (in_array($field, $contentFields, true) || in_array($field, $resourceFields, true)) {
            $value = $resource->get($field);
            return $value !== null && $value !== '' ? (string)$value : '';
        }

        $tv = $this->modx->getObject(\MODX\Revolution\modTemplateVar::class, array('name' => $field));
        if ($tv) {
            $value = $resource->getTVValue($field);
            if ($value !== null && $value !== '') {
                return (string)\localizator3\localizatorContent::renderTVOutput(
                    $this->modx,
                    $tv,
                    $value,
                    (int)$resource->get('id')
                );
            }
        }

        return '';
    }

    /**
     * @param \MODX\Revolution\modResource $resource
     * @param string $field
     * @param string|null $key
     * @return string
     */
    public function getLocalizedFieldValue($resource, $field, $key = null)
    {
        if (!$resource || $field === '') {
            return '';
        }

        $id = (int)$resource->get('id');
        if ($key === null || $key === '') {
            $key = (string)$this->modx->getOption('localizator3_key', null, '', true);
        }
        $key = $this->resolveLanguageKey($key);

        if ($this->shouldUseResourceFields($key)) {
            return $this->getStoredResourceFieldValue($resource, $field);
        }

        $current = $this->modx->resource;
        $currentKey = $this->resolveLanguageKey((string)$this->modx->getOption('localizator3_key', null, '', true));
        if ($current && $id > 0 && (int)$current->get('id') === $id && $key !== '' && $key === $currentKey) {
            return $this->getCurrentResourceFieldValue($current, $field);
        }

        $contentFields = array_diff(
            array_keys($this->modx->getFields(\localizator3\localizatorContent::class)),
            array('id', 'resource_id')
        );
        $resourceFields = array_keys($this->modx->getFields(\MODX\Revolution\modResource::class));

        if (in_array($field, $contentFields, true) || in_array($field, $resourceFields, true)) {
            $content = $this->findLocalizedContent($id, $key);
            if ($content) {
                $value = $content->get($field);
                if ($value !== null && $value !== '') {
                    return (string)$value;
                }
            }
            return $this->fallbackLocalizedFieldValue($resource, $field, $key);
        }

        $tv = $this->modx->getObject(\MODX\Revolution\modTemplateVar::class, array('name' => $field));
        if ($tv) {
            if ($tv->get('localizator3_enabled') && $this->findLocalizedContent($id, $key)) {
                $q = $this->modx->newQuery(\localizator3\locTemplateVarResource::class);
                $q->where(array(
                    'contentid' => $id,
                    'key' => $key,
                    'tmplvarid' => $tv->get('id'),
                ));
                $q->select('value');
                if ($q->prepare() && $q->stmt->execute()) {
                    $value = $q->stmt->fetchColumn();
                    if ($value !== false && $value !== null && $value !== '') {
                        return (string)\localizator3\localizatorContent::renderTVOutput(
                            $this->modx,
                            $tv,
                            $value,
                            $id
                        );
                    }
                }
            }
            return $this->fallbackLocalizedFieldValue($resource, $field, $key);
        }

        return '';
    }

    /**
     * EN и en, а также cultureKey, приводятся к сохранённому ключу языка.
     * Неизвестный ключ остаётся как есть.
     *
     * @param string $key
     * @return string
     */
    public function resolveLanguageKey($key)
    {
        $key = trim((string)$key);
        if ($key === '') {
            return '';
        }
        $map = $this->getLanguageKeyMap();
        $lookup = strtolower($key);
        if (isset($map[$lookup])) {
            return $map[$lookup];
        }
        return $key;
    }

    /**
     * @return array<string, string>
     */
    protected function getLanguageKeyMap()
    {
        if ($this->languageKeyMap !== null) {
            return $this->languageKeyMap;
        }
        $this->languageKeyMap = array();
        $q = $this->modx->newQuery(\localizator3\localizatorLanguage::class);
        $q->select(array('key', 'cultureKey'));
        $rows = array();
        if ($q->prepare() && $q->stmt->execute()) {
            while ($row = $q->stmt->fetch(\PDO::FETCH_ASSOC)) {
                $normalized = array();
                foreach ($row as $column => $value) {
                    $normalized[strtolower((string)$column)] = (string)$value;
                }
                $stored = isset($normalized['key']) ? $normalized['key'] : '';
                if ($stored === '') {
                    continue;
                }
                $rows[] = array(
                    'key' => $stored,
                    'culture' => isset($normalized['culturekey']) ? $normalized['culturekey'] : '',
                );
                $this->languageKeyMap[strtolower($stored)] = $stored;
            }
        }
        foreach ($rows as $row) {
            if ($row['culture'] === '') {
                continue;
            }
            $lookup = strtolower($row['culture']);
            if (isset($this->languageKeyMap[$lookup]) && $this->languageKeyMap[$lookup] !== $row['key']) {
                if ($this->modx->getOption('localizator3_debug_log', null, false, true)) {
                    $this->modx->log(\modX::LOG_LEVEL_WARN, '[localizator3] cultureKey "' . $row['culture'] . '" уже указывает на "' . $this->languageKeyMap[$lookup] . '", язык "' . $row['key'] . '" не записан');
                }
                continue;
            }
            $this->languageKeyMap[$lookup] = $row['key'];
        }
        return $this->languageKeyMap;
    }

    /**
     * Поле текущего ресурса после OnLoadWebDocument.
     * Пустая строка — уже результат перевода, её нечем заменять.
     *
     * @param \MODX\Revolution\modResource $resource
     * @param string $field
     * @return string
     */
    protected function getCurrentResourceFieldValue($resource, $field)
    {
        if ($field === 'content') {
            $value = $resource->get('localizator3_content');
            return $value !== null ? (string)$value : '';
        }
        $value = $resource->get($field);
        return $value !== null ? (string)$value : '';
    }

    /**
     * Опубликованная строка перевода: key или cultureKey, active = 1.
     *
     * @param int $resourceId
     * @param string $key
     * @return \localizator3\localizatorContent|null
     */
    protected function findLocalizedContent($resourceId, $key)
    {
        $q = $this->modx->newQuery(\localizator3\localizatorContent::class);
        $q->leftJoin(
            \localizator3\localizatorLanguage::class,
            'localizatorLanguage',
            'localizatorLanguage.key = localizatorContent.key'
        );
        $q->where(array(
            'localizatorContent.resource_id' => (int)$resourceId,
            'localizatorContent.active' => 1,
        ));
        $q->where(array(
            'localizatorLanguage.key' => $key,
            'OR:localizatorLanguage.cultureKey:=' => $key,
        ));
        return $this->modx->getObject(\localizator3\localizatorContent::class, $q);
    }

    /**
     * Запасное значение, если ячейка перевода пустая.
     * При чтении базового языка из полей ресурса документ — это другой язык,
     * поэтому для остальных ключей запасной путь не используется.
     *
     * @param \MODX\Revolution\modResource $resource
     * @param string $field
     * @param string $key
     * @return string
     */
    protected function fallbackLocalizedFieldValue($resource, $field, $key)
    {
        if ($this->isDefaultFromResource() && !$this->shouldUseResourceFields($key)) {
            return '';
        }

        return $this->getStoredResourceFieldValue($resource, $field);
    }

    /**
     * Поле документа из базы, минуя объект ресурса в памяти.
     * OnLoadWebDocument может уже подменить поля текущего ресурса.
     *
     * @param \MODX\Revolution\modResource $resource
     * @param string $field
     * @return string
     */
    protected function getStoredResourceFieldValue($resource, $field)
    {
        $resourceFields = array_keys($this->modx->getFields(\MODX\Revolution\modResource::class));
        $id = (int)$resource->get('id');
        if ($id > 0 && in_array($field, $resourceFields, true)) {
            $q = $this->modx->newQuery(\MODX\Revolution\modResource::class);
            $q->where(array('id' => $id));
            $q->select($this->modx->escape($field));
            if ($q->prepare() && $q->stmt->execute()) {
                $value = $q->stmt->fetchColumn();
                if ($value !== false && $value !== null && $value !== '') {
                    return (string)$value;
                }
            }
            return '';
        }

        return $this->getResourceFieldValue($resource, $field);
    }

    /**
     * @param int $resourceId
     * @return \localizator3\localizatorContent|null
     */
    public function buildDefaultLanguageSource($resourceId)
    {
        $defaultKey = $this->getDefaultLanguageKey();
        if ($defaultKey === '' || !$resourceId) {
            return null;
        }

        $resource = $this->modx->getObject(\MODX\Revolution\modResource::class, (int)$resourceId);
        if (!$resource) {
            return null;
        }

        /** @var \localizator3\localizatorContent $content */
        $content = $this->modx->newObject(\localizator3\localizatorContent::class);
        $content->set('resource_id', (int)$resourceId);
        $content->set('key', $defaultKey);
        $content->set('active', 1);
        $content->addOne($resource, 'Resource');

        if (!$content->hydrateFromResource($resource)) {
            return null;
        }

        return $content;
    }


    /**
     * Возвращает локализованный caption опции msOption для текущего языка.
     *
     * @param int $option_id ID опции (msOption)
     * @param string|null $default Значение по умолчанию, если перевод не найден
     * @return string
     */
    public function getLocalizedOptionCaption($option_id, $default = null)
    {
        if ($this->shouldUseResourceFields()) {
            return $default !== null ? (string)$default : '';
        }
        $key = $this->modx->getOption('localizator3_key', null, '', true);
        if (empty($key) || empty($option_id)) {
            return $default !== null ? (string)$default : '';
        }
        $loc = $this->modx->getObject(\localizator3\locOption::class, array(
            'option_id' => (int)$option_id,
            'key' => $key,
        ));
        return $loc && $loc->get('caption') !== '' ? $loc->get('caption') : ($default !== null ? (string)$default : '');
    }

    /**
     * Возвращает локализованный description опции msOption для текущего языка.
     *
     * @param int $option_id ID опции (msOption)
     * @param string|null $default Значение по умолчанию, если перевод не найден
     * @return string
     */
    public function getLocalizedOptionDescription($option_id, $default = null)
    {
        if ($this->shouldUseResourceFields()) {
            return $default !== null ? (string)$default : '';
        }
        $key = $this->modx->getOption('localizator3_key', null, '', true);
        if (empty($key) || empty($option_id)) {
            return $default !== null ? (string)$default : '';
        }
        $loc = $this->modx->getObject(\localizator3\locOption::class, array(
            'option_id' => (int)$option_id,
            'key' => $key,
        ));
        return $loc && $loc->get('description') !== null && $loc->get('description') !== '' ? $loc->get('description') : ($default !== null ? (string)$default : '');
    }

    /**
     * Возвращает локализованное value msProductOption для текущего языка.
     *
     * @param int $product_option_id ID записи msProductOption
     * @param string|null $default Значение по умолчанию, если перевод не найден
     * @return string
     */
    public function getLocalizedProductOptionValue($product_option_id, $default = null)
    {
        if ($this->shouldUseResourceFields()) {
            return $default !== null ? (string)$default : '';
        }
        $key = $this->modx->getOption('localizator3_key', null, '', true);
        if (empty($key) || empty($product_option_id)) {
            return $default !== null ? (string)$default : '';
        }
        $loc = $this->modx->getObject(\localizator3\locProductOption::class, array(
            'product_option_id' => (int)$product_option_id,
            'key' => $key,
        ));
        return $loc && $loc->get('value') !== null && $loc->get('value') !== '' ? $loc->get('value') : ($default !== null ? (string)$default : '');
    }

    /**
     * Shorthand for original modX::invokeEvent() method with some useful additions.
     *
     * @param $eventName
     * @param array $params
     * @param $glue
     *
     * @return array
     */
    public function invokeEvent($eventName, array $params = array(), $glue = '<br/>')
    {
        if (isset($this->modx->event->returnedValues)) {
            $this->modx->event->returnedValues = null;
        }

        $response = $this->modx->invokeEvent($eventName, $params);
        if (is_array($response) && count($response) > 1) {
            foreach ($response as $k => $v) {
                if (empty($v)) {
                    unset($response[$k]);
                }
            }
        }

        $message = is_array($response) ? implode($glue, $response) : trim((string)$response);
        if (isset($this->modx->event->returnedValues) && is_array($this->modx->event->returnedValues)) {
            $params = array_merge($params, $this->modx->event->returnedValues);
        }

        return array(
            'success' => empty($message),
            'message' => $message,
            'data' => $params,
        );
    }

    /**
     * JOIN и SELECT для выборки pdoTools.
     * Пустые массивы, если поля документа читаются как есть.
     *
     * @param string $class
     * @param string $key
     * @param bool $onlyWithLocalization
     * @return array{leftJoin: array, innerJoin: array, select: array}
     */
    public function getListingQuery($class, $key, $onlyWithLocalization = false)
    {
        $empty = array(
            'leftJoin' => array(),
            'innerJoin' => array(),
            'select' => array(),
        );
        $key = (string)$key;
        $class = (string)$class;
        if ($key === '' || $class === '' || $this->shouldUseResourceFields($key)) {
            return $empty;
        }

        $resourceFields = $this->modx->getFields($class);
        $contentFields = $this->modx->getFields(\localizator3\localizatorContent::class);
        if (!is_array($resourceFields) || !is_array($contentFields)) {
            return $empty;
        }

        $overlay = array_values(array_intersect(
            array('pagetitle', 'longtitle', 'menutitle', 'description', 'introtext', 'content'),
            array_keys($resourceFields),
            array_keys($contentFields)
        ));
        if ($overlay === array()) {
            return $empty;
        }

        $alias = $this->modx->getAlias($class);
        $on = '`localizator`.`resource_id` = `' . $alias . '`.`id`'
            . ' AND `localizator`.`key` = ' . $this->modx->quote($key)
            . ' AND `localizator`.`active` = 1';
        $join = array(
            'class' => \localizator3\localizatorContent::class,
            'alias' => 'localizator',
            'on' => $on,
        );
        $query = $empty;
        if ($onlyWithLocalization) {
            $query['innerJoin'] = array('localizator' => $join);
        } else {
            $query['leftJoin'] = array('localizator' => $join);
        }

        $keepEmptyTranslation = $this->isDefaultFromResource();
        $columns = array('`' . $alias . '`.*');
        foreach ($overlay as $field) {
            // CASE без скобок: pdoTools режет select по запятым и по первой паре скобок.
            if ($field === 'menutitle') {
                $columns[] = 'CASE WHEN `localizator`.`menutitle` IS NULL OR `localizator`.`menutitle` = \'\''
                    . ' THEN CASE WHEN `localizator`.`pagetitle` IS NULL OR `localizator`.`pagetitle` = \'\''
                    . ' THEN `' . $alias . '`.`menutitle`'
                    . ' ELSE `localizator`.`pagetitle` END'
                    . ' ELSE `localizator`.`menutitle` END AS `menutitle`';
                continue;
            }
            if ($keepEmptyTranslation) {
                $columns[] = 'CASE WHEN `localizator`.`id` IS NULL'
                    . ' THEN `' . $alias . '`.`' . $field . '`'
                    . ' WHEN `localizator`.`' . $field . '` IS NULL OR `localizator`.`' . $field . "` = ''"
                    . ' THEN \'\''
                    . ' ELSE `localizator`.`' . $field . '` END AS `' . $field . '`';
                continue;
            }
            $columns[] = 'CASE WHEN `localizator`.`' . $field . '` IS NULL OR `localizator`.`' . $field . "` = ''"
                . ' THEN `' . $alias . '`.`' . $field . '`'
                . ' ELSE `localizator`.`' . $field . '` END AS `' . $field . '`';
        }
        $columns[] = '`' . $alias . '`.`id`';
        $query['select'] = array(
            'localizator' => implode(', ', $columns),
        );

        return $query;
    }
}
