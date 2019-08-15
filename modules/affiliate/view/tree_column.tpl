<a href="{adminUrl do="edit" id=$cell->getRow('id')}" class="crud-edit{if !$cell->getRow('clickable')} c-gray{/if}" title="{t}Нажмите, чтобы отредактировать{/t}">
{if $cell->getRow('is_highlight')}<b>{$cell->getValue()}</b>{else}{$cell->getValue()}{/if}
{if $cell->getRow('is_default')} <b>({t}по умолчанию{/t})</b>{/if}
</a>