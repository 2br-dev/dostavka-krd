<div class="onePagePaymentBlock">
    <div class="workArea">
        <h2>Оплата</h2>
        <input type="hidden" name="payment" value="0">
        <ul class="vertItems delivery">
            {foreach $pay_list as $item}
            <li>
                <div class="radioLine">
                    <span class="input">
                        <input type="radio" name="payment" value="{$item.id}" id="pay_{$item.id}" {if $order.payment==$item.id}checked{/if}>
                        <label for="pay_{$item.id}">{$item.title}</label>
                    </span>
                </div>
                <div class="descr">
                    {if !empty($item.picture)}
                       <img class="logoService" src="{$item.__picture->getUrl(100, 100, 'xy')}"/>
                    {/if}                                    
                    <p>{$item.description}</p>
                </div>
            </li>
            {/foreach}
        </ul>
    </div>
</div>