localizator.grid.Content = function (config) {
    config = config || {};
    if (!config.id) {
        config.id = 'localizator3-grid-content';
    }

    Ext.applyIf(config, {
        url: localizator.config.connector_url,
        fields: this.getFields(config),
        columns: this.getColumns(config),
        tbar: this.getTopBar(config),
        sm: new Ext.grid.CheckboxSelectionModel(),
        baseParams: {
            action: 'mgr/content/getlist',
			resource_id: config.resource_id,
        },
        listeners: {
            /*
            rowDblClick: function (grid, rowIndex, e) {
                const row = grid.store.getAt(rowIndex);
                this.updateItem(grid, e, row);
            }
            */
        },
        viewConfig: {
            forceFit: true,
            enableRowBody: true,
            autoFill: true,
            showPreview: true,
            scrollOffset: 0,
            getRowClass: function (rec) {
                return !rec.data.active
                    ? 'localizator-grid-row-disabled'
                    : '';
            }
        },
        paging: true,
        remoteSort: true,
        autoHeight: true,
        autosave: true,
        save_action: 'mgr/content/updatefromgrid',
    });
    localizator.grid.Content.superclass.constructor.call(this, config);

    this.store.on('load', function () {
        if (this._getSelectedIds().length) {
            this.getSelectionModel().clearSelections();
        }
    }, this);
};
Ext.extend(localizator.grid.Content, MODx.grid.Grid, {
    windows: {},

    getMenu: function (grid, rowIndex) {
        const ids = this._getSelectedIds();

        const row = grid.getStore().getAt(rowIndex);
        const menu = localizator.utils.getMenu(row.data['actions'], this, ids);

        this.addContextMenuItem(menu);
    },

	translateItem: function (btn, e) {
        Ext.MessageBox.confirm(
            _('localizator_translate'),
            _('localizator_translate_confirm'),
            function (val) {
                if (val == 'yes') {
                    this._translation(0);
                }
            },
            this
        );
    },

	_translation: function (start) {
        if (!this._wait) {
            this._wait = Ext.MessageBox.wait(
                _('localizator_translate_wait'),
                _('please_wait')
            );
        }
        MODx.Ajax.request({
            url: localizator.config.connector_url,
            params: {
                action: 'mgr/content/translate',
                start: start || 0,
				resource_id: MODx.request.id,
            },
            listeners: {
                success: {
                    fn: function (response) {
						if (response.object['total'] == response.object['processed']) {
                            this._wait.hide();
                            this._wait = null;
                            this.refresh();
                        } else {
                            this._wait.updateText(
                                _('localizator_translate_wait_ext')
                                    .replace('[[+processed]]', response.object['processed'])
                                    .replace('[[+total]]', response.object['total'])
                            );
                            this._translation(response.object['processed']);
                        }

                    }, scope: this
                },
				failure: {
					fn: function(response) {
						this._wait.hide();
						this._wait = null;
						this.refresh();
						MODx.msg.alert(_('error'), response.message);
					}, scope: this
				}
            }
        });
    },

    сontentAction: function (method) {
        const ids = this._getSelectedIds();
        if (!ids.length) {
            return false;
        }
        MODx.Ajax.request({
            url: localizator.config.connector_url,
            params: {
                action: 'mgr/content/multiple',
                method: method,
                ids: Ext.util.JSON.encode(ids),
            },
            listeners: {
                success: {
                    fn: function () {
                        //noinspection JSUnresolvedFunction
                        this.refresh();
                    }, scope: this
                },
                failure: {
                    fn: function (response) {
                        MODx.msg.alert(_('error'), response.message);
                    }, scope: this
                },
            }
        })
    },

    removeItem: function () {
        const ids = this._getSelectedIds();
        if (!ids.length) {
            return false;
        }

        Ext.MessageBox.confirm(
            ids.length > 1
                ? _('localizator_items_remove')
                : _('localizator_item_remove'),
            ids.length > 1
                ? _('localizator_items_remove_confirm')
                : _('localizator_item_remove_confirm'),
            function (val) {
                if (val == 'yes') {
                    this.сontentAction('remove');
                }
            }, this
        );
    },

    disableItem: function () {
        this.сontentAction('disable');
    },

    enableItem: function () {
        this.сontentAction('enable');
    },

    getFields: function () {
        return ['id', '_key', 'pagetitle', 'seotitle', 'active', 'actions'];
    },

    getColumns: function () {
        return [{
            header: _('localizator__key'),
            dataIndex: '_key',
            sortable: true,
            width: 200,
            editor: {xtype: 'localizator-combo-language', name: '_key'},
        }, {
            header: _('localizator_pagetitle'),
            dataIndex: 'pagetitle',
            sortable: true,
            width: 200,
            editor: {xtype: 'textfield'},
        }, {
            header: _('localizator_seotitle'),
            dataIndex: 'seotitle',
            sortable: true,
            width: 200,
            editor: {xtype: 'textfield'},
        }, {
            header: _('localizator_active'),
            dataIndex: 'active',
            renderer: localizator.utils.renderBoolean,
            sortable: true,
            width: 100,
            editor: {xtype: 'combo-boolean', renderer: 'boolean'},
        }, {
            header: _('localizator_grid_actions'),
            dataIndex: 'actions',
            renderer: localizator.utils.renderActions,
            sortable: false,
            width: 100,
            id: 'actions'
        }];
    },

    getTopBar: function () {
        return [{
            text: '<i class="icon icon-globe"></i>&nbsp;' + _('localizator_add'),
            handler: this.loadCreateWin,
            scope: this
        }, {
            text: '<i class="icon icon-language"></i>&nbsp;' + _('localizator_translate'),
            handler: this.translateItem,
            scope: this
        }, '->', {
            xtype: 'localizator-field-search',
            width: 250,
            listeners: {
                search: {
                    fn: function (field) {
                        this._doSearch(field);
                    }, scope: this
                },
                clear: {
                    fn: function (field) {
                        field.setValue('');
                        this._clearSearch();
                    }, scope: this
                },
            }
        }];
    },

    onClick: function (e) {
        const elem = e.getTarget();
        if (elem.nodeName == 'BUTTON') {
            const row = this.getSelectionModel().getSelected();
            if (typeof(row) != 'undefined') {
                const action = elem.getAttribute('action');
                if (action == 'showMenu') {
                    const ri = this.getStore().find('id', row.id);
                    return this._showMenu(this, ri, e);
                }
                else if (typeof this[action] === 'function') {
                    this.menu.record = row.data;
                    return this[action](this, e);
                }
            }
        }
        return this.processEvent('click', e);
    },

    _getSelectedIds: function () {
        const ids = [];
        const selected = this.getSelectionModel().getSelections();

        for (const i in selected) {
            if (!selected.hasOwnProperty(i)) {
                continue;
            }
            ids.push(selected[i]['id']);
        }

        return ids;
    },

    _doSearch: function (tf) {
        this.getStore().baseParams.query = tf.getValue();
        this.getBottomToolbar().changePage(1);
    },

    _clearSearch: function () {
        this.getStore().baseParams.query = '';
        this.getBottomToolbar().changePage(1);
    },  

    loadCreateWin: function(btn,e) {
        return this._loadWin(btn,e,0);
    },

    loadUpdateWin: function(btn,e) {
        if (typeof(row) != 'undefined') {
            this.menu.record = row.data;
        }
        else if (!this.menu.record) {
            return false;
        }
        const id = this.menu.record.id;
        return this._loadWin(btn,e,id);
    },
    
    _loadWin: function(btn,e, loc_id) {
        const resource_id = this.config.resource_id;

        const win_xtype = 'modx-window-localizator-item-content';

        const w = Ext.getCmp(win_xtype);
        if (w) {
            w.hide().getEl().remove();
        }

        this.windows[win_xtype] = null;
        const action = 'mgr/fields';
        this.loadWindow(btn,e,{
    		url: localizator.config.connector_url
            ,xtype: win_xtype
            ,grid: this
            ,action: action
            ,baseParams : {
                action: action,
                resource_id : resource_id,
                loc_id : loc_id,
                win_id: win_xtype
            }
        });
    }

});
Ext.reg('localizator3-grid-content', localizator.grid.Content);

