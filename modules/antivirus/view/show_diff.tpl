{addcss file="%antivirus%/showdiff.css"}
<div class="DifferencesFile">
    {t}Файл{/t}: {$file}
</div>
{if $error}
    <div class="notice-box">
    {t}Невозможно сравнить файл с эталоном. Причина:{/t} {$error}
    </div>
{else}
    {if $renderedDiff}
        {$renderedDiff}
    {else}
        <div class="inform-block">
            {t}Изменений нет{/t}
        </div>
    {/if}
{/if}