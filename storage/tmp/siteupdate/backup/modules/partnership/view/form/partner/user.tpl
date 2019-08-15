<input name="is_reg_user" type="radio" value="0" id="link-user" {if !$elem.is_reg_user}checked{/if}><label for="link-user">{t}Связать с зарегистрированным пользователем{/t}</label><br>
<input name="is_reg_user" type="radio" value="1" id="reg-user" {if $elem.is_reg_user}checked{/if}><label for="reg-user">{t}Зарегистрировать нового пользователя{/t}</label><br>
<br>
<div id="partner-link-user" class="reg-tab">
    {include file=$field->getOriginalTemplate()}<br>
</div>
<div id="partner-reg-user" class="reg-tab" {if !$elem.is_reg_user}style="display:none"{/if}>
    <table class="otable">
        <tr>
            <td class="otitle">{$elem.__reg_user_name->getTitle()}</td>
            <td>{include file=$elem.__reg_user_name->getRenderTemplate() field=$elem.__reg_user_name}</td>
        </tr>    
        <tr>
            <td class="otitle">{$elem.__reg_user_surname->getTitle()}</td>
            <td>{include file=$elem.__reg_user_surname->getRenderTemplate() field=$elem.__reg_user_surname}</td>
        </tr>    
        <tr>
            <td class="otitle">{$elem.__reg_user_phone->getTitle()}</td>
            <td>{include file=$elem.__reg_user_phone->getRenderTemplate() field=$elem.__reg_user_phone}</td>
        </tr>        
        <tr>
            <td class="otitle">{$elem.__reg_user_e_mail->getTitle()}</td>
            <td>{include file=$elem.__reg_user_e_mail->getRenderTemplate() field=$elem.__reg_user_e_mail}</td>
        </tr>        
        <tr>
            <td class="otitle">{$elem.__reg_user_openpass->getTitle()}</td>
            <td>{include file=$elem.__reg_user_openpass->getRenderTemplate() field=$elem.__reg_user_openpass}</td>
        </tr>            
    </table>
</div>
<script>
    $(function() {
        var regChange = function() {
            var value = $('input[name="is_reg_user"]:checked');
            $('.reg-tab').hide();
            $('#partner-'+value.attr('id')).show();
        }
        $('input[name="is_reg_user"]').change(regChange);
        regChange();
    });
</script>