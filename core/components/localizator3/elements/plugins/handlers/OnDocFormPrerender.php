<?php

/**
 * OnDocFormPrerender handler — adds Localizator3 tab to resource form.
 *
 * Vue-UI (content.min.js) — lean entry, Vue-стек даёт VueTools ≥1.1.2-pl
 * через Import Map. Без VueTools показывается lexicon-сообщение (ExtJS grid удалён).
 *
 * @var \MODX\Revolution\modX $modx
 * @var localizator $localizator
 */

require_once $modx->getOption('localizator3_core_path', null, $modx->getOption('core_path') . 'components/localizator3/') . 'Localizator3VueControllerTrait.php';

$eventParams = $modx->event->params ?? [];
$mode = $mode ?? ($eventParams['mode'] ?? '');
$id = (int)($id ?? ($eventParams['id'] ?? 0));
$resource = $resource ?? ($eventParams['resource'] ?? null);

if (!$resource && $id > 0) {
    $resource = $modx->getObject(\MODX\Revolution\modResource::class, $id);
}

$isResource = $resource instanceof \MODX\Revolution\modResource
    || (class_exists('\MiniShop3\Model\msProduct') && $resource instanceof \MiniShop3\Model\msProduct);

if ($mode !== 'upd' || !$isResource || $id <= 0) {
    return;
}

$disabledTemplates = $modx->getOption('localizator3_disabled_templates', null, '', true);
$disabledTemplates = array_map('trim', array_filter(explode(',', $disabledTemplates)));
if (in_array((string)$resource->get('template'), $disabledTemplates, true)) {
    return;
}

$vueBridge = new class {
    use Localizator3VueControllerTrait;

    /** @var \MODX\Revolution\modX */
    public $modx;

    /** @var localizator */
    public $localizator;
};
$vueBridge->modx = $modx;
$vueBridge->localizator = $localizator;

if ($modx->controller && method_exists($modx->controller, 'addLexiconTopic')) {
    $modx->controller->addLexiconTopic('localizator3:default');
} else {
    $modx->lexicon->load('localizator3:default');
}
$vueBridge->addClientCss($localizator->versionedAsset('css/mgr/main.css'));
$vueBridge->addClientCss($localizator->versionedAsset('css/mgr/bootstrap.buttons.css'));

$assetsPath = $modx->getOption('localizator3_assets_path', null, $modx->getOption('assets_path') . 'components/localizator3/');
$vueContentExists = is_file($assetsPath . 'js/mgr/vue-dist/content.min.js');

$lexiconKeys = array(
    'localizator3_tab', 'localizator_content_section_desc',
    'localizator_stats_translations', 'localizator_stats_active', 'localizator_stats_available',
    'localizator_add', 'localizator_translate', 'localizator_grid_search',
    'localizator__key', 'localizator_pagetitle', 'localizator_seotitle', 'localizator_active',
    'localizator_grid_actions', 'localizator_item_update', 'localizator_item_enable',
    'localizator_item_disable', 'localizator_item_remove', 'localizator_items_remove_confirm',
    'localizator_translate_confirm', 'localizator_language', 'localizator_loading',
    'localizator_cancel', 'localizator_success', 'localizator_error', 'localizator_save',
    'localizator_item_create', 'localizator_content_created', 'localizator_content_updated',
    'localizator_no_available_languages', 'localizator_no_languages_configured', 'localizator_add_languages_hint',
    'localizator3_vuetools_required',
);
$lexicon = array();
foreach ($lexiconKeys as $k) {
    $lexicon[$k] = $modx->lexicon($k);
}
$localizator->config['lexicon'] = $lexicon;

$vueToolsOk = $vueBridge->requireVueTools();
$useVueUi = $vueContentExists && $vueToolsOk;

$modAuth = $modx->user ? $modx->user->getUserToken('mgr') : '';
$connectorUrl = $localizator->config['connectorUrl'] ?? '';
if (strpos($connectorUrl, 'http') !== 0 && strpos($connectorUrl, '//') !== 0 && (strlen($connectorUrl) === 0 || $connectorUrl[0] !== '/')) {
    $connectorUrl = rtrim($modx->getOption('site_url'), '/') . '/' . ltrim($connectorUrl, '/');
}
$localizator->config['connectorUrl'] = $connectorUrl;
$localizator->config['modAuth'] = $modAuth;

