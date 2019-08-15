{addcss file="{$mod_css}stateinfo.css" basepath="root"}
<div class="stateinfo"
     data-refresh-url="{$refresh_url}"
     data-intensive="{if $integrity.is_intensive || $antivirus.is_intensive}1{else}0{/if}">
    {if $is_cron_work}
        <div class="stateinfo-checksum">
            {if $integrity.is_intensive}
                <div class="scan">
                    <div class="progress" style="width:{$integrity.progress}%;"></div>
                    <div class="actions">
                        <a href="{adminUrl avdo="disableIntegrityIntensiveMode" mod_controller="antivirus-widget-stateinfo"}"
                           class="call-update no-update-hash stateinfo-button gray-fill">{t}Стоп{/t}</a>
                    </div>
                    <div class="info">
                        {t current=$integrity.global_position total=$integrity.total_files_count}<span class="big-text">Идет полная проверка файлов</span><span class="small-text">Проверено</span> <span>%current из %total</span>{/t}
                    </div>
                </div>
            {else}
                {if $integrity.unread_event_count}
                    <!-- Проблема -->
                    <div class="problem">
                        <div class="actions">
                            <a href="{$integrity.event_list_url}" class="report" title="{t}отчет{/t}"></a>
                        </div>
                        <div class="problem-info">
                            <p>{t}Обнаружено измененных файлов:{/t} {$integrity.unread_event_count}</p>
                            <a href="{$integrity.event_list_url}" class="stateinfo-button white-fill">{t}Подробнее{/t}</a>
                            <a href="{adminUrl avdo="readIntegrityEvents" mod_controller="antivirus-widget-stateinfo"}" class="call-update no-update-hash stateinfo-button white-border">{t}Скрыть{/t}</a>
                        </div>
                    </div>
                {else}
                    <!-- Информация -->
                    <div class="information">
                        <div class="state">
                            <i class="ok" title="{t}Целостность файлов в норме{/t}"></i>
                        </div>
                        <div class="section">
                            <div class="title">{t}Целостность файлов{/t}</div>
                            {if $integrity.completed}
                                <div class="last-cycle">
                                    {t}Последняя проверка{/t} {$integrity.completed|date_format:"d.m.Y H:i"}
                                </div>
                            {/if}
                        </div>
                        <div class="actions">
                            <a href="{adminUrl avdo="enableIntegrityIntensiveMode" mod_controller="antivirus-widget-stateinfo"}" class="run call-update no-update-hash" title="{t}Запустить полную проверку{/t}"></a>
                            <a href="{$integrity.event_list_url}" class="report" title="{t}Показать отчет{/t}"></a>
                        </div>
                    </div>
                {/if}
            {/if}
        </div>


        <div class="stateinfo-proactive">

            {if $proactive.unread_event_count}
                <!-- Проблема -->
                <div class="problem">
                    <div class="actions">
                        <a href="{$proactive.event_list_url}" class="report" title="{t}Показать отчет{/t}"></a>
                    </div>
                    <div class="problem-info">
                        <p>
                            {t alias="Зафиксированы атаки" val=$proactive.unread_event_count ips=$ctrl->getIpCount()}
                                [plural:%val:Зафиксирована|Зафиксировано|Зафиксировано] %val [plural:%val:атака|атаки|атак]
                                c %ipsxIP [plural:%ips:адреса|адресов|адресов]
                            {/t}
                            {*<br>Заблокировано: { 2 } IP адреса*}
                        </p>
                        <a href="{$proactive.event_list_url}" class="stateinfo-button white-fill">{t}Подробнее{/t}</a>
                        <a href="{adminUrl avdo="readProactiveEvents" mod_controller="antivirus-widget-stateinfo"}" class="call-update no-update-hash stateinfo-button white-border">{t}Скрыть{/t}</a>
                    </div>
                </div>
            {else}
                <!-- Информация -->
                <div class="information">
                    <div class="state">
                        <i class="ok" title="{t}Атаки не обнаружены{/t}"></i>
                    </div>
                    <div class="section">
                        <div class="title">{t}Атаки на сайт{/t}</div>
                    </div>
                    <div class="actions">
                        <a href="{$proactive.event_list_url}" class="report" title="{t}Показать отчет{/t}"></a>
                    </div>
                </div>
            {/if}

        </div>


        <div class="stateinfo-antivirus">
            {if $antivirus.unread_event_count}
                <!-- Проблема -->
                <div class="problem">
                    <div class="actions">
                        <a class="report" title="{t}Показать отчет{/t}"></a>
                    </div>
                    <div class="problem-info">
                        <p>{t}Обнаружено зараженных файлов:{/t} {$antivirus.unread_event_count}</p>
                        <a href="{$antivirus.event_list_url}" class="stateinfo-button white-fill">{t}Подробнее{/t}</a>
                        <a href="{adminUrl avdo="readAntivirusEvents" mod_controller="antivirus-widget-stateinfo"}" class="call-update no-update-hash stateinfo-button white-border">{t}Скрыть{/t}</a>
                    </div>
                </div>
            {else}
                {if $antivirus.is_intensive}
                    <!-- Идет проверка -->
                    <div class="scan">
                        <div class="progress" style="width:{$antivirus.progress}%;"></div>
                        <div class="actions">
                            <a href="{adminUrl avdo="disableAntivirusIntensiveMode" mod_controller="antivirus-widget-stateinfo"}"
                               class="call-update no-update-hash stateinfo-button gray-fill">{t}Стоп{/t}</a>
                        </div>
                        <div class="info">{t current=$antivirus.global_position total=$antivirus.total_files_count}<span class="big-text">Идет полная проверка файлов</span> <span class="small-text">Проверено</span> <span>%current из %total</span>{/t}</div>
                    </div>
                {else}
                    <div class="information">
                        <div class="state">
                            <i class="ok" title="{t}Вирусы не обнаружены{/t}"></i>
                        </div>
                        <div class="section">
                            <div class="title">{t}Вирусы{/t}</div>
                            {if $antivirus.completed}
                                <div class="last-cycle">
                                    {t}Последняя проверка{/t} {$antivirus.completed|date_format:"d.m.Y H:i"}
                                </div>
                            {/if}
                        </div>
                        <div class="actions">
                            <a href="{adminUrl avdo="enableAntivirusIntensiveMode" mod_controller="antivirus-widget-stateinfo"}" class="run call-update no-update-hash" title="{t}Запустить полную проверку{/t}"></a>
                            <a href="{$antivirus.event_list_url}" class="report" title="{t}Показать отчет{/t}"></a>
                        </div>
                    </div>
                {/if}
            {/if}
        </div>

        <div class="stateinfo-footer">
            <img src="{$mod_img}scan.gif" class="protect-img">
            <span class="protect big-text">{t}защита включена{/t}</span>
            <a href="{adminUrl do="edit" mod_controller="modcontrol-control" mod="antivirus"}" class="settings" title="{t}Настройки модуля{/t}"></a>
            <a href="{$excluded_list_url}" class="trustzone" title="{t}Исключения{/t}"></a>
        </div>

    {else}
        <!-- Есть ошибка -->
        <div class="trouble">
            <p>{t}Не зафиксирован запуск фонового модуля антивируса. Настройте запуск внутреннего планировщика cron.{/t}</p>
            <a href="http://readyscript.ru/manual/cron.html" class="stateinfo-button white-fill">{t}Подробнее{/t}</a>
        </div>
    {/if}
</div>