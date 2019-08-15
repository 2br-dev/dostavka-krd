{addjs file="jcarousel/jquery.jcarousel.min.js"}
{addjs file="jquery.changeoffer.js?v=2"}
{addjs file="jquery.zoom.min.js"}
{addjs file="product.js"}
{addjs file="%catalog%/jquery.favorite.js"}
{assign var=shop_config value=ConfigLoader::byModule('shop')}
{assign var=check_quantity value=$shop_config.check_quantity}
{assign var=catalog_config value=$this_controller->getModuleConfig()} 
{if $product->isVirtualMultiOffersUse()} {* Если используются виртуальные многомерные комплектации *}
    {addjs file="jquery.virtualmultioffers.js"}
{/if} 
{$product->fillOffersStockStars()} {* Загружаем сведения по остаткам на складах *}
{$fullstock = $product->getWarehouseFullStock()}
<!--  -->
{addjs file="product_castom.js"}
{addcss file="comments.css"}
{addcss file="one_click.css"}
<!--  -->
<link rel="stylesheet" href="/templates/dostavka/resource/fancybox/jquery.fancybox.min.css"  type="text/css" media="screen">
<script type="text/javascript" src="/templates/dostavka/resource/fancybox/jquery.fancybox.min.js"></script>
<!--  -->
<div class="prod_mobile_bar">
    <a href="{$smarty.server.HTTP_REFERER}" class="prod_mobile_bar_btn"></a>
</div>
<pre>
</pre>
<div class="prod_body">
    <!--  -->
    <section class="prod_img_body">
        <div class="prod_img_list">          
            {$images=$product->getImages()}
            {$limit = 0}
            {if count($images) > 0}
            {foreach $images as $key => $image}
                {if $limit < 3}
                <a url-href="{$image->getUrl(360, 360, axy)}" url-href-fb="{$image->getUrl(600, 600, axy)}" class="prod_img_link {if $limit == 0}prod_img_link_active{/if}">
                    <img src="{$image->getUrl(200, 200, axy)}" alt="{$product.title}" class="prod_img">
                </a>
                {/if}
            {$limit = $limit + 1}
            {/foreach}
            {/if}
        </div>
        <!--  -->
        <div class="prod_img_wrap">
            {$main_image=$product->getMainImage()}
            <a id="item_fb" href="{$main_image->getUrl(600,600, axy)}" data-fancybox >
                <img class="prod_img_main {if $fullstock.1 == '0.000'} product-not_inStock{/if}" src="{$main_image->getUrl(360,360, axy)}" alt="" />  
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
            <!-- <img src="{$main_image->getUrl(360,360)}" alt="{$main_image.title|default:"{$product.title}"}" class="prod_img_main"> -->
        </div>
    </section>
    <!--  -->
    <section class="prod_info_body">
        <!--  -->
        <div class="prod_panel_btns">
            {* <a class="prod_panel_btn prod_panel_btn_stat compare {if $product->inCompareList()} inCompare{/if}"></a> *}
            <div class="dotBlock addToFavourites " data-id="{$product.id}" data-href="{$router->getUrl('catalog-front-favorite')}" data-favorite-url="{$router->getUrl('catalog-front-favorite')}">   
                <a class="prod_panel_btn prod_panel_btn_favor favorite{if $product->inFavorite()} inFavorite{/if}" ></a>                    
            </div>
        </div>
        <!--  -->
        <p class="prod_info_main_title">{$product.title}</p>
        <!--  -->
        <div class="prod_info_rating_body">
            {$rating = $product->getRatingBall()}
            {for $star=1 to 5}
            {if $star <= $rating}
            <button class="prod_info_rating_star prod_info_rating_star_full"></button>
            {else}
            <button class="prod_info_rating_star"></button>
            {/if}
            {/for}
        </div>
        <!--  -->
        <p class="prod_info_description">{$product.description_short}</p>
        <!--  -->
        <p class="prod_info_articul">Артикул: <span>{$product.barcode}</span></p>
        <!--  -->
        <div class="prod_info_btns_service">
            <div class="prod_info_quantity_body">
                <div class="prod_info_cost">
                    {assign var=last_price value=$product->getOldCost()}
                    {if $last_price > 0}
                        <p class="prod_info_old_price">{$last_price} р.</p>
                    {/if}
                    <p class="prod_info_price">{$product->getCost()} р.</p>
                </div>
                <div class="prod_info_btn_wrap_2">
                    <a class="prod_info_btn_2 prod_info_btn_3 BTN DEC" data-amount-step="{$product->getAmountStep()}"></a>
                    <p id="amount_mask" class="prod_info_quantity_count">{($product.min_order == '' || $product.min_order == 0) ? 1 : $product.min_order} шт.</p>
                    <input type="hidden" value="{($product.min_order == '' || $product.min_order == 0) ? 1 : $product.min_order}" id="amount">
                    <a class="prod_info_btn_2 prod_info_btn_4 BTN INC" data-amount-step="{$product->getAmountStep()}"></a>
                </div>
                {* <div class="amountBlock amount">
                    <a class="minusButton amountButton fleft dec" data-id="{$product.id}"> - </a>
                    <input type="text" name="amount" class=" fleft fieldAmount" value="120" id="{$product.id}">
                    <a class="plusButton amountButton fleft" data-id="{$product.id}"> + </a>                
                </div> *}
            </div>
            <!--  -->
            {if $fullstock.1 !== '0.000'}
                <div class="prod_info_btns_wrap">
                    <a id="amount_btn" class="prod_info_btn prod_info_btn_type_1 button addToCart noShowCart" data-href="{$router->getUrl('shop-front-cartpage', ["add" => $product.id])}" data-add-text="Добавлено">В корзину</a>
                    <a id="amount_btn_onClick" class="prod_info_btn prod_info_btn_type_2 button buyOneClick inDialog" data-href="{$router->getUrl('catalog-front-oneclick',["product_id"=>$product.id, 'amount' => ($product.min_order == '' || $product.min_order == 0) ? 1 : $product.min_order])}" title="Купить в 1 клик">Купить в 1 клик</a>
                </div>
            {else}
                <div class="not-in-stock">Нет в наличии</div>
            {/if}
        </div>
    </section>
    <!--  -->
