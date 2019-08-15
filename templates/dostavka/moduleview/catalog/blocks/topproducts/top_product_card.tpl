{foreach $products as $product}
{* <div class="catal_item">
    <!--  -->
    <div class="catal_btns">
        <div class="addToFavourites"
            data-id="{$product.id}"
            data-href="{$router->getUrl('catalog-front-favorite')}"
            data-favorite-url="{$router->getUrl('catalog-front-favorite')}">
            <a class="catal_btn catal_favor favorite{if $product->inFavorite()} inFavorite{/if}"></a>
        </div>
        <!-- <a class="catal_btn catal_stat"></a> -->
    </div>
    <!--  -->
    <div class="catal_img_box">
        {$main_image=$product->getMainImage()}
        <img src="{$main_image->getUrl(160,160)}" alt='{$main_image.title|default:"{$product.title}"}' class="catal_main_img">
    </div>
    <!--  -->
    <a href="{$product->getUrl()}?palette={$palette}" class="catal_title">{$product.title}</a>
    <!--  -->
    <div class="catal_rating">
        {$rating = $product->getRatingBall()}
        {for $star=1 to 5}
            {if $star <= $rating}
                <button class="catal_star catal_star_full"></button>
            {else}
                <button class="catal_star catal_star"></button>
            {/if}
        {/for}
    </div>
    <!--  -->
    <div class="catal_box_2">
        <p class="catal_price">{$product->getCost()} р.</p>
        <a class="catal_basket  addToCart noShowCart {if $product->isOffersUse() || $product->isMultiOffersUse()} {/if}"
            data-href="{$router->getUrl('shop-front-cartpage', ['add' => $product.id])}"
            data-add-text="">
        </a>
    </a>
    </div>
</div> *}


<div class="prod_sample_box_item">
    <div class="prod_sample_img_box">
        <img src="/catalog/item_1.png" alt="" class="prod_sample_img">
    </div>
    <a href="#" class="prod_sample_name">Название товара (заглушка)</a>
    <div class="prod_sample_rating">
        <button class="prod_sample_rating_star prod_sample_rating_star_full"></button>
        <button class="prod_sample_rating_star"></button>
        <button class="prod_sample_rating_star"></button>
        <button class="prod_sample_rating_star"></button>
        <button class="prod_sample_rating_star"></button>
    </div>
    <div class="prod_sample_bottom">
        <p class="prod_sample_price">500 р.</p>
        <a  class="prod_sample_basket"></a>
    </div>
</div>

{/foreach} 

