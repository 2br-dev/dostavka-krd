{addcss file="%statistic%/statistic_source.css"}
{addjs file="%statistic%/statistic_source.js"}
{$source=$elem->getSource()}
{$type=$source->getType()}
{if $source.id}
    <table class="otable sourceTable">
        <tbody>
            <tr>
                <td class="otitle">
                    {t}№{/t}
                </td>
                <td>
                    {$source.id}
                </td>
            </tr>
            <tr>
                <td>
                    {t}Тип источника{/t}
                </td>
                <td>
                    {if $type.id}
                        <b>{$type.title}</b>
                    {else}
                        <b>{t}Не определен{/t}</b> <a href="{$router->getAdminUrl("", null, 'statistic-sourcetypesctrl')}">{t}Посмотреть список{/t}</a>
                    {/if}
                </td>
            </tr>
            <tr>
                <td>
                    {t}Сайт источника{/t}
                </td>
                <td>
                    <b>{$source.referer_site}</b>
                </td>
            </tr>
            <tr>
                <td>
                    {t}Источник{/t}
                </td>
                <td>
                    <a href="#" class="openSourceDetail" data-target="#sourceDetail">{t}Показать{/t}</a>
                    <div id="sourceDetail" class="sourceDetail" style="display: none">
                        <b>{$source.referer_source}</b>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="otitle">
                    {t}Страница посещения{/t}
                </td>
                <td>
                    <a href="#" class="openSourceDetail" data-target="#landingDetail">{t}Показать{/t}</a>
                    <div id="landingDetail" class="sourceDetail" style="display: none">
                        <b>{$source.landing_page}</b>
                    </div>
                </td>
            </tr>
            {if !empty($source.utm_source)}
                <tr>
                    <td>
                        UTM Source
                    </td>
                    <td>
                        <b>{$source.utm_source}</b>
                    </td>
                </tr>
            {/if}
            {if !empty($source.utm_medium)}
                <tr>
                    <td>
                        UTM Medium
                    </td>
                    <td>
                        <b>{$source.utm_medium}</b>
                    </td>
                </tr>
            {/if}
            {if !empty($source.utm_campaign)}
                <tr>
                    <td>
                        UTM Campaign
                    </td>
                    <td>
                        <b>{$source.utm_campaign}</b>
                    </td>
                </tr>
            {/if}
            {if !empty($source.utm_term)}
                <tr>
                    <td>
                        UTM term
                    </td>
                    <td>
                        <b>{$source.utm_term}</b>
                    </td>
                </tr>
            {/if}
            {if !empty($source.utm_content)}
                <tr>
                    <td>
                        UTM Content
                    </td>
                    <td>
                        <b>{$source.utm_content}</b>
                    </td>
                </tr>
            {/if}
            <tr>
                <td>
                    {t}Дата{/t}
                </td>
                <td>
                    <b>{$source.dateof|date_format:"d.m.Y H:i:s"}</b>
                </td>
            </tr>
        </tbody>
    </table>
{else}
    {t}Не определен{/t}
{/if}