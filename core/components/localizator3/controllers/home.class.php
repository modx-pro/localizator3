<?php

/**
 * The home manager controller for localizator.
 *
 */
class Localizator3homeManagerController extends modExtraManagerController
{
    /** @var localizator $localizator */
    public $localizator;


    /**
     *
     */
    public function initialize()
    {
        $path = $this->modx->getOption(
            'localizator3_core_path',
            null,
            $this->modx->getOption('core_path') . 'components/localizator3/'
        ) . 'model/localizator3/';
        $this->localizator = $this->modx->getService('localizator3', 'localizator', $path);
        parent::initialize();
    }


    /**
     * @return array
     */
    public function getLanguageTopics()
    {
        return array('localizator3:default');
    }


    /**
     * @return bool
     */
    public function checkPermissions()
    {
        return true;
    }


    /**
     * @return null|string
     */
    public function getPageTitle()
    {
        return $this->modx->lexicon('localizator3');
    }


    /**
     * @return void
     */
    public function loadCustomCssJs()
    {
        $this->addCss($this->localizator->versionedAsset('css/mgr/main.css'));
        $this->addCss($this->localizator->versionedAsset('css/mgr/bootstrap.buttons.css'));

        $lexiconKeys = array(
            'localizator_languages', 'localizator_language_create', 'localizator_language_update',
            'localizator_key', 'localizator_language_name', 'localizator_language_http_host',
            'localizator_language_cultureKey', 'localizator_language_description', 'localizator_active',
            'localizator_grid_search', 'localizator_grid_actions', 'localizator_item_update',
            'localizator_item_create', 'localizator_item_enable', 'localizator_item_disable',
            'localizator_item_remove', 'localizator_items_remove', 'localizator_items_remove_confirm',
            'localizator_cancel', 'localizator_success', 'localizator_error', 'localizator_save',
            'localizator_language_updated', 'localizator_language_created', 'localizator_deleted',
            'localizator_enabled', 'localizator_disabled',
        );
        $lexicon = array();
        foreach ($lexiconKeys as $k) {
            $lexicon[$k] = $this->modx->lexicon($k);
        }
        $this->localizator->config['lexicon'] = $lexicon;

        $vueDistUrl = $this->localizator->config['jsUrl'] . 'mgr/vue-dist/';
        $assetsPath = $this->modx->getOption(
            'localizator3_assets_path',
            null,
            $this->modx->getOption('assets_path') . 'components/localizator3/'
        );
        $vueLanguagesExists = is_file($assetsPath . 'js/mgr/vue-dist/languages.min.js');

        if ($vueLanguagesExists) {
            $modAuth = $this->modx->user ? $this->modx->user->getUserToken('mgr') : '';
            $this->localizator->config['modAuth'] = $modAuth;
            $this->addHtml('<script type="text/javascript">
            localizator = { config: ' . json_encode($this->localizator->config) . ' };
            localizator.config.connector_url = "' . $this->localizator->config['connectorUrl'] . '";
            </script>');
            $langJsUrl = $this->localizator->versionedAsset('js/mgr/vue-dist/languages.min.js');
            $this->addHtml('<script type="module" src="' . htmlspecialchars($langJsUrl) . '"></script>');
        } else {
            $this->addJavascript($this->localizator->config['jsUrl'] . 'mgr/localizator.js');
            $this->addJavascript($this->localizator->config['jsUrl'] . 'mgr/misc/utils.js');
            $this->addJavascript($this->localizator->config['jsUrl'] . 'mgr/misc/combo.js');
            $this->addJavascript($this->localizator->config['jsUrl'] . 'mgr/widgets/languages.grid.js');
            $this->addJavascript($this->localizator->config['jsUrl'] . 'mgr/widgets/lexicon.grid.js');
            $this->addJavascript($this->localizator->config['jsUrl'] . 'mgr/widgets/home.panel.js');
            $this->addJavascript($this->localizator->config['jsUrl'] . 'mgr/sections/home.js');
            $this->addHtml('<script type="text/javascript">
            localizator.config = ' . json_encode($this->localizator->config) . ';
            localizator.config.connector_url = "' . $this->localizator->config['connectorUrl'] . '";
            Ext.onReady(function() {
                MODx.load({ xtype: "localizator-page-home"});
            });
            </script>
            ');
        }
    }


    /**
     * @return string
     */
    public function getTemplateFile()
    {
        $assetsPath = $this->modx->getOption(
            'localizator3_assets_path',
            null,
            $this->modx->getOption('assets_path') . 'components/localizator3/'
        );
        $vueExists = is_file($assetsPath . 'js/mgr/vue-dist/languages.min.js');
        return $this->localizator->config['templatesPath'] . ($vueExists ? 'home_vue.tpl' : 'home.tpl');
    }
}
