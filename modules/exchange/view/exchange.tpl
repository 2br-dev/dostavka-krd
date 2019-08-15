{addjs file="fileinput/fileinput.min.js" basepath="common"}
{addjs file="{$mod_js}exchange.js" basepath="root"}

<div class="viewport">
    <div class="notice-box">
        {t}В "1С:Предприятие" в диалоге настройки профиля обмена в качестве адреса сайта необходимо указать{/t}
        <b>{$router->getUrl('exchange-front-gate', ['site_id' => $SITE.id], true)}</b>

        <br><br>
        {assign var=last_date value=$this_controller->api->getLastExchangeDate()}
        {if empty($last_date)}
        {t}Обмен данными еще не производился{/t}
        {else}
        {t}Последний обмен данными был выполен{/t}
        {$last_date|dateformat:"<b>@date</b> @time"}
        {/if}
    </div>

    {if !$CONFIG->firm_inn}
        <div class="notice-box no-padd" style="margin-top:10px;">
            <div class="notice-bg">
                <a href="{$router->getAdminUrl(false, [], 'site-options')}">
                    {t alias="ИНН не указан"}Внимание! <b>ИНН организации</b> не указан. Обмен заказами невозможен{/t}
                </a>
            </div>
        </div>
    {/if}

    <div class="vpad10"></div>

    <label><input type="radio" name="import_source" value="xml-files" checked="checked"> {t}Загрузка XML-файлов{/t}</label><br>
    <label><input type="radio" name="import_source" value="ftp"> {t}Загрузка из папки{/t}</label>

    <div id="import-params" data-urls='{json_encode(
        ["{$router->getUrl('exchange-front-gate', ['site_id' => $SITE.id, 'type' => 'catalog', 'mode' => 'init', 'dont_clear' => '1'])}",
        "{$router->getUrl('exchange-front-gate', ['site_id' => $SITE.id, 'type' => 'catalog', 'mode' => 'import', 'filename' => 'import.xml'])}",
        "{$router->getUrl('exchange-front-gate', ['site_id' => $SITE.id, 'type' => 'catalog', 'mode' => 'import', 'filename' => 'offers.xml'])}" ])}'></div>

    {* Блок загрузки из XML-файлов *}
    <div class="source-block xml-files">
        <form method="POST" class="crud-form" enctype="multipart/form-data">
            <h3>{t}Загрузка XML-файлов{/t}</h3>

            <label>{t}Файл import.xml:{/t}</label><br>
            <div class="fileinput fileinput-new" data-provides="fileinput">
                <span class="btn btn-default btn-file">
                    <span class="fileinput-new">{t}Выберите файл{/t}</span>
                    <span class="fileinput-exists">{t}Изменить{/t}</span>
                    <input type="file" name="file_import1">
                </span>
                <span class="fileinput-filename"></span>
                <a href="#" class="close fileinput-exists" data-dismiss="fileinput" style="float: none">&times;</a>
            </div>
            <br><br>

            <label>{t}Файл offers.xml:{/t}</label><br>
            <div class="fileinput fileinput-new" data-provides="fileinput">
                <span class="btn btn-default btn-file">
                    <span class="fileinput-new">{t}Выберите файл{/t}</span>
                    <span class="fileinput-exists">{t}Изменить{/t}</span>
                    <input type="file" name="file_import2">
                </span>
                <span class="fileinput-filename"></span>
                <a href="#" class="close fileinput-exists" data-dismiss="fileinput" style="float: none">&times;</a>
            </div>
        </form>
    </div>


    {* Блок загрузки из папкп FTP *}
    <div class="source-block ftp hidden" >
        <h3>{t}Загрузка из папки FTP{/t}</h3>
        {if $this_controller->api->isExchangeDirectoryEmpty()}
            <div class="notice-box">
                {t}Папка{/t} <b>/storage/exchange/{$SITE.id}/import/</b> {t}пуста{/t}
            </div><br>
        {/if}
        <div class="notice-box no-padd1">
             {t}Загрузите файлы в папку{/t} <b>/storage/exchange/{$SITE.id}/import/</b>
        </div>
    </div>
</div>