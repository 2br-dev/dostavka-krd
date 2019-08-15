{assign var=bc value=$app->breadcrumbs->getBreadCrumbs()}

{if !empty($bc)}
<div class="bread_crumps">
    <div class="bread_crumps_screen">
        {foreach $bc as $item}
        <a href="{$item.href}" class="bread_crumps_link">{$item.title}</a>
        {/foreach}
    </div>
</div>
{/if}