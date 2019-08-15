{*
    В этом шаблоне доступна переменная $transaction
    Объект заказа можно получить так: $transaction->getOrder()
    $need_check_receipt - Флаг, если нужно проверить статус чека после оплаты 
*}
<div class="paymentResult success profile">
    {if $need_check_receipt}
        <img id="rs-waitReceiptLoading" src="{$THEME_IMG}/loading.gif" alt=""/>
    {/if}
    <h2>{t}Оплата успешно проведена{/t} {if $need_check_receipt}<br/><span id="rs-waitReceiptStatus">{t}Ожидается получение чека.{/t}</span>{/if}</h2>
    <p class="descr">
    {if $transaction->getOrder()->id}
        <a href="{$router->getUrl('shop-front-myorderview', ['order_id' => $transaction->getOrder()->order_num])}" class="colorButton">{t}перейти к заказу{/t}</a>
    {else}
        <a href="{$router->getUrl('shop-front-mybalance')}" class="colorButton">{t}перейти к лицевому счету{/t}</a>
    {/if}
    </p>
</div>

{if $need_check_receipt} {* Если нужно проверить статус чека после оплаты *}
   {addjs file="%shop%/order/success_receipt.js"}
{/if}