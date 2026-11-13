<?php

class localizatorContentTranslateProcessor extends modProcessor
{
    /* @var localizator $localizator */
    public $localizator;

    public function process()
    {

        $this->localizator = $this->modx->getService('localizator3', 'localizator', $this->modx->getOption('localizator3_core_path', null, $this->modx->getOption('core_path') . 'components/localizator3/') . 'model/localizator3/');

        if (!$resource_id = $this->getProperty('resource_id')) {
            return $this->failure('Не указан id ресурса');
        }

        if (!$default_language = $this->modx->getOption('localizator3_default_language')) {
            return $this->failure($this->modx->lexicon('localizator_item_err_default_language'));
        }

        /* @var localizatorContent $default_content */
        if ($this->localizator->isDefaultFromResource()) {
            $default_content = $this->localizator->buildDefaultLanguageSource($resource_id);
        } else {
            $default_content = $this->modx->getObject(\localizator3\localizatorContent::class, array('key' => $default_language, 'resource_id' => $resource_id));
        }
        if (!$default_content) {
            return $this->failure($this->modx->lexicon('localizator_item_err_no_line'));
        }

        $loc_permission = $this->modx->getOption('localizator3_check_permissions', null, false, true);

        $translate_translated = $this->modx->getOption('localizator3_translate_translated', null, false);
        $translate_translated_fields = $this->modx->getOption('localizator3_translate_translated_fields', null, false);
        $translate_fields = explode(',', $this->modx->getOption('localizator3_translate_fields', null, 'pagetitle,longtitle,menutitle,seotitle,keywords,introtext,description,content'));


        $processed = 0;

        $c = $this->modx->newQuery(\localizator3\localizatorLanguage::class);
        $c->limit(1000000);
        $c->where(array(
            'key:!=' => $default_language
        ));

        $keysFilter = $this->getProperty('keys');
        if ($keysFilter) {
            $keysArray = is_string($keysFilter) ? $this->modx->fromJSON($keysFilter) : $keysFilter;
            if (is_array($keysArray) && !empty($keysArray)) {
                $c->where(array('key:IN' => $keysArray));
            }
        }

        $total = $this->modx->getCount(\localizator3\localizatorLanguage::class, $c);

        $defaultTVs = $default_content->loadTVs();

        $languages = $this->modx->getIterator(\localizator3\localizatorLanguage::class, $c);
        foreach ($languages as $language) {
            if ($loc_permission && !$this->modx->hasPermission("localizatorcontent_save_" . $language->key)) {
                continue;
            }

            /* @var localizatorContent $content */
            $content = $this->modx->getObject(\localizator3\localizatorContent::class, array('key' => $language->key, 'resource_id' => $resource_id));
            if ($content && $translate_translated) {
                $contentData = $content->toArray();

                foreach ($translate_fields as $field) {
                    $current = $content->get($field);
                    $val = $default_content->get($field);
                    if (empty($val)) {
                        continue;
                    }
                    if (empty($current) || !empty($current) && $translate_translated_fields) {
                        if (isset($this->modx->map[\localizator3\localizatorContent::class]['fieldMeta'][$field])) {
                            $contentData[$field] = $this->localizator->translate($val, $default_language, ($language->cultureKey ?: $language->key));
                        } elseif (isset($defaultTVs[$field])) {
                            if ($tv = $this->modx->getObject(\MODX\Revolution\modTemplateVar::class, ['name' => $field])) {
                                $tv->set('value', $val);
                                $contentData[$field] = $this->translateTV($tv, $default_language, ($language->cultureKey ?: $language->key));
                            }
                        }
                    }
                }
                $response = $this->modx->runProcessor(
                    'mgr/content/update',
                    $contentData,
                    array(
                        'processors_path' => $this->localizator->config['processorsPath']
                    )
                );
                if ($response->isError()) {
                    return $response->getResponse();
                }
            } elseif (!$content) {
                $contentData = array(
                    'key' => $language->key,
                    'resource_id' => $resource_id,
                    'active' => 1,
                );
                foreach ($translate_fields as $field) {
                    $val = $default_content->get($field);
                    if (!empty($val)) {
                        if (isset($this->modx->map[\localizator3\localizatorContent::class]['fieldMeta'][$field])) {
                            $contentData[$field] = $this->localizator->translate($val, $default_language, ($language->cultureKey ?: $language->key));
                        } elseif (isset($defaultTVs[$field])) {
                            if ($tv = $this->modx->getObject(\MODX\Revolution\modTemplateVar::class, ['name' => $field])) {
                                $tv->set('value', $val);
                                $contentData[$field] = $this->translateTV($tv, $default_language, ($language->cultureKey ?: $language->key));
                            }
                        }
                    }
                }
                $response = $this->modx->runProcessor(
                    'mgr/content/create',
                    $contentData,
                    array(
                        'processors_path' => $this->localizator->config['processorsPath']
                    )
                );
                if ($response->isError()) {
                    return $response->getResponse();
                }
            }

            $processed++;
        }

        return $this->success('', array(
            'total' => $total,
            'processed' => $processed,
        ));
    }

