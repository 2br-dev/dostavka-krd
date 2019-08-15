<div class="crud-form">
    <div style="overflow:auto; max-height:600px;">
        {foreach $files as $one}
            <div>{$one}</div>
        {foreachelse}
            {t}Нет измененных файлов{/t}
        {/foreach}
    </div>
</div>