
<div class="product_main {if $_SESSION['view'] == 'row'} product_main_rows{/if}">
    {foreach $list as $product}
    {$fullstock = $product->getWarehouseFullStock()}
    <div class="catalog_section_item">
        <div class="catalog_section_item_barcode">
            {$product.barcode}
        </div>
        <div class="catalog_section_item_btns_1">
            <div class="addToFavourites"
                data-id="{$product.id}"
                data-href="{$router->getUrl('catalog-front-favorite')}"
                data-favorite-url="{$router->getUrl('catalog-front-favorite')}">
                <div class="dotBlock addToFavourites " data-id="{$product.id}" data-href="{$router->getUrl('catalog-front-favorite')}" data-favorite-url="{$router->getUrl('catalog-front-favorite')}">   
                <a class="catalog_section_item_btn_1 favorite{if $product->inFavorite()} inFavorite{/if}" ></a>                    
            </div>
            </div>
            {* <a class="catalog_section_item_btn_2"></a> *}
        </div>
        <a href="{$product->getUrl()}" class="catalog_section_item_img_wrap">
            {$main_image=$product->getMainImage()}
            <img src="{$main_image->getUrl(160,160, axy)}" alt="" class="catalog_section_item_img {if $fullstock.1 == '0.000'} product-not_inStock{/if}">
            {if $fullstock.1 !== '0.000'}
                {foreach $product->getMySpecDir() as $spec}
                    {if $spec.is_label}
                        {if $spec.name == 'Новинки'}
                            <div class="ticket-new">Новинка</div>
                        {elseif $spec.name == 'Акции'}
                            <div class="ticket-discount">Акция</div>
                        {else}
                            <div class="ticket-new">{$spec.name}</div>
                        {/if}                    
                    {/if}
                {/foreach}
            {else}
                <div class="ticket-not_in_stock">Нет в наличии</div>
            {/if}    
        </a>
        <div class="catalog_section_item_cont">
            <a href="{$product->getUrl()}" class="catalog_section_item_title">{$product.title}</a>
            <p class="catalog_section_item_discription">{$product.short_description}</p>
            <div class="catalog_section_item_rating">
            {$rating = $product->getRatingBall()}
            {for $star=1 to 5}
            {if $star <= $rating}
                <button class="catalog_section_item_star catalog_section_item_star_full"></button>
            {else}
                <button class="catalog_section_item_star"></button>
            {/if}
            {/for}
            </div>
            <div class="catalog_section_item_bottom">
                <div class="catalog_section_item_price_block">
                    {assign var=last_price value=$product->getOldCost()}
                    {if $last_price > 0}
                        <p class="catalog_section_item_old_price">{$last_price} р.</p>
                    {/if}
                    <p class="catalog_section_item_price">{$product->getCost()} р.</p>
                </div>
                {if $fullstock.1 !== '0.000'}
                    <a 
                        class="catalog_section_item_basket addToCart noShowCart {if $product->isOffersUse() || $product->isMultiOffersUse()} {/if}"
                        data-href="{$router->getUrl('shop-front-cartpage', ['add' => $product.id])}"
                        data-add-text="Добавлено">В корзину
                    </a>
                {/if}
            </div>
        </div>
    </div>
    {/foreach}
</div>

{include file="%THEME%/paginator.tpl"}