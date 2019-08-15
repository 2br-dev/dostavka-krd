{if count($products)}
<div class="lastViewed">
    <p class="lastViewed_title">{t}Вы недавно смотрели{/t}</p>
    {* <ul>
        {foreach $products as $product}
        {$main_image=$product->getMainImage()}
        <li><a href="{$product->getUrl()}" title="{$product.title}"><img src="{$main_image->getUrl(78,109, 'xy')}" alt="{$main_image.title|default:"{$product.title}"}"/></a></li>
        {/foreach}
    </ul> *}
    <div class="lastViewed_list">
        {foreach $products as $product}
            {$fullstock = $product->getWarehouseFullStock()}
            <div class="lastViewed_item">
                <!--  -->
                <div class="catal_item_barcode">
                    {$product.barcode}
                </div>
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
                <a  href="{$product->getUrl()}" class="catal_img_box">
                    {$main_image=$product->getMainImage()}
                    <img src="{$main_image->getUrl(160,160,axy)}" alt='{$main_image.title|default:"{$product.title}"}' class="catal_main_img {if $fullstock.1 == '0.000'} product-not_inStock{/if}">
                    {if $fullstock.1 !== '0.000'}
                        {foreach $product->getMySpecDir() as $spec}
                            {if $spec.is_label}
                                {if $spec.name == 'Новинки'}
                                    <div class="ticket-new">Новинка</div>
                                {elseif $spec.name == 'Акции'}
                                    <div class="ticket-discount">Акция</div>
                                {else}
                                    <div class="ticket-default">{$spec.name}</div>
                                {/if}                    
                            {/if}
                        {/foreach}
                    {else}
                        <div class="ticket-not_in_stock">Нет в наличии</div>
                    {/if}
                </a>
                <!--  -->
                <a href="{$product->getUrl()}" class="catal_title">{$product.title}</a>
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
                    {if $fullstock.1 !== '0.000'}
                        <a class="catal_basket  addToCart noShowCart {if $product->isOffersUse() || $product->isMultiOffersUse()} {/if}"
                            data-href="{$router->getUrl('shop-front-cartpage', ['add' => $product.id])}"
                            data-add-text="">
                        </a> 
                    {/if}           
                </div>
            </div>
        {/foreach}
    </div>
</div>
{/if}