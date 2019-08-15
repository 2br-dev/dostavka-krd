<div class="updatable stat-report" data-url="{urlmake}" data-update-block-id="{$block_id}" id="{$block_id}" data-update-replace="true">
    {$period_selector->render()}
    
    {if $percents}
        <div class="plot stat-funnel m-b-10">
            {foreach $percents as $key=>$val}
                <div class="stat-funnel__row">
                    <div class="stat-funnel__legend">{$titles.$key}</div>
                    <div class="stat-funnel__funnel">
                        <div class="stat-funnel__bar" style="width: {$percents.$key}%"></div>
                        <div class="stat-funnel__number">{$counts.$key}</div>
                    </div>
                </div>
            {/foreach}
        </div>
    {else}
        <div class="stat-nodata">{t}Нет ни одной записи за выбранный период{/t}</div>
    {/if}
</div>