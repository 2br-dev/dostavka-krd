<div class="onePagePaymentBlock over__pay">
    <p class="over__pay-title">{t}Оплата{/t}</p>
    <input type="hidden" name="payment" value="0">
    <div class="over__pay_way-box">
        {foreach $pay_list as $item}
        <div class="over__pay_way-row radio">
            <input type="radio" name="payment" value="{$item.id}" id="pay_{$item.id}" {if $order.payment==$item.id}checked{/if} {if $item@first}checked{/if}>
            <label for="pay_{$item.id}" class="over__pay_way-label">{$item.title}</label>
        </div>
        {/foreach}
    </div>
</div>