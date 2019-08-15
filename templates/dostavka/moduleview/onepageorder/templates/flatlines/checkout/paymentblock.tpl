<div class="onePagePaymentBlock">
    <div class="t-order_method-of-payment">
        <h3 class="h3">{t}Выбор способа оплаты{/t}</h3>
        <input type="hidden" name="payment" value="0">

        <div class="order-list-items">
            {foreach $pay_list as $item}
                <div class="item">
                    <div class="radio-column">
                        <input type="radio" name="payment" value="{$item.id}" id="pay_{$item.id}" {if $order.payment==$item.id}checked{/if}>
                    </div>

                    <div class="info-column">
                        <div class="line">
                            <label class="h3" for="pay_{$item.id}" class="title">{$item.title}</label>
                        </div>

                        <div class="descr">
                            {if !empty($item.picture)}
                                <img class="logoService" src="{$item.__picture->getUrl(100, 100, 'xy')}" alt="{$item.title}"/>
                            {/if}
                            {$item.description}
                        </div>
                    </div>
                </div>
            {/foreach}
        </div>
    </div>
</div>