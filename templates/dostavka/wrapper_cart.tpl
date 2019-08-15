{extends file="%THEME%/wrapper.tpl"}
{block name="content"}
    <div class="cart_conteiner">
        {moduleinsert name="\Main\Controller\Block\Breadcrumbs"}
        {$app->blocks->getMainContent()}
    </div>
{/block}
{block name="fixedCart"}{/block} {* Исключаем плавающую корзину *}