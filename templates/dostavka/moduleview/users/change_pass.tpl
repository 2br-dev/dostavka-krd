<form method="POST" class="authorization formStyle">
    <h1>{t}Восстановление пароля{/t}</h1>
    <div class="forms">
        <div class="center">
            <div class="formLine">
                <label class="fieldName">{t}Новый пароль{/t}</label>
                <input type="password" size="30" name="new_pass" class="inp{if !empty($error)} has-error{/if}">
                <span class="formFieldError">{$error}</span>
            </div>
            <div class="formLine">
                <label class="fieldName">{t}Повтор нового пароля{/t}</label>
                <input type="password" size="30" name="new_pass_confirm" class="inp">
            </div>            
            <input type="submit" value="{t}Сменить пароль{/t}">
        </div>
    </div>
</form>