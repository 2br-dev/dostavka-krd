{addcss file="index.css"}
{addjs file="index.js"}

<!-- определяет текущую палитру сайта -->
{assign var="palette" value="none"}
{if $smarty.get.palette == "1" || $smarty.get.palette == "2" || $smarty.get.palette == "3" || $smarty.get.palette == "4" || $smarty.get.palette == "5" || $smarty.get.palette == "6" || $smarty.get.palette == "7"}
    {assign var="palette" value=$smarty.get.palette}
{/if}
<!--  -->
{extends file="%THEME%/wrapper.tpl"}
{block name="content"}
    <div class="content">
        <!-- Слайдер с банерами -->
        {moduleinsert name="\Banners\Controller\Block\Slider"}
        <!-- /Слайдер с банерами -->
        <!--  -->
        <!-- кнопки (на десктопе сверху) -->
        {include file="%THEME%/home_buttons_top.tpl"}
        <!-- /кнопки -->
        <!--  -->
        <!-- каталог -->
        {include file="%THEME%/home_catalog.tpl"}
        <!-- /каталог -->
        <!--  -->
        <!-- кнопки (на мобайле снизу) -->
        {include file="%THEME%/home_buttons_bottom.tpl"}
        <!-- /кнопки -->
        <!-- отступ -->
        <div class="content_space"></div>
        <!-- /отступ -->
        <!-- Недавно просмотренные товары -->
        <div class="index_last_view">
            <div class="index_last_view_content">
                {moduleinsert name="\Catalog\Controller\Block\LastViewed" indexTemplate="%THEME%/moduleview/catalog/blocks/lastviewed/products_index.tpl" pageSize="6"}
            </div>
        </div>
        <!-- /Недавно просмотренные товары -->
        <div class="content_space"></div>
    </div>
    
    {* <div class="box mt40"> *}
        {* Лидеры продаж *}
        {* {moduleinsert name="\Catalog\Controller\Block\TopProducts" dirs="samye-prodavaemye-veshchi" pageSize="5"} *}
             
        {* <div class="oh mt40"> *}
            {* <div class="left"> *}
                {* Новости *}
                {* {moduleinsert name="\Article\Controller\Block\LastNews" indexTemplate="blocks/lastnews/lastnews.tpl" category="2" pageSize="4"} *}
            {* </div> *}
            {* <div class="right"> *}
                {* Оплата и возврат *}
                {* {moduleinsert name="\Article\Controller\Block\Article" indexTemplate="blocks/article/main_payment_block.tpl" article_id="molodezhnaya--glavnaya--ob-oplate"} *}
                
                {* Доставка *}
                {* {moduleinsert name="\Article\Controller\Block\Article" indexTemplate="blocks/article/main_delivery_block.tpl" article_id="molodezhnaya--glavnaya--o-dostavke"} *}
            {* </div> *}
        {* </div> *}
        {* Товары во вкладках *}
        {* {moduleinsert name="\Catalog\Controller\Block\ProductTabs" categories=["populyarnye-veshchi", "novye-postupleniya"] pageSize=6} *}
        
        {* Бренды *}
        {* {moduleinsert name="\Catalog\Controller\Block\BrandList"} *}

    {* </div> *}
{/block}