</div>
<!--  -->
<section class="prod_dop">
    <div class="prod_samples">
        <div class="prod_sample_row">
            {* <p class="prod_sample_main_title">Похожие товары</p> *}
            {* <div class="prod_sample_box"> *}
                {moduleinsert name="\Catalog\Controller\Block\Recommended"}
            {* </div> *}
        </div>
        <!--  -->
        <div class="prod_sample_row">
            {* <p class="prod_sample_main_title">С этим товаром покупают</p> *}
            {* <div class="prod_sample_box"> *}
                {moduleinsert name="\Shop\Controller\Block\Concomitant" indexTemplate="blocks\concomitant\prod_concomitant.tpl"}
            {* </div> *}
        </div>
    </div>
    <!--  -->
    <div class="prod_skills">
        <div class="prod_skills_about">
            <p class="prod_skills_about_title">О товаре</p>
            <div class="prod_skills_about_text">
                {$product.description}
            </div>
            {if !empty($product.properties)}
            <div class="prod_about_specific">
                <p class="prod_about_specific_title">Характеристики товара</p>
                {foreach $product.properties as $data}
                    {foreach $data.properties as $prop}
                    {if !empty($prop.value_in_string)}
                    <div class="prod_about_specific_row">
                        <p class="prod_about_specific_label">{$prop.title}</p>
                        <p class="prod_about_specific_label">{$prop.value_in_string[0]} {$prop.unit}</p>              
                    </div>
                    {/if}
                    {/foreach}  
                {/foreach}
            </div>
            {/if}
        </div>
        <!--  -->
        <!--  -->
        <div class="prod_skills_reviews"> 
            {moduleinsert name="\Comments\Controller\Block\Comments" type="\Catalog\Model\CommentType\Product"}    
        </div>
        <!--  -->
    </div>
</section>
<!--  -->
<script>
    $(document).ready(function() {
        $('#item_fb').fancybox({})
    })
</script>