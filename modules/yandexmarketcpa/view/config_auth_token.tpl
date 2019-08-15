{$oauth_url="https://oauth.yandex.ru/authorize?response_type=code&client_id="}
<p>
    <a target="_blank" href="{$oauth_url}{$elem->getYandexAppId()}" class="u-link">{t}Получите код подтверждения{/t}</a>, {t}затем введите его в поле ниже и нажмите <i>Получить oAuth токен</i>{/t}
</p>
<p>
    <input type="text" size="20" placeholder="Код подтверждения" id="oAuthCode">&nbsp;&nbsp; <a class="u-link" data-href="{adminUrl do="GetOauthTokenByCode" mod_controller="yandexmarketcpa-tools"}" id="getOauthToken">{t}получить oAuth токен{/t}</a>
</p>
{include file=$elem.__ytoken->getOriginalTemplate() field=$elem.__ytoken}

<p class="f-11 c-gray">{t}Доступ выдаётся на 1 год с момента разрешения.{/t}</p>


<script type="text/javascript">
    $("#getOauthToken").on('click', function(){
        var oauth_code = $('#oAuthCode').val();

        if (!oauth_code) {
            $.messenger(lang.t('Необходимо заполнить поле код подтверждения'));
        } else {
            $.ajaxQuery({
                url: $(this).data('href'),
                data: {
                    oauth_code: oauth_code
                },
                success: function (response) {
                    if (response.success) {
                        $('[name="ytoken"]').val(response.data.access_token);
                        $('[name="ytoken_expire"]').val(response.data.expire);
                    }
                }
            });
        }
    });
</script>