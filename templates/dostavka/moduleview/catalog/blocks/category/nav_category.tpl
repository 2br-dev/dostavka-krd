{$active = 0}
{$uri = explode('/', trim($smarty.server.REQUEST_URI, '/'))}
{$dir = array_shift($uri)}
{if $dir === 'catalog'}
    {$alias = array_shift($uri)}
    {$active = \DostavkaApi\Model\DostavkaApiApi::getCategoryPaletteByAlias($alias)}
{/if}
{if $dir === 'product'}
    {$alias = array_shift($uri)}
    {$active = \DostavkaApi\Model\DostavkaApiApi::getProductPaletteByAlias($alias)}
{/if}

{$counter= 1}
{foreach $dirlist as $dir}
    {if count($dir.child) > 0}
        <a target="_self" href="{$dir.fields->getUrl()}" target_menu="category_{$counter}" ready="on" class="tn_nav_item {if $active == $counter } tn_nav_item_active{/if}">{$dir.fields.name}</a>
    {else}
        <a target="_self" href="{$dir.fields->getUrl()}" class="tn_nav_item withount_child" ready="on">{$dir.fields.name}</a>
    {/if}
    {$counter=$counter+1}
{/foreach}