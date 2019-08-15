{addjs file="jquery.rs.ormobject.js" basepath="common"}
{addjs file="%statistic%/date_period_selector.js"}
{addcss file="%statistic%/date_selector.css"}
{$controller_id = $controller->getUrlName()}
<div class="widget-filters">
    <form action="{adminUrl do=false mod_controller=$controller->getUrlName()}" class="form-call-update no-update-hash stat-date-range{if !$controller->getParam('widget')} page{/if}" method="POST">

        {if !empty($cmp->presets)}
            <div class="dropdown">
                <a id="presetSelector-{$controller_id}" data-toggle="dropdown" class="widget-dropdown-handle">{$cmp->current_preset_title} <i class="zmdi zmdi-chevron-down"></i></a>
                <ul class="dropdown-menu date-presets" aria-labelledby="presetSelector-{$controller_id}">
                    {foreach $cmp->presets as $preset}
                        <li><a data-to="{$preset.id}" data-from="{$preset.id}" class="item {if $preset.active}act{/if}">{$preset.label}</a></li>
                    {/foreach}
                </ul>
            </div>
        {/if}

        <div class="dropdown">
            <a id="dateSelector-{$controller_id}" data-toggle="dropdown" class="widget-dropdown-handle">{$cmp->date_from|dateformat:"@date"} &ndash; {$cmp->date_to|dateformat:"@date"} <i class="zmdi zmdi-chevron-down"></i></a>
            <div class="dropdown-menu p-20" aria-labelledby="dateSelector-{$controller_id}">

                <p>{t}Начало диапазона{/t}</p>
                <div class="input-group form-group">
                    <span class="input-group-addon"><i class="zmdi zmdi-calendar"></i></span>
                    <div class="dtp-container">
                        <input class="form-control date-time-picker from" type="text" name="{$cmp->url_id}_filter_date_from" value="{$cmp->date_from}" datefilter>
                    </div>
                </div>

                <p>{t}Конец диапазона{/t}</p>
                <div class="input-group form-group">
                    <span class="input-group-addon"><i class="zmdi zmdi-calendar"></i></span>
                    <div class="dtp-container">
                        <input class="form-control date-time-picker to" type="text" name="{$cmp->url_id}_filter_date_to" value="{$cmp->date_to}" datefilter>
                    </div>
                </div>

                <input type="submit" value="Применить" class="btn btn-primary">
            </div>
        </div>

        {if !empty($cmp->preset_groups)} {* Если есть группировки данныз (для синосоид) *}
            <hr/>
            <div class="dropdown">
                <a id="presetGroupSelector-{$controller_id}" data-toggle="dropdown" class="widget-dropdown-handle"><span>{$cmp->current_preset_group_title}</span> <i class="zmdi zmdi-chevron-down"></i></a>
                <ul class="dropdown-menu date-presets-groups" aria-labelledby="presetGroupSelector-{$controller_id}">

                    {foreach $cmp->preset_groups as $preset}
                        {if $preset.active}
                            {$active_group=$preset.id}
                        {/if}
                        <li><a data-value="{$preset.id}" class="item {if $preset.active}act{/if}">{$preset.label}</a></li>
                    {/foreach}
                </ul>
                <input type="hidden" name="{$cmp->url_id}_filter_date_group" value="{$active_group|default:$preset.id}"/>
            </div>
        {/if}

        {if !empty($cmp->preset_waves)} {* Если есть группировки данных (для синосоид) *}
            <div id="presetWaves" class="dropdown" data-cookie="{$cmp->url_id}_filter_wave">
                <a id="presetWavesSelector-{$controller_id}" data-toggle="dropdown" class="widget-dropdown-handle"><span>{$cmp->current_preset_wave_title}</span> <i class="zmdi zmdi-chevron-down"></i></a>
                <ul class="dropdown-menu date-presets-waves" aria-labelledby="presetWavesSelector-{$controller_id}">
                    {foreach $cmp->preset_waves as $preset}
                        {if $preset.active}
                            {$active_group=$preset.id}
                        {/if}
                        <li><a data-value="{$preset.id}" class="item {if $preset.active}act{/if}">{$preset.label}</a></li>
                    {/foreach}
                </ul>
                <input type="hidden" name="{$cmp->url_id}_filter_wave" value="{$active_group|default:$preset.id}"/>
            </div>
        {/if}
    </form>
</div>