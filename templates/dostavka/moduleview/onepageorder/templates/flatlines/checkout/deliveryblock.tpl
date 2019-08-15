<div class="t-order_method-of-payment onePageDeliveryBlock">
    <h3 class="h3">{t}Выбор способа доставки{/t}</h3>
    <input type="hidden" name="delivery" value="0">

    <div class="order-list-items">
        {foreach $delivery_list as $item}
            {if $item.class != 'myself' || $order.only_pickup_points == 1}
                {$addittional_html = $item->getAddittionalHtml($order)}
                {$something_wrong = $item->getTypeObject()->somethingWrong($order)}
                <div class="item">
                    <div class="radio-column">
                        <input type="radio" name="delivery" value="{$item.id}" id="dlv_{$item.id}" {if $order.delivery==$item.id}checked{/if} {if $something_wrong}disabled="disabled"{/if}>
                    </div>

                    <div class="info-column">
                        <div class="line">
                            <label class="h3" for="dlv_{$item.id}" class="title">{$item.title}</label>
                            <span class="price">
                                {if !$something_wrong}
                                    <span class="help">{$order->getDeliveryExtraText($item)}</span>
                                    <span class="price-value">{$order->getDeliveryCostText($item)}</span>
                                {/if}
                            </span>
                        </div>

                        <div class="descr">
                            {if $something_wrong}
                                <div class="something-wrong">{$something_wrong}</div>
                            {/if}
                            {if !empty($item.picture)}
                                <img class="logoService" src="{$item.__picture->getUrl(100, 100, 'xy')}" alt="{$item.title}"/>
                            {/if}
                            {$item.description}
                        </div>

                        {if $item.class=='myself' && !empty($warehouses)}
                            {$pvzList = $item->getTypeObject()->getOption('pvz_list')}
                            <div class="warehouseBlock">
                                <p class="title">{t}Выберите пункт выдачи{/t}:</p>
                                <select name="warehouse" {if $order.delivery!=$item.id}disabled="disabled"{/if}>
                                    {foreach $warehouses as $warehouse}
                                        {if empty($pvzList) || in_array(0, $pvzList) || in_array($warehouse.id, $pvzList)}
                                            <option value="{$warehouse.id}" {if $order.warehouse==$warehouse.id}selected="selected"{/if}>{$warehouse.title}</option>
                                        {/if}
                                    {/foreach}
                                </select>
                            </div>
                        {/if}

                        <div class="additionalInfo">{$addittional_html}</div>
                    </div>
                </div>
            {/if}
        {/foreach}
    </div>
</div>