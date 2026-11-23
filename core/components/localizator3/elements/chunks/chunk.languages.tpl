<ul class="{$outerClass | escape}">
    {foreach $languages as $language}
        <li class="{$language.rowClass | escape}"><a href="{$language.url | escape}">{$language.name | escape}</a></li>
    {/foreach}
</ul>
