{assign var=row value=$cell->getRow()}
{assign var=details value=$row->getDetails()}

<div>
    {* Детали вирусного заражения *}
    {if $details|is_a:'\Antivirus\Model\Libs\Manul\MalwareInfo'}
        {t}Вирусное заражение{/t}<br>
        {if $row->file}{t}Файл{/t}: <strong>{$row->file|escape}</strong><br>{/if}
        {if !empty($details->title)}{t}Тип угрозы{/t}: <strong>{$details->title|escape}</strong><br>{/if}
        {if !empty($details->pos)}{t}Позиция{/t}: <strong>{$details->pos|escape}</strong><br>{/if}
        {*{if !empty($details->fragment)}{t}Фрагмент{/t}: <strong>{$details->fragment}</strong><br>{/if}*}
        {if !empty($details->signature)}{t}Сигнатура{/t}: <strong>{$details->signature|escape}</strong><br>{/if}
    {/if}

    {* Детали атаки вредоносным запросом *}
    {if $details|is_a:'\Antivirus\Model\MaliciousRequestInfo'}
        {t}Подозрительный запрос{/t}<br>
        {if !empty($details->ip)}{t}IP-адрес{/t}: <strong>{$details->ip|escape}</strong><br>{/if}
        {if !empty($details->url)}{t}URL{/t}: <strong>{$details->url|escape}</strong><br>{/if}
        {if !empty($details->source_type)}{t}Тип источника{/t}: <strong>{$details->source_type|escape}</strong><br>{/if}
        {if !empty($details->param_name)}{t}Параметр{/t}: <strong>{$details->param_name|escape}</strong><br>{/if}
        {if !empty($details->param_value)}{t}Значение{/t}: <strong>{$details->param_value|escape}</strong><br>{/if}
        {if !empty($details->pattern)}{t}Шаблон{/t}: <strong>{$details->pattern|escape}</strong><br>{/if}
        {if !empty($details->user_agent)}{t}User Agent{/t}: <strong>{$details->user_agent|escape}</strong><br>{/if}
    {/if}

    {* Детали атаки частотными запросами *}
    {if $details|is_a:'\Antivirus\Model\RequestCountAttackInfo'}
        {t}Атака частотным запросом{/t}<br>
        {if !empty($details->ip)}{t}IP-адрес{/t}: <strong>{$details->ip|escape}</strong><br>{/if}
        {if !empty($details->url)}{t}URL{/t}: <strong>{$details->url|escape}</strong><br>{/if}
        {if !empty($details->user_agent)}{t}User Agent{/t}: <strong>{$details->user_agent|escape}</strong><br>{/if}
    {/if}

    {if $details == null}
        {if $row->file}{t}Файл{/t}: <strong>{$row->file|escape}</strong><br>{/if}
    {/if}

</div>
