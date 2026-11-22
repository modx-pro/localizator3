<?php

/**
 * OnManagerPageBeforeRender — lexicon for element descriptions.
 *
 * The chunk, snippet and plugin forms show the description column as stored.
 * The value is a lexicon key. Show the translation in the field and write the
 * key back on save when the manager did not edit the text.
 *
 * @var \MODX\Revolution\modX $modx
 */

if (!$modx->controller || !method_exists($modx->controller, 'addLexiconTopic')) {
    return;
}

$modx->controller->addLexiconTopic('localizator3:default');

$action = (string)($_REQUEST['a'] ?? '');
$elementPages = array(
    'element/chunk/update',
    'element/snippet/update',
    'element/plugin/update',
);
if (!in_array($action, $elementPages, true)) {
    return;
}

$modx->controller->addHtml('
<script>
Ext.onReady(function () {
    const pairs = [
        ["modx-chunk-description", "modx-panel-chunk"],
        ["modx-snippet-description", "modx-panel-snippet"],
        ["modx-plugin-description", "modx-panel-plugin"]
    ];
    pairs.forEach(function (pair) {
        const fieldId = pair[0];
        const panelId = pair[1];
        Ext.ComponentMgr.onAvailable(panelId, function () {
            const panel = this;
            const field = Ext.getCmp(fieldId);
            if (!field) {
                return;
            }
            const key = field.getValue();
            const text = _(key);
            if (!text || text === key) {
                return;
            }
            field.localizatorLexiconKey = key;
            field.setValue(text);
            field.localizatorEdited = false;
            field.on("change", function (cmp, value) {
                cmp.localizatorEdited = value !== text && value !== cmp.localizatorLexiconKey;
            });
            panel.on("beforeSubmit", function () {
                if (field.localizatorLexiconKey && !field.localizatorEdited) {
                    field.setValue(field.localizatorLexiconKey);
                }
                return true;
            });
        });
    });
});
</script>
');
