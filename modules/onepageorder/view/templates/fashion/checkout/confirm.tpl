{addjs file="{$mod_js}onepageorder.js" basepath="root"}
{addcss file="{$mod_css}onepageorder.css" basepath="root"}

<form id="order-form" class="checkoutBox formStyle {$order.user_type|default:"authorized"}" method="POST" data-city-autocomplete-url="{$router->getUrl('shop-front-checkout', ['Act'=>'searchcity'])}" data-update-url="{$router->getUrl('shop-front-checkout',['Act'=>'update'])}" data-blocks='{ 
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
    {include file="%onepageorder%/templates/{$this_controller->theme}/checkout/adressblock.tpl" is_auth=$is_auth user=$user order=$order conf_userfields=$conf_userfields reg_userfields=$reg_userfields}
    {* Блок с доставками *}
    {$delivery_block}
    {* Блок с оплатами *}
    {$payment_block}
    {* Блок с составом заказа *}
    {$product_block}
    
    
    <div class="buttons">
        <input type="submit" value="{t}Подтвердить заказ{/t}" class="formSave">
    </div>
</form>

<script type="text/javascript">
    $(function() {
        //Инициализация плагина для обработки оформления на одной странице
        $('form#order-form', this).onePageCheckout({
            oneTab : "input[name='user_type']", //Селектор - вкладки
            adressListBlock : ".lastAddress", //Селектор - блока с вводом адреса
            newAdressBlock  : ".newAddress",   //Селектор - блока готовыми адресами пользователя  
            userType        : 'input[name="user_type"]:checked' //Селектор поля с выбранным пользователем
        });
        //Смена вкладок
        $('.userType input').click(function() {
            $(this).closest('.checkoutBox').removeClass('person company noregister user').addClass( $(this).val() );
            $('#doAuth').attr('disabled', $(this).val()!='user');
        });
    });
</script>
