<div class="onePageDeliveryBlock">
    <div class="workArea">
        <h2>Доставка</h2>
        <input type="hidden" name="delivery" value="0">
        <ul class="vertItems">
            {foreach $delivery_list as $item}
            {$something_wrong=$item->getTypeObject()->somethingWrong($order)}
            <li>
                <div class="radioLine">
                    <span class="input">
                        <input type="radio" name="delivery" value="{$item.id}" id="dlv_{$item.id}" {if $order.delivery==$item.id}checked{/if} {if $something_wrong}disabled="disabled"{/if}>
                        <label for="dlv_{$item.id}">{$item.title}</label>
                    </span>
                    <span id="scost_{$item.id}" class="price">
                        {if $something_wrong}
                            <span style="color:red;">{$something_wrong}</span>
                        {else}
                            <span class="help">{$order->getDeliveryExtraText($item)}</span>
                            {$order->getDeliveryCostText($item)}
                        {/if}
                    </span>
                </div>
                <div class="descr">
                    {if !empty($item.picture)}
                       <img class="logoService" src="{$item.__picture->getUrl(100, 100, 'xy')}"/>
                    {/if}
                    <p>{$item.description}</p>
                    <div class="additionalInfo">{$item->getAddittionalHtml($order)}</div>
                </div>
            </li>
            {/foreach}
        </ul>
    </div>
</div>