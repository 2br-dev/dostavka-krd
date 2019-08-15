{if $items}
    <ul class="menu">    
    {foreach from=$items item=item}
        <li class="{if $item->getChildsCount()}node{/if}{if $item.fields->isAct()} act{/if}" {$item.fields->getDebugAttributes()}>
            <a href="{$item.fields->getHref()}" {if $item.fields.target_blank}target="_blank"{/if}>{$item.fields.title}</a>
        </li>
    {/foreach}
    </ul>
{else}
    {include file="%THEME%/block_stub.tpl"  class="noBack blockSmall blockLeft blockFootMenu" do=[
        {$this_controller->getSettingUrl()}    => t("Настройте блок")
    ]}
{/if}