MODx.window.UpdatLocalizatorItem = function(config) {
    config = config || {};
    
    Ext.applyIf(config,{
        title:_('localizator_item_update')
        ,id: 'modx-window-localizator-item-content'
        ,width: 1000
		,closeAction: 'hide'
        ,shadow: false
        ,resizable: true
        ,collapsible: true
        ,maximizable: true
        ,allowDrop: true
        ,height: 600
        ,forceLayout: true
        ,defaults: {
            autoWidth: true
        }
        ,autoScroll: true
        ,buttons: [{
            text: config.cancelBtnText || _('cancel')
            ,scope: this
            ,handler: this.cancel
        },{
            text: config.saveBtnText || _('done')
            ,scope: this
            ,handler: this.submit
        }]
		,grid: null		
        ,fields: []
    });
    MODx.window.UpdatLocalizatorItem.superclass.constructor.call(this,config);
    this.options = config;
    this.config = config;

    this.on('hide',this.onHideWindow,this);
    this.addEvents({
        success: true
        ,failure: true
        ,beforeSubmit: true
        ,hide:true
    });
    this._loadForm();	
};
Ext.extend(MODx.window.UpdatLocalizatorItem,Ext.Window,{
    cancel: function(){
        this.hide();
    },         
    onHideWindow: function(){
   
        const v = this.fp.getForm().getValues();
        const fields = Ext.util.JSON.decode(v['mulititems_grid_item_fields']);
        if (fields.length>0){
            for (let i = 0; i < fields.length; i++) {
                if (Ext.get('tv'+(fields[i].tv_id)) && typeof(Ext.get('tv'+(fields[i].tv_id)).onHide) != 'undefined'){
                    Ext.get('tv'+(fields[i].tv_id)).onHide();
                }
            }
        }
        this.destroy();
    },
    submit: function() {
        const f = this.fp.getForm();
        let v = f.getValues();
        const fields = Ext.util.JSON.decode(v['mulititems_grid_item_fields']);
        if (fields.length>0){
            for (let i = 0; i < fields.length; i++) {
                if (Ext.get('tv'+(fields[i].tv_id)) && typeof(Ext.get('tv'+(fields[i].tv_id)).onBeforeSubmit) != 'undefined'){
                    Ext.get('tv'+(fields[i].tv_id)).onBeforeSubmit();
                }
            }
        }	

        if (f.isValid()) {
			v = f.getValues();
			delete v['mulititems_grid_item_fields'];
            MODx.Ajax.request({
		        url: localizator.config.connector_url
                ,params: v
                ,listeners: {
                    'success': {fn:function(r){
                    	this.grid.refresh();
			            this.fp.getForm().reset();
			            this.hide();
			            return true;
                    },scope:this}
                    ,'failure':{fn:function(r) {
                        return this.fireEvent('failure',r);
                    },scope:this}
                }
            });
            return true;
        }
        return false;
    },
    _loadForm: function() {
    	
        this.fp = this.createForm({
            url: this.config.url
            ,baseParams: this.config.baseParams || { action: this.config.action || '' }
        });
        this.add(this.fp);
    }	
    ,createForm: function(config){
        config = config || {};
        
        Ext.applyIf(config,{
            labelAlign: this.config.labelAlign || 'right'
            ,labelWidth: this.config.labelWidth || 100
            ,frame: this.config.formFrame || true
            ,popwindow : this
			,border: false
            ,bodyBorder: false
            ,errorReader: MODx.util.JSONReader
            ,url: this.config.url
            ,baseParams: this.config.baseParams || {}
            ,fileUpload: this.config.fileUpload || false
        });
        return new MODx.panel.LocalizatorWindowPanel(config);
    }
    ,onShow: function() {
        if (this.fp.isloading) return;
        this.fp.isloading=true;
        this.fp.doAutoLoad();
    }
});
Ext.reg('modx-window-localizator-item-content', MODx.window.UpdatLocalizatorItem);

