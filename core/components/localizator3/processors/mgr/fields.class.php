<?php

/**
 * Loads the Tabs panel for Localizator.
 *
 * Note: This page is not to be accessed directly.
 *
 * @package localizator
 * @subpackage processors
 */

class localizatorFormProcessor extends modProcessor
{
    public function process()
    {

        require_once $this->modx->getOption('localizator3_core_path', null, $this->modx->getOption('core_path') . 'components/localizator3/') . 'model/localizator3/localizatorformcontroller.class.php';
        $controller = new LocalizatorFormController($this->modx);
        $this->modx->controller = &$controller;

        $this->modx->getService('smarty', 'smarty.modSmarty');
        $localizator = $this->modx->getService('localizator3', 'localizator', $this->modx->getOption('localizator3_core_path', null, $this->modx->getOption('core_path') . 'components/localizator3/') . 'model/localizator3/');
        $scriptProperties = $this->getProperties();

        $localizator->working_context = 'web';

        $which_editor = $this->modx->getOption('which_editor', null, false, true);


        $class_key = 'modDocument';
        $richtext = false;
        if ($this->modx->resource = $this->modx->getObject(\MODX\Revolution\modResource::class, $scriptProperties['resource_id'])) {
            $localizator->working_context = $this->modx->resource->get('context_key');
            $class_key = $this->modx->resource->get('class_key');

            if ($which_editor != false) {
                $richtext = $this->modx->resource->get('richtext');
            }
        }

        $controller->loadTemplatesPath();

        $controller->setPlaceholder('_config', $this->modx->config);
        $this->modx->lexicon->load('core:resource');
        $this->modx->lexicon->load('core:default');
        $this->modx->lexicon->load('core:formcustomization');

        /*actual record */
        if ($loc = $this->modx->getObject(\localizator3\localizatorContent::class, $scriptProperties['loc_id'])) {
            $scriptProperties['isnew'] = 0;
        } else {
            $loc = $this->modx->newObject(\localizator3\localizatorContent::class);
            $loc->set('resource_id', $scriptProperties['resource_id']);
            $scriptProperties['isnew'] = 1;
        }

        $allfields = array();

        $resourcefields = array(
            'id' => array(
                'inputTVtype' => 'hidden',
            ),
            'key' => array(
                'caption' => $this->modx->lexicon('localizator_language'),
                'inputTVtype' => 'listbox',
                'inputOptionValues' => '@SELECT `name`,`key` FROM `[[+PREFIX]]localizator3_languages` WHERE `active` = 1',
            ),
            'pagetitle' => array(),
            'longtitle' => array(),
            'menutitle' => array(),
            'description' => array(
                'inputTVtype' => 'textarea',
            ),
            'introtext' => array(
                'inputTVtype' => 'textarea',
                'caption' => $this->modx->lexicon('introtext'),
            ),
            'seotitle' => array(
                'caption' => $this->modx->lexicon('localizator_seotitle'),
            ),
            'keywords' => array(
                'caption' => $this->modx->lexicon('localizator_keywords'),
            ),
        );
        $isWebLink = $class_key === 'modWebLink' || $class_key === \MODX\Revolution\modWebLink::class;
        $isSymLink = $class_key === 'modSymLink' || $class_key === \MODX\Revolution\modSymLink::class;
        $isStaticResource = $class_key === 'modStaticResource' || $class_key === \MODX\Revolution\modStaticResource::class;

        if ($isWebLink || $isSymLink) {
            $resourcefields['content'] = array(
                'inputTVtype' => 'number',
                'caption' => $this->modx->lexicon('resource_content'),
            );
        } elseif (!$isStaticResource) {
            $resourcefields['content'] = array(
                'inputTVtype' => $richtext ? 'richtext' : 'textarea',
            );
        }

        foreach ($resourcefields as $key => &$values) {
            $values = array_merge(array(
                'field' => $key,
                'caption' => $this->modx->lexicon("resource_{$key}"),
                'inputTVtype' => 'text',
            ), $values);
        }

        $tvtabs = array();

        $templateId = $this->modx->resource ? (int)$this->modx->resource->get('template') : 0;
        if ($templateId) {
            /* get categories */
            $cc = $this->modx->newQuery(\MODX\Revolution\modCategory::class);
            $cc->sortby('rank', 'ASC');
            $cc->sortby('category', 'ASC');
            $cats = $this->modx->getCollection(\MODX\Revolution\modCategory::class, $cc);
            foreach ($cats as $cat) {
                $tvtabs[$cat->get('id')] = array(
                    'caption' => $cat->get('category'),
                    'fields' => array(),
                );
            }

            $tvtabs[0] = array(
                'caption' => ucfirst($this->modx->lexicon('uncategorized')),
                'fields' => array(),
            );

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

            foreach ($this->modx->getCollection(\MODX\Revolution\modTemplateVar::class, $c) as $tv) {
                if (!$tv->checkResourceGroupAccess()) {
                    continue;
                }
                $catId = $tv->get('category') ?: 0;
                if (!isset($tvtabs[$catId])) {
                    $tvtabs[$catId] = array(
                        'caption' => $this->modx->lexicon('uncategorized'),
                        'fields' => array(),
                    );
                }
                $tvtabs[$catId]['fields'][] = array(
                    'field' => $tv->get('name'),
                    'caption' => $tv->get('caption') ? $tv->get('caption') : $tv->get('name'),
                    'description' => $tv->get('description'),
                    'inputTV' => $tv->get('name'),
                );
            }

            $tvtabs = array_filter($tvtabs, function ($var) {
                return (count($var['fields']) > 0);
            });
        }

        $formtabs = array(
            'document' => array(
                'caption' => $this->modx->lexicon('document'),
                'tabs' => array(
                    'document' => array(
                        'caption' => $this->modx->lexicon('document'),
                        'fields' => array_values($resourcefields),
                    ),
                ),
            ),
        );

        if (!empty($tvtabs)) {
            $formtabs['tvs'] = array(
                'caption' => $this->modx->lexicon('tvs'),
                'tabs' => $tvtabs,
            );
        }

        $response = $localizator->invokeEvent('OnBuildLocalizationTabs', array(
            'localizatorContent' => &$loc,
            'tabs' => $formtabs,
            'resource' => $this->modx->resource,
            'user' => $this->modx->user,
            'customization' => array(
                'user_id' => $this->modx->user ? (int)$this->modx->user->get('id') : 0,
                'resource_id' => (int)$scriptProperties['resource_id'],
            ),
        ));
        if ($response['success']) {
            $formtabs = $response['data']['tabs'] ?? $formtabs;
        }
        $record = $loc->toArray();

        $categories = array();
        $result = $localizator->createForm($formtabs, $record, $allfields, $categories, $scriptProperties);

        if (isset($result['error'])) {
            $controller->setPlaceholder('error', $result['error']);
        }

        //$controller->setPlaceholder('formcaption', '');
        $controller->setPlaceholder('fields', $this->modx->toJSON($allfields));
        $controller->setPlaceholder('categories', $categories);
        $controller->setPlaceholder('resource_id', $loc->get('resource_id'));
        $controller->setPlaceholder('formAction', $scriptProperties['isnew'] ? 'create' : 'update');
        $controller->setPlaceholder('properties', $scriptProperties);

        $controller->setPlaceholder('win_id', $scriptProperties['win_id']);
        $controller->setPlaceholder('tvcount', count($resourcefields));

        if (!empty($_REQUEST['showCheckbox'])) {
            $controller->setPlaceholder('showCheckbox', 1);
        } else {
            $controller->setPlaceholder('showCheckbox', 0);
        }


        return $controller->process($scriptProperties);
    }
}
return 'localizatorFormProcessor';
