<?php

/**
 * The home manager controller for localizator.
 *
 * Vue-UI (languages.min.js) — lean entry, Vue-стек даёт VueTools ≥1.1.2-pl
 * через Import Map. Без VueTools показывается lexicon-сообщение (ExtJS grid удалён).
 */
class Localizator3homeManagerController extends modExtraManagerController
{
    use Localizator3VueControllerTrait;

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
        require_once $this->modx->getOption(
            'localizator3_core_path',
            null,
            $this->modx->getOption('core_path') . 'components/localizator3/'
        ) . 'Localizator3VueControllerTrait.php';
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
            'localizator_enabled', 'localizator_disabled', 'localizator3_vuetools_required',
        );
        $lexicon = array();
        foreach ($lexiconKeys as $k) {
            $lexicon[$k] = $this->modx->lexicon($k);
        }
        $this->localizator->config['lexicon'] = $lexicon;

        $assetsPath = $this->modx->getOption(
            'localizator3_assets_path',
            null,
            $this->modx->getOption('assets_path') . 'components/localizator3/'
        );
        $vueLanguagesExists = is_file($assetsPath . 'js/mgr/vue-dist/languages.min.js');
        $vueToolsOk = $this->requireVueTools();

        if ($vueLanguagesExists && $vueToolsOk) {
            $modAuth = $this->modx->user ? $this->modx->user->getUserToken('mgr') : '';
            $this->localizator->config['modAuth'] = $modAuth;
            $this->addHtml('<script type="text/javascript">
            localizator = { config: ' . json_encode($this->localizator->config) . ' };
            localizator.config.connector_url = "' . $this->localizator->config['connectorUrl'] . '";
            </script>');
            // Component-scoped CSS entry; тема/PrimeIcons даёт VueTools (vuetools.css).
            $this->addCss($this->localizator->versionedAsset('css/mgr/vue-dist/languages.min.css'));
            // Lean ES-модуль: vue/pinia/primevue резолвятся через Import Map VueTools.
            $langJsUrl = $this->localizator->versionedAsset('js/mgr/vue-dist/languages.min.js');
            $this->addVueModule($langJsUrl);
        } else {
            // VueTools — обязательная зависимость. Без неё (или без собранного бандла)
            // показываем понятное сообщение вместо удалённого ExtJS grid.
            $this->addHtml('<script type="text/javascript">
            document.addEventListener("DOMContentLoaded", function() {
                var el = document.getElementById("localizator3-languages-app");
                if (el) { el.innerHTML = "<p style=\"padding:1rem\">" + ' . json_encode($this->modx->lexicon('localizator3_vuetools_required')) . ' + "</p>"; }
            });
            </script>');
        }
    }


    /**
     * @return string
     */
    public function getTemplateFile()
    {
        // Шаблон единственный — Vue-контейнер. ExtJS-вариант (home.tpl) удалён.
        return $this->localizator->config['templatesPath'] . 'home_vue.tpl';
    }
}