if ($useVueUi) {
    $vueBridge->addClientHtml('<script type="text/javascript">
        localizator = { config: ' . json_encode($localizator->config, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ' };
        localizator.config.connector_url = ' . json_encode($connectorUrl, JSON_UNESCAPED_UNICODE) . ';
    </script>');
    $vueBridge->addClientCss($localizator->versionedAsset('css/mgr/vue-dist/content.min.css'));
    $vueBridge->addVueModule($localizator->versionedAsset('js/mgr/vue-dist/content.min.js'));

    $panelHtml = '<div id="localizator3-content-app"'
        . ' data-resource-id="' . $id . '"'
        . ' data-connector-url="' . htmlspecialchars($connectorUrl, ENT_QUOTES, 'UTF-8') . '"'
        . ' data-mod-auth="' . htmlspecialchars($modAuth, ENT_QUOTES, 'UTF-8') . '"'
        . '></div>';
} else {
    if (!$vueContentExists) {
        $message = 'Localizator3: UI bundle not found (content.min.js). Rebuild vueManager.';
    } else {
        $message = $modx->lexicon('localizator3_vuetools_required');
    }
    $panelHtml = '<p style="padding:1rem">' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>';
}

$tabPanelHtml = json_encode($panelHtml, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

$vueBridge->addClientHtml('<script type="text/javascript">
(function() {
    var tabConfig = {
        title: _("localizator3_tab"),
        id: "localizator3-resource-tab",
        cls: "modx-resource-tab",
        layout: "form",
        autoHeight: true,
        items: [{
            xtype: "panel",
            border: false,
            cls: "main-wrapper vueApp localizator3-content-tab",
            html: ' . $tabPanelHtml . ',
            listeners: {
                afterrender: function(panel) { localizator3MountContentTab(panel); },
                activate: function(panel) { localizator3MountContentTab(panel); }
            }
        }]
    };

    function isContentMounted(el) {
        return el && el.querySelector && el.querySelector(".content-grid");
    }

    function tryMountContent(el, attempt) {
        if (!el) {
            return;
        }
        if (isContentMounted(el)) {
            el.setAttribute("data-mounted", "1");
            return;
        }
        if (el.getAttribute("data-mounted") === "1") {
            el.removeAttribute("data-mounted");
        }
        if (typeof localizatorContentApp === "function") {
            try {
                localizatorContentApp(el);
            } catch (err) {
                console.error("[Localizator3] Content tab mount failed:", err);
                el.removeAttribute("data-mounted");
                return;
            }
            var tab = Ext.getCmp("localizator3-resource-tab");
            if (tab && typeof tab.doLayout === "function") {
                tab.doLayout();
            }
            return;
        }
        if ((attempt || 0) < 50) {
            setTimeout(function() {
                tryMountContent(el, (attempt || 0) + 1);
            }, 100);
        }
    }

    window.localizator3MountContentTab = function(panel) {
        if (!panel) {
            return;
        }
        var root = panel.getEl ? panel.getEl().dom : panel;
        var el = root && root.querySelector ? root.querySelector("#localizator3-content-app") : null;
        if (!el) {
            el = document.getElementById("localizator3-content-app");
        }
        tryMountContent(el);
    };

    function addLocalizatorTab() {
        if (Ext.getCmp("localizator3-resource-tab")) {
            return true;
        }
        var tabs = Ext.getCmp("modx-resource-tabs");
        if (!tabs) {
            return false;
        }
        if (typeof MODx !== "undefined" && typeof MODx.addTab === "function") {
            MODx.addTab("modx-resource-tabs", tabConfig);
        } else {
            tabs.add(tabConfig);
            tabs.doLayout();
        }
        var tab = Ext.getCmp("localizator3-resource-tab");
        if (tab) {
            tab.on("activate", function(activeTab) {
                localizator3MountContentTab(activeTab);
            });
            if (tabs.getActiveTab && tabs.getActiveTab() === tab) {
                localizator3MountContentTab(tab);
            }
        }
        return true;
    }

    function scheduleAddTab() {
        if (addLocalizatorTab()) {
            return;
        }
        Ext.ComponentMgr.onAvailable("modx-resource-tabs", function() {
            addLocalizatorTab();
        });
    }

    if (typeof MODx !== "undefined" && typeof MODx.on === "function") {
        MODx.on("ready", scheduleAddTab);
    } else {
        Ext.onReady(scheduleAddTab);
    }
})();
</script>');
