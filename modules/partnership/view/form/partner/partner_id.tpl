{if $elem.partner_id > 0}
    {static_call var=partner callback=['\Partnership\Model\Orm\Partner', 'loadByWhere'] params=[['id' => $elem.partner_id]]}
    {$partner.title}
{else}
    {t}Нет{/t}
{/if}