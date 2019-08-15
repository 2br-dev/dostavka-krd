{assign var=catalog_config value=ConfigLoader::byModule('catalog')}
<div class="onePageProductsBlock">           
    {$products=$cart->getProductItems()}
    {$cartdata=$cart->getCartData()}        
    <div class="coItems">
        <table class="themeTable">
            <thead>
                <tr>
                    <td>{t}Товар{/t}</td>
                    <td>{t}Количество{/t}</td>
                    <td class="price">{t}Цена{/t}</td>
                </tr>
            </thead>
            <tbody>
                {foreach $products as $n=>$item}
                {$barcode=$item.product->getBarCode($item.cartitem.offer)}
                {$offer_title=$item.product->getOfferTitle($item.cartitem.offer)}                        
                {$multioffer_titles=$item.cartitem->getMultiOfferTitles()}
                <tr>
                    <td><a href="{$item.product->getUrl()}">{$item.product.title}</a>
                        <div class="codeLine">
                            {if $barcode != ''}{t}Артикул{/t}:<span class="value">{$barcode}</span><br>{/if}
                            {if $multioffer_titles || ($offer_title && $item.product->isOffersUse())}
                                <div class="multioffersWrap">
                                    {foreach $multioffer_titles as $multioffer}
                                        <p class="value">{$multioffer.title} - <strong>{$multioffer.value}</strong></p>
                                    {/foreach}
                                    {if !$multioffer_titles}
                                        <p class="value"><strong>{$offer_title}</strong></p>
                                    {/if}
                                </div>
                            {/if}
                        </div>
                    </td>
                    <td>
                        {$item.cartitem.amount}
                        {if $catalog_config.use_offer_unit}
                            {$item.product.offers.items[$item.cartitem.offer]->getUnit()->stitle}
                        {else}
                            {$item.product->getUnit()->stitle}
                        {/if}
                        {if !empty($cartdata.items[$n].amount_error)}<div class="amountError">{$cartdata.items[$n].amount_error}</div>{/if}
                    </td>
                    <td class="price">
                        {$cartdata.items[$n].cost}
                        <div class="discount">
                            {if $cartdata.items[$n].discount>0}
                            {t}скидка{/t} {$cartdata.items[$n].discount}
                            {/if}
                        </div>
                    </td>
                </tr>
                {/foreach}
            </tbody>
        </table>
        <br>
        <table class="themeTable">
            <tbody>
                {foreach $cart->getCouponItems() as $id=>$item}
                <tr>
                    <td>{t}Купон на скидку{/t} {$item.coupon.code}</td>
                    <td></td>
                </tr>
                {/foreach}
                {if $cartdata.total_discount>0}
                <tr>
                    <td>{t}Скидка на заказ{/t}</td>
                    <td>{$cartdata.total_discount}</td>
                </tr>
                {/if}
                {foreach $cartdata.taxes as $tax}
                <tr {if !$tax.tax.included}class="bold"{/if}>
                    <td>{$tax.tax->getTitle()}</td>
                    <td>{$tax.cost}</td>
                </tr>
                {/foreach}
                {if $order.delivery}
                    <tr class="bold">
                        <td colspan="2">{t}Доставка{/t}: {$delivery.title}</td>
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
        </table>
        <div class="summary">
            <span class="text">{t}Итого{/t}: </span>
            <span class="price">{$cartdata.total}</span>
        </div>
        <div class="commentSection">
            <label class="commentLabel">{t}Комментарий к заказу{/t}</label>
            {$order.__comments->formView()}
        </div>
        {if $shop_config->require_license_agree}
        <br>
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
    </div>
    <div class="clearBoth"></div>
</div>