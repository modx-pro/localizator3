let localizator = function (config) {
    config = config || {};
    localizator.superclass.constructor.call(this, config);
};
Ext.extend(localizator, Ext.Component, {
    page: {}, window: {}, grid: {}, tree: {}, panel: {}, combo: {}, config: {}, view: {}, utils: {}
});
Ext.reg('localizator', localizator);

localizator = new localizator();