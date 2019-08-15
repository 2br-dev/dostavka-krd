<div class="onePageDeliveryBlock">
    <div class="workArea">
        <h2>{t}Доставка{/t}</h2>
        <input type="hidden" name="delivery" value="0">
        <ul class="vertItems">
            {foreach $delivery_list as $item}
                {if $item.class != 'myself' || $order.only_pickup_points == 1}
                    {$addittional_html = $item->getAddittionalHtml($order)}
                    {$something_wrong = $item->getTypeObject()->somethingWrong($order)}
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
                            {* Если выбран самовывоз покажем склады для выбора *}
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
                    </li>
                {/if}
            {/foreach}
        </ul>
    </div>
</div>