MODx.panel.LocalizatorWindowPanel = function(config) {
    config = config || {};
    Ext.applyIf(config,{
        id: 'localizator-panel-object'
		,title: ''
        ,url: config.url
        ,baseParams: config.baseParams	
        ,class_key: ''
        ,bodyStyle: 'padding: 5px;'
        ,autoLoad: this.autoload(config)
        ,listeners: {
			'load': {fn:this.load,scope:this}
        }		
    });
 	MODx.panel.LocalizatorWindowPanel.superclass.constructor.call(this,config);
};
Ext.extend(MODx.panel.LocalizatorWindowPanel,MODx.FormPanel,{
    autoload: function(config) {
		this.isloading=true;
		const a = {
            url: localizator.config.connector_url
			,method: 'POST'
            ,params: config.baseParams
            ,scripts: true
            ,callback: function() {
				this.isloading=false;
				this.isloaded=true;
				this.fireEvent('load');
            }
            ,scope: this
        };
        return a;        	
    },scope: this
    
    ,
    setup: function() {

    }
    ,beforeSubmit: function(o) {
        tinyMCE.triggerSave(); 
    }
	,load: function() {
        if (this.isloaded !== true) return '';
        const v = this.getForm().getValues();
        const fields = Ext.util.JSON.decode(v['mulititems_grid_item_fields']);
        if (fields.length>0){
            for (let i = 0; i < fields.length; i++) {
                if (Ext.get('tv'+(fields[i].tv_id)) && typeof(Ext.get('tv'+(fields[i].tv_id)).onLoad) != 'undefined'){
                    Ext.get('tv'+(fields[i].tv_id)).onLoad();
                }
            }
        }
        this.popwindow.width='1000px';
		this.width='1000px';
		this.syncSize();
		this.popwindow.syncSize();
		return '';
	 }
});
Ext.reg('localizator-panel-object', MODx.panel.LocalizatorWindowPanel);
