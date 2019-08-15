<form method="POST" action="{$router->getUrl('shop-front-reservation', ["product_id" => $product.id])}" class="authorization formStyle reserveForm">
        <input type="hidden" name="product_id" value="{$product.id}">
        <input type="hidden" name="offer_id" value="{$reserve.offer_id}">
        <input type="hidden" name="currency" value="{$product->getCurrencyCode()}">
        <h1 data-dialog-options='{ "width": "400" }'>Заказать</h1>
        <div class="infotext">
            <p class="title">{$product.title} {$product.barcode}</p>
            {t}В данный момент товара нет в наличии. Заполните форму и мы оповестим вас о поступлении товара.{/t}
        </div>
        <div class="forms">
            {if $reserve->hasError()}<div class="error">{implode(', ', $reserve->getErrors())}</div>{/if}
            <div class="center">
                <div class="formLine">
                    <label class="fieldName">{t}Количество{/t} <br>
                    <input type="number" min="{$product->getAmountStep()}" step="{$product->getAmountStep()}" name="amount" class="amount" value="{$reserve.amount}">
                    <span class="incdec">
                        <a class="inc" data-amount-step="{$product->getAmountStep()}"></a>
                        <a class="dec" data-amount-step="{$product->getAmountStep()}"></a>
                    </span>
                    </label>
                </div>
                {if $product->isMultiOffersUse()}
                    <div class="formLine">
                        <strong>{$product.offer_caption|default:t('Комплектация')}</strong>
                    </div>
                    {assign var=offers_levels value=$product.multioffers.levels} 
                    {foreach $offers_levels as $level}
                        <div class="formLine">
                            <label class="fielName">{$level.title|default:$level.prop_title}
                                <input name="multioffers[{$level.prop_id}]" value="{$reserve.multioffers[$level.prop_id]}" readonly>
                            </label>
                        </div>
                    {/foreach}
                {elseif $product->isOffersUse()}
                    {assign var=offers value=$product.offers.items}
                    <div class="formLine">
                        <label class="fielName">{$product.offer_caption|default:t('Комплектация')}
                            <input name="offer" value="{$reserve.offer}" readonly>
                        </label>
                    </div>
                {/if} 
                <div class="formLine">
                    <label class="fieldName">{t}Телефон{/t}
                        <input type="text" name="phone" class="inp" value="{$reserve.phone}">
                    </label>
                </div>
                <div class="formLine">
                    <label class="fieldName"><small>{t}или{/t}</small> E-mail
                        <input type="text" name="email" class="inp" value="{$reserve.email}">
                    </label>
                </div>

                {if !$is_auth}
                <div class="formLine">
                    <label class="fieldName">
                        {$reserve->__kaptcha->getTypeObject()->getFieldTitle()}
                        {$reserve->getPropertyView('kaptcha')}
                    </label>
                </div>
                {/if}
            </div>
            <div class="buttons">
                <input type="submit" value="{t}Оповестить меня{/t}">
            </div>
        </div>
</form>

<script>
    $(function() {
        $('.reserveForm .inc').off('click').on('click', function() {
            var amountField = $(this).closest('.reserveForm').find('.amount');
            amountField.val( (+amountField.val()|0) + ($(this).data('amount-step')-0) );
        });
        
        $('.reserveForm .dec').off('click').on('click', function() {
            var amountField = $(this).closest('.reserveForm').find('.amount');
            var val = (+amountField.val()|0);
            if (val > $(this).data('amount-step')) {
                amountField.val( val - $(this).data('amount-step') );
            }
        });
    });
</script>