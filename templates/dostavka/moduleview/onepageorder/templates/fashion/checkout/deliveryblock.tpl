<div class="onePageDeliveryBlock over__deliv">
    <p class="over__deliv-title">Доставка</p>
    <input type="hidden" name="delivery" value="0">
    <!--  -->
    <div class="over__deliv_box">
        {foreach $delivery_list as $item}
            {$addittional_html = $item->getAddittionalHtml($order)}
            {$something_wrong = $item->getTypeObject()->somethingWrong($order)}
            <div class="over__deliv_box-row">
                    <input type="radio" class="over__deliv_box-input" value="{$item.id}" name="delivery" {if $order.delivery == $item.id}checked{/if} {if $something_wrong}disabled="disabled"{/if}>
                    <label class="over__deliv_box-label" for="{$item.id}">{$item.title} <span>({$order->getDeliveryCostText($item)})</span></label>
            </div>
        {/foreach}
    </div>
    <!--  -->
</div>