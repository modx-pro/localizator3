<?php

/**
 * OnTVFormPrerender handler — adds Localizator3 checkbox to TV form.
 *
 * @var \MODX\Revolution\modX $modx
 * @var localizator $localizator
 */

$modx->controller->addLexiconTopic('localizator3:default');
$modx->controller->addHtml('
    <script type="text/javascript">
        Ext.ComponentMgr.onAvailable("modx-panel-tv", function(config) {
            Ext.ComponentMgr.onAvailable("modx-tv-form", function() {
                this.items[1].items[1].items.push({
                    xtype: "xcheckbox"
                    ,boxLabel: _("tv_localizator3_enabled")
                    ,description: _("tv_localizator3_enabled_msg")
                    ,name: "localizator3_enabled"
                    ,id: "modx-tv-localizator3_enabled"
                    ,inputValue: 1
                    ,checked: config.record.localizator3_enabled || false
                });
            });
        });
    </script>
    ');
