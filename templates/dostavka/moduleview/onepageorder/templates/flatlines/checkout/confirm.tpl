{addjs file="{$mod_js}onepageorder.js" basepath="root"}
{addcss file="{$mod_css}onepageorder.css" basepath="root"}
{$shop_config=ConfigLoader::byModule('shop')}

<div class="row form-style">
    <form id="order-form" class="clearfix t-opo t-order rs-order-form {$order.user_type|default:"authorized"}" method="POST" data-city-autocomplete-url="{$router->getUrl('shop-front-checkout', ['Act'=>'searchcity'])}" data-update-url="{$router->getUrl('shop-front-checkout',['Act'=>'update'])}" data-blocks='{
            "delivery" : ".onePageDeliveryBlock", {* Блок для обновления с доставками *}
            "payment"  : ".onePagePaymentBlock", {* Блок для обновления с оплатами *}
            "products" : ".onePageProductsBlock" {* Блок для обновления с товарами *}
        }'>

        <div class="col-xs-12 col-md-9 t-opo_items">
            {$errors=$order->getErrorsStr()}
            {if $errors}
                <div class="page-error">
                    {$errors}
                </div>
            {/if}

            {* Адрес и пользователь*}
            {include file="%onepageorder%/templates/{$this_controller->theme}/checkout/adressblock.tpl" is_auth=$is_auth user=$user order=$order conf_userfields=$conf_userfields reg_userfields=$reg_userfields}
            {* Блок с доставками *}
            {$delivery_block}
            {* Блок с оплатами *}
            {$payment_block}

            <div class="t-order_comment-to-shipment">
                <h3 class="h3">{t}Комментарий к заказу{/t}</h3>
                <div class="form-group">
                    {$order->getPropertyView('comments')}
                </div>

                {if $is_agreement_require=$shop_config->require_license_agree}
                    <input type="checkbox" name="iagree" value="1" id="iagree">
                    <label for="iagree">{t alias="Заказ на одной странице - ссылка на условия предоставления услуг" agreement_url=$router->getUrl('shop-front-licenseagreement')}Я согласен с <a href="%agreement_url" class="rs-indialog">условиями предоставления услуг</a>{/t}</label>
                {/if}
                
                {if $CONFIG.enable_agreement_personal_data}
                    {include file="%site%/policy/agreement_phrase.tpl" button_title="{t}Подтвердить заказ{/t}"}
                {/if}
            </div>
            
            

            <div class="t-order_button-block">
                <button type="submit" class="form-save link link-more{if $is_agreement_require} disabled{/if}">{t}Подтвердить заказ{/t}</button>
                <a href="{$router->getRootUrl()}" class="link link-del">{t}Продолжить покупки{/t}</a>
            </div>
        </div>

        <div class="col-xs-12 col-md-3 t-opo_info sticky">
            {* Блок с составом заказа *}
            {$product_block}
        </div>

    </form>
</div>

<script type="text/javascript">
    $(function() {
        //Инициализация плагина для обработки оформления на одной странице
        $('.rs-order-form', this).onePageCheckout({
            oneTab : "input[name='user_type']", //Селектор - вкладки
            adressListBlock : ".rs-last-address", //Селектор - блока с вводом адреса
            newAdressBlock  : ".rs-new-address",   //Селектор - блока готовыми адресами пользователя
            userType        : 'input[name="user_type"]:checked', //Селектор поля с выбранным пользователем
            deliveryTogglerWrap: '#form-address-section-wrapper',
            changeUser: false,
            submitButton: '.form-save',
            deleteAddress: '.rs-delete-address',
            addressItem:'.rs-address-item',
            searchCityItemsClass:'search-city-items',
            alwaysUpdate: ["products"],
            oneTab: false
        });

        //Устанавливаем радиокнопки, при выборе таба
        $('.rs-user-type-tabs').on('shown.bs.tab', function(e) {
            var userType = $(e.target).data('value');
            $('input[name="user_type"][value="'+userType+'"]').prop('checked', true).change();
        })

        //Устанавливаем
        $('input[name="user_type"]').change(function() {
            var userType = $(this).val();
            $('.rs-order-form').removeClass('person company noregister user').addClass( userType );
            $('.rs-order-form').onePageCheckout('ajaxGetBlocks');
        });

        /**
         * Активируем флажок "Я согласен с условиями продаж"
         */
        if ($('#iagree').length) {
            $('#iagree').change(function() {
                $('.form-save[type="submit"]').toggleClass('disabled', !this.checked);
            }).change();

            $('.form-save').click(function() {
                if ( !$('#iagree').is(':checked') ) {
                    alert({t}'Необходимо подтвердить согласие с условиями предоставления услуг'{/t});
                    return false;
                }
            });
        }
    });
</script>
