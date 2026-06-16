{$OnResourceTVFormPrerender}

{if $formcaption != ''}
    <h2>{$formcaption}</h2>
{/if} 

<input type="hidden" class="mulititems_grid_item_fields" name="mulititems_grid_item_fields" value='{$fields}' />
<input type="hidden" name="action" value='mgr/content/{$formAction}' />
<input type="hidden" name="resource_id" value='{$resource_id}' />

<div id="modx-window-mi-grid-update-{$win_id}-tabs" style="margin-top:0px;">

    {foreach from=$categories item=topcategory name=topcat}

        {if count($topcategory.tabs) > 0}

            {if count($categories) < 2 OR ($smarty.foreach.topcat.first AND $topcategory.print_before_tabs)}
                <div id="modx-tv-tab_{$topcategory.id}-{$win_id}" >
            {else}
                <div id="modx-tv-tab_{$topcategory.id}-{$win_id}" class="x-tab-main" title="{$topcategory.category|default:$_lang.uncategorized|ucfirst}">
            {/if}

                <div id="modx-tv-vtabs_{$topcategory.id}-{$win_id}" class="x-form-label-top">
                {foreach from=$topcategory.tabs item=category name=cat}
                    

                    {if count($category.tvs) > 0}

                        {if count($topcategory.tabs) < 2 OR ($smarty.foreach.cat.first AND $category.print_before_tabs)}
                            <div id="modx-tv-vtab_{$category.id}-{$win_id}" >
                        {else}
                            <div id="modx-tv-vtab_{$category.id}-{$win_id}" class="x-tab" title="{$category.category|default:$_lang.uncategorized|ucfirst}" style="min-height: 45px">
                        {/if}

                        {foreach from=$category.tvs item=tv name='tv'}

                            {if $tv->type EQ "description_is_code"}

                                {$tv->get('formElement')}
                               
                            {elseif $tv->type NEQ "hidden"}
                                {if $tv->type EQ "checkbox"}
                                    <input type="hidden" name="tvlocalizator_{$tv->name}[]" value="" />
                                {/if}
                                <div class="x-form-item x-tab-item {cycle values=",alt"} modx-tv" id="tv{$tv->id}-tr" style="padding: 10px 0 0 ;{if $tv->display EQ "none"}display:none;{/if} ">
                                    <label for="tv{$tv->id}" class="x-form-item-label modx-tv-label" style="width: auto;margin-bottom:2px;">
                                        <div class="modx-tv-label-title"> 
                                            {if $showCheckbox}<input type="checkbox" name="tv{$tv->id}-checkbox" class="modx-tv-checkbox" value="1" />{/if}
                                            <span class="modx-tv-caption" id="tv{$tv->id}-caption">{$tv->caption}</span>
                                        </div>

                                        {if $tv->description}<span class="modx-tv-label-description">{$tv->description}</span>{/if}
                                    </label>
                                    {if $tv->inherited}<br /><span class="modx-tv-inherited">{$_lang.tv_value_inherited}</span>{/if}
                                    <div class="x-form-clear-left"></div>
                                    <div class="x-form-element modx-tv-form-element" style="padding-left: 0px;">
                                        <input type="hidden" id="tvdef{$tv->id}" value="{$tv->default_text|escape}" />
                                        {$tv->get('formElement')}
                                    </div>
                                </div>
                                <script type="text/javascript">{literal}Ext.onReady(function() { new Ext.ToolTip({{/literal}target: 'tv{$tv->id}-caption',html: '[[*{$tv->name}]]'{literal}});});{/literal}</script>
                            {else}
                                <input type="hidden" id="tvdef{$tv->id}" value="{$tv->default_text|escape}" />
                                {$tv->get('formElement')}
                            {/if}
                        {/foreach}

                        </div>
                        
                    {/if}
                {/foreach}
                </div>
                {if count($topcategory.tabs) > 1}
                    {literal}
                        <script type="text/javascript">
                        // <![CDATA[
                        Ext.onReady(function() {    

                            MODx.load({
                                xtype: 'modx-vtabs'
                                ,applyTo: '{/literal}modx-tv-vtabs_{$topcategory.id}-{$win_id}{literal}'
                                ,activeTab: 0
                                ,autoTabs: true
                                ,border: false
                                ,plain: true
                                ,width: '95%'
                                ,hideMode: 'offsets'
                                ,deferredRender: false
                                ,id: '{/literal}modx-tv-vtab_{$topcategory.id}-{$win_id}{literal}'
                                ,defaults: {
                                    bodyStyle: 'padding: 5px;'
                                    ,autoHeight: true
                                }
                                ,padding: '0 0 0 5px'
                                ,headerCfg: {
                                    tag: 'div'
                                    ,cls: 'x-tab-panel-header vertical-tabs-header'
                                    ,id: '{/literal}modx-tv-vtab_{$topcategory.id}-header-{$win_id}{literal}'
                                }
                            });
                        });    
                        // ]]>
                        </script>
                    {/literal}
                {/if}
            </div>
        {/if}
    {/foreach}
</div>

{if count($categories) > 1}
    {literal}
        <script type="text/javascript">
        // <![CDATA[
        Ext.onReady(function() {    

            MODx.load({
                xtype: 'modx-tabs'
                ,applyTo: '{/literal}modx-window-mi-grid-update-{$win_id}-tabs{literal}'
                ,autoTabSelector: 'div.x-tab-main'
                ,activeTab: 0
                ,autoTabs: true
                ,border: false
                ,plain: true
                ,anchor: '100%'
                ,hideMode: 'offsets'
                ,defaults: {
                    bodyStyle: 'padding: 5px;'
                    ,autoHeight: true
                    ,autoWidth: true
                }
                ,deferredRender: false
            });
        });    
        // ]]>
        </script>
        <style>
            {/literal}#modx-window-mi-grid-update-{$win_id}-tabs{literal} .vertical-tabs-header .x-tab-strip>li:not(.x-tab-edge){
                min-height:45px;
            }
        
        </style>
    {/literal}
{/if}

{$OnResourceTVFormRender}

<br class="clear" />