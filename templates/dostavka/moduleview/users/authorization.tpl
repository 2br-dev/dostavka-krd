<!--  -->

<form method="POST" action="{$router->getUrl('users-front-auth')}" class="auth_body authorization formStyle">
    <div class="auth_head">
        <p class="auth_title">{t}Авторизация{/t}</p>
        <a href="{$router->getUrl('users-front-register')}" class="auth_btn_1 inDialog">{t}Зарегистрироваться{/t}</a>
    </div>
    <div class="auth_form">
        {$this_controller->myBlockIdInput()}
        {hook name="users-authorization:form" title="{t}Авторизация:форма{/t}"}
        <input type="hidden" name="referer" value="{$data.referer}">
        <!--  -->
        <div class="auth_row">
            <input type="text" class="auth_input inp" placeholder="E-mail" size="30" name="login" value="{$data.login|default:$Setup.DEFAULT_DEMO_LOGIN}">
        </div>
        <!--  -->
        <div class="auth_row">
            <input type="password" class="auth_input inp" placeholder="Пароль" type="password" size="30" name="pass" value="{$Setup.DEFAULT_DEMO_PASS}">
        </div>
        <!--  -->
        <div class="auth_row rem">
            <input class="auth_cb" type="checkbox" id="rememberMe" name="remember" value="1" {if $data.remember}checked{/if}>
            <label class="auth_label" for="rememberMe">{t}Запомнить меня{/t}</label>
        </div>
        <!--  -->
        <div class="auth_row_2">
            <input class="auth_submit" type="submit" value="{t}Войти{/t}">
            <a href="{$router->getUrl('users-front-auth', ["Act" => "recover"])}" class="auth_btn_1 recover inDialog">{t}Забыли пароль?{/t}</a>
        </div>
        <!--  -->
        <div class="auth_row">
            {if !empty($status_message)}<p class="auth_error">{$status_message}</p>{/if}
            {if !empty($error)}<p class="auth_error">{$error}</p>{/if}
        </div>
        <!--  -->
        {/hook}
    </div>
</form>
