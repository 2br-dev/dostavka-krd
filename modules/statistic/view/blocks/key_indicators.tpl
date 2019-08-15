{addjs file="flot/excanvas.js" basepath="common"}
{addjs file="flot/jquery.flot.min.js" basepath="common"}
{addjs file="flot/jquery.flot.resize.js" basepath="common"}
{addjs file="flot/jquery.flot.pie.js" basepath="common"}
{addjs file="%statistic%/jquery.flot.orderbars.js"}

<div class="updatable stat-report" data-url="{urlmake}" data-update-block-id="{$block_id}" id="{$block_id}" data-update-replace="true">

    {if !$param.widget}
        <div class="viewport">
            <h2 class="stat-h2">{t}Ключевые показатели{/t}</h2>

            {$period_selector->render()}

            {addjs file="%statistic%/diagram.js"}
            <div class="plot">
                <div class="graph"></div>
            </div>
            <script>
                $.allReady(function() {
                    statisticShowKeyIndicator("#{$block_id} .graph", {$json_bars}, {$json_values}, {$json_ticks});
                });
            </script>
            <div class="stat-last-period">
                {t}Предыдущий период - с{/t} {$period_selector->getPrevDateFrom()|dateformat} {t}по{/t} {$period_selector->getPrevDateTo()|dateformat}
            </div>
        </div>
    {else}
        {$period_selector->render()}
    {/if}

    {if empty($param.no_list)}
        <div class="{if $param.widget}m-l-20 m-r-20 no-space{/if} m-b-20">
            <table border="0" class="{if $param.widget}wtable{else}rs-table{/if} stat-key-table overable-type2">
                <thead>
                    <tr>
                        <th class="l-w-space"></th>
                        <th>{t}Выбранный период{/t}</th>
                        <th>{if $param.widget}<span title="{t}с{/t} {$period_selector->getPrevDateFrom()|dateformat} {t}по{/t} {$period_selector->getPrevDateTo()|dateformat}">{t}Предыдущий период{/t}</span>{else}{t}Значение за предыдущий период{/t}{/if}</th>
                        <th class="r-w-space"></th>
                    </tr>
                </thead>
                <tbody>
                    {foreach $raw_data as $row}
                        <tr>
                            <td class="l-w-space"></td>
                            <td class="stat-nowrap">
                                {$row.label}&nbsp;{if $row.help}<span class="help-icon" title="{$row.help}">?</span>{/if}<br>
                                <span class="stat-value">{$row.values.1|format_price} <small>{$row.unit}</small></span> <sup class="{if $row.percent<0}red{else}green{/if}">{if $row.percent<0}{$row.percent}{else}+{$row.percent}{/if}%</sup>
                            </td>
                            <td class="stat-nowrap">{$row.values.0|format_price} <small>{$row.unit}</small></td>
                            <td class="r-w-space"></td>
                        </tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
    {/if}   
</div>