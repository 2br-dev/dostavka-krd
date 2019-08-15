
<div class="fav_body">
{foreach $list as $product}
    <div class="fav_item">
        <!--  -->
        <div class="fav_btn_box">
            <div class="dotBlock addToFavourites " data-id="{$product.id}" data-href="{$router->getUrl('catalog-front-favorite')}" data-favorite-url="{$router->getUrl('catalog-front-favorite')}">   
                <a class="fav_btn favorite{if $product->inFavorite()} inFavorite{/if}" ></a>                    
            </div>
        </div>
        <!--  -->
        <a href="{$product->getUrl()}" class="fav_img_box">
            {$main_image=$product->getMainImage()}
            <img src="{$main_image->getUrl(160,160, axy)}" alt="{$product.title}" class="fav_img">
        </a>
        <!--  -->
        <div class="fav_title_box">
            <a href="{$product->getUrl()}" class="fav_name">{$product.title} - {$categoryId}</a>
        </div>
        <!--  -->
        <div class="fav_rating_box">
            {$rating = $product->getRatingBall()}
            {for $star=1 to 5}
            {if $star <= $rating}
            <button class="fav_star fav_star_full"></button>
            {else}
            <button class="fav_star"></button>
            {/if}
            {/for}
        </div>
        <!--  -->
        <div class="fav_bottom_box">
            <p class="fav_price">{$product->getCost()} р.</p>
            <a 
                class="fav_basket addToCart noShowCart {if $product->isOffersUse() || $product->isMultiOffersUse()} {/if}"
                data-href="{$router->getUrl('shop-front-cartpage', ['add' => $product.id])}"
                data-add-text="Добавлено">В корзину
            </a>
        </div>
        <!--  -->
    </div>
{/foreach}
</div>