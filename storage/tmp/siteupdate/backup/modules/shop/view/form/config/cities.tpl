{$list=$elem->getCitiesList()}
<select name="default_city">
    <option value="0">{t}Не выбрано{/t}</option>
    {if !empty($list)}
        {foreach $list as $city}
            <option value="{$city.id}" data-parent="{$city.parent_id}" {if $elem.default_city==$city.id}selected{/if}>{$city.title}</option>
        {/foreach}
    {/if}
</select>

<script type="text/javascript">

    /**
     * Скрывает ненужные регионы для определенной страны
     *
     * @param boolean change_region_to_first - меняет регион на не выбрано
     */
    function hideNoNeedRegions(change_region_to_first = false) {
        var country = $("[name='default_country']").val();
        $("[name='default_region'] option").show();
        $("[name='default_region'] option:not([data-parent=" + country + "])").hide();
        $("[name='default_region'] option:first").show();
        $("[name='default_city'] option").hide();
        $("[name='default_city'] option:first").show();
        if (change_region_to_first){
            $("[name='default_region'] option:first").prop('selected', true);
            $("[name='default_city'] option:first").prop('selected', true);
        }
    }

    /**
     * Скрывает ненужные регионы для определенной страны
     */
    function hideNoNeedCities() {
        var region  = $("[name='default_region']").val();
        $("[name='default_city'] option").show();
        $("[name='default_city'] option:not([data-parent=" + region + "])").hide();
        $("[name='default_city'] option:first").show();
    }

    $(document).ready(function() {
        hideNoNeedRegions(false);  //Скроем регионы
        hideNoNeedCities();   //Скроем города
        $("[name='default_country']").on('change', function() {
            hideNoNeedRegions(true);
        });
        $("[name='default_region']").on('change', hideNoNeedCities);
    });
</script>