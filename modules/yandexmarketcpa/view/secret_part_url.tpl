{include file=$elem.__secret_part_url->getOriginalTemplate() field=$elem.__secret_part_url}

<div class="notice m-t-10">
    {t alias="!Настройки Заказов на Яндексе"}<p>Установите следующие настройки на странице <b>Работа с API &rarr; Настройки API заказа</b>
    на странице вашего магазина в <a href="https://partner.market.yandex.ru">партнерском кабинете Яндекса</a>. Данные настройки используются как в качестве <b>основных</b>, так и <b>тестовых</b>.</p>{/t}

    <p>{t}URL API{/t}: <b>{$router->getUrl('yandexmarketcpa-front-base', [], true)|replace:"http://":"https://"}<span id="secret-part-url">{$elem.secret_part_url}</span></b></p>
    <p>{t}SHA1 fingerprint{/t}: <a href="https://readyscript.ru/manual/yandex_market_cpa.html">{t}Узнайте из нашей документации, как получить данный параметр{/t}</a></p>
    <p>{t}Тип авторизации{/t}: <b>URL</b></p>
    <p>{t}Формат данных{/t}: <b>JSON</b></p>
</div>

<script type="text/javascript">
    $(function() {
        $('[name="secret_part_url"]').keyup(function(e) {
            $('#secret-part-url').text( $(this).val() );
        });
    });
</script>