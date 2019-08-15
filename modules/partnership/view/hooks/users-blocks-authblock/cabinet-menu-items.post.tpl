{static_call var="is_partner" callback=['Partnership\Model\Api', 'isUserPartner'] params=$current_user.id}
{if $is_partner}
    <li><a href="{$router->getUrl('partnership-front-profile')}">{t}профиль партнера{/t}</a></li>
{/if}                    