<select class="{$outerClass | escape}" onchange="if(this.value) window.location.href=this.value">
    {foreach $languages as $language}
        <option value="{$language.url | escape}" class="{$language.rowClass | escape}"{if $language.is_current} selected{/if}>{$language.name | escape}</option>
    {/foreach}
</select>