    /**
     * Translate a TV value. MIGX walks formtabs recursively so nested migx
     * (inputTV or inputTVtype=migx with configs/formtabs) is translated too.
     *
     * @param \MODX\Revolution\modTemplateVar $tvvar
     * @param string $default_language
     * @param string $language
     * @return mixed
     */
    public function translateTV($tvvar, $default_language, $language)
    {
        $type = $tvvar->get('type');
        $val = $tvvar->get('value');
        if (in_array($type, array('text', 'textarea', 'richtext'), true)) {
            if ($val === null || $val === '') {
                return $val;
            }
            return $this->localizator->translate($val, $default_language, $language);
        }
        if ($type !== 'migx') {
            return $val;
        }

        $this->modx->addPackage('migx', MODX_CORE_PATH . 'components/migx/model/');

        $params = $this->normalizeMigxParams($tvvar->get('input_properties'));
        $formtabs = $this->resolveMigxFormtabs($params);
        if (!is_array($formtabs) || $formtabs === array()) {
            return $val;
        }

        if (!is_array($val)) {
            $decoded = json_decode((string)$val, true);
            if (!is_array($decoded)) {
                return $val;
            }
            $val = $decoded;
        }

        foreach ($formtabs as $tab) {
            $fields = isset($tab['fields']) ? $tab['fields'] : array();
            if (!is_array($fields)) {
                continue;
            }
            foreach ($fields as $field) {
                if (empty($field['field'])) {
                    continue;
                }
                $nestedTv = $this->buildMigxFieldTv($field);
                foreach ($val as &$row) {
                    if (!is_array($row) || !array_key_exists($field['field'], $row)) {
                        continue;
                    }
                    $nestedTv->set('value', $row[$field['field']]);
                    $row[$field['field']] = $this->translateTV($nestedTv, $default_language, $language);
                }
                unset($row);
            }
        }

        return json_encode($val);
    }

    /**
     * @param mixed $params
     * @return array
     */
    protected function normalizeMigxParams($params)
    {
        if (is_array($params)) {
            return $params;
        }
        if (is_string($params) && $params !== '') {
            $decoded = $this->modx->fromJSON($params);
            if (is_array($decoded)) {
                return $decoded;
            }
        }
        return array();
    }

    /**
     * @param array $params
     * @return array|null
     */
    protected function resolveMigxFormtabs(array $params)
    {
        $formtabs = isset($params['formtabs']) ? $params['formtabs'] : null;
        if (!empty($params['configs'])) {
            $cfg = $this->modx->getObject('migxConfig', array('name' => $params['configs']));
            if ($cfg) {
                $formtabs = $cfg->get('formtabs');
            }
        }
        if (!is_array($formtabs)) {
            $formtabs = json_decode((string)$formtabs, true);
        }
        return is_array($formtabs) ? $formtabs : null;
    }

    /**
     * Build a TV object for one MIGX form field, including configs/formtabs
     * needed for nested migx recursion.
     *
     * @param array $field
     * @return \MODX\Revolution\modTemplateVar
     */
    protected function buildMigxFieldTv(array $field)
    {
        $tv = null;
        if (!empty($field['inputTV'])) {
            $tv = $this->modx->getObject(\MODX\Revolution\modTemplateVar::class, array(
                'name' => $field['inputTV'],
            ));
        }
        // inputTVtype overrides inputTV when both are set (MIGX convention).
        if (!empty($field['inputTVtype'])) {
            $tv = $this->modx->newObject(\MODX\Revolution\modTemplateVar::class);
            $tv->set('type', $field['inputTVtype']);
        }
        if (!$tv) {
            $tv = $this->modx->newObject(\MODX\Revolution\modTemplateVar::class);
            $tv->set('type', 'text');
        }

        if (!empty($field['inputOptionValues'])) {
            $tv->set('elements', $field['inputOptionValues']);
        }

        $childParams = $this->normalizeMigxParams($tv->get('input_properties'));
        if (!empty($field['configs'])) {
            $cfg = is_array($field['configs'])
                ? $field['configs']
                : $this->modx->fromJSON($field['configs']);
            if (is_array($cfg)) {
                $childParams = array_merge($childParams, $cfg);
            } else {
                // Plain string = migxConfig name.
                $childParams['configs'] = $field['configs'];
            }
        }
        if (!empty($field['formtabs'])) {
            $childParams['formtabs'] = $field['formtabs'];
        }
        if ($tv->get('type') === 'migx') {
            $tv->set('input_properties', $childParams);
        }

        return $tv;
    }
}

return 'localizatorContentTranslateProcessor';
