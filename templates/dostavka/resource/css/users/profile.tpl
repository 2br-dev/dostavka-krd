

<form method="POST" class="formStyle profile lk">
    {csrf}
    {$this_controller->myBlockIdInput()}
    <!--  -->
    <div class="lk__head userType">
        <a class="lk__head_btn {if !$user.is_company} lk__head_btn_active{/if}" more="1">{t}Частное лицо{/t}</a>
        <a class="lk__head_btn {if $user.is_company} lk__head_btn_active{/if}" more="2">{t}Компания{/t}</a>
    </div>
    <div class="userType" style="display: none">
        <input type="radio" id="ut_user" name="is_company" value="0" {if !$user.is_company}checked{/if}></label>
        <input type="radio" id="ut_company" name="is_company" value="1" {if $user.is_company}checked{/if}></label>
    </div>  
    <!--  -->
    {if count($user->getNonFormErrors())>0}
        <div class="pageError">
            {foreach $user->getNonFormErrors() as $item}
            <p>{$item}</p>
            {/foreach}
        </div>
    {/if}    
    <!--  -->
    {if $result}
        <div class="formResult success">{$result}</div>
    {/if} 
    <!--  -->
    <div class="lk__item">
        <!--  -->
        <div class="lk__item-more {if $user.is_company}lk__item-more_active{/if}"  id="more">
            <div class="formLine">
                <label class="fieldName">{t}Название организации{/t}</label>
                {$user->getPropertyView('company')}
            </div>                            
            <div class="formLine">
                <label class="fieldName">{t}ИНН{/t}</label>
                {$user->getPropertyView('company_inn')}
            </div> 
        </div>
        <!--  -->
        <div class="formLine"> 
            <label class="fieldName">{t}Фамилия{/t}</label>
            {$user->getPropertyView('surname')}
        </div>                    
        <div class="formLine">
            <label class="fieldName">{t}Имя{/t}</label>
            {$user->getPropertyView('name')}
        </div>
        <div class="formLine">
            <label class="fieldName">{t}Отчество{/t}</label>
            {$user->getPropertyView('midname')}
        </div>
        <div class="formLine">
            <label class="fieldName">{t}Телефон{/t}</label>
            {$user->getPropertyView('phone')}
        </div>
        <div class="formLine">
            <label class="fieldName">E-mail</label>
            {$user->getPropertyView('e_mail')}
        </div>
        <!--  -->
        {if $conf_userfields->notEmpty()}
            {foreach $conf_userfields->getStructure() as $fld}
            <div class="formLine">
            <label class="fieldName">{$fld.title}</label>
                {$conf_userfields->getForm($fld.alias)}
                {$errname=$conf_userfields->getErrorForm($fld.alias)}
                {$error=$user->getErrorsByForm($errname, ', ')}
                {if !empty($error)}
                    <span class="formFieldError">{$error}</span>
                {/if}
            </div>
            {/foreach}
        {/if}    
        <!--  -->
        <div class="lk__pass-box">
            <div class="formLine">
                <label class="fieldName">{t}Текущий пароль{/t}</label>
                <input autocomplete="off" type="password" name="current_pass" {if count($user->getErrorsByForm('current_pass'))}class="has-error"{/if} value="" >
                <span class="formFieldError">{$user->getErrorsByForm('current_pass', ',')}</span>
            </div>
            <div class="formLine">
                <label class="fieldName">{t}Новый пароль{/t}</label>
                <input type="password" name="openpass" {if count($user->getErrorsByForm('openpass'))}class="has-error"{/if}>
                <span class="formFieldError">{$user->getErrorsByForm('openpass', ',')}</span>
            </div>                        
            <div class="formLine">
                <label class="fieldName">{t}Повторить пароль{/t}</label>
                <input type="password" name="openpass_confirm" {if count($user->getErrorsByForm('openpass'))}class="has-error"{/if}>
            </div>
        </div>
        <!--  -->
        <div class="buttons lk__btn-box">
            <input class="lk__btn-submit" type="submit" value="{t}Сохранить{/t}">
        </div>
    </div>
    <!--  -->
    <!--  -->
    <!--  -->
    
</form>    
<script type="text/javascript">
    $(function() {
        $('#changePass').change(function() {
            $('.changePass').toggleClass('hidden', !this.checked);
        });            
        
        $('.profile .userType input').click(function() {
            $('#fieldsBlock').toggleClass('thiscompany', $(this).val() == 1);
        });
        
    });        
</script>    

<script>
$(document).ready(() => {
    $(document).on('click', '.lk__head_btn', function() {
        $('.lk__head_btn').removeClass('lk__head_btn_active')
        if($(this).attr('more') == 1) {
            $('#more').removeClass('lk__item-more_active')
            $('#ut_user').prop( "checked", true );
        } else if($(this).attr('more') == 2) {
            $('#more').addClass('lk__item-more_active')
             $('#ut_company').prop( "checked", true );
        }
        $(this).addClass('lk__head_btn_active')
    })
})

</script>