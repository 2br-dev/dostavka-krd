{addjs file="jquery.slider.min.js" no_compress=true}
{addjs file="{$mod_js}jquery.filter.js" basepath="root"}
{addjs file="jquery.filter.js"}
<!--  -->
{addcss file="side_filter.css"}
<!--  -->
{$catalog_config=ConfigLoader::byModule('catalog')}

<section class="block filterSection filt_box" data-query-value="{$url->get('query', $smarty.const.TYPE_STRING)}">
    <div class="loadOverlay"></div>
    <form method="GET" class="filters" action="{urlmake filters=null pf=null bfilter=null p=null}" autocomplete="off">
        {if $param.show_cost_filter}
            <div class="filter typeInterval">
                <h4>{t}Цена:{/t}</h4>
                {if $catalog_config.price_like_slider && ($moneyArray.interval_to>$moneyArray.interval_from)} {* Если нужно показать как слайдер*}
                    <input type="hidden" data-slider='{ "from":{$moneyArray.interval_from}, "to":{$moneyArray.interval_to}, "step": "{$moneyArray.step}", "round": {$moneyArray.round}, "dimension": " {$moneyArray.unit}", "heterogeneity": [{$moneyArray.heterogeneity}]  }' value="{$basefilters.cost.from|default:$moneyArray.interval_from};{$basefilters.cost.to|default:$moneyArray.interval_to}" class="pluginInput" data-closest=".fromToPrice" data-start-value="{$basefilters.cost.from|default:$moneyArray.interval_from};{$basefilters.cost.to|default:$moneyArray.interval_to}"/>
                {/if}
                <div class="fullwidth fromToLine filt_table">
                    <input type="text" min="{$moneyArray.interval_from}" max="{$moneyArray.interval_to}" class="filt_input textinp fromto" name="bfilter[cost][from]" value="{if !$catalog_config.price_like_slider}{$basefilters.cost.from}{else}{$basefilters.cost.from|default:$moneyArray.interval_from}{/if}" data-start-value="{if $catalog_config.price_like_slider}{$moneyArray.interval_from|intval}{/if}" placeholder="от">
                    <input type="text" min="{$moneyArray.interval_from}" max="{$moneyArray.interval_to}" class="filt_input textinp fromto" name="bfilter[cost][to]" value="{if !$catalog_config.price_like_slider}{$basefilters.cost.to}{else}{$basefilters.cost.to|default:$moneyArray.interval_to}{/if}" data-start-value="{if $catalog_config.price_like_slider}{$moneyArray.interval_to|intval}{/if}" placeholder="до">
                </div>
            </div>
        {/if}
        {if $param.show_brand_filter && count($brands)>1}
            <div class="filter typeMultiselect">
                <h4>{t}Бренд{/t}: <a class="removeBlockProps hidden" title="{t}Сбросить выбранные параметры{/t}"></a></h4>
                <!-- <ul class="propsContentSelected "></ul>
                <div class="propsContainer">
                    <ul class="propsContent">
                        {$i = 1}
                        {foreach $brands as $brand}
                            <li style="order: {$i++};" {if isset($filters_allowed_sorted['brand'][$brand.id]) && ($filters_allowed_sorted['brand'][$brand.id] == false)}class="disabled-property"{/if}>
                                <input type="checkbox" {if is_array($basefilters.brand) && in_array($brand.id, $basefilters.brand)}checked{/if} name="bfilter[brand][]" value="{$brand.id}" class="cb filt_cb" id="cb_{$brand.id}_{$smarty.foreach.i.iteration}">
                                <label for="cb_{$brand.id}_{$smarty.foreach.i.iteration}" class="filt_label">{$brand.title}</label>
                            </li>
                        {/foreach}
                    </ul>
                </div> -->

                <nav class="fil__box propsContentSelected hidden"></nav>
                <nav class="fil__box propsContent">
                    {$i = 1}
                    {foreach $brands as $brand}
                    <div style="order: {$i++}" class="fil__row {if isset($filters_allowed_sorted['brand'][$brand.id]) && ($filters_allowed_sorted['brand'][$brand.id] == false)} disabled-property{/if}">
                        <input type="checkbox" {if is_array($basefilters.brand) && in_array($brand.id, $basefilters.brand)}checked{/if} name="bfilter[brand][]" value="{$brand.id}" class="cb filt_cb fil__check" id="cb_{$brand.id}_{$smarty.foreach.i.iteration}">
                        <label for="cb_{$brand.id}_{$smarty.foreach.i.iteration}" class="fil__label">{$brand.title}</label>
                    </div>
                    {/foreach}
                </nav>
            </div>
        {/if}
        {foreach $prop_list as $item}
        {foreach $item.properties as $prop}
        
            {* Подключаем фильтры по характеристикам *}
            {include file="%catalog%/blocks/sidefilters/type/{$prop.type}.tpl"}
            
        {/foreach}
        {/foreach}
        <div class="buttons filt_btns">
            <input type="submit" class="button submitFilter filt_btn_submit" value="Применить">
            <a href="{urlmake pf=null p=null filters=null bfilter=null}" class="filt_btn_reset button cleanFilter{if empty($filters) && empty($basefilters)} hidden{/if}">{t}Сбросить{/t}</a>
        </div>
        
        <script type="text/javascript">
            $(function() {
                $('.typeInterval .pluginInput').each(function() {
                    var $this = $(this);
                    var fromTo = $this.siblings('.fromToLine');
                    $this.jslider( $.extend( $(this).data('slider'), { callback: function(value) {
                        var values = value.split(';');
                        $('input[name$="[from]"]', fromTo).val(values[0]);
                        $('input[name$="[to]"]', fromTo).val(values[1]);
                        $this.trigger('change');
                    }}));
                    
                    $('input[name$="[from]"], input[name$="[to]"]', fromTo).change(function() {
                        var from = $('input[name$="[from]"]', fromTo).val();
                        var to = $('input[name$="[to]"]', fromTo).val();
                        $this.jslider('value', from, to);
                    });
                });
            });
        </script>
    </form>
</section>