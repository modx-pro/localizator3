localizator.combo.Language = function(config) {
	config = config || {};
	Ext.applyIf(config,{
		name: 'key',
		hiddenName: 'key',
		displayField: '_title',
		valueField: 'key',
		pageSize: 20,
		emptyText: _('localizator_choose_language'),
		fields: ['id','name','key', 'http_host', '_title'],
		url: localizator.config.connector_url,
		baseParams: {
			action: 'mgr/language/getlist',
			combo: 1,
		},
		typeAhead: true,
		autoSelect: true,
		editable: true,
		tpl: new Ext.XTemplate('<tpl for="."><div class="x-combo-list-item {class}">{_title}</div></tpl>')
	});
	localizator.combo.Language.superclass.constructor.call(this,config);
};
Ext.extend(localizator.combo.Language,MODx.combo.ComboBox);
Ext.reg('localizator-combo-language',localizator.combo.Language);



localizator.combo.Search = function (config) {
    config = config || {};
    Ext.applyIf(config, {
        xtype: 'twintrigger',
        ctCls: 'x-field-search',
        allowBlank: true,
        msgTarget: 'under',
        emptyText: _('search'),
        name: 'query',
        triggerAction: 'all',
        clearBtnCls: 'x-field-search-clear',
        searchBtnCls: 'x-field-search-go',
        onTrigger1Click: this._triggerSearch,
        onTrigger2Click: this._triggerClear,
    });
    localizator.combo.Search.superclass.constructor.call(this, config);
    this.on('render', function () {
        this.getEl().addKeyListener(Ext.EventObject.ENTER, function () {
            this._triggerSearch();
        }, this);
    });
    this.addEvents('clear', 'search');
};
Ext.extend(localizator.combo.Search, Ext.form.TwinTriggerField, {

    initComponent: function () {
        Ext.form.TwinTriggerField.superclass.initComponent.call(this);
        this.triggerConfig = {
            tag: 'span',
            cls: 'x-field-search-btns',
            cn: [
                {tag: 'div', cls: 'x-form-trigger ' + this.searchBtnCls},
                {tag: 'div', cls: 'x-form-trigger ' + this.clearBtnCls}
            ]
        };
    },

    _triggerSearch: function () {
        this.fireEvent('search', this);
    },

    _triggerClear: function () {
        this.fireEvent('clear', this);
    },

});
Ext.reg('localizator-combo-search', localizator.combo.Search);
Ext.reg('localizator-field-search', localizator.combo.Search);