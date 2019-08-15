{$user = $elem->getUser()}
{$source=$user->getSource()}
{$type=$source->getType()}

{if $type.id > 0}
    <table class="otable">
    <tr>
        <td class="otitle">
            {t}Источник пользователя{/t}:
        </td>
        <td>
            {$type.title} ({$type.referer_host})
        </td>
    </tr>
    </table>
{/if}