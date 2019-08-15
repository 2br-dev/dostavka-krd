<div class="onePagePaymentBlock">
    <input type="hidden" name="payment" value="0">
    <div class="formSection">
        <span class="formSectionTitle">{t}Оплата{/t}</span>
    </div>    
    <table class="formTable">
        <tr>                                            
            {foreach from=$pay_list item=item}
                <tr>
                    <td class="value fixedRadio topPadd" width="40">
                        <input type="radio" name="payment" value="{$item.id}" id="pay_{$item.id}" {if $order.payment==$item.id}checked{/if}>
                    </td>
                    <td class="value marginRadio topPadd" colspan="2">
                       {if !empty($item.picture)}
                           <img class="logoService" src="{$item.__picture->getUrl(100, 100, 'xy')}"/>
                       {/if}                    
                        <div class="middleBox">
                            <label for="pay_{$item.id}">{$item.title}</label>
                            <div class="help">{$item.description}</div>
                        </div>
                    </td>
                </tr>
            {/foreach}
        </tr>
    </table>
</div>