{addcss file="cart.css"}
{addcss file="bread_crumps.css"}

{$shop_config = ConfigLoader::byModule('shop')}
{$catalog_config = ConfigLoader::byModule('catalog')}
{$product_items = $cart->getProductItems()}
{$floatCart = (int)$smarty.request.floatCart}

{if $floatCart}
    <div class="viewport" id="cartItems">
        <div class="cartFloatBlock">
            <i class="corner"></i>
            {if !empty($cart_data.items)}
            <form method="POST" action="{$router->getUrl('shop-front-cartpage', ["Act" => "update", "floatCart" => $floatCart])}" id="cartForm">
                <div class="cartHeader">
                    <a href="{$router->getUrl('shop-front-cartpage', ["Act" => "cleanCart", "floatCart" => $floatCart])}" class="clearCart">{t}очистить корзину{/t}</a>
                    <a class="iconX closeDlg"></a>
                    <img src="{$THEME_IMG}/loading.gif" class="loader">
                </div>
                {hook name="shop-cartpage:products" title="{t}Корзина:товары{/t}" product_items=$product_items}
                    <div class="scrollBox">
                        <table class="items cartTable">
                            <tbody>
                                {foreach $cart_data.items as $index => $item}
                                    {$product = $product_items[$index].product}
                                    {$cartitem = $product_items[$index].cartitem}
                                    {if !empty($cartitem.multioffers)}
                                        {$multioffers=unserialize($cartitem.multioffers)}
                                    {/if}
                                <tr data-id="{$index}" data-product-id="{$cartitem.entity_id}" class="cartitem{if $item@first} first{/if}">
                                    <td class="image"><a href="{$product->getUrl()}"><img src="{$product->getOfferMainImage($cartitem.offer, 81, 81, 'axy')}" alt="{$product.title}"/></a></td>
                                    <td class="title"><a href="{$product->getUrl()}">{$cartitem.title}</a>
                                            {$offer_barcode=$product->getBarcode($cartitem.offer)}
                                            {if $offer_barcode}{* Артикул комлпектации *}
                                                <p class="barcode">{t}Артикул:{/t} {$product->getBarcode($cartitem.offer)}</p>
                                            {elseif $product.barcode} {* Артикул товара *}
                                                <p class="barcode">{t}Артикул:{/t} {$product.barcode}</p>
                                            {/if}
                                            
                                             {if $product->isMultiOffersUse()}
                                                <div class="multiOffers">
                                                    {foreach $product.multioffers.levels as $level}
                                                        {if !empty($level.values)}
                                                            <div class="multiofferTitle">{if $level.title}{$level.title}{else}{$level.prop_title}{/if}</div>
                                                            <select name="products[{$index}][multioffers][{$level.prop_id}]" data-prop-title="{if $level.title}{$level.title}{else}{$level.prop_title}{/if}">
                                                                {foreach $level.values as $value}
                                                                    <option {if $multioffers[$level.prop_id].value == $value.val_str}selected="selected"{/if} value="{$value.val_str}">{$value.val_str}</option>
                                                                {/foreach}
                                                            </select>
                                                        {/if}
                                                    {/foreach}
                                                    {if $product->isOffersUse()}
                                                        {foreach from=$product.offers.items key=key item=offer name=offers}
                                                            <input id="offer_{$key}" type="hidden" name="hidden_offers" class="hidden_offers" value="{$key}" data-info='{$offer->getPropertiesJson()}' data-num="{$offer.num}"/>
                                                            {if $cartitem.offer==$key}
                                                                <input type="hidden" name="products[{$index}][offer]" value="{$key}"/>
                                                            {/if}
                                                        {/foreach}
                                                    {/if}
                                                </div>
                                            {elseif $product->isOffersUse()}
                                                <div class="offers">
                                                    <select name="products[{$index}][offer]" class="offer">
                                                        {foreach from=$product.offers.items key=key item=offer name=offers}
                                                            <option value="{$key}" {if $cartitem.offer==$key}selected{/if}>{$offer.title}</option>
                                                        {/foreach}
                                                    </select>
                                                </div>
                                            {/if}
                                    </td>
                                    <td class="price">
                                        <div class="amount">
                                            <input type="hidden" min="{$product->getAmountStep()}" step="{$product->getAmountStep()}" class="fieldAmount" value="{$cartitem.amount}" name="products[{$index}][amount]"/>
                                            {if !$cartitem->getForbidChangeAmount()}
                                                <a class="dec" data-amount-step="{$product->getAmountStep()}"></a>
                                            {/if}
                                            <span class="num" title="{t}Количество{/t}">{$cartitem.amount}</span>
                                            
                                            <span class="unit">
                                            {if $catalog_config.use_offer_unit}
                                                {$product.offers.items[$cartitem.offer]->getUnit()->stitle}
                                            {else}
                                                {$product->getUnit()->stitle|default:"шт."}
                                            {/if}
                                            </span>

                                            {if !$cartitem->getForbidChangeAmount()}
                                                <a class="inc" data-amount-step="{$product->getAmountStep()}"></a>
                                            {/if}
                                        </div>
                                        <div class="cost">{$item.cost}</div>
                                        <div class="discount">
                                            {if $item.discount>0}
                                                {t}скидка{/t} {$item.discount}
                                            {/if}
                                        </div>
                                        <div class="error">{$item.amount_error}</div>
                                    </td>
                                    <td class="remove">
                                        {if !$cartitem->getForbidRemove()}
                                            <a href="{$router->getUrl('shop-front-cartpage', ["Act" => "removeItem", "id" => $index, "floatCart" => $floatCart])}" title="{t}Удалить товар из корзины{/t}" class="iconRemove"></a>
                                        {/if}
                                    </td>
                                </tr>
                                    {assign var=concomitant value=$product->getConcomitant()}
                                    {foreach from=$item.sub_products key=id item=sub_product_data}
                                        {assign var=sub_product value=$concomitant[$id]}
                                        <tr>
                                            <td colspan="2" class="title">
                                                <label>
                                                    <input 
                                                        class="fieldConcomitant" 
                                                        type="checkbox" 
                                                        name="products[{$index}][concomitant][]" 
                                                        value="{$sub_product->id}"
                                                        {if $sub_product_data.checked}
                                                            checked="checked"
                                                        {/if}
                                                        >
                                                    {$sub_product->title}
                                                </label>
                                            </td>
                                            <td class="price">
                                                {if $shop_config.allow_concomitant_count_edit}
                                                    <div class="amount">
                                                        <input type="hidden" min="{$sub_product->getAmountStep()}" step="{$sub_product->getAmountStep()}" class="fieldAmount concomitant" data-id="{$sub_product->id}" value="{$sub_product_data.amount}" name="products[{$index}][concomitant_amount][{$sub_product->id}]"> 
                                                        <a class="dec" data-amount-step="{$sub_product->getAmountStep()}"></a>
                                                        <span class="num" title="{t}Количество{/t}">{$sub_product_data.amount}</span> {$product->getUnit()->stitle|default:t("шт.")}
                                                        <a class="inc" data-amount-step="{$sub_product->getAmountStep()}"></a>
                                                    </div>
                                                {else}
                                                    <div class="amount" title="{t}Количество{/t}">{$sub_product_data.amount} {$sub_product->getUnit()->stitle|default:t("шт.")}</div>
                                                {/if}
                                                <div class="cost">{$sub_product_data.cost}</div>
                                                <div class="discount">
                                                    {if $sub_product_data.discount>0}
                                                        {t}скидка{/t} {$sub_product_data.discount}
                                                    {/if}
                                                </div>
                                                <div class="error">{$sub_product_data.amount_error}</div>
                                            </td>
                                            <td class="remove"></td>
                                        </tr>
                                    {/foreach}
                                {/foreach}
                                {foreach from=$cart->getCouponItems() key=id item=item}
                                <tr data-id="{$index}" data-product-id="{$cartitem.entity_id}" class="cartitem couponLine">
                                    <td colspan="2" class="title">{t}Купон на скидку{/t} {$item.coupon.code}</td>
                                    <td class="price"></td>
                                    <td class="remove"><a href="{$router->getUrl('shop-front-cartpage', ["Act" => "removeItem", "id" => $id, "floatCart" => $floatCart])}" title="{t}Удалить скидочный купон{/t}" class="iconRemove"></a></td>
                                </tr>
                                {/foreach}
                            </tbody>
                        </table>
                    </div>
                {/hook}
                {hook name="shop-cartpage:summary" title="{t}Корзина:итог{/t}"}
                    <div class="cartFooter{if $coupon_code} onPromo{/if}">
                        <a class="hasPromo" onclick="$(this).parent().toggleClass('onPromo')">{t}У меня есть промо-код{/t}</a>
                        <div class="promo">
                            Промо-код: &nbsp;<input type="text" name="coupon" value="{$coupon_code}" class="couponCode">&nbsp; 
                            <a class="applyCoupon">{t}применить{/t}</a>
                        </div>
                    </div>
                {/hook}                    
                {hook name="shop-cartpage:bottom" title="{t}Корзина:подвал{/t}"}
                    <div class="cartError {if empty($cart_data.errors)}hidden{/if}">
                        {foreach from=$cart_data.errors item=error}
                            {$error}<br>
                        {/foreach}
                    </div>
                {/hook}
            </form>
            {* Покупка в один клик в корзине *}
            {if $THEME_SETTINGS.enable_one_click_cart}            
                <a href="JavaScript:;" class="toggleOneClickCart">{t}Заказать по телефону{/t}</a>
                {moduleinsert name="\Shop\Controller\Block\OneClickCart"}
            {/if}
            {else}
            <div class="emptyCart">
                <a class="iconX closeDlg"></a>
                {t}В корзине нет товаров{/t}
            </div>            
            {/if}
        </div>
    </div>
{else}
    <!--  -->
    <!--  -->
    <!--  -->
    <div class="cartPage cart_screen" id="cartItems">
        <!--  -->
        <div class="cart_col_left">
            <div class="cart_box_1">
                <p class="cart_title_1">{t}Корзина{/t}</p>
                {if !empty($cart_data.items)}
                <a href="{$router->getUrl('shop-front-cartpage', ["Act" => "cleanCart"])}" class="cart_btn_1 clearCart">{t}Очистить корзину{/t}</a>
                {/if}
            </div>
            <!--  -->
            {if !empty($cart_data.items)}
            <form method="POST" action="{$router->getUrl('shop-front-cartpage', ["Act" => "update"])}" id="cartForm" class="formStyle cart_form">
                <input type="submit" class="hidden">
                {hook name="shop-cartpage:products" title="{t}Корзина:товары{/t}"}
                <div class="scrollCartWrap">
                    <!--  -->
                    <div class="cart_table">
                        {foreach $cart_data.items as $index => $item}
                        {$product=$product_items[$index].product}
                        {$cartitem=$product_items[$index].cartitem}
                        {if !empty($cartitem.multioffers)}
                                {$multioffers=unserialize($cartitem.multioffers)} 
                        {/if} 
                        <div class="cart_item" data-id="{$index}" data-product-id="{$cartitem.entity_id}" class="cartitem{if $smarty.foreach.items.first} first{/if}">
                            <a href="{$product->getUrl()}" class="cart_item_img_box">
                                <img src="{$product->getOfferMainImage($cartitem.offer, 160, 160)}" alt="{$product.title}" class="cart_item_img">
                            </a>
                            <!--  -->
                            <div class="cart_item_wrap">
                                <div class="cart_item_name_box">
                                    <a href="{$product->getUrl()}" class="cart_item_name">{$product.title}</a>
                                </div>
                                <!--  -->
                                <div class="cart_item_counter_box amount">
                                    <input type="number" min="{$product->getAmountStep()}" step="{$product->getAmountStep()}" class="inp fieldAmount cart_input_count" value="{$cartitem.amount}" name="products[{$index}][amount]">
                                    <div class="incdec">
                                        <a class="cart_item_counter_btn cart_item_counter_minus dec" data-amount-step="{$product->getAmountStep()}"></a>
                                        <p class="cart_item_counter_label"><span>{$cartitem.amount}</span> шт.</p>
                                        <a class="cart_item_counter_btn cart_item_counter_plus inc" data-amount-step="{$product->getAmountStep()}"></a>
                                    </div>
                                </div>
                                <!--  -->
                                <div class="cart_item_wrap_inner">
                                    <div class="cart_item_price_box ">
                                        <p class="cart_item_price price">{$item.cost}</p>
                                    </div>
                                    <!--  -->
                                    <div class="remove cart_item_remove_box">
                                        <a title="{t}Удалить{/t}" class="removeItem cart_item_remove" href="{$router->getUrl('shop-front-cartpage', ["Act" => "removeItem", "id" => $index])}">Удалить</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        {/foreach}
                    </div>
                    <!--  -->
                    {hook name="shop-cartpage:summary" title="{t}Корзина:итог{/t}"}
                    <div class="cart_result">
                        <div class="cart_result_row">
                            <p class="cart_result_label_bold">Товары на сумму:</p>
                            <p class="cart_result_label_bold">{$cart_data.total}</p>
                        </div>
                        <!-- <div class="cart_result_row">
                            <p class="cart_result_label">Скидка:</p>
                            <p class="cart_result_label">0 р.</p>
                        </div> -->
                        <div class="cart_result_row">
                            <p class="cart_result_label_bold">Итого:</p>
                            <p class="cart_result_label_bold">{$cart_data.total}</p>
                        </div>
                    </div>
                    {/hook}
                </div>
                {/hook}
                <!--  -->    
                {hook name="shop-cartpage:bottom" title="{t}Корзина:подвал{/t}"}
                    {if !empty($cart_data.errors)}
                    <div class="cartErrors">
                        {foreach $cart_data.errors as $error}
                            {$error}<br>
                        {/foreach}
                    </div>
                    {/if}
                    <div class="actionLine cart_bottom">
                        <!-- <a href="JavaScript:;" class="button continue">{t}Продолжить покупки{/t}</a> -->
                        <a href="{$router->getUrl('shop-front-checkout')}" class="cart_bottom_submit submit button color{if $cart_data.has_error} disabled{/if}">{t}Оформить заказ{/t}</a>
                        <!-- {if $THEME_SETTINGS.enable_one_click_cart}            
                        <a href="JavaScript:;" class="button toggleOneClickCart">{t}Заказать по телефону{/t}</a>
                        {/if} -->
                    </div>
                {/hook}
            </form>
            {* Покупка в один клик в корзине *}
            {if $THEME_SETTINGS.enable_one_click_cart}            
                {moduleinsert name="\Shop\Controller\Block\OneClickCart"}
            {/if}
            {else}
            <div class="emptyCart empty_cart">
                <p class="empty_cart_title">{t}В корзине нет товаров{/t}</p>
            </div>                    
            {/if}
        </div>
        <!--  -->
        <div class="cart_col_right">
            <div class="right_col">
                <div class="right_icon_box">
                    <img src="{$THEME_IMG}/dostavka/icons/icon_cart_1.svg" alt="" class="right_img">
                </div>
                <p class="right_title">Доставка</p>
                <p class="right_text">При заказе от 1000 рублей в пределах Краснодара доставка бесплатная.</p>
                <p class="right_text">При заказе до 1000 рублей стоимость доставки в пределах Краснодара составляет 200 рублей.</p>
                <div class="right_spacer"></div>
            </div>
            <!--  -->
            <div class="right_col">
                <div class="right_icon_box">
                    <img src="{$THEME_IMG}/dostavka/icons/icon_cart_2.svg" alt="" class="right_img">
                </div>
                <p class="right_title">Время доставки</p>
                <p class="right_text">Заказы, сформированные до 15:00, будут доставляться на следующий день с 15:00 до 22:00, а после 15:00 - через день с 9:00 до 15-00. Желательное время доставки можно указать в комментарии при оформлении заказа.</p>
                <div class="right_spacer"></div>
            </div>
            <!--  -->
            <div class="right_col">
                <div class="right_icon_box">
                    <img src="{$THEME_IMG}/dostavka/icons/icon_cart_3.svg" alt="" class="right_img">
                </div>
                <p class="right_title">Оплата</p>
                <p class="right_text">Вы можете оплатить заказ одним из четырех способов: </p>
                <p class="right_text">Наличными курьеру</p>
                <p class="right_text">Картой курьеру</p>
                <p class="right_text">Безналичным расчетом по выставленному счету</p>
                <p class="right_text">Оплата на сайте</p>
            </div>
            <!--  -->
            <div class="cart_row">
                <p class="right_text_2">Подробнее об условиях доставки и оплаты Вы можете</p>
                <a href="/dostavka/" class="right_link">прочитать тут</a>
            </div>    
        </div>
        <!--  -->
    </div>
{/if}