{foreach $menu_level as $item}
<li class="{if $item->getChildsCount()}node{/if}{if $item.fields->isAct()} act{/if}{if $item@first} first{/if}" {$item.fields->getDebugAttributes()}>
    <a href="{$item.fields->getHref()}" {if $item.fields.target_blank}target="_blank"{/if}>{$item.fields.title}</a>
    {if $item->getChildsCount()}
    <ul>
        {include file="blocks/menu/branch.tpl" menu_level=$item.child}
    </ul>
    {/if}
</li>
{/foreach}