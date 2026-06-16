<select class="{$outerClass}" onchange="if(this.value) window.location.href=this.value">
    {foreach $languages as $language}
        <option value="{$language.url}"{if $language.is_current} selected{/if}>{$language.name}</option>
    {/foreach}
</select>
