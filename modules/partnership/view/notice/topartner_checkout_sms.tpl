{$order=$data->order}
{$cart=$order->getCart()}
{$order_data=$cart->getOrderData(true, false)}
{t}Оформлен заказ на сумму{/t} {$order_data.total_cost}
