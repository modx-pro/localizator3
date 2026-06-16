<?php

/**
 * Возвращает конфигурацию формы локализации для Vue-вкладки.
 * Поддерживает кастомизацию через OnBuildLocalizationTabs (user, resource, tabs).
 *
 * @package localizator3
 */
class localizatorContentGetFormConfigProcessor extends modProcessor
{
    public function process()
    {
        $debugLog = $this->modx->getOption('localizator3_debug_log', null, false) || (defined('LOCALIZATOR3_DEBUG_LOG') && LOCALIZATOR3_DEBUG_LOG);

        $this->modx->lexicon->load('localizator3:default');
        $this->modx->lexicon->load('core:resource');

        $resourceId = (int)$this->getProperty('resource_id');
        $locId = (int)$this->getProperty('loc_id');
        if ($debugLog) {
            $this->modx->log(modX::LOG_LEVEL_DEBUG, '[localizator3 getformconfig] START resource_id=' . $resourceId . ', loc_id=' . $locId);
        }

        $corePath = $this->modx->getOption('localizator3_core_path', null,
            $this->modx->getOption('core_path') . 'components/localizator3/'
        );
        $this->modx->addPackage('localizator3', $corePath . 'model/', null, 'localizator3\\');
        $localizator = $this->modx->getService(
            'localizator3',
            'localizator',
            $this->modx->getOption(
                'localizator3_core_path',
                null,
                $this->modx->getOption('core_path') . 'components/localizator3/'
            ) . 'model/localizator3/'
        );
        if (!($localizator instanceof localizator)) {
            return $this->failure('Localizator3 service not available');
        }

        if (!$resourceId) {
            return $this->failure($this->modx->lexicon('localizator_item_err_ns'));
        }

        $resource = $this->modx->getObject(\MODX\Revolution\modResource::class, $resourceId);
        if (!$resource) {
            return $this->failure($this->modx->lexicon('resource_err_nf'));
        }

        $classKey = $resource->get('class_key');
        if ($debugLog) {
            $this->modx->log(modX::LOG_LEVEL_DEBUG, '[localizator3 getformconfig] resource loaded class_key=' . $classKey);
        }
        $richtext = $resource->get('richtext');
        $localizator->working_context = $resource->get('context_key');

        $resourcefields = $this->getResourceFields($classKey, $richtext);
        try {
            $tvtabs = $this->getTvTabs($resource);
            if ($debugLog) {
                $this->modx->log(modX::LOG_LEVEL_DEBUG, '[localizator3 getformconfig] getTvTabs OK, tabs=' . count($tvtabs));
            }
        } catch (\Exception $e) {
            $tvtabs = array();
            $this->modx->log(modX::LOG_LEVEL_ERROR, '[localizator3 getformconfig] getTvTabs exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            $this->modx->log(modX::LOG_LEVEL_ERROR, '[localizator3 getformconfig] getTvTabs trace: ' . $e->getTraceAsString());
            if ($debugLog) {
                $this->modx->log(modX::LOG_LEVEL_DEBUG, '[localizator3 getformconfig] getTvTabs failed for resource_id=' . $resourceId . ', class_key=' . $classKey);
            }
        }

        $formtabs = array(
            'document' => array(
                'id' => 'document',
                'caption' => $this->modx->lexicon('document'),
                'fields' => array_values($resourcefields),
            ),
        );

        if (!empty($tvtabs)) {
            $formtabs['tvs'] = array(
                'id' => 'tvs',
                'caption' => $this->modx->lexicon('tvs'),
                'tabs' => $tvtabs,
            );
        }

        $customization = array(
            'user_id' => $this->modx->user ? (int)$this->modx->user->get('id') : 0,
            'resource_id' => $resourceId,
            'context_key' => $resource->get('context_key'),
            'class_key' => $classKey,
        );

        $response = $localizator->invokeEvent('OnBuildLocalizationTabs', array(
            'localizatorContent' => null,
            'tabs' => &$formtabs,
            'resource' => $resource,
            'user' => $this->modx->user,
            'customization' => &$customization,
        ));

        if ($response['success'] && isset($response['data']['tabs'])) {
            $formtabs = $response['data']['tabs'];
        }
        if ($response['success'] && isset($response['data']['customization'])) {
            $customization = $response['data']['customization'];
        }

        $languages = array();
        $usedKeys = array();
        if (!$locId) {
            $existing = $this->modx->getCollection(\localizator3\localizatorContent::class, ['resource_id' => $resourceId]);
            $existing = $existing ?? [];
            foreach ($existing as $loc) {
                $usedKeys[] = $loc->get('key');
            }
        }
        $fallbackUsed = false;
        $allActive = $this->fetchLanguagesFallback();
        if ($debugLog) {
            $this->modx->log(modX::LOG_LEVEL_DEBUG, '[localizator3 getformconfig] fetchLanguagesFallback returned ' . count($allActive) . ' rows');
        }
        if (empty($allActive)) {
            $q = $this->modx->newQuery(\localizator3\localizatorLanguage::class);
            $q->where(['active' => 1]);
            $q->sortby('`rank`', 'ASC');
            $q->sortby('name', 'ASC');
            $allActive = $this->modx->getCollection(\localizator3\localizatorLanguage::class, $q);
            $allActive = $allActive ?? [];
            if ($debugLog) {
                $this->modx->log(modX::LOG_LEVEL_DEBUG, '[localizator3 getformconfig] getCollection(localizatorLanguage) returned ' . count($allActive) . ' rows');
            }
        } else {
            $fallbackUsed = true;
        }
        $allActive = $allActive ?? [];
        $totalActiveLanguages = count($allActive);
        foreach ($allActive as $lang) {
            $key = is_object($lang) ? $lang->get('key') : $lang['key'];
            $name = is_object($lang) ? $lang->get('name') : $lang['name'];
            if ($locId || !in_array($key, $usedKeys)) {
                $languages[] = array(
                    'key' => $key,
                    'name' => $name,
                );
            }
        }

        if ($debugLog) {
            $totalAll = $this->modx->getCount(\localizator3\localizatorLanguage::class);
            $totalInactive = $this->modx->getCount(\localizator3\localizatorLanguage::class, ['active' => 0]);
            $this->modx->log(modX::LOG_LEVEL_INFO, sprintf(
                '[localizator3 getformconfig] resource_id=%d, loc_id=%d, totalActiveLanguages=%d, totalAllLanguages=%d, totalInactive=%d, existingCount=%d, languagesAvailable=%d, usedKeys=[%s], fallbackUsed=%s',
                $resourceId, $locId, $totalActiveLanguages, $totalAll, $totalInactive, count($usedKeys), count($languages), implode(',', $usedKeys), $fallbackUsed ? '1' : '0'
            ));
        }

        $result = array(
            'formtabs' => $formtabs,
            'customization' => $customization,
            'languages' => $languages,
            'totalActiveLanguages' => $totalActiveLanguages,
            'existingCount' => count($usedKeys),
        );
        if ($debugLog) {
            $prefix = $this->modx->getOption('table_prefix', null, null)
                ?: $this->modx->getOption(\xPDO::OPT_TABLE_PREFIX, null, 'modx_');
            if ($prefix === null || $prefix === '') {
                $prefix = 'modx_';
            }
            $result['_debug'] = array(
                'fallbackUsed' => $fallbackUsed,
                'tablePrefix' => $prefix,
                'rawLanguagesCount' => $totalActiveLanguages,
            );
        }

        if ($locId) {
            $loc = $this->modx->getObject(\localizator3\localizatorContent::class, $locId);
            if ($loc) {
                try {
                    $record = $loc->toArray();
                    foreach ($loc->getTemplateVars() as $tv) {
                        $record['tvlocalizator_' . $tv->get('name')] = $tv->get('value');
                    }
                    $result['record'] = $record;
                    if ($debugLog) {
                        $this->modx->log(modX::LOG_LEVEL_DEBUG, '[localizator3 getformconfig] record loaded for loc_id=' . $locId);
                    }
                } catch (\Exception $e) {
                    $this->modx->log(modX::LOG_LEVEL_ERROR, '[localizator3 getformconfig] getTemplateVars for record exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
                    $this->modx->log(modX::LOG_LEVEL_ERROR, '[localizator3 getformconfig] record load trace: ' . $e->getTraceAsString());
                }
            }
        }

        if ($debugLog) {
            $this->modx->log(modX::LOG_LEVEL_DEBUG, '[localizator3 getformconfig] SUCCESS languagesCount=' . count($languages));
        }
        return $this->success('', $result);
    }

    protected function getResourceFields($classKey, $richtext)
    {
        $fields = array(
            'id' => array('field' => 'id', 'type' => 'hidden', 'visible' => false),
            'key' => array(
                'field' => 'key',
                'caption' => $this->modx->lexicon('localizator_language'),
                'type' => 'listbox',
                'required' => true,
                'visible' => true,
                'optionValues' => null,
            ),
            'pagetitle' => array('field' => 'pagetitle', 'caption' => $this->modx->lexicon('resource_pagetitle'), 'type' => 'text', 'visible' => true),
            'longtitle' => array('field' => 'longtitle', 'caption' => $this->modx->lexicon('resource_longtitle'), 'type' => 'text', 'visible' => true),
            'menutitle' => array('field' => 'menutitle', 'caption' => $this->modx->lexicon('resource_menutitle'), 'type' => 'text', 'visible' => true),
            'description' => array('field' => 'description', 'caption' => $this->modx->lexicon('resource_description'), 'type' => 'textarea', 'visible' => true),
            'introtext' => array('field' => 'introtext', 'caption' => $this->modx->lexicon('introtext'), 'type' => 'textarea', 'visible' => true),
            'seotitle' => array('field' => 'seotitle', 'caption' => $this->modx->lexicon('localizator_seotitle'), 'type' => 'text', 'visible' => true),
            'keywords' => array('field' => 'keywords', 'caption' => $this->modx->lexicon('localizator_keywords'), 'type' => 'text', 'visible' => true),
        );

        $isWebLink = $classKey === 'modWebLink' || $classKey === \MODX\Revolution\modWebLink::class;
        $isSymLink = $classKey === 'modSymLink' || $classKey === \MODX\Revolution\modSymLink::class;
        $isStaticResource = $classKey === 'modStaticResource' || $classKey === \MODX\Revolution\modStaticResource::class;

        if ($isWebLink || $isSymLink) {
            $fields['content'] = array('field' => 'content', 'caption' => $this->modx->lexicon('resource_content'), 'type' => 'text', 'visible' => true);
        } elseif (!$isStaticResource) {
            $fields['content'] = array('field' => 'content', 'caption' => $this->modx->lexicon('resource_content'), 'type' => $richtext ? 'richtext' : 'textarea', 'visible' => true);
        }

        return $fields;
    }

    protected function getTvTabs($resource)
    {
        $debugLog = $this->modx->getOption('localizator3_debug_log', null, false) || (defined('LOCALIZATOR3_DEBUG_LOG') && LOCALIZATOR3_DEBUG_LOG);
        if ($debugLog) {
            $this->modx->log(modX::LOG_LEVEL_DEBUG, '[localizator3 getformconfig] getTvTabs ENTER resource_id=' . $resource->get('id') . ', class_key=' . $resource->get('class_key'));
        }

        $templateId = (int)$resource->get('template');
        if (!$templateId) {
            if ($debugLog) {
                $this->modx->log(modX::LOG_LEVEL_DEBUG, '[localizator3 getformconfig] getTvTabs: no template, skip TVs');
            }
            return array();
        }

        $c = $this->modx->newQuery(\MODX\Revolution\modTemplateVar::class);
        $c->innerJoin(\MODX\Revolution\modTemplateVarTemplate::class, 'tvtpl', array(
            'tvtpl.tmplvarid = modTemplateVar.id',
            'tvtpl.templateid' => $templateId,
        ));

        $columns = $this->modx->getFields(\MODX\Revolution\modTemplateVar::class);
        if (isset($columns['localizator3_enabled'])) {
            $c->where(array('modTemplateVar.localizator3_enabled' => 1));
        }

        $c->sortby('tvtpl.rank', 'ASC');
        $c->sortby('modTemplateVar.rank', 'ASC');

        $tvs = $this->modx->getCollection(\MODX\Revolution\modTemplateVar::class, $c);
        if ($debugLog) {
            $this->modx->log(modX::LOG_LEVEL_DEBUG, '[localizator3 getformconfig] getTvTabs query returned ' . count($tvs) . ' TVs');
        }

        $tvtabs = array();
        foreach ($tvs as $tv) {
            if (!$tv->checkResourceGroupAccess()) {
                continue;
            }
            $catId = $tv->get('category') ?: 0;
            if (!isset($tvtabs[$catId])) {
                $cat = $this->modx->getObject(\MODX\Revolution\modCategory::class, $catId);
                $tvtabs[$catId] = array(
                    'id' => 'tv_' . $catId,
                    'caption' => $cat ? $cat->get('category') : $this->modx->lexicon('uncategorized'),
                    'fields' => array(),
                );
            }
            $tvtabs[$catId]['fields'][] = array(
                'field' => $tv->get('name'),
                'caption' => $tv->get('caption') ?: $tv->get('name'),
                'description' => $tv->get('description'),
                'type' => 'tv',
                'inputTV' => $tv->get('name'),
                'visible' => true,
            );
        }

        return array_values($tvtabs);
    }

    /**
     * Fallback: прямой SQL-запрос, если getCollection возвращает пусто (проблемы с загрузкой пакета).
     *
     * @return array Массив записей [['key' => ..., 'name' => ...], ...]
     */
    /**
     * Fallback: прямой SQL-запрос (обход проблем с загрузкой пакета xPDO).
     *
     * @return array Массив записей [['key' => ..., 'name' => ...], ...]
     */
    protected function fetchLanguagesFallback()
    {
        $debugLog = $this->modx->getOption('localizator3_debug_log', null, false) || (defined('LOCALIZATOR3_DEBUG_LOG') && LOCALIZATOR3_DEBUG_LOG);
        $prefix = $this->modx->getOption('table_prefix', null, null)
            ?: $this->modx->getOption(\xPDO::OPT_TABLE_PREFIX, null, 'modx_');
        if ($prefix === null || $prefix === '') {
            $prefix = 'modx_';
        }
        $table = '`' . $prefix . 'localizator3_languages`';
        $sql = "SELECT `key`, name FROM {$table} WHERE active = 1 ORDER BY `rank`, name";
        if ($debugLog) {
            $this->modx->log(modX::LOG_LEVEL_DEBUG, '[localizator3 getformconfig] fetchLanguagesFallback table=' . $table . ', prefix=' . $prefix);
        }
        $stmt = $this->modx->query($sql);
        if (!$stmt) {
            if ($debugLog) {
                $this->modx->log(modX::LOG_LEVEL_WARN, '[localizator3 getformconfig] fetchLanguagesFallback query failed, sql=' . $sql);
            }
            return array();
        }
        $rows = array();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $rows[] = $row;
        }
        if ($debugLog) {
            $this->modx->log(modX::LOG_LEVEL_DEBUG, '[localizator3 getformconfig] fetchLanguagesFallback rawLanguagesCount=' . count($rows));
        }
        return $rows;
    }
}

return 'localizatorContentGetFormConfigProcessor';
