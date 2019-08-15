{$list=$elem->getRegionsList()}
<select name="default_region">
    <option value="0">{t}Не выбрано{/t}</option>
    {if !empty($list)}
        {foreach $list as $region}
            <option value="{$region.id}" data-parent="{$region.parent_id}" {if $elem.default_region==$region.id}selected{/if}>{$region.title}</option>
        {/foreach}
    {/if}
</select>