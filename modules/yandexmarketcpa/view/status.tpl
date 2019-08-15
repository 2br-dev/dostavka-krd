<div class="clearfix">
    <div class="pull-left m-r-10 m-b-10">
        <p>{t}Яндекс &rarr; ReadyScript{/t}</p>
        {include file=$field->getOriginalTemplate()}
    </div>
    <div class="pull-left m-b-10">
        <p>{t}ReadyScript &rarr; Яндекс{/t} (<a onclick="$(this).parent().siblings('select').val( $(this).closest('.clearfix').find('select').val() ); return false;" title="{t}Нажмите, чтобы отметить тот же статус, что выбран для сопоставления Яндекс&nbsp;&rarr;&nbsp;ReadyScript{/t}">{t}Выбрать тот же статус{/t}</a>)</p>
        {$name="__{$field->getName()}_reverse"}
        {include file=$elem[$name]->getRenderTemplate() field=$elem[$name]}
    </div>
</div>