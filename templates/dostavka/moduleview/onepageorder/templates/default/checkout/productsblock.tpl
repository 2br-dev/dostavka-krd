{assign var=catalog_config value=ConfigLoader::byModule('catalog')}
<div class="onePageProductsBlock">
    <div id="basket">
        <div class="cartInfo">
            {assign var=products value=$cart->getProductItems()}
            {assign var=cartdata value=$cart->getCartData()}
            <table class="orderBasket">
                <tbody class="head">
                    <tr>
                        <td></td>
                        <td>{t}Количество{/t}</td>
                        <td>{t}Цена{/t}</td>
                    </tr>
                </tbody>
                <tbody>
                    {foreach from=$products item=item key=n name="basket"}
                    <tr {if $smarty.foreach.basket.first}class="first"{/if} data-id="{$item.obj.id}">
                        <td>
                            {assign var=barcode value=$item.product->getBarCode($item.cartitem.offer)}
                            {assign var=offer_title value=$item.product->getOfferTitle($item.cartitem.offer)}          
                            {assign var=multioffer_titles value=$item.cartitem->getMultiOfferTitles()}
                            <a href="{$item.product->getUrl()}" target="_blank" class="prod-name">{$item.product.title}</a>
                            <div class="codeLine">
                                {if $barcode != ''}{t}Артикул{/t}:<span class="value">{$barcode}</span>&nbsp;&nbsp;{/if}
                                {if $multioffer_titles || ($offer_title && $item.product->isOffersUse())}
                                    <div class="multioffersWrap">
                                        Комплектация: 
                                        {foreach $multioffer_titles as $multioffer}
                                            <p class="value">{$multioffer.title} - {$multioffer.value}</p>
                                        {/foreach}
                                        {if !$multioffer_titles}
                                            <p class="value">{$offer_title}</p>
                                        {/if}
                                    </div>
                                {/if}
                            </div>
                        </td>
                        <td width="110">
                            {$item.cartitem.amount} 
                            {if $catalog_config.use_offer_unit}
                                {$item.product.offers.items[$item.cartitem.offer]->getUnit()->stitle}
                            {else}
                                {$item.product->getUnit()->stitle}
                            {/if}
                            {if !empty($cartdata.items[$n].amount_error)}<div class="errorNum" style="display:block">{$cartdata.items[$n].amount_error}</div>{/if}
                        </td>
                        <td width="160">
                            <span class="priceBlock">
                                <span class="priceValue">{$cartdata.items[$n].cost}</span>
                            </span>
                            <div class="discount">
                                {if $cartdata.items[$n].discount>0}
                                    {t}скидка{/t} {$cartdata.items[$n].discount}
                                {/if}
                            </div>
                        </td>
                    </tr>
                    {/foreach}
                </tbody>
                <tbody class="additional">
                    {foreach from=$cart->getCouponItems() key=id item=item}
                    <tr class="first">
                        <td colspan="2">{t}Купон на скидку{/t} {$item.coupon.code}</td>
                        <td></td>
                    </tr>
                    {/foreach}
                    {if $cartdata.total_discount>0}
                        <tr class="bold">
                            <td colspan="2">{t}Скидка на заказ{/t}</td>
                            <td class="price">{$cartdata.total_discount}</td>
                        </tr>
                    {/if}
                    {foreach from=$cartdata.taxes item=tax}
                        <tr {if !$tax.tax.included}class="bold"{/if}>
                            <td colspan="2">{$tax.tax->getTitle()}</td>
                            <td class="price">{$tax.cost}</td>
                        </tr>                    
                    {/foreach}
                    {if $order.delivery}
                        <tr class="bold">
                            <td colspan="2">Доставка: {$delivery.title}</td>
                            <td class="price">{$cartdata.delivery.cost}</td>
                        </tr>
                    {/if}
                    {if $cartdata.payment_commission}
                        <tr class="bold">
                            <td colspan="2">{if $cartdata.payment_commission.cost>0}{t}Комиссия{/t}{else}{t}Скидка{/t}{/if} {t}при оплате через{/t} "{$order->getPayment()->title}": </td>
                            <td class="price">{$cartdata.payment_commission.cost}</td>
                        </tr>
                    {/if}
                </tbody>
                <tfoot class="summary">
                    <tr>
                        <td colspan="2">{t}Итого{/t}</td>
                        <td>{$cartdata.total}</td>
                    </tr>
                </tfoot>
            </table>        
            
            <br>
            <div class="orderComment">
                <label>{t}Комментарий к заказу{/t}</label>
                {$order.__comments->formView()}
            </div>
            <br>
            {if $shop_config->require_license_agree}
                <input type="checkbox" name="iagree" value="1" id="iagree"> <label for="iagree">{t alias="Заказ на одной странице - ссылка на условия предоставления услуг" agreement_url=$router->getUrl('shop-front-licenseagreement')}Я согласен с <a href="%agreement_url" class="licAgreement inDialog">условиями предоставления услуг</a>{/t}</label>
                <script type="text/javascript">
                    $(function() {
                        $('.formSave').click(function() {
                            if (!$('#iagree').prop('checked')) {
                                alert('{t}Подтвердите согласие с условиями предоставления услуг{/t}');
                                return false;
                            }
                        });
                    });
                </script>
            {/if}
            {if $shop_config.enable_agreement_personal_data}
                {include file="%site%/policy/agreement_phrase.tpl" button_title="{t}Далее{/t}"}
            {/if}
        </div>
    </div>
</div>