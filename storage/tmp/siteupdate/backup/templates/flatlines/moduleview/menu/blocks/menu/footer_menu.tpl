{* Меню в подвале *}
{if $items}
    <div class="column">
        <div class="footer-social_wrapper">
            <div class="column_title"><span>{$root.title|default:"{t}КОМПАНИЯ{/t}"}</span></div>
            <ul class="column_menu">
                {foreach $items as $item}
                    <li {$item.fields->getDebugAttributes()}>
                        <a href="{$item.fields->getHref()}" {if $item.fields.target_blank}target="_blank"{/if}>{$item.fields.title}</a>
                    </li>
                {/foreach}
            </ul>
        </div>
    </div>
{else}
    {include file="%THEME%/block_stub.tpl"  class="block-footer-menu white" do=
    [
        {$this_controller->getSettingUrl()}    => t("Настройте блок")
    ]}
{/if}