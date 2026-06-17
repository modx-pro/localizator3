<?php

/**
 * OnDocFormPrerender handler — adds Localizator3 tab to resource form.
 *
 * Vue-UI (content.min.js) — lean entry, Vue-стек даёт VueTools ≥1.1.2-pl
 * через Import Map. Без VueTools показывается lexicon-сообщение (ExtJS grid удалён).
 *
 * @var \MODX\Revolution\modX $modx
 * @var localizator $localizator
 * @var \MODX\Revolution\modResource $resource
 * @var string $mode
 * @var int $id
 */

require_once $modx->getOption('localizator3_core_path', null, $modx->getOption('core_path') . 'components/localizator3/') . 'Localizator3VueControllerTrait.php';

$isResource = $resource instanceof \MODX\Revolution\modResource
    || (class_exists('\MiniShop3\Model\msProduct') && $resource instanceof \MiniShop3\Model\msProduct);
if ($mode == 'upd' && $isResource) {
    $disabledTemplates = $modx->getOption('localizator3_disabled_templates', null, '', true);
    $disabledTemplates = array_map('trim', array_filter(explode(',', $disabledTemplates)));
    if (in_array((string)$resource->get('template'), $disabledTemplates, true)) {
        return;
    }
    $modx->controller->addLexiconTopic('localizator3:default');
    $modx->controller->addCss($localizator->versionedAsset('css/mgr/main.css'));
    $modx->controller->addCss($localizator->versionedAsset('css/mgr/bootstrap.buttons.css'));

    $assetsPath = $modx->getOption('localizator3_assets_path', null, $modx->getOption('assets_path') . 'components/localizator3/');
    $vueContentExists = is_file($assetsPath . 'js/mgr/vue-dist/content.min.js');

    $lexiconKeys = array(
        'localizator3_tab', 'localizator_add', 'localizator_translate', 'localizator_grid_search',
        'localizator__key', 'localizator_pagetitle', 'localizator_seotitle', 'localizator_active',
        'localizator_grid_actions', 'localizator_item_update', 'localizator_item_enable',
        'localizator_item_disable', 'localizator_item_remove', 'localizator_items_remove_confirm',
        'localizator_translate_confirm', 'localizator_language', 'localizator_loading',
        'localizator_cancel', 'localizator_success', 'localizator_error', 'localizator_save',
        'localizator_item_create', 'localizator_content_created', 'localizator_content_updated',
        'localizator_language_created', 'localizator_language_updated',
        'localizator_no_available_languages', 'localizator_no_languages_configured', 'localizator_add_languages_hint',
        'localizator3_vuetools_required',
    );
    $lexicon = array();
    foreach ($lexiconKeys as $k) {
        $lexicon[$k] = $modx->lexicon($k);
    }
    $localizator->config['lexicon'] = $lexicon;

    $controller = new class {
        use Localizator3VueControllerTrait;
        public $modx;
        public function __construct($modx) { $this->modx = $modx; }
    };
    $controller->modx = $modx;
    $vueToolsOk = $controller->requireVueTools();

    if ($vueContentExists && $vueToolsOk) {
        $modAuth = $modx->user ? $modx->user->getUserToken('mgr') : '';
        $localizator->config['modAuth'] = $modAuth;
        $connectorUrl = $localizator->config['connectorUrl'];
        if (strpos($connectorUrl, 'http') !== 0 && strpos($connectorUrl, '//') !== 0 && (strlen($connectorUrl) === 0 || $connectorUrl[0] !== '/')) {
            $connectorUrl = rtrim($modx->getOption('site_url'), '/') . '/' . ltrim($connectorUrl, '/');
        }
        $localizator->config['connectorUrl'] = $connectorUrl;
        $modx->controller->addHtml('<script type="text/javascript">
            localizator = { config: ' . json_encode($localizator->config) . ' };
            localizator.config.connector_url = "' . addslashes($connectorUrl) . '";
        </script>');
        // Component-scoped CSS entry; тема/PrimeIcons даёт VueTools (vuetools.css).
        $modx->controller->addCss($localizator->versionedAsset('css/mgr/vue-dist/content.min.css'));
        // Lean ES-модуль: vue/pinia/primevue резолвятся через Import Map VueTools.
        $contentJsUrl = $localizator->versionedAsset('js/mgr/vue-dist/content.min.js');
        $controller->addVueModule($contentJsUrl);
        $modx->controller->addHtml('
        <script type="text/javascript">
            Ext.ComponentMgr.onAvailable("modx-resource-tabs", function() {
                this.on("beforerender", function() {
                    this.add({
                        title: _("localizator3_tab"),
                        id: "localizator3-resource-tab",
                        items: [{
                            xtype: "panel",
                            cls: "main-wrapper vueApp",
                            html: \'<div id="localizator3-content-app" data-resource-id="' . (int)$id . '" data-connector-url="' . addslashes($connectorUrl) . '" data-mod-auth="' . addslashes($modAuth) . '"></div>\',
                            listeners: {
                                afterrender: function() {
                                    var el = document.getElementById("localizator3-content-app");
                                    if (el && typeof localizatorContentApp !== "undefined") {
                                        localizatorContentApp(el);
                                    }
                                }
                            }
                        }]
                    });
                });
            });
        </script>');
    } else {
        // VueTools — обязательная зависимость. Без неё (или без собранного бандла)
        // показываем понятное сообщение вместо удалённого ExtJS grid.
        $modx->controller->addHtml('
        <script type="text/javascript">
            Ext.ComponentMgr.onAvailable("modx-resource-tabs", function() {
                this.on("beforerender", function() {
                    this.add({
                        title: _("localizator3_tab"),
                        id: "localizator3-resource-tab",
                        items: [{
                            xtype: "panel",
                            cls: "main-wrapper",
                            html: "<p style=\"padding:1rem\">" + _("localizator3_vuetools_required") + "</p>"
                        }]
                    });
                });
            });
        </script>');
    }
}
