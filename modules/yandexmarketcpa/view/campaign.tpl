<select name="campaign_id" class="campaignSelect">
    {if $elem.campaign_id}
        <option value="{$elem.campaign_id}" selected>{$elem.campaign_id}</option>
    {else}
        <option value="">{t}-- Нет кампаний --{/t}</option>
    {/if}
</select>
&nbsp;<a class="u-link updateCampaignList" data-url="{adminUrl do="getCampaigns" mod_controller="yandexmarketcpa-tools"}">{t}обновить список кампаний{/t}</a>
<script>
    $(function() {
        $('.updateCampaignList').on('click', function() {
            var data = {
                oauth_token: $('[name="ytoken"]').val(),
            };

            if (!data.oauth_token) {
                $.messenger(lang.t('Необходимо заполнить поле oAuth токен'));
                return false;
            }

            $.ajaxQuery({
                url: $(this).data('url'),
                data: data,
                success: function(response) {
                    var select = $('.campaignSelect');
                    var current_value = select.val();
                    select.empty();
                    select.append($('<option />').attr('value', '').text(lang.t('-- Не выбрано --')));

                    $.each(response.campaigns, function(key, val) {
                        select.append( $('<option />').attr('value', val).text(val) );
                    });

                    select.val(current_value);
                }
            });
        });
    });
</script>