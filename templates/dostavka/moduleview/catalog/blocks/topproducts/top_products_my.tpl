<div class="catalog_section_wrap">
{foreach $products as $product}
    <div class="catalog_section_item">
        <div class="catalog_section_item_btns_1">
            <div class="addToFavourites"
                data-id="{$product.id}"
                data-href="{$router->getUrl('catalog-front-favorite')}"
                data-favorite-url="{$router->getUrl('catalog-front-favorite')}">
                <a class="catalog_section_item_btn_1 favorite{if $product->inFavorite()} inFavorite{/if}"></a>
            </div>
            <a class="catalog_section_item_btn_2"></a>
        </div>
        <div class="catalog_section_item_img_wrap">
            {$main_image=$product->getMainImage()}
            <img src="{$main_image->getUrl(160,160)}" alt="" class="catalog_section_item_img">
        </div>
        <a href="{$product->getUrl()}" class="catalog_section_item_title">{$product.title}</a>
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
            <p class="catalog_section_item_price">{$product->getCost()} р.</p>
            <a 
                class="catalog_section_item_basket addToCart noShowCart {if $product->isOffersUse() || $product->isMultiOffersUse()} {/if}"
                data-href="{$router->getUrl('shop-front-cartpage', ['add' => $product.id])}"
                data-add-text="">
            </a>
        </div>
    </div>
{/foreach}
</div>