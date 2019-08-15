<form method="POST" action="{$router->getUrl('users-front-auth', ["Act" => "recover"])}" class="auth_box_2 authorization formStyle">
    {$this_controller->myBlockIdInput()}
    <!-- {if $url->request('dialogWrap', $smarty.const.TYPE_INTEGER)} -->
        <div class="auth_box_3">
            <p class="auth_title_2" data-dialog-options='{ "width": "300" }'>{t}Восстановление пароля{/t}</p> 
        </div>
        <div class="auth_box_4">
            {if !empty($error)}<p class="auth_error_2 error">{$error}</p>{/if}
            {if $send_success}
                <p class="auth_success success">{t}Письмо успешно отправлено. Следуйте инструкциям в письме{/t}</p>
            {else}
                <p class="auth_text_1">{t}На указанный E-mail будет отправлено письмо с дальнейшими инструкциями по восстановлению пароля{/t}</p>
                <input type="text" size="30" name="login" value="{$data.login}" placeholder="e-mail" class="auth_input login inp" value="{$data.login}" {if $send_success}readonly{/if}>
                <div class="floatWrap auth_box_5">
                    <input class="auth_submit" type="submit" value="{t}Отправить{/t}">
                </div>
            {/if}
        </div>
    <!-- {else}
        {if $send_success}
            <div class="formSuccessText">E-mail: {$data.login}<br>
                {t}Письмо успешно отправлено. Следуйте инструкциям в письме{/t}</div>
        {else}
            <table class="formTable">
                <tr>
                    <td class="key">E-mail</td>
                    <td class="value"><input type="text" size="30" name="login" value="{$data.login}" {if !empty($error)}class="has-error"{/if}>
                        <span class="formFieldError">{$error}</span>
                        <div class="help">{t}На указанный E-mail будет отправлено письмо с дальнейшими инструкциями по восстановлению пароля{/t}</div>
                    </td>
                </tr>
            </table>
            <input type="submit" value="{t}Отправить{/t}">
        {/if}
    {/if} -->
</form>