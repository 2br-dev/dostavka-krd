{addjs file="flot/excanvas.js" basepath="common" before="<!--[if lte IE 8]>" after="<![endif]-->"}
{addjs file="flot/jquery.flot.min.js" basepath="common"}
{addjs file="flot/jquery.flot.tooltip.min.js" basepath="common"}
{addjs file="flot/jquery.flot.resize.js" basepath="common" waitbefore=true}
{addjs file="flot/jquery.flot.pie.js" basepath="common" waitbefore=true}
{if !$param.widget}
    {addjs file="jquery.rs.tableimage.js" basepath="common"}
{/if}

{addjs file="%statistic%/diagram.js"}
<div class="updatable stat-report" data-url="{urlmake}" data-update-block-id="{$block_id}" id="{$block_id}" data-update-replace="true">
    {if !$param.widget}
        <div class="viewport">
            <h2 class="stat-h2">{$title}</h2>

            {$period_selector->render()}
        </div>
    {else}
        {$period_selector->render()}
    {/if}

    {if $total}
        <div class="plot">
            <div class="graph"></div>
            <div class="flc-plot m-20"></div>
        </div>

        <script>
            $.allReady(function() {
                statisticShowPlot('#{$block_id}', {$json_data}, {$json_unit}); // Круговая диаграмма
            });
        </script>
    {/if}

    {if !$param.widget}
        <div class="beforetable-line">

            <form method="GET" action="{adminUrl do=false mod_controller=$this_controller->getUrlName()}" class="paginator form-call-update no-update-hash">
                <input type="hidden" name="{$block_id}_filter_date_from" value="{$period_selector->date_from}">
                <input type="hidden" name="{$block_id}_filter_date_to" value="{$period_selector->date_to}">
                {$paginator->getView([short => true])}
            </form>
            {if isset($filters)}
                {$filters->getView()}
            {/if}
        </div>

        <div class="viewport">
            {if isset($filters)}
                {$filters->getPartsHtml()}
            {/if}
        </div>

        {if $total}
            <div class="table-mobile-wrapper">
                {$data_provider->getTable($paginator)->getView()}
            </div>
        {else}
            <div class="stat-nodata">{t}Нет ни одной записи за выбранный период{/t}</div>
        {/if}

        <div class="viewport">
            <form method="GET" action="{adminUrl do=false mod_controller=$this_controller->getUrlName()}" class="paginator form-call-update no-update-hash">
                <input type="hidden" name="{$block_id}_filter_date_from" value="{$period_selector->date_from}">
                <input type="hidden" name="{$block_id}_filter_date_to" value="{$period_selector->date_to}">
                {$paginator->getView()}
            </form>
        </div>
    {/if}

</div>
