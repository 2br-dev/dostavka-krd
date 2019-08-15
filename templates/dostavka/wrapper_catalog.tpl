{addcss file="catalog.css"}
{addcss file="bread_crumps.css"}
{addcss file="paginator.css"}
{addjs file="catalog.js"}

{extends file="%THEME%/wrapper.tpl"}
{block name="content"}
    <div class="catal">
        {* Хлебные крошки *}
        {moduleinsert name="\Main\Controller\Block\BreadCrumbs"}
        <div class="catal_filter_panel_mobile">
            <button class="catal_filter_panel_btn_1" id="call_filter">Фильтры</button>
            <button class="catal_filter_panel_btn_2" id="call_sort"></button>
        </div>
        <div class="catal_screen">
            <div class="catal_left">
                {moduleinsert name="\Catalog\Controller\Block\SideFilters"}
            </div>
            <div class="catal_right">
                {$app->blocks->getMainContent()}
            </div>
        </div>
        <div class="lastViewed_catalog">
            <div class="lastViewed_catalog_content">
                {moduleinsert name="\Catalog\Controller\Block\LastViewed" pageSize="5"}
            </div>
        </div>
    </div>    
{/block}

<!-- {$app->blocks->getMainContent()} -->
<!-- {* Фильтр *}
                
                
{* Просмотренные товары *}
{moduleinsert name="\Catalog\Controller\Block\LastViewed" pageSize="8"}

{* Новости *}
{moduleinsert name="\Article\Controller\Block\LastNews" indexTemplate="blocks/lastnews/lastnews.tpl" category="2" pageSize="5"} -->