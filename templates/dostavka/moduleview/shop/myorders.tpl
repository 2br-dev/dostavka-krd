{addcss file="order.css"}
<!--  -->
{if count($order_list)}
<div class="order">
    {foreach $order_list as $order}
        {$cart=$order->getCart()}
        {$products=$cart->getProductItems()}
        {$order_data=$cart->getOrderData()}
        <div class="order__box">
            <div class="order__col order__col-left">
                <p class="order__title-1">Заказ № {$order.order_num}</p>
                <p class="order__status" style="color:{$order->getStatus()->bgcolor}">Статус: {$order->getStatus()->title}</p>
                <p class="order__title-3">{$order->admin_comments}</p>
            </div>
            <div class="order__col order__col-right">
                <div class="order__box-2">
                    <p class="order__title-2">Сумма к оплате</p>
                    <p class="order__cost">{$order_data.total_cost}</p>
                </div>
                <p class="order__title-4">{$order->getPayment()->title}</p>
            </div>
        </div>
    {/foreach}
</div>
{else}
<div class="oreder-empty">
    <p class="order-empty__title">Еще не оформлено ни одного заказа</p>
</div>
{/if}
{include file="%THEME%/paginator.tpl"}