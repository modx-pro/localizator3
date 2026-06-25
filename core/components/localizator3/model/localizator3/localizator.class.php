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

        require_once dirname(__FILE__, 3) . '/translators/' . strtolower($this->config['translator']) . '.class.php';

        $this->translator = new $this->config['translator']($this->modx, $this->config);

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
                    $preferredKey = $_COOKIE['localizator3_key'] ?? null;
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
                                : MODX_URL_SCHEME . $targetLang->http_host;
                            $siteUrl = rtrim($siteUrl, '/') . '/';
                            $path = $request ? ltrim($request, '/') : '';
                            $redirectUrl = $siteUrl . $path;
                            $this->modx->sendRedirect($redirectUrl, ['responseCode' => 'HTTP/1.1 302 Found']);
                            return false;
                        }
                    }
                }
            }

            $q = $this->modx->newQuery(\localizator3\localizatorLanguage::class);
            $q->where(array(
                array('http_host' => $find),
                array('OR:http_host:=' => $host . '/'),
                array('OR:http_host:=' => $host),
            ));
            $q->sortby("FIELD(http_host, '{$find}', '{$host}/', '{$host}')");
            $language = $this->modx->getObject(\localizator3\localizatorLanguage::class, $q);
        }

        if ($language) {
            if (preg_match("/^(http(s):\/\/)/i", $language->http_host)) {
                $site_url = $language->http_host;
            } else {
                $site_url = MODX_URL_SCHEME . $language->http_host;
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

            setcookie('localizator3_key', $language->key, time() + 31536000, '/', '', !empty($_SERVER['HTTPS']), true);

            $this->modx->invokeEvent('OnToggleLocalizatorLanguage', array(
                'language' => $language,
                'language_key' => $language->key,
                'http_host' => $http_host,
                'request' => $request,
            ));
        }

        $this->invokeEvent('OnFindLocalization', array(
            'language' => $language,
            'http_host' => $http_host,
            'request' => $request,
        ));

        return false;
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
    public function getDefaultLanguageKey()
    {
        return (string)$this->modx->getOption('localizator3_default_language', null, '', true);
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
        return $key === $defaultKey;
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

        if ($this->shouldUseResourceFields($key)) {
            return $this->getResourceFieldValue($resource, $field);
        }

        $contentFields = array_diff(
            array_keys($this->modx->getFields(\localizator3\localizatorContent::class)),
            array('id', 'resource_id')
        );
        $resourceFields = array_keys($this->modx->getFields(\MODX\Revolution\modResource::class));

        if (in_array($field, $contentFields, true)) {
            $q = $this->modx->newQuery(\localizator3\localizatorContent::class);
            $q->where(array(
                'resource_id' => $id,
                'key' => $key,
                'active' => 1,
            ));
            $q->select($field);
            if ($q->prepare() && $q->stmt->execute()) {
                $value = $q->stmt->fetchColumn();
                if ($value !== false && $value !== null && $value !== '') {
                    return (string)$value;
                }
            }
            return $this->getResourceFieldValue($resource, $field);
        }

        if (in_array($field, $resourceFields, true)) {
            $q = $this->modx->newQuery(\localizator3\localizatorContent::class);
            $q->where(array(
                'resource_id' => $id,
                'key' => $key,
                'active' => 1,
            ));
            $content = $this->modx->getObject(\localizator3\localizatorContent::class, $q);
            if ($content) {
                $value = $content->get($field);
                if ($value !== null && $value !== '') {
                    return (string)$value;
                }
            }
            return $this->getResourceFieldValue($resource, $field);
        }

        $tv = $this->modx->getObject(\MODX\Revolution\modTemplateVar::class, array('name' => $field));
        if ($tv) {
            if ($tv->get('localizator3_enabled')) {
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
            return $this->getResourceFieldValue($resource, $field);
        }

        return '';
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
}
