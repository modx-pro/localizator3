localizator.grid.Language = function (config) {
    config = config || {};
    if (!config.id) {
        config.id = 'localizator-grid-language';
    }
    Ext.applyIf(config, {
        url: localizator.config.connector_url,
        fields: this.getFields(config),
        columns: this.getColumns(config),
        tbar: this.getTopBar(config),
        sm: new Ext.grid.CheckboxSelectionModel(),
        baseParams: {
            action: 'mgr/language/getlist'
        },
        listeners: {
            rowDblClick: function (grid, rowIndex, e) {
                const row = grid.store.getAt(rowIndex);
                this.updateItem(grid, e, row);
            }
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
    });
    localizator.grid.Language.superclass.constructor.call(this, config);

    this.store.on('load', function () {
        if (this._getSelectedIds().length) {
            this.getSelectionModel().clearSelections();
        }
    }, this);
};
Ext.extend(localizator.grid.Language, MODx.grid.Grid, {
    windows: {},

    getMenu: function (grid, rowIndex) {
        const ids = this._getSelectedIds();

        const row = grid.getStore().getAt(rowIndex);
        const menu = localizator.utils.getMenu(row.data['actions'], this, ids);

        this.addContextMenuItem(menu);
    },

    createItem: function (btn, e) {
        const w = MODx.load({
            xtype: 'localizator-language-window-create',
            id: Ext.id(),
            listeners: {
                success: {
                    fn: function () {
                        this.refresh();
                    }, scope: this
                }
            }
        });
        w.reset();
        w.setValues({active: true});
        w.show(e.target);
    },

    updateItem: function (btn, e, row) {
        if (typeof(row) != 'undefined') {
            this.menu.record = row.data;
        }
        else if (!this.menu.record) {
            return false;
        }
        const id = this.menu.record.id;

        MODx.Ajax.request({
            url: this.config.url,
            params: {
                action: 'mgr/language/get',
                id: id
            },
            listeners: {
                success: {
                    fn: function (r) {
                        const w = MODx.load({
                            xtype: 'localizator-language-window-update',
                            id: Ext.id(),
                            record: r,
                            listeners: {
                                success: {
                                    fn: function () {
                                        this.refresh();
                                    }, scope: this
                                }
                            }
                        });
                        w.reset();
                        w.setValues(r.object);
                        w.show(e.target);
                    }, scope: this
                }
            }
        });
    },

    removeItem: function () {
        const ids = this._getSelectedIds();
        if (!ids.length) {
            return false;
        }
        MODx.msg.confirm({
            title: ids.length > 1
                ? _('localizator_items_remove')
                : _('localizator_item_remove'),
            text: ids.length > 1
                ? _('localizator_items_remove_confirm')
                : _('localizator_item_remove_confirm'),
            url: this.config.url,
            params: {
                action: 'mgr/language/remove',
                ids: Ext.util.JSON.encode(ids),
            },
            listeners: {
                success: {
                    fn: function () {
                        this.refresh();
                    }, scope: this
                }
            }
        });
        return true;
    },

    disableItem: function () {
        const ids = this._getSelectedIds();
        if (!ids.length) {
            return false;
        }
        MODx.Ajax.request({
            url: this.config.url,
            params: {
                action: 'mgr/language/disable',
                ids: Ext.util.JSON.encode(ids),
            },
            listeners: {
                success: {
                    fn: function () {
                        this.refresh();
                    }, scope: this
                }
            }
        })
    },

    enableItem: function () {
        const ids = this._getSelectedIds();
        if (!ids.length) {
            return false;
        }
        MODx.Ajax.request({
            url: this.config.url,
            params: {
                action: 'mgr/language/enable',
                ids: Ext.util.JSON.encode(ids),
            },
            listeners: {
                success: {
                    fn: function () {
                        this.refresh();
                    }, scope: this
                }
            }
        })
    },

    getFields: function () {
        return ['id', 'key', 'name', 'http_host', 'cultureKey', 'active', 'actions'];
    },

    getColumns: function () {
        return [{
            header: _('localizator_key'),
            dataIndex: 'key',
            sortable: true,
            width: 200,
        }, {
            header: _('localizator_language_name'),
            dataIndex: 'name',
            sortable: true,
            width: 200,
        }, {
            header: _('localizator_language_http_host'),
            dataIndex: 'http_host',
            sortable: true,
            width: 200,
        }, {
            header: _('localizator_language_cultureKey'),
            dataIndex: 'cultureKey',
            sortable: true,
            width: 200,
        }, {
            header: _('localizator_active'),
            dataIndex: 'active',
            renderer: localizator.utils.renderBoolean,
            sortable: true,
            width: 100,
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
            text: '<i class="icon icon-globe"></i>&nbsp;' + _('localizator_language_create'),
            handler: this.createItem,
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
});
Ext.reg('localizator-grid-language', localizator.grid.Language);


localizator.window.CreateLanguage = function (config) {
    config = config || {};
    if (!config.id) {
        config.id = 'localizator-language-window-create';
    }
    Ext.applyIf(config, {
        title: _('localizator_language_create'),
        width: 550,
        autoHeight: true,
        url: localizator.config.connector_url,
        action: 'mgr/language/create',
        fields: this.getFields(config),
        keys: [{
            key: Ext.EventObject.ENTER, shift: true, fn: function () {
                this.submit()
            }, scope: this
        }]
    });
    localizator.window.CreateLanguage.superclass.constructor.call(this, config);
};
Ext.extend(localizator.window.CreateLanguage, MODx.Window, {

	getFields: function(config) {
		return [{
			layout:'column',
			border:false,
			anchor: '100%',
			style: {margin: '0 0 20px 0'},
			items: [{
				layout: 'form',
				border:false,
				columnWidth: .5,
				items: [{
					xtype: 'textfield',
					fieldLabel: _('localizator_language_key'),
					name: 'key',
					id: config.id + '-key',
					anchor: '99%',
					allowBlank: false,
				}, {
					xtype: 'textfield',
					fieldLabel: _('localizator_language_http_host'),
					name: 'http_host',
					id: config.id + '-http_host',
					anchor: '99%',
					allowBlank: false,
				}],
			}, {
				layout: 'form',
				border:false,
				columnWidth: .5,
				items: [{
					xtype: 'textfield',
					fieldLabel: _('localizator_language_name'),
					name: 'name',
					id: config.id + '-name',
					anchor: '99%',
				}, {
					xtype: 'textfield',
					fieldLabel: _('localizator_language_cultureKey'),
					name: 'cultureKey',
					id: config.id + '-cultureKey',
					anchor: '99%',
				}],
			}]
		}, {
			xtype: 'textarea',
			fieldLabel: _('localizator_language_description'),
			name: 'description',
			id: config.id + '-description',
			anchor: '99%',
		}, {
			xtype: 'xcheckbox',
            boxLabel: _('localizator_active'),
            name: 'active',
            id: config.id + '-active',
            checked: true,
		}];
	},

});
Ext.reg('localizator-language-window-create', localizator.window.CreateLanguage);

localizator.window.UpdateLanguage = function (config) {
    config = config || {};
    if (!config.id) {
        config.id = 'localizator-language-window-update';
    }
    Ext.applyIf(config, {
        title: _('localizator_language_update'),
        width: 550,
        autoHeight: true,
        url: localizator.config.connector_url,
        action: 'mgr/language/update',
        fields: this.getFields(config),
        keys: [{
            key: Ext.EventObject.ENTER, shift: true, fn: function () {
                this.submit()
            }, scope: this
        }]
    });
    localizator.window.UpdateLanguage.superclass.constructor.call(this, config);
};
Ext.extend(localizator.window.UpdateLanguage, MODx.Window, {

	getFields: function(config) {
		return [{
            xtype: 'hidden',
            name: 'id',
            id: config.id + '-id',
        }, {
			layout:'column',
			border:false,
			anchor: '100%',
			style: {margin: '0 0 20px 0'},
			items: [{
				layout: 'form',
				border:false,
				columnWidth: .5,
				items: [{
					xtype: 'textfield',
					fieldLabel: _('localizator_language_key'),
					name: 'key',
					id: config.id + '-key',
					anchor: '99%',
					allowBlank: false,
				}, {
					xtype: 'textfield',
					fieldLabel: _('localizator_language_http_host'),
					name: 'http_host',
					id: config.id + '-http_host',
					anchor: '99%',
					allowBlank: false,
				}],
			}, {
				layout: 'form',
				border:false,
				columnWidth: .5,
				items: [{
					xtype: 'textfield',
					fieldLabel: _('localizator_language_name'),
					name: 'name',
					id: config.id + '-name',
					anchor: '99%',
				}, {
					xtype: 'textfield',
					fieldLabel: _('localizator_language_cultureKey'),
					name: 'cultureKey',
					id: config.id + '-cultureKey',
					anchor: '99%',
				}],
			}]
		}, {
			xtype: 'textarea',
			fieldLabel: _('localizator_language_description'),
			name: 'description',
			id: config.id + '-description',
			anchor: '99%',
		}, {
			xtype: 'xcheckbox',
            boxLabel: _('localizator_active'),
            name: 'active',
            id: config.id + '-active',
		}];
	},


});
Ext.reg('localizator-language-window-update', localizator.window.UpdateLanguage);
