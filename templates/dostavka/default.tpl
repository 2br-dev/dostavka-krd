{addcss file="bread_crumps.css"}
{addcss file="product_card.css"}
<!--  -->
{extends file="%THEME%/wrapper.tpl"}
{block name="content"}
    <div class="box">
        <div class="box_center">
            {moduleinsert name="\Main\Controller\Block\Breadcrumbs"}
            {$app->blocks->getMainContent()}
        </div>
    </div>
{/block}