localizator.page.Home = function (config) {
    config = config || {};
    Ext.applyIf(config, {
        components: [{
            xtype: 'localizator-panel-home',
            renderTo: 'localizator-panel-home-div'
        }]
    });
    localizator.page.Home.superclass.constructor.call(this, config);
};
Ext.extend(localizator.page.Home, MODx.Component);
Ext.reg('localizator-page-home', localizator.page.Home);