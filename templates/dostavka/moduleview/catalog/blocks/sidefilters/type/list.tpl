<div class="filter typeMultiselect">
    <h4>{$prop.title}: <a class="removeBlockProps hidden" title="{t}Сбросить выбранные параметры{/t}"></a></h4>
    <nav class="fil__box propsContentSelected hidden"></nav>
    <nav class="fil__box propsContent">
        {$i = 1}
        {foreach $prop->getAllowedValues() as $key => $value}
        <div style="order: {$i++}" class="fil__row {if isset($filters_allowed_sorted[$prop.id][$key]) && ($filters_allowed_sorted[$prop.id][$key] == false)} disabled-property{/if}">
            <input type="checkbox" {if is_array($filters[$prop.id]) && in_array($key, $filters[$prop.id])}checked{/if} name="pf[{$prop.id}][]" value="{$key}" class="cb filt_cb fil__check" id="cb_{$prop.id}_{$value@iteration}">
            <label for="cb_{$prop.id}_{$value@iteration}" class="fil__label">{$value}</label>
        </div>
        {/foreach}
    </nav>
</div>