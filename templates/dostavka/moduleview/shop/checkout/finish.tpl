<div class="formStyle checkoutBox fin">
    <div class="fin__title-box">
        <p class="fin__title">{t}Спасибо! Ваш заказ успешно оформлен{/t}</p>
    </div>
    <div class="fin__body">
        <p class="fin__body-title">{t}Все уведомления об изменениях в данном заказе также будут отправлены на электронную почту покупателя.{/t}</p>
        <div class="fin__info">
            <p class="fin__info-title">{t}Заказ №{/t} {$order.order_num}</p>
            {$delivery=$order->getDelivery()}
            {$address=$order->getAddress()}
            {$pay=$order->getPayment()}
            <div class="fin__info-row">
                <p class="fin__info-label">Дата заказа</p>
                <p class="fin__info-label">{$order.dateof|date_format:"d.m.Y"}</p>
            </div>
            <div class="fin__info-row">
                <p class="fin__info-label">Оплата</p>
                <p class="fin__info-label">{$pay.title}</p>
            </div>
            <div class="fin__info-row">
                <p class="fin__info-label">Способ доставки</p>
                <p class="fin__info-label">{$delivery.title}</p>
            </div>
            <div class="fin__info-row">
                <p class="fin__info-label">Адрес доставки</p>
                <p class="fin__info-label">{$address->getLineView()}</p>
            </div>
            {assign var=orderdata value=$cart->getOrderData()}
            <p class="fin__info-title-2">Сумма заказа:  {$orderdata.total_cost}</p>
            <div class="buttons fin__btn-box">
                {if $order->canOnlinePay()}
                    <a href="{$order->getOnlinePayUrl()}" class="button color fin__btn">{t}Перейти к оплате{/t}</a>
                {else}
                    <a href="{$router->getRootUrl()}" class="button color fin__btn">{t}Завершить заказ{/t}</a>
                {/if}
            </div>
        </div>
    </div>
</div>