{addjs file="{$mod_js}onepageorder.js" basepath="root"}
{addcss file="{$mod_css}onepageorder.css" basepath="root"}

<form id="order-form" method="POST" data-city-autocomplete-url="{$router->getUrl('shop-front-checkout', ['Act'=>'searchcity'])}" data-update-url="{$router->getUrl('shop-front-checkout',['Act'=>'update'])}" data-blocks='{ 
        "delivery" : ".onePageDeliveryBlock", {* Блок для обновления с доставками *}
        "payment"  : ".onePagePaymentBlock", {* Блок для обновления с оплатами *}
        "products" : ".onePageProductsBlock" {* Блок для обновления с товарами *}
    }'>
    {* Показ ошибок *}
    {$errors=$order->getNonFormErrors()}
    {if $errors}
        <div class="pageError">
            {foreach from=$errors item=item}
                <p>{$item}</p>
            {/foreach}
        </div>
    {/if}
    
    {$errors=$order->getErrors()}
    {if $errors}
        <div class="pageError">
            {foreach from=$errors item=item}
                <p>{$item}</p>
            {/foreach}
        </div>
    {/if}
    
    {* Адрес и пользователь*}
    {include file="%onepageorder%/templates/{$this_controller->theme}/checkout/adressblock.tpl" is_auth=$is_auth user=$user order=$order conf_userfields=$conf_userfields reg_userfields=$reg_userfields shop_config=$shop_config}
    {* Блок с доставками *}
    {$delivery_block}
    {* Блок с оплатами *}
    {$payment_block}
    {* Блок с составом заказа *}
    {$product_block}
    
    
    <div>
        <button type="submit" class="formSave">{t}Подтвердить заказ{/t}</button>
    </div>
</form>

<script type="text/javascript">
    $(function() {
        //Инициализация плагина для обработки оформления на одной странице
        $('form#order-form', this).onePageCheckout();
    });
</